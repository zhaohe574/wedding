<template>
    <page-meta :page-style="pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="加项申请" />
        <view class="order-change-page">
            <view class="order-change-page__wrapper wm-page-content">
                <BaseCard variant="surface" scene="consumer" class="order-change-tip-card">
                    <tn-icon name="gift" size="34" color="#E85A4F" />
                    <text class="order-change-tip-card__text"
                        >请选择新增套餐或服务人员，平台审核通过后会同步写入订单。</text
                    >
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
                            <text class="order-change-link-card__main">{{
                                getValueText(orderInfo.order_sn, '订单待补充')
                            }}</text>
                            <text class="order-change-link-card__meta"
                                >服务日：{{ getValueText(orderInfo.service_date) }}</text
                            >
                        </view>
                        <view class="order-change-link-card__bottom">
                            <text class="order-change-link-card__meta"
                                >主服务人员：{{
                                    getValueText(orderItem?.staff_name, '待补充')
                                }}</text
                            >
                            <text class="order-change-link-card__meta"
                                >实付：¥{{ formatCurrency(orderInfo.pay_amount) }}</text
                            >
                        </view>
                    </view>
                </BaseCard>

                <BaseCard
                    variant="surface"
                    scene="consumer"
                    class="order-change-card order-change-form-card"
                >
                    <text class="order-change-card__title">加项类型</text>
                    <view class="order-change-choice-grid">
                        <view
                            class="order-change-choice-card"
                            :class="{ 'order-change-choice-card--active': addType === 'package' }"
                            @click="addType = 'package'"
                        >
                            <tn-icon
                                name="gift"
                                size="44"
                                :color="addType === 'package' ? $theme.primaryColor : '#B4ACA8'"
                            />
                            <text class="order-change-choice-card__title">添加套餐</text>
                            <text class="order-change-choice-card__desc"
                                >基于当前订单增加服务套餐</text
                            >
                        </view>
                        <view
                            class="order-change-choice-card"
                            :class="{ 'order-change-choice-card--active': addType === 'staff' }"
                            @click="addType = 'staff'"
                        >
                            <tn-icon
                                name="my"
                                size="44"
                                :color="addType === 'staff' ? $theme.primaryColor : '#B4ACA8'"
                            />
                            <text class="order-change-choice-card__title">添加人员</text>
                            <text class="order-change-choice-card__desc"
                                >新增人员并绑定对应套餐</text
                            >
                        </view>
                    </view>
                </BaseCard>

                <BaseCard
                    v-if="addType === 'package'"
                    variant="surface"
                    scene="consumer"
                    class="order-change-card order-change-form-card"
                >
                    <text class="order-change-card__title">选择套餐</text>
                    <view class="order-change-form-field">
                        <text
                            class="order-change-form-field__label order-change-form-field__label--required"
                            >套餐</text
                        >
                        <view class="order-change-form-field__shell" @click="openPackagePicker">
                            <view class="order-change-form-field__value-row">
                                <text
                                    v-if="selectedPackage"
                                    class="order-change-form-field__value"
                                    >{{ selectedPackage.name }}</text
                                >
                                <text v-else class="order-change-form-field__placeholder"
                                    >请选择新增套餐</text
                                >
                                <tn-icon name="right" size="30" color="#B4ACA8" />
                            </view>
                        </view>
                    </view>
                    <view v-if="selectedPackage" class="order-change-selected-card">
                        <view class="order-change-selected-card__media">
                            <image
                                v-if="selectedPackage.image"
                                :src="selectedPackage.image"
                                mode="aspectFill"
                                class="order-change-selected-card__image"
                            />
                            <text v-else class="order-change-selected-card__placeholder">{{
                                getInitial(selectedPackage.name)
                            }}</text>
                        </view>
                        <view class="order-change-selected-card__copy">
                            <text class="order-change-selected-card__title">{{
                                selectedPackage.name
                            }}</text>
                            <text class="order-change-selected-card__desc">{{
                                getValueText(selectedPackage.description, '待补充套餐说明')
                            }}</text>
                            <text class="order-change-selected-card__price"
                                >¥{{ formatCurrency(selectedPackage.price) }}</text
                            >
                        </view>
                    </view>
                </BaseCard>

                <BaseCard
                    v-else
                    variant="surface"
                    scene="consumer"
                    class="order-change-card order-change-form-card"
                >
                    <text class="order-change-card__title">选择服务内容</text>
                    <view class="order-change-form-field">
                        <text
                            class="order-change-form-field__label order-change-form-field__label--required"
                            >服务人员</text
                        >
                        <view class="order-change-form-field__shell" @click="staffPopup?.open()">
                            <view class="order-change-form-field__value-row">
                                <text v-if="selectedStaff" class="order-change-form-field__value">{{
                                    selectedStaff.name
                                }}</text>
                                <text v-else class="order-change-form-field__placeholder"
                                    >请选择新增服务人员</text
                                >
                                <tn-icon name="right" size="30" color="#B4ACA8" />
                            </view>
                        </view>
                    </view>
                    <view v-if="selectedStaff" class="order-change-selected-card">
                        <view class="order-change-selected-card__media">
                            <image
                                v-if="selectedStaff.avatar"
                                :src="selectedStaff.avatar"
                                mode="aspectFill"
                                class="order-change-selected-card__image"
                            />
                            <text v-else class="order-change-selected-card__placeholder">{{
                                getInitial(selectedStaff.name)
                            }}</text>
                        </view>
                        <view class="order-change-selected-card__copy">
                            <text class="order-change-selected-card__title">{{
                                selectedStaff.name
                            }}</text>
                            <text class="order-change-selected-card__desc">{{
                                getValueText(selectedStaff.level_name, '普通级别')
                            }}</text>
                            <text class="order-change-selected-card__price"
                                >¥{{ formatCurrency(selectedStaff.price) }}</text
                            >
                        </view>
                    </view>
                    <view class="order-change-form-field">
                        <text
                            class="order-change-form-field__label order-change-form-field__label--required"
                            >关联套餐</text
                        >
                        <view
                            class="order-change-form-field__shell"
                            @click="openStaffPackagePicker"
                        >
                            <view class="order-change-form-field__value-row">
                                <text
                                    v-if="selectedStaffPackage"
                                    class="order-change-form-field__value"
                                    >{{ selectedStaffPackage.name }}</text
                                >
                                <text v-else class="order-change-form-field__placeholder"
                                    >请选择该人员对应套餐</text
                                >
                                <tn-icon name="right" size="30" color="#B4ACA8" />
                            </view>
                        </view>
                    </view>
                    <view v-if="selectedStaffPackage" class="order-change-selected-card">
                        <view class="order-change-selected-card__media">
                            <text class="order-change-selected-card__placeholder">{{
                                getInitial(selectedStaffPackage.name)
                            }}</text>
                        </view>
                        <view class="order-change-selected-card__copy">
                            <text class="order-change-selected-card__title">{{
                                selectedStaffPackage.name
                            }}</text>
                            <text class="order-change-selected-card__desc">{{
                                getValueText(selectedStaffPackage.description, '待补充套餐说明')
                            }}</text>
                            <text class="order-change-selected-card__price"
                                >¥{{ formatCurrency(selectedStaffPackage.price) }}</text
                            >
                        </view>
                    </view>
                </BaseCard>

                <BaseCard
                    variant="surface"
                    scene="consumer"
                    class="order-change-card order-change-form-card"
                >
                    <text class="order-change-card__title">服务日期</text>
                    <view class="order-change-form-field">
                        <text
                            class="order-change-form-field__label order-change-form-field__label--required"
                            >服务日期</text
                        >
                        <view class="order-change-form-field__shell" @click="openDatePicker">
                            <view class="order-change-form-field__value-row">
                                <text
                                    v-if="formData.service_date"
                                    class="order-change-form-field__value"
                                    >{{ formData.service_date }}</text
                                >
                                <text v-else class="order-change-form-field__placeholder"
                                    >请选择加项服务日期</text
                                >
                                <tn-icon name="right" size="30" color="#B4ACA8" />
                            </view>
                        </view>
                        <text class="order-change-form-field__helper"
                            >最终日期以审核结果为准，可与原订单日期不同。</text
                        >
                    </view>
                </BaseCard>

                <BaseCard
                    variant="surface"
                    scene="consumer"
                    class="order-change-card order-change-form-card"
                >
                    <text class="order-change-card__title">申请说明</text>
                    <view class="order-change-form-field">
                        <text class="order-change-form-field__label">加项原因</text>
                        <view
                            class="order-change-form-field__shell order-change-form-field__shell--textarea"
                        >
                            <textarea
                                v-model="formData.reason"
                                class="order-change-form-field__textarea"
                                maxlength="200"
                                placeholder="请补充加项原因。"
                                placeholder-style="color:#B4ACA8;"
                            />
                        </view>
                        <text class="order-change-form-field__counter"
                            >{{ formData.reason.length }}/200</text
                        >
                    </view>
                </BaseCard>

                <BaseCard
                    variant="surface"
                    scene="consumer"
                    class="order-change-card order-change-form-card"
                >
                    <text class="order-change-card__title">附件图片</text>
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

                <BaseCard
                    v-if="totalPrice > 0"
                    variant="surface"
                    scene="consumer"
                    class="order-change-card order-change-price-card"
                >
                    <text class="order-change-card__title">费用预览</text>
                    <view class="order-change-price-card__row">
                        <text class="order-change-price-card__label">{{
                            addType === 'package' ? '套餐费用' : '人员费用'
                        }}</text>
                        <text class="order-change-price-card__value"
                            >¥{{ formatCurrency(basePrice) }}</text
                        >
                    </view>
                    <view
                        v-if="addType === 'staff' && selectedStaffPackage"
                        class="order-change-price-card__row"
                    >
                        <text class="order-change-price-card__label">套餐费用</text>
                        <text class="order-change-price-card__value"
                            >¥{{ formatCurrency(selectedStaffPackage.price) }}</text
                        >
                    </view>
                    <view
                        class="order-change-price-card__row order-change-price-card__row--emphasis"
                    >
                        <text class="order-change-price-card__label">预计费用</text>
                        <text
                            class="order-change-price-card__value order-change-price-card__value--emphasis"
                            >¥{{ formatCurrency(totalPrice) }}</text
                        >
                    </view>
                </BaseCard>
            </view>
        </view>

        <ActionArea sticky safeBottom>
            <view class="order-change-page__actions">
                <BaseButton
                    block
                    size="lg"
                    :disabled="!canSubmit"
                    :loading="submitting"
                    @click="handleSubmit"
                    >提交申请</BaseButton
                >
            </view>
        </ActionArea>

        <uni-popup
            ref="packagePopup"
            type="bottom"
            :safe-area="false"
            :mask-click="true"
            @change="handlePopupChange"
        >
            <view class="order-change-sheet">
                <view class="order-change-sheet__header">
                    <text class="order-change-sheet__cancel" @click="closePopup(packagePopup)"
                        >取消</text
                    >
                    <text class="order-change-sheet__title">选择套餐</text>
                    <text class="order-change-sheet__spacer" />
                </view>
                <scroll-view scroll-y class="order-change-sheet__list">
                    <view v-if="packageList.length" class="order-change-sheet__option-list">
                        <view
                            v-for="item in packageList"
                            :key="item.id"
                            class="order-change-sheet__option-card"
                            :class="{
                                'order-change-sheet__option-card--active':
                                    selectedPackage?.id === item.id
                            }"
                            @click="selectPackage(item)"
                        >
                            <view class="order-change-sheet__option-media">
                                <image
                                    v-if="item.image"
                                    :src="item.image"
                                    mode="aspectFill"
                                    class="order-change-sheet__option-image"
                                />
                                <text v-else class="order-change-selected-card__placeholder">{{
                                    getInitial(item.name)
                                }}</text>
                            </view>
                            <view class="order-change-sheet__option-copy">
                                <text class="order-change-sheet__option-title">{{
                                    item.name
                                }}</text>
                                <text class="order-change-sheet__option-desc">{{
                                    getValueText(item.description, '待补充套餐说明')
                                }}</text>
                                <text class="order-change-sheet__option-price"
                                    >¥{{ formatCurrency(item.price) }}</text
                                >
                            </view>
                            <tn-icon
                                v-if="selectedPackage?.id === item.id"
                                name="success"
                                size="34"
                                :color="$theme.primaryColor"
                            />
                        </view>
                    </view>
                    <view v-else class="order-change-page__center"
                        ><text class="order-change-page__center-text">暂无可选套餐。</text></view
                    >
                </scroll-view>
            </view>
        </uni-popup>

        <uni-popup
            ref="staffPopup"
            type="bottom"
            :safe-area="false"
            :mask-click="true"
            @change="handlePopupChange"
        >
            <view class="order-change-sheet">
                <view class="order-change-sheet__header">
                    <text class="order-change-sheet__cancel" @click="closePopup(staffPopup)"
                        >取消</text
                    >
                    <text class="order-change-sheet__title">选择人员</text>
                    <text class="order-change-sheet__spacer" />
                </view>
                <scroll-view scroll-y class="order-change-sheet__list">
                    <view v-if="staffList.length" class="order-change-sheet__option-list">
                        <view
                            v-for="item in staffList"
                            :key="item.id"
                            class="order-change-sheet__option-card"
                            :class="{
                                'order-change-sheet__option-card--active':
                                    selectedStaff?.id === item.id
                            }"
                            @click="selectStaff(item)"
                        >
                            <view class="order-change-sheet__option-media">
                                <image
                                    v-if="item.avatar"
                                    :src="item.avatar"
                                    mode="aspectFill"
                                    class="order-change-sheet__option-image"
                                />
                                <text v-else class="order-change-selected-card__placeholder">{{
                                    getInitial(item.name)
                                }}</text>
                            </view>
                            <view class="order-change-sheet__option-copy">
                                <text class="order-change-sheet__option-title">{{
                                    item.name
                                }}</text>
                                <text class="order-change-sheet__option-desc">{{
                                    getValueText(item.level_name, '普通级别')
                                }}</text>
                                <text class="order-change-sheet__option-price"
                                    >¥{{ formatCurrency(item.price) }}</text
                                >
                            </view>
                            <tn-icon
                                v-if="selectedStaff?.id === item.id"
                                name="success"
                                size="34"
                                :color="$theme.primaryColor"
                            />
                        </view>
                    </view>
                    <view v-else class="order-change-page__center"
                        ><text class="order-change-page__center-text">暂无可选人员。</text></view
                    >
                </scroll-view>
            </view>
        </uni-popup>

        <uni-popup
            ref="staffPackagePopup"
            type="bottom"
            :safe-area="false"
            :mask-click="true"
            @change="handlePopupChange"
        >
            <view class="order-change-sheet">
                <view class="order-change-sheet__header">
                    <text class="order-change-sheet__cancel" @click="closePopup(staffPackagePopup)"
                        >取消</text
                    >
                    <text class="order-change-sheet__title">选择套餐</text>
                    <text class="order-change-sheet__spacer" />
                </view>
                <scroll-view scroll-y class="order-change-sheet__list">
                    <view v-if="staffPackageList.length" class="order-change-sheet__option-list">
                        <view
                            v-for="item in staffPackageList"
                            :key="item.id"
                            class="order-change-sheet__option-card"
                            :class="{
                                'order-change-sheet__option-card--active':
                                    selectedStaffPackage?.id === item.id
                            }"
                            @click="selectStaffPackage(item)"
                        >
                            <view class="order-change-sheet__option-media"
                                ><text class="order-change-selected-card__placeholder">{{
                                    getInitial(item.name)
                                }}</text></view
                            >
                            <view class="order-change-sheet__option-copy">
                                <text class="order-change-sheet__option-title">{{
                                    item.name
                                }}</text>
                                <text class="order-change-sheet__option-desc">{{
                                    getValueText(item.description, '待补充套餐说明')
                                }}</text>
                                <text class="order-change-sheet__option-price"
                                    >¥{{ formatCurrency(item.price) }}</text
                                >
                            </view>
                            <tn-icon
                                v-if="selectedStaffPackage?.id === item.id"
                                name="success"
                                size="34"
                                :color="$theme.primaryColor"
                            />
                        </view>
                    </view>
                    <view v-else class="order-change-page__center"
                        ><text class="order-change-page__center-text"
                            >当前人员暂无可选套餐。</text
                        ></view
                    >
                </scroll-view>
            </view>
        </uni-popup>

        <uni-popup
            ref="datePopup"
            type="bottom"
            :safe-area="false"
            :mask-click="true"
            @change="handlePopupChange"
        >
            <view class="order-change-sheet">
                <view class="order-change-sheet__header">
                    <text class="order-change-sheet__cancel" @click="closePopup(datePopup)"
                        >取消</text
                    >
                    <text class="order-change-sheet__title">选择日期</text>
                    <text class="order-change-sheet__confirm" @click="confirmDate">确定</text>
                </view>
                <picker-view
                    class="order-change-sheet__picker"
                    :value="datePickerValue"
                    @change="onDateChange"
                >
                    <picker-view-column
                        ><view
                            v-for="year in years"
                            :key="year"
                            class="order-change-sheet__picker-item"
                            >{{ year }}年</view
                        ></picker-view-column
                    >
                    <picker-view-column
                        ><view
                            v-for="month in months"
                            :key="month"
                            class="order-change-sheet__picker-item"
                            >{{ month }}月</view
                        ></picker-view-column
                    >
                    <picker-view-column
                        ><view
                            v-for="day in days"
                            :key="day"
                            class="order-change-sheet__picker-item"
                            >{{ day }}日</view
                        ></picker-view-column
                    >
                </picker-view>
            </view>
        </uni-popup>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getOrderDetail } from '@/api/order'
