<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="优惠券详情"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="coupon-detail-page">
        <!-- 加载状态 -->
        <view v-if="loading" class="loading-state">
            <tn-loading mode="flower" :color="$theme.primaryColor" />
            <text class="loading-text">加载中...</text>
        </view>

        <view v-else class="content-wrapper">
            <!-- 优惠券主卡片 - 玻璃态设计 -->
            <view class="coupon-main-card glass-card">
                <!-- 金额区域 - 上方 -->
                <view
                    class="amount-section"
                    :style="{ 
                        background: `linear-gradient(135deg, ${$theme.ctaColor} 0%, ${$theme.ctaColor} 100%)` 
                    }"
                >
                    <!-- 折扣券 -->
                    <view v-if="detail.coupon_type === 2" class="amount-content">
                        <view class="discount-wrapper">
                            <text class="discount-num">{{ (detail.discount_amount / 10).toFixed(1) }}</text>
                            <text class="discount-unit">折</text>
                        </view>
                        <text class="threshold-text">{{ thresholdText }}</text>
                    </view>
                    <!-- 满减券 -->
                    <view v-else class="amount-content">
                        <view class="price-wrapper">
                            <text class="price-symbol">¥</text>
                            <text class="price-num">{{ detail.discount_amount }}</text>
                        </view>
                        <text class="threshold-text">{{ thresholdText }}</text>
                    </view>
                    
                    <!-- 装饰圆点 -->
                    <view class="deco-circle deco-circle-top"></view>
                    <view class="deco-circle deco-circle-bottom"></view>
                </view>

                <!-- 信息区域 - 下方 -->
                <view class="info-section">
                    <view class="title-row">
                        <text class="coupon-title">{{ detail.name }}</text>
                        <view
                            class="type-badge"
                            :style="{ 
                                background: detail.coupon_type === 2 
                                    ? 'rgba(249, 115, 22, 0.1)' 
                                    : 'rgba(124, 58, 237, 0.1)',
                                color: detail.coupon_type === 2 ? $theme.ctaColor : $theme.primaryColor,
                                borderColor: detail.coupon_type === 2 ? $theme.ctaColor : $theme.primaryColor
                            }"
                        >
                            {{ detail.coupon_type_text }}
                        </view>
                    </view>
                    
                    <view class="desc-row">
                        <tn-icon name="discount" size="28" :color="$theme.ctaColor" />
                        <text class="desc-text" :style="{ color: $theme.ctaColor }">
                            {{ detail.discount_desc }}
                        </text>
                    </view>
                    
                    <view class="scope-row">
                        <tn-icon name="shop" size="28" color="#999999" />
                        <text class="scope-text">{{ detail.use_scope_text || '全部可用' }}</text>
                    </view>
                </view>
            </view>

            <!-- 使用规则卡片 -->
            <view class="rules-card">
                <view class="card-header">
                    <tn-icon name="document" size="32" :color="$theme.primaryColor" />
                    <text class="card-title">使用与领取规则</text>
                </view>
                
                <view class="rules-list">
                    <!-- 可领取时间 -->
                    <view class="rule-item">
                        <view class="rule-label">
                            <tn-icon name="time" size="32" :color="$theme.primaryColor" />
                            <text>可领取时间</text>
                        </view>
                        <view class="rule-value">
                            <text 
                                v-for="(line, index) in receiveTimeLines" 
                                :key="index"
                                class="rule-value-line"
                            >
                                {{ line }}
                            </text>
                        </view>
                    </view>

                    <!-- 可使用时间 -->
                    <view class="rule-item">
                        <view class="rule-label">
                            <tn-icon name="calendar" size="32" :color="$theme.primaryColor" />
                            <text>可使用时间</text>
                        </view>
                        <view class="rule-value">
                            <text 
                                v-for="(line, index) in useTimeLines" 
                                :key="index"
                                class="rule-value-line"
                            >
                                {{ line }}
                            </text>
                        </view>
                    </view>

                    <!-- 剩余数量 -->
                    <view class="rule-item">
                        <view class="rule-label">
                            <tn-icon name="ticket" size="32" :color="$theme.primaryColor" />
                            <text>剩余数量</text>
                        </view>
                        <view class="rule-value">
                            <text class="rule-value-line">{{ remainText }}</text>
                        </view>
                    </view>

                    <!-- 每人限领 -->
                    <view class="rule-item">
                        <view class="rule-label">
                            <tn-icon name="user" size="32" :color="$theme.primaryColor" />
                            <text>每人限领</text>
                        </view>
                        <view class="rule-value">
                            <text class="rule-value-line">{{ perLimitText }}</text>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <!-- 底部操作栏 - 固定 -->
        <view class="action-bar">
            <view class="action-content">
                <button
                    class="receive-button"
                    :class="{ 'receive-button-disabled': actionDisabled }"
                    :style="actionDisabled ? {} : { 
                        background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                        color: $theme.btnColor,
                        boxShadow: `0 12rpx 32rpx ${$theme.primaryColor}40`
                    }"
                    :disabled="actionDisabled"
                    :loading="receiving"
                    @click="handleReceive"
                >
                    <template v-if="showCountdown">
                        <tn-icon name="time" size="36" :color="$theme.btnColor" />
                        <text class="countdown-label">距开始：</text>
                        <TnCountDown
                            :time="Number(detail.receive_countdown)"
                            :auto-start="false"
                            :show-hour="true"
                            :show-minute="true"
                            :show-second="true"
                            separator-mode="cn"
                        />
                    </template>
                    <template v-else>
                        <tn-icon 
                            v-if="!actionDisabled" 
                            name="gift" 
                            size="36" 
                            :color="$theme.btnColor" 
                        />
                        <text>{{ actionText }}</text>
                    </template>
                </button>
                
                <text v-if="statusText" class="status-hint">{{ statusText }}</text>
            </view>
            <view class="safe-bottom"></view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, ref, onUnmounted } from 'vue'
