<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace">
        <BaseNavbar
            title="我的评价"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <view class="review-list-page wm-page-content">
            <BaseCard
                class="review-tabs-card"
                variant="panel"
                padding="10rpx"
                border-radius="34rpx"
            >
                <BaseSegmentedControl
                    :model-value="currentTab"
                    :options="tabOptions"
                    @change="switchTab"
                />
            </BaseCard>

            <view v-if="currentTab === 'pending'" class="review-list-section">
                <LoadingState v-if="loading && pendingList.length === 0" text="评价订单加载中..." />

                <view v-else-if="pendingList.length" class="review-list">
                    <BaseCard
                        v-for="item in pendingList"
                        :key="item.id"
                        class="pending-review-card"
                        variant="list"
                        padding="24rpx"
                        border-radius="32rpx"
                        border="1rpx solid rgba(216, 201, 173, 0.9)"
                        box-shadow="0 16rpx 36rpx rgba(74, 43, 24, 0.07)"
                    >
                        <view class="review-card__top">
                            <view class="review-card__order">
                                <BaseIcon name="order" size="24" color="#B8954A" />
                                <text class="review-card__order-text">
                                    订单号 {{ getOrderNo(item) }}
                                </text>
                            </view>
                            <StatusBadge tone="pending" size="sm" dot>待评价</StatusBadge>
                        </view>

                        <view class="review-card__main">
                            <image
                                :src="getStaffAvatar(item)"
                                class="review-card__avatar"
                                mode="aspectFill"
                            />
                            <view class="review-card__copy">
                                <text class="review-card__title">{{ getStaffName(item) }}</text>
                                <text class="review-card__meta">{{ getPackageName(item) }}</text>
                                <view class="review-card__date">
                                    <BaseIcon name="calendar" size="22" color="#9A9388" />
                                    <text class="review-card__date-text">
                                        {{ getServiceDate(item) }}
                                    </text>
                                </view>
                            </view>
                        </view>

                        <view class="review-card__footer">
                            <text class="review-card__note">
                                {{
                                    miniProgramReviewMode
                                        ? '评价功能维护中'
                                        : '服务已完成，可评价'
                                }}
                            </text>
                            <view class="review-card__actions">
                                <BaseButton
                                    label="查看订单"
                                    variant="light"
                                    size="sm"
                                    height="64rpx"
                                    font-size="23rpx"
                                    @click.stop="goOrder(item)"
                                />
                                <BaseButton
                                    v-if="!miniProgramReviewMode"
                                    label="去评价"
                                    variant="dark"
                                    size="sm"
                                    height="64rpx"
                                    font-size="23rpx"
                                    @click.stop="goReview(item)"
                                />
                                <StatusBadge v-else tone="neutral" size="sm">维护中</StatusBadge>
                            </view>
                        </view>
                    </BaseCard>
                </view>

                <EmptyState v-else title="暂无待评价订单" />
            </view>

            <view v-if="currentTab === 'reviewed'" class="review-list-section">
                <LoadingState v-if="loading && reviewedList.length === 0" text="评价记录加载中..." />

                <view v-else-if="reviewedList.length" class="review-list">
                    <BaseCard
                        v-for="item in reviewedList"
                        :key="item.id"
                        class="reviewed-card"
                        variant="list"
                        padding="24rpx"
                        border-radius="32rpx"
                        border="1rpx solid rgba(216, 201, 173, 0.9)"
                        box-shadow="0 16rpx 36rpx rgba(74, 43, 24, 0.07)"
                    >
                        <view class="review-card__top">
                            <view class="review-card__reviewer">
                                <image
                                    :src="getStaffAvatar(item)"
                                    class="review-card__avatar review-card__avatar--small"
                                    mode="aspectFill"
                                />
                                <view class="review-card__reviewer-copy">
                                    <text class="review-card__title">{{ getStaffName(item) }}</text>
                                    <view class="review-card__score">
                                        <BaseIcon name="star-fill" size="24" color="#B8954A" />
                                        <text class="review-card__score-text">
                                            {{ getScoreText(item.score) }}
                                        </text>
                                    </view>
                                </view>
                            </view>
                            <StatusBadge
                                :tone="getStatusTone(item.status)"
                                size="sm"
                                dot
                            >
                                {{ item.status_text || '审核状态' }}
                            </StatusBadge>
                        </view>

                        <view class="review-card__content">
                            <text class="review-card__content-text">
                                {{ getReviewContent(item) }}
                            </text>
                            <text v-if="item.status_summary" class="review-card__summary">
                                {{ item.status_summary }}
                            </text>
                        </view>

                        <view v-if="getReviewImages(item).length" class="review-card__images">
                            <image
                                v-for="(img, index) in getReviewImages(item).slice(0, 3)"
                                :key="`${img}-${index}`"
                                :src="img"
                                class="review-card__image"
                                mode="aspectFill"
                            />
                            <view v-if="getReviewImages(item).length > 3" class="review-card__more">
                                +{{ getReviewImages(item).length - 3 }}
                            </view>
                        </view>

                        <view class="review-card__footer">
                            <text class="review-card__time">{{ item.create_time_text || '-' }}</text>
                            <view class="review-card__actions">
                                <BaseButton
                                    label="查看订单"
                                    variant="light"
                                    size="sm"
                                    height="64rpx"
                                    font-size="23rpx"
                                    @click.stop="goOrder(item)"
                                />
                                <BaseButton
                                    label="查看详情"
                                    variant="light"
                                    size="sm"
                                    icon="right"
                                    icon-position="right"
                                    height="64rpx"
                                    font-size="23rpx"
                                    @click.stop="goDetail(item)"
                                />
                            </view>
                        </view>
                    </BaseCard>
                </view>

                <EmptyState v-else title="暂无评价记录" />
            </view>

            <view v-if="loading && currentListHasData" class="loading-tip">
                <tn-loading size="34" mode="flower" color="#B8954A" />
                <text>加载中...</text>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { onReachBottom, onShow } from '@dcloudio/uni-app'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import BaseSegmentedControl from '@/components/base/BaseSegmentedControl.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { getMyReviews, getPendingOrders } from '@/packages/common/api/review'
