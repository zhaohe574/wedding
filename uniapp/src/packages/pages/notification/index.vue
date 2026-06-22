<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace">
        <BaseNavbar
            title="通知中心"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />
        <view class="notification-page wm-page-content">
            <view class="notification-page__content wm-page-stack">
                <view class="notification-page__summary-card">
                    <text class="notification-page__summary-kicker">{{ currentScopeLabel }}</text>
                    <view class="notification-page__summary-count">
                        <text class="notification-page__summary-number">
                            {{ currentUnreadTotal }}
                        </text>
                        <text class="notification-page__summary-unit">条未读</text>
                    </view>
                    <view v-if="notificationList.length" class="notification-page__summary-actions">
                        <view
                            class="notification-page__summary-action"
                            @click.stop="handleDeleteRead"
                        >
                            删除已读
                        </view>
                        <view
                            class="notification-page__summary-action notification-page__summary-action--primary"
                            :class="{
                                'notification-page__summary-action--disabled':
                                    currentUnreadTotal <= 0
                            }"
                            @click.stop="handleMarkAllReadFromToolbar"
                        >
                            全部已读
                        </view>
                    </view>
                </view>

                <scroll-view
                    scroll-x
                    class="notification-page__filter-scroll"
                    :show-scrollbar="false"
                >
                    <view class="wm-pill-tabs notification-page__filter-row">
                        <view
                            class="wm-pill-tab notification-page__filter-chip"
                            :class="{ 'wm-pill-tab--active': currentType === 0 }"
                            @click="switchType(0)"
                        >
                            <text>全部</text>
                            <text
                                v-if="hasUnread"
                                class="notification-page__filter-chip-count"
                                :class="{
                                    'notification-page__filter-chip-count--active':
                                        currentType === 0
                                }"
                            >
                                {{ formatUnreadCount(unreadCount.total) }}
                            </text>
                        </view>
                        <view
                            v-for="item in categoryList"
                            :key="item.type"
                            class="wm-pill-tab notification-page__filter-chip"
                            :class="{ 'wm-pill-tab--active': currentType === item.type }"
                            @click="switchType(item.type)"
                        >
                            <text>{{ item.name }}</text>
                            <text
                                v-if="getUnreadByType(item.type) > 0"
                                class="notification-page__filter-chip-count"
                                :class="{
                                    'notification-page__filter-chip-count--active':
                                        currentType === item.type
                                }"
                            >
                                {{ formatUnreadCount(getUnreadByType(item.type)) }}
                            </text>
                        </view>
                    </view>
                </scroll-view>

                <BaseCard
                    v-if="loading && !notificationList.length"
                    class="notification-page__state-card"
                    variant="quiet"
                    padding="42rpx 28rpx"
                    border-radius="32rpx"
                >
                    <LoadingState text="正在同步通知..." />
                </BaseCard>

                <EmptyState
                    v-else-if="!notificationList.length"
                    :title="`暂无${currentTypeLabel}`"
                    icon="notice"
                    compact
                />

                <view v-else class="notice-list">
                    <BaseCard
                        v-for="item in notificationList"
                        :key="item.id"
                        class="notice-card"
                        :class="{
                            'notice-card--read': isNoticeRead(item),
                            'notice-card--unread': !isNoticeRead(item)
                        }"
                        variant="list"
                        padding="24rpx"
                        border-radius="30rpx"
                        border="1rpx solid rgba(216, 201, 173, 0.9)"
                        box-shadow="0 12rpx 28rpx rgba(74, 43, 24, 0.06)"
                        interactive
                        @click="handleItemClick(item)"
                    >
                        <view class="notice-card__top">
                            <view class="notice-card__identity">
                                <view
                                    class="notice-card__icon"
                                    :class="{ 'notice-card__icon--read': isNoticeRead(item) }"
                                >
                                    <BaseIcon
                                        :name="getNoticeIcon(item)"
                                        size="30"
                                        :color="isNoticeRead(item) ? '#9A9388' : '#D9BE82'"
                                    />
                                </view>
                                <view class="notice-card__title-group">
                                    <text class="notice-card__title text-ellipsis">
                                        {{ getNoticeTitle(item) }}
                                    </text>
                                    <view class="notice-card__meta-row">
                                        <StatusBadge
                                            :tone="getNoticeTypeTone(item)"
                                            size="xs"
                                        >
                                            {{ getNoticeTypeLabel(item) }}
                                        </StatusBadge>
                                        <text
                                            v-if="getNoticeTime(item)"
                                            class="notice-card__time text-ellipsis"
                                        >
                                            {{ getNoticeTime(item) }}
                                        </text>
                                    </view>
                                    <text class="notice-card__content text-ellipsis-2">
                                        {{ getNoticeContent(item) }}
                                    </text>
                                </view>
                            </view>
                            <StatusBadge
                                :tone="isNoticeRead(item) ? 'neutral' : 'primary'"
                                size="xs"
                                :dot="!isNoticeRead(item)"
                            >
                                {{ isNoticeRead(item) ? '已读' : '未读' }}
                            </StatusBadge>
                        </view>
                        <view class="notice-card__foot">
                            <view class="notice-card__action-hint">
                                <text class="notice-card__action-text">
                                    {{ getNoticeActionText(item) }}
                                </text>
                                <BaseIcon name="right" size="22" color="#B8954A" />
                            </view>
                            <view
                                class="notice-card__delete"
                                @click.stop="handleDeleteItem(item)"
                            >
                                删除
                            </view>
                        </view>
                    </BaseCard>
                </view>

                <view v-if="!loading && notificationList.length" class="load-more-tip">
                    <text v-if="hasMore">上拉加载更多</text>
                    <text v-else>没有更多了</text>
                </view>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onPullDownRefresh, onReachBottom, onShow } from '@dcloudio/uni-app'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { useThemeStore } from '@/stores/theme'
