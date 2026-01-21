<template>
    <view class="ticket-detail-page">
        <view class="detail-card" v-if="detail">
            <!-- 工单状态 -->
            <view class="status-section">
                <view class="status-icon" :class="getStatusClass(detail.status)">
                    <u-icon :name="getStatusIcon(detail.status)" size="60" color="#fff"></u-icon>
                </view>
                <view class="status-info">
                    <text class="status-text">{{ detail.status_desc || getStatusText(detail.status) }}</text>
                    <text class="status-time">{{ detail.update_time || detail.create_time }}</text>
                </view>
            </view>

            <!-- 工单信息 -->
            <view class="info-section">
                <view class="info-item">
                    <text class="info-label">工单编号</text>
                    <text class="info-value">{{ detail.ticket_sn }}</text>
                </view>
                <view class="info-item">
                    <text class="info-label">工单类型</text>
                    <text class="info-value">{{ detail.type_desc || getTypeText(detail.type) }}</text>
                </view>
                <view class="info-item">
                    <text class="info-label">创建时间</text>
                    <text class="info-value">{{ detail.create_time }}</text>
                </view>
            </view>

            <!-- 工单内容 -->
            <view class="content-section">
                <view class="section-title">工单内容</view>
                <view class="content-title">{{ detail.title }}</view>
                <view class="content-text" v-if="detail.content">{{ detail.content }}</view>
                <view class="content-images" v-if="detail.images && detail.images.length">
                    <image 
                        v-for="(img, index) in detail.images" 
                        :key="index" 
                        :src="img" 
                        mode="aspectFill"
                        @click="previewImage(detail.images, index)"
                    ></image>
                </view>
            </view>

            <!-- 处理进度 -->
            <view class="progress-section" v-if="detail.logs && detail.logs.length">
                <view class="section-title">处理进度</view>
                <view class="progress-list">
                    <view class="progress-item" v-for="(log, index) in detail.logs" :key="index">
                        <view class="progress-dot" :class="{ active: index === 0 }"></view>
                        <view class="progress-content">
                            <text class="progress-text">{{ log.content }}</text>
                            <text class="progress-time">{{ log.create_time }}</text>
                        </view>
                    </view>
                </view>
            </view>

            <!-- 处理结果 -->
            <view class="result-section" v-if="detail.result">
                <view class="section-title">处理结果</view>
                <view class="result-content">{{ detail.result }}</view>
            </view>
        </view>

        <!-- 操作按钮 -->
        <view class="action-bar" v-if="detail">
            <template v-if="detail.status === 0">
                <u-button type="default" @click="handleCancel">取消工单</u-button>
            </template>
            <template v-else-if="detail.status === 1">
                <u-button type="primary" @click="showConfirmPopup = true">确认完成</u-button>
            </template>
        </view>

        <!-- 确认完成弹窗 -->
        <u-popup v-model="showConfirmPopup" mode="bottom" :border-radius="24">
            <view class="confirm-popup">
                <view class="popup-header">
                    <text class="popup-title">确认完成</text>
                </view>
                <view class="popup-body">
                    <view class="form-item">
                        <text class="form-label">满意度评价</text>
                        <view class="rating-wrap">
                            <u-rate v-model="confirmForm.satisfaction" :min-count="1"></u-rate>
                        </view>
                    </view>
                    <view class="form-item">
                        <text class="form-label">备注</text>
                        <textarea 
                            class="form-textarea" 
                            v-model="confirmForm.remark" 
                            placeholder="请输入备注（选填）"
                        ></textarea>
                    </view>
                </view>
                <view class="popup-footer">
                    <u-button type="default" @click="showConfirmPopup = false">取消</u-button>
                    <u-button type="primary" @click="handleConfirm">确认</u-button>
                </view>
            </view>
        </u-popup>
    </view>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { getTicketDetail, cancelTicket, confirmComplete } from '@/api/aftersale'
import { onLoad } from '@dcloudio/uni-app'

const ticketId = ref<number>(0)
const detail = ref<any>(null)
const showConfirmPopup = ref(false)
const confirmForm = reactive({
    satisfaction: 5,
    remark: ''
})

const getDetail = async () => {
    try {
        const res = await getTicketDetail(ticketId.value)
        detail.value = res?.data || res
    } catch (e) {
        uni.showToast({ title: '获取详情失败', icon: 'none' })
    }
}

const getStatusClass = (status: number) => {
    const map: Record<number, string> = {
        0: 'status-pending',
        1: 'status-processing',
        2: 'status-completed',
        3: 'status-closed'
    }
    return map[status] || ''
}

const getStatusIcon = (status: number) => {
    const map: Record<number, string> = {
        0: 'clock',
        1: 'setting',
        2: 'checkmark-circle',
        3: 'close-circle'
    }
    return map[status] || 'info-circle'
}

const getStatusText = (status: number) => {
    const map: Record<number, string> = {
        0: '待处理',
        1: '处理中',
        2: '已完成',
        3: '已关闭'
    }
    return map[status] || '未知'
}

const getTypeText = (type: number) => {
    const map: Record<number, string> = {
        1: '服务咨询',
        2: '订单问题',
        3: '技术问题',
        4: '投诉建议',
        5: '其他'
    }
    return map[type] || '其他'
}

