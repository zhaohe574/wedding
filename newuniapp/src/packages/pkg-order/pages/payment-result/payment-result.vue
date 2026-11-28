<template>
    <page-shell
        bg="page"
        :bottom-bar="true"
        title="支付结果"
        nav-variant="solid"
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
                action-text="查看订单"
                show-action
                @action="goOrder"
            />
        </view>

        <!-- 支付成功：黑金定档礼笺（深墨仪式头 + 撕口 + 暖白收据） -->
        <view v-else-if="displayState === 'success'" class="pay">
            <view class="receipt">
                <view class="receipt__head">
                    <text class="receipt__eyebrow">PAYMENT CONFIRMED</text>

                    <view class="seal" aria-hidden="true">
                        <view class="seal__ring" />
                        <tn-icon name="check" :size="44" class="seal__icon" />
                    </view>

                    <text class="receipt__title">支付成功</text>
                    <text class="receipt__desc">{{ successDesc }}</text>

                    <!-- 档期铭牌：只认真实 service_date，异常退款时不展示 -->
                    <view v-if="lockPillText" class="receipt__lock">
                        <tn-icon name="calendar" :size="24" class="receipt__lock-ic" />
                        <text class="receipt__lock-text">{{ lockPillText }}</text>
                    </view>
                </view>

                <!-- 撕口：左右半圆缺口 + 虚线，保留可留存的礼券质感 -->
                <view class="receipt__tear" aria-hidden="true">
                    <view class="receipt__notch receipt__notch--l" />
                    <view class="receipt__tear-dash" />
                    <view class="receipt__notch receipt__notch--r" />
                </view>

                <view class="receipt__body">
                    <view class="receipt__amount">
                        <view class="receipt__amount-head">
                            <text class="receipt__amount-label">本次实付</text>
                            <status-tag
                                :label="payException ? '退款处理中' : '已入账'"
                                :tone="payException ? 'warning' : 'success'"
                                size="sm"
                            />
                        </view>
                        <view v-if="amountText !== null" class="receipt__price">
                            <price
                                size="xl"
                                :content="amountText"
                                :trim-zeros="false"
                            />
                        </view>
                        <text v-if="amountSubText" class="receipt__amount-sub">{{
                            amountSubText
                        }}</text>
                    </view>

                    <!-- 定金模式才有款项进度：已付 / 待付尾款 / 总额 -->
                    <view v-if="showMetrics" class="receipt__metrics">
                        <view class="metric">
                            <text class="metric__k">已付金额</text>
                            <text class="metric__v t-num">{{ paidMetric }}</text>
                        </view>
                        <view class="metric">
                            <text class="metric__k">待付尾款</text>
                            <text class="metric__v t-num">{{ dueMetric }}</text>
                        </view>
                        <view class="metric">
                            <text class="metric__k">订单总额</text>
                            <text class="metric__v t-num">{{ totalMetric }}</text>
                        </view>
                    </view>

                    <view class="receipt__detail">
                        <view class="receipt__detail-head">
                            <text class="receipt__detail-title">订单凭证</text>
                            <view class="receipt__more" hover-class="is-pressed" @tap="goOrder">
                                <text class="receipt__more-text">查看详情</text>
                                <tn-icon name="right" :size="20" class="receipt__more-ic" />
                            </view>
                        </view>

                        <!-- 摘要失败只降级本区块，绝不影响已确认的支付结果 -->
                        <view v-if="detailLoading" class="receipt__sk">
                            <base-skeleton :rows="4" variant="text" />
                        </view>
                        <view v-else-if="detailError" class="receipt__err">
                            <text class="receipt__err-text">订单摘要没取到</text>
                            <view class="receipt__err-btn" hover-class="is-pressed" @tap="loadDetail">
                                <text class="receipt__err-btn-text">重新加载</text>
                            </view>
                        </view>
                        <view v-else class="rcpt">
                            <view class="rcpt__row">
                                <text class="rcpt__k">订单编号</text>
                                <view class="rcpt__vwrap">
                                    <text class="rcpt__v t-num">{{ orderSnText || '—' }}</text>
                                    <view
                                        v-if="orderSnText"
                                        class="copy"
                                        hover-class="is-pressed"
                                        aria-role="button"
                                        aria-label="复制订单编号"
                                        @tap="copyOrderSn"
                                    >
                                        <text class="copy__text">复制</text>
                                    </view>
                                </view>
                            </view>

                            <view v-if="serviceDateText" class="rcpt__row">
                                <text class="rcpt__k">服务档期</text>
                                <text class="rcpt__v t-num">{{ serviceDateText }}</text>
                            </view>

                            <view v-if="teamText" class="rcpt__row">
                                <text class="rcpt__k">服务团队</text>
                                <text class="rcpt__v">{{ teamText }}</text>
                            </view>

                            <view v-if="venueText" class="rcpt__row">
                                <text class="rcpt__k">举办场地</text>
                                <text class="rcpt__v">{{ venueText }}</text>
                            </view>

                            <view class="rcpt__row">
                                <text class="rcpt__k">支付时间</text>
                                <text class="rcpt__v t-num">{{ payTimeText || '—' }}</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <!-- 档期锁失败自动退款：真实异常字段才出现 -->
            <view v-if="payException" class="exc tone-warning">
                <tn-icon name="tips" :size="30" class="exc__ic" />
                <view class="exc__body">
                    <text class="exc__text">{{ exceptionDesc }}</text>
                    <text v-if="refundId" class="exc__meta">退款单号 {{ refundId }}</text>
                </view>
            </view>

            <!-- 接下来：只拼真实字段，凑不出两项就不渲染 -->
            <view v-if="nextSteps.length >= 2" class="steps-card">
                <view class="steps-card__head">
                    <text class="steps-card__title">接下来</text>
                </view>

                <view class="tl">
                    <view
                        v-for="(s, i) in nextSteps"
                        :key="s.key"
                        class="tl__i"
                        :class="`is-${s.state}`"
                        :hover-class="s.clickable ? 'is-pressed' : 'none'"
                        @tap="onStepTap(s)"
                    >
                        <view class="tl__rail">
                            <view class="tl__dot" />
                            <view v-if="i < nextSteps.length - 1" class="tl__line" />
                        </view>

                        <view class="tl__body">
                            <text class="tl__title">{{ s.title }}</text>
                            <text v-if="s.desc" class="tl__desc">{{ s.desc }}</text>
                        </view>

                        <status-tag
                            v-if="s.state === 'done'"
                            label="已完成"
                            tone="success"
                            size="sm"
                        />
                        <view v-else-if="s.clickable" class="tl__cta">
                            <text class="tl__cta-text">去填写</text>
                            <tn-icon name="right" :size="18" class="tl__cta-ic" />
                        </view>
                        <text v-else class="tl__when">{{ s.when }}</text>
                    </view>
                </view>
            </view>
        </view>

        <!-- 确认中 / 待确认：同源暖白仪式卡（不再用会撑破首屏的 result-page） -->
        <template v-else>
            <view class="state-card" :class="`state-card--${displayState}`">
                <view class="state-badge">
                    <tn-icon name="clock-fill" :size="60" class="state-badge__ic" />
                </view>

                <text class="state-card__title" aria-live="polite">{{ stateTitle }}</text>
                <text class="state-card__desc">{{ stateDesc }}</text>

                <view v-if="amountText !== null" class="state-card__amt">
                    <price size="xl" :content="amountText" :trim-zeros="false" />
                </view>

                <!-- 确认中：轮询文案 + 6 格进度，传达系统正在积极确认 -->
                <view v-if="displayState === 'pending'" class="poll-pill">
                    <u-loading :size="28" color="var(--c-info)" />
                    <text class="poll-pill__text">{{ pollView.text }}</text>
                    <view class="poll-dots" aria-hidden="true">
                        <view
                            v-for="n in maxPoll"
                            :key="n"
                            class="poll-dots__i"
                            :class="{ 'is-on': n <= pollCount }"
                        />
                    </view>
                </view>

                <!-- 待确认：可复制流水号 + 两条安心指引；不出现失败/重新支付 -->
                <view v-else class="guide">
                    <view v-if="paymentSnText" class="guide__sn">
                        <text class="guide__sn-k">支付流水号</text>
                        <text class="guide__sn-v ellipsis">{{ paymentSnText }}</text>
                        <view
                            class="guide__copy"
                            hover-class="is-pressed"
                            aria-role="button"
                            aria-label="复制支付流水号"
                            @tap="copyPaymentSn"
                        >
                            <text class="guide__copy-text">复制</text>
                        </view>
                    </view>

                    <view class="guide__box">
                        <view class="guide__row">
                            <view class="guide__idx"><text class="guide__idx-text">1</text></view>
                            <text class="guide__text"
                                >微信回调可能延迟数秒，可点底部「{{
                                    FB.ACTION_RECHECK_RESULT
                                }}」再确认。</text
                            >
                        </view>
                        <view class="guide__row">
                            <view class="guide__idx"><text class="guide__idx-text">2</text></view>
                            <text class="guide__text"
                                >账单已扣款请勿重复支付，稍后刷新即可同步。</text
                            >
                        </view>
                    </view>
                </view>
            </view>

            <view class="home-link" hover-class="is-pressed" @tap="goHome">
                <text class="home-link__text">{{ FB.ACTION_HOME }}</text>
            </view>
        </template>

        <template #bottom>
            <bottom-bar v-if="orderId">
                <view class="bbar-pair">
                    <template v-if="displayState === 'success'">
                        <template v-if="isQuestionnaireIssued">
                            <view class="bbar-pair__sub">
                                <base-button
                                    :text="FB.ACTION_VIEW_ORDER"
                                    variant="ghost"
                                    size="lg"
                                    @click="goOrder"
                                />
                            </view>
                            <view class="bbar-pair__main">
                                <base-button
                                    text="填写新人问卷"
                                    variant="ink"
                                    size="lg"
                                    @click="goQuestionnaire"
                                />
                            </view>
                        </template>
                        <template v-else>
                            <view class="bbar-pair__sub">
                                <base-button
                                    :text="FB.ACTION_HOME"
                                    variant="ghost"
                                    size="lg"
                                    @click="goHome"
                                />
                            </view>
                            <view class="bbar-pair__main">
                                <base-button
                                    :text="FB.ACTION_VIEW_ORDER"
                                    variant="ink"
                                    size="lg"
                                    @click="goOrder"
                                />
                            </view>
                        </template>
                    </template>

                    <!-- pending：O2+O14 不让 ink 主按钮抢位 -->
                    <view v-else-if="displayState === 'pending'" class="bbar-pair__full">
                        <base-button
                            :text="FB.ACTION_VIEW_ORDER"
                            variant="ghost"
                            size="lg"
                            block
                            @click="goOrder"
                        />
                    </view>

                    <!-- unconfirmed：主「查看订单」+ 次「刷新结果」 -->
                    <template v-else>
                        <view class="bbar-pair__sub">
                            <base-button
                                :text="FB.ACTION_RECHECK_RESULT"
                                variant="ghost"
                                size="lg"
                                :loading="rechecking"
                                @click="onSecondary"
                            />
                        </view>
                        <view class="bbar-pair__main">
                            <base-button
                                :text="FB.ACTION_VIEW_ORDER"
                                variant="ink"
                                size="lg"
                                @click="goOrder"
                            />
                        </view>
                    </template>
                </view>
            </bottom-bar>
        </template>
    </page-shell>
    <app-toast />