import { confirmModal, showError, showSuccess } from '@/utils/feedback'
import {
    clearNotification,
    deleteNotification,
    getNotificationDetail,
    getNotificationList,
    getUnreadCount,
    markAllNotificationRead,
    markNotificationRead
} from '@/api/notification'
import type {
    NotificationItem,
    NotificationListParams,
    NotificationUnreadCount
} from '@/types/notification'

const $theme = useThemeStore()

const notificationRouteMap: Record<string, (targetId?: number) => string> = {
    order: (targetId) => `/packages/pages/order_detail/order_detail?id=${targetId || 0}`,
    order_detail: (targetId) => `/packages/pages/order_detail/order_detail?id=${targetId || 0}`,
    staff_order: (targetId) =>
        `/packages/pages/staff_order_detail/staff_order_detail?id=${targetId || 0}`,
    waitlist: () => '/packages/pages/waitlist/waitlist',
    change: (targetId) => `/packages/pages/order_change/change_detail?id=${targetId || 0}`,
    pause: (targetId) => `/packages/pages/order_change/pause_detail?id=${targetId || 0}`,
    aftersale: () => '/packages/pages/aftersale/index',
    ticket: () => '/packages/pages/aftersale/ticket',
    ticket_detail: (targetId) => `/packages/pages/aftersale/ticket_detail?id=${targetId || 0}`,
    complaint: () => '/packages/pages/aftersale/complaint',
    complaint_detail: (targetId) =>
        `/packages/pages/aftersale/complaint_detail?id=${targetId || 0}`,
    callback: () => '/packages/pages/aftersale/callback',
    callback_detail: (targetId) => `/packages/pages/aftersale/callback_detail?id=${targetId || 0}`,
    couple_questionnaire: (targetId) =>
        `/packages/pages/couple_questionnaire/detail?id=${targetId || 0}`,
    review: (targetId) => `/packages/pages/review/detail?id=${targetId || 0}`,
    review_list: () => '/packages/pages/review/list',
    review_detail: (targetId) => `/packages/pages/review/detail?id=${targetId || 0}`,
    dynamic: (targetId) => `/packages/pages/dynamic_detail/dynamic_detail?id=${targetId || 0}`,
    dynamic_detail: (targetId) => `/packages/pages/dynamic_detail/dynamic_detail?id=${targetId || 0}`,
    staff_detail: (targetId) => `/packages/pages/staff_detail/staff_detail?id=${targetId || 0}`
}

