/**
 * 服务号专属绑定邀请凭据。
 *
 * 凭据（64 位 hex token）通过服务号邀请链接的 ?invitation= 进入小程序。
 * 安全口径：
 *  - 凭据单独限时保存（10 分钟），不进路由 query 日志、不写入 BACK_URL；
 *  - 登录回跳只携带页面纯路径，登录完成后再从缓存取凭据回填。
 */
import cache from '@/utils/cache'

export const OA_BINDING_PATH = '/pages/oa_subscribe/oa_subscribe'
const INVITATION_KEY = 'oa_pending_invitation'

/** 校验并保存邀请凭据（10 分钟有效）；非法值拒绝保存 */
export function saveOaInvitation(value: unknown): boolean {
    const token = typeof value === 'string' ? value : ''
    if (!/^[a-f0-9]{64}$/.test(token)) return false
    cache.set(INVITATION_KEY, token, 600)
    return true
}

export const getOaInvitation = (): string => String(cache.get(INVITATION_KEY) || '')

export const clearOaInvitation = (): void => cache.remove(INVITATION_KEY)

/**
 * 路由守卫里捕获邀请凭据：命中绑定页且带 invitation 参数时，
 * 合法凭据保存、非法凭据清理（避免残留旧凭据串台）。
 */
export function captureOaInvitation(path: string, query: Record<string, any> = {}): void {
    if ('/' + path.replace(/^\//, '') === OA_BINDING_PATH && query.invitation !== undefined) {
        if (!saveOaInvitation(query.invitation)) clearOaInvitation()
    }
}
