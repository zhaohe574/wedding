<template>
    <page-meta :page-style="pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="申请暂停" />

        <view class="order-change-page">
            <view class="order-change-page__wrapper wm-page-content">
                <BaseCard variant="surface" scene="consumer" class="order-change-tip-card">
                    <tn-icon name="tip-fill" size="34" color="#C98524" />
                    <text class="order-change-tip-card__text">
                        暂停期间订单冻结，请准确填写起止日期。
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
                                服务日期：{{ getValueText(orderInfo.service_date) }}
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
                    <text class="order-change-card__title">暂停原因类型</text>
                    <view class="order-change-choice-grid">
                        <view
                            v-for="type in pauseTypes"
                            :key="type.value"
                            class="order-change-choice-card"
                            :class="{
                                'order-change-choice-card--active':
                                    formData.pause_type === type.value
                            }"
                            @click="formData.pause_type = type.value"
                        >
                            <tn-icon
                                :name="type.icon"
                                size="44"
                                :color="
                                    formData.pause_type === type.value
                                        ? $theme.primaryColor
                                        : '#B4ACA8'
                                "
                            />
                            <text class="order-change-choice-card__title">{{ type.label }}</text>
                            <text class="order-change-choice-card__desc">
                                {{ type.description }}
                            </text>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard
                    variant="surface"
                    scene="consumer"
                    class="order-change-card order-change-form-card"
                >
                    <text class="order-change-card__title">暂停时间</text>
                    <view class="order-change-form-field">
                        <text
                            class="order-change-form-field__label order-change-form-field__label--required"
                        >
                            开始日期
                        </text>
                        <view
                            class="order-change-form-field__shell"
                            @click="openDatePicker('start')"
                        >
                            <view class="order-change-form-field__value-row">
                                <text
                                    v-if="formData.start_date"
                                    class="order-change-form-field__value"
                                >
                                    {{ formData.start_date }}
                                </text>
                                <text v-else class="order-change-form-field__placeholder">
                                    请选择开始日期
                                </text>
                                <tn-icon name="right" size="30" color="#B4ACA8" />
                            </view>
                        </view>
                    </view>
                    <view class="order-change-form-field">
                        <text
                            class="order-change-form-field__label order-change-form-field__label--required"
                        >
                            结束日期
                        </text>
                        <view class="order-change-form-field__shell" @click="openDatePicker('end')">
                            <view class="order-change-form-field__value-row">
                                <text
                                    v-if="formData.end_date"
                                    class="order-change-form-field__value"
                                >
                                    {{ formData.end_date }}
                                </text>
                                <text v-else class="order-change-form-field__placeholder">
                                    请选择结束日期
                                </text>
                                <tn-icon name="right" size="30" color="#B4ACA8" />
                            </view>
                        </view>
                    </view>
                    <view v-if="pauseDays > 0" class="order-change-summary-grid">
                        <view class="order-change-summary-grid__item">
                            <text class="order-change-summary-grid__label">暂停天数</text>
                            <text class="order-change-summary-grid__value">{{ pauseDays }} 天</text>
                        </view>
                        <view class="order-change-summary-grid__item">
                            <text class="order-change-summary-grid__label">处理提示</text>
                            <text class="order-change-summary-grid__value">审核通过后生效</text>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard
                    variant="surface"
                    scene="consumer"
                    class="order-change-card order-change-form-card"
                >
                    <text class="order-change-card__title">详细说明</text>
                    <view class="order-change-form-field">
                        <text
                            class="order-change-form-field__label order-change-form-field__label--required"
                        >
                            暂停原因
                        </text>
                        <view
                            class="order-change-form-field__shell order-change-form-field__shell--textarea"
                        >
                            <textarea
                                v-model="formData.reason"
                                class="order-change-form-field__textarea"
                                maxlength="500"
                                placeholder="请填写暂停原因，至少 10 字。"
                                placeholder-style="color:#B4ACA8;"
                            />
                        </view>
                        <text class="order-change-form-field__counter">
                            {{ formData.reason.length }}/500
                        </text>
                    </view>
                </BaseCard>

                <BaseCard
                    variant="surface"
                    scene="consumer"
                    class="order-change-card order-change-form-card"
                >
                    <text class="order-change-card__title">证明材料</text>
                    <text class="order-change-card__caption"> 选填，最多 10 张。 </text>
                    <view class="order-change-upload-grid">
                        <view
                            v-for="(image, index) in formData.proof_images"
                            :key="`${image}-${index}`"
                            class="order-change-upload-grid__item"
                        >
                            <image
                                :src="image"
                                mode="aspectFill"
                                class="order-change-upload-grid__preview"
                                @click="openImagePreview(formData.proof_images, index)"
                            />
                            <view
                                class="order-change-upload-grid__remove"
                                @click.stop="removeImage(index)"
                            >
                                <tn-icon name="close" size="20" color="#FFFFFF" />
                            </view>
                        </view>
                        <view
                            v-if="formData.proof_images.length < 10"
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
                        提交成功，可在详情页查看进度。
                    </text>
                </BaseCard>
            </view>
        </view>

        <ActionArea sticky safeBottom>
            <view class="order-change-page__actions">
                <BaseButton block size="lg" :loading="submitting" @click="handleSubmit">
                    提交暂停申请
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
                    <text class="order-change-sheet__title">{{ activeDateTitle }}</text>
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
import { applyPause, checkCanChange } from '@/packages/common/api/orderChange'
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
const activeDateField = ref<'start' | 'end'>('start')

