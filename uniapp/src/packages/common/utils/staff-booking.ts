import {
    buildServiceRegionQuery,
    normalizeServiceRegion,
    toServiceRegionParams
} from '@/utils/service-region'
import cache from '@/utils/cache'

export const BOOKING_ROLE_KEYS = ['butler', 'director'] as const

export type BookingRoleKey = typeof BOOKING_ROLE_KEYS[number]

export const BOOKING_RETURN_MODE_DETAIL_BACK = 'detail_back' as const

export type BookingReturnMode = typeof BOOKING_RETURN_MODE_DETAIL_BACK | ''

export type BookingQuery = ReturnType<typeof normalizeBookingQuery>

export type StaffDetailReturnState = {
    staff_id: number
    package_id: number
}

export type StaffDetailRestoreSnapshot = {
    staff_id: number
    package_id: number
    staff_info: Record<string, any> | null
    selected_region: ReturnType<typeof normalizeServiceRegion>
    preset_date: string
    current_tab: string
    scroll_top: number
    saved_at: number
}

const STAFF_DETAIL_RETURN_STATE_KEY = 'staff_detail_return_state'
const STAFF_DETAIL_RESTORE_SNAPSHOT_KEY = 'staff_detail_restore_snapshot'
const STAFF_DETAIL_RESTORE_SNAPSHOT_EXPIRE = 120

const normalizeBookingReturnMode = (value: unknown): BookingReturnMode => {
    return value === BOOKING_RETURN_MODE_DETAIL_BACK ? BOOKING_RETURN_MODE_DETAIL_BACK : ''
}

const normalizeStaffDetailReturnState = (
    value: Record<string, any> | null | undefined
): StaffDetailReturnState => {
    return {
        staff_id: Number(value?.staff_id || 0),
        package_id: Number(value?.package_id || 0)
    }
}

const normalizeStaffDetailRestoreSnapshot = (
    value: Record<string, any> | null | undefined
): StaffDetailRestoreSnapshot => {
    const scrollTop = Number(value?.scroll_top || 0)
    const savedAt = Number(value?.saved_at || 0)

    return {
        staff_id: Number(value?.staff_id || 0),
        package_id: Number(value?.package_id || 0),
        staff_info:
            value?.staff_info && typeof value.staff_info === 'object'
                ? (value.staff_info as Record<string, any>)
                : null,
        selected_region: normalizeServiceRegion(value?.selected_region),
        preset_date: String(value?.preset_date || ''),
        current_tab: String(value?.current_tab || ''),
        scroll_top: Number.isFinite(scrollTop) && scrollTop > 0 ? scrollTop : 0,
        saved_at: Number.isFinite(savedAt) && savedAt > 0 ? savedAt : 0
    }
}

export const normalizeAddonIds = (value: any): number[] => {
    const rawList = Array.isArray(value) ? value : typeof value === 'string' ? value.split(',') : []

    return rawList
        .map((item) => Number(item))
        .filter((item) => Number.isInteger(item) && item > 0)
        .filter((item, index, list) => list.indexOf(item) === index)
}

export const normalizeBookingQuery = (value: Record<string, any> | null | undefined) => {
    const region = normalizeServiceRegion(value)
    const flowTotalSteps = Number(value?.flow_total_steps || 0)
    return {
        staff_id: Number(value?.staff_id || 0),
        package_id: Number(value?.package_id || 0),
        waitlist_id: Number(value?.waitlist_id || 0),
        date: String(value?.date || ''),
        ...region,
        addon_ids: normalizeAddonIds(value?.addon_ids),
        butler_staff_id: Number(value?.butler_staff_id || 0),
        butler_package_id: Number(value?.butler_package_id || 0),
        director_staff_id: Number(value?.director_staff_id || 0),
        director_package_id: Number(value?.director_package_id || 0),
        return_mode: normalizeBookingReturnMode(value?.return_mode),
        flow_total_steps:
            Number.isInteger(flowTotalSteps) && flowTotalSteps > 0 ? flowTotalSteps : 0
    }
}

