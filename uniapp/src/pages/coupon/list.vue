<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="我的优惠券"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="coupon-page">
        <!-- 统计数字卡片 -->
        <view class="stats-card">
            <view 
                class="stat-item" 
                :class="{ active: currentTab === '' }"
                @click="switchTab('')"
            >
                <view class="stat-num" :style="currentTab === '' ? { color: $theme.primaryColor } : {}">
                    {{ stats.total || 0 }}
                </view>
                <view class="stat-label">全部</view>
            </view>
            <view 
                class="stat-item" 
                :class="{ active: currentTab === '0' }"
                @click="switchTab('0')"
            >
                <view class="stat-num highlight" :style="currentTab === '0' ? { color: $theme.ctaColor } : { color: $theme.ctaColor }">
                    {{ stats.unused || 0 }}
                </view>
                <view class="stat-label">可使用</view>
            </view>
            <view 
                class="stat-item" 
                :class="{ active: currentTab === '1' }"
                @click="switchTab('1')"
            >
                <view class="stat-num" :style="currentTab === '1' ? { color: $theme.primaryColor } : {}">
                    {{ stats.used || 0 }}
                </view>
                <view class="stat-label">已使用</view>
            </view>
            <view 
                class="stat-item" 
                :class="{ active: currentTab === '2' }"
                @click="switchTab('2')"
            >
                <view class="stat-num" :style="currentTab === '2' ? { color: $theme.primaryColor } : {}">
                    {{ stats.expired || 0 }}
                </view>
                <view class="stat-label">已过期</view>
            </view>
        </view>

        <!-- 标签页 -->
        <view class="tabs-wrapper">
            <view class="tabs">
                <view 
                    class="tab-item" 
                    :class="{ active: currentTab === '' }" 
                    @click="switchTab('')"
                >
                    <text>全部</text>
                    <view 
                        v-if="currentTab === ''" 
                        class="tab-indicator" 
                        :style="{ background: $theme.primaryColor }"
                    ></view>
                </view>
                <view 
                    class="tab-item" 
                    :class="{ active: currentTab === '0' }" 
                    @click="switchTab('0')"
                >
                    <text>可使用</text>
                    <view 
                        v-if="currentTab === '0'" 
                        class="tab-indicator" 
                        :style="{ background: $theme.primaryColor }"
                    ></view>
                </view>
                <view 
                    class="tab-item" 
                    :class="{ active: currentTab === '1' }" 
                    @click="switchTab('1')"
                >
                    <text>已使用</text>
                    <view 
                        v-if="currentTab === '1'" 
                        class="tab-indicator" 
                        :style="{ background: $theme.primaryColor }"
                    ></view>
                </view>
                <view 
                    class="tab-item" 
                    :class="{ active: currentTab === '2' }" 
                    @click="switchTab('2')"
                >
                    <text>已过期</text>
                    <view 
                        v-if="currentTab === '2'" 
                        class="tab-indicator" 
                        :style="{ background: $theme.primaryColor }"
                    ></view>
                </view>
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
                <!-- 左侧金额区 -->
                <view 
                    class="coupon-left"
                    :style="item.status === 0 ? { 
                        background: `linear-gradient(135deg, ${$theme.ctaColor} 0%, ${$theme.ctaColor} 100%)` 
                    } : {}"
                >
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

                <!-- 右侧信息区 -->
                <view class="coupon-content">
                    <view class="coupon-info">
                        <view class="info-header">
                            <text class="coupon-name">{{ item.coupon_name }}</text>
                            <view 
                                class="coupon-tag"
                                :style="{ 
                                    background: item.coupon_type === 2 ? 'rgba(249, 115, 22, 0.1)' : 'rgba(124, 58, 237, 0.1)',
                                    color: item.coupon_type === 2 ? $theme.ctaColor : $theme.primaryColor
                                }"
                            >
                                {{ item.coupon_type_text }}
                            </view>
                        </view>
                        
                        <view class="coupon-desc" :style="{ color: $theme.ctaColor }">
                            <tn-icon name="discount" size="24" :color="$theme.ctaColor" />
                            <text>{{ item.discount_desc }}</text>
                        </view>
                        
                        <view class="coupon-detail">
                            <view class="detail-item">
                                <tn-icon name="time" size="24" color="#999999" />
                                <text>{{ formatValidPeriod(item) }}</text>
                            </view>
                            <view v-if="isNotStarted(item)" class="detail-item not-started-item">
                                <tn-icon name="time" size="24" :color="$theme.primaryColor" />
                                <text class="not-started-text">未到使用时间</text>
                            </view>
                            <view class="detail-item" v-if="item.use_scope_text">
                                <tn-icon name="shop" size="24" color="#999999" />
                                <text>{{ item.use_scope_text }}</text>
                            </view>
                        </view>
                    </view>

                    <!-- 状态/操作区 -->
                    <view class="coupon-action">
                        <view v-if="item.status === 0" class="action-unused">
                            <view 
                                v-if="item.is_expiring && !isNotStarted(item)" 
                                class="expiring-badge"
                                :style="{ 
                                    background: 'rgba(255, 77, 79, 0.1)',
                                    color: '#FF4D4F'
                                }"
                            >
                                <tn-icon name="warning" size="24" color="#FF4D4F" />
                                <text>即将过期</text>
                            </view>
                            <button 
                                class="btn-use" 
                                :class="{ disabled: isNotStarted(item) }"
                                :style="isNotStarted(item) ? { background: '#F5F5F5', color: '#999999' } : { 
                                    background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                                    color: $theme.btnColor
                                }"
                                :disabled="isNotStarted(item)"
                                @click="goUse(item)"
                            >
                                <text>{{ isNotStarted(item) ? '未生效' : '去使用' }}</text>
                            </button>
                        </view>
                        <view v-else-if="item.status === 1" class="action-used">
                            <tn-icon name="check-circle" size="48" color="#52C41A" />
                            <text class="status-text">已使用</text>
                        </view>
                        <view v-else class="action-expired">
                            <tn-icon name="close-circle" size="48" color="#CCCCCC" />
                            <text class="status-text">已过期</text>
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
            <text class="empty-text">{{ emptyText }}</text>
            <text class="empty-desc">{{ emptyDesc }}</text>
            <button 
                class="btn-get" 
                :style="{ 
                    background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                    color: $theme.btnColor
                }"
                @click="goCenter"
            >
                <tn-icon name="gift" size="32" :color="$theme.btnColor" />
                <text>去领券</text>
            </button>
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
import { getMyCoupons, getMyCouponStats } from '@/api/coupon'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()

