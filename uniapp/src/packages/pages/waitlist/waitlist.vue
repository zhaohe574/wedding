<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="我的候补" />

        <view class="waitlist-page">
            <scroll-view scroll-x class="waitlist-page__filter-scroll" :show-scrollbar="false">
                <view class="waitlist-page__filter-row">
                    <view
                        v-for="tab in statusTabs"
                        :key="tab.value"
                        class="waitlist-page__filter-chip"
                        :class="{
                            'waitlist-page__filter-chip--active': currentStatus === tab.value
                        }"
                        @click="handleStatusChange(tab.value)"
                    >
                        <text class="waitlist-page__filter-chip-text">{{ tab.label }}</text>
                    </view>
                </view>
            </scroll-view>

            <view class="waitlist-page__content">
                <view v-if="loading && waitlistItems.length === 0" class="loading-state">
                    <view class="loading-content">
                        <tn-loading size="72" mode="flower" :color="$theme.primaryColor" />
                        <text class="loading-text">加载中...</text>
                    </view>
                </view>

                <view v-else-if="waitlistItems.length === 0" class="empty-state">
                    <view class="empty-icon-wrapper">
                        <tn-icon name="inbox" size="156" color="#D9CDC7" />
                    </view>
                    <text class="empty-title">暂无候补记录</text>
                    <view
                        class="empty-action-btn"
                        :style="{
                            background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${
                                $theme.secondaryColor || $theme.primaryColor
                            } 100%)`
                        }"
                        @click="goSchedule"
                    >
                        <text class="empty-action-text" :style="{ color: $theme.btnColor }">
                            去预约
                        </text>
                    </view>
                </view>

                <view v-else class="waitlist-list">
                    <view v-for="item in waitlistItems" :key="item.id" class="waitlist-card">
                        <view class="waitlist-card__head">
                            <text class="waitlist-card__title">{{ item.title }}</text>
                            <view
                                class="waitlist-card__status"
                                :class="item.statusClass"
                            >
                                <text class="waitlist-card__status-text">
                                    {{ item.statusText }}
                                </text>
                            </view>
                        </view>

                        <view class="waitlist-card__body">
                            <text class="waitlist-card__schedule">{{ item.scheduleText }}</text>
                            <text class="waitlist-card__detail">{{ item.detailText }}</text>
                        </view>

                        <view class="waitlist-card__foot">
                            <text class="waitlist-card__created-at">
                                创建于 {{ item.createdAtText }}
                            </text>

                            <view
                                v-if="item.showBookAction || item.showCancelAction"
                                class="waitlist-card__actions"
                            >
                                <view
                                    v-if="item.showCancelAction"
                                    class="waitlist-card__action"
                                    @click.stop="handleCancel(item)"
                                >
                                    <text class="waitlist-card__action-text">取消候补</text>
                                </view>
                                <view
                                    v-if="item.showBookAction"
                                    class="waitlist-card__action waitlist-card__action--primary"
                                    @click.stop="handleBook(item)"
                                >
                                    <text
                                        class="waitlist-card__action-text waitlist-card__action-text--primary"
                                    >
                                        立即预约
                                    </text>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import PageShell from '@/components/base/PageShell.vue'
import { getMyWaitlist, cancelWaitlist } from '@/api/schedule'
import { useThemeStore } from '@/stores/theme'

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
}

interface WaitlistViewItem extends WaitlistRecord {
    title: string
    scheduleText: string
    detailText: string
    createdAtText: string
    statusText: string
    statusClass: string
    showBookAction: boolean
    showCancelAction: boolean
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

const getStatusClass = (status: number) => {
    const map: Record<number, string> = {
        0: 'waitlist-card__status--waiting',
        1: 'waitlist-card__status--notified',
        2: 'waitlist-card__status--ordered',
        3: 'waitlist-card__status--expired'
    }

    return map[status] || 'waitlist-card__status--waiting'
}

const getStatusText = (status: number) => {
    const map: Record<number, string> = {
        0: '等待中',
        1: '已通知',
        2: '已下单',
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

const waitlistItems = computed<WaitlistViewItem[]>(() => {
    return waitlist.value.map((item) => ({
        ...item,
        title: buildTitle(item),
        scheduleText: buildScheduleText(item),
        detailText: buildDetailText(item),
        createdAtText: formatTime(item.create_time),
        statusText: item.notify_status_desc || getStatusText(Number(item.notify_status || 0)),
        statusClass: getStatusClass(Number(item.notify_status || 0)),
        showBookAction: Number(item.notify_status) === 1,
        showCancelAction: [0, 1].includes(Number(item.notify_status))
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
    if (!item.staff_id) {
        uni.showToast({ title: '服务人员信息错误', icon: 'none' })
        return
    }

    const params = [`id=${item.staff_id}`]
    if (item.schedule_date) {
        params.push(`date=${item.schedule_date}`)
    }
    if (item.package_id) {
        params.push(`package_id=${item.package_id}`)
    }
    if (!item.schedule_date) {
        params.push('open_date_picker=1')
    }

    uni.navigateTo({
        url: `/packages/pages/staff_detail/staff_detail?${params.join('&')}`
    })
}

const handleCancel = (item: WaitlistRecord) => {
    uni.showModal({
        title: '取消候补',
        content: '确定要取消该候补吗？取消后需要重新加入候补队列。',
        confirmColor: '#FF2C3C',
        success: async (res) => {
            if (res.confirm) {
                try {
                    await cancelWaitlist({ id: Number(item.id) })
                    uni.showToast({ title: '取消成功', icon: 'success' })
                    fetchList()
                } catch (error: any) {
                    uni.showToast({ title: error.message || '操作失败', icon: 'none' })
                }
            }
        }
    })
}

onShow(() => {
    $theme.setScene('consumer')
    fetchList()
})
</script>

<style lang="scss" scoped>
.waitlist-page {
    min-height: 100%;
    background: var(--wm-color-page, #fcfbf9);

    &__filter-scroll {
        margin-top: var(--wm-space-section-gap-sm, 22rpx);
        padding: 0 var(--wm-space-page-x, 37rpx);
        white-space: nowrap;
    }

    &__filter-row {
        display: inline-flex;
        gap: var(--wm-space-section-gap-sm, 22rpx);
        padding-bottom: 15rpx;
    }

    &__filter-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 82rpx;
        padding: 0 30rpx;
        border-radius: 999rpx;
        border: 1rpx solid var(--wm-color-border, #efe6e1);
        background: rgba(255, 255, 255, 0.84);
        color: var(--wm-text-secondary, #7f7b78);
        box-sizing: border-box;

        &--active {
            border-color: var(--wm-color-primary, #e85a4f);
            background: var(--wm-color-primary, #e85a4f);
            color: #ffffff;
            box-shadow: 0 8rpx 18rpx rgba(232, 90, 79, 0.14);
        }
    }

    &__filter-chip-text {
        font-size: 28rpx;
        font-weight: 600;
        line-height: 1;
    }

    &__content {
        padding: var(--wm-space-section-gap-sm, 22rpx) var(--wm-space-page-x, 37rpx)
            var(--wm-space-6, 45rpx);
    }
}

.loading-state {
    min-height: 56vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.loading-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16rpx;
}

.loading-text {
    font-size: 26rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.empty-state {
    min-height: 56vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: var(--wm-space-8, 60rpx) 24rpx 96rpx;
}

.empty-icon-wrapper {
    width: 220rpx;
    height: 220rpx;
    margin-bottom: var(--wm-space-3, 22rpx);
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-title {
    font-size: 34rpx;
    font-weight: 600;
    color: var(--wm-text-primary, #1e2432);
    margin-bottom: var(--wm-space-5, 37rpx);
    text-align: center;
}

.empty-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 280rpx;
    min-height: 88rpx;
    padding: 0 36rpx;
    border-radius: 999rpx;
    box-shadow: 0 12rpx 24rpx rgba(232, 90, 79, 0.16);

    &:active {
        transform: translateY(1rpx);
    }
}

.empty-action-text {
    font-size: 30rpx;
    font-weight: 700;
}

.waitlist-list {
    display: flex;
    flex-direction: column;
    gap: 30rpx;
}

.waitlist-card {
    padding: var(--wm-space-card-padding-lg, 34rpx) var(--wm-space-page-x, 37rpx);
    border-radius: var(--wm-radius-card, 45rpx);
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    background: rgba(255, 255, 255, 0.88);
    box-shadow: 0 8rpx 18rpx rgba(214, 185, 167, 0.08);
}

.waitlist-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20rpx;
}

.waitlist-card__title {
    flex: 1;
    min-width: 0;
    display: block;
    font-size: 30rpx;
    font-weight: 600;
    line-height: 1.6;
    color: var(--wm-text-primary, #1e2432);
}

.waitlist-card__status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48rpx;
    padding: 0 24rpx;
    border-radius: 999rpx;
    box-sizing: border-box;
    flex-shrink: 0;
}

.waitlist-card__status--waiting {
    color: var(--wm-color-info, #607086);
    background: rgba(96, 112, 134, 0.1);
    border: 1rpx solid rgba(96, 112, 134, 0.14);
}

.waitlist-card__status--notified {
    color: var(--wm-color-warning, #c98524);
    background: rgba(201, 133, 36, 0.12);
    border: 1rpx solid rgba(201, 133, 36, 0.14);
}

.waitlist-card__status--ordered {
    color: var(--wm-color-success, #2f7d58);
    background: rgba(47, 125, 88, 0.12);
    border: 1rpx solid rgba(47, 125, 88, 0.14);
}

.waitlist-card__status--expired {
    color: var(--wm-text-tertiary, #b4aca8);
    background: rgba(180, 172, 168, 0.14);
    border: 1rpx solid rgba(180, 172, 168, 0.16);
}

.waitlist-card__status-text {
    font-size: 24rpx;
    font-weight: 600;
    line-height: 1;
}

.waitlist-card__body {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    margin-top: 20rpx;
}

.waitlist-card__schedule {
    display: block;
    font-size: 28rpx;
    line-height: 1.6;
    color: var(--wm-text-primary, #1e2432);
}

.waitlist-card__detail {
    display: block;
    font-size: 24rpx;
    line-height: 1.7;
    color: var(--wm-text-secondary, #7f7b78);
}

.waitlist-card__foot {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24rpx;
    margin-top: var(--wm-space-4, 30rpx);
}

.waitlist-card__created-at {
    flex: 1;
    min-width: 0;
    font-size: 22rpx;
    line-height: 1.5;
    color: var(--wm-text-tertiary, #b4aca8);
}

.waitlist-card__actions {
    display: inline-flex;
    align-items: center;
    gap: 16rpx;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.waitlist-card__action {
    min-width: 128rpx;
    height: 82rpx;
    padding: 0 30rpx;
    border-radius: 999rpx;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    background: rgba(255, 255, 255, 0.82);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;

    &--primary {
        border-color: var(--wm-color-primary, #e85a4f);
        background: var(--wm-color-primary, #e85a4f);
    }
}

.waitlist-card__action-text {
    font-size: 24rpx;
    font-weight: 600;
    line-height: 1;
    color: var(--wm-text-primary, #1e2432);

    &--primary {
        color: #ffffff;
    }
}
</style>
