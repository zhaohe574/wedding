/**
 * 11 新人问卷 / 12 问卷已提交。
 *
 * 后端题型只有 5 种（text / textarea / single / multiple / rating），
 * 且 rating 固定 5 星（现网 questionnaire/detail.vue 写死 for n in 5）。
 * 设计稿 11 屏要 1–10 分 + 两端含义标注（「1 安静 · 10 热烈」）→ F-32 / F-33。
 *
 * 可填条件必须按 can_submit===1 或 status ∈ [0,3] 判断，
 * 只看 status===0 会让用户点开一次（变已查看）后再也交不了 —— 契约测试 CQ-FE-002 明令禁止。
 */

import type {
    QuestionnaireQuestionType,
    QuestionnaireTaskStatus,
    QuestionnaireAnswerInput
} from '@shared/contracts'
import type { BitFlag, TimeText } from './common'

/** 评分题的刻度与两端含义。✘ F-32：后端无 min/max/labels */
export interface RatingConfig {
    min: number
    max: number
    min_label?: string
    max_label?: string
    /** 是否显示分数值 */
    show_value?: boolean
}

/** 题目。在 shared QuestionnaireQuestion 基础上补设计稿需要的展示字段 */
export interface QuestionVO {
    id: number
    /** 提交时的 key，缺省用 String(id) —— 现网两种都在用，页面必须兼容 */
    key?: string
    type: QuestionnaireQuestionType
    title: string
    /** ✘ F-33 每道题一句说明（「会写进开场白」） */
    hint?: string
    placeholder?: string
    options?: string[]
    required: BitFlag
    sort?: number
    /** ◐ 多选题的最少/最多选几项 */
    min_select?: number
    max_select?: number
    /** ✘ F-32 评分题刻度 */
    rating?: RatingConfig
}

/** 已提交的答案快照 */
export interface QuestionnaireAnswerVO {
    key: string
    title: string
    type: QuestionnaireQuestionType | string
    value: string | string[] | number
}

/** 11/12 屏任务详情 */
export interface QuestionnaireTaskVO {
    id: number
    task_sn?: string
    order_id: number
    staff_id?: number
    title_snapshot: string
    description_snapshot?: string
    status: QuestionnaireTaskStatus | number
    status_desc?: string
    /** ◐ 契约里是 version_no，「第 1 版」直接用它 */
    version_no?: number
    can_submit?: BitFlag
    expire_time?: TimeText
    submit_time?: TimeText
    questions: QuestionVO[]
    answer?: { answers: QuestionnaireAnswerVO[]; create_time?: TimeText } | null
    /** ✘ F-34 草稿（11 屏「草稿已保存」）。后端无草稿接口，只能本地存 */
    draft?: { answers: Array<{ key: string; value: unknown }>; saved_at?: number } | null
}

/** 页面答案表：key → 值。单选/文本存字符串，多选存数组，评分存数字 */
export type AnswerMap = Record<string, string | string[] | number | null>

/** 进度计算（11 屏「已完成 6/12」「还差 6 题」） */
export interface QuestionnaireProgress {
    answered: number
    total: number
    required_missing: number
    percent: number
}

/** 提交入参：与 openapi POST /couple_questionnaire/submit 完全一致 */
export interface QuestionnaireSubmitPayload {
    id: number
    answers: QuestionnaireAnswerInput[]
}

/** 12 屏摘要行 */
export interface QuestionnaireResultRow {
    label: string
    value: string
    /** 「偏温暖」这类解释性尾巴 */
    note?: string
}