import { onLoad, onPullDownRefresh } from '@dcloudio/uni-app'
import { getCouponDetail, receiveCoupon } from '@/api/coupon'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import TnCountDown from '@tuniao/tnui-vue3-uniapp/components/count-down/src/count-down.vue'

const $theme = useThemeStore()
const userStore = useUserStore()

const detail = ref<any>({})
const loading = ref(false)
const receiving = ref(false)
const couponId = ref<number>(0)
let countdownTimer: number | null = null

const pad = (num: number) => String(num).padStart(2, '0')

// 格式化日期时间 - 更友好的显示方式
const formatDateTime = (timestamp: number) => {
    if (!timestamp) return ''
    const date = new Date(timestamp * 1000)
    const year = date.getFullYear()
    const month = pad(date.getMonth() + 1)
    const day = pad(date.getDate())
    const hours = pad(date.getHours())
    const minutes = pad(date.getMinutes())
    
    // 返回格式：2026年02月03日 08:00
    return `${year}年${month}月${day}日 ${hours}:${minutes}`
}

// 格式化日期（不含时间）
const formatDate = (timestamp: number) => {
    if (!timestamp) return ''
    const date = new Date(timestamp * 1000)
    const year = date.getFullYear()
    const month = pad(date.getMonth() + 1)
    const day = pad(date.getDate())
    
    return `${year}年${month}月${day}日`
}

const clearCountdown = () => {
    if (countdownTimer) {
        clearInterval(countdownTimer)
        countdownTimer = null
    }
}

const startCountdown = (seconds: number) => {
    clearCountdown()
    let remaining = Math.max(0, Number(seconds) || 0)
    detail.value.receive_countdown = remaining
    if (remaining <= 0) return

    countdownTimer = setInterval(() => {
        remaining = Math.max(0, remaining - 1)
        detail.value.receive_countdown = remaining
        if (remaining <= 0) {
            clearCountdown()
            refreshDetail(false)
        }
    }, 1000) as unknown as number
}

const refreshDetail = async (showLoading = true) => {
    if (!couponId.value) return
    if (showLoading) {
        loading.value = true
    }
    try {
        const res = await getCouponDetail({ id: couponId.value })
        detail.value = res || {}
        if (Number(detail.value.receive_countdown) > 0) {
            startCountdown(detail.value.receive_countdown)
        } else {
            clearCountdown()
        }
    } catch (error) {
        console.error(error)
    } finally {
        loading.value = false
        uni.stopPullDownRefresh()
    }
}

const thresholdText = computed(() => {
    const threshold = Number(detail.value.threshold_amount || 0)
    return threshold > 0 ? `满${threshold}元可用` : '无门槛'
})

const receiveTimeLines = computed(() => {
    const start = Number(detail.value.receive_start_time || 0)
    const end = Number(detail.value.receive_end_time || 0)
    
    if (!start && !end) return ['不限时间']
    
    if (start && end) {
        return [
            formatDateTime(start),
            '至',
            formatDateTime(end)
        ]
    }
    
    if (start) return [`${formatDateTime(start)} 起`]
    return [`${formatDateTime(end)} 止`]
})

