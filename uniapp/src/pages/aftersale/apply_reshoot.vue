<template>
    <view class="apply-reshoot-page">
        <view class="form-card">
            <!-- 选择订单 -->
            <view class="form-section">
                <view class="section-title">选择订单</view>
                <view class="order-select" @click="showOrderPicker = true">
                    <text v-if="form.order_id">{{ selectedOrder?.order_sn }}</text>
                    <text v-else class="placeholder">请选择关联订单</text>
                    <u-icon name="arrow-right" size="28" color="#999"></u-icon>
                </view>
            </view>

            <!-- 补拍类型 -->
            <view class="form-section">
                <view class="section-title">补拍类型</view>
                <view class="type-options">
                    <view 
                        class="type-option" 
                        :class="{ active: form.type === type.value }"
                        v-for="type in typeOptions"
                        :key="type.value"
                        @click="form.type = type.value"
                    >
                        {{ type.label }}
                    </view>
                </view>
            </view>

            <!-- 申请原因 -->
            <view class="form-section">
                <view class="section-title">申请原因</view>
                <view class="reason-options">
                    <view 
                        class="reason-option" 
                        :class="{ active: form.reason_type === reason.value }"
                        v-for="reason in reasonOptions"
                        :key="reason.value"
                        @click="form.reason_type = reason.value"
                    >
                        {{ reason.label }}
                    </view>
                </view>
            </view>

            <!-- 详细说明 -->
            <view class="form-section">
                <view class="section-title">详细说明</view>
                <textarea 
                    class="form-textarea" 
                    v-model="form.reason" 
                    placeholder="请详细描述补拍原因（选填）"
                    maxlength="500"
                ></textarea>
            </view>

            <!-- 上传图片 -->
            <view class="form-section">
                <view class="section-title">上传图片</view>
                <view class="upload-area">
                    <view class="image-list">
                        <view class="image-item" v-for="(img, index) in form.images" :key="index">
                            <image :src="img" mode="aspectFill"></image>
                            <view class="delete-btn" @click="removeImage(index)">
                                <u-icon name="close" size="24" color="#fff"></u-icon>
                            </view>
                        </view>
                        <view class="upload-btn" @click="chooseImage" v-if="form.images.length < 9">
                            <u-icon name="plus" size="48" color="#999"></u-icon>
                            <text>上传图片</text>
                        </view>
                    </view>
                    <text class="upload-tip">最多上传9张图片</text>
                </view>
            </view>

            <!-- 期望日期 -->
            <view class="form-section">
                <view class="section-title">期望补拍日期</view>
                <view class="date-picker" @click="showDatePicker = true">
                    <text v-if="form.expect_date">{{ form.expect_date }}</text>
                    <text v-else class="placeholder">请选择期望日期（选填）</text>
                    <u-icon name="calendar" size="32" color="#999"></u-icon>
                </view>
            </view>
        </view>

        <!-- 提交按钮 -->
        <view class="submit-bar">
            <u-button type="primary" :loading="submitting" @click="handleSubmit">提交申请</u-button>
        </view>

        <!-- 订单选择器 -->
        <u-picker 
            v-model="showOrderPicker" 
            mode="selector"
            :range="orderOptions"
            range-key="label"
            @confirm="onOrderConfirm"
        ></u-picker>

        <!-- 日期选择器 -->
        <u-picker 
            v-model="showDatePicker" 
            mode="time"
            @confirm="onDateConfirm"
        ></u-picker>
    </view>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { applyReshoot } from '@/api/aftersale'
import { getOrderList } from '@/api/order'
import { onLoad } from '@dcloudio/uni-app'

const form = reactive({
    order_id: 0,
    type: 1,
    reason_type: 1,
    reason: '',
    images: [] as string[],
    expect_date: ''
})

const typeOptions = [
    { value: 1, label: '全部重拍' },
    { value: 2, label: '部分重拍' },
    { value: 3, label: '补拍加片' }
]

const reasonOptions = [
    { value: 1, label: '拍摄效果不满意' },
    { value: 2, label: '照片质量问题' },
    { value: 3, label: '服务态度问题' },
    { value: 4, label: '天气原因' },
    { value: 5, label: '其他原因' }
]

