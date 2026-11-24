import { defineConfig } from 'vite'
import { resolve, join, dirname, posix, relative } from 'node:path'
import {
    readFileSync,
    writeFileSync,
    existsSync,
    readdirSync,
    mkdirSync,
    rmSync
} from 'node:fs'
import uni from '@dcloudio/vite-plugin-uni'
import tailwindcss from 'tailwindcss'
import autoprefixer from 'autoprefixer'
import postcssRemToResponsivePixel from 'postcss-rem-to-responsive-pixel'
import postcssWeappTailwindcssRename from 'weapp-tailwindcss-webpack-plugin/postcss'
import vwt from 'weapp-tailwindcss-webpack-plugin/vite'
import uniRouter from 'unplugin-uni-router/vite'

const isH5 = process.env.UNI_PLATFORM === 'h5'
const isApp = process.env.UNI_PLATFORM === 'app'
const weappTailwindcssDisabled = isH5 || isApp

const postcssPlugin = [autoprefixer(), tailwindcss()]
if (!weappTailwindcssDisabled) {
    postcssPlugin.push(
        postcssRemToResponsivePixel({
            rootValue: 32,
            propList: ['*'],
            transformUnit: 'rpx'
        })
    )
    postcssPlugin.push(postcssWeappTailwindcssRename())
}

// https://vitejs.dev/config/

// 小程序端全局样式注入：本工程 uni-app 未把 App.vue <style> / main.ts 的 CSS import 编译进 app.wxss，
// 导致 tokens.css 的 page{} 变量定义与全局规则丢失，页面 var(--c-*) 全部悬空。
//
// app.wxss 的拼装顺序（全部幂等，dev 与 build 都会执行）：
//   1. tokens.css        —— 设计令牌 SSOT，page{} 的 --c-*
//   2. tn-theme.scss     —— 图鸟 --tn-* 基线 + 覆盖为 --c-*（必须在 tokens 之后编译，sass 里直接读常量）
//   3. globals.scss      —— 全局基础规则（page 底色、.page-wrap、.chip、.tone-* 等）
//   4. 图鸟图标字体      —— @tuniao/tn-icon base64（110KB 字体 / 182KB wxss），必须全局可见，
//                           tn-popup/tn-calendar/tn-checkbox 等 26 个组件的内部图标都依赖它
//   5. 原有 app.wxss 内容（uni-app 生成的部分）
//
// 注意：`apply` 不再限定 'build'，否则 `uni -p mp-weixin`（dev）下页面拿不到 --c-*，
// 三位前端工程师本地开发会看到完全无样式的页面。

/** 图鸟图标字体：npm 扁平安装与嵌套安装两种位置都要能解析（pnpm/npm hoisting 差异） */
const TN_ICON_CSS_CANDIDATES = [
    '@tuniao/tn-icon/dist/base64/index.css',
    '@tuniao/tnui-vue3-uniapp/node_modules/@tuniao/tn-icon/dist/base64/index.css'
]

/**
 * 从 tn-icon CSS 里只提取 `@font-face` 字体块。
 *
 * 为什么要剥离：base64/index.css = @font-face 字体块（约 147KB）+ 838 条
 * `.tn-icon-*:before` 图标类（约 35KB）。后者与组件自带的 `icon.wxss`
 * （已带完整 849 条图标类）完全重复，全站也没有页面/组件直接使用 `.tn-icon-*` 类，
 * 原样注入等于往 app.wxss 塞 35KB 死 CSS（微信代码质量「主包 CSS 冗余」扣分项）。
 * 所以这里只返回真正必需的 @font-face（含 tuniaoFont + x-font-woff2 base64 字体），
 * 保留 stripRemoteIconFont() 与 validate-dist.mjs 依赖的两个特征串。
 */
function extractTnIconFontFace(css: string): string | null {
    const fontFaceRe = /@font-face\s*\{[^}]*\}/g
    for (const block of css.match(fontFaceRe) || []) {
        if (/font-family\s*:\s*['"]?tuniaoFont/i.test(block) && block.includes('x-font-woff2')) {
            return block
        }
    }
    return null
}