</template>

<script setup lang="ts">
/**
 * 09 支付结果（v9「黑金定档礼笺」）。
 *
 * 铁律：requestPayment 的 success 只代表用户完成了支付动作，订单是否真的支付成功
 * 必须以 /pay/payStatus 轮询为准 —— 微信回调可能晚几秒。
 *
 * 三态（02 §4.9）：
 *  - pending      → 暖白仪式卡 + 6 格轮询进度，底部只给次要按钮「查看订单」
 *  - success      → 黑金礼笺（深墨仪式头 + 撕口 + 暖白收据 + 真实凭证细目）
 *  - unconfirmed  → 只代表「没查到」，不等于失败：无红叉/「失败」字样/「重新支付」，
 *                   给「刷新结果」幂等回查
 *
 * 数据：订单摘要来自 order.getOrderDetailV2，取不到只降级该区块，不影响已确认的支付结果；
 * 凭证字段（支付时间 / 流水号 / 实付金额 / 订单号 / 异常退款）优先取 payStatus 实时返回。
 */
import { computed, ref } from 'vue'
import { onLoad, onUnload } from '@dcloudio/uni-app'
import { getPayStatus } from '@/api/pay'
import { getOrderDetailV2 } from '@/api/order'
import { formatDate, formatMoney, parseDateValue } from '@shared/utils/format'
import { FB, fbPollText } from '@/constants/feedback'
import { toast } from '@/composables/useToast'
import { report } from '@/platform/report'
import { useBadgeStore } from '@/stores/badge'

