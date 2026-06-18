<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar
            title="订单确认"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <view class="order-confirm-page wm-page-content">
            <view class="order-confirm-page__step">
                <StatusBadge tone="warning" size="sm">
                    {{ orderConfirmStepTag }}
                </StatusBadge>
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

                <BaseCard
                    v-else-if="pageError"
                    variant="glass"
                    scene="consumer"
                    :hoverable="false"
                    class="order-confirm-page__state-card"
                >
                    <EmptyState
                        :title="pageError.title"
                        :description="pageError.message"
                        :action-text="pageError.actionText"
                        @action="handlePageErrorAction"
                    />

                    <view class="order-confirm-page__state-actions">
                        <view class="order-confirm-page__state-link" @click="handleReselect">
                            <text>重新选择服务</text>
                        </view>

                        <view class="order-confirm-page__state-divider" />

                        <view class="order-confirm-page__state-link" @click="goHome">
                            <text>返回首页</text>
                        </view>
                    </view>
                </BaseCard>

                <view v-else class="order-confirm-page__content">
                    <BaseCard
                        variant="glass"
                        scene="consumer"
                        :hoverable="false"
                        class="section-card section-card--reserve wm-form-block"
                    >
                        <view class="section-header section-header--stack">
                            <view>
                                <text class="section-title">预约信息</text>
                                <text class="section-desc">提交前请确认档期与地区。</text>
                            </view>
                        </view>
                        <view class="booking-grid">
                            <BaseInfoRow label="预约日期" :value="bookingDateText || '-'" />

                            <BaseInfoRow
                                label="服务地区"
                                :value="serviceRegionText || '未选择区县'"
                                multiline
                            />
                        </view>
                    </BaseCard>

                    <BaseCard
                        variant="glass"
                        scene="consumer"
                        :hoverable="false"
                        class="section-card section-card--contact wm-form-block"
                    >
                        <view class="section-header section-header--stack">
                            <view>
                                <text class="section-title">联系人信息</text>
                                <text class="section-desc">请填写准确信息。</text>
                            </view>
                        </view>

                        <view class="field-item field-item--compact">
                            <BaseInput
                                v-model="form.contact_name"
                                label="* 联系人"
                                placeholder="请输入联系人姓名"
                                variant="filled"
                                clearable
                            />
                        </view>

                        <view class="field-item field-item--compact">
                            <BaseInput
                                v-model="form.contact_mobile"
                                label="* 手机号码"
                                placeholder="请输入手机号码"
                                type="tel"
                                variant="filled"
                                clearable
                            />
                        </view>

                        <view class="field-item field-item--address">
                            <BaseInput
                                v-model="form.service_address"
                                label="* 详细地址"
                                placeholder="请输入详细地址"
                                variant="filled"
                                clearable
                            />
                        </view>

                        <view class="field-item field-item--note">
                            <text class="field-label">备注</text>
                            <view class="field-shell field-shell--textarea">
                                <textarea
                                    v-model="form.remark"
                                    class="remark-textarea"
                                    maxlength="200"
                                    placeholder="请输入备注（选填）"
                                    placeholder-style="color: #9a9388;"
                                    :cursor-spacing="120"
                                    :auto-height="false"
                                    adjust-position
                                />
                            </view>
                        </view>
                    </BaseCard>

                    <BaseCard
                        v-if="mainItem"
                        variant="glass"
                        scene="consumer"
                        :hoverable="false"
                        class="section-card section-card--service wm-form-block"
                    >
                        <view class="section-header">
                            <view class="section-header__text">
                                <text class="section-title">服务项目</text>
                                <text class="section-desc">先确认主服务，再核对附加项。</text>
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
                                <text class="service-main__meta">已选择服务人员</text>
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
                                <BaseCard
                                    v-for="item in extraItems"
                                    :key="`${item.item_type}-${item.staff_id}-${item.package_id}-${item.price}`"
                                    variant="list"
                                    scene="consumer"
                                    padding="24rpx 26rpx"
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
                                </BaseCard>
                            </view>
                        </view>
                    </BaseCard>

                    <BaseCard
                        variant="glass"
                        scene="consumer"
                        :hoverable="false"
                        class="section-card section-card--payment wm-form-block"
                    >
                        <view class="section-header section-header--stack">
                            <view>
                                <text class="section-title">支付安排</text>
                                <text class="section-desc">提交后按支付规则进入下一步。</text>
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
                                <BaseInfoRow
                                    label="订单总额"
                                    :value="`¥${totalAmountText}`"
                                    tone="price"
                                />

                                <BaseInfoRow
                                    v-if="Number(preview.deposit_amount || 0) > 0"
                                    label="定金"
                                    :value="`¥${formatPrice(preview.deposit_amount)}`"
                                    tone="price"
                                />

                                <BaseInfoRow
                                    v-if="Number(preview.balance_amount || 0) > 0"
                                    label="尾款"
                                    :value="`¥${formatPrice(preview.balance_amount)}`"
                                    tone="price"
                                />
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

            <ActionArea v-if="!pageError" class="order-confirm-page__submit-bar" sticky safeBottom>
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
import { onLoad, onShow } from '@dcloudio/uni-app'
import ActionArea from '@/components/base/ActionArea.vue'
import PageShell from '@/components/base/PageShell.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseInfoRow from '@/components/base/BaseInfoRow.vue'
import BaseInput from '@/components/base/BaseInput.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { previewOrder, createOrder } from '@/api/order'
import { shouldUseOfflineCollection } from '@/utils/paymentChannel'
import { ClientEnum } from '@/enums/appEnums'
import { BACK_URL } from '@/enums/constantEnums'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import cache from '@/utils/cache'
import { client } from '@/utils/client'
import { confirmModal, showError, showSuccess } from '@/utils/feedback'
import { goHome, goLoginWithBack, normalizePageRecoveryError } from '@/utils/page-recovery'
import { navigateTo } from '@/utils/util'
import { getAllScenes, setSceneCache, subscribeOrderScenes } from '@/utils/subscribe'
import {
    clearBookingLockSession,
    getOrderConfirmPageUrl,
    getStaffBookingPageUrl,
    isBookingLockSessionMatchingSelection,
    normalizeBookingQuery,
    releaseAllBookingLocks,
    renewAllBookingLocks,
    toBookingOrderParams
} from '@/packages/common/utils/staff-booking'
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
const pageError = ref<ReturnType<typeof normalizePageRecoveryError> | null>(null)
const selection = reactive({
    staff_id: 0,
    package_id: 0,
    waitlist_id: 0,
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

const getConfirmPageUrl = () => {
    return getOrderConfirmPageUrl(selection)
}

const ensureOrderConfirmLogin = (message = '请先登录后确认订单') => {
    if (userStore.isLogin) {
        return true
    }

    cache.set(BACK_URL, getConfirmPageUrl())
    showError(message, '请先登录后确认订单')
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
        form.contact_name = info.real_name || ''
    }
    if (!form.contact_mobile) {
        form.contact_mobile = info.mobile || ''
    }
}

const warmOrderSubscribeScenes = async () => {
    try {
        setSceneCache(await getAllScenes())
    } catch (error) {
        console.error('预加载订单订阅场景失败', error)
    }
}

const buildSelectionParams = (extra: Record<string, any> = {}) => {
    const params: Record<string, any> = {
        ...toBookingOrderParams(selection),
        ...extra
    }
    return params
}

const getStaffBookingUrl = () => {
    if (!selection.staff_id) {
        return ''
    }
    return getStaffBookingPageUrl(selection)
}

const handlePreviewError = async (message: string) => {
    initialized.value = false
    loading.value = false
    submitting.value = false
    pageError.value = normalizePageRecoveryError(message || '订单预览加载失败', '订单预览加载失败')
}

const ensureBookingLockAlive = async () => {
    if (!isBookingLockSessionMatchingSelection(selection)) {
        throw new Error('预约锁已失效，请重新开始预约')
    }

    await renewAllBookingLocks()
}

const fetchPreview = async () => {
    loading.value = true
    try {
        await ensureBookingLockAlive()
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
        } else {
            pageError.value = null
        }
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '加载失败'
        if (errorMsg.includes('预约锁') || errorMsg.includes('档期')) {
            await releaseAllBookingLocks(true)
        }
        await handlePreviewError(errorMsg)
    } finally {
        loading.value = false
    }
}

