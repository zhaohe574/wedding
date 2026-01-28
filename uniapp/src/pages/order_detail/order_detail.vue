<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="订单详情"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="order-detail" v-if="order">
        <!-- 订单状态 -->
        <view class="status-banner" :style="getStatusStyle(order.order_status)">
            <view class="status-icon">
                <tn-icon :name="getStatusIcon(order.order_status)" size="48" color="#FFFFFF" />
            </view>
            <view class="status-text">
                <text class="status-title">{{ order.order_status_desc }}</text>
                <text class="status-desc" v-if="order.order_status === 0">
                    请在30分钟内完成支付
                </text>
            </view>
        </view>

        <!-- 联系信息 -->
        <view class="contact-card">
            <view class="contact-header">
                <tn-icon name="location-fill" size="32" :color="$theme.primaryColor" />
                <text class="contact-label">服务地址</text>
            </view>
            <view class="contact-info">
                <view class="contact-row">
                    <text class="contact-name">{{ order.contact_name }}</text>
                    <text class="contact-phone">{{ order.contact_mobile }}</text>
                </view>
                <view class="contact-address" v-if="order.service_address">
                    {{ order.service_address }}
                </view>
            </view>
        </view>

        <!-- 服务信息 -->
                <view class="service-card">
            <view class="card-header">
                <tn-icon name="list" size="32" :color="$theme.primaryColor" />
                <text class="card-title">服务项目</text>
            </view>
            <view class="service-groups">
                <view
                    v-for="group in groupedItems"
                    :key="group.key"
                    class="service-group"
                >
                    <view class="group-header">
                        <view class="staff-section">
                            <image
                                :src="group.staff?.avatar || group.staff_avatar || '/static/images/default-avatar.png'"
                                class="staff-avatar"
                                mode="aspectFill"
                            />
                            <view class="staff-info">
                                <text class="staff-name">{{ group.staff?.name || group.staff_name || '未知人员' }}</text>
                                <text class="staff-subtitle">{{ group.service_date }}</text>
                            </view>
                        </view>
                        <view class="group-total">
                            <text class="group-total-label">小计</text>
                            <text class="group-total-value" :style="{ color: $theme.ctaColor }">￥{{ group.total_price }}</text>
                        </view>
                    </view>

                    <view class="group-packages">
                        <view
                            v-for="pkg in group.packages"
                            :key="pkg.key"
                            class="package-group"
                        >
                            <view class="package-header">
                                <view class="package-title">
                                    <tn-icon name="gift" size="24" />
                                    <text>{{ pkg.package_name }}</text>
                                </view>
                                <text class="package-total">￥{{ pkg.total_price }}</text>
                            </view>
                            <view class="package-items">
                                <view
                                    v-for="item in pkg.items"
                                    :key="item.id"
                                    class="package-item"
                                >
                                    <view class="slot-main">
                                        <view class="slot-info">
                                            <view class="slot-row">
                                                <text class="slot-label">{{ getOrderTimeSlotLabel(item) }}</text>
                                                <text class="slot-price" :style="{ color: $theme.ctaColor }">￥{{ item.price }}</text>
                                            </view>
                                            <view class="slot-meta">
                                                <text class="slot-quantity">x{{ item.quantity }}</text>
                                            </view>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>

