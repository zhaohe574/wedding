import { defineConfig } from 'vite'
import { resolve } from 'node:path'
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
export default defineConfig({
    plugins: [uni(), uniRouter(), weappTailwindcssDisabled ? undefined : vwt()],
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
