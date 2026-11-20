#!/usr/bin/env node
/**
 * 装修契约一致性校验
 * ---------------------------------------------------------------
 * 三端各有一份「装修数据模型」：
 *   shared/contracts/decorate.ts            ← 唯一事实源（TS）
 *   server/.../SplashAdDecorateService.php  ← 服务端镜像（PHP）
 *   server/.../IndexLogic.php + DecoratePageLogic.php ← 首页组件白名单
 *
 * PHP 不能 import TS，所以只能靠这个脚本在 CI / 本地把两边对一遍。
 * 检查项：
 *   1. 开屏广告字段：TS `SplashAdContent` 与 PHP `normalizeContent()` 的键集合必须相等
 *   2. 首页组件白名单：TS `DECORATE_PAGES[home].widgets` 与 PHP 两处 HOME_WIDGET_ORDER 必须相等
 *   3. 开屏页 ID：TS 与 PHP 的 PAGE_ID 必须相等
 *   4. 首页 5 个组件字段：TS 接口与 `HomeDecorateService` 的返回键必须相等
 *   5. 首页组件显示名：TS `HOME_WIDGET_TITLES` 与 PHP `WIDGET_TITLES` 必须相等
 *
 * 用法：node scripts/check-decorate-contract.mjs
 */

import { readFileSync } from 'node:fs'
import { dirname, join, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const ROOT = resolve(__dirname, '..', '..')

const TS_CONTRACT = join(ROOT, 'shared/contracts/decorate.ts')
const PAGES_JSON = join(ROOT, 'newuniapp/src/pages.json')
const PHP_SPLASH = join(ROOT, 'server/app/common/service/SplashAdDecorateService.php')
const PHP_INDEX = join(ROOT, 'server/app/api/logic/IndexLogic.php')
const PHP_PAGE = join(ROOT, 'server/app/adminapi/logic/decorate/DecoratePageLogic.php')
const PHP_HOME = join(ROOT, 'server/app/common/service/HomeDecorateService.php')

const errors = []

function read(path) {
    try {
        return readFileSync(path, 'utf8')
    } catch {
        errors.push(`读不到文件：${path}`)
        return ''
    }
}

const ts = read(TS_CONTRACT)
const pagesJson = read(PAGES_JSON)
const phpSplash = read(PHP_SPLASH)
const phpIndex = read(PHP_INDEX)
const phpPage = read(PHP_PAGE)
const phpHome = read(PHP_HOME)

/** 取 TS 接口体里的字段名 */
function tsInterfaceKeys(source, interfaceName) {
    const start = source.indexOf(`export interface ${interfaceName}`)
    if (start < 0) return []
    const open = source.indexOf('{', start)
    let depth = 0
    let end = open
    for (let i = open; i < source.length; i += 1) {
        if (source[i] === '{') depth += 1
        else if (source[i] === '}') {
            depth -= 1
            if (depth === 0) {
                end = i
                break
            }
        }
    }
    const body = source.slice(open + 1, end)
    return [...body.matchAll(/^\s{4}([a-z_][a-z0-9_]*)\??:/gm)].map((m) => m[1])
}

/** 取 PHP 函数体里 `'key' =>` 形式的返回键 */
function phpReturnKeys(source, fnName, nextMarker) {
    const start = source.indexOf(`function ${fnName}`)
    if (start < 0) return []
    const end = nextMarker ? source.indexOf(nextMarker, start) : source.length
    const body = source.slice(start, end < 0 ? source.length : end)
    const keys = [...body.matchAll(/^\s+'([a-z_][a-z0-9_]*)'\s*=>/gm)].map((m) => m[1])
    return [...new Set(keys)]
}

/** 取 PHP 常量数组里的字符串项 */
function phpConstList(source, constName) {
    const start = source.indexOf(`const ${constName}`)
    if (start < 0) return []
    const open = source.indexOf('[', start)
    const close = source.indexOf('];', open)
    const body = source.slice(open, close < 0 ? source.length : close)
    return [...body.matchAll(/'([a-z0-9_-]+)'/g)].map((m) => m[1])
}

/** 取 TS 里某个页面定义的 widgets 列表 */
function tsPageWidgets(source, pageKey) {
    const start = source.indexOf(`key: '${pageKey}'`)
    if (start < 0) return []
    const open = source.indexOf('widgets: [', start)
    const close = source.indexOf(']', open)
    const body = source.slice(open, close)
    return [...body.matchAll(/'([a-z0-9-]+)'/g)].map((m) => m[1])
}

/** 取 TS 常量对象里的 `key: '中文值'` */
function tsStringMap(source, constName) {
    const start = source.indexOf(`export const ${constName}`)
    if (start < 0) return {}
    const open = source.indexOf('{', start)
    const close = source.indexOf('\n}', open)
    const body = source.slice(open, close < 0 ? source.length : close)
    const map = {}
    for (const match of body.matchAll(/(?:'([a-z0-9-]+)'|([a-z0-9-]+))\s*:\s*'([^']+)'/g)) {
        map[match[1] || match[2]] = match[3]
    }
    return map
}

