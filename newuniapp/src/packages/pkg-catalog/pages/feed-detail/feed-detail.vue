<template>
    <!--
        13 动态详情
        常规实底导航：不再用 hero 压状态栏，标题与返回在任何动态（含纯文字）上都可用。
        媒体改为正文内展示：图片按张数栅格 + 点击大图预览；视频用原生 controls 播放。

        与设计稿的差异（都由数据/开关决定）：
        - 活动卡：只有动态真的挂了活动才渲染，没有就不占位
        - 评论输入：送审模式（feature_switch.mini_program_review_mode）下后端会拒发，
          所以开关打开或读不到时**隐藏输入框**，只保留只读列表
    -->
    <page-shell
        nav
        nav-variant="solid"
        :show-back="true"
        bg="page"
        nav-tone="dark"
        title="动态详情"
        :bottom-bar="true"
    >
        <view v-if="loading" class="pad">
            <base-skeleton :rows="6" />
        </view>

        <base-empty
            v-else-if="error"
            status="error"
            :error-kind="view?.errorKind || 'unknown'"
            :error-text="view?.title"
            :desc="view?.desc"
            :primary-action="view?.primaryAction"
            fixed
            @primary="onPrimary"
            @retry="load"
        />

        <template v-else-if="post">
            <view class="body">
                <!-- 作者卡：这一页唯一的强动作 -->
                <view class="author">
                    <image
                        v-if="authorAvatar"
                        class="author__av"
                        :src="authorAvatar"
                        mode="aspectFill"
                    />
                    <view v-else class="author__av author__av--txt">
                        <text class="author__av-text">{{
                            (post.user_nickname || '·').slice(0, 1)
                        }}</text>
                    </view>

                    <view class="author__who">
                        <view class="author__row">
                            <text class="author__name">{{ post.user_nickname || '匿名用户' }}</text>
                            <!-- USER_TYPE_OFFICIAL = 3；2 是工作人员。
                                 之前写 ===2 会把工作人员动态标成「官方」。 -->
                            <status-tag
                                v-if="post.user_type === 3"
                                label="官方"
                                tone="primary"
                                size="sm"
                            />
                        </view>
                        <text v-if="authorMeta" class="author__meta">{{ authorMeta }}</text>
                    </view>

                    <view
                        v-if="post.user_type === 2 && staffId"
                        class="author__book"
                        hover-class="is-pressed"
                        @tap="goStaff"
                    >
                        <text class="author__book-text">预约 TA</text>
                        <tn-icon name="right" :size="18" class="ic-brand-text" />
                    </view>
                </view>

                <text class="title">{{ post.title || '婚礼现场实录' }}</text>
                <text v-if="post.content" class="text">{{ post.content }}</text>

                <!-- 媒体：视频原生 controls；图片按张数栅格，点击看大图（可左右滑全部） -->
                <view v-if="hasVideo" class="media-video">
                    <video
                        class="media-video__player"
                        :src="videoUrl"
                        :poster="cover"
                        :controls="true"
                        show-center-play-btn
                        enable-progress-gesture
                        object-fit="contain"
                        @error="onVideoError"
                    />
                </view>
                <view
                    v-else-if="imageUrls.length"
                    class="gallery"
                    :class="'gallery--g' + galleryLayout"
                >
                    <view
                        v-for="(img, i) in displayImages"
                        :key="img + i"
                        class="gallery__cell"
                        @tap="previewDetailImage(i)"
                    >
                        <image class="gallery__img" :src="img" mode="aspectFill" lazy-load />
                        <!-- 超过 9 张：第 9 格压深色叠层报剩余张数 -->
                        <view v-if="mediaMore && i === 8" class="gallery__more">
                            <text class="gallery__more-text">+{{ mediaMore }}</text>
                        </view>
                    </view>
                </view>

                <view class="meta">
                    <text v-for="t in tagList" :key="t" class="meta__hash"># {{ t }}</text>
                    <text v-if="post.location" class="meta__item">{{ post.location }}</text>
                    <text v-if="viewCount > 0" class="meta__item">{{ viewCount }} 次浏览</text>
                    <text v-if="post.create_time" class="meta__item">{{ dateText }}</text>
                </view>
            </view>

            <!-- 活动卡：正文与评论之间的一行，不弹窗不浮层 -->
            <view v-if="activity" class="event" hover-class="is-pressed" @tap="goActivity">
                <view class="event__cal">
                    <text class="event__cal-d">{{ activityDay }}</text>
                    <text class="event__cal-w">{{ activityWeek }}</text>
                </view>
                <view class="event__em">
                    <text class="event__title">{{ activity.title || '线下沙龙' }}</text>
                    <text v-if="activityTimeText" class="event__sub">{{ activityTimeText }}</text>
                </view>
                <view class="event__go">
                    <text class="event__go-text">报名</text>
                    <tn-icon name="right" :size="18" class="ic-brand-text" />
                </view>
            </view>

            <!-- 评论区 -->
            <view class="com">
                <view class="com__head">
                    <text class="com__title">评论</text>
                    <text v-if="commentCount > 0" class="com__num">{{ commentCount }}</text>
                    <view class="com__sort">
                        <text
                            class="com__sort-item"
                            :class="{ 'is-on': sort === 'hot' }"
                            @tap="switchSort('hot')"
                            >最热</text
                        >
                        <text
                            class="com__sort-item"
                            :class="{ 'is-on': sort === 'new' }"
                            @tap="switchSort('new')"
                            >最新</text
                        >
                    </view>
                </view>

                <view v-if="comments.length === 0 && !commentLoading" class="com__empty">
                    <text class="com__empty-text">还没有评论</text>
                </view>

                <view v-for="c in comments" :key="c.id" class="cm">
                    <image
                        v-if="c.user_avatar"
                        class="cm__av"
                        :src="imgUrl(c.user_avatar)"
                        mode="aspectFill"
                    />
                    <view v-else class="cm__av cm__av--txt">
                        <text class="cm__av-text">{{ (c.user_nickname || '·').slice(0, 1) }}</text>
                    </view>
                    <view class="cm__body">
                        <view class="cm__who">
                            <text class="cm__name">{{ c.user_nickname || '匿名' }}</text>
                            <text v-if="c.pending" class="cm__pending">待审核</text>
                        </view>
                        <text class="cm__p">{{ c.content }}</text>
                        <view class="cm__foot">
                            <text class="cm__time">{{ c.create_time_text || '' }}</text>
                            <view
                                class="cm__like"
                                :class="{ 'is-on': c.is_liked }"
                                @tap="onLikeComment(c)"
                            >
                                <tn-icon
                                    :name="c.is_liked ? 'like-fill' : 'like'"
                                    :size="22"
                                />
                                <text class="cm__like-num">{{ c.like_count || 0 }}</text>
                            </view>
                            <text
                                v-if="canComment"
                                class="cm__act"
                                hover-class="is-pressed"
                                @tap="startReply(c, c.id)"
                                >回复</text
                            >
                        </view>

                        <!-- 二级回复：默认收起，展开时按 parent_id 拉取；新回复即时插到列表尾部 -->
                        <view
                            v-if="Number(c.reply_count) > 0 || replyStates[c.id]?.expanded"
                            class="cm__replies"
                        >
                            <view class="cm__replies-toggle" @tap="toggleReplies(c)">
                                <text class="cm__replies-toggle-text">
                                    {{
                                        replyStates[c.id]?.expanded
                                            ? '收起回复'
                                            : `展开 ${
                                                  replyStates[c.id]?.total || c.reply_count
                                              } 条回复`
                                    }}
                                </text>
                            </view>

                            <view v-if="replyStates[c.id]?.expanded" class="cm__reply-list">
                                <view
                                    v-for="r in replyStates[c.id]?.items || []"
                                    :key="r.id"
                                    class="cm__reply-item"
                                >
                                    <image
                                        v-if="r.user_avatar"
                                        class="cm__reply-av"
                                        :src="imgUrl(r.user_avatar)"
                                        mode="aspectFill"
                                    />
                                    <view v-else class="cm__reply-av cm__reply-av--txt">
                                        <text class="cm__reply-av-text">{{
                                            (r.user_nickname || '·').slice(0, 1)
                                        }}</text>
                                    </view>
                                    <view class="cm__reply-body">
                                        <view class="cm__reply-who">
                                            <text class="cm__reply-name">{{
                                                r.user_nickname || '匿名'
                                            }}</text>
                                            <text v-if="r.pending" class="cm__pending">待审核</text>
                                        </view>
                                        <text class="cm__reply-text">
                                            <text
                                                v-if="
                                                    Number(r.reply_user_id) > 0 &&
                                                    r.reply_user_nickname
                                                "
                                                class="cm__reply-to"
                                                >回复 @{{ r.reply_user_nickname }}：</text
                                            >{{ r.content }}
                                        </text>
                                        <view class="cm__reply-foot">
                                            <text class="cm__time">{{
                                                r.create_time_text || ''
                                            }}</text>
                                            <view
                                                class="cm__like"
                                                :class="{ 'is-on': r.is_liked }"
                                                @tap="onLikeComment(r)"
                                            >
                                                <tn-icon
                                                    :name="r.is_liked ? 'like-fill' : 'like'"
                                                    :size="20"
                                                />
                                                <text class="cm__like-num">{{
                                                    r.like_count || 0
                                                }}</text>
                                            </view>
                                            <text
                                                v-if="canComment"
                                                class="cm__act"
                                                hover-class="is-pressed"
                                                @tap="startReply(r, c.id)"
                                                >回复</text
                                            >
                                        </view>
                                    </view>
                                </view>

                                <view v-if="replyStates[c.id]?.loading" class="cm__reply-loading">
                                    <tn-loading mode="circle" :size="28" :show="true" />
                                </view>
                                <view
                                    v-else-if="replyStates[c.id]?.error"
                                    class="cm__reply-tip"
                                    @tap="loadReplies(c.id, true)"
                                    >加载失败，点击重试</view
                                >
                                <view
                                    v-else-if="replyStates[c.id]?.hasMore"
                                    class="cm__reply-tip"
                                    @tap="loadReplies(c.id)"
                                    >查看更多回复</view
                                >
                            </view>
                        </view>
                    </view>
                </view>

                <load-more v-if="comments.length" :status="loadMoreStatus" @retry="loadMore" />
                <view v-else-if="commentLoading" class="com__loading">
                    <!-- tn-loading 的 show 默认 false，模板是 v-if="show"：不传就是空节点 -->
                    <tn-loading mode="circle" :size="32" :show="true" />
                </view>
            </view>
        </template>
    </page-shell>

    <!-- 底部操作条：评论 / 点赞 / 分享 -->
    <view v-if="post" class="bar">
        <!-- 回复目标：从评论区的「回复」进入，确认后随评论一起提交 parent_id/reply_user_id -->
        <view v-if="replyTarget" class="bar__reply">
            <text class="bar__reply-text">回复 @{{ replyTarget.nickname }}</text>
            <view class="bar__reply-close" hover-class="is-pressed" @tap="cancelReply">
                <tn-icon name="close" :size="18" />
            </view>
        </view>

        <view class="bar__in">
            <template v-if="canComment">
                <input
                    v-model="draft"
                    class="bar__input"
                    :placeholder="replyTarget ? `回复 @${replyTarget.nickname}…` : '说点什么…'"
                    placeholder-class="bar__ph"
                    confirm-type="send"
                    :adjust-position="true"
                    :focus="inputFocus"
                    @blur="inputFocus = false"
                    @confirm="submitComment"
                />
            </template>
            <view v-else class="bar__input bar__input--off">
                <text class="bar__off-text">评论暂不可用</text>
            </view>

            <view class="bar__act" :class="{ 'is-on': post.is_liked }" @tap="onLikePost">
                <tn-icon :name="post.is_liked ? 'like-fill' : 'like'" :size="30" />
                <text class="bar__act-text">{{ post.like_count || 0 }}</text>
            </view>

            <button class="bar__act bar__act--btn" open-type="share">
                <tn-icon name="share" :size="28" class="ic-ink-2" />
                <text class="bar__act-text">分享</text>
            </button>
        </view>
    </view>
    <app-toast />

    <!-- 报名：就地完成选票→联系人→提交→支付，不再跳到「我的活动」列表充数 -->
    <activity-register
        v-model:show="registerShow"
        :dynamic-id="id"
        :title="activity?.title || '线下沙龙'"
        :time-text="activityTimeText"
        @finished="onRegisterFinished"
    />
