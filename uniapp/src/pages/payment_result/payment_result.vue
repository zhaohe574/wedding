<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="支付结果" />
        <page-status :status="status">
            <template #error>
                <view class="wm-empty-shell">
                    <EmptyState
                        title="支付记录不存在"
                        description="当前支付结果无法找到对应订单，请返回首页重新查看。"
                        action-text="返回首页"
                        @action="goHome"
                    />
                </view>
            </template>
            <template #default>
                <view class="payment-result">
                    <view class="payment-result__body wm-page-content">
                        <view class="wm-page-stack payment-result__stack">
                            <BaseCard
                                variant="surface"
                                scene="consumer"
                                class="payment-result__status-card"
                            >
                                <view class="payment-result__status-top">
                                    <view
                                        class="payment-result__status-icon"
                                        :class="statusToneClass"
                                    >
                                        <text class="payment-result__status-icon-text">
                                            {{ statusMark }}
                                        </text>
                                    </view>
                                    <view class="payment-result__status-copy">
                                        <text class="payment-result__title">
                                            {{ presentation.title }}
                                        </text>
                                        <text class="payment-result__desc">
                                            {{ presentation.description }}
                                        </text>
                                    </view>
                                    <view
                                        class="payment-result__status-badge"
                                        :class="statusToneClass"
                                    >
                                        <text class="payment-result__status-badge-text">
                                            {{ presentation.badge }}
                                        </text>
                                    </view>
                                </view>
                                <view class="payment-result__amount-block">
                                    <text class="payment-result__amount-label">
                                        {{ presentation.amountLabel }}
                                    </text>
                                    <text class="payment-result__amount" :class="statusToneClass">
                                        ¥{{ displayAmount }}
                                    </text>
                                </view>
                                <view
                                    v-if="statusMetaItems.length"
                                    class="payment-result__status-meta"
                                >
                                    <view
                                        v-for="item in statusMetaItems"
                                        :key="`${item.label}-${item.value}`"
                                        class="payment-result__status-meta-item"
                                    >
                                        <text class="payment-result__status-meta-label">
                                            {{ item.label }}
                                        </text>
                                        <text class="payment-result__status-meta-value">
                                            {{ item.value }}
                                        </text>
                                    </view>
                                </view>
                            </BaseCard>

                            <BaseCard
                                variant="surface"
                                scene="consumer"
                                class="payment-result__card"
                            >
                                <view class="payment-result__section-head">
                                    <text class="payment-result__section-title">支付概览</text>
                                    <text class="payment-result__section-tag">
                                        {{ paymentModeText }}
                                    </text>
                                </view>
                                <view class="payment-result__summary-grid">
                                    <view
                                        v-for="item in summaryItems"
                                        :key="item.label"
                                        class="payment-result__summary-item"
                                    >
                                        <text class="payment-result__summary-label">
                                            {{ item.label }}
                                        </text>
                                        <text class="payment-result__summary-value">
                                            {{ item.value }}
                                        </text>
                                    </view>
                                </view>
                            </BaseCard>

                            <BaseCard
                                variant="surface"
                                scene="consumer"
                                class="payment-result__card"
                            >
                                <view class="payment-result__section-head">
                                    <text class="payment-result__section-title">订单信息</text>
                                    <text
                                        class="payment-result__section-tag payment-result__section-tag--soft"
                                    >
                                        {{ orderInfoTagText }}
                                    </text>
                                </view>
                                <view class="payment-result__info-list">
                                    <view
                                        v-for="item in orderInfoItems"
                                        :key="item.label"
                                        class="payment-result__info-item"
                                    >
                                        <text class="payment-result__info-label">
                                            {{ item.label }}
                                        </text>
                                        <text class="payment-result__info-value">
                                            {{ item.value }}
                                        </text>
                                    </view>
                                </view>
                                <view class="payment-result__notice">
                                    <text class="payment-result__notice-label">说明</text>
                                    <text class="payment-result__notice-text">
                                        {{ resultHintText }}
                                    </text>
                                </view>
                            </BaseCard>
                        </view>
                    </view>
                    <ActionArea class="payment-result__action-bar" sticky safeBottom>
                        <view v-if="secondaryActionLabel" class="payment-result__action-slot">
                            <BaseButton
                                block
                                size="lg"
                                variant="secondary"
                                @click="handleSecondaryAction"
                            >
                                {{ secondaryActionLabel }}
                            </BaseButton>
                        </view>
                        <view class="payment-result__action-slot">
                            <BaseButton
                                block
                                size="lg"
                                :loading="primaryActionLoading"
                                @click="handlePrimaryActionClick"
                            >
                                {{ primaryActionLoading ? '刷新中...' : primaryActionLabel }}
                            </BaseButton>
                        </view>
                    </ActionArea>
                </view>
            </template>
        </page-status>
    </PageShell>