const isValidMobile = (mobile: string) => /^1[3-9]\d{9}$/.test(mobile)

const handleReselect = async () => {
    pageError.value = null

    const url = getStaffBookingUrl()
    if (!url) {
        uni.navigateBack()
        return
    }
    uni.redirectTo({ url })
}

const handlePageErrorAction = () => {
    if (pageError.value?.kind === 'auth') {
        goLoginWithBack(getConfirmPageUrl())
        return
    }

    if (pageError.value?.message.includes('档期')) {
        void handleReselect()
        return
    }

    void initPage()
}

const promptOrderSubscribe = async () => {
    if (client !== ClientEnum.MP_WEIXIN) {
        return true
    }

    const confirmed = await confirmModal({
        title: '接收订单与服务提醒',
        content: '订阅后可接收订单确认和服务提醒。',
        confirmText: '去订阅',
        cancelText: '暂不订阅'
    })

    if (!confirmed) {
        return false
    }

    try {
        await subscribeOrderScenes()
    } catch (error) {
        console.error('请求订单订阅失败', error)
    }

    return true
}

const handleSubmit = async () => {
    if (!canSubmit.value) return
    if (!ensureSubmitLogin()) {
        return
    }
    if (!form.contact_name.trim()) {
        showError('请输入联系人姓名')
        return
    }
    if (!form.contact_mobile.trim()) {
        showError('请输入手机号码')
        return
    }
    if (!isValidMobile(form.contact_mobile.trim())) {
        showError('手机号格式不正确')
        return
    }
    if (!form.service_address.trim()) {
        showError('请输入详细地址')
        return
    }

    submitting.value = true
    try {
        await ensureBookingLockAlive()
        await promptOrderSubscribe()

        const params: any = {
            ...buildSelectionParams(),
            contact_name: form.contact_name.trim(),
            contact_mobile: form.contact_mobile.trim(),
            service_address: form.service_address.trim()
        }
        if (form.remark.trim()) params.remark = form.remark.trim()

        const res = await createOrder(params)
        const orderId = Number(res?.order_id || res?.id || 0)
        const offlineCollectionPayload = {
            ...preview.value,
            ...res,
            need_pay: res?.need_pay || preview.value.need_pay,
            current_pay_stage: res?.current_pay_stage || preview.value.current_pay_stage,
            payment_stage: res?.payment_stage || preview.value.payment_stage
        }
        const isOfflineCollectionOrder = shouldUseOfflineCollection(offlineCollectionPayload)
        showSuccess(isOfflineCollectionOrder ? '订单已提交，请联系顾问线下收款' : '订单已提交')
        clearBookingLockSession()
        if (orderId) {
            uni.reLaunch({ url: `/pages/order_detail/order_detail?id=${orderId}` })
        } else {
            navigateTo({ path: '/pages/order/order', type: 'shop' }, 'reLaunch')
        }
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '提交失败'
        if (errorMsg.includes('预约锁') || errorMsg.includes('档期')) {
            await releaseAllBookingLocks(true)
            await handlePreviewError(errorMsg)
        } else {
            showError(errorMsg, '提交失败')
        }
    } finally {
        submitting.value = false
    }
}

