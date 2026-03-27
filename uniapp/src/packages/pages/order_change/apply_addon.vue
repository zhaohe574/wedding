<template>
    <page-meta :page-style="$theme.pageStyle" />
    <BaseNavbar title="附加服务变更" />
    <view class="apply-addon-page">
        <view class="bg-white p-4" v-if="orderInfo">
            <view class="section-title">当前订单</view>
            <view class="order-card">
                <view class="text-sm text-gray-500">订单号：{{ orderInfo.order_sn }}</view>
                <view class="mt-2 text-sm text-gray-500">服务日期：{{ orderInfo.service_date || '-' }}</view>
            </view>
        </view>

        <view class="bg-white mt-3 p-4" v-if="orderItems.length">
            <view class="section-title">选择主服务项</view>
            <view class="item-list">
                <view
                    v-for="item in orderItems"
                    :key="item.id"
                    class="item-card"
                    :class="{ active: selectedOrderItemId === item.id }"
                    @click="selectOrderItem(item)"
                >
                    <view class="item-card__main">
                        <view class="item-card__title">{{ item.staff_name }} / {{ item.package_name }}</view>
                        <view class="item-card__meta">服务日期：{{ item.service_date || orderInfo.service_date || '-' }}</view>
                        <view class="item-card__meta">已选附加服务：{{ item.addons?.length || 0 }} 个</view>
                    </view>
                    <tn-icon
                        v-if="selectedOrderItemId === item.id"
                        name="success"
                        size="40rpx"
                        :color="$theme.primaryColor"
                    />
                </view>
            </view>
        </view>

        <view class="bg-white mt-3 p-4">
            <view class="section-title">变更动作</view>
            <view class="action-tabs">
                <view
                    class="action-tab"
                    :class="{ active: addonAction === 'add' }"
                    @click="addonAction = 'add'"
                >
                    <text>新增附加服务</text>
                </view>
                <view
                    class="action-tab"
                    :class="{ active: addonAction === 'remove' }"
                    @click="addonAction = 'remove'"
                >
                    <text>移除已选附加服务</text>
                </view>
            </view>
        </view>

        <view class="bg-white mt-3 p-4">
            <view class="section-title">
                {{ addonAction === 'add' ? '选择附加服务' : '选择要移除的附加服务' }}
            </view>

            <view v-if="addonAction === 'add' && loadingAddons" class="empty-box">加载中...</view>

            <template v-else-if="addonAction === 'add'">
                <view v-if="availableAddons.length" class="addon-list">
                    <view
                        v-for="addon in availableAddons"
                        :key="addon.id"
                        class="addon-card"
                        :class="{
                            active: selectedAddonIds.includes(addon.id),
                            disabled: currentAddonIdSet.has(Number(addon.id))
                        }"
                        @click="toggleAddon(addon)"
                    >
                        <view class="addon-card__main">
                            <view class="addon-card__name-row">
                                <text class="addon-card__name">{{ addon.name }}</text>
                                <text v-if="currentAddonIdSet.has(Number(addon.id))" class="addon-card__badge">
                                    已在订单中
                                </text>
                            </view>
                            <text v-if="addon.description" class="addon-card__desc">
                                {{ addon.description }}
                            </text>
                        </view>
                        <text class="addon-card__price">+¥{{ formatPrice(addon.price) }}</text>
                    </view>
                </view>
                <view v-else class="empty-box">当前主服务项暂无可新增的附加服务</view>
            </template>

            <template v-else>
                <view v-if="removableAddons.length" class="addon-list">
                    <view
                        v-for="addon in removableAddons"
                        :key="addon.id"
                        class="addon-card"
                        :class="{ active: selectedAddonIds.includes(Number(addon.addon_id || addon.id)) }"
                        @click="toggleAddon(addon)"
                    >
                        <view class="addon-card__main">
                            <view class="addon-card__name-row">
                                <text class="addon-card__name">{{ addon.addon_name || addon.name }}</text>
                            </view>
                        </view>
                        <text class="addon-card__price">-¥{{ formatPrice(addon.subtotal || addon.price) }}</text>
                    </view>
                </view>
                <view v-else class="empty-box">当前主服务项没有可移除的附加服务</view>
            </template>
        </view>

        <view class="bg-white mt-3 p-4">
            <view class="section-title">申请原因</view>
            <textarea
                v-model="formData.reason"
                class="reason-input"
                placeholder="请填写变更原因（选填）"
                maxlength="200"
            />
            <view class="text-right text-xs text-gray-400">{{ formData.reason.length }}/200</view>
        </view>

        <view class="bg-white mt-3 p-4">
            <view class="section-title">附件图片（选填）</view>
            <view class="image-uploader">
                <view
                    v-for="(img, index) in formData.attach_images"
                    :key="`${img}-${index}`"
                    class="image-item"
                >
                    <image :src="img" class="upload-image" mode="aspectFill" />
                    <view class="delete-btn" @click="removeImage(index)">
                        <tn-icon name="close" size="28rpx" color="#fff"></tn-icon>
                    </view>
                </view>
                <view
                    v-if="formData.attach_images.length < 5"
                    class="add-image"
                    @click="chooseImage"
                >
                    <tn-icon name="add" size="72rpx" color="#ccc"></tn-icon>
                    <text class="text-xs text-gray-400 mt-1">
                        {{ uploadingImages ? '上传中...' : '上传图片' }}
                    </text>
                </view>
            </view>
        </view>

        <view class="bg-white mt-3 p-4" v-if="selectedAddonList.length">
            <view class="section-title">金额预览</view>
            <view class="price-preview">
                <view v-for="addon in selectedAddonList" :key="getAddonKey(addon)" class="price-item">
                    <text class="text-sm text-gray-500">{{ getAddonName(addon) }}</text>
                    <text class="text-sm">
                        {{ addonAction === 'remove' ? '-' : '+' }}¥{{ formatPrice(getAddonPrice(addon)) }}
                    </text>
                </view>
                <view class="price-item total">
                    <text class="text-sm font-medium">净差额</text>
                    <text
                        class="text-lg font-bold"
                        :class="priceDiff >= 0 ? 'text-primary' : 'text-green-500'"
                    >
                        {{ priceDiff >= 0 ? '+' : '' }}¥{{ formatPrice(Math.abs(priceDiff)) }}
                    </text>
                </view>
            </view>
        </view>

        <view class="bottom-actions">
            <button
                class="btn-submit"
                :disabled="submitting || !canSubmit"
                :style="{ background: $theme.primaryColor }"
                @click="handleSubmit"
            >
                {{ submitting ? '提交中...' : '提交申请' }}
            </button>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getOrderDetail } from '@/api/order'