function readTnIconCss(): string {
    let css: string | null = null
    for (const c of TN_ICON_CSS_CANDIDATES) {
        try {
            css = readFileSync(require.resolve(c), 'utf-8')
            break
        } catch (e) {
            /* try next */
        }
    }
    if (css === null) {
        throw new Error(
            '[inject-global-wxss] 找不到 @tuniao/tn-icon/dist/base64/index.css。' +
                '图鸟组件内部图标（popup 关闭、calendar 箭头、checkbox 勾）会全部渲染成空白方块。' +
                '请确认 @tuniao/tn-icon 已安装。'
        )
    }
    const fontFace = extractTnIconFontFace(css)
    if (!fontFace) {
        // 宁可构建失败，也不能静默丢掉图标字体（离线即全站图标空白）
        throw new Error(
            '[inject-global-wxss] tn-icon CSS 中未找到含 tuniaoFont + x-font-woff2 的 @font-face 块，' +
                '图标字体将无法注入 app.wxss，构建中止。请检查 @tuniao/tn-icon 包内容是否完整。'
        )
    }
    return fontFace
}

async function compileScss(file: string): Promise<string> {
    // sass 1.54.5 是 CJS 包：动态 import 得到的命名空间里 compile 挂在 default 上，
    // 老代码写 `import('sass').then(m => m.compile(...))` 会静默抛 TypeError 并回退到
    // 预生成的 globals.css（陈旧产物）。这里两种形态都兼容，且失败直接抛错。
    const mod: any = await import('sass')
    const sass = mod.default ?? mod
    const entry = resolve(__dirname, file)
    if (typeof sass.compile === 'function') {
        return sass.compile(entry, { style: 'compressed' }).css
    }
    return sass.renderSync({ file: entry, outputStyle: 'compressed' }).css.toString()
}

/** 注入标记：只由本插件写入，用于判断"是否已注入过"。
    不能再用 `--c-bg:` 之类的内容特征做幂等判断 —— uni-app 现在会把 main.ts 里的
    `import './styles/index.scss'`（内含 tokens.css / globals.scss）也编译进 app.wxss，
    于是首次 writeBundle 时 app.wxss 就已经带 `--c-bg:`，旧判断直接 return，
    **tn-theme.scss 与 tn-icon base64 字体永远注入不进去**（实测：app.wxss 从 202KB 掉到 51KB，
    `--tn-color*` 与 `@font-face` 双双为 0，图标只能退回组件自带的 CDN 字体）。 */
const INJECT_SENTINEL = '/*__inject_global_wxss__*/'

function injectGlobalWxss() {
    return {
        name: 'inject-global-wxss',
        async writeBundle(options: any) {
            if (process.env.UNI_PLATFORM !== 'mp-weixin') return
            const outDir = options.dir || resolve(__dirname, 'dist/build/mp-weixin')
            const target = resolve(outDir, 'app.wxss')
            if (!existsSync(target)) return

            let current = readFileSync(target, 'utf-8')

            // 幂等：dev 下每次增量编译都会触发 writeBundle。只认本插件写的标记。
            if (!current.includes(INJECT_SENTINEL)) {
                const has = (needle: string) => current.includes(needle)
                const parts: string[] = []

                // 逐块判重：uni 已经编译进去的就不重复注入，避免小程序主包体积翻倍。
                // 顺序沿用 1 令牌 → 2 图鸟变量 → 3 全局规则 → 4 图标字体。
                if (!has('--c-bg:')) {
                    parts.push(readFileSync(resolve(__dirname, 'src/styles/tokens.css'), 'utf-8'))
                }
                if (!has('--tn-color')) {
                    try {
                        parts.push(await compileScss('src/styles/tn-theme.scss'))
                    } catch (e) {
                        // 不再静默回退：全局样式缺失时页面会整体失去令牌，宁可构建失败
                        throw new Error('[inject-global-wxss] tn-theme.scss 编译失败，构建中止：' + e)
                    }
                }
                if (!has('.page-pad')) {
                    try {
                        parts.push(await compileScss('src/styles/globals.scss'))
                    } catch (e) {
                        throw new Error('[inject-global-wxss] globals.scss 编译失败，构建中止：' + e)
                    }
                }
                if (!has('font-family:tuniaoFont')) {
                    parts.push(readTnIconCss())
                }

                writeFileSync(target, [...parts, INJECT_SENTINEL, current].join('\n'))
                current = readFileSync(target, 'utf-8')
            }

            // 剥离组件自带的 CDN 版图标字体 @font-face（见下方 stripRemoteIconFont 注释）。
            // 放在注入之后：必须先确认 app.wxss 里真的有 base64 字体，否则剥掉 CDN 就等于把图标删了。
            stripRemoteIconFont(outDir, current)
        }
    }
}