</template>

<script setup lang="ts">
/**
 * 动态详情。
 *
 * 三个数据开关决定了这一页的形态：
 *  1. 评论区受 feature_switch.mini_program_review_mode 控制（送审模式后端会拒发评论）
 *  2. 活动卡只在动态挂了 activity 时出现
 *  3. 视频用原生 controls 播放；图片按张数栅格展示，点击进大图预览
 */
import { computed, nextTick, reactive, ref } from 'vue'
import { onLoad, onReachBottom, onShareAppMessage } from '@dcloudio/uni-app'
import {
    getCaseDetail,
    toggleCaseLike,
    getCommentList,
    addComment,
    likeComment
} from '@/api/content'
import { normalizePage } from '@/request'
import { navigate } from '@/utils/link'
import { toast } from '@/composables/useToast'
import { useUserStore } from '@/stores/user'
import { useAppStore } from '@/stores/app'
import { useInteractionStore } from '@/stores/interaction'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { report } from '@/platform/report'
import { buildShare } from '@/utils/share'

interface CommentRow {
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

interface ReplyState {
    items: CommentRow[]
    loading: boolean
    loaded: boolean
    error: boolean
    expanded: boolean
    total: number
    page: number
    hasMore: boolean
}

const userStore = useUserStore()
const appStore = useAppStore()
const interactionStore = useInteractionStore()

const post = ref<any>(null)
const loading = ref(true)
const rawError = ref<unknown>(null)
const error = computed(() => !!rawError.value)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const id = ref(0)
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
const replyTarget = ref<{ parentId: number; userId: number; nickname: string } | null>(null)
const inputFocus = ref(false)
let cPage = 1
let cHasMore = true

const staffId = computed(() => Number(post.value?.staff_id || post.value?.staff?.id || 0))

const cover = computed(() => {
    const p = post.value
    if (!p) return ''
    const first = Array.isArray(p.images) ? p.images[0] : ''
    const firstUrl =
        typeof first === 'string' ? first : first?.url || first?.path || first?.src || ''
    return imgUrl(p.video_cover || p.media_cover || firstUrl || '')
})

const videoUrl = computed(() => imgUrl(post.value?.video_url || ''))

const hasVideo = computed(() => !!videoUrl.value)

/** 详情图片：兼容字符串/对象两种形态，统一补域名；预览用完整数组（不受 9 张上限影响） */
const imageUrls = computed<string[]>(() => {
    const raw = post.value?.images
    if (!Array.isArray(raw)) return []
    return raw
        .map((item: any) => {
            if (!item) return ''
            if (typeof item === 'string') return imgUrl(item)
            return imgUrl(item.url || item.image || item.path || item.src || '')
        })
        .filter(Boolean)
})

const displayImages = computed(() => imageUrls.value.slice(0, 9))
const mediaMore = computed(() => Math.max(0, imageUrls.value.length - 9))

const galleryLayout = computed(() => {
    const n = imageUrls.value.length
    if (n <= 1) return 1
    if (n === 2) return 2
    if (n === 3) return 3
    if (n === 4) return 4
    return 9
})

const authorAvatar = computed(() => imgUrl(post.value?.user_avatar || ''))

const viewCount = computed(() => Number(post.value?.view_count || post.value?.views || 0))

const dateText = computed(() => {
    const t = String(post.value?.create_time || '')
    return t ? `${t.slice(5, 10).replace('-', '.')} 发布` : ''
})

/** 「全案统筹 · 128 场 · 4.9 分」：三段各自可缺 */
const authorMeta = computed(() => {
    const p = post.value || {}
    const parts: string[] = []
    if (p.category_name) parts.push(p.category_name)
    if (p.service_count) parts.push(`${p.service_count} 场`)
    if (p.rating) parts.push(`${p.rating} 分`)
    return parts.join(' · ')
})

const tagList = computed(() => {
    const raw = post.value?.tags
    if (!raw) return []
    return String(raw)
        .split(/[,，\s]+/)
        .filter(Boolean)
        .slice(0, 3)
})

const activity = computed(() => post.value?.activity || null)

const activityDay = computed(() => {
    const d = String(activity.value?.start_time || activity.value?.activity_time || '')
    return d ? d.slice(5, 10).replace('-', '.') : ''
})

const activityWeek = computed(() => {
    const d = String(activity.value?.start_time || activity.value?.activity_time || '')
    if (!d) return ''
    const dt = new Date(d.replace(/-/g, '/'))
    if (Number.isNaN(dt.getTime())) return ''
    return ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][dt.getDay()]
})

