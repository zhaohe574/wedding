import type { StaffPackageRegionPriceItem } from '@/api/staff-center'

export const REGION_LEVEL_PROVINCE = 1 as const
export const REGION_LEVEL_CITY = 2 as const
export const REGION_LEVEL_DISTRICT = 3 as const

export interface RegionPriceDistrictRef {
    code: string
    name: string
}

export interface RegionPriceGroup {
    key: string
    region_level: 1 | 2 | 3
    province_code: string
    province_name: string
    city_code: string
    city_name: string
    districts: RegionPriceDistrictRef[]
    price: number
}

export interface RegionPriceSummary {
    total: number
    provinceCount: number
    cityCount: number
    districtCount: number
}

function roundPrice(value: unknown): number {
    const num = Number(value)
    if (!Number.isFinite(num) || num < 0) return -1
    return Math.round(num * 100) / 100
}

export function buildRegionItemUniqKey(item: Pick<
    StaffPackageRegionPriceItem,
    'region_level' | 'province_code' | 'city_code' | 'district_code'
>): string {
    return `${item.region_level}:${item.province_code}:${item.city_code}:${item.district_code}`
}

export function buildRegionGroupKey(item: {
    region_level: number
    province_code: string
    city_code: string
    price: number
}): string {
    if (item.region_level === REGION_LEVEL_PROVINCE) {
        return `1:${item.province_code}`
    }
    if (item.region_level === REGION_LEVEL_CITY) {
        return `2:${item.city_code}`
    }
    return `3:${item.city_code}:${Number(item.price || 0).toFixed(2)}`
}

/** 清洗后端或编辑态的 region_prices，口径与 PackageRegionPriceService::normalizeRegionPrices 一致 */
export function normalizeRawRegionPrices(raw: unknown): StaffPackageRegionPriceItem[] {
    if (!Array.isArray(raw)) return []
    const uniq = new Set<string>()
    const result: StaffPackageRegionPriceItem[] = []

    for (const row of raw) {
        if (!row || typeof row !== 'object') continue
        const level = Number((row as any).region_level)
        if (
            level !== REGION_LEVEL_PROVINCE &&
            level !== REGION_LEVEL_CITY &&
            level !== REGION_LEVEL_DISTRICT
        ) {
            continue
        }
        const price = roundPrice((row as any).price)
        if (price < 0) continue

        const province_code = String((row as any).province_code ?? '').trim()
        const province_name = String((row as any).province_name ?? '').trim()
        let city_code = String((row as any).city_code ?? '').trim()
        let city_name = String((row as any).city_name ?? '').trim()
        let district_code = String((row as any).district_code ?? '').trim()
        let district_name = String((row as any).district_name ?? '').trim()

        if (level === REGION_LEVEL_PROVINCE) {
            if (!province_code) continue
            city_code = ''
            city_name = ''
            district_code = ''
            district_name = ''
        } else {
            if (!city_code) continue
            if (level === REGION_LEVEL_CITY) {
                district_code = ''
                district_name = ''
            } else if (!district_code) {
                continue
            }
        }

        const item: StaffPackageRegionPriceItem = {
            region_level: level as 1 | 2 | 3,
            province_code,
            province_name: province_name || province_code,
            city_code,
            city_name: city_name || city_code,
            district_code,
            district_name: district_name || district_code,
            price
        }

        const uniqKey = buildRegionItemUniqKey(item)
        if (uniq.has(uniqKey)) continue
        uniq.add(uniqKey)
        result.push(item)
    }

    return result
}

/** 按省级 / 城市 / 同城同价区县分组并稳定排序 */
export function groupRegionPrices(flat: StaffPackageRegionPriceItem[]): RegionPriceGroup[] {
    const clean = normalizeRawRegionPrices(flat)
    const map = new Map<string, RegionPriceGroup>()

    for (const item of clean) {
        const key = buildRegionGroupKey(item)
        let group = map.get(key)
        if (!group) {
            group = {
                key,
                region_level: item.region_level,
                province_code: item.province_code,
                province_name: item.province_name,
                city_code: item.city_code,
                city_name: item.city_name,
                districts: [],
                price: item.price
            }
            map.set(key, group)
        }
        if (item.region_level === REGION_LEVEL_DISTRICT && item.district_code) {
            if (!group.districts.some((d) => d.code === item.district_code)) {
                group.districts.push({
                    code: item.district_code,
                    name: item.district_name || item.district_code
                })
            }
        }
    }

    const groups = Array.from(map.values())
    groups.sort((a, b) => {
        if (a.region_level !== b.region_level) return a.region_level - b.region_level
        if (a.province_code !== b.province_code) {
            return a.province_code.localeCompare(b.province_code)
        }
        if (a.city_code !== b.city_code) {
            return a.city_code.localeCompare(b.city_code)
        }
        return a.price - b.price
    })
    return groups
}

