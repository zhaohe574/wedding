<template>
    <view class="page-wrap detail">
        <view v-if="loading" class="page-pad">
            <base-skeleton :rows="4" />
        </view>

        <base-empty v-else-if="error" status="error" error-kind="network" fixed @retry="load" />

        <template v-else>
            <!-- 状态头 -->
            <view class="status-head">
                <view class="status-head__row">
                    <text class="status-head__label">{{ statusText }}</text>
                </view>
                <text v-if="hint" class="status-head__hint">{{ hint }}</text>
                <view v-if="countdown > 0" class="status-head__count">
                    <text class="status-head__count-label">剩余</text>
                    <text class="status-head__count-value">{{ countdownText }}</text>
                </view>
            </view>

            <!-- 服务项 -->
            <view class="page-pad section">
                <base-card>
                    <view v-for="item in items" :key="item.id" class="item">
                        <image
                            v-if="item.staff && item.staff.avatar"
                            class="item__avatar"
                            :src="item.staff.avatar"
                            mode="aspectFill"
                        />
                        <view class="item__body">
                            <text class="item__title">{{ itemTitle(item) }}</text>
                            <text v-if="item.staff && item.staff.name" class="item__meta">
                                服务人员 {{ item.staff.name }}
                            </text>
                            <text v-if="item.addons && item.addons.length" class="item__meta">
                                附加 {{ item.addons.map((a: any) => a.name).join('、') }}
                            </text>
                        </view>
                        <view class="item__price">
                            <price size="md" :content="item.subtotal ?? 0" />
                        </view>
                    </view>

                    <view v-if="!items.length" class="item item--empty">
                        <text class="item__meta">暂无服务项明细</text>
                    </view>
                </base-card>
            </view>

            <!-- 金额 -->
            <view class="page-pad section">
                <base-card>
                    <view class="kv">
                        <text class="kv__k">订单金额</text>
                        <price size="md" :content="order.total_amount ?? 0" />
                    </view>
                    <view class="kv">
                        <text class="kv__k">已支付</text>
                        <text class="kv__v">¥{{ money(order.paid_amount) }}</text>
                    </view>
                    <view class="kv kv--strong">
                        <text class="kv__k">待支付</text>
                        <text class="kv__v kv__v--brand">¥{{ money(needPayAmount) }}</text>
                    </view>
                </base-card>
            </view>

            <!-- 订单信息 -->
            <view class="page-pad section">
                <base-card>
                    <view class="kv">
                        <text class="kv__k">订单编号</text>
                        <text class="kv__v" @tap="copySn">{{ order.order_sn }}</text>
                    </view>
                    <view class="kv">
                        <text class="kv__k">下单时间</text>
                        <text class="kv__v">{{ order.create_time }}</text>
                    </view>
                    <view v-if="order.service_date" class="kv">
                        <text class="kv__k">服务日期</text>
                        <text class="kv__v">{{ order.service_date }}</text>
                    </view>
                    <view v-if="order.contact_name" class="kv">
                        <text class="kv__k">联系人</text>
                        <text class="kv__v">{{ order.contact_name }} {{ order.contact_mobile }}</text>
                    </view>
                    <view v-if="order.address" class="kv">
                        <text class="kv__k">服务地址</text>
                        <text class="kv__v kv__v--wrap">{{ order.address }}</text>
                    </view>
                </base-card>
            </view>

            <view class="detail__space" />

            <!-- 底部操作栏 -->
            <view class="action-bar">
                <base-button
                    v-if="hasMoreActions"
                    text="更多"
                    variant="ghost"
                    size="md"
                    :block="false"
                    @click="moreVisible = true"
                />
                <base-button
                    v-if="actions.pay"
                    text="去支付"
                    variant="brand"
                    size="md"
                    :block="false"
                    :loading="paying"
                    @click="handlePay"
                />
                <base-button
                    v-else-if="payReason"
                    :text="payReason"
                    variant="ghost"
                    size="md"
                    :block="false"
                    :disabled="true"
                />
                <base-button
                    v-else
                    text="联系顾问"
                    variant="brand"
                    size="md"
                    :block="false"
                    @click="contactAdvisor"
                />
            </view>

            <!-- 更多操作 -->
            <view v-if="moreVisible" class="mask" @tap="moreVisible = false">
                <view class="sheet" @tap.stop>
                    <text class="sheet__title">更多操作</text>

                    <view hover-class="is-pressed" v-if="actions.change_date" class="sheet__item" @tap="goChange('apply-date')">
                        <text class="sheet__item-text">申请改期</text>
                        <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
                    </view>
                    <view hover-class="is-pressed" v-if="actions.change_date" class="sheet__item" @tap="goChange('apply-addon')">
                        <text class="sheet__item-text">申请加项</text>
                        <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
                    </view>
                    <view hover-class="is-pressed" v-if="actions.pause" class="sheet__item" @tap="goChange('apply-pause')">
                        <text class="sheet__item-text">申请暂停</text>
                        <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
                    </view>
                    <view hover-class="is-pressed" class="sheet__item" @tap="goChangeList">
                        <text class="sheet__item-text">变更记录</text>
                        <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
                    </view>
                    <view hover-class="is-pressed" v-if="actions.cancel" class="sheet__item" @tap="handleCancelFromSheet">
                        <text class="sheet__item-text sheet__item-text--danger">取消订单</text>
                        <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
                    </view>

                    <view class="sheet__actions">
                        <base-button text="关闭" variant="ghost" size="md" @click="moreVisible = false" />
                    </view>
                </view>
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
/**
 * 订单详情。
 *
 * 可执行动作一律读服务端返回的 actions / action_reasons，不在页面里判断状态组合 ——
 * 老端把 6 个字段在页面里组合判断，3,302 行里近 400 行在算按钮该不该显示。
 *
 * 支付流程：prepay → wx.requestPayment → 轮询 payStatus（不以 requestPayment 的 success 为准）。
 */
