<template>
    <!--
        23 售后申请（设计稿 4225–4335）
        设计意图：把「要不算了」变成一次有回音的正规申请——讲清要填什么、多久有结果、会不会被扣钱。

        三条硬口径（读后端源码得出，不要按直觉改）：
        1. 资格不自己算：改期看 `checkCanChange.can_change`（+ message 直接上墙），
           退款看 `can_user_refund` 且 `refundable_amount > 0` 且 order_status ∈ {2,3}
        2. 退款**不出现可退金额输入框**，金额由服务端算；只读展示 `refundable_amount`
        3. 改期接口只收**一个** new_date，所以新档期是单选；备选日期写进说明里
    -->
    <page-shell title="售后申请" bg="page" :show-back="true" :bottom-bar="showBottomBar">
        <view v-if="loading" class="pad">
            <base-skeleton :rows="5" />
        </view>

        <base-empty
            v-else-if="error"
            status="error"
            :error-kind="view?.errorKind || 'unknown'"
            :error-text="view?.title"
            :desc="view?.desc"
            :primary-action="view?.primaryAction"
            fixed
            @primary="onPrimary"
            @retry="load"
        />

        <!-- 没有订单：先选单，不在没有上下文的情况下让用户填表 -->
        <base-empty
            v-else-if="!orderId"
            status="empty"
            empty-text="请先选择要申请的订单"
            action-text="去我的订单"
            :show-action="true"
            fixed
            @action="goOrders"
        />

        <view v-else class="tc-body">
            <!-- 订单摘要：一行的成本，避免「申请错单」 -->
            <view class="ord">
                <image
                    v-if="order.staff_avatar"
                    class="ord__av"
                    :src="imgUrl(order.staff_avatar)"
                    mode="aspectFill"
                />
                <view v-else class="ord__av ord__av--txt">
                    <text class="ord__av-text">{{ (order.staff_name || '·').slice(0, 1) }}</text>
                </view>
                <view class="ord__t">
                    <text class="ord__title">{{ orderTitle }}</text>
                    <text class="ord__sub">{{ orderSub }}</text>
                </view>
                <view v-if="paidDeposit > 0" class="ord__amt">
                    <text class="ord__amt-label">已付定金</text>
                    <text class="ord__amt-value">¥{{ money(paidDeposit) }}</text>
                </view>
            </view>

            <!-- 改期 / 退款：同一张订单、一段说明、一次审核 -->
            <view class="seg">
                <view
                    v-for="m in modes"
                    :key="m.key"
                    class="seg__item"
                    :class="{ 'is-on': mode === m.key, 'is-off': !m.enabled }"
                    :hover-class="m.enabled ? 'is-pressed' : 'none'"
                    :hover-start-time="20"
                    :hover-stay-time="70"
                    :hover-stop-propagation="true"
                    @tap="switchMode(m)"
                >
                    <text class="seg__text">{{ m.label }}</text>
                </view>
            </view>

            <!-- 不可用原因：直接用后端 message，不自己编 -->
            <view v-if="blockReason" class="note note--warn">
                <tn-icon name="tips" :size="22" />
                <text class="note__text">{{ blockReason }}</text>
            </view>

            <!-- 改期表单 -->
            <view v-if="mode === 'change'" class="card">
                <text class="card__h">新档期</text>

                <view class="kv">
                    <text class="kv__k">当前档期</text>
                    <view class="kv__v-row">
                        <text class="kv__v">{{ dateText(order.service_date) || '—' }}</text>
                        <text v-if="order.service_date" class="kv__tag">已锁定</text>
                    </view>
                </view>

                <view class="kv kv--col">
                    <view class="kv__row">
                        <text class="kv__k">期望新档期</text>
                    </view>
                    <picker
                        mode="date"
                        :value="form.newDate || minDateStr"
                        :start="minDateStr"
                        end="2099-12-31"
                        @change="onPickDate"
                    >
                        <view
                            class="picker"
                            hover-class="is-pressed"
                            :hover-start-time="20"
                            :hover-stay-time="70"
                            :hover-stop-propagation="true"
                        >
                            <text class="picker__text" :class="{ 'is-ph': !form.newDate }">
                                {{ form.newDate ? dateText(form.newDate) : '选择新的服务日期' }}
                            </text>
                            <tn-icon name="calendar" :size="24" />
                        </view>
                    </picker>
                </view>

                <view class="kv kv--col kv--last">
                    <text class="kv__k">改期原因</text>
                    <view class="chips">
                        <view
                            v-for="r in CHANGE_REASONS"
                            :key="r"
                            class="chip"
                            :class="{ 'is-on': form.changeReason === r }"
                            hover-class="is-pressed"
                            :hover-start-time="20"
                            :hover-stay-time="70"
                            :hover-stop-propagation="true"
                            @tap="form.changeReason = r"
                        >
                            <text class="chip__text">{{ r }}</text>
                        </view>
                    </view>
                </view>
            </view>

            <!-- 退款表单 -->
            <view v-else class="card">
                <text class="card__h">退款原因</text>
                <view class="chips">
                    <view
                        v-for="r in REFUND_REASONS"
                        :key="r"
                        class="chip"
                        :class="{ 'is-on': form.refundReason === r }"
                        hover-class="is-pressed"
                        :hover-start-time="20"
                        :hover-stay-time="70"
                        :hover-stop-propagation="true"
                        @tap="form.refundReason = r"
                    >
                        <text class="chip__text">{{ r }}</text>
                    </view>
                </view>

                <view v-if="refundable > 0" class="kv kv--last">
                    <text class="kv__k">可退金额</text>
                    <text class="kv__v kv__v--strong">¥{{ money(refundable) }}</text>
                </view>
            </view>

            <!-- 说明与凭证 -->
            <view class="card">
                <text class="card__h">说明与凭证</text>
                <view class="ta-wrap">
                    <form-field
                        label="说明"
                        type="textarea"
                        layout="stack"
                        :maxlength="200"
                        count
                        :placeholder="
                            mode === 'change'
                                ? '请说明改期原因，备选日期可写进说明，至少 10 字'
                                : '请说明退款原因，至少 10 字'
                        "
                        :model-value="form.remark"
                        @update:model-value="(v: string) => (form.remark = v)"
                    />
                </view>
                <form-field
                    label="上传图片"
                    type="upload"
                    layout="stack"
                    :max="10"
                    hint="选填，最多 10 张。如场地变更通知、家庭沟通记录"
                    :model-value="form.images"
                    @update:model-value="(v: string[]) => (form.images = v)"
                />
            </view>

            <view class="note">
                <text class="note__b">提交之后：</text>
                <text class="note__text">
                    顾问会在 24
                    小时内与你确认，审核结果通过服务号通知。审核期间原档期仍然保留，不会二次售出。
                </text>
            </view>

            <view class="note note--plain">
                <text class="note__text">
                    {{
                        mode === 'change'
                            ? '改期是否产生费用，以《售后服务规则》与合同约定为准；若新档期价格不同，差额由顾问确认后告知，不会直接从定金里扣。'
                            : '退款金额由系统按合同与已履约进度计算，以顾问确认为准；到账时间以支付渠道为准。'
                    }}
                </text>
            </view>
        </view>

        <!-- 吸底提交栏 -->
        <template #bottom>
            <bottom-bar v-if="showBottomBar">
                <view class="bar__left">
                    <text class="bar__label">{{ mode === 'change' ? '改期后' : '退款金额' }}</text>
                    <text class="bar__value">{{ barValue }}</text>
                </view>
                <template #actions>
                    <view
                        class="bar__btn"
                        :class="{ 'is-off': !canSubmit }"
                        hover-class="is-pressed"
                        :hover-start-time="20"
                        :hover-stay-time="70"
                        :hover-stop-propagation="true"
                        aria-role="button"
                        @tap="onSubmit"
                    >
                        <text class="bar__btn-text">{{
                            submitting ? '提交中…' : '提交申请'
                        }}</text>
                    </view>
                </template>
            </bottom-bar>
        </template>
    </page-shell>
    <app-toast />
