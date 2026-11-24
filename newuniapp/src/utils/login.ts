/**
 * 登录入口（唯一）。
 *
 * 为什么要有这个文件：全站四十多处「去登录」原本各写各的 `uni.navigateTo`，
 * 谁也没告诉登录页「从哪来」，于是登录成功后 login.vue 只能 switchTab 回首页 ——
 * 用户选好的人员 / 套餐 / 档期全丢。登录页真正的正确行为是「从哪来回哪去」，
 * 但前提是入口不乱写 BACK_URL。
 *
 * 约定（与 router/index.ts 的分工）：
 *   - 路由守卫拦下 meta.auth 页面 → 写 BACK_URL + 时间戳，登录后去那一页（那页还没进栈）
 *   - 本文件的 toLogin()           → 不写 BACK_URL，登录后 navigateBack 回原页面
 *     原页面实例还在栈里，已选内容天然保留，不需要序列化到 URL 再重建
 */
import cache from './cache'
import { BACK_URL, BACK_URL_AT } from '@/enums/constantEnums'

export const LOGIN_PAGE = '/pages/login/login'

/** 同一时刻多个请求并发 401 会连开好几层登录页，这里按时间窗去重 */
let lastNavAt = 0
const NAV_DEDUPE_MS = 800

/** 清掉守卫留下的「目标页」，避免登录后串到上一次被拦下的页面 */
export function clearLoginTarget() {
    cache.remove(BACK_URL)
    cache.remove(BACK_URL_AT)
}

/**
 * 去登录。
 *
 * @param options.redirect   明确指定登录后要去哪一页（不传 = 回当前页）
 * @param options.bindMobile 带上 bind_mobile=1，登录页进入「强制绑定手机号」流程
 *
 * 不传 redirect 时就地清一次 BACK_URL：调用点可能是「上次被守卫拦下、用户放弃登录、
 * 这次又点了收藏」，不清的话登录页会认得那条旧目标，把人带走。
 */
export function toLogin(options: { redirect?: string; bindMobile?: boolean } = {}) {
    const now = Date.now()
    const pages = getCurrentPages()
    const current: any = pages[pages.length - 1]
    // 已经在登录页：重复调用只会叠页面
    if (current && `/${current.route}` === LOGIN_PAGE) return
    if (now - lastNavAt < NAV_DEDUPE_MS) return
    lastNavAt = now

    if (options.redirect) {
        cache.set(BACK_URL, options.redirect)
        cache.set(BACK_URL_AT, now)
    } else {
        clearLoginTarget()
    }

    const url = options.bindMobile ? `${LOGIN_PAGE}?bind_mobile=1` : LOGIN_PAGE

    uni.navigateTo({
        url,
        fail: () => {
            // 页面栈满（小程序上限 10 层）时 navigateTo 会失败。
            // 宁可丢掉下面那一摞页面，也不能让用户点登录没反应。
            uni.redirectTo({ url })
        }
    })
}
