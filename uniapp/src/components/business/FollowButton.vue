<template>
    <view
        class="follow-button"
        :class="[isFollowed ? 'followed' : 'not-followed', sizeClass, { loading: loading }]"
        @tap="handleClick"
    >
        <tn-icon
            v-if="showIcon"
            :name="isFollowed ? 'check' : 'add'"
            :size="iconSize"
            :color="iconColor"
            class="follow-icon"
        />
        <text class="follow-text">{{ buttonText }}</text>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    /** 是否已关注 */
    isFollowed?: boolean
    /** 按钮尺寸：sm(52rpx) | md(64rpx) | lg(72rpx) */
    size?: 'sm' | 'md' | 'lg'
    /** 是否显示图标 */
    showIcon?: boolean
    /** 加载状态 */
    loading?: boolean
    /** 自定义文本 */
    followText?: string
    followedText?: string
}

const props = withDefaults(defineProps<Props>(), {
    isFollowed: false,
    size: 'sm',
    showIcon: true,
    loading: false,
    followText: '关注',
    followedText: '已关注'
})

const emit = defineEmits<{
    (event: 'click'): void
}>()

// 按钮文本
const buttonText = computed(() => {
    if (props.loading) return '处理中...'
    return props.isFollowed ? props.followedText : props.followText
})

// 尺寸类名
const sizeClass = computed(() => `size-${props.size}`)

// 图标尺寸
const iconSize = computed(() => {
    const sizeMap = {
        sm: '22',
        md: '26',
        lg: '28'
    }
    return sizeMap[props.size]
})

// 图标颜色
const iconColor = computed(() => {
    return props.isFollowed ? 'var(--color-primary, #7C3AED)' : 'var(--color-btn-text, #FFFFFF)'
})

// 点击处理
const handleClick = () => {
    if (props.loading) return
    emit('click')
}
</script>

<style lang="scss" scoped>
.follow-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    border-radius: 999rpx;
    font-weight: 600;
    transition: all 0.2s ease;
    user-select: none;
    border: 1rpx solid transparent;

    // 未关注状态
    &.not-followed {
        background: linear-gradient(
            135deg,
            var(--color-primary, #7c3aed) 0%,
            var(--color-primary-dark-2, #6d28d9) 100%
        );
        color: var(--color-btn-text, #ffffff);
        box-shadow: 0 8rpx 20rpx rgba(15, 23, 42, 0.12);

        &:active {
            transform: scale(0.98);
            box-shadow: 0 4rpx 12rpx rgba(15, 23, 42, 0.1);
        }
    }

    // 已关注状态
    &.followed {
        background: var(--color-primary-light-9, rgba(124, 58, 237, 0.08));
        color: var(--color-primary, #7c3aed);
        border-color: var(--color-primary-light-7, rgba(124, 58, 237, 0.2));

        &:active {
            transform: scale(0.98);
            background: var(--color-primary-light-7, rgba(124, 58, 237, 0.12));
        }
    }

    // 加载状态
    &.loading {
        opacity: 0.6;
        pointer-events: none;
    }

    // 小尺寸 (52rpx)
    &.size-sm {
        height: 56rpx;
        padding: 0 24rpx;
        font-size: 24rpx;
        min-width: 120rpx;
    }

    // 中尺寸 (64rpx)
    &.size-md {
        height: 64rpx;
        padding: 0 28rpx;
        font-size: 26rpx;
        min-width: 128rpx;
    }

    // 大尺寸 (72rpx)
    &.size-lg {
        height: 72rpx;
        padding: 0 32rpx;
        font-size: 28rpx;
        min-width: 144rpx;
    }
}

.follow-icon {
    flex-shrink: 0;
}

.follow-text {
    white-space: nowrap;
    line-height: 1;
}
</style>
