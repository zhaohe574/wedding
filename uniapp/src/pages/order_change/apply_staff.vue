<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="换人申请"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="apply-page">
        <!-- 订单信息 -->
        <view class="bg-white p-4" v-if="orderInfo">
            <view class="section-title">当前订单</view>
            <view class="order-card">
                <view class="text-sm text-gray-500">订单号: {{ orderInfo.order_sn }}</view>
                <view class="mt-2 text-sm text-gray-500">
                    服务日期: {{ orderInfo.service_date }}
                </view>
            </view>
        </view>

        <!-- 当前人员 -->
        <view class="bg-white mt-3 p-4" v-if="currentItem">
            <view class="section-title">当前服务人员</view>
            <view class="staff-card current">
                <image
                    :src="currentItem.staff?.avatar || '/static/images/default-avatar.png'"
                    class="staff-avatar"
                    mode="aspectFill"
                />
                <view class="staff-info">
                    <view class="staff-name">{{ currentItem.staff_name }}</view>
                    <view class="staff-package">{{ currentItem.package_name }}</view>
                    <view class="staff-price">¥{{ currentItem.price }}</view>
                </view>
                <view class="current-tag">当前</view>
            </view>
        </view>

        <!-- 选择新人员 -->
        <view class="bg-white mt-3 p-4">
            <view class="section-title">选择新服务人员</view>
            <view class="form-item" @click="showStaffPicker = true">
                <text class="label">新人员</text>
                <view class="value-area">
                    <text v-if="selectedStaff" class="text-primary">{{ selectedStaff.name }}</text>
                    <text v-else class="placeholder">请选择要更换的服务人员</text>
                    <uni-icons type="right" size="16" color="#999"></uni-icons>
                </view>
            </view>

            <!-- 新人员信息展示 -->
            <view v-if="selectedStaff" class="staff-card new mt-3">
                <image
                    :src="selectedStaff.avatar || '/static/images/default-avatar.png'"
                    class="staff-avatar"
                    mode="aspectFill"
                />
                <view class="staff-info">
                    <view class="staff-name">{{ selectedStaff.name }}</view>
                    <view class="staff-level">{{ selectedStaff.level_name || '普通级别' }}</view>
                    <view class="flex items-center mt-1">
                        <uni-icons type="star-filled" size="14" color="#ffc107"></uni-icons>
                        <text class="text-xs text-yellow-500 ml-1">{{
                            selectedStaff.score || '5.0'
                        }}</text>
                    </view>
                </view>
                <view class="new-tag">新</view>
            </view>

            <!-- 选择套餐 -->
            <view class="form-item mt-3" @click="showPackagePicker = true" v-if="selectedStaff">
                <text class="label">服务套餐</text>
                <view class="value-area">
                    <text v-if="selectedPackage" class="text-primary">{{
                        selectedPackage.name
                    }}</text>
                    <text v-else class="placeholder">请选择套餐</text>
                    <uni-icons type="right" size="16" color="#999"></uni-icons>
                </view>
            </view>
        </view>

        <!-- 价格对比 -->
        <view class="bg-white mt-3 p-4" v-if="selectedStaff && selectedPackage && currentItem">
            <view class="section-title">价格对比</view>
            <view class="price-compare">
                <view class="price-row">
                    <text class="text-sm text-gray-500">原服务价格</text>
                    <text class="text-sm">¥{{ currentItem.price }}</text>
                </view>
                <view class="price-row">
                    <text class="text-sm text-gray-500">新服务价格</text>
                    <text class="text-sm">¥{{ newPrice }}</text>
                </view>
                <view
                    class="price-row diff"
                    :class="{ positive: priceDiff > 0, negative: priceDiff < 0 }"
                >
                    <text class="text-sm font-medium">价格差额</text>
                    <text class="text-lg font-bold">
                        {{ priceDiff > 0 ? '+' : '' }}¥{{ priceDiff }}
                    </text>
                </view>
            </view>
            <view class="tip-box mt-3" v-if="priceDiff > 0">
                <uni-icons type="info" size="14" color="#ff9800"></uni-icons>
                <text class="text-xs text-orange-500 ml-1">换人后需补差价 ¥{{ priceDiff }}</text>
            </view>
            <view class="tip-box success mt-3" v-else-if="priceDiff < 0">
                <uni-icons type="info" size="14" color="#4caf50"></uni-icons>
                <text class="text-xs text-green-500 ml-1"
                    >换人后可退差价 ¥{{ Math.abs(priceDiff) }}</text
                >
            </view>
        </view>

        <!-- 申请原因 -->
        <view class="bg-white mt-3 p-4">
            <view class="section-title">申请原因</view>
            <textarea
                v-model="formData.reason"
                class="reason-input"
                placeholder="请填写换人原因（选填）"
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
            <button class="btn-submit" :disabled="submitting || !canSubmit" @click="handleSubmit">
                {{ submitting ? '提交中...' : '提交申请' }}
            </button>
        </view>

        <!-- 人员选择弹窗 -->
        <uni-popup ref="staffPopup" type="bottom" :safe-area="false">
            <view class="picker-popup">
                <view class="picker-header">
                    <text class="cancel" @click="staffPopup?.close()">取消</text>
                    <text class="title">选择人员</text>
                    <text class="placeholder-btn"></text>
                </view>
                <scroll-view class="picker-list" scroll-y>
                    <view
                        v-for="item in availableStaffList"
                        :key="item.id"
                        class="picker-item-card"
                        :class="{ active: selectedStaff?.id === item.id }"
                        @click="selectStaff(item)"
                    >
                        <image
                            :src="item.avatar || '/static/images/default-avatar.png'"
                            class="item-avatar"
                            mode="aspectFill"
                        />
                        <view class="item-info">
                            <view class="item-name">{{ item.name }}</view>
                            <view class="item-desc">{{ item.level_name || '普通级别' }}</view>
                            <view class="flex items-center mt-1">
                                <uni-icons type="star-filled" size="12" color="#ffc107"></uni-icons>
                                <text class="text-xs text-yellow-500 ml-1">{{
                                    item.score || '5.0'
                                }}</text>
                            </view>
                        </view>
                        <uni-icons
                            v-if="selectedStaff?.id === item.id"
                            type="checkmarkempty"
                            size="20"
                            color="#ff6b35"
                        ></uni-icons>
                    </view>
                    <view v-if="availableStaffList.length === 0" class="empty-tip">
                        <text>暂无可选人员</text>
                    </view>
                </scroll-view>
            </view>
        </uni-popup>

        <!-- 套餐选择弹窗 -->
        <uni-popup ref="packagePopup" type="bottom" :safe-area="false">
            <view class="picker-popup">
                <view class="picker-header">
                    <text class="cancel" @click="packagePopup?.close()">取消</text>
                    <text class="title">选择套餐</text>
                    <text class="placeholder-btn"></text>
                </view>
                <scroll-view class="picker-list" scroll-y>
                    <view
                        v-for="item in staffPackageList"
                        :key="item.id"
                        class="picker-item-card"
                        :class="{ active: selectedPackage?.id === item.id }"
                        @click="selectPackage(item)"
                    >
                        <view class="item-info flex-1">
                            <view class="item-name">{{ item.name }}</view>
                            <view class="item-desc">{{ item.description }}</view>
                            <view class="item-price">¥{{ item.price }}</view>
                        </view>
                        <uni-icons
                            v-if="selectedPackage?.id === item.id"
                            type="checkmarkempty"
                            size="20"
                            color="#ff6b35"
                        ></uni-icons>
                    </view>
                    <view v-if="staffPackageList.length === 0" class="empty-tip">
                        <text>暂无可选套餐</text>
                    </view>
                </scroll-view>
            </view>
        </uni-popup>
    </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { applyStaffChange, checkCanChange } from '@/api/orderChange'
