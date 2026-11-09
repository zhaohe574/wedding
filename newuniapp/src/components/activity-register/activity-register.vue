<template>
    <!--
        活动报名（真机反馈：动态详情的「报名」此前跳到「我的活动」列表，并没有报名功能）。

        后端 /dynamic/activity* 这一组接口齐全但小程序此前零调用，本组件就地完成
        「选票 → 填联系人 → 提交 → 支付 → 结果」的完整链路，不新建页面、不改 pages.json。

        三条契约（读 ActivityRegistrationService 得出，别按订单支付那套写）：
        1. activityRegister 返回 { registration_id, need_pay }，need_pay=0 是免费票直接成功；
        2. 需要支付时走 activityPrepay → wx.requestPayment → activityPayStatus 轮询，
           参数是 registration_id 不是 order_id，不能复用 api/pay.ts；
        3. requestPayment 的 success 只代表「用户完成支付动作」，业务成功必须以
           pay_status === 1 为准（platform/pay.ts 的铁律）。
    -->
    <tn-popup v-model="visible" open-direction="bottom" :radius="28" :safe-area-inset-bottom="true">
        <view class="ar">
            <view class="ar__head">
                <text class="ar__title">活动报名</text>
                <view
                    class="ar__close"
                    hover-class="is-pressed"
                    aria-role="button"
                    aria-label="关闭"
                    @tap="close"
                >
                    <tn-icon name="close" :size="28" />
                </view>
            </view>

            <view class="ar__brief">
                <text class="ar__name">{{ title || '线下沙龙' }}</text>
                <text v-if="timeText" class="ar__meta">{{ timeText }}</text>
            </view>

            <text class="ar__label">选择票种</text>
            <view v-if="loading" class="ar__loading">
                <base-skeleton :rows="2" />
            </view>
            <view v-else-if="!tickets.length" class="ar__empty">暂无可报名的票种</view>
            <view v-else class="ar__tickets">
                <view
                    v-for="t in tickets"
                    :key="t.id"
                    class="ar__ticket"
                    :class="{ 'is-on': selectedId === t.id, 'is-off': !t.canBuy }"
                    hover-class="is-pressed"
                    @tap="pick(t)"
                >
                    <view class="ar__ticket-main">
                        <text class="ar__ticket-name">{{ t.name || '活动票' }}</text>
                        <text class="ar__ticket-price">{{ t.priceLabel }}</text>
                    </view>
                    <view class="ar__ticket-sub">
                        <text v-if="t.saleTimeText" class="ar__ticket-meta">{{
                            t.saleTimeText
                        }}</text>
                        <text class="ar__ticket-meta">余 {{ t.remainingCount }}</text>
                    </view>
                    <text v-if="!t.canBuy && t.disabledReason" class="ar__ticket-reason">{{
                        t.disabledReason
                    }}</text>
                    <view v-if="selectedId === t.id" class="ar__ticket-check" aria-hidden="true">
                        <tn-icon name="check" :size="20" />
                    </view>
                </view>
            </view>

            <text class="ar__label">报名信息</text>
            <form-field
                label="联系人"
                layout="stack"
                type="input"
                placeholder="请输入姓名"
                :maxlength="20"
                :error="nameError"
                :model-value="contactName"
                @update:model-value="(v: string) => (contactName = v)"
            />
            <form-field
                label="手机号"
                layout="stack"
                type="input"
                placeholder="请输入 11 位手机号"
                :maxlength="11"
                :error="mobileError"
                :model-value="contactMobile"
                @update:model-value="(v: string) => (contactMobile = v)"
            />

            <base-button
                class="ar__submit"
                :text="submitting ? '提交中…' : pendingRegistrationId > 0 ? '继续支付' : '提交报名'"
                variant="ink"
                size="lg"
                :loading="submitting"
                :disabled="submitting"
                @click="submit"
            />
            <text class="ar__tip">报名后可在「我的活动」查看入场码与地址</text>
        </view>
    </tn-popup>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import {
    getActivityTickets,
    registerActivity,
    activityPrepay,
    getActivityPayStatus
} from '@/api/activity'
import { requestPayment, sleep } from '@/platform/pay'
import { toast } from '@/composables/useToast'
import { useUserStore } from '@/stores/user'

const props = withDefaults(
    defineProps<{
        show: boolean
        /** 活动动态 id（后端字段叫 dynamic_id） */
        dynamicId: number
        title?: string
        timeText?: string
    }>(),
    { title: '', timeText: '' }
)