const useTimeLines = computed(() => {
    const validType = Number(detail.value.valid_type || 0)
    const validDays = Number(detail.value.valid_days || 0)
    
    // 领取后N天有效
    if (validType === 2 && validDays) {
        return [`领取后 ${validDays} 天内有效`]
    }
    
    const start = Number(detail.value.valid_start_time || 0)
    const end = Number(detail.value.valid_end_time || 0)
    
    if (!start && !end) return ['长期有效']
    
    if (start && end) {
        return [
            formatDateTime(start),
            '至',
            formatDateTime(end)
        ]
    }
    
    if (start) return [`${formatDateTime(start)} 起`]
    return [`${formatDateTime(end)} 止`]
})
const remainText = computed(() => {
    if (detail.value.remain_count === -1) return '不限'
    if (detail.value.remain_count === 0) return '0张'
    if (detail.value.remain_count) return `${detail.value.remain_count}张`
    const total = Number(detail.value.total_count || 0)
    if (total === 0) return '不限'
    const remain = Math.max(0, total - Number(detail.value.receive_count || 0))
    return `${remain}张`
})

const perLimitText = computed(() => {
    const limit = Number(detail.value.per_limit || 0)
    return limit > 0 ? `${limit}张` : '不限'
})

const showCountdown = computed(() => {
    return detail.value.can_receive === false && Number(detail.value.receive_countdown) > 0
})

const actionText = computed(() => {
    if (detail.value.is_received) return '已领取'
    if (detail.value.remain_count === 0) return '已领完'
    if (detail.value.can_receive === false) return detail.value.receive_status_text || '暂不可领'
    return '立即领取'
})

const actionDisabled = computed(() => {
    return detail.value.is_received || detail.value.remain_count === 0 || detail.value.can_receive === false
})

const statusText = computed(() => {
    if (detail.value.can_receive === false && !showCountdown.value) {
        return detail.value.receive_status_text || '暂不可领取'
    }
    return ''
})

const handleReceive = async () => {
    if (receiving.value) return
    if (detail.value.is_received) {
        uni.showToast({ title: '已领取过该优惠券', icon: 'none' })
        return
    }
    if (detail.value.remain_count === 0) {
        uni.showToast({ title: '优惠券已领完', icon: 'none' })
        return
    }
    if (detail.value.can_receive === false) {
        uni.showToast({ title: detail.value.receive_status_text || '暂不可领取', icon: 'none' })
        return
    }
    if (!userStore.isLogin) {
        uni.showToast({ title: '请先登录', icon: 'none' })
        setTimeout(() => {
            uni.navigateTo({ url: '/pages/login/login' })
        }, 1200)
        return
    }

    receiving.value = true
    try {
        await receiveCoupon({ coupon_id: couponId.value })
        uni.showToast({ title: '领取成功', icon: 'success' })
        await refreshDetail(false)
    } catch (error: any) {
        uni.showToast({ title: error.msg || '领取失败', icon: 'none' })
    } finally {
        receiving.value = false
    }
}

onLoad((options) => {
    const id = Number(options?.id || 0)
    if (!id) {
        uni.showToast({ title: '优惠券不存在', icon: 'none' })
        return
    }
    couponId.value = id
    refreshDetail(true)
})

onPullDownRefresh(() => {
    refreshDetail(false)
})

onUnmounted(() => {
    clearCountdown()
})
</script>

<style scoped lang="scss">
.coupon-detail-page {
    min-height: 100vh;
    background: linear-gradient(180deg, rgba(124, 58, 237, 0.05) 0%, #F6F6F6 100%);
    padding-bottom: 200rpx;
}

.content-wrapper {
    padding: 24rpx;
}

/* 加载状态 */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 200rpx 0;
    gap: 24rpx;
}

.loading-text {
    font-size: 28rpx;
    color: #999999;
}

/* 玻璃态主卡片 */
.coupon-main-card {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(20rpx);
    border-radius: 32rpx;
    padding: 0;
    box-shadow: 0 20rpx 60rpx rgba(124, 58, 237, 0.12),
                0 8rpx 16rpx rgba(0, 0, 0, 0.04);
    margin-bottom: 24rpx;
    border: 2rpx solid rgba(255, 255, 255, 0.3);
    transition: all 0.3s ease;
    overflow: hidden;
}

