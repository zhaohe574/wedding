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
            <OaNoticeCard v-if="!isOaBound" />
            <view class="notification-page__content wm-page-stack">
                <!-- Obsidian & Champagne Summary Banner -->
                <view class="notification-page__summary-card">
                    <view class="notification-page__summary-left">
                        <view class="notification-page__summary-kicker">
                            <BaseIcon name="notice" size="20" color="#D9BE82" />
                            <text>{{ currentScopeLabel }}</text>
                        </view>
                        <view class="notification-page__summary-count">
                            <text class="notification-page__summary-number">
                                {{ currentUnreadTotal }}
                            </text>
                            <text class="notification-page__summary-unit">条未读消息</text>
                        </view>
                    </view>
                    <view v-if="notificationList.length" class="notification-page__summary-actions">
                        <view
                            class="notification-page__summary-action"
                            @click.stop="handleDeleteRead"
                        >
                            <BaseIcon name="delete" size="22" color="rgba(255, 253, 248, 0.72)" />
                            <text>删除已读</text>
                        </view>
                        <view
                            class="notification-page__summary-action notification-page__summary-action--primary"
                            :class="{
                                'notification-page__summary-action--disabled': currentUnreadTotal <= 0
                            }"
                            @click.stop="handleMarkAllReadFromToolbar"
                        >
                            <BaseIcon name="check" size="22" color="#191713" />
                            <text>全部已读</text>
                        </view>
                    </view>
                </view>

                <!-- Category Pill Tabs -->
                <scroll-view
                    scroll-x
                    class="notification-page__filter-scroll"
                    :show-scrollbar="false"
                >
                    <view class="notification-page__filter-row">
                        <view
                            class="notification-filter-chip"
                            :class="{ 'notification-filter-chip--active': currentType === 0 }"
                            @click="switchType(0)"
                        >
                            <text class="notification-filter-chip__label">全部</text>
                            <text
                                v-if="hasUnread"
                                class="notification-filter-chip__count"
                                :class="{
                                    'notification-filter-chip__count--active': currentType === 0
                                }"
                            >
                                {{ formatUnreadCount(unreadCount.total) }}
                            </text>
                        </view>
                        <view
                            v-for="item in categoryList"
                            :key="item.type"
                            class="notification-filter-chip"
                            :class="{ 'notification-filter-chip--active': currentType === item.type }"
                            @click="switchType(item.type)"
                        >
                            <text class="notification-filter-chip__label">{{ item.name }}</text>
                            <text
                                v-if="getUnreadByType(item.type) > 0"
                                class="notification-filter-chip__count"
                                :class="{
                                    'notification-filter-chip__count--active': currentType === item.type
                                }"
                            >
                                {{ formatUnreadCount(getUnreadByType(item.type)) }}
                            </text>
                        </view>
                    </view>
                </scroll-view>

                <!-- Loading State -->
                <BaseCard
                    v-if="loading && !notificationList.length"
                    class="notification-page__state-card"
                    variant="quiet"
                    padding="42rpx 28rpx"
                    border-radius="32rpx"
                >
                    <LoadingState text="正在同步通知..." tone="wedding" compact />
                </BaseCard>

                <!-- Empty State -->
                <EmptyState
                    v-else-if="!notificationList.length"
                    :title="`暂无${currentTypeLabel}`"
                    description="暂未收到相关通知，重要履约与服务提醒将在此处展示"
                    icon="notice"
                    compact
                />

                <!-- Notice List -->
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
                        border="1rpx solid rgba(216, 201, 173, 0.72)"
                        box-shadow="0 12rpx 28rpx rgba(74, 43, 24, 0.05)"
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
                                    <view class="notice-card__header-row">
                                        <text class="notice-card__title text-ellipsis">
                                            {{ getNoticeTitle(item) }}
                                        </text>
                                        <StatusBadge
                                            :tone="isNoticeRead(item) ? 'neutral' : 'primary'"
                                            size="xs"
                                            :dot="!isNoticeRead(item)"
                                        >
                                            {{ isNoticeRead(item) ? '已读' : '未读' }}
                                        </StatusBadge>
                                    </view>
                                    <view class="notice-card__meta-row">
                                        <StatusBadge
                                            :tone="getNoticeTypeTone(item)"
                                            size="xs"
                                        >
                                            {{ getNoticeTypeLabel(item) }}
                                        </StatusBadge>
                                        <view v-if="getNoticeTime(item)" class="notice-card__time-box">
                                            <BaseIcon name="calendar" size="20" color="#9A9388" />
                                            <text class="notice-card__time text-ellipsis">
                                                {{ getNoticeTime(item) }}
                                            </text>
                                        </view>
                                    </view>
                                    <text class="notice-card__content text-ellipsis-2">
                                        {{ getNoticeContent(item) }}
                                    </text>
                                </view>
                            </view>
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
                                <BaseIcon name="delete" size="20" color="#9A9388" />
                                <text>删除</text>
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

        <!-- Luxury Notification Detail Drawer -->
        <BaseOverlayMask
            :show="showDetailPopup"
            :z-index="popupMaskZIndex"
            background="rgba(18, 16, 14, 0.65)"
            @close="showDetailPopup = false"
        />

        <TnPopup
            v-model="showDetailPopup"
            open-direction="bottom"
            :radius="36"
            :overlay="false"
            :safe-area-inset-bottom="true"
            :z-index="popupZIndex"
        >
            <view v-if="activeNoticeItem" class="notice-detail-drawer">
                <view class="notice-detail-drawer__bar-wrap" @click="showDetailPopup = false">
                    <view class="notice-detail-drawer__bar"></view>
                </view>
                <view class="notice-detail-drawer__header">
                    <view class="notice-detail-drawer__header-left">
                        <text class="notice-detail-drawer__kicker">消息通知详情</text>
                        <text class="notice-detail-drawer__title text-ellipsis">
                            {{ getNoticeTitle(activeNoticeItem) }}
                        </text>
                    </view>
                    <view class="notice-detail-drawer__close" @click="showDetailPopup = false">
                        <BaseIcon name="close" size="28" color="#191713" />
                    </view>
                </view>

                <view class="notice-detail-drawer__body">
                    <!-- Meta Row -->
                    <view class="notice-detail-drawer__meta-row">
                        <StatusBadge
                            :tone="getNoticeTypeTone(activeNoticeItem)"
                            size="sm"
                        >
                            {{ getNoticeTypeLabel(activeNoticeItem) }}
                        </StatusBadge>
                        <view v-if="getNoticeTime(activeNoticeItem)" class="notice-detail-drawer__time">
                            <BaseIcon name="calendar" size="22" color="#9A9388" />
                            <text>{{ getNoticeTime(activeNoticeItem) }}</text>
                        </view>
                    </view>

                    <!-- Message Body Box -->
                    <view class="notice-detail-drawer__content-box">
                        <text class="notice-detail-drawer__content-text">
                            {{ activeNoticeItem.content || '暂无详细内容' }}
                        </text>
                    </view>

                    <!-- Optional Hint Alert -->
                    <view v-if="activeNoticeHint" class="notice-detail-drawer__hint-box">
                        <BaseIcon name="tip" size="26" color="#B8954A" />
                        <text class="notice-detail-drawer__hint-text">{{ activeNoticeHint }}</text>
                    </view>
                </view>

                <!-- Actions -->
                <view class="notice-detail-drawer__actions">
                    <BaseButton
                        v-if="hasTargetRoute(activeNoticeItem)"
                        label="前往关联页面"
                        variant="primary"
                        size="md"
                        height="84rpx"
                        icon="right"
                        icon-position="right"
                        class="notice-detail-drawer__btn notice-detail-drawer__btn--primary"
                        @click="handleDrawerNavigate"
                    />
                    <BaseButton
                        label="我知道了"
                        :variant="hasTargetRoute(activeNoticeItem) ? 'light' : 'dark'"
                        size="md"
                        height="84rpx"
                        class="notice-detail-drawer__btn"
                        @click="showDetailPopup = false"
                    />
                </view>
            </view>
        </TnPopup>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onPullDownRefresh, onReachBottom, onShow } from '@dcloudio/uni-app'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import BaseOverlayMask from '@/components/base/BaseOverlayMask.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import OaNoticeCard from '@/components/base/OaNoticeCard.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import TnPopup from '@tuniao/tnui-vue3-uniapp/components/popup/src/popup.vue'
