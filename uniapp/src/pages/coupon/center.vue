<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="领券中心"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="coupon-center-page">
        <!-- 顶部入口卡片 -->
        <view class="top-entry" @click="goMyCoupons">
            <view class="entry-card">
                <view class="entry-left">
                    <view class="icon-wrapper" :style="{ background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)` }">
                        <tn-icon name="coupon-fill" size="40" color="#FFFFFF" />
                    </view>
                    <view class="entry-info">
                        <text class="entry-title">我的优惠券</text>
                        <text class="entry-desc" v-if="stats.unused > 0">{{ stats.unused }}张可用</text>
                        <text class="entry-desc" v-else>暂无可用优惠券</text>
                    </view>
                </view>
                <tn-icon name="right" size="32" color="#999999" />
            </view>
        </view>

        <!-- 优惠券列表 -->
        <view class="section-header">
            <view class="section-title">
                <view class="title-icon" :style="{ background: $theme.primaryColor }"></view>
                <text>可领取优惠券</text>
            </view>
            <text class="section-count" v-if="couponList.length">共{{ couponList.length }}张</text>
        </view>

        <view v-if="couponList.length" class="list-wrap">
            <view v-for="item in couponList" :key="item.id" class="coupon-card" @click="goDetail(item)">
                <!-- 优惠券左侧金额区 -->
                <view class="coupon-left" :style="{ background: `linear-gradient(135deg, ${$theme.ctaColor} 0%, ${$theme.ctaColor} 100%)` }">
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
                        {{ item.threshold_amount > 0 ? `满${item.threshold_amount}元` : '无门槛' }}
                    </view>
                </view>

                <view class="coupon-content">
                    <!-- 优惠券右侧信息区 -->
                    <view class="coupon-right">
                        <view class="coupon-header">
                            <text class="coupon-name">{{ item.name }}</text>
                            <view class="coupon-tag" :style="{ 
                                background: item.coupon_type === 2 ? 'rgba(249, 115, 22, 0.1)' : 'rgba(124, 58, 237, 0.1)',
                                color: item.coupon_type === 2 ? '#F97316' : $theme.primaryColor
                            }">
                                {{ item.coupon_type_text }}
                            </view>
                            <tn-icon name="right" size="26" color="#C0C4CC" class="detail-arrow" />
                        </view>
                        
                        <view class="coupon-desc" :style="{ color: $theme.ctaColor }">
                            {{ item.discount_desc }}
                        </view>
                        
                        <view class="coupon-info">
                            <view class="info-item">
                                <tn-icon name="time" size="24" color="#999999" />
                                <text>{{ formatValidPeriod(item) }}</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <!-- 空状态 -->
        <view v-else-if="!loading" class="empty-state">
            <view class="empty-icon-wrapper">
                <tn-icon name="coupon" size="120" color="#E5E5E5" />
            </view>
            <text class="empty-text">暂无可领取的优惠券</text>
            <text class="empty-desc">敬请期待更多优惠活动</text>
        </view>

        <!-- 加载状态 -->
        <view v-if="loading && page === 1" class="loading-state">
            <tn-loading mode="flower" :color="$theme.primaryColor" />
            <text class="loading-text">加载中...</text>
        </view>

        <!-- 加载更多 -->
        <view v-if="loading && page > 1" class="loading-more">
            <tn-loading mode="circle" size="small" :color="$theme.primaryColor" />
            <text>加载更多...</text>
        </view>

        <!-- 没有更多 -->
        <view v-if="!loading && !hasMore && couponList.length > 0" class="no-more">
            <text>没有更多了</text>
        </view>

        <!-- 底部安全区 -->
        <view class="safe-bottom"></view>
    </view>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { onReachBottom, onPullDownRefresh } from '@dcloudio/uni-app'
import { getAvailableCoupons, getMyCouponStats } from '@/api/coupon'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()

const loading = ref(false)
const couponList = ref<any[]>([])
const stats = ref<any>({})
const page = ref(1)
const hasMore = ref(true)

// 格式化时间显示
const formatValidPeriod = (item: any) => {
    const validType = Number(item.valid_type || 0)
    const validDays = Number(item.valid_days || 0)
    
    // 领取后N天有效
    if (validType === 2 && validDays) {
        return `领取后${validDays}天有效`
    }
    
    // 固定时间段
    const start = Number(item.valid_start_time || 0)
    const end = Number(item.valid_end_time || 0)
    
    if (!start && !end) return '长期有效'
    
    const formatDate = (timestamp: number) => {
        if (!timestamp) return ''
        const date = new Date(timestamp * 1000)
        const year = date.getFullYear()
        const month = String(date.getMonth() + 1).padStart(2, '0')
        const day = String(date.getDate()).padStart(2, '0')
        return `${year}.${month}.${day}`
    }
    
    if (start && end) {
        return `${formatDate(start)}-${formatDate(end)}`
    }
    
    if (start) return `${formatDate(start)}起`
    return `${formatDate(end)}止`
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

const goDetail = (item: any) => {
    if (!item?.id) return
    uni.navigateTo({
        url: `/pages/coupon/detail?id=${item.id}`
    })
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
    background: linear-gradient(180deg, rgba(124, 58, 237, 0.03) 0%, #F6F6F6 100%);
    padding-bottom: 24rpx;
}

/* 顶部入口卡片 */
.top-entry {
    padding: 24rpx;
    margin-bottom: 8rpx;

    .entry-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #FFFFFF;
        border-radius: 16rpx;
        padding: 24rpx;
        box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);
        transition: all 0.2s ease;

        &:active {
            transform: scale(0.98);
            box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.12);
        }

        .entry-left {
            display: flex;
            align-items: center;
            gap: 16rpx;

            .icon-wrapper {
                width: 80rpx;
                height: 80rpx;
                border-radius: 16rpx;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.3);
            }

            .entry-info {
                display: flex;
                flex-direction: column;
                gap: 4rpx;

                .entry-title {
                    font-size: 32rpx;
                    font-weight: 600;
                    color: #333333;
                }

                .entry-desc {
                    font-size: 24rpx;
                    color: #999999;
                }
            }
        }
    }
}

