import { ref, type Ref } from 'vue'
import { normalizePage } from '@/request'
import type { LoadPhase } from '@/api/types'

/**
 * 列表分页统一 composable（24 屏里 03/14/15/16/22 都用它）。
 *
 * 为什么要收口：现网 5 个列表页各写一套 `page / hasMore / loadingMore / loadMoreStatus`，
 * 每个都有各自的 bug —— 触底连发、条件切换后旧数据混入、失败被静默吞掉。
 *
 * 行为约定：
 *   · phase 只有五种（LoadPhase），空态由「请求成功但 list 为空」得出，**失败不许画成空态**
 *   · loadMore 防重入；失败置 loadMoreStatus='error' 让用户点重试
 *   · 条件变化用 reload()：清空列表、回第一页、丢弃在飞响应（seq 序号保护）
 *   · refresh() 保留旧列表直到新数据回来（下拉刷新不闪空屏）
 */

export interface UsePagedListOptions<T, Q extends Record<string, any>> {
    /** 取一页。返回原始响应（四种分页形态由 normalizePage 归一），失败请直接抛 */
    fetcher: (query: Q & { page: number; page_size: number }) => Promise<unknown>
    /** 筛选条件；条件对象变化后调用 reload() 生效（composable 不会自己 watch） */
    query?: Ref<Q>
    /** 每页条数，默认 10 */
    pageSize?: number
    /** 错误回调（请求层已 toast 时这里只做埋点/错误页） */
    onError?: (error: unknown) => void
}

export type LoadMoreStatus = 'idle' | 'loading' | 'nomore' | 'error'

export interface UsePagedListReturn<T> {
    list: Ref<T[]>
    total: Ref<number>
    phase: Ref<LoadPhase>
    refreshing: Ref<boolean>
    loadMoreStatus: Ref<LoadMoreStatus>
    /** 清空 + 回第一页（条件变化时用） */
    reload: () => Promise<void>
    /** 追加下一页（防重入） */
    loadMore: () => Promise<void>
    /** 下拉刷新（保留旧列表） */
    refresh: () => Promise<void>
}

export function usePagedList<T = any, Q extends Record<string, any> = Record<string, any>>(
    options: UsePagedListOptions<T, Q>
): UsePagedListReturn<T> {
    const list = ref([]) as Ref<T[]>
    const total = ref(0)
    const phase = ref<LoadPhase>('idle') as Ref<LoadPhase>
    const refreshing = ref(false)
    const loadMoreStatus = ref<LoadMoreStatus>('idle') as Ref<LoadMoreStatus>

    let page = 1
    let size = options.pageSize && options.pageSize > 0 ? options.pageSize : 10
    let hasMore = true
    let appending = false
    /** 序号：重置时自增，旧响应回来会因序号不匹配被丢弃 */
    let seq = 0

    function currentQuery(): Q {
        return (options.query ? options.query.value : ({} as Q)) as Q
    }

    async function run(mode: 'reset' | 'append' | 'refresh') {
        if (mode === 'append') {
            if (appending || !hasMore) return
        } else {
            // 重置/刷新：让所有在飞请求作废
            seq += 1
        }

        const mySeq = seq
        const targetPage = mode === 'append' ? page : 1

        if (mode === 'append') {
            appending = true
            loadMoreStatus.value = 'loading'
        } else if (mode === 'reset') {
            list.value = []
            total.value = 0
            hasMore = true
            page = 1
            phase.value = 'loading'
        } else {
            refreshing.value = true
            if (phase.value === 'idle' || phase.value === 'error') phase.value = 'loading'
        }

        try {
            const raw = await options.fetcher({ ...currentQuery(), page: targetPage, page_size: size })
            if (mySeq !== seq) return // 已被更新的请求取代，丢弃

            const paged = normalizePage<T>(raw, targetPage, size)
            if (paged.pageSize > 0) size = paged.pageSize
            list.value = mode === 'append' ? list.value.concat(paged.list) : paged.list
            total.value = paged.total
            hasMore = paged.hasMore
            page = targetPage + 1
            phase.value = list.value.length ? 'ready' : 'empty'
            loadMoreStatus.value = hasMore ? 'idle' : 'nomore'
        } catch (error) {
            if (mySeq !== seq) return
            if (mode === 'append') {
                loadMoreStatus.value = 'error'
            } else {
                phase.value = 'error'
            }
            options.onError?.(error)
        } finally {
            if (mode === 'append') appending = false
            if (mode === 'refresh') refreshing.value = false
        }
    }

    return {
        list,
        total,
        phase,
        refreshing,
        loadMoreStatus,
        reload: () => run('reset'),
        loadMore: () => run('append'),
        refresh: () => run('refresh')
    }
}
