<template>
    <view class="my-activity-list">
        <!-- 顶部高定分段胶囊选择器 -->
        <view v-if="showTabs" class="my-activity-list__filter-sticky">
            <scroll-view
                scroll-x
                class="my-activity-list__filter-scroll"
                :show-scrollbar="false"
                enhanced
                :bounces="true"
            >
                <view class="my-activity-list__filter-track">
                    <view
                        v-for="tab in tabs"
                        :key="tab.value"
                        class="my-activity-list__filter-pill"
                        :class="{ 'my-activity-list__filter-pill--active': currentStatusGroup === tab.value }"
                        @click="switchTab(tab.value)"
                    >
                        <text class="my-activity-list__filter-pill-label">{{ tab.label }}</text>
                    </view>
                </view>
            </scroll-view>
        </view>

        <!-- 加载与空状态 -->
        <view v-if="loading && registrations.length === 0" class="my-activity-list__state-wrap">
            <LoadingState text="正在加载活动报名..." />
        </view>
        <view v-else-if="errorText" class="my-activity-list__state-wrap">
            <EmptyState
                :title="errorText"
                action-text="重试"
                @action="fetchList(true)"
            />
        </view>
        <view v-else-if="registrations.length === 0" class="my-activity-list__state-wrap">
            <EmptyState :title="emptyTitle" icon="calendar" />
        </view>

        <!-- 活动卡片列表 -->
        <view v-else class="my-activity-list__stack">
            <view
                v-for="item in registrations"
                :key="item.id"
                class="activity-card"
                @click="goDetail(item.id)"
            >
                <!-- 卡片头部：活动标题 + 状态徽章 -->
                <view class="activity-card__head">
                    <view class="activity-card__title-wrap">
                        <view class="activity-card__date-badge">
                            <BaseIcon name="calendar" size="20" color="#C6A15B" />
                            <text class="activity-card__date-text">{{ formatDateOnly(item.dynamic?.activity_start_time) }}</text>
                        </view>
                        <text class="activity-card__title">{{ item.dynamic?.title || '精彩主题活动' }}</text>
                    </view>
                    <view class="activity-card__badge">
                        <StatusBadge
                            :tone="getStatusTone(item)"
                            size="sm"
                            dot
                        >
                            {{ item.registration_status_desc || '未知状态' }}
                        </StatusBadge>
                    </view>
                </view>

                <!-- 卡片中部：门票与活动时间网格 -->
                <view class="activity-card__body">
                    <view class="activity-card__meta-row">
                        <view class="activity-card__ticket-pill">
                            <text class="activity-card__ticket-name">{{ item.ticket_name || '常规活动门票' }}</text>
                        </view>
                        <view class="activity-card__price-box">
                            <text class="activity-card__price-label">报名费用</text>
                            <text class="activity-card__price-value">{{ item.ticket_price_label || '免费' }}</text>
                        </view>
                    </view>

                    <view class="activity-card__info-grid">
                        <view class="activity-card__info-item">
                            <BaseIcon name="clock" size="22" color="#8C8273" />
                            <text class="activity-card__info-label">活动开始：</text>
                            <text class="activity-card__info-value">{{ formatTime(item.dynamic?.activity_start_time) }}</text>
                        </view>
                        <view class="activity-card__info-item">
                            <BaseIcon name="user" size="22" color="#8C8273" />
                            <text class="activity-card__info-label">报名联系人：</text>
                            <text class="activity-card__info-value">{{ item.contact_name || '-' }}</text>
                        </view>
                    </view>

                    <!-- 取消/退款进度提示条 -->
                    <view v-if="getProgressText(item)" class="activity-card__progress-banner">
                        <BaseIcon name="tip" size="22" color="#9A6B35" />
                        <text class="activity-card__progress-text">{{ getProgressText(item) }}</text>
                    </view>
                </view>

                <!-- 卡片底部：报名时间 + 操作按钮 -->
                <view class="activity-card__foot">
                    <text class="activity-card__time">报名时间 {{ formatTime(item.create_time) }}</text>
                    <view class="activity-card__action" @click.stop>
                        <BaseButton
                            v-if="Number(item.registration_status) === 0"
                            label="继续支付"
                            size="sm"
                            variant="dark"
                            height="62rpx"
                            font-size="23rpx"
                            @click="goDetail(item.id)"
                        />
                        <BaseButton
                            v-else
                            label="查看详情"
                            size="sm"
                            variant="light"
                            height="62rpx"
                            font-size="23rpx"
                            icon="right"
                            icon-position="right"
                            @click="goDetail(item.id)"
                        />
                    </view>
                </view>
            </view>

            <!-- 分页状态 -->
            <view v-if="loading && registrations.length > 0" class="my-activity-list__loading-more">
                <tn-loading size="34" mode="flower" color="#C6A15B" />
                <text class="my-activity-list__loading-text">加载中...</text>
            </view>
            <view v-else-if="!hasMore && registrations.length > 0" class="my-activity-list__end">
                <view class="my-activity-list__end-line"></view>
                <text class="my-activity-list__end-text">✦ 没有更多报名记录了 ✦</text>
                <view class="my-activity-list__end-line"></view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { getActivityRegistrations } from '@/api/dynamic'