/** 取 PHP 常量数组里的 `'key' => '中文值'` */
function phpStringMap(source, constName) {
    const start = source.indexOf(`const ${constName}`)
    if (start < 0) return {}
    const open = source.indexOf('[', start)
    const close = source.indexOf('];', open)
    const body = source.slice(open, close < 0 ? source.length : close)
    const map = {}
    for (const match of body.matchAll(/'([a-z0-9_-]+)'\s*=>\s*'([^']+)'/g)) {
        map[match[1]] = match[2]
    }
    return map
}

function diffMaps(name, tsMap, phpMap) {
    const keys = new Set([...Object.keys(tsMap), ...Object.keys(phpMap)])
    const bad = [...keys].filter((key) => tsMap[key] !== phpMap[key])
    if (bad.length) {
        errors.push(
            `${name} 不一致：${bad
                .map((key) => `${key}（TS=${tsMap[key] || '-'} / PHP=${phpMap[key] || '-'}）`)
                .join('、')}`
        )
    }
}

function diff(name, tsList, phpList) {
    const a = new Set(tsList)
    const b = new Set(phpList)
    const onlyTs = [...a].filter((x) => !b.has(x))
    const onlyPhp = [...b].filter((x) => !a.has(x))
    if (onlyTs.length || onlyPhp.length) {
        errors.push(
            `${name} 不一致：\n  TS 独有 [${onlyTs.join(', ') || '-'}]\n  PHP 独有 [${onlyPhp.join(', ') || '-'}]`
        )
    }
}

/* 1. 开屏广告字段 */
const tsSplashKeys = tsInterfaceKeys(ts, 'SplashAdContent')
const phpSplashKeys = phpReturnKeys(
    phpSplash,
    'normalizeContent',
    'private static function normalizeLink'
)
diff('开屏广告字段（SplashAdContent）', tsSplashKeys, phpSplashKeys)

/* 2. 首页组件白名单 */
const tsHomeWidgets = tsPageWidgets(ts, 'home')
diff('首页组件（IndexLogic::HOME_WIDGET_ORDER）', tsHomeWidgets, phpConstList(phpIndex, 'HOME_WIDGET_ORDER'))
diff('首页组件（DecoratePageLogic::HOME_WIDGET_ORDER）', tsHomeWidgets, phpConstList(phpPage, 'HOME_WIDGET_ORDER'))

