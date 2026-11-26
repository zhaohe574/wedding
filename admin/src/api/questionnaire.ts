import request from '@/utils/request'

/**
 * 新人问卷模块共享接口类型。
 *
 * 说明：request 成功响应已由拦截器解包为业务 data 本身，
 * 因此这里声明的都是「解包后」的返回结构。
 */

export type QuestionnaireQuestionType = 'text' | 'textarea' | 'single' | 'multiple' | 'rating'

export interface QuestionnaireOptionItem {
    value: string
    label: string
}

export interface QuestionnaireStaffBrief {
    id?: number
    sn?: string
    name?: string
    mobile?: string
    status?: number
}

export interface QuestionnaireQuestionItem {
    id?: number
    bank_id?: number
    category?: string
    type?: string
    title?: string
    placeholder?: string
    options?: string[]
    required?: number
    sort?: number
}

export interface QuestionnaireVersionItem {
    id?: number
    version_no?: number
    title?: string
    description?: string
    questions?: QuestionnaireQuestionItem[]
    create_time?: string
}

/** 服务人员问卷配置详情（/growth.coupleQuestionnaire/detail） */
export interface CoupleQuestionnaireConfigDetail {
    id?: number
    staff_id?: number
    title?: string
    description?: string
    push_mode?: number
    /** 自动推送触发条件：1 支付后立即 / 2 距服务日前 N 天 / 3 支付后延迟 N 天 */
    auto_push_trigger?: number
    auto_push_days?: number
    auto_push_time?: string
    push_schedule_desc?: string
    status?: number
    draft_questions?: QuestionnaireQuestionItem[]
    latest_version?: QuestionnaireVersionItem | null
    bank_questions?: QuestionBankItem[]
    categories?: string[]
    question_types?: QuestionnaireOptionItem[]
    staff?: QuestionnaireStaffBrief | null
    pending_task_count?: number
    current_version_id?: number
    published_version_no?: number
    published_time?: string
    create_time?: string
    update_time?: string
}

/** 服务人员问卷配置列表行（/growth.coupleQuestionnaire/lists） */
export interface CoupleQuestionnaireConfigListItem {
    staff_id?: number
    staff_sn?: string
    staff_name?: string
    staff_mobile?: string
    staff_status?: number
    staff?: QuestionnaireStaffBrief | null
    questionnaire_id?: number
    has_config?: boolean
    title?: string
    description?: string
    draft_question_count?: number
    push_mode?: number
    push_mode_desc?: string
    auto_push_trigger?: number
    auto_push_days?: number
    auto_push_time?: string
    push_schedule_desc?: string
    status?: number
    status_desc?: string
    published?: boolean
    published_version_no?: number
    published_time?: string
    pending_task_count?: number
    submitted_task_count?: number
    total_task_count?: number
    create_time?: string
    update_time?: string
}

export interface QuestionnaireOrderBrief {
    id?: number
    order_sn?: string
    contact_name?: string
    contact_mobile?: string
    order_status?: number
    order_status_desc?: string
    service_date?: string
    wedding_date?: string
    [key: string]: any
}

export interface QuestionnaireUserBrief {
    id?: number
    nickname?: string
    mobile?: string
    [key: string]: any
}

/** 问卷任务列表行（/growth.coupleQuestionnaire/tasks） */
export interface QuestionnaireTaskItem {
    id?: number
    task_sn?: string
    order_id?: number
    user_id?: number
    staff_id?: number
    staff_name?: string
    staff?: QuestionnaireStaffBrief | null
    order?: QuestionnaireOrderBrief | null
    user?: QuestionnaireUserBrief | null
    title_snapshot?: string
    description_snapshot?: string
    version_id?: number
    version_no?: number
    push_mode?: number
    push_mode_desc?: string
    auto_push_trigger?: number
    auto_push_days?: number
    auto_push_time?: string
    push_schedule_desc?: string
    status?: number
    status_desc?: string
    send_status?: number
    send_status_desc?: string
    send_count?: number
    send_error?: string
    planned_send_time?: string
    send_time?: string
    last_send_time?: string
    viewed_time?: string
    expire_time?: string
    submit_time?: string
    submitted_time?: string
    can_resend?: number
    can_submit?: number
    create_time?: string
    update_time?: string
}

export interface QuestionnaireAnswerItem {
    key?: number | string
    question_id?: number | string
    value?: any
    [key: string]: any
}

export interface QuestionnaireAnswerRecord {
    id?: number
    task_id?: number
    order_id?: number
    questionnaire_id?: number
    version_id?: number
    version_no?: number
    user_id?: number
    staff_id?: number
    answers?: QuestionnaireAnswerItem[]
    create_time?: string
    update_time?: string
    [key: string]: any
}

/** 问卷任务详情（/growth.coupleQuestionnaire/taskDetail） */
export interface QuestionnaireTaskDetail extends QuestionnaireTaskItem {
    questions?: QuestionnaireQuestionItem[]
    answer?: QuestionnaireAnswerRecord | null
}

/**
 * 兼容旧页面 `res.data || res` 的读取方式：
 * 成功响应已由拦截器解包，正常直接使用顶层字段即可。
 */
export type CoupleQuestionnaireConfigDetailResult = CoupleQuestionnaireConfigDetail & {
    data?: CoupleQuestionnaireConfigDetail
}

export type QuestionnaireTaskDetailResult = QuestionnaireTaskDetail & {
    data?: QuestionnaireTaskDetail
}