/**
 * 组件自带的 `icon.wxss` 里有一份 **CDN** 版图标字体：
 *
 *   @font-face{font-family:tuniaoFont;src:url(//at.alicdn.com/t/c/font_3063751_xxx.woff?...)}
 *
 * 它在 `app.wxss`（base64 版，同名校验通过）**之后**注入，两条声明 `font-family` 同名、
 * 描述符相同 → 后声明的胜出 → 真正生效的是 CDN 那份，base64 成了 182KB 死重。
 * 后果：离线不可用；且小程序 `@font-face` 走远端时必须把域名列进 downloadFile 合法域名，
 * 仓库里没有任何地方声明 `at.alicdn.com` —— 一旦后台没配，**全站图标直接不显示**。
 *
 * 所以：只要 app.wxss 里确实带着 base64 字体，就把远端那份剥掉，让 base64 成为唯一来源。
 * ⚠️ 反向保护：app.wxss 没有 base64 时**一律不剥**。此时宁可退回 CDN（有网就能显示），
 *    也不能让图标彻底消失 —— 注入失败是已知脆弱点（见 INJECT_SENTINEL 注释）。
 */
const REMOTE_ICON_FONT_RE = /@font-face\s*\{[^}]*\}/g

function listWxss(dir: string, acc: string[] = []): string[] {
    for (const ent of readdirSync(dir, { withFileTypes: true })) {
        const p = resolve(dir, ent.name)
        if (ent.isDirectory()) listWxss(p, acc)
        else if (ent.name.endsWith('.wxss')) acc.push(p)
    }
    return acc
}