const loading = ref(false)
const currentType = ref(0)
const notificationList = ref<NotificationItem[]>([])
const unreadCount = ref<NotificationUnreadCount>({
    total: 0,
    system: 0,
    order: 0,
    interact: 0
})
const page = ref(1)
const hasMore = ref(true)

type NoticeTone =
    | 'neutral'
    | 'success'
    | 'warning'
    | 'danger'
    | 'info'
    | 'primary'
    | 'paid'
    | 'running'
    | 'pending'
    | 'risk'

interface NoticeCategory {
    type: number
    name: string
    unreadKey: 'system' | 'order' | 'interact'
    tone: NoticeTone
    icon: string
}

const categoryList: NoticeCategory[] = [
    { type: 1, name: '系统通知', unreadKey: 'system', tone: 'info', icon: 'notice' },
    { type: 2, name: '订单通知', unreadKey: 'order', tone: 'warning', icon: 'order' },
    { type: 3, name: '互动通知', unreadKey: 'interact', tone: 'success', icon: 'mail' }
]

const currentUnreadTotal = computed(() => {
    if (currentType.value === 0) {
        return Number(unreadCount.value.total || 0)
    }
    return getUnreadByType(currentType.value)
})
const hasUnread = computed(() => Number(unreadCount.value.total || 0) > 0)
const currentTypeLabel = computed(() => {
    if (currentType.value === 0) {
        return '通知'
    }
    return categoryList.find((item) => item.type === currentType.value)?.name || '通知'
})
const currentScopeLabel = computed(() => {
    return currentType.value > 0 ? currentTypeLabel.value : '全部通知'
})
const normalizeText = (value: unknown) => String(value || '').trim()
const isNoticeRead = (item: NotificationItem) => Number(item?.is_read || 0) > 0
const formatUnreadCount = (count?: number | string) => {
    const value = Number(count || 0)
    return value > 99 ? '99+' : `${value}`
}
const getUnreadByType = (type: number) => {
    const item = categoryList.find((target) => target.type === type)
    if (!item) {
        return 0
    }
    return Number(unreadCount.value[item.unreadKey] || 0)
}
const getNoticeCategory = (item: NotificationItem) => {
    const type = Number(item?.notify_type || 0)
    return categoryList.find((target) => target.type === type)
}
const getNoticeTitle = (item: NotificationItem) => normalizeText(item?.title) || '消息通知'
const getNoticeContent = (item: NotificationItem) => normalizeText(item?.content) || '暂无消息内容'
const getNoticeTypeLabel = (item: NotificationItem) => {
    return normalizeText(item?.notify_type_text) || getNoticeCategory(item)?.name || '站内通知'
}
const getNoticeTypeTone = (item: NotificationItem): NoticeTone => {
    return getNoticeCategory(item)?.tone || 'neutral'
}
const getNoticeIcon = (item: NotificationItem) => {
    return getNoticeCategory(item)?.icon || 'notice'
}
const getNoticeTime = (item: NotificationItem) => {
    return normalizeText(item?.create_time_text) || normalizeText(item?.create_time)
}
const getNoticeActionText = (item: NotificationItem) => {
    return normalizeText(item?.target_type) ? '查看关联内容' : '查看详情'
}
const switchType = (type: number) => {
    if (currentType.value === type) {
        return
    }
    currentType.value = type
    loadList(true)
}

const loadUnreadCount = async () => {
    try {
        const res = await getUnreadCount()
        unreadCount.value = res || {}
    } catch (error) {
        console.error(error)
    }
}

