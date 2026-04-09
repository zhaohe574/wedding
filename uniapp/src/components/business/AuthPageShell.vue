<template>
    <page-meta :page-style="themeStore.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar :title="navbarTitle" />
        <view class="auth-shell">
            <view class="auth-shell__content">
                <view v-if="$slots.hero" class="auth-shell__hero">
                    <slot name="hero" />
                </view>

                <view class="auth-shell__card">
                    <slot />
                </view>

                <view v-if="$slots.footer" class="auth-shell__footer">
                    <slot name="footer" />
                </view>
            </view>

            <view v-if="$slots.overlay" class="auth-shell__overlay">
                <slot name="overlay" />
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import PageShell from '@/components/base/PageShell.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import { useThemeStore } from '@/stores/theme'

interface Props {
    navbarTitle: string
}

const props = defineProps<Props>()
const themeStore = useThemeStore()
</script>

<style lang="scss" scoped>
.auth-shell {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
    background: transparent;
}

.auth-shell__content {
    position: relative;
    z-index: 1;
    min-height: calc(100vh - 88rpx);
    padding: 52rpx 24rpx 56rpx;
}

.auth-shell__hero {
    padding: 16rpx 8rpx 32rpx;
}

.auth-shell__card {
    padding: 28rpx 24rpx 32rpx;
    border-radius: var(--wm-radius-popup, 28rpx);
    background: rgba(255, 255, 255, 0.84);
    border: 1rpx solid rgba(244, 199, 191, 0.48);
    backdrop-filter: blur(20rpx);
    box-shadow: var(--wm-shadow-card, 0 18rpx 36rpx rgba(214, 185, 167, 0.2));
}

.auth-shell__footer {
    padding: 24rpx 8rpx 0;
}

.auth-shell__overlay {
    position: relative;
    z-index: 30;
}
</style>