const initPage = async () => {
    if (!ensureOrderConfirmLogin()) {
        return
    }
    try {
        await ensureBookingLockAlive()
        await initContact()
        await warmOrderSubscribeScenes()
        await fetchPreview()
        pageError.value = null
        initialized.value = true
    } catch (error: any) {
        const errorMsg =
            typeof error === 'string' ? error : error?.msg || error?.message || '订单确认信息加载失败'
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
        return item?.package?.name || item?.package_name || '已选择推荐套餐'
    }
    return item?.package?.description || item?.package_description || ''
}

onLoad((options: any) => {
    $theme.setScene('consumer')

    const normalized = normalizeBookingQuery({
        ...loadServiceRegionSelection(),
        ...options
    })
    selection.staff_id = normalized.staff_id
    selection.package_id = normalized.package_id
    selection.waitlist_id = normalized.waitlist_id
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
        void handlePreviewError('预约信息不完整，请重新选择服务地区和日期')
        return
    }

    void initPage()
})

onShow(() => {
    if (pageError.value) {
        return
    }

    if (initialized.value) {
        if (!userStore.isLogin) {
            return
        }

        void fetchPreview().catch((error: any) => {
            const errorMsg =
                typeof error === 'string'
                    ? error
                    : error?.msg || error?.message || '订单确认信息刷新失败'
            handlePreviewError(errorMsg)
        })
    }
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
        border-radius: var(--wm-radius-action-bar, 28rpx);
        background: linear-gradient(
            180deg,
            rgba(245, 241, 232, 0) 0%,
            rgba(255, 253, 248, 0.96) 28%,
            #fffdf8 100%
        );
    }

    &__submit-action {
        width: 242rpx;
        flex-shrink: 0;
    }
}

