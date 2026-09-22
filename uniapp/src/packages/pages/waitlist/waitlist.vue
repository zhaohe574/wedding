<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace" hasSafeBottom>
        <BaseNavbar
            title="我的候补"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <view class="waitlist-page wm-page-content">
            <view class="waitlist-page__content wm-page-stack">
                <!-- Urgency Hero Alert if Released Waitlist Exists -->
                <view v-if="hasReleasedWaitlist" class="waitlist-page__alert-banner">
                    <view class="waitlist-page__alert-icon-box">
                        <BaseIcon name="tip" size="28" color="#D9BE82" />
                    </view>
                    <view class="waitlist-page__alert-copy">
                        <text class="waitlist-page__alert-title">已有候补档期成功释放！</text>
                        <text class="waitlist-page__alert-desc">
                            手艺人热门档期紧俏，请在截止时间前尽快完成预约锁定
                        </text>
                    </view>
                </view>

                <!-- Filter Tabs Scroll -->
                <scroll-view scroll-x class="waitlist-page__filter-scroll" :show-scrollbar="false">
                    <view class="waitlist-page__filter-row">
                        <view
                            v-for="tab in statusTabs"
                            :key="tab.value"
                            class="waitlist-filter-chip"
                            :class="{ 'waitlist-filter-chip--active': currentStatus === tab.value }"
                            @click="handleStatusChange(tab.value)"
                        >
                            <text class="waitlist-filter-chip__label">{{ tab.label }}</text>
                            <text
                                v-if="getTabCount(tab.value) > 0"
                                class="waitlist-filter-chip__count"
                                :class="{
                                    'waitlist-filter-chip__count--active': currentStatus === tab.value
                                }"
                            >
                                {{ getTabCount(tab.value) }}
                            </text>
                        </view>
                    </view>
                </scroll-view>

                <!-- Loading State -->
                <BaseCard
                    v-if="loading && waitlistItems.length === 0"
                    class="waitlist-page__state-card"
                    variant="quiet"
                    padding="42rpx 28rpx"
                    border-radius="32rpx"
                >
                    <LoadingState text="正在同步候补记录..." tone="wedding" compact />
                </BaseCard>

                <!-- Empty State -->
                <EmptyState
                    v-else-if="waitlistItems.length === 0"
                    title="暂无候补记录"
                    description="心仪手艺人档期已满时可加入候补，档期释放后将第一时间通知您"
                    icon="calendar"
                    action-text="去查询档期"
                    compact
                    @action="goSchedule"
                />

                <!-- Waitlist List -->
                <view v-else class="waitlist-list">
                    <BaseCard
                        v-for="item in waitlistItems"
                        :key="item.id"
                        variant="list"
                        padding="24rpx"
                        border-radius="32rpx"
                        :border="
                            item.notify_status === 1
                                ? '1rpx solid rgba(217, 190, 130, 0.95)'
                                : '1rpx solid rgba(216, 201, 173, 0.72)'
                        "
                        :box-shadow="
                            item.notify_status === 1
                                ? '0 16rpx 38rpx rgba(74, 43, 24, 0.12)'
                                : '0 14rpx 32rpx rgba(74, 43, 24, 0.06)'
                        "
                        class="waitlist-card"
                        :class="{ 'waitlist-card--released': item.notify_status === 1 }"
                    >
                        <!-- Card Header -->
                        <view class="waitlist-card__head">
                            <view class="waitlist-card__title-group">
                                <view class="waitlist-card__eyebrow">
                                    <BaseIcon name="calendar" size="20" color="#B8954A" />
                                    <text>候补档期</text>
                                </view>
                                <view class="waitlist-card__title-row">
                                    <text class="waitlist-card__title text-ellipsis">
                                        {{ item.title }}
                                    </text>
                                    <text v-if="item.package?.name" class="waitlist-card__pkg-badge">
                                        {{ item.package.name }}
                                    </text>
                                </view>
                            </view>

                            <StatusBadge :tone="item.statusTone" size="sm" dot>
                                {{ item.statusText }}
                            </StatusBadge>
                        </view>

                        <!-- Meta Grid -->
                        <view class="waitlist-card__meta-grid">
                            <view class="waitlist-card__meta-item">
                                <view class="waitlist-card__meta-icon">
                                    <BaseIcon name="calendar" size="22" color="#9A9388" />
                                </view>
                                <view class="waitlist-card__meta-copy">
                                    <text class="waitlist-card__meta-label">候补预约日期</text>
                                    <text class="waitlist-card__meta-value">
                                        {{ item.scheduleText }}
                                    </text>
                                </view>
                            </view>

                            <view class="waitlist-card__meta-item">
                                <view class="waitlist-card__meta-icon">
                                    <BaseIcon name="order" size="22" color="#9A9388" />
                                </view>
                                <view class="waitlist-card__meta-copy">
                                    <text class="waitlist-card__meta-label">服务团队</text>
                                    <text class="waitlist-card__meta-value">
                                        {{ item.detailText }}
                                    </text>
                                </view>
                            </view>
                        </view>

                        <!-- Progress Box -->
                        <view
                            class="waitlist-card__progress"
                            :class="{ 'waitlist-card__progress--released': item.notify_status === 1 }"
                        >
                            <view class="waitlist-card__progress-main">
                                <view
                                    class="waitlist-card__progress-icon"
                                    :class="{
                                        'waitlist-card__progress-icon--released':
                                            item.notify_status === 1
                                    }"
                                >
                                    <BaseIcon
                                        :name="item.notify_status === 1 ? 'tip' : 'notice'"
                                        size="24"
                                        :color="item.notify_status === 1 ? '#D9BE82' : '#B8954A'"
                                    />
                                </view>
                                <view class="waitlist-card__progress-copy">
                                    <text class="waitlist-card__progress-label">当前进度状态</text>
                                    <text class="waitlist-card__progress-title">
                                        {{ item.statusSummary }}
                                    </text>
                                    <text class="waitlist-card__progress-next">
                                        {{ item.nextStepText }}
                                    </text>
                                </view>
                            </view>

                            <view
                                v-if="item.timelineText || item.bookBlockReason"
                                class="waitlist-card__progress-meta"
                            >
                                <view
                                    v-if="item.timelineText"
                                    class="waitlist-card__progress-meta-row"
                                >
                                    <BaseIcon name="time" size="20" color="#9A9388" />
                                    <text class="waitlist-card__progress-meta-value">
                                        {{ item.timelineText }}
                                    </text>
                                </view>
                                <view
                                    v-if="item.bookBlockReason"
                                    class="waitlist-card__progress-meta-row waitlist-card__progress-meta-row--warning"
                                >
                                    <BaseIcon name="tip" size="20" color="#9A6B35" />
                                    <text class="waitlist-card__progress-meta-value">
                                        {{ item.bookBlockReason }}
                                    </text>
                                </view>
                            </view>
                        </view>

                        <!-- Card Foot -->
                        <view class="waitlist-card__foot">
                            <view class="waitlist-card__created-at">
                                <BaseIcon name="calendar" size="20" color="#9A9388" />
                                <text>提交于 {{ item.createdAtText }}</text>
                            </view>

                            <view
                                v-if="item.showBookAction || item.showCancelAction"
                                class="waitlist-card__actions"
                            >
                                <BaseButton
                                    v-if="item.showCancelAction"
                                    label="取消候补"
                                    variant="light"
                                    size="sm"
                                    height="62rpx"
                                    font-size="23rpx"
                                    class="waitlist-card__action"
                                    @click.stop="handleCancel(item)"
                                />
                                <BaseButton
                                    v-if="item.showBookAction"
                                    label="立即预约锁定"
                                    variant="dark"
                                    size="sm"
                                    height="62rpx"
                                    font-size="23rpx"
                                    icon="right"
                                    icon-position="right"
                                    class="waitlist-card__action"
                                    @click.stop="handleBook(item)"
                                />
                            </view>
                        </view>
                    </BaseCard>
                </view>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { getMyWaitlist, cancelWaitlist } from '@/packages/common/api/schedule'
