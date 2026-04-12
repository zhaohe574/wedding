<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="订单确认" />

        <view class="order-confirm-page">
            <view class="order-confirm-page__step">
                <view class="order-confirm-page__step-chip">
                    <text>{{ orderConfirmStepTag }}</text>
                </view>
            </view>

            <view class="order-confirm-page__surface">
                <BaseCard
                    v-if="loading"
                    variant="glass"
                    scene="consumer"
                    :hoverable="false"
                    class="loading-state"
                >
                    <LoadingState text="加载中..." />
                </BaseCard>

                <view v-else class="order-confirm-page__content">
                    <BaseCard
                        variant="glass"
                        scene="consumer"
                        :hoverable="false"
                        class="section-card section-card--reserve"
                    >
                        <view class="section-header section-header--stack">
                            <view>
                                <text class="section-title">预约信息</text>
                                <text class="section-desc"
                                    >当前档期和服务地区会直接写入订单，提交前请再次确认。</text
                                >
                            </view>
                        </view>
                        <view class="booking-grid">
                            <view class="booking-box">
                                <text class="booking-box__label">预约日期</text>
                                <text class="booking-box__value">{{ bookingDateText || '-' }}</text>
                            </view>
                            <view class="booking-box">
                                <text class="booking-box__label">服务地区</text>
                                <text class="booking-box__value booking-box__value--region">
                                    {{ serviceRegionText || '未选择区县' }}
                                </text>
                            </view>
                        </view>
                    </BaseCard>

                    <BaseCard
                        variant="glass"
                        scene="consumer"
                        :hoverable="false"
                        class="section-card section-card--contact"
                    >
                        <view class="section-header section-header--stack">
                            <view>
                                <text class="section-title">联系人信息</text>
                                <text class="section-desc"
                                    >用于订单沟通、档期确认和上门服务，请尽量填写准确信息。</text
                                >
                            </view>
                        </view>

                        <view class="field-item field-item--compact">
                            <text class="field-label field-label--required">联系人</text>
                            <view class="field-shell">
                                <tn-input
                                    v-model="form.contact_name"
                                    placeholder="请输入联系人姓名"
                                    :border="true"
                                    height="84"
                                />
                            </view>
                        </view>

                        <view class="field-item field-item--compact">
                            <text class="field-label field-label--required">手机号码</text>
                            <view class="field-shell">
                                <tn-input
                                    v-model="form.contact_mobile"
                                    placeholder="请输入手机号码"
                                    type="number"
                                    :border="true"
                                    height="84"
                                />
                            </view>
                        </view>

                        <view class="field-item field-item--address">
                            <text class="field-label field-label--required">详细地址</text>
                            <view class="field-shell">
                                <tn-input
                                    v-model="form.service_address"
                                    placeholder="请输入详细地址"
                                    :border="true"
                                    height="92"
                                />
                            </view>
                        </view>

                        <view class="field-item field-item--note">
                            <text class="field-label">备注</text>
                            <view class="field-shell field-shell--textarea">
                                <textarea
                                    v-model="form.remark"
                                    class="remark-textarea"
                                    maxlength="200"
                                    placeholder="请输入备注（选填）"
                                    placeholder-style="color: #b4aca8;"
                                />
                            </view>
                        </view>
                    </BaseCard>

                    <BaseCard
                        v-if="mainItem"
                        variant="glass"
                        scene="consumer"
                        :hoverable="false"
                        class="section-card section-card--service"
                    >
                        <view class="section-header">
                            <view class="section-header__text">
                                <text class="section-title">服务项目</text>
                                <text class="section-desc"
                                    >先确认主服务，再统一核对附加项与关联服务人员。</text
                                >
                            </view>
                            <view class="section-action" @click="handleReselect">
                                <text>重新选择</text>
                            </view>
                        </view>

                        <view class="service-main">
                            <view class="service-main__avatar">
                                <image
                                    v-if="mainItem.staff?.avatar"
                                    :src="mainItem.staff?.avatar"
                                    class="service-main__avatar-image"
                                    mode="aspectFill"
                                />
                                <text v-else class="service-main__avatar-text">{{
                                    staffInitial
                                }}</text>
                            </view>

                            <view class="service-main__info">
                                <text class="service-main__name">{{
                                    mainItem.staff?.name || '服务人员'
                                }}</text>
                                <text class="service-main__meta">已为当前档期锁定服务人员</text>
                                <view class="service-main__tag">
                                    <text>主套餐</text>
                                </view>
                            </view>

                            <view class="service-main__price">
                                <text class="service-main__price-label">套餐金额</text>
                                <text class="service-main__price-value"
                                    >¥{{ formatPrice(mainItem.price) }}</text
                                >
                            </view>
                        </view>

                        <text class="service-package-summary">{{ mainPackageSummary }}</text>

                        <view v-if="extraItems.length" class="service-addon">
                            <view class="service-addon__header">
                                <text class="service-addon__title">附加内容</text>
                                <text class="service-addon__meta">{{ extraItems.length }} 项</text>
                            </view>

                            <view class="service-addon__list">
                                <view
                                    v-for="item in extraItems"
                                    :key="`${item.item_type}-${item.staff_id}-${item.package_id}-${item.price}`"
                                    class="service-addon__card"
                                >
                                    <view class="service-addon__top">
                                        <text class="service-addon__name">{{
                                            getExtraItemTitle(item)
                                        }}</text>
                                        <text class="service-addon__price"
                                            >¥{{ formatPrice(item.price) }}</text
                                        >
                                    </view>
                                    <text class="service-addon__desc">{{
                                        getExtraItemDesc(item)
                                    }}</text>
                                </view>
                            </view>
                        </view>
                    </BaseCard>

                    <BaseCard
                        variant="glass"
                        scene="consumer"
                        :hoverable="false"
                        class="section-card section-card--payment"
                    >
                        <view class="section-header section-header--stack">
                            <view>
                                <text class="section-title">支付安排</text>
                                <text class="section-desc"
                                    >提交后将根据当前支付规则进入对应支付阶段。</text
                                >
                            </view>
                        </view>
                        <view class="payment-arrangement">
                            <view class="payment-arrangement__grid">
                                <view class="payment-arrangement__summary-card">
                                    <text class="payment-arrangement__summary-label">支付模式</text>
                                    <text class="payment-arrangement__summary-value">{{
                                        paymentModeText
                                    }}</text>
                                </view>
                                <view
                                    class="payment-arrangement__summary-card payment-arrangement__summary-card--accent"
                                >
                                    <text class="payment-arrangement__summary-label">当前应付</text>
                                    <text
                                        class="payment-arrangement__summary-value payment-arrangement__summary-value--amount"
                                        >¥{{ currentPayAmountText }}</text
                                    >
                                </view>
                            </view>

                            <view class="payment-arrangement__stage">
                                <text class="payment-arrangement__stage-label">当前阶段</text>
                                <text class="payment-arrangement__stage-value">{{
                                    currentPayStageText
                                }}</text>
                            </view>

                            <view class="payment-arrangement__detail">
                                <view class="payment-arrangement__detail-row">
                                    <text class="payment-arrangement__detail-label">订单总额</text>
                                    <text class="payment-arrangement__detail-value"
                                        >¥{{ totalAmountText }}</text
                                    >
                                </view>
                                <view
                                    v-if="Number(preview.deposit_amount || 0) > 0"
                                    class="payment-arrangement__detail-row"
                                >
                                    <text class="payment-arrangement__detail-label">定金</text>
                                    <text class="payment-arrangement__detail-value"
                                        >¥{{ formatPrice(preview.deposit_amount) }}</text
                                    >
                                </view>
                                <view
                                    v-if="Number(preview.balance_amount || 0) > 0"
                                    class="payment-arrangement__detail-row"
                                >
                                    <text class="payment-arrangement__detail-label">尾款</text>
                                    <text class="payment-arrangement__detail-value"
                                        >¥{{ formatPrice(preview.balance_amount) }}</text
                                    >
                                </view>
                            </view>

                            <view v-if="preview.deposit_remark" class="payment-arrangement__remark">
                                <text class="payment-arrangement__remark-label">{{
                                    paymentRemarkLabel
                                }}</text>
                                <text class="payment-arrangement__remark-text">{{
                                    preview.deposit_remark
                                }}</text>
                            </view>
                        </view>
                    </BaseCard>
                </view>
            </view>

            <ActionArea class="order-confirm-page__submit-bar" sticky safeBottom>
                <view class="submit-summary">
                    <text class="submit-summary__label">合计</text>
                    <text class="submit-summary__amount"
                        >¥{{ formatPrice(preview.pay_amount) }}</text
                    >
                </view>
                <view class="order-confirm-page__submit-action">
                    <BaseButton
                        block
                        size="lg"
                        :disabled="!canSubmit"
                        :loading="submitting"
                        @click="handleSubmit"
                    >
                        提交订单
                    </BaseButton>
                </view>
            </ActionArea>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onLoad, onShow, onUnload } from '@dcloudio/uni-app'