/* 金额区域 - 上方全宽 */
.amount-section {
    position: relative;
    width: 100%;
    min-height: 200rpx;
    border-radius: 32rpx 32rpx 0 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40rpx 32rpx;
    overflow: hidden;
    box-shadow: 0 8rpx 24rpx rgba(249, 115, 22, 0.2);
}

.amount-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16rpx;
    z-index: 2;
}

.discount-wrapper,
.price-wrapper {
    display: flex;
    align-items: baseline;
    gap: 12rpx;
    color: #FFFFFF;
}

.discount-num,
.price-num {
    font-size: 96rpx;
    font-weight: 700;
    line-height: 1;
    text-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.15);
}

.discount-unit {
    font-size: 40rpx;
    font-weight: 600;
}

.price-symbol {
    font-size: 40rpx;
    font-weight: 600;
}

.threshold-text {
    font-size: 28rpx;
    color: #FFFFFF;
    opacity: 0.95;
    font-weight: 500;
}

/* 装饰圆点 */
.deco-circle {
    position: absolute;
    width: 120rpx;
    height: 120rpx;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.12);
    z-index: 1;
}

.deco-circle-top {
    top: -40rpx;
    right: -40rpx;
}

.deco-circle-bottom {
    bottom: -50rpx;
    left: -50rpx;
    width: 150rpx;
    height: 150rpx;
}

/* 信息区域 - 下方 */
.info-section {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 20rpx;
    padding: 32rpx;
}

.title-row {
    display: flex;
    align-items: flex-start;
    gap: 12rpx;
}

.coupon-title {
    flex: 1;
    font-size: 36rpx;
    font-weight: 700;
    color: #333333;
    line-height: 1.4;
    word-break: break-all;
}

.type-badge {
    padding: 8rpx 16rpx;
    border-radius: 12rpx;
    font-size: 24rpx;
    font-weight: 600;
    white-space: nowrap;
    border: 1rpx solid;
}

.desc-row,
.scope-row {
    display: flex;
    align-items: center;
    gap: 10rpx;
}

.desc-text {
    font-size: 30rpx;
    font-weight: 600;
    line-height: 1.4;
    flex: 1;
}

.scope-text {
    font-size: 28rpx;
    color: #666666;
    line-height: 1.4;
    flex: 1;
}

/* 规则卡片 */
.rules-card {
    background: #FFFFFF;
    border-radius: 24rpx;
    padding: 32rpx;
    box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.06);
}

.card-header {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin-bottom: 24rpx;
    padding-bottom: 20rpx;
    border-bottom: 2rpx solid #F5F5F5;
}

.card-title {
    font-size: 32rpx;
    font-weight: 700;
    color: #333333;
}

.rules-list {
    display: flex;
    flex-direction: column;
    gap: 28rpx;
}

.rule-item {
    display: flex;
    align-items: flex-start;
    gap: 16rpx;
}

.rule-label {
    display: flex;
    align-items: center;
    gap: 8rpx;
    min-width: 180rpx;
    font-size: 28rpx;
    color: #666666;
    font-weight: 500;
}

.rule-value {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6rpx;
}

.rule-value-line {
    font-size: 26rpx;
    color: #333333;
    font-weight: 500;
    text-align: right;
    line-height: 1.6;
    
    &:nth-child(2) {
        font-size: 24rpx;
        color: #999999;
        font-weight: 400;
    }
}

/* 底部操作栏 */
.action-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20rpx);
    border-top: 1rpx solid rgba(0, 0, 0, 0.05);
    z-index: 100;
}

.action-content {
    padding: 24rpx;
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.receive-button {
    width: 100%;
    height: 96rpx;
    border-radius: 48rpx;
    font-size: 32rpx;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    border: none;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;

    &::after {
        border: none;
    }

    &:active:not(.receive-button-disabled) {
        transform: translateY(2rpx);
        opacity: 0.9;
    }
}

.receive-button-disabled {
    background: #F5F5F5 !important;
    color: #999999 !important;
    box-shadow: none !important;
}

.countdown-label {
    font-size: 28rpx;
    font-weight: 500;
}

.status-hint {
    text-align: center;
    font-size: 24rpx;
    color: #999999;
}

.safe-bottom {
    height: constant(safe-area-inset-bottom);
    height: env(safe-area-inset-bottom);
}

/* 动画效果 */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20rpx);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.coupon-main-card,
.rules-card {
    animation: fadeIn 0.4s ease-out;
}

.rules-card {
    animation-delay: 0.1s;
}
</style>
