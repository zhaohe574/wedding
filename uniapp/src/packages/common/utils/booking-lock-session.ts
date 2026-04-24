import {
    batchLockSchedule,
    lockSchedule,
    releaseScheduleLock
} from '@/packages/common/api/schedule'
import cache from '@/utils/cache'

const BOOKING_LOCK_SESSION_KEY = 'booking_lock_session'
const BOOKING_LOCK_ROLE_KEYS = ['butler', 'director'] as const

export type BookingLockRoleKey = typeof BOOKING_LOCK_ROLE_KEYS[number]

export interface BookingLockSession {
    date: string
    main_staff_id: number
    role_locks: Record<BookingLockRoleKey, number>
    updated_at: number
}

type BookingLockTarget = {
    staff_id: number
    date: string
}

let bookingLockQueue: Promise<void> = Promise.resolve()

const createEmptySession = (): BookingLockSession => ({
    date: '',
    main_staff_id: 0,
    role_locks: {
        butler: 0,
        director: 0
    },
    updated_at: 0
})

const normalizeStaffId = (value: any) => {
    const staffId = Number(value || 0)
    return Number.isInteger(staffId) && staffId > 0 ? staffId : 0
}

const normalizeDate = (value: any) => String(value || '').trim()

const normalizeSession = (value: any): BookingLockSession => {
    const session = createEmptySession()
    session.date = normalizeDate(value?.date)
    session.main_staff_id = normalizeStaffId(value?.main_staff_id)
    BOOKING_LOCK_ROLE_KEYS.forEach((roleKey) => {
        session.role_locks[roleKey] = normalizeStaffId(value?.role_locks?.[roleKey])
    })
    session.updated_at = Number(value?.updated_at || 0)
    return session
}

const hasValidSession = (session: BookingLockSession) =>
    Boolean(session.date && session.main_staff_id > 0)

const saveSession = (session: BookingLockSession) => {
    const normalized = normalizeSession(session)
    normalized.updated_at = Date.now()
    cache.set(BOOKING_LOCK_SESSION_KEY, normalized)
    return normalized
}

const runInQueue = <T>(task: () => Promise<T>) => {
    const result = bookingLockQueue.then(task, task)
    bookingLockQueue = result.then(
        () => undefined,
        () => undefined
    )
    return result
}

const normalizeTargets = (targets: BookingLockTarget[]) =>
    targets
        .map((target) => ({
            staff_id: normalizeStaffId(target.staff_id),
            date: normalizeDate(target.date)
        }))
        .filter(
            (target, index, list) =>
                target.staff_id > 0 &&
                target.date &&
                list.findIndex(
                    (item) => item.staff_id === target.staff_id && item.date === target.date
                ) === index
        )

const buildTargets = (session: BookingLockSession): BookingLockTarget[] => {
    if (!hasValidSession(session)) {
        return []
    }

    return [
        session.main_staff_id,
        ...BOOKING_LOCK_ROLE_KEYS.map((roleKey) => session.role_locks[roleKey])
    ]
        .map((staffId) => normalizeStaffId(staffId))
        .filter((staffId, index, list) => staffId > 0 && list.indexOf(staffId) === index)
        .map((staff_id) => ({
            staff_id,
            date: session.date
        }))
}

const acquireLock = async (target: BookingLockTarget) => {
    await lockSchedule({
        staff_id: target.staff_id,
        date: target.date
    })
}

const acquireBatchLocks = async (targets: BookingLockTarget[]) => {
    const normalizedTargets = normalizeTargets(targets)

    if (!normalizedTargets.length) {
        return
    }

    await batchLockSchedule({
        schedules: normalizedTargets.map((target) => [target.staff_id, target.date])
    })
}

const releaseTargets = async (targets: BookingLockTarget[], silent = true) => {
    let firstError: any = null
    await Promise.allSettled(
        targets.map(async (target) => {
            try {
                await releaseScheduleLock({
                    staff_id: target.staff_id,
                    date: target.date
                })
            } catch (error) {
                if (!silent && !firstError) {
                    firstError = error
                }
            }
        })
    )

    if (firstError) {
        throw firstError
    }
}