/* 区块标题 */
.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24rpx 24rpx 16rpx;

    .section-title {
        display: flex;
        align-items: center;
        gap: 12rpx;

        .title-icon {
            width: 6rpx;
            height: 32rpx;
            border-radius: 3rpx;
        }

        text {
            font-size: 34rpx;
            font-weight: 600;
            color: #333333;
        }
    }

    .section-count {
        font-size: 26rpx;
        color: #999999;
    }
}

/* 优惠券列表 */
.list-wrap {
    padding: 0 24rpx;
}

.coupon-card {
    display: flex;
    background: #FFFFFF;
    border-radius: 16rpx;
    margin-bottom: 24rpx;
    overflow: hidden;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);
    transition: all 0.2s ease;
    position: relative;

    &:active {
        transform: translateY(-2rpx);
        box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.12);
    }

    /* 左侧金额区 */
    .coupon-left {
        width: 240rpx;
        min-width: 240rpx;
        padding: 32rpx 20rpx;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #FFFFFF;
        position: relative;

        /* 锯齿边缘效果 */
        &::after {
            content: '';
            position: absolute;
            right: -8rpx;
            top: 0;
            bottom: 0;
            width: 16rpx;
            background: radial-gradient(circle at 0 0, transparent 8rpx, currentColor 8rpx);
            background-size: 16rpx 32rpx;
            background-repeat: repeat-y;
            color: #FFFFFF;
        }

        .coupon-value {
            display: flex;
            align-items: baseline;
            justify-content: center;
            margin-bottom: 8rpx;
            width: 100%;

            .symbol {
                font-size: 28rpx;
                font-weight: 600;
                margin-right: 4rpx;
                flex-shrink: 0;
            }

            .num {
                font-size: 56rpx;
                font-weight: 700;
                line-height: 1;
                flex-shrink: 1;
                min-width: 0;
            }

            .unit {
                font-size: 28rpx;
                font-weight: 600;
                margin-left: 4rpx;
                flex-shrink: 0;
            }
        }

        .coupon-threshold {
            font-size: 24rpx;
            opacity: 0.95;
            text-align: center;
            width: 100%;
            line-height: 1.4;
            padding: 0 8rpx;
        }
    }

    .coupon-content {
        flex: 1;
        display: flex;
        align-items: stretch;
        justify-content: space-between;
        gap: 16rpx;
        padding: 24rpx 24rpx 24rpx 20rpx;
        min-width: 0;
    }

    /* 右侧信息区 */
    .coupon-right {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 10rpx;
        min-width: 0;

        .coupon-header {
            display: flex;
            align-items: center;
            gap: 8rpx;
            flex-wrap: wrap;

            .coupon-name {
                font-size: 30rpx;
                font-weight: 600;
                color: #333333;
                flex: 1;
                min-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .coupon-tag {
                padding: 4rpx 10rpx;
                border-radius: 8rpx;
                font-size: 20rpx;
                font-weight: 500;
                white-space: nowrap;
                flex-shrink: 0;
            }

            .detail-arrow {
                margin-left: auto;
            }
        }

        .coupon-desc {
            font-size: 24rpx;
            font-weight: 500;
            line-height: 1.4;
        }

        .coupon-info {
            display: flex;
            flex-direction: column;
            gap: 8rpx;

            .info-item {
                display: flex;
                align-items: center;
                gap: 8rpx;
                font-size: 22rpx;
                color: #999999;
                line-height: 1.4;
            }
        }
    }
}

/* 空状态 */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 120rpx 0;

    .empty-icon-wrapper {
        width: 240rpx;
        height: 240rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #F9FAFB;
        border-radius: 50%;
        margin-bottom: 32rpx;
    }

    .empty-text {
        font-size: 32rpx;
        font-weight: 500;
        color: #666666;
        margin-bottom: 12rpx;
    }

    .empty-desc {
        font-size: 26rpx;
        color: #999999;
    }
}

/* 加载状态 */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 120rpx 0;
    gap: 24rpx;

    .loading-text {
        font-size: 28rpx;
        color: #999999;
    }
}

.loading-more {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32rpx;
    gap: 12rpx;
    font-size: 26rpx;
    color: #999999;
}

.no-more {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32rpx;
    font-size: 26rpx;
    color: #CCCCCC;

    &::before,
    &::after {
        content: '';
        width: 80rpx;
        height: 1rpx;
        background: #E5E5E5;
        margin: 0 24rpx;
    }
}

/* 底部安全区 */
.safe-bottom {
    height: constant(safe-area-inset-bottom);
    height: env(safe-area-inset-bottom);
}
</style>