import { getOrderDetail } from '@/api/order'
import { getStaffList, getStaffPackages } from '@/api/staff'

const orderId = ref(0)
const itemId = ref(0) // 要换人的订单项ID
const orderInfo = ref<any>(null)
const currentItem = ref<any>(null) // 当前订单项信息
const submitting = ref(false)

// 选项数据
const staffList = ref<any[]>([])
const staffPackageList = ref<any[]>([])

// 选中项
const selectedStaff = ref<any>(null)
const selectedPackage = ref<any>(null)

// 弹窗显示控制
const showStaffPicker = ref(false)
const showPackagePicker = ref(false)

const formData = reactive({
    reason: '',
    attach_images: [] as string[]
})

// 弹窗 refs
const staffPopup = ref()
const packagePopup = ref()

// 过滤掉当前人员的列表
const availableStaffList = computed(() => {
    if (!currentItem.value) return staffList.value
    return staffList.value.filter((s) => s.id !== currentItem.value.staff_id)
})

// 计算新价格
const newPrice = computed(() => {
    if (!selectedPackage.value) return 0
    // 新价格 = 人员基础价格 + 套餐价格 或者直接使用套餐定价
    return selectedPackage.value.price || 0
})

// 计算价格差额
const priceDiff = computed(() => {
    if (!currentItem.value || !selectedPackage.value) return 0
    return newPrice.value - currentItem.value.price
})

// 是否可以提交
const canSubmit = computed(() => {
    return selectedStaff.value && selectedPackage.value
})

// 获取订单信息
const fetchOrderInfo = async () => {
    try {
        const res = await getOrderDetail({ id: orderId.value })
        orderInfo.value = res
        // 找到要换人的订单项
        if (res.items && itemId.value) {
            currentItem.value = res.items.find((item: any) => item.id === itemId.value)
        } else if (res.items && res.items.length > 0) {
            // 默认取第一个订单项
            currentItem.value = res.items[0]
            itemId.value = currentItem.value.id
        }
    } catch (e) {
        console.error(e)
    }
}

// 检查订单是否可变更
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

// 获取人员列表
const fetchStaffList = async () => {
    try {
        const res = await getStaffList({ page: 1, limit: 100 })
        staffList.value = res.lists || res || []
    } catch (e) {
        console.error(e)
    }
}