<view class="info-card">
            <view class="card-header">
                <tn-icon name="document" size="32" :color="$theme.primaryColor" />
                <text class="card-title">订单信息</text>
            </view>
            <view class="info-list">
                <view class="info-row">
                    <text class="info-label">订单编号</text>
                    <view class="info-value-row" @click="copyOrderSn">
                        <text class="info-value">{{ order.order_sn }}</text>
                        <tn-icon name="copy" size="28" color="#999999" />
                    </view>
                </view>
                <view class="info-row">
                    <text class="info-label">下单时间</text>
                    <text class="info-value">{{ order.create_time }}</text>
                </view>
                <view class="info-row" v-if="order.service_date">
                    <text class="info-label">服务日期</text>
                    <text class="info-value">{{ order.service_date }}</text>
                </view>
                <view class="info-row" v-if="order.wedding_date">
                    <text class="info-label">婚礼日期</text>
                    <text class="info-value">{{ order.wedding_date }}</text>
                </view>
                <view class="info-row" v-if="order.wedding_venue">
                    <text class="info-label">婚礼地点</text>
                    <text class="info-value">{{ order.wedding_venue }}</text>
                </view>
                <view class="info-row" v-if="order.pay_time">
                    <text class="info-label">支付时间</text>
                    <text class="info-value">{{ order.pay_time }}</text>
                </view>
                <view class="info-row" v-if="order.user_remark">
                    <text class="info-label">备注</text>
                    <text class="info-value remark">{{ order.user_remark }}</text>
                </view>
            </view>
        </view>

        <!-- 金额明细 -->
        <view class="amount-card">
            <view class="card-header">
                <tn-icon name="money" size="32" :color="$theme.primaryColor" />
                <text class="card-title">金额明细</text>
            </view>
            <view class="amount-list">
                <view class="amount-row">
                    <text class="amount-label">商品总额</text>
                    <text class="amount-value">¥{{ order.total_amount }}</text>
                </view>
                <view class="amount-row" v-if="order.discount_amount > 0">
                    <text class="amount-label">优惠金额</text>
                    <text class="amount-value discount">-¥{{ order.discount_amount }}</text>
                </view>
                <view class="amount-row" v-if="order.coupon_amount > 0">
                    <text class="amount-label">优惠券</text>
                    <text class="amount-value discount">-¥{{ order.coupon_amount }}</text>
                </view>
                <view class="amount-divider"></view>
                <view class="amount-row total">
                    <text class="amount-label">实付金额</text>
                    <text class="amount-value primary" :style="{ color: $theme.ctaColor }">
                        ¥{{ order.pay_amount }}
                    </text>
                </view>
                <view class="amount-row" v-if="order.deposit_amount > 0">
                    <text class="amount-label">定金</text>
                    <view class="amount-value-row">
                        <text class="amount-value">¥{{ order.deposit_amount }}</text>
                        <text 
                            class="amount-status" 
                            :style="{ 
                                color: order.deposit_paid ? '#19BE6B' : '#FF9900',
                                backgroundColor: order.deposit_paid ? 'rgba(25, 190, 107, 0.1)' : 'rgba(255, 153, 0, 0.1)'
                            }"
                        >
                            {{ order.deposit_paid ? '已付' : '待付' }}
                        </text>
                    </view>
                </view>
                <view class="amount-row" v-if="order.balance_amount > 0">
                    <text class="amount-label">尾款</text>
                    <view class="amount-value-row">
                        <text class="amount-value">¥{{ order.balance_amount }}</text>
                        <text 
                            class="amount-status"
                            :style="{ 
                                color: order.balance_paid ? '#19BE6B' : '#FF9900',
                                backgroundColor: order.balance_paid ? 'rgba(25, 190, 107, 0.1)' : 'rgba(255, 153, 0, 0.1)'
                            }"
                        >
                            {{ order.balance_paid ? '已付' : '待付' }}
                        </text>
                    </view>
                </view>
            </view>
        </view>

        <!-- 退款信息 -->
        <view class="refund-card" v-if="order.refund">
            <view class="card-header">
                <tn-icon name="refund" size="32" color="#FF2C3C" />
                <text class="card-title">退款信息</text>
            </view>
            <view class="refund-list">
                <view class="refund-row">
                    <text class="refund-label">退款状态</text>
                    <text 
                        class="refund-status" 
                        :style="{ 
                            color: getRefundStatusStyle(order.refund.refund_status).color,
                            backgroundColor: getRefundStatusStyle(order.refund.refund_status).bg
                        }"
                    >
                        {{ order.refund.refund_status_desc }}
                    </text>
                </view>
                <view class="refund-row">
                    <text class="refund-label">退款金额</text>
                    <text class="refund-value">¥{{ order.refund.refund_amount }}</text>
                </view>
                <view class="refund-row">
                    <text class="refund-label">退款原因</text>
                    <text class="refund-reason">{{ order.refund.refund_reason }}</text>
                </view>
            </view>
        </view>

        <!-- 底部按钮 -->
        <view class="action-bar">
            <view class="action-buttons">
                <view 
                    v-if="order.order_status === 0" 
                    class="btn-secondary" 
                    :style="{ 
                        borderColor: $theme.primaryColor,
                        color: $theme.primaryColor
                    }"
                    @click="handleCancel"
                >
                    <text>取消订单</text>
                </view>
                <view 
                    v-if="order.order_status === 0" 
                    class="btn-primary"
                    :style="{ 
                        background: `linear-gradient(135deg, ${$theme.ctaColor} 0%, ${$theme.ctaColor} 100%)`,
                        color: $theme.btnColor
                    }"
                    @click="handlePay"
                >
                    <tn-icon name="wallet-fill" size="32" :color="$theme.btnColor" />
                    <text>立即支付 ¥{{ needPayAmount }}</text>
                </view>
                <view 
                    v-if="order.order_status === 2" 
                    class="btn-primary"
                    :style="{ 
                        background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                        color: $theme.btnColor
                    }"
                    @click="handleConfirm"
                >
                    <tn-icon name="check-circle-fill" size="32" :color="$theme.btnColor" />
                    <text>确认完成</text>
                </view>
                <view
                    v-if="order.order_status === 1 && !order.refund"
                    class="btn-secondary"
                    :style="{ 
                        borderColor: '#FF2C3C',
                        color: '#FF2C3C'
                    }"
                    @click="showRefundPopup = true"
                >
                    <text>申请退款</text>
                </view>
                <view
                    v-if="[3, 4, 5].includes(order.order_status)"
                    class="btn-secondary"
                    :style="{ 
                        borderColor: '#999999',
                        color: '#999999'
                    }"
                    @click="handleDelete"
                >
                    <text>删除订单</text>
                </view>
            </view>
        </view>

        <!-- 退款弹窗 -->
        <tn-popup v-model="showRefundPopup" mode="bottom" border-radius="32">
            <view class="refund-popup">
                <view class="popup-header">
                    <text class="popup-title">申请退款</text>
                    <tn-icon 
                        name="close" 
                        size="40" 
                        color="#999999" 
                        @click="showRefundPopup = false"
                    />
                </view>
                <view class="popup-content">
                    <view class="form-item">
                        <text class="form-label">退款金额</text>
                        <tn-input
                            v-model="refundForm.amount"
                            type="number"
                            :placeholder="`最多可退 ¥${order.pay_amount}`"
                            border
                        />
                    </view>
                    <view class="form-item">
                        <text class="form-label">退款原因</text>
                        <tn-input
                            v-model="refundForm.reason"
                            type="textarea"
                            placeholder="请输入退款原因"
                            :maxlength="200"
                            border
                            height="200"
                        />
                    </view>
                </view>
                <view class="popup-actions">
                    <view 
                        class="popup-btn cancel"
                        :style="{ 
                            borderColor: $theme.primaryColor,
                            color: $theme.primaryColor
                        }"
                        @click="showRefundPopup = false"
                    >
                        <text>取消</text>
                    </view>
                    <view 
                        class="popup-btn confirm"
                        :style="{ 
                            background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                            color: $theme.btnColor
                        }"
                        @click="submitRefund"
                    >
                        <text>提交申请</text>
                    </view>
                </view>
            </view>
        </tn-popup>

        <view class="safe-bottom"></view>
    </view>
    <view v-else class="loading-container">
        <tn-loading mode="circle" />
        <text class="loading-text">加载中...</text>
    </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { useThemeStore } from '@/stores/theme'
