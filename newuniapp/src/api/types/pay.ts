/**
 * 支付域（08 发起支付 / 09 支付结果）。
 *
 * ⚠ 契约冲突（P0，见 01-接口契约.md §4.4-C1）：
 *   openapi-core.yaml 明确要求
 *     GET  /pay/payWay    → from(required) + order_id(required)
 *     POST /pay/prepay    → from(required) + order_id(required) + pay_way(required, enum[2])
 *     GET  /pay/payStatus → from(required) + order_id(required) + payment_sn(optional)
 *   而现网 src/api/pay.ts 传的是 { id }，全仓库搜不到任何 from 参数。
 *   在确认后端到底认 id 还是 order_id 之前，支付链路（08/09/10/22 四屏）不能算接通。
 */

import type { PaymentFlowStatus } from '@shared/contracts'
import type { Money, TimeText } from './common'

/** 支付阶段与后端 current_pay_stage 对齐 */
export type PayStage = 'deposit' | 'full' | 'balance' | 'balance_after_service' | 'paid' | 'none'

/** 支付来源：订单支付 / 服务人员结算补交 */
export type PayFrom = 'order' | 'staff_settlement_repay'

/** 沙箱支付来源（覆盖订单、抽成补交、活动报名三条微信支付链路） */
export type MockPayFrom = PayFrom | 'activity_registration'

/** 后端沙箱模式下 prepay 返回的 config 结构 */
export interface MockPayConfig {
    mock: true
    from: MockPayFrom
    payment_sn: string
    order_id?: number
    amount?: number
    subject?: string
}

/** 支付渠道项。通用支付接口 pay_way=2 表示微信（与订单流水模型 pay_way=1 不同，禁止跨模型比较） */
export interface PayWayItemVO {
    pay_way: number
    pay_way_desc: string
    is_default?: number
}

/** GET /pay/payWay */
export interface PayWayResultVO {
    lists: PayWayItemVO[]
    need_pay: string
    need_pay_amount: Money
    order_amount?: Money
    pay_remain_seconds?: number
    stage?: PayStage
}

/** POST /pay/prepay：config 直接喂 wx.requestPayment，禁止自己拼字段 */
export interface PrepayVO {
    config: Record<string, any>
    pay_way: number
    payment_sn: string
    /** ✘ F-31 预支付有效期，用于失败后判断是否要重新 prepay */
    expire_time?: number
}

/** GET /pay/payStatus */
export interface PayStatusVO {
    pay_status: PaymentFlowStatus | number
    pay_way?: number
    payment?: {
        payment_sn: string
        pay_status: PaymentFlowStatus | number
        pay_amount: Money
        transaction_id?: string
        pay_time?: TimeText
    }
    order?: {
        id?: number
        order_id?: number
        order_sn?: string
        order_status: number
        pay_status: number
    }
    /** 异常付款补偿状态 */
    compensate_status?: number
    /** 是否需要重新发起支付（true 才允许再次 prepay） */
    can_repay?: boolean
}

/** 支付请求统一入参（显式声明 from/order_id，避免再次传错字段名） */
export interface PayRequestParams {
    from: PayFrom
    order_id: number
    pay_way?: number
    payment_sn?: string
}

/** 前端支付流程相位（与 payment-result 页的 state 命名保持一致，不新造词） */
export type PayFlowPhase = 'idle' | 'prepaying' | 'paying' | 'polling' | 'success' | 'unconfirmed' | 'failed'