import { useThemeStore } from '@/stores/theme'
import { confirmModal, showError, showSuccess } from '@/utils/feedback'

type WaitlistStatusTone = 'neutral' | 'success' | 'warning' | 'info'

interface WaitlistRecord {
    id: number | string
    staff_id?: number
    schedule_date?: string
    package_id?: number
    package?: {
        name?: string
    }
    staff?: {
        name?: string
        category_name?: string
    }
    notify_status: number
    notify_status_desc?: string
    create_time?: string | number
    notify_time_text?: string
    expire_time_text?: string
    can_book_now?: boolean
    can_cancel?: boolean
    book_block_reason?: string
    status_hint?: string
}

interface WaitlistViewItem extends WaitlistRecord {
    title: string
    scheduleText: string
    detailText: string
    timelineText: string
    statusSummary: string
    nextStepText: string
    createdAtText: string
    statusText: string
    statusTone: WaitlistStatusTone
    showBookAction: boolean
    showCancelAction: boolean
    bookBlockReason: string
}

const $theme = useThemeStore()

const statusTabs = [
    { value: -1, label: '全部' },
    { value: 0, label: '等待中' },
    { value: 1, label: '已通知' },
    { value: 2, label: '已转正' },
    { value: 3, label: '已失效' }
]

const loading = ref(false)
const currentStatus = ref(-1)
const waitlist = ref<WaitlistRecord[]>([])