</template>

<script lang="ts" setup>
import { getPayResult } from '@/api/pay'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import PageShell from '@/components/base/PageShell.vue'
import { PageStatusEnum } from '@/enums/appEnums'
import { onLoad, onShow, onUnload } from '@dcloudio/uni-app'
import { computed, ref } from 'vue'
import { useRouter } from 'uniapp-router-next'

type PaymentResultState = 'paid' | 'pending' | 'failed' | 'partial_refund' | 'full_refund'

interface PageOptions {
    id: string
    from: string
    payment_sn: string
}

interface ResultPresentation {
    badge: string
    title: string
    description: string
    amountLabel: string
}

interface PaymentResultMetaItem {
    label: string
    value: string
}

interface PaymentResultLineItem {
    label: string
    value: string
}

const router = useRouter()

const status = ref(PageStatusEnum.LOADING)
const pageOptions = ref<PageOptions>({
    id: '',
    from: '',
    payment_sn: ''
})
const payResult = ref<any>({
    order: {}
})
const isRefreshing = ref(false)
const hasShownOnce = ref(false)

let pollTimer: ReturnType<typeof setTimeout> | null = null
let pollAttempt = 0

const POLL_INTERVAL_STEPS = [2000, 4000, 8000, 15000, 15000, 30000]
const MAX_POLL_COUNT = POLL_INTERVAL_STEPS.length

const normalizedOrder = computed(() => payResult.value?.order || {})
const normalizedPayment = computed(() => payResult.value?.payment || {})
const isRechargeResult = computed(() => pageOptions.value.from === 'recharge')
const hasPaymentSn = computed(() => !!pageOptions.value.payment_sn)
const topLevelPayStatus = computed(() => Number(payResult.value?.pay_status ?? 0))
const orderStatus = computed(() => Number(normalizedOrder.value?.order_status ?? 0))
const paymentStatus = computed(() => Number(normalizedPayment.value?.pay_status ?? -1))
const paymentStatusText = computed(() =>
    String(normalizedPayment.value?.pay_status_desc || '').trim()
)
const normalizedStatusText = computed(() =>
    String(normalizedOrder.value?.pay_status_desc || normalizedOrder.value?.pay_status || '').trim()
)
const currentPaymentTypeText = computed(() => {
    if (isRechargeResult.value) return '充值'

    return String(normalizedPayment.value?.pay_type_desc || '').trim()
})
const paymentSubjectText = computed(() => {
    if (isRechargeResult.value) return '充值'
    if (currentPaymentTypeText.value) return `${currentPaymentTypeText.value}支付`
    return '支付'
})
const currentAmountLabel = computed(() => {
    if (isRechargeResult.value) return '本次充值金额'

    if (currentPaymentTypeText.value === '定金') return '本次定金金额'
    if (currentPaymentTypeText.value === '尾款') return '本次尾款金额'
    if (currentPaymentTypeText.value === '全款') return '本次全款金额'

    return '本次支付金额'
})
const isCurrentPaymentContext = computed(() => hasPaymentSn.value && !isRechargeResult.value)

const formatAmount = (value: string | number | undefined | null) => {
    const amount = Number(value || 0)
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00'
}

