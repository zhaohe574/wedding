<template>
    <view class="sec">
        <view class="sec__left">
            <!-- 竖条由页面通过 bar 控制，每屏 ≤2 条（视觉规范 §7.8）；组件不自动隐藏 -->
            <view v-if="bar" class="sec__bar" aria-hidden="true" />
            <text class="sec__title">{{ title }}</text>
            <text v-if="subtitle" class="sec__sub">{{ subtitle }}</text>
        </view>
        <view hover-class="is-pressed" v-if="more" class="sec__more" :aria-label="more" @tap="emit('more')">
            <text class="sec__more-text">{{ more }}</text>
            <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" aria-hidden="true" />
        </view>
    </view>
</template>

<script setup lang="ts">
/** 区块标题：标题 + 可选副标题 + 右侧「更多」 */
withDefaults(
    defineProps<{
        title: string
        subtitle?: string
        more?: string
        /** 是否显示金色竖条；默认 false，由页面控制每屏 ≤2 条 */
        bar?: boolean
    }>(),
    {
        subtitle: '',
        more: '',
        bar: false
    }
)

const emit = defineEmits<{ (e: 'more'): void }>()
</script>

<style lang="scss" scoped>
.sec {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--sp-5) 0 var(--sp-3);   /* 上 32 → 48rpx（A12） */

    &__left {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__bar {
        width: 4rpx;          /* 6rpx → 4rpx（A12，AC-A-06 白名单登记） */
        height: 32rpx;        /* 28rpx → 32rpx，与 H3 字高贴合 */
        border-radius: var(--r-pill);
        background: var(--c-brand);   /* 唯一允许 --c-brand 的装饰位 */
    }

    &__title {
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);   /* 500 → 600（A12） */
        color: var(--c-ink);
        line-height: var(--lh-tight);
        letter-spacing: 0.01em;
    }

    &__sub {
        font-size: var(--fs-md);   /* 中文副标题改 Caption 档 */
        color: var(--c-ink-3);
        line-height: var(--lh-body);
    }

    &__more {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: var(--sp-1);
        min-width: 88rpx;      /* 88rpx 热区保持 */
        min-height: 88rpx;
        margin: -24rpx 0;
    }

    &__more-text {
        font-size: var(--fs-md);   /* 中文「更多」文案改 Caption 档 */
        color: var(--c-ink-3);
    }
}
</style>
