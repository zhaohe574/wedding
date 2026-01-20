<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="转让详情"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="transfer-detail">
        <view v-if="loading" class="py-20 text-center text-gray-400">
            加载中...
        </view>
        <template v-else-if="detail">
            <!-- 状态卡片 -->
            <view class="status-card" :class="getStatusBgClass(detail.transfer_status)">
                <view class="text-lg font-bold">{{ detail.transfer_status_desc }}</view>
                <view class="text-sm mt-1 opacity-80">{{ getStatusTip(detail.transfer_status) }}</view>
            </view>

            <!-- 转让信息 -->
            <view class="bg-white mt-3 p-4">
                <view class="section-title">转让信息</view>
                <view class="info-row">
                    <text class="label">转让单号</text>
                    <text class="value">{{ detail.transfer_sn }}</text>
                </view>
                <view class="info-row">
                    <text class="label">申请时间</text>
                    <text class="value">{{ detail.create_time }}</text>
                </view>
                <view class="info-row" v-if="detail.audit_time">
                    <text class="label">审核时间</text>
                    <text class="value">{{ detail.audit_time }}</text>
                </view>
                <view class="info-row" v-if="detail.accept_time">
                    <text class="label">接收时间</text>
                    <text class="value">{{ detail.accept_time }}</text>
                </view>
                <view class="info-row" v-if="detail.complete_time">
                    <text class="label">完成时间</text>
                    <text class="value">{{ detail.complete_time }}</text>
                </view>
            </view>

            <!-- 转让双方信息 -->
            <view class="bg-white mt-3 p-4">
                <view class="section-title">转让双方</view>
                <view class="transfer-parties">
                    <view class="party-item">
                        <view class="party-label">转让方</view>
                        <view class="party-name">{{ detail.from_user_name }}</view>
                        <view class="party-mobile">{{ detail.from_user_mobile }}</view>
                    </view>
                    <view class="transfer-arrow">
                        <uni-icons type="arrowright" size="24" color="#999"></uni-icons>
                    </view>
                    <view class="party-item">
                        <view class="party-label">接收方</view>
                        <view class="party-name">{{ detail.to_user_name }}</view>
                        <view class="party-mobile">{{ detail.to_user_mobile }}</view>
                        <view class="verify-status mt-2">
                            <view v-if="detail.to_user_verified" class="tag bg-green-100 text-green-600">已验证</view>
                            <view v-else class="tag bg-gray-100 text-gray-600">未验证</view>
                        </view>
                    </view>
                </view>
            </view>

            <!-- 转让原因 -->
            <view class="bg-white mt-3 p-4" v-if="detail.transfer_reason">
                <view class="section-title">转让原因</view>
                <view class="text-gray-600 text-sm">{{ detail.transfer_reason }}</view>
            </view>

            <!-- 审核结果 -->
            <view class="bg-white mt-3 p-4" v-if="detail.transfer_status >= 4 && detail.reject_reason">
                <view class="section-title">拒绝原因</view>
                <view class="text-red-500 text-sm">{{ detail.reject_reason }}</view>
            </view>

            <!-- 关联订单 -->
            <view class="bg-white mt-3 p-4" v-if="detail.order">
                <view class="section-title">关联订单</view>
                <view class="order-card" @click="goOrder(detail.order.id)">
                    <view class="flex justify-between items-center">
                        <text class="text-sm text-gray-500">{{ detail.order.order_sn }}</text>
                        <text class="text-sm text-primary">查看订单 ></text>
                    </view>
                    <view class="flex justify-between mt-2">
                        <text class="text-sm text-gray-600">服务日期: {{ detail.order.service_date }}</text>
                        <text class="text-primary font-bold">¥{{ detail.order.pay_amount }}</text>
                    </view>
                </view>
            </view>

            <!-- 底部操作 -->
            <view class="bottom-actions" v-if="showActions">
                <!-- 转让方可取消 -->
                <button 
                    v-if="detail.is_from_user && detail.transfer_status <= 1" 
                    class="btn-cancel" 
                    @click="handleCancel"
                >取消转让</button>
                
                <!-- 接收方确认 (待接收状态) -->
                <template v-if="!detail.is_from_user && detail.transfer_status === 1">
                    <view class="accept-tip">请使用接收方手机号输入验证码确认接收</view>
                    <view class="code-input-area">
                        <input 
                            v-model="acceptCode" 
                            class="code-input"
                            type="number"
                            placeholder="请输入验证码"
                            maxlength="6"
                        />
                        <button class="btn-accept" :disabled="accepting" @click="handleAccept">
                            {{ accepting ? '确认中...' : '确认接收' }}
                        </button>
                    </view>
                </template>
            </view>
        </template>
        <view v-else class="py-20 text-center text-gray-400">
            数据不存在
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getTransferDetail, cancelTransfer, acceptTransfer } from '@/api/orderChange'

const loading = ref(true)
const detail = ref<any>(null)
const transferId = ref(0)
const acceptCode = ref('')
const accepting = ref(false)

const showActions = computed(() => {
    if (!detail.value) return false
    // 转让方可取消，或接收方可确认
    return (detail.value.is_from_user && detail.value.transfer_status <= 1) ||
           (!detail.value.is_from_user && detail.value.transfer_status === 1)
})