const resultState = computed<PaymentResultState>(() => {
    const payStatusText = paymentStatusText.value || normalizedStatusText.value

    if (payStatusText.includes('部分退款')) {
        return 'partial_refund'
    }

    if (payStatusText.includes('全额退款')) {
        return 'full_refund'
    }

    if (paymentStatus.value === 2 || payStatusText.includes('退款')) {
        return orderStatus.value === 8 ? 'full_refund' : 'partial_refund'
    }

    if (paymentStatus.value === 3 || payStatusText.includes('支付失败')) {
        return 'failed'
    }

    if (paymentStatus.value === 1) {
        return 'paid'
    }

    if (orderStatus.value === 6 && topLevelPayStatus.value !== 1) {
        return 'failed'
    }

    if (hasPaymentSn.value && paymentStatus.value === 0) {
        return 'pending'
    }

    if (
        topLevelPayStatus.value === 1 ||
        payStatusText.includes('已支付') ||
        payStatusText.includes('支付成功')
    ) {
        return 'paid'
    }

    return 'pending'
})

const isPendingResult = computed(() => resultState.value === 'pending')
const canAutoPoll = computed(() => isPendingResult.value && !!pageOptions.value.payment_sn)
const statusToneClass = computed(() => {
    const toneMap: Record<PaymentResultState, string> = {
        paid: 'is-success',
        pending: 'is-warning',
        failed: 'is-danger',
        partial_refund: 'is-warning',
        full_refund: 'is-info'
    }

    return toneMap[resultState.value]
})
const statusMark = computed(() => {
    const markMap: Record<PaymentResultState, string> = {
        paid: '成',
        pending: '待',
        failed: '败',
        partial_refund: '退',
        full_refund: '退'
    }

    return markMap[resultState.value]
})

const presentation = computed<ResultPresentation>(() => {
    const presentationMap: Record<PaymentResultState, ResultPresentation> = {
        paid: {
            badge: currentPaymentTypeText.value
                ? `${currentPaymentTypeText.value}已完成`
                : '款项已入账',
            title: `${paymentSubjectText.value}成功`,
            description: isRechargeResult.value ? '余额已到账' : '当前款项已确认',
            amountLabel: currentAmountLabel.value
        },
        pending: {
            badge: '结果确认中',
            title: `${paymentSubjectText.value}确认中`,
            description: canAutoPoll.value ? '正在同步当前结果' : '请手动刷新当前结果',
            amountLabel: currentAmountLabel.value
        },
        failed: {
            badge: orderStatus.value === 6 ? '支付已关闭' : '支付未完成',
            title:
                orderStatus.value === 6
                    ? `${paymentSubjectText.value}已关闭`
                    : `${paymentSubjectText.value}失败`,
            description: orderStatus.value === 6 ? '订单已自动关闭' : '可返回订单重新支付',
            amountLabel: currentAmountLabel.value
        },
        partial_refund: {
            badge: '部分退款',
            title: `${paymentSubjectText.value}已部分退款`,
            description: '退款进度以到账通知为准',
            amountLabel: currentAmountLabel.value
        },
        full_refund: {
            badge: '退款完成',
            title: `${paymentSubjectText.value}已退款`,
            description: '请留意原支付渠道到账',
            amountLabel: currentAmountLabel.value
        }
    }

    return presentationMap[resultState.value]
})

const displayAmount = computed(() => formatAmount(resolveDisplayAmount()))
const totalAmountText = computed(() =>
    formatAmount(
        normalizedOrder.value?.total_amount ||
            normalizedOrder.value?.pay_amount ||
            normalizedOrder.value?.order_amount ||
            0
    )
)
const paidAmountText = computed(() => formatAmount(normalizedOrder.value?.paid_amount || 0))
const unpaidAmountText = computed(() => formatAmount(normalizedOrder.value?.unpaid_amount || 0))
const payTimeText = computed(
    () =>
        String(normalizedPayment.value?.pay_time || normalizedOrder.value?.pay_time || '').trim() ||
        '-'
)
const currentPayStageText = computed(() => {
    if (currentPaymentTypeText.value && resultState.value === 'paid') {
        return `${currentPaymentTypeText.value}已支付`
    }

    const stageText = String(normalizedOrder.value?.current_pay_stage_desc || '').trim()
    if (stageText) return stageText

    const fallbackMap: Record<PaymentResultState, string> = {
        paid: '已完成支付',
        pending: '待确认',
        failed: orderStatus.value === 6 ? '订单已取消' : '等待重新支付',
        partial_refund: '部分退款',
        full_refund: '退款完成'
    }

    return fallbackMap[resultState.value]
})
const orderInfoTagText = computed(() => {
    if (currentPaymentTypeText.value) {
        return currentPaymentTypeText.value
    }

    return currentPayStageText.value
})
const paymentModeText = computed(
    () => String(normalizedOrder.value?.payment_mode_desc || '').trim() || '订单支付'
)
const payWayText = computed(() => {
    const payWay = String(
        normalizedPayment.value?.pay_way_desc || normalizedOrder.value?.pay_way || ''
    ).trim()
    if (payWay) return payWay
    return isPendingResult.value ? '待确认' : '-'
})

