/**
 * 我的钱包（纯对账只读）页面契约。
 *
 * 边界：只描述「订单支付/退款 + 活动支付/退款」的流水查询形状，
 * 不提供任何资金变动能力。
 * 状态色调统一走 @shared/contracts 的 Tone，本文件不重复定义色值。
 */
import type { Tone } from '@shared/contracts'

/** 资金方向筛选：全部 / 支出 / 退款 */
export type WalletDirection = 'all' | 'out' | 'in'

/** 业务分组筛选：全部 / 订单 / 活动 */
export type WalletBizGroup = 'all' | 'order' | 'activity'

/** 单条流水的业务类别 */
export type WalletBizCategory = 'order_pay' | 'order_refund' | 'activity_pay' | 'activity_refund'

/** 单条流水状态 */
export type WalletFlowStatus = 'completed' | 'processing' | 'exception'

/** 对账汇总（按月份聚合，退款中不计入已退款） */
export interface WalletSummaryVO {
    month: string
    total_paid: number
    total_refunded: number
    net_expense: number
    refunding_amount: number
    refunding_count: number
    paid_count: number
    refunded_count: number
    total_count: number
}

/** 对账流水列表项 */
export interface WalletFlowItemVO {
    id: number
    flow_key: string
    biz_category: WalletBizCategory
    biz_group: 'order' | 'activity'
    biz_sn: string
    biz_type_label: string
    order_id: number
    order_sn: string
    registration_id: number
    dynamic_id: number
    remark: string
    direction: 'out' | 'in'
    amount: number
    status: WalletFlowStatus
    status_label: string
    status_tone: Tone
    title: string
    subtitle: string
    month: string
    event_time: number
    event_time_text: string
    pay_way: number
    pay_way_label: string
    transaction_id: string
    voucher_url: string
    refunded_amount: number
    collection_owner: number
    collection_owner_label: string
}

/** 退款子项（按支付方式拆分的退款明细） */
export interface WalletRefundSubItemVO {
    id: number
    pay_way_label: string
    refund_amount: number
    refund_status_label: string
    out_refund_no: string
    third_refund_no: string
    refund_voucher: string
    refund_time_text: string
}

/** 对账流水详情（在列表项基础上按业务补充字段） */
export interface WalletFlowDetailVO extends WalletFlowItemVO {
    event_full_time_text?: string
    order_total_amount?: number
    order_paid_amount?: number
    service_date?: string
    staff_names?: string
    pay_type_label?: string
    apply_amount?: number
    actual_refund_amount?: number
    refund_reason?: string
    audit_remark?: string
    refund_items?: WalletRefundSubItemVO[]
    dynamic_title?: string
    ticket_name?: string
    activity_start_time_text?: string
    third_refund_no?: string
}

/** 对账流水查询参数 */
export interface WalletFlowsQuery {
    month?: string
    direction?: WalletDirection
    biz_group?: WalletBizGroup
    page?: number
    page_size?: number
}

/** 对账流水分页响应 */
export interface WalletFlowsResponse {
    summary: WalletSummaryVO
    months: string[]
    list: WalletFlowItemVO[]
    total: number
    page: number
    page_size: number
    has_more: boolean
}
