<template>
    <view class="staff-workspace-hero">
        <view class="staff-workspace-hero__main">
            <view class="staff-workspace-hero__copy">
                <text v-if="eyebrow" class="staff-workspace-hero__eyebrow">{{ eyebrow }}</text>
                <text class="staff-workspace-hero__title">{{ title }}</text>
                <text v-if="description" class="staff-workspace-hero__desc">{{
                    description
                }}</text>
            </view>

            <view v-if="$slots.badges" class="staff-workspace-hero__badges">
                <slot name="badges" />
            </view>

            <view v-if="actionText" class="staff-workspace-hero__action" @click="emit('action')">
                <text class="staff-workspace-hero__action-text">{{ actionText }}</text>
            </view>
        </view>

        <view v-if="metaText || $slots.default" class="staff-workspace-hero__footer">
            <text v-if="metaText" class="staff-workspace-hero__meta">{{ metaText }}</text>
            <slot />
        </view>
    </view>
</template>

<script setup lang="ts">
interface Props {
    eyebrow?: string
    title: string
    description?: string
    metaText?: string
    actionText?: string
}

withDefaults(defineProps<Props>(), {
    eyebrow: '',
    description: '',
    metaText: '',
    actionText: ''
})

const emit = defineEmits<{
    (event: 'action'): void
}>()
</script>

<style lang="scss" scoped>
.staff-workspace-hero {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
    padding: 30rpx;
    border-radius: var(--wm-radius-card-lg, 20rpx);
    background: #ffffff;
    border: 1rpx solid var(--wm-color-border, #e5e5e5);
}

.staff-workspace-hero__main {
    display: flex;
    align-items: flex-start;
    gap: 18rpx;
}

.staff-workspace-hero__copy {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.staff-workspace-hero__eyebrow {
    font-size: 21rpx;
    font-weight: 700;
    line-height: 1.25;
    color: var(--wm-color-secondary, #c8a45d);
}

.staff-workspace-hero__title {
    font-size: 38rpx;
    font-weight: 700;
    line-height: 1.25;
    color: var(--wm-text-primary, #111111);
}

.staff-workspace-hero__desc,
.staff-workspace-hero__meta {
    font-size: 23rpx;
    font-weight: 600;
    line-height: 1.55;
    color: var(--wm-text-secondary, #4a4a4a);
}

.staff-workspace-hero__badges {
    display: flex;
    align-items: center;
    gap: 10rpx;
    flex-wrap: wrap;
}

.staff-workspace-hero__action {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 60rpx;
    padding: 0 22rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: var(--wm-color-primary, #0b0b0b);
    transition: opacity var(--wm-motion-base, 220ms) ease,
        transform var(--wm-motion-base, 220ms) ease;

    &:active {
        opacity: 0.88;
        transform: translateY(2rpx);
    }
}

.staff-workspace-hero__action-text {
    font-size: 23rpx;
    font-weight: 700;
    line-height: 1;
    color: var(--wm-text-inverse, #ffffff);
}

.staff-workspace-hero__footer {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
}
</style>
