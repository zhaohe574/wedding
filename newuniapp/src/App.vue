<template>
    <!-- 全局 Toast 挂载点（03 §3.2 N5）：组件走 easycom.autoscan 自动注册，无需在此 import。
         注：H5 端 App.vue 作为根组件渲染此 template；小程序端 App.vue 不渲染视图，
         需由后续页面批次（T-27~T-36）在需要的页面显式挂载 <app-toast />。此为跨端差异，已在批次报告单列。 -->
    <!-- ⚠️ 必须条件编译：小程序端若在 App.vue 模板里挂 <app-toast />，uni-app 会把这个组件
         挂到「App 根（无 wxss 输出）」的编译路径上，导致 app-toast.wxss 恒为 0 字节 ——
         全站 47 页的 toast 都没有样式（无背景、无定位、无动画）。
         实测：移除本行 → app-toast.wxss 0 字节；加 #ifdef H5 后 → 1093 字节，样式完整。 -->
    <!-- #ifdef H5 -->
    <app-toast />
    <!-- #endif -->
</template>

<script setup lang="ts">
import { onLaunch, onError } from '@dcloudio/uni-app'
import { useAppStore } from './stores/app'
import { useUserStore } from './stores/user'
import { configureRequest } from './request'
import { getToken } from './utils/auth'
import { report } from './platform/report'
import { setupPrivacyGuard, promptPrivacyOnLaunch } from './composables/usePrivacyGuard'

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
            report('api_error', {
                message: error.message,
                extra: { code: error.code, errorCode: error.errorCode }
            })
        }
    }
})

onLaunch(() => {
    // 合规 B1：manifest 已开 __usePrivacyCheck__，必须配一个授权兜底，
    // 否则相册 / 手机号类接口在真机上拿不到授权（审核必测「上传评价图片」）。
    setupPrivacyGuard()
    // 用户裁决（P0-1 / P1-8）：首次启动主动展示隐私弹窗，不再只等隐私接口按需触发。
    // 已同意则静默通过；未同意会走 setupPrivacyGuard 注册的队列弹窗。
    promptPrivacyOnLaunch()

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