const getStatusTone = (status: number): WaitlistStatusTone => {
    const map: Record<number, WaitlistStatusTone> = {
        0: 'info',
        1: 'warning',
        2: 'success',
        3: 'neutral'
    }

    return map[status] || 'info'
}

const getStatusText = (status: number) => {
    const map: Record<number, string> = {
        0: '等待中',
        1: '已通知',
        2: '已转正',
        3: '已失效'
    }

    return map[status] || '等待中'
}

const buildTitle = (item: WaitlistRecord) => {
    return String(item.staff?.name || '').trim() || '待确认服务人员'
}

const buildScheduleText = (item: WaitlistRecord) => {
    return String(item.schedule_date || '').trim() || '待选择预约日期'
}

const buildDetailText = (item: WaitlistRecord) => {
    return (
        [item.package?.name, item.staff?.category_name]
            .map((value) => String(value || '').trim())
            .filter(Boolean)
            .join(' · ') || '候补已提交'
    )
}

const getStatusSummary = (status: number) => {
    const map: Record<number, string> = {
        0: '已加入候补队列，系统将在档期释放时通知您。',
        1: '档期已成功释放，请尽快完成预约锁定。',
        2: '已成功转为正式订单预约。',
        3: '本次候补已过期失效。'
    }

    return map[status] || '请留意后续通知。'
}

const getNextStepText = (status: number) => {
    const map: Record<number, string> = {
        0: '下一步：留意服务通知提醒。',
        1: '下一步：确认服务并提交预约。',
        2: '下一步：在订单列表查看服务履约进度。',
        3: '下一步：可重新查询其他档期。'
    }

    return map[status] || '下一步：关注消息通知。'
}

const buildTimelineText = (item: WaitlistRecord) => {
    const parts: string[] = []
    if (item.notify_time_text) {
        parts.push(`通知时间：${item.notify_time_text}`)
    }
    if (item.expire_time_text) {
        parts.push(
            Number(item.notify_status) === 1
                ? `预约截止：${item.expire_time_text}`
                : `保留至：${item.expire_time_text}`
        )
    }

    const timelineText = parts.join(' · ')
    const hintText = String(item.status_hint || '')
    if (item.expire_time_text && hintText.includes(String(item.expire_time_text))) {
        return parts.length > 1
            ? parts.filter((part) => !part.includes(String(item.expire_time_text))).join(' · ')
            : ''
    }

    return timelineText
}

