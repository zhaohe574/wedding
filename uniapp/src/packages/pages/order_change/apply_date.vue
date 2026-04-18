<template>
    <page-meta :page-style="pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="申请改期" />

        <view class="order-change-page">
            <view class="order-change-page__wrapper wm-page-content">
                <BaseCard variant="surface" scene="consumer" class="order-change-tip-card">
                    <tn-icon name="calendar" size="34" color="#E85A4F" />
                    <text class="order-change-tip-card__text">
                        提交后进入审核，请先确认新日期。
                    </text>
                </BaseCard>

                <BaseCard
                    v-if="orderInfo"
                    variant="surface"
                    scene="consumer"
                    class="order-change-card order-change-form-card"
                >
                    <text class="order-change-card__title">订单摘要</text>
                    <view class="order-change-link-card">
                        <view class="order-change-link-card__top">
                            <text class="order-change-link-card__main">
                                {{ getValueText(orderInfo.order_sn, '订单待补充') }}
                            </text>
                            <text class="order-change-link-card__meta">
                                应付：¥{{ formatCurrency(orderInfo.pay_amount) }}
                            </text>
                        </view>
                        <view class="order-change-link-card__bottom">
                            <text class="order-change-link-card__meta">
                                当前服务日期：{{ getValueText(orderInfo.service_date) }}
                            </text>
                            <text class="order-change-link-card__meta">
                                {{ getValueText(orderItem?.staff_name, '待分配服务人员') }}
                            </text>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard
                    variant="surface"
                    scene="consumer"
                    class="order-change-card order-change-form-card"
                >
                    <text class="order-change-card__title">改期信息</text>
                    <view class="order-change-form-field">
                        <text
                            class="order-change-form-field__label order-change-form-field__label--required"
                        >
                            新服务日期
                        </text>
                        <view class="order-change-form-field__shell" @click="openDatePicker">
                            <view class="order-change-form-field__value-row">
                                <text
                                    v-if="formData.new_date"
                                    class="order-change-form-field__value"
                                >
                                    {{ formData.new_date }}
                                </text>
                                <text v-else class="order-change-form-field__placeholder">
                                    请选择新的服务日期
                                </text>
                                <tn-icon name="right" size="30" color="#B4ACA8" />
                            </view>
                        </view>
                        <text class="order-change-form-field__helper">
                            当前订单日期：{{ getValueText(orderInfo?.service_date, '待补充') }}
                        </text>
                    </view>
                </BaseCard>

                <BaseCard
                    variant="surface"
                    scene="consumer"
                    class="order-change-card order-change-form-card"
                >
                    <text class="order-change-card__title">申请说明</text>
                    <view class="order-change-form-field">
                        <text class="order-change-form-field__label">改期原因</text>
                        <view
                            class="order-change-form-field__shell order-change-form-field__shell--textarea"
                        >
                            <textarea
                                v-model="formData.reason"
                                class="order-change-form-field__textarea"
                                maxlength="200"
                                placeholder="请填写改期原因。"
                                placeholder-style="color:#B4ACA8;"
                            />
                        </view>
                        <text class="order-change-form-field__counter">
                            {{ formData.reason.length }}/200
                        </text>
                    </view>
                </BaseCard>

                <BaseCard
                    variant="surface"
                    scene="consumer"
                    class="order-change-card order-change-form-card"
                >
                    <text class="order-change-card__title">附件图片</text>
                    <text class="order-change-card__caption"> 选填，最多 5 张。 </text>
                    <view class="order-change-upload-grid">
                        <view
                            v-for="(image, index) in formData.attach_images"
                            :key="`${image}-${index}`"
                            class="order-change-upload-grid__item"
                        >
                            <image
                                :src="image"
                                mode="aspectFill"
                                class="order-change-upload-grid__preview"
                                @click="openImagePreview(formData.attach_images, index)"
                            />
                            <view
                                class="order-change-upload-grid__remove"
                                @click.stop="removeImage(index)"
                            >
                                <tn-icon name="close" size="20" color="#FFFFFF" />
                            </view>
                        </view>
                        <view
                            v-if="formData.attach_images.length < 5"
                            class="order-change-upload-grid__add"
                            @click="chooseImage"
                        >
                            <tn-icon name="add" size="48" color="#C9B2AA" />
                            <text class="order-change-upload-grid__add-text">上传图片</text>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="surface" scene="consumer" class="order-change-card">
                    <text class="order-change-card__title">提交后说明</text>
                    <text class="order-change-card__paragraph">
                        提交成功，可在“我的申请”查看进度。
                    </text>
                </BaseCard>
            </view>
        </view>

        <ActionArea sticky safeBottom>
            <view class="order-change-page__actions">
                <BaseButton block size="lg" :loading="submitting" @click="handleSubmit">
                    提交申请
                </BaseButton>
            </view>
        </ActionArea>

        <uni-popup
            ref="datePopup"
            type="bottom"
            :safe-area="false"
            :mask-click="true"
            @change="handlePopupChange"
        >
            <view class="order-change-sheet">
                <view class="order-change-sheet__header">
                    <text class="order-change-sheet__cancel" @click="closeDatePicker">取消</text>
                    <text class="order-change-sheet__title">选择日期</text>
                    <text class="order-change-sheet__confirm" @click="confirmDate">确定</text>
                </view>
                <picker-view
                    class="order-change-sheet__picker"
                    :value="datePickerValue"
                    @change="onDateChange"
                >
                    <picker-view-column>
                        <view
                            v-for="year in years"
                            :key="year"
                            class="order-change-sheet__picker-item"
                        >
                            {{ year }}年
                        </view>
                    </picker-view-column>
                    <picker-view-column>
                        <view
                            v-for="month in months"
                            :key="month"
                            class="order-change-sheet__picker-item"
                        >
                            {{ month }}月
                        </view>
                    </picker-view-column>
                    <picker-view-column>
                        <view
                            v-for="day in days"
                            :key="day"
                            class="order-change-sheet__picker-item"
                        >
                            {{ day }}日
                        </view>
                    </picker-view-column>
                </picker-view>
            </view>
        </uni-popup>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getOrderDetail } from '@/api/order'