const resolveDisplayAmount = () => {
    if (isRechargeResult.value) {
        return (
            normalizedPayment.value?.pay_amount ||
            normalizedOrder.value?.order_amount ||
            normalizedOrder.value?.pay_amount ||
            0
        )
    }

    if (hasPaymentSn.value) {
        return (
            normalizedPayment.value?.pay_amount ||
            normalizedOrder.value?.order_amount ||
            normalizedOrder.value?.need_pay_amount ||
            normalizedOrder.value?.pay_amount ||
            0
        )
    }

    return (
        normalizedOrder.value?.need_pay_amount ||
        normalizedOrder.value?.order_amount ||
        normalizedOrder.value?.pay_amount ||
        0
    )
}

const statusMetaItems = computed<PaymentResultMetaItem[]>(() => {
    const items: PaymentResultMetaItem[] = []

    if (resultState.value === 'pending') {
        items.push({
            label: '同步状态',
            value: canAutoPoll.value ? '自动确认中' : '等待手动刷新'
        })
    }

    if (currentPaymentTypeText.value && !isRechargeResult.value) {
        items.push({
            label: '支付类型',
            value: currentPaymentTypeText.value
        })
    }

    if (payWayText.value !== '-' && items.length < 2) {
        items.push({
            label: '支付方式',
            value: payWayText.value
        })
    }

    if (payTimeText.value !== '-' && resultState.value !== 'pending' && items.length < 2) {
        items.push({
            label: resultState.value === 'paid' ? '到账时间' : '处理时间',
            value: payTimeText.value
        })
    } else if (normalizedOrder.value?.order_status_desc && items.length < 2) {
        items.push({
            label: '订单状态',
            value: String(normalizedOrder.value.order_status_desc)
        })
    }

    return items.slice(0, 2)
})

const summaryItems = computed<PaymentResultLineItem[]>(() => {
    const items: PaymentResultLineItem[] = [
        {
            label: '支付方式',
            value: payWayText.value
        },
        {
            label: isPendingResult.value ? '同步状态' : '当前状态',
            value: isPendingResult.value
                ? canAutoPoll.value
                    ? '系统确认中'
                    : '等待手动刷新'
                : currentPayStageText.value
        }
    ]

    if (!isRechargeResult.value) {
        items.unshift(
            {
                label: '订单总额',
                value: `¥${totalAmountText.value}`
            },
            {
                label: '已付金额',
                value: `¥${paidAmountText.value}`
            }
        )

        if (
            Number(unpaidAmountText.value) > 0 ||
            isPendingResult.value ||
            resultState.value === 'failed'
        ) {
            items.push({
                label: '待付金额',
                value: `¥${unpaidAmountText.value}`
            })
        }
    }

    if (payTimeText.value !== '-') {
        items.push({
            label: resultState.value === 'pending' ? '最近更新时间' : '支付时间',
            value: payTimeText.value
        })
    }

    return items.slice(0, isRechargeResult.value ? 3 : 5)
})

