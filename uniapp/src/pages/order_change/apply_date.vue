<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="申请改期"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="apply-page">
        <!-- 订单信息 -->
        <view class="bg-white p-4" v-if="orderInfo">
            <view class="section-title">订单信息</view>
            <view class="order-card">
                <view class="text-sm text-gray-500">订单号: {{ orderInfo.order_sn }}</view>
                <view
                    class="flex items-center mt-2"
                    v-if="orderInfo.items && orderInfo.items.length > 0"
                >
                    <image
                        :src="
                            orderInfo.items[0].staff?.avatar || '/static/images/default-avatar.png'
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
                </view>
                <view class="mt-2 p-3 bg-orange-50 rounded-lg">
                    <view class="text-sm">
                        <text class="text-gray-500">当前服务日期: </text>
                        <text class="text-orange-500 font-bold">{{ orderInfo.service_date }}</text>
                    </view>
                </view>
            </view>
        </view>

        <!-- 选择新日期 -->
        <view class="bg-white mt-3 p-4">
            <view class="section-title">选择新服务日期</view>
            <view class="form-item" @click="showDatePicker = true">
                <text class="label">新服务日期</text>
                <view class="value-area">
                    <text v-if="formData.new_date" class="text-primary">{{
                        formData.new_date
                    }}</text>
                    <text v-else class="placeholder">请选择日期</text>
                    <uni-icons type="right" size="16" color="#999"></uni-icons>
                </view>
            </view>
            <view class="form-item" @click="showTimePicker = true">
                <text class="label">时间段</text>
                <view class="value-area">
                    <text v-if="formData.new_time_slot !== null" class="text-primary">
                        {{ timeSlotOptions[formData.new_time_slot]?.label }}
                    </text>
                    <text v-else class="placeholder">请选择时间段</text>
                    <uni-icons type="right" size="16" color="#999"></uni-icons>
                </view>
            </view>
        </view>

        <!-- 申请原因 -->
        <view class="bg-white mt-3 p-4">
            <view class="section-title">申请原因</view>
            <textarea
                v-model="formData.reason"
                class="reason-input"
                placeholder="请填写改期原因（选填）"
                maxlength="200"
            />
            <view class="text-right text-xs text-gray-400">{{ formData.reason.length }}/200</view>
        </view>

        <!-- 附件图片 -->
        <view class="bg-white mt-3 p-4">
            <view class="section-title">附件图片（选填）</view>
            <view class="image-uploader">
                <view
                    v-for="(img, index) in formData.attach_images"
                    :key="index"
                    class="image-item"
                >
                    <image :src="img" class="upload-image" mode="aspectFill" />
                    <view class="delete-btn" @click="removeImage(index)">
                        <uni-icons type="close" size="14" color="#fff"></uni-icons>
                    </view>
                </view>
                <view
                    class="add-image"
                    @click="chooseImage"
                    v-if="formData.attach_images.length < 5"
                >
                    <uni-icons type="plusempty" size="40" color="#ccc"></uni-icons>
                    <text class="text-xs text-gray-400 mt-1">上传图片</text>
                </view>
            </view>
            <view class="text-xs text-gray-400 mt-2">最多上传5张图片，支持jpg、png格式</view>
        </view>

        <!-- 提交按钮 -->
        <view class="bottom-actions">
            <button class="btn-submit" :disabled="submitting" @click="handleSubmit">
                {{ submitting ? '提交中...' : '提交申请' }}
            </button>
        </view>

        <!-- 日期选择器 -->
        <uni-popup ref="datePopup" type="bottom" :safe-area="false">
            <view class="picker-popup">
                <view class="picker-header">
                    <text class="cancel" @click="closeDatePicker">取消</text>
                    <text class="title">选择日期</text>
                    <text class="confirm" @click="confirmDate">确定</text>
                </view>
                <picker-view :value="datePickerValue" @change="onDateChange" class="picker-view">
                    <picker-view-column>
                        <view v-for="year in years" :key="year" class="picker-item"
                            >{{ year }}年</view
                        >
                    </picker-view-column>
                    <picker-view-column>
                        <view v-for="month in months" :key="month" class="picker-item"
                            >{{ month }}月</view
                        >
                    </picker-view-column>
                    <picker-view-column>
                        <view v-for="day in days" :key="day" class="picker-item">{{ day }}日</view>
                    </picker-view-column>
                </picker-view>
            </view>
        </uni-popup>

        <!-- 时间段选择器 -->
        <uni-popup ref="timePopup" type="bottom" :safe-area="false">
            <view class="picker-popup">
                <view class="picker-header">
                    <text class="cancel" @click="closeTimePicker">取消</text>
                    <text class="title">选择时间段</text>
                    <text class="confirm" @click="confirmTime">确定</text>
                </view>
                <view class="time-options">
                    <view
                        v-for="option in timeSlotOptions"
                        :key="option.value"
                        class="time-option"
                        :class="{ active: tempTimeSlot === option.value }"
                        @click="tempTimeSlot = option.value"
                    >
                        {{ option.label }}
                    </view>
                </view>
            </view>
        </uni-popup>
    </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { applyDateChange, checkCanChange } from '@/api/orderChange'
import { getOrderDetail } from '@/api/order'

