<template>
    <!-- 全局 Toast 挂载点（03 §3.2 N5）：组件走 easycom.autoscan 自动注册，无需在此 import。
         注：H5 端 App.vue 作为根组件渲染此 template；小程序端 App.vue 不渲染视图，
         需由后续页面批次（T-27~T-36）在需要的页面显式挂载 <app-toast />。此为跨端差异，已在批次报告单列。 -->
    <app-toast />
</template>

<script setup lang="ts">
import { onLaunch, onError } from '@dcloudio/uni-app'
import { useAppStore } from './stores/app'
import { useUserStore } from './stores/user'
import { configureRequest } from './request'
import { getToken } from './utils/auth'
import { report } from './platform/report'

const appStore = useAppStore()
const userStore = useUserStore()

/** 登录失效：清 token，必要时跳登录 */
function handleUnauthorized() {
    userStore.logout()
    const pages = getCurrentPages()
    const current = pages[pages.length - 1]
    const route = current ? `/${(current as any).route}` : ''
    if (route && route.indexOf('/pages/login/login') === -1) {
        uni.navigateTo({ url: '/pages/login/login' })
    }
}

// 请求层依赖注入：避免 store ↔ request 循环依赖
configureRequest({
    getToken,
    onUnauthorized: handleUnauthorized,
    onError: (error) => {
        if (error.shouldReport) {
            report('api_error', { message: error.message, extra: { code: error.code, errorCode: error.errorCode } })
        }
    }
})

onLaunch(() => {
    // 配色由 tokens.css 静态决定：原先的运行时主题链路生成的 --color-* 从未被任何样式消费，
    // 只会让人误以为「主题可配」。颜色是设计资产，不接受运行时下发。
    appStore.getConfig().catch(() => {
        report('api_error', { endpoint: 'index.config', message: '全局配置加载失败' })
    })
})

onError((err) => {
    report('page_error', { message: String(err) })
})
</script>

<style lang="scss">
/* 全局样式由 vite 插件 inject-global-wxss 注入 app.wxss */
</style>
