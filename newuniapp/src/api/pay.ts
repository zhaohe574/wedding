import request, { ApiError } from '@/request'
import type { MockPayFrom, PayFrom, PayStatusVO, PayWayResultVO, PrepayVO } from './types'

/**
 * 支付域。
 *
 * ⚠ 2026-11 契约修正（C-01，P0 bug 修复）：
 * 后端 `PayValidate` 的校验规则是
 *   prepay    → from(require|in:order,staff_settlement_repay) + order_id(require|integer|gt:0) + pay_way(require|in:微信)
 *   payWay    → from + order_id                    （only 这两个字段）
 *   payStatus → from + order_id + payment_sn(max:32)
 * 而本文件此前三个方法都传 `{ id }` —— 参数名对不上，线上支付实际调不通。
 * 现在统一收敛为 `{ from, order_id }`，`from` 默认 'order'，
 * `staff_settlement_repay` 只给服务人员结算补交用（工作台），小程序主链路不传。
 *
 * 兼容：为免页面大面积改动，`id` 仍可作为 `order_id` 的别名传入（内部归一后只发 order_id）。
 * 新代码一律写 `order_id`，`id` 标记 deprecated，下一轮清理。
 *
 * 铁律（不变）：
 *  1. prepay 返回的 config 直接喂 wx.requestPayment，不要自己拼字段。
 *  2. 支付结果一律以 payStatus 轮询为准 —— 微信回调可能晚于 requestPayment 的 success。
 *  3. 客户端不传金额，金额由服务端按订单阶段计算。
 */

/** 支付来源：订单支付 / 服务人员结算补交 */
export type { PayFrom }

export interface PayBaseParams {
    /** 订单 id（新写法） */
    order_id?: number
    /** @deprecated 用 order_id；保留仅为兼容存量调用 */
    id?: number
    /** 缺省 'order' */
    from?: PayFrom
}

export interface PayWayParams extends PayBaseParams {
    [k: string]: any
}

export interface PrepayParams extends PayBaseParams {
    /** 通用支付接口 pay_way=2 表示微信（与订单流水模型 pay_way=1 不同，禁止跨模型比较） */
    pay_way?: number
}

export interface PayStatusParams extends PayBaseParams {
    /** 后端限制 max:32，超长直接截断避免整单 400 */
    payment_sn?: string
}

/** 归一出入参：只保留后端认的字段，缺 order_id 时抛结构化错误而不是发一个注定失败的请求 */
function resolveBase(params: PayBaseParams): { from: PayFrom; order_id: number } {
    const orderId = Number(params.order_id ?? params.id ?? 0)
    if (!Number.isFinite(orderId) || orderId <= 0) {
        throw new ApiError({
            code: -99,
            message: '缺少 order_id，无法发起支付',
            errorCode: 'VALIDATE_FAILED',
            retryStrategy: 'none'
        })
    }
    return { from: (params.from ?? 'order') as PayFrom, order_id: orderId }
}

/** 查询当前付款阶段与可用渠道（线下阶段 lists 为空） @endpoint pay.payWay */
export function getPayWay(data: PayWayParams) {
    const base = resolveBase(data)
    return request.get<PayWayResultVO>(
        '/pay/payWay',
        { order_id: base.order_id, from: base.from },
        { auth: true, silent: true }
    )
}

/** 生成预支付单（必须幂等，否则会生成多个 payment_sn） @endpoint pay.prepay */
export function prepay(data: PrepayParams) {
    const base = resolveBase(data)
    return request.post<PrepayVO>(
        '/pay/prepay',
        {
            order_id: base.order_id,
            from: base.from,
            // 小程序只有微信：后端 pay_way 是 require，必须显式给 2
            pay_way: data.pay_way ?? 2
        },
        { auth: true, idempotent: true, dedupe: false, loading: true }
    )
}

/** 支付状态轮询（唯一可信来源） @endpoint pay.payStatus */
export function getPayStatus(data: PayStatusParams) {
    const base = resolveBase(data)
    const paymentSn = data.payment_sn ? String(data.payment_sn).slice(0, 32) : ''
    return request.get<PayStatusVO>(
        '/pay/payStatus',
        { order_id: base.order_id, from: base.from, ...(paymentSn ? { payment_sn: paymentSn } : {}) },
        { auth: true, silent: true, retry: 0 }
    )
}

/** 历史别名，兼容 payment 组件 */
export const getPayResult = getPayStatus

/** 仅测试环境支付沙箱：模拟微信支付回调入账 @endpoint pay.mockConfirm */
export function mockConfirmPay(data: { from: MockPayFrom; payment_sn: string }) {
    return request.post<{ payment_sn: string; transaction_id: string; already_paid?: number }>(
        '/pay/mockConfirm',
        { from: data.from, payment_sn: data.payment_sn },
        { auth: true, idempotent: true, dedupe: false, silent: true }
    )
}