import { useThemeStore } from '@/stores/theme'
import { showError } from '@/utils/feedback'
import {
    ensureMiniProgramReviewModeConfig,
    isMiniProgramReviewMode,
    showMiniProgramReviewModeTip
} from '@/utils/miniProgramReviewMode'

type ReviewTab = 'pending' | 'reviewed'

const $theme = useThemeStore()
const tabOptions: Array<{ label: string; value: ReviewTab }> = [
    { label: '待评价', value: 'pending' },
    { label: '已评价', value: 'reviewed' }
]

const currentTab = ref<ReviewTab>('pending')
const loading = ref(false)
const pendingList = ref<any[]>([])
const reviewedList = ref<any[]>([])
const pendingPage = ref(1)
const reviewedPage = ref(1)
const hasMorePending = ref(true)
const hasMoreReviewed = ref(true)
const hasInitialized = ref(false)
const miniProgramReviewMode = computed(() => isMiniProgramReviewMode())
const currentListHasData = computed(() =>
    currentTab.value === 'pending' ? pendingList.value.length > 0 : reviewedList.value.length > 0
)

const defaultAvatar = '/static/images/user/default_avatar.png'

const getStaffAvatar = (item: any) => {
    return item?.staff?.avatar || item?.staff_avatar || defaultAvatar
}

const getStaffName = (item: any) => {
    return item?.staff_name || item?.staff?.name || '服务人员'
}

const getPackageName = (item: any) => {
    return item?.package_name || item?.order_item?.package_name || item?.orderItem?.package_name || '服务项目'
}

const getOrderNo = (item: any) => {
    return item?.order?.order_sn || item?.order_sn || '--'
}

const getOrderId = (item: any) => {
    return Number(
        item?.order_id ||
            item?.order?.id ||
            item?.orderItem?.order_id ||
            item?.order_item?.order_id ||
            0
    )
}

const getServiceDate = (item: any) => {
    return item?.order?.service_date || item?.service_date || '服务日期待确认'
}

const getScoreText = (score: number | string) => {
    const value = Number(score || 0)
    return value > 0 ? `${value}分` : '未评分'
}

