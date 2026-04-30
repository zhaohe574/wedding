<template>
    <tn-button
        :class="buttonClass"
        :style="buttonVars"
        :size="computedSize"
        :width="computedWidth"
        :height="computedHeight"
        :font-size="computedFontSize"
        :custom-style="buttonCustomStyle"
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

const buttonSizeStyleMap = {
    lg: {
        tnSize: 'lg',
        height: '88rpx',
        fontSize: '28rpx',
        padding: '0 32rpx'
    },
    md: {
        tnSize: '',
        height: '76rpx',
        fontSize: '26rpx',
        padding: '0 28rpx'
    },
    sm: {
        tnSize: 'sm',
        height: '60rpx',
        fontSize: '24rpx',
        padding: '0 22rpx'
    }
} as const

const currentSizeStyle = computed(() => buttonSizeStyleMap[props.size])

const computedSize = computed(() => currentSizeStyle.value.tnSize)

const computedWidth = computed(() => (props.block ? '100%' : ''))

const computedHeight = computed(() => props.height || currentSizeStyle.value.height)

const computedFontSize = computed(() => props.fontSize || currentSizeStyle.value.fontSize)

const bgColor = computed(() => {
    if (resolvedVariant.value === 'secondary' || resolvedVariant.value === 'ghost') {
        return 'transparent'
    }

    if (resolvedVariant.value === 'cta') {
        return themeStore.ctaColor || '#0B0B0B'
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
            '--button-bg-start': themeStore.ctaColor || '#0B0B0B',
            '--button-bg-end': themeStore.ctaColor || '#0B0B0B',
            '--button-shadow': props.shadow || '0 12rpx 24rpx rgba(11, 11, 11, 0.16)',
            '--button-shadow-active': props.activeShadow || '0 6rpx 12rpx rgba(11, 11, 11, 0.12)'
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
            '--button-bg-start': '#F7F7F7',
            '--button-bg-end': '#F7F7F7',
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

const buttonCustomStyle = computed<Record<string, string>>(() => {
    const style: Record<string, string> = {
        width: props.block ? '100%' : 'auto',
        height: computedHeight.value,
        minHeight: computedHeight.value,
        padding: currentSizeStyle.value.padding,
        borderRadius: props.radius || 'var(--wm-radius-pill, 999rpx)',
        boxSizing: 'border-box',
        fontWeight: '600',
        letterSpacing: '0',
        lineHeight: '1',
        transition: 'all var(--wm-motion-base, 220ms) ease'
    }

    if (
        resolvedVariant.value === 'primary' ||
        resolvedVariant.value === 'cta' ||
        resolvedVariant.value === 'danger'
    ) {
        style.backgroundImage =
            'linear-gradient(135deg, var(--button-bg-start) 0%, var(--button-bg-end) 100%)'
        style.boxShadow = 'var(--button-current-shadow, var(--button-shadow))'
        style.border = 'none'
    } else if (resolvedVariant.value === 'secondary') {
        style.backgroundColor = 'rgba(255, 255, 255, 0.94)'
        style.border = '1rpx solid rgba(11, 11, 11, 0.12)'
        style.boxShadow = 'none'
    } else if (resolvedVariant.value === 'ghost') {
        style.backgroundColor = '#F7F7F7'
        style.border = '1rpx solid rgba(11, 11, 11, 0.1)'
        style.boxShadow = 'none'
    }

    return style
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
@mixin base-button-frame {
    width: auto;
    min-width: 88rpx;
    border-radius: var(--button-radius, var(--wm-radius-pill, 999rpx));
    box-sizing: border-box;
    font-weight: 600;
    letter-spacing: 0;
    line-height: 1;
    transition: all var(--wm-motion-base, 220ms) ease;
}

@mixin base-button-filled {
    background-image: linear-gradient(135deg, var(--button-bg-start) 0%, var(--button-bg-end) 100%);
    box-shadow: var(--button-current-shadow, var(--button-shadow));
    border: none;
}

@mixin base-button-subtle($background, $border) {
    background: $background;
    border: 1rpx solid $border;
    box-shadow: none;
}

.base-button {
    @include base-button-frame;
    transition: all var(--wm-motion-base, 220ms) cubic-bezier(0.4, 0, 0.2, 1);

    &:active {
        transform: translateY(2rpx) scale(0.99);
    }

    /* 兼容 tn-button 被额外 wrapper 包住的端；普通端样式直接落在当前根节点。 */
    :deep(.tn-button) {
        @include base-button-frame;
    }
}

.base-button.base-button--block {
    width: 100%;

    :deep(.tn-button) {
        width: 100%;
    }
}

.base-button.base-button--primary,
.base-button.base-button--cta,
.base-button.base-button--danger {
    @include base-button-filled;

    &:active {
        --button-current-shadow: var(--button-shadow-active);
        box-shadow: var(--button-shadow-active);
    }

    :deep(.tn-button) {
        @include base-button-filled;
    }
}

.base-button.base-button--secondary {
    @include base-button-subtle(rgba(255, 255, 255, 0.94), rgba(11, 11, 11, 0.12));

    :deep(.tn-button) {
        @include base-button-subtle(rgba(255, 255, 255, 0.94), rgba(11, 11, 11, 0.12));
    }
}

.base-button.base-button--ghost {
    @include base-button-subtle(#f7f7f7, rgba(11, 11, 11, 0.1));

    :deep(.tn-button) {
        @include base-button-subtle(#f7f7f7, rgba(11, 11, 11, 0.1));
    }
}

.base-button.base-button--lg {
    min-height: var(--button-height, 88rpx);
    padding: 0 32rpx;
    font-size: var(--button-font-size, 28rpx);

    :deep(.tn-button) {
        min-height: var(--button-height, 88rpx);
        padding: 0 32rpx;
        font-size: var(--button-font-size, 28rpx);
    }
}

.base-button.base-button--md {
    min-height: var(--button-height, 76rpx);
    padding: 0 28rpx;
    font-size: var(--button-font-size, 26rpx);

    :deep(.tn-button) {
        min-height: var(--button-height, 76rpx);
        padding: 0 28rpx;
        font-size: var(--button-font-size, 26rpx);
    }
}

.base-button.base-button--sm {
    min-height: var(--button-height, 60rpx);
    padding: 0 22rpx;
    font-size: var(--button-font-size, 24rpx);

    :deep(.tn-button) {
        min-height: var(--button-height, 60rpx);
        padding: 0 22rpx;
        font-size: var(--button-font-size, 24rpx);
    }
}
</style>
