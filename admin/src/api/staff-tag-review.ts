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

export function staffTagReviewBatchApprove(params: { ids: number[] }) {
    return request.post({ url: '/ops.staffTagReview/batchApprove', params })
}

export function staffTagReviewBatchReject(params: { ids: number[]; reject_reason: string }) {
    return request.post({ url: '/ops.staffTagReview/batchReject', params })
}
