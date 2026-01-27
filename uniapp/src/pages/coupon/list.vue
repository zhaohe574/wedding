<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="我的优惠券"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="coupon-page">
        <!-- 统计数字 -->
        <view class="stats-bar">
            <view class="stat-item" @click="switchTab('')">
                <view class="stat-num">{{ stats.total || 0 }}</view>
                <view class="stat-label">全部</view>
            </view>
            <view class="stat-item" @click="switchTab('0')">
                <view class="stat-num highlight">{{ stats.unused || 0 }}</view>
                <view class="stat-label">可使用</view>
            </view>
            <view class="stat-item" @click="switchTab('1')">
                <view class="stat-num">{{ stats.used || 0 }}</view>
                <view class="stat-label">已使用</view>
            </view>
            <view class="stat-item" @click="switchTab('2')">
                <view class="stat-num">{{ stats.expired || 0 }}</view>
                <view class="stat-label">已过期</view>
            </view>
        </view>

        <!-- 标签页 -->
        <view class="tabs">
            <view class="tab-item" :class="{ active: currentTab === '' }" @click="switchTab('')">
                全部
            </view>
            <view class="tab-item" :class="{ active: currentTab === '0' }" @click="switchTab('0')">
                可使用
            </view>
            <view class="tab-item" :class="{ active: currentTab === '1' }" @click="switchTab('1')">
                已使用
            </view>
            <view class="tab-item" :class="{ active: currentTab === '2' }" @click="switchTab('2')">
                已过期
            </view>
        </view>

        <!-- 优惠券列表 -->
        <view v-if="couponList.length" class="list-wrap">
            <view
                v-for="item in couponList"
                :key="item.id"
                class="coupon-card"
                :class="{ disabled: item.status !== 0 }"
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
                        {{
                            item.threshold_amount > 0
                                ? `满${item.threshold_amount}元可用`
                                : '无门槛'
                        }}
                    </view>
                </view>
                <view class="coupon-right">
                    <view class="coupon-name">{{ item.coupon_name }}</view>
                    <view class="coupon-desc">{{ item.discount_desc }}</view>
                    <view class="coupon-time">{{ item.valid_period }}</view>
                    <view class="coupon-scope">{{ item.use_scope_text }}</view>
                </view>
                <view class="coupon-status">
                    <view v-if="item.status === 0" class="status-unused">
                        <view v-if="item.is_expiring" class="expiring-tag">即将过期</view>
                        <button class="btn-use" @click="goUse(item)">去使用</button>
                    </view>
                    <view v-else-if="item.status === 1" class="status-used">
                        <text class="status-text">已使用</text>
                    </view>
                    <view v-else class="status-expired">
                        <text class="status-text">已过期</text>
                    </view>
                </view>
            </view>
        </view>

        <!-- 空状态 -->
        <view v-else-if="!loading" class="empty-tip">
            <image src="/static/images/empty.png" class="empty-icon" mode="aspectFit" />
            <text>暂无优惠券</text>
            <button class="btn-get" @click="goCenter">去领券</button>
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
import { getMyCoupons, getMyCouponStats } from '@/api/coupon'

const loading = ref(false)
const currentTab = ref('')
const couponList = ref<any[]>([])
const stats = ref<any>({})
const page = ref(1)
const hasMore = ref(true)

const switchTab = (tab: string) => {
    if (currentTab.value === tab) return
    currentTab.value = tab
    loadList(true)
}

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
        const res = await getMyCoupons({
            page: page.value,
            limit: 10,
            status: currentTab.value
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

const goUse = (item: any) => {
    uni.navigateTo({
        url: '/pages/staff/staff'
    })
}

const goCenter = () => {
    uni.navigateTo({
        url: '/pages/coupon/center'
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
.coupon-page {
    min-height: 100vh;
    background: #f5f5f5;
}

.stats-bar {
    display: flex;
    background: #fff;
    padding: 24rpx 0;
    margin-bottom: 20rpx;

    .stat-item {
        flex: 1;
        text-align: center;

        .stat-num {
            font-size: 40rpx;
            font-weight: bold;
            color: #333;

            &.highlight {
                color: #ff6b35;
            }
        }

        .stat-label {
            font-size: 24rpx;
            color: #999;
            margin-top: 8rpx;
        }
    }
}

.tabs {
    display: flex;
    background: #fff;
    padding: 0 32rpx;
    margin-bottom: 20rpx;

    .tab-item {
        flex: 1;
        text-align: center;
        padding: 24rpx 0;
        font-size: 28rpx;
        color: #666;
        position: relative;

        &.active {
            color: #ff6b35;
            font-weight: bold;

            &::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 48rpx;
                height: 4rpx;
                background: #ff6b35;
                border-radius: 2rpx;
            }
        }
    }
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

    &.disabled {
        opacity: 0.6;

        .coupon-left {
            background: #ccc;
        }
    }

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

        .coupon-scope {
            font-size: 22rpx;
            color: #999;
        }
    }

    .coupon-status {
        position: absolute;
        right: 24rpx;
        top: 50%;
        transform: translateY(-50%);

        .status-unused {
            display: flex;
            flex-direction: column;
            align-items: center;

            .expiring-tag {
                font-size: 20rpx;
                color: #ff4d4f;
                margin-bottom: 8rpx;
            }

            .btn-use {
                width: 120rpx;
                height: 56rpx;
                line-height: 56rpx;
                font-size: 24rpx;
                color: #ff6b35;
                border: 2rpx solid #ff6b35;
                border-radius: 28rpx;
                background: transparent;
                padding: 0;
            }
        }

        .status-used,
        .status-expired {
            .status-text {
                font-size: 24rpx;
                color: #999;
            }
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
        margin-bottom: 32rpx;
    }

    .btn-get {
        width: 240rpx;
        height: 72rpx;
        line-height: 72rpx;
        font-size: 28rpx;
        color: #fff;
        background: linear-gradient(135deg, #ff6b35 0%, #ff9a5a 100%);
        border-radius: 36rpx;
        border: none;
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
