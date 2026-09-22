<template>
    <PageShell scene="consumer" tone="editorial">
        <BaseNavbar
            :title="navbarTitle"
            :title-align="navbarTitleAlign"
            :variant="navbarVariant"
            :bg-color="navbarBgColor"
            :text-color="navbarTextColor"
            :transparent="navbarVariant === 'transparent'"
        />
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

interface Props {
    navbarTitle: string
    navbarTitleAlign?: 'center' | 'left'
    navbarVariant?: 'light' | 'solid' | 'glass' | 'transparent'
    navbarBgColor?: string
    navbarTextColor?: string
}

withDefaults(defineProps<Props>(), {
    navbarTitleAlign: 'left',
    navbarVariant: 'light',
    navbarBgColor: '',
    navbarTextColor: ''
})
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
    padding: 24rpx var(--wm-space-page-x, 28rpx) calc(48rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;
}

.auth-shell__hero {
    padding: 12rpx 8rpx 32rpx;
}

.auth-shell__card {
    padding: 40rpx 32rpx;
    border-radius: 36rpx;
    background: rgba(255, 255, 255, 0.98);
    border: 1.5rpx solid rgba(217, 190, 130, 0.45);
    box-shadow: 0 16rpx 44rpx rgba(74, 43, 24, 0.08);
    box-sizing: border-box;
}

.auth-shell__footer {
    padding: 32rpx 8rpx 0;
}

.auth-shell__overlay {
    position: relative;
    z-index: 30;
}

</style>
