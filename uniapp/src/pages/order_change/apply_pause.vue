<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="申请暂停"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="apply-page">
        <!-- 提示信息 -->
        <view class="tip-card">
            <uni-icons type="info-filled" size="18" color="#ff9500"></uni-icons>
            <text class="tip-text"
                >暂停期间订单将处于冻结状态，暂停到期后需主动申请恢复或重新选择服务日期。</text
            >
        </view>

        <!-- 订单信息 -->
        <view class="bg-white mt-3 p-4" v-if="orderInfo">
            <view class="section-title">暂停订单</view>
            <view class="order-card">
                <view class="text-sm text-gray-500">订单号: {{ orderInfo.order_sn }}</view>
                <view
                    class="flex items-center mt-2"
                    v-if="orderInfo.items && orderInfo.items.length > 0"
                >
                    <image
                        :src="
                            orderInfo.items[0].staff?.avatar || '/static/images/user/default_avatar.png'
                        "
                        class="w-12 h-12 rounded-lg mr-3"
                        mode="aspectFill"
                    />
                    <view class="flex-1">
                        <view class="text-sm font-medium">{{ orderInfo.items[0].staff_name }}</view>
                        <view class="text-xs text-gray-400">{{
                            orderInfo.items[0].package_name
                        }}</view>
                    </view>
                    <view class="text-right">
                        <view class="text-primary font-bold">¥{{ orderInfo.pay_amount }}</view>
                        <view class="text-xs text-gray-400">{{ orderInfo.service_date }}</view>
                    </view>
                </view>
            </view>
        </view>

        <!-- 暂停类型 -->
        <view class="bg-white mt-3 p-4">
            <view class="section-title">暂停原因类型</view>
            <view class="type-options">
                <view
                    v-for="type in pauseTypes"
                    :key="type.value"
                    class="type-option"
                    :class="{ active: formData.pause_type === type.value }"
                    @click="formData.pause_type = type.value"
                >
                    <uni-icons
                        :type="type.icon"
                        size="24"
                        :color="formData.pause_type === type.value ? '#ff6b35' : '#999'"
                    ></uni-icons>
                    <text class="type-label">{{ type.label }}</text>
                </view>
            </view>
        </view>

        <!-- 暂停时间 -->
        <view class="bg-white mt-3 p-4">
            <view class="section-title">暂停时间</view>
            <view class="form-item" @click="showStartPicker = true">
                <text class="label required">开始日期</text>
                <view class="value-area">
                    <text v-if="formData.start_date" class="text-primary">{{
                        formData.start_date
                    }}</text>
                    <text v-else class="placeholder">请选择开始日期</text>
                    <uni-icons type="right" size="16" color="#999"></uni-icons>
                </view>
            </view>
            <view class="form-item" @click="showEndPicker = true">
                <text class="label required">结束日期</text>
                <view class="value-area">
                    <text v-if="formData.end_date" class="text-primary">{{
                        formData.end_date
                    }}</text>
                    <text v-else class="placeholder">请选择结束日期</text>
                    <uni-icons type="right" size="16" color="#999"></uni-icons>
                </view>
            </view>
            <view class="pause-days" v-if="pauseDays > 0">
                <text class="text-gray-500">暂停天数: </text>
                <text class="text-primary font-bold">{{ pauseDays }}天</text>
            </view>
        </view>

        <!-- 暂停原因 -->
        <view class="bg-white mt-3 p-4">
            <view class="section-title">详细说明</view>
            <textarea
                v-model="formData.reason"
                class="reason-input"
                placeholder="请详细说明暂停原因（必填）"
                maxlength="500"
            />
            <view class="text-right text-xs text-gray-400">{{ formData.reason.length }}/500</view>
        </view>

        <!-- 证明材料 -->
        <view class="bg-white mt-3 p-4">
            <view class="section-title">证明材料（选填）</view>
            <view class="text-xs text-gray-400 mb-3">如有相关证明材料，请上传以加快审核</view>
            <view class="image-uploader">
                <view v-for="(img, index) in formData.proof_images" :key="index" class="image-item">
                    <image :src="img" class="upload-image" mode="aspectFill" />
                    <view class="delete-btn" @click="removeImage(index)">
                        <uni-icons type="close" size="14" color="#fff"></uni-icons>
                    </view>
                </view>
                <view
                    class="add-image"
                    @click="chooseImage"
                    v-if="formData.proof_images.length < 10"
                >
                    <uni-icons type="plusempty" size="40" color="#ccc"></uni-icons>
                    <text class="text-xs text-gray-400 mt-1">上传图片</text>
                </view>
            </view>
            <view class="text-xs text-gray-400 mt-2">最多上传10张图片</view>
        </view>

        <!-- 提交按钮 -->
        <view class="bottom-actions">
            <button class="btn-submit" :disabled="submitting" @click="handleSubmit">
                {{ submitting ? '提交中...' : '提交暂停申请' }}
            </button>
        </view>

        <!-- 日期选择器 -->
        <uni-datetime-picker
            type="date"
            v-model="formData.start_date"
            :start="today"
            @change="onStartDateChange"
        />
        <uni-datetime-picker
            type="date"
            v-model="formData.end_date"
            :start="formData.start_date || today"
            @change="onEndDateChange"
        />
    </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { applyPause, checkCanChange } from '@/api/orderChange'