import { applyAddItem, checkCanChange } from '@/packages/common/api/orderChange'
import { getStaffList, getStaffPackages } from '@/api/staff'
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
const addType = ref<'package' | 'staff'>('package')
const packageList = ref<any[]>([])
const staffList = ref<any[]>([])
const staffPackageList = ref<any[]>([])
const selectedPackage = ref<any>(null)
const selectedStaff = ref<any>(null)
const selectedStaffPackage = ref<any>(null)
const packagePopup = ref()
const staffPopup = ref()
const staffPackagePopup = ref()
const datePopup = ref()
const datePickerValue = ref([0, 0, 0])
const formData = reactive({ service_date: '', reason: '', attach_images: [] as string[] })

const currentYear = new Date().getFullYear()
const years = Array.from({ length: 3 }, (_, index) => currentYear + index)
const months = Array.from({ length: 12 }, (_, index) => index + 1)
const days = computed(() => {
    const year = years[datePickerValue.value[0]] || years[0]
    const month = months[datePickerValue.value[1]] || months[0]
    return Array.from({ length: new Date(year, month, 0).getDate() }, (_, index) => index + 1)
})
const pageStyle = computed(() => getPageStyleWithPopupLock($theme.pageStyle, popupVisible.value))
const orderItem = computed(() => orderInfo.value?.items?.[0] || null)
const currentOrderStaffId = computed(() => Number(orderItem.value?.staff_id || 0))
const basePrice = computed(() =>
    Number(
        addType.value === 'package' ? selectedPackage.value?.price : selectedStaff.value?.price || 0
    )
)
const totalPrice = computed(() =>
    addType.value === 'package'
        ? Number(selectedPackage.value?.price || 0)
        : Number(selectedStaff.value?.price || 0) + Number(selectedStaffPackage.value?.price || 0)
)
const canSubmit = computed(() =>
    addType.value === 'package'
        ? Boolean(formData.service_date && selectedPackage.value && currentOrderStaffId.value > 0)
        : Boolean(formData.service_date && selectedStaff.value && selectedStaffPackage.value)
)

