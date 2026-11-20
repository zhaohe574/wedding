// v2 聚合接口的响应 DTO。
//
// 这些接口解决的是「一个页面 5~8 次请求」的问题：
//   首页 4→1、人员详情 6→1、订单详情 5→1、我的 8→1。
//
// 字段命名以服务端返回为准；服务端未实现的字段一律标 optional，避免客户端假设。

import type { OrderStatus, OrderPayStatus, AfterSaleTicketStatus, QuestionnaireTaskStatus } from './generated/status'
import type { MoneyValue } from './core'

/** 统一分页响应（v2 接口） */
export interface Paged<T> {
    list: T[]
    total: number
    page: number
    page_size: number
    has_more: boolean
}

/**
 * 订单可执行动作矩阵 —— 由服务端计算，客户端只渲染。
 *
 * 只保留小程序真正有 UI 出口的 5 项。其余 6 项（confirm_complete / apply_refund /
 * delete / review / fill_questionnaire / upload_voucher）已于 2026-11 决策移除：
 * 评价与问卷改由「我的 → 待处理」与消息路由进入；退款、删单、凭证上传、确认完成不在小程序提供。
 * 服务端若仍下发多余字段，客户端忽略即可。
 */
export interface OrderActions {
    pay: boolean
    cancel: boolean
    change_date: boolean
    pause: boolean
    contact_advisor: boolean
}

export type OrderActionKey = keyof OrderActions

/** 动作不可用时的原因说明，解决「按钮置灰但不知道为什么」 */
export type OrderActionReasons = Partial<Record<OrderActionKey, string>>

export interface UnreadSummary {
    total: number
    system: number
    order: number
    interact: number
}

export interface FeatureSwitch {
    staff_center: number
    staff_admin: number
    staff_tag_review_enabled: number
    mini_program_review_mode: number
    admin_dashboard: number
}

/**
 * 后台「网站信息 → 前台设置」下发的品牌字段（小程序名称 / 图标 / Slogan 的唯一真源）。
 * 后端已把无效值（空串、likeadmin 模板默认值、模板占位图标）归一成空串，
 * 前端按「空 = 未配置」处理，回落到本地缓存或 `BRAND_FALLBACK`。
 */
export interface WebsiteConfig {
    shop_name: string
    shop_logo: string
    shop_slogan?: string
    [key: string]: unknown
}

export interface HomeConfig {
    domain: string
    website: WebsiteConfig
    feature_switch: FeatureSwitch
    app_update?: {
        version: string
        force_update: number
        url?: string
        content?: string
    }
}

export interface StaffCard {
    id: number
    name: string
    avatar: string
    cover?: string
    rating?: number | string
    order_count?: number
    price?: MoneyValue
    tags?: string[]
    is_favorite?: number
    is_recommend?: number
}

export interface ArticleCard {
    id: number
    title: string
    desc?: string
    abstract?: string
    image?: string
    author?: string
    click?: number | string
    create_time?: string
}

/** GET /api/v2/home */
export interface HomeResponse {
    config: HomeConfig
    page: {
        data: string
        meta: string
    }
    staff_recommend: StaffCard[]
    article_latest: ArticleCard[]
    unread: UnreadSummary
    server_time: number
}

export interface PackageItem {
    id: number
    name: string
    price: MoneyValue
    origin_price?: MoneyValue
    addon_ids?: number[]
    desc?: string
}

export interface AddonItem {
    id: number
    name: string
    price: MoneyValue
    desc?: string
}

export interface StaffReviewStats {
    total: number
    good: number
    medium: number
    bad: number
    score?: number | string
}

export interface ReviewCard {
    id: number
    score: number
    content: string
    images?: string[]
    create_time?: string
    user?: {
        nickname: string
        avatar: string
    }
}

export interface AvailabilityInfo {
    date: string
    is_available: boolean
    message?: string
    calendar?: Record<string, unknown>
}

export interface RoleConfig {
    role_key: string
    label: string
    required: boolean
}

/** 服务人员详情页「当前日期有效订单」 */
export interface StaffActiveOrder {
    order_id: number
    order_sn: string
    order_status: number
    order_status_text: string
    service_date: string
    time_slot: number
    time_slot_text: string
    package_name: string
    staff_name: string
}

