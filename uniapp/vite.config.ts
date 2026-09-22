import { defineConfig } from 'vite'
import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import uni from '@dcloudio/vite-plugin-uni'
import tailwindcss from 'tailwindcss'
import autoprefixer from 'autoprefixer'
import postcssRemToResponsivePixel from 'postcss-rem-to-responsive-pixel'
import postcssWeappTailwindcssRename from 'weapp-tailwindcss-webpack-plugin/postcss'
import vwt from 'weapp-tailwindcss-webpack-plugin/vite'
import uniRouter from 'unplugin-uni-router/vite'

import type { Plugin } from 'vite'

if (process.env.UNI_PLATFORM && process.env.UNI_PLATFORM !== 'mp-weixin') {
    throw new Error('仅支持构建微信小程序')
}

// 自动消除 uniapp-router-next 遗留在 onShow 钩子中的 console.log(options) 调试信息
function stripRouterLogsPlugin(): Plugin {
    return {
        name: 'strip-router-logs',
        enforce: 'pre',
        transform(code, id) {
            if (id.includes('uniapp-router-next')) {
                return {
                    code: code
                        .replace(/console\.log\(options\);?/g, '')
                        .replace(/console\.log\(vm\);?/g, ''),
                    map: null
                }
            }
        }
    }
}

// 空 AppID 会被编译成游客模式，游客登录码无法用于正式后端登录。
const manifestPath = fileURLToPath(new URL('./src/manifest.json', import.meta.url))
const wechatAppId = JSON.parse(readFileSync(manifestPath, 'utf8'))['mp-weixin']?.appid
if (typeof wechatAppId !== 'string' || !/^wx[0-9a-f]{16}$/.test(wechatAppId)) {
    throw new Error('请在 src/manifest.json 的 mp-weixin.appid 中填写与后端一致的微信小程序 AppID，不能使用游客模式')
}

export default defineConfig({
    plugins: [stripRouterLogsPlugin(), uni(), uniRouter(), vwt()],
    css: {
        postcss: {
            plugins: [
                autoprefixer(),
                tailwindcss(),
                postcssRemToResponsivePixel({
                    rootValue: 32,
                    propList: ['*'],
                    transformUnit: 'rpx'
                }),
                postcssWeappTailwindcssRename()
            ]
        }
    },
    server: {
        port: 8991
    }
})
