import { computed, nextTick, reactive, ref, type Ref } from 'vue'
import { addComment, getCommentList, likeComment } from '@/api/content'
import { normalizePage } from '@/request'
import { navigate } from '@/utils/link'
import { haptic } from '@/utils/haptics'
import { toast } from '@/composables/useToast'
import { useUserStore } from '@/stores/user'
import { report } from '@/platform/report'

export interface CommentRow {
    id: number
    user_id?: number
    user_nickname?: string
    user_avatar?: string
    content?: string
    create_time?: string
    create_time_text?: string
    like_count?: number
    is_liked?: boolean | number
    reply_count?: number
    parent_id?: number
    reply_user_id?: number
    reply_user_nickname?: string
    /** 主列表作者在嵌套 user 里；子回复也可能带 replyUser */
    user?: { id?: number; nickname?: string; avatar?: string } | null
    replyUser?: { id?: number; nickname?: string } | null
    /** 提交成功但未过审：只在本地占位，服务端列表不会返回 */
    pending?: boolean
}

export interface ReplyState {
    items: CommentRow[]
    loading: boolean
    loaded: boolean
    error: boolean
    expanded: boolean
    total: number
    page: number
    hasMore: boolean
}

export interface ReplyTarget {
    parentId: number
    userId: number
    nickname: string
}

function pad2(n: number): string {
    return String(n).padStart(2, '0')
}

/** 评论时间：后端给 'YYYY-MM-DD HH:mm:ss' 或 10/13 位时间戳，统一 MM.DD HH:mm */
export function commentTimeText(value: unknown): string {
    if (!value) return ''
    const raw = String(value)
    let d: Date | null = null
    if (/^\d{10}$/.test(raw)) d = new Date(Number(raw) * 1000)
    else if (/^\d{13}$/.test(raw)) d = new Date(Number(raw))
    if (d && Number.isFinite(d.getTime())) {
        return `${pad2(d.getMonth() + 1)}.${pad2(d.getDate())} ${pad2(d.getHours())}:${pad2(
            d.getMinutes()
        )}`
    }
    if (/^\d{4}-\d{2}-\d{2}/.test(raw)) {
        return `${raw.slice(5, 10).replace('-', '.')} ${raw.slice(11, 16)}`.trim()
    }
    return raw
}

/**
 * 评论字段归一化：
 *  · 主列表作者在嵌套 user 里，子回复是扁平 user_nickname/user_avatar；
 *  · 子回复的 reply_user_nickname 后端可能为空，用 replyUser.nickname 兜底；
 *  · is_liked 0/1、reply_count 字符串统一成前端好用的类型。
 */
export function normalizeComment(raw: any): CommentRow {
    const user = raw?.user || {}
    const replyUser = raw?.replyUser || raw?.reply_user || {}
    return {
        ...raw,
        user_id: Number(raw?.user_id || user?.id || 0),
        user_nickname: raw?.user_nickname || user?.nickname || '匿名',
        user_avatar: raw?.user_avatar || user?.avatar || '',
        reply_user_id: Number(raw?.reply_user_id || replyUser?.id || 0),
        reply_user_nickname: raw?.reply_user_nickname || replyUser?.nickname || '',
        reply_count: Number(raw?.reply_count || 0),
        create_time_text: raw?.create_time_text || commentTimeText(raw?.create_time),
        is_liked: raw?.is_liked === true || Number(raw?.is_liked) === 1
    }
}

