<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="暂停详情"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="pause-detail">
        <view v-if="loading" class="py-20 text-center text-gray-400">
            加载中...
        </view>
        <template v-else-if="detail">
            <!-- 状态卡片 -->
            <view class="status-card" :class="getStatusBgClass(detail.pause_status)">
                <view class="text-lg font-bold">{{ detail.pause_status_desc }}</view>
                <view class="text-sm mt-1 opacity-80">{{ getStatusTip(detail.pause_status) }}</view>
                <!-- 剩余天数 -->
                <view v-if="detail.pause_status === 1 && remainDays >= 0" class="remain-days">
                    <text v-if="remainDays > 0">剩余 {{ remainDays }} 天</text>
                    <text v-else class="text-yellow-300">今日到期</text>
                </view>
            </view>

            <!-- 暂停信息 -->
            <view class="bg-white mt-3 p-4">
                <view class="section-title">暂停信息</view>
                <view class="info-row">
                    <text class="label">暂停单号</text>
                    <text class="value">{{ detail.pause_sn }}</text>
                </view>
                <view class="info-row">
                    <text class="label">暂停类型</text>
                    <view class="tag" :class="getTypeClass(detail.pause_type)">
                        {{ detail.pause_type_desc }}
                    </view>
                </view>
                <view class="info-row">
                    <text class="label">申请时间</text>
                    <text class="value">{{ detail.create_time }}</text>
                </view>
                <view class="info-row" v-if="detail.audit_time">
                    <text class="label">审核时间</text>
                    <text class="value">{{ detail.audit_time }}</text>
                </view>
                <view class="info-row" v-if="detail.resume_time">
                    <text class="label">恢复时间</text>
                    <text class="value">{{ detail.resume_time }}</text>
                </view>
            </view>

            <!-- 暂停时间 -->
            <view class="bg-white mt-3 p-4">
                <view class="section-title">暂停时间</view>
                <view class="pause-time-card">
                    <view class="time-item">
                        <view class="time-label">开始日期</view>
                        <view class="time-value">{{ detail.pause_start_date }}</view>
                    </view>
                    <view class="time-divider">~</view>
                    <view class="time-item">
                        <view class="time-label">结束日期</view>
                        <view class="time-value">{{ detail.pause_end_date }}</view>
                    </view>
                </view>
                <view class="pause-days-info">
                    <view class="days-item">
                        <text class="days-label">计划天数</text>
                        <text class="days-value">{{ detail.pause_days }}天</text>
                    </view>
                    <view class="days-item" v-if="detail.actual_pause_days">
                        <text class="days-label">实际天数</text>
                        <text class="days-value">{{ detail.actual_pause_days }}天</text>
                    </view>
                </view>
            </view>

            <!-- 暂停原因 -->
            <view class="bg-white mt-3 p-4">
                <view class="section-title">暂停原因</view>
                <view class="text-gray-600 text-sm">{{ detail.pause_reason }}</view>
            </view>

            <!-- 证明材料 -->
            <view class="bg-white mt-3 p-4" v-if="detail.proof_images && detail.proof_images.length > 0">
                <view class="section-title">证明材料</view>
                <view class="image-list">
                    <image 
                        v-for="(img, index) in detail.proof_images" 
                        :key="index"
                        :src="img"
                        class="proof-image"
                        mode="aspectFill"
                        @click="previewImage(index)"
                    />
                </view>
            </view>

            <!-- 审核结果 -->
            <view class="bg-white mt-3 p-4" v-if="detail.pause_status >= 3 && detail.reject_reason">
                <view class="section-title">拒绝原因</view>
                <view class="text-red-500 text-sm">{{ detail.reject_reason }}</view>
            </view>

            <!-- 服务日期变更 -->
            <view class="bg-white mt-3 p-4" v-if="detail.original_service_date || detail.new_service_date">
                <view class="section-title">服务日期</view>
                <view class="info-row" v-if="detail.original_service_date">
                    <text class="label">原服务日期</text>
                    <text class="value">{{ detail.original_service_date }}</text>
                </view>
                <view class="info-row" v-if="detail.new_service_date">
                    <text class="label">新服务日期</text>
                    <text class="value text-primary">{{ detail.new_service_date }}</text>
                </view>
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
            <view class="bottom-actions" v-if="detail.pause_status === 0">
                <button class="btn-cancel" @click="handleCancel">取消申请</button>
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
import { getPauseDetail, cancelPause } from '@/api/orderChange'