import ActionArea from '@/components/base/ActionArea.vue'
import PageShell from '@/components/base/PageShell.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import { previewOrder, createOrder } from '@/api/order'
import { lockSchedule, releaseScheduleLock } from '@/api/schedule'
import { BACK_URL } from '@/enums/constantEnums'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import cache from '@/utils/cache'
import { requestSubscribeByScene } from '@/utils/subscribe'
import {
    getOrderConfirmPageUrl,
    getStaffBookingPageUrl,
    normalizeBookingQuery,
    toBookingOrderParams
} from '@/utils/staff-booking'
import {
    formatServiceRegionText,
    hasServiceRegion,
    loadServiceRegionSelection,
    normalizeServiceRegion,
    saveServiceRegionSelection
} from '@/utils/service-region'

const $theme = useThemeStore()
const userStore = useUserStore()

const loading = ref(false)
const submitting = ref(false)
const initialized = ref(false)
const scheduleLocking = ref(false)
const orderCreated = ref(false)
const selection = reactive({
    staff_id: 0,
    package_id: 0,
    date: '',
    province_code: '',
    province_name: '',
    city_code: '',
    city_name: '',
    district_code: '',
    district_name: '',
    addon_ids: [] as number[],
    butler_staff_id: 0,
    butler_package_id: 0,
    director_staff_id: 0,
    director_package_id: 0,
    flow_total_steps: 0
})

