<template>
    <view class="sec">
        <view class="sec__left">
            <view class="sec__bar" />
            <text class="sec__title">{{ title }}</text>
            <text v-if="subtitle" class="sec__sub">{{ subtitle }}</text>
        </view>
        <view hover-class="is-pressed" v-if="more" class="sec__more" @tap="emit('more')">
            <text class="sec__more-text">{{ more }}</text>
            <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
        </view>
    </view>
</template>

<script setup lang="ts">
/** 区块标题：标题 + 可选副标题 + 右侧「更多」 */
withDefaults(defineProps<{ title: string; subtitle?: string; more?: string }>(), {
    subtitle: '',
    more: ''
})

const emit = defineEmits<{ (e: 'more'): void }>()
</script>

<style lang="scss" scoped>
.sec {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--sp-4) 0 var(--sp-3);

    &__left {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__bar {
        width: 6rpx;
        height: 28rpx;
        border-radius: var(--r-pill);
        background: var(--c-brand);
    }

    &__title {
        font-size: var(--fs-lg);
        font-weight: 500;
        color: var(--c-ink);
    }

    &__sub {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__more {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: var(--sp-1);
        min-width: 88rpx;
        min-height: 88rpx;
        margin: -24rpx 0;
    }

    &__more-text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}
</style>