const orderId = ref(0)
const orderInfo = ref<any>(null)
const submitting = ref(false)
const showDatePicker = ref(false)
const showTimePicker = ref(false)

const formData = reactive({
    new_date: '',
    new_time_slot: null as number | null,
    reason: '',
    attach_images: [] as string[]
})

const timeSlotOptions = [
    { value: 0, label: '全天' },
    { value: 1, label: '早礼' },
    { value: 2, label: '午宴' },
    { value: 3, label: '晚宴' }
]

// 日期选择器相关
const datePopup = ref()
const timePopup = ref()
const datePickerValue = ref([0, 0, 0])
const tempTimeSlot = ref(0)

const currentYear = new Date().getFullYear()
const years = Array.from({ length: 3 }, (_, i) => currentYear + i)
const months = Array.from({ length: 12 }, (_, i) => i + 1)
const days = computed(() => {
    const year = years[datePickerValue.value[0]]
    const month = months[datePickerValue.value[1]]
    const daysInMonth = new Date(year, month, 0).getDate()
    return Array.from({ length: daysInMonth }, (_, i) => i + 1)
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

const onDateChange = (e: any) => {
    datePickerValue.value = e.detail.value
}

const closeDatePicker = () => {
    datePopup.value?.close()
}

const confirmDate = () => {
    const year = years[datePickerValue.value[0]]
    const month = String(months[datePickerValue.value[1]]).padStart(2, '0')
    const day = String(days.value[datePickerValue.value[2]]).padStart(2, '0')
    formData.new_date = `${year}-${month}-${day}`
    closeDatePicker()
}

const closeTimePicker = () => {
    timePopup.value?.close()
}

const confirmTime = () => {
    formData.new_time_slot = tempTimeSlot.value
    closeTimePicker()
}

// 监听 showDatePicker 变化
const openDatePicker = () => {
    // 设置默认值为今天
    const today = new Date()
    const yearIndex = years.indexOf(today.getFullYear())
    const monthIndex = today.getMonth()
    const dayIndex = today.getDate() - 1
    datePickerValue.value = [yearIndex >= 0 ? yearIndex : 0, monthIndex, dayIndex]
    datePopup.value?.open()
}

const openTimePicker = () => {
    tempTimeSlot.value = formData.new_time_slot ?? 0
    timePopup.value?.open()
}

// 使用 watch 或直接在模板中调用
const chooseImage = () => {
    uni.chooseImage({
        count: 5 - formData.attach_images.length,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: (res) => {
            // 这里应该先上传到服务器，获取URL后再添加
            // 简化处理：直接使用本地路径
            formData.attach_images.push(...res.tempFilePaths)
        }
    })
}

const removeImage = (index: number) => {
    formData.attach_images.splice(index, 1)
}

const handleSubmit = async () => {
    if (!formData.new_date) {
        uni.showToast({ title: '请选择新服务日期', icon: 'none' })
        return
    }
    if (formData.new_time_slot === null) {
        uni.showToast({ title: '请选择时间段', icon: 'none' })
        return
    }

    submitting.value = true
    try {
        const res = await applyDateChange({
            order_id: orderId.value,
            new_date: formData.new_date,
            new_time_slot: formData.new_time_slot,
            reason: formData.reason,
            attach_images: formData.attach_images
        })
        uni.showToast({ title: '申请已提交' })
        setTimeout(() => {
            uni.redirectTo({ url: `/pages/order_change/change_detail?id=${res.change_id}` })
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

// 处理点击事件
watch(
    () => showDatePicker.value,
    (val) => {
        if (val) openDatePicker()
    }
)
watch(
    () => showTimePicker.value,
    (val) => {
        if (val) openTimePicker()
    }
)
</script>

<style lang="scss" scoped>
.apply-page {
    min-height: 100vh;
    background-color: #f5f5f5;
    padding-bottom: 140rpx;
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
    justify-content: space-between;
    align-items: center;
    padding: 24rpx 0;
    border-bottom: 1rpx solid #f5f5f5;

    .label {
        font-size: 28rpx;
        color: #333;
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

.reason-input {
    width: 100%;
    height: 200rpx;
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
    width: 160rpx;
    height: 160rpx;
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
    width: 160rpx;
    height: 160rpx;
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

.picker-popup {
    background: #fff;
    border-radius: 24rpx 24rpx 0 0;
}

.picker-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 30rpx;
    border-bottom: 1rpx solid #f5f5f5;

    .cancel {
        color: #999;
        font-size: 28rpx;
    }
    .title {
        font-size: 30rpx;
        font-weight: bold;
    }
    .confirm {
        color: var(--primary-color, #ff6b35);
        font-size: 28rpx;
    }
}

.picker-view {
    height: 400rpx;
}

.picker-item {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30rpx;
}

.time-options {
    padding: 30rpx;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20rpx;
}

.time-option {
    padding: 24rpx;
    text-align: center;
    border: 2rpx solid #eee;
    border-radius: 12rpx;
    font-size: 28rpx;

    &.active {
        border-color: var(--primary-color, #ff6b35);
        color: var(--primary-color, #ff6b35);
        background: rgba(255, 107, 53, 0.05);
    }
}
</style>
