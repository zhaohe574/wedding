<template>
    <page-shell
        bg="page"
        :bottom-bar="true"
        title="支付结果"
        nav-variant="solid"
        nav-background="page"
        :show-back="false"
        :nav-capsule-avoid="true"
    >
        <!-- 左关闭（不返回支付页）：关闭语义走 #nav-left，不进返回栈 -->
        <template #nav-left>
            <view
                class="pnav-close"
                hover-class="is-pressed"
                aria-role="button"
                aria-label="关闭"
                @tap="goOrder"
            >
                <tn-icon name="close" :size="36" />
            </view>
        </template>

        <!-- 缺订单号：没有可查的对象，直接给不可重试的错误态 + 订单列表出口 -->
        <view v-if="!orderId" class="rp-wrap">
            <base-empty
                status="error"
                :error-kind="'business'"
                error-text="没有找到订单信息"
                desc="请到「我的订单」查看支付结果"
                action-text="查看订单"
                show-action
                @action="goOrder"
            />
        </view>

        <!-- 支付中 / 结果待确认：交给 result-page 统一三态（不在这里做第二套视觉） -->
        <template v-else-if="displayState !== 'success'">
            <view class="rp-wrap">
                <result-page
                    :state="displayState"
                    :poll="pollView"
                    :rechecking="retrying"
                    @primary="goOrder"
                    @secondary="onSecondary"
                    @tertiary="goHome"
                >
                    <template #amount>
                        <price
                            v-if="amountText !== null"
                            size="xl"
                            :content="amountText"
                            :trim-zeros="false"
                        />
                    </template>
                </result-page>
            </view>
        </template>

        <!-- 支付成功：压印印章 + 结论 + 订单摘要 + 接下来（设计稿 09） -->
        <template v-else>
            <view class="payok">
                <view class="seal" aria-hidden="true">
                    <view class="seal__ring" />
                    <tn-icon name="check" :size="48" class="seal__icon" />
                </view>
                <text class="payok__title">支付成功</text>
                <text class="payok__desc">{{ successDesc }}</text>
                <view v-if="amountText !== null" class="payok__amt">
                    <price size="xl" :content="amountText" :trim-zeros="false" />
                    <text class="payok__amt-sub">{{ amountSubText }}</text>
                </view>
            </view>

            <!-- 订单摘要：取不到就整块降级成「重新加载」，不编内容 -->
            <view class="card2">
                <view class="card2__head">
                    <text class="card2__title">订单摘要</text>
                    <view class="card2__more" hover-class="is-pressed" @tap="goOrder">
                        <text class="card2__more-text">查看订单</text>
                        <tn-icon name="right" :size="20" />
                    </view>
                </view>

                <view v-if="detailLoading" class="card2__sk">
                    <base-skeleton :rows="3" variant="text" />
                </view>
                <view v-else-if="detailError" class="card2__err">
                    <text class="card2__err-text">订单摘要没取到</text>
                    <view class="card2__err-btn" hover-class="is-pressed" @tap="loadDetail">
                        <text class="card2__err-btn-text">重新加载</text>
                    </view>
                </view>
                <template v-else>
                    <view v-for="row in summaryRows" :key="row.key" class="kv">
                        <text class="kv__k">{{ row.label }}</text>
                        <text class="kv__v ellipsis">{{ row.value }}</text>
                    </view>
                </template>
            </view>

            <!-- 接下来：只用真实字段拼时间轴，凑不出两项就不渲染 -->
            <view v-if="nextSteps.length" class="card2">
                <view class="card2__head">
                    <text class="card2__title">接下来</text>
                </view>
                <view v-for="(s, i) in nextSteps" :key="s.key" class="nx">
                    <view class="nx__idx"
                        ><text class="nx__idx-text">{{ i + 1 }}</text></view
                    >
                    <view class="nx__body">
                        <text class="nx__title">{{ s.title }}</text>
                        <text v-if="s.desc" class="nx__desc">{{ s.desc }}</text>
                    </view>
                    <text class="nx__when">{{ s.when }}</text>
                </view>
            </view>
        </template>

        <template #bottom>
            <!-- 设计稿：唯一主操作通栏（其余入口降为摘要卡右上角小链接） -->
            <bottom-bar v-if="orderId && displayState === 'success'">
                <base-button
                    :text="primaryText"
                    variant="ink"
                    size="lg"
                    block
                    @click="onPrimaryAction"
                />
            </bottom-bar>
        </template>
    </page-shell>
    <app-toast />
