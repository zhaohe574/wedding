import { shallowReactive } from 'vue'
import { oaReminderStatus, oaReminderSkipToday } from '@/api/app'
import { useUserStore } from '@/stores/user'
import cache from '@/utils/cache'
import { getOaInvitation, OA_BINDING_PATH } from '@/utils/oa-invitation'
import { BACK_URL } from '@/enums/constantEnums'

type Choice = 'once' | 'today' | 'settings' | 'cancel'
export const oaReminderDialog = shallowReactive({ host: '', title: '', content: '' })
const hosts = new Map<string, any>()
const skipped = new Map<number, number>()
let active: { host: string; cancel: () => void } | undefined
let resolveChoice: ((choice: Choice) => void) | undefined
const currentPage = () => { const pages = getCurrentPages(); return pages[pages.length - 1] }
const dayEnd = () => (Math.floor((Date.now() / 1000 + 28800) / 86400) + 1) * 86400 - 28800
const cacheKey = (id: number) => `oa_reminder_skip_${id}`

export function registerOaReminderHost(id: string) { hosts.set(id, currentPage()) }
export function unregisterOaReminderHost(id: string) {
    hosts.delete(id)
    if (active?.host === id) active.cancel()
}
export function chooseOaReminder(choice: Choice) { resolveChoice?.(choice) }

/** 仅独立登录提醒；登录中转的业务在恢复后的提交位置统一检查。 */
export function shouldRemindAfterLogin() {
    if (getOaInvitation()) return false
    const business = /\/(?:staff_detail|staff_booking|order_confirm|order_detail|order_change|dynamic_detail|my_activity|aftersale|couple_questionnaire|payment_result)(?:\/|$)/
    return !business.test(String(cache.get(BACK_URL) || '')) && !getCurrentPages().slice(0, -1).some(page => business.test('/' + page.route))
}

/** 提醒失败放行业务；离页、换号或主动查看设置时暂停原提交。 */
export async function remindBeforeOaAction(): Promise<boolean> {
    const user = useUserStore()
    const token = user.token
    const id = Number(user.userInfo.id || user.userInfo.user_id || 0)
    if (!token || !id || getOaInvitation()) return true
    if (active) return false
    const page = currentPage()
    const host = [...hosts].find(([, owner]) => owner === page)?.[0]
    if (!host) return true
    const valid = () => user.token === token && Number(user.userInfo.id || user.userInfo.user_id || 0) === id && currentPage() === page && hosts.has(host)
    let cancelled = false
    let cancelWait: (value: null) => void = () => {}
    const cancellation = new Promise<null>(resolve => { cancelWait = resolve })
    active = { host, cancel: () => { cancelled = true; cancelWait(null); resolveChoice?.('cancel') } }
    let timer: ReturnType<typeof setTimeout> | undefined
    try {
        if (Math.max(skipped.get(id) || 0, Number(cache.get(cacheKey(id)) || 0)) > Date.now() / 1000) return valid()
        const status: any = await Promise.race([
            oaReminderStatus().catch(() => null), cancellation,
            new Promise<null>(resolve => { timer = setTimeout(() => resolve(null), 2000) })
        ])
        clearTimeout(timer)
        if (cancelled || !valid()) return false
        if (!status || (status.bound && status.follow_status === 'followed')) return true
        if (status.reminder_snoozed_today) {
            skipped.set(id, Number(status.reminder_skip_until || 0))
            return true
        }
        const choice = await new Promise<Choice>(resolve => {
            resolveChoice = resolve
            oaReminderDialog.title = !status.bound ? '开启服务号提醒' : status.follow_status === 'unfollowed' ? '重新关注，恢复微信提醒' : '确认关注状态'
            oaReminderDialog.content = !status.bound
                ? '绑定后可在微信接收订单、档期和工作提醒。不设置也可继续操作。'
                : status.follow_status === 'unfollowed' ? '您的账号绑定已保留，重新关注即可恢复微信提醒，无需重复绑定。不设置也可继续操作。'
                : '您的账号已绑定，微信关注状态暂未确认。可前往服务号查看，不影响继续操作。'
            oaReminderDialog.host = host
        })
        oaReminderDialog.host = ''
        if (cancelled || !valid() || choice === 'cancel') return false
        if (choice === 'today') {
            const until = dayEnd()
            skipped.set(id, until)
            try { cache.set(cacheKey(id), until, Math.max(1, until - Date.now() / 1000)) } catch {}
            // 本机立即生效，跨设备保存失败不延迟当前业务。
            void oaReminderSkipToday().catch(() => {})
        }
        if (choice === 'settings') {
            uni.navigateTo({ url: OA_BINDING_PATH, fail: () => uni.showToast({ title: '暂时无法打开设置，请从通知中心重试', icon: 'none' }) })
            return false
        }
        return true
    } catch { return !cancelled && valid() }
    finally { clearTimeout(timer); resolveChoice = undefined; oaReminderDialog.host = ''; active = undefined }
}