const preview = ref<any>({
    items: [],
    service_amount: 0,
    total_amount: 0,
    pay_amount: 0,
    deposit_amount: 0,
    balance_amount: 0,
    payment_mode: 'full',
    payment_mode_desc: '全款支付',
    need_pay: 'full',
    need_pay_amount: 0,
    need_pay_label: '立即支付',
    current_pay_stage: 'full',
    current_pay_stage_desc: '待全额支付',
    deposit_remark: ''
})

const form = reactive({
    contact_name: '',
    contact_mobile: '',
    service_address: '',
    remark: ''
})
const lockedSchedules = ref<Array<{ staff_id: number; date: string }>>([])

const hasItems = computed(
    () => Array.isArray(preview.value.items) && preview.value.items.length > 0
)
const mainItem = computed(() => {
    if (!hasItems.value) {
        return null
    }
    return (
        preview.value.items.find((item: any) => Number(item?.item_type || 1) === 1) ||
        preview.value.items[0] ||
        null
    )
})
const extraItems = computed(() =>
    hasItems.value
        ? preview.value.items.filter((item: any) => Number(item?.item_type || 1) !== 1)
        : []
)
const bookingDateText = computed(() => mainItem.value?.schedule_date || selection.date || '')
const serviceRegionText = computed(() => {
    const city = String(selection.city_name || '').trim()
    const district = String(selection.district_name || '').trim()
    const brief = [city, district].filter(Boolean).join(' / ')
    return brief || formatServiceRegionText(selection, ' / ')
})
const canSubmit = computed(() => hasItems.value && !loading.value && !submitting.value)
const orderFlowTotalSteps = computed(() => {
    const total = Number(selection.flow_total_steps || 0)
    return Number.isInteger(total) && total > 0 ? total : 0
})
const orderConfirmStepTag = computed(() => {
    if (!orderFlowTotalSteps.value) {
        return '订单确认｜提交前确认订单信息'
    }

    return `步骤 ${orderFlowTotalSteps.value}/${orderFlowTotalSteps.value}｜提交前确认订单信息`
})
const staffInitial = computed(() => {
    const name = String(mainItem.value?.staff?.name || '').trim()
    return name ? name.slice(0, 1) : '婚'
})
const paymentModeText = computed(
    () => String(preview.value.payment_mode_desc || '').trim() || '全款支付'
)
const currentPayStageText = computed(
    () => String(preview.value.current_pay_stage_desc || '').trim() || '待支付'
)
const currentPayAmountText = computed(() =>
    formatPrice(preview.value.need_pay_amount ?? preview.value.pay_amount)
)
const totalAmountText = computed(() =>
    formatPrice(preview.value.total_amount ?? preview.value.pay_amount)
)
const paymentRemarkLabel = computed(() =>
    Number(preview.value.deposit_amount || 0) > 0 ? '定金说明' : '支付说明'
)
const mainPackageSummary = computed(() => {
    if (!mainItem.value) {
        return '主套餐'
    }

    const packageName = mainItem.value?.package?.name || mainItem.value?.package_name || ''
    const packageDesc =
        mainItem.value?.package?.description || mainItem.value?.package_description || ''
    const summary = [packageName, packageDesc]
        .filter((item, index, list) => item && list.indexOf(item) === index)
        .join('，')

    return summary || '主套餐'
})

