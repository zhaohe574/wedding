import { computed, ref, type Ref } from 'vue'
import { checkAvailable } from '@/api/catalog'
import { createOrder, previewBooking, previewOrder } from '@/api/order'
import { toast } from '@/composables/useToast'
import { report } from '@/platform/report'
import { useBadgeStore } from '@/stores/badge'
import { remindBeforeOaAction } from '@/utils/oa-reminder'
import {
    money,
    toCents,
    type BookingFocusTarget,
    type BookingFormData,
    type BookingLockState,
    type BookingQuoteState
} from './booking-helpers'

/**
 * 预约核价 + 提交预约申请闭环：校验 → checkAvailable → createOrder → 进入「待确认」，
 * 待服务人员确认后开放支付。
 *
 * 铁律：
 * - 金额只消费服务端 quote，前端不做加减；
 * - 提交前断言 定金 + 尾款 === 总额，不等即阻断（amountMismatch）；
 * - 顺序固定：校验 → checkAvailable → createOrder → 进入「待确认」，确认页不直接拉起支付。
 */
export function useBookingOrder(options: {
    staffId: Ref<number>
    date: Ref<string>
    form: BookingFormData
    orderId: Ref<number>
    lockState: Ref<BookingLockState>
    agreed: Ref<boolean>
    agreeError: Ref<string>
    formErrorVisible: Ref<boolean>
    getBarAmount: () => number | string | null
    buildParams: () => Record<string, any>
    validate: () => boolean
    focusFirstError: (target?: BookingFocusTarget) => void
    relockSchedule: () => void
    markOrderCreated: () => void
}) {
    const {
        staffId,
        date,
        form,
        orderId,
        lockState,
        agreed,
        agreeError,
        formErrorVisible,
        getBarAmount,
        buildParams,
        validate,
        focusFirstError,
        relockSchedule,
        markOrderCreated
    } = options

    const quote = ref<any>({})
    const quoteItems = ref<any[]>([])
    const validations = ref<Array<{ message: string; level?: string }>>([])
    const quoteHash = ref('')
    const quoteState = ref<BookingQuoteState>('idle')
    const submitError = ref('')
    const orderPendingConfirm = ref(false)
    const submitting = ref(false)

    const quoteRoleItems = computed(() =>
        quoteItems.value.filter((i: any) => Number(i?.item_type) === 3)
    )
    const validationErrors = computed(() =>
        validations.value.filter((v: any) => String(v?.level || '') !== 'warn')
    )
    const validationWarns = computed(() =>
        validations.value.filter((v: any) => String(v?.level || '') === 'warn')
    )

    const depositCents = computed(() => toCents(quote.value?.deposit_amount))
    const balanceCents = computed(() => toCents(quote.value?.balance_amount))
    const totalCents = computed(() => toCents(quote.value?.total_amount))

    const amountMismatch = computed(() => {
        const d = depositCents.value
        const b = balanceCents.value
        const t = totalCents.value
        if (d === null || b === null || t === null) return false
        if (d <= 0 || b <= 0) return false
        return Math.abs(d + b - t) > 1
    })

    function quoteText(key: string) {
        const v = quote.value?.[key]
        if (v === null || v === undefined || v === '') return '—'
        return `¥${money(v)}`
    }

    const depositText = computed(() => quoteText('deposit_amount'))
    const balanceText = computed(() => quoteText('balance_amount'))
    const isFullPayment = computed(() => !(balanceCents.value && balanceCents.value > 0))
    const balanceDueText = computed(() => {
        const d = quote.value?.balance_pay_desc || quote.value?.balance_desc
        return d ? String(d) : '服务完成后开放支付'
    })

    function invalidateQuote() {
        quoteHash.value = ''
        quote.value = {}
        quoteItems.value = []
        validations.value = []
        quoteState.value = 'idle'
        submitError.value = ''
    }

    function applyPreview(res: any) {
        quote.value = res?.quote || res || {}
        quoteItems.value = Array.isArray(res?.items) ? res.items : []
        quoteHash.value = String(res?.quote_hash || '')
        validations.value = Array.isArray(res?.validations) ? res.validations : []
        const contact = res?.contact_default
        if (contact) {
            if (!form.contact_name) form.contact_name = String(contact.name || '').trim()
            if (!form.contact_mobile) form.contact_mobile = String(contact.mobile || '').trim()
        }
        quoteState.value = quoteHash.value ? 'ready' : 'failed'
    }

    async function runPreview() {
        quoteState.value = 'loading'
        submitError.value = ''
        try {
            try {
                const v2: any = await previewBooking(buildParams())
                applyPreview(v2)
                return
            } catch {
                // v2 未上线：降级 v1
            }
            const legacy: any = await previewOrder(buildParams())
            applyPreview(legacy)
        } catch (e: any) {
            quoteState.value = 'failed'
            quote.value = {}
            quoteItems.value = []
            quoteHash.value = ''
            validations.value = []
            report('api_error', { endpoint: 'order.preview', message: e?.message || '报价获取失败' })
        }
    }

    function showPendingConfirm(id: number) {
        orderPendingConfirm.value = true
        uni.showModal({
            title: '预约申请已提交',
            content:
                '订单当前状态为「待确认」，待服务人员确认档期后将进入「待支付」，届时可在订单详情中完成支付。',
            showCancel: false,
            confirmText: '查看订单',
            success: () => {
                uni.redirectTo({ url: `/packages/pkg-order/pages/detail/detail?id=${id}` })
            }
        })
    }

    async function handleSubmit() {
        if (submitting.value) return

        const formValid = validate()
        if (!agreed.value) {
            agreeError.value = '请先勾选《预约服务协议》再提交申请'
        } else {
            agreeError.value = ''
        }

        if (!formValid) {
            focusFirstError()
            return
        }
        formErrorVisible.value = false

        if (!agreed.value) {
            focusFirstError('agree')
            return
        }

        if (lockState.value === 'failed') {
            toast.info('该档期刚被订走，请换一个日期')
            return
        }
        if (lockState.value === 'expired') {
            relockSchedule()
            toast.info('正在重新锁定档期，请稍候')
            return
        }

        if (quoteState.value !== 'ready' || !quoteHash.value) {
            runPreview()
            toast.info('正在重新获取报价，请稍候')
            return
        }
        if (amountMismatch.value) {
            toast.info('报价金额不一致，请重新获取报价')
            return
        }
        if (getBarAmount() === null) {
            toast.info('订单金额缺失，正在重新核价')
            runPreview()
            return
        }

        submitting.value = true
        try {
            const params: Record<string, any> = {
                ...buildParams(),
                contact_name: form.contact_name.trim(),
                contact_mobile: form.contact_mobile.trim(),
                service_address: form.service_address.trim(),
                quote_hash: quoteHash.value
            }
            if (form.remark.trim()) params.remark = form.remark.trim()

            try {
                const av: any = await checkAvailable({ staff_id: staffId.value, date: date.value })
                if (av && av.is_available === false) {
                    submitError.value = av.message || '该档期刚被订走，请换一个日期'
                    lockState.value = 'failed'
                    return
                }
            } catch {
                // 校验失败不阻断主流程
            }

            // 服务号提醒：校验与档期复核都过了才弹，return 由 finally 释放 submitting
            if (!(await remindBeforeOaAction())) return

            const res: any = await createOrder(params as any)
            const id = Number(res?.order_id || res?.id || 0)
            if (!id) throw new Error('提交申请失败：未返回订单号')
            orderId.value = id
            markOrderCreated()
            // 新订单立即改变「待确认」计数，标脏下次回源
            useBadgeStore().invalidate()
            showPendingConfirm(id)
        } catch (e: any) {
            submitError.value = e?.message || '提交申请失败，请稍后重试'
            report('api_error', { endpoint: 'order.create', message: submitError.value })
        } finally {
            submitting.value = false
        }
    }

    function goOrderDetail() {
        if (!orderId.value) {
            uni.redirectTo({ url: '/pages/order/order' })
            return
        }
        uni.redirectTo({ url: `/packages/pkg-order/pages/detail/detail?id=${orderId.value}` })
    }

    return {
        quote,
        quoteItems,
        quoteRoleItems,
        validations,
        validationErrors,
        validationWarns,
        quoteHash,
        quoteState,
        submitError,
        orderPendingConfirm,
        submitting,
        depositCents,
        balanceCents,
        totalCents,
        amountMismatch,
        depositText,
        balanceText,
        isFullPayment,
        balanceDueText,
        invalidateQuote,
        runPreview,
        handleSubmit,
        goOrderDetail
    }
}
