<template>
    <page-meta :page-style="$theme.pageStyle" />
    <BaseNavbar title="加项申请" />
    <view class="apply-page">
        <!-- 订单信息 -->
        <view class="bg-white p-4" v-if="orderInfo">
            <view class="section-title">当前订单</view>
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
                <view class="mt-2 text-sm text-gray-500">
                    服务日期: {{ orderInfo.service_date }}
                </view>
            </view>
        </view>

        <!-- 选择加项类型 -->
        <view class="bg-white mt-3 p-4">
            <view class="section-title">加项类型</view>
            <view class="type-options">
                <view
                    class="type-option"
                    :class="{ active: addType === 'package' }"
                    @click="addType = 'package'"
                >
                    <tn-icon
                        name="gift"
                        size="56rpx"
                        :color="addType === 'package' ? $theme.primaryColor : '#999'"
                    ></tn-icon>
                    <text>添加套餐</text>
                </view>
                <view
                    class="type-option"
                    :class="{ active: addType === 'staff' }"
                    @click="addType = 'staff'"
                >
                    <tn-icon
                        name="my"
                        size="56rpx"
                        :color="addType === 'staff' ? $theme.primaryColor : '#999'"
                    ></tn-icon>
                    <text>添加人员</text>
                </view>
            </view>
        </view>

        <!-- 选择套餐 (当 addType === 'package') -->
        <view class="bg-white mt-3 p-4" v-if="addType === 'package'">
            <view class="section-title">选择套餐</view>
            <view class="form-item" @click="showPackagePicker = true">
                <text class="label">套餐</text>
                <view class="value-area">
                    <text v-if="selectedPackage" class="text-primary">{{
                        selectedPackage.name
                    }}</text>
                    <text v-else class="placeholder">请选择要添加的套餐</text>
                    <tn-icon name="right" size="32rpx" color="#999"></tn-icon>
                </view>
            </view>
            <view v-if="selectedPackage" class="selected-item mt-3">
                <view class="flex items-center">
                    <image
                        :src="selectedPackage.image || '/static/images/default-package.png'"
                        class="w-16 h-16 rounded-lg mr-3"
                        mode="aspectFill"
                    />
                    <view class="flex-1">
                        <view class="text-sm font-medium">{{ selectedPackage.name }}</view>
                        <view class="text-xs text-gray-400 mt-1">{{
                            selectedPackage.description
                        }}</view>
                    </view>
                </view>
                <view class="flex justify-between items-center mt-2 pt-2 border-t border-gray-100">
                    <text class="text-sm text-gray-500">套餐价格</text>
                    <text class="text-lg font-bold text-primary">¥{{ selectedPackage.price }}</text>
                </view>
            </view>
        </view>

        <!-- 选择人员 (当 addType === 'staff') -->
        <view class="bg-white mt-3 p-4" v-if="addType === 'staff'">
            <view class="section-title">选择服务人员</view>
            <view class="form-item" @click="showStaffPicker = true">
                <text class="label">人员</text>
                <view class="value-area">
                    <text v-if="selectedStaff" class="text-primary">{{ selectedStaff.name }}</text>
                    <text v-else class="placeholder">请选择要添加的服务人员</text>
                    <tn-icon name="right" size="32rpx" color="#999"></tn-icon>
                </view>
            </view>
            <view v-if="selectedStaff" class="selected-item mt-3">
                <view class="flex items-center">
                    <image
                        :src="selectedStaff.avatar || '/static/images/user/default_avatar.png'"
                        class="w-16 h-16 rounded-full mr-3"
                        mode="aspectFill"
                    />
                    <view class="flex-1">
                        <view class="text-sm font-medium">{{ selectedStaff.name }}</view>
                        <view class="text-xs text-gray-400 mt-1">{{
                            selectedStaff.level_name || '普通级别'
                        }}</view>
                        <view class="flex items-center mt-1">
                            <tn-icon name="star-fill" size="28rpx" color="#ffc107"></tn-icon>
                            <text class="text-xs text-yellow-500 ml-1">{{
                                selectedStaff.score || '5.0'
                            }}</text>
                        </view>
                    </view>
                </view>
                <view class="flex justify-between items-center mt-2 pt-2 border-t border-gray-100">
                    <text class="text-sm text-gray-500">服务价格</text>
                    <text class="text-lg font-bold text-primary">¥{{ selectedStaff.price }}</text>
                </view>
            </view>

            <!-- 选择套餐（为人员选择套餐） -->
            <view
                class="form-item mt-3"
                @click="showStaffPackagePicker = true"
                v-if="selectedStaff"
            >
                <text class="label">关联套餐</text>
                <view class="value-area">
                    <text v-if="selectedStaffPackage" class="text-primary">{{
                        selectedStaffPackage.name
                    }}</text>
                    <text v-else class="placeholder">请选择服务套餐</text>
                    <tn-icon name="right" size="32rpx" color="#999"></tn-icon>
                </view>
            </view>
        </view>

        <!-- 选择服务日期 -->
        <view class="bg-white mt-3 p-4">
            <view class="section-title">服务日期</view>
            <view class="form-item" @click="openDatePicker">
                <text class="label">服务日期</text>
                <view class="value-area">
                    <text v-if="formData.service_date" class="text-primary">{{
                        formData.service_date
                    }}</text>
                    <text v-else class="placeholder">请选择服务日期</text>
                    <tn-icon name="right" size="32rpx" color="#999"></tn-icon>
                </view>
            </view>
            <view class="tip-box mt-3">
                <tn-icon name="tip" size="28rpx" color="#ff9800"></tn-icon>
                <text class="text-xs text-orange-500 ml-1">加项服务日期可与原订单日期不同</text>
            </view>
        </view>

        <!-- 申请原因 -->
        <view class="bg-white mt-3 p-4">
            <view class="section-title">申请原因</view>
            <textarea
                v-model="formData.reason"
                class="reason-input"
                placeholder="请填写加项原因（选填）"
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

        <!-- 费用预览 -->
        <view class="bg-white mt-3 p-4" v-if="totalPrice > 0">
            <view class="section-title">费用预览</view>
            <view class="price-preview">
                <view class="price-item">
                    <text class="text-sm text-gray-500">{{
                        addType === 'package' ? '套餐费用' : '人员费用'
                    }}</text>
                    <text class="text-sm">¥{{ basePrice }}</text>
                </view>
                <view class="price-item" v-if="addType === 'staff' && selectedStaffPackage">
                    <text class="text-sm text-gray-500">套餐费用</text>
                    <text class="text-sm">¥{{ selectedStaffPackage.price }}</text>
                </view>
                <view class="price-item total">
                    <text class="text-sm font-medium">预计费用</text>
                    <text class="text-lg font-bold text-primary">¥{{ totalPrice }}</text>
                </view>
            </view>
            <view class="tip-box mt-3">
                <tn-icon name="tip" size="28rpx" color="#999"></tn-icon>
                <text class="text-xs text-gray-400 ml-1">最终费用以审核结果为准</text>
            </view>
        </view>

        <!-- 提交按钮 -->
        <view class="bottom-actions">
            <button class="btn-submit" :disabled="submitting || !canSubmit" :style="{ background: $theme.primaryColor }" @click="handleSubmit">
                {{ submitting ? '提交中...' : '提交申请' }}
            </button>
        </view>

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
                        v-for="item in packageList"
                        :key="item.id"
                        class="picker-item-card"
                        :class="{ active: selectedPackage?.id === item.id }"
                        @click="selectPackage(item)"
                    >
                        <image
                            :src="item.image || '/static/images/default-package.png'"
                            class="item-image"
                            mode="aspectFill"
                        />
                        <view class="item-info">
                            <view class="item-name">{{ item.name }}</view>
                            <view class="item-desc">{{ item.description }}</view>
                            <view class="item-price">¥{{ item.price }}</view>
                        </view>
                        <tn-icon
                            v-if="selectedPackage?.id === item.id"
                            name="success"
                            size="40rpx"
                            :color="$theme.primaryColor"
                        ></tn-icon>
                    </view>
                    <view v-if="packageList.length === 0" class="empty-tip">
                        <text>暂无可选套餐</text>
                    </view>
                </scroll-view>
            </view>
        </uni-popup>

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
                        v-for="item in staffList"
                        :key="item.id"
                        class="picker-item-card"
                        :class="{ active: selectedStaff?.id === item.id }"
                        @click="selectStaff(item)"
                    >
                        <image
                            :src="item.avatar || '/static/images/user/default_avatar.png'"
                            class="item-avatar"
                            mode="aspectFill"
                        />
                        <view class="item-info">
                            <view class="item-name">{{ item.name }}</view>
                            <view class="item-desc">{{ item.level_name || '普通级别' }}</view>
                            <view class="item-price">¥{{ item.price }}</view>
                        </view>
                        <tn-icon
                            v-if="selectedStaff?.id === item.id"
                            name="success"
                            size="40rpx"
                            :color="$theme.primaryColor"
                        ></tn-icon>
                    </view>
                    <view v-if="staffList.length === 0" class="empty-tip">
                        <text>暂无可选人员</text>
                    </view>
                </scroll-view>
            </view>
        </uni-popup>

        <!-- 人员关联套餐选择弹窗 -->
        <uni-popup ref="staffPackagePopup" type="bottom" :safe-area="false">
            <view class="picker-popup">
                <view class="picker-header">
                    <text class="cancel" @click="staffPackagePopup?.close()">取消</text>
                    <text class="title">选择套餐</text>
                    <text class="placeholder-btn"></text>
                </view>
                <scroll-view class="picker-list" scroll-y>
                    <view
                        v-for="item in staffPackageList"
                        :key="item.id"
                        class="picker-item-card"
                        :class="{ active: selectedStaffPackage?.id === item.id }"
                        @click="selectStaffPackage(item)"
                    >
                        <view class="item-info flex-1">
                            <view class="item-name">{{ item.name }}</view>
                            <view class="item-desc">{{ item.description }}</view>
                            <view class="item-price">¥{{ item.price }}</view>
                        </view>
                        <tn-icon
                            v-if="selectedStaffPackage?.id === item.id"
                            name="success"
                            size="40rpx"
                            :color="$theme.primaryColor"
                        ></tn-icon>
                    </view>
                    <view v-if="staffPackageList.length === 0" class="empty-tip">
                        <text>暂无可选套餐</text>
                    </view>
                </scroll-view>
            </view>
        </uni-popup>

        <!-- 日期选择器 -->
        <uni-popup ref="datePopup" type="bottom" :safe-area="false">
            <view class="picker-popup">
                <view class="picker-header">
                    <text class="cancel" @click="datePopup?.close()">取消</text>
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
import { ref, reactive, computed, watch } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { applyAddItem, checkCanChange } from '@/api/orderChange'
import { getOrderDetail } from '@/api/order'
import { getStaffList, getStaffPackages } from '@/api/staff'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()