const buildNextStepText = (item: WaitlistRecord) => {
    if (Number(item.notify_status) === 1 && item.can_book_now === false) {
        return item.book_block_reason
            ? `下一步：${item.book_block_reason}，请留意后续通知。`
            : '下一步：请留意后续通知。'
    }

    return getNextStepText(Number(item.notify_status || 0))
}

const waitlistItems = computed<WaitlistViewItem[]>(() => {
    return waitlist.value.map((item) => ({
        ...item,
        title: buildTitle(item),
        scheduleText: buildScheduleText(item),
        detailText: buildDetailText(item),
        timelineText: buildTimelineText(item),
        statusSummary: item.status_hint || getStatusSummary(Number(item.notify_status || 0)),
        nextStepText: buildNextStepText(item),
        createdAtText: formatTime(item.create_time),
        statusText: item.notify_status_desc || getStatusText(Number(item.notify_status || 0)),
        statusTone: getStatusTone(Number(item.notify_status || 0)),
        showBookAction: Number(item.notify_status) === 1 && Boolean(item.can_book_now),
        showCancelAction: Boolean(item.can_cancel),
        bookBlockReason: String(item.book_block_reason || '')
    }))
})

const hasReleasedWaitlist = computed(() => {
    return waitlist.value.some((item) => Number(item.notify_status) === 1)
})

const getTabCount = (statusValue: number) => {
    if (statusValue === -1) {
        return waitlist.value.length
    }
    return waitlist.value.filter((item) => Number(item.notify_status) === statusValue).length
}

const fetchList = async () => {
    loading.value = true
    try {
        const params: Record<string, number> = {}
        if (currentStatus.value >= 0) {
            params.status = currentStatus.value
        }
        const res = await getMyWaitlist(params)
        waitlist.value = Array.isArray(res) ? res : []
    } finally {
        loading.value = false
    }
}

const handleStatusChange = (status: number) => {
    if (currentStatus.value === status) {
        return
    }

    currentStatus.value = status
    fetchList()
}

const formatTime = (timestamp: WaitlistRecord['create_time']) => {
    if (!timestamp) return '未知时间'

    let date: Date

    if (typeof timestamp === 'string') {
        date = new Date(timestamp)
    } else if (typeof timestamp === 'number') {
        date = timestamp < 10000000000 ? new Date(timestamp * 1000) : new Date(timestamp)
    } else {
        return '未知时间'
    }

    if (isNaN(date.getTime())) {
        return '未知时间'
    }

    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    const hour = String(date.getHours()).padStart(2, '0')
    const minute = String(date.getMinutes()).padStart(2, '0')

    return `${year}-${month}-${day} ${hour}:${minute}`
}

const goSchedule = () => {
    uni.navigateTo({ url: '/pages/schedule_query/schedule_query' })
}

const handleBook = (item: WaitlistRecord) => {
    if (item.can_book_now === false) {
        showError(item.book_block_reason || '当前档期暂不可预约')
        return
    }

    if (!item.staff_id) {
        showError('服务人员信息错误')
        return
    }

    const params = [`id=${item.staff_id}`]
    if (item.schedule_date) {
        params.push(`date=${item.schedule_date}`)
    }
    if (item.package_id) {
        params.push(`package_id=${item.package_id}`)
    }
    if (item.id) {
        params.push(`waitlist_id=${item.id}`)
    }
    if (!item.schedule_date) {
        params.push('open_date_picker=1')
    }

    uni.navigateTo({
        url: `/packages/pages/staff_detail/staff_detail?${params.join('&')}`
    })
}