import {
    getOrderDetail,
    cancelOrder,
    confirmOrder,
    deleteOrder,
    applyRefund,
    orderPay
} from '@/api/order'

const $theme = useThemeStore()
const orderId = ref(0)
const order = ref<any>(null)
const showRefundPopup = ref(false)
const refundForm = reactive({
    amount: '',
    reason: ''
})

const needPayAmount = computed(() => {
    if (!order.value) return 0
    if (order.value.deposit_amount > 0) {
        if (!order.value.deposit_paid) return order.value.deposit_amount
        if (!order.value.balance_paid) return order.value.balance_amount
    }
    return order.value.pay_amount
})

const groupedItems = computed(() => {
    const items = order.value?.items || []
    const groups: any[] = []
    const groupMap = new Map<string, any>()

    items.forEach((item: any) => {
        const staffId = item.staff_id ?? item.staff?.id ?? 'unknown'
        const serviceDate = item.service_date || item.schedule_date || item.date || ''
        const key = `${staffId}-${serviceDate}`
        let group = groupMap.get(key)
        if (!group) {
            group = {
                key,
                staff: item.staff,
                staff_name: item.staff_name,
                staff_avatar: item.staff_avatar,
                service_date: serviceDate,
                packages: [],
                total_price: 0,
                packageMap: new Map<string, any>()
            }
            groupMap.set(key, group)
            groups.push(group)
        }

        const itemTotal = Number(item.price || 0) * Number(item.quantity || 1)
        group.total_price += itemTotal

        const pkgKey = String(item.package_id ?? item.package?.id ?? item.package_name ?? 'unknown')
        let pkgGroup = group.packageMap.get(pkgKey)
        if (!pkgGroup) {
            pkgGroup = {
                key: `${key}-${pkgKey}`,
                package_name: item.package?.name || item.package_name || '未命名套餐',
                items: [],
                total_price: 0
            }
            group.packageMap.set(pkgKey, pkgGroup)
            group.packages.push(pkgGroup)
        }

        pkgGroup.items.push(item)
        pkgGroup.total_price += itemTotal
    })

    groups.forEach((group) => {
        group.packages.forEach((pkg: any) => {
            pkg.items.sort((a: any, b: any) => Number(a.time_slot || 0) - Number(b.time_slot || 0))
        })
        delete group.packageMap
    })

    return groups
})

