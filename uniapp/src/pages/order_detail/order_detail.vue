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
        <view class="status-banner" :class="getStatusBg(order.order_status)">
            <view class="text-xl font-bold">{{ order.order_status_desc }}</view>
            <view class="text-sm mt-2 opacity-80" v-if="order.order_status === 0">
                请在30分钟内完成支付
            </view>
        </view>

        <!-- 联系信息 -->
        <view class="bg-white mx-3 -mt-6 rounded-lg p-4 relative z-10">
            <view class="flex items-center">
                <image src="/static/images/icon-location.png" class="w-5 h-5 mr-2" mode="aspectFit" />
                <view class="flex-1">
                    <view class="flex items-center">
                        <text class="font-medium">{{ order.contact_name }}</text>
                        <text class="ml-3 text-gray-500">{{ order.contact_mobile }}</text>
                    </view>
                    <view class="text-sm text-gray-400 mt-1" v-if="order.service_address">
                        {{ order.service_address }}
                    </view>
                </view>
            </view>
        </view>

        <!-- 服务信息 -->
        <view class="bg-white mx-3 mt-3 rounded-lg overflow-hidden">
            <view class="px-4 py-3 border-b border-gray-100 font-medium">服务项目</view>
            <view 
                v-for="item in order.items" 
                :key="item.id"
                class="flex items-center p-4 border-b border-gray-100 last:border-0"
            >
                <image 
                    :src="item.staff?.avatar || '/static/images/default-avatar.png'" 
                    class="w-20 h-20 rounded-lg mr-3"
                    mode="aspectFill"
                />
                <view class="flex-1">
                    <view class="font-medium">{{ item.staff_name }}</view>
                    <view class="text-sm text-gray-400 mt-1">{{ item.package_name }}</view>
                    <view class="text-sm text-gray-400">服务日期: {{ item.service_date }}</view>
                </view>
                <view class="text-right">
                    <view class="text-primary font-medium">¥{{ item.price }}</view>
                    <view class="text-xs text-gray-400">x{{ item.quantity }}</view>
                </view>
            </view>
        </view>

        <!-- 订单信息 -->
        <view class="bg-white mx-3 mt-3 rounded-lg p-4">
            <view class="font-medium mb-3">订单信息</view>
            <view class="info-item">
                <text class="label">订单编号</text>
                <text class="value" @click="copyOrderSn">{{ order.order_sn }}</text>
            </view>
            <view class="info-item">
                <text class="label">下单时间</text>
                <text class="value">{{ order.create_time }}</text>
            </view>
            <view class="info-item" v-if="order.service_date">
                <text class="label">服务日期</text>
                <text class="value">{{ order.service_date }}</text>
            </view>
            <view class="info-item" v-if="order.wedding_date">
                <text class="label">婚礼日期</text>
                <text class="value">{{ order.wedding_date }}</text>
            </view>
            <view class="info-item" v-if="order.wedding_venue">
                <text class="label">婚礼地点</text>
                <text class="value">{{ order.wedding_venue }}</text>
            </view>
            <view class="info-item" v-if="order.pay_time">
                <text class="label">支付时间</text>
                <text class="value">{{ order.pay_time }}</text>
            </view>
            <view class="info-item" v-if="order.user_remark">
                <text class="label">备注</text>
                <text class="value">{{ order.user_remark }}</text>
            </view>
        </view>

        <!-- 金额明细 -->
        <view class="bg-white mx-3 mt-3 rounded-lg p-4">
            <view class="font-medium mb-3">金额明细</view>
            <view class="info-item">
                <text class="label">商品总额</text>
                <text class="value">¥{{ order.total_amount }}</text>
            </view>
            <view class="info-item" v-if="order.discount_amount > 0">
                <text class="label">优惠金额</text>
                <text class="value text-red-500">-¥{{ order.discount_amount }}</text>
            </view>
            <view class="info-item" v-if="order.coupon_amount > 0">
                <text class="label">优惠券</text>
                <text class="value text-red-500">-¥{{ order.coupon_amount }}</text>
            </view>
            <view class="info-item font-medium">
                <text class="label">实付金额</text>
                <text class="value text-primary text-lg">¥{{ order.pay_amount }}</text>
            </view>
            <view class="info-item" v-if="order.deposit_amount > 0">
                <text class="label">定金</text>
                <text class="value">
                    ¥{{ order.deposit_amount }}
                    <text :class="order.deposit_paid ? 'text-green-500' : 'text-orange-500'">
                        ({{ order.deposit_paid ? '已付' : '待付' }})
                    </text>
                </text>
            </view>
            <view class="info-item" v-if="order.balance_amount > 0">
                <text class="label">尾款</text>
                <text class="value">
                    ¥{{ order.balance_amount }}
                    <text :class="order.balance_paid ? 'text-green-500' : 'text-orange-500'">
                        ({{ order.balance_paid ? '已付' : '待付' }})
                    </text>
                </text>
            </view>
        </view>

        <!-- 退款信息 -->
        <view class="bg-white mx-3 mt-3 rounded-lg p-4" v-if="order.refund">
            <view class="font-medium mb-3">退款信息</view>
            <view class="info-item">
                <text class="label">退款状态</text>
                <text class="value" :class="getRefundStatusColor(order.refund.refund_status)">
                    {{ order.refund.refund_status_desc }}
                </text>
            </view>
            <view class="info-item">
                <text class="label">退款金额</text>
                <text class="value">¥{{ order.refund.refund_amount }}</text>
            </view>
            <view class="info-item">
                <text class="label">退款原因</text>
                <text class="value">{{ order.refund.refund_reason }}</text>
            </view>
        </view>

        <!-- 底部按钮 -->
        <view class="fixed-bottom bg-white px-4 py-3 flex justify-end gap-3">
            <button 
                v-if="order.order_status === 0"
                class="btn-outline"
                @click="handleCancel"
            >取消订单</button>
            <button 
                v-if="order.order_status === 0"
                class="btn-primary"
                @click="handlePay"
            >立即支付 ¥{{ needPayAmount }}</button>
            <button 
                v-if="order.order_status === 2"
                class="btn-primary"
                @click="handleConfirm"
            >确认完成</button>
            <button 
                v-if="order.order_status === 1 && !order.refund"
                class="btn-outline"
                @click="showRefundPopup = true"
            >申请退款</button>
            <button 
                v-if="[3, 4, 5].includes(order.order_status)"
                class="btn-outline"
                @click="handleDelete"
            >删除订单</button>
        </view>

        <!-- 退款弹窗 -->
        <u-popup v-model="showRefundPopup" mode="bottom" border-radius="20">
            <view class="p-4">
                <view class="text-center font-medium text-lg mb-4">申请退款</view>
                <view class="mb-4">
                    <view class="text-sm text-gray-500 mb-2">退款金额</view>
                    <u-input 
                        v-model="refundForm.amount" 
                        type="number" 
                        :placeholder="`最多可退 ¥${order.pay_amount}`"
                    />
                </view>
                <view class="mb-4">
                    <view class="text-sm text-gray-500 mb-2">退款原因</view>
                    <u-input 
                        v-model="refundForm.reason" 
                        type="textarea" 
                        placeholder="请输入退款原因"
                        :maxlength="200"
                    />
                </view>
                <view class="flex gap-3">
                    <button class="btn-outline flex-1" @click="showRefundPopup = false">取消</button>
                    <button class="btn-primary flex-1" @click="submitRefund">提交申请</button>
                </view>
            </view>
        </u-popup>

        <view class="h-20"></view>
    </view>
    <view v-else class="py-20 text-center text-gray-400">
        加载中...
    </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getOrderDetail, cancelOrder, confirmOrder, deleteOrder, applyRefund, orderPay } from '@/api/order'

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

