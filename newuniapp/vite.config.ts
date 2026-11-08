import { defineConfig } from 'vite'
import { resolve } from 'node:path'
import { readFileSync, writeFileSync, existsSync, readdirSync } from 'node:fs'
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

function readTnIconCss(): string {
    for (const c of TN_ICON_CSS_CANDIDATES) {
        try {
            return readFileSync(require.resolve(c), 'utf-8')
        } catch (e) {
            /* try next */
        }
    }
    throw new Error(
        '[inject-global-wxss] 找不到 @tuniao/tn-icon/dist/base64/index.css。' +
            '图鸟组件内部图标（popup 关闭、calendar 箭头、checkbox 勾）会全部渲染成空白方块。' +
            '请确认 @tuniao/tn-icon 已安装。'
    )
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


export default defineConfig({
    plugins: [uni(), uniRouter(), weappTailwindcssDisabled ? undefined : vwt(), injectGlobalWxss()],
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
