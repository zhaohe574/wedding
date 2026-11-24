import request from '@/request'

/** 咨询入口 */

export type ConsultScene = 'home' | 'staff_detail' | 'order_detail' | 'aftersale' | 'package_detail'

export interface ConsultContact {
    name: string
    role: string
    mobile: string
    avatar: string
    service_time: string
    tips: string
    specialties?: string[]
    areas?: string[]
    online?: number
    reply_avg_minutes?: number
}

export interface ConsultCustomerProfile {
    customer_name: string
    customer_mobile: string
    customer_wechat: string
    wedding_date: string
    city: string
    district: string
    budget_range: string
    service_needs: string[]
    remark: string
    intention_submitted?: boolean
}

export interface ConsultResult {
    entry_type: 'advisor' | 'fallback'
    customer_id: number
    advisor_id: number
    is_new_assignment: boolean
    contact: ConsultContact
    customer_profile?: ConsultCustomerProfile | null
    customer_service_chat: { enabled: boolean; mode: string }
}

/**
 * 解析并判断客服/顾问当前是否处于服务时段。
 * 后端给出明确 `online` 标志时以其为准；否则按 `service_time` 推导（含「工作日」判定），无法解析返回 null。
 */
export function resolveConsultOnline(serviceTime?: string, metaOnline?: number): boolean | null {
    if (typeof metaOnline === 'number') return Number(metaOnline) === 1
    const raw = String(serviceTime || '').trim()
    if (!raw) return null
    const m = /(\d{1,2})\s*[:：]\s*(\d{2})\s*[-–—~～至]\s*(\d{1,2})\s*[:：]\s*(\d{2})/.exec(raw)
    if (!m) return null

    const startH = Number(m[1])
    const startM = Number(m[2])
    const endH = Number(m[3])
    const endM = Number(m[4])
    if (startH > 23 || endH > 23 || startM > 59 || endM > 59) return null

    const now = new Date()
    if (raw.includes('工作日')) {
        const day = now.getDay()
        if (day === 0 || day === 6) return false
    }

    const cur = now.getHours() * 60 + now.getMinutes()
    const start = startH * 60 + startM
    const end = endH * 60 + endM
    return start <= end ? cur >= start && cur <= end : cur >= start || cur <= end
}

/** 补全客服头像完整 URL（空值返回空串，避免回退时拼出裸域名） */
export function resolveConsultAvatarUrl(avatar?: string, domain?: unknown): string {
    const value = String(avatar || '').trim()
    if (!value) return ''
    if (/^https?:/i.test(value) || value.startsWith('//')) return value
    const base = String(domain || '')
        .trim()
        .replace(/\/+$/, '')
    if (!base) return value
    return value.startsWith('/') ? `${base}${value}` : `${base}/${value}`
}

/**
 * 启动咨询：返回当前应联系的顾问（已分配）或兜底客服。
 * 免登录可用 —— 未登录时直接返回兜底客服，不阻断咨询。
 * @endpoint customer_service.startConsult
 */
export function startConsult(data?: {
    scene?: ConsultScene
    staff_id?: number
    order_id?: number
    category_id?: number
    wedding_date?: string
    city?: string
    district?: string
}) {
    return request.post<ConsultResult>('/customer_service/startConsult', data ?? { scene: 'home' }, {
        auth: false,
        silent: true
    })
}

/**
 * 客户主动提交婚礼需求并预约专属顾问回电
 * @endpoint customer_service.submitIntention
 */
export function submitConsultIntention(data: {
    customer_name?: string
    customer_mobile: string
    customer_wechat?: string
    wedding_date?: string
    city?: string
    district?: string
    budget_range?: string
    service_needs?: string[]
    remark?: string
}) {
    return request.post<{
        customer_id: number
        advisor_id: number
        advisor_name: string
        customer_profile: ConsultCustomerProfile | null
    }>('/customer_service/submitIntention', data)
}