import { computed, ref } from 'vue'
import { onLoad, onUnload } from '@dcloudio/uni-app'
import {
    ORDER_STATUS_NAME_MAP,
    resolveStatusLabel,
    type OrderActions
} from '@shared/contracts'
import { getOrderDetailV2, getOrderDetail, cancelOrder } from '@/api/order'
import { prepay } from '@/api/pay'
import { requestPayment } from '@/platform/pay'
import { formatCountdown } from '@/request'
import { report } from '@/platform/report'
import { navigate } from '@/utils/link'
import { formatMoney } from '@shared/utils/format'

const orderId = ref(0)
const loading = ref(true)
const error = ref(false)
const paying = ref(false)

const order = ref<any>({})
const items = ref<any[]>([])
const actions = ref<OrderActions>({
    pay: false,
    cancel: false,
    change_date: false,
    pause: false,
    contact_advisor: true
})
const payReason = ref('')
const remainSeconds = ref(0)
const moreVisible = ref(false)
/** 从订单列表「去支付」直达时，加载完成即拉起支付 */
const autoPay = ref(false)

/** 次要操作是否可用（决定「更多」按钮是否出现） */
const hasMoreActions = computed(
    () => actions.value.change_date || actions.value.pause || actions.value.cancel
)

let timer: any = null

const statusText = computed(() => {
    const raw = Number(order.value.order_status)
    const mapped = resolveStatusLabel(ORDER_STATUS_NAME_MAP as any, raw)
    return mapped.known ? mapped.label : order.value.order_status_desc || mapped.label
})

const needPayAmount = computed(() => order.value.need_pay_amount ?? order.value.unpaid_amount ?? 0)

const hint = computed(() => {
    const s = Number(order.value.order_status)
    if (s === 0) return '商家确认后即可支付，通常 1 小时内完成'
    if (s === 1) return '请在有效期内完成支付，超时订单将自动取消'
    if (s === 2) return '服务人员将在约定时间上门，请保持手机畅通'
    if (s === 4) return '服务已完成，欢迎评价'
    return ''
})

