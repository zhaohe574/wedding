// 本地缓冲key

//token
export const TOKEN_KEY = 'token'

// 搜索历史记录
export const HISTORY = 'history'

/**
 * 登录后要去的目标页（只由路由守卫写入：未登录访问 meta.auth 页面被拦下时，
 * 把「本想去的那一页」记在这里，登录成功后进过去）。
 */
export const BACK_URL = 'back_url'

/**
 * BACK_URL 的写入时间戳。
 *
 * 为什么需要它：守卫拦下目标页后会立刻打开登录页，两步之间不会超过一两秒。
 * 用户如果在登录页点了返回、过一阵再从别处进登录页，这条 BACK_URL 就是「陈的」，
 * 照着它跳会把用户甩到另一个页面。登录页只认 5 秒内写入的目标。
 */
export const BACK_URL_AT = 'back_url_at'

export const PAY_STATUS_EVENT = 'event:payStatus'
