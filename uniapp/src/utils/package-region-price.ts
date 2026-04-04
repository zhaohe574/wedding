export const REGION_LEVEL_PROVINCE = 1
export const REGION_LEVEL_CITY = 2
export const REGION_LEVEL_DISTRICT = 3

export type RegionLevel =
    | typeof REGION_LEVEL_PROVINCE
    | typeof REGION_LEVEL_CITY
    | typeof REGION_LEVEL_DISTRICT

export interface RegionPriceRow {
    region_level: RegionLevel
    province_code: string
    province_name: string
    city_code: string
    city_name: string
    district_code: string
    district_name: string
    price: number
}

export interface RegionPriceSummary {
    total: number
    provinceCount: number
    cityCount: number
    districtCount: number
}

const LEVEL_VALUES: RegionLevel[] = [
    REGION_LEVEL_PROVINCE,
    REGION_LEVEL_CITY,
    REGION_LEVEL_DISTRICT
]

export const normalizeRegionLevel = (value: unknown): RegionLevel => {
    const level = Number(value)
    return LEVEL_VALUES.includes(level as RegionLevel) ? (level as RegionLevel) : REGION_LEVEL_CITY
}

export const normalizeRegionPriceRows = (list: Record<string, any>[] = []): RegionPriceRow[] => {
    if (!Array.isArray(list)) {
        return []
    }

    return list
        .filter((item) => item && typeof item === 'object')
        .map((item) => ({
            region_level: normalizeRegionLevel(item.region_level),
            province_code: String(item.province_code || ''),
            province_name: String(item.province_name || ''),
            city_code: String(item.city_code || ''),
            city_name: String(item.city_name || ''),
            district_code: String(item.district_code || ''),
            district_name: String(item.district_name || ''),
            price: Number(item.price ?? 0)
        }))
}

export const summarizeRegionPrices = (list: Record<string, any>[] = []): RegionPriceSummary => {
    const rows = normalizeRegionPriceRows(list)
    const provinceKeys = new Set<string>()
    const cityKeys = new Set<string>()
    const districtKeys = new Set<string>()

    rows.forEach((row) => {
        if (row.region_level === REGION_LEVEL_PROVINCE && row.province_code) {
            provinceKeys.add(row.province_code)
            return
        }
        if (row.region_level === REGION_LEVEL_CITY && row.city_code) {
            cityKeys.add(row.city_code)
            return
        }
        if (row.region_level === REGION_LEVEL_DISTRICT && row.city_code && row.district_code) {
            districtKeys.add(`${row.city_code}:${row.district_code}`)
        }
    })

    return {
        total: rows.length,
        provinceCount: provinceKeys.size,
        cityCount: cityKeys.size,
        districtCount: districtKeys.size
    }
}
