import type { ActivityRegistrationVO } from '@/api/types/activity'

export type ActivityBadgeTone =
    | 'success'
    | 'warning'
    | 'danger'
    | 'info'
    | 'neutral'
    | 'primary'

const WEEK_NAMES = ['周日', '周一', '周二', '周三', '周四', '周五', '周六']

function pad2(n: number): string {
    return n < 10 ? `0${n}` : String(n)
}

/** 统一把秒/毫秒/日期串转成毫秒时间戳 */
export function toMs(val?: number | string | null): number {
    if (val === null || val === undefined || val === '') return 0
    if (typeof val === 'number') {
        if (!Number.isFinite(val) || val <= 0) return 0
        return val < 1e12 ? val * 1000 : val
    }
    const raw = String(val).trim()
    if (!raw) return 0
    if (/^\d+$/.test(raw)) {
        const num = Number(raw)
        if (!Number.isFinite(num) || num <= 0) return 0
        return num < 1e12 ? num * 1000 : num
    }
    const parsed = new Date(raw.replace(/-/g, '/')).getTime()
    return Number.isNaN(parsed) ? 0 : parsed
}

export function activityStartTs(r: Partial<ActivityRegistrationVO>): number {
    return Number(r.activity_start_time || r.dynamic?.activity_start_time || 0)
}

export function activityEndTs(r: Partial<ActivityRegistrationVO>): number {
    const rawEnd = Number(r.activity_end_time || r.dynamic?.activity_end_time || 0)
    if (rawEnd > 0) return rawEnd
    const start = activityStartTs(r)
    return start > 0 ? start + 86400 : 0
}

export function activityTitle(r: Partial<ActivityRegistrationVO>): string {
    return String(r.dynamic?.title || '精彩主题活动')
}

export function activityLocation(r: Partial<ActivityRegistrationVO>): string {
    return String(r.activity_location || r.dynamic?.location || '')
}

export function activityCover(r: Partial<ActivityRegistrationVO>): string {
    if (r.activity_cover) return String(r.activity_cover)
    const imgs = r.dynamic?.images
    return Array.isArray(imgs) && imgs.length ? String(imgs[0] || '') : ''
}

export function isActivityEnded(r: Partial<ActivityRegistrationVO>, nowMs = Date.now()): boolean {
    if (Number(r.is_activity_ended) === 1) return true
    const endMs = toMs(activityEndTs(r))
    return endMs > 0 && endMs <= nowMs
}

export function formatDayOf(ts?: number | string): string {
    const ms = toMs(ts)
    if (!ms) return '—'
    const d = new Date(ms)
    return `${pad2(d.getMonth() + 1)}.${pad2(d.getDate())}`
}

export function formatWeekOf(ts?: number | string): string {
    const ms = toMs(ts)
    if (!ms) return ''
    return WEEK_NAMES[new Date(ms).getDay()] || ''
}

export function formatShortTime(ts?: number | string): string {
    const ms = toMs(ts)
    if (!ms) return '时间待定'
    const d = new Date(ms)
    return `${pad2(d.getMonth() + 1)}.${pad2(d.getDate())} ${WEEK_NAMES[d.getDay()]} ${pad2(
        d.getHours()
    )}:${pad2(d.getMinutes())}`
}

export function formatFullTime(ts?: number | string): string {
    const ms = toMs(ts)
    if (!ms) return '—'
    const d = new Date(ms)
    return `${d.getFullYear()}.${pad2(d.getMonth() + 1)}.${pad2(d.getDate())} ${pad2(
        d.getHours()
    )}:${pad2(d.getMinutes())}`
}

export function formatClockTime(ts?: number | string): string {
    const ms = toMs(ts)
    if (!ms) return ''
    const d = new Date(ms)
    return `${pad2(d.getHours())}:${pad2(d.getMinutes())}`
}

export function formatCountdown(remainSeconds: number): string {
    const s = Math.max(0, Math.floor(remainSeconds))
    const hours = Math.floor(s / 3600)
    const mins = Math.floor((s % 3600) / 60)
    const secs = s % 60
    if (hours > 0) return `${pad2(hours)}:${pad2(mins)}:${pad2(secs)}`
    return `${pad2(mins)}:${pad2(secs)}`
}

