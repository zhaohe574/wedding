/**
 * 服务号提醒软引导。
 *
 * 只在「用户已登录、尚未绑定/关注服务号、当前页有弹窗宿主」时弹一次选择框；
 * 任何网络失败、超时、离页、换号都放行业务提交 —— 提醒不能挡住下单、预约这类主流程。
 *
 * 宿主机制：page-shell 在每个页面挂一个 oa-reminder-dialog，组件自己注册 hostId →
 * 当前页实例。这里按「页面实例」反查宿主，避免页面栈里多个实例抢同一个弹窗。
 */
import { shallowReactive, watch } from 'vue'
import { getOaReminderStatus, skipOaReminderToday } from '@/api/user'
import { useUserStore } from '@/stores/user'
import cache from '@/utils/cache'
import { getOaInvitation, OA_BINDING_PATH } from '@/utils/oa-invitation'
import { BACK_URL } from '@/enums/constantEnums'

export type OaReminderChoice = 'once' | 'today' | 'settings' | 'cancel'

/** 弹窗内容与归属页：host 为空表示没有弹窗 */
export const oaReminderDialog = shallowReactive({ host: '', title: '', content: '' })

/** hostId → 注册时所在的页面实例 */
const hosts = new Map<string, unknown>()
/** 本机「今天不再提醒」内存副本（跨端缓存失败时仍然生效） */
const skipped = new Map<string, number>()

let active: { host: string; cancel: () => void } | undefined
let resolveChoice: ((choice: OaReminderChoice) => void) | undefined

const currentPage = (): any => {
    const pages = getCurrentPages()
    return pages[pages.length - 1]
}

/** 北京时间（UTC+8）次日零点，秒级时间戳 */
const dayEnd = () => (Math.floor((Date.now() / 1000 + 28800) / 86400) + 1) * 86400 - 28800

const skipKey = (id: string) => `oa_reminder_skip_${id}`

export function registerOaReminderHost(id: string): void {
    hosts.set(id, currentPage())
}

export function unregisterOaReminderHost(id: string): void {
    hosts.delete(id)
    // 弹窗正属于这一页时，离页即视为放弃本次提醒（并让业务提交继续等待新入口）
    if (active?.host === id) active.cancel()
}

export function chooseOaReminder(choice: OaReminderChoice): void {
    resolveChoice?.(choice)
}

/**
 * 是否在登录成功后弹提醒。
 *
 * 只认「独立登录」：登录中转回来的业务路径（下单、预约、售后等）由恢复后的
 * 提交动作统一走 remindBeforeOaAction，避免刚登录就叠一个弹窗挡住原来的操作。
 */
export function shouldRemindAfterLogin(): boolean {
    if (getOaInvitation()) return false
    const business =
        /\/(?:staff-detail|booking|detail|change|feed-detail|activity|aftersale|questionnaire|payment-result|waitlist)(?:\/|$)/
    if (business.test(String(cache.get(BACK_URL) || ''))) return false
    const pages = getCurrentPages()
    return !pages.slice(0, -1).some((page) => business.test('/' + String(page.route || '')))
}

/**
 * 业务提交前的提醒检查。
 *
 * 返回 false 表示「暂停本次提交」（用户要看设置、离页或换号），
 * true 表示可以继续 —— 包括所有拿不到状态的异常情况。
 */