const activityTimeText = computed(() => {
    const a = activity.value || {}
    const parts: string[] = []
    const t = String(a.start_time || a.activity_start_time || '')
    if (t.length >= 16) parts.push(t.slice(11, 16))
    if (a.city_name || a.address) parts.push(a.city_name || a.address)
    const left = Number(a.activity_remaining_count ?? a.remaining ?? a.remain ?? 0)
    if (left > 0) parts.push(`还剩 ${left} 席`)
    return parts.join(' · ')
})

/**
 * 能否发评论：只有明确读到开关为「关闭」才允许。
 * 读不到时按「不可用」处理 —— 发出去被后端拒，比没有输入框更伤。
 */
const canComment = computed(() => {
    const cfg: any = (appStore.config as any) || {}
    const flag =
        cfg?.feature_switch?.mini_program_review_mode ??
        cfg?.index?.feature_switch?.mini_program_review_mode
    if (flag === undefined || flag === null || flag === '') return false
    return Number(flag) !== 1
})

function imgUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        load()
        return
    }
    if (kind === 'reselect' || kind === 'back') uni.navigateBack()
}

async function load() {
    loading.value = true
    rawError.value = null
    try {
        const res: any = await getCaseDetail({ id: id.value })
        post.value = res?.detail || res?.data || res || null
        if (post.value) {
            if (post.value.is_liked !== undefined) {
                post.value.is_liked = Number(post.value.is_liked) === 1
            }
            // 列表页已点过赞/取消赞：以会话快照为准，避免详情接口的旧值把状态打回去。
            // 详情不反向播种：列表是主数据源，避免深链先打开详情时用未登录态污染快照。
            const cached = interactionStore.getDynamicLike(post.value.id)
            if (cached) {
                post.value.is_liked = cached.liked
                post.value.like_count = cached.count
            }
        }
    } catch (e) {
        rawError.value = e
        report('api_error', { endpoint: 'dynamic.detail', message: '动态详情加载失败' })
    } finally {
        loading.value = false
    }
}

