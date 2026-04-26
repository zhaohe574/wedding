<template>
    <view v-if="content.enabled !== 0 && showList.length" class="quick-entry-widget">
        <view v-if="showHeading" class="profile-quick-heading">
            <text class="profile-quick-title">{{ headingTitle }}</text>
            <text v-if="headingMeta" class="profile-quick-meta">{{ headingMeta }}</text>
        </view>

        <scroll-view
            v-if="isRoleEntry"
            :scroll-x="true"
            class="profile-role-scroll"
            :show-scrollbar="false"
        >
            <view class="profile-role-track">
                <view
                    v-for="(item, index) in showList"
                    :key="item.key || index"
                    class="profile-role-pill"
                    :class="{ 'profile-role-pill--disabled': !!item.disabled }"
                    @click="handleClick(item)"
                >
                    <text class="profile-role-title">{{ item.title }}</text>
                    <text v-if="getItemDetail(item)" class="profile-role-desc">
                        {{ getItemDetail(item) }}
                    </text>
                </view>
            </view>
        </scroll-view>

        <view v-else class="profile-entry-panel">
            <view
                v-if="primaryEntry"
                class="profile-entry-primary"
                :class="{ 'profile-entry-primary--disabled': !!primaryEntry.disabled }"
                @click="handleClick(primaryEntry)"
            >
                <view class="profile-entry-primary-main">
                    <text class="profile-entry-primary-title">{{ primaryEntry.title }}</text>
                    <text v-if="getItemDetail(primaryEntry)" class="profile-entry-primary-desc">
                        {{ getItemDetail(primaryEntry) }}
                    </text>
                </view>
                <tn-icon name="right" size="28" color="#C8A45D" />
            </view>

            <view v-if="secondaryEntries.length" class="profile-entry-list">
                <view
                    v-for="(item, index) in secondaryEntries"
                    :key="item.key || index"
                    class="profile-entry-row"
                    :class="{ 'profile-entry-row--disabled': !!item.disabled }"
                    @click="handleClick(item)"
                >
                    <view class="profile-entry-row-main">
                        <text class="profile-entry-row-title">{{ item.title }}</text>
                        <text v-if="getItemDetail(item)" class="profile-entry-row-desc">
                            {{ getItemDetail(item) }}
                        </text>
                    </view>
                    <tn-icon name="right" size="24" color="#8A8A8A" />
                </view>
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
const isRoleEntry = computed(() => headingTitle.value === '角色入口')
const primaryEntry = computed(() => (isRoleEntry.value ? null : showList.value[0] || null))
const secondaryEntries = computed(() => (isRoleEntry.value ? [] : showList.value.slice(1)))

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
    gap: 16rpx;
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
    color: var(--wm-text-primary, #111111);
}

.profile-quick-meta {
    flex-shrink: 0;
    font-size: 22rpx;
    line-height: 1.3;
    font-weight: 600;
    color: var(--wm-color-secondary, #c8a45d);
}

.profile-role-scroll {
    white-space: nowrap;

    &::-webkit-scrollbar {
        display: none;
    }
}

.profile-role-track {
    display: inline-flex;
    align-items: stretch;
    gap: 14rpx;
    width: max-content;
}

.profile-role-pill {
    min-width: 220rpx;
    max-width: 280rpx;
    padding: 20rpx 22rpx;
    border-radius: 16rpx;
    border: 1rpx solid var(--wm-color-border, #e5e5e5);
    background: #ffffff;
    box-sizing: border-box;
    display: inline-flex;
    flex-direction: column;
    gap: 8rpx;

    &:active {
        transform: translateY(1rpx);
        opacity: 0.92;
    }
}

.profile-role-pill--disabled {
    opacity: 0.54;
}

.profile-role-title {
    font-size: 26rpx;
    line-height: 1.35;
    font-weight: 700;
    color: var(--wm-text-primary, #111111);
}

.profile-role-desc {
    font-size: 22rpx;
    line-height: 1.4;
    font-weight: 600;
    color: var(--wm-text-secondary, #4a4a4a);
}

.profile-entry-panel {
    border-radius: var(--wm-user-quick-radius, 16rpx);
    border: 1rpx solid var(--wm-color-border, #e5e5e5);
    background: #ffffff;
    overflow: hidden;
}

.profile-entry-primary {
    min-height: 112rpx;
    padding: 24rpx 26rpx;
    background: var(--wm-color-primary, #0b0b0b);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20rpx;
    box-sizing: border-box;

    &:active {
        opacity: 0.92;
    }
}

.profile-entry-primary--disabled,
.profile-entry-row--disabled {
    opacity: 0.54;
}

.profile-entry-primary-main,
.profile-entry-row-main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.profile-entry-primary-title {
    font-size: 30rpx;
    line-height: 1.35;
    font-weight: 700;
    color: #ffffff;
}

.profile-entry-primary-desc {
    font-size: 23rpx;
    line-height: 1.4;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.72);
}

.profile-entry-list {
    display: flex;
    flex-direction: column;
}

.profile-entry-row {
    min-height: 96rpx;
    padding: 0 24rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
    border-top: 1rpx solid var(--wm-color-border, #e5e5e5);
    box-sizing: border-box;

    &:active {
        background: var(--wm-color-bg-soft, #f7f7f7);
    }
}

.profile-entry-row-title {
    font-size: 27rpx;
    line-height: 1.35;
    font-weight: 700;
    color: var(--wm-text-primary, #111111);
}

.profile-entry-row-desc {
    font-size: 22rpx;
    line-height: 1.4;
    font-weight: 600;
    color: var(--wm-text-tertiary, #8a8a8a);
}
</style>