/** 轮询节奏：1/2/3/5/8/13 秒，总计约 32 秒；前密后疏，避免一上来就打满接口 */
const POLL_INTERVALS = [1, 2, 3, 5, 8, 13]
const maxPoll = POLL_INTERVALS.length
const WEEK_LABELS = ['周日', '周一', '周二', '周三', '周四', '周五', '周六']

type StepState = 'done' | 'now' | 'todo'
interface StepView {
    key: string
    title: string
    desc: string
    state: StepState
    when: string
    clickable?: boolean
}

const orderId = ref(0)
const paymentSn = ref('')
const queryAmount = ref<string | number>('')
const state = ref<'pending' | 'success' | 'fail'>('pending')
const pollCount = ref(0)
const rechecking = ref(false)

const order = ref<any>({})
const items = ref<any[]>([])
const pendingQuestionnaire = ref<any>(null)
const payInfo = ref<any>({})
const detailLoading = ref(false)
const detailError = ref(false)

/* ---- payStatus 实时凭证字段（比订单摘要更及时） ---- */
const paySnFromStatus = ref('')
const payAmountFromStatus = ref<string | number>('')
const payTimeFromStatus = ref('')
const payTypeDescFromStatus = ref('')
const payException = ref(false)
const payExceptionDesc = ref('')
const refundId = ref(0)

