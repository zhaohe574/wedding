import { ElMessage } from 'element-plus'

import type {
    QuestionnaireOptionItem,
    QuestionnaireOverview,
    QuestionnaireQuestionItem
} from '@/api/questionnaire'

export type {
    CoupleQuestionBankListParams,
    CoupleQuestionBankListResult,
    CoupleQuestionBankSavePayload,
    CoupleQuestionBankSortPayload,
    CoupleQuestionnaireConfigDetail,
    CoupleQuestionnaireConfigDetailResult,
    CoupleQuestionnaireConfigListItem,
    CoupleQuestionnaireListParams,
    CoupleQuestionnaireListResult,
    CoupleQuestionnairePublishResult,
    CoupleQuestionnaireSavePayload,
    CoupleQuestionnaireSendResult,
    CoupleQuestionnaireTaskListParams,
    CoupleQuestionnaireTaskListResult,
    QuestionBankItem,
    QuestionnaireAnswerItem,
    QuestionnaireAnswerRecord,
    QuestionnaireOptionItem,
    QuestionnaireOrderBrief,
    QuestionnaireOverview,
    QuestionnaireQuestionItem,
    QuestionnaireQuestionType,
    QuestionnaireStaffBrief,
    QuestionnaireTaskDetail,
    QuestionnaireTaskDetailResult,
    QuestionnaireTaskItem,
    QuestionnaireUserBrief,
    QuestionnaireVersionItem
} from '@/api/questionnaire'

/** 可编辑题目（编辑器内部使用，local_id 仅用于列表 key 与拖拽定位） */
export interface EditableQuestionItem {
    id: number
    local_id: string
    bank_id: number
    category: string
    type: string
    title: string
    placeholder: string
    options: string[]
    required: number
    sort: number
}

/** Hub KPI 点击后带给问卷列表面板的预设筛选 */
export interface StaffConfigPresetFilter {
    published?: number | ''
    push_mode?: number | ''
    status?: number | ''
    keyword?: string
    _ts?: number
}

/** Hub KPI 点击后带给任务列表面板的预设筛选 */
export interface TaskPresetFilter {
    staff_id?: number | ''
    keyword?: string
    status?: number | ''
    send_status?: number | ''
    _ts?: number
}

export const QUESTION_TYPES: QuestionnaireOptionItem[] = [
    { value: 'text', label: '短文本' },
    { value: 'textarea', label: '长文本' },
    { value: 'single', label: '单选' },
    { value: 'multiple', label: '多选' },
    { value: 'rating', label: '评分' }
]

export const DEFAULT_CATEGORIES: string[] = [
    '新人基础信息',
    '职业与性格',
    '恋爱经过',
    '求婚故事',
    '家庭成员',
    '仪式风格',
    '特殊纪念',
    '避讳内容',
    '补充说明'
]

export const PUSH_MODE_OPTIONS = [
    { value: 1, label: '自动推送' },
    { value: 2, label: '手动触发' }
]

/** 自动推送触发条件（仅 push_mode=1 自动推送时生效） */
export const AUTO_PUSH_TRIGGER_OPTIONS = [
    { value: 1, label: '支付后立即推送', desc: '订单进入待服务后立即下发' },
    { value: 2, label: '距服务日前推送', desc: '按婚礼服务日期提前指定天数与时间点自动下发' },
    { value: 3, label: '支付后延迟推送', desc: '支付完成后延迟指定天数与时间点自动下发' }
]

/** 距服务日前推送的常用天数快捷项 */
export const BEFORE_SERVICE_DAY_PRESETS = [7, 15, 30, 60]

/** 支付后延迟推送的常用天数快捷项 */
export const AFTER_PAID_DAY_PRESETS = [1, 3, 7]

export const ENABLE_STATUS_OPTIONS = [
    { value: 1, label: '启用' },
    { value: 0, label: '停用' }
]

export const PUBLISH_STATUS_OPTIONS = [
    { value: 1, label: '已发布' },
    { value: 0, label: '未发布' }
]

export const TASK_STATUS_OPTIONS = [
    { value: 0, label: '待填写' },
    { value: 3, label: '已查看' },
    { value: 1, label: '已填写' },
    { value: 2, label: '已取消' },
    { value: 4, label: '已过期' }
]

export const SEND_STATUS_OPTIONS = [
    { value: 0, label: '待推送' },
    { value: 1, label: '已推送' },
    { value: 2, label: '推送失败' },
    { value: 3, label: '推送中' }
]