const countdown = computed(() => remainSeconds.value)
const countdownText = computed(() => formatCountdown(remainSeconds.value))

function money(v: any) {
    return formatMoney(v as any)
}

function itemTitle(item: any) {
    return item?.package?.name || item?.staff?.name || '婚庆服务'
}

/** 老接口降级时的动作推导：只在 v2 不可用时使用 */
function deriveActionsLegacy(raw: any): OrderActions {
    const s = Number(raw.order_status)
    return {
        pay: s === 1 && Number(raw.need_pay_amount ?? 0) > 0,
        cancel: s === 0 || s === 1,
        change_date: s === 2,
        pause: s === 2,
        contact_advisor: true
    }
}

async function load() {
    loading.value = true
    error.value = false
    try {
        try {
            const res = await getOrderDetailV2(orderId.value)
            order.value = res.order
            items.value = res.items || []
            actions.value = res.actions
            payReason.value = res.action_reasons?.pay || ''
            remainSeconds.value = Number(res.pay?.pay_remain_seconds ?? 0)
        } catch {
            const raw = await getOrderDetail({ id: orderId.value })
            order.value = raw
            items.value = raw.items || []
            actions.value = deriveActionsLegacy(raw)
            payReason.value = Number(raw.order_status) === 0 ? '待商家确认' : ''
            remainSeconds.value = Number(raw.pay_remain_seconds ?? 0)
        }
        startCountdown()
    } catch {
        error.value = true
    } finally {
        loading.value = false
    }
}

function startCountdown() {
    stopCountdown()
    if (remainSeconds.value <= 0) return
    timer = setInterval(() => {
        remainSeconds.value = Math.max(0, remainSeconds.value - 1)
        if (remainSeconds.value === 0) stopCountdown()
    }, 1000)
}

function stopCountdown() {
    if (timer) {
        clearInterval(timer)
        timer = null
    }
}

function copySn() {
    uni.setClipboardData({ data: String(order.value.order_sn || '') })
}

function contactAdvisor() {
    navigate({
        path: '/pages/customer-service/customer-service',
        query: { scene: 'order', order_id: orderId.value }
    })
}

function goChange(mode: 'apply-date' | 'apply-addon' | 'apply-pause') {
    moreVisible.value = false
    navigate({
        path: `/packages/pkg-service/pages/change/${mode}`,
        query: { order_id: orderId.value }
    })
}

function goChangeList() {
    moreVisible.value = false
    navigate({ path: '/packages/pkg-service/pages/change/list' })
}

function handleCancelFromSheet() {
    moreVisible.value = false
    handleCancel()
}

async function handleCancel() {
    const confirmed = await new Promise<boolean>((resolve) => {
        uni.showModal({
            title: '取消订单',
            content: '取消后档期将释放，确定取消吗？',
            success: (r) => resolve(!!r.confirm),
            fail: () => resolve(false)
        })
    })
    if (!confirmed) return
    try {
        await cancelOrder({ id: orderId.value })
        uni.showToast({ title: '已取消', icon: 'none' })
        load()
    } catch {
        // 请求层已提示
    }
}

async function handlePay() {
    if (paying.value) return
    paying.value = true
    try {
        const pre = await prepay({ id: orderId.value })
        if (!pre || !pre.config) throw new Error('预支付参数缺失')

        const result = await requestPayment(pre.config)
        if (result === 'cancel') {
            uni.showToast({ title: '已取消支付', icon: 'none' })
            return
        }
        if (result === 'fail') {
            uni.showToast({ title: '支付失败，请重试', icon: 'none' })
            return
        }

        // 结果判定交给支付结果页统一轮询，避免两处各写一套
        uni.navigateTo({
            url: `/packages/pkg-order/pages/payment-result/payment-result?order_id=${orderId.value}&payment_sn=${encodeURIComponent(
                pre.payment_sn || ''
            )}&amount=${Number(needPayAmount.value) || ''}`
        })
    } catch (e: any) {
        report('api_error', { endpoint: 'pay.prepay', message: e?.message || '支付异常' })
        // 静默失败等于用户点了没反应：订单过期、参数异常都必须说出来
        uni.showToast({ title: e?.message || '支付发起失败，请稍后重试', icon: 'none' })
    } finally {
        paying.value = false
    }
}

