<template>
    <!-- O2/O14：展示层用 ResultPage 三态矩阵；不直接给「重新支付」，次操作为幂等「刷新结果」 -->
    <result-page
        :state="displayState"
        :poll="pollView"
        :rechecking="retrying"
        @primary="goOrder"
        @secondary="onSecondary"
        @tertiary="goHome"
    >
        <template #amount>
            <price size="xl" :content="amount" :trim-zeros="false" />
        </template>
    </result-page>
    <app-toast />
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
import { FB } from '@/constants/feedback'
import { toast } from '@/composables/useToast'
import { fbPollText } from '@/constants/feedback'

const POLL_INTERVALS = [1, 2, 3, 5, 8, 13]
const maxPoll = POLL_INTERVALS.length

const orderId = ref(0)
const paymentSn = ref('')
const amount = ref<string | number>('')
const state = ref<'pending' | 'success' | 'fail'>('pending')
const pollCount = ref(0)
const retrying = ref(false)

let cancelled = false

/** O2：展示语义映射，业务 state 变量名与取值域（pending/success/fail）逐字不改 */
const displayState = computed<'success' | 'pending' | 'unconfirmed'>(() =>
    state.value === 'success' ? 'success' : state.value === 'pending' ? 'pending' : 'unconfirmed')
/** pending 轮询进度（分母明确、不虚构百分比） */
const pollView = computed(() => ({ current: pollCount.value, max: maxPoll, text: fbPollText(pollCount.value, maxPoll) }))

/** O2：图标/配色/文案由 ResultPage 按展示语义统一渲染（success=--c-success，pending/unconfirmed=--c-info）；
 *  本页保留业务 state 与 FB.PAY_* 文案 SSOT，不再自算展示层 computed。 */

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

/**
 * O14：unconfirmed 的次操作「刷新结果」= 幂等 queryPayState 回查，不发起 prepay。
 * paid → success；unknown → 禁用重付并提示；unpaid → 引导去订单详情重付。
 * 原 retryPay（含 prepay）函数体保留不动，仅不再被模板调用；重付入口移到订单详情。
 */
async function onSecondary() {
    if (state.value === 'pending') { goOrder(); return }
    if (retrying.value) return
    retrying.value = true
    try {
        const now = await queryPayState()
        if (now === 'paid') { state.value = 'success'; toast.info(FB.PAY_ALREADY_PAID) }
        else if (now === 'unknown') { toast.error(FB.PAY_RECHECK_UNKNOWN) }
        else { toast.info(FB.PAY_UNCONFIRMED_TITLE) }
    } finally {
        retrying.value = false
    }
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
            toast.info(FB.PAY_ALREADY_PAID)
            return
        }
        if (now === 'unknown') {
            toast.error(FB.PAY_RECHECK_UNKNOWN)
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
/* 展示层视觉规格已由 ResultPage 组件（T-23）统一承载，原页面级 .result 死样式已移除 */
</style>
