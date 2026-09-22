<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace">
        <BaseNavbar
            title="我的评价"
            variant="solid"
            bg-color="#181614"
            text-color="#FFFDF8"
        />

        <view class="review-page">
            <!-- 顶部高定分段胶囊选择器 -->
            <view class="review-page__top">
                <view class="review-capsule-tabs">
                    <view
                        v-for="tab in tabOptions"
                        :key="tab.value"
                        class="review-capsule-tab"
                        :class="{ 'review-capsule-tab--active': currentTab === tab.value }"
                        @click="switchTab(tab.value)"
                    >
                        <text class="review-capsule-tab__label">{{ tab.label }}</text>
                    </view>
                </view>
            </view>

            <!-- 列表主体 -->
            <view class="review-page__body wm-page-content">
                <!-- 待评价订单列表 -->
                <view v-if="currentTab === 'pending'" class="review-stack">
                    <view v-if="loading && pendingList.length === 0" class="review-page__state">
                        <LoadingState text="正在同步待评价订单..." />
                    </view>

                    <view v-else-if="pendingList.length" class="review-list">
                        <view
                            v-for="item in pendingList"
                            :key="item.id"
                            class="review-card"
                            @click="goReview(item)"
                        >
                            <view class="review-card__head">
                                <view class="review-card__order-box">
                                    <BaseIcon name="order" size="20" color="#C6A15B" />
                                    <text class="review-card__order-no">订单号 {{ getOrderNo(item) }}</text>
                                </view>
                                <StatusBadge tone="pending" size="sm" dot>待评价</StatusBadge>
                            </view>

                            <view class="review-card__main">
                                <view class="review-card__avatar-ring">
                                    <image
                                        :src="getStaffAvatar(item)"
                                        class="review-card__avatar"
                                        mode="aspectFill"
                                    />
                                </view>
                                <view class="review-card__info">
                                    <text class="review-card__staff-name">{{ getStaffName(item) }}</text>
                                    <text class="review-card__pkg-name">{{ getPackageName(item) }}</text>
                                    <view class="review-card__date-chip">
                                        <BaseIcon name="calendar" size="18" color="#C6A15B" />
                                        <text class="review-card__date-text">{{ getServiceDate(item) }}</text>
                                    </view>
                                </view>
                            </view>

                            <view class="review-card__foot">
                                <text class="review-card__hint">
                                    {{ miniProgramReviewMode ? '评价功能维护中' : '婚礼服务已完成，期待您的真实反馈' }}
                                </text>
                                <view class="review-card__actions" @click.stop>
                                    <BaseButton
                                        label="查看订单"
                                        variant="light"
                                        size="sm"
                                        height="62rpx"
                                        font-size="23rpx"
                                        @click="goOrder(item)"
                                    />
                                    <BaseButton
                                        v-if="!miniProgramReviewMode"
                                        label="去评价"
                                        variant="dark"
                                        size="sm"
                                        height="62rpx"
                                        font-size="23rpx"
                                        @click="goReview(item)"
                                    />
                                    <StatusBadge v-else tone="neutral" size="sm">维护中</StatusBadge>
                                </view>
                            </view>
                        </view>
                    </view>

                    <view v-else class="review-page__state">
                        <EmptyState title="暂无待评价订单" icon="order" />
                    </view>
                </view>

                <!-- 已评价记录列表 -->
                <view v-if="currentTab === 'reviewed'" class="review-stack">
                    <view v-if="loading && reviewedList.length === 0" class="review-page__state">
                        <LoadingState text="正在同步评价记录..." />
                    </view>

                    <view v-else-if="reviewedList.length" class="review-list">
                        <view
                            v-for="item in reviewedList"
                            :key="item.id"
                            class="review-card"
                            @click="goDetail(item)"
                        >
                            <view class="review-card__head">
                                <view class="review-card__staff-mini">
                                    <view class="review-card__avatar-ring review-card__avatar-ring--sm">
                                        <image
                                            :src="getStaffAvatar(item)"
                                            class="review-card__avatar"
                                            mode="aspectFill"
                                        />
                                    </view>
                                    <view class="review-card__staff-summary">
                                        <text class="review-card__staff-name">{{ getStaffName(item) }}</text>
                                        <view class="review-card__score-box">
                                            <BaseIcon name="star-fill" size="20" color="#C6A15B" />
                                            <text class="review-card__score-value">{{ getScoreText(item.score) }}</text>
                                        </view>
                                    </view>
                                </view>
                                <StatusBadge
                                    :tone="getStatusTone(item.status)"
                                    size="sm"
                                    dot
                                >
                                    {{ item.status_text || '已评价' }}
                                </StatusBadge>
                            </view>

                            <!-- 评价内容引言框 -->
                            <view class="review-card__quote-box">
                                <text class="review-card__quote-text">{{ getReviewContent(item) }}</text>
                                <text v-if="item.status_summary" class="review-card__quote-status">{{ item.status_summary }}</text>
                            </view>

                            <!-- 评价晒图缩略网格 -->
                            <view v-if="getReviewImages(item).length" class="review-card__gallery">
                                <image
                                    v-for="(img, imgIdx) in getReviewImages(item).slice(0, 3)"
                                    :key="`${img}-${imgIdx}`"
                                    :src="img"
                                    class="review-card__gallery-thumb"
                                    mode="aspectFill"
                                />
                                <view v-if="getReviewImages(item).length > 3" class="review-card__gallery-more">
                                    <text>+{{ getReviewImages(item).length - 3 }}</text>
                                </view>
                            </view>

                            <view class="review-card__foot">
                                <text class="review-card__time">{{ item.create_time_text || '-' }}</text>
                                <view class="review-card__actions" @click.stop>
                                    <BaseButton
                                        label="查看订单"
                                        variant="light"
                                        size="sm"
                                        height="62rpx"
                                        font-size="23rpx"
                                        @click="goOrder(item)"
                                    />
                                    <BaseButton
                                        label="评价详情"
                                        variant="light"
                                        size="sm"
                                        height="62rpx"
                                        font-size="23rpx"
                                        icon="right"
                                        icon-position="right"
                                        @click="goDetail(item)"
                                    />
                                </view>
                            </view>
                        </view>
                    </view>

                    <view v-else class="review-page__state">
                        <EmptyState title="暂无评价记录" icon="order" />
                    </view>
                </view>

                <!-- 加载更多提示 -->
                <view v-if="loading && currentListHasData" class="loading-tip">
                    <tn-loading size="34" mode="flower" color="#C6A15B" />
                    <text class="loading-tip__text">正在加载更多评价...</text>
                </view>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onReachBottom, onShow } from '@dcloudio/uni-app'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
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
    return item?.package_name || item?.order_item?.package_name || item?.orderItem?.package_name || '婚礼定制服务'
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
    return value > 0 ? `${value.toFixed(1)}分` : '未评分'
}

