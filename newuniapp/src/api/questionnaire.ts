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
 *
 * 可填条件是 can_submit===1 或 status ∈ [0,3] —— 只看 status===0 会让「已查看」状态无法提交，
 * 这是契约测试 CQ-FE-002 明确禁止的写法。
 *
 * 幂等键复用（重要）：
 *   请求层默认「一次调用一个 key，重试之间复用」。但「提交失败 → 用户点重试」是**两次调用**，
 *   默认会拿到两个不同 key，若第一次其实已到服务端，就会被当成新提交。
 *   所以重试时必须把第一次的 key 传进来：
 *
 *   const key = `q-${taskId}-${Date.now()}`   // 生成一次，存在页面/本地，直到提交成功
 *   await submitQuestionnaire(data, key)      // 每次重试都传同一个 key
 *
 *   后端 yaml 已声明「已提交任务重复提交幂等返回成功但不得覆盖答案」，
 *   前提就是它能认出这是同一次提交 —— 靠的就是这个 key。
 * @endpoint couple_questionnaire.submit
 */
export function submitQuestionnaire(
    data: { id: number; answers: QuestionnaireAnswerInput[] },
    idempotencyKey?: string
) {
    return request.post<null>('/couple_questionnaire/submit', data as any, {
        auth: true,
        idempotent: true,
        dedupe: false,
        loading: true,
        ...(idempotencyKey ? { idempotencyKey } : {})
    })
}
