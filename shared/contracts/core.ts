// 核心跨端契约类型。当前作为只读来源，三端迁移时可逐步 import 或复制生成。

export enum RequestCodeEnum {
    NOT_INSTALL = -2,
    LOGIN_FAILURE = -1,
    FAIL = 0,
    SUCCESS = 1,
    OPEN_NEW_PAGE = 2
}

export interface ApiEnvelope<T = unknown> {
    code: RequestCodeEnum | number
    show: 0 | 1 | number
    msg: string
    data: T
    request_id: string
}

export enum OrderStatus {
    PENDING_CONFIRM = 0,
    PENDING_PAY = 1,
    PENDING_SERVICE = 2,
    IN_SERVICE = 3,
    COMPLETED = 4,
    REVIEWED = 5,
    CANCELLED = 6,
    PAUSED = 7,
    REFUNDED = 8,
    USER_DELETED = 9,
    REFUNDING = 10
}

export enum OrderPayStatus {
    UNPAID = 0,
    PAID = 1,
    PARTIAL_REFUND = 2,
    FULL_REFUND = 3
}

export enum PaymentFlowStatus {
    PENDING = 0,
    PAID = 1,
    REFUNDED = 2,
    FAILED = 3
}

export enum ScheduleStatus {
    UNAVAILABLE = 0,
    AVAILABLE = 1,
    BOOKED = 2,
    LOCKED = 3,
    RESERVED = 4
}

export enum QuestionnaireTaskStatus {
    PENDING = 0,
    SUBMITTED = 1,
    CANCELLED = 2
}

export interface ErrorRecovery {
    retryable?: boolean
    back_url?: string
    action?: 'retry' | 'relogin' | 'back' | 'reselect_schedule' | 'view_order' | 'contact_admin'
}

export interface ScheduleLockPayload {
    schedule_id: number
    staff_id: number
    schedule_date: string
    lock_expire_time: number
    lock_ttl_seconds?: number
}

export interface PaymentStatusPayload {
    pay_status: number
    pay_way?: number
    payment?: {
        payment_sn: string
        pay_status: PaymentFlowStatus | number
        pay_amount: number
        transaction_id?: string
    }
    order?: {
        id?: number
        order_id?: number
        order_sn?: string
        order_status: OrderStatus | number
        pay_status: OrderPayStatus | number
    }
}
