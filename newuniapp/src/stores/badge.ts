import { defineStore } from 'pinia'
import { getMe } from '@/api/user'
import type { MeResponse } from '@shared/contracts'
import { useUserStore } from '@/stores/user'

interface BadgeState {
    /** 未读消息数（v2/me 的 unread.total） */
    unread: number
    /** 服务人员工作台待办数（v2/me 的 staff_todo.total，非 staff 恒为 0） */
    staffTodo: number
    /** 待支付订单数（v2/me 的 order_stats.pending_pay，参与「我的」Tab 角标聚合） */
    order: number
    /** 上次成功同步时间戳，0 表示已失效 */
    refreshedAt: number
}

/** 同一窗口内重复触发不再发请求，避免每次 tab 切换都打一次 /v2/me */
const THROTTLE_MS = 30 * 1000

/**
 * 单飞锁：并发 refresh 复用同一个 Promise。
 * 刻意放在模块作用域而不是 Pinia state —— Promise 不是可序列化状态，
 * 进 state 会污染 devtools 快照并触发无意义的响应式追踪。
 */
let inFlight: Promise<void> | null = null

/**
 * 底部导航角标 + 「我的」页入口角标的唯一数据源。
 *
 * 口径（一级角标，红标 = 需要立即处理）：
 *   · unread    → v2/me.unread.total（消息中心未读）
 *   · staffTodo → v2/me.staff_todo.total（仅 is_staff=1 且 staff_center 开关开启时有值）
 *   · order     → v2/me.order_stats.pending_pay（待支付订单，需尽快完成支付）
 *
 * 「我的」Tab 展示的是三者聚合（mineCount）：待支付订单、客户消息、工作台待办都属于
 * 「现在就去处理」的红色一级角标，拆成多个数字在 24rpx 的图标上根本放不下。
 * 二级角标（待确认/待服务/服务中/售后）只在「我的订单」对应格显示，不计入 Tab。
 */
export const useBadgeStore = defineStore({
    id: 'badgeStore',
    state: (): BadgeState => ({
        unread: 0,
        staffTodo: 0,
        order: 0,
        refreshedAt: 0
    }),
    getters: {
        /** 「我的」Tab 红色角标：待支付订单 + 未读消息 + 服务人员工作台待办（与个人中心红标 1:1 对齐） */
        mineCount: (state) =>
            Math.max(0, state.order) + Math.max(0, state.unread) + Math.max(0, state.staffTodo),
        /** 消息入口角标（语义化别名，与 unread 同源） */
        message: (state) => state.unread
    },
    actions: {
        /** 用 v2/me 聚合结果覆盖角标（服务人员才取 staff_todo） */
        syncFromMe(me: Partial<MeResponse> | null | undefined) {
            if (!me) return

            // 已绑手机的用户冷启后 userStore.userInfo 可能还是空壳：
            // 把 v2/me 带回的 user 顺手合并回去，保证 userInfo.mobile 等字段能被及时读到
            if (me.user && (me.user as any).id) {
                const userStore = useUserStore()
                userStore.setUserInfo({ ...userStore.userInfo, ...me.user })
            }

            this.unread = Math.max(0, Number((me.unread as any)?.total ?? 0) || 0)

            // 角色与开关双校验：非服务人员、或后台关了工作台时，服务端可能仍带 staff_todo，
            // 但入口不可见，角标必须为 0，否则会出现「点不进去却有红点」。
            const canStaff =
                Number(me.roles?.is_staff ?? 0) === 1 &&
                Number(me.feature_switch?.staff_center ?? 1) !== 0
            this.staffTodo = canStaff
                ? Math.max(0, Number(me.staff_todo?.total ?? 0) || 0)
                : 0

            this.order = Math.max(0, Number((me.order_stats as any)?.pending_pay ?? 0) || 0)
            this.refreshedAt = Date.now()
        },

        /** 订单列表拉取统计后就地同步待支付角标，不等下一次 30s 节流窗口 */
        setPendingPay(count: number) {
            this.order = Math.max(0, Math.floor(Number(count) || 0))
        },

        /** 消息页已读操作后就地改数，不回源（下一次 refresh 会校正） */
        setUnread(count: number) {
            this.unread = Math.max(0, Number(count) || 0)
        },

        /** 工作台 dashboard.todo → 5 项可操作待办之和 */
        syncFromStaffDashboard(todo: Record<string, any> | null | undefined) {
            if (!todo) return

            const total =
                Number(todo.pending_confirm_orders ?? 0) +
                Number(todo.today_service_count ?? 0) +
                Number(todo.pending_settlement_count ?? 0) +
                Number(todo.pending_waitlist_count ?? 0) +
                Number(todo.pending_followup_count ?? 0)
            this.staffTodo = Math.max(0, Math.floor(total) || 0)

            // 工作台里做了「批量已读」这类操作，dashboard 会带最新未读数
            if (
                todo.unread_message_count !== undefined &&
                Number.isFinite(Number(todo.unread_message_count))
            ) {
                this.unread = Math.max(0, Number(todo.unread_message_count) || 0)
            }

            this.refreshedAt = Date.now()
        },

        /** 标脏：下一次 refresh 必定回源（订单履约 / 取消后待办数已变） */
        invalidate() {
            this.refreshedAt = 0
        },

        async refresh(force = false) {
            const userStore = useUserStore()
            if (!userStore.isLogin) {
                this.clear()
                return
            }
            if (!force && this.refreshedAt > 0 && Date.now() - this.refreshedAt < THROTTLE_MS)
                return

            if (inFlight) return inFlight

            inFlight = getMe()
                .then((res) => {
                    if (res) this.syncFromMe(res)
                })
                .catch(() => {
                    // 角标是辅助信息，取数失败保持旧值，不打扰用户
                })
                .finally(() => {
                    inFlight = null
                })
            return inFlight
        },

        clear() {
            this.unread = 0
            this.staffTodo = 0
            this.order = 0
            this.refreshedAt = 0
        }
    }
})
