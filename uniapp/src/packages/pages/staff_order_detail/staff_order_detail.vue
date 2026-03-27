<template>
    <page-meta :page-style="$theme.pageStyle" />
    <BaseNavbar title="订单详情" />

    <view class="page-container" v-if="order">
        <!-- 订单状态卡片 -->
        <view
            class="status-card"
            :style="{
                background: `linear-gradient(135deg, ${statusInfo.color}15 0%, ${statusInfo.color}30 100%)`,
                borderColor: `${statusInfo.color}40`
            }"
        >
            <view class="status-icon-wrapper" :style="{ background: statusInfo.color }">
                <tn-icon :name="statusInfo.icon" size="48" color="#FFFFFF" />
            </view>
            <view class="status-content">
                <view class="status-text" :style="{ color: statusInfo.color }">
                    {{ statusInfo.text }}
                </view>
                <view class="order-no">订单号：{{ order.order_sn }}</view>
            </view>
        </view>

        <!-- 服务信息卡片 -->
        <view class="info-card">
            <view class="card-header">
                <tn-icon name="map-pin" size="32" :color="$theme.primaryColor" />
                <text class="card-title">服务信息</text>
            </view>
            <view class="contact-info">
                <view class="contact-row">
                    <view class="contact-label">
                        <tn-icon name="user" size="24" color="#999999" />
                        <text>联系人</text>
                    </view>
                    <text class="contact-value">{{ order.contact_name || '未填写' }}</text>
                </view>
                <view class="contact-row">
                    <view class="contact-label">
                        <tn-icon name="phone" size="24" color="#999999" />
                        <text>联系电话</text>
                    </view>
                    <text class="contact-value">{{ order.contact_mobile || '未填写' }}</text>
                </view>
                <view class="contact-row" v-if="order.service_region_text">
                    <view class="contact-label">
                        <tn-icon name="location" size="24" color="#999999" />
                        <text>服务地区</text>
                    </view>
                    <text class="contact-value address">{{ order.service_region_text }}</text>
                </view>
                <view class="contact-row" v-if="order.service_address">
                    <view class="contact-label">
                        <tn-icon name="location" size="24" color="#999999" />
                        <text>详细地址</text>
                    </view>
                    <text class="contact-value address">{{ order.service_address }}</text>
                </view>
            </view>
        </view>

        <!-- 服务项目卡片 -->
        <view class="info-card">
            <view class="card-header">
                <tn-icon name="list" size="32" :color="$theme.primaryColor" />
                <text class="card-title">服务项目</text>
            </view>
            <view v-if="order.items && order.items.length" class="service-list">
                <view v-for="item in order.items" :key="item.id" class="service-item">
                    <view class="service-header">
                        <text class="service-name">{{ item.package_name || '服务套餐' }}</text>
                        <text class="service-price" :style="{ color: $theme.ctaColor }">
                            <text class="price-symbol">¥</text>
                            <text class="price-value">{{ item.price }}</text>
                        </text>
                    </view>
                    <text v-if="getPackageDescription(item)" class="service-description">
                        {{ getPackageDescription(item) }}
                    </text>
                    <view class="service-detail">
                        <tn-icon name="calendar" size="24" color="#999999" />
                        <text>{{ item.service_date || '未选择日期' }}</text>
                    </view>
                    <view class="service-meta">
                        <view
                            class="meta-tag"
                            :style="{
                                background: `${$theme.primaryColor}15`,
                                color: $theme.primaryColor
                            }"
                        >
                            {{ item.item_status_desc || '未知状态' }}
                        </view>
                        <text class="meta-quantity">数量 x{{ item.quantity || 1 }}</text>
                    </view>
                    <view v-if="item.addons && item.addons.length" class="addon-box">
                        <view class="addon-header">
                            <text class="addon-title">附加服务</text>
                            <text class="addon-total">+¥{{ formatAmount(getItemAddonTotal(item)) }}</text>
                        </view>
                        <view v-for="addon in item.addons" :key="`${item.id}-${addon.id}`" class="addon-row">
                            <text class="addon-name">{{ addon.addon_name || addon.name }}</text>
                            <text class="addon-price">+¥{{ formatAmount(addon.subtotal || addon.price) }}</text>
                        </view>
                    </view>
                </view>
            </view>
            <view v-else class="empty-tip">暂无服务项目</view>
        </view>

        <!-- 订单信息卡片 -->
        <view class="info-card">
            <view class="card-header">
                <tn-icon name="file-text" size="32" :color="$theme.primaryColor" />
                <text class="card-title">订单信息</text>
            </view>
            <view class="info-list">
                <view class="info-row">
                    <text class="info-label">订单编号</text>
                    <text class="info-value">{{ order.order_sn }}</text>
                </view>
                <view class="info-row">
                    <text class="info-label">下单时间</text>
                    <text class="info-value">{{ order.create_time }}</text>
                </view>
                <view class="info-row" v-if="order.pay_time">
                    <text class="info-label">支付时间</text>
                    <text class="info-value">{{ order.pay_time }}</text>
                </view>
                <view class="info-row" v-if="order.order_status_desc">
                    <text class="info-label">订单状态</text>
                    <text class="info-value">{{ order.order_status_desc }}</text>
                </view>
                <view class="info-row" v-if="order.pay_status_desc">
                    <text class="info-label">支付状态</text>
                    <text class="info-value">{{ order.pay_status_desc }}</text>
                </view>
            </view>
        </view>

        <!-- 金额明细卡片 -->
        <view class="info-card amount-card">
            <view class="card-header">
                <tn-icon name="money" size="32" :color="$theme.primaryColor" />
                <text class="card-title">金额明细</text>
            </view>
            <view class="amount-list">
                <view class="amount-row">
                    <text class="amount-label">主服务金额</text>
                    <text class="amount-value">¥{{ formatAmount(orderServiceAmount) }}</text>
                </view>
                <view class="amount-row" v-if="Number(order.addon_amount || 0) > 0">
                    <text class="amount-label">附加服务金额</text>
                    <text class="amount-value">+¥{{ formatAmount(order.addon_amount) }}</text>
                </view>
                <view class="amount-row" v-if="order.discount_amount > 0">
                    <text class="amount-label">优惠金额</text>
                    <text class="amount-value discount">-¥{{ formatAmount(order.discount_amount) }}</text>
                </view>
                <view class="amount-row total">
                    <text class="amount-label">实付金额</text>
                    <text class="amount-value total-value" :style="{ color: $theme.ctaColor }">
                        <text class="price-symbol">¥</text>
                        <text class="price-number">{{ formatAmount(order.pay_amount || 0) }}</text>
                    </text>
                </view>
            </view>
        </view>

        <!-- 底部操作按钮 -->
        <view v-if="canConfirm" class="action-wrapper">
            <view
                class="confirm-btn"
                :style="{
                    background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                    color: $theme.btnColor
                }"
                @click="handleConfirm"
            >
                <tn-icon name="check-circle" size="32" :color="$theme.btnColor" />
                <text>确认订单</text>
            </view>
        </view>
    </view>

    <!-- 加载状态 -->
    <view v-else class="loading-container">
        <tn-icon name="loading" size="80" color="#C8C9CC" />
        <text class="loading-text">订单加载中...</text>
    </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { staffCenterOrderDetail, staffCenterOrderConfirm } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()
