<template>
    <view class="splash-page">
        <splash-screen @done="backHome" />
    </view>
</template>

<script setup lang="ts">
/**
 * 开屏页兼容入口。
 *
 * 正常冷启动已经改为首页直接挂载 `<splash-screen>`，不再经过本页，
 * 避免「非 Tab 页 → switchTab 首页」的额外页面装载。
 * 本页仅保留给旧链接 / 调试直达，行为与开屏层保持一致：
 * 首页类 CTA 由本页收口到 switchTab，其它目标由开屏层自己跳转。
 */
import { onReady } from '@dcloudio/uni-app'
import SplashScreen from '@/components/splash-screen/splash-screen.vue'

/**
 * 兼容页直达时，开屏组件可能在首帧判定「不展示」并立刻 emit done。
 * 此时若直接 switchTab，会在本页 onReady 之前取消导航，开发者工具报
 * `navigateTo:fail timeout`。沿用旧页的做法：等 onReady 后再离开。
 */
let pageReady = false
let pendingHome = false

function backHome() {
    if (!pageReady) {
        pendingHome = true
        return
    }
    uni.switchTab({ url: '/pages/index/index' })
}

onReady(() => {
    pageReady = true
    if (pendingHome) {
        pendingHome = false
        backHome()
    }
})
</script>

<style lang="scss" scoped>
/* 页面窗口兜底：开屏层自身 fixed；本页直开时窗口底色也必须先铺常暗底 */
.splash-page {
    min-height: 100vh;
    background: var(--c-dark-2);
}
</style>