export const loadBookingLockSession = () => normalizeSession(cache.get(BOOKING_LOCK_SESSION_KEY))

export const clearBookingLockSession = () => {
    cache.remove(BOOKING_LOCK_SESSION_KEY)
}

export const isBookingLockSessionMatchingSelection = (
    value: Record<string, any> | null | undefined
) => {
    const session = loadBookingLockSession()
    if (!hasValidSession(session)) {
        return false
    }

    if (
        session.date !== normalizeDate(value?.date) ||
        session.main_staff_id !== normalizeStaffId(value?.staff_id)
    ) {
        return false
    }

    return BOOKING_LOCK_ROLE_KEYS.every(
        (roleKey) =>
            session.role_locks[roleKey] ===
            normalizeStaffId((value as any)?.[`${roleKey}_staff_id`])
    )
}

export const ensureMainBookingLock = (value: { staff_id: number; date: string }) =>
    runInQueue(async () => {
        const staffId = normalizeStaffId(value.staff_id)
        const date = normalizeDate(value.date)
        if (!staffId || !date) {
            throw new Error('预约信息不完整，请重新选择日期')
        }

        const currentSession = loadBookingLockSession()
        const isSameMain = currentSession.date === date && currentSession.main_staff_id === staffId

        if (!isSameMain && hasValidSession(currentSession)) {
            clearBookingLockSession()
            await releaseTargets(buildTargets(currentSession))
        }

        await acquireLock({ staff_id: staffId, date })

        const nextSession = isSameMain
            ? currentSession
            : {
                  ...createEmptySession(),
                  date,
                  main_staff_id: staffId
              }

        return saveSession(nextSession)
    })

export const replaceRoleBookingLock = (
    roleKey: BookingLockRoleKey,
    value: { staff_id: number; date: string } | null
) =>
    runInQueue(async () => {
        const session = loadBookingLockSession()
        if (!hasValidSession(session)) {
            throw new Error('预约锁已失效，请重新开始预约')
        }

        const sessionDate = normalizeDate(session.date)
        const nextStaffId = normalizeStaffId(value?.staff_id)
        const nextDate = normalizeDate(value?.date || sessionDate)
        if (nextDate !== sessionDate) {
            throw new Error('预约日期已变更，请重新开始预约')
        }

        const previousStaffId = normalizeStaffId(session.role_locks[roleKey])

        if (!nextStaffId) {
            if (previousStaffId > 0) {
                session.role_locks[roleKey] = 0
                saveSession(session)
                await releaseTargets(
                    [
                        {
                            staff_id: previousStaffId,
                            date: sessionDate
                        }
                    ],
                    true
                )
            }
            return saveSession(session)
        }

        if (previousStaffId === nextStaffId) {
            await acquireLock({
                staff_id: nextStaffId,
                date: sessionDate
            })
            session.role_locks[roleKey] = nextStaffId
            return saveSession(session)
        }

        await acquireLock({
            staff_id: nextStaffId,
            date: sessionDate
        })

        session.role_locks[roleKey] = nextStaffId
        saveSession(session)

        if (previousStaffId > 0) {
            await releaseTargets(
                [
                    {
                        staff_id: previousStaffId,
                        date: sessionDate
                    }
                ],
                true
            )
        }

        return saveSession(session)
    })

export const renewAllBookingLocks = () =>
    runInQueue(async () => {
        const session = loadBookingLockSession()
        const targets = buildTargets(session)
        if (!targets.length) {
            throw new Error('预约锁已失效，请重新开始预约')
        }

        await acquireBatchLocks(targets)

        return saveSession(session)
    })

export const releaseAllBookingLocks = (silent = true) =>
    runInQueue(async () => {
        const session = loadBookingLockSession()
        const targets = buildTargets(session)
        clearBookingLockSession()

        if (!targets.length) {
            return
        }

        await releaseTargets(targets, silent)
    })
