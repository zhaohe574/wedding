<template>
    <view class="page-wrap result">
        <view class="result__icon" :class="`is-${state}`">
            <u-icon :name="iconName" :size="96" :color="iconColor" />
        </view>

        <text class="result__title">{{ titleText }}</text>
        <text class="result__desc">{{ descText }}</text>

        <base-card v-if="amount" compact class="result__amount">
            <text class="result__amount-label">支付金额</text>
            <price size="xl" :content="amount" />
        </base-card>

        <view v-if="state === 'pending'" class="result__poll">
            <text class="result__poll-text">正在确认支付结果…（{{ pollCount }}/{{ maxPoll }})</text>
        </view>

        <view class="result__actions">
            <base-button text="查看订单" variant="brand" size="md" @click="goOrder" />
            <base-button
                v-if="state === 'fail'"
                text="重新支付"
                variant="ghost"
                size="md"
                :loading="retrying"
                @click="retryPay"
            />
            <base-button text="返回首页" variant="text" size="md" @click="goHome" />
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 支付结果页。
 *
 * 铁律：requestPayment 的 success 只代表用户完成了支付动作，
 * 订单是否真的支付成功必须以 /pay/payStatus 轮询为准 —— 微信回调可能晚几秒。
 */
import { computed, ref } from 'vue'
import { onLoad, onUnload } from '@dcloudio/uni-app'
import { getPayStatus, prepay } from '@/api/pay'
import { requestPayment, sleep } from '@/platform/pay'

const POLL_INTERVALS = [1, 2, 3, 5, 8, 13]
const maxPoll = POLL_INTERVALS.length

const orderId = ref(0)
const paymentSn = ref('')
const amount = ref<string | number>('')
const state = ref<'pending' | 'success' | 'fail'>('pending')
const pollCount = ref(0)
const retrying = ref(false)

let cancelled = false

const iconName = computed(() => {
    if (state.value === 'success') return 'checkmark-circle-fill'
    if (state.value === 'fail') return 'close-circle-fill'
    return 'clock-fill'
})

const iconColor = computed(() => {
    if (state.value === 'success') return 'var(--c-success)'
    if (state.value === 'fail') return 'var(--c-danger)'
    return 'var(--c-warning)'
})

const titleText = computed(() => {
    if (state.value === 'success') return '支付成功'
    // 轮询超时 ≠ 支付失败，措辞不能替用户下结论
    if (state.value === 'fail') return '支付结果待确认'
    return '确认中'
})

const descText = computed(() => {
    if (state.value === 'success') return '订单已进入服务流程，可在订单详情查看进度'
    if (state.value === 'fail') return '暂未查到支付结果，请勿重复支付；如已扣款请联系客服'
    return '微信支付结果可能有延迟，请稍候'
})

async function poll() {
    for (const seconds of POLL_INTERVALS) {
        if (cancelled) return
        await sleep(seconds * 1000)
        if (cancelled) return
        pollCount.value += 1
        try {
            const res = await getPayStatus({ id: orderId.value, payment_sn: paymentSn.value || undefined })
            const status = Number((res as any)?.pay_status ?? (res as any)?.payment?.pay_status ?? -1)
            if (status === 1) {
                state.value = 'success'
                return
            }
        } catch {
            // 单次失败继续轮询
        }
    }
    if (!cancelled && state.value === 'pending') state.value = 'fail'
}

function goOrder() {
    if (!orderId.value) {
        uni.switchTab({ url: '/pages/order/order' })
        return
    }
    // 从详情页进入支付结果时直接回退，避免 redirectTo 在页面栈里压出第二个详情页
    if (getCurrentPages().length > 1) {
        uni.navigateBack()
        return
    }
    uni.redirectTo({ url: `/packages/pkg-order/pages/detail/detail?id=${orderId.value}` })
}

function goHome() {
    uni.switchTab({ url: '/pages/index/index' })
}

/** 支付状态的唯一可信来源；返回 unknown 时不允许重付 */
async function queryPayState(): Promise<'paid' | 'unpaid' | 'unknown'> {
    try {
        const res = await getPayStatus({ id: orderId.value, payment_sn: paymentSn.value || undefined })
        const status = Number((res as any)?.pay_status ?? (res as any)?.payment?.pay_status ?? -1)
        if (status === 1) return 'paid'
        if (status === 0) return 'unpaid'
        return 'unknown'
    } catch {
        return 'unknown'
    }
}

async function retryPay() {
    if (retrying.value || !orderId.value) return
    retrying.value = true
    try {
        // 铁律：轮询超时只代表「没查到」，不代表「没付」。
        // 重付前必须回查一次真实状态，否则可能对已支付订单再发一次 prepay。
        const now = await queryPayState()
        if (now === 'paid') {
            state.value = 'success'
            uni.showToast({ title: '该订单已支付成功', icon: 'none' })
            return
        }
        if (now === 'unknown') {
            uni.showToast({ title: '暂时无法确认支付状态，请稍后再试', icon: 'none' })
            return
        }

        const pre = await prepay({ id: orderId.value })
        if (!pre || !pre.config) throw new Error('预支付参数缺失')
        if (pre.payment_sn) paymentSn.value = pre.payment_sn
        const r = await requestPayment(pre.config)
        if (r === 'success') {
            state.value = 'pending'
            pollCount.value = 0
            poll()
        }
    } catch {
        // 请求层已提示
    } finally {
        retrying.value = false
    }
}

onLoad((options: any) => {
    orderId.value = Number(options?.order_id || options?.id || 0)
    paymentSn.value = options?.payment_sn ? String(options.payment_sn) : ''
    amount.value = options?.amount ? Number(options.amount) : ''
    if (!orderId.value) {
        state.value = 'fail'
        return
    }
    poll()
})

onUnload(() => {
    cancelled = true
})
</script>

<style lang="scss" scoped>
.result {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    min-height: 100vh;
    box-sizing: border-box;
    padding: 120rpx var(--sp-4) calc(var(--sp-5) + var(--safe-bottom));
    background: var(--c-bg);

    &__icon {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: 144rpx;
        height: 144rpx;
        border-radius: 50%;
        box-shadow: var(--shadow-float);

        &.is-pending {
            background: var(--c-warning-bg);
        }

        &.is-success {
            background: var(--c-success-bg);
        }

        &.is-fail {
            background: var(--c-danger-bg);
        }
    }

    &__title {
        margin-top: var(--sp-4);
        font-size: var(--fs-2xl);
        font-weight: 500;
        color: var(--c-ink);
        line-height: 1.4;
        text-align: center;
    }

    &__desc {
        max-width: 600rpx;
        margin-top: var(--sp-2);
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        text-align: center;
        line-height: 1.6;
    }

    &__amount {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--sp-1);
        width: 100%;
        box-sizing: border-box;
        margin-top: var(--sp-4);
    }

    &__amount-label {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__poll {
        margin-top: var(--sp-3);
        padding: var(--sp-2) var(--sp-3);
        border-radius: var(--r-pill);
        background: var(--c-warning-bg);
    }

    &__poll-text {
        font-size: var(--fs-xs);
        color: var(--c-warning);
        line-height: 1.5;
    }

    &__actions {
        display: flex;
        flex-direction: column;
        gap: var(--sp-2);
        width: 100%;
        margin-top: auto;
        padding-top: var(--sp-5);
    }
}
</style>
