#!/usr/bin/env node
/**
 * 页面路由校验
 * ---------------------------------------------------------------
 * 小程序里 navigateTo 一个不存在的路径不会报错，只会静默失败。
 * 这个脚本扫描源码中所有页面路径字面量，与 pages.json 注册表比对。
 *
 * 检查项：
 *   1. pages.json 里注册的页面文件必须真实存在（否则构建产物缺页）
 *   2. 源码里引用的页面路径必须已注册（否则点击无反应）
 *   3. tabBar 页面必须出现在 pages 数组中
 *
 * 用法：
 *   node scripts/check-pages.mjs
 */

import { readFileSync, readdirSync, statSync, existsSync } from 'node:fs'
import { dirname, join, resolve, relative } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const ROOT = resolve(__dirname, '..')
const SRC = join(ROOT, 'src')

/** 去掉 pages.json 里的 // 与 /* *\/ 注释（uni-app 允许注释） */
function stripJsonComments(text) {
    let out = ''
    let inString = false
    let inLine = false
    let inBlock = false

    for (let i = 0; i < text.length; i += 1) {
        const ch = text[i]
        const next = text[i + 1]

        if (inLine) {
            if (ch === '\n') {
                inLine = false
                out += ch
            }
            continue
        }
        if (inBlock) {
            if (ch === '*' && next === '/') {
                inBlock = false
                i += 1
            }
            continue
        }
        if (inString) {
            out += ch
            if (ch === '\\') {
                out += next
                i += 1
            } else if (ch === '"') {
                inString = false
            }
            continue
        }
        if (ch === '"') {
            inString = true
            out += ch
            continue
        }
        if (ch === '/' && next === '/') {
            inLine = true
            i += 1
            continue
        }
        if (ch === '/' && next === '*') {
            inBlock = true
            i += 1
            continue
        }
        out += ch
    }
    return out
}

const pagesJsonPath = join(SRC, 'pages.json')
if (!existsSync(pagesJsonPath)) {
    console.error('找不到 src/pages.json')
    process.exit(1)
}

const config = JSON.parse(stripJsonComments(readFileSync(pagesJsonPath, 'utf8')))

/** 注册表：'/pages/index/index' -> 绝对文件路径 */
const registered = new Map()

for (const page of config.pages || []) {
    const route = '/' + page.path
    registered.set(route, join(SRC, page.path + '.vue'))
}

for (const sub of config.subPackages || []) {
    for (const page of sub.pages || []) {
        const route = '/' + sub.root + '/' + page.path
        registered.set(route, join(SRC, sub.root, page.path + '.vue'))
    }
}

const errors = []

// 1. 注册的页面文件必须存在
for (const [route, file] of registered) {
    if (!existsSync(file)) {
        errors.push(`注册了但文件不存在：${route} → ${relative(ROOT, file)}`)
    }
}

// 2. tabBar 页面必须已注册
const tabList = config.tabBar?.list || []
for (const tab of tabList) {
    if (!registered.has('/' + tab.pagePath)) {
        errors.push(`tabBar 页面未在 pages 中注册：${tab.pagePath}`)
    }
}

// 2.1 preloadRule 必须指向已注册页面与真实分包 root
// 微信要求 packages 填 subPackages[].root 全路径（如 packages/pkg-order），
// 填成目录名（pkg-order）编译期不报错，但开发者工具会报「未找到」并导致预下载失效。
const subRoots = new Set((config.subPackages || []).map((s) => s.root))
for (const [pagePath, rule] of Object.entries(config.preloadRule || {})) {
    if (!registered.has('/' + pagePath)) {
        errors.push(`preloadRule 指向未注册页面：${pagePath}`)
    }
    for (const pkg of rule?.packages || []) {
        if (pkg === 'all') continue
        if (!subRoots.has(pkg)) {
            errors.push(
                `preloadRule["${pagePath}"].packages 含无效分包 "${pkg}"，` +
                    `应为 subPackages[].root 全路径。可选：${[...subRoots].join(' / ') || '（无分包）'}`
            )
        }
    }
}

// 3. 源码引用的路径必须已注册
const SOURCE_EXTS = ['.vue', '.ts', '.js']
const SKIP_DIRS = new Set(['node_modules', 'dist', 'uni_modules', 'unpackage', 'static'])

function walk(dir, files = []) {
    let entries
    try {
        entries = readdirSync(dir)
    } catch {
        return files
    }
    for (const entry of entries) {
        if (SKIP_DIRS.has(entry)) continue
        const full = join(dir, entry)
        const stat = statSync(full)
        if (stat.isDirectory()) walk(full, files)
        else if (SOURCE_EXTS.includes(full.slice(full.lastIndexOf('.')))) files.push(full)
    }
    return files
}