function stripRemoteIconFont(outDir: string, appWxss: string) {
    const hasLocalFont =
        appWxss.includes('font-family:tuniaoFont') && appWxss.includes('x-font-woff2')
    if (!hasLocalFont) {
        // 本地字体没进 app.wxss：保留组件自带的 CDN 声明，别把图标弄没
        return
    }
    for (const file of listWxss(outDir)) {
        if (file.endsWith('app.wxss')) continue
        const src = readFileSync(file, 'utf-8')
        if (!src.includes('at.alicdn.com')) continue
        const next = src.replace(REMOTE_ICON_FONT_RE, (block) =>
            block.includes('tuniaoFont') && /url\(\s*['"]?(?:https?:)?\/\//.test(block) ? '' : block
        )
        if (next !== src) writeFileSync(file, next)
    }
}

/**
 * 主包未使用 JS 迁移插件（仅 build 模式）。
 *
 * 背景：微信代码质量检测会报「主包存在未使用 JS」。uni-app 把全部 utils/composables/
 * request 等模块都打进主包 common 之外的目录，其中一部分只有分包页面在用 —— 主包里
 * 就是死代码。此插件在产物写盘后做一次 require 依赖图分析：
 *   1. 从主包入口（app.js / 主包 pages / 带 .json 的主包组件）沿相对 require 做 BFS，
 *      得到主包可达集合；
 *   2. 主包里不可达的 .js（candidate）若被某分包引用，则迁入 `<分包根>/_shared/`
 *      并重写引用方与被迁文件内部的相对 require；
 *   3. 没有任何分包引用的 candidate 直接删除（含 0 引用空 stub），并清理变空的目录。
 *
 * 迁移合法性：微信允许「分包 require 主包」，反之不允许 —— 迁移只动主包不可达文件，
 * 主包可达文件（common/vendor.js 等）留在原地供分包跨包引用。
 *
 * ⚠️ apply: 'build' —— dev（uni -p mp-weixin）下 writeBundle 每次增量编译都会触发，
 *    不能对 watch 中的产物做迁移/删除，否则破坏 dev 增量构建。
 */
function relocateSubpackageJs() {
    return {
        name: 'relocate-subpackage-js',
        apply: 'build' as const,
        writeBundle(options: any) {
            if (process.env.UNI_PLATFORM !== 'mp-weixin') return
            const outDir: string = options.dir || resolve(__dirname, 'dist/build/mp-weixin')
            const appJsonPath = resolve(outDir, 'app.json')
            if (!existsSync(appJsonPath)) return
            const app = JSON.parse(readFileSync(appJsonPath, 'utf-8'))
            const subs: any[] = app.subPackages || app.subpackages || []
            const subRoots = subs
                .map((s) => String(s?.root || '').replace(/^\/+/, '').replace(/\/+$/, ''))
                .filter(Boolean)
            if (!subRoots.length) return
            const subRootOf = (rel: string) => subRoots.find((r) => rel.startsWith(r + '/'))

            // 递归收集全部 .js（统一 posix 相对路径）
            const allJs: string[] = []
            const walkJs = (dir: string) => {
                for (const ent of readdirSync(dir, { withFileTypes: true })) {
                    const full = join(dir, ent.name)
                    if (ent.isDirectory()) walkJs(full)
                    else if (ent.name.endsWith('.js')) {
                        allJs.push(relative(outDir, full).split('\\').join('/'))
                    }
                }
            }
            walkJs(outDir)
            if (!allJs.length) return
            const allJsSet = new Set(allJs)

            // 相对 require 邻接表（目标不在产物中的忽略，交给 validate-dist 门禁报告）
            const REQUIRE_RE = /\brequire\(\s*(['"])(\.[^'"]+)\1\s*\)/g
            const normalizeTarget = (fromRel: string, raw: string): string => {
                let t = posix.normalize(posix.join(posix.dirname(fromRel), raw))
                if (!t.endsWith('.js')) t += '.js'
                return t
            }
            const deps = new Map<string, string[]>()
            for (const rel of allJs) {
                const src = readFileSync(join(outDir, rel), 'utf-8')
                const targets: string[] = []
                for (const m of src.matchAll(REQUIRE_RE)) {
                    const t = normalizeTarget(rel, m[2])
                    if (allJsSet.has(t)) targets.push(t)
                }
                deps.set(rel, targets)
            }

            // 主包入口种子集：app.js + 主包 pages + 带 .json 同名文件的主包组件/页面，
            // 兜底纳入 common/vendor.js、common/assets.js（绝不误删公共 chunk）
            const mainSeeds = new Set<string>()
            if (allJsSet.has('app.js')) mainSeeds.add('app.js')
            for (const p of app.pages || []) {
                const rel = `${p}.js`
                if (allJsSet.has(rel) && !subRootOf(rel)) mainSeeds.add(rel)
            }
            for (const rel of allJs) {
                if (subRootOf(rel)) continue
                if (existsSync(resolve(outDir, rel.replace(/\.js$/, '.json')))) mainSeeds.add(rel)
            }
            for (const fallback of ['common/vendor.js', 'common/assets.js']) {
                if (allJsSet.has(fallback)) mainSeeds.add(fallback)
            }
            if (!mainSeeds.size) return

            // 主包可达集（BFS 仅沿主包文件，不进分包）
            const mainReachable = new Set(mainSeeds)
            const queue = [...mainSeeds]
            while (queue.length) {
                const cur = queue.pop() as string
                for (const t of deps.get(cur) || []) {
                    if (subRootOf(t)) continue
                    if (!mainReachable.has(t)) {
                        mainReachable.add(t)
                        queue.push(t)
                    }
                }
            }

            // 主包候选：不可达 .js
            const candidates = allJs.filter((rel) => !subRootOf(rel) && !mainReachable.has(rel))
            if (!candidates.length) return
            const candidateSet = new Set(candidates)

            // 各分包所需候选：以分包内全部 .js 为起点 BFS（允许穿过候选追踪传递依赖，
            // 不进入其他分包）
            const neededBySub = new Map<string, Set<string>>()
            for (const root of subRoots) {
                const prefix = root + '/'
                const visited = new Set<string>()
                const q: string[] = []
                for (const rel of allJs) {
                    if (rel.startsWith(prefix)) {
                        visited.add(rel)
                        q.push(rel)
                    }
                }
                while (q.length) {
                    const cur = q.pop() as string
                    for (const t of deps.get(cur) || []) {
                        const rootOfT = subRootOf(t)
                        if (rootOfT && rootOfT !== root) continue
                        if (!visited.has(t)) {
                            visited.add(t)
                            q.push(t)
                        }
                    }
                }
                neededBySub.set(root, new Set([...visited].filter((rel) => candidateSet.has(rel))))
            }

            // 迁移：candidate → <分包根>/_shared/<原主包相对路径>，
            // 重写其内部相对 require（同迁移集内 → _shared；否则留在主包，分包可合法跨包引用）
            for (const root of subRoots) {
                const needed = neededBySub.get(root) as Set<string>
                if (!needed.size) continue
                for (const cand of needed) {
                    const destRel = `${root}/_shared/${cand}`
                    const destAbs = join(outDir, destRel)
                    let src = readFileSync(join(outDir, cand), 'utf-8')
                    src = src.replace(REQUIRE_RE, (full, quote, raw) => {
                        const origTarget = normalizeTarget(cand, raw as string)
                        const newTarget = needed.has(origTarget)
                            ? `${root}/_shared/${origTarget}`
                            : origTarget
                        let relReq = posix.relative(posix.dirname(destRel), newTarget)
                        if (!relReq.startsWith('.')) relReq = './' + relReq
                        return `require(${quote}${relReq}${quote})`
                    })
                    mkdirSync(dirname(destAbs), { recursive: true })
                    writeFileSync(destAbs, src)
                }
            }

            // 重写各分包自有文件（非 _shared）指向候选的 require
            for (const root of subRoots) {
                const needed = neededBySub.get(root) as Set<string>
                if (!needed.size) continue
                const sharedPrefix = `${root}/_shared/`
                for (const rel of allJs) {
                    if (!rel.startsWith(root + '/') || rel.startsWith(sharedPrefix)) continue
                    const abs = join(outDir, rel)
                    const src = readFileSync(abs, 'utf-8')
                    let changed = false
                    const next = src.replace(REQUIRE_RE, (full, quote, raw) => {
                        const origTarget = normalizeTarget(rel, raw as string)
                        if (!needed.has(origTarget)) return full
                        const newTarget = `${root}/_shared/${origTarget}`
                        let relReq = posix.relative(posix.dirname(rel), newTarget)
                        if (!relReq.startsWith('.')) relReq = './' + relReq
                        changed = true
                        return `require(${quote}${relReq}${quote})`
                    })
                    if (changed) writeFileSync(abs, next)
                }
            }

            // 删除主包原文件（含 0 引用空 stub），并自底向上清理变空的目录
            for (const cand of candidates) rmSync(join(outDir, cand), { force: true })
            pruneEmptyDirs(outDir)
        }
    }
}

/** 自底向上删除空目录（不含 outDir 本身），避免主包残留 hooks/ 之类的空壳 */
function pruneEmptyDirs(dir: string, root?: string) {
    for (const ent of readdirSync(dir, { withFileTypes: true })) {
        if (ent.isDirectory()) pruneEmptyDirs(join(dir, ent.name), root ?? dir)
    }
    if (dir !== (root ?? dir) && readdirSync(dir).length === 0) {
        rmSync(dir, { recursive: true, force: true })
    }
}


export default defineConfig({
    plugins: [
        uni(),
        uniRouter(),
        weappTailwindcssDisabled ? undefined : vwt(),
        injectGlobalWxss(),
        relocateSubpackageJs()
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'src'),
            // 跨端契约层：与 admin / pc 共用同一份 shared/contracts
            '@shared': resolve(__dirname, '../shared')
        }
    },
    css: {
        postcss: {
            plugins: postcssPlugin
        }
    },
    server: {
        port: 8991,
        // shared/ 在工程根目录之外，dev 需要放行
        fs: {
            allow: [resolve(__dirname, '..')]
        }
    }
})