const orderInfoItems = computed<PaymentResultLineItem[]>(() => {
    const items: PaymentResultLineItem[] = [
        {
            label: '订单编号',
            value: String(normalizedOrder.value?.order_sn || '-')
        }
    ]

    if (normalizedPayment.value?.payment_sn) {
        items.push({
            label: '支付流水',
            value: String(normalizedPayment.value.payment_sn)
        })
    }

    items.push(
        {
            label: '支付方式',
            value: payWayText.value
        },
        {
            label: '付款时间',
            value: payTimeText.value
        },
        {
            label: isCurrentPaymentContext.value ? '支付状态' : '订单状态',
            value: String(
                isCurrentPaymentContext.value
                    ? paymentStatusText.value || normalizedOrder.value?.pay_status_desc || '-'
                    : normalizedOrder.value?.order_status_desc || '-'
            )
        }
    )

    return items
})
const resultHintText = computed(() => {
    const hintMap: Record<PaymentResultState, string> = {
        paid: isRechargeResult.value
            ? '充值金额已计入账户余额，可返回原页面继续使用。'
            : '如需继续查看服务安排或付款进度，可进入订单详情页。',
        pending: canAutoPoll.value
            ? '系统正在同步支付结果，如已扣款请稍候刷新。'
            : '若支付已成功但页面未更新，请稍后重新进入或手动刷新。',
        failed:
            orderStatus.value === 6
                ? '当前订单已关闭，如需继续请重新下单或联系商家处理。'
                : '如支付渠道已扣款但状态失败，请保留凭证并联系商家核实。',
        partial_refund: '退款金额将按原支付渠道退回，具体到账时间以渠道通知为准。',
        full_refund: '退款完成后会原路退回，请留意支付渠道的到账消息。'
    }

    return hintMap[resultState.value]
})

const primaryActionLabel = computed(() => (isPendingResult.value ? '刷新结果' : '返回首页'))
const primaryActionLoading = computed(() => isPendingResult.value && isRefreshing.value)
const secondaryActionLabel = computed(() => {
    if (pageOptions.value.from === 'recharge') return '继续充值'
    if (pageOptions.value.from === 'order') return '查看订单'
    return ''
})

const clearPollTimer = () => {
    if (pollTimer) {
        clearTimeout(pollTimer)
        pollTimer = null
    }
}

const scheduleNextPoll = () => {
    if (!canAutoPoll.value || pollAttempt >= MAX_POLL_COUNT) {
        clearPollTimer()
        return
    }

    clearPollTimer()
    pollAttempt += 1
    const lastInterval = POLL_INTERVAL_STEPS[POLL_INTERVAL_STEPS.length - 1] || 2000
    const interval = POLL_INTERVAL_STEPS[Math.max(pollAttempt - 1, 0)] || lastInterval
    pollTimer = setTimeout(() => {
        fetchPayResult({
            silent: true,
            keepPolling: true
        })
    }, interval)
}

const fetchPayResult = async ({
    silent = false,
    keepPolling = false
}: {
    silent?: boolean
    keepPolling?: boolean
} = {}) => {
    if (!pageOptions.value.id) return

    clearPollTimer()
    const previousStatus = status.value
    if (!silent) {
        status.value = PageStatusEnum.LOADING
    }

    isRefreshing.value = true

    try {
        const data = await getPayResult({
            order_id: pageOptions.value.id,
            from: pageOptions.value.from,
            payment_sn: pageOptions.value.payment_sn || ''
        })

        payResult.value = data || { order: {} }
        status.value = PageStatusEnum.NORMAL

        if (keepPolling && canAutoPoll.value) {
            scheduleNextPoll()
            return
        }

        pollAttempt = 0
        if (canAutoPoll.value) {
            scheduleNextPoll()
        }
    } catch (error) {
        console.log(error)
        if (!silent || previousStatus !== PageStatusEnum.NORMAL) {
            status.value = PageStatusEnum.ERROR
        }
        clearPollTimer()
    } finally {
        isRefreshing.value = false
    }
}

const goHome = () => {
    router.reLaunch('/pages/index/index')
}

const goSourcePage = () => {
    if (pageOptions.value.from === 'recharge') {
        router.navigateBack()
        return
    }

    if (pageOptions.value.from === 'order') {
        const paymentSn = pageOptions.value.payment_sn
            ? `&payment_sn=${pageOptions.value.payment_sn}`
            : ''
        router.redirectTo(`/pages/order_detail/order_detail?id=${pageOptions.value.id}${paymentSn}`)
    }
}