</template>

<script setup lang="ts">
/**
 * 09 支付成功（设计稿 2855–2919）。
 *
 * 铁律：requestPayment 的 success 只代表用户完成了支付动作，订单是否真的支付成功
 * 必须以 /pay/payStatus 轮询为准 —— 微信回调可能晚几秒。
 *
 * 三态（02 §4.9）：
 *  - pending      → result-page「支付结果确认中」+ 3s 档轮询（最多 5 次），底部「查看订单」
 *  - success      → 压印印章 + 结论 + 订单摘要 + 接下来（本页自绘，印章是全站识别符号）
 *  - unconfirmed  → 轮询超时只代表「没查到」，不等于失败：不出现红叉/「失败」字样，给「刷新结果」
 *
 * 数据：订单摘要与「接下来」来自 order.getOrderDetailV2；取不到只降级该区块，
 * 不影响已确认的支付结果（绝不因为取不到数据就说没付成功）。
 */
import { computed, ref } from 'vue'
import { onLoad, onUnload } from '@dcloudio/uni-app'
import { getPayStatus } from '@/api/pay'
import { getOrderDetailV2 } from '@/api/order'
import { formatDate, parseDateValue } from '@shared/utils/format'
import { FB, fbPollText } from '@/constants/feedback'
import { toast } from '@/composables/useToast'
import { report } from '@/platform/report'

/** 轮询节奏：1/2/3/5/8/13 秒，总计约 32 秒；前密后疏，避免一上来就打满接口 */
const POLL_INTERVALS = [1, 2, 3, 5, 8, 13]
const maxPoll = POLL_INTERVALS.length

const orderId = ref(0)
const paymentSn = ref('')
const queryAmount = ref<string | number>('')
const state = ref<'pending' | 'success' | 'fail'>('pending')
const pollCount = ref(0)
const retrying = ref(false)

const order = ref<any>({})
const items = ref<any[]>([])
const pendingQuestionnaire = ref<any>(null)
const payInfo = ref<any>({})
const detailLoading = ref(false)
const detailError = ref(false)

let cancelled = false

const displayState = computed<'success' | 'pending' | 'unconfirmed'>(() =>
    state.value === 'success' ? 'success' : state.value === 'pending' ? 'pending' : 'unconfirmed'
)
const pollView = computed(() => ({
    current: pollCount.value,
    max: maxPoll,
    text: fbPollText(pollCount.value, maxPoll)
}))

/* ---------------- 金额（只认服务端） ---------------- */

const amountText = computed<string | number | null>(() => {
    const paid = order.value?.paid_amount ?? payInfo.value?.paid_amount ?? payInfo.value?.pay_amount
    if (paid !== undefined && paid !== null && paid !== '' && Number(paid) > 0) return paid
    if (queryAmount.value !== '' && queryAmount.value !== null && Number(queryAmount.value) > 0) {
        return queryAmount.value
    }
    return null
})

const amountSubText = computed(() => {
    const stage = String(payInfo.value?.current_pay_stage || '')
    const stageText =
        stage === 'deposit' ? '定金' : stage === 'full' ? '全款' : stage === 'balance' ? '尾款' : ''
    const parts = [stageText, '微信支付'].filter(Boolean)
    return parts.join(' · ')
})

/* ---------------- 文案 ---------------- */

const hostName = computed(() => items.value[0]?.staff?.name || '')

const successDesc = computed(() => {
    const d = parseDateValue(order.value?.service_date)
    const staffName = hostName.value
    const lines: string[] = []
    if (d) lines.push(`已为你锁定 ${formatDate(d, 'MM.DD')} 的档期`)
    else lines.push('已为你锁定档期')
    if (staffName) lines.push(`主持人${staffName}已收到通知`)
    return lines.join('，')
})

const summaryRows = computed(() => {
    const rows: Array<{ key: string; label: string; value: string }> = []
    const d = parseDateValue(order.value?.service_date)
    if (d) {
        const w = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][d.getDay()]
        rows.push({ key: 'date', label: '服务档期', value: `${formatDate(d, 'YYYY.MM.DD')} ${w}` })
    }
    const pkgName = items.value[0]?.package?.name || ''
    if (hostName.value || pkgName) {
        rows.push({
            key: 'host',
            label: '主持人',
            value: [hostName.value, pkgName].filter(Boolean).join(' · ')
        })
    }
    if (order.value?.address)
        rows.push({ key: 'addr', label: '举办场地', value: String(order.value.address) })
    return rows
})

