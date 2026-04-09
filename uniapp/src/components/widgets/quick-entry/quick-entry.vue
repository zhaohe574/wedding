<template>
    <view v-if="content.enabled !== 0 && showList.length" class="quick-entry-widget">
        <view class="profile-quick-card">
            <view class="profile-quick-title-row">
                <text class="profile-quick-title">{{ content.title || '快捷功能' }}</text>
                <text class="profile-quick-subtitle">{{ content.subtitle || '常用入口' }}</text>
            </view>

            <view class="profile-quick-grid">
                <view
                    v-for="(item, index) in showList"
                    :key="item.key || index"
                    class="profile-quick-item"
                    :class="{
                        'profile-quick-item--primary': index === 0,
                        'profile-quick-item--disabled': !!item.disabled
                    }"
                    @click="handleClick(item)"
                >
                    <text class="profile-quick-item-title">{{ item.title }}</text>
                    <text class="profile-quick-item-desc">{{ item.subtitle || '点击进入' }}</text>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { navigateTo } from '@/utils/util'
import { computed } from 'vue'

interface QuickEntryItem {
    key?: string
    title: string
    subtitle?: string
    link?: any
    is_show?: string
    disabled?: boolean
    requiresLogin?: boolean
}

const props = defineProps({
    content: {
        type: Object,
        default: () => ({})
    },
    styles: {
        type: Object,
        default: () => ({})
    },
    isLogin: {
        type: Boolean,
        default: false
    }
})

const showList = computed<QuickEntryItem[]>(() => {
    const list = Array.isArray(props.content?.data) ? props.content.data : []
    return list.filter((item: QuickEntryItem) => String(item.is_show ?? '1') !== '0')
})

const handleClick = (item: QuickEntryItem) => {
    if (item.disabled) {
        uni.showToast({ title: item.subtitle || '当前不可用', icon: 'none' })
        return
    }

    if (item.requiresLogin && !props.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }

    navigateTo(item.link)
}
</script>

<style scoped lang="scss">
.quick-entry-widget {
    position: relative;
    width: 100%;
}

.profile-quick-card {
    background: var(--wm-color-bg-card, rgba(255, 255, 255, 0.84));
    border: 2rpx solid var(--wm-color-border, #efe6e1);
    border-radius: var(--wm-user-quick-radius, 48rpx);
    padding: var(--wm-user-quick-padding, 32rpx);
    backdrop-filter: blur(24rpx);
    -webkit-backdrop-filter: blur(24rpx);
    box-shadow: var(--wm-shadow-soft, 0 16rpx 34rpx rgba(214, 185, 167, 0.12));
}

.profile-quick-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--wm-user-quick-title-gap, 24rpx);
}

.profile-quick-title {
    font-size: 34rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.profile-quick-subtitle {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);
}

.profile-quick-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--wm-user-quick-grid-gap, 20rpx);
}

.profile-quick-item {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: var(--wm-user-quick-item-gap, 8rpx);
    min-height: var(--wm-user-quick-item-height, 126rpx);
    border-radius: var(--wm-user-quick-item-radius, 36rpx);
    padding: var(--wm-user-quick-item-padding, 24rpx);
    background: rgba(255, 248, 245, 0.92);
    border: 2rpx solid var(--wm-color-border, #efe6e1);
    box-sizing: border-box;
    transition: all 0.2s ease;

    &:active {
        transform: translateY(1rpx);
        opacity: 0.92;
    }
}

.profile-quick-item--primary {
    background: var(--wm-color-bg-soft, #fff1ee);
    border-color: var(--wm-color-border-strong, #f4c7bf);
}

.profile-quick-item--disabled {
    opacity: 0.58;
}

.profile-quick-item-title {
    display: block;
    font-size: 28rpx;
    line-height: 1.4;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
    word-break: break-word;
}

.profile-quick-item-desc {
    display: -webkit-box;
    font-size: 22rpx;
    line-height: 1.5;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);
    overflow: hidden;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
</style>
