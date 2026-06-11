<template>
    <view
        :class="buttonClass"
        :style="buttonStyle"
        :aria-disabled="disabled || loading"
        @click="handleClick"
    >
        <tn-loading
            v-if="loading"
            class="base-button__loading"
            :size="loadingIconSize"
            mode="flower"
            :color="resolvedTextColor"
        />
        <BaseIcon
            v-else-if="safeIcon && resolvedIconPosition === 'left'"
            class="base-button__icon"
            :name="safeIcon"
            :size="iconSize"
            :color="resolvedIconColor"
        />
        <text class="base-button__text">
            <slot>{{ loading && loadingText ? loadingText : label }}</slot>
        </text>
        <BaseIcon
            v-if="!loading && safeIcon && resolvedIconPosition === 'right'"
            class="base-button__icon"
            :name="safeIcon"
            :size="iconSize"
            :color="resolvedIconColor"
        />
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseIcon from './BaseIcon.vue'

type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger' | 'cta' | 'dark' | 'light'
type LegacyButtonType = 'primary' | 'secondary' | 'cta' | 'ghost' | 'danger'

interface Props {
    label?: string
    icon?: string
    iconPosition?: 'left' | 'right'
    variant?: ButtonVariant
    type?: LegacyButtonType
    size?: 'lg' | 'md' | 'sm' | 'mini'
    shape?: 'round' | 'square'
    block?: boolean
    disabled?: boolean
    loading?: boolean
    loadingText?: string
    textColor?: string
    iconColor?: string
    radius?: string
    height?: string
    fontSize?: string
    shadow?: string
    activeShadow?: string
}

const props = withDefaults(defineProps<Props>(), {
    label: '',
    icon: '',
    iconPosition: 'left',
    variant: undefined,
    type: 'primary',
    size: 'md',
    shape: 'round',
    block: false,
    disabled: false,
    loading: false,
    loadingText: '',
    textColor: '',
    iconColor: '',
    radius: '',
    height: '',
    fontSize: '',
    shadow: '',
    activeShadow: ''
})

const emit = defineEmits<{
    (event: 'click', payload: Event): void
}>()

const normalizeString = (value?: string) => (typeof value === 'string' ? value.trim() : '')
const validVariants: ButtonVariant[] = ['primary', 'secondary', 'ghost', 'danger', 'cta', 'dark', 'light']
const validTypes: LegacyButtonType[] = ['primary', 'secondary', 'cta', 'ghost', 'danger']
const validSizes: Array<NonNullable<Props['size']>> = ['lg', 'md', 'sm', 'mini']

const resolvedVariant = computed<ButtonVariant>(() => {
    if (validVariants.includes(props.variant as ButtonVariant)) return props.variant as ButtonVariant
    if (props.type === 'cta') return 'dark'
    if (validTypes.includes(props.type as LegacyButtonType)) return props.type as LegacyButtonType
    return 'primary'
})

const sizeMap = {
    lg: { height: '104rpx', fontSize: '28rpx', padding: '0 40rpx', icon: '36', loading: 34 },
    md: { height: '88rpx', fontSize: '26rpx', padding: '0 32rpx', icon: '32', loading: 30 },
    sm: { height: '72rpx', fontSize: '24rpx', padding: '0 26rpx', icon: '28', loading: 26 },
    mini: { height: '56rpx', fontSize: '22rpx', padding: '0 20rpx', icon: '24', loading: 22 }
} as const

const resolvedSize = computed<keyof typeof sizeMap>(() =>
    validSizes.includes(props.size as keyof typeof sizeMap) ? (props.size as keyof typeof sizeMap) : 'md'
)
const currentSize = computed(() => sizeMap[resolvedSize.value])
const safeIcon = computed(() => normalizeString(props.icon))
const resolvedIconPosition = computed(() => (props.iconPosition === 'right' ? 'right' : 'left'))