/** 接下来：只拼真实字段，凑不出 2 项就不渲染（宁可少显示，不编日期） */
const nextSteps = computed(() => {
    const list: Array<{ key: string; title: string; desc: string; when: string }> = []
    if (pendingQuestionnaire.value) {
        list.push({
            key: 'questionnaire',
            title: '填写新人问卷',
            desc: '仪式偏好、迎亲细节与音乐流程',
            when: '今天'
        })
    }
    const needPay = Number(payInfo.value?.need_pay_amount ?? 0)
    const stage = String(payInfo.value?.current_pay_stage || '')
    if (needPay > 0 && stage !== 'paid') {
        list.push({
            key: 'balance',
            title: `支付尾款 ¥${needPay}`,
            desc: '按订单约定的开放时间支付',
            when: '待开放'
        })
    }
    const d = parseDateValue(order.value?.service_date)
    if (d) {
        list.push({
            key: 'ceremony',
            title: '婚礼当天',
            desc: '主持人与流程督导按约定时间到场',
            when: formatDate(d, 'MM.DD')
        })
    }
    return list.length >= 2 ? list : []
})

const primaryText = computed(() => (pendingQuestionnaire.value ? '填写新人问卷' : '查看订单'))

/* ---------------- 动作 ---------------- */

function onPrimaryAction() {
    if (pendingQuestionnaire.value) {
        uni.navigateTo({
            url: `/packages/pkg-service/pages/questionnaire/detail?id=${pendingQuestionnaire.value.id}`
        })
        return
    }
    goOrder()
}