const getInitial = (value: unknown) =>
    String(value || '')
        .trim()
        .slice(0, 1) || '项'
const handlePopupChange = (event: any) => {
    popupVisible.value = Boolean(event?.show)
}
const closePopup = (popupRef: any) => popupRef?.value?.close?.()

const fetchOrderInfo = async () => {
    try {
        const res = await getOrderDetail({ id: orderId.value })
        orderInfo.value = res?.data || res
        if (currentOrderStaffId.value > 0) {
            await fetchPackageList(currentOrderStaffId.value)
        }
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
                content: res?.message || '当前订单暂不支持加项申请',
                showCancel: false,
                success: () => uni.navigateBack()
            })
        }
    } catch (error: any) {
        uni.showToast({ title: error?.message || '校验失败', icon: 'none' })
    }
}

const fetchPackageList = async (staffId: number) => {
    if (!staffId) {
        packageList.value = []
        return
    }
    try {
        const res = await getStaffPackages({ staff_id: staffId })
        packageList.value = Array.isArray(res?.lists) ? res.lists : Array.isArray(res) ? res : []
    } catch (error) {
        console.error('获取套餐失败', error)
        packageList.value = []
    }
}

const fetchStaffList = async () => {
    try {
        const res = await getStaffList({ page: 1, limit: 100 })
        staffList.value = Array.isArray(res?.lists) ? res.lists : Array.isArray(res) ? res : []
    } catch (error) {
        console.error('获取人员失败', error)
        staffList.value = []
    }
}