// 获取人员关联套餐
const fetchStaffPackages = async (staffId: number) => {
    try {
        const res = await getStaffPackages({ staff_id: staffId })
        staffPackageList.value = res.lists || res || []
    } catch (e) {
        console.error(e)
    }
}

// 选择人员
const selectStaff = (item: any) => {
    selectedStaff.value = item
    selectedPackage.value = null
    staffPackageList.value = []
    fetchStaffPackages(item.id)
    staffPopup.value?.close()
}

// 选择套餐
const selectPackage = (item: any) => {
    selectedPackage.value = item
    packagePopup.value?.close()
}

// 图片上传
const chooseImage = () => {
    uni.chooseImage({
        count: 5 - formData.attach_images.length,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: (res) => {
            formData.attach_images.push(...res.tempFilePaths)
        }
    })
}

const removeImage = (index: number) => {
    formData.attach_images.splice(index, 1)
}

// 提交申请
const handleSubmit = async () => {
    if (!canSubmit.value) {
        uni.showToast({ title: '请选择新服务人员和套餐', icon: 'none' })
        return
    }

    submitting.value = true
    try {
        const params = {
            order_id: orderId.value,
            item_id: itemId.value,
            new_staff_id: selectedStaff.value.id,
            new_package_id: selectedPackage.value.id,
            reason: formData.reason,
            attach_images: formData.attach_images
        }

        const res = await applyStaffChange(params)
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

// 监听弹窗显示
watch(
    () => showStaffPicker.value,
    (val) => {
        if (val) staffPopup.value?.open()
    }
)
watch(
    () => showPackagePicker.value,
    (val) => {
        if (val) packagePopup.value?.open()
    }
)

onLoad((options: any) => {
    if (options.order_id) {
        orderId.value = Number(options.order_id)
    }
    if (options.item_id) {
        itemId.value = Number(options.item_id)
    }
    fetchOrderInfo()
    checkOrder()
    fetchStaffList()
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
    border-left: 6rpx solid var(--primary-color, #ff6b35);
}

.order-card {
    padding: 20rpx;
    background: #f9f9f9;
    border-radius: 12rpx;
}

.staff-card {
    display: flex;
    align-items: center;
    padding: 20rpx;
    background: #f9f9f9;
    border-radius: 12rpx;
    position: relative;

    &.current {
        border: 2rpx solid #e0e0e0;
    }

    &.new {
        border: 2rpx solid var(--primary-color, #ff6b35);
        background: rgba(255, 107, 53, 0.05);
    }
}

.staff-avatar {
    width: 100rpx;
    height: 100rpx;
    border-radius: 50%;
    margin-right: 20rpx;
}

.staff-info {
    flex: 1;
}

.staff-name {
    font-size: 30rpx;
    font-weight: bold;
    color: #333;
}

.staff-package,
.staff-level {
    font-size: 24rpx;
    color: #999;
    margin-top: 8rpx;
}

.staff-price {
    font-size: 28rpx;
    color: var(--primary-color, #ff6b35);
    font-weight: bold;
    margin-top: 8rpx;
}

.current-tag,
.new-tag {
    position: absolute;
    top: 0;
    right: 0;
    padding: 8rpx 16rpx;
    font-size: 22rpx;
    border-radius: 0 12rpx 0 12rpx;
}

.current-tag {
    background: #e0e0e0;
    color: #666;
}

.new-tag {
    background: var(--primary-color, #ff6b35);
    color: #fff;
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

.price-compare {
    background: #f9f9f9;
    border-radius: 12rpx;
    padding: 20rpx;
}

.price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12rpx 0;

    &.diff {
        border-top: 1rpx dashed #e0e0e0;
        margin-top: 12rpx;
        padding-top: 20rpx;

        &.positive text:last-child {
            color: #f44336;
        }

        &.negative text:last-child {
            color: #4caf50;
        }
    }
}

.tip-box {
    display: flex;
    align-items: center;
    padding: 16rpx 20rpx;
    background: #fff7e6;
    border-radius: 8rpx;

    &.success {
        background: #e8f5e9;
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
    .placeholder-btn {
        width: 60rpx;
    }
}

.picker-list {
    max-height: 600rpx;
    padding: 20rpx;
}

.picker-item-card {
    display: flex;
    align-items: center;
    padding: 20rpx;
    margin-bottom: 16rpx;
    background: #f9f9f9;
    border-radius: 12rpx;
    border: 2rpx solid transparent;

    &.active {
        border-color: var(--primary-color, #ff6b35);
        background: rgba(255, 107, 53, 0.05);
    }
}

.item-avatar {
    width: 80rpx;
    height: 80rpx;
    border-radius: 50%;
    margin-right: 20rpx;
}

.item-info {
    flex: 1;
}

.item-name {
    font-size: 28rpx;
    font-weight: bold;
    color: #333;
}

.item-desc {
    font-size: 24rpx;
    color: #999;
    margin-top: 8rpx;
}

.item-price {
    font-size: 28rpx;
    color: var(--primary-color, #ff6b35);
    font-weight: bold;
    margin-top: 8rpx;
}

.empty-tip {
    text-align: center;
    padding: 60rpx;
    color: #999;
    font-size: 28rpx;
}
</style>
