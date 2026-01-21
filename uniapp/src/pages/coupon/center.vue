<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="领券中心"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="coupon-center-page">
        <!-- 顶部入口 -->
        <view class="top-entry">
            <view class="entry-item" @click="goMyCoupons">
                <uni-icons type="wallet" size="24" color="#ff6b35"></uni-icons>
                <text>我的优惠券</text>
                <text class="count" v-if="stats.unused > 0">{{ stats.unused }}张可用</text>
            </view>
        </view>

        <!-- 优惠券列表 -->
        <view class="section-title">可领取优惠券</view>
        
        <view v-if="couponList.length" class="list-wrap">
            <view 
                v-for="item in couponList" 
                :key="item.id"
                class="coupon-card"
            >
                <view class="coupon-left">
                    <view class="coupon-value">
                        <template v-if="item.coupon_type === 2">
                            <text class="num">{{ (item.discount_amount / 10).toFixed(1) }}</text>
                            <text class="unit">折</text>
                        </template>
                        <template v-else>
                            <text class="symbol">¥</text>
                            <text class="num">{{ item.discount_amount }}</text>
                        </template>
                    </view>
                    <view class="coupon-threshold">
                        {{ item.threshold_amount > 0 ? `满${item.threshold_amount}元可用` : '无门槛' }}
                    </view>
                </view>
                <view class="coupon-right">
                    <view class="coupon-name">{{ item.name }}</view>
                    <view class="coupon-desc">{{ item.discount_desc }}</view>
                    <view class="coupon-time">{{ item.valid_period }}</view>
                    <view class="coupon-remain" v-if="item.remain_count !== -1">
                        剩余 {{ item.remain_count }} 张
                    </view>
                </view>
                <view class="coupon-action">
                    <button 
                        v-if="item.is_received && !item.can_receive"
                        class="btn-received"
                        disabled
                    >
                        已领取
                    </button>
                    <button 
                        v-else-if="item.remain_count === 0"
                        class="btn-empty"
                        disabled
                    >
                        已领完
                    </button>
                    <button 
                        v-else
                        class="btn-receive"
                        :loading="receivingId === item.id"
                        @click="handleReceive(item)"
                    >
                        立即领取
                    </button>
                </view>
            </view>
        </view>

        <!-- 空状态 -->
        <view v-else-if="!loading" class="empty-tip">
            <image src="/static/images/empty.png" class="empty-icon" mode="aspectFit" />
            <text>暂无可领取的优惠券</text>
        </view>

        <!-- 加载状态 -->
        <view v-if="loading" class="loading-tip">
            <uni-icons type="spinner-cycle" size="20" color="#999"></uni-icons>
            <text>加载中...</text>
        </view>

        <!-- 底部安全区 -->
        <view class="safe-bottom"></view>
    </view>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { onReachBottom, onPullDownRefresh } from '@dcloudio/uni-app'
import { getAvailableCoupons, receiveCoupon, getMyCouponStats } from '@/api/coupon'

const loading = ref(false)
const couponList = ref<any[]>([])
const stats = ref<any>({})
const page = ref(1)
const hasMore = ref(true)
const receivingId = ref<number | null>(null)

const loadStats = async () => {
    try {
        const res = await getMyCouponStats()
        stats.value = res || {}
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
        const res = await getAvailableCoupons({
            page: page.value,
            limit: 10
        })

        const list = res.lists || []
        if (refresh) {
            couponList.value = list
        } else {
            couponList.value = [...couponList.value, ...list]
        }

        hasMore.value = res.has_more
        if (hasMore.value) {
            page.value++
        }
    } catch (error) {
        console.error(error)
    } finally {
        loading.value = false
        uni.stopPullDownRefresh()
    }
}