const fetchStaffPackages = async (staffId: number) => {
    if (!staffId) {
        staffPackageList.value = []
        return
    }
    try {
        const res = await getStaffPackages({ staff_id: staffId })
        staffPackageList.value = Array.isArray(res?.lists)
            ? res.lists
            : Array.isArray(res)
            ? res
            : []
    } catch (error) {
        console.error('获取人员套餐失败', error)
        staffPackageList.value = []
    }
}

const openPackagePicker = () => {
    if (!currentOrderStaffId.value) {
        uni.showToast({ title: '当前订单未关联主服务人员', icon: 'none' })
        return
    }
    packagePopup.value?.open()
}

const openStaffPackagePicker = () => {
    if (!selectedStaff.value) {
        uni.showToast({ title: '请先选择服务人员', icon: 'none' })
        return
    }
    staffPackagePopup.value?.open()
}

const selectPackage = (item: any) => {
    selectedPackage.value = item
    closePopup(packagePopup)
}
const selectStaff = async (item: any) => {
    selectedStaff.value = item
    selectedStaffPackage.value = null
    staffPackageList.value = []
    closePopup(staffPopup)
    await fetchStaffPackages(Number(item?.id || 0))
}
const selectStaffPackage = (item: any) => {
    selectedStaffPackage.value = item
    closePopup(staffPackagePopup)
}

