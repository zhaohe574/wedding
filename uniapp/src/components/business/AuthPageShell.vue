<template>
    <page-meta :page-style="themeStore.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar :title="navbarTitle" />
        <view class="auth-shell">
            <view class="auth-shell__ornament auth-shell__ornament--primary" :style="primaryOrb" />
            <view
                class="auth-shell__ornament auth-shell__ornament--secondary"
                :style="secondaryOrb"
            />

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
import { computed } from 'vue'
import PageShell from '@/components/base/PageShell.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import { useThemeStore } from '@/stores/theme'
import { alphaColor } from '@/utils/color'

interface Props {
    navbarTitle: string
}

const props = defineProps<Props>()
const themeStore = useThemeStore()

const primaryOrb = computed(() => ({
    background: `radial-gradient(circle, ${alphaColor(themeStore.primaryColor, 0.18)} 0%, ${alphaColor(
        themeStore.primaryColor,
        0
    )} 72%)`
}))

const secondaryOrb = computed(() => ({
    background: `radial-gradient(circle, ${alphaColor(themeStore.secondaryColor, 0.18)} 0%, ${alphaColor(
        themeStore.secondaryColor,
        0
    )} 72%)`
}))
</script>

<style lang="scss" scoped>
.auth-shell {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
    background:
        linear-gradient(180deg, rgba(255, 245, 241, 0.96) 0%, rgba(252, 251, 249, 1) 56%, rgba(247, 241, 237, 0.92) 100%),
        var(--wm-color-bg-page, #fcfbf9);
}

.auth-shell__ornament {
    position: absolute;
    border-radius: 50%;
    filter: blur(8rpx);
    pointer-events: none;
}

.auth-shell__ornament--primary {
    top: -140rpx;
    right: -80rpx;
    width: 420rpx;
    height: 420rpx;
}

.auth-shell__ornament--secondary {
    left: -110rpx;
    bottom: 160rpx;
    width: 320rpx;
    height: 320rpx;
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
