import request from '@/utils/request'
import type {
    CoupleQuestionnaireListParams,
    CoupleQuestionnaireListResponse,
    CoupleQuestionnaireTask,
    SubmitCoupleQuestionnaireParams
} from '@/types/coupleQuestionnaire'

export function getCoupleQuestionnaireLists(
    params?: CoupleQuestionnaireListParams
): Promise<CoupleQuestionnaireListResponse> {
    return request.get<CoupleQuestionnaireListResponse>({
        url: '/couple_questionnaire/lists',
        params
    })
}

export function getCoupleQuestionnaireDetail(id: number): Promise<CoupleQuestionnaireTask> {
    return request.get<CoupleQuestionnaireTask>({ url: '/couple_questionnaire/detail', params: { id } })
}

export function submitCoupleQuestionnaire(data: SubmitCoupleQuestionnaireParams) {
    return request.post({ url: '/couple_questionnaire/submit', data })
}