const handleCancel = async (item: WaitlistRecord) => {
    const confirmed = await confirmModal({
        title: '取消候补',
        content: '确定要取消该候补吗？取消后需重新加入队列。',
        confirmColor: '#5A4433'
    })
    if (!confirmed) return

    try {
        await cancelWaitlist({ id: Number(item.id) })
        showSuccess('取消成功')
        fetchList()
    } catch (error: any) {
        showError(error, '操作失败')
    }
}

onShow(() => {
    $theme.setScene('consumer')
    fetchList()
})
</script>

<style lang="scss" scoped>
.waitlist-page {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    padding: 24rpx var(--wm-space-page-x, 28rpx) calc(48rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;
}

.waitlist-page__content {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

/* Released Alert Banner */
.waitlist-page__alert-banner {
    display: flex;
    align-items: center;
    gap: 16rpx;
    padding: 20rpx 24rpx;
    border-radius: 28rpx;
    border: 1rpx solid rgba(217, 190, 130, 0.7);
    background: radial-gradient(circle at 90% -20rpx, rgba(217, 190, 130, 0.2) 0, rgba(217, 190, 130, 0) 160rpx),
        linear-gradient(135deg, #2a2318 0%, #171512 100%);
    box-shadow: 0 12rpx 28rpx rgba(74, 43, 24, 0.1);
}

.waitlist-page__alert-icon-box {
    width: 60rpx;
    height: 60rpx;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(217, 190, 130, 0.16);
    border: 1rpx solid rgba(217, 190, 130, 0.4);
}

.waitlist-page__alert-copy {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4rpx;
}

.waitlist-page__alert-title {
    font-size: 27rpx;
    font-weight: 900;
    color: var(--wm-color-champagne, #d9be82);
}

.waitlist-page__alert-desc {
    font-size: 21rpx;
    color: rgba(255, 253, 248, 0.75);
    line-height: 1.4;
}

/* Filter Chips */
.waitlist-page__filter-scroll {
    width: 100%;
    white-space: nowrap;
}

.waitlist-page__filter-row {
    display: inline-flex;
    align-items: center;
    gap: 14rpx;
    padding: 4rpx 2rpx 8rpx;
}

.waitlist-filter-chip {
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

.waitlist-filter-chip--active {
    background: #191713;
    border-color: var(--wm-color-champagne, #d9be82);
    box-shadow: 0 8rpx 20rpx rgba(25, 23, 19, 0.18);
}

.waitlist-filter-chip__label {
    font-size: 24rpx;
    font-weight: 800;
    line-height: 1;
    color: var(--wm-text-secondary, #5f5a50);
}

.waitlist-filter-chip--active .waitlist-filter-chip__label {
    color: var(--wm-text-inverse, #fffdf8);
}

.waitlist-filter-chip__count {
    min-width: 32rpx;
    height: 32rpx;
    padding: 0 8rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(25, 23, 19, 0.08);
    font-size: 20rpx;
    font-weight: 800;
    line-height: 32rpx;
    text-align: center;
    color: var(--wm-text-primary, #191713);
}

.waitlist-filter-chip__count--active {
    background: var(--wm-color-champagne, #d9be82);
    color: #191713;
}

.waitlist-page__state-card {
    display: block;
}

/* Waitlist Card */
.waitlist-list {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.waitlist-card {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
    box-sizing: border-box;
}

.waitlist-card--released {
    background: linear-gradient(180deg, #fffdf9 0%, #faf5ea 100%) !important;
}

.waitlist-card__head {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;
}

.waitlist-card__title-group {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.waitlist-card__eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
    font-size: 21rpx;
    font-weight: 800;
    color: var(--wm-color-gold, #b8954a);
}

.waitlist-card__title-row {
    display: flex;
    align-items: center;
    gap: 10rpx;
    flex-wrap: wrap;
}

.waitlist-card__title {
    font-size: 32rpx;
    font-weight: 900;
    line-height: 1.35;
    color: var(--wm-text-primary, #191713);
}

.waitlist-card__pkg-badge {
    display: inline-flex;
    align-items: center;
    padding: 2rpx 12rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(217, 190, 130, 0.16);
    border: 1rpx solid rgba(217, 190, 130, 0.45);
    font-size: 20rpx;
    font-weight: 800;
    color: var(--wm-color-gold, #b8954a);
}

/* Meta Grid */
.waitlist-card__meta-grid {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14rpx;
}

.waitlist-card__meta-item {
    display: flex;
    align-items: flex-start;
    gap: 12rpx;
    padding: 16rpx 18rpx;
    border-radius: 22rpx;
    background: rgba(248, 242, 228, 0.6);
    border: 1rpx solid rgba(216, 201, 173, 0.55);
    box-sizing: border-box;
}

.waitlist-card__meta-icon {
    width: 44rpx;
    height: 44rpx;
    flex-shrink: 0;
    border-radius: 14rpx;
    background: #fffdf8;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4rpx 10rpx rgba(74, 43, 24, 0.04);
}

.waitlist-card__meta-copy {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4rpx;
}

.waitlist-card__meta-label {
    font-size: 20rpx;
    line-height: 1.3;
    color: var(--wm-text-tertiary, #9a9388);
}

.waitlist-card__meta-value {
    font-size: 23rpx;
    font-weight: 800;
    line-height: 1.35;
    color: var(--wm-text-primary, #191713);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Progress Box */
.waitlist-card__progress {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    padding: 22rpx;
    border-radius: 26rpx;
    background: rgba(255, 253, 248, 0.88);
    border: 1rpx solid rgba(216, 201, 173, 0.6);
}

.waitlist-card__progress--released {
    border-color: rgba(217, 190, 130, 0.7);
    background: rgba(255, 250, 240, 0.95);
}

.waitlist-card__progress-main {
    display: flex;
    align-items: flex-start;
    gap: 14rpx;
}

.waitlist-card__progress-icon {
    width: 48rpx;
    height: 48rpx;
    flex-shrink: 0;
    border-radius: 16rpx;
    background: rgba(241, 229, 200, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
}

.waitlist-card__progress-icon--released {
    background: #191713;
}

.waitlist-card__progress-copy {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.waitlist-card__progress-label {
    font-size: 20rpx;
    font-weight: 800;
    line-height: 1.2;
    color: var(--wm-text-tertiary, #9a9388);
}

.waitlist-card__progress-title {
    font-size: 25rpx;
    font-weight: 900;
    line-height: 1.5;
    color: var(--wm-text-primary, #191713);
}

.waitlist-card__progress-next {
    font-size: 22rpx;
    line-height: 1.5;
    color: var(--wm-text-secondary, #665e52);
}

.waitlist-card__progress-meta {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    padding-top: 12rpx;
    border-top: 1rpx solid rgba(216, 201, 173, 0.45);
}

.waitlist-card__progress-meta-row {
    display: flex;
    align-items: center;
    gap: 8rpx;
    font-size: 21rpx;
    color: var(--wm-text-secondary, #665e52);
}

.waitlist-card__progress-meta-row--warning {
    color: var(--wm-color-clay, #9a6b35);
    font-weight: 700;
}

/* Card Foot */
.waitlist-card__foot {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    padding-top: 16rpx;
    border-top: 1rpx solid rgba(216, 201, 173, 0.5);
}

.waitlist-card__created-at {
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
    font-size: 21rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

.waitlist-card__actions {
    display: inline-flex;
    align-items: center;
    gap: 12rpx;
    flex-shrink: 0;
}

.waitlist-card__action {
    flex-shrink: 0;
}

@media screen and (max-width: 360px) {
    .waitlist-card__meta-grid {
        grid-template-columns: 1fr;
    }

    .waitlist-card__foot {
        flex-direction: column;
        align-items: stretch;
        gap: 12rpx;
    }

    .waitlist-card__actions {
        width: 100%;
        justify-content: flex-end;
    }
}
</style>
