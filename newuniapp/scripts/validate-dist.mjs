#!/usr/bin/env node
/**
 * 构建产物 app.json 引用完整性校验
 * ---------------------------------------------------------------
 * 微信开发者工具读的是 dist/build/mp-weixin/app.json。
 * 这里的错误在 `uni build` 阶段**不会报**，只有打开开发者工具才暴露：
 *   - preloadRule.packages 填了目录名而不是 subPackages[].root 全路径 → 「未找到」
 *   - pages 里登记了页面但产物缺 .wxml/.js/.json/.wxss
 *   - tabBar 的 iconPath 指向不存在的图片
 *   - tabBar.pagePath 不在 pages 中
 *
 * 必须在 build 之后执行：
 *   node scripts/validate-dist.mjs
 */

import { existsSync, readdirSync, readFileSync, statSync } from 'node:fs'
import { dirname, join, resolve, sep } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const ROOT = resolve(__dirname, '..')
const DIST = join(ROOT, 'dist/build/mp-weixin')

if (!existsSync(DIST)) {
    console.error(`找不到构建产物：${DIST}\n请先执行 npm run build:mp-weixin`)
    process.exit(1)
}

const appJsonPath = join(DIST, 'app.json')
if (!existsSync(appJsonPath)) {
    console.error('找不到 app.json')
    process.exit(1)
}

const app = JSON.parse(readFileSync(appJsonPath, 'utf8'))
const errors = []
const PAGE_EXTS = ['.js', '.json', '.wxml', '.wxss']

const exists = (rel) => existsSync(join(DIST, rel))

// 1. 主包页面四件套齐全
for (const page of app.pages || []) {
    for (const ext of PAGE_EXTS) {
        if (!exists(page + ext)) errors.push(`主包缺文件：${page}${ext}`)
    }
}

// 2. 分包页面
const subRoots = new Set()
for (const sub of app.subPackages || []) {
    subRoots.add(sub.root)
    for (const page of sub.pages || []) {
        const rel = `${sub.root}/${page}`
        if (!exists(rel + '.wxml')) errors.push(`分包缺页面：${rel}`)
    }
}

// 3. tabBar
const pageSet = new Set(app.pages || [])
for (const tab of app.tabBar?.list || []) {
    if (!pageSet.has(tab.pagePath)) {
        errors.push(`tabBar 页面未在 pages 中：${tab.pagePath}`)
    }
    for (const key of ['iconPath', 'selectedIconPath']) {
        const value = tab[key]
        if (value && !exists(value)) errors.push(`tabBar 缺图标：${value}`)
    }
}

// 4. preloadRule —— 最容易踩的一个
for (const [pagePath, rule] of Object.entries(app.preloadRule || {})) {
    if (!pageSet.has(pagePath)) {
        errors.push(`preloadRule 指向未注册页面：${pagePath}`)
    }
    for (const pkg of rule?.packages || []) {
        if (pkg === 'all') continue
        if (!subRoots.has(pkg)) {
            errors.push(
                `preloadRule["${pagePath}"].packages 含无效分包 "${pkg}"，` +
                    `必须为 subPackages[].root 全路径。可选：${[...subRoots].join(' / ') || '（无分包）'}`
            )
        }
    }
}

if (errors.length) {
    console.error('产物 app.json 校验未通过：')
    for (const e of errors) console.error(' - ' + e)
    process.exit(1)
}

/**
 * 样式产物检查（两级）。
 *
 * 为什么要有：源码里的 `<style scoped>` 完全合法、编译不报错、模板和 js 都正常，
 * 但产物 `.wxss` 可以是 0 字节 —— 组件在真机上就是"裸的"（无定位、无背景、无动画）。
 * 这类问题肉眼验收发现不了，只能靠产物字节数判定。
 *
 * 首个实例（已修复）：`components/app-toast/app-toast.wxss` 曾长期为 0，全站 Toast 无样式。
 * 实测根因：小程序端 `App.vue` 模板里挂了该组件，uni-app 把它编到「App 根（无 wxss 输出）」
 * 这条路径上，样式块随之被丢弃；各页面复用同一已编译组件，于是全站继承空 wxss。
 * 修法：App.vue 里用 `<!-- #ifdef H5 -->` 包住，mp 端不参与编译。
 *
 * 分级：
 *   0 字节        → failure（确定坏，组件完全没样式）
 *   < 200 字节    → warning（可能只剩一两条规则，需要人看一眼；不阻断构建）
 * 200 字节阈值依据：目前最小正常组件 `sticky-holder` 为 143 字节，故阈值取 200。
 * 刻意不把 < 200 设成 failure —— 会制造假阳性，然后大家开始习惯性忽略，等于没有门禁。
 */