const getStatusBgClass = (status: number) => {
    const classes: Record<number, string> = {
        0: 'bg-orange',
        1: 'bg-blue',
        2: 'bg-purple',
        3: 'bg-green',
        4: 'bg-red',
        5: 'bg-gray'
    }
    return classes[status] || 'bg-gray'
}

const getStatusTip = (status: number) => {
    const tips: Record<number, string> = {
        0: '您的转让申请正在等待审核中',
        1: '审核已通过，等待接收方确认',
        2: '接收方已确认，转让即将完成',
        3: '转让已完成，订单已归属接收方',
        4: '很抱歉，您的转让申请未通过审核',
        5: '该转让申请已取消'
    }
    return tips[status] || ''
}

const fetchDetail = async () => {
    loading.value = true
    try {
        const res = await getTransferDetail({ id: transferId.value })
        detail.value = res
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

const goOrder = (orderId: number) => {
    uni.navigateTo({ url: `/pages/order_detail/order_detail?id=${orderId}` })
}

const handleCancel = async () => {
    const res = await uni.showModal({
        title: '提示',
        content: '确定要取消该转让申请吗？'
    })
    if (res.confirm) {
        try {
            await cancelTransfer({ id: transferId.value })
            uni.showToast({ title: '已取消' })
            fetchDetail()
        } catch (e: any) {
            uni.showToast({ title: e.message || '操作失败', icon: 'none' })
        }
    }
}

const handleAccept = async () => {
    if (!acceptCode.value || acceptCode.value.length !== 6) {
        uni.showToast({ title: '请输入6位验证码', icon: 'none' })
        return
    }

    accepting.value = true
    try {
        await acceptTransfer({
            id: transferId.value,
            mobile: detail.value.to_user_mobile,
            code: acceptCode.value
        })
        uni.showToast({ title: '接收成功' })
        fetchDetail()
    } catch (e: any) {
        uni.showToast({ title: e.message || '验证失败', icon: 'none' })
    } finally {
        accepting.value = false
    }
}

onLoad((options: any) => {
    if (options.id) {
        transferId.value = Number(options.id)
        fetchDetail()
    }
})
</script>

<style lang="scss" scoped>
.transfer-detail {
    min-height: 100vh;
    background-color: #f5f5f5;
    padding-bottom: 200rpx;
}

.status-card {
    padding: 40rpx 30rpx;
    color: #fff;
    
    &.bg-orange { background: linear-gradient(135deg, #ff9500, #ff6b00); }
    &.bg-blue { background: linear-gradient(135deg, #007aff, #0056d6); }
    &.bg-purple { background: linear-gradient(135deg, #af52de, #8e44ad); }
    &.bg-green { background: linear-gradient(135deg, #34c759, #28a745); }
    &.bg-red { background: linear-gradient(135deg, #ff3b30, #d63027); }
    &.bg-gray { background: linear-gradient(135deg, #8e8e93, #636366); }
}

.section-title {
    font-size: 28rpx;
    font-weight: bold;
    color: #333;
    margin-bottom: 20rpx;
    padding-left: 16rpx;
    border-left: 6rpx solid var(--primary-color, #ff6b35);
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16rpx 0;
    border-bottom: 1rpx solid #f5f5f5;
    
    &:last-child { border-bottom: none; }
    
    .label { color: #999; font-size: 26rpx; }
    .value { color: #333; font-size: 26rpx; }
}

.transfer-parties {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.party-item {
    flex: 1;
    text-align: center;
    padding: 20rpx;
}

.party-label {
    font-size: 24rpx;
    color: #999;
    margin-bottom: 10rpx;
}

.party-name {
    font-size: 30rpx;
    font-weight: bold;
    color: #333;
}

.party-mobile {
    font-size: 26rpx;
    color: #666;
    margin-top: 6rpx;
}

.transfer-arrow {
    padding: 0 20rpx;
}

.tag {
    display: inline-block;
    padding: 4rpx 16rpx;
    font-size: 22rpx;
    border-radius: 6rpx;
}

.order-card {
    padding: 20rpx;
    background: #f9f9f9;
    border-radius: 12rpx;
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

.btn-cancel {
    width: 100%;
    height: 88rpx;
    line-height: 88rpx;
    background: #fff;
    border: 1rpx solid #ff3b30;
    color: #ff3b30;
    border-radius: 44rpx;
    font-size: 30rpx;
}

.accept-tip {
    font-size: 24rpx;
    color: #999;
    text-align: center;
    margin-bottom: 20rpx;
}

.code-input-area {
    display: flex;
    gap: 20rpx;
}

.code-input {
    flex: 1;
    height: 88rpx;
    border: 2rpx solid #eee;
    border-radius: 12rpx;
    padding: 0 24rpx;
    font-size: 32rpx;
    text-align: center;
    letter-spacing: 10rpx;
}

.btn-accept {
    width: 240rpx;
    height: 88rpx;
    line-height: 88rpx;
    background: var(--primary-color, #ff6b35);
    color: #fff;
    border-radius: 12rpx;
    font-size: 28rpx;
    border: none;
    
    &[disabled] {
        opacity: 0.6;
    }
}
</style>
