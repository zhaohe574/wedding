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
                                variant="hero"
                                scene="consumer"
                                class="payment-result__hero-card"
                            >
                                <view
                                    class="payment-result__chip"
                                    :style="{ background: presentation.chipBackground }"
                                >
                                    <text
                                        class="payment-result__chip-text"
                                        :style="{ color: presentation.chipTextColor }"
                                    >
                                        {{ presentation.badge }}
                                    </text>
                                </view>
                                <view class="payment-result__hero-main">
                                    <text class="payment-result__title">
                                        {{ presentation.title }}
                                    </text>
                                    <text class="payment-result__amount-label">
                                        {{ presentation.amountLabel }}
                                    </text>
                                    <text
                                        class="payment-result__amount"
                                        :style="{ color: presentation.amountColor }"
                                    >
                                        ¥{{ displayAmount }}
                                    </text>
                                    <text class="payment-result__desc">
                                        {{ presentation.description }}
                                    </text>
                                </view>
                                <view v-if="heroMetaItems.length" class="payment-result__meta">
                                    <view
                                        v-for="item in heroMetaItems"
                                        :key="`${item.label}-${item.value}`"
                                        class="payment-result__meta-item"
                                    >
                                        <text class="payment-result__meta-label">
                                            {{ item.label }}
                                        </text>
                                        <text class="payment-result__meta-value">
                                            {{ item.value }}
                                        </text>
                                    </view>
                                </view>
                            </BaseCard>

                            <BaseCard variant="glass" scene="consumer" class="payment-result__card">
                                <view class="payment-result__section-head">
                                    <text class="payment-result__section-title">金额概览</text>
                                    <text class="payment-result__section-tag">
                                        {{ paymentModeText }}
                                    </text>
                                </view>
                                <view class="payment-result__amount-grid">
                                    <view
                                        v-for="item in amountOverviewItems"
                                        :key="item.label"
                                        class="payment-result__amount-item"
                                        :class="{
                                            'payment-result__amount-item--featured': item.featured,
                                            'payment-result__amount-item--accent': item.accent
                                        }"
                                    >
                                        <text class="payment-result__amount-item-label">
                                            {{ item.label }}
                                        </text>
                                        <text class="payment-result__amount-item-value">
                                            {{ item.value }}
                                        </text>
                                    </view>
                                </view>
                            </BaseCard>

                            <BaseCard variant="glass" scene="consumer" class="payment-result__card">
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
                            </BaseCard>
                        </view>
                    </view>
                    <ActionArea sticky safeBottom>
                        <view class="wm-page-actions-bar payment-result__actions-bar">
                            <BaseButton
                                v-if="secondaryActionLabel"
                                class="payment-result__action-btn"
                                variant="secondary"
                                size="lg"
                                block
                                @click="handleSecondaryAction"
                            >
                                {{ secondaryActionLabel }}
                            </BaseButton>
                            <BaseButton
                                class="payment-result__action-btn"
                                variant="primary"
                                size="lg"
                                block
                                text-color="#FFFFFF"
                                :loading="primaryActionLoading"
                                @click="handlePrimaryAction"
                            >
                                {{ primaryActionLabel }}
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
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
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
    chipBackground: string
    chipTextColor: string
    amountColor: string
}

interface PaymentResultMetaItem {
    label: string
    value: string
}

