<template>
    <view class="empty-state-block">
        <view class="empty-state-block__halo"></view>
        <view class="empty-state-block__icon">
            <slot name="icon">
                <tn-icon name="inbox" size="120" color="#D8D3C7" />
            </slot>
        </view>
        <text class="empty-state-block__title">{{ title }}</text>
        <text v-if="description" class="empty-state-block__description">{{ description }}</text>
        <BaseButton v-if="actionText" variant="cta" size="md" @click="emit('action')">
            {{ actionText }}
        </BaseButton>
    </view>
</template>

<script setup lang="ts">
import BaseButton from './BaseButton.vue'

interface Props {
    title: string
    description?: string
    actionText?: string
}

withDefaults(defineProps<Props>(), {
    description: '',
    actionText: ''
})

const emit = defineEmits<{
    (event: 'action'): void
}>()
</script>

<style lang="scss" scoped>
.empty-state-block {
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 18rpx;
    min-height: 360rpx;
    padding: 72rpx 36rpx;
    text-align: center;
    box-sizing: border-box;
    border-radius: var(--wm-radius-card-lg, 32rpx);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.82) 0%, rgba(255, 255, 255, 0.46) 100%);

    &__halo {
        position: absolute;
        top: 36rpx;
        width: 180rpx;
        height: 180rpx;
        border-radius: 999rpx;
        background: radial-gradient(circle, rgba(200, 164, 93, 0.18) 0, rgba(200, 164, 93, 0) 70%);
        pointer-events: none;
    }

    &__icon {
        position: relative;
        opacity: 0.82;
    }

    &__title {
        position: relative;
        font-size: 30rpx;
        font-weight: 800;
        line-height: 1.35;
        color: var(--wm-text-primary, #111111);
    }

    &__description {
        position: relative;
        max-width: 540rpx;
        font-size: 24rpx;
        line-height: 1.7;
        color: var(--wm-text-secondary, #56524a);
    }

    :deep(.base-button) {
        position: relative;
        margin-top: 10rpx;
    }
}
</style>