const getOrderTimeSlotLabel = (item: any) => {
    if (item?.time_slot_desc) {
        return item.time_slot_desc
    }
    if (item?.service_time) {
        return item.service_time
    }
    if (item?.service_date) {
        return item.service_date
    }
    const map: Record<number, string> = {
        0: '全天',
        1: '早档',
        2: '午档',
        3: '晚档'
    }
    const slot = Number(item?.time_slot)
    return Number.isFinite(slot) ? (map[slot] || '未知场次') : '未知场次'
}

// 获取订单状态样式
const getStatusStyle = (status: number) => {
    const styles: Record<number, { background: string }> = {
        0: { background: 'linear-gradient(135deg, #FF9900 0%, #FF7700 100%)' }, // 待支付 - 警告色
        1: { background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)` }, // 待确认 - 主色
        2: { background: `linear-gradient(135deg, ${$theme.secondaryColor} 0%, ${$theme.secondaryColor} 100%)` }, // 服务中 - 辅助色
        3: { background: 'linear-gradient(135deg, #19BE6B 0%, #0FA958 100%)' }, // 已完成 - 成功色
        4: { background: 'linear-gradient(135deg, #999999 0%, #666666 100%)' }, // 已取消 - 灰色
        5: { background: 'linear-gradient(135deg, #FF2C3C 0%, #E6192A 100%)' }  // 已退款 - 错误色
    }
    return styles[status] || styles[4]
}

// 获取订单状态图标
const getStatusIcon = (status: number) => {
    const icons: Record<number, string> = {
        0: 'wallet-fill',      // 待支付
        1: 'time-fill',        // 待确认
        2: 'loading',          // 服务中
        3: 'check-circle-fill', // 已完成
        4: 'close-circle-fill', // 已取消
        5: 'refund'            // 已退款
    }
    return icons[status] || 'document'
}

// 获取退款状态样式
const getRefundStatusStyle = (status: number) => {
    const styles: Record<number, { color: string; bg: string }> = {
        0: { color: '#FF9900', bg: 'rgba(255, 153, 0, 0.1)' },   // 待审核
        1: { color: '#3574FF', bg: 'rgba(53, 116, 255, 0.1)' },  // 审核中
        2: { color: '#7C4DFF', bg: 'rgba(124, 77, 255, 0.1)' },  // 退款中
        3: { color: '#19BE6B', bg: 'rgba(25, 190, 107, 0.1)' },  // 已退款
        4: { color: '#FF2C3C', bg: 'rgba(255, 44, 60, 0.1)' }    // 已拒绝
    }
    return styles[status] || { color: '#999999', bg: 'rgba(153, 153, 153, 0.1)' }
}

const fetchDetail = async () => {
    try {
        const res = await getOrderDetail({ id: orderId.value })
        order.value = res
    } catch (e: any) {
        uni.showToast({ title: e.message || '加载失败', icon: 'none' })
    }
}

const copyOrderSn = () => {
    uni.setClipboardData({
        data: order.value.order_sn,
        success: () => {
            uni.showToast({ title: '已复制订单编号', icon: 'success' })
        }
    })
}

const handlePay = async () => {
    try {
        const payType = !order.value.deposit_paid ? 1 : !order.value.balance_paid ? 2 : 3
        const res = await orderPay({ id: orderId.value, pay_way: 1, pay_type: payType })
        // 调用微信支付
        // @ts-ignore
        if (res.data?.pay_params) {
            // @ts-ignore
            uni.requestPayment({
                ...res.data.pay_params,
                success: () => {
                    uni.showToast({ title: '支付成功', icon: 'success' })
                    fetchDetail()
                },
                fail: () => {
                    uni.showToast({ title: '支付取消', icon: 'none' })
                }
            })
        }
    } catch (e: any) {
        uni.showToast({ title: e.message || '支付失败', icon: 'none' })
    }
}

const handleCancel = async () => {
    const res = await uni.showModal({
        title: '提示',
        content: '确定要取消该订单吗？'
    })
    if (res.confirm) {
        try {
            await cancelOrder({ id: orderId.value, reason: '用户取消' })
            uni.showToast({ title: '订单已取消', icon: 'success' })
            fetchDetail()
        } catch (e: any) {
            uni.showToast({ title: e.message || '操作失败', icon: 'none' })
        }
    }
}

const handleConfirm = async () => {
    const res = await uni.showModal({
        title: '提示',
        content: '确定服务已完成吗？'
    })
    if (res.confirm) {
        try {
            await confirmOrder({ id: orderId.value })
            uni.showToast({ title: '订单已完成', icon: 'success' })
            fetchDetail()
        } catch (e: any) {
            uni.showToast({ title: e.message || '操作失败', icon: 'none' })
        }
    }
}

const handleDelete = async () => {
    const res = await uni.showModal({
        title: '提示',
        content: '确定要删除该订单吗？'
    })
    if (res.confirm) {
        try {
            await deleteOrder({ id: orderId.value })
            uni.showToast({ title: '删除成功', icon: 'success' })
            setTimeout(() => {
                uni.navigateBack()
            }, 1500)
        } catch (e: any) {
            uni.showToast({ title: e.message || '操作失败', icon: 'none' })
        }
    }
}

const submitRefund = async () => {
    if (!refundForm.amount || Number(refundForm.amount) <= 0) {
        uni.showToast({ title: '请输入退款金额', icon: 'none' })
        return
    }
    if (Number(refundForm.amount) > order.value.pay_amount) {
        uni.showToast({ title: '退款金额不能超过实付金额', icon: 'none' })
        return
    }
    if (!refundForm.reason.trim()) {
        uni.showToast({ title: '请输入退款原因', icon: 'none' })
        return
    }
    try {
        await applyRefund({
            id: orderId.value,
            amount: Number(refundForm.amount),
            reason: refundForm.reason
        })
        uni.showToast({ title: '申请已提交', icon: 'success' })
        showRefundPopup.value = false
        refundForm.amount = ''
        refundForm.reason = ''
        fetchDetail()
    } catch (e: any) {
        uni.showToast({ title: e.message || '申请失败', icon: 'none' })
    }
}

onLoad((options: any) => {
    orderId.value = Number(options.id)
    fetchDetail()
})
</script>

<style lang="scss" scoped>
.order-detail {
    min-height: 100vh;
    background-color: #F6F6F6;
    padding-bottom: calc(env(safe-area-inset-bottom) + 160rpx);
}

// 订单状态横幅
.status-banner {
    padding: 80rpx 48rpx 100rpx;
    display: flex;
    align-items: center;
    gap: 24rpx;
    
    .status-icon {
        flex-shrink: 0;
    }
    
    .status-text {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8rpx;
    }
    
    .status-title {
        font-size: 40rpx;
        font-weight: 700;
        color: #FFFFFF;
        line-height: 1.4;
    }
    
    .status-desc {
        font-size: 26rpx;
        color: rgba(255, 255, 255, 0.85);
        line-height: 1.5;
    }
}

// 联系信息卡片
.contact-card {
    background: #FFFFFF;
    margin: -48rpx 24rpx 24rpx;
    padding: 24rpx;
    border-radius: 16rpx;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);
    position: relative;
    z-index: 10;
    
    .contact-header {
        display: flex;
        align-items: center;
        gap: 12rpx;
        margin-bottom: 16rpx;
    }
    
    .contact-label {
        font-size: 28rpx;
        font-weight: 600;
        color: #333333;
    }
    
    .contact-info {
        padding-left: 44rpx;
    }
    
    .contact-row {
        display: flex;
        align-items: center;
        gap: 24rpx;
        margin-bottom: 8rpx;
    }
    
    .contact-name {
        font-size: 32rpx;
        font-weight: 600;
        color: #333333;
    }
    
    .contact-phone {
        font-size: 28rpx;
        color: #666666;
    }
    
    .contact-address {
        font-size: 26rpx;
        color: #999999;
        line-height: 1.6;
    }
}

// 通用卡片样式
.service-card,
.info-card,
.amount-card,
.refund-card {
    background: #FFFFFF;
    margin: 0 24rpx 24rpx;
    border-radius: 16rpx;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

// 卡片头部
.card-header {
    display: flex;
    align-items: center;
    gap: 12rpx;
    padding: 24rpx;
    border-bottom: 1rpx solid #F5F5F5;
}

.card-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #333333;
}

// 服务项目
.service-groups {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    padding: 0 24rpx 24rpx;
}

.service-group {
    background: #F9FAFB;
    border-radius: 16rpx;
    padding: 20rpx;
    border: 1rpx solid #F0F0F0;
}

.group-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16rpx;
}

.staff-section {
    display: flex;
    align-items: center;
    gap: 16rpx;
}

.staff-avatar {
    width: 88rpx;
    height: 88rpx;
    border-radius: 16rpx;
}

.staff-info {
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.staff-name {
    font-size: 30rpx;
    font-weight: 600;
    color: #333333;
}

.staff-subtitle {
    font-size: 24rpx;
    color: #999999;
}

.group-total {
    text-align: right;
}

.group-total-label {
    display: block;
    font-size: 22rpx;
    color: #999999;
}

.group-total-value {
    font-size: 30rpx;
    font-weight: 700;
}

.group-packages {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.package-group {
    background: #FFFFFF;
    border-radius: 12rpx;
    padding: 16rpx;
    border: 1rpx solid #EEEEEE;
}

.package-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12rpx;
}

.package-title {
    display: flex;
    align-items: center;
    gap: 8rpx;
    font-size: 26rpx;
    font-weight: 600;
    color: #333333;
}

.package-total {
    font-size: 26rpx;
    font-weight: 600;
    color: #333333;
}

.package-items {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.package-item {
    padding: 12rpx 0;
    border-top: 1rpx solid #F5F5F5;

    &:first-child {
        border-top: none;
        padding-top: 0;
    }
}

.slot-main {
    display: flex;
    align-items: center;
}

.slot-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.slot-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.slot-label {
    font-size: 26rpx;
    font-weight: 500;
    color: #333333;
}

.slot-price {
    font-size: 26rpx;
    font-weight: 600;
}

.slot-meta {
    display: flex;
    justify-content: flex-end;
}

.slot-quantity {
    font-size: 24rpx;
    color: #999999;
}

// 信息列表
.info-list {
    padding: 0 24rpx 12rpx;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 16rpx 0;
    gap: 24rpx;
}

.info-label {
    font-size: 28rpx;
    color: #999999;
    flex-shrink: 0;
}

.info-value {
    font-size: 28rpx;
    color: #333333;
    text-align: right;
    flex: 1;
    
    &.remark {
        line-height: 1.6;
    }
}

.info-value-row {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

// 金额明细
.amount-list {
    padding: 0 24rpx 12rpx;
}

.amount-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16rpx 0;
    
    &.total {
        padding-top: 24rpx;
    }
}

.amount-label {
    font-size: 28rpx;
    color: #666666;
}

.amount-value {
    font-size: 28rpx;
    color: #333333;
    font-weight: 500;
    
    &.discount {
        color: #FF2C3C;
    }
    
    &.primary {
        font-size: 44rpx;
        font-weight: 700;
    }
}

.amount-value-row {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.amount-status {
    padding: 4rpx 12rpx;
    border-radius: 8rpx;
    font-size: 24rpx;
    font-weight: 500;
}

.amount-divider {
    height: 1rpx;
    background: #F5F5F5;
    margin: 12rpx 0;
}

// 退款信息
.refund-list {
    padding: 0 24rpx 12rpx;
}

.refund-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 16rpx 0;
    gap: 24rpx;
}

.refund-label {
    font-size: 28rpx;
    color: #999999;
    flex-shrink: 0;
}

.refund-status {
    padding: 6rpx 16rpx;
    border-radius: 12rpx;
    font-size: 26rpx;
    font-weight: 500;
}

.refund-value {
    font-size: 28rpx;
    color: #333333;
    font-weight: 600;
}

.refund-reason {
    font-size: 28rpx;
    color: #666666;
    text-align: right;
    flex: 1;
    line-height: 1.6;
}

// 底部操作栏
.action-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20rpx);
    padding: 24rpx;
    padding-bottom: calc(env(safe-area-inset-bottom) + 24rpx);
    box-shadow: 0 -2rpx 16rpx rgba(0, 0, 0, 0.08);
    z-index: 100;
}

.action-buttons {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 24rpx;
}

.btn-primary,
.btn-secondary {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    padding: 24rpx 48rpx;
    border-radius: 48rpx;
    font-size: 28rpx;
    font-weight: 600;
    transition: all 0.2s ease;
    
    &:active {
        transform: translateY(2rpx);
        opacity: 0.9;
    }
}

.btn-primary {
    box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.15);
}

.btn-secondary {
    background: transparent;
    border: 2rpx solid;
}

// 退款弹窗
.refund-popup {
    padding: 32rpx;
}

.popup-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32rpx;
}

.popup-title {
    font-size: 36rpx;
    font-weight: 700;
    color: #333333;
}

.popup-content {
    margin-bottom: 32rpx;
}

.form-item {
    margin-bottom: 32rpx;
    
    &:last-child {
        margin-bottom: 0;
    }
}

.form-label {
    display: block;
    font-size: 28rpx;
    color: #666666;
    margin-bottom: 16rpx;
}

.popup-actions {
    display: flex;
    gap: 24rpx;
}

.popup-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 28rpx;
    border-radius: 48rpx;
    font-size: 32rpx;
    font-weight: 600;
    transition: all 0.2s ease;
    
    &:active {
        transform: translateY(2rpx);
        opacity: 0.9;
    }
    
    &.cancel {
        background: transparent;
        border: 2rpx solid;
    }
    
    &.confirm {
        box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.15);
    }
}

// 加载状态
.loading-container {
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

// 安全区域
.safe-bottom {
    height: calc(env(safe-area-inset-bottom) + 160rpx);
}
</style>
