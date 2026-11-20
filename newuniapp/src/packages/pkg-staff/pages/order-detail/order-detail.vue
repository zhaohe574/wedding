<template>
    <page-shell title="服务订单详情" bg="page" :show-back="true">
        <view class="page-wrap detail">
            <view v-if="phase === 'loading' || phase === 'idle'">
                <base-skeleton :rows="4" />
            </view>

            <base-empty
                v-else-if="phase === 'error'"
                status="error"
                :error-kind="view?.errorKind || 'unknown'"
                :error-text="view?.title"
                :desc="view?.desc"
                :primary-action="view?.primaryAction"
                fixed
                @primary="onPrimary"
                @retry="load"
            />

            <template v-else>
                <view class="hero">
                    <view class="hero__top">
                        <text class="hero__eyebrow">SERVICE ORDER</text>
                        <status-tag :label="statusLabel" :tone="statusTone" size="sm" />
                    </view>
                    <text class="hero__sn">{{ order.order_sn || '服务订单' }}</text>
                    <view class="hero__facts">
                        <view class="hero-fact">
                            <text class="hero-fact__label">服务日期</text>
                            <text class="hero-fact__value">{{ serviceDateSummary }}</text>
                        </view>
                        <view class="hero-fact">
                            <text class="hero-fact__label">我的服务项</text>
                            <text class="hero-fact__value">{{ serviceItems.length }} 项</text>
                        </view>
                    </view>
                </view>

                <view v-if="timeoutText" class="timeout">
                    <tn-icon name="clock" :size="26" class="timeout__icon" />
                    <text class="timeout__text">{{ timeoutText }}</text>
                </view>

                <section-header title="服务明细" :subtitle="`${serviceItems.length} 个服务项`" />
                <base-card compact>
                    <view v-if="serviceItems.length" class="services">
                        <view
                            v-for="(item, index) in serviceItems"
                            :key="item.id"
                            class="service"
                            :class="{ 'service--divided': index > 0 }"
                        >
                            <view class="service__head">
                                <text class="service__title ellipsis-2">{{ item.title }}</text>
                                <status-tag
                                    :label="item.statusText"
                                    :tone="item.statusTone"
                                    size="sm"
                                />
                            </view>
                            <text v-if="item.desc" class="service__desc ellipsis-2">{{
                                item.desc
                            }}</text>
                            <view class="service__meta">
                                <text>{{ item.dateText }}</text>
                                <text>{{ item.quantityText }}</text>
                            </view>
                            <view class="service__amount-row">
                                <text class="service__amount-label">服务小计</text>
                                <text class="service__amount">{{ item.amountText }}</text>
                            </view>
                            <view v-if="item.addons.length" class="addons">
                                <text class="addons__title">附加服务</text>
                                <view
                                    v-for="addon in item.addons"
                                    :key="addon.id"
                                    class="addons__row"
                                >
                                    <text class="addons__name ellipsis">{{ addon.name }}</text>
                                    <text class="addons__quantity">× {{ addon.quantity }}</text>
                                    <text class="addons__amount">{{ addon.amountText }}</text>
                                </view>
                            </view>
                        </view>
                    </view>
                    <base-empty
                        v-else
                        status="empty"
                        empty-text="暂无可展示的服务项"
                        desc="当前订单没有指派给你的服务内容"
                    />
                </base-card>

                <section-header title="费用信息" :subtitle="order.current_pay_stage_desc || ''" />
                <base-card compact>
                    <view
                        v-for="(row, index) in amountRows"
                        :key="row.key"
                        class="row"
                        :class="{ 'row--divided': index > 0 }"
                    >
                        <text class="row__label">{{ row.label }}</text>
                        <text
                            class="row__value row__value--money"
                            :class="{ 'row__value--strong': row.strong }"
                            >{{ money(row.value) }}</text
                        >
                    </view>
                    <view class="payment-state">
                        <text class="payment-state__label">支付状态</text>
                        <text class="payment-state__value">{{
                            order.pay_status_desc || '待确认'
                        }}</text>
                    </view>
                </base-card>

                <section-header title="履约联系" subtitle="仅用于查看服务安排" />
                <base-card compact>
                    <view
                        v-for="(row, index) in contactRows"
                        :key="row.key"
                        class="row"
                        :class="{ 'row--divided': index > 0 }"
                    >
                        <text class="row__label">{{ row.label }}</text>
                        <text class="row__value">{{ row.value || '未填写' }}</text>
                    </view>
                    <view class="address">
                        <text class="address__label">服务地址</text>
                        <text class="address__value">{{
                            order.service_address || '未填写'
                        }}</text>
                    </view>
                </base-card>

                <section-header title="订单信息" />
                <base-card compact>
                    <view class="row">
                        <text class="row__label">订单编号</text>
                        <view class="order-sn">
                            <text class="order-sn__value">{{ order.order_sn || '—' }}</text>
                            <text
                                v-if="order.order_sn"
                                class="order-sn__copy"
                                hover-class="is-pressed"
                                aria-role="button"
                                aria-label="复制订单编号"
                                @tap="copyOrderSn"
                                >复制</text
                            >
                        </view>
                    </view>
                    <view
                        v-for="row in orderInfoRows"
                        :key="row.key"
                        class="row row--divided"
                    >
                        <text class="row__label">{{ row.label }}</text>
                        <text class="row__value">{{ row.value || '—' }}</text>
                    </view>
                </base-card>

                <view class="readonly-note">
                    <text class="readonly-note__text">
                        本页为服务人员只读视图，接单、履约和收款操作请在后台完成
                    </text>
                </view>
            </template>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 服务人员订单详情（只读）。
 *
 * 使用 /staff_center/orderDetail，服务端按 staff_id 过滤服务项并鉴权。
 * 不复用用户订单详情，避免误入下单人校验及支付、取消等客户动作。
 */
