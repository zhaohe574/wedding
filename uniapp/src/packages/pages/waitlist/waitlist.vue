<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace" hasSafeBottom>
        <BaseNavbar
            title="我的候补"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <view class="waitlist-page">
            <view class="waitlist-page__wrapper wm-page-content">
                <scroll-view scroll-x class="waitlist-page__filter-scroll" :show-scrollbar="false">
                    <view class="waitlist-page__filter-row">
                        <FilterChip
                            v-for="tab in statusTabs"
                            :key="tab.value"
                            class="waitlist-page__filter-chip"
                            :label="tab.label"
                            :selected="currentStatus === tab.value"
                            scene="consumer"
                            @click="handleStatusChange(tab.value)"
                        />
                    </view>
                </scroll-view>

                <LoadingState
                    v-if="loading && waitlistItems.length === 0"
                    text="候补记录加载中"
                    tone="wedding"
                    compact
                />

                <EmptyState
                    v-else-if="waitlistItems.length === 0"
                    title="暂无候补记录"
                    icon="calendar"
                    action-text="去预约"
                    compact
                    @action="goSchedule"
                />

                <view v-else class="waitlist-list">
                    <BaseCard
                        v-for="item in waitlistItems"
                        :key="item.id"
                        variant="list"
                        scene="consumer"
                        padding="24rpx"
                        border-radius="32rpx"
                        border="1rpx solid rgba(216, 201, 173, 0.9)"
                        box-shadow="0 16rpx 36rpx rgba(74, 43, 24, 0.07)"
                        class="waitlist-card"
                    >
                        <view class="waitlist-card__head">
                            <view class="waitlist-card__title-group">
                                <view class="waitlist-card__eyebrow">
                                    <BaseIcon name="calendar" size="22" color="#B8954A" />
                                    <text>候补档期</text>
                                </view>
                                <text class="waitlist-card__title">{{ item.title }}</text>
                            </view>

                            <StatusBadge :tone="item.statusTone" size="sm" dot>
                                {{ item.statusText }}
                            </StatusBadge>
                        </view>

                        <view class="waitlist-card__meta-grid">
                            <view class="waitlist-card__meta-item">
                                <view class="waitlist-card__meta-icon">
                                    <BaseIcon name="calendar" size="24" color="#9A9388" />
                                </view>
                                <view class="waitlist-card__meta-copy">
                                    <text class="waitlist-card__meta-label">候补日期</text>
                                    <text class="waitlist-card__meta-value">
                                        {{ item.scheduleText }}
                                    </text>
                                </view>
                            </view>

                            <view class="waitlist-card__meta-item waitlist-card__meta-item--second">
                                <view class="waitlist-card__meta-icon">
                                    <BaseIcon name="order" size="24" color="#9A9388" />
                                </view>
                                <view class="waitlist-card__meta-copy">
                                    <text class="waitlist-card__meta-label">服务内容</text>
                                    <text class="waitlist-card__meta-value">
                                        {{ item.detailText }}
                                    </text>
                                </view>
                            </view>
                        </view>

                        <view class="waitlist-card__progress">
                            <view class="waitlist-card__progress-main">
                                <view class="waitlist-card__progress-icon">
                                    <BaseIcon name="tip" size="24" color="#B8954A" />
                                </view>
                                <view class="waitlist-card__progress-copy">
                                    <text class="waitlist-card__progress-label">当前进度</text>
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
                                    <text class="waitlist-card__progress-meta-label">时间提醒</text>
                                    <text class="waitlist-card__progress-meta-value">
                                        {{ item.timelineText }}
                                    </text>
                                </view>
                                <view
                                    v-if="item.bookBlockReason"
                                    class="waitlist-card__progress-meta-row waitlist-card__progress-meta-row--warning"
                                >
                                    <text class="waitlist-card__progress-meta-label">当前提示</text>
                                    <text class="waitlist-card__progress-meta-value">
                                        {{ item.bookBlockReason }}
                                    </text>
                                </view>
                            </view>
                        </view>

                        <view class="waitlist-card__foot">
                            <text class="waitlist-card__created-at">
                                创建于 {{ item.createdAtText }}
                            </text>

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
                                    icon="close"
                                    class="waitlist-card__action"
                                    @click.stop="handleCancel(item)"
                                />
                                <BaseButton
                                    v-if="item.showBookAction"
                                    label="立即预约"
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
import FilterChip from '@/components/base/FilterChip.vue'
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
    { value: 2, label: '已下单' },
    { value: 3, label: '已过期' }
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
        3: '已过期'
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
        0: '已加入候补队列。',
        1: '档期已释放，请尽快预约。',
        2: '已转为正式预约。',
        3: '本次候补已失效。'
    }

    return map[status] || '请留意后续通知。'
}

