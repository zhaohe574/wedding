<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="通知中心" />
        <view class="notification-page wm-page-content">
            <view class="notification-page__content">
                <view class="notification-page__toolbar wm-panel-card">
                    <view class="notification-page__unread-pill">{{ unreadSummaryText }}</view>
                    <view v-if="notificationList.length" class="notification-page__toolbar-actions">
                        <view class="notification-page__toolbar-link" @click="handleDeleteRead">
                            删除已读
                        </view>
                        <view class="notification-page__toolbar-link" @click="handleMarkAllRead">
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

                <view v-if="loading && !notificationList.length" class="loading-tip">
                    <tn-icon name="loading" :size="32" color="#B4ACA8" />
                    <text>加载中...</text>
                </view>

                <EmptyState
                    v-else-if="!notificationList.length"
                    :title="`暂无${currentTypeLabel}`"
                    description="消息会显示在这里。"
                />

                <view v-else class="notice-list">
                    <view
                        v-for="item in notificationList"
                        :key="item.id"
                        class="notice-card touch-active"
                        :class="{ 'notice-card--read': isNoticeRead(item) }"
                        @click="handleItemClick(item)"
                    >
                        <view class="notice-card__head">
                            <text class="notice-card__title text-ellipsis">{{ item.title }}</text>
                            <view class="notice-card__delete" @click.stop="handleDeleteItem(item)">
                                删除
                            </view>
                        </view>
                        <text class="notice-card__content text-ellipsis-2">{{ item.content }}</text>
                    </view>
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
import EmptyState from '@/components/base/EmptyState.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useThemeStore } from '@/stores/theme'
import {
    clearNotification,
    deleteNotification,
    getNotificationDetail,
    getNotificationList,
    getUnreadCount,
    markAllNotificationRead,
    markNotificationRead
} from '@/api/notification'

const $theme = useThemeStore()

const notificationRouteMap: Record<string, (targetId?: number) => string> = {
    order: (targetId) => `/pages/order_detail/order_detail?id=${targetId || 0}`,
    order_detail: (targetId) => `/pages/order_detail/order_detail?id=${targetId || 0}`,
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
    review: (targetId) => `/packages/pages/review/detail?id=${targetId || 0}`,
    review_list: () => '/packages/pages/review/list',
    review_detail: (targetId) => `/packages/pages/review/detail?id=${targetId || 0}`,
    dynamic: (targetId) => `/pages/dynamic_detail/dynamic_detail?id=${targetId || 0}`,
    dynamic_detail: (targetId) => `/pages/dynamic_detail/dynamic_detail?id=${targetId || 0}`,
    staff_detail: (targetId) => `/packages/pages/staff_detail/staff_detail?id=${targetId || 0}`,
    confirm_letter_order: (targetId) =>
        `/pages/order_detail/order_detail?id=${targetId || 0}&open_confirm_letter=1&from_notification=1`,
    confirm_letter: (targetId) =>
        `/pages/order_detail/order_detail?letter_id=${targetId || 0}&from_notification=1`
}

const buildConfirmLetterNotificationRoute = (item: any) =>
    `/pages/order_detail/order_detail?letter_id=${Number(item?.target_id || 0)}&entry=confirm_letter_notification&notification_id=${Number(item?.id || 0)}`

const loading = ref(false)
const currentType = ref(0)
const notificationList = ref<any[]>([])
const unreadCount = ref<any>({
    total: 0,
    system: 0,
    order: 0,
    interact: 0
})
const page = ref(1)
const hasMore = ref(true)
const categoryList = [
    { type: 1, name: '系统通知', unreadKey: 'system' },
    { type: 2, name: '订单通知', unreadKey: 'order' },
    { type: 3, name: '互动通知', unreadKey: 'interact' }
]