const previewImage = (images: string[], index: number) => {
    uni.previewImage({
        urls: images,
        current: index
    })
}

const handleCancel = async () => {
    uni.showModal({
        title: '确认取消',
        content: '确定要取消此工单吗？',
        success: async (res) => {
            if (res.confirm) {
                try {
                    await cancelTicket(ticketId.value)
                    uni.showToast({ title: '取消成功' })
                    getDetail()
                } catch (e) {
                    uni.showToast({ title: '取消失败', icon: 'none' })
                }
            }
        }
    })
}

const handleConfirm = async () => {
    try {
        await confirmComplete({
            id: ticketId.value,
            satisfaction: confirmForm.satisfaction,
            remark: confirmForm.remark
        })
        showConfirmPopup.value = false
        uni.showToast({ title: '确认成功' })
        getDetail()
    } catch (e) {
        uni.showToast({ title: '操作失败', icon: 'none' })
    }
}

onLoad((options: any) => {
    if (options?.id) {
        ticketId.value = parseInt(options.id)
        getDetail()
    }
})
</script>

<style lang="scss" scoped>
.ticket-detail-page {
    min-height: 100vh;
    background: #f5f5f5;
    padding-bottom: 150rpx;
}

.detail-card {
    background: #fff;
    margin: 20rpx;
    border-radius: 16rpx;
    overflow: hidden;
}

.status-section {
    display: flex;
    align-items: center;
    padding: 40rpx 30rpx;
    background: linear-gradient(135deg, #ff758c 0%, #ff7eb3 100%);
}

.status-icon {
    width: 100rpx;
    height: 100rpx;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    
    &.status-pending { background: rgba(250, 173, 20, 0.3); }
    &.status-processing { background: rgba(24, 144, 255, 0.3); }
    &.status-completed { background: rgba(82, 196, 26, 0.3); }
    &.status-closed { background: rgba(153, 153, 153, 0.3); }
}

.status-info {
    margin-left: 24rpx;
}

.status-text {
    font-size: 36rpx;
    font-weight: 600;
    color: #fff;
    display: block;
}

.status-time {
    font-size: 24rpx;
    color: rgba(255, 255, 255, 0.8);
    margin-top: 8rpx;
    display: block;
}

.info-section {
    padding: 30rpx;
    border-bottom: 1rpx solid #f5f5f5;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16rpx 0;
}

.info-label {
    font-size: 28rpx;
    color: #999;
}

.info-value {
    font-size: 28rpx;
    color: #333;
}

.content-section, .progress-section, .result-section {
    padding: 30rpx;
    border-bottom: 1rpx solid #f5f5f5;
}

.section-title {
    font-size: 30rpx;
    font-weight: 600;
    color: #333;
    margin-bottom: 20rpx;
}

.content-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #333;
    margin-bottom: 16rpx;
}

.content-text {
    font-size: 28rpx;
    color: #666;
    line-height: 1.6;
}

.content-images {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
    margin-top: 20rpx;
    
    image {
        width: 200rpx;
        height: 200rpx;
        border-radius: 8rpx;
    }
}

.progress-list {
    padding-left: 20rpx;
}

.progress-item {
    display: flex;
    position: relative;
    padding-bottom: 30rpx;
    
    &:not(:last-child)::before {
        content: '';
        position: absolute;
        left: 10rpx;
        top: 30rpx;
        bottom: 0;
        width: 2rpx;
        background: #eee;
    }
}

.progress-dot {
    width: 20rpx;
    height: 20rpx;
    border-radius: 50%;
    background: #eee;
    margin-right: 20rpx;
    margin-top: 8rpx;
    
    &.active {
        background: #ff758c;
    }
}

.progress-content {
    flex: 1;
}

.progress-text {
    font-size: 28rpx;
    color: #333;
    display: block;
}

.progress-time {
    font-size: 24rpx;
    color: #999;
    margin-top: 8rpx;
    display: block;
}

.result-content {
    font-size: 28rpx;
    color: #666;
    line-height: 1.6;
}

.action-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20rpx 30rpx;
    background: #fff;
    display: flex;
    gap: 20rpx;
    box-shadow: 0 -4rpx 20rpx rgba(0, 0, 0, 0.05);
    padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
}

.confirm-popup {
    padding: 30rpx;
}

.popup-header {
    text-align: center;
    padding-bottom: 30rpx;
    border-bottom: 1rpx solid #f5f5f5;
}

.popup-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #333;
}

.popup-body {
    padding: 30rpx 0;
}

.form-item {
    margin-bottom: 30rpx;
}

.form-label {
    font-size: 28rpx;
    color: #333;
    margin-bottom: 16rpx;
    display: block;
}

.rating-wrap {
    padding: 16rpx 0;
}

.form-textarea {
    width: 100%;
    height: 200rpx;
    background: #f5f5f5;
    border-radius: 12rpx;
    padding: 20rpx;
    font-size: 28rpx;
    box-sizing: border-box;
}

.popup-footer {
    display: flex;
    gap: 20rpx;
}
</style>
