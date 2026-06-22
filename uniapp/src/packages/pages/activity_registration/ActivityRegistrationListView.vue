<template>
    <view class="my-activity-list">
        <BaseCard v-if="showTabs" variant="list" padding="10rpx" class="my-activity-list__filter-shell">
            <scroll-view
                scroll-x
                class="my-activity-list__filter-scroll"
                :show-scrollbar="false"
            >
                <view class="my-activity-list__filter-track">
                    <view
                        v-for="tab in tabs"
                        :key="tab.value"
                        class="my-activity-list__filter-item"
                    >
                        <FilterChip
                            :label="tab.label"
                            :selected="currentStatusGroup === tab.value"
                            @click="switchTab(tab.value)"
                        />
                    </view>
                </view>
            </scroll-view>
        </BaseCard>

        <BaseCard v-if="loading && registrations.length === 0" variant="panel" class="my-activity-list__state-card">
            <LoadingState text="正在加载活动报名..." tone="wedding" compact />
        </BaseCard>
        <BaseCard v-else-if="errorText" variant="panel" class="my-activity-list__state-card">
            <EmptyState :title="errorText" tone="error" action-text="重试" compact @action="fetchList(true)" />
        </BaseCard>
        <BaseCard v-else-if="registrations.length === 0" variant="panel">
            <EmptyState :title="emptyTitle" icon="calendar" compact />
        </BaseCard>

        <view v-else class="my-activity-list__stack">
            <BaseCard
                v-for="item in registrations"
                :key="item.id"
                variant="panel"
                class="my-activity-list__card"
                interactive
                @click="goDetail(item.id)"
            >
                <view class="my-activity-list__head">
                    <view class="my-activity-list__copy">
                        <text class="my-activity-list__eyebrow">
                            {{ formatDateOnly(item.dynamic?.activity_start_time) }}
                        </text>
                        <text class="my-activity-list__title">
                            {{ item.dynamic?.title || '活动' }}
                        </text>
                        <text class="my-activity-list__meta">
                            {{ item.ticket_name || '活动票' }}
                        </text>
                    </view>
                    <StatusBadge
                        :label="item.registration_status_desc || '未知'"
                        :tone="getStatusTone(item)"
                        size="sm"
                        strong
                    />
                </view>

                <view class="my-activity-list__info-grid">
                    <view class="my-activity-list__info-item">
                        <text class="my-activity-list__info-label">活动时间</text>
                        <text class="my-activity-list__info-value">
                            {{ formatTime(item.dynamic?.activity_start_time) }}
                        </text>
                    </view>
                    <view class="my-activity-list__info-item">
                        <text class="my-activity-list__info-label">报名时间</text>
                        <text class="my-activity-list__info-value">{{ formatTime(item.create_time) }}</text>
                    </view>
                    <view class="my-activity-list__info-item">
                        <text class="my-activity-list__info-label">支付状态</text>
                        <text class="my-activity-list__info-value">{{ item.pay_status_desc || '-' }}</text>
                    </view>
                    <view class="my-activity-list__info-item">
                        <text class="my-activity-list__info-label">票种金额</text>
                        <text class="my-activity-list__info-value my-activity-list__info-value--price">
                            {{ item.ticket_price_label || '免费' }}
                        </text>
                    </view>
                    <view v-if="getProgressText(item)" class="my-activity-list__info-item">
                        <text class="my-activity-list__info-label">进度</text>
                        <text class="my-activity-list__info-value">{{ getProgressText(item) }}</text>
                    </view>
                </view>

                <view class="my-activity-list__footer">
                    <view class="my-activity-list__contact-wrap">
                        <text class="my-activity-list__contact-label">联系人</text>
                        <text class="my-activity-list__contact">
                            {{ item.contact_name || '联系人' }}
                        </text>
                    </view>
                    <BaseButton
                        v-if="Number(item.registration_status) === 0"
                        label="继续支付"
                        size="mini"
                        variant="dark"
                        @click.stop="goDetail(item.id)"
                    />
                    <BaseButton
                        v-else
                        label="查看详情"
                        size="mini"
                        variant="secondary"
                        @click.stop="goDetail(item.id)"
                    />
                </view>
            </BaseCard>
        </view>

        <view v-if="loading && registrations.length > 0" class="my-activity-list__loading-more">
            加载中...
        </view>
        <view v-else-if="!hasMore && registrations.length > 0" class="my-activity-list__end">
            没有更多报名了
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import FilterChip from '@/components/base/FilterChip.vue'
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
    { label: '全部', value: 'all', empty: '暂无活动' },
    { label: '待支付', value: 'pending_pay', empty: '暂无待支付活动' },
    { label: '已报名', value: 'registered', empty: '暂无已报名活动' },
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
    gap: 22rpx;

    &__filter-shell {
        position: sticky;
        top: 0;
        z-index: 5;
    }

    &__filter-scroll {
        width: 100%;
        white-space: nowrap;
    }

    &__filter-track {
        display: flex;
        align-items: center;
        gap: 12rpx;
        min-width: max-content;
        padding: 2rpx;
        box-sizing: border-box;
    }

    &__filter-item {
        flex-shrink: 0;
    }

    &__filter-item :deep(.filter-chip) {
        min-height: 68rpx;
        padding: 0 22rpx;
    }

    &__filter-item :deep(.filter-chip__text) {
        max-width: none;
        font-size: 23rpx;
    }

    &__state-card {
        padding: 26rpx;
    }

    &__state-card :deep(.empty-state-block),
    &__state-card :deep(.loading-state-block) {
        min-height: 260rpx;
    }

    &__stack {
        display: flex;
        flex-direction: column;
        gap: 20rpx;
    }

    &__card {
        padding: 30rpx;
        border-radius: var(--wm-radius-card-lg, 28rpx);
        background: #fffdf8;
        border-color: rgba(216, 201, 173, 0.96);
        box-shadow: 0 14rpx 28rpx rgba(74, 43, 24, 0.08);
    }

    &__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18rpx;
    }

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 8rpx;
    }

    &__eyebrow {
        align-self: flex-start;
        min-height: 36rpx;
        padding: 0 14rpx;
        display: inline-flex;
        align-items: center;
        border-radius: 999rpx;
        background: rgba(241, 229, 200, 0.66);
        border: 1rpx solid rgba(217, 190, 130, 0.58);
        font-size: 20rpx;
        font-weight: 900;
        color: var(--wm-color-clay, #9a6b35);
        box-sizing: border-box;
        white-space: nowrap;
    }

    &__title,
    &__meta,
    &__info-value,
    &__contact {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__title {
        display: block;
        font-size: 32rpx;
        line-height: 1.35;
        font-weight: 900;
        color: var(--wm-text-primary, #191713);
    }

    &__meta {
        display: block;
        font-size: 24rpx;
        color: var(--wm-text-secondary, #665E52);
    }

    &__info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12rpx;
        margin-top: 24rpx;
    }

    &__info-item {
        min-width: 0;
        min-height: 100rpx;
        padding: 16rpx;
        border-radius: 22rpx;
        background: rgba(248, 244, 235, 0.84);
        border: 1rpx solid rgba(216, 201, 173, 0.62);
        box-sizing: border-box;
    }

    &__info-label,
    &__info-value {
        display: block;
    }

    &__info-label {
        margin-bottom: 6rpx;
        font-size: 21rpx;
        color: var(--wm-text-tertiary, #8A806F);
    }

    &__info-value {
        font-size: 24rpx;
        font-weight: 900;
        color: var(--wm-text-primary, #191713);
    }

    &__info-value--price {
        color: var(--wm-color-clay, #9a6b35);
    }

    &__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18rpx;
        margin-top: 24rpx;
        padding-top: 20rpx;
        border-top: 1rpx solid rgba(216, 201, 173, 0.62);
    }

    &__contact-wrap {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 6rpx;
    }

    &__contact-label {
        font-size: 20rpx;
        color: var(--wm-text-tertiary, #8a806f);
    }

    &__contact {
        font-size: 25rpx;
        font-weight: 900;
        color: var(--wm-text-primary, #191713);
    }

    &__footer :deep(.base-button) {
        min-width: 156rpx;
    }

    &__loading-more,
    &__end {
        padding: 10rpx 0 24rpx;
        text-align: center;
        font-size: 22rpx;
        color: var(--wm-text-tertiary, #8A806F);
    }
}

@media screen and (max-width: 320px) {
    .my-activity-list {
        &__card {
            padding: 26rpx;
        }

        &__head {
            flex-direction: column;
        }

        &__footer {
            align-items: stretch;
            flex-direction: column;
        }

        &__footer :deep(.base-button) {
            width: 100%;
        }
    }
}
</style>