const order = ref<any>(null)

// 状态配置
const statusConfig: Record<string, { text: string; color: string; icon: string }> = {
    pending_confirm: { text: '待确认', color: '#FF9900', icon: 'clock' },
    pending_pay: { text: '待支付', color: '#F97316', icon: 'money' },
    paid: { text: '已支付', color: '#19BE6B', icon: 'check-circle' },
    in_service: { text: '服务中', color: '#E85A4F', icon: 'star' },
    completed: { text: '已完成', color: '#19BE6B', icon: 'check-circle' },
    reviewed: { text: '已评价', color: '#19BE6B', icon: 'like' },
    cancelled: { text: '已取消', color: '#999999', icon: 'close-circle' },
    paused: { text: '已暂停', color: '#FF9900', icon: 'pause' },
    refunded: { text: '已退款', color: '#FF2C3C', icon: 'close-circle' }
}

// 获取状态键
const getStatusKey = (status: number) => {
    const statusMap: Record<number, string> = {
        0: 'pending_confirm',
        1: 'pending_pay',
        2: 'paid',
        3: 'in_service',
        4: 'completed',
        5: 'reviewed',
        6: 'cancelled',
        7: 'paused',
        8: 'refunded'
    }
    return statusMap[status] || 'pending_pay'
}

// 状态信息
const statusInfo = computed(() => {
    const key = getStatusKey(order.value?.order_status ?? 1)
    return statusConfig[key] || statusConfig.pending_pay
})