const syncPickerWithDate = (value?: string) => {
    const source = value ? new Date(value.replace(/-/g, '/')) : new Date()
    const date = Number.isNaN(source.getTime()) ? new Date() : source
    datePickerValue.value = [
        Math.max(0, years.indexOf(date.getFullYear())),
        date.getMonth(),
        date.getDate() - 1
    ]
}

const openDatePicker = () => {
    syncPickerWithDate(formData.service_date || orderInfo.value?.service_date)
    datePopup.value?.open()
}
const onDateChange = (event: any) => {
    datePickerValue.value = event?.detail?.value || [0, 0, 0]
}
const confirmDate = () => {
    const year = years[datePickerValue.value[0]] || years[0]
    const month = String(months[datePickerValue.value[1]] || months[0]).padStart(2, '0')
    const day = String(days.value[datePickerValue.value[2]] || days.value[0]).padStart(2, '0')
    formData.service_date = `${year}-${month}-${day}`
    closePopup(datePopup)
}

const chooseImage = () => {
    uni.chooseImage({
        count: 5 - formData.attach_images.length,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: (result) => formData.attach_images.push(...result.tempFilePaths)
    })
}
const removeImage = (index: number) => {
    formData.attach_images.splice(index, 1)
}

const handleSubmit = async () => {
    if (!canSubmit.value) {
        uni.showToast({ title: '请完善申请信息', icon: 'none' })
        return
    }
    submitting.value = true
    try {
        const params: Record<string, any> = {
            order_id: orderId.value,
            add_type: addType.value,
            service_date: formData.service_date,
            reason: formData.reason.trim(),
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
        uni.showToast({ title: '申请已提交', icon: 'none' })
        setTimeout(
            () =>
                uni.redirectTo({
                    url: `/packages/pages/order_change/change_detail?id=${res.change_id}`
                }),
            1200
        )
    } catch (error: any) {
        uni.showToast({ title: error?.message || '提交失败', icon: 'none' })
    } finally {
        submitting.value = false
    }
}

watch(addType, async () => {
    selectedPackage.value = null
    selectedStaff.value = null
    selectedStaffPackage.value = null
    staffPackageList.value = []
    if (addType.value === 'package' && currentOrderStaffId.value > 0) {
        await fetchPackageList(currentOrderStaffId.value)
    }
})

onLoad((options: any) => {
    orderId.value = Number(options?.order_id || 0)
    if (orderId.value) {
        void fetchOrderInfo()
        void checkOrder()
    }
    void fetchStaffList()
})
</script>

<style lang="scss" scoped>
@import './shared.scss';
</style>