const orderId = ref(0)
const orderInfo = ref<any>(null)
const submitting = ref(false)
const addType = ref<'package' | 'staff'>('package')

// 选项数据
const packageList = ref<any[]>([])
const staffList = ref<any[]>([])
const staffPackageList = ref<any[]>([])

// 选中项
const selectedPackage = ref<any>(null)
const selectedStaff = ref<any>(null)
const selectedStaffPackage = ref<any>(null)

// 弹窗显示控制
const showPackagePicker = ref(false)
const showStaffPicker = ref(false)
const showStaffPackagePicker = ref(false)

const formData = reactive({
    service_date: '',
    reason: '',
    attach_images: [] as string[]
})

// 弹窗 refs
const packagePopup = ref()
const staffPopup = ref()
const staffPackagePopup = ref()
const datePopup = ref()

// 日期选择器相关
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

// 计算价格
const basePrice = computed(() => {
    if (addType.value === 'package') {
        return selectedPackage.value?.price || 0
    } else {
        return selectedStaff.value?.price || 0
    }
})

const totalPrice = computed(() => {
    if (addType.value === 'package') {
        return selectedPackage.value?.price || 0
    } else {
        const staffPrice = selectedStaff.value?.price || 0
        const packagePrice = selectedStaffPackage.value?.price || 0
        return staffPrice + packagePrice
    }
})