export function getPayRemainSeconds(r: Partial<ActivityRegistrationVO>, nowSec: number): number {
    if (Number(r.registration_status) !== 0) return 0
    const deadline = Number(r.pay_deadline_time || 0)
    if (deadline > 0) return Math.max(0, deadline - nowSec)
    return Math.max(0, Number(r.pay_remain_seconds || 0))
}

export function countdownBadgeText(r: Partial<ActivityRegistrationVO>, nowMs = Date.now()): string {
    if (Number(r.checkin_status) === 1) return '已签到核销'
    const startMs = toMs(activityStartTs(r))
    if (!startMs) return '已确认席位'
    const diff = startMs - nowMs
    if (diff <= 0) return '进行中'
    const nowDate = new Date(nowMs)
    const startDate = new Date(startMs)
    const sameDay =
        nowDate.getFullYear() === startDate.getFullYear() &&
        nowDate.getMonth() === startDate.getMonth() &&
        nowDate.getDate() === startDate.getDate()
    if (sameDay) return '今日开始'
    const days = Math.max(1, Math.ceil(diff / 86400000))
    return `还有 ${days} 天`
}

export function resolveStatusBadge(
    r: Partial<ActivityRegistrationVO>,
    nowMs = Date.now()
): { label: string; tone: ActivityBadgeTone } {
    const status = Number(r.registration_status ?? -1)
    if (status === 0) return { label: '待支付', tone: 'warning' }
    if (status === 1) {
        if (isActivityEnded(r, nowMs)) return { label: '已结束', tone: 'neutral' }
        if (Number(r.checkin_status) === 1) return { label: '已签到', tone: 'primary' }
        return { label: '待参加', tone: 'success' }
    }
    if (status === 2) return { label: '取消审核中', tone: 'warning' }
    if (status === 3) return { label: '已取消', tone: 'neutral' }
    if (status === 4) return { label: '退款处理中', tone: 'info' }
    if (status === 5) return { label: '退款失败', tone: 'danger' }
    return { label: String(r.registration_status_desc || '未知状态'), tone: 'neutral' }
}

export function resolveBannerInfo(
    r: Partial<ActivityRegistrationVO>,
    nowSec: number
): { tone: 'warn' | 'info' | 'danger' | 'gold'; text: string; right?: string } | null {
    const status = Number(r.registration_status ?? -1)
    if (status === 0) {
        const remain = getPayRemainSeconds(r, nowSec)
        if (remain > 0) {
            return {
                tone: 'warn',
                text: `请在 ${formatCountdown(remain)} 内完成支付，超时将释放名额`,
                right: String(r.ticket_price_label || '')
            }
        }
        return {
            tone: 'warn',
            text: '支付已超时，正在释放名额',
            right: String(r.ticket_price_label || '')
        }
    }
    if (status === 1 && Number(r.checkin_status) === 1 && !isActivityEnded(r, nowSec * 1000)) {
        const t = r.checkin_time ? formatShortTime(r.checkin_time) : '现场'
        return {
            tone: 'gold',
            text: `已于 ${t} 完成签到核销`,
            right: r.checkin_code ? String(r.checkin_code) : undefined
        }
    }
    if (status === 2) {
        return {
            tone: 'warn',
            text: '取消申请审核中，通过后将原路退回费用'
        }
    }
    if (status === 4) {
        const amt = Number(r.latest_refund?.refund_amount ?? r.pay_amount ?? 0)
        return {
            tone: 'info',
            text: amt > 0 ? `退款处理中，预计退回 ¥${amt.toFixed(2)}` : '退款处理中，请留意到账通知'
        }
    }
    if (status === 5) {
        return {
            tone: 'danger',
            text: r.latest_refund?.refund_msg || '原路退款失败，可重新提交取消退款申请'
        }
    }
    if (status === 1 && Number(r.cancel_status) === 3 && r.cancel_reject_reason) {
        return {
            tone: 'danger',
            text: `取消申请未通过：${r.cancel_reject_reason}`
        }
    }
    return null
}

export function buildMetaLine(r: Partial<ActivityRegistrationVO>): string {
    const parts: string[] = []
    const ticketName = String(r.ticket_name || r.ticket?.name || '').trim()
    if (ticketName) parts.push(ticketName)
    const price = String(r.ticket_price_label || '').trim()
    if (price) parts.push(price)
    const loc = activityLocation(r).trim()
    if (loc) parts.push(loc)
    return parts.join(' · ')
}