const getNextStepText = (status: number) => {
    const map: Record<number, string> = {
        0: '下一步：等待通知。',
        1: '下一步：确认档期并预约。',
        2: '下一步：留意订单与消息通知。',
        3: '下一步：重新查询档期。'
    }

    return map[status] || '下一步：关注消息通知。'
}

const buildTimelineText = (item: WaitlistRecord) => {
    const parts: string[] = []
    if (item.notify_time_text) {
        parts.push(`通知时间 ${item.notify_time_text}`)
    }
    if (item.expire_time_text) {
        parts.push(
            Number(item.notify_status) === 1
                ? `预约截止 ${item.expire_time_text}`
                : `候补保留至 ${item.expire_time_text}`
        )
    }

    const timelineText = parts.join(' · ')
    const hintText = String(item.status_hint || '')
    if (item.expire_time_text && hintText.includes(String(item.expire_time_text))) {
        return parts.length > 1 ? parts.filter((part) => !part.includes(String(item.expire_time_text))).join(' · ') : ''
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
        content: '确定要取消该候补吗？取消后需重新加入。',
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
@import '../../../styles/aftersale.scss';

.waitlist-page {
    @include aftersale-page-base;
    min-height: 100vh;
}

.waitlist-page__wrapper {
    @include aftersale-page-wrapper;
    gap: 18rpx;
    padding-top: 16rpx;
    padding-bottom: var(--wm-space-section-gap-lg, 30rpx);
}

.waitlist-page__filter-scroll {
    width: 100%;
    white-space: nowrap;
}

.waitlist-page__filter-row {
    display: inline-flex;
    align-items: center;
    padding-bottom: 4rpx;
}

.waitlist-page__filter-chip {
    margin-right: 12rpx;
}

.waitlist-list {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    width: 100%;
    gap: 18rpx;
    box-sizing: border-box;
}

.waitlist-card {
    display: block;
    width: 100%;
    box-sizing: border-box;
}

.waitlist-card__head,
.waitlist-card__eyebrow,
.waitlist-card__meta-item,
.waitlist-card__progress-main,
.waitlist-card__foot,
.waitlist-card__actions {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
}

.waitlist-card__head {
    align-items: flex-start;
    justify-content: space-between;
    gap: 18rpx;
}

.waitlist-card__title-group {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.waitlist-card__eyebrow {
    gap: 8rpx;
    font-size: 22rpx;
    line-height: 1.2;
    font-weight: 800;
    color: var(--wm-color-gold, #b8954a);
}

.waitlist-card__title {
    display: block;
    min-width: 0;
    font-size: 30rpx;
    line-height: 1.35;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.waitlist-card__meta-grid {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: stretch;
    width: 100%;
    margin-top: 20rpx;
}

.waitlist-card__meta-item {
    min-width: 0;
    flex: 1 1 0;
    align-items: flex-start;
    padding: 16rpx;
    border-radius: 24rpx;
    background: rgba(248, 242, 228, 0.58);
    border: 1rpx solid rgba(216, 201, 173, 0.62);
    box-sizing: border-box;
}

.waitlist-card__meta-item--second {
    margin-left: 14rpx;
}

.waitlist-card__meta-icon {
    width: 42rpx;
    height: 42rpx;
    flex-shrink: 0;
    border-radius: 16rpx;
    background: rgba(255, 253, 248, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
}

.waitlist-card__meta-copy {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    margin-left: 12rpx;
}

.waitlist-card__meta-label {
    display: block;
    font-size: 21rpx;
    line-height: 1.35;
    color: var(--wm-text-tertiary, #9a9388);
}

.waitlist-card__meta-value {
    display: block;
    min-width: 0;
    margin-top: 4rpx;
    font-size: 24rpx;
    line-height: 1.35;
    font-weight: 800;
    color: var(--wm-text-primary, #191713);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.waitlist-card__progress {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    margin-top: 16rpx;
    padding: 20rpx;
    border-radius: 26rpx;
    background: rgba(255, 253, 248, 0.84);
    border: 1rpx solid rgba(216, 201, 173, 0.62);
}

.waitlist-card__progress-main {
    align-items: flex-start;
}

.waitlist-card__progress-icon {
    width: 44rpx;
    height: 44rpx;
    flex-shrink: 0;
    border-radius: 16rpx;
    background: rgba(241, 229, 200, 0.72);
    display: flex;
    align-items: center;
    justify-content: center;
}

.waitlist-card__progress-copy {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    margin-left: 14rpx;
}

.waitlist-card__progress-label {
    display: block;
    font-size: 22rpx;
    line-height: 1.2;
    color: var(--wm-text-tertiary, #9a9388);
}

.waitlist-card__progress-title {
    display: block;
    margin-top: 8rpx;
    font-size: 25rpx;
    line-height: 1.5;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
}

.waitlist-card__progress-next {
    display: block;
    margin-top: 8rpx;
    font-size: 23rpx;
    line-height: 1.5;
    color: var(--wm-text-secondary, #665e52);
}

.waitlist-card__progress-meta {
    display: flex;
    flex-direction: column;
    margin-top: 14rpx;
    padding-top: 14rpx;
    border-top: 1rpx solid rgba(216, 201, 173, 0.5);
}

.waitlist-card__progress-meta-row {
    display: flex;
    align-items: flex-start;
    min-width: 0;
}

.waitlist-card__progress-meta-row + .waitlist-card__progress-meta-row {
    margin-top: 10rpx;
}

.waitlist-card__progress-meta-label {
    width: 112rpx;
    flex-shrink: 0;
    font-size: 22rpx;
    line-height: 1.45;
    color: var(--wm-text-tertiary, #9a9388);
}

.waitlist-card__progress-meta-value {
    flex: 1;
    min-width: 0;
    margin-left: 16rpx;
    font-size: 22rpx;
    line-height: 1.45;
    color: var(--wm-text-secondary, #665e52);
    word-break: break-all;
}

.waitlist-card__progress-meta-row--warning .waitlist-card__progress-meta-value {
    color: var(--wm-color-clay, #9a6b35);
}

.waitlist-card__foot {
    align-items: stretch;
    justify-content: space-between;
    margin-top: 16rpx;
    padding-top: 20rpx;
    border-top: 1rpx solid rgba(216, 201, 173, 0.62);
}

.waitlist-card__created-at {
    min-width: 0;
    flex: 1;
    font-size: 22rpx;
    line-height: 1.4;
    color: var(--wm-text-tertiary, #9a9388);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.waitlist-card__actions {
    flex-shrink: 0;
    justify-content: flex-end;
    margin-left: 20rpx;
}

.waitlist-card__action {
    flex-shrink: 0;
    min-width: 154rpx;
}

.waitlist-card__action + .waitlist-card__action {
    margin-left: 12rpx;
}

@media screen and (max-width: 360px) {
    .waitlist-card__head,
    .waitlist-card__foot {
        align-items: stretch;
        flex-direction: column;
    }

    .waitlist-card__meta-grid {
        flex-direction: column;
    }

    .waitlist-card__meta-item--second {
        margin-top: 12rpx;
        margin-left: 0;
    }

    .waitlist-card__actions,
    .waitlist-card__action {
        width: 100%;
    }

    .waitlist-card__actions {
        flex-direction: column;
        margin-left: 0;
    }

    .waitlist-card__progress-meta-row {
        flex-direction: column;
    }

    .waitlist-card__progress-meta-label {
        width: auto;
    }

    .waitlist-card__progress-meta-value {
        margin-top: 4rpx;
        margin-left: 0;
    }

    .waitlist-card__action + .waitlist-card__action {
        margin-top: 12rpx;
        margin-left: 0;
    }
}
</style>
