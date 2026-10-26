// https://v3.nuxtjs.org/api/configuration/nuxt.config

import { fileURLToPath } from 'node:url'
import { getEnvConfig } from './nuxt/env'
const envConfig = getEnvConfig()

// 需要产出「含真实内容 HTML」的静态路由。
// 说明：`nuxt generate` 默认会从 `/` 开始爬取站内链接，但首页与资讯页之间的
// 跳转依赖运行时数据，因此这里显式声明，保证 SEO 关键页面一定被预渲染。
const prerenderRoutes = [
    '/',
    '/information',
    '/information/new',
    '/information/hot'
]

export default defineNuxtConfig({
    // 跨端契约唯一事实源（仓库根 shared/）
    alias: {
        '@shared': fileURLToPath(new URL('../shared', import.meta.url))
    },
    css: ['@/assets/styles/index.scss'],
    modules: ['@pinia/nuxt', '@nuxtjs/tailwindcss', '@element-plus/nuxt'],
    app: {
        baseURL: envConfig.baseUrl
    },
    runtimeConfig: {
        public: {
            ...envConfig
        }
    },
    // SSR 由 NUXT_SSR 控制（生产环境开启）。开启后 `nuxt generate` 仍产出纯静态
    // 文件到 .output/public，部署链路不变；构建期后端不可达时由各页面 default 兜底。
    ssr: !!envConfig.ssr,
    nitro: {
        // 静态产物统一输出到 dist/（与仓库既有约定、验收命令一致），
        // scripts/build.mjs 仍从该目录拷贝到 server/public/pc，部署链路不变。
        output: {
            publicDir: 'dist'
        },
        prerender: {
            crawlLinks: true,
            routes: prerenderRoutes,
            // 预渲染阶段后端不可达时不要把构建打断
            failOnError: false
        }
    }
})