function pad2(n: number): string {
    return String(n).padStart(2, '0')
}

/** 评论时间：后端给 'YYYY-MM-DD HH:mm:ss' 或 10/13 位时间戳，统一 MM.DD HH:mm */
function commentTimeText(value: unknown): string {
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
function normalizeComment(raw: any): CommentRow {
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
        // 服务端 reply_count 是权威值，分页时也能显示总条数
        const parent = comments.value.find((c) => Number(c.id) === Number(parentId))
        if (parent) parent.reply_count = st.total
    } catch (e) {
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
    // 复用同一个 input：先 false 再下一帧 true，避免已经聚焦时 :focus 不再触发
    inputFocus.value = false
    nextTick(() => {
        inputFocus.value = true
    })
}

function cancelReply(): void {
    replyTarget.value = null
    inputFocus.value = false
}

function previewDetailImage(index: number): void {
    const urls = imageUrls.value
    if (!urls.length) return
    uni.previewImage({ urls, current: urls[index] })
}

function onVideoError(): void {
    toast.error('视频加载失败，请稍后重试')
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
        // 排序/刷新会重建父评论，二级回复的展开状态一并清掉，避免旧 id 命中新数据
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
        // 主列表作者在嵌套 user 里，必须摊平；否则头像/昵称永远显示「匿名」
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
    } catch (e) {
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

async function onLikePost() {
    if (!userStore.isLogin) {
        navigate({ path: '/pages/login/login' })
        return
    }
    const p = post.value
    if (!p) return
    const liked = !p.is_liked
    p.is_liked = liked
    p.like_count = Math.max(0, Number(p.like_count || 0) + (liked ? 1 : -1))
    interactionStore.setDynamicLike(p.id, p.is_liked, p.like_count)
    try {
        const res: any = await toggleCaseLike({ id: p.id })
        if (res && typeof res.is_like === 'number') {
            p.is_liked = res.is_like === 1
            interactionStore.setDynamicLike(p.id, p.is_liked, p.like_count)
        }
    } catch {
        p.is_liked = !liked
        p.like_count = Math.max(0, Number(p.like_count || 0) + (liked ? -1 : 1))
        interactionStore.setDynamicLike(p.id, p.is_liked, p.like_count)
        toast.error('点赞没成功，再点一次')
    }
}

async function onLikeComment(c: CommentRow) {
    if (!userStore.isLogin) {
        navigate({ path: '/pages/login/login' })
        return
    }
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
            // 后端只返回 comment_id，不返回审核态；重拉后仍不在列表就本地占位并提示待审
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

function goStaff() {
    if (!staffId.value) return
    navigate({
        path: '/packages/pkg-catalog/pages/staff-detail/staff-detail',
        query: { id: staffId.value }
    })
}

/** 报名弹层：真机反馈「点报名跳到我的活动、没有实际报名」，改为就地走完整链路 */
const registerShow = ref(false)

function goActivity() {
    registerShow.value = true
}

/** 报名已落地（免费成功 / 待支付 / 支付结果待确认），回「我的活动」看入场码与地址 */
function onRegisterFinished() {
    navigate({ path: '/packages/pkg-user/pages/activity/activity' })
}

onLoad((options: any) => {
    id.value = Number(options?.id || 0)
    if (!id.value) {
        rawError.value = { code: 'PARAM', message: '缺少动态 id' }
        loading.value = false
        return
    }
    load()
    loadComments(true)
})

onReachBottom(() => loadMore())

onShareAppMessage(() =>
    buildShare({
        title: post.value?.title,
        suffix: '婚礼主持',
        path: `/packages/pkg-catalog/pages/feed-detail/feed-detail?id=${id.value}`,
        imageUrl: cover.value || undefined
    })
)
</script>

<style lang="scss" scoped>
.pad {
    padding: var(--sp-4);
}

/* 视频：原生 controls，16:9 容器；圆角由外层裁切 */
.media-video {
    margin-top: var(--sp-3);
    border-radius: var(--r-27);
    overflow: hidden;
    background: var(--c-ink);

    &__player {
        display: block;
        width: 100%;
        height: 375rpx;
    }
}

/*
 * 图片画廊：与列表同一套档位，保证两处图片比例/间距/圆角一致。
 * 1 图 4:3 大图；2/4 图双列 329；3/5–9 图三列 216。
 */
.gallery {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-start;
    gap: var(--sp-1);
    margin-top: var(--sp-3);

    &__cell {
        position: relative;
        flex: none;
        overflow: hidden;
        border-radius: var(--r-sm);
        background: var(--c-neutral-bg);
    }

    &__img {
        display: block;
        width: 100%;
        height: 100%;
    }

    &__more {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--c-toast-bg);
    }

    &__more-text {
        font-family: var(--font-serif);
        font-size: var(--fs-30);
        letter-spacing: 0.04em;
        color: var(--c-on-dark);
    }

    &--g1 .gallery__cell {
        width: 100%;
        height: 500rpx;
        border-radius: var(--r-27);
    }

    &--g2 .gallery__cell {
        width: calc((100% - var(--sp-1)) / 2);
        height: 329rpx;
    }

    &--g3 .gallery__cell {
        width: calc((100% - var(--sp-2)) / 3);
        height: 216rpx;
    }

    &--g4 .gallery__cell {
        width: calc((100% - var(--sp-1)) / 2);
        height: 329rpx;
    }

    &--g9 .gallery__cell {
        width: calc((100% - var(--sp-2)) / 3);
        height: 216rpx;
    }
}

.body {
    padding: 30rpx 42rpx 0;
}

.author {
    display: flex;
    align-items: center;
    gap: 18rpx;

    &__av {
        width: 76rpx;
        height: 76rpx;
        border-radius: var(--r-pill);
        background: var(--c-neutral-bg);
        flex: none;

        &--txt {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--c-brand-bg);
        }
    }

    &__av-text {
        font-family: var(--font-serif);
        font-size: 28rpx;
        color: var(--c-brand-text);
    }

    &__who {
        flex: 1;
        min-width: 0;
    }

    &__row {
        display: flex;
        align-items: center;
        gap: 10rpx;
    }

    &__name {
        font-size: 28rpx;
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__meta {
        display: block;
        margin-top: 6rpx;
        font-size: 20rpx;
        color: var(--c-ink-3);
    }

    &__book {
        flex: none;
        display: flex;
        align-items: center;
        gap: 4rpx;
        height: 56rpx;
        padding: 0 20rpx;
        border-radius: var(--r-pill);
        /* 可点控件边界：原 --c-primary-border 对卡面 --c-surface 仅 1.68:1（不可见），
           按项目既有约定改 --c-control-border（对 --c-surface 3.77:1）。
           --c-primary-border 的定位是 .tone-primary 状态标签的装饰描边，不用于控件边界。
           填充 --c-brand-bg（1.15:1）刻意不动：按钮边界由边框表达，填充只是氛围；
           若要给次要按钮加可见浅底，属视觉改动，另议。 */
        border: 1rpx solid var(--c-control-border);
        background: var(--c-brand-bg);
    }

    &__book-text {
        font-size: var(--fs-22);
        color: var(--c-brand-text);
    }
}

.title {
    display: block;
    margin-top: 28rpx;
    font-size: var(--fs-38);
    line-height: var(--lh-body);
    font-weight: var(--fw-semibold);
    color: var(--c-ink);
    letter-spacing: 0.01em;
}

.text {
    display: block;
    margin-top: 20rpx;
    font-size: var(--fs-26);
    line-height: 1.85;
    color: var(--c-ink-2);
    letter-spacing: 0.01em;
}

.meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 16rpx;
    margin-top: 22rpx;

    &__hash {
        font-size: var(--fs-21);
        color: var(--c-brand-text);
        background: var(--c-brand-bg);
        padding: 6rpx 14rpx;
        border-radius: var(--r-pill);
    }

    &__item {
        font-size: 20rpx;
        color: var(--c-ink-3);
    }
}

.event {
    display: flex;
    align-items: center;
    gap: 18rpx;
    margin: 30rpx 42rpx 0;
    padding: 20rpx;
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);

    &__cal {
        width: 84rpx;
        flex: none;
        text-align: center;
        border-right: 1rpx solid var(--c-line);
        padding-right: 16rpx;
    }

    &__cal-d {
        display: block;
        font-family: var(--font-serif);
        font-size: 28rpx;
        color: var(--c-ink);
    }

    &__cal-w {
        display: block;
        font-size: var(--fs-18);
        color: var(--c-ink-3);
        margin-top: 4rpx;
    }

    &__em {
        flex: 1;
        min-width: 0;
    }

    &__title {
        display: block;
        font-size: var(--fs-25);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__sub {
        display: block;
        margin-top: 6rpx;
        font-size: 20rpx;
        color: var(--c-ink-3);
    }

    &__go {
        flex: none;
        display: flex;
        align-items: center;
        gap: 2rpx;
    }

    &__go-text {
        font-size: var(--fs-22);
        color: var(--c-brand-text);
    }
}

.com {
    margin-top: 34rpx;
    padding: 0 0 40rpx;

    &__head {
        display: flex;
        align-items: center;
        gap: 10rpx;
        padding: 0 42rpx 18rpx;
    }

    &__title {
        font-size: 28rpx;
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__num {
        font-size: var(--fs-22);
        color: var(--c-ink-3);
    }

    &__sort {
        margin-left: auto;
        display: flex;
        gap: 20rpx;
    }

    &__sort-item {
        font-size: var(--fs-21);
        color: var(--c-ink-3);

        &.is-on {
            color: var(--c-ink);
            font-weight: var(--fw-medium);
        }
    }

    &__empty {
        padding: 40rpx 42rpx;
        text-align: center;
    }

    &__empty-text {
        font-size: var(--fs-22);
        color: var(--c-ink-3);
    }

    &__loading {
        display: flex;
        justify-content: center;
        padding: 30rpx 0;
    }
}

.cm {
    display: flex;
    gap: 16rpx;
    padding: 18rpx 42rpx;

    &__av {
        width: 60rpx;
        height: 60rpx;
        border-radius: var(--r-pill);
        background: var(--c-neutral-bg);
        flex: none;

        &--txt {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--c-surface-2);
        }
    }

    &__av-text {
        font-size: 24rpx;
        color: var(--c-ink-2);
    }

    &__body {
        flex: 1;
        min-width: 0;
    }

    &__who {
        display: flex;
        align-items: center;
        gap: 8rpx;
    }

    &__name {
        font-size: var(--fs-23);
        font-weight: var(--fw-medium);
        color: var(--c-ink-2);
    }

    &__p {
        display: block;
        margin-top: 8rpx;
        font-size: var(--fs-25);
        line-height: 1.7;
        color: var(--c-ink);
    }

    &__pending {
        flex: none;
        padding: 2rpx 8rpx;
        border-radius: var(--r-xs);
        background: var(--c-warning-bg);
        color: var(--c-warning);
        border: 1rpx solid var(--c-warning-border);
        font-size: var(--fs-18);
        line-height: var(--lh-tight);
    }

    &__act {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }

    /* 二级回复：左侧细线缩进，和父评论正文对齐（不再压在头像下面） */
    &__replies {
        margin-top: 10rpx;
    }

    &__replies-toggle {
        display: inline-flex;
        align-items: center;
        padding: 4rpx 0;
    }

    &__replies-toggle-text {
        font-size: var(--fs-21);
        color: var(--c-brand-text);
    }

    &__reply-list {
        margin-top: 6rpx;
        padding-left: var(--sp-2);
        border-left: 2rpx solid var(--c-line);
    }

    &__reply-item {
        display: flex;
        gap: 12rpx;
        padding: 12rpx 0;
        border-bottom: 1rpx solid var(--c-line);

        &:last-child {
            border-bottom: 0;
        }
    }

    &__reply-av {
        width: 44rpx;
        height: 44rpx;
        border-radius: var(--r-pill);
        background: var(--c-neutral-bg);
        flex: none;

        &--txt {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--c-surface-2);
        }
    }

    &__reply-av-text {
        font-size: var(--fs-18);
        color: var(--c-ink-2);
    }

    &__reply-body {
        flex: 1;
        min-width: 0;
    }

    &__reply-who {
        display: flex;
        align-items: center;
        gap: 8rpx;
    }

    &__reply-name {
        font-size: var(--fs-21);
        font-weight: var(--fw-medium);
        color: var(--c-ink-2);
    }

    &__reply-text {
        display: block;
        margin-top: 4rpx;
        font-size: var(--fs-23);
        line-height: var(--lh-body);
        color: var(--c-ink);
    }

    &__reply-to {
        color: var(--c-brand-text);
    }

    &__reply-foot {
        display: flex;
        align-items: center;
        gap: 20rpx;
        margin-top: 6rpx;
    }

    &__reply-loading {
        display: flex;
        justify-content: center;
        padding: 12rpx 0;
    }

    &__reply-tip {
        padding: 10rpx 0;
        font-size: var(--fs-21);
        color: var(--c-brand-text);
    }

    &__foot {
        display: flex;
        align-items: center;
        gap: 24rpx;
        margin-top: 10rpx;
    }

    &__time {
        font-size: var(--fs-19);
        color: var(--c-ink-3);
    }

    &__like {
        display: flex;
        align-items: center;
        gap: 6rpx;
        /* 和列表 .pstat 一样：颜色放在父级，tn-icon 只继承，避免组件节点吃掉图标类上的 color */
        color: var(--c-ink-3);

        &.is-on {
            color: var(--c-danger);
        }
    }

    &__like-num {
        font-size: var(--fs-19);
        color: inherit;
    }
}