let cancelled = false

const badgeStore = useBadgeStore()

const displayState = computed<'success' | 'pending' | 'unconfirmed'>(() =>
    state.value === 'success' ? 'success' : state.value === 'pending' ? 'pending' : 'unconfirmed'
)
const pollView = computed(() => ({
    current: pollCount.value,
    max: maxPoll,
    text: fbPollText(pollCount.value, maxPoll)
}))

/* ---------------- 凭证字段 ---------------- */

function applyPayStatus(res: any) {
    const payment = res?.payment || {}
    const info = res?.order || {}

    const sn = String(payment.payment_sn || info.payment_sn || '')
    if (sn) {
        paySnFromStatus.value = sn
        if (!paymentSn.value) paymentSn.value = sn
    }

    if (payment.pay_amount !== undefined && payment.pay_amount !== null && payment.pay_amount !== '') {
        payAmountFromStatus.value = payment.pay_amount
    }

    const payTime = String(payment.pay_time || info.pay_time || '')
    if (payTime) payTimeFromStatus.value = payTime

    const typeDesc = String(payment.pay_type_desc || '')
    payTypeDescFromStatus.value = typeDesc === '未知' ? '' : typeDesc

    payException.value =
        Number(res?.payment_exception ?? payment.payment_exception ?? info.payment_exception ?? 0) ===
        1
    payExceptionDesc.value = String(
        res?.payment_exception_desc ||
            payment.payment_exception_desc ||
            info.payment_exception_desc ||
            ''
    )
    refundId.value = Number(res?.refund_id ?? payment.refund_id ?? info.refund_id ?? 0) || 0
}

const paymentSnText = computed(() => paySnFromStatus.value || paymentSn.value)
const orderSnText = computed(() => String(order.value?.order_sn || ''))

/** 本次实付：先认 payStatus 的真实流水金额，再回退订单已付 / 摘要 / 路由参数 */
const amountText = computed<string | number | null>(() => {
    const candidates = [
        payAmountFromStatus.value,
        order.value?.paid_amount,
        payInfo.value?.paid_amount,
        payInfo.value?.pay_amount
    ]
    for (const v of candidates) {
        if (v !== undefined && v !== null && v !== '' && Number(v) > 0) return v
    }
    if (queryAmount.value !== '' && queryAmount.value !== null && Number(queryAmount.value) > 0) {
        return queryAmount.value
    }
    return null
})

function deriveStageText() {
    const stage = String(payInfo.value?.current_pay_stage || '')
    const balancePaid = Number(payInfo.value?.balance_paid ?? order.value?.balance_paid ?? 0) === 1
    const depositPaid = Number(payInfo.value?.deposit_paid ?? order.value?.deposit_paid ?? 0) === 1
    const depositAmount = Number(payInfo.value?.deposit_amount ?? order.value?.deposit_amount ?? 0)
    const payStatus = Number(order.value?.pay_status ?? -1)
    if (balancePaid && depositAmount > 0) return '尾款'
    if (depositPaid && (payStatus === 0 || stage === 'balance_after_service' || stage === 'balance')) {
        return '定金'
    }
    if (stage === 'paid' || payStatus === 1) return depositAmount > 0 ? '尾款' : '全款'
    if (stage === 'deposit') return '定金'
    if (stage === 'full') return '全款'
    if (stage === 'balance' || stage === 'balance_after_service') return '尾款'
    return ''
}

const amountSubText = computed(() => {
    const stageText = payTypeDescFromStatus.value || deriveStageText()
    return [stageText, '微信支付'].filter(Boolean).join(' · ')
})

/* ---------------- 文案 ---------------- */

const activeItems = computed(() =>
    items.value.filter((it: any) => Number(it?.item_status ?? 0) !== 3)
)

const hostName = computed(() => String(activeItems.value[0]?.staff?.name || ''))

const successDesc = computed(() => {
    if (payException.value) return '款项已收到，档期锁定异常，系统已自动发起原路退款'
    if (hostName.value) return `款项已入账，主持人${hostName.value}已收到通知`
    return '款项已入账，我们会按约定为你筹备婚礼'
})

const exceptionDesc = computed(
    () => payExceptionDesc.value || '支付已收到，但档期锁定失败，退款处理中。'
)

