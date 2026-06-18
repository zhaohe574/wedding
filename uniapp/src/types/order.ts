export type NumericValue = number | string

export interface PaginationResponse<T> {
    data?: T[]
    lists?: T[]
    total?: number
    current_page?: number
    last_page?: number
    per_page?: number
    [key: string]: unknown
}

export type OrderActionType = 'pay' | 'cancel' | 'confirm' | 'voucher' | 'contact' | 'detail' | 'delete'

export interface OrderCardAction {
    text: string
    type: 'primary' | 'secondary'
    action: OrderActionType
}

export interface OrderItemStaff {
    id?: NumericValue
    name?: string
    avatar?: string
    [key: string]: unknown
}

export interface OrderApiItemLine {
    id?: NumericValue
    staff_id?: NumericValue
    staff_name?: string
    package_name?: string
    package_description?: string
    service_date?: string
    item_type?: NumericValue
    item_type_desc?: string
    item_meta?: Record<string, unknown>
    amount?: NumericValue
    total_amount?: NumericValue
    quantity?: NumericValue
    staff?: OrderItemStaff
    package?: {
        name?: string
        description?: string
        [key: string]: unknown
    }
    addons?: Array<{
        addon_name?: string
        name?: string
        quantity?: NumericValue
        amount?: NumericValue
        total_amount?: NumericValue
        [key: string]: unknown
    }>
    [key: string]: unknown
}

export interface OrderApiItem {
    id?: NumericValue
    order_id?: NumericValue
    order_sn?: string
    order_status?: NumericValue
    order_status_desc?: string
    pay_status?: NumericValue
    pay_status_desc?: string
    pay_way?: NumericValue | string
    pay_amount?: NumericValue
    order_amount?: NumericValue
    total_amount?: NumericValue
    paid_amount?: NumericValue
    unpaid_amount?: NumericValue
    need_pay?: string
    need_pay_amount?: NumericValue
    need_pay_label?: string
    payment_channel?: NumericValue
    payment_channel_desc?: string
    payment_mode?: string
    payment_mode_desc?: string
    current_pay_stage?: string
    current_pay_stage_desc?: string
    payment_stage?: string
    service_region_text?: string
    service_address?: string
    pay_voucher?: string
    pay_voucher_status?: NumericValue
    offline_collection_required?: NumericValue
    is_offline_collection?: NumericValue
    offline_collection_available?: NumericValue
    offline_collection_enabled?: NumericValue
    deposit_amount?: NumericValue
    deposit_paid?: NumericValue
    balance_amount?: NumericValue
    balance_paid?: NumericValue
    confirm_deadline_time?: NumericValue
    confirm_remain_seconds?: NumericValue
    confirm_timeout_action_desc?: string
    pay_deadline_time?: NumericValue
    pay_remain_seconds?: NumericValue
    pay_timeout_action_desc?: string
    can_user_complete?: NumericValue
    pay_time?: string | number
    items?: OrderApiItemLine[]
    [key: string]: unknown
}

export interface OrderListViewItem {
    id: number
    orderNo: string
    statusValue: number
    statusText: string
    actualPrice: number
    totalPrice: number
    paymentChannel: number
    paymentChannelDesc: string
    payVoucherStatus: number
    payVoucher: string
    offlineCollectionEnabled: number
    paymentModeDesc: string
    serviceTitle: string
    serviceMeta: string
    serviceDateText: string
    confirmDeadlineTime: number
    confirmRemainSeconds: number
    confirmExpireAt: number
    confirmTimeoutActionDesc: string
    payDeadlineTime: number
    payRemainSeconds: number
    payExpireAt: number
    payTimeoutActionDesc: string
    isBalancePendingPayment: boolean
    displaySummary: string
    items: Array<{
        id?: NumericValue
        staffId?: NumericValue
        staffName?: string
        staffAvatar: string
        packageName?: string
        serviceDate?: string
    }>
    actions: OrderCardAction[]
}

export type OrderStatusKey =
    | 'all'
    | 'pending_confirm'
    | 'pending_pay'
    | 'paid'
    | 'in_service'
    | 'completed'
    | 'reviewed'
    | 'cancelled'
    | 'paused'
    | 'refunding'
    | 'refund'

export type OrderStatistics = Record<OrderStatusKey, number>

export interface OrderListParams {
    page?: number
    page_size?: number
    status?: NumericValue
}

export interface IdParams {
    id: NumericValue
}

export interface OrderCreateParams extends Record<string, unknown> {
    contact_name?: string
    contact_mobile?: string
    service_address?: string
    remark?: string
}

export interface OrderCreateResponse extends Record<string, unknown> {
    id?: NumericValue
    order_id?: NumericValue
    need_pay?: string
    current_pay_stage?: string
    payment_stage?: string
}

export interface OrderPreviewResponse extends OrderApiItem {
    items?: OrderApiItemLine[]
}

export interface OrderConfirmLetter {
    letter_id?: NumericValue
    order_id?: NumericValue
    full_image_url?: string
    fallback_message?: string
    version?: NumericValue
    [key: string]: unknown
}

export interface PaymentInfo {
    id?: NumericValue
    payment_sn?: string
    pay_sn?: string
    sn?: string
    pay_amount?: NumericValue
    order_amount?: NumericValue
    pay_way?: NumericValue | string
    pay_way_desc?: string
    pay_type?: string
    pay_type_desc?: string
    pay_status?: NumericValue
    pay_status_desc?: string
    pay_time?: string | number
    [key: string]: unknown
}

export interface PayWayOption {
    pay_way: number
    name?: string
    icon?: string
    extra?: string
    is_default?: boolean | number
    [key: string]: unknown
}

export interface PayWayResponse extends OrderApiItem {
    order_amount?: NumericValue
    lists: PayWayOption[]
    pay_subject?: string
}

export interface PayRequestParams extends Record<string, unknown> {
    order_id?: NumericValue
    registration_id?: NumericValue
    from?: string
    pay_way?: NumericValue
    redirect?: string
    payment_sn?: string
}

export interface PrepayResponse extends Record<string, unknown> {
    payment_sn?: string
    pay_sn?: string
    sn?: string
    pay_way: number
    config?: unknown
}

export interface PayResultResponse {
    pay_status?: NumericValue
    order?: OrderApiItem
    payment?: PaymentInfo
    registration?: {
        id?: NumericValue
        registration_status?: NumericValue
        registration_status_desc?: string
        [key: string]: unknown
    }
    [key: string]: unknown
}

export interface QuestionnaireTask {
    id?: NumericValue
    title?: string
    status?: NumericValue
    [key: string]: unknown
}