import { getOrderDetail } from '@/api/order'

const orderId = ref(0)
const orderInfo = ref<any>(null)
const submitting = ref(false)
const showStartPicker = ref(false)
const showEndPicker = ref(false)

const today = new Date().toISOString().split('T')[0]

const pauseTypes = [
    { value: 1, label: '疫情原因', icon: 'medal' },
    { value: 2, label: '突发事件', icon: 'notification' },
    { value: 3, label: '个人原因', icon: 'person' },
    { value: 4, label: '其他原因', icon: 'more' }
]

const formData = reactive({
    pause_type: 3,
    start_date: '',
    end_date: '',
    reason: '',
    proof_images: [] as string[]
})

const pauseDays = computed(() => {
    if (!formData.start_date || !formData.end_date) return 0
    const start = new Date(formData.start_date)
    const end = new Date(formData.end_date)
    const diff = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)) + 1
    return diff > 0 ? diff : 0
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

const onStartDateChange = (e: any) => {
    formData.start_date = e
    // 如果结束日期早于开始日期，清空结束日期
    if (formData.end_date && formData.end_date < formData.start_date) {
        formData.end_date = ''
    }
}

const onEndDateChange = (e: any) => {
    formData.end_date = e
}

const chooseImage = () => {
    uni.chooseImage({
        count: 10 - formData.proof_images.length,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: (res) => {
            formData.proof_images.push(...res.tempFilePaths)
        }
    })
}

const removeImage = (index: number) => {
    formData.proof_images.splice(index, 1)
}

const handleSubmit = async () => {
    if (!formData.start_date) {
        uni.showToast({ title: '请选择开始日期', icon: 'none' })
        return
    }
    if (!formData.end_date) {
        uni.showToast({ title: '请选择结束日期', icon: 'none' })
        return
    }
    if (!formData.reason.trim()) {
        uni.showToast({ title: '请填写暂停原因', icon: 'none' })
        return
    }
    if (formData.reason.length < 10) {
        uni.showToast({ title: '暂停原因至少10个字', icon: 'none' })
        return
    }

    submitting.value = true
    try {
        const res = await applyPause({
            order_id: orderId.value,
            pause_type: formData.pause_type,
            reason: formData.reason,
            start_date: formData.start_date,
            end_date: formData.end_date,
            proof_images: formData.proof_images
        })
        uni.showToast({ title: '申请已提交' })
        setTimeout(() => {
            uni.redirectTo({ url: `/pages/order_change/pause_detail?id=${res.pause_id}` })
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

.type-options {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20rpx;
}

.type-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30rpx 20rpx;
    border: 2rpx solid #eee;
    border-radius: 12rpx;

    &.active {
        border-color: var(--primary-color, #ff6b35);
        background: rgba(255, 107, 53, 0.05);
    }

    .type-label {
        font-size: 26rpx;
        color: #666;
        margin-top: 12rpx;
    }

    &.active .type-label {
        color: var(--primary-color, #ff6b35);
    }
}

.form-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24rpx 0;
    border-bottom: 1rpx solid #f5f5f5;

    .label {
        font-size: 28rpx;
        color: #333;

        &.required::before {
            content: '*';
            color: #ff3b30;
            margin-right: 4rpx;
        }
    }

    .value-area {
        display: flex;
        align-items: center;
        gap: 8rpx;
    }

    .placeholder {
        color: #ccc;
        font-size: 28rpx;
    }
}

.pause-days {
    padding: 20rpx;
    background: #f0f9ff;
    border-radius: 8rpx;
    margin-top: 20rpx;
    text-align: center;
}

.reason-input {
    width: 100%;
    height: 240rpx;
    padding: 20rpx;
    background: #f9f9f9;
    border-radius: 12rpx;
    font-size: 28rpx;
}

.image-uploader {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.image-item {
    position: relative;
    width: 140rpx;
    height: 140rpx;
}

.upload-image {
    width: 100%;
    height: 100%;
    border-radius: 8rpx;
}

.delete-btn {
    position: absolute;
    top: -10rpx;
    right: -10rpx;
    width: 36rpx;
    height: 36rpx;
    background: rgba(0, 0, 0, 0.5);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.add-image {
    width: 140rpx;
    height: 140rpx;
    border: 2rpx dashed #ddd;
    border-radius: 8rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.bottom-actions {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20rpx 30rpx;
    background: #fff;
    box-shadow: 0 -2rpx 10rpx rgba(0, 0, 0, 0.05);
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