const formatPrice = (value: any) => Number(value || 0).toFixed(2)

const collectScheduleLocks = () => {
    const date = String(selection.date || '').trim()
    if (!date) {
        return []
    }

    return [
        selection.staff_id > 0 && selection.package_id > 0 ? selection.staff_id : 0,
        selection.butler_staff_id > 0 && selection.butler_package_id > 0
            ? selection.butler_staff_id
            : 0,
        selection.director_staff_id > 0 && selection.director_package_id > 0
            ? selection.director_staff_id
            : 0,
    ]
        .map((staffId) => Number(staffId || 0))
        .filter((staffId) => Number.isInteger(staffId) && staffId > 0)
        .filter((staffId, index, list) => list.indexOf(staffId) === index)
        .map((staff_id) => ({ staff_id, date }))
}

const getConfirmPageUrl = () => {
    return getOrderConfirmPageUrl(selection)
}

const ensureOrderConfirmLogin = (message = '请先登录后确认订单') => {
    if (userStore.isLogin) {
        return true
    }

    cache.set(BACK_URL, getConfirmPageUrl())
    uni.showToast({ title: message, icon: 'none' })
    setTimeout(() => {
        uni.navigateTo({ url: '/pages/login/login' })
    }, 300)
    return false
}

const ensureSubmitLogin = () => ensureOrderConfirmLogin('请先登录后提交订单')

const initContact = async () => {
    await userStore.getUser()
    const info = userStore.userInfo || {}
    if (!form.contact_name) {
        form.contact_name = info.real_name || info.nickname || ''
    }
    if (!form.contact_mobile) {
        form.contact_mobile = info.mobile || ''
    }
}

const buildSelectionParams = (extra: Record<string, any> = {}) => {
    const params: Record<string, any> = {
        ...toBookingOrderParams(selection),
        ...extra
    }
    return params
}

const getStaffDetailUrl = () => {
    if (!selection.staff_id) {
        return ''
    }
    return getStaffBookingPageUrl(selection)
}

const releaseCurrentLocks = async (silent = true) => {
    if (orderCreated.value || !lockedSchedules.value.length) {
        return
    }

    const targets = [...lockedSchedules.value]
    lockedSchedules.value = []

    await Promise.allSettled(
        targets.map((target) =>
            releaseScheduleLock({
                staff_id: target.staff_id,
                date: target.date
            }).catch((error) => {
                if (!silent) {
                    return Promise.reject(error)
                }
                return null
            })
        )
    )
}