function goOrder() {
    if (!orderId.value) {
        uni.reLaunch({ url: '/pages/order/order' })
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

/** unconfirmed 的次操作「刷新结果」= 幂等回查，不发起 prepay（避免重复扣款） */
async function onSecondary() {
    if (state.value === 'pending') {
        goOrder()
        return
    }
    if (retrying.value) return
    retrying.value = true
    try {
        const now = await queryPayState()
        if (now === 'paid') {
            state.value = 'success'
            loadDetail()
            toast.info(FB.PAY_ALREADY_PAID)
        } else if (now === 'unknown') {
            toast.error(FB.PAY_RECHECK_UNKNOWN)
        } else {
            toast.info(FB.PAY_UNCONFIRMED_TITLE)
        }
    } finally {
        retrying.value = false
    }
}

async function queryPayState(): Promise<'paid' | 'unpaid' | 'unknown'> {
    try {
        const res: any = await getPayStatus({
            from: 'order',
            order_id: orderId.value,
            payment_sn: paymentSn.value || undefined
        })
        const status = Number(res?.pay_status ?? res?.payment?.pay_status ?? -1)
        if (status === 1) return 'paid'
        if (status === 0) return 'unpaid'
        return 'unknown'
    } catch {
        return 'unknown'
    }
}

async function poll() {
    for (const seconds of POLL_INTERVALS) {
        if (cancelled) return
        await new Promise((r) => setTimeout(r, seconds * 1000))
        if (cancelled) return
        pollCount.value += 1
        try {
            const res: any = await getPayStatus({
                from: 'order',
                order_id: orderId.value,
                payment_sn: paymentSn.value || undefined
            })
            const status = Number(res?.pay_status ?? res?.payment?.pay_status ?? -1)
            if (status === 1) {
                state.value = 'success'
                loadDetail()
                return
            }
        } catch {
            // 单次失败继续轮询
        }
    }
    // 超时只代表没查到，不代表没付：转「待确认」，不给「失败」
    if (!cancelled && state.value === 'pending') state.value = 'fail'
}

/** 订单摘要与「接下来」：失败只降级区块，不影响已确认的支付结果 */
async function loadDetail() {
    if (!orderId.value) return
    detailLoading.value = true
    detailError.value = false
    try {
        const res: any = await getOrderDetailV2(orderId.value)
        order.value = res?.order || {}
        items.value = Array.isArray(res?.items) ? res.items : []
        pendingQuestionnaire.value = res?.pending_questionnaire || null
        payInfo.value = res?.pay || res?.payment || {}
    } catch (e: any) {
        detailError.value = true
        report('api_error', {
            endpoint: 'order.detail',
            message: e?.message || '支付结果页订单摘要加载失败'
        })
    } finally {
        detailLoading.value = false
    }
}

onLoad((options: any) => {
    orderId.value = Number(options?.order_id || options?.id || 0)
    paymentSn.value = options?.payment_sn ? String(options.payment_sn) : ''
    queryAmount.value = options?.amount ? Number(options.amount) : ''
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
/* ========== 导航左侧关闭按钮（88rpx 热区，其余交给 nav-bar） ========== */
.pnav-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 88rpx;
    height: 88rpx;
}

.rp-wrap {
    padding-top: var(--sp-6);
}

/* ========== 压印印章（全站识别符号，不用通用对勾图标顶替） ========== */
.payok {
    text-align: center;
    padding: 50rpx 42rpx 0;

    &__title {
        display: block;
        margin-top: 31rpx;
        font-size: var(--fs-37);
        font-weight: var(--fw-semibold);
        letter-spacing: 3rpx;
        color: var(--c-ink);
    }

    &__desc {
        display: block;
        margin-top: 17rpx;
        font-size: 20rpx;
        line-height: var(--lh-loose);
        color: var(--c-ink-3);
    }

    &__amt {
        margin-top: 31rpx;
    }

    &__amt-sub {
        display: block;
        margin-top: 10rpx;
        font-size: var(--fs-18);
        letter-spacing: 1rpx;
        color: var(--c-ink-3);
    }
}

.seal {
    width: 123rpx;
    height: 123rpx;
    margin: 0 auto;
    position: relative;
    border-radius: var(--r-pill);
    /* 金色渐变是全站唯一允许的渐变用法（印章），文字/正文一律不用 */
    background: linear-gradient(
        158deg,
        var(--c-brand-soft) 0%,
        var(--c-brand) 46%,
        var(--c-brand-text) 100%
    );
    display: flex;
    align-items: center;
    justify-content: center;
    /* 只播一次：0.86 → 1，220ms */
    animation: seal-in 220ms var(--ease-out) 1 both;

    &__ring {
        position: absolute;
        left: 10rpx;
        right: 10rpx;
        top: 10rpx;
        bottom: 10rpx;
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-on-dark-2);
        opacity: 0.7;
    }

    &__icon {
        color: var(--c-ink);
    }
}

@keyframes seal-in {
    from {
        opacity: 0;
        transform: scale(0.86);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* ========== 卡片 ========== */
.card2 {
    margin: 42rpx 42rpx 0;
    padding: 27rpx 29rpx;
    background: var(--c-surface);
    border: 1rpx solid var(--c-line-strong);
    border-radius: var(--r-md);

    &__head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-bottom: var(--sp-2);
    }

    &__title {
        font-size: 24rpx;
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__more {
        display: inline-flex;
        align-items: center;
        gap: 4rpx;
        padding: 12rpx 0;
        margin: -12rpx 0;
        color: var(--c-ink-3);
    }

    &__more-text {
        font-size: 20rpx;
    }

    &__sk {
        padding: var(--sp-2) 0;
    }

    &__err {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        padding: var(--sp-2) 0;
    }

    &__err-text {
        font-size: var(--fs-22);
        color: var(--c-ink-3);
    }

    &__err-btn {
        padding: 10rpx 18rpx;
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-control-border);
    }

    &__err-btn-text {
        font-size: 20rpx;
        color: var(--c-ink-2);
    }
}

.kv {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);
    padding: 10rpx 0;

    &__k {
        flex: none;
        font-size: var(--fs-22);
        color: var(--c-ink-3);
    }

    &__v {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-22);
        color: var(--c-ink);
        text-align: right;
    }
}

/* ========== 接下来（时间轴） ========== */
.nx {
    display: flex;
    align-items: flex-start;
    gap: 18rpx;
    padding: 17rpx 0;
    border-bottom: 1rpx solid var(--c-line);

    &:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    &__idx {
        flex: none;
        width: 32rpx;
        height: 32rpx;
        margin-top: 2rpx;
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-primary-border);
        background: var(--c-brand-bg);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__idx-text {
        font-family: var(--font-serif);
        font-size: var(--fs-18);
        color: var(--c-brand-text);
        line-height: var(--lh-none);
    }

    &__body {
        flex: 1;
        min-width: 0;
    }

    &__title {
        display: block;
        font-size: var(--fs-22);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__desc {
        display: block;
        margin-top: 6rpx;
        font-size: var(--fs-18);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__when {
        flex: none;
        padding-top: 4rpx;
        font-family: var(--font-serif);
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }
}
</style>