const handleReceive = async (item: any) => {
    if (receivingId.value) return

    receivingId.value = item.id
    try {
        await receiveCoupon({ coupon_id: item.id })
        uni.showToast({
            title: '领取成功',
            icon: 'success'
        })
        
        // 更新状态
        item.is_received = true
        item.can_receive = item.per_limit === 0 || false
        if (item.remain_count > 0) {
            item.remain_count--
        }

        // 刷新统计
        loadStats()
    } catch (error: any) {
        uni.showToast({
            title: error.msg || '领取失败',
            icon: 'none'
        })
    } finally {
        receivingId.value = null
    }
}

const goMyCoupons = () => {
    uni.navigateTo({
        url: '/pages/coupon/list'
    })
}

onReachBottom(() => {
    loadList()
})

onPullDownRefresh(() => {
    loadStats()
    loadList(true)
})

onMounted(() => {
    loadStats()
    loadList(true)
})
</script>

<style scoped lang="scss">
.coupon-center-page {
    min-height: 100vh;
    background: #f5f5f5;
}

.top-entry {
    background: #fff;
    padding: 24rpx 32rpx;
    margin-bottom: 20rpx;

    .entry-item {
        display: flex;
        align-items: center;
        padding: 20rpx;
        background: #fff8f5;
        border-radius: 12rpx;

        text {
            margin-left: 16rpx;
            font-size: 28rpx;
            color: #333;
        }

        .count {
            margin-left: auto;
            font-size: 24rpx;
            color: #ff6b35;
        }
    }
}

.section-title {
    padding: 24rpx 32rpx;
    font-size: 32rpx;
    font-weight: bold;
    color: #333;
}

.list-wrap {
    padding: 0 24rpx;
}

.coupon-card {
    display: flex;
    background: #fff;
    border-radius: 16rpx;
    margin-bottom: 20rpx;
    overflow: hidden;
    position: relative;

    .coupon-left {
        width: 200rpx;
        background: linear-gradient(135deg, #ff6b35 0%, #ff9a5a 100%);
        padding: 30rpx 20rpx;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #fff;

        .coupon-value {
            display: flex;
            align-items: baseline;

            .symbol {
                font-size: 28rpx;
            }

            .num {
                font-size: 56rpx;
                font-weight: bold;
            }

            .unit {
                font-size: 28rpx;
                margin-left: 4rpx;
            }
        }

        .coupon-threshold {
            font-size: 22rpx;
            margin-top: 8rpx;
            opacity: 0.9;
        }
    }

    .coupon-right {
        flex: 1;
        padding: 24rpx;
        display: flex;
        flex-direction: column;
        justify-content: center;

        .coupon-name {
            font-size: 30rpx;
            font-weight: bold;
            color: #333;
            margin-bottom: 8rpx;
        }

        .coupon-desc {
            font-size: 24rpx;
            color: #ff6b35;
            margin-bottom: 8rpx;
        }

        .coupon-time {
            font-size: 22rpx;
            color: #999;
            margin-bottom: 4rpx;
        }

        .coupon-remain {
            font-size: 22rpx;
            color: #ff4d4f;
        }
    }

    .coupon-action {
        position: absolute;
        right: 24rpx;
        top: 50%;
        transform: translateY(-50%);

        button {
            width: 140rpx;
            height: 56rpx;
            line-height: 56rpx;
            font-size: 24rpx;
            border-radius: 28rpx;
            padding: 0;
        }

        .btn-receive {
            color: #fff;
            background: linear-gradient(135deg, #ff6b35 0%, #ff9a5a 100%);
            border: none;
        }

        .btn-received,
        .btn-empty {
            color: #999;
            background: #f5f5f5;
            border: none;
        }
    }
}

.empty-tip {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 120rpx 0;

    .empty-icon {
        width: 240rpx;
        height: 240rpx;
        margin-bottom: 24rpx;
    }

    text {
        font-size: 28rpx;
        color: #999;
    }
}

.loading-tip {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32rpx;
    color: #999;
    font-size: 26rpx;

    text {
        margin-left: 12rpx;
    }
}

.safe-bottom {
    height: constant(safe-area-inset-bottom);
    height: env(safe-area-inset-bottom);
}
</style>
