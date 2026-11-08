/**
 * 08 订单确认 / 09 支付成功 / 10 订单详情 / 22 我的订单。
 *
 * 事实源：openapi-core.yaml 只定义了 GET /order/detail（返回 OrderPaymentSummary），
 * 没有 /v2/order 的定义；/order/lists、/order/statistics 也未进 yaml。
 * 所以下面凡是 v2 聚合字段，全部按 shared/contracts/dto.ts 的 OrderDetailResponse 口径，
 * 并显式标出设计稿要而契约没有的字段（F-xx）。
 *
 * 铁律（沿用现有代码与 yaml 描述）：
 *   1. 可执行动作只认服务端 actions / action_reasons，页面不许状态组合推导。
 *   2. 金额由服务端算，客户端不传金额。
 *   3. 支付结果只认 /pay/payStatus，不认 requestPayment 的 success。
 */

import type {
    OrderActions,
    OrderActionReasons,
    OrderPayStatus,
    OrderStatus,
    OrderPaymentSummary,
    OrderDetailResponse,
    PaymentStage
} from '@shared/contracts'
import type { BitFlag, Money, TimeText } from './common'
import type { StaffCardVO } from './catalog'

/** 08 屏报价行（对应 v2/bookingPreview 的 quote，字段名沿用 QuoteSummary） */
export interface QuoteLineVO {
    label: string
    amount: Money
    /** 是否允许「更改」（联系人 / 地址） */
    editable?: boolean
}

/** 08 屏支付安排一步 */
export interface PaymentScheduleStepVO {
    key: 'deposit' | 'balance'
    label: string
    /** 「今天支付」/「婚礼前 7 天 · 服务号提醒」 */
    when_text: string
    amount: Money
    state: 'now' | 'waiting' | 'paid'
    /** ✘ F-26 「尾款 05.13 开放」需要具体开放日，现只有文案 */
    open_date?: string
}

/** 08 屏加购推荐（「78% 的新人还会加『致辞润色』」） ✘ G-18 */
export interface UpsellVO {
    addon_id: number
    name: string
    price: Money
    /** 必须有数据来源，禁止前端编造百分比 */
    percent: number
}

/** 订单列表行（22 屏）。比 v2 的 OrderListItem 多出：服务项摘要、倒计时、场地 */
export interface OrderListItemVO {
    id: number
    order_sn: string
    order_status: OrderStatus
    order_status_desc: string
    pay_status: OrderPayStatus
    total_amount: Money
    pay_amount: Money
    paid_amount?: Money
    service_date?: string
    create_time: string
    /** ◐ 22 屏卡片头：主持人头像 + 名 + 徽章 + 套餐摘要（共 N 项服务） */
    staff?: Pick<StaffCardVO, 'id' | 'name' | 'avatar'>
    package_name?: string
    item_count?: number
    /** ✘ F-27 「杭州 西溪湿地」城市场地一行 */
    city_name?: string
    venue?: string
    /** ✘ F-27 待确认/待支付死线「剩余 23:41」 */
    countdown_seconds?: number
    /** ✘ F-27 金额语义（待付尾款 / 已付 / 待付定金）需后端给，否则前端要重算 */
    amount_label?: string
    amount_value?: Money
}

/** 10 屏四段里程碑 */
export interface OrderMilestoneVO {
    key: 'deposit' | 'questionnaire' | 'rehearsal' | 'ceremony'
    label: string
    /** 「11.02」/「进行中」 */
    value_text: string
    state: 'done' | 'now' | 'todo'
    /** ✘ F-30 彩排日无字段 */
    date?: string
}

/** 10 屏待办卡一行 */
export interface OrderTodoVO {
    key: 'questionnaire' | 'balance' | 'review' | 'rehearsal'
    title: string
    desc?: string
    /** 「必填」/「待开放」 */
    tag_text?: string
    action_path?: string
    /** 未到时间时禁用并说明 */
    enabled: boolean
}