const currentOrderStaffId = computed(() => {
    const firstItem = orderInfo.value?.items?.[0]
    return Number(firstItem?.staff_id || 0)
})

// 是否可以提交
const canSubmit = computed(() => {
    if (addType.value === 'package') {
        return selectedPackage.value && formData.service_date && currentOrderStaffId.value > 0
    } else {
        return (
            selectedStaff.value &&
            selectedStaffPackage.value &&
            formData.service_date
        )
    }
})

// 获取订单信息
const fetchOrderInfo = async () => {
    try {
        const res = await getOrderDetail({ id: orderId.value })
        orderInfo.value = res
        if (currentOrderStaffId.value > 0) {
            await fetchPackageList(currentOrderStaffId.value)
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

// 获取套餐列表
const fetchPackageList = async (staffId: number) => {
    if (!staffId) {
        packageList.value = []
        return
    }
    try {
        const res = await getStaffPackages({ staff_id: staffId })
        packageList.value = res.lists || res || []
    } catch (e) {
        console.error(e)
        packageList.value = []
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
        staffPackageList.value = res.lists || []
    } catch (e) {
        console.error(e)
    }
}

// 选择套餐
const selectPackage = (item: any) => {
    selectedPackage.value = item
    packagePopup.value?.close()
}

// 选择人员
const selectStaff = (item: any) => {
    selectedStaff.value = item
    selectedStaffPackage.value = null
    staffPackageList.value = []
    fetchStaffPackages(item.id)
    staffPopup.value?.close()
}

// 选择人员关联套餐
const selectStaffPackage = (item: any) => {
    selectedStaffPackage.value = item
    staffPackagePopup.value?.close()
}

// 日期选择
const onDateChange = (e: any) => {
    datePickerValue.value = e.detail.value
}

const openDatePicker = () => {
    const today = new Date()
    const yearIndex = years.indexOf(today.getFullYear())
    const monthIndex = today.getMonth()
    const dayIndex = today.getDate() - 1
    datePickerValue.value = [yearIndex >= 0 ? yearIndex : 0, monthIndex, dayIndex]
    datePopup.value?.open()
}

const confirmDate = () => {
    const year = years[datePickerValue.value[0]]
    const month = String(months[datePickerValue.value[1]]).padStart(2, '0')
    const day = String(days.value[datePickerValue.value[2]]).padStart(2, '0')
    formData.service_date = `${year}-${month}-${day}`
    datePopup.value?.close()
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
        uni.showToast({ title: '请完善申请信息', icon: 'none' })
        return
    }

    submitting.value = true
    try {
        const params: any = {
            order_id: orderId.value,
            add_type: addType.value,
            service_date: formData.service_date,
            reason: formData.reason,
            attach_images: formData.attach_images
        }

        if (addType.value === 'package') {
            params.staff_id = currentOrderStaffId.value
            params.package_id = selectedPackage.value.id
        } else {
            params.staff_id = selectedStaff.value.id
            params.package_id = selectedStaffPackage.value.id
        }

        const res = await applyAddItem(params)
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

// 监听弹窗显示
watch(
    () => showPackagePicker.value,
    (val) => {
        if (val) packagePopup.value?.open()
    }
)
watch(
    () => showStaffPicker.value,
    (val) => {
        if (val) staffPopup.value?.open()
    }
)
watch(
    () => showStaffPackagePicker.value,
    (val) => {
        if (val) staffPackagePopup.value?.open()
    }
)

// 切换类型时重置选择
watch(addType, () => {
    selectedPackage.value = null
    selectedStaff.value = null
    selectedStaffPackage.value = null
    staffPackageList.value = []
    if (addType.value === 'package') {
        fetchPackageList(currentOrderStaffId.value)
    }
})

onLoad((options: any) => {
    if (options.order_id) {
        orderId.value = Number(options.order_id)
        fetchOrderInfo()
        checkOrder()
    }
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
    border-left: 6rpx solid var(--color-primary, #E85A4F);
}

.order-card {
    padding: 20rpx;
    background: #f9f9f9;
    border-radius: 12rpx;
}

.type-options {
    display: flex;
    gap: 20rpx;
}

.type-option {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30rpx;
    background: #f9f9f9;
    border-radius: 12rpx;
    border: 2rpx solid transparent;

    text {
        font-size: 26rpx;
        color: #666;
        margin-top: 10rpx;
    }

    &.active {
        border-color: var(--color-primary, #E85A4F);
        background: rgba(124, 58, 237, 0.05);

        text {
            color: var(--color-primary, #E85A4F);
        }
    }
}

.selected-item {
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

.tip-box {
    display: flex;
    align-items: center;
    padding: 16rpx 20rpx;
    background: #fff7e6;
    border-radius: 8rpx;
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

.price-preview {
    background: #f9f9f9;
    border-radius: 12rpx;
    padding: 20rpx;
}

.price-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12rpx 0;

    &.total {
        border-top: 1rpx dashed #e0e0e0;
        margin-top: 12rpx;
        padding-top: 20rpx;
    }
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
    background: var(--color-primary, #E85A4F);
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
        color: var(--color-primary, #E85A4F);
        font-size: 28rpx;
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
        border-color: var(--color-primary, #E85A4F);
        background: rgba(124, 58, 237, 0.05);
    }
}

.item-image {
    width: 120rpx;
    height: 120rpx;
    border-radius: 8rpx;
    margin-right: 20rpx;
}

.item-avatar {
    width: 100rpx;
    height: 100rpx;
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
    color: var(--color-primary, #E85A4F);
    font-weight: bold;
    margin-top: 8rpx;
}

.empty-tip {
    text-align: center;
    padding: 60rpx;
    color: #999;
    font-size: 28rpx;
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