import { computed, ref } from 'vue'
import { onLoad, onPullDownRefresh } from '@dcloudio/uni-app'
import {
    ORDER_STATUS_NAME_MAP,
    ORDER_STATUS_TONE,
    resolveStatusLabel,
    resolveTone
} from '@shared/contracts'
import { formatMoney } from '@shared/utils/format'
import { getStaffOrderDetail } from '@/api/staff'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { toast } from '@/composables/useToast'
import type { LoadPhase } from '@/api/types'

const phase = ref<LoadPhase>('idle')
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
const orderId = ref(0)
const order = ref<Record<string, any>>({})
const loading = ref(false)

function toNumber(value: unknown) {
    const n = Number(value ?? 0)
    return Number.isFinite(n) ? n : 0
}

function money(value: unknown) {
    const n = Number(value ?? 0)
    if (!Number.isFinite(n)) return '—'
    return formatMoney(n, {
        symbol: '¥',
        thousand: true,
        hideZeroDecimals: true
    })
}

function maskMobile(value: unknown) {
    const mobile = String(value || '')
    return mobile.replace(/^(\d{3})\d{4}(\d{4})$/, '$1****$2')
}

const statusLabel = computed(() => {
    const mapped = resolveStatusLabel(
        ORDER_STATUS_NAME_MAP as any,
        Number(order.value.order_status)
    )
    return String(order.value.order_status_desc || mapped.label)
})

const statusTone = computed(() =>
    resolveTone(ORDER_STATUS_TONE as any, Number(order.value.order_status))
)

const serviceItems = computed(() => {
    const items = Array.isArray(order.value.items) ? order.value.items : []
    return items.map((item: any) => {
        const confirmed = Number(item.confirm_status) === 1
        const addons = Array.isArray(item.addons) ? item.addons : []
        return {
            id: Number(item.id),
            title: String(item.package_name || item.item_type_desc || '服务项'),
            desc: String(item.package_description || ''),
            dateText: item.service_date ? `服务日期 ${item.service_date}` : '服务日期待安排',
            quantityText: `数量 ${Math.max(1, toNumber(item.quantity) || 1)}`,
            amountText: money(item.subtotal ?? item.price),
            statusText: confirmed ? '已确认' : '待确认',
            statusTone: confirmed ? ('success' as const) : ('warning' as const),
            addons: addons.map((addon: any) => ({
                id: Number(addon.id),
                name: String(addon.addon_name || '附加服务'),
                quantity: Math.max(1, toNumber(addon.quantity) || 1),
                amountText: money(addon.subtotal ?? addon.price)
            }))
        }
    })
})

