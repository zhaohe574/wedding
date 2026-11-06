import { defineConfig } from 'vite'
import { resolve } from 'node:path'
import { readFileSync, writeFileSync, existsSync } from 'node:fs'
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
// writeBundle 阶段把 SSOT（tokens.css）与编译后的 globals.css 追加到 app.wxss（幂等）。
function injectGlobalWxss() {
    return {
        name: 'inject-global-wxss',
        apply: 'build',
        async writeBundle(options) {
            if (process.env.UNI_PLATFORM !== 'mp-weixin') return
            let target = resolve(options.dir || '', 'app.wxss')
            if (!existsSync(target)) target = resolve(__dirname, 'dist/build/mp-weixin/app.wxss')
            if (!existsSync(target)) return
            const tokens = readFileSync(resolve(__dirname, 'src/styles/tokens.css'), 'utf-8')
            let globals
            try {
                const sass = await import('sass')
                globals = sass.compile(resolve(__dirname, 'src/styles/globals.scss'), { style: 'compressed' }).css
            } catch (e) {
                globals = readFileSync(resolve(__dirname, 'src/styles/globals.css'), 'utf-8')
            }
            const current = readFileSync(target, 'utf-8')
            if (current.includes('--c-bg:')) return
            writeFileSync(target, tokens + '\n' + globals + '\n' + current)
        }
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