/** 匹配 '/pages/xxx' 或 '/packages/xxx' 形式的页面路径 */
const PATH_RE = /['"`](\/(?:pages|packages)\/[A-Za-z0-9_\-/]+)['"`]/g

/** 行内出现该标记时跳过（用于历史路径映射表的 key 侧） */
const IGNORE_MARK = 'route-ignore'

const referenced = new Map() // route -> Set(file)
for (const file of walk(SRC)) {
    const content = readFileSync(file, 'utf8')
    for (const line of content.split(/\r?\n/)) {
        if (line.includes(IGNORE_MARK)) continue
        PATH_RE.lastIndex = 0
        let m
        while ((m = PATH_RE.exec(line)) !== null) {
            const route = m[1]
            // 只看像页面路径的（至少 3 段），排除资源目录
            if (route.split('/').filter(Boolean).length < 3) continue
            if (!referenced.has(route)) referenced.set(route, new Set())
            referenced.get(route).add(relative(ROOT, file).split('\\').join('/'))
        }
    }
}

for (const [route, files] of referenced) {
    if (!registered.has(route)) {
        errors.push(`引用了未注册的页面：${route}\n     出现在：${[...files].join(', ')}`)
    }
}

// 4. 静态资源引用必须存在
// <image src="/static/x.png"> 指向不存在的文件不会报错，只会显示空白，
// 属于「上线后才发现」的典型问题。
const STATIC_RE = /['"`](\/static\/[A-Za-z0-9_\-./]+)['"`]/g
const missingStatic = new Map()
for (const file of walk(SRC)) {
    const content = readFileSync(file, 'utf8')
    for (const line of content.split(/\r?\n/)) {
        if (line.includes(IGNORE_MARK)) continue
        STATIC_RE.lastIndex = 0
        let sm
        while ((sm = STATIC_RE.exec(line)) !== null) {
            const assetPath = sm[1]
            if (!existsSync(join(SRC, assetPath))) {
                if (!missingStatic.has(assetPath)) missingStatic.set(assetPath, new Set())
                missingStatic.get(assetPath).add(relative(ROOT, file).split('\\').join('/'))
            }
        }
    }
}
for (const [asset, files] of missingStatic) {
    errors.push(`静态资源不存在：${asset}\n     被引用：${[...files].join(', ')}`)
}

// 5. 图标名必须真实存在（uview 或图鸟两套之一）
// <u-icon name="xxx"> 写错名字不会报错，只会渲染成空白/方块，属于上线才发现的问题。
const ICON_CSS = join(SRC, 'uni_modules/vk-uview-ui/iconfont.css')
if (existsSync(ICON_CSS)) {
    const iconCss = readFileSync(ICON_CSS, 'utf8')
    const validIcons = new Set()
    const iconRe = /\.uicon-([a-z0-9-]+)/g
    let im
    while ((im = iconRe.exec(iconCss)) !== null) validIcons.add(im[1])

    /** 模板里静态写的 name="xxx" / :name="'xxx'" */
    const TPL_ICON = /<u-icon\b[^>]*?(?::name="'([a-z0-9-]+)'"|(?<![:\w-])name="([a-z0-9-]+)")/g
    /** 脚本里 icon: 'xxx' / iconName: 'xxx' 这类图标映射表 */
    const SCRIPT_ICON = /\b(?:icon|iconName)\s*:\s*'([a-z][a-z0-9-]*)'/g
    /** uni.showToast / showModal 的 icon 取值，不是 uview 图标名 */
    const UNI_ICON_VALUES = new Set(['none', 'success', 'error', 'fail', 'exception', 'loading'])

    // 图鸟图标集（v2 决策 D6：全站图标统一 tn-icon）。名字只要在 uview 或图鸟任一套里存在就放行，
    // 两套都不存在的才报错 —— 否则每个迁移到 <tn-icon> 的页面都会被 icon 映射表误伤。
    const TN_ICON_CSS = join(ROOT, 'node_modules/@tuniao/tn-icon/dist/index.css')
    const validTnIcons = new Set()
    if (existsSync(TN_ICON_CSS)) {
        const tnCss = readFileSync(TN_ICON_CSS, 'utf8')
        const tnRe = /\.tn-icon-([a-z0-9-]+):before/g
        let im2
        while ((im2 = tnRe.exec(tnCss)) !== null) validTnIcons.add(im2[1])
    }

    const badIcons = new Map()
    const recordBad = (name, file) => {
        if (UNI_ICON_VALUES.has(name)) return
        if (validIcons.has(name)) return
        if (validTnIcons.has(name)) return
        if (!badIcons.has(name)) badIcons.set(name, new Set())
        badIcons.get(name).add(file)
    }

    for (const file of walk(SRC)) {
        const content = readFileSync(file, 'utf8')
        const rel = relative(ROOT, file).split('\\').join('/')

        TPL_ICON.lastIndex = 0
        let tm
        while ((tm = TPL_ICON.exec(content)) !== null) {
            recordBad(tm[1] || tm[2], rel)
        }

        SCRIPT_ICON.lastIndex = 0
        let sm2
        while ((sm2 = SCRIPT_ICON.exec(content)) !== null) {
            recordBad(sm2[1], rel)
        }
    }

    for (const [name, files] of badIcons) {
        errors.push(
            `uview 图标不存在：uicon-${name}（会渲染为空白）\n     被引用：${[...files].join(', ')}`
        )
    }
}

// 6. 反向提示：注册了但没人引用（仅提示，不阻断）
const unused = [...registered.keys()].filter((route) => !referenced.has(route))

if (errors.length) {
    console.error('页面路由校验未通过：')
    for (const e of errors) console.error(' - ' + e)
    process.exit(1)
}

console.log(
    `页面路由校验通过：注册 ${registered.size} 页 / 引用 ${referenced.size} 个路径` +
        (unused.length ? `（${unused.length} 页仅通过 tabBar 或代码拼接进入）` : '')
)
