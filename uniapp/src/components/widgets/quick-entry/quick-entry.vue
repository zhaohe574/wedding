<template>
    <view v-if="content.enabled !== 0 && showList.length" class="quick-entry-widget">
        <view v-if="showHeading" class="profile-quick-heading">
            <text class="profile-quick-title">{{ headingTitle }}</text>
            <text v-if="headingMeta" class="profile-quick-meta">{{ headingMeta }}</text>
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
                <view class="profile-quick-item-top">
                    <text class="profile-quick-item-title">{{ item.title }}</text>
                    <text class="profile-quick-item-arrow">›</text>
                </view>
                <text v-if="getItemDetail(item)" class="profile-quick-item-desc">
                    {{ getItemDetail(item) }}
                </text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { hasConfiguredLink, navigateTo } from '@/utils/util'
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

const conciseSubtitleMap: Record<string, string> = {
    favorite: '已收藏',
    aftersale: '售后进度',
    waitlist: '候补进度',
    settings: '账号设置',
    notification: '消息更新',
    profile: '资料维护'
}

const showList = computed<QuickEntryItem[]>(() => {
    const list = Array.isArray(props.content?.data) ? props.content.data : []
    return list.filter(
        (item: QuickEntryItem) =>
            String(item.is_show ?? '1') !== '0' && hasConfiguredLink(item.link)
    )
})

const headingTitle = computed(() => {
    const title = String(props.content?.title || '').trim()
    if (!title || title === '快捷功能') return '账户入口'
    return title
})

const headingMeta = computed(() => {
    const subtitle = String(props.content?.subtitle || '').trim()
    if (subtitle && subtitle !== '常用入口') return subtitle
    return `${showList.value.length} 项`
})

const showHeading = computed(() => Boolean(headingTitle.value || headingMeta.value))

const getItemDetail = (item: QuickEntryItem) => {
    const rawSubtitle = String(item.subtitle || '').trim()
    if (!rawSubtitle || rawSubtitle === '点击进入') return ''

    if (/\d/.test(rawSubtitle) || /婚期|待处理|进行中|同步/.test(rawSubtitle)) {
        return rawSubtitle
    }

    return conciseSubtitleMap[item.key || ''] || rawSubtitle
}

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
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.profile-quick-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    padding: 0 4rpx;
}

.profile-quick-title {
    min-width: 0;
    font-size: 24rpx;
    line-height: 1.4;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.profile-quick-meta {
    flex-shrink: 0;
    font-size: 22rpx;
    line-height: 1.3;
    font-weight: 600;
    color: var(--wm-text-tertiary, #9d948f);
}

.profile-quick-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--wm-user-quick-grid-gap, 20rpx);
}

.profile-quick-item {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 12rpx;
    min-height: var(--wm-user-quick-item-height, 126rpx);
    border-radius: var(--wm-user-quick-item-radius, 36rpx);
    padding: var(--wm-user-quick-item-padding, 24rpx);
    background: rgba(255, 255, 255, 0.8);
    border: 2rpx solid rgba(239, 230, 225, 0.9);
    box-sizing: border-box;
    box-shadow: 0 12rpx 28rpx rgba(214, 185, 167, 0.08);
    transition: all 0.2s ease;

    &:active {
        transform: translateY(1rpx);
        opacity: 0.92;
    }
}

.profile-quick-item--primary {
    background: linear-gradient(
        180deg,
        rgba(255, 245, 241, 0.92) 0%,
        rgba(255, 255, 255, 0.88) 100%
    );
    border-color: rgba(244, 199, 191, 0.9);
}

.profile-quick-item--disabled {
    opacity: 0.58;
}

.profile-quick-item-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12rpx;
}

.profile-quick-item-title {
    min-width: 0;
    display: block;
    flex: 1;
    font-size: 28rpx;
    line-height: 1.4;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
    word-break: break-word;
}

.profile-quick-item-arrow {
    flex-shrink: 0;
    font-size: 28rpx;
    line-height: 1;
    color: var(--wm-text-tertiary, #a89d97);
}

.profile-quick-item-desc {
    display: -webkit-box;
    font-size: 22rpx;
    line-height: 1.45;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);
    overflow: hidden;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
</style>