const ensureScheduleLocks = async () => {
    if (scheduleLocking.value) {
        return lockedSchedules.value.length > 0
    }

    const targets = collectScheduleLocks()
    if (!targets.length) {
        return false
    }

    scheduleLocking.value = true
    const acquired: Array<{ staff_id: number; date: string }> = []
    try {
        for (const target of targets) {
            await lockSchedule({
                staff_id: target.staff_id,
                date: target.date
            })
            acquired.push(target)
        }

        lockedSchedules.value = targets
        return true
    } catch (error) {
        if (acquired.length) {
            await Promise.allSettled(
                acquired.map((target) =>
                    releaseScheduleLock({
                        staff_id: target.staff_id,
                        date: target.date
                    }).catch(() => null)
                )
            )
        }
        lockedSchedules.value = []
        throw error
    } finally {
        scheduleLocking.value = false
    }
}

const handlePreviewError = async (message: string) => {
    await releaseCurrentLocks()
    uni.showToast({ title: message, icon: 'none' })
    const url = getStaffDetailUrl()
    setTimeout(() => {
        if (url) {
            uni.redirectTo({ url })
            return
        }
        uni.navigateBack()
    }, 1200)
}

const fetchPreview = async () => {
    loading.value = true
    try {
        const data = await previewOrder(buildSelectionParams())
        preview.value = {
            ...preview.value,
            ...data,
            items: data?.items || [],
            service_amount: data?.service_amount || 0,
            total_amount: data?.total_amount || 0,
            pay_amount: data?.pay_amount || 0,
            deposit_amount: data?.deposit_amount || 0,
            balance_amount: data?.balance_amount || 0,
            payment_mode: data?.payment_mode || 'full',
            payment_mode_desc: data?.payment_mode_desc || '全款支付',
            need_pay: data?.need_pay || 'full',
            need_pay_amount: data?.need_pay_amount ?? data?.pay_amount ?? 0,
            need_pay_label: data?.need_pay_label || '立即支付',
            current_pay_stage: data?.current_pay_stage || 'full',
            current_pay_stage_desc: data?.current_pay_stage_desc || '待全额支付',
            deposit_remark: data?.deposit_remark || ''
        }
        if (!preview.value.items.length) {
            await handlePreviewError('暂无可结算的服务')
        }
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '加载失败'
        await handlePreviewError(errorMsg)
    } finally {
        loading.value = false
    }
}

const isValidMobile = (mobile: string) => /^1[3-9]\d{9}$/.test(mobile)

const handleReselect = async () => {
    await releaseCurrentLocks()
    const url = getStaffDetailUrl()
    if (!url) {
        uni.navigateBack()
        return
    }
    uni.redirectTo({ url })
}

const handleSubmit = async () => {
    if (!canSubmit.value) return
    if (!ensureSubmitLogin()) {
        return
    }
    if (!form.contact_name.trim()) {
        uni.showToast({ title: '请输入联系人姓名', icon: 'none' })
        return
    }
    if (!form.contact_mobile.trim()) {
        uni.showToast({ title: '请输入手机号码', icon: 'none' })
        return
    }
    if (!isValidMobile(form.contact_mobile.trim())) {
        uni.showToast({ title: '手机号格式不正确', icon: 'none' })
        return
    }
    if (!form.service_address.trim()) {
        uni.showToast({ title: '请输入详细地址', icon: 'none' })
        return
    }

    submitting.value = true
    try {
        await ensureScheduleLocks()

        try {
            await requestSubscribeByScene('order_confirm')
        } catch (error) {
            // 订阅授权失败不影响下单
        }

        const params: any = {
            ...buildSelectionParams(),
            contact_name: form.contact_name.trim(),
            contact_mobile: form.contact_mobile.trim(),
            service_address: form.service_address.trim()
        }
        if (form.remark.trim()) params.remark = form.remark.trim()

        const res = await createOrder(params)
        const orderId = Number(res?.order_id || res?.id || 0)
        orderCreated.value = true
        lockedSchedules.value = []
        uni.showToast({ title: '订单已提交', icon: 'success' })
        if (orderId) {
            uni.reLaunch({ url: `/pages/order_detail/order_detail?id=${orderId}` })
        } else {
            uni.reLaunch({ url: '/pages/order/order' })
        }
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '提交失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    } finally {
        submitting.value = false
    }
}

const initPage = async () => {
    if (!ensureOrderConfirmLogin()) {
        return
    }
    try {
        await ensureScheduleLocks()
        await initContact()
        await fetchPreview()
        initialized.value = true
    } catch (error: any) {
        const errorMsg =
            typeof error === 'string' ? error : error?.msg || error?.message || '档期锁定失败'
        await handlePreviewError(errorMsg)
    }
}

