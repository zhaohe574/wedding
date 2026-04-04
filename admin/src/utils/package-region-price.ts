export const REGION_LEVEL_PROVINCE = 1
export const REGION_LEVEL_CITY = 2
export const REGION_LEVEL_DISTRICT = 3

export type RegionLevel =
    | typeof REGION_LEVEL_PROVINCE
    | typeof REGION_LEVEL_CITY
    | typeof REGION_LEVEL_DISTRICT

export type RegionPriceRow = {
    region_level: RegionLevel
    province_code: string
    province_name: string
    city_code: string
    city_name: string
    district_code: string
    district_name: string
    price: number
}

export type RegionPriceSummary = {
    total: number
    provinceCount: number
    cityCount: number
    districtCount: number
    label: string
    tooltip: string
}

const LEVEL_VALUES: RegionLevel[] = [
    REGION_LEVEL_PROVINCE,
    REGION_LEVEL_CITY,
    REGION_LEVEL_DISTRICT
]

export const normalizeRegionLevel = (value: unknown): RegionLevel => {
    const level = Number(value)
    return LEVEL_VALUES.includes(level as RegionLevel)
        ? (level as RegionLevel)
        : REGION_LEVEL_CITY
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

const buildProvinceLabel = (row: RegionPriceRow) => row.province_name || row.province_code || '未命名省份'

const buildCityLabel = (row: RegionPriceRow) => {
    const parts = [row.province_name || row.province_code, row.city_name || row.city_code].filter(Boolean)
    return parts.join(' / ') || '未命名城市'
}

const buildDistrictGroupLabel = (rows: RegionPriceRow[]) => {
    const firstRow = rows[0]
    if (!firstRow) {
        return ''
    }

    const cityLabel = buildCityLabel(firstRow)
    const districtNames = Array.from(
        new Set(rows.map((item) => item.district_name || item.district_code).filter(Boolean))
    )
    if (!districtNames.length) {
        return cityLabel
    }

    const preview = districtNames.slice(0, 2).join('、')
    if (districtNames.length <= 2) {
        return `${cityLabel}：${preview}`
    }

    return `${cityLabel}：${preview}等 ${districtNames.length} 个区县`
}

export const summarizeRegionPrices = (list: Record<string, any>[] = []): RegionPriceSummary => {
    const rows = normalizeRegionPriceRows(list)
    const provinceMap = new Map<string, RegionPriceRow>()
    const cityMap = new Map<string, RegionPriceRow>()
    const districtMap = new Map<string, RegionPriceRow>()

    rows.forEach((row) => {
        if (row.region_level === REGION_LEVEL_PROVINCE && row.province_code) {
            provinceMap.set(row.province_code, row)
            return
        }
        if (row.region_level === REGION_LEVEL_CITY && row.city_code) {
            cityMap.set(row.city_code, row)
            return
        }
        if (row.region_level === REGION_LEVEL_DISTRICT && row.city_code && row.district_code) {
            districtMap.set(`${row.city_code}:${row.district_code}`, row)
        }
    })

    const provinceCount = provinceMap.size
    const cityCount = cityMap.size
    const districtCount = districtMap.size
    const total = provinceCount + cityCount + districtCount

    if (!total) {
        return {
            total: 0,
            provinceCount: 0,
            cityCount: 0,
            districtCount: 0,
            label: '',
            tooltip: ''
        }
    }

    const tooltipLines: string[] = []
    const provinceRows = Array.from(provinceMap.values())
    const cityRows = Array.from(cityMap.values())
    const districtRows = Array.from(districtMap.values())

    if (provinceRows.length) {
        tooltipLines.push(`省级：${provinceRows.slice(0, 2).map(buildProvinceLabel).join('、')}`)
    }

    if (cityRows.length) {
        tooltipLines.push(`城市：${cityRows.slice(0, 2).map(buildCityLabel).join('、')}`)
    }

    if (districtRows.length) {
        const districtGroupMap = new Map<string, RegionPriceRow[]>()
        districtRows.forEach((row) => {
            const key = row.city_code || row.city_name || row.province_code
            const current = districtGroupMap.get(key) || []
            current.push(row)
            districtGroupMap.set(key, current)
        })

        const districtExamples = Array.from(districtGroupMap.values())
            .slice(0, 2)
            .map((group) => buildDistrictGroupLabel(group))
            .filter(Boolean)
        tooltipLines.push(`区县：${districtExamples.join('；')}`)
    }

    return {
        total,
        provinceCount,
        cityCount,
        districtCount,
        label: `省${provinceCount} / 市${cityCount} / 区${districtCount}`,
        tooltip: tooltipLines.join('\n')
    }
}
