import request from '@/request'
import type { QuestionnaireTaskDetail, QuestionnaireAnswerInput } from '@shared/contracts'

/** 新人问卷：列表 / 详情 / 提交 */

/** @endpoint couple_questionnaire.lists */
export function getQuestionnaireList(params?: { page?: number; page_size?: number; status?: number }) {
    return request.get<any>('/couple_questionnaire/lists', params ?? {}, { auth: true, silent: true })
}

/** @endpoint couple_questionnaire.detail */
export function getQuestionnaireDetail(params: { id: number }) {
    return request.get<QuestionnaireTaskDetail>('/couple_questionnaire/detail', params, { auth: true, silent: true })
}

/**
 * 提交问卷（需幂等）。
 * 可填条件是 can_submit===1 或 status ∈ [0,3] —— 只看 status===0 会让「已查看」状态无法提交，
 * 这是契约测试 CQ-FE-002 明确禁止的写法。
 * @endpoint couple_questionnaire.submit
 */
export function submitQuestionnaire(data: { id: number; answers: QuestionnaireAnswerInput[] }) {
    return request.post<null>('/couple_questionnaire/submit', data as any, {
        auth: true,
        idempotent: true,
        dedupe: false,
        loading: true
    })
}