const getReviewContent = (item: any) => {
    return String(item?.content || '').trim() || '未填写评价内容'
}

const getReviewImages = (item: any) => {
    return Array.isArray(item?.images) ? item.images.filter(Boolean) : []
}

const getStatusTone = (
    status: number
): 'neutral' | 'success' | 'warning' | 'danger' | 'pending' => {
    const map: Record<number, 'neutral' | 'success' | 'warning' | 'danger' | 'pending'> = {
        0: 'pending',
        1: 'success',
        2: 'danger'
    }
    return map[Number(status)] || 'neutral'
}

const loadPendingList = async (refresh = false) => {
    if (loading.value || (!refresh && !hasMorePending.value)) return

    if (refresh) {
        pendingPage.value = 1
        hasMorePending.value = true
    }

    loading.value = true
    try {
        const res = await getPendingOrders({
            page: pendingPage.value,
            limit: 10
        })

        if (refresh) {
            pendingList.value = res.lists || []
        } else {
            pendingList.value.push(...(res.lists || []))
        }

        hasMorePending.value = res.has_more
        pendingPage.value++
    } finally {
        loading.value = false
    }
}

const loadReviewedList = async (refresh = false) => {
    if (loading.value || (!refresh && !hasMoreReviewed.value)) return

    if (refresh) {
        reviewedPage.value = 1
        hasMoreReviewed.value = true
    }

    loading.value = true
    try {
        const res = await getMyReviews({
            page: reviewedPage.value,
            limit: 10
        })

        if (refresh) {
            reviewedList.value = res.lists || []
        } else {
            reviewedList.value.push(...(res.lists || []))
        }

        hasMoreReviewed.value = res.has_more
        reviewedPage.value++
    } finally {
        loading.value = false
    }
}

const switchTab = (tab: string | number) => {
    const nextTab: ReviewTab = tab === 'reviewed' ? 'reviewed' : 'pending'
    currentTab.value = nextTab
    if (nextTab === 'pending' && pendingList.value.length === 0) {
        loadPendingList(true)
    } else if (nextTab === 'reviewed' && reviewedList.value.length === 0) {
        loadReviewedList(true)
    }
}

const refreshCurrentTab = () => {
    if (currentTab.value === 'pending') {
        loadPendingList(true)
        return
    }

    loadReviewedList(true)
}

const goReview = (item: any) => {
    if (miniProgramReviewMode.value) {
        showMiniProgramReviewModeTip('评价功能维护中，暂时无法发表评价')
        return
    }

    uni.navigateTo({
        url: `/packages/pages/review/publish?order_item_id=${item.id}`
    })
}

const goDetail = (item: any) => {
    uni.navigateTo({
        url: `/packages/pages/review/detail?id=${item.id}`
    })
}

const goOrder = (item: any) => {
    const id = getOrderId(item)

    if (id <= 0) {
        showError('订单信息暂不可用')
        return
    }

    uni.navigateTo({
        url: `/pages/order_detail/order_detail?id=${id}`
    })
}

onReachBottom(() => {
    if (currentTab.value === 'pending') {
        loadPendingList()
    } else {
        loadReviewedList()
    }
})

onMounted(() => {
    ensureMiniProgramReviewModeConfig()
    loadPendingList(true)
})

onShow(() => {
    if (!hasInitialized.value) {
        hasInitialized.value = true
        return
    }
    refreshCurrentTab()
})
</script>

<style lang="scss" scoped>
.review-list-page {
    display: flex;
    flex-direction: column;
    gap: 22rpx;
    background: transparent;
    padding-top: 20rpx;
    padding-bottom: calc(40rpx + env(safe-area-inset-bottom));
}

.review-tabs-card {
    display: block;
    box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));
}

.review-list-section {
    min-height: 56vh;
}