const loadList = async (refresh = false) => {
    if (loading.value || (!refresh && !hasMore.value)) return

    if (refresh) {
        page.value = 1
        hasMore.value = true
    }

    loading.value = true
    try {
        const params: NotificationListParams = {
            page: page.value,
            limit: 10
        }
        if (currentType.value > 0) {
            params.notify_type = currentType.value
        }
        const res = await getNotificationList(params)

        const list = res.lists || []
        if (refresh) {
            notificationList.value = list
        } else {
            notificationList.value = [...notificationList.value, ...list]
        }

        hasMore.value = Boolean(res.has_more)
        if (hasMore.value) {
            page.value += 1
        }
    } catch (error) {
        console.error(error)
    } finally {
        loading.value = false
        uni.stopPullDownRefresh()
    }
}

const refreshListState = async () => {
    await Promise.all([loadUnreadCount(), loadList(true)])
}

const openNotificationDetail = async (item: NotificationItem, hint = '') => {
    const notificationId = item.id
    if (notificationId === undefined || notificationId === null || notificationId === '') {
        return
    }
    try {
        const detail = await getNotificationDetail({ id: notificationId })
        const lines = [
            detail?.content || item?.content || '暂无详细内容',
            detail?.create_time_text ? `时间：${detail.create_time_text}` : '',
            hint
        ].filter(Boolean)

        await confirmModal({
            title: detail?.title || item?.title || '消息详情',
            content: lines.join('\n\n'),
            showCancel: false,
            confirmText: '我知道了'
        })
    } catch (error) {
        console.error(error)
        await confirmModal({
            title: item?.title || '消息详情',
            content: [item?.content || '暂无详细内容', hint].filter(Boolean).join('\n\n'),
            showCancel: false,
            confirmText: '我知道了'
        })
    }
}

const navigateByTarget = (item: NotificationItem) => {
    const targetType = String(item?.target_type || '').trim()
    const route = notificationRouteMap[targetType]?.(Number(item?.target_id || 0)) || ''
    if (!route) {
        return false
    }

    uni.navigateTo({
        url: route,
        fail: () => {
            openNotificationDetail(item, '当前消息暂不支持跳转。')
        }
    })
    return true
}

const handleItemClick = async (item: NotificationItem) => {
    if (!isNoticeRead(item)) {
        try {
            if (item.id !== undefined && item.id !== null && item.id !== '') {
                await markNotificationRead({ id: item.id })
            }
            item.is_read = 1
            loadUnreadCount()
        } catch (error) {
            console.error(error)
        }
    }

    if (!item.target_type) {
        openNotificationDetail(item)
        return
    }

    if (navigateByTarget(item)) {
        return
    }

    showError('当前消息仅支持查看详情')
    openNotificationDetail(item, '已为你打开详情。')
}

const handleMarkAllRead = async () => {
    const confirmed = await confirmModal({
        title: '提示',
        content: `确定将${currentScopeLabel.value}标记为已读吗？`
    })
    if (!confirmed) return
    try {
        await markAllNotificationRead({
            notify_type: currentType.value || undefined
        })
        showSuccess('标记成功')
        refreshListState()
    } catch (error) {
        console.error(error)
    }
}

const handleMarkAllReadFromToolbar = () => {
    if (currentUnreadTotal.value <= 0) {
        return
    }
    void handleMarkAllRead()
}

const handleDeleteRead = async () => {
    const confirmed = await confirmModal({
        title: '提示',
        content: `确定删除${currentScopeLabel.value}中的已读消息吗？`
    })
    if (!confirmed) return
    try {
        const result = await clearNotification({
            notify_type: currentType.value || undefined,
            read_status: 1
        })
        await refreshListState()
        if (Number(result?.count || 0) > 0) {
            showSuccess('删除成功')
            return
        }
        showError('没有可删除的已读消息')
    } catch (error) {
        console.error(error)
    }
}

const handleDeleteItem = async (item: NotificationItem) => {
    if (item.id === undefined || item.id === null || item.id === '') {
        return
    }
    const confirmed = await confirmModal({
        title: '提示',
        content: '确定删除这条通知吗？'
    })
    if (!confirmed) return
    try {
        await deleteNotification({ id: item.id })
        showSuccess('删除成功')
        await refreshListState()
    } catch (error) {
        console.error(error)
    }
}

onReachBottom(() => {
    loadList()
})

