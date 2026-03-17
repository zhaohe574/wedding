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
                            orderInfo.items[0].staff?.avatar ||
                            '/static/images/user/default_avatar.png'
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
            <view class="form-item" @click="openDatePicker">
                <text class="label">新服务日期</text>
                <view class="value-area">
                    <text v-if="formData.new_date" class="text-primary">{{
                        formData.new_date
                    }}</text>
                    <text v-else class="placeholder">请选择日期</text>
                    <tn-icon name="right" size="32rpx" color="#999"></tn-icon>
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
                        <tn-icon name="close" size="28rpx" color="#fff"></tn-icon>
                    </view>
                </view>
                <view
                    class="add-image"
                    @click="chooseImage"
                    v-if="formData.attach_images.length < 5"
                >
                    <tn-icon name="add" size="80rpx" color="#ccc"></tn-icon>
                    <text class="text-xs text-gray-400 mt-1">上传图片</text>
                </view>
            </view>
            <view class="text-xs text-gray-400 mt-2">最多上传5张图片，支持jpg、png格式</view>
        </view>

        <!-- 提交按钮 -->
        <view class="bottom-actions">
            <button class="btn-submit" :disabled="submitting" :style="{ background: $theme.primaryColor }" @click="handleSubmit">
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
    </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { applyDateChange, checkCanChange } from '@/api/orderChange'
import { getOrderDetail } from '@/api/order'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()

const orderId = ref(0)
const orderInfo = ref<any>(null)
const submitting = ref(false)

const formData = reactive({
    new_date: '',
    reason: '',
    attach_images: [] as string[]
})

// 日期选择器相关
const datePopup = ref()
const datePickerValue = ref([0, 0, 0])

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

const openDatePicker = () => {
    // 设置默认值为今天
    const today = new Date()
    const yearIndex = years.indexOf(today.getFullYear())
    const monthIndex = today.getMonth()
    const dayIndex = today.getDate() - 1
    datePickerValue.value = [yearIndex >= 0 ? yearIndex : 0, monthIndex, dayIndex]
    datePopup.value?.open()
}

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

    submitting.value = true
    try {
        const res = await applyDateChange({
            order_id: orderId.value,
            new_date: formData.new_date,
            reason: formData.reason,
            attach_images: formData.attach_images
        })
        uni.showToast({ title: '申请已提交' })
        setTimeout(() => {
            uni.redirectTo({
                url: `/packages/pages/order_change/change_detail?id=${res.change_id}`
            })
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

.section-title {
    font-size: 28rpx;
    font-weight: bold;
    color: #333;
    margin-bottom: 20rpx;
    padding-left: 16rpx;
    border-left: 6rpx solid var(--color-primary, #7C3AED);
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
    height: 72rpx;
    line-height: 72rpx;
    background: var(--color-primary, #7C3AED);
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
        color: var(--color-primary, #7C3AED);
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

</style>