const loading = ref(false)
const currentTab = ref('')
const couponList = ref<any[]>([])
const stats = ref<any>({})
const page = ref(1)
const hasMore = ref(true)

// 格式化有效期显示
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

// 空状态文案
const emptyText = computed(() => {
    const textMap: Record<string, string> = {
        '': '暂无优惠券',
        '0': '暂无可用优惠券',
        '1': '暂无已使用优惠券',
        '2': '暂无已过期优惠券'
    }
    return textMap[currentTab.value] || '暂无优惠券'
})

const emptyDesc = computed(() => {
    if (currentTab.value === '0') {
        return '快去领券中心领取优惠券吧'
    }
    return '敬请期待更多优惠活动'
})

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

const isNotStarted = (item: any) => {
    if (item.status !== 0) return false
    const start = Number(item.valid_start_time || 0)
    if (!start) return false
    return start > Math.floor(Date.now() / 1000)
}

const goUse = (item: any) => {
    if (isNotStarted(item)) {
        uni.showToast({
            title: '优惠券未到使用时间',
            icon: 'none'
        })
        return
    }
    uni.switchTab({
        url: '/packages/pages/staff_list/staff_list'
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
    background: linear-gradient(180deg, rgba(124, 58, 237, 0.03) 0%, #F6F6F6 100%);
    padding-bottom: 24rpx;
}

/* 统计卡片 */
.stats-card {
    display: flex;
    background: #FFFFFF;
    border-radius: 24rpx;
    padding: 32rpx 0;
    margin: 24rpx 24rpx 16rpx;
    box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.06);

    .stat-item {
        flex: 1;
        text-align: center;
        position: relative;
        transition: all 0.2s ease;

        &:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 1rpx;
            height: 60rpx;
            background: #F0F0F0;
        }

        &.active {
            .stat-num {
                transform: scale(1.1);
            }
        }

        .stat-num {
            font-size: 48rpx;
            font-weight: 700;
            color: #333333;
            margin-bottom: 8rpx;
            transition: all 0.2s ease;

            &.highlight {
                color: #F97316;
            }
        }

        .stat-label {
            font-size: 26rpx;
            color: #999999;
        }
    }
}

/* 标签页 */
.tabs-wrapper {
    background: #FFFFFF;
    margin: 0 24rpx 24rpx;
    border-radius: 24rpx;
    box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.04);
}

