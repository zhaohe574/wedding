import type { NumericValue, PaginationResponse } from './order'

export type QuestionnaireAnswerValue = string | number | Array<string | number>

export interface QuestionnaireAnswer {
    key: NumericValue
    value: QuestionnaireAnswerValue
}

export interface QuestionnaireQuestion {
    id?: NumericValue
    bank_id?: NumericValue
    title?: string
    type?: 'text' | 'textarea' | 'single' | 'multiple' | 'rating' | string
    required?: NumericValue
    options?: string[]
    placeholder?: string
    [key: string]: unknown
}

export interface SubmittedQuestionnaireAnswer {
    answers?: QuestionnaireAnswer[]
    [key: string]: unknown
}

export interface QuestionnaireOrderInfo {
    id?: NumericValue
    order_sn?: string
    [key: string]: unknown
}

export interface QuestionnaireStaffInfo {
    id?: NumericValue
    name?: string
    [key: string]: unknown
}

export interface CoupleQuestionnaireTask {
    data?: CoupleQuestionnaireTask
    id?: NumericValue
    order_id?: NumericValue
    order?: QuestionnaireOrderInfo
    staff?: QuestionnaireStaffInfo
    title_snapshot?: string
    description_snapshot?: string
    version_no?: NumericValue
    last_send_time?: string
    send_time?: string
    status?: NumericValue
    status_desc?: string
    send_status_desc?: string
    can_submit?: NumericValue
    questions?: QuestionnaireQuestion[]
    answer?: SubmittedQuestionnaireAnswer
    [key: string]: unknown
}

export interface CoupleQuestionnaireListParams {
    page?: number
    page_size?: number
    status?: NumericValue
    [key: string]: unknown
}

export interface CoupleQuestionnaireListResponse extends PaginationResponse<CoupleQuestionnaireTask> {
    lists?: CoupleQuestionnaireTask[]
    data?: CoupleQuestionnaireTask[]
}

export interface SubmitCoupleQuestionnaireParams {
    id: NumericValue
    answers: QuestionnaireAnswer[]
}
