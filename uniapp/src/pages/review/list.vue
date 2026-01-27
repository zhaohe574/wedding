<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="我的评价"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="my-reviews-page">
        <!-- 标签页 -->
        <view class="tabs">
            <view
                class="tab-item"
                :class="{ active: currentTab === 'pending' }"
                @click="switchTab('pending')"
            >
                待评价
            </view>
            <view
                class="tab-item"
                :class="{ active: currentTab === 'reviewed' }"
                @click="switchTab('reviewed')"
            >
                已评价
            </view>
        </view>

        <!-- 待评价列表 -->
        <view v-if="currentTab === 'pending'">
            <view v-if="pendingList.length" class="list-wrap">
                <view v-for="item in pendingList" :key="item.id" class="pending-card">
                    <view class="card-header">
                        <text class="order-sn">订单号: {{ item.order?.order_sn }}</text>
                        <text class="service-date">{{ item.order?.service_date }}</text>
                    </view>
                    <view class="card-body">
                        <image
                            :src="item.staff?.avatar || '/static/images/default-avatar.png'"
                            class="staff-avatar"
                            mode="aspectFill"
                        />
                        <view class="staff-info">
                            <view class="staff-name">{{ item.staff_name }}</view>
                            <view class="package-name">{{ item.package_name }}</view>
                        </view>
                        <button class="btn-review" @click="goReview(item)">去评价</button>
                    </view>
                </view>
            </view>
            <view v-else class="empty-tip">
                <image src="/static/images/empty.png" class="empty-icon" mode="aspectFit" />
                <text>暂无待评价订单</text>
            </view>
        </view>

        <!-- 已评价列表 -->
        <view v-if="currentTab === 'reviewed'">
            <view v-if="reviewedList.length" class="list-wrap">
                <view
                    v-for="item in reviewedList"
                    :key="item.id"
                    class="review-card"
                    @click="goDetail(item)"
                >
                    <view class="card-header">
                        <view class="staff-info">
                            <image
                                :src="item.staff?.avatar || '/static/images/default-avatar.png'"
                                class="staff-avatar-small"
                                mode="aspectFill"
                            />
                            <text class="staff-name">{{ item.staff?.name }}</text>
                        </view>
                        <view class="score">
                            <uni-icons type="star-filled" size="14" color="#ff9800"></uni-icons>
                            <text>{{ item.score }}</text>
                        </view>
                    </view>
                    <view class="card-body">
                        <view class="content" v-if="item.content">{{ item.content }}</view>
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
                        <view class="status" :class="getStatusClass(item.status)">
                            {{ item.status_text }}
                        </view>
                    </view>
                </view>
            </view>
            <view v-else class="empty-tip">
                <image src="/static/images/empty.png" class="empty-icon" mode="aspectFit" />
                <text>暂无评价记录</text>
            </view>
        </view>

        <!-- 加载更多 -->
        <view v-if="loading" class="loading-tip">
            <uni-icons type="spinner-cycle" size="20" color="#999"></uni-icons>
            <text>加载中...</text>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { onReachBottom } from '@dcloudio/uni-app'
import { getMyReviews, getPendingOrders } from '@/api/review'

const currentTab = ref('pending')
const loading = ref(false)
const pendingList = ref<any[]>([])
const reviewedList = ref<any[]>([])
const pendingPage = ref(1)
const reviewedPage = ref(1)
const hasMorePending = ref(true)
const hasMoreReviewed = ref(true)

const getStatusClass = (status: number) => {
    const map: Record<number, string> = {
        0: 'pending',
        1: 'approved',
        2: 'rejected'
    }
    return map[status] || ''
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

const goReview = (item: any) => {
    uni.navigateTo({
        url: `/pages/review/publish?order_item_id=${item.id}`
    })
}

const goDetail = (item: any) => {
    uni.navigateTo({
        url: `/pages/review/detail?id=${item.id}`
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
</script>

<style lang="scss" scoped>
.my-reviews-page {
    min-height: 100vh;
    background-color: #f5f5f5;
}

.tabs {
    display: flex;
    background: #fff;
    padding: 0 30rpx;

    .tab-item {
        flex: 1;
        text-align: center;
        padding: 30rpx 0;
        font-size: 28rpx;
        color: #666;
        position: relative;

        &.active {
            color: var(--primary-color, #ff6b35);
            font-weight: bold;

            &::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 60rpx;
                height: 4rpx;
                background: var(--primary-color, #ff6b35);
                border-radius: 2rpx;
            }
        }
    }
}

.list-wrap {
    padding: 20rpx;
}

.pending-card {
    background: #fff;
    border-radius: 16rpx;
    margin-bottom: 20rpx;
    overflow: hidden;

    .card-header {
        display: flex;
        justify-content: space-between;
        padding: 20rpx 24rpx;
        background: #f9f9f9;
        font-size: 24rpx;
        color: #999;
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
            color: #333;
        }

        .package-name {
            font-size: 24rpx;
            color: #999;
            margin-top: 8rpx;
        }
    }

    .btn-review {
        padding: 16rpx 32rpx;
        background: var(--primary-color, #ff6b35);
        color: #fff;
        font-size: 26rpx;
        border-radius: 30rpx;
        border: none;
    }
}

.review-card {
    background: #fff;
    border-radius: 16rpx;
    margin-bottom: 20rpx;
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
        color: #ff9800;
    }

    .content {
        font-size: 28rpx;
        color: #333;
        line-height: 1.6;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
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
        border-top: 1rpx solid #f0f0f0;

        .time {
            font-size: 24rpx;
            color: #999;
        }

        .status {
            font-size: 24rpx;
            padding: 4rpx 16rpx;
            border-radius: 4rpx;

            &.pending {
                background: #fff7e6;
                color: #ff9800;
            }

            &.approved {
                background: #e8f5e9;
                color: #4caf50;
            }

            &.rejected {
                background: #ffebee;
                color: #f44336;
            }
        }
    }
}

.empty-tip {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 120rpx 0;
    color: #999;

    .empty-icon {
        width: 200rpx;
        height: 200rpx;
        margin-bottom: 20rpx;
    }
}

.loading-tip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    padding: 30rpx;
    color: #999;
    font-size: 26rpx;
}
</style>