const orderServiceAmount = computed(() => {
    const serviceAmount = Number(order.value?.service_amount ?? -1)
    if (serviceAmount >= 0) {
        return serviceAmount
    }
    const total = Number(order.value?.total_amount || 0)
    const addonAmount = Number(order.value?.addon_amount || 0)
    return Math.max(0, total - addonAmount)
})

// 是否可确认
const canConfirm = computed(() => {
    const hasPending = (order.value?.items || []).some(
        (item: any) => Number(item?.confirm_status ?? 0) === 0
    )
    return Number(order.value?.order_status ?? -1) === 0 && hasPending
})

const formatAmount = (amount: number | string) => {
    return Number(amount || 0).toFixed(2)
}

const getPackageDescription = (item: any) => {
    return String(item?.package_description || '').trim()
}

const getItemAddonTotal = (item: any) => {
    return (item?.addons || []).reduce((sum: number, addon: any) => {
        return sum + Number(addon?.subtotal || addon?.price || 0)
    }, 0)
}

// 获取订单详情
const fetchDetail = async (id: number) => {
    try {
        const res: any = await staffCenterOrderDetail({ id })
        order.value = res || null
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '获取订单失败'
        uni.showToast({ title: msg, icon: 'none' })
    }
}

// 确认订单
const handleConfirm = () => {
    if (!order.value?.id) return
    uni.showModal({
        title: '确认订单',
        content: '确认后客户可进行支付，是否继续？',
        success: async (res) => {
            if (!res.confirm) return
            try {
                await staffCenterOrderConfirm({ id: order.value.id })
                uni.showToast({ title: '确认成功', icon: 'success' })
                fetchDetail(order.value.id)
            } catch (e: any) {
                const msg = typeof e === 'string' ? e : e?.msg || e?.message || '确认失败'
                uni.showToast({ title: msg, icon: 'none' })
            }
        }
    })
}

onLoad(async (options: any) => {
    if (!(await ensureStaffCenterAccess())) return
    const id = Number(options?.id || 0)
    if (!id) {
        uni.showToast({ title: '订单不存在', icon: 'none' })
        setTimeout(() => {
            uni.navigateBack()
        }, 1500)
        return
    }
    fetchDetail(id)
})
</script>

<style lang="scss" scoped>
.page-container {
    min-height: 100vh;
    background: #F4F5F7;
    padding-bottom: 120rpx;
}

/* 订单状态卡片 */
.status-card {
    display: flex;
    align-items: center;
    gap: 20rpx;
    margin: 20rpx 24rpx;
    padding: 28rpx;
    border-radius: 24rpx;
    border: 2rpx solid;
    box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.05);
}

.status-icon-wrapper {
    width: 96rpx;
    height: 96rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 48rpx;
    box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.15);
}

.status-content {
    flex: 1;
}

.status-text {
    font-size: 36rpx;
    font-weight: 700;
    line-height: 1.4;
}

.order-no {
    font-size: 24rpx;
    color: #999999;
    margin-top: 8rpx;
}

/* 信息卡片 */
.info-card {
    margin: 0 24rpx 20rpx;
    padding: 28rpx;
    background: #FFFFFF;
    border-radius: 24rpx;
    box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.05);
}

.card-header {
    display: flex;
    align-items: center;
    gap: 12rpx;
    padding-bottom: 20rpx;
    border-bottom: 1rpx solid #f5f5f5;
    margin-bottom: 20rpx;
}

