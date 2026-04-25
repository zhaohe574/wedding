<template>
    <tn-button
        :class="buttonClass"
        :style="buttonVars"
        :size="computedSize"
        :shape="shape"
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
import { alphaColor, resolveReadableTextColor } from '@/utils/color'

type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger' | 'cta'
type LegacyButtonType = 'primary' | 'secondary' | 'cta' | 'ghost' | 'danger'

interface Props {
    variant?: ButtonVariant
    type?: LegacyButtonType
    size?: 'lg' | 'md' | 'sm'
    shape?: 'round' | 'square'
    block?: boolean
    disabled?: boolean
    loading?: boolean
    textColor?: string
    radius?: string
    height?: string
    fontSize?: string
    shadow?: string
    activeShadow?: string
}

const props = withDefaults(defineProps<Props>(), {
    variant: undefined,
    type: 'primary',
    size: 'md',
    shape: 'round',
    block: false,
    disabled: false,
    loading: false,
    radius: '',
    height: '',
    fontSize: '',
    shadow: '',
    activeShadow: ''
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
        return 'cta'
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

    if (resolvedVariant.value === 'cta') {
        return themeStore.ctaColor || '#D0021B'
    }

    return resolvedVariant.value === 'danger'
        ? 'var(--wm-color-danger, #8A4B45)'
        : themeStore.primaryColor
})

const textColor = computed(() => {
    if (props.textColor) {
        return props.textColor
    }

    if (resolvedVariant.value === 'secondary' || resolvedVariant.value === 'ghost') {
        return themeStore.primaryColor
    }

    if (resolvedVariant.value === 'danger') {
        return resolveReadableTextColor('#8A4B45', themeStore.btnColor)
    }

    if (resolvedVariant.value === 'cta') {
        return '#FFFFFF'
    }

    return resolveReadableTextColor(themeStore.primaryColor, themeStore.btnColor)
})

const borderColor = computed(() => {
    if (resolvedVariant.value === 'primary') {
        return 'transparent'
    }

    if (resolvedVariant.value === 'cta') {
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
    const sharedVars: Record<string, string> = {
        '--button-radius': props.radius || 'var(--wm-radius-pill, 999rpx)'
    }

    if (props.height) {
        sharedVars['--button-height'] = props.height
    }

    if (props.fontSize) {
        sharedVars['--button-font-size'] = props.fontSize
    }

    if (resolvedVariant.value === 'primary') {
        return {
            ...sharedVars,
            '--button-bg-start': themeStore.primaryColor,
            '--button-bg-end': themeStore.primaryColor,
            '--button-shadow':
                props.shadow || `0 12rpx 24rpx ${alphaColor(themeStore.primaryColor, 0.18)}`,
            '--button-shadow-active':
                props.activeShadow || `0 6rpx 12rpx ${alphaColor(themeStore.primaryColor, 0.12)}`
        }
    }

    if (resolvedVariant.value === 'cta') {
        return {
            ...sharedVars,
            '--button-bg-start': themeStore.ctaColor || '#D0021B',
            '--button-bg-end': themeStore.ctaColor || '#D0021B',
            '--button-shadow':
                props.shadow || '0 12rpx 24rpx rgba(208, 2, 27, 0.18)',
            '--button-shadow-active':
                props.activeShadow || '0 6rpx 12rpx rgba(208, 2, 27, 0.12)'
        }
    }

    if (resolvedVariant.value === 'danger') {
        return {
            ...sharedVars,
            '--button-bg-start': 'var(--wm-color-danger, #8A4B45)',
            '--button-bg-end': 'var(--wm-color-danger, #8A4B45)',
            '--button-shadow': props.shadow || '0 10rpx 20rpx rgba(138, 75, 69, 0.16)',
            '--button-shadow-active': props.activeShadow || '0 5rpx 10rpx rgba(138, 75, 69, 0.12)'
        }
    }

    if (resolvedVariant.value === 'ghost') {
        return {
            ...sharedVars,
            '--button-bg-start': 'rgba(248,247,242,0.82)',
            '--button-bg-end': 'rgba(248,247,242,0.82)',
            '--button-shadow': props.shadow || 'none',
            '--button-shadow-active': props.activeShadow || 'none'
        }
    }

    return {
        ...sharedVars,
        '--button-bg-start': '#FFFFFF',
        '--button-bg-end': '#FFFFFF',
        '--button-shadow': props.shadow || 'none',
        '--button-shadow-active': props.activeShadow || 'none'
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
    border-radius: var(--button-radius, var(--wm-radius-pill, 999rpx));

    &:active {
        transform: translateY(2rpx) scale(0.99);
    }

    :deep(.tn-button) {
        width: auto;
        border-radius: var(--button-radius, var(--wm-radius-pill, 999rpx));
        font-weight: 600;
        letter-spacing: 0;
        transition: all var(--wm-motion-base, 220ms) ease;
        min-width: 88rpx;
    }

    &--block {
        width: 100%;

        :deep(.tn-button) {
            width: 100%;
        }
    }

    &--primary,
    &--cta,
    &--danger {
        :deep(.tn-button) {
            background: linear-gradient(
                135deg,
                var(--button-bg-start) 0%,
                var(--button-bg-end) 100%
            );
            box-shadow: var(--button-shadow);
            border: none;

            &:active {
                box-shadow: var(--button-shadow-active);
            }
        }
    }

    &--secondary {
        :deep(.tn-button) {
            background: rgba(255, 255, 255, 0.94);
            border-width: 1rpx;
            border-color: rgba(200, 164, 93, 0.28);
            box-shadow: none;
        }
    }

    &--ghost {
        :deep(.tn-button) {
            background: rgba(248, 247, 242, 0.82);
            border-width: 1rpx;
            border-color: rgba(226, 222, 213, 0.96);
            box-shadow: none;
        }
    }

    &--lg {
        :deep(.tn-button) {
            min-height: var(--button-height, 88rpx);
            padding: 0 32rpx;
            font-size: var(--button-font-size, 28rpx);
        }
    }

    &--md {
        :deep(.tn-button) {
            min-height: var(--button-height, 76rpx);
            padding: 0 28rpx;
            font-size: var(--button-font-size, 26rpx);
        }
    }

    &--sm {
        :deep(.tn-button) {
            min-height: var(--button-height, 60rpx);
            padding: 0 22rpx;
            font-size: var(--button-font-size, 24rpx);
        }
    }
}
</style>