onPullDownRefresh(() => {
    loadUnreadCount()
    loadList(true)
})

onShow(() => {
    $theme.setScene('consumer')
    loadUnreadCount()
    loadList(true)
})
</script>

<style scoped lang="scss">
.notification-page {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
    padding-top: 16rpx;
    padding-bottom: calc(36rpx + env(safe-area-inset-bottom));
    background: transparent;
}

.notification-page__content {
    gap: 18rpx;
}

.notification-page__summary-card {
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    width: 100%;
    min-height: 86rpx;
    padding: 16rpx 18rpx;
    gap: 8rpx;
    flex-wrap: nowrap;
    border-radius: 28rpx;
    border: 1rpx solid var(--wm-color-champagne, #d9be82);
    background: radial-gradient(circle at 90% -40rpx, rgba(217, 190, 130, 0.24) 0, rgba(217, 190, 130, 0) 170rpx),
        linear-gradient(145deg, #2b261d 0%, #191713 62%, #3a2a16 100%);
    box-shadow: 0 18rpx 38rpx rgba(74, 43, 24, 0.14);
    box-sizing: border-box;
}

.notification-page__summary-card::after {
    display: none;
}

.notification-page__summary-kicker {
    position: relative;
    z-index: 1;
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    max-width: 100rpx;
    min-height: 36rpx;
    padding: 0 10rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    border: 1rpx solid rgba(217, 190, 130, 0.7);
    background: rgba(217, 190, 130, 0.14);
    font-size: 18rpx;
    font-weight: 900;
    line-height: 1;
    color: var(--wm-color-champagne, #d9be82);
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.notification-page__summary-count {
    position: relative;
    z-index: 1;
    min-width: 0;
    flex: 1 1 auto;
    display: flex;
    align-items: baseline;
    gap: 4rpx;
    color: var(--wm-text-inverse, #fffdf8);
    overflow: hidden;
    white-space: nowrap;
}

.notification-page__summary-number {
    font-size: 32rpx;
    font-weight: 900;
    line-height: 1;
}

.notification-page__summary-unit {
    flex-shrink: 0;
    font-size: 19rpx;
    font-weight: 800;
    line-height: 1;
    color: rgba(255, 253, 248, 0.72);
}

.notification-page__summary-actions {
    position: relative;
    z-index: 1;
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
    flex-wrap: nowrap;
    justify-content: flex-end;
}

.notification-page__summary-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 88rpx;
    height: 48rpx;
    padding: 0 8rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    border: 1rpx solid rgba(255, 253, 248, 0.2);
    background: rgba(255, 253, 248, 0.08);
    font-size: 18rpx;
    font-weight: 900;
    line-height: 1;
    color: rgba(255, 253, 248, 0.82);
    box-sizing: border-box;
    white-space: nowrap;
}

.notification-page__summary-action--primary {
    border-color: rgba(217, 190, 130, 0.74);
    background: var(--wm-color-bg-card, #fffdf8);
    color: var(--wm-text-primary, #191713);
}

.notification-page__summary-action--disabled {
    opacity: 0.48;
}

.notification-page__filter-scroll {
    margin: 0 calc(var(--wm-space-page-x, 32rpx) * -1);
    padding: 0 var(--wm-space-page-x, 32rpx);
    white-space: nowrap;
}

.notification-page__filter-row {
    display: inline-flex;
    flex-wrap: nowrap;
    gap: 12rpx;
    padding-bottom: 4rpx;
}

.notification-page__filter-chip {
    flex-shrink: 0;
    gap: 8rpx;
    min-height: 58rpx;
    padding: 0 20rpx;
    box-shadow: 0 8rpx 18rpx rgba(74, 43, 24, 0.04);
}

.notification-page__filter-chip-count {
    min-width: 32rpx;
    padding: 0 8rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(11, 11, 11, 0.1);
    font-size: 20rpx;
    font-weight: 700;
    line-height: 32rpx;
    text-align: center;
    color: var(--wm-color-primary, #0b0b0b);
}

.notification-page__filter-chip-count--active {
    background: rgba(255, 255, 255, 0.18);
    color: #ffffff;
}

.notification-page__state-card {
    display: block;
}

.notice-list {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.notice-card {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.notice-card--unread {
    border-color: rgba(217, 190, 130, 0.84) !important;
    background: linear-gradient(180deg, #fffdf8 0%, rgba(255, 248, 232, 0.96) 100%);
}

.notice-card--read {
    background: rgba(255, 253, 248, 0.76);
    box-shadow: 0 10rpx 24rpx rgba(74, 43, 24, 0.04) !important;
}

.notice-card__top {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14rpx;
}

.notice-card__identity {
    min-width: 0;
    flex: 1;
    display: flex;
    align-items: flex-start;
    gap: 14rpx;
}

.notice-card__icon {
    width: 58rpx;
    height: 58rpx;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 20rpx;
    border: 1rpx solid rgba(217, 190, 130, 0.58);
    background: linear-gradient(145deg, #191713 0%, #2b261d 100%);
    box-shadow: 0 8rpx 18rpx rgba(74, 43, 24, 0.1);
}

.notice-card__icon--read {
    border-color: rgba(216, 201, 173, 0.72);
    background: rgba(248, 242, 228, 0.8);
    box-shadow: none;
}

.notice-card__title-group {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.notice-card__title {
    display: block;
    font-size: 28rpx;
    font-weight: 900;
    line-height: 1.32;
    color: var(--wm-text-primary, #191713);
}

.notice-card__meta-row {
    display: flex;
    align-items: center;
    gap: 10rpx;
    min-width: 0;
    max-width: 100%;
}

.notice-card__time {
    min-width: 0;
    flex: 1;
    font-size: 21rpx;
    line-height: 1.35;
    color: var(--wm-text-tertiary, #9a9388);
}

.notice-card__content {
    position: relative;
    z-index: 1;
    display: block;
    margin-top: 2rpx;
    font-size: 25rpx;
    font-weight: 500;
    line-height: 1.6;
    color: var(--wm-text-secondary, #5f5a50);
}

.notice-card__foot {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14rpx;
    padding-top: 12rpx;
    border-top: 1rpx solid rgba(216, 201, 173, 0.46);
}

.notice-card__action-hint {
    min-width: 0;
    flex: 1;
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
    color: var(--wm-color-gold, #b8954a);
}

.notice-card__action-text {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 23rpx;
    font-weight: 900;
    line-height: 1.4;
}

.notice-card__delete {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 96rpx;
    min-height: 50rpx;
    padding: 0 18rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    border: 1rpx solid rgba(25, 23, 19, 0.08);
    background: rgba(255, 253, 248, 0.72);
    font-size: 21rpx;
    font-weight: 800;
    line-height: 1;
    color: var(--wm-text-tertiary, #9a9388);
}

.load-more-tip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    padding: 10rpx 0 6rpx;
    font-size: 22rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

@media screen and (max-width: 360px) {
    .notification-page__summary-card {
        min-height: 82rpx;
        padding: 15rpx 14rpx;
        gap: 6rpx;
    }

    .notification-page__summary-kicker {
        max-width: 88rpx;
        min-height: 34rpx;
        padding: 0 8rpx;
        font-size: 17rpx;
    }

    .notification-page__summary-count {
        gap: 3rpx;
    }

    .notification-page__summary-number {
        font-size: 30rpx;
    }

    .notification-page__summary-unit {
        font-size: 18rpx;
    }

    .notification-page__summary-actions {
        gap: 5rpx;
    }

    .notification-page__summary-action {
        width: 82rpx;
        height: 46rpx;
        padding: 0 6rpx;
        font-size: 17rpx;
    }

    .notice-card__top {
        gap: 12rpx;
    }

    .notice-card__identity {
        gap: 12rpx;
    }

    .notice-card__icon {
        width: 56rpx;
        height: 56rpx;
        border-radius: 20rpx;
    }

    .notice-card__foot {
        align-items: flex-start;
        flex-direction: column;
    }

    .notice-card__action-hint {
        width: 100%;
    }
}
</style>
