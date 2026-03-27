<template>
    <tn-button
        :class="buttonClass"
        :style="buttonVars"
        :size="computedSize"
        shape="round"
        :disabled="disabled"
        :loading="loading"
        :bg-color="bgColor"
        :text-color="textColor"
        :border-color="borderColor"
        @click="handleClick"
    >
        <slot />
    </tn-button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useThemeStore } from '@/stores/theme'
import { alphaColor } from '@/utils/color'

type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger'
type LegacyButtonType = 'primary' | 'secondary' | 'cta' | 'ghost' | 'danger'

interface Props {
    variant?: ButtonVariant
    type?: LegacyButtonType
    size?: 'lg' | 'md' | 'sm'
    block?: boolean
    disabled?: boolean
    loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    variant: undefined,
    type: 'primary',
    size: 'md',
    block: false,
    disabled: false,
    loading: false
})

const emit = defineEmits<{
    (event: 'click', payload: Event): void
}>()

const themeStore = useThemeStore()

const resolvedVariant = computed<ButtonVariant>(() => {
    if (props.variant) {
        return props.variant
    }

    if (props.type === 'cta') {
        return 'primary'
    }

    return props.type
})

const buttonClass = computed(() => {
    const classes = [
        'base-button',
        `base-button--${resolvedVariant.value}`,
        `base-button--${props.size}`
    ]

    if (props.block) {
        classes.push('base-button--block')
    }

    return classes.join(' ')
})

const computedSize = computed(() => {
    const sizeMap = {
        lg: 'lg',
        md: 'md',
        sm: 'sm'
    }
    return sizeMap[props.size]
})

const bgColor = computed(() => {
    if (resolvedVariant.value === 'secondary' || resolvedVariant.value === 'ghost') {
        return 'transparent'
    }

    return resolvedVariant.value === 'danger'
        ? 'var(--wm-color-danger, #B44A3A)'
        : themeStore.primaryColor
})

const textColor = computed(() => {
    if (resolvedVariant.value === 'secondary' || resolvedVariant.value === 'ghost') {
        return themeStore.primaryColor
    }

    return themeStore.btnColor
})

const borderColor = computed(() => {
    if (resolvedVariant.value === 'primary') {
        return 'transparent'
    }

    if (resolvedVariant.value === 'danger') {
        return 'transparent'
    }

    if (resolvedVariant.value === 'ghost') {
        return alphaColor(themeStore.primaryColor, 0.14)
    }

    return alphaColor(themeStore.primaryColor, 0.26)
})

const buttonVars = computed(() => {
    if (resolvedVariant.value === 'primary') {
        return {
            '--button-bg-start': themeStore.primaryColor,
            '--button-bg-end': themeStore.primaryColor,
            '--button-shadow': `0 14rpx 28rpx ${alphaColor(themeStore.primaryColor, 0.22)}`,
            '--button-shadow-active': `0 8rpx 16rpx ${alphaColor(themeStore.primaryColor, 0.18)}`
        }
    }

    if (resolvedVariant.value === 'danger') {
        return {
            '--button-bg-start': 'var(--wm-color-danger, #B44A3A)',
            '--button-bg-end': 'var(--wm-color-danger, #B44A3A)',
            '--button-shadow': '0 14rpx 28rpx rgba(180, 74, 58, 0.2)',
            '--button-shadow-active': '0 8rpx 16rpx rgba(180, 74, 58, 0.16)'
        }
    }

    if (resolvedVariant.value === 'ghost') {
        return {
            '--button-bg-start': 'rgba(255,255,255,0.72)',
            '--button-bg-end': 'rgba(255,255,255,0.72)',
            '--button-shadow': 'none',
            '--button-shadow-active': 'none'
        }
    }

    return {
        '--button-bg-start': '#FFFFFF',
        '--button-bg-end': '#FFFFFF',
        '--button-shadow': 'none',
        '--button-shadow-active': 'none'
    }
})

const handleClick = (event: Event) => {
    if (!props.disabled && !props.loading) {
        emit('click', event)
    }
}
</script>

<script lang="ts">
export default {
    name: 'BaseButton',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-button {
    transition: all var(--wm-motion-base, 220ms) cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: var(--wm-radius-pill, 999rpx);

    &:active {
        transform: translateY(2rpx) scale(0.99);
    }

    :deep(.tn-button) {
        width: auto;
        border-radius: var(--wm-radius-pill, 999rpx);
        font-weight: 600;
        letter-spacing: 0.6rpx;
        transition: all var(--wm-motion-base, 220ms) ease;
    }

    &--block {
        width: 100%;

        :deep(.tn-button) {
            width: 100%;
        }
    }

    &--primary,
    &--danger {
        :deep(.tn-button) {
            background: linear-gradient(135deg, var(--button-bg-start) 0%, var(--button-bg-end) 100%);
            box-shadow: var(--button-shadow);
            border: none;

            &:active {
                box-shadow: var(--button-shadow-active);
            }
        }
    }

    &--secondary {
        :deep(.tn-button) {
            background: #ffffff;
            border-width: 1rpx;
            box-shadow: inset 0 1rpx 0 rgba(255, 255, 255, 0.6);
        }
    }

    &--ghost {
        :deep(.tn-button) {
            background: rgba(255, 255, 255, 0.72);
            border-width: 1rpx;
            box-shadow: none;
        }
    }

    &--lg {
        :deep(.tn-button) {
            min-height: 88rpx;
            padding: 0 34rpx;
            font-size: 30rpx;
        }
    }

    &--md {
        :deep(.tn-button) {
            min-height: 76rpx;
            padding: 0 30rpx;
            font-size: 28rpx;
        }
    }

    &--sm {
        :deep(.tn-button) {
            min-height: 64rpx;
            padding: 0 24rpx;
            font-size: 24rpx;
        }
    }
}
</style>