import { useThemeStore } from '@/stores/theme'
import { confirmModal, showError, showSuccess } from '@/utils/feedback'
import { useOaBound } from '@/utils/oa-status'
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
const { isOaBound, checkOaBoundStatus } = useOaBound()

const notificationRouteMap: Record<string, (targetId?: number) => string> = {
    activity_registration: (targetId) => `/packages/pages/activity_registration/detail?id=${targetId || 0}`,
    staff_settlement: () => '/packages/pages/staff_settlement/staff_settlement',
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

// Detail Drawer States
const activeNoticeItem = ref<NotificationItem | null>(null)
const activeNoticeHint = ref('')
const showDetailPopup = ref(false)
const popupMaskZIndex = ref(20074)
const popupZIndex = ref(20075)

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
const hasTargetRoute = (item?: NotificationItem | null) => {
    if (!item) return false
    const targetType = normalizeText(item.target_type)
    return Boolean(notificationRouteMap[targetType])
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
    activeNoticeHint.value = hint
    activeNoticeItem.value = { ...item }
    showDetailPopup.value = true

    if (notificationId !== undefined && notificationId !== null && notificationId !== '') {
        try {
            const detail = await getNotificationDetail({ id: notificationId })
            if (detail) {
                activeNoticeItem.value = { ...item, ...detail }
            }
        } catch (error) {
            console.error(error)
        }
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

const handleDrawerNavigate = () => {
    if (!activeNoticeItem.value) return
    showDetailPopup.value = false
    navigateByTarget(activeNoticeItem.value)
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

    if (item.target_type === 'admin_business') {
        openNotificationDetail(item, '此事项需前往管理后台处理。')
        return
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
    void checkOaBoundStatus()
    loadUnreadCount()
    loadList(true)
})
</script>

<style scoped lang="scss">
.notification-page {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
    padding: 24rpx var(--wm-space-page-x, 28rpx) calc(48rpx + env(safe-area-inset-bottom));
    background: transparent;
    box-sizing: border-box;
}

.notification-page__content {
    gap: 20rpx;
}

/* Luxury Summary Banner */
.notification-page__summary-card {
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    min-height: 120rpx;
    padding: 24rpx 28rpx;
    border-radius: 32rpx;
    border: 1rpx solid rgba(217, 190, 130, 0.6);
    background: radial-gradient(circle at 90% -30rpx, rgba(217, 190, 130, 0.22) 0, rgba(217, 190, 130, 0) 180rpx),
        linear-gradient(145deg, #26221B 0%, #171512 60%, #30261A 100%);
    box-shadow: 0 16rpx 36rpx rgba(74, 43, 24, 0.12);
    box-sizing: border-box;
}

.notification-page__summary-left {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.notification-page__summary-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    width: fit-content;
    padding: 4rpx 14rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    border: 1rpx solid rgba(217, 190, 130, 0.5);
    background: rgba(217, 190, 130, 0.14);
    font-size: 20rpx;
    font-weight: 800;
    line-height: 1.2;
    color: var(--wm-color-champagne, #d9be82);
}

.notification-page__summary-count {
    display: flex;
    align-items: baseline;
    gap: 8rpx;
    color: var(--wm-text-inverse, #fffdf8);
}

.notification-page__summary-number {
    font-size: 40rpx;
    font-weight: 900;
    line-height: 1;
    color: var(--wm-color-champagne, #d9be82);
}

.notification-page__summary-unit {
    font-size: 22rpx;
    font-weight: 700;
    line-height: 1;
    color: rgba(255, 253, 248, 0.76);
}

.notification-page__summary-actions {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 12rpx;
    flex-shrink: 0;
}

.notification-page__summary-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6rpx;
    height: 56rpx;
    padding: 0 20rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    border: 1rpx solid rgba(255, 253, 248, 0.2);
    background: rgba(255, 253, 248, 0.08);
    font-size: 22rpx;
    font-weight: 800;
    color: rgba(255, 253, 248, 0.85);
    box-sizing: border-box;
    white-space: nowrap;
}

.notification-page__summary-action--primary {
    border-color: rgba(217, 190, 130, 0.8);
    background: var(--wm-color-champagne, #d9be82);
    color: var(--wm-text-primary, #191713);
}

.notification-page__summary-action--disabled {
    opacity: 0.42;
    pointer-events: none;
}

/* Category Filter Tabs */
.notification-page__filter-scroll {
    width: 100%;
    white-space: nowrap;
}

.notification-page__filter-row {
    display: inline-flex;
    align-items: center;
    gap: 14rpx;
    padding: 4rpx 2rpx 8rpx;
}

.notification-filter-chip {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    height: 64rpx;
    padding: 0 24rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    border: 1rpx solid rgba(216, 201, 173, 0.6);
    background: rgba(255, 253, 248, 0.88);
    box-shadow: 0 6rpx 16rpx rgba(74, 43, 24, 0.04);
    box-sizing: border-box;
    transition: all 0.2s ease;
}

.notification-filter-chip--active {
    background: #191713;
    border-color: var(--wm-color-champagne, #d9be82);
    box-shadow: 0 8rpx 20rpx rgba(25, 23, 19, 0.18);
}

.notification-filter-chip__label {
    font-size: 24rpx;
    font-weight: 800;
    line-height: 1;
    color: var(--wm-text-secondary, #5f5a50);
}

.notification-filter-chip--active .notification-filter-chip__label {
    color: var(--wm-text-inverse, #fffdf8);
}

.notification-filter-chip__count {
    min-width: 34rpx;
    height: 34rpx;
    padding: 0 10rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(25, 23, 19, 0.08);
    font-size: 20rpx;
    font-weight: 800;
    line-height: 34rpx;
    text-align: center;
    color: var(--wm-text-primary, #191713);
}

.notification-filter-chip__count--active {
    background: var(--wm-color-champagne, #d9be82);
    color: #191713;
}

.notification-page__state-card {
    display: block;
}

/* Notice List & Cards */
.notice-list {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.notice-card {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.notice-card--unread {
    border-color: rgba(217, 190, 130, 0.82) !important;
    background: linear-gradient(180deg, #fffdfa 0%, #faf5eb 100%) !important;
    box-shadow: 0 14rpx 32rpx rgba(74, 43, 24, 0.08) !important;
}

.notice-card--read {
    background: rgba(255, 253, 248, 0.72) !important;
    border-color: rgba(216, 201, 173, 0.5) !important;
}

.notice-card__top {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;
}

.notice-card__identity {
    min-width: 0;
    flex: 1;
    display: flex;
    align-items: flex-start;
    gap: 18rpx;
}

.notice-card__icon {
    width: 64rpx;
    height: 64rpx;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 22rpx;
    border: 1rpx solid rgba(217, 190, 130, 0.7);
    background: linear-gradient(145deg, #1c1914 0%, #2e281f 100%);
    box-shadow: 0 8rpx 18rpx rgba(74, 43, 24, 0.12);
}

.notice-card__icon--read {
    border-color: rgba(216, 201, 173, 0.6);
    background: rgba(242, 236, 224, 0.85);
    box-shadow: none;
}

.notice-card__title-group {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.notice-card__header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12rpx;
}

.notice-card__title {
    min-width: 0;
    flex: 1;
    font-size: 29rpx;
    font-weight: 900;
    line-height: 1.35;
    color: var(--wm-text-primary, #191713);
}

.notice-card__meta-row {
    display: flex;
    align-items: center;
    gap: 12rpx;
    flex-wrap: wrap;
}

.notice-card__time-box {
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
}

.notice-card__time {
    font-size: 21rpx;
    line-height: 1.35;
    color: var(--wm-text-tertiary, #9a9388);
}

.notice-card__content {
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
    gap: 16rpx;
    padding-top: 14rpx;
    border-top: 1rpx solid rgba(216, 201, 173, 0.5);
}

.notice-card__action-hint {
    min-width: 0;
    flex: 1;
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    color: var(--wm-color-gold, #b8954a);
}

.notice-card__action-text {
    font-size: 23rpx;
    font-weight: 900;
    line-height: 1.4;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.notice-card__delete {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6rpx;
    height: 52rpx;
    padding: 0 20rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    border: 1rpx solid rgba(25, 23, 19, 0.08);
    background: rgba(255, 253, 248, 0.76);
    font-size: 21rpx;
    font-weight: 800;
    color: var(--wm-text-tertiary, #9a9388);
}

.load-more-tip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    padding: 12rpx 0 6rpx;
    font-size: 22rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

/* Luxury Notification Detail Drawer */
.notice-detail-drawer {
    display: flex;
    flex-direction: column;
    padding: 20rpx 32rpx calc(48rpx + env(safe-area-inset-bottom));
    background: #faf7f2;
    box-sizing: border-box;
}

.notice-detail-drawer__bar-wrap {
    display: flex;
    justify-content: center;
    padding: 8rpx 0 16rpx;
}

.notice-detail-drawer__bar {
    width: 72rpx;
    height: 8rpx;
    border-radius: 4rpx;
    background: rgba(25, 23, 19, 0.18);
}

.notice-detail-drawer__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20rpx;
    padding-bottom: 20rpx;
    border-bottom: 1rpx solid rgba(216, 201, 173, 0.6);
}

.notice-detail-drawer__header-left {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.notice-detail-drawer__kicker {
    font-size: 20rpx;
    font-weight: 800;
    letter-spacing: 2rpx;
    color: var(--wm-color-gold, #b8954a);
}

.notice-detail-drawer__title {
    font-size: 32rpx;
    font-weight: 900;
    line-height: 1.35;
    color: var(--wm-text-primary, #191713);
}

.notice-detail-drawer__close {
    width: 60rpx;
    height: 60rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(25, 23, 19, 0.06);
    flex-shrink: 0;
}

.notice-detail-drawer__body {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
    padding: 24rpx 0;
}

.notice-detail-drawer__meta-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.notice-detail-drawer__time {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    font-size: 22rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

.notice-detail-drawer__content-box {
    padding: 28rpx;
    border-radius: 24rpx;
    background: #fffdf8;
    border: 1rpx solid rgba(216, 201, 173, 0.65);
    box-shadow: 0 8rpx 24rpx rgba(74, 43, 24, 0.04);
}

.notice-detail-drawer__content-text {
    font-size: 27rpx;
    line-height: 1.75;
    color: var(--wm-text-primary, #2b261d);
    white-space: pre-wrap;
    word-break: break-all;
}

.notice-detail-drawer__hint-box {
    display: flex;
    align-items: flex-start;
    gap: 14rpx;
    padding: 20rpx 24rpx;
    border-radius: 20rpx;
    background: rgba(217, 190, 130, 0.14);
    border: 1rpx solid rgba(217, 190, 130, 0.5);
}

.notice-detail-drawer__hint-text {
    font-size: 23rpx;
    font-weight: 700;
    line-height: 1.5;
    color: var(--wm-color-clay, #8f6027);
}

.notice-detail-drawer__actions {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    padding-top: 10rpx;
}

.notice-detail-drawer__btn {
    width: 100%;
}
</style>
