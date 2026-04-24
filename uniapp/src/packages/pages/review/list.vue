<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="我的评价" />
        <view class="my-reviews-page wm-page-content">
            <!-- 标签页 -->
            <view class="tabs wm-pill-tabs wm-panel-card">
                <view
                    class="tab-item wm-pill-tab"
                    :class="{ active: currentTab === 'pending' }"
                    :style="currentTab === 'pending' ? $theme.activeTab.value : {}"
                    @click="switchTab('pending')"
                >
                    待评价
                    <view
                        v-if="currentTab === 'pending'"
                        class="tab-indicator"
                        :style="$theme.tabIndicator.value"
                    ></view>
                </view>
                <view
                    class="tab-item wm-pill-tab"
                    :class="{ active: currentTab === 'reviewed' }"
                    :style="currentTab === 'reviewed' ? $theme.activeTab.value : {}"
                    @click="switchTab('reviewed')"
                >
                    已评价
                    <view
                        v-if="currentTab === 'reviewed'"
                        class="tab-indicator"
                        :style="$theme.tabIndicator.value"
                    ></view>
                </view>
            </view>

            <!-- 待评价列表 -->
            <view v-if="currentTab === 'pending'">
                <view v-if="pendingList.length" class="list-wrap">
                    <view
                        v-for="item in pendingList"
                        :key="item.id"
                        class="pending-card wm-panel-card"
                    >
                        <view class="card-header">
                            <text class="order-sn">订单号: {{ item.order?.order_sn }}</text>
                            <text class="service-date">{{ item.order?.service_date }}</text>
                        </view>
                        <view class="card-body">
                            <image
                                :src="
                                    item.staff?.avatar || '/static/images/user/default_avatar.png'
                                "
                                class="staff-avatar"
                                mode="aspectFill"
                            />
                            <view class="staff-info">
                                <view class="staff-name">{{ item.staff_name }}</view>
                                <view class="package-name">{{ item.package_name }}</view>
                                <view class="pending-note"> 服务已完成，可去评价 </view>
                            </view>
                            <button
                                class="btn-review"
                                :style="$theme.btnReview.value"
                                @click="goReview(item)"
                            >
                                去评价
                            </button>
                        </view>
                    </view>
                </view>
                <EmptyState v-else title="暂无待评价订单" description="待评价订单会显示在这里。" />
            </view>

            <!-- 已评价列表 -->
            <view v-if="currentTab === 'reviewed'">
                <view v-if="reviewedList.length" class="list-wrap">
                    <view
                        v-for="item in reviewedList"
                        :key="item.id"
                        class="review-card wm-panel-card"
                        @click="goDetail(item)"
                    >
                        <view class="card-header">
                            <view class="staff-info">
                                <image
                                    :src="
                                        item.staff?.avatar ||
                                        '/static/images/user/default_avatar.png'
                                    "
                                    class="staff-avatar-small"
                                    mode="aspectFill"
                                />
                                <text class="staff-name">{{ item.staff?.name }}</text>
                            </view>
                            <view class="score">
                                <tn-icon name="star-fill" size="28rpx" color="#9f7a2e"></tn-icon>
                                <text>{{ item.score }}</text>
                            </view>
                        </view>
                        <view class="card-body">
                            <view class="content" v-if="item.content">{{ item.content }}</view>
                            <view class="review-summary">{{ item.status_summary }}</view>
                            <view class="review-reward">{{ item.reward_summary }}</view>
                            <view class="images" v-if="item.images?.length">
                                <image
                                    v-for="(img, index) in item.images.slice(0, 3)"
                                    :key="index"
                                    :src="img"
                                    class="review-image"
                                    mode="aspectFill"
                                />
                                <view v-if="item.images.length > 3" class="more-count">
                                    +{{ item.images.length - 3 }}
                                </view>
                            </view>
                        </view>
                        <view class="card-footer">
                            <view class="time">{{ item.create_time_text }}</view>
                            <view class="footer-status-group">
                                <view class="reward-tag" :class="getRewardClass(item)">
                                    {{ item.reward_status_text || '待审核' }}
                                </view>
                                <view class="status" :class="getStatusClass(item.status)">
                                    {{ item.status_text }}
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
                <EmptyState v-else title="暂无评价记录" description="评价记录会显示在这里。" />
            </view>

            <!-- 加载更多 -->
            <view v-if="loading" class="loading-tip">
                <tn-icon name="loading" size="36rpx" color="#9A9388"></tn-icon>
                <text>加载中...</text>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { CSSProperties } from 'vue'
import { onReachBottom, onShow } from '@dcloudio/uni-app'
import EmptyState from '@/components/base/EmptyState.vue'
import PageShell from '@/components/base/PageShell.vue'
import { getMyReviews, getPendingOrders } from '@/packages/common/api/review'
import { useThemeStore } from '@/stores/theme'

const themeStore = useThemeStore()
const $theme = {
    pageStyle: computed(() => themeStore.pageStyle),
    navColor: computed(() => themeStore.navColor),
    navBgColor: computed(() => themeStore.navBgColor),
    primaryColor: computed(() => themeStore.primaryColor || '#0B0B0B'),
    activeTab: computed<CSSProperties>(() => ({
        color: themeStore.primaryColor || '#0B0B0B',
        fontWeight: 700
    })),
    tabIndicator: computed(() => ({
        background: themeStore.primaryColor || '#0B0B0B'
    })),
    btnReview: computed(() => ({
        background: themeStore.primaryColor || '#0B0B0B'
    }))
}

