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
    CANCELLED = 2,
    VIEWED = 3,
    EXPIRED = 4
}

export enum QuestionnaireSendStatus {
    PENDING = 0,
    SENT = 1,
    FAILED = 2,
    SENDING = 3
}

export enum QuestionnaireConfigStatus {
    DISABLED = 0,
    ENABLED = 1
}

export enum QuestionnairePushMode {
    AUTO = 1,
    MANUAL = 2
}

export type QuestionnaireQuestionType = 'text' | 'textarea' | 'single' | 'multiple' | 'rating'

export type QuestionnaireErrorCode =
    | 'QUESTIONNAIRE_NOT_FOUND'
    | 'QUESTIONNAIRE_ALREADY_SUBMITTED'
    | 'QUESTIONNAIRE_CANCELLED'
    | 'QUESTIONNAIRE_EXPIRED'
    | 'QUESTIONNAIRE_VERSION_MISSING'
    | 'QUESTIONNAIRE_REQUIRED_MISSING'
    | 'QUESTIONNAIRE_SEND_NOT_ALLOWED'
    | 'QUESTIONNAIRE_SEND_FAILED'
    | 'QUESTIONNAIRE_TEMPLATE_INVALID'

export interface ErrorRecovery {
    retryable?: boolean
    back_url?: string
    action?: 'retry' | 'relogin' | 'back' | 'reselect_schedule' | 'view_order' | 'contact_admin'
    error_code?: string
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

export interface QuestionnaireQuestion {
    id: number
    bank_id?: number
    category?: string
    type: QuestionnaireQuestionType
    title: string
    placeholder?: string
    options?: string[]
    required: 0 | 1 | number
    sort?: number
}

export interface QuestionnaireAnswerInput {
    key: string
    value: string | string[] | number | null
}

export interface QuestionnaireTaskSummary {
    id: number
    task_sn: string
    order_id: number
    order_item_id?: number
    user_id: number
    staff_id: number
    questionnaire_id: number
    version_id: number
    version_no: number
    title_snapshot: string
    description_snapshot?: string
    status: QuestionnaireTaskStatus | number
    send_status: QuestionnaireSendStatus | number
    push_mode: QuestionnairePushMode | number
    send_count?: number
    send_time?: string | number
    last_send_time?: string | number
    send_error?: string
    next_retry_time?: string | number
    expire_time?: string | number
    submit_time?: string | number
    submitted_time?: string | number
    status_desc?: string
    send_status_desc?: string
    push_mode_desc?: string
    can_submit?: 0 | 1 | number
    can_resend?: 0 | 1 | number
}

export interface QuestionnaireAnswerSnapshot {
    id?: number
    task_id: number
    order_id: number
    questionnaire_id: number
    version_id: number
    version_no: number
    user_id: number
    staff_id: number
    questions_snapshot: QuestionnaireQuestion[]
    answers: Array<{
        key: string
        title: string
        type: QuestionnaireQuestionType | string
        value: string | string[] | number
    }>
    create_time?: string | number
}

export interface QuestionnaireTaskDetail extends QuestionnaireTaskSummary {
    questions: QuestionnaireQuestion[]
    answer: QuestionnaireAnswerSnapshot | null
}

export interface QuestionnaireConfig {
    id?: number
    staff_id: number
    title: string
    description: string
    push_mode: QuestionnairePushMode | number
    status: QuestionnaireConfigStatus | number
    draft_questions: QuestionnaireQuestion[]
    current_version_id?: number
    published_version_no?: number
    published_time?: string | number
    latest_version?: unknown | null
    pending_task_count?: number
}

export interface QuestionnaireSubmitRequest {
    id: number
    answers: QuestionnaireAnswerInput[]
}

export interface QuestionnaireFailure extends ErrorRecovery {
    error_code: QuestionnaireErrorCode
}