const stateTitle = computed(() =>
    displayState.value === 'pending' ? FB.PAY_PENDING_TITLE : FB.PAY_UNCONFIRMED_TITLE
)
const stateDesc = computed(() =>
    displayState.value === 'pending' ? FB.PAY_PENDING_DESC : FB.PAY_UNCONFIRMED_DESC
)

/* ---------------- 凭证细目 ---------------- */

const serviceDate = computed(() => parseDateValue(order.value?.service_date))
const serviceDateText = computed(() => {
    const d = serviceDate.value
    if (!d) return ''
    return `${formatDate(d, 'YYYY.MM.DD')} ${WEEK_LABELS[d.getDay()]}`
})
const lockPillText = computed(() =>
    serviceDate.value && !payException.value ? `已锁档 · ${serviceDateText.value}` : ''
)

const teamText = computed(() => {
    const first = activeItems.value[0]
    if (!first) return ''
    const base = [first?.staff?.name, first?.package?.name].filter(Boolean).join(' · ')
    if (!base) return ''
    const count = activeItems.value.length
    return count > 1 ? `${base} 等 ${count} 项` : base
})

const venueText = computed(() => String(order.value?.address || ''))

const payTimeText = computed(() => {
    const raw = payTimeFromStatus.value || order.value?.pay_time || ''
    const d = parseDateValue(raw)
    return d ? formatDate(d, 'YYYY.MM.DD HH:mm') : ''
})

function moneyText(v: any) {
    return `¥${formatMoney(v, { thousand: true })}`
}

const paidAmountNum = computed(() =>
    Number(order.value?.paid_amount ?? payInfo.value?.paid_amount ?? payAmountFromStatus.value ?? 0)
)
const totalAmountNum = computed(() =>
    Number(order.value?.total_amount ?? payInfo.value?.pay_amount ?? 0)
)
const dueAmountNum = computed(() => Number(payInfo.value?.need_pay_amount ?? 0))
const depositMode = computed(
    () => Number(payInfo.value?.deposit_amount ?? order.value?.deposit_amount ?? 0) > 0
)
const showMetrics = computed(
    () => depositMode.value && totalAmountNum.value > 0 && dueAmountNum.value > 0
)
const paidMetric = computed(() => moneyText(paidAmountNum.value))
const dueMetric = computed(() => moneyText(dueAmountNum.value))
const totalMetric = computed(() => moneyText(totalAmountNum.value))

/** 新人问卷是否已正式发放给用户（仅已发放或推送兜底可见态才允许点击填写） */
const isQuestionnaireIssued = computed(() => {
    if (payException.value || !pendingQuestionnaire.value) return false
    const q = pendingQuestionnaire.value
    if (q.is_issued !== undefined && q.is_issued !== null) {
        return Number(q.is_issued) === 1
    }
    const sendStatus = Number(q.send_status ?? 0)
    return sendStatus === 1 || sendStatus === 2
})

/** 接下来：只拼真实字段，凑不出 2 项就不渲染（宁可少显示，不编日期） */
const nextSteps = computed<StepView[]>(() => {
    const list: StepView[] = []

    if (!payException.value) {
        list.push({
            key: 'paid',
            title: '完成支付并锁定档期',
            desc: '',
            state: 'done',
            when: ''
        })
    }

    if (pendingQuestionnaire.value && !payException.value) {
        if (isQuestionnaireIssued.value) {
            list.push({
                key: 'questionnaire',
                title: '填写新人问卷',
                desc: '仪式偏好、迎亲细节与音乐流程',
                state: 'now',
                when: '',
                clickable: true
            })
        } else {
            list.push({
                key: 'questionnaire_pending',
                title: '新人问卷',
                desc: pendingQuestionnaire.value.planned_send_time
                    ? `预计 ${pendingQuestionnaire.value.planned_send_time} 自动发放`
                    : '临近婚期自动发放',
                state: 'todo',
                when: '待发放问卷',
                clickable: false
            })
        }
    }

    const needPay = Number(payInfo.value?.need_pay_amount ?? 0)
    const stage = String(payInfo.value?.current_pay_stage || '')
    if (needPay > 0 && stage !== 'paid') {
        list.push({
            key: 'balance',
            title: `支付尾款 ${moneyText(needPay)}`,
            desc: '按订单约定的开放时间支付',
            state: 'todo',
            when: '待开放'
        })
    }

    if (serviceDate.value) {
        list.push({
            key: 'ceremony',
            title: '婚礼当天',
            desc: '',
            state: 'todo',
            when: formatDate(serviceDate.value, 'MM.DD')
        })
    }

    return list
})

/* ---------------- 动作 ---------------- */

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

function goQuestionnaire() {
    if (!isQuestionnaireIssued.value || !pendingQuestionnaire.value) return
    uni.navigateTo({
        url: `/packages/pkg-service/pages/questionnaire/detail?id=${pendingQuestionnaire.value.id}`
    })
}

