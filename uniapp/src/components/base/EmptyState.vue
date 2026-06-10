<template>
    <view class="empty-state-block" :class="stateClass">
        <view class="empty-state-block__icon">
            <slot name="icon">
                <tn-icon :name="resolvedIcon" size="112" :color="iconColor" />
            </slot>
        </view>
        <text class="empty-state-block__title">{{ title }}</text>
        <text v-if="description" class="empty-state-block__description">{{ description }}</text>
        <BaseButton
            v-if="actionText"
            :variant="actionVariant"
            :size="compact ? 'sm' : 'md'"
            @click="emit('action')"
        >
            {{ actionText }}
        </BaseButton>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseButton from './BaseButton.vue'

interface Props {
    title: string
    description?: string
    actionText?: string
    tone?: 'neutral' | 'wedding' | 'error' | 'success' | 'auth'
    icon?: string
    compact?: boolean
    actionVariant?: 'primary' | 'secondary' | 'ghost' | 'danger' | 'cta'
}

const props = withDefaults(defineProps<Props>(), {
    description: '',
    actionText: '',
    tone: 'neutral',
    icon: '',
    compact: false,
    actionVariant: 'cta'
})

const emit = defineEmits<{
    (event: 'action'): void
}>()

const defaultIconMap = {
    neutral: 'inbox',
    wedding: 'calendar',
    error: 'warning',
    success: 'success',
    auth: 'user'
} as const

const iconColorMap = {
    neutral: '#D8D3C7',
    wedding: '#C8A45D',
    error: '#8A4B45',
    success: '#4F6F5A',
    auth: '#0B0B0B'
} as const

const resolvedIcon = computed(() => props.icon || defaultIconMap[props.tone])
const iconColor = computed(() => iconColorMap[props.tone])
const stateClass = computed(() => [
    `empty-state-block--${props.tone}`,
    {
        'empty-state-block--compact': props.compact
    }
])
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
    border-radius: var(--wm-radius-card-lg, 28rpx);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.72) 100%);
    border: 1rpx dashed rgba(200, 164, 93, 0.32);

    &::before {
        content: '';
        position: absolute;
        top: 36rpx;
        width: 172rpx;
        height: 172rpx;
        border-radius: 999rpx;
        background: radial-gradient(circle, rgba(200, 164, 93, 0.16) 0, rgba(200, 164, 93, 0) 72%);
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

    &--compact {
        min-height: 260rpx;
        padding: 48rpx 28rpx;
        gap: 14rpx;
    }

    &--error {
        border-color: rgba(138, 75, 69, 0.24);

        &::before {
            background: radial-gradient(circle, rgba(138, 75, 69, 0.12) 0, rgba(138, 75, 69, 0) 72%);
        }
    }

    &--success {
        border-color: rgba(79, 111, 90, 0.24);

        &::before {
            background: radial-gradient(circle, rgba(79, 111, 90, 0.12) 0, rgba(79, 111, 90, 0) 72%);
        }
    }

    &--auth {
        border-color: rgba(11, 11, 11, 0.14);

        &::before {
            background: radial-gradient(circle, rgba(11, 11, 11, 0.1) 0, rgba(11, 11, 11, 0) 72%);
        }
    }
}
</style>
