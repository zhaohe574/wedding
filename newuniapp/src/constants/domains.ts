/**
 * web-view 允许打开的域名（合规 S5）。
 *
 * 问题：原来只校验 `^https://` 之后就直出。URL 来自后端配置
 * （about 的备案/版权项、utils/link.ts 的装修链接），只要后端配了**没进「业务域名」的域名**，
 * web-view 就白屏 → 审核判「页面无法打开」。
 *
 * ⚠️ 这张表必须与**微信后台「开发设置 → 业务域名」**保持一致，两处不成对会白屏。
 *    新增合作方域名时：先加到后台，再加到这里。
 */
export const WEBVIEW_ALLOWED_HOSTS = ['gelinshe.com', 'www.gelinshe.com']

/**
 * 判断 URL 是否允许在 web-view 中打开。
 *
 * 规则：
 *   - 协议必须是 `https:`（web-view 不支持 http）；
 *   - host **精确等于**列表项，或**以 `.列表项` 结尾**（子域）。
 *     加 `.` 前缀这一条是为了防 `evilgelinshe.com` 这类后缀欺骗 ——
 *     只用 `endsWith('gelinshe.com')` 会把 `evilgelinshe.com` 放行。
 *
 * 不用 `new URL()`：小程序各基础库对 URL 的支持不一致，这里用正则取 host，各端行为一致。
 */
export function isAllowedWebviewUrl(url: string): boolean {
    const raw = String(url || '').trim()
    // 反斜杠绕过：`https://evil.com\@www.gelinshe.com/x` 用 split('@') 会取到后半段，
    // 但浏览器把 `\` 归一化成 `/`，真实 host 其实是 evil.com。直接拒绝含反斜杠的 URL。
    if (raw.includes('\\')) return false

    const m = /^https:\/\/([^/?#]+)/i.exec(raw)
    if (!m) return false

    // 去掉可能存在的 userinfo 与端口
    const host = m[1].split('@').pop()!.split(':')[0].toLowerCase()
    if (!host) return false

    return WEBVIEW_ALLOWED_HOSTS.some((item) => {
        const rule = item.toLowerCase()
        return host === rule || host.endsWith(`.${rule}`)
    })
}