const handlePrimaryAction = () => {
    if (isPendingResult.value) {
        pollAttempt = 0
        fetchPayResult({
            silent: true,
            keepPolling: canAutoPoll.value
        })
        return
    }

    goHome()
}

const handleSecondaryAction = () => {
    if (!secondaryActionLabel.value) {
        goHome()
        return
    }

    goSourcePage()
}

const handlePrimaryActionClick = () => {
    if (primaryActionLoading.value) return
    handlePrimaryAction()
}

onLoad(async (options: Record<string, string>) => {
    pageOptions.value = {
        id: String(options?.id || ''),
        from: String(options?.from || ''),
        payment_sn: String(options?.payment_sn || '')
    }

    if (!pageOptions.value.id) {
        status.value = PageStatusEnum.ERROR
        return
    }

    await fetchPayResult()
})

onShow(() => {
    if (!hasShownOnce.value) {
        hasShownOnce.value = true
        return
    }

    if (pageOptions.value.id) {
        pollAttempt = 0
        fetchPayResult({
            silent: true,
            keepPolling: canAutoPoll.value
        })
    }
})

onUnload(() => {
    clearPollTimer()
})
</script>

<style lang="scss" scoped>
.payment-result {
    min-height: 100vh;
}

.payment-result__body {
    padding-top: 16rpx;
    padding-bottom: calc(env(safe-area-inset-bottom) + 224rpx);
}

.payment-result__stack {
    gap: 18rpx;
}

.payment-result__status-card,
.payment-result__card {
    padding: 30rpx;
    border-radius: 40rpx;
    background: #ffffff;
    border-color: rgba(239, 230, 225, 0.96);
    box-shadow: 0 14rpx 28rpx rgba(214, 185, 167, 0.1);
}

.payment-result__status-card {
    display: flex;
    flex-direction: column;
    gap: 22rpx;
}

.payment-result__status-top {
    display: flex;
    align-items: flex-start;
    gap: 18rpx;
}

.payment-result__status-icon {
    flex-shrink: 0;
    width: 76rpx;
    height: 76rpx;
    border-radius: 24rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(232, 90, 79, 0.1);
}