type StatusGroup = 'all' | 'pending_pay' | 'registered' | 'cancel_refund'

interface Props {
    showTabs?: boolean
}

withDefaults(defineProps<Props>(), {
    showTabs: true
})

const tabs: Array<{ label: string; value: StatusGroup; empty: string }> = [
    { label: '全部', value: 'all', empty: '暂无活动报名记录' },
    { label: '待支付', value: 'pending_pay', empty: '暂无待支付的活动' },
    { label: '已报名', value: 'registered', empty: '暂无已报名的活动' },
    { label: '取消/退款', value: 'cancel_refund', empty: '暂无取消或退款记录' }
]

const currentStatusGroup = ref<StatusGroup>('all')
const registrations = ref<any[]>([])
const loading = ref(false)
const page = ref(1)
const hasMore = ref(true)
const errorText = ref('')

const emptyTitle = computed(() => {
    return tabs.find((item) => item.value === currentStatusGroup.value)?.empty || '暂无活动'
})

const fetchList = async (refresh = false) => {
    if (loading.value || (!refresh && !hasMore.value)) return
    loading.value = true
    try {
        if (refresh) {
            page.value = 1
            registrations.value = []
            hasMore.value = true
            errorText.value = ''
        }
        const res = await getActivityRegistrations({
            page: page.value,
            page_size: 10,
            status_group: currentStatusGroup.value
        })
        const list = res?.data || []
        registrations.value = refresh ? list : registrations.value.concat(list)
        const currentPage = Number(res?.current_page || page.value)
        const lastPage = Number(res?.last_page || currentPage)
        hasMore.value = currentPage < lastPage
    } catch (error: any) {
        errorText.value = error?.message || '活动报名加载失败'
        hasMore.value = false
    } finally {
        loading.value = false
    }
}

const switchTab = (value: StatusGroup) => {
    if (currentStatusGroup.value === value) return
    currentStatusGroup.value = value
    fetchList(true)
}

const refresh = () => fetchList(true)

const loadMore = () => {
    if (!hasMore.value || loading.value) return
    page.value += 1
    fetchList()
}

const getStatusTone = (item: any) => {
    const status = Number(item?.registration_status ?? -1)
    if (status === 0) return 'warning'
    if (status === 1) return 'success'
    if (status === 2 || status === 4) return 'info'
    if (status === 5) return 'danger'
    return 'neutral'
}

const getProgressText = (item: any) => {
    if (Number(item?.cancel_status || 0) > 0) {
        return item.cancel_status_desc || ''
    }
    if (item?.latest_refund?.refund_status_desc) {
        return item.latest_refund.refund_status_desc
    }
    return ''
}