interface PaymentResultAmountItem {
    label: string
    value: string
    featured?: boolean
    accent?: boolean
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

const POLL_INTERVAL = 2000
const MAX_POLL_COUNT = 3

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

const presentation = computed<ResultPresentation>(() => {
    const presentationMap: Record<PaymentResultState, ResultPresentation> = {
        paid: {
            badge: currentPaymentTypeText.value
                ? `${currentPaymentTypeText.value}已完成`
                : '款项已入账',
            title: `${paymentSubjectText.value}成功`,
            description: isRechargeResult.value ? '余额已到账' : '当前款项已确认',
            amountLabel: currentAmountLabel.value,
            chipBackground: 'rgba(47, 125, 88, 0.12)',
            chipTextColor: 'var(--wm-color-success, #2F7D58)',
            amountColor: 'var(--wm-color-primary, #E85A4F)'
        },
        pending: {
            badge: '结果确认中',
            title: `${paymentSubjectText.value}确认中`,
            description: canAutoPoll.value ? '正在同步当前结果' : '请手动刷新当前结果',
            amountLabel: currentAmountLabel.value,
            chipBackground: 'rgba(201, 133, 36, 0.12)',
            chipTextColor: 'var(--wm-color-warning, #C98524)',
            amountColor: 'var(--wm-color-primary, #E85A4F)'
        },
        failed: {
            badge: orderStatus.value === 6 ? '支付已关闭' : '支付未完成',
            title:
                orderStatus.value === 6
                    ? `${paymentSubjectText.value}已关闭`
                    : `${paymentSubjectText.value}失败`,
            description: orderStatus.value === 6 ? '订单已自动关闭' : '可返回订单重新支付',
            amountLabel: currentAmountLabel.value,
            chipBackground: 'rgba(180, 74, 58, 0.12)',
            chipTextColor: 'var(--wm-color-danger, #B44A3A)',
            amountColor: 'var(--wm-color-danger, #B44A3A)'
        },
        partial_refund: {
            badge: '部分退款',
            title: `${paymentSubjectText.value}已部分退款`,
            description: '退款进度以到账通知为准',
            amountLabel: currentAmountLabel.value,
            chipBackground: 'rgba(201, 133, 36, 0.12)',
            chipTextColor: 'var(--wm-color-warning, #C98524)',
            amountColor: 'var(--wm-color-warning, #C98524)'
        },
        full_refund: {
            badge: '退款完成',
            title: `${paymentSubjectText.value}已退款`,
            description: '请留意原支付渠道到账',
            amountLabel: currentAmountLabel.value,
            chipBackground: 'rgba(74, 118, 180, 0.12)',
            chipTextColor: '#4A76B4',
            amountColor: '#4A76B4'
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

const heroMetaItems = computed<PaymentResultMetaItem[]>(() => {
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

const amountOverviewItems = computed<PaymentResultAmountItem[]>(() => {
    const items: PaymentResultAmountItem[] = [
        {
            label: presentation.value.amountLabel,
            value: `¥${displayAmount.value}`,
            featured: true,
            accent: true
        }
    ]

    if (isRechargeResult.value) {
        return items
    }

    items.push(
        {
            label: '订单总额',
            value: `¥${totalAmountText.value}`
        },
        {
            label: '已付金额',
            value: `¥${paidAmountText.value}`
        },
        {
            label: '待付金额',
            value: `¥${unpaidAmountText.value}`
        }
    )

    return items
})

const orderInfoItems = computed(() => {
    const items = [
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
    pollTimer = setTimeout(() => {
        fetchPayResult({
            silent: true,
            keepPolling: true
        })
    }, POLL_INTERVAL)
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
    background: var(--wm-color-page, #fcfbf9);
}

.payment-result__body {
    padding-top: 20rpx;
    padding-bottom: calc(env(safe-area-inset-bottom) + 248rpx);
}

.payment-result__stack {
    gap: 22rpx;
}

.payment-result__hero-card {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
    align-items: center;
    text-align: center;
}

.payment-result__chip {
    align-self: center;
    min-height: 48rpx;
    padding: 0 24rpx;
    border-radius: 999rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.payment-result__chip-text {
    font-size: 24rpx;
    font-weight: 700;
    line-height: 1;
}

.payment-result__hero-main {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 10rpx;
    align-items: center;
}

.payment-result__title {
    font-size: 44rpx;
    font-weight: 700;
    line-height: 1.28;
    color: var(--wm-text-primary, #1e2432);
}

.payment-result__amount-label {
    font-size: 22rpx;
    font-weight: 600;
    letter-spacing: 0.08em;
    color: var(--wm-text-secondary, #7f7b78);
}

.payment-result__amount {
    font-size: 58rpx;
    font-weight: 700;
    line-height: 1.15;
}

.payment-result__desc {
    font-size: 24rpx;
    font-weight: 500;
    line-height: 1.55;
    color: var(--wm-text-secondary, #7f7b78);
}

.payment-result__meta {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 14rpx;
}

.payment-result__meta-item {
    min-height: 52rpx;
    padding: 0 24rpx;
    border-radius: 999rpx;
    display: inline-flex;
    align-items: center;
    gap: 10rpx;
    background: rgba(255, 255, 255, 0.76);
    border: 1rpx solid rgba(244, 199, 191, 0.88);
    box-sizing: border-box;
}

.payment-result__meta-label {
    font-size: 22rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.payment-result__meta-value {
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
    padding: 8rpx 18rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1.4;
    text-align: right;
    color: var(--wm-color-primary, #e85a4f);
    background: var(--wm-color-primary-soft, #fff1ee);
    border: 1rpx solid var(--wm-color-border-strong, #f4c7bf);
    box-sizing: border-box;
}

.payment-result__section-tag--soft {
    color: var(--wm-text-secondary, #7f7b78);
    background: rgba(255, 255, 255, 0.84);
    border-color: var(--wm-color-border, #efe6e1);
}

.payment-result__amount-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16rpx;
}

.payment-result__amount-item {
    padding: 22rpx 20rpx;
    border-radius: 34rpx;
    background: linear-gradient(180deg, rgba(255, 250, 247, 0.96) 0%, #ffffff 100%);
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.payment-result__amount-item--featured {
    grid-column: 1 / -1;
    padding: 28rpx 26rpx;
}

.payment-result__amount-item--accent {
    background: linear-gradient(180deg, rgba(255, 244, 240, 0.98) 0%, #fffaf7 100%);
    border-color: var(--wm-color-border-strong, #f4c7bf);
}

.payment-result__amount-item-label {
    font-size: 22rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.payment-result__amount-item-value {
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.35;
    color: var(--wm-text-primary, #1e2432);
}

.payment-result__amount-item--featured .payment-result__amount-item-value {
    font-size: 48rpx;
    line-height: 1.16;
    color: var(--wm-color-primary, #e85a4f);
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

.payment-result__actions-bar {
    width: 100%;
}

.payment-result__action-btn {
    width: 100%;
}

@media screen and (max-width: 380px) {
    .payment-result__amount-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .payment-result__amount-item--featured {
        grid-column: auto;
    }

    .payment-result__actions-bar {
        flex-direction: column;
    }
}
</style>