import { applyAddonChange, checkCanChange } from '@/api/orderChange'
import { getStaffAddons } from '@/api/staff'
import { uploadImage } from '@/api/app'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()

const orderId = ref(0)
const orderInfo = ref<any>(null)
const selectedOrderItemId = ref(0)
const addonAction = ref<'add' | 'remove'>('add')
const availableAddons = ref<any[]>([])
const selectedAddonIds = ref<number[]>([])
const loadingAddons = ref(false)
const submitting = ref(false)
const uploadingImages = ref(false)

const formData = reactive({
    reason: '',
    attach_images: [] as string[]
})

const orderItems = computed(() => orderInfo.value?.items || [])
const selectedOrderItem = computed(
    () => orderItems.value.find((item: any) => Number(item.id) === Number(selectedOrderItemId.value)) || null
)
const removableAddons = computed(() => selectedOrderItem.value?.addons || [])
const currentAddonIdSet = computed(
    () =>
        new Set(
            removableAddons.value.map((item: any) => Number(item.addon_id || item.id || 0)).filter(Boolean)
        )
)
const selectedAddonList = computed(() => {
    if (addonAction.value === 'add') {
        return availableAddons.value.filter((item: any) => selectedAddonIds.value.includes(Number(item.id)))
    }
    return removableAddons.value.filter((item: any) =>
        selectedAddonIds.value.includes(Number(item.addon_id || item.id))
    )
})
const priceDiff = computed(() => {
    const total = selectedAddonList.value.reduce(
        (sum: number, item: any) => sum + Number(item.subtotal || item.price || 0),
        0
    )
    return addonAction.value === 'remove' ? -total : total
})
const canSubmit = computed(
    () => !!selectedOrderItem.value && selectedAddonIds.value.length > 0 && !submitting.value
)