const getReviewContent = (item: any) => {
    return String(item?.content || '').trim() || '未填写文字评价'
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

const switchTab = (tab: ReviewTab) => {
    if (currentTab.value === tab) return
    currentTab.value = tab
    if (tab === 'pending' && pendingList.value.length === 0) {
        loadPendingList(true)
    } else if (tab === 'reviewed' && reviewedList.value.length === 0) {
        loadReviewedList(true)
    }
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
        url: `/packages/pages/order_detail/order_detail?id=${id}`
    })
}

onShow(() => {
    ensureMiniProgramReviewModeConfig()
    if (currentTab.value === 'pending') {
        loadPendingList(true)
    } else {
        loadReviewedList(true)
    }
})

onReachBottom(() => {
    if (currentTab.value === 'pending') {
        loadPendingList()
    } else {
        loadReviewedList()
    }
})
</script>

<style lang="scss" scoped>
.review-page {
    min-height: 100vh;
    background:
        radial-gradient(ellipse at 50% 0%, rgba(217, 190, 130, 0.1) 0%, rgba(248, 246, 240, 0) 65%),
        var(--wm-color-bg-page, #F8F6F0);
    box-sizing: border-box;

    &__top {
        position: sticky;
        top: 0;
        z-index: 20;
        padding: 16rpx var(--wm-space-page-x, 28rpx);
        background: rgba(248, 246, 240, 0.94);
        backdrop-filter: blur(20px);
        border-bottom: 1rpx solid rgba(217, 190, 130, 0.3);
    }

    &__body {
        padding: 24rpx var(--wm-space-page-x, 28rpx) calc(44rpx + env(safe-area-inset-bottom));
        box-sizing: border-box;
    }

    &__state {
        min-height: 50vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
}

/* 顶部胶囊切换器 */
.review-capsule-tabs {
    display: flex;
    align-items: center;
    gap: 12rpx;
    padding: 6rpx;
    border-radius: 999rpx;
    background: rgba(255, 253, 248, 0.9);
    border: 1rpx solid rgba(217, 190, 130, 0.45);
    box-shadow: 0 4rpx 14rpx rgba(24, 22, 20, 0.03);
}

.review-capsule-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 64rpx;
    border-radius: 999rpx;
    transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);

    &__label {
        font-size: 25rpx;
        font-weight: 600;
        color: var(--wm-color-text-secondary, #5E564B);
    }

    &--active {
        background: var(--wm-color-primary, #181614);
        box-shadow: 0 6rpx 16rpx rgba(24, 22, 20, 0.18);

        .review-capsule-tab__label {
            color: var(--wm-text-inverse, #FFFDF8);
            font-weight: 700;
        }
    }
}

/* 评价卡片 */
.review-list {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
}

.review-card {
    padding: 30rpx;
    border-radius: 32rpx;
    background: linear-gradient(180deg, #FFFFFF 0%, #FAF8F5 100%);
    border: 1rpx solid rgba(217, 190, 130, 0.4);
    box-shadow: 0 12rpx 32rpx rgba(24, 22, 20, 0.05);
    display: flex;
    flex-direction: column;
    gap: 20rpx;
    box-sizing: border-box;

    &:active {
        transform: translateY(2rpx);
    }

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__order-box {
        display: inline-flex;
        align-items: center;
        gap: 8rpx;
        padding: 4rpx 14rpx;
        border-radius: 999rpx;
        background: rgba(242, 236, 225, 0.6);
        border: 1rpx solid rgba(217, 190, 130, 0.3);
    }

    &__order-no {
        font-size: 20rpx;
        font-family: monospace;
        color: var(--wm-color-text-secondary, #5E564B);
    }

    &__main {
        display: flex;
        align-items: center;
        gap: 18rpx;
    }

    &__avatar-ring {
        width: 90rpx;
        height: 90rpx;
        border-radius: 50%;
        padding: 3rpx;
        box-sizing: border-box;
        background: linear-gradient(135deg, #D9BE82 0%, #FAF6EE 100%);
        box-shadow: 0 6rpx 14rpx rgba(24, 22, 20, 0.08);
        flex-shrink: 0;

        &--sm {
            width: 72rpx;
            height: 72rpx;
        }
    }

    &__avatar {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: #FFFFFF;
        display: block;
    }

    &__info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 6rpx;
    }

    &__staff-name {
        font-size: 29rpx;
        font-weight: 800;
        color: var(--wm-color-primary, #181614);
    }

    &__pkg-name {
        font-size: 23rpx;
        color: var(--wm-color-text-secondary, #5E564B);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__date-chip {
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
        margin-top: 2rpx;
    }

    &__date-text {
        font-size: 20rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }

    &__staff-mini {
        display: flex;
        align-items: center;
        gap: 14rpx;
    }

    &__staff-summary {
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__score-box {
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
    }

    &__score-value {
        font-size: 21rpx;
        font-weight: 700;
        color: var(--wm-color-gold, #C6A15B);
    }

    /* 评价内容框 */
    &__quote-box {
        padding: 16rpx 20rpx;
        border-radius: 20rpx;
        background: rgba(250, 246, 238, 0.85);
        border: 1rpx solid rgba(231, 224, 211, 0.85);
        display: flex;
        flex-direction: column;
        gap: 6rpx;
    }

    &__quote-text {
        font-size: 25rpx;
        line-height: 1.55;
        color: var(--wm-color-text-primary, #181614);
    }

    &__quote-status {
        font-size: 20rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }

    /* 画报相册小缩略 */
    &__gallery {
        display: flex;
        gap: 12rpx;
    }

    &__gallery-thumb {
        width: 140rpx;
        height: 140rpx;
        border-radius: 16rpx;
        border: 1rpx solid rgba(217, 190, 130, 0.35);
    }

    &__gallery-more {
        width: 140rpx;
        height: 140rpx;
        border-radius: 16rpx;
        background: rgba(24, 22, 20, 0.75);
        display: flex;
        align-items: center;
        justify-content: center;

        text {
            font-size: 28rpx;
            font-weight: 700;
            color: #FFFDF8;
        }
    }

    &__foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
        padding-top: 16rpx;
        border-top: 1rpx solid rgba(231, 224, 211, 0.7);
    }

    &__hint,
    &__time {
        font-size: 21rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__actions {
        display: flex;
        align-items: center;
        gap: 12rpx;
        flex-shrink: 0;
    }
}

.loading-tip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    padding: 30rpx 0;

    &__text {
        font-size: 24rpx;
        color: var(--wm-color-text-secondary, #5E564B);
    }
}
</style>
