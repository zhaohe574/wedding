import { formatMoney } from '@shared/utils/format'

export interface RolePickedPerson {
    staff_id: number
    package_id: number
    name: string
    avatar?: string
    price: number
}

export type BookingLockState = 'idle' | 'locking' | 'locked' | 'failed' | 'expired' | 'error'
export type BookingQuoteState = 'idle' | 'loading' | 'ready' | 'failed'
export type BookingSheetMode = 'contact' | 'address' | 'remark'
export type BookingValidateField = 'contact_name' | 'contact_mobile' | 'service_address'
export type BookingFocusTarget = 'contact' | 'address' | 'agree'

export type BookingEditSheetSavePayload =
    | { mode: 'contact'; contact_name: string; contact_mobile: string }
    | { mode: 'address'; service_address: string }
    | { mode: 'remark'; remark: string }

export interface BookingFormData {
    contact_name: string
    contact_mobile: string
    service_address: string
    remark: string
}

export interface QuoteRoleRow {
    role_key: string
    role_label: string
    text: string
}

export function pkgId(p: any) {
    return Number(p?.package_id || p?.id || 0)
}

export function pkgName(p: any) {
    return p?.package?.name || p?.name || '服务套餐'
}

export function pkgPrice(p: any) {
    return p?.price ?? p?.package?.price ?? 0
}

export function addonId(a: any) {
    return Number(a?.id || a?.addon_id || 0)
}

export function addonPrice(a: any) {
    return a?.price ?? 0
}

export function money(v: any) {
    return formatMoney(v as any, { thousand: true })
}

/** 金额小字：整数不带 .00，有角分才保留两位 */
export function shortMoney(v: any) {
    const n = Number(v || 0)
    if (!Number.isFinite(n)) return '0'
    return Number.isInteger(n) ? money(n).replace(/\.00$/, '') : money(n)
}

export function maskMobile(v: string) {
    const s = String(v || '')
    return /^\d{11}$/.test(s) ? `${s.slice(0, 3)}****${s.slice(7)}` : s
}

/** 与后端 ThinkPHP `mobile` 规则对齐：1 开头 + 第二位 3-9 + 共 11 位 */
export function isValidMobile(v: string): boolean {
    return /^1[3-9]\d{9}$/.test(String(v || '').trim())
}

/** 表单字段校验：返回错误文案，通过返回空串；弹层保存与提交前校验共用同一份 */
export function validateBookingField(field: BookingValidateField, value: string): string {
    const v = String(value || '').trim()
    if (field === 'contact_name') {
        if (!v) return '请输入联系人姓名'
        if (v.length < 2) return '姓名至少 2 个字'
        return ''
    }
    if (field === 'contact_mobile') {
        if (!v) return '请输入手机号'
        if (!isValidMobile(v)) return '请输入正确的 11 位手机号'
        return ''
    }
    if (field === 'service_address') {
        if (!v) return '请输入服务地址'
        return ''
    }
    return ''
}

export function toCents(v: any): number | null {
    if (v === null || v === undefined || v === '') return null
    const n = Number(v)
    return Number.isFinite(n) ? Math.round(n * 100) : null
}

/** 卡片副信息只放评分/单量；套餐名太长，留给详情页展示 */
export function candidateMeta(c: any) {
    const parts: string[] = []
    if (Number(c?.rating) > 0) parts.push(`${Number(c.rating).toFixed(1)} 分`)
    if (Number(c?.order_count) > 0) parts.push(`${c.order_count} 单`)
    return parts.join(' · ')
}

export function pad2(n: number) {
    return String(n).padStart(2, '0')
}

export function dateKeyOf(year: number, month: number, day: number) {
    return `${year}-${pad2(month)}-${pad2(day)}`
}