/** GET /api/v2/staff/:id */
export interface StaffDetailResponse {
    staff: StaffCard & {
        intro?: string
        service_desc?: string
        certificates?: Array<{ id: number; name: string; image?: string }>
        works_count?: number
    }
    packages: PackageItem[]
    addons: AddonItem[]
    works: Paged<{ id: number; title: string; cover: string }>
    review: {
        stats: StaffReviewStats
        list: ReviewCard[]
    }
    availability: AvailabilityInfo
    my_order?: StaffActiveOrder | null
    role_configs: RoleConfig[]
    banner: {
        small_height: number
        large_height: number
    }
}

export interface OrderItemCard {
    id: number
    item_type: number
    staff?: { id: number; name: string; avatar: string }
    package?: { id: number; name: string; price: MoneyValue }
    addons?: Array<{ id: number; name: string; price: MoneyValue }>
    subtotal: MoneyValue
}

export interface PaymentCard {
    id?: number
    payment_sn: string
    pay_amount: MoneyValue
    pay_status: number
    pay_time?: number | string
    pay_way?: number
}

export interface OrderLogItem {
    id: number
    content: string
    create_time: string
}

export interface OrderPayInfo {
    need_pay: string
    need_pay_amount: MoneyValue
    pay_remain_seconds: number
    current_pay_stage?: string
}

/** GET /api/v2/order/:id */
export interface OrderDetailResponse {
    order: {
        id: number
        order_sn: string
        order_status: OrderStatus
        order_status_desc: string
        pay_status: OrderPayStatus
        total_amount: MoneyValue
        pay_amount: MoneyValue
        paid_amount?: MoneyValue
        service_date?: string
        contact_name?: string
        contact_mobile?: string
        address?: string
        create_time: string
    }
    items: OrderItemCard[]
    payments: PaymentCard[]
    logs: OrderLogItem[]
    actions: OrderActions
    action_reasons: OrderActionReasons
    pending_review: { id: number; order_id: number } | null
    pending_questionnaire: {
        id: number
        title: string
        status: QuestionnaireTaskStatus
    } | null
    pay: OrderPayInfo
    offline: {
        enabled: boolean
        available: boolean
        contact: string
    }
}

export interface OrderStats {
    pending_confirm: number
    pending_pay: number
    pending_service: number
    in_service: number
    completed: number
    refunding: number
}

export interface UserTodo {
    pending_review: number
    pending_questionnaire: number
    pending_waitlist: number
    aftersale_unfinished: number
}

export interface WeddingInfo {
    date: string
    days_remaining: number
    venue?: string
    order_sn?: string
}

/** GET /api/v2/me */
export interface MeResponse {
    user: {
        id: number
        nickname: string
        avatar: string
        mobile: string
        is_new_user: number
        gender?: number
    }
    order_stats: OrderStats
    todo: UserTodo
    wedding: WeddingInfo
    unread: UnreadSummary
    roles: {
        is_staff: number
        admin_dashboard_access: number
    }
    feature_switch: FeatureSwitch
}

export interface QuoteSummary {
    service_amount: MoneyValue
    addon_amount: MoneyValue
    total_amount: MoneyValue
    pay_amount: MoneyValue
    deposit_amount: MoneyValue
    balance_amount: MoneyValue
    payment_mode: string
    payment_mode_desc: string
}

export interface PreviewValidation {
    code: string
    level: 'error' | 'warn'
    field?: string
    message: string
}

/** POST /api/v2/booking/preview */
export interface BookingPreviewResponse {
    quote: QuoteSummary
    items: OrderItemCard[]
    validations: PreviewValidation[]
    quote_hash: string
    expires_at: number
    contact_default: {
        name: string
        mobile: string
    }
}

/** 订单列表行（复用列表接口，但收敛状态分组） */
export interface OrderListItem {
    id: number
    order_sn: string
    order_status: OrderStatus
    order_status_desc: string
    pay_status: OrderPayStatus
    total_amount: MoneyValue
    pay_amount: MoneyValue
    service_date?: string
    create_time: string
    items?: OrderItemCard[]
}

/** 售后工单行 */
export interface TicketListItem {
    id: number
    ticket_sn: string
    type: number
    status: AfterSaleTicketStatus
    status_desc: string
    content: string
    create_time: string
}