function onStepTap(step: StepView) {
    if (step.clickable && step.key === 'questionnaire') goQuestionnaire()
}

function copyOrderSn() {
    if (!orderSnText.value) return
    uni.setClipboardData({
        data: orderSnText.value,
        success: () => toast.info('已复制订单号')
    })
}

function copyPaymentSn() {
    if (!paymentSnText.value) return
    uni.setClipboardData({
        data: paymentSnText.value,
        success: () => toast.info('已复制支付流水号')
    })
}

/** unconfirmed 的次操作「刷新结果」= 幂等回查，不发起 prepay（避免重复扣款） */
async function onSecondary() {
    if (state.value === 'pending') {
        goOrder()
        return
    }
    if (rechecking.value) return
    rechecking.value = true
    try {
        const now = await queryPayState()
        if (now === 'paid') {
            state.value = 'success'
            // 支付成功后待支付数减少，标脏让角标立即回源
            badgeStore.invalidate()
            loadDetail()
            toast.info(FB.PAY_ALREADY_PAID)
        } else if (now === 'unknown') {
            toast.error(FB.PAY_RECHECK_UNKNOWN)
        } else {
            toast.info(FB.PAY_UNCONFIRMED_TITLE)
        }
    } finally {
        rechecking.value = false
    }
}

async function queryPayState(): Promise<'paid' | 'unpaid' | 'unknown'> {
    try {
        const res: any = await getPayStatus({
            from: 'order',
            order_id: orderId.value,
            payment_sn: paymentSn.value || undefined
        })
        applyPayStatus(res)
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
        const now = await queryPayState()
        if (now === 'paid') {
            state.value = 'success'
            // 轮询确认支付成功同样要刷新角标
            badgeStore.invalidate()
            loadDetail()
            return
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

.pay {
    padding: var(--sp-3) var(--page-gutter) 0;
}

/* ========== 主礼笺：深墨黑金仪式头 + 撕口 + 暖白收据 ========== */
.receipt {
    /* v10 §2.1：卡片描边与暖影统一走变量（浅色透明、暗色 --c-line） */
    border: 1rpx solid var(--c-card-border);
    border-radius: var(--r-lg);
    box-shadow: var(--shadow-card);
    overflow: hidden;
    background: var(--c-surface);

    &__head {
        padding: var(--sp-5) var(--sp-4) var(--sp-4);
        text-align: center;
        background: linear-gradient(
            155deg,
            var(--c-dark-1) 0%,
            var(--c-dark-2) 58%,
            var(--c-dark-3) 100%
        );
    }

    &__eyebrow {
        display: block;
        font-family: var(--font-serif);
        font-size: var(--fs-20);
        letter-spacing: 4rpx;
        color: var(--c-brand-soft);
    }

    &__title {
        display: block;
        margin-top: var(--sp-3);
        font-size: var(--fs-2xl);
        font-weight: var(--fw-semibold);
        letter-spacing: 2rpx;
        line-height: var(--lh-tight);
        color: var(--c-on-dark);
    }

    &__desc {
        display: block;
        margin-top: var(--sp-2);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-on-dark-2);
    }

    &__lock {
        display: inline-flex;
        align-items: center;
        gap: var(--sp-1);
        margin-top: var(--sp-3);
        padding: var(--sp-1) var(--sp-3);
        border: 1rpx solid var(--c-primary-border);
        border-radius: var(--r-pill);
    }

    &__lock-ic {
        color: var(--c-brand-soft);
    }

    &__lock-text {
        font-size: var(--fs-xs);
        line-height: var(--lh-none);
        color: var(--c-brand-soft);
    }

    &__tear {
        position: relative;
        display: flex;
        align-items: center;
        height: 32rpx;
        background: var(--c-surface);
    }

    &__notch {
        position: absolute;
        top: 4rpx;
        width: 24rpx;
        height: 24rpx;
        border-radius: var(--r-pill);
        background: var(--c-bg);
        border: 1rpx solid var(--c-line-strong);

        &--l {
            left: -13rpx;
        }

        &--r {
            right: -13rpx;
        }
    }

    &__tear-dash {
        width: calc(100% - 56rpx);
        margin: 0 auto;
        border-top: 1rpx dashed var(--c-line-strong);
    }

    &__body {
        padding: var(--sp-3) var(--sp-4) var(--sp-4);
        background: var(--c-surface);
    }

    &__amount-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__amount-label {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        letter-spacing: 1rpx;
    }

    &__price {
        display: block;
        margin-top: var(--sp-1);
    }

    &__amount-sub {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__metrics {
        display: flex;
        margin-top: var(--sp-3);
        padding: var(--sp-3);
        border-radius: var(--r-md);
        background: var(--c-surface-2);
    }

    &__detail {
        margin-top: var(--sp-4);
        padding-top: var(--sp-3);
        border-top: 1rpx solid var(--c-line);
    }

    &__detail-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-bottom: var(--sp-1);
    }

    &__detail-title {
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        letter-spacing: 1rpx;
        color: var(--c-ink);
    }

    &__more {
        display: inline-flex;
        align-items: center;
        gap: 4rpx;
        padding: 12rpx 0;
        margin: -12rpx 0;
    }

    &__more-text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__more-ic {
        color: var(--c-ink-3);
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
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__err-btn {
        padding: 10rpx var(--sp-3);
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-control-border);
    }

    &__err-btn-text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }
}

/* ========== 压印香槟金双环圆印（全站识别符号，只播一次） ========== */
.seal {
    position: relative;
    width: 112rpx;
    height: 112rpx;
    margin: var(--sp-4) auto 0;
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

/* ========== 款项进度三栏 ========== */
.metric {
    flex: 1;
    min-width: 0;
    text-align: center;

    &__k {
        display: block;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__v {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        font-variant-numeric: tabular-nums;
    }
}

/* ========== 凭证 KV ========== */
.rcpt {
    &__row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: var(--sp-3);
        padding: 10rpx 0;
    }

    &__k {
        flex: none;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__vwrap {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: var(--sp-1);
    }

    &__v {
        flex: 1;
        min-width: 0;
        text-align: right;
        font-size: var(--fs-md);
        line-height: var(--lh-body);
        color: var(--c-ink);
        word-break: break-all;
    }
}

.copy {
    position: relative;
    flex: none;
    display: inline-flex;
    align-items: center;
    padding: 8rpx var(--sp-2);
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-control-border);

    /* 保持轻量视觉，纵向扩展至 88rpx 独立热区 */
    &::before {
        content: '';
        position: absolute;
        top: -24rpx;
        bottom: -24rpx;
        left: 0;
        right: 0;
    }

    &__text {
        font-size: var(--fs-xs);
        line-height: var(--lh-none);
        color: var(--c-ink-2);
    }
}

/* ========== 异常补偿提示 ========== */
.exc {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-2);
    margin-top: var(--sp-3);
    padding: var(--sp-3);
    border-radius: var(--r-md);

    &__ic {
        flex: none;
        color: var(--c-warning);
    }

    &__body {
        flex: 1;
        min-width: 0;
    }

    &__text {
        display: block;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
    }

    &__meta {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
    }
}

/* ========== 接下来（时间轴） ========== */
.steps-card {
    margin-top: var(--sp-3);
    padding: var(--sp-4);
    background: var(--c-surface);
    border: 1rpx solid var(--c-card-border);
    border-radius: var(--r-md);
    box-shadow: var(--shadow-card);

    &__head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-bottom: var(--sp-3);
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }
}