</template>

<script setup lang="ts">
/**
 * 23 售后申请。
 *
 * 数据来源分两路，不能混：
 *  - 资格与金额：v1 `/order/detail`（`can_user_refund` / `refundable_amount` 只在 v1 根层）
 *  - 改期资格：`/order_change/checkCanChange`（`can_change` + `message`）
 * 退款提交 `order/applyRefund { id, reason }`，reason 用 buildRefundReason 拼（上限 255）。
 */
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getOrderDetail } from '@/api/order'
import { checkCanChange, applyDateChange } from '@/api/change'
import { applyRefund, buildRefundReason } from '@/api/aftersale'
import { navigate } from '@/utils/link'
import { toast } from '@/composables/useToast'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { remindBeforeOaAction } from '@/utils/oa-reminder'
import { useAppStore } from '@/stores/app'
import { report } from '@/platform/report'

const appStore = useAppStore()

const CHANGE_REASONS = ['家庭原因', '工作冲突', '场地变更', '健康状况', '其他']
const REFUND_REASONS = ['计划有变', '服务不符预期', '重复下单', '其他']

const orderId = ref(0)
const order = ref<any>({})
const loading = ref(true)
const rawError = ref<unknown>(null)
const error = computed(() => !!rawError.value)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: true }))
const showBottomBar = computed(() => Boolean(orderId.value && !loading.value && !error.value))