const emit = defineEmits<{
    (e: 'update:show', v: boolean): void
    /** 提交已落地（含免费成功 / 待支付 / 支付结果待确认），由页面决定跳不跳「我的活动」 */
    (e: 'finished', registrationId: number): void
}>()

/**
 * 已落地但还没支付成功的报名单。后端 register() 对同一活动已有未过期报名会直接返回
 * 「你已提交过该活动报名」，所以支付失败/取消后用户再点提交，必须复用这个 id 直接
 * activityPrepay，不能重新 registerActivity。
 */
const pendingRegistrationId = ref(0)

function clearPending() {
    pendingRegistrationId.value = 0
}

const visible = computed({
    get: () => props.show,
    set: (v: boolean) => {
        // 提交/支付轮询期间禁止关闭（含点遮罩），避免请求还在跑、弹层已消失
        if (!v && submitting.value) return
        // 点遮罩 / v-model 置 false 也算主动关闭：清掉待支付单，别带到下一次打开
        if (!v) clearPending()
        emit('update:show', v)
    }
})

interface Ticket {
    id: number
    name: string
    priceLabel: string
    remainingCount: number
    canBuy: boolean
    disabledReason: string
    saleTimeText: string
}

const tickets = ref<Ticket[]>([])
const loading = ref(false)
const selectedId = ref(0)
const contactName = ref('')
const contactMobile = ref('')
const nameError = ref('')
const mobileError = ref('')
const submitting = ref(false)

const userStore = useUserStore()

/** 从当前登录态带出默认值，减少输入（后端仍会校验，前端不替它做主） */
function fillDefaults() {
    const info = userStore.userInfo || {}
    if (!contactName.value) {
        contactName.value = String(info.nickname || info.real_name || info.name || '')
    }
    if (!contactMobile.value) {
        contactMobile.value = String(info.mobile || info.phone || '')
    }
}

watch(
    () => props.show,
    (open) => {
        if (open) {
            fillDefaults()
            void loadTickets()
        }
    }
)

async function loadTickets() {
    if (!props.dynamicId) return
    loading.value = true
    tickets.value = []
    selectedId.value = 0
    try {
        const list = await getActivityTickets({ dynamic_id: props.dynamicId })
        tickets.value = (Array.isArray(list) ? list : []).map((t: any) => ({
            id: Number(t.id || 0),
            name: String(t.name || t.title || '活动票'),
            priceLabel: String(t.price_label || (Number(t.price) > 0 ? `¥${t.price}` : '免费')),
            remainingCount: Math.max(0, Number(t.remaining_count ?? t.stock ?? 0)),
            canBuy: Number(t.can_buy ?? 1) === 1,
            disabledReason: String(t.buy_disabled_reason || ''),
            saleTimeText: String(t.sale_time_text || '')
        }))
        const first = tickets.value.find((t) => t.canBuy)
        if (first) selectedId.value = first.id
    } catch (e: any) {
        toast.error(e?.message || '票种加载失败，请稍后重试')
    } finally {
        loading.value = false
    }
}

function pick(t: Ticket) {
    if (!t.canBuy) {
        toast.info(t.disabledReason || '该票种暂不可报名')
        return
    }
    selectedId.value = t.id
}

function close() {
    if (submitting.value) return
    clearPending()
    visible.value = false
}

function validate(): boolean {
    nameError.value = ''
    mobileError.value = ''
    if (!selectedId.value) {
        toast.info('请选择票种')
        return false
    }
    if (!contactName.value.trim()) {
        nameError.value = '请填写联系人'
        return false
    }
    if (!/^1\d{10}$/.test(contactMobile.value.trim())) {
        mobileError.value = '请输入正确的 11 位手机号'
        return false
    }
    return true
}

/** 支付回调可能晚于 requestPayment 的 success，短轮询确认 pay_status */
async function waitPaid(registrationId: number): Promise<boolean> {
    for (const seconds of [1, 2, 3, 5]) {
        await sleep(seconds * 1000)
        try {
            const r = await getActivityPayStatus({ registration_id: registrationId })
            if (Number(r?.pay_status) === 1) return true
        } catch {
            // 单次查询失败继续下一轮，不打断支付
        }
    }
    return false
}