const formatPrice = (value: any) => Number(value || 0).toFixed(2)
const getAddonKey = (addon: any) => String(addon.id || addon.addon_id)
const getAddonName = (addon: any) => addon.addon_name || addon.name || '未命名附加服务'
const getAddonPrice = (addon: any) => Number(addon.subtotal || addon.price || 0)

const resetSelection = () => {
    selectedAddonIds.value = []
}

const fetchOrderInfo = async () => {
    const res = await getOrderDetail({ id: orderId.value })
    orderInfo.value = res
    const firstItemId = Number(res?.items?.[0]?.id || 0)
    if (firstItemId > 0 && !selectedOrderItemId.value) {
        selectedOrderItemId.value = firstItemId
    }
}

const checkOrder = async () => {
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
}

const fetchAvailableAddons = async () => {
    if (!selectedOrderItem.value || addonAction.value !== 'add') {
        availableAddons.value = []
        return
    }

    loadingAddons.value = true
    try {
        const res = await getStaffAddons({
            staff_id: Number(selectedOrderItem.value.staff_id),
            package_id: Number(selectedOrderItem.value.package_id || 0)
        })
        availableAddons.value = Array.isArray(res) ? res : []
    } catch (error) {
        availableAddons.value = []
    } finally {
        loadingAddons.value = false
    }
}

const selectOrderItem = (item: any) => {
    selectedOrderItemId.value = Number(item.id)
}

const toggleAddon = (addon: any) => {
    const addonId = Number(addon?.id || addon?.addon_id || 0)
    if (!addonId) return

    if (addonAction.value === 'add' && currentAddonIdSet.value.has(addonId)) {
        return
    }

    if (selectedAddonIds.value.includes(addonId)) {
        selectedAddonIds.value = selectedAddonIds.value.filter((id) => id !== addonId)
    } else {
        selectedAddonIds.value = [...selectedAddonIds.value, addonId]
    }
}

const chooseImage = () => {
    if (uploadingImages.value) return

    uni.chooseImage({
        count: 5 - formData.attach_images.length,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: async (res) => {
            const tempFiles = res.tempFilePaths || []
            if (!tempFiles.length) return

            uploadingImages.value = true
            try {
                for (const filePath of tempFiles) {
                    const uploadRes: any = await uploadImage(filePath)
                    if (uploadRes?.url) {
                        formData.attach_images.push(uploadRes.url)
                    }
                }
            } catch (e: any) {
                uni.showToast({ title: e.message || '图片上传失败', icon: 'none' })
            } finally {
                uploadingImages.value = false
            }
        }
    })
}

const removeImage = (index: number) => {
    formData.attach_images.splice(index, 1)
}

const handleSubmit = async () => {
    if (!canSubmit.value || !selectedOrderItem.value) {
        uni.showToast({ title: '请先选择附加服务', icon: 'none' })
        return
    }

    submitting.value = true
    try {
        const res = await applyAddonChange({
            order_id: orderId.value,
            order_item_id: Number(selectedOrderItem.value.id),
            addon_action: addonAction.value === 'add' ? 1 : 2,
            addon_ids: selectedAddonIds.value,
            reason: formData.reason,
            attach_images: formData.attach_images
        })
        uni.showToast({ title: '申请已提交', icon: 'success' })
        setTimeout(() => {
            uni.redirectTo({
                url: `/packages/pages/order_change/change_detail?id=${res.change_id}`
            })
        }, 1200)
    } catch (e: any) {
        uni.showToast({ title: e.message || '提交失败', icon: 'none' })
    } finally {
        submitting.value = false
    }
}