const getExtraItemTitle = (item: any) => {
    if (Number(item?.item_type || 1) === 2) {
        return item?.item_meta?.label || item?.package?.name || item?.package_name || '预约附加项'
    }
    if (Number(item?.item_type || 1) === 3) {
        const roleLabel = item?.item_meta?.role_label || '关联服务'
        const staffName = item?.staff?.name || item?.staff_name || ''
        return staffName ? `${roleLabel} · ${staffName}` : roleLabel
    }
    return item?.package?.name || item?.package_name || '服务项目'
}

const getExtraItemDesc = (item: any) => {
    if (Number(item?.item_type || 1) === 2) {
        return item?.package?.description || item?.package_description || '服务人员预约附加项'
    }
    if (Number(item?.item_type || 1) === 3) {
        return item?.package?.name || item?.package_name || '已锁定推荐套餐'
    }
    return item?.package?.description || item?.package_description || ''
}

onLoad((options: any) => {
    const normalized = normalizeBookingQuery({
        ...loadServiceRegionSelection(),
        ...options
    })
    selection.staff_id = normalized.staff_id
    selection.package_id = normalized.package_id
    selection.date = normalized.date
    selection.addon_ids = normalized.addon_ids
    selection.butler_staff_id = normalized.butler_staff_id
    selection.butler_package_id = normalized.butler_package_id
    selection.director_staff_id = normalized.director_staff_id
    selection.director_package_id = normalized.director_package_id
    selection.flow_total_steps = normalized.flow_total_steps
    const region = normalizeServiceRegion(normalized)
    selection.province_code = region.province_code
    selection.province_name = region.province_name
    selection.city_code = region.city_code
    selection.city_name = region.city_name
    selection.district_code = region.district_code
    selection.district_name = region.district_name

    if (hasServiceRegion(region)) {
        saveServiceRegionSelection(region)
    }

    if (
        !selection.staff_id ||
        !selection.package_id ||
        !selection.date ||
        !hasServiceRegion(selection)
    ) {
        handlePreviewError('预约信息不完整，请重新选择服务地区和日期')
        return
    }

    initPage()
})

onShow(() => {
    if (initialized.value) {
        if (!userStore.isLogin) {
            return
        }

        ensureScheduleLocks()
            .then((locked) => {
                if (!locked) {
                    return
                }
                return fetchPreview()
            })
            .catch((error: any) => {
                const errorMsg =
                    typeof error === 'string'
                        ? error
                        : error?.msg || error?.message || '档期锁定失败'
                handlePreviewError(errorMsg)
            })
    }
})

onUnload(() => {
    if (orderCreated.value) {
        return
    }
    void releaseCurrentLocks()
})
</script>