.review-list {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.pending-review-card,
.reviewed-card {
    display: block;
}

.review-card__top,
.review-card__main,
.review-card__footer,
.review-card__reviewer,
.review-card__order,
.review-card__score,
.review-card__date {
    display: flex;
    align-items: center;
}

.review-card__top {
    position: relative;
    z-index: 1;
    justify-content: space-between;
    gap: 18rpx;
}

.review-card__order {
    min-width: 0;
    flex: 1;
    gap: 8rpx;
}

.review-card__order-text {
    min-width: 0;
    flex: 1;
    font-size: 22rpx;
    line-height: 1.4;
    color: var(--wm-text-tertiary, #9a9388);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.review-card__main {
    position: relative;
    z-index: 1;
    gap: 18rpx;
    margin-top: 22rpx;
}

.review-card__avatar {
    width: 96rpx;
    height: 96rpx;
    flex-shrink: 0;
    border-radius: 999rpx;
    background: var(--wm-color-bg-soft, #faf6ee);
    border: 4rpx solid rgba(255, 253, 248, 0.96);
    box-shadow: 0 10rpx 22rpx rgba(74, 43, 24, 0.1);
}

.review-card__avatar--small {
    width: 72rpx;
    height: 72rpx;
    border-width: 3rpx;
}

.review-card__copy,
.review-card__reviewer-copy {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.review-card__reviewer {
    min-width: 0;
    flex: 1;
    gap: 14rpx;
}

.review-card__title {
    max-width: 100%;
    font-size: 30rpx;
    font-weight: 900;
    line-height: 1.32;
    color: var(--wm-text-primary, #191713);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.review-card__meta {
    max-width: 100%;
    font-size: 24rpx;
    line-height: 1.35;
    color: var(--wm-text-secondary, #5f5a50);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.review-card__date {
    gap: 8rpx;
}

.review-card__date-text,
.review-card__score-text,
.review-card__note,
.review-card__time {
    font-size: 22rpx;
    line-height: 1.4;
    color: var(--wm-text-tertiary, #9a9388);
}

.review-card__score {
    gap: 6rpx;
}

.review-card__score-text {
    font-weight: 800;
    color: var(--wm-color-gold, #b8954a);
}

.review-card__content {
    position: relative;
    z-index: 1;
    margin-top: 22rpx;
    padding: 20rpx 22rpx;
    border-radius: 24rpx;
    background: rgba(248, 242, 228, 0.58);
    border: 1rpx solid rgba(216, 201, 173, 0.68);
}

.review-card__content-text {
    display: -webkit-box;
    font-size: 26rpx;
    line-height: 1.6;
    color: var(--wm-text-primary, #191713);
    overflow: hidden;
    text-overflow: ellipsis;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
}

.review-card__summary {
    display: block;
    margin-top: 12rpx;
    font-size: 22rpx;
    line-height: 1.5;
    color: var(--wm-text-secondary, #5f5a50);
}

.review-card__images {
    position: relative;
    z-index: 1;
    display: flex;
    gap: 12rpx;
    margin-top: 18rpx;
}

.review-card__image,
.review-card__more {
    width: 148rpx;
    height: 148rpx;
    flex-shrink: 0;
    border-radius: 22rpx;
    overflow: hidden;
    background: var(--wm-color-bg-soft, #faf6ee);
}

.review-card__more {
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--wm-text-inverse, #fffdf8);
    font-size: 26rpx;
    font-weight: 900;
    background: rgba(25, 23, 19, 0.62);
}

.review-card__footer {
    position: relative;
    z-index: 1;
    justify-content: space-between;
    gap: 18rpx;
    margin-top: 22rpx;
    padding-top: 22rpx;
    border-top: 1rpx solid rgba(216, 201, 173, 0.64);
}

.review-card__actions {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12rpx;
    flex-wrap: wrap;
}

.review-card__note,
.review-card__time {
    min-width: 0;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.loading-tip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
    padding: 28rpx 0 10rpx;
    color: var(--wm-text-tertiary, #9a9388);
    font-size: 24rpx;
}

@media screen and (max-width: 360px) {
    .review-card__top,
    .review-card__footer {
        align-items: flex-start;
        flex-direction: column;
    }

    .review-card__order,
    .review-card__reviewer,
    .review-card__note,
    .review-card__time,
    .review-card__actions {
        width: 100%;
        flex: none;
    }

    .review-card__actions {
        justify-content: flex-end;
    }

    .review-card__images {
        gap: 10rpx;
    }

    .review-card__image,
    .review-card__more {
        width: 132rpx;
        height: 132rpx;
    }
}
</style>