.loading-state,
.order-confirm-page__state-card {
    min-height: 56vh;
}

.order-confirm-page__state-card {
    flex-direction: column;
    gap: 18rpx;
}

.order-confirm-page__state-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16rpx;
}

.order-confirm-page__state-link {
    min-height: 64rpx;
    padding: 0 18rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #5f5a50);
}

.order-confirm-page__state-divider {
    width: 1rpx;
    height: 28rpx;
    background: var(--wm-color-border, #e7e2d6);
}

.section-card {
    border-radius: var(--wm-radius-card-lg, 28rpx) !important;
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
    font-weight: 900;
    color: var(--wm-text-primary, #111111);
}

.section-desc {
    display: block;
    margin-top: 8rpx;
    font-size: 24rpx;
    line-height: 1.45;
    color: var(--wm-text-secondary, #5f5a50);
}

.section-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    padding: 12rpx 20rpx;
    border-radius: 999rpx;
    background: var(--wm-color-primary-soft, #f3f2ee);
    border: 1rpx solid var(--wm-color-border-strong, #d8c28a);

    text {
        font-size: 22rpx;
        font-weight: 600;
        color: var(--wm-color-primary, #0b0b0b);
    }
}

.booking-grid {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    padding: 6rpx 0 0;
}

.booking-grid :deep(.base-info-row) {
    min-height: 76rpx;
    padding: 0 4rpx;
    justify-content: flex-start;
}

.booking-grid :deep(.base-info-row__label) {
    width: 132rpx;
}

.booking-grid :deep(.base-info-row__value-wrap) {
    justify-content: flex-start;
}

.booking-grid :deep(.base-info-row__value) {
    text-align: left;
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
    background: var(--wm-color-bg-card, #fffdf8);
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
}

.payment-arrangement__summary-card--accent {
    background: linear-gradient(180deg, rgba(241, 229, 200, 0.94) 0%, #fffdf8 100%);
    border-color: var(--wm-color-border-strong, #d8c28a);
}

.payment-arrangement__summary-label {
    display: block;
    font-size: 22rpx;
    letter-spacing: 0;
    text-transform: uppercase;
    color: var(--wm-text-tertiary, #9a9388);
}

.payment-arrangement__summary-value {
    display: block;
    margin-top: 10rpx;
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.45;
    color: var(--wm-text-primary, #111111);
    word-break: break-word;
}

.payment-arrangement__summary-value--amount {
    font-size: 38rpx;
    line-height: 1.25;
    color: var(--wm-color-price, var(--wm-color-primary, #0b0b0b));
}

.payment-arrangement__stage {
    padding: 24rpx 28rpx;
    border-radius: 32rpx;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.96) 0%, #ffffff 100%);
    border: 1rpx solid rgba(216, 194, 138, 0.92);
}

.payment-arrangement__stage-label {
    display: block;
    font-size: 22rpx;
    letter-spacing: 0;
    color: var(--wm-text-tertiary, #9a9388);
}

.payment-arrangement__stage-value {
    display: block;
    margin-top: 8rpx;
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1.55;
    color: var(--wm-text-primary, #111111);
    word-break: break-word;
}

.payment-arrangement__detail {
    padding: 4rpx 4rpx 0;
    display: flex;
    flex-direction: column;
    gap: 0;
}

.payment-arrangement__detail :deep(.base-info-row) {
    min-height: 72rpx;
    border-bottom: 1rpx solid rgba(231, 226, 214, 0.72);
}

.payment-arrangement__detail :deep(.base-info-row:last-child) {
    border-bottom: none;
}

.payment-arrangement__remark {
    padding: 24rpx 26rpx;
    border-radius: 30rpx;
    background: rgba(248, 247, 242, 0.96);
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
}

.payment-arrangement__remark-label {
    display: block;
    font-size: 22rpx;
    font-weight: 700;
    letter-spacing: 0;
    color: var(--wm-text-secondary, #5f5a50);
}

.payment-arrangement__remark-text {
    display: block;
    margin-top: 10rpx;
    font-size: 24rpx;
    line-height: 1.65;
    color: var(--wm-text-secondary, #5f5a50);
    word-break: break-word;
}

.field-item + .field-item {
    margin-top: 22rpx;
}

.field-item--note {
    padding-bottom: 24rpx;
}

.field-label {
    display: block;
    margin-bottom: 8rpx;
    font-size: 28rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #111111);
}

.field-label--required::before {
    content: '*';
    margin-right: 6rpx;
    color: var(--wm-color-primary, #0b0b0b);
}

.field-shell--textarea {
    padding: 28rpx;
    border-radius: var(--wm-radius-input, 44rpx);
    min-height: 188rpx;
    background: var(--wm-color-bg-soft, #faf6ee);
    border: 1rpx solid var(--wm-color-border, #d8c9ad);
    box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));
}

.remark-textarea {
    width: 100%;
    height: 128rpx;
    font-size: 28rpx;
    line-height: 1.6;
    color: var(--wm-color-price, var(--wm-color-primary, #0b0b0b));
}

.field-item :deep(.base-input__label) {
    color: var(--wm-text-primary, #191713);
}

.field-item :deep(.base-input__control) {
    min-height: 92rpx;
}

.service-main {
    display: flex;
    align-items: flex-start;
    gap: 18rpx;
    padding: 24rpx;
    border-radius: 34rpx;
    background: linear-gradient(145deg, #2b261d 0%, #191713 62%, #3a2a16 100%);
    border: 1rpx solid var(--wm-color-champagne, #d9be82);
    box-shadow: var(--wm-shadow-action, 0 20rpx 44rpx rgba(74, 43, 24, 0.18));
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
    background: rgba(255, 253, 248, 0.12);
    border: 1rpx solid var(--wm-color-border-strong, #d8c28a);
}

.service-main__avatar-image {
    width: 100%;
    height: 100%;
    display: block;
}

.service-main__avatar-text {
    font-size: 36rpx;
    font-weight: 700;
    color: var(--wm-color-price, var(--wm-color-primary, #0b0b0b));
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
    color: var(--wm-text-inverse, #fffdf8);
}

.service-main__meta {
    display: block;
    font-size: 24rpx;
    color: rgba(255, 253, 248, 0.68);
}

.service-main__tag {
    display: inline-flex;
    align-items: center;
    align-self: flex-start;
    padding: 8rpx 18rpx;
    border-radius: 999rpx;
    background: rgba(241, 229, 200, 0.16);
    border: 1rpx solid rgba(217, 190, 130, 0.5);

    text {
        font-size: 22rpx;
        font-weight: 700;
        color: var(--wm-color-champagne, #d9be82);
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
    color: rgba(255, 253, 248, 0.68);
}

.service-main__price-value {
    font-size: 32rpx;
    font-weight: 700;
    color: var(--wm-color-champagne, #d9be82);
}

.service-package-summary {
    display: block;
    margin-top: 18rpx;
    font-size: 26rpx;
    line-height: 1.55;
    color: var(--wm-text-primary, #111111);
}

.service-addon {
    margin-top: 18rpx;
    padding-top: 18rpx;
    border-top: 1rpx solid #f8f7f2;
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
    color: var(--wm-text-primary, #111111);
}

.service-addon__meta {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #5f5a50);
}

.service-addon__list {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.service-addon__card {
    padding: 24rpx 26rpx;
    border-radius: 32rpx;
    background: var(--wm-color-bg-card, #fffdf8);
    border: 1rpx solid var(--wm-color-border, #d8c9ad);
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
    color: var(--wm-text-primary, #111111);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.service-addon__price {
    font-size: 28rpx;
    font-weight: 700;
    color: var(--wm-color-secondary, #c8a45d);
}

.service-addon__desc {
    display: block;
    margin-top: 8rpx;
    font-size: 24rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #5f5a50);
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
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
    color: var(--wm-text-secondary, #5f5a50);
}

.submit-summary__amount {
    font-size: 44rpx;
    font-weight: 900;
    color: var(--wm-color-primary, #0b0b0b);
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