const showOrderPicker = ref(false)
const showDatePicker = ref(false)
const submitting = ref(false)
const orderOptions = ref<any[]>([])
const selectedOrder = ref<any>(null)

const loadOrders = async () => {
    try {
        const res = await getOrderList({ status: 3 }) // 已完成的订单
        const lists = res?.data?.lists || res?.lists || []
        orderOptions.value = lists.map((item: any) => ({
            value: item.id,
            label: item.order_sn,
            ...item
        }))
    } catch (e) {
        console.error('获取订单列表失败:', e)
    }
}

const onOrderConfirm = (e: any) => {
    const index = e.detail?.value || e[0]
    const order = orderOptions.value[index]
    if (order) {
        form.order_id = order.value
        selectedOrder.value = order
    }
}

const onDateConfirm = (e: any) => {
    form.expect_date = e.detail?.value || e
}

const chooseImage = () => {
    uni.chooseImage({
        count: 9 - form.images.length,
        success: (res) => {
            res.tempFilePaths.forEach(path => {
                // 实际项目中应该上传到服务器
                form.images.push(path)
            })
        }
    })
}

const removeImage = (index: number) => {
    form.images.splice(index, 1)
}

const handleSubmit = async () => {
    if (!form.order_id) {
        uni.showToast({ title: '请选择关联订单', icon: 'none' })
        return
    }

    submitting.value = true
    try {
        await applyReshoot(form)
        uni.showToast({ title: '申请提交成功' })
        setTimeout(() => {
            uni.navigateBack()
        }, 1500)
    } catch (e: any) {
        uni.showToast({ title: e.message || '提交失败', icon: 'none' })
    } finally {
        submitting.value = false
    }
}

onLoad((options: any) => {
    if (options?.order_id) {
        form.order_id = parseInt(options.order_id)
    }
    loadOrders()
})
</script>

<style lang="scss" scoped>
.apply-reshoot-page {
    min-height: 100vh;
    background: #f5f5f5;
    padding-bottom: 150rpx;
}

.form-card {
    background: #fff;
    margin: 20rpx;
    border-radius: 16rpx;
    overflow: hidden;
}

.form-section {
    padding: 30rpx;
    border-bottom: 1rpx solid #f5f5f5;
    
    &:last-child {
        border-bottom: none;
    }
}

.section-title {
    font-size: 30rpx;
    font-weight: 600;
    color: #333;
    margin-bottom: 20rpx;
}

.order-select, .date-picker {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24rpx;
    background: #f8f8f8;
    border-radius: 12rpx;
    font-size: 28rpx;
    color: #333;
    
    .placeholder {
        color: #999;
    }
}

.type-options, .reason-options {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.type-option, .reason-option {
    padding: 16rpx 32rpx;
    background: #f5f5f5;
    border-radius: 8rpx;
    font-size: 26rpx;
    color: #666;
    
    &.active {
        background: rgba(79, 172, 254, 0.1);
        color: #4facfe;
        border: 1rpx solid #4facfe;
    }
}

.form-textarea {
    width: 100%;
    height: 200rpx;
    background: #f8f8f8;
    border-radius: 12rpx;
    padding: 20rpx;
    font-size: 28rpx;
    box-sizing: border-box;
}

.upload-area {
    margin-top: 20rpx;
}

.image-list {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.image-item {
    position: relative;
    width: 200rpx;
    height: 200rpx;
    border-radius: 12rpx;
    overflow: hidden;
    
    image {
        width: 100%;
        height: 100%;
    }
    
    .delete-btn {
        position: absolute;
        top: 8rpx;
        right: 8rpx;
        width: 40rpx;
        height: 40rpx;
        background: rgba(0, 0, 0, 0.5);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
}

.upload-btn {
    width: 200rpx;
    height: 200rpx;
    border: 2rpx dashed #ddd;
    border-radius: 12rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    
    text {
        font-size: 24rpx;
        color: #999;
        margin-top: 8rpx;
    }
}

.upload-tip {
    font-size: 24rpx;
    color: #999;
    margin-top: 16rpx;
    display: block;
}

.submit-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20rpx 30rpx;
    background: #fff;
    box-shadow: 0 -4rpx 20rpx rgba(0, 0, 0, 0.05);
    padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
}
</style>