export const buildBookingQuery = (value: Record<string, any> | null | undefined) => {
    const booking = normalizeBookingQuery(value)
    const params = [
        `staff_id=${booking.staff_id}`,
        `package_id=${booking.package_id}`,
        `date=${encodeURIComponent(booking.date)}`
    ]

    if (booking.waitlist_id > 0) {
        params.push(`waitlist_id=${booking.waitlist_id}`)
    }

    const regionQuery = buildServiceRegionQuery(booking)
    if (regionQuery) {
        params.push(regionQuery)
    }

    if (booking.addon_ids.length) {
        params.push(`addon_ids=${encodeURIComponent(booking.addon_ids.join(','))}`)
    }

    ;['butler_staff_id', 'butler_package_id', 'director_staff_id', 'director_package_id'].forEach(
        (key) => {
            const currentValue = Number((booking as any)[key] || 0)
            if (currentValue > 0) {
                params.push(`${key}=${currentValue}`)
            }
        }
    )

    if (booking.return_mode) {
        params.push(`return_mode=${encodeURIComponent(booking.return_mode)}`)
    }

    if (booking.flow_total_steps > 0) {
        params.push(`flow_total_steps=${booking.flow_total_steps}`)
    }

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

    if (booking.waitlist_id > 0) {
        params.push(`waitlist_id=${booking.waitlist_id}`)
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

export const saveStaffDetailReturnState = (value: Record<string, any> | null | undefined) => {
    const state = normalizeStaffDetailReturnState(value)

    if (state.staff_id <= 0) {
        cache.remove(STAFF_DETAIL_RETURN_STATE_KEY)
        return
    }

    cache.set(STAFF_DETAIL_RETURN_STATE_KEY, state, 60)
}

export const consumeStaffDetailReturnState = (): StaffDetailReturnState | null => {
    const state = normalizeStaffDetailReturnState(
        cache.get(STAFF_DETAIL_RETURN_STATE_KEY) as Record<string, any> | null
    )

    cache.remove(STAFF_DETAIL_RETURN_STATE_KEY)

    return state.staff_id > 0 ? state : null
}

export const saveStaffDetailRestoreSnapshot = (
    value: Record<string, any> | null | undefined,
    expire = STAFF_DETAIL_RESTORE_SNAPSHOT_EXPIRE
) => {
    const snapshot = normalizeStaffDetailRestoreSnapshot(value)

    if (snapshot.staff_id <= 0 || !snapshot.staff_info) {
        cache.remove(STAFF_DETAIL_RESTORE_SNAPSHOT_KEY)
        return
    }

    cache.set(
        STAFF_DETAIL_RESTORE_SNAPSHOT_KEY,
        {
            ...snapshot,
            saved_at: Date.now()
        },
        expire
    )
}

export const loadStaffDetailRestoreSnapshot = (): StaffDetailRestoreSnapshot | null => {
    const snapshot = normalizeStaffDetailRestoreSnapshot(
        cache.get(STAFF_DETAIL_RESTORE_SNAPSHOT_KEY) as Record<string, any> | null
    )

    return snapshot.staff_id > 0 && snapshot.staff_info ? snapshot : null
}

export const consumeStaffDetailRestoreSnapshot = (): StaffDetailRestoreSnapshot | null => {
    const snapshot = loadStaffDetailRestoreSnapshot()

    cache.remove(STAFF_DETAIL_RESTORE_SNAPSHOT_KEY)

    return snapshot
}

export const clearStaffDetailRestoreSnapshot = () => {
    cache.remove(STAFF_DETAIL_RESTORE_SNAPSHOT_KEY)
}

export const toBookingOrderParams = (value: Record<string, any> | null | undefined) => {
    const booking = normalizeBookingQuery(value)
    const params: Record<string, any> = {
        staff_id: booking.staff_id,
        package_id: booking.package_id,
        date: booking.date,
        ...toServiceRegionParams(booking)
    }

    if (booking.waitlist_id > 0) {
        params.waitlist_id = booking.waitlist_id
    }

    if (booking.addon_ids.length) {
        params.addon_ids = booking.addon_ids
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