.bar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 90;
    padding: 12rpx 24rpx calc(12rpx + var(--safe-bottom));
    background: var(--c-surface);
    border-top: 1rpx solid var(--c-line);

    &__in {
        display: flex;
        align-items: center;
        gap: 18rpx;
    }

    &__input {
        flex: 1;
        height: 68rpx;
        padding: 0 24rpx;
        border-radius: var(--r-pill);
        background: var(--c-surface-2);
        font-size: 24rpx;
        color: var(--c-ink);

        &--off {
            display: flex;
            align-items: center;
        }
    }

    &__ph {
        color: var(--c-ink-3);
    }

    /* 回复目标条：在输入框上方，不改变原有输入/点赞/分享三件套 */
    &__reply {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        padding: 0 4rpx 10rpx;
    }

    &__reply-text {
        font-size: var(--fs-21);
        color: var(--c-brand-text);
    }

    &__reply-close {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36rpx;
        height: 36rpx;
        color: var(--c-ink-3);
    }

    &__off-text {
        font-size: var(--fs-22);
        color: var(--c-ink-3);
    }

    &__act {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2rpx;
        flex: none;
        padding: 0 6rpx;
        /* 和列表 .pstat 一样：颜色放在父级，tn-icon 只继承 */
        color: var(--c-ink-3);

        &.is-on {
            color: var(--c-danger);
        }

        &--btn {
            background: transparent;
            border: 0;
            line-height: 1;
            padding: 0 6rpx;

            &::after {
                border: 0;
            }
        }
    }

    &__act-text {
        font-size: var(--fs-18);
        color: inherit;
    }
}

/* tn-icon 颜色工具类（本地 scoped 版）：tn-icon 的 color prop 不认 var()，
   颜色必须靠父级继承。 */
.ic-brand-text {
    color: var(--c-brand-text);
}
.ic-ink-2 {
    color: var(--c-ink-2);
}
</style>
