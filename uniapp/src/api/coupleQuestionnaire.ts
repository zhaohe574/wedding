import request from '@/utils/request'

export function getCoupleQuestionnaireLists(params?: any) {
    return request.get({ url: '/couple_questionnaire/lists', params })
}

export function getCoupleQuestionnaireDetail(id: number) {
    return request.get({ url: '/couple_questionnaire/detail', params: { id } })
}

export function submitCoupleQuestionnaire(data: { id: number; answers: any[] }) {
    return request.post({ url: '/couple_questionnaire/submit', data })
}
