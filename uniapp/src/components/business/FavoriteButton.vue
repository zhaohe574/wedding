<template>
    <view
        class="favorite-button"
        :class="[isFavorited ? 'favorited' : 'not-favorited', sizeClass, { loading: loading }]"
        @tap.stop="handleClick"
    >
        <tn-icon
            v-if="showIcon"
            :name="isFavorited ? 'star-fill' : 'star'"
            :size="iconSize"
            :color="iconColor"
            class="favorite-icon"
        />
        <text class="favorite-text">{{ buttonText }}</text>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    /** 是否已收藏 */
    isFavorited?: boolean
    /** 按钮尺寸：sm(52rpx) | md(64rpx) | lg(72rpx) */
    size?: 'sm' | 'md' | 'lg'
    /** 是否显示图标 */
    showIcon?: boolean
    /** 加载状态 */
    loading?: boolean
    /** 自定义文本 */
    favoriteText?: string
    favoritedText?: string
}

const props = withDefaults(defineProps<Props>(), {
    isFavorited: false,
    size: 'sm',
    showIcon: true,
    loading: false,
    favoriteText: '收藏',
    favoritedText: '已收藏'
})

const emit = defineEmits<{
    (event: 'click'): void
}>()

const buttonText = computed(() => {
    if (props.loading) return '处理中...'
    return props.isFavorited ? props.favoritedText : props.favoriteText
})

const sizeClass = computed(() => `size-${props.size}`)

const iconSize = computed(() => {
    const sizeMap = {
        sm: '22',
        md: '26',
        lg: '28'
    }
    return sizeMap[props.size]
})

const iconColor = computed(() => {
    return props.isFavorited ? 'var(--color-primary, #0B0B0B)' : 'var(--color-btn-text, #FFFFFF)'
})

const handleClick = () => {
    if (props.loading) return
    emit('click')
}
</script>

<style lang="scss" scoped>
.favorite-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    border-radius: 999rpx;
    font-weight: 600;
    transition: all 0.2s ease;
    user-select: none;
    border: 1rpx solid transparent;

    &.not-favorited {
        background: linear-gradient(
            135deg,
            var(--wm-color-primary, #0b0b0b) 0%,
            var(--wm-color-secondary, #c8a45d) 100%
        );
        color: var(--color-btn-text, #ffffff);
        box-shadow: 0 8rpx 20rpx rgba(17, 17, 17, 0.12);

        &:active {
            transform: scale(0.98);
            box-shadow: 0 4rpx 12rpx rgba(17, 17, 17, 0.1);
        }
    }

    &.favorited {
        background: rgba(11, 11, 11, 0.08);
        color: var(--wm-color-primary, #0b0b0b);
        border-color: rgba(11, 11, 11, 0.18);

        &:active {
            transform: scale(0.98);
            background: rgba(11, 11, 11, 0.12);
        }
    }

    &.loading {
        opacity: 0.6;
        pointer-events: none;
    }

    &.size-sm {
        height: 56rpx;
        padding: 0 24rpx;
        font-size: 24rpx;
        min-width: 120rpx;
    }

    &.size-md {
        height: 64rpx;
        padding: 0 28rpx;
        font-size: 26rpx;
        min-width: 128rpx;
    }

    &.size-lg {
        height: 72rpx;
        padding: 0 32rpx;
        font-size: 28rpx;
        min-width: 144rpx;
    }
}

.favorite-icon {
    flex-shrink: 0;
}

.favorite-text {
    white-space: nowrap;
    line-height: 1;
}
</style>
