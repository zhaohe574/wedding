import { defineStore } from 'pinia'

/**
 * tab 页「一次性入参」中转站。
 *
 * 为什么需要它：
 *   uni.switchTab 不接受 query（微信限制），而 tab 页不像普通页能靠 onLoad(options) 拿参数。
 *   所以「首页 → 查看我的档期（带上已选日期）」这类跨页带条件进 tab 的诉求，只能靠共享状态中转。
 *
 * 用法（写入方）：
 *   const tabQuery = useTabQueryStore()
 *   tabQuery.setQuery('query', { date: '2027-05-20' })
 *   uni.switchTab({ url: '/pages/query/query' })
 * 用法（tab 页，在 onShow 里）：
 *   const params = tabQuery.consumeQuery('query')   // 取完即清，避免下次进入被旧条件污染
 *   if (params) { ... }
 *
 * 边界（不要扩大）：
 *   - 只放「一次性入参」，不放筛选状态的持久值 —— tab 页实例常驻，页面自己的 ref 已经够用；
 *   - 不放业务数据（订单、人员详情等），那些走各自的 store 或页面参数。
 */
type TabKey = 'index' | 'feed' | 'query' | 'user'

interface TabQueryState {
    pending: Partial<Record<TabKey, Record<string, any>>>
}

export const useTabQueryStore = defineStore({
    id: 'tabQueryStore',
    state: (): TabQueryState => ({
        pending: {}
    }),
    actions: {
        setQuery(tab: TabKey, params: Record<string, any>) {
            this.pending[tab] = { ...params }
        },
        /** 取出并清空；没有待消费参数时返回 null */
        consumeQuery(tab: TabKey): Record<string, any> | null {
            const value = this.pending[tab]
            if (!value) return null
            delete this.pending[tab]
            return value
        },
        clear() {
            this.pending = {}
        }
    }
})
