<template>
    <view class="empty-state-block">
        <view class="empty-state-block__icon">
            <slot name="icon">
                <tn-icon name="inbox" size="120" color="#D8CEC8" />
            </slot>
        </view>
        <text class="empty-state-block__title">{{ title }}</text>
        <text v-if="description" class="empty-state-block__description">{{ description }}</text>
        <BaseButton v-if="actionText" variant="primary" size="md" @click="emit('action')">
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
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16rpx;
    padding: 56rpx 32rpx;
    text-align: center;

    &__title {
        font-size: 30rpx;
        font-weight: 700;
        color: var(--wm-text-primary, #1e2432);
    }

    &__description {
        font-size: 24rpx;
        line-height: 1.6;
        color: var(--wm-text-secondary, #7f7b78);
    }
}
</style>