.tl {
    &__i {
        display: flex;
        align-items: flex-start;
        gap: var(--sp-3);
    }

    &__rail {
        flex: none;
        width: 20rpx;
        align-self: stretch;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    &__dot {
        width: 20rpx;
        height: 20rpx;
        margin-top: 6rpx;
        border-radius: var(--r-pill);
        box-sizing: border-box;
    }

    &__line {
        flex: 1;
        width: 2rpx;
        background: var(--c-line);
        margin: 4rpx 0;
    }

    &__body {
        flex: 1;
        min-width: 0;
        padding-bottom: var(--sp-4);
    }

    &__i:last-child &__body {
        padding-bottom: 0;
    }

    &__title {
        display: block;
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        line-height: var(--lh-tight);
        color: var(--c-ink);
    }

    &__desc {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__when {
        flex: none;
        padding-top: 4rpx;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__cta {
        flex: none;
        display: inline-flex;
        align-items: center;
        gap: 4rpx;
        padding: 8rpx var(--sp-2);
        border-radius: var(--r-pill);
        background: var(--c-action-bg);
        color: var(--c-action-fg);
    }

    &__cta-text {
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        line-height: var(--lh-none);
        color: var(--c-action-fg);
    }

    &__cta-ic {
        color: var(--c-action-fg);
    }

    /* 已完成：实心香槟金；进行中：墨黑圆环；待办：细描边空心 */
    &__i.is-done &__dot {
        background: var(--c-brand);
        border: 1rpx solid var(--c-brand-text);
    }

    &__i.is-now &__dot {
        background: var(--c-surface);
        border: 4rpx solid var(--c-ink);
    }

    &__i.is-todo &__dot {
        background: var(--c-surface);
        border: 2rpx solid var(--c-line-strong);
    }
}

/* ========== 确认中 / 待确认：同源暖白仪式卡 ========== */
.state-card {
    margin: var(--sp-4) var(--page-gutter) 0;
    padding: var(--sp-6) var(--sp-4) var(--sp-5);
    background: var(--c-surface);
    border: 1rpx solid var(--c-card-border);
    border-radius: var(--r-lg);
    box-shadow: var(--shadow-card);
    text-align: center;

    &__title {
        display: block;
        margin-top: var(--sp-4);
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        letter-spacing: 1rpx;
        line-height: var(--lh-tight);
        color: var(--c-ink);
    }

    &__desc {
        display: block;
        margin-top: var(--sp-2);
        font-size: var(--fs-xs);
        line-height: var(--lh-loose);
        color: var(--c-ink-3);
    }

    &__amt {
        margin-top: var(--sp-4);
        padding: var(--sp-3) var(--sp-4);
        border-radius: var(--r-md);
        background: var(--c-surface-2);
    }
}

.state-badge {
    position: relative;
    width: 128rpx;
    height: 128rpx;
    margin: 0 auto;
    border-radius: var(--r-pill);
    background: var(--c-info-bg);
    border: 1rpx solid var(--c-info-border);
    color: var(--c-info);
    display: flex;
    align-items: center;
    justify-content: center;

    &__ic {
        color: var(--c-info);
    }
}

/* 确认中：外圈呼吸描边，传达「系统正在积极确认」 */
.state-card--pending .state-badge::after {
    content: '';
    position: absolute;
    left: -12rpx;
    right: -12rpx;
    top: -12rpx;
    bottom: -12rpx;
    border-radius: var(--r-pill);
    border: 2rpx solid var(--c-info-border);
    animation: pulse-ring 2.2s var(--ease-out) infinite;
}

@keyframes pulse-ring {
    0% {
        opacity: 0.55;
        transform: scale(1);
    }
    70% {
        opacity: 0;
        transform: scale(1.12);
    }
    100% {
        opacity: 0;
        transform: scale(1.12);
    }
}

.poll-pill {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--sp-2);
    margin-top: var(--sp-4);
    padding: var(--sp-2) var(--sp-4);
    border-radius: var(--r-pill);
    background: var(--c-info-bg);
    border: 1rpx solid var(--c-info-border);

    &__text {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
    }
}

.poll-dots {
    flex: none;
    display: flex;
    align-items: center;
    gap: 6rpx;

    &__i {
        width: 10rpx;
        height: 10rpx;
        border-radius: var(--r-pill);
        background: var(--c-info-border);
    }

    &__i.is-on {
        background: var(--c-info);
    }
}

.guide {
    margin-top: var(--sp-4);
    text-align: left;

    &__sn {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
        padding: var(--sp-2) var(--sp-3);
        border: 1rpx solid var(--c-card-border);
        border-radius: var(--r-md);
    }

    &__sn-k {
        flex: none;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__sn-v {
        flex: 1;
        min-width: 0;
        font-family: var(--font-serif);
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__copy {
        position: relative;
        flex: none;
        padding: 8rpx var(--sp-2);
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-control-border);
    }

    &__copy::before {
        content: '';
        position: absolute;
        top: -24rpx;
        bottom: -24rpx;
        left: 0;
        right: 0;
    }

    &__copy-text {
        font-size: var(--fs-xs);
        line-height: var(--lh-none);
        color: var(--c-ink-2);
    }

    &__box {
        margin-top: var(--sp-2);
        padding: var(--sp-3);
        border-radius: var(--r-md);
        background: var(--c-surface-2);
    }

    &__row {
        display: flex;
        align-items: flex-start;
        gap: var(--sp-2);
    }

    &__row + &__row {
        margin-top: var(--sp-2);
    }

    &__idx {
        flex: none;
        width: 32rpx;
        height: 32rpx;
        border-radius: var(--r-pill);
        background: var(--c-surface);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__idx-text {
        font-size: var(--fs-xs);
        line-height: var(--lh-none);
        color: var(--c-ink-3);
    }

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
    }
}

.home-link {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 88rpx;
    margin: var(--sp-2) var(--page-gutter) 0;

    &__text {
        font-size: var(--fs-md);
        letter-spacing: 1rpx;
        color: var(--c-ink-2);
    }
}

/* ========== 底部双按钮 ========== */
.bbar-pair {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    /* bottom-bar 未传 #actions 时，空 __actions 节点仍占用 --sp-3 的 flex gap；补回该宽度让左右边距对称 */
    width: calc(100% + var(--sp-3));

    &__sub {
        flex: 1;
        min-width: 0;
    }

    &__main {
        flex: 1.4;
        min-width: 0;
    }

    &__full {
        flex: 1;
        min-width: 0;
    }
}
</style>