onLoad((options: any) => {
    orderId.value = Number(options?.id || 0)
    autoPay.value = String(options?.pay || '') === '1'
    if (!orderId.value) {
        error.value = true
        loading.value = false
        return
    }
    load().then(() => {
        // 只有服务端确认可支付时才自动拉起，避免对已支付/已关闭订单再发 prepay
        if (autoPay.value && actions.value.pay) handlePay()
    })
})

onUnload(() => stopCountdown())
</script>

<style lang="scss" scoped>
.detail {
    padding-bottom: calc(180rpx + var(--safe-bottom));
    background: var(--c-bg);

    &__space {
        height: var(--sp-4);
    }
}

.status-head {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
    padding: var(--sp-5) var(--sp-4) var(--sp-6);
    background: var(--c-ink);

    &__row {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__label {
        font-size: var(--fs-2xl);
        font-weight: 500;
        color: var(--c-on-dark);
        line-height: 1.4;
    }

    &__hint {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
        line-height: 1.6;
    }

    &__count {
        display: flex;
        align-items: baseline;
        gap: var(--sp-1);
        margin-top: var(--sp-1);
    }

    &__count-label {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
    }

    &__count-value {
        font-size: var(--fs-lg);
        color: var(--c-brand-soft);
        font-weight: 500;
    }
}

.section {
    margin-top: var(--sp-3);
}

.item {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    padding: var(--sp-3) 0;

    & + & {
        border-top: 1rpx solid var(--c-line);
    }

    &--empty {
        justify-content: center;
    }

    &__avatar {
        flex-shrink: 0;
        width: 88rpx;
        height: 88rpx;
        border-radius: var(--r-sm);
        background: var(--c-neutral-bg);
    }

    &__body {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
        min-width: 0;
    }

    &__title {
        font-size: var(--fs-md);
        color: var(--c-ink);
        font-weight: 500;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    &__meta {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    &__price {
        flex-shrink: 0;
        max-width: 180rpx;
        text-align: right;
    }
}

.kv {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--sp-3);
    padding: var(--sp-2) 0;

    &__k {
        flex-shrink: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: 1.6;
    }

    &__v {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink);
        line-height: 1.6;
        text-align: right;
        overflow-wrap: anywhere;
        word-break: break-word;

        &--brand {
            color: var(--c-brand-text);
            font-weight: 500;
            font-size: var(--fs-lg);
        }
    }

    &--strong {
        margin-top: var(--sp-2);
        padding-top: var(--sp-3);
        border-top: 1rpx solid var(--c-line);
    }
}

.action-bar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--sp-2);
    padding: var(--sp-3) var(--sp-4);
    padding-bottom: calc(var(--sp-3) + var(--safe-bottom));
    background: var(--c-surface);
    border-top: 1rpx solid var(--c-line);
}

.mask {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 200;
    display: flex;
    align-items: flex-end;
    background: var(--c-mask);
}

.sheet {
    display: flex;
    flex-direction: column;
    width: 100%;
    box-sizing: border-box;
    padding: var(--sp-4);
    padding-bottom: calc(var(--sp-4) + var(--safe-bottom));
    border-radius: var(--r-lg) var(--r-lg) 0 0;
    background: var(--c-surface);

    &__title {
        margin-bottom: var(--sp-2);
        font-size: var(--fs-lg);
        font-weight: 500;
        color: var(--c-ink);
        line-height: 1.5;
    }

    &__item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-3);
        min-height: 88rpx;
        box-sizing: border-box;
        border-bottom: 1rpx solid var(--c-line);
    }

    &__item-text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        color: var(--c-ink);
        line-height: 1.5;

        &--danger {
            color: var(--c-danger);
            font-weight: 500;
        }
    }

    &__actions {
        margin-top: var(--sp-4);
    }
}
</style>