export function useFeedComments(id: Ref<number>, post: Ref<any>) {
    const userStore = useUserStore()

    const comments = ref<CommentRow[]>([])
    const commentTotal = ref(0)
    /** 头部计数用动态的 comment_count（含二级回复）；为 0 时回退父评论分页 total */
    const commentCount = computed(() => {
        const n = Number(post.value?.comment_count || 0)
        return n > 0 ? n : commentTotal.value
    })
    const commentLoading = ref(false)
    const sort = ref<'hot' | 'new'>('hot')
    const loadMoreStatus = ref<'idle' | 'loading' | 'nomore' | 'error'>('idle')
    const draft = ref('')
    /** 二级回复的展开/分页状态，按父评论 id 存 */
    const replyStates = reactive<Record<number, ReplyState>>({})
    /** 当前回复目标：parentId 始终是根父评论 id，userId 是被回复人 */
    const replyTarget = ref<ReplyTarget | null>(null)
    const inputFocus = ref(false)
    let cPage = 1
    let cHasMore = true

    function ensureReplyState(parentId: number): ReplyState {
        const key = Number(parentId)
        if (!replyStates[key]) {
            replyStates[key] = {
                items: [],
                loading: false,
                loaded: false,
                error: false,
                expanded: false,
                total: 0,
                page: 1,
                hasMore: true
            }
        }
        return replyStates[key]
    }

    function clearReplyStates(): void {
        for (const key of Object.keys(replyStates)) {
            delete replyStates[Number(key)]
        }
    }

    /** 按父评论拉二级回复；reset 清空重拉，否则追加下一页 */
    async function loadReplies(parentId: number, reset = false): Promise<void> {
        const st = ensureReplyState(parentId)
        if (st.loading) return
        if (!reset && !st.hasMore) return
        if (reset) {
            st.items = []
            st.page = 1
            st.hasMore = true
            st.error = false
            st.loaded = false
        }
        st.loading = true
        try {
            const res: any = await getCommentList({
                dynamic_id: id.value,
                parent_id: parentId,
                page: st.page,
                page_size: 20
            })
            const paged = normalizePage<any>(res)
            const rows: CommentRow[] = (paged.list || []).map(normalizeComment)
            st.items = reset ? rows : st.items.concat(rows)
            st.total = Number(paged.total || st.items.length)
            st.loaded = true
            st.error = false
            st.hasMore = paged.hasMore
            if (paged.hasMore) st.page += 1
            const parent = comments.value.find((c) => Number(c.id) === Number(parentId))
            if (parent) parent.reply_count = st.total
        } catch {
            st.error = true
            report('api_error', {
                endpoint: 'dynamic.commentLists.reply',
                message: '回复加载失败'
            })
        } finally {
            st.loading = false
        }
    }

    function toggleReplies(c: CommentRow): void {
        const st = ensureReplyState(c.id)
        if (st.expanded) {
            st.expanded = false
            return
        }
        st.expanded = true
        if (!st.loaded && !st.loading) loadReplies(c.id, true)
    }

    /**
     * 点「回复」：父评论传 rootId=c.id；子回复也传父评论 c.id（后端只做一层）。
     * reply_user_id 取被回复人的 user_id，而不是评论 id。
     */
    function startReply(target: CommentRow, rootId: number): void {
        replyTarget.value = {
            parentId: Number(rootId),
            userId: Number(target.user_id || 0),
            nickname: target.user_nickname || '匿名'
        }
        inputFocus.value = false
        nextTick(() => {
            inputFocus.value = true
        })
    }

    function cancelReply(): void {
        replyTarget.value = null
        inputFocus.value = false
    }

    function localUser() {
        const u: any = userStore.userInfo || {}
        return {
            id: Number(u.id || 0),
            nickname: u.nickname || u.name || '我',
            avatar: u.avatar || ''
        }
    }

    /** 审核通过前只在本地展示的占位行；id 用服务端 comment_id 便于重拉后去重 */
    function makePendingComment(
        content: string,
        parentId = 0,
        target?: { userId: number; nickname: string }
    ): CommentRow {
        const me = localUser()
        return {
            id: -Date.now(),
            user_id: me.id,
            user_nickname: me.nickname,
            user_avatar: me.avatar,
            content,
            create_time_text: '刚刚',
            like_count: 0,
            is_liked: false,
            reply_count: 0,
            parent_id: parentId,
            reply_user_id: target?.userId || 0,
            reply_user_nickname: target?.nickname || '',
            pending: true
        }
    }

    async function loadComments(reset = false) {
        if (!id.value) return
        if (!reset && (loadMoreStatus.value === 'loading' || !cHasMore)) return
        if (reset) {
            cPage = 1
            cHasMore = true
            comments.value = []
            clearReplyStates()
        }
        commentLoading.value = true
        loadMoreStatus.value = 'loading'
        try {
            const res: any = await getCommentList({
                dynamic_id: id.value,
                page: cPage,
                page_size: 10,
                sort: sort.value
            })
            const paged = normalizePage<any>(res)
            const rows: CommentRow[] = (paged.list || []).map(normalizeComment)
            comments.value = reset ? rows : comments.value.concat(rows)
            for (const c of comments.value) {
                const st = ensureReplyState(c.id)
                if (!st.total) st.total = Number(c.reply_count || 0)
            }
            commentTotal.value = Number(paged.total || comments.value.length)
            cHasMore = comments.value.length < commentTotal.value && rows.length > 0
            cPage += 1
            loadMoreStatus.value = cHasMore ? 'idle' : 'nomore'
        } catch {
            loadMoreStatus.value = 'error'
            report('api_error', { endpoint: 'dynamic.commentLists', message: '评论加载失败' })
        } finally {
            commentLoading.value = false
        }
    }

    function loadMore() {
        loadComments(false)
    }

    function switchSort(s: 'hot' | 'new') {
        if (sort.value === s) return
        sort.value = s
        loadComments(true)
    }

    async function onLikeComment(c: CommentRow) {
        if (!userStore.isLogin) {
            navigate({ path: '/pages/login/login' })
            return
        }
        haptic('light', 'feed-comment-like')
        const liked = !c.is_liked
        c.is_liked = liked
        c.like_count = Math.max(0, Number(c.like_count || 0) + (liked ? 1 : -1))
        try {
            const res: any = await likeComment({ comment_id: c.id })
            if (res && res.is_liked !== undefined) c.is_liked = Number(res.is_liked) === 1
        } catch {
            c.is_liked = !liked
            c.like_count = Math.max(0, Number(c.like_count || 0) + (liked ? -1 : 1))
        }
    }

    async function submitComment() {
        const text = draft.value.trim()
        if (!text) return
        if (!userStore.isLogin) {
            navigate({ path: '/pages/login/login' })
            return
        }
        const target = replyTarget.value
        try {
            const res: any = await addComment({
                dynamic_id: id.value,
                content: text,
                ...(target ? { parent_id: target.parentId, reply_user_id: target.userId } : {})
            })
            const newId = Number(res?.comment_id || 0)
            draft.value = ''
            replyTarget.value = null
            inputFocus.value = false
            if (post.value) {
                post.value.comment_count = Number(post.value.comment_count || 0) + 1
            }

            if (target) {
                const st = ensureReplyState(target.parentId)
                st.expanded = true
                const parent = comments.value.find((c) => Number(c.id) === target.parentId)
                if (parent) parent.reply_count = Number(parent.reply_count || 0) + 1
                await loadReplies(target.parentId, true)
                if (newId > 0 && st.items.some((r) => Number(r.id) === newId)) {
                    toast.success('回复已发布')
                } else {
                    const row = makePendingComment(text, target.parentId, {
                        userId: target.userId,
                        nickname: target.nickname
                    })
                    if (newId > 0) row.id = newId
                    st.items.push(row)
                    st.total = Math.max(st.total, st.items.length)
                    toast.info('已提交，审核通过后展示')
                }
                return
            }

            await loadComments(true)
            if (newId > 0 && comments.value.some((c) => Number(c.id) === newId)) {
                toast.success('评论已发布')
            } else {
                const row = makePendingComment(text)
                if (newId > 0) row.id = newId
                comments.value.unshift(row)
                toast.info('已提交，审核通过后展示')
            }
        } catch (e: any) {
            toast.error(
                e?.message || (target ? '回复没发出去，请重试' : '评论没发出去，请重试')
            )
        }
    }

    return {
        comments,
        commentCount,
        commentLoading,
        sort,
        loadMoreStatus,
        draft,
        replyStates,
        replyTarget,
        inputFocus,
        loadComments,
        loadMore,
        switchSort,
        loadReplies,
        toggleReplies,
        startReply,
        cancelReply,
        onLikeComment,
        submitComment
    }
}
