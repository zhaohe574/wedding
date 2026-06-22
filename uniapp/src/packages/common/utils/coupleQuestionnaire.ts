import type {
    CoupleQuestionnaireListResponse,
    CoupleQuestionnaireTask
} from '@/types/coupleQuestionnaire'

type QuestionnaireListPayload =
    | CoupleQuestionnaireListResponse
    | CoupleQuestionnaireTask[]
    | {
          data?: CoupleQuestionnaireListResponse | CoupleQuestionnaireTask[]
          lists?: CoupleQuestionnaireTask[]
      }
    | null
    | undefined

export const normalizeQuestionnaireLists = (
    payload: QuestionnaireListPayload
): CoupleQuestionnaireTask[] => {
    if (!payload) {
        return []
    }
    if (Array.isArray(payload)) {
        return payload
    }
    const data = payload?.data
    if (Array.isArray(data)) {
        return data
    }
    if (data && !Array.isArray(data) && Array.isArray(data.lists)) {
        return data.lists
    }
    if (Array.isArray(payload.lists)) {
        return payload.lists
    }
    return []
}
