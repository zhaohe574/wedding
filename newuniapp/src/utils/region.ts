/**
 * 服务地区上下文（列表 → 详情 → 预约 → 报价/下单）。
 *
 * 为什么要有这个工具：列表页按所选地区算价，详情页和下单如果丢了这个上下文，
 * 后端会退回全国价/直接要求选择地区，出现「列表 1868、详情 1888、下单报错」。
 * 六个字段必须整组透传：省市区编码用于算价，名称用于订单的 service_region_text。
 */

export interface RegionContext {
    province_code: string
    province_name: string
    city_code: string
    city_name: string
    district_code: string
    district_name: string
}

export const EMPTY_REGION: RegionContext = {
    province_code: '',
    province_name: '',
    city_code: '',
    city_name: '',
    district_code: '',
    district_name: ''
}

/** 跳转参数可能被框架编码；只在含 %XX 时解一次，失败原样返回（与列表页 safeDecode 同口径） */
function decodeValue(value: unknown): string {
    const raw = String(value ?? '')
    if (!/%[0-9A-Fa-f]{2}/.test(raw)) return raw
    try {
        return decodeURIComponent(raw)
    } catch {
        return raw
    }
}

/** 从页面 options 读取地区；缺字段补空串，保证六字段恒定存在 */
export function pickRegionContext(options: Record<string, any> = {}): RegionContext {
    return {
        province_code: String(options?.province_code || ''),
        province_name: decodeValue(options?.province_name),
        city_code: String(options?.city_code || ''),
        city_name: decodeValue(options?.city_name),
        district_code: String(options?.district_code || ''),
        district_name: decodeValue(options?.district_name)
    }
}

/** 是否拿到了可用于算价的地区（后端要求城市 + 区县同时存在） */
export function hasRegionContext(region: RegionContext): boolean {
    return !!region.city_code && !!region.district_code
}

/** 只保留非空字段：接口参数/跳转 query 通用，空串会被 navigate 的 buildQuery 直接丢掉 */
export function regionParams(region: RegionContext): Record<string, string> {
    const result: Record<string, string> = {}
    for (const key of Object.keys(EMPTY_REGION) as Array<keyof RegionContext>) {
        const value = String(region[key] || '').trim()
        if (value) result[key] = value
    }
    return result
}

/** v2 详情只认三个编码（名称由后端按编码补全），单独给一个窄参数集 */
export function regionCodeParams(region: RegionContext): Record<string, string> {
    const result: Record<string, string> = {}
    if (region.province_code) result.province_code = region.province_code
    if (region.city_code) result.city_code = region.city_code
    if (region.district_code) result.district_code = region.district_code
    return result
}