function collectWxssBySize(dir, out = []) {
    for (const entry of readdirSync(dir, { withFileTypes: true })) {
        const full = join(dir, entry.name)
        if (entry.isDirectory()) {
            collectWxssBySize(full, out)
        } else if (entry.name.endsWith('.wxss')) {
            // rel 统一成 posix 分隔符：Windows 下 path.sep 是 `\`，白名单里写的是 `/`，
            // 不归一化会导致白名单永远匹配不上（第一次跑就踩了）。
            const rel = full.replace(DIST + sep, '').split(sep).join('/')
            out.push({ file: full, rel, size: statSync(full).size })
        }
    }
    return out
}

const TINY_WXSS_BYTES = 200

/**
 * 0 字节 wxss 白名单：必须「显式相对路径 + 理由」，禁止整目录跳过。
 * 加条目 = 声明"这个空产物是合法的或已被别处补偿"，请同时写清补偿位置与清理条件。
 */
const EMPTY_WXSS_ALLOW = [
    {
        path: 'pages/webview/webview.wxss',
        why: 'webview.vue 没有 <style> 块（纯容器页，只放 web-view），空产物是正常的'
    },
    {
        path: 'node-modules/@tuniao/tnui-vue3-uniapp/components/overlay/src/overlay.wxss',
        why:
            '上游缺陷（根因未确认）：overlay.wxss 恒为 0 字节，已排除「@import 含 @use 的 scss」' +
            '（popup 同写法正常）、「仅被组件引用导致样式不收集」（实测页面直接引用后仍为 0）。' +
            '已由 src/styles/globals.scss 末尾的 .tn-overlay / .tn-overlay--show 补丁补偿' +
            '（全局 wxss 可命中组件内部节点）。上游修复后请删除本条白名单与那段补丁'
    }
]
const allowEmpty = (rel) => EMPTY_WXSS_ALLOW.find((a) => a.path === rel)

// 扫全量 .wxss（含 node-modules 与 uni_modules）：
// overlay 那种上游缺陷就藏在 node-modules 里，只扫 components/ 会漏掉。
// 合法的空产物走 EMPTY_WXSS_ALLOW 逐条白名单，不做目录级跳过。
const wxss = collectWxssBySize(DIST)

const emptyWxss = wxss.filter((w) => w.size === 0 && !allowEmpty(w.rel))
if (emptyWxss.length) {
    console.error('产物样式校验未通过：以下 .wxss 为 0 字节，对应组件/页面在真机上将没有样式')
    for (const w of emptyWxss) console.error(' - ' + w.rel)
    console.error(
        '排查方向：组件是否被 App.vue 模板直接引用（mp 端 App 无 wxss 输出，会连带丢掉样式块；' +
            '用 #ifdef H5 包住）→ style 块是否只有 @import/@use 引外部 scss（mp 下相对路径可能解析失败）' +
            '→ 再看 scoped 样式提取'
    )
    process.exit(1)
}

// 非阻断提示：体积异常小，可能只剩一两条规则
const tinyWxss = wxss.filter((w) => w.size > 0 && w.size < TINY_WXSS_BYTES)
if (tinyWxss.length) {
    console.warn(`产物样式提示：以下 .wxss 小于 ${TINY_WXSS_BYTES} 字节，请确认样式是否完整（不阻断构建）`)
    for (const w of tinyWxss) console.warn(` - ${w.rel} (${w.size} 字节)`)
}

