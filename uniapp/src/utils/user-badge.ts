import { getUnreadCount as getNotificationUnreadCount } from '@/api/notification'
import { staffCenterProfile } from '@/api/staffCenter'

export interface UserBadgeResult {
    messageCount: number
    staffTodoCount: number
}

let unreadCountPromise: Promise<number> | null = null
let staffTodoPromise: Promise<number> | null = null

const loadUnreadCount = async (): Promise<number> => {
    if (unreadCountPromise) {
        return unreadCountPromise
    }

    unreadCountPromise = getNotificationUnreadCount()
        .then((result) => Number(result?.total || 0))
        .catch(() => 0)
        .finally(() => {
            unreadCountPromise = null
        })

    return unreadCountPromise
}

const loadStaffTodoCount = async (): Promise<number> => {
    if (staffTodoPromise) {
        return staffTodoPromise
    }

    staffTodoPromise = staffCenterProfile()
        .then((result) => Number(result?.todoCount || 0))
        .catch(() => 0)
        .finally(() => {
            staffTodoPromise = null
        })

    return staffTodoPromise
}

export const loadUserBadgeData = async (
    options: {
        loadMessage?: boolean
        loadStaffTodo?: boolean
    } = {}
): Promise<UserBadgeResult> => {
    const { loadMessage = false, loadStaffTodo = false } = options

    const [messageCount, staffTodoCount] = await Promise.all([
        loadMessage ? loadUnreadCount() : Promise.resolve(0),
        loadStaffTodo ? loadStaffTodoCount() : Promise.resolve(0)
    ])

    return {
        messageCount,
        staffTodoCount
    }
}
