import {
    buildServiceRegionQuery,
    normalizeServiceRegion,
    toServiceRegionParams
} from '@/utils/service-region'

export const BOOKING_OPTION_KEYS = ['booking_option_1', 'booking_option_2'] as const
export const BOOKING_ROLE_KEYS = ['butler', 'director'] as const

export type BookingOptionKey = (typeof BOOKING_OPTION_KEYS)[number]
export type BookingRoleKey = (typeof BOOKING_ROLE_KEYS)[number]

export type BookingQuery = ReturnType<typeof normalizeBookingQuery>

export const normalizeCustomOptionKeys = (value: any): BookingOptionKey[] => {
    const rawList = Array.isArray(value)
        ? value
        : typeof value === 'string'
            ? value.split(',')
            : []

    return rawList
        .map((item) => String(item || '').trim())
        .filter((item): item is BookingOptionKey =>
            (BOOKING_OPTION_KEYS as readonly string[]).includes(item)
        )
        .filter((item, index, list) => list.indexOf(item) === index)
}

export const normalizeBookingQuery = (value: Record<string, any> | null | undefined) => {
    const region = normalizeServiceRegion(value)
    return {
        staff_id: Number(value?.staff_id || 0),
        package_id: Number(value?.package_id || 0),
        date: String(value?.date || ''),
        ...region,
        custom_option_keys: normalizeCustomOptionKeys(value?.custom_option_keys),
        butler_staff_id: Number(value?.butler_staff_id || 0),
        butler_package_id: Number(value?.butler_package_id || 0),
        director_staff_id: Number(value?.director_staff_id || 0),
        director_package_id: Number(value?.director_package_id || 0)
    }
}

export const buildBookingQuery = (value: Record<string, any> | null | undefined) => {
    const booking = normalizeBookingQuery(value)
    const params = [
        `staff_id=${booking.staff_id}`,
        `package_id=${booking.package_id}`,
        `date=${encodeURIComponent(booking.date)}`
    ]

    const regionQuery = buildServiceRegionQuery(booking)
    if (regionQuery) {
        params.push(regionQuery)
    }

    if (booking.custom_option_keys.length) {
        params.push(`custom_option_keys=${encodeURIComponent(booking.custom_option_keys.join(','))}`)
    }

    ;[
        'butler_staff_id',
        'butler_package_id',
        'director_staff_id',
        'director_package_id'
    ].forEach((key) => {
        const currentValue = Number((booking as any)[key] || 0)
        if (currentValue > 0) {
            params.push(`${key}=${currentValue}`)
        }
    })

    return params.join('&')
}

export const getStaffBookingPageUrl = (value: Record<string, any> | null | undefined) => {
    return `/packages/pages/staff_booking/staff_booking?${buildBookingQuery(value)}`
}

export const getOrderConfirmPageUrl = (value: Record<string, any> | null | undefined) => {
    return `/packages/pages/order_confirm/order_confirm?${buildBookingQuery(value)}`
}

export const getStaffDetailPageUrl = (value: Record<string, any> | null | undefined) => {
    const booking = normalizeBookingQuery(value)
    const params = [`id=${booking.staff_id}`]

    if (booking.package_id > 0) {
        params.push(`package_id=${booking.package_id}`)
    }

    if (booking.date) {
        params.push(`date=${encodeURIComponent(booking.date)}`)
    }

    const regionQuery = buildServiceRegionQuery(booking)
    if (regionQuery) {
        params.push(regionQuery)
    }

    return `/packages/pages/staff_detail/staff_detail?${params.join('&')}`
}

export const toBookingOrderParams = (value: Record<string, any> | null | undefined) => {
    const booking = normalizeBookingQuery(value)
    const params: Record<string, any> = {
        staff_id: booking.staff_id,
        package_id: booking.package_id,
        date: booking.date,
        ...toServiceRegionParams(booking)
    }

    if (booking.custom_option_keys.length) {
        params.custom_option_keys = booking.custom_option_keys
    }

    if (booking.butler_staff_id > 0 && booking.butler_package_id > 0) {
        params.butler_staff_id = booking.butler_staff_id
        params.butler_package_id = booking.butler_package_id
    }

    if (booking.director_staff_id > 0 && booking.director_package_id > 0) {
        params.director_staff_id = booking.director_staff_id
        params.director_package_id = booking.director_package_id
    }

    return params
}

export const getBookingRoleLabel = (roleKey: string) => {
    const labelMap: Record<string, string> = {
        butler: '婚礼管家',
        director: '婚礼督导'
    }
    return labelMap[roleKey] || '关联服务'
}