export async function remindBeforeOaAction(): Promise<boolean> {
    const user = useUserStore()
    const token = user.token
    if (!token || getOaInvitation()) return true
    // 手机号登录可能还没写入 userInfo.id，这里用 token 尾部兜底本地跳过键
    const userId = Number(user.userId || user.userInfo?.id || user.userInfo?.user_id || 0)
    const skipIdentity = userId > 0 ? String(userId) : 'tok_' + String(token).slice(-12)

    // 已有弹窗在处理中：按「本次提交暂停」处理，避免两个提交互相顶掉
    if (active) return false

    const page = currentPage()
    const host = [...hosts].find(([, owner]) => owner === page)?.[0]
    // 当前页没有宿主（未走 page-shell 等）时静默放行
    if (!host) return true

    const identityOf = () =>
        Number(user.userId || user.userInfo?.id || user.userInfo?.user_id || 0)
    const valid = () =>
        user.token === token &&
        // userId 为 0 表示调用时还没拿到 id（手机号登录），此时只认 token，
        // 否则 id 在弹窗期间异步写入会被误判成「换号」。
        (userId === 0 || identityOf() === userId) &&
        currentPage() === page &&
        hosts.has(host)

    let cancelled = false
    let cancelWait: (value: null) => void = () => {
        /* 由下方 Promise 构造器赋值 */
    }
    const cancellation = new Promise<null>((resolve) => {
        cancelWait = resolve
    })
    const cancel = () => {
        if (cancelled) return
        cancelled = true
        cancelWait(null)
        resolveChoice?.('cancel')
    }
    active = { host, cancel }
    // 换号（token 变化）立即取消：旧账号的提醒不能落到新账号头上
    let stopTokenWatch: (() => void) | undefined
    try {
        stopTokenWatch = watch(() => user.token, cancel, { flush: 'sync' })

        const localSkipUntil = Math.max(
            skipped.get(skipIdentity) || 0,
            Number(cache.get(skipKey(skipIdentity)) || 0)
        )
        if (localSkipUntil > Date.now() / 1000) return valid()

        let timer: ReturnType<typeof setTimeout> | undefined
        const status: any = await Promise.race([
            getOaReminderStatus().catch(() => null),
            cancellation,
            new Promise<null>((resolve) => {
                timer = setTimeout(() => resolve(null), 2000)
            })
        ])
        clearTimeout(timer)
        if (cancelled || !valid()) return false

        // 拿不到状态、渠道不可用、已关注 —— 都不打扰用户
        if (
            !status ||
            status.channel_available === false ||
            (status.bound && status.follow_status === 'followed')
        ) {
            return true
        }

        if (status.reminder_snoozed_today) {
            const until = Number(status.reminder_skip_until || 0)
            if (until > 0) {
                skipped.set(skipIdentity, until)
                try {
                    cache.set(
                        skipKey(skipIdentity),
                        until,
                        Math.max(1, Math.ceil(until - Date.now() / 1000))
                    )
                } catch {
                    /* 本地缓存写入失败不影响本次跳过 */
                }
            }
            return true
        }

        const choice = await new Promise<OaReminderChoice>((resolve) => {
            resolveChoice = resolve
            if (!status.bound) {
                oaReminderDialog.title = '开启服务号提醒'
                oaReminderDialog.content =
                    '绑定后可在微信接收订单、档期和工作提醒。不设置也可继续操作。'
            } else if (status.follow_status === 'unfollowed') {
                oaReminderDialog.title = '重新关注，恢复微信提醒'
                oaReminderDialog.content =
                    '您的账号绑定已保留，重新关注即可恢复微信提醒，无需重复绑定。不设置也可继续操作。'
            } else {
                oaReminderDialog.title = '确认关注状态'
                oaReminderDialog.content =
                    '您的账号已绑定，微信关注状态暂未确认。可前往服务号查看，不影响继续操作。'
            }
            oaReminderDialog.host = host
        })
        oaReminderDialog.host = ''

        if (cancelled || !valid() || choice === 'cancel') return false

        if (choice === 'settings') {
            uni.navigateTo({
                url: OA_BINDING_PATH,
                fail: () =>
                    uni.showToast({ title: '暂时无法打开设置，请从设置页重试', icon: 'none' })
            })
            return false
        }

        if (choice === 'today') {
            const until = dayEnd()
            skipped.set(skipIdentity, until)
            try {
                cache.set(skipKey(skipIdentity), until, Math.max(1, until - Date.now() / 1000))
            } catch {
                /* 本地缓存写入失败不影响本次「今天不再提醒」 */
            }
            // 本机已即时生效，跨设备保存失败不延迟当前业务
            void skipOaReminderToday().catch(() => {})
        }

        return true
    } catch {
        return !cancelled && valid()
    } finally {
        stopTokenWatch?.()
        resolveChoice = undefined
        oaReminderDialog.host = ''
        active = undefined
    }
}