const formatTime = (timestamp: number | string | undefined) => {
    if (timestamp === undefined || timestamp === null || timestamp === '') return '-'
    const rawValue = String(timestamp).trim()
    if (!rawValue || rawValue === '0') return '-'
    const numericValue = Number(rawValue)
    const date = Number.isFinite(numericValue)
        ? new Date(numericValue > 1000000000000 ? numericValue : numericValue * 1000)
        : new Date(rawValue.replace(/-/g, '/'))
    if (Number.isNaN(date.getTime())) return '-'
    const pad = (num: number) => String(num).padStart(2, '0')
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(
        date.getHours()
    )}:${pad(date.getMinutes())}`
}

const formatDateOnly = (timestamp: number | string | undefined) => {
    const value = formatTime(timestamp)
    return value === '-' ? '时间待定' : value.slice(0, 10)
}

const goDetail = (id: number) => {
    uni.navigateTo({ url: `/packages/pages/activity_registration/detail?id=${id}` })
}

onMounted(() => {
    refresh()
})

defineExpose({
    refresh,
    loadMore
})
</script>

<style lang="scss" scoped>
.my-activity-list {
    display: flex;
    flex-direction: column;
    gap: 24rpx;

    &__filter-sticky {
        position: sticky;
        top: 0;
        z-index: 10;
        padding-bottom: 8rpx;
    }

    &__filter-scroll {
        width: 100%;
        white-space: nowrap;
    }

    &__filter-track {
        display: inline-flex;
        align-items: center;
        gap: 14rpx;
        padding: 4rpx 2rpx;
    }

    &__filter-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 64rpx;
        padding: 0 30rpx;
        border-radius: 999rpx;
        background: rgba(255, 253, 248, 0.88);
        border: 1rpx solid rgba(217, 190, 130, 0.45);
        box-shadow: 0 4rpx 12rpx rgba(24, 22, 20, 0.03);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        box-sizing: border-box;

        &:active {
            transform: scale(0.96);
        }

        &--active {
            background: var(--wm-color-primary, #181614);
            border-color: var(--wm-color-primary, #181614);
            box-shadow: 0 8rpx 20rpx rgba(24, 22, 20, 0.2);

            .my-activity-list__filter-pill-label {
                color: var(--wm-text-inverse, #FFFDF8);
                font-weight: 700;
            }
        }
    }

    &__filter-pill-label {
        font-size: 24rpx;
        font-weight: 600;
        color: var(--wm-color-text-secondary, #5E564B);
    }

    &__state-wrap {
        min-height: 50vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__stack {
        display: flex;
        flex-direction: column;
        gap: 24rpx;
    }
}

/* 活动卡片 */
.activity-card {
    padding: 30rpx;
    border-radius: 32rpx;
    background: linear-gradient(180deg, #FFFFFF 0%, #FAF8F5 100%);
    border: 1rpx solid rgba(217, 190, 130, 0.4);
    box-shadow: 0 12rpx 32rpx rgba(24, 22, 20, 0.05);
    display: flex;
    flex-direction: column;
    gap: 20rpx;
    box-sizing: border-box;
    transition: transform 0.18s ease;

    &:active {
        transform: translateY(2rpx);
    }

    &__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__title-wrap {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 8rpx;
    }

    &__date-badge {
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
        padding: 4rpx 14rpx;
        border-radius: 999rpx;
        background: rgba(242, 236, 225, 0.7);
        border: 1rpx solid rgba(217, 190, 130, 0.3);
        align-self: flex-start;
    }

    &__date-text {
        font-size: 20rpx;
        font-weight: 600;
        color: var(--wm-color-text-secondary, #5E564B);
    }

    &__title {
        font-size: 30rpx;
        font-weight: 800;
        line-height: 1.35;
        color: var(--wm-color-primary, #181614);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__badge {
        flex-shrink: 0;
    }

    &__body {
        display: flex;
        flex-direction: column;
        gap: 14rpx;
        padding: 18rpx 20rpx;
        border-radius: 20rpx;
        background: rgba(250, 246, 238, 0.8);
        border: 1rpx solid rgba(231, 224, 211, 0.85);
    }

    &__meta-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__ticket-pill {
        padding: 4rpx 14rpx;
        border-radius: 999rpx;
        background: rgba(198, 161, 91, 0.16);
    }

    &__ticket-name {
        font-size: 21rpx;
        font-weight: 600;
        color: var(--wm-color-clay, #9A6B35);
    }

    &__price-box {
        display: inline-flex;
        align-items: baseline;
        gap: 6rpx;
    }

    &__price-label {
        font-size: 20rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }

    &__price-value {
        font-size: 28rpx;
        font-weight: 800;
        color: var(--wm-color-primary, #181614);
    }

    &__info-grid {
        display: flex;
        flex-direction: column;
        gap: 8rpx;
        padding-top: 10rpx;
        border-top: 1rpx solid rgba(231, 224, 211, 0.7);
    }

    &__info-item {
        display: flex;
        align-items: center;
        gap: 8rpx;
    }

    &__info-label {
        font-size: 22rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }

    &__info-value {
        font-size: 22rpx;
        font-weight: 600;
        color: var(--wm-color-text-secondary, #5E564B);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__progress-banner {
        display: flex;
        align-items: center;
        gap: 8rpx;
        padding: 10rpx 16rpx;
        border-radius: 12rpx;
        background: rgba(217, 190, 130, 0.18);
    }

    &__progress-text {
        font-size: 21rpx;
        font-weight: 600;
        color: var(--wm-color-clay, #9A6B35);
    }

    &__foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
        padding-top: 16rpx;
        border-top: 1rpx solid rgba(231, 224, 211, 0.7);
    }

    &__time {
        font-size: 20rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }

    &__action {
        flex-shrink: 0;
    }
}

/* 分页状态 */
.my-activity-list__loading-more {
    padding: 30rpx 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
}

.my-activity-list__loading-text {
    font-size: 24rpx;
    color: var(--wm-color-text-secondary, #5E564B);
}

.my-activity-list__end {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20rpx;
    padding: 36rpx 20rpx 20rpx;
}

.my-activity-list__end-line {
    flex: 1;
    max-width: 120rpx;
    height: 1rpx;
    background: linear-gradient(90deg, transparent, rgba(217, 190, 130, 0.6), transparent);
}

.my-activity-list__end-text {
    font-size: 22rpx;
    color: var(--wm-color-text-tertiary, #8C8273);
    letter-spacing: 2rpx;
}
</style>