import { applyDateChange, checkCanChange } from '@/packages/common/api/orderChange'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useThemeStore } from '@/stores/theme'
import { formatCurrency, getPageStyleWithPopupLock, getValueText, openImagePreview } from './shared'

const $theme = useThemeStore()

const orderId = ref(0)
const orderInfo = ref<any>(null)
const submitting = ref(false)
const popupVisible = ref(false)

const formData = reactive({
    new_date: '',
    reason: '',
    attach_images: [] as string[]
})

const datePopup = ref()
const datePickerValue = ref([0, 0, 0])

const currentYear = new Date().getFullYear()
const years = Array.from({ length: 3 }, (_, index) => currentYear + index)
const months = Array.from({ length: 12 }, (_, index) => index + 1)
const days = computed(() => {
    const year = years[datePickerValue.value[0]] || years[0]
    const month = months[datePickerValue.value[1]] || months[0]
    const daysInMonth = new Date(year, month, 0).getDate()
    return Array.from({ length: daysInMonth }, (_, index) => index + 1)
})

const pageStyle = computed(() => getPageStyleWithPopupLock($theme.pageStyle, popupVisible.value))
const orderItem = computed(() => orderInfo.value?.items?.[0] || null)

const fetchOrderInfo = async () => {
    try {
        const res = await getOrderDetail({ id: orderId.value })
        orderInfo.value = res?.data || res
    } catch (error) {
        console.error('获取订单详情失败', error)
    }
}

const checkOrder = async () => {
    try {
        const res = await checkCanChange({ order_id: orderId.value })
        if (!res?.can_change) {
            uni.showModal({
                title: '提示',
                content: res?.message || '当前订单暂不支持改期',
                showCancel: false,
                success: () => {
                    uni.navigateBack()
                }
            })
        }
    } catch (error: any) {
        uni.showToast({ title: error?.message || '校验失败', icon: 'none' })
    }
}

const syncPickerWithDate = (value?: string) => {
    const targetDate = value ? new Date(value.replace(/-/g, '/')) : new Date()
    const safeDate = Number.isNaN(targetDate.getTime()) ? new Date() : targetDate
    const yearIndex = Math.max(0, years.indexOf(safeDate.getFullYear()))
    const monthIndex = safeDate.getMonth()
    const dayIndex = safeDate.getDate() - 1
    datePickerValue.value = [yearIndex, monthIndex, dayIndex]
}

const openDatePicker = () => {
    syncPickerWithDate(formData.new_date || orderInfo.value?.service_date)
    datePopup.value?.open()
}

const closeDatePicker = () => {
    datePopup.value?.close()
}

const handlePopupChange = (event: any) => {
    popupVisible.value = Boolean(event?.show)
}

const onDateChange = (event: any) => {
    datePickerValue.value = event?.detail?.value || [0, 0, 0]
}

const confirmDate = () => {
    const year = years[datePickerValue.value[0]] || years[0]
    const month = String(months[datePickerValue.value[1]] || months[0]).padStart(2, '0')
    const day = String(days.value[datePickerValue.value[2]] || days.value[0]).padStart(2, '0')
    formData.new_date = `${year}-${month}-${day}`
    closeDatePicker()
}

const chooseImage = () => {
    uni.chooseImage({
        count: 5 - formData.attach_images.length,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: (result) => {
            formData.attach_images.push(...result.tempFilePaths)
        }
    })
}

const removeImage = (index: number) => {
    formData.attach_images.splice(index, 1)
}

const handleSubmit = async () => {
    if (!formData.new_date) {
        uni.showToast({ title: '请选择新的服务日期', icon: 'none' })
        return
    }

    submitting.value = true
    try {
        const res = await applyDateChange({
            order_id: orderId.value,
            new_date: formData.new_date,
            reason: formData.reason.trim(),
            attach_images: formData.attach_images
        })
        uni.showToast({ title: '申请已提交', icon: 'none' })
        setTimeout(() => {
            uni.redirectTo({
                url: `/packages/pages/order_change/change_detail?id=${res.change_id}`
            })
        }, 1200)
    } catch (error: any) {
        uni.showToast({ title: error?.message || '提交失败', icon: 'none' })
    } finally {
        submitting.value = false
    }
}

onLoad((options: any) => {
    orderId.value = Number(options?.order_id || 0)
    if (orderId.value) {
        void fetchOrderInfo()
        void checkOrder()
    }
})
</script>

<style lang="scss" scoped>
@import './shared.scss';
</style>
