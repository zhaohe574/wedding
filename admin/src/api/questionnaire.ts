import request from '@/utils/request'

export function coupleQuestionnaireLists(params?: any) {
    return request.get({ url: '/growth.coupleQuestionnaire/lists', params })
}

export function coupleQuestionnaireDetail(params: any) {
    return request.get({ url: '/growth.coupleQuestionnaire/detail', params })
}

export function coupleQuestionnaireSave(params: any) {
    return request.post({ url: '/growth.coupleQuestionnaire/save', params })
}

export function coupleQuestionnairePublish(params: any) {
    return request.post({ url: '/growth.coupleQuestionnaire/publish', params })
}

export function coupleQuestionnaireTasks(params?: any) {
    return request.get({ url: '/growth.coupleQuestionnaire/tasks', params })
}

export function coupleQuestionnaireTaskDetail(params: any) {
    return request.get({ url: '/growth.coupleQuestionnaire/taskDetail', params })
}

export function coupleQuestionnaireSend(params: any) {
    return request.post({ url: '/growth.coupleQuestionnaire/send', params })
}

export function coupleQuestionBankLists(params?: any) {
    return request.get({ url: '/growth.coupleQuestionBank/lists', params })
}

export function coupleQuestionBankDetail(params: any) {
    return request.get({ url: '/growth.coupleQuestionBank/detail', params })
}

export function coupleQuestionBankSave(params: any) {
    return request.post({ url: '/growth.coupleQuestionBank/save', params })
}

export function coupleQuestionBankDelete(params: any) {
    return request.post({ url: '/growth.coupleQuestionBank/delete', params })
}

export function coupleQuestionBankChangeStatus(params: any) {
    return request.post({ url: '/growth.coupleQuestionBank/changeStatus', params })
}

export function coupleQuestionBankSort(params: any) {
    return request.post({ url: '/growth.coupleQuestionBank/sort', params })
}