async function submit() {
    if (submitting.value) return
    // 已有待支付单时是「继续支付」，表单只用于首次报名，不再重复校验
    if (!pendingRegistrationId.value && !validate()) return
    submitting.value = true
    try {
        let registrationId = pendingRegistrationId.value
        let needPay = 1
        if (!registrationId) {
            const res = await registerActivity({
                dynamic_id: props.dynamicId,
                ticket_id: selectedId.value,
                contact_name: contactName.value.trim(),
                contact_mobile: contactMobile.value.trim()
            })
            registrationId = Number(res?.registration_id ?? res?.id ?? 0)
            if (!registrationId) throw new Error('报名未成功，请稍后重试')
            needPay = Number(res?.need_pay ?? 1)
        }
        // 一拿到 id 就记住：连 prepay 失败也能复用，不必再走 register()
        pendingRegistrationId.value = registrationId

        // 免费票：后端直接把 pay_status 置为已支付，无需拉起微信支付
        if (needPay === 0) {
            toast.success('报名成功')
            clearPending()
            emit('update:show', false)
            emit('finished', registrationId)
            return
        }

        // 后端 prepay() 对同一 registration_id 会复用待支付流水，重复调用是安全的
        const prepayRes = await activityPrepay({ registration_id: registrationId })
        const config = prepayRes?.config
        if (!config) throw new Error('发起支付失败，请到「我的活动」继续')

        const payResult = await requestPayment(config)
        if (payResult === 'cancel') {
            // 记住待支付单：重新打开弹层时是「继续支付」，不是重新报名
            pendingRegistrationId.value = registrationId
            toast.info('已取消支付，可在「我的活动」继续完成')
            emit('update:show', false)
            emit('finished', registrationId)
            return
        }
        if (payResult === 'fail') {
            // 支付失败不能落进 waitPaid()：那会轮询 11 秒后说「支付结果确认中」，
            // 把失败错误归因成「结果未确认」，用户会以为钱可能已扣。
            // 弹层保持打开、submitting 在 finally 复位，用户可原地点「继续支付」重试。
            // 措辞不断言资金状态：requestPayment 的 fail 只代表 SDK 调用失败，前端不做
            // 服务端查单，无法证实「未扣款」；用「支付未完成」避免给出无法证实的保证。
            // 与 activity.vue:420 / detail.vue:695 的既有范式一致。
            pendingRegistrationId.value = registrationId
            toast.error('支付未完成，请重试')
            return
        }

        const paid = await waitPaid(registrationId)
        if (paid) toast.success('报名成功')
        else toast.info('支付结果确认中，请到「我的活动」查看')
        // 报名单已落地并走到终态，清掉待支付单，下次打开是干净的首单流程
        clearPending()
        emit('update:show', false)
        emit('finished', registrationId)
    } catch (e: any) {
        toast.error(e?.message || '报名失败，请重试')
    } finally {
        submitting.value = false
    }
}
</script>

<style lang="scss" scoped>
.ar {
    padding: 40rpx 42rpx calc(32rpx + var(--safe-bottom));
    max-height: 80vh;
    overflow-y: auto;
    box-sizing: border-box;
    background: var(--c-surface);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: var(--sp-3);
    }

    &__title {
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__close {
        width: 72rpx;
        height: 72rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--c-ink-3);
    }

    &__brief {
        padding: var(--sp-3);
        border-radius: var(--r-md);
        background: var(--c-surface-2);
        margin-bottom: var(--sp-4);
    }

    &__name {
        display: block;
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__meta {
        display: block;
        margin-top: 6rpx;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__label {
        display: block;
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink-2);
        margin: var(--sp-3) 0 var(--sp-2);
    }

    &__loading,
    &__empty {
        padding: var(--sp-3) 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        text-align: center;
    }

    &__tickets {
        display: flex;
        flex-direction: column;
        gap: var(--sp-2);
    }

    &__ticket {
        position: relative;
        padding: var(--sp-3);
        border: 1rpx solid var(--c-control-border);
        border-radius: var(--r-md);
        background: var(--c-surface);

        &.is-on {
            border-color: var(--c-brand-text);
            background: var(--c-surface-2);
        }

        &.is-off {
            opacity: 0.55;
        }
    }

    &__ticket-main {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__ticket-name {
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__ticket-price {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
        flex: none;
    }

    &__ticket-sub {
        display: flex;
        gap: var(--sp-3);
        margin-top: 6rpx;
    }

    &__ticket-meta {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__ticket-reason {
        display: block;
        margin-top: 6rpx;
        font-size: var(--fs-xs);
        color: var(--c-danger);
    }

    &__ticket-check {
        position: absolute;
        top: var(--sp-2);
        right: var(--sp-2);
        color: var(--c-brand-text);
    }

    &__submit {
        margin-top: var(--sp-4);
    }

    &__tip {
        display: block;
        margin-top: var(--sp-2);
        text-align: center;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}
</style>