const mode = ref<'change' | 'refund'>('change')
const canChange = ref(false)
const changeMessage = ref('')
const submitting = ref(false)

const form = reactive({
    newDate: '',
    changeReason: '',
    refundReason: '',
    remark: '',
    images: [] as string[]
})

const minDateStr = computed(() => {
    const d = new Date()
    d.setDate(d.getDate() + 1)
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(
        d.getDate()
    ).padStart(2, '0')}`
})

const paidDeposit = computed(() =>
    Number(order.value.deposit_amount || order.value.paid_amount || 0)
)
const refundable = computed(() => Number(order.value.refundable_amount || 0))
const canRefund = computed(() => {
    const st = Number(order.value.order_status)
    const flag = order.value.can_user_refund
    const ok = flag === true || Number(flag) === 1
    return ok && [2, 3].includes(st) && refundable.value > 0
})

const modes = computed(() => [
    { key: 'change' as const, label: '申请改期', enabled: canChange.value },
    { key: 'refund' as const, label: '申请退款', enabled: canRefund.value }
])

const blockReason = computed(() => {
    if (mode.value === 'change')
        return canChange.value ? '' : changeMessage.value || '当前订单不支持改期'
    return canRefund.value ? '' : '当前订单状态不支持自助退款，请联系客服'
})

const orderTitle = computed(() => {
    const parts: string[] = []
    const name = order.value.staff_name || order.value.items?.[0]?.staff_name || ''
    if (name) parts.push(name)
    const d = dateText(order.value.service_date)
    if (d) parts.push(d)
    return parts.join(' · ') || '婚庆服务'
})

const orderSub = computed(() => {
    const parts: string[] = []
    if (order.value.order_sn) parts.push(`订单 ${order.value.order_sn}`)
    const pkg = order.value.package_name || order.value.items?.[0]?.package_name || ''
    if (pkg) parts.push(pkg)
    return parts.join(' · ')
})

const barValue = computed(() => {
    if (mode.value === 'change') return form.newDate ? dateText(form.newDate) : '未选择'
    return refundable.value > 0 ? `¥${money(refundable.value)}` : '以顾问确认为准'
})

const canSubmit = computed(() => {
    if (submitting.value) return false
    if (mode.value === 'change') {
        return canChange.value && !!form.newDate && form.remark.trim().length >= 10
    }
    return canRefund.value && !!form.refundReason && form.remark.trim().length >= 10
})

function money(n: number) {
    return Number(n || 0).toLocaleString('zh-CN')
}

function dateText(d?: string) {
    if (!d) return ''
    const t = String(d).slice(0, 10)
    const dt = new Date(t.replace(/-/g, '/'))
    if (Number.isNaN(dt.getTime())) return t.replace(/-/g, '.')
    const w = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][dt.getDay()]
    return `${t.replace(/-/g, '.')} ${w}`
}

function imgUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        load()
        return
    }
    if (kind === 'login') {
        navigate({ path: '/pages/login/login' })
        return
    }
    if (kind === 'reselect' || kind === 'back') uni.navigateBack()
}

function switchMode(m: { key: 'change' | 'refund'; enabled: boolean }) {
    if (!m.enabled) {
        toast.info(
            m.key === 'change'
                ? blockReason.value || '当前不可改期'
                : blockReason.value || '当前不可退款'
        )
        return
    }
    mode.value = m.key
}

function onPickDate(e: any) {
    const val = String(e?.detail?.value || '').trim()
    if (val) {
        form.newDate = val.slice(0, 10)
    }
}

async function load() {
    loading.value = true
    rawError.value = null
    try {
        const [detail, change] = await Promise.all([
            getOrderDetail({ id: orderId.value }),
            checkCanChange({ order_id: orderId.value }).catch(() => null)
        ])
        order.value = detail || {}
        if (change) {
            canChange.value = Boolean((change as any).can_change)
            changeMessage.value = (change as any).message || ''
        }
        // 默认落在可用的那个模式上
        if (!canChange.value && canRefund.value) mode.value = 'refund'
    } catch (e) {
        rawError.value = e
        report('api_error', { endpoint: 'order.detail', message: '售后页订单加载失败' })
    } finally {
        loading.value = false
    }
}

async function onSubmit() {
    if (submitting.value) return
    if (!canSubmit.value) {
        if (form.remark.trim().length < 10) {
            toast.info('请把说明写到 10 个字以上')
            return
        }
        if (mode.value === 'change' && !form.newDate) {
            toast.info('请选择期望的新档期')
            return
        }
        if (mode.value === 'refund' && !form.refundReason) {
            toast.info('请选择退款原因')
            return
        }
        toast.info(blockReason.value || '当前不可提交')
        return
    }

    submitting.value = true
    try {
        // 服务号提醒：表单校验已通过，退款与改期共用同一个写请求前的检查；
        // 暂停时直接退出，submitting 由 finally 释放
        if (!(await remindBeforeOaAction())) return
        if (mode.value === 'change') {
            await applyDateChange({
                order_id: orderId.value,
                new_date: form.newDate,
                reason: `${form.changeReason || '改期'}：${form.remark.trim()}`.slice(0, 255),
                attach_images: form.images.length ? form.images : undefined
            })
            toast.success('改期申请已提交')
        } else {
            const reason = buildRefundReason({
                reason_label: form.refundReason,
                supplement: form.remark.trim()
            })
            await applyRefund({ id: orderId.value, reason })
            toast.success('退款申请已提交')
        }
        setTimeout(() => {
            navigate({
                path: '/packages/pkg-service/pages/aftersale/index',
                query: { orderId: orderId.value }
            })
        }, 700)
    } catch (e: any) {
        toast.error(e?.message || '提交失败，请重试')
        report('api_error', {
            endpoint:
                mode.value === 'change' ? 'order_change.applyDateChange' : 'order.applyRefund',
            message: '售后申请提交失败'
        })
    } finally {
        submitting.value = false
    }
}

function goOrders() {
    navigate({ path: '/pages/order/order' })
}

onLoad((options: any) => {
    orderId.value = Number(options?.orderId || options?.id || 0)
    const t = String(options?.type || '')
    if (t === 'refund') mode.value = 'refund'
    if (!orderId.value) {
        loading.value = false
        return
    }
    load()
})
</script>

<style lang="scss" scoped>
.pad {
    padding: var(--sp-3) var(--page-gutter);
}

.tc-body {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding: var(--sp-3) var(--page-gutter) var(--sp-4);
}

.ord {
    display: flex;
    align-items: center;
    gap: 18rpx;
    padding: 22rpx;
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);

    &__av {
        width: 72rpx;
        height: 72rpx;
        border-radius: var(--r-sm);
        background: var(--c-neutral-bg);
        flex: none;

        &--txt {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--c-brand-bg);
        }
    }

    &__av-text {
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        color: var(--c-brand-text);
    }

    &__t {
        flex: 1;
        min-width: 0;
    }

    &__title {
        display: block;
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__sub {
        display: block;
        margin-top: 6rpx;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__amt {
        flex: none;
        text-align: right;
    }

    &__amt-label {
        display: block;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__amt-value {
        display: block;
        margin-top: 4rpx;
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        color: var(--c-ink);
    }
}

.seg {
    display: flex;
    gap: var(--sp-2);

    &__item {
        flex: 1;
        height: 68rpx;
        border-radius: var(--r-pill);
        /* 可点控件边界：--c-line-strong 对 --c-surface 1.60:1（未选中态几个选项几乎连成一片），
           按项目既有约定改 --c-control-border（3.77:1）。判定依据是模板里
           `@tap="switchMode(m)"` + hover-class，不是类名。 */
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__item.is-on {
        background: var(--c-ink);
        border-color: var(--c-ink);

        .seg__text {
            color: var(--c-on-dark);
        }
    }

    &__item.is-off {
        opacity: 0.45;
    }
}

.note {
    display: flex;
    align-items: flex-start;
    gap: 10rpx;
    padding: 18rpx 20rpx;
    border-radius: var(--r-sm);
    background: var(--c-brand-bg);

    &__b {
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
    }

    &__text {
        font-size: var(--fs-xs);
        line-height: var(--lh-loose);
        color: var(--c-ink-2);
    }

    &--warn {
        background: var(--c-warning-bg);
        /* 警示图标用琥珀；.note__text 自己声明了 --c-ink-2 */
        color: var(--c-warning);
    }

    &--plain {
        background: var(--c-surface-2);
    }
}

.card {
    padding: 26rpx;
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);

    &__h {
        display: block;
        font-size: var(--fs-26);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        margin-bottom: 8rpx;
    }
}

.kv {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20rpx 0;
    border-bottom: 1rpx solid var(--c-line);

    &--col {
        display: block;
    }

    &--last {
        border-bottom: 0;
        padding-bottom: 4rpx;
    }

    &__row {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    &__k {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__v-row {
        display: flex;
        align-items: center;
        gap: 10rpx;
    }

    &__v {
        font-size: var(--fs-xs);
        color: var(--c-ink);

        &--strong {
            font-family: var(--font-serif);
            font-size: var(--fs-md);
        }
    }

    &__tag {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
        background: var(--c-brand-bg);
        padding: 4rpx 10rpx;
        border-radius: var(--r-xs);
    }
}

.picker {
    display: flex;
    align-items: center;
    justify-content: space-between;
    /* 日历图标；&-text 自己声明了 --c-ink */
    color: var(--c-ink-3);
    height: 84rpx;
    margin-top: 14rpx;
    padding: 0 20rpx;
    border-radius: var(--r-sm);
    background: var(--c-surface-2);

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink);

        &.is-ph {
            color: var(--c-ink-3);
        }
    }
}

.chips {
    display: flex;
    flex-wrap: wrap;
    gap: 14rpx;
    margin-top: 16rpx;
}

.chip {
    padding: 12rpx 24rpx;
    border-radius: var(--r-pill);
    /* 可点控件边界（模板 @tap + hover-class + is-on）：--c-line-strong 1.60:1 → --c-control-border 3.77:1 */
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);

    &__text {
        font-size: var(--fs-22);
        color: var(--c-ink-2);
    }

    &.is-on {
        background: var(--c-ink);
        border-color: var(--c-ink);

        .chip__text {
            color: var(--c-on-dark);
        }
    }
}

.ta-wrap {
    margin-top: 16rpx;
}

.bar {
    &__left {
        flex: 1;
        min-width: 0;
    }

    &__label {
        display: block;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__value {
        display: block;
        margin-top: 2rpx;
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__btn {
        flex: none;
        height: 84rpx;
        padding: 0 40rpx;
        border-radius: var(--r-pill);
        background: var(--c-ink);
        display: flex;
        align-items: center;
        justify-content: center;

        &-text {
            font-size: var(--fs-md);
            font-weight: var(--fw-medium);
            color: var(--c-on-dark);
        }

        &.is-off {
            background: var(--c-surface-2);

            .bar__btn-text {
                color: var(--c-ink-3);
            }
        }
    }
}
</style>