export function createEmptyOverview(): QuestionnaireOverview {
    return {
        staff_total: 0,
        configured_count: 0,
        published_count: 0,
        unpublished_count: 0,
        unconfigured_count: 0,
        task_total: 0,
        pending_count: 0,
        viewed_count: 0,
        active_task_count: 0,
        submitted_count: 0,
        cancelled_count: 0,
        expired_count: 0,
        send_pending_count: 0,
        send_sent_count: 0,
        send_failed_count: 0,
        submit_rate: 0,
        bank_total_count: 0,
        bank_enabled_count: 0
    }
}

/**
 * 统一提取接口错误文案。
 *
 * request 拦截器已经弹出服务端业务错误，reject 出来的 err 可能是空数组 []
 * 或非 Error 对象，所以只有 message/字符串确实有值时才采用，否则回退默认文案。
 */
export function extractErrorMessage(error: unknown, fallback: string): string {
    if (!error) {
        return fallback
    }
    if (typeof error === 'string') {
        return error.trim() || fallback
    }
    if (error instanceof Error) {
        return error.message.trim() || fallback
    }
    if (typeof error === 'object' && 'message' in error) {
        const message = (error as { message?: unknown }).message
        if (typeof message === 'string' && message.trim()) {
            return message
        }
    }
    return fallback
}

export function getQuestionTypeLabel(
    type?: string,
    customTypes?: Array<{ value: string; label: string }>
): string {
    const source = customTypes?.length ? customTypes : QUESTION_TYPES
    return source.find((item) => item.value === type)?.label || type || '-'
}

export function getTaskStatusMeta(status?: number | string): {
    label: string
    tagType: 'warning' | 'primary' | 'success' | 'info' | 'danger'
} {
    const value = Number(status ?? 0)
    if (value === 3) return { label: '已查看', tagType: 'primary' }
    if (value === 1) return { label: '已填写', tagType: 'success' }
    if (value === 2) return { label: '已取消', tagType: 'info' }
    if (value === 4) return { label: '已过期', tagType: 'danger' }
    return { label: '待填写', tagType: 'warning' }
}

export function getSendStatusMeta(status?: number | string): {
    label: string
    tagType: 'warning' | 'success' | 'danger' | 'primary'
} {
    const value = Number(status ?? 0)
    if (value === 1) return { label: '已推送', tagType: 'success' }
    if (value === 2) return { label: '推送失败', tagType: 'danger' }
    if (value === 3) return { label: '推送中', tagType: 'primary' }
    return { label: '待推送', tagType: 'warning' }
}

export function isTaskResendable(row: any): boolean {
    return Number(row?.can_resend) === 1 || [0, 3].includes(Number(row?.status ?? -1))
}

export function getSendActionLabel(row: any): string {
    const sendStatus = Number(row?.send_status || 0)
    if (sendStatus === 2) return '重试发送'
    if (sendStatus === 1) return '再次发送'
    if (sendStatus === 0 && row?.planned_send_time) return '立即发送'
    return '发送问卷'
}

/** 自动推送调度策略文案：手动触发 / 距服务日前 N 天 HH:MM / 支付后 N 天 HH:MM / 立即发送 */
export function formatPushScheduleSummary(
    pushMode?: number,
    trigger?: number,
    days?: number,
    pushTime?: string
): string {
    if (Number(pushMode ?? 1) === 2) {
        return '手动触发'
    }
    if (Number(trigger ?? 1) === 2) {
        return `距服务日前 ${Number(days || 15)} 天 ${pushTime || '10:00'} 自动发送`
    }
    if (Number(trigger ?? 1) === 3) {
        return `支付后 ${Number(days || 3)} 天 ${pushTime || '10:00'} 自动发送`
    }
    return '订单进入待服务后立即自动发送'
}

/** 校验自动推送时间条件；手动触发或无延迟条件时直接通过 */
export function validateAutoPushConfig(config: {
    push_mode: number
    auto_push_trigger: number
    auto_push_days: number
    auto_push_time: string
}): { valid: boolean; message?: string } {
    if (Number(config.push_mode) !== 1) {
        return { valid: true }
    }
    const trigger = Number(config.auto_push_trigger ?? 1)
    if (trigger !== 2 && trigger !== 3) {
        return { valid: true }
    }
    const days = Number(config.auto_push_days)
    if (!Number.isInteger(days) || days < 1) {
        return { valid: false, message: '请填写正确的推送条件天数' }
    }
    if (trigger === 2 && days > 365) {
        return { valid: false, message: '距服务日前推送天数需在 1-365 天之间' }
    }
    if (trigger === 3 && days > 90) {
        return { valid: false, message: '支付后延迟推送天数需在 1-90 天之间' }
    }
    if (!/^([01]\d|2[0-3]):[0-5]\d$/.test(String(config.auto_push_time || ''))) {
        return { valid: false, message: '推送时间点需为 HH:MM 格式' }
    }
    return { valid: true }
}

export function buildUserQuestionnairePath(id?: number | string): string {
    return `/packages/pkg-service/pages/questionnaire/detail?id=${Number(id || 0)}`
}