const loading = ref(true)
const detail = ref<any>(null)
const pauseId = ref(0)

const remainDays = computed(() => {
    if (!detail.value || detail.value.pause_status !== 1) return -1
    const endDate = new Date(detail.value.pause_end_date)
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    const diff = Math.ceil((endDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))
    return diff
})

const getStatusBgClass = (status: number) => {
    const classes: Record<number, string> = {
        0: 'bg-orange',
        1: 'bg-blue',
        2: 'bg-green',
        3: 'bg-red',
        4: 'bg-gray'
    }
    return classes[status] || 'bg-gray'
}

const getStatusTip = (status: number) => {
    const tips: Record<number, string> = {
        0: '您的暂停申请正在等待审核中',
        1: '订单已暂停，到期后请及时申请恢复',
        2: '订单已恢复正常状态',
        3: '很抱歉，您的暂停申请未通过审核',
        4: '该暂停申请已取消'
    }
    return tips[status] || ''
}

const getTypeClass = (type: number) => {
    const classes: Record<number, string> = {
        1: 'bg-red-100 text-red-600',
        2: 'bg-orange-100 text-orange-600',
        3: 'bg-blue-100 text-blue-600',
        4: 'bg-gray-100 text-gray-600'
    }
    return classes[type] || 'bg-gray-100 text-gray-600'
}

const fetchDetail = async () => {
    loading.value = true
    try {
        const res = await getPauseDetail({ id: pauseId.value })
        detail.value = res
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

const previewImage = (index: number) => {
    uni.previewImage({
        current: index,
        urls: detail.value.proof_images
    })
}

const goOrder = (orderId: number) => {
    uni.navigateTo({ url: `/pages/order_detail/order_detail?id=${orderId}` })
}

const handleCancel = async () => {
    const res = await uni.showModal({
        title: '提示',
        content: '确定要取消该暂停申请吗？'
    })
    if (res.confirm) {
        try {
            await cancelPause({ id: pauseId.value })
            uni.showToast({ title: '已取消' })
            fetchDetail()
        } catch (e: any) {
            uni.showToast({ title: e.message || '操作失败', icon: 'none' })
        }
    }
}

onLoad((options: any) => {
    if (options.id) {
        pauseId.value = Number(options.id)
        fetchDetail()
    }
})
</script>

<style lang="scss" scoped>
.pause-detail {
    min-height: 100vh;
    background-color: #f5f5f5;
    padding-bottom: 140rpx;
}

.status-card {
    padding: 40rpx 30rpx;
    color: #fff;
    
    &.bg-orange { background: linear-gradient(135deg, #ff9500, #ff6b00); }
    &.bg-blue { background: linear-gradient(135deg, #007aff, #0056d6); }
    &.bg-green { background: linear-gradient(135deg, #34c759, #28a745); }
    &.bg-red { background: linear-gradient(135deg, #ff3b30, #d63027); }
    &.bg-gray { background: linear-gradient(135deg, #8e8e93, #636366); }
}

.remain-days {
    margin-top: 20rpx;
    padding: 12rpx 24rpx;
    background: rgba(255,255,255,0.2);
    border-radius: 30rpx;
    display: inline-block;
    font-size: 26rpx;
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

.tag {
    display: inline-block;
    padding: 4rpx 16rpx;
    font-size: 22rpx;
    border-radius: 6rpx;
}

.pause-time-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 30rpx;
    background: #f0f9ff;
    border-radius: 12rpx;
}

.time-item {
    text-align: center;
}

.time-label {
    font-size: 24rpx;
    color: #999;
}

.time-value {
    font-size: 30rpx;
    font-weight: bold;
    color: var(--primary-color, #ff6b35);
    margin-top: 8rpx;
}

.time-divider {
    font-size: 36rpx;
    color: #999;
}

.pause-days-info {
    display: flex;
    justify-content: space-around;
    margin-top: 20rpx;
    padding: 20rpx;
    background: #f9f9f9;
    border-radius: 8rpx;
}

.days-item {
    text-align: center;
}

.days-label {
    font-size: 24rpx;
    color: #999;
}

.days-value {
    font-size: 28rpx;
    font-weight: bold;
    color: #333;
    margin-top: 6rpx;
}

.image-list {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.proof-image {
    width: 160rpx;
    height: 160rpx;
    border-radius: 8rpx;
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
</style>