.payment-result__status-icon.is-success {
    background: rgba(47, 125, 88, 0.12);
    color: var(--wm-color-success, #2f7d58);
}

.payment-result__status-icon.is-warning {
    background: rgba(201, 133, 36, 0.12);
    color: var(--wm-color-warning, #c98524);
}

.payment-result__status-icon.is-danger {
    background: rgba(180, 74, 58, 0.12);
    color: var(--wm-color-danger, #b44a3a);
}

.payment-result__status-icon.is-info {
    background: rgba(74, 118, 180, 0.12);
    color: #4a76b4;
}

.payment-result__status-icon-text {
    font-size: 28rpx;
    font-weight: 700;
}

.payment-result__status-copy {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.payment-result__status-badge {
    flex-shrink: 0;
    min-height: 52rpx;
    padding: 0 20rpx;
    border-radius: 999rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1rpx solid transparent;
    box-sizing: border-box;
}

.payment-result__status-badge.is-success {
    background: rgba(47, 125, 88, 0.1);
    border-color: rgba(47, 125, 88, 0.18);
    color: var(--wm-color-success, #2f7d58);
}

.payment-result__status-badge.is-warning {
    background: rgba(201, 133, 36, 0.1);
    border-color: rgba(201, 133, 36, 0.18);
    color: var(--wm-color-warning, #c98524);
}

.payment-result__status-badge.is-danger {
    background: rgba(180, 74, 58, 0.1);
    border-color: rgba(180, 74, 58, 0.18);
    color: var(--wm-color-danger, #b44a3a);
}

.payment-result__status-badge.is-info {
    background: rgba(74, 118, 180, 0.1);
    border-color: rgba(74, 118, 180, 0.18);
    color: #4a76b4;
}

.payment-result__status-badge-text {
    font-size: 22rpx;
    font-weight: 700;
    line-height: 1;
}

.payment-result__amount-block {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
    padding: 26rpx 28rpx;
    border-radius: 32rpx;
    background: rgba(255, 248, 245, 0.86);
}

.payment-result__title {
    font-size: 38rpx;
    font-weight: 700;
    line-height: 1.32;
    color: var(--wm-text-primary, #1e2432);
}

.payment-result__amount-label {
    font-size: 22rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);
}

.payment-result__amount {
    font-size: 56rpx;
    font-weight: 700;
    line-height: 1.15;
}

.payment-result__amount.is-success {
    color: var(--wm-color-primary, #e85a4f);
}

.payment-result__amount.is-warning {
    color: var(--wm-color-warning, #c98524);
}

.payment-result__amount.is-danger {
    color: var(--wm-color-danger, #b44a3a);
}

.payment-result__amount.is-info {
    color: #4a76b4;
}

.payment-result__desc {
    font-size: 24rpx;
    line-height: 1.55;
    color: var(--wm-text-secondary, #7f7b78);
}

.payment-result__status-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.payment-result__status-meta-item {
    min-height: 58rpx;
    padding: 0 22rpx;
    border-radius: 999rpx;
    display: inline-flex;
    align-items: center;
    gap: 10rpx;
    background: rgba(255, 248, 245, 0.92);
    border: 1rpx solid rgba(239, 230, 225, 0.96);
    box-sizing: border-box;
}

.payment-result__status-meta-label {
    font-size: 22rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.payment-result__status-meta-value {
    font-size: 24rpx;
    font-weight: 700;
    color: var(--wm-color-primary, #e85a4f);
}

.payment-result__card {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.payment-result__section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18rpx;
}

.payment-result__section-title {
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1.4;
    color: var(--wm-text-primary, #1e2432);
}

.payment-result__section-tag {
    flex-shrink: 0;
    max-width: 62%;
    min-height: 48rpx;
    padding: 0 18rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1;
    text-align: right;
    color: var(--wm-color-primary, #e85a4f);
    background: var(--wm-color-primary-soft, #fff1ee);
    border: 1rpx solid var(--wm-color-border-strong, #f4c7bf);
    box-sizing: border-box;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.payment-result__section-tag--soft {
    color: var(--wm-text-secondary, #7f7b78);
    background: rgba(255, 255, 255, 0.84);
    border-color: var(--wm-color-border, #efe6e1);
}

.payment-result__summary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14rpx;
}

.payment-result__summary-item {
    min-height: 124rpx;
    padding: 22rpx 20rpx;
    border-radius: 30rpx;
    background: rgba(255, 248, 245, 0.82);
    border: 1rpx solid rgba(239, 230, 225, 0.96);
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    justify-content: space-between;
}

.payment-result__summary-label {
    font-size: 22rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.payment-result__summary-value {
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1.45;
    color: var(--wm-text-primary, #1e2432);
    word-break: break-all;
}

.payment-result__info-list {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.payment-result__info-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24rpx;
    padding: 18rpx 0;
}

.payment-result__info-item + .payment-result__info-item {
    border-top: 1rpx solid rgba(239, 230, 225, 0.82);
}

.payment-result__info-label {
    flex-shrink: 0;
    font-size: 24rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #7f7b78);
}

.payment-result__info-value {
    flex: 1;
    min-width: 0;
    text-align: right;
    font-size: 26rpx;
    line-height: 1.6;
    color: var(--wm-text-primary, #1e2432);
    word-break: break-all;
}

.payment-result__notice {
    padding: 22rpx 24rpx;
    border-radius: 28rpx;
    background: rgba(255, 248, 245, 0.8);
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.payment-result__notice-label {
    font-size: 22rpx;
    font-weight: 700;
    color: var(--wm-text-secondary, #7f7b78);
}

.payment-result__notice-text {
    font-size: 24rpx;
    line-height: 1.6;
    color: var(--wm-text-primary, #1e2432);
}

.payment-result__action-bar {
    z-index: 80;
}

.payment-result__action-slot {
    flex: 1;
    min-width: 0;
}

@media screen and (max-width: 380px) {
    .payment-result__status-top {
        flex-wrap: wrap;
    }

    .payment-result__status-badge {
        margin-left: 94rpx;
    }

    .payment-result__summary-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .payment-result__action-bar {
        flex-direction: column;
    }
}
</style>
