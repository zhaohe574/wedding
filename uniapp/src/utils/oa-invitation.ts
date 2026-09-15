import cache from '@/utils/cache'

export const OA_BINDING_PATH = '/pages/oa_subscribe/oa_subscribe'
const INVITATION_KEY = 'oa_pending_invitation'

/** 登录回跳只携带页面路径，邀请单独限时保存，避免进入通用路由日志。 */
export function saveOaInvitation(value: unknown) {
    const token = typeof value === 'string' ? value : ''
    if (!/^[a-f0-9]{64}$/.test(token)) return false
    cache.set(INVITATION_KEY, token, 600)
    return true
}

export const getOaInvitation = (): string => String(cache.get(INVITATION_KEY) || '')
export const clearOaInvitation = () => cache.remove(INVITATION_KEY)

export function captureOaInvitation(path: string, query: Record<string, any> = {}) {
    if ('/' + path.replace(/^\//, '') === OA_BINDING_PATH && query.invitation !== undefined) {
        if (!saveOaInvitation(query.invitation)) clearOaInvitation()
    }
}
