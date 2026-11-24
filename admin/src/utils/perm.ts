import useUserStore from '@/stores/modules/user'

/**
 * 校验当前管理员是否具备给定权限列表中的任一权限（与 v-perms 指令语义一致）。
 */
export const hasPermission = (perms: string[]): boolean => {
    if (!Array.isArray(perms) || perms.length === 0) {
        return true
    }
    const userStore = useUserStore()
    const permissions = Array.isArray(userStore.perms) ? userStore.perms : []
    const allPermission = '*'
    if (permissions.includes(allPermission)) {
        return true
    }
    return perms.some((key: string) => permissions.includes(key))
}