.card-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #333333;
}

/* 联系信息 */
.contact-info {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.contact-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;
}

.contact-label {
    display: flex;
    align-items: center;
    gap: 8rpx;
    font-size: 28rpx;
    color: #666666;
    min-width: 160rpx;
}

.contact-value {
    flex: 1;
    font-size: 28rpx;
    color: #333333;
    text-align: right;

    &.address {
        text-align: right;
        line-height: 1.6;
    }
}

/* 服务列表 */
.service-list {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.service-item {
    padding: 20rpx;
    background: rgba(124, 58, 237, 0.03);
    border-radius: 16rpx;
    border: 1rpx solid rgba(124, 58, 237, 0.1);
}

.service-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12rpx;
}

.service-name {
    flex: 1;
    font-size: 30rpx;
    font-weight: 600;
    color: #333333;
}

.service-description {
    display: block;
    margin-top: 4rpx;
    font-size: 24rpx;
    line-height: 1.7;
    color: #64748b;
    white-space: pre-wrap;
    word-break: break-word;
}

.service-price {
    display: flex;
    align-items: baseline;
    gap: 4rpx;
    font-weight: 700;
}

.price-symbol {
    font-size: 24rpx;
}

.price-value {
    font-size: 32rpx;
}

.service-detail {
    display: flex;
    align-items: center;
    gap: 8rpx;
    font-size: 26rpx;
    color: #666666;
    margin-top: 8rpx;
}

.service-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 12rpx;
}

.meta-tag {
    padding: 6rpx 16rpx;
    border-radius: 24rpx;
    font-size: 24rpx;
    font-weight: 500;
}

.meta-quantity {
    font-size: 24rpx;
    color: #999999;
}

.addon-box {
    margin-top: 18rpx;
    padding: 18rpx;
    border-radius: 16rpx;
    background: rgba(14, 165, 233, 0.06);
}

.addon-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10rpx;
}

.addon-title {
    font-size: 26rpx;
    font-weight: 600;
    color: #0f172a;
}

.addon-total {
    font-size: 24rpx;
    font-weight: 600;
    color: #0ea5e9;
}

.addon-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 10rpx;
    font-size: 24rpx;
    color: #475569;
}

.addon-row:not(:first-of-type) {
    border-top: 1rpx solid rgba(14, 165, 233, 0.08);
    margin-top: 10rpx;
}

.addon-price {
    color: #0ea5e9;
    font-weight: 600;
}

/* 信息列表 */
.info-list {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12rpx 0;
}

.info-label {
    font-size: 28rpx;
    color: #666666;
}

.info-value {
    font-size: 28rpx;
    color: #333333;
    text-align: right;
}

/* 金额列表 */
.amount-card {
    margin-bottom: 0;
}

.amount-list {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.amount-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12rpx 0;

    &.total {
        padding-top: 20rpx;
        border-top: 2rpx solid #f5f5f5;
        margin-top: 8rpx;
    }
}

.amount-label {
    font-size: 28rpx;
    color: #666666;
}

.amount-value {
    font-size: 28rpx;
    color: #333333;

    &.discount {
        color: #ff2c3c;
    }
}

.total-value {
    display: flex;
    align-items: baseline;
    gap: 4rpx;
    font-weight: 700;
}

.price-number {
    font-size: 40rpx;
}

/* 空提示 */
.empty-tip {
    padding: 40rpx 0;
    text-align: center;
    font-size: 26rpx;
    color: #999999;
}

/* 底部操作 */
.action-wrapper {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 24rpx;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(20rpx);
    border-top: 1rpx solid #f5f5f5;
    z-index: 100;
}

.confirm-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    height: 72rpx;
    border-radius: 32rpx;
    font-size: 30rpx;
    font-weight: 600;
    box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.3);
    transition: all 0.2s ease;

    &:active {
        transform: translateY(2rpx);
        box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.3);
    }
}

/* 加载状态 */
.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    background: #F4F5F7;
    gap: 24rpx;
}

.loading-text {
    font-size: 28rpx;
    color: #999999;
}
</style>