<style lang="scss" scoped>
.order-confirm-page {
    padding: 20rpx 0 calc(236rpx + constant(safe-area-inset-bottom));
    padding: 20rpx 0 calc(236rpx + env(safe-area-inset-bottom));
    background: transparent;

    &__step {
        padding: 0 37rpx;
    }

    &__step-chip {
        display: inline-flex;
        align-items: center;
        padding: 13rpx 22rpx;
        border-radius: 999rpx;
        background: var(--wm-color-primary-soft, #fff1ee);
        border: 1rpx solid var(--wm-color-border-strong, #f4c7bf);

        text {
            font-size: 22rpx;
            font-weight: 600;
            color: var(--wm-color-primary, #e85a4f);
        }
    }

    &__surface {
        padding: 30rpx 37rpx 0;
    }

    &__content {
        display: flex;
        flex-direction: column;
        gap: 22rpx;
    }

    &__submit-bar {
        z-index: 90;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 22rpx;
        border-radius: 45rpx;
    }

    &__submit-action {
        width: 242rpx;
        flex-shrink: 0;
    }
}

.loading-state {
    min-height: 56vh;
}

.section-card {
    border-radius: 49rpx !important;
    padding: 30rpx 34rpx !important;
}

.section-card--service {
    padding: 34rpx 34rpx !important;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-bottom: 12rpx;
}

.section-header__text {
    min-width: 0;
    flex: 1;
}

.section-header--stack {
    align-items: flex-start;
    justify-content: flex-start;
}

.section-title {
    display: block;
    font-size: 30rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.section-desc {
    display: block;
    margin-top: 12rpx;
    font-size: 24rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #7f7b78);
}

.section-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    padding: 12rpx 20rpx;
    border-radius: 999rpx;
    background: var(--wm-color-primary-soft, #fff1ee);
    border: 1rpx solid var(--wm-color-border-strong, #f4c7bf);

    text {
        font-size: 22rpx;
        font-weight: 600;
        color: var(--wm-color-primary, #e85a4f);
    }
}

.booking-grid {
    display: flex;
    gap: 16rpx;
}

.booking-box {
    flex: 1;
    min-width: 0;
    padding: 30rpx 30rpx;
    border-radius: 37rpx;
    background: #fcfbf9;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
}

.booking-box__label {
    display: block;
    font-size: 22rpx;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--wm-text-tertiary, #b4aca8);
}

.booking-box__value {
    display: block;
    margin-top: 10rpx;
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.45;
    color: var(--wm-text-primary, #1e2432);
}

.booking-box__value--region {
    font-size: 28rpx;
}

.payment-arrangement {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.payment-arrangement__grid {
    display: flex;
    gap: 16rpx;
}

.payment-arrangement__summary-card {
    flex: 1;
    min-width: 0;
    padding: 30rpx 30rpx 28rpx;
    border-radius: 37rpx;
    background: #fcfbf9;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
}

.payment-arrangement__summary-card--accent {
    background: linear-gradient(180deg, rgba(255, 244, 240, 0.96) 0%, #fffaf7 100%);
    border-color: var(--wm-color-border-strong, #f4c7bf);
}

.payment-arrangement__summary-label {
    display: block;
    font-size: 22rpx;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--wm-text-tertiary, #b4aca8);
}

.payment-arrangement__summary-value {
    display: block;
    margin-top: 10rpx;
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.45;
    color: var(--wm-text-primary, #1e2432);
    word-break: break-word;
}

.payment-arrangement__summary-value--amount {
    font-size: 38rpx;
    line-height: 1.25;
    color: var(--wm-color-primary, #e85a4f);
}

.payment-arrangement__stage {
    padding: 24rpx 28rpx;
    border-radius: 32rpx;
    background: linear-gradient(180deg, rgba(255, 250, 247, 0.96) 0%, #ffffff 100%);
    border: 1rpx solid rgba(244, 199, 191, 0.92);
}

.payment-arrangement__stage-label {
    display: block;
    font-size: 22rpx;
    letter-spacing: 0.06em;
    color: var(--wm-text-tertiary, #b4aca8);
}

.payment-arrangement__stage-value {
    display: block;
    margin-top: 8rpx;
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1.55;
    color: var(--wm-text-primary, #1e2432);
    word-break: break-word;
}

.payment-arrangement__detail {
    padding: 10rpx 0 0;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.payment-arrangement__detail-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24rpx;
    padding: 10rpx 2rpx;
}

.payment-arrangement__detail-label {
    min-width: 0;
    font-size: 24rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #7f7b78);
}

.payment-arrangement__detail-value {
    flex-shrink: 0;
    text-align: right;
    font-size: 26rpx;
    font-weight: 700;
    line-height: 1.6;
    color: var(--wm-text-primary, #1e2432);
}

.payment-arrangement__remark {
    padding: 24rpx 26rpx;
    border-radius: 30rpx;
    background: rgba(252, 251, 249, 0.96);
    border: 1rpx solid var(--wm-color-border, #efe6e1);
}

.payment-arrangement__remark-label {
    display: block;
    font-size: 22rpx;
    font-weight: 700;
    letter-spacing: 0.04em;
    color: var(--wm-text-secondary, #7f7b78);
}

.payment-arrangement__remark-text {
    display: block;
    margin-top: 10rpx;
    font-size: 24rpx;
    line-height: 1.65;
    color: var(--wm-text-secondary, #7f7b78);
    word-break: break-word;
}

.field-item + .field-item {
    margin-top: 20rpx;
}

.field-label {
    display: block;
    margin-bottom: 8rpx;
    font-size: 28rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.field-label--required::before {
    content: '*';
    margin-right: 6rpx;
    color: var(--wm-color-primary, #e85a4f);
}

.field-shell {
    padding: 0;
    border-radius: 37rpx;
    background: #fcfbf9;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
}

.field-shell--textarea {
    padding: 30rpx;
    border-radius: 37rpx;
}

.field-shell :deep(.tn-input) {
    min-height: 84rpx !important;
    padding: 0 30rpx !important;
    border-radius: 37rpx !important;
    background: #fcfbf9 !important;
    border: none !important;
    box-shadow: none !important;
    font-size: 28rpx !important;
    color: var(--wm-text-primary, #1e2432) !important;
}

.field-item--address .field-shell :deep(.tn-input) {
    min-height: 92rpx !important;
}

.field-shell :deep(.input-placeholder) {
    color: var(--wm-text-tertiary, #b4aca8) !important;
}

.remark-textarea {
    width: 100%;
    min-height: 100rpx;
    font-size: 28rpx;
    line-height: 1.6;
    color: var(--wm-text-primary, #1e2432);
}

.service-main {
    display: flex;
    align-items: flex-start;
    gap: 18rpx;
}

.service-main__avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 96rpx;
    height: 96rpx;
    flex-shrink: 0;
    overflow: hidden;
    border-radius: 37rpx;
    background: var(--wm-color-primary-soft, #fff1ee);
    border: 1rpx solid var(--wm-color-border-strong, #f4c7bf);
}

.service-main__avatar-image {
    width: 100%;
    height: 100%;
    display: block;
}

.service-main__avatar-text {
    font-size: 36rpx;
    font-weight: 700;
    color: var(--wm-color-primary, #e85a4f);
}

.service-main__info {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.service-main__name {
    display: block;
    font-size: 32rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.service-main__meta {
    display: block;
    font-size: 24rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.service-main__tag {
    display: inline-flex;
    align-items: center;
    align-self: flex-start;
    padding: 8rpx 18rpx;
    border-radius: 999rpx;
    background: var(--wm-color-primary-soft, #fff1ee);

    text {
        font-size: 22rpx;
        font-weight: 700;
        color: var(--wm-color-primary, #e85a4f);
    }
}

.service-main__price {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6rpx;
    flex-shrink: 0;
}

.service-main__price-label {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);
}

.service-main__price-value {
    font-size: 32rpx;
    font-weight: 700;
    color: var(--wm-color-primary, #e85a4f);
}

.service-package-summary {
    display: block;
    margin-top: 18rpx;
    font-size: 26rpx;
    line-height: 1.55;
    color: var(--wm-text-primary, #1e2432);
}

.service-addon {
    margin-top: 18rpx;
    padding-top: 18rpx;
    border-top: 1rpx solid #f7f1ed;
}

.service-addon__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-bottom: 14rpx;
}

.service-addon__title {
    font-size: 28rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.service-addon__meta {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);
}

.service-addon__list {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.service-addon__card {
    padding: 30rpx 30rpx;
    border-radius: 37rpx;
    background: #fcfbf9;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
}

.service-addon__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.service-addon__name {
    min-width: 0;
    flex: 1;
    font-size: 28rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.service-addon__price {
    font-size: 28rpx;
    font-weight: 700;
    color: var(--wm-color-secondary, #c99b73);
}

.service-addon__desc {
    display: block;
    margin-top: 8rpx;
    font-size: 24rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #7f7b78);
}

.submit-summary {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 4rpx;
    min-width: 0;
    flex: 1;
}

.submit-summary__label {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);
}

.submit-summary__amount {
    font-size: 44rpx;
    font-weight: 700;
    color: var(--wm-color-primary, #e85a4f);
}

@media screen and (max-width: 380px) {
    .booking-grid {
        flex-direction: column;
    }

    .payment-arrangement__grid {
        flex-direction: column;
    }

    .service-main {
        flex-wrap: wrap;
    }

    .service-main__price {
        width: 100%;
        align-items: flex-start;
        padding-left: 114rpx;
    }

    .order-confirm-page__submit-action {
        width: 220rpx;
    }
}
</style>