const currentTab = ref('pending')
const loading = ref(false)
const pendingList = ref<any[]>([])
const reviewedList = ref<any[]>([])
const pendingPage = ref(1)
const reviewedPage = ref(1)
const hasMorePending = ref(true)
const hasMoreReviewed = ref(true)
const hasInitialized = ref(false)

const getStatusClass = (status: number) => {
    const map: Record<number, string> = {
        0: 'pending',
        1: 'approved',
        2: 'rejected'
    }
    return map[status] || ''
}

const getRewardClass = (item: any) => {
    if (item?.reward_status_text === '已发放') {
        return 'granted'
    }
    if (item?.reward_status_text === '不发放') {
        return 'rejected'
    }
    if (item?.reward_status_text === '无需发放') {
        return 'plain'
    }
    return 'pending'
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

const switchTab = (tab: string) => {
    currentTab.value = tab
    if (tab === 'pending' && pendingList.value.length === 0) {
        loadPendingList(true)
    } else if (tab === 'reviewed' && reviewedList.value.length === 0) {
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
    uni.navigateTo({
        url: `/packages/pages/review/publish?order_item_id=${item.id}`
    })
}

const goDetail = (item: any) => {
    uni.navigateTo({
        url: `/packages/pages/review/detail?id=${item.id}`
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
.my-reviews-page {
    background-color: transparent;
}

.tabs {
    display: flex;
    padding: 12rpx;
    margin-bottom: 20rpx;

    .tab-item {
        flex: 1;
        text-align: center;
        min-height: 74rpx;
        padding: 0 20rpx;
        font-size: 28rpx;
        color: var(--wm-text-secondary, #5f5a50);
        position: relative;
        justify-content: center;

        &.active {
            font-weight: bold;
        }

        .tab-indicator {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60rpx;
            height: 4rpx;
            border-radius: 2rpx;
        }
    }
}

.list-wrap {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.pending-card {
    overflow: hidden;

    .card-header {
        display: flex;
        justify-content: space-between;
        padding: 20rpx 24rpx;
        background: rgba(248, 247, 242, 0.72);
        font-size: 24rpx;
        color: var(--wm-text-tertiary, #9a9388);
    }

    .card-body {
        display: flex;
        align-items: center;
        padding: 24rpx;
    }

    .staff-avatar {
        width: 100rpx;
        height: 100rpx;
        border-radius: 50%;
        margin-right: 20rpx;
    }

    .staff-info {
        flex: 1;

        .staff-name {
            font-size: 30rpx;
            font-weight: bold;
            color: var(--wm-text-primary, #111111);
        }

        .package-name {
            font-size: 24rpx;
            color: var(--wm-text-secondary, #5f5a50);
            margin-top: 8rpx;
        }

        .pending-note {
            margin-top: 10rpx;
            font-size: 22rpx;
            line-height: 1.6;
            color: #5f5a50;
        }
    }

    .btn-review {
        padding: 16rpx 32rpx;
        color: #fff;
        font-size: 26rpx;
        border-radius: 30rpx;
        border: none;
    }
}

.review-card {
    padding: 24rpx;

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16rpx;
    }

    .staff-info {
        display: flex;
        align-items: center;
        gap: 12rpx;
    }

    .staff-avatar-small {
        width: 48rpx;
        height: 48rpx;
        border-radius: 50%;
    }

    .staff-name {
        font-size: 28rpx;
        font-weight: 500;
    }

    .score {
        display: flex;
        align-items: center;
        gap: 4rpx;
        font-size: 26rpx;
        color: #9f7a2e;
    }

    .content {
        font-size: 28rpx;
        color: var(--wm-text-primary, #111111);
        line-height: 1.6;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .review-summary,
    .review-reward {
        margin-top: 12rpx;
        font-size: 24rpx;
        line-height: 1.7;
        color: #5f5a50;
    }

    .images {
        display: flex;
        gap: 12rpx;
        margin-top: 16rpx;

        .review-image {
            width: 160rpx;
            height: 160rpx;
            border-radius: 8rpx;
        }

        .more-count {
            width: 160rpx;
            height: 160rpx;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 8rpx;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 28rpx;
        }
    }

    .card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 16rpx;
        padding-top: 16rpx;
        border-top: 1rpx solid #F8F7F2;

        .footer-status-group {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12rpx;
            flex-wrap: wrap;
        }

        .time {
            font-size: 24rpx;
            color: var(--wm-text-tertiary, #9a9388);
        }

        .reward-tag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40rpx;
            padding: 0 14rpx;
            border-radius: 999rpx;
            font-size: 22rpx;
            font-weight: 600;

            &.pending {
                color: #9F7A2E;
                background: rgba(159, 122, 46, 0.12);
            }

            &.granted {
                color: #4D4A42;
                background: rgba(77, 74, 66, 0.12);
            }

            &.rejected {
                color: #5A4433;
                background: rgba(90, 68, 51, 0.12);
            }

            &.plain {
                color: #6c665c;
                background: rgba(154, 147, 136, 0.14);
            }
        }

        .status {
            font-size: 24rpx;
            padding: 4rpx 16rpx;
            border-radius: 4rpx;

            &.pending {
                background: #F7F0DF;
                color: #9f7a2e;
            }

            &.approved {
                background: #F3F2EE;
                color: #4D4A42;
            }

            &.rejected {
                background: #F3F2EE;
                color: #5A4433;
            }
        }
    }
}

.loading-tip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    padding: 30rpx;
    color: var(--wm-text-tertiary, #9a9388);
    font-size: 26rpx;
}
</style>
