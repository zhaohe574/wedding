<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="申请转让"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="apply-page">
        <!-- 提示信息 -->
        <view class="tip-card">
            <uni-icons type="info-filled" size="18" color="#ff9500"></uni-icons>
            <text class="tip-text">转让后，订单将完全归属于接收方，原订单服务及权益一并转移。</text>
        </view>

        <!-- 订单信息 -->
        <view class="bg-white mt-3 p-4" v-if="orderInfo">
            <view class="section-title">转让订单</view>
            <view class="order-card">
                <view class="text-sm text-gray-500">订单号: {{ orderInfo.order_sn }}</view>
                <view class="flex items-center mt-2" v-if="orderInfo.items && orderInfo.items.length > 0">
                    <image 
                        :src="orderInfo.items[0].staff?.avatar || '/static/images/default-avatar.png'" 
                        class="w-12 h-12 rounded-lg mr-3"
                        mode="aspectFill"
                    />
                    <view class="flex-1">
                        <view class="text-sm font-medium">{{ orderInfo.items[0].staff_name }}</view>
                        <view class="text-xs text-gray-400">{{ orderInfo.items[0].package_name }}</view>
                    </view>
                    <view class="text-right">
                        <view class="text-primary font-bold">¥{{ orderInfo.pay_amount }}</view>
                        <view class="text-xs text-gray-400">{{ orderInfo.service_date }}</view>
                    </view>
                </view>
            </view>
        </view>

        <!-- 接收方信息 -->
        <view class="bg-white mt-3 p-4">
            <view class="section-title">接收方信息</view>
            <view class="form-item">
                <text class="label required">接收方姓名</text>
                <input 
                    v-model="formData.to_user_name" 
                    class="input"
                    placeholder="请输入接收方姓名"
                    maxlength="20"
                />
            </view>
            <view class="form-item">
                <text class="label required">接收方手机号</text>
                <input 
                    v-model="formData.to_user_mobile" 
                    class="input"
                    type="number"
                    placeholder="请输入接收方手机号"
                    maxlength="11"
                />
            </view>
        </view>

        <!-- 转让原因 -->
        <view class="bg-white mt-3 p-4">
            <view class="section-title">转让原因</view>
            <textarea 
                v-model="formData.reason" 
                class="reason-input"
                placeholder="请填写转让原因（选填）"
                maxlength="200"
            />
            <view class="text-right text-xs text-gray-400">{{ formData.reason.length }}/200</view>
        </view>

        <!-- 转让流程说明 -->
        <view class="bg-white mt-3 p-4">
            <view class="section-title">转让流程</view>
            <view class="flow-steps">
                <view class="flow-step">
                    <view class="step-num">1</view>
                    <view class="step-content">
                        <view class="step-title">提交申请</view>
                        <view class="step-desc">填写接收方信息并提交</view>
                    </view>
                </view>
                <view class="flow-step">
                    <view class="step-num">2</view>
                    <view class="step-content">
                        <view class="step-title">平台审核</view>
                        <view class="step-desc">等待平台审核通过</view>
                    </view>
                </view>
                <view class="flow-step">
                    <view class="step-num">3</view>
                    <view class="step-content">
                        <view class="step-title">接收方确认</view>
                        <view class="step-desc">接收方通过短信验证码确认</view>
                    </view>
                </view>
                <view class="flow-step">
                    <view class="step-num">4</view>
                    <view class="step-content">
                        <view class="step-title">转让完成</view>
                        <view class="step-desc">订单归属变更完成</view>
                    </view>
                </view>
            </view>
        </view>

        <!-- 提交按钮 -->
        <view class="bottom-actions">
            <button class="btn-submit" :disabled="submitting" @click="handleSubmit">
                {{ submitting ? '提交中...' : '提交转让申请' }}
            </button>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { applyTransfer, checkCanChange } from '@/api/orderChange'
import { getOrderDetail } from '@/api/order'

const orderId = ref(0)
const orderInfo = ref<any>(null)
const submitting = ref(false)

const formData = reactive({
    to_user_name: '',
    to_user_mobile: '',
    reason: ''
})

const fetchOrderInfo = async () => {
    try {
        const res = await getOrderDetail({ id: orderId.value })
        orderInfo.value = res
    } catch (e) {
        console.error(e)
    }
}