/** 将 UI 分组拍平为后端 region_prices 提交数组 */
export function flattenRegionPriceGroups(
    groups: RegionPriceGroup[]
): StaffPackageRegionPriceItem[] {
    const flat: StaffPackageRegionPriceItem[] = []
    for (const group of groups) {
        if (group.region_level === REGION_LEVEL_DISTRICT) {
            for (const d of group.districts) {
                flat.push({
                    region_level: REGION_LEVEL_DISTRICT,
                    province_code: group.province_code,
                    province_name: group.province_name,
                    city_code: group.city_code,
                    city_name: group.city_name,
                    district_code: d.code,
                    district_name: d.name,
                    price: group.price
                })
            }
        } else {
            flat.push({
                region_level: group.region_level,
                province_code: group.province_code,
                province_name: group.province_name,
                city_code: group.region_level === REGION_LEVEL_CITY ? group.city_code : '',
                city_name: group.region_level === REGION_LEVEL_CITY ? group.city_name : '',
                district_code: '',
                district_name: '',
                price: group.price
            })
        }
    }
    return normalizeRawRegionPrices(flat)
}

/** 从原始 region_prices 中剔除指定分组 */
export function removeGroupFromFlat(
    flat: StaffPackageRegionPriceItem[],
    groupKey: string
): StaffPackageRegionPriceItem[] {
    return normalizeRawRegionPrices(flat).filter((item) => buildRegionGroupKey(item) !== groupKey)
}

/**
 * 构建已占用地区映射：用于新增/编辑弹层中置灰和防重复提示。
 * key 口径：省级 1:{province_code} · 城市 2:{city_code} · 区县 3:{city_code}:{district_code}
 */
export function buildOccupiedKeyMap(
    flat: StaffPackageRegionPriceItem[],
    excludeGroupKey?: string | null
): Map<string, number> {
    const occupied = new Map<string, number>()
    for (const item of normalizeRawRegionPrices(flat)) {
        if (excludeGroupKey && buildRegionGroupKey(item) === excludeGroupKey) {
            continue
        }
        if (item.region_level === REGION_LEVEL_PROVINCE) {
            occupied.set(`1:${item.province_code}`, item.price)
        } else if (item.region_level === REGION_LEVEL_CITY) {
            occupied.set(`2:${item.city_code}`, item.price)
        } else if (item.region_level === REGION_LEVEL_DISTRICT) {
            occupied.set(`3:${item.city_code}:${item.district_code}`, item.price)
        }
    }
    return occupied
}

export function summarizeRegionPrices(flat: StaffPackageRegionPriceItem[]): RegionPriceSummary {
    const clean = normalizeRawRegionPrices(flat)
    let provinceCount = 0
    let cityCount = 0
    let districtCount = 0
    for (const item of clean) {
        if (item.region_level === REGION_LEVEL_PROVINCE) provinceCount += 1
        else if (item.region_level === REGION_LEVEL_CITY) cityCount += 1
        else if (item.region_level === REGION_LEVEL_DISTRICT) districtCount += 1
    }
    return {
        total: clean.length,
        provinceCount,
        cityCount,
        districtCount
    }
}

export function getLevelShortLabel(level: number): string {
    if (level === REGION_LEVEL_PROVINCE) return '省级'
    if (level === REGION_LEVEL_CITY) return '城市'
    if (level === REGION_LEVEL_DISTRICT) return '区县'
    return '地区'
}

export function formatMoneyNum(value: number): string {
    const rounded = Math.round(Number(value || 0) * 100) / 100
    if (Number.isInteger(rounded)) return String(rounded)
    return rounded.toFixed(2).replace(/0$/, '')
}

export function formatPriceDiff(price: number, basePrice: number): string {
    if (!Number.isFinite(basePrice) || basePrice <= 0 || !Number.isFinite(price)) return ''
    const diff = Math.round((price - basePrice) * 100) / 100
    if (Math.abs(diff) < 0.005) return '同基础价'
    return diff > 0 ? `+¥${formatMoneyNum(diff)}` : `-¥${formatMoneyNum(Math.abs(diff))}`
}

export function buildGroupTitle(group: RegionPriceGroup): string {
    if (group.region_level === REGION_LEVEL_PROVINCE) {
        return group.province_name || group.province_code || '未命名省份'
    }
    const p = (group.province_name || '').trim()
    const c = (group.city_name || group.city_code || '').trim()
    if (!p || p === c) return c || '未命名城市'
    return `${p} · ${c}`
}

export function buildGroupSubtitle(group: RegionPriceGroup): string {
    if (group.region_level === REGION_LEVEL_PROVINCE) {
        return '全省统一价（未命中城市/区县价时生效）'
    }
    if (group.region_level === REGION_LEVEL_CITY) {
        return '全市统一价（未命中区县价时生效）'
    }
    const names = group.districts.map((d) => d.name).filter(Boolean)
    if (!names.length) return '区县专属价'
    if (names.length <= 3) {
        return names.join('、')
    }
    return `${names.slice(0, 3).join('、')} 等 ${names.length} 个区县`
}