const getStatusBg = (status: number) => {
    const bgs: Record<number, string> = {
        0: 'bg-orange',
        1: 'bg-blue',
        2: 'bg-purple',
        3: 'bg-green',
        4: 'bg-gray',
        5: 'bg-red'
    }
    return bgs[status] || 'bg-gray'
}

const getRefundStatusColor = (status: number) => {
    const colors: Record<number, string> = {
        0: 'text-orange-500',
        1: 'text-blue-500',
        2: 'text-purple-500',
        3: 'text-green-500',
        4: 'text-red-500'
    }
    return colors[status] || 'text-gray-500'
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
            uni.showToast({ title: '已复制' })
        }
    })
}

const handlePay = async () => {
    try {
        const payType = !order.value.deposit_paid ? 1 : (!order.value.balance_paid ? 2 : 3)
        const res = await orderPay({ id: orderId.value, pay_way: 1, pay_type: payType })
        // 调用微信支付
        // @ts-ignore
        if (res.data?.pay_params) {
            // @ts-ignore
            uni.requestPayment({
                ...res.data.pay_params,
                success: () => {
                    uni.showToast({ title: '支付成功' })
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
            uni.showToast({ title: '订单已取消' })
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
            uni.showToast({ title: '订单已完成' })
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
            uni.showToast({ title: '删除成功' })
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
        uni.showToast({ title: '申请已提交' })
        showRefundPopup.value = false
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
    background-color: #f5f5f5;
    padding-bottom: env(safe-area-inset-bottom);
}

.status-banner {
    padding: 60rpx 30rpx 80rpx;
    color: #fff;

    &.bg-orange { background: linear-gradient(135deg, #ff9a56 0%, #ff6b35 100%); }
    &.bg-blue { background: linear-gradient(135deg, #56a4ff 0%, #3574ff 100%); }
    &.bg-purple { background: linear-gradient(135deg, #a56bff 0%, #7c4dff 100%); }
    &.bg-green { background: linear-gradient(135deg, #56c596 0%, #34a853 100%); }
    &.bg-gray { background: linear-gradient(135deg, #999 0%, #666 100%); }
    &.bg-red { background: linear-gradient(135deg, #ff6b6b 0%, #ee4d4d 100%); }
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16rpx 0;

    .label {
        color: #999;
        font-size: 28rpx;
    }

    .value {
        color: #333;
        font-size: 28rpx;
    }
}

.fixed-bottom {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding-bottom: calc(env(safe-area-inset-bottom) + 24rpx);
    box-shadow: 0 -2rpx 10rpx rgba(0, 0, 0, 0.05);
}

.btn-outline {
    padding: 16rpx 32rpx;
    font-size: 28rpx;
    border: 1rpx solid #ddd;
    border-radius: 40rpx;
    background: #fff;
    color: #666;
}

.btn-primary {
    padding: 16rpx 32rpx;
    font-size: 28rpx;
    border: none;
    border-radius: 40rpx;
    background: var(--primary-color, #ff6b35);
    color: #fff;
}
</style>