/** 基础题库题目（/growth.coupleQuestionBank/lists 直接返回数组） */
export interface QuestionBankItem {
    id?: number
    category?: string
    category_sort?: number
    type?: string
    title?: string
    placeholder?: string
    options?: string[]
    required?: number
    sort?: number
    status?: number
    create_time?: string
    update_time?: string
}

/** 管理端新人问卷 Hub 聚合统计 */
export interface QuestionnaireOverview {
    staff_total: number
    configured_count: number
    published_count: number
    unpublished_count: number
    unconfigured_count: number
    task_total: number
    pending_count: number
    viewed_count: number
    active_task_count: number
    submitted_count: number
    cancelled_count: number
    expired_count: number
    send_pending_count: number
    send_sent_count: number
    send_failed_count: number
    submit_rate: number
    bank_total_count: number
    bank_enabled_count: number
}

export interface CoupleQuestionnaireListResult {
    lists: CoupleQuestionnaireConfigListItem[]
    total: number
    count: number
    page: number
    limit: number
    overview?: Partial<QuestionnaireOverview>
}

/** 题库列表直接返回数组；data 仅用于兼容旧页面 `res.data || res` 的读取 */
export type CoupleQuestionBankListResult = QuestionBankItem[] & {
    data?: QuestionBankItem[]
}

export interface CoupleQuestionnaireTaskListResult {
    lists: QuestionnaireTaskItem[]
    total: number
    count: number
    page: number
    limit: number
    overview?: Partial<QuestionnaireOverview>
}

export interface CoupleQuestionnaireListParams {
    page_no?: number
    page_size?: number
    staff_id?: number | ''
    keyword?: string
    published?: number | ''
    push_mode?: number | ''
    status?: number | ''
}

export interface CoupleQuestionnaireTaskListParams {
    page_no?: number
    page_size?: number
    staff_id?: number | ''
    keyword?: string
    status?: number | ''
    send_status?: number | ''
    order_id?: number | ''
}

export interface CoupleQuestionBankListParams {
    keyword?: string
    category?: string
    type?: string
    status?: number | '' | string
}

export interface CoupleQuestionnaireSavePayload {
    staff_id: number
    title: string
    description?: string
    push_mode?: number
    auto_push_trigger?: number
    auto_push_days?: number
    auto_push_time?: string
    status?: number
    questions: QuestionnaireQuestionItem[]
}

export interface CoupleQuestionnairePublishResult {
    version_id: number
    version_no: number
}

/** 发送接口按任务发送返回 { id }，按订单补发返回任务详情 */
export type CoupleQuestionnaireSendResult = Partial<QuestionnaireTaskDetail> & {
    id?: number
}

export interface CoupleQuestionBankSavePayload {
    id?: number
    category: string
    category_sort?: number
    type: string
    title: string
    placeholder?: string
    options?: string[]
    required?: number
    sort?: number
    status?: number
}

export interface CoupleQuestionBankSortPayload {
    items: Array<{ id: number; sort: number; category_sort?: number }>
}

export function coupleQuestionnaireLists(params?: CoupleQuestionnaireListParams) {
    return request.get<CoupleQuestionnaireListResult>({
        url: '/growth.coupleQuestionnaire/lists',
        params
    })
}

export function coupleQuestionnaireDetail(params: { staff_id: number }) {
    return request.get<CoupleQuestionnaireConfigDetailResult>({
        url: '/growth.coupleQuestionnaire/detail',
        params
    })
}

export function coupleQuestionnaireSave(params: CoupleQuestionnaireSavePayload) {
    return request.post<unknown>({ url: '/growth.coupleQuestionnaire/save', params })
}

export function coupleQuestionnairePublish(params: CoupleQuestionnaireSavePayload) {
    return request.post<CoupleQuestionnairePublishResult>({
        url: '/growth.coupleQuestionnaire/publish',
        params
    })
}

export function coupleQuestionnaireTasks(params?: CoupleQuestionnaireTaskListParams) {
    return request.get<CoupleQuestionnaireTaskListResult>({
        url: '/growth.coupleQuestionnaire/tasks',
        params
    })
}

export function coupleQuestionnaireTaskDetail(params: { id: number }) {
    return request.get<QuestionnaireTaskDetailResult>({
        url: '/growth.coupleQuestionnaire/taskDetail',
        params
    })
}

export function coupleQuestionnaireSend(params: { id?: number; order_id?: number }) {
    return request.post<CoupleQuestionnaireSendResult>({
        url: '/growth.coupleQuestionnaire/send',
        params
    })
}

export function coupleQuestionBankLists(params?: CoupleQuestionBankListParams) {
    return request.get<CoupleQuestionBankListResult>({
        url: '/growth.coupleQuestionBank/lists',
        params
    })
}

export function coupleQuestionBankDetail(params: { id: number }) {
    return request.get<QuestionBankItem | null>({
        url: '/growth.coupleQuestionBank/detail',
        params
    })
}

export function coupleQuestionBankSave(params: CoupleQuestionBankSavePayload) {
    return request.post<{ id: number }>({ url: '/growth.coupleQuestionBank/save', params })
}

export function coupleQuestionBankDelete(params: { id: number }) {
    return request.post<unknown>({ url: '/growth.coupleQuestionBank/delete', params })
}

export function coupleQuestionBankChangeStatus(params: { id: number; status: number }) {
    return request.post<unknown>({ url: '/growth.coupleQuestionBank/changeStatus', params })
}

export function coupleQuestionBankSort(params: CoupleQuestionBankSortPayload) {
    return request.post<unknown>({ url: '/growth.coupleQuestionBank/sort', params })
}