const unreadSummaryText = computed(() => `未读 ${unreadCount.value.total || 0} 条`)
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
const isNoticeRead = (item: any) => Number(item?.is_read || 0) > 0
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
        const params: Record<string, any> = {
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

const openNotificationDetail = async (item: any, hint = '') => {
    try {
        const detail = await getNotificationDetail({ id: item.id })
        const lines = [
            detail?.content || item?.content || '暂无详细内容',
            detail?.create_time_text ? `时间：${detail.create_time_text}` : '',
            hint
        ].filter(Boolean)

        uni.showModal({
            title: detail?.title || item?.title || '消息详情',
            content: lines.join('\n\n'),
            showCancel: false,
            confirmText: '我知道了'
        })
    } catch (error) {
        console.error(error)
        uni.showModal({
            title: item?.title || '消息详情',
            content: [item?.content || '暂无详细内容', hint].filter(Boolean).join('\n\n'),
            showCancel: false,
            confirmText: '我知道了'
        })
    }
}

const navigateByTarget = (item: any) => {
    const targetType = String(item?.target_type || '').trim()
    const route =
        targetType === 'confirm_letter'
            ? buildConfirmLetterNotificationRoute(item)
            : notificationRouteMap[targetType]?.(item?.target_id) || ''
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

const handleItemClick = async (item: any) => {
    if (!isNoticeRead(item)) {
        try {
            await markNotificationRead({ id: item.id })
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

    uni.showToast({ title: '当前消息仅支持查看详情', icon: 'none' })
    openNotificationDetail(item, '已为你打开详情。')
}

const handleMarkAllRead = () => {
    uni.showModal({
        title: '提示',
        content: `确定将${currentScopeLabel.value}标记为已读吗？`,
        success: async (res) => {
            if (!res.confirm) return
            try {
                await markAllNotificationRead({
                    notify_type: currentType.value || undefined
                })
                uni.showToast({ title: '标记成功', icon: 'success' })
                refreshListState()
            } catch (error) {
                console.error(error)
            }
        }
    })
}

const handleDeleteRead = () => {
    uni.showModal({
        title: '提示',
        content: `确定删除${currentScopeLabel.value}中的已读消息吗？`,
        success: async (res) => {
            if (!res.confirm) return
            try {
                const result = await clearNotification({
                    notify_type: currentType.value || undefined,
                    read_status: 1
                })
                await refreshListState()
                if (Number(result?.count || 0) > 0) {
                    uni.showToast({ title: '删除成功', icon: 'success' })
                    return
                }
                uni.showToast({ title: '没有可删除的已读消息', icon: 'none' })
            } catch (error) {
                console.error(error)
            }
        }
    })
}

const handleDeleteItem = (item: any) => {
    uni.showModal({
        title: '提示',
        content: '确定删除这条通知吗？',
        success: async (res) => {
            if (!res.confirm) return
            try {
                await deleteNotification({ id: item.id })
                uni.showToast({ title: '删除成功', icon: 'success' })
                await refreshListState()
            } catch (error) {
                console.error(error)
            }
        }
    })
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
    background: transparent;
}

.notification-page__content {
    padding-bottom: calc(var(--wm-space-card-padding-lg, 34rpx) + env(safe-area-inset-bottom));
}

.notification-page__toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
    padding: 22rpx 26rpx;
}

.notification-page__unread-pill {
    display: inline-flex;
    align-items: center;
    min-height: 56rpx;
    padding: 0 26rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: var(--wm-color-primary-soft, #fff1ee);
    font-size: 22rpx;
    font-weight: 700;
    color: var(--wm-color-primary, #e85a4f);
}

.notification-page__toolbar-actions {
    display: inline-flex;
    align-items: center;
    gap: 18rpx;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.notification-page__toolbar-link {
    flex-shrink: 0;
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-color-primary, #e85a4f);
}

.notification-page__filter-scroll {
    margin-top: 18rpx;
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
    min-height: 60rpx;
    padding: 0 24rpx;
}

.notification-page__filter-chip-count {
    min-width: 32rpx;
    padding: 0 8rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(232, 90, 79, 0.1);
    font-size: 20rpx;
    font-weight: 700;
    line-height: 32rpx;
    text-align: center;
    color: var(--wm-color-primary, #e85a4f);
}

.notification-page__filter-chip-count--active {
    background: rgba(255, 255, 255, 0.18);
    color: #ffffff;
}

.notice-list {
    display: flex;
    flex-direction: column;
    gap: 30rpx;
    margin-top: 30rpx;
}

.notice-card {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    padding: 30rpx 34rpx;
    border-radius: var(--wm-radius-card, 45rpx);
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    background: rgba(255, 255, 255, 0.86);
    box-shadow: 0 10rpx 28rpx rgba(214, 185, 167, 0.08);
    backdrop-filter: blur(22rpx);
    -webkit-backdrop-filter: blur(22rpx);

    &--read {
        background: rgba(255, 255, 255, 0.76);
        opacity: 0.82;
    }
}

.notice-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;
}

.notice-card__title {
    display: block;
    flex: 1;
    min-width: 0;
    font-size: 28rpx;
    font-weight: 600;
    line-height: 1.6;
    color: var(--wm-text-primary, #1e2432);
}

.notice-card__delete {
    flex-shrink: 0;
    padding: 4rpx 0 0;
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1.4;
    color: var(--wm-text-tertiary, #b4aca8);
}

.notice-card__content {
    display: block;
    font-size: 28rpx;
    font-weight: 600;
    line-height: 1.6;
    color: var(--wm-text-primary, #1e2432);
}

.loading-tip,
.load-more-tip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    padding: 30rpx 0 8rpx;
    font-size: 22rpx;
    color: var(--wm-text-tertiary, #b4aca8);
}
</style>