watch(selectedOrderItemId, async () => {
    resetSelection()
    await fetchAvailableAddons()
})

watch(addonAction, async () => {
    resetSelection()
    await fetchAvailableAddons()
})

onLoad(async (options: any) => {
    if (!options?.order_id) {
        uni.showToast({ title: '订单参数缺失', icon: 'none' })
        setTimeout(() => uni.navigateBack(), 1200)
        return
    }

    orderId.value = Number(options.order_id)
    try {
        await Promise.all([fetchOrderInfo(), checkOrder()])
        await fetchAvailableAddons()
    } catch (e: any) {
        uni.showToast({ title: e.message || '页面加载失败', icon: 'none' })
    }
})
</script>

<style lang="scss" scoped>
.apply-addon-page {
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
    border-left: 6rpx solid var(--color-primary, #7c3aed);
}

.order-card {
    padding: 20rpx;
    background: #f9f9f9;
    border-radius: 12rpx;
}

.item-list,
.addon-list {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.item-card,
.addon-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20rpx;
    padding: 22rpx;
    background: #f9f9fb;
    border-radius: 14rpx;
    border: 2rpx solid transparent;
}

.item-card.active,
.addon-card.active {
    border-color: var(--color-primary, #7c3aed);
    background: rgba(124, 58, 237, 0.05);
}

.addon-card.disabled {
    opacity: 0.55;
}

.item-card__main,
.addon-card__main {
    flex: 1;
    min-width: 0;
}

.item-card__title,
.addon-card__name {
    font-size: 28rpx;
    font-weight: 600;
    color: #333;
}

.item-card__meta,
.addon-card__desc {
    display: block;
    margin-top: 8rpx;
    font-size: 24rpx;
    color: #999;
}

.addon-card__name-row {
    display: flex;
    align-items: center;
    gap: 12rpx;
    flex-wrap: wrap;
}

.addon-card__badge {
    padding: 4rpx 12rpx;
    border-radius: 999rpx;
    background: rgba(124, 58, 237, 0.1);
    color: #7c3aed;
    font-size: 20rpx;
}

.addon-card__price {
    font-size: 28rpx;
    font-weight: 700;
    color: #d85c61;
}

.action-tabs {
    display: flex;
    gap: 16rpx;
}

.action-tab {
    flex: 1;
    text-align: center;
    padding: 22rpx 16rpx;
    border-radius: 12rpx;
    background: #f7f7f9;
    border: 2rpx solid transparent;
    color: #666;
    font-size: 28rpx;
}

.action-tab.active {
    border-color: var(--color-primary, #7c3aed);
    color: var(--color-primary, #7c3aed);
    background: rgba(124, 58, 237, 0.05);
}

.empty-box {
    padding: 48rpx 24rpx;
    text-align: center;
    font-size: 26rpx;
    color: #999;
    background: #f9f9fb;
    border-radius: 14rpx;
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
    border-radius: 10rpx;
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
    border-radius: 10rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #fff;
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
}

.price-item.total {
    border-top: 1rpx dashed #e0e0e0;
    margin-top: 12rpx;
    padding-top: 20rpx;
}

.bottom-actions {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    padding: 20rpx 30rpx;
    background: #fff;
    box-shadow: 0 -2rpx 10rpx rgba(0, 0, 0, 0.05);
}

.btn-submit {
    width: 100%;
    height: 72rpx;
    line-height: 72rpx;
    background: var(--color-primary, #7c3aed);
    color: #fff;
    border-radius: 44rpx;
    font-size: 30rpx;
    border: none;
}

.btn-submit[disabled] {
    opacity: 0.6;
}
</style>
