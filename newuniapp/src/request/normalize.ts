import { formatMoney } from '@shared/utils/format'
import type { Paginated } from './types'

/**
 * 分页归一化。
 *
 * 后端存在两套参数（page+page_size / page_no+page_size / page+limit）
 * 和两种响应（dataLists 的 {lists,count} / paginate 的 {data,total,current_page,last_page}）。
 * 老端每个列表页各写一套取法，结果出现 res.data / res.lists / res.data.lists 三种写法。
 * 这里统一成 Paginated<T>。
 */
export function normalizePage<T = any>(raw: any, page = 1, pageSize = 10): Paginated<T> {
    // 形态 A：dataLists → { lists, count, page_no, page_size, extend }
    if (raw && Array.isArray(raw.lists)) {
        const total = Number(raw.count ?? raw.total ?? raw.lists.length)
        const size = Number(raw.page_size ?? pageSize) || pageSize
        const current = Number(raw.page_no ?? page) || page
        return { list: raw.lists, total, page: current, pageSize: size, hasMore: current * size < total }
    }

    // 形态 B：paginate → { data, total, per_page, current_page, last_page }
    if (raw && Array.isArray(raw.data)) {
        const total = Number(raw.total ?? raw.data.length)
        const size = Number(raw.per_page ?? pageSize) || pageSize
        const current = Number(raw.current_page ?? page) || page
        const last = Number(raw.last_page ?? (size ? Math.ceil(total / size) : 1))
        return { list: raw.data, total, page: current, pageSize: size, hasMore: current < last }
    }

    // 形态 C：纯数组（如 staff/myFavorites、region/serviceTree）
    if (Array.isArray(raw)) {
        return { list: raw, total: raw.length, page, pageSize, hasMore: false }
    }

    // 形态 D：v2 统一分页 { list, total, page, page_size, has_more }
    if (raw && Array.isArray(raw.list)) {
        return {
            list: raw.list,
            total: Number(raw.total ?? raw.list.length),
            page: Number(raw.page ?? page),
            pageSize: Number(raw.page_size ?? pageSize),
            hasMore: Boolean(raw.has_more)
        }
    }

    return { list: [], total: 0, page, pageSize, hasMore: false }
}

/** 金额：统一走 shared/utils/format 的 formatMoney，禁止页面自行 toFixed */
export function toMoney(value: unknown, fallback = '0.00'): string {
    return formatMoney(value as any, { fallback })
}

/** 布尔：后端大量用 0/1 与 '0'/'1' 混用 */
export function toBool(value: unknown): boolean {
    if (typeof value === 'boolean') return value
    const n = Number(value)
    return n === 1
}

/** 数字：字符串数字兜底 */
export function toNumber(value: unknown, fallback = 0): number {
    const n = Number(value)
    return Number.isFinite(n) ? n : fallback
}

/** 数组：null / 非数组一律返回空数组，避免模板里 .length 报错 */
export function toArray<T = any>(value: unknown): T[] {
    return Array.isArray(value) ? (value as T[]) : []
}

/** 倒计时：服务端返回剩余秒数，本地按秒递减 */
export function remainSeconds(seconds: unknown): number {
    const n = Number(seconds)
    return Number.isFinite(n) && n > 0 ? Math.floor(n) : 0
}

/** 秒 → mm:ss / hh:mm:ss */
export function formatCountdown(total: number): string {
    const s = Math.max(0, Math.floor(total))
    const h = Math.floor(s / 3600)
    const m = Math.floor((s % 3600) / 60)
    const sec = s % 60
    const pad = (v: number) => String(v).padStart(2, '0')
    return h > 0 ? `${pad(h)}:${pad(m)}:${pad(sec)}` : `${pad(m)}:${pad(sec)}`
}