/** 10 屏 / 09 屏聚合 */
export interface OrderDetailVO {
    order: {
        id: number
        order_sn: string
        order_status: OrderStatus
        order_status_desc: string
        pay_status: OrderPayStatus
        total_amount: Money
        pay_amount: Money
        paid_amount?: Money
        service_date?: string
        /** ✘ F-26 「14:00」服务时间点 */
        service_time?: string
        contact_name?: string
        contact_mobile?: string
        address?: string
        /** ✘ F-28 场地类型「户外草坪」 */
        venue_type?: string
        create_time: string
    }
    /** 付款摘要（openapi 的 OrderPaymentSummary，金额三栏全靠它） */
    payment: OrderPaymentSummary
    items: Array<{
        id: number
        item_type?: number
        staff?: Pick<StaffCardVO, 'id' | 'name' | 'avatar'> & { rating?: number | string }
        package?: { id: number; name: string; price: Money; duration_desc?: string }
        addons?: Array<{ id: number; name: string; price: Money }>
        subtotal: Money
    }>
    actions: OrderActions
    action_reasons: OrderActionReasons
    milestones: OrderMilestoneVO[]
    todos: OrderTodoVO[]
    pending_questionnaire?: { id: number; title: string; status: number } | null
    pending_review?: { id: number; order_id: number } | null
    /** ✘ F-29 「已锁档」状态（08 屏右上角、04 屏「我的档期 05.20 已锁定」） */
    schedule_locked?: BitFlag
    pay: {
        need_pay: string
        need_pay_amount: Money
        pay_remain_seconds: number
        current_pay_stage?: PaymentStage
    }
    /** 09 屏「接下来」时间轴：问卷 / 尾款 / 彩排 */
    next_steps?: Array<{ key: string; title: string; desc?: string; when_text: string }>
    offline?: { enabled: boolean; available: boolean; contact: string }
}

/** 22 屏 tab 计数（order.statistics 的收敛结果） */
export interface OrderTabCountVO {
    all: number
    pending_confirm: number
    pending_pay: number
    pending_service: number
    in_service: number
    aftersale: number
}

/**
 * v2/order 实际返回的动作矩阵。
 *
 * ⚠ shared/contracts 的 `OrderActions` 只声明了 5 个键（pay/cancel/change_date/pause/contact_advisor），
 * 但 `V2Logic::order()` 实际算了 11 个键。少掉的 6 个是页面真正需要的（尤其是 `apply_refund`）：
 *   apply_refund       ← 由 `can_user_refund === 1` 得出，**18/23 屏退款入口的唯一可信依据**
 *   upload_voucher     ← 线下待支付且线下收款可用
 *   confirm_complete / delete / review / fill_questionnaire
 * 这里用交叉类型补齐，不改 shared（跨端契约文件由契约脚本生成，单独排期）。
 */
export type V2OrderActions = OrderActions & {
    apply_refund: boolean
    upload_voucher: boolean
    confirm_complete: boolean
    delete: boolean
    review: boolean
    fill_questionnaire: boolean
}

/**
 * v2/order 的响应外壳。
 *
 * 注意：**v2 不返回 `refundable_amount` / `can_user_refund` 原始字段**，
 * `V2Logic::order()` 的 order / pay 两个对象都是白名单取值，这两个金额与资格只在 v1
 * `/order/detail` 的根层才有。所以：
 *   · 退款资格 → 读 `actions.apply_refund`
 *   · 可退金额 → 只能取 v1 详情根层 `refundable_amount`，或 `GET /order/refundDetail`
 *   · 不要在 v2 响应里多层级兜底找金额（找不到的，只会写出永不命中的死代码）
 */
export type V2OrderResponse = Omit<OrderDetailResponse, 'actions'> & {
    actions: V2OrderActions
    /** 后端降级标记：聚合里某些子请求失败时会列出被降级的模块名 */
    degraded?: string[]
    server_time?: number
}

/** 22 屏筛选（status 支持逗号分隔多值，v2 起） */
export interface OrderListQuery {
    status?: string
    keyword?: string
    page?: number
    page_size?: number
}

/** 09 屏支付成功页需要的摘要（避免二次请求详情） */
export interface PaySuccessVO {
    order_id: number
    order_sn: string
    paid_amount: Money
    pay_way_text: string
    pay_stage_text: string
    service_date_text?: string
    staff_name?: string
    package_name?: string
    venue?: string
    questionnaire_task_id?: number
    next_steps: Array<{ key: string; title: string; desc?: string; when_text: string }>
}

/** 展示工具口径：下单/支付后回到页面时统一用它判断「该切主按钮了」 */
export type OrderPrimaryActionKey = 'pay' | 'questionnaire' | 'aftersale' | 'contact'
export type OrderPrimaryAction = {
    key: OrderPrimaryActionKey
    text: string
    disabled?: boolean
    reason?: string
}

/** 22 屏一行的时间文案 */
export type OrderTimeText = TimeText