const checkOrder = async () => {
    try {
        const res = await checkCanChange({ order_id: orderId.value })
        if (!res.can_change) {
            uni.showModal({
                title: '提示',
                content: res.message,
                showCancel: false,
                success: () => {
                    uni.navigateBack()
                }
            })
        }
    } catch (e: any) {
        uni.showToast({ title: e.message || '检查失败', icon: 'none' })
    }
}

const validateMobile = (mobile: string) => {
    return /^1[3-9]\d{9}$/.test(mobile)
}

const handleSubmit = async () => {
    if (!formData.to_user_name.trim()) {
        uni.showToast({ title: '请输入接收方姓名', icon: 'none' })
        return
    }
    if (!formData.to_user_mobile.trim()) {
        uni.showToast({ title: '请输入接收方手机号', icon: 'none' })
        return
    }
    if (!validateMobile(formData.to_user_mobile)) {
        uni.showToast({ title: '手机号格式不正确', icon: 'none' })
        return
    }

    const confirmRes = await uni.showModal({
        title: '确认转让',
        content: `确定将订单转让给 ${formData.to_user_name}（${formData.to_user_mobile}）吗？转让后订单将完全归属于对方。`
    })
    if (!confirmRes.confirm) return

    submitting.value = true
    try {
        const res = await applyTransfer({
            order_id: orderId.value,
            to_user_name: formData.to_user_name,
            to_user_mobile: formData.to_user_mobile,
            reason: formData.reason
        })
        uni.showToast({ title: '申请已提交' })
        setTimeout(() => {
            uni.redirectTo({ url: `/pages/order_change/transfer_detail?id=${res.transfer_id}` })
        }, 1500)
    } catch (e: any) {
        uni.showToast({ title: e.message || '提交失败', icon: 'none' })
    } finally {
        submitting.value = false
    }
}

onLoad((options: any) => {
    if (options.order_id) {
        orderId.value = Number(options.order_id)
        fetchOrderInfo()
        checkOrder()
    }
})
</script>

<style lang="scss" scoped>
.apply-page {
    min-height: 100vh;
    background-color: #f5f5f5;
    padding-bottom: 140rpx;
}

.tip-card {
    display: flex;
    align-items: flex-start;
    padding: 24rpx 30rpx;
    background: #fffbe6;
    gap: 12rpx;
    
    .tip-text {
        flex: 1;
        font-size: 24rpx;
        color: #ff9500;
        line-height: 1.5;
    }
}

.section-title {
    font-size: 28rpx;
    font-weight: bold;
    color: #333;
    margin-bottom: 20rpx;
    padding-left: 16rpx;
    border-left: 6rpx solid var(--primary-color, #ff6b35);
}

.order-card {
    padding: 20rpx;
    background: #f9f9f9;
    border-radius: 12rpx;
}

.form-item {
    display: flex;
    align-items: center;
    padding: 24rpx 0;
    border-bottom: 1rpx solid #f5f5f5;
    
    .label {
        font-size: 28rpx;
        color: #333;
        width: 200rpx;
        flex-shrink: 0;
        
        &.required::before {
            content: '*';
            color: #ff3b30;
            margin-right: 4rpx;
        }
    }
    
    .input {
        flex: 1;
        font-size: 28rpx;
        text-align: right;
    }
}

.reason-input {
    width: 100%;
    height: 200rpx;
    padding: 20rpx;
    background: #f9f9f9;
    border-radius: 12rpx;
    font-size: 28rpx;
}

.flow-steps {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
}

.flow-step {
    display: flex;
    align-items: flex-start;
    gap: 20rpx;
}

.step-num {
    width: 44rpx;
    height: 44rpx;
    background: var(--primary-color, #ff6b35);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24rpx;
    flex-shrink: 0;
}

.step-content {
    flex: 1;
    padding-bottom: 24rpx;
    border-bottom: 1rpx dashed #eee;
}

.flow-step:last-child .step-content {
    border-bottom: none;
}

.step-title {
    font-size: 28rpx;
    color: #333;
    font-weight: 500;
}

.step-desc {
    font-size: 24rpx;
    color: #999;
    margin-top: 6rpx;
}

.bottom-actions {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20rpx 30rpx;
    background: #fff;
    box-shadow: 0 -2rpx 10rpx rgba(0,0,0,0.05);
}

.btn-submit {
    width: 100%;
    height: 88rpx;
    line-height: 88rpx;
    background: var(--primary-color, #ff6b35);
    color: #fff;
    border-radius: 44rpx;
    font-size: 30rpx;
    border: none;
    
    &[disabled] {
        opacity: 0.6;
    }
}
</style>