const formData = reactive({
    pause_type: 3,
    start_date: '',
    end_date: '',
    reason: '',
    proof_images: [] as string[]
})

const pauseTypes = [
    { value: 1, label: '疫情原因', icon: 'warning-circle', description: '因公共事件导致档期调整' },
    { value: 2, label: '突发事件', icon: 'notification', description: '因紧急事务需暂缓服务' },
    { value: 3, label: '个人原因', icon: 'my', description: '因个人安排需临时暂停' },
    { value: 4, label: '其他原因', icon: 'more-circle', description: '其他需要平台审核的原因' }
]

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

const orderItem = computed(() => orderInfo.value?.items?.[0] || null)
const pageStyle = computed(() => getPageStyleWithPopupLock($theme.pageStyle, popupVisible.value))
const pauseDays = computed(() => {
    if (!formData.start_date || !formData.end_date) {
        return 0
    }
    const start = new Date(formData.start_date.replace(/-/g, '/'))
    const end = new Date(formData.end_date.replace(/-/g, '/'))
    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
        return 0
    }
    const diff = Math.ceil((end.getTime() - start.getTime()) / (24 * 60 * 60 * 1000)) + 1
    return diff > 0 ? diff : 0
})
const activeDateTitle = computed(() =>
    activeDateField.value === 'start' ? '选择开始日期' : '选择结束日期'
)

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
                content: res?.message || '当前订单暂不支持暂停申请',
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

const openDatePicker = (field: 'start' | 'end') => {
    activeDateField.value = field
    syncPickerWithDate(
        field === 'start' ? formData.start_date : formData.end_date || formData.start_date
    )
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
    const dateText = `${year}-${month}-${day}`

    if (activeDateField.value === 'start') {
        formData.start_date = dateText
        if (formData.end_date && formData.end_date < formData.start_date) {
            formData.end_date = ''
        }
    } else {
        formData.end_date = dateText
    }

    closeDatePicker()
}

const chooseImage = () => {
    uni.chooseImage({
        count: 10 - formData.proof_images.length,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: (result) => {
            formData.proof_images.push(...result.tempFilePaths)
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
    if (formData.reason.trim().length < 10) {
        uni.showToast({ title: '暂停原因至少 10 个字', icon: 'none' })
        return
    }

    submitting.value = true
    try {
        const res = await applyPause({
            order_id: orderId.value,
            pause_type: formData.pause_type,
            reason: formData.reason.trim(),
            start_date: formData.start_date,
            end_date: formData.end_date,
            proof_images: formData.proof_images
        })
        uni.showToast({ title: '申请已提交', icon: 'none' })
        setTimeout(() => {
            uni.redirectTo({
                url: `/packages/pages/order_change/pause_detail?id=${res.pause_id}`
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
