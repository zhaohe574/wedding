/**
 * 预约上下文（服务日期 + 服务地区）的跨入口传递。
 *
 * 背景：详情页原来只从上一页 query 里读 date/region。列表页会带，但分享链接、
 * 搜索、扫码等入口通常只带 id —— 进入预约后 date 为空、region 为空，
 * 报价接口直接拒绝（“请选择服务省份”），订单确认页只能显示 0。
 *
 * 口径（2026-11-10 调整）：
 *  - 入口参数 > 最近一次用户选择（缓存）> 空；
 *  - **不再给系统默认值**：既不用「首个开通区县」，也不用「规则最早可约日」。
 *    日期和地区必须由用户在预约页明确选择，未选择时页面强制拦截并引导补全；
 *  - 参数齐全时零额外请求。
 */

import cache from '@/utils/cache'
import { EMPTY_REGION, hasRegionContext, pickRegionContext, type RegionContext } from './region'

const CACHE_KEY = 'booking_context'

export interface BookingContext {
    date: string
    region: RegionContext
}

function normalizeDate(value: unknown): string {
    const raw = String(value || '').trim()
    return /^\d{4}-\d{2}-\d{2}$/.test(raw) ? raw : ''
}

export function readBookingContext(): BookingContext {
    const saved: any = cache.get(CACHE_KEY) || {}
    const region: RegionContext = { ...EMPTY_REGION }
    for (const key of Object.keys(EMPTY_REGION) as Array<keyof RegionContext>) {
        region[key] = String(saved?.region?.[key] || '')
    }
    return { date: normalizeDate(saved?.date), region }
}

/**
 * 只补空字段：入口参数优先，缓存兜底。
 * 但如果入口给的是另一个区县，不能拿缓存里的旧区县名称去补 —— 会拼出
 * “新 district_code + 旧 district_name”的错误地区，直接整组采用入口值。
 */
function mergeRegion(primary: RegionContext, fallback: RegionContext): RegionContext {
    if (primary.district_code && fallback.district_code && primary.district_code !== fallback.district_code) {
        return { ...primary }
    }
    const region: RegionContext = { ...EMPTY_REGION }
    for (const key of Object.keys(EMPTY_REGION) as Array<keyof RegionContext>) {
        region[key] = primary[key] || fallback[key] || ''
    }
    return region
}

export function saveBookingContext(patch: { date?: string; region?: Partial<RegionContext> }): void {
    const current = readBookingContext()
    const incoming: RegionContext = { ...EMPTY_REGION }
    for (const key of Object.keys(EMPTY_REGION) as Array<keyof RegionContext>) {
        incoming[key] = String(patch.region?.[key] ?? '').trim()
    }
    const hasIncoming = Object.values(incoming).some(Boolean)
    const region = hasIncoming ? mergeRegion(incoming, current.region) : current.region
    const date = normalizeDate(patch.date) || current.date
    if (!date && !hasRegionContext(region)) return
    cache.set(CACHE_KEY, { date, region })
}

/**
 * 解析预约上下文。
 *
 * ⚠️ 只做「入口参数 + 上次选择」的合并，不再补系统默认值：
 * 默认日期/地区会让用户在不知情的情况下按一个没选过的城市结算，
 * 也会掩盖“没选日期导致金额 0”的问题。缺什么由预约页显式要求用户选择。
 */
export async function resolveBookingContext(
    options: Record<string, any> = {}
): Promise<BookingContext> {
    const cached = readBookingContext()
    const date = normalizeDate(options?.date) || cached.date
    const region = mergeRegion(pickRegionContext(options), cached.region)

    if (date || hasRegionContext(region)) saveBookingContext({ date, region })
    return { date, region }
}