const resolvedTextColor = computed(() => {
    if (props.textColor) return props.textColor
    if (['primary', 'cta', 'dark', 'danger'].includes(resolvedVariant.value)) return '#FFFDF8'
    if (resolvedVariant.value === 'secondary') return '#6B4B10'
    return 'var(--wm-text-primary, #1A1A1A)'
})

const resolvedIconColor = computed(() => {
    if (props.iconColor) return props.iconColor
    if (['primary', 'cta', 'dark'].includes(resolvedVariant.value)) return 'var(--wm-color-champagne, #E9C7A7)'
    if (resolvedVariant.value === 'danger') return '#FFFDF8'
    return 'var(--wm-color-gold, #D4916E)'
})

const iconSize = computed(() => currentSize.value.icon)
const loadingIconSize = computed(() => currentSize.value.loading)

const buttonClass = computed(() => [
    'base-button',
    `base-button--${resolvedVariant.value}`,
    `base-button--${resolvedSize.value}`,
    {
        'base-button--block': props.block,
        'base-button--square': props.shape === 'square',
        'base-button--disabled': props.disabled,
        'base-button--loading': props.loading
    }
])

const buttonStyle = computed(() => ({
    minHeight: props.height || currentSize.value.height,
    height: props.height || currentSize.value.height,
    padding: currentSize.value.padding,
    borderRadius:
        props.radius || (props.shape === 'square' ? 'var(--wm-radius-control, 44rpx)' : '999rpx'),
    fontSize: props.fontSize || currentSize.value.fontSize,
    color: resolvedTextColor.value,
    boxShadow: props.shadow || undefined,
    '--button-active-shadow': props.activeShadow || undefined
}))

const handleClick = (event: Event) => {
    if (props.disabled || props.loading) return
    emit('click', event)
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
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    min-width: 112rpx;
    border: 1rpx solid transparent;
    box-sizing: border-box;
    overflow: hidden;
    font-weight: 900;
    line-height: 1;
    letter-spacing: 0;
    transition: transform var(--wm-motion-base, 220ms) ease,
        box-shadow var(--wm-motion-base, 220ms) ease,
        opacity var(--wm-motion-base, 220ms) ease;

    &::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(120deg, rgba(255, 253, 248, 0.16), transparent 38%);
        pointer-events: none;
    }

    &:active {
        transform: translateY(2rpx) scale(0.99);
        box-shadow: var(--button-active-shadow, 0 10rpx 24rpx rgba(74, 43, 24, 0.14));
    }

    &--block {
        width: 100%;
    }

    &--primary,
    &--cta,
    &--dark {
        background: var(--wm-color-primary, #1A1A1A);
        border-color: var(--wm-color-champagne, #E9C7A7);
        box-shadow: var(--wm-shadow-action, 0 20rpx 44rpx rgba(74, 43, 24, 0.18));
    }

    &--secondary {
        background: var(--wm-color-secondary-soft, #F6E2D6);
        border-color: var(--wm-color-champagne, #E9C7A7);
        box-shadow: 0 12rpx 28rpx rgba(212, 145, 110, 0.12);
    }

    &--light {
        background: var(--wm-color-bg-card, #FFFDF8);
        border-color: var(--wm-color-border, #E3D7C9);
        box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));
    }

    &--ghost {
        background: transparent;
        border-color: rgba(26, 26, 26, 0.12);
        box-shadow: none;
    }

    &--danger {
        background: var(--wm-color-clay, #C97957);
        border-color: rgba(255, 253, 248, 0.26);
        box-shadow: 0 16rpx 34rpx rgba(201, 121, 87, 0.18);
    }

    &--disabled {
        opacity: 0.52;
        box-shadow: none;
    }

    &--loading {
        pointer-events: none;
    }

    &__text,
    &__icon,
    &__loading {
        position: relative;
        z-index: 1;
    }

    &__text {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        line-height: 1;
    }
}
</style>
