import { defineStore } from 'pinia'
import { getUnreadCount } from '@/api/message'
import { getOrderStatistics } from '@/api/order'
import { useUserStore } from '@/stores/user'

interface BadgeState {
    /** 消息 tab：未读通知总数 */
    message: number
    /** 订单 tab：待支付订单数 */
    order: number
    refreshedAt: number
}

/** 同一分钟内重复触发不再发请求，避免每个 tab 切换都打两次接口 */
const THROTTLE_MS = 30 * 1000

/**
 * 底部导航角标。
 *
 * 两个 tab 的数据源不同，不能混用：
 *   · 消息 → notification.unreadCount 的 total
 *   · 订单 → order.statistics 的 pending_pay
 * 之前 message 页取了 unread.total 但只存在页面局部 ref，tabbar 读不到，
 * 所以角标一直没出现过。
 */
export const useBadgeStore = defineStore({
    id: 'badgeStore',
    state: (): BadgeState => ({
        message: 0,
        order: 0,
        refreshedAt: 0
    }),
    actions: {
        async refresh(force = false) {
            const userStore = useUserStore()
            if (!userStore.isLogin) {
                this.clear()
                return
            }
            if (!force && Date.now() - this.refreshedAt < THROTTLE_MS) return

            const [unread, stats] = await Promise.all([
                getUnreadCount().catch(() => null),
                getOrderStatistics().catch(() => null)
            ])
            if (unread) this.message = Number((unread as any).total ?? 0)
            if (stats) this.order = Number((stats as any).pending_pay ?? 0)
            this.refreshedAt = Date.now()
        },
        clear() {
            this.message = 0
            this.order = 0
            this.refreshedAt = 0
        }
    }
})
