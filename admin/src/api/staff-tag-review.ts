import request from '@/utils/request'

export function staffTagReviewLists(params?: any) {
    return request.get({ url: '/ops.staffTagReview/lists', params })
}

export function staffTagReviewDetail(params: { id: number }) {
    return request.get({ url: '/ops.staffTagReview/detail', params })
}

export function staffTagReviewApprove(params: { id: number }) {
    return request.post({ url: '/ops.staffTagReview/approve', params })
}

export function staffTagReviewReject(params: { id: number; reject_reason: string }) {
    return request.post({ url: '/ops.staffTagReview/reject', params })
}
