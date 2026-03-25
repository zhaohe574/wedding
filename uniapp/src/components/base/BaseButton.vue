<template>
    <tn-button
        :class="buttonClass"
        :style="buttonVars"
        :size="computedSize"
        :shape="'round'"
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

interface Props {
    type?: 'primary' | 'secondary' | 'cta'
    size?: 'lg' | 'md' | 'sm'
    disabled?: boolean
    loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    type: 'primary',
    size: 'md',
    disabled: false,
    loading: false
})

const emit = defineEmits<{
    (event: 'click', payload: Event): void
}>()

const themeStore = useThemeStore()

// 计算按钮类名
const buttonClass = computed(() => {
    const classes = ['base-button', `base-button--${props.type}`, `base-button--${props.size}`]
    return classes.join(' ')
})

// 计算图鸟UI的尺寸映射
const computedSize = computed(() => {
    const sizeMap = {
        lg: 'lg',
        md: 'md',
        sm: 'sm'
    }
    return sizeMap[props.size]
})

// 计算背景色
const bgColor = computed(() => {
    if (props.type === 'primary') {
        return themeStore.primaryColor
    } else if (props.type === 'cta') {
        return themeStore.ctaColor
    } else if (props.type === 'secondary') {
        return 'transparent'
    }
    return themeStore.primaryColor
})

// 计算文字颜色
const textColor = computed(() => {
    if (props.type === 'secondary') {
        return themeStore.primaryColor
    }
    return themeStore.btnColor
})

// 计算边框颜色
const borderColor = computed(() => {
    if (props.type === 'secondary') {
        return alphaColor(themeStore.primaryColor, 0.26)
    }
    return 'transparent'
})

const buttonVars = computed(() => {
    if (props.type === 'primary') {
        return {
            '--button-bg-start': themeStore.primaryColor,
            '--button-bg-end': themeStore.secondaryColor,
            '--button-shadow': `0 14rpx 28rpx ${alphaColor(themeStore.primaryColor, 0.24)}`,
            '--button-shadow-active': `0 8rpx 16rpx ${alphaColor(themeStore.primaryColor, 0.2)}`
        }
    }
    if (props.type === 'cta') {
        return {
            '--button-bg-start': themeStore.ctaColor,
            '--button-bg-end': themeStore.accentColor,
            '--button-shadow': `0 14rpx 28rpx ${alphaColor(themeStore.ctaColor, 0.22)}`,
            '--button-shadow-active': `0 8rpx 16rpx ${alphaColor(themeStore.ctaColor, 0.18)}`
        }
    }
    return {
        '--button-bg-start': themeStore.surfaceElevatedColor,
        '--button-bg-end': themeStore.surfaceElevatedColor,
        '--button-shadow': 'none',
        '--button-shadow-active': 'none'
    }
})

// 处理点击事件
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
    transition: all var(--cinema-motion-base, 220ms) cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 999rpx;

    &:active {
        transform: translateY(2rpx) scale(0.99);
    }

    &--primary {
        :deep(.tn-button) {
            background: linear-gradient(135deg, var(--button-bg-start) 0%, var(--button-bg-end) 100%);
            box-shadow: var(--button-shadow);
            font-weight: 600;
            letter-spacing: 1rpx;
            border: none;

            &:active {
                box-shadow: var(--button-shadow-active);
            }
        }
    }

    &--secondary {
        :deep(.tn-button) {
            font-weight: 600;
            border-width: 1rpx;
            background: var(--cinema-surface-elevated, #fffdf8);
            box-shadow: inset 0 1rpx 0 rgba(255, 255, 255, 0.48);

            &:active {
                opacity: 0.86;
            }
        }
    }

    &--cta {
        :deep(.tn-button) {
            background: linear-gradient(135deg, var(--button-bg-start) 0%, var(--button-bg-end) 100%);
            box-shadow: var(--button-shadow);
            font-weight: 600;
            letter-spacing: 1rpx;
            border: none;

            &:active {
                box-shadow: var(--button-shadow-active);
            }
        }
    }

    &--lg {
        :deep(.tn-button) {
            height: 78rpx;
            padding: 0 34rpx;
            font-size: 30rpx;
        }
    }

    &--md {
        :deep(.tn-button) {
            height: 68rpx;
            padding: 0 30rpx;
            font-size: 28rpx;
        }
    }

    &--sm {
        :deep(.tn-button) {
            height: 58rpx;
            padding: 0 24rpx;
            font-size: 24rpx;
            font-weight: 600;
            letter-spacing: 0.5rpx;
        }
    }
}
</style>