const serviceDateSummary = computed(() => {
    const dates = Array.from(
        new Set(
            serviceItems.value
                .map((item) => item.dateText.replace('服务日期 ', ''))
                .filter((text) => text && text !== '服务日期待安排')
        )
    )
    if (!dates.length) return '待安排'
    if (dates.length === 1) return dates[0]
    return `${dates[0]} 等 ${dates.length} 个日期`
})

const timeoutText = computed(() => {
    if (Number(order.value.order_status) === 0 && order.value.confirm_timeout_action_desc) {
        return `请及时在后台处理待确认订单，${order.value.confirm_timeout_action_desc}`
    }
    if (Number(order.value.order_status) === 1 && order.value.pay_timeout_action_desc) {
        return `客户支付时限：${order.value.pay_timeout_action_desc}`
    }
    return ''
})

const amountRows = computed(() => [
    { key: 'service', label: '服务金额', value: order.value.service_amount },
    { key: 'addon', label: '附加项金额', value: order.value.addon_amount },
    { key: 'discount', label: '优惠金额', value: order.value.discount_amount },
    { key: 'pay', label: '应付金额', value: order.value.pay_amount, strong: true },
    { key: 'paid', label: '已支付', value: order.value.paid_amount },
    { key: 'unpaid', label: '待支付', value: order.value.unpaid_amount }
])

const contactRows = computed(() => [
    { key: 'contact', label: '联系人', value: order.value.contact_name || '' },
    { key: 'mobile', label: '联系电话', value: maskMobile(order.value.contact_mobile) },
    { key: 'wedding', label: '婚期', value: order.value.wedding_date || '' }
])

const orderInfoRows = computed(() => [
    { key: 'source', label: '订单来源', value: order.value.source_desc || '' },
    { key: 'payType', label: '支付方式', value: order.value.pay_type_desc || '' },
    { key: 'created', label: '创建时间', value: order.value.create_time || '' },
    { key: 'remark', label: '订单备注', value: order.value.remark || '' }
])

function copyOrderSn() {
    uni.setClipboardData({
        data: String(order.value.order_sn || ''),
        success: () => toast.success('订单编号已复制')
    })
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        load()
        return
    }
    if (kind === 'reselect' || kind === 'back') {
        uni.navigateBack()
        return
    }
    if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
    }
}

async function load() {
    if (loading.value || !orderId.value) return
    loading.value = true
    if (!order.value.id) phase.value = 'loading'
    rawError.value = null
    try {
        const res = (await getStaffOrderDetail({ id: orderId.value })) || {}
        if (!res.id) throw new Error('empty-staff-order')
        order.value = res
        phase.value = 'ready'
    } catch (e) {
        rawError.value = e
        if (order.value.id) {
            phase.value = 'ready'
            toast.error('刷新失败，请稍后重试')
        } else {
            phase.value = 'error'
        }
    } finally {
        loading.value = false
    }
}

onLoad((options: any) => {
    orderId.value = Number(options?.id || options?.order_id || 0)
    if (!orderId.value) {
        rawError.value = new Error('missing-order-id')
        phase.value = 'error'
        return
    }
    void load()
})

onPullDownRefresh(async () => {
    try {
        await load()
    } finally {
        uni.stopPullDownRefresh()
    }
})
</script>

<style lang="scss" scoped>
.detail {
    box-sizing: border-box;
    padding: var(--sp-2) var(--page-gutter) calc(var(--sp-5) + var(--safe-bottom));
}

