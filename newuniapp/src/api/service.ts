import request from '@/request'

/** 咨询入口 */

export interface ConsultContact {
    name: string
    role: string
    mobile: string
    avatar: string
    service_time: string
    tips: string
}

export interface ConsultResult {
    entry_type: 'advisor' | 'fallback'
    customer_id: number
    advisor_id: number
    is_new_assignment: boolean
    contact: ConsultContact
    customer_service_chat: { enabled: boolean; mode: string }
}

/**
 * 启动咨询：返回当前应联系的顾问（已分配）或兜底客服。
 * 免登录可用 —— 未登录时直接返回兜底客服，不阻断咨询。
 * @endpoint customer_service.startConsult
 */
export function startConsult(data?: { scene?: string; staff_id?: number; order_id?: number; category_id?: number }) {
    return request.post<ConsultResult>('/customer_service/startConsult', data ?? { scene: 'home' }, {
        auth: false,
        silent: true
    })
}
