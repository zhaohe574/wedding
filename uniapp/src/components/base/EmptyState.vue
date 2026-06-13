<template>
    <view class="empty-state-block" :class="stateClass">
        <view class="empty-state-block__icon">
            <slot name="icon">
                <BaseIcon :name="resolvedIcon" size="58" :color="iconColor" />
            </slot>
        </view>
        <text class="empty-state-block__title">{{ title }}</text>
        <text v-if="description" class="empty-state-block__description">{{ description }}</text>
        <view v-if="actionText" class="empty-state-block__action">
            <BaseButton
                :label="actionText"
                :variant="actionVariant"
                :size="compact ? 'sm' : 'md'"
                icon="add"
                @click="emit('action')"
            />
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseButton from './BaseButton.vue'
import BaseIcon from './BaseIcon.vue'

interface Props {
    title: string
    description?: string
    actionText?: string
    tone?: 'neutral' | 'wedding' | 'error' | 'success' | 'auth'
    icon?: string
    compact?: boolean
    actionVariant?: 'primary' | 'secondary' | 'ghost' | 'danger' | 'cta' | 'dark' | 'light'
}

const props = withDefaults(defineProps<Props>(), {
    description: '',
    actionText: '',
    tone: 'neutral',
    icon: '',
    compact: false,
    actionVariant: 'dark'
})

const emit = defineEmits<{
    (event: 'action'): void
}>()

const defaultIconMap = {
    neutral: 'empty',
    wedding: 'calendar',
    error: 'warning',
    success: 'success',
    auth: 'user'
} as const

const iconColorMap = {
    neutral: '#B8954A',
    wedding: '#B8954A',
    error: '#9A6B35',
    success: '#71806F',
    auth: '#D9BE82'
} as const

const resolvedTone = computed<keyof typeof defaultIconMap>(() =>
    props.tone in defaultIconMap ? props.tone : 'neutral'
)
const resolvedIcon = computed(() => {
    const icon = typeof props.icon === 'string' ? props.icon.trim() : ''
    return icon || defaultIconMap[resolvedTone.value]
})
const iconColor = computed(() => iconColorMap[resolvedTone.value])
const stateClass = computed(() => [
    `empty-state-block--${resolvedTone.value}`,
    {
        'empty-state-block--compact': props.compact
    }
])
</script>

<script lang="ts">
export default {
    name: 'EmptyState',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.empty-state-block {
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 20rpx;
    min-height: 420rpx;
    padding: 56rpx 36rpx;
    text-align: center;
    border-radius: var(--wm-radius-card, 44rpx);
    background: var(--wm-color-bg-card, #FFFDF8);
    border: 1rpx solid var(--wm-color-border, #D8C9AD);
    box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));

    &::before {
        content: '';
        position: absolute;
        top: 34rpx;
        width: 188rpx;
        height: 188rpx;
        border-radius: 999rpx;
        background: radial-gradient(circle, rgba(217, 190, 130, 0.16) 0, rgba(217, 190, 130, 0) 72%);
        pointer-events: none;
    }

    &__icon {
        position: relative;
        z-index: 1;
        width: 128rpx;
        height: 128rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 999rpx;
        background: var(--wm-color-gold-soft, #F1E5C8);
        border: 1rpx solid var(--wm-color-champagne, #D9BE82);
    }

    &__title,
    &__description {
        position: relative;
        z-index: 1;
    }

    &__title {
        font-size: 32rpx;
        font-weight: 900;
        line-height: 1.35;
        color: var(--wm-text-primary, #191713);
    }

    &__description {
        max-width: 540rpx;
        font-size: 24rpx;
        line-height: 1.65;
        color: var(--wm-text-secondary, #665E52);
    }

    &__action {
        position: relative;
        z-index: 1;
        margin-top: 8rpx;
    }

    &--compact {
        min-height: 300rpx;
        padding: 42rpx 28rpx;
        gap: 16rpx;
    }
}
</style>