/**
 * app.wxss 关键内容体检。
 *
 * 为什么要有：0 字节检查只能抓"文件空"，抓不到"文件 242KB 但关键块缺失"。
 * 实测事故：注入插件用 `--c-bg:` 做幂等判据，而 uni-app 也会把 tokens.css 编进 app.wxss，
 * 判据与内容重叠 → 首次 writeBundle 就 return → tn-theme 变量与 tn-icon base64 字体
 * 从未注入，而 tokens/globals 因为 uni 自己也编了所以"看起来正常"，掩盖了问题。
 * 症状：图标只能靠组件自带的 CDN 字体（弱网全空白）、26 个 tn-* 组件跑 fallback 配色。
 *
 * 五项缺一不可：
 *   --c-bg:               设计令牌（tokens.css）
 *   --tn-color            图鸟主题桥（tn-theme.scss）
 *   font-family:tuniaoFont 图标字体 @font-face
 *   x-font-woff2          字体 base64（离线可用）
 *   .tn-overlay           遮罩兜底补丁（globals.scss，上游 overlay.wxss 恒 0 字节）
 *
 * ⚠️ 本缺陷是非确定性的（同一天出现过 202KB 有字体 / 51KB 无字体两种产物），
 * 所以"跑一次通过"不构成稳定证明，验收要求连续两次构建同一组标记都齐。
 */
const APP_WXSS_REQUIRED = [
    '--c-bg:',
    '--tn-color',
    'font-family:tuniaoFont',
    'x-font-woff2',
    '.tn-overlay'
]
const appWxssPath = join(DIST, 'app.wxss')
if (existsSync(appWxssPath)) {
    const appWxss = readFileSync(appWxssPath, 'utf-8')
    const missing = APP_WXSS_REQUIRED.filter((k) => !appWxss.includes(k))
    if (missing.length) {
        console.error('产物样式校验未通过：app.wxss 缺少以下关键内容，全站样式/图标会静默降级')
        for (const k of missing) console.error(' - ' + k)
        console.error('排查方向：vite.config.ts 的 inject-global-wxss 是否因幂等判据误判而提前 return')
        process.exit(1)
    }

    /**
     * 图标字体唯一来源检查（2026-11 新增）。
     *
     * 背景：组件自带的 `node-modules/@tuniao/.../icon/src/icon.wxss` 里有一份 **CDN 版**
     * `@font-face`，与 app.wxss 的 base64 版 `font-family` 同名、描述符相同。它在 app.wxss
     * **之后**注入 → 后声明胜出 → 真正生效的是 CDN 那份，base64（约 182KB）成了死重。
     * 后果：① 离线不可用；② 小程序 `@font-face` 走远端必须把域名列进 downloadFile 合法域名，
     * 而仓库里没有任何地方声明 `at.alicdn.com`，后台没配就**全站图标空白**。
     *
     * 修法：vite.config.ts 的 stripRemoteIconFont 在 base64 确认就位后剥掉远端那份。
     * 本条门禁锁住"除 app.wxss 外不得再出现远端图标字体"，防止上游升级把 CDN 声明带回来。
     *
     * 注：app.wxss 内部**允许**出现 at.alicdn.com —— 它在同一声明的 src 列表里排第 2/3 位，
     * 属于 base64 之后的 fallback，不影响离线，也不用改（base64 woff2 早就覆盖所有目标机型）。
     */
    const strayRemoteFont = []
    const walkWxss = (dir) => {
        for (const ent of readdirSync(dir, { withFileTypes: true })) {
            const p = join(dir, ent.name)
            if (ent.isDirectory()) walkWxss(p)
            else if (ent.name.endsWith('.wxss') && p !== appWxssPath) {
                if (readFileSync(p, 'utf-8').includes('at.alicdn.com')) strayRemoteFont.push(p)
            }
        }
    }
    walkWxss(DIST)
    if (strayRemoteFont.length) {
        console.error('产物样式校验未通过：除 app.wxss 外仍有远端图标字体声明（会覆盖 base64，导致离线失效/图标空白）')
        for (const f of strayRemoteFont) console.error(' - ' + f.replace(DIST + '\\', '').replace(DIST + '/', ''))
        console.error('排查方向：vite.config.ts 的 stripRemoteIconFont 是否被跳过（常见原因：app.wxss 里没有 x-font-woff2，函数按保护逻辑主动不剥）')
        process.exit(1)
    }
}


console.log(
    `产物 app.json 校验通过：主包 ${(app.pages || []).length} 页 / 分包 ${(app.subPackages || []).length} 个 / ` +
        `preloadRule ${Object.keys(app.preloadRule || {}).length} 条`
)