.hero {
    padding: var(--sp-4);
    border: 1rpx solid var(--c-brand-text);
    border-radius: var(--r-lg);
    background: linear-gradient(145deg, var(--c-dark-1), var(--c-dark-2));

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-3);
    }

    &__eyebrow {
        font-family: var(--font-serif);
        font-size: var(--fs-xs);
        letter-spacing: 0.16em;
        color: var(--c-brand-soft);
    }

    &__sn {
        display: block;
        margin-top: var(--sp-3);
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-on-dark);
        overflow-wrap: anywhere;
    }

    &__facts {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: var(--sp-2);
        margin-top: var(--sp-4);
    }
}

.hero-fact {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
        padding: var(--sp-3);
        border-radius: var(--r-md);
        background: var(--c-dark-3);

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
    }

    &__value {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-body);
        color: var(--c-on-dark);
        overflow-wrap: anywhere;
    }
}

.timeout {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-2);
    margin-top: var(--sp-2);
    padding: var(--sp-3);
    border: 1rpx solid var(--c-warning-border);
    border-radius: var(--r-md);
    background: var(--c-warning-bg);

    &__icon {
        flex-shrink: 0;
        margin-top: var(--sp-1);
        color: var(--c-warning);
    }

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
    }
}

.service {
    &--divided {
        margin-top: var(--sp-4);
        padding-top: var(--sp-4);
        border-top: 1rpx solid var(--c-line);
    }

    &__head {
        display: flex;
        align-items: flex-start;
        gap: var(--sp-3);
    }

    &__title {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-body);
        color: var(--c-ink);
    }

    &__desc {
        display: block;
        margin-top: var(--sp-2);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__meta,
    &__amount-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-3);
    }

    &__meta {
        margin-top: var(--sp-2);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__amount-row {
        margin-top: var(--sp-3);
    }

    &__amount-label {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__amount {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
        font-variant-numeric: tabular-nums;
    }
}

.addons {
    margin-top: var(--sp-3);
    padding: var(--sp-3);
    border-radius: var(--r-sm);
    background: var(--c-neutral-bg);

    &__title {
        display: block;
        margin-bottom: var(--sp-2);
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-ink-2);
    }

    &__row {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
        min-height: 48rpx;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__name {
        flex: 1;
        min-width: 0;
    }

    &__quantity {
        flex-shrink: 0;
    }

    &__amount {
        flex-shrink: 0;
        color: var(--c-ink-2);
        font-variant-numeric: tabular-nums;
    }
}

.row {
    display: flex;
    align-items: baseline;
    gap: var(--sp-3);
    min-height: 72rpx;
    padding: var(--sp-2) 0;

    &--divided {
        border-top: 1rpx solid var(--c-line);
    }

    &__label {
        flex-shrink: 0;
        width: 144rpx;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__value {
        flex: 1;
        min-width: 0;
        text-align: right;
        font-size: var(--fs-md);
        line-height: var(--lh-body);
        color: var(--c-ink);
        overflow-wrap: anywhere;
    }

    &__value--money {
        font-variant-numeric: tabular-nums;
    }

    &__value--strong {
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
    }
}

.payment-state {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);
    margin-top: var(--sp-3);
    padding: var(--sp-3);
    border-radius: var(--r-sm);
    background: var(--c-neutral-bg);

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__value {
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-ink-2);
    }
}

.address {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-3);
    padding: var(--sp-3) 0 var(--sp-2);
    border-top: 1rpx solid var(--c-line);

    &__label {
        flex-shrink: 0;
        width: 144rpx;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__value {
        flex: 1;
        min-width: 0;
        text-align: right;
        font-size: var(--fs-md);
        line-height: var(--lh-body);
        color: var(--c-ink);
        overflow-wrap: anywhere;
    }
}

.order-sn {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--sp-2);

    &__value {
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink);
        overflow-wrap: anywhere;
    }

    &__copy {
        flex-shrink: 0;
        padding: var(--sp-2) 0 var(--sp-2) var(--sp-2);
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
    }
}

.readonly-note {
    padding: var(--sp-4) var(--sp-2) 0;
    text-align: center;

    &__text {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}
</style>