export async function copyQuestionnairePath(row: any): Promise<void> {
    const path = buildUserQuestionnairePath(row?.id)
    if (!navigator.clipboard?.writeText) {
        ElMessage.info(path)
        return
    }
    try {
        await navigator.clipboard.writeText(path)
        ElMessage.success('已复制小程序问卷路径')
    } catch {
        ElMessage.info(path)
    }
}

function normalizeOptionsValue(options: unknown): string[] {
    if (Array.isArray(options)) {
        return options.map((item) => String(item ?? '').trim()).filter(Boolean)
    }
    if (typeof options === 'string' && options.trim()) {
        try {
            const parsed = JSON.parse(options)
            return Array.isArray(parsed)
                ? parsed.map((item) => String(item ?? '').trim()).filter(Boolean)
                : []
        } catch {
            return []
        }
    }
    return []
}

export function normalizeEditableQuestion(item: any, index = 0): EditableQuestionItem {
    const rawId = Number(item?.id || 0)
    return {
        id: rawId,
        local_id: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}-${index}`,
        bank_id: Number(item?.bank_id === undefined || item?.bank_id === null ? rawId : item.bank_id) || 0,
        category: String(item?.category || '补充说明'),
        type: String(item?.type || 'textarea'),
        title: String(item?.title || ''),
        placeholder: String(item?.placeholder || ''),
        options: normalizeOptionsValue(item?.options),
        required: Number(item?.required || 0) ? 1 : 0,
        sort: Number(item?.sort || 0)
    }
}

export function serializeEditableQuestions(
    questions: EditableQuestionItem[]
): QuestionnaireQuestionItem[] {
    return questions.map((item, index) => {
        const options = Array.from(
            new Set(item.options.map((option) => String(option ?? '').trim()).filter(Boolean))
        )
        return {
            id: index + 1,
            bank_id: Number(item.bank_id || 0),
            category: item.category,
            type: item.type,
            title: item.title.trim(),
            placeholder: item.placeholder.trim(),
            options: ['single', 'multiple'].includes(item.type) ? options : [],
            required: Number(item.required) ? 1 : 0,
            sort: questions.length - index
        }
    })
}

export function validateQuestionnaireForm(form: {
    title: string
    questions: EditableQuestionItem[]
}): { valid: boolean; message?: string; invalidIndex?: number } {
    if (!form.title.trim()) {
        return { valid: false, message: '请填写问卷标题' }
    }
    if (!form.questions.length) {
        return { valid: false, message: '请至少添加一道题目' }
    }
    for (let index = 0; index < form.questions.length; index++) {
        const item = form.questions[index]
        if (!item.title.trim()) {
            return {
                valid: false,
                message: `第 ${index + 1} 题标题不能为空`,
                invalidIndex: index
            }
        }
        if (['single', 'multiple'].includes(item.type)) {
            const options = Array.from(
                new Set(item.options.map((option) => String(option ?? '').trim()).filter(Boolean))
            )
            if (options.length < 2) {
                return {
                    valid: false,
                    message: `第 ${index + 1} 题至少需要两个选项`,
                    invalidIndex: index
                }
            }
        }
    }
    return { valid: true }
}

export function groupQuestionsByCategory(
    questions: any[]
): Array<{ category: string; items: any[] }> {
    const groups: Array<{ category: string; items: any[] }> = []
    for (const question of Array.isArray(questions) ? questions : []) {
        const category = String(question?.category || '').trim() || '未分类'
        const group = groups.find((item) => item.category === category)
        if (group) {
            group.items.push(question)
        } else {
            groups.push({ category, items: [question] })
        }
    }
    return groups
}

export function getAnswerItem(question: any, answers: any[]): any {
    if (!Array.isArray(answers) || !answers.length) {
        return undefined
    }
    return answers.find((item) => String(item?.key) === String(question?.id))
}

export function formatAnswerValue(
    question: any,
    answers: any[]
): { empty: boolean; text: string; tags: string[]; rating: number } {
    const answer = getAnswerItem(question, answers)
    const raw = answer?.value
    const tags: string[] = []
    if (Array.isArray(raw)) {
        raw.forEach((value) => {
            const text = String(value ?? '').trim()
            if (text) {
                tags.push(text)
            }
        })
    }
    const single =
        typeof raw === 'number'
            ? String(raw)
            : typeof raw === 'string'
              ? raw.trim()
              : typeof raw === 'boolean'
                ? raw
                    ? '是'
                    : '否'
                : ''
    const rating = Math.max(0, Math.min(5, Number(raw) || 0))
    return {
        empty: !tags.length && !single && rating <= 0,
        text: tags.length ? tags.join('、') : single,
        tags,
        rating
    }
}