.tabs {
    display: flex;
    padding: 0 32rpx;

    .tab-item {
        flex: 1;
        text-align: center;
        padding: 28rpx 0;
        font-size: 30rpx;
        color: #666666;
        position: relative;
        transition: all 0.2s ease;

        &.active {
            color: #333333;
            font-weight: 600;
        }

        .tab-indicator {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 48rpx;
            height: 6rpx;
            border-radius: 3rpx;
        }
    }
}

/* 优惠券列表 */
.list-wrap {
    padding: 0 24rpx;
}

.coupon-card {
    display: flex;
    background: #FFFFFF;
    border-radius: 24rpx;
    margin-bottom: 24rpx;
    overflow: hidden;
    box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;

    &:active {
        transform: translateY(-2rpx);
        box-shadow: 0 12rpx 32rpx rgba(0, 0, 0, 0.1);
    }

    &.disabled {
        opacity: 0.6;

        .coupon-left {
            background: linear-gradient(135deg, #CCCCCC 0%, #DDDDDD 100%) !important;
        }
    }

    /* 左侧金额区 */
    .coupon-left {
        width: 220rpx;
        min-width: 220rpx;
        padding: 32rpx 20rpx;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #FFFFFF;
        position: relative;

        /* 锯齿边缘 */
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
            margin-bottom: 12rpx;

            .symbol {
                font-size: 32rpx;
                font-weight: 600;
                margin-right: 4rpx;
            }

            .num {
                font-size: 64rpx;
                font-weight: 700;
                line-height: 1;
            }

            .unit {
                font-size: 32rpx;
                font-weight: 600;
                margin-left: 4rpx;
            }
        }

        .coupon-threshold {
            font-size: 24rpx;
            opacity: 0.95;
            text-align: center;
        }
    }

    /* 右侧内容区 */
    .coupon-content {
        flex: 1;
        display: flex;
        padding: 24rpx;
        gap: 16rpx;
        min-width: 0;
    }

    .coupon-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 12rpx;
        min-width: 0;

        .info-header {
            display: flex;
            align-items: center;
            gap: 10rpx;

            .coupon-name {
                flex: 1;
                font-size: 32rpx;
                font-weight: 700;
                color: #333333;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .coupon-tag {
                padding: 6rpx 12rpx;
                border-radius: 8rpx;
                font-size: 22rpx;
                font-weight: 600;
                white-space: nowrap;
            }
        }

        .coupon-desc {
            display: flex;
            align-items: center;
            gap: 8rpx;
            font-size: 26rpx;
            font-weight: 600;
        }

        .coupon-detail {
            display: flex;
            flex-direction: column;
            gap: 8rpx;

            .detail-item {
                display: flex;
                align-items: center;
                gap: 8rpx;
                font-size: 24rpx;
                color: #999999;
            }

            .not-started-item {
                color: #7C3AED;

                .not-started-text {
                    color: #7C3AED;
                    font-weight: 600;
                }
            }
        }
    }

    /* 操作区 */
    .coupon-action {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12rpx;

        .action-unused {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12rpx;

            .expiring-badge {
                display: flex;
                align-items: center;
                gap: 6rpx;
                padding: 6rpx 12rpx;
                border-radius: 12rpx;
                font-size: 22rpx;
                font-weight: 600;
                white-space: nowrap;
            }

            .btn-use {
                width: 120rpx;
                height: 64rpx;
                line-height: 64rpx;
                font-size: 26rpx;
                font-weight: 600;
                border-radius: 32rpx;
                border: none;
                padding: 0;
                box-shadow: 0 8rpx 16rpx rgba(124, 58, 237, 0.3);

                &::after {
                    border: none;
                }

                &.disabled {
                    box-shadow: none;
                }
            }
        }

        .action-used,
        .action-expired {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8rpx;

            .status-text {
                font-size: 24rpx;
                color: #999999;
                font-weight: 500;
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
        font-weight: 600;
        color: #666666;
        margin-bottom: 12rpx;
    }

    .empty-desc {
        font-size: 26rpx;
        color: #999999;
        margin-bottom: 48rpx;
    }

    .btn-get {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8rpx;
        width: 280rpx;
        height: 88rpx;
        font-size: 30rpx;
        font-weight: 600;
        border-radius: 44rpx;
        border: none;
        box-shadow: 0 12rpx 32rpx rgba(124, 58, 237, 0.3);

        &::after {
            border: none;
        }
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
