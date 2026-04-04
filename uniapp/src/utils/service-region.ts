export type ServiceRegionSelection = {
    province_code: string
    province_name: string
    city_code: string
    city_name: string
    district_code: string
    district_name: string
}

const SERVICE_REGION_STORAGE_KEY = 'service_region_selection'

const createEmptyRegion = (): ServiceRegionSelection => ({
    province_code: '',
    province_name: '',
    city_code: '',
    city_name: '',
    district_code: '',
    district_name: ''
})

export const normalizeServiceRegion = (
    value: Record<string, any> | null | undefined
): ServiceRegionSelection => {
    return {
        province_code: String(value?.province_code || ''),
        province_name: String(value?.province_name || ''),
        city_code: String(value?.city_code || ''),
        city_name: String(value?.city_name || ''),
        district_code: String(value?.district_code || ''),
        district_name: String(value?.district_name || '')
    }
}

export const hasServiceRegion = (value: Record<string, any> | null | undefined) => {
    const region = normalizeServiceRegion(value)
    return Boolean(region.city_code && region.district_code)
}

export const formatServiceRegionText = (
    value: Record<string, any> | null | undefined,
    separator = ' / '
) => {
    const region = normalizeServiceRegion(value)
    return [region.province_name, region.city_name, region.district_name]
        .filter(Boolean)
        .join(separator)
}

export const saveServiceRegionSelection = (value: Record<string, any> | null | undefined) => {
    const region = normalizeServiceRegion(value)
    if (!hasServiceRegion(region)) {
        return
    }
    uni.setStorageSync(SERVICE_REGION_STORAGE_KEY, region)
}

export const loadServiceRegionSelection = (): ServiceRegionSelection => {
    try {
        const cached = uni.getStorageSync(SERVICE_REGION_STORAGE_KEY)
        return normalizeServiceRegion(cached)
    } catch (error) {
        return createEmptyRegion()
    }
}

export const toServiceRegionParams = (value: Record<string, any> | null | undefined) => {
    const region = normalizeServiceRegion(value)
    if (!hasServiceRegion(region)) {
        return {}
    }
    return {
        province_code: region.province_code,
        province_name: region.province_name,
        city_code: region.city_code,
        city_name: region.city_name,
        district_code: region.district_code,
        district_name: region.district_name
    }
}

export const buildServiceRegionQuery = (value: Record<string, any> | null | undefined) => {
    const params = toServiceRegionParams(value)
    return Object.entries(params)
        .map(([key, currentValue]) => `${key}=${encodeURIComponent(String(currentValue))}`)
        .join('&')
}