/* 2.1 首页 5 个组件字段（TS 接口 ↔ HomeDecorateService 归一化返回键） */
const HOME_FIELD_PAIRS = [
    ['首屏主图内容（HomeHeroContent）', 'HomeHeroContent', 'normalizeHeroContent', 'private static function normalizeHeroItem'],
    ['首屏主图数据项（HomeHeroItem）', 'HomeHeroItem', 'normalizeHeroItem', 'private static function buildDefaultBanner'],
    ['数据条内容（HomeBrandContent）', 'HomeBrandContent', 'normalizeBrandContent', 'private static function normalizeBrandStat'],
    ['数据条统计项（HomeBrandStat）', 'HomeBrandStat', 'normalizeBrandStat', 'private static function buildDefaultBrand'],
    ['四宫格内容（HomeServiceCategoriesContent）', 'HomeServiceCategoriesContent', 'normalizeCategoriesContent', 'private static function normalizeCategoryItem'],
    ['四宫格入口项（HomeServiceCategoryItem）', 'HomeServiceCategoryItem', 'normalizeCategoryItem', 'private static function buildDefaultCategories'],
    ['服务承诺内容（HomeFlowContent）', 'HomeFlowContent', 'normalizeFlowContent', 'private static function normalizeFlowItem'],
    ['服务承诺步骤（HomeFlowItem）', 'HomeFlowItem', 'normalizeFlowItem', 'private static function buildDefaultFlow'],
    ['首页弹窗内容（HomePopupAdContent）', 'HomePopupAdContent', 'normalizePopupContent', 'private static function buildDefaultPopup']
]

for (const [label, tsName, phpFn, nextMarker] of HOME_FIELD_PAIRS) {
    diff(label, tsInterfaceKeys(ts, tsName), phpReturnKeys(phpHome, phpFn, nextMarker))
}

/* 2.2 首页组件显示名 */
diffMaps(
    '首页组件显示名（HOME_WIDGET_TITLES）',
    tsStringMap(ts, 'HOME_WIDGET_TITLES'),
    phpStringMap(phpHome, 'WIDGET_TITLES')
)

/* 3. 开屏页 ID */
// DECORATE_PAGES 里字段顺序是 id → type → key，所以从 key 往前找**最近**的一个 id
const splashKeyAt = ts.indexOf("key: 'splash'")
const idMatches = splashKeyAt > 0 ? [...ts.slice(0, splashKeyAt).matchAll(/id: (\d+)/g)] : []
const tsSplashId = idMatches.length ? Number(idMatches[idMatches.length - 1][1]) : NaN
const phpSplashId = Number((phpSplash.match(/PAGE_ID = (\d+)/) || [])[1])
if (tsSplashId && phpSplashId && tsSplashId !== phpSplashId) {
    errors.push(`开屏页 ID 不一致：TS=${tsSplashId} / PHP=${phpSplashId}`)
}

/* 4. 预览路由必须已在小程序注册，且每个 live 页都要有 */
const registeredRoutes = new Set(
    [...pagesJson.matchAll(/"path"\s*:\s*"([^"]+)"/g)].map((m) => `/${m[1]}`)
)
const previewRoutes = [...ts.matchAll(/previewRoute:\s*'([^']+)'/g)].map((m) => m[1])
// 只统计对象字面量里的 `status: 'live',`；类型定义那行是 `'live' | 'pending'`，不会被匹配
const livePageCount = (ts.match(/status: 'live',/g) || []).length

if (previewRoutes.length !== livePageCount) {
    errors.push(
        `预览路由数量与 live 页面不一致：live=${livePageCount} / previewRoute=${previewRoutes.length}`
    )
}
for (const route of previewRoutes) {
    if (!registeredRoutes.has(route)) {
        errors.push(`预览路由未在 pages.json 注册：${route}`)
    }
}

if (errors.length) {
    console.error('装修契约校验失败：\n')
    errors.forEach((e) => console.error(` - ${e}\n`))
    process.exit(1)
}

console.log(
    `装修契约校验通过：开屏字段 ${tsSplashKeys.length} 个 / 首页组件 ${tsHomeWidgets.length} 个 / 开屏页 ID ${tsSplashId} / 预览路由 ${previewRoutes.length} 条`
)
