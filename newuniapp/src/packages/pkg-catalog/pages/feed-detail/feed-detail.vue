<template>
    <!--
        13 动态详情（设计稿 3165–3271）
        设计意图：让一次浏览走到一次预约——内容负责说服，作者卡负责把人交出去。

        与设计稿的三处差异（都由数据/开关决定，不是偷懒）：
        - 视频播放：小程序内联播放会同时出声，这里只出封面 + 播放键，点击走系统播放
        - 活动卡：只有动态真的挂了活动才渲染，没有就不占位
        - 评论输入：送审模式（feature_switch.mini_program_review_mode）下后端会拒发，
          所以开关打开或读不到时**隐藏输入框**，只保留只读列表
    -->
    <page-shell nav nav-variant="transparent" :show-back="true" bg="page" nav-tone="light">
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
            <!-- 头图 / 视频封面 -->
            <view v-if="cover" class="hero">
                <image class="hero__img" :src="cover" mode="aspectFill" />
                <view class="hero__mask" />
                <view v-if="isVideo" class="hero__play" @tap="playVideo">
                    <tn-icon name="play-fill" :size="44" class="ic-on-dark" />
                </view>
                <text v-if="post.location" class="hero__place">{{ post.location }}</text>
            </view>

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
                            <text class="author__name">{{ post.user_nickname || BRAND_NAME }}</text>
                            <status-tag
                                v-if="post.user_type === 2"
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

                <view class="meta">
                    <text v-for="t in tagList" :key="t" class="meta__hash"># {{ t }}</text>
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
                    <text v-if="commentTotal > 0" class="com__num">{{ commentTotal }}</text>
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
                        </view>
                        <text class="cm__p">{{ c.content }}</text>
                        <view v-if="c.reply_content" class="cm__reply">
                            <text class="cm__reply-b"
                                >{{ c.reply_user_nickname || '作者' }} 回复：</text
                            >
                            <text>{{ c.reply_content }}</text>
                        </view>
                        <view class="cm__foot">
                            <text class="cm__time">{{ c.create_time_text || '' }}</text>
                            <view class="cm__like" @tap="onLikeComment(c)">
                                <tn-icon
                                    :name="c.is_liked ? 'like-fill' : 'like'"
                                    :size="22"
                                    class="ic-like-c"
                                    :class="{ 'is-on': c.is_liked }"
                                />
                                <text class="cm__like-num">{{ c.like_count || 0 }}</text>
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

    <!-- 底部操作条：只做三件事 -->
    <view v-if="post" class="bar">
        <view class="bar__in">
            <template v-if="canComment">
                <input
                    v-model="draft"
                    class="bar__input"
                    placeholder="说点什么…"
                    placeholder-class="bar__ph"
                    confirm-type="send"
                    :adjust-position="true"
                    @confirm="submitComment"
                />
            </template>
            <view v-else class="bar__input bar__input--off">
                <text class="bar__off-text">评论暂不可用</text>
            </view>

            <view class="bar__act" :class="{ 'is-on': post.is_liked }" @tap="onLikePost">
                <tn-icon
                    :name="post.is_liked ? 'like-fill' : 'like'"
                    :size="30"
                    class="ic-like-p"
                    :class="{ 'is-on': post.is_liked }"
                />
                <text class="bar__act-text">{{ post.like_count || 0 }}</text>
            </view>

            <button class="bar__act bar__act--btn" open-type="share">
                <tn-icon name="share" :size="28" class="ic-ink-2" />
                <text class="bar__act-text">分享</text>
            </button>
        </view>
    </view>
    <app-toast />
</template>

<script setup lang="ts">
/**
 * 动态详情。
 *
 * 三个数据开关决定了这一页的形态：
 *  1. 评论区受 feature_switch.mini_program_review_mode 控制（送审模式后端会拒发评论）
 *  2. 活动卡只在动态挂了 activity 时出现
 *  3. 视频只出封面，点击走系统播放
 */
import { computed, ref } from 'vue'
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
import { resolveErrorView } from '@/utils/resolveErrorView'
import { report } from '@/platform/report'
import { BRAND_NAME } from '@/constants/brand'

interface CommentRow {
    id: number
    user_nickname?: string
    user_avatar?: string
    content?: string
    create_time_text?: string
    like_count?: number
    is_liked?: boolean | number
    reply_content?: string
    reply_user_nickname?: string
    parent_id?: number
}

const userStore = useUserStore()
const appStore = useAppStore()

const post = ref<any>(null)
const loading = ref(true)
const rawError = ref<unknown>(null)
const error = computed(() => !!rawError.value)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const id = ref(0)
const comments = ref<CommentRow[]>([])
const commentTotal = ref(0)
const commentLoading = ref(false)
const sort = ref<'hot' | 'new'>('hot')
const loadMoreStatus = ref<'idle' | 'loading' | 'nomore' | 'error'>('idle')
const draft = ref('')
let cPage = 1
let cHasMore = true

const staffId = computed(() => Number(post.value?.staff_id || post.value?.staff?.id || 0))

const cover = computed(() => {
    const p = post.value
    if (!p) return ''
    return imgUrl(p.video_cover || p.media_cover || (p.images && p.images[0]) || '')
})

const isVideo = computed(() => !!post.value?.video_url)

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
    const t = String(a.start_time || '')
    if (t.length >= 16) parts.push(t.slice(11, 16))
    if (a.city_name || a.address) parts.push(a.city_name || a.address)
    const left = Number(a.remaining ?? a.remain ?? 0)
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
        if (post.value && post.value.is_liked !== undefined) {
            post.value.is_liked = Number(post.value.is_liked) === 1
        }
    } catch (e) {
        rawError.value = e
        report('api_error', { endpoint: 'dynamic.detail', message: '动态详情加载失败' })
    } finally {
        loading.value = false
    }
}

async function loadComments(reset = false) {
    if (!id.value) return
    if (!reset && (loadMoreStatus.value === 'loading' || !cHasMore)) return
    if (reset) {
        cPage = 1
        cHasMore = true
        comments.value = []
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
        const rows: CommentRow[] = (paged.list || []).map((c: any) => ({
            ...c,
            is_liked: Number(c.is_liked) === 1
        }))
        comments.value = reset ? rows : comments.value.concat(rows)
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
    try {
        const res: any = await toggleCaseLike({ id: p.id })
        if (res && typeof res.is_like === 'number') p.is_liked = res.is_like === 1
    } catch {
        p.is_liked = !liked
        p.like_count = Math.max(0, Number(p.like_count || 0) + (liked ? -1 : 1))
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
    try {
        await addComment({ dynamic_id: id.value, content: text })
        draft.value = ''
        toast.success('评论已发布')
        loadComments(true)
        if (post.value) post.value.comment_count = Number(post.value.comment_count || 0) + 1
    } catch {
        toast.error('评论没发出去，请重试')
    }
}

function playVideo() {
    const url = post.value?.video_url
    if (!url) return
    // #ifdef MP-WEIXIN
    const vc = uni.createVideoContext('feedVideo')
    if (vc) {
        vc.play()
        return
    }
    // #endif
    navigate({
        path: '/packages/pkg-catalog/pages/feed-detail/feed-detail',
        query: { id: id.value }
    })
}

function goStaff() {
    if (!staffId.value) return
    navigate({
        path: '/packages/pkg-catalog/pages/staff-detail/staff-detail',
        query: { id: staffId.value }
    })
}

function goActivity() {
    const a = activity.value || {}
    navigate({
        path: '/packages/pkg-user/pages/activity/activity',
        query: a.id ? { id: a.id } : {}
    })
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

onShareAppMessage(() => ({
    title: post.value?.title || `${BRAND_NAME}婚礼主持`,
    path: `/packages/pkg-catalog/pages/feed-detail/feed-detail?id=${id.value}`,
    imageUrl: cover.value || undefined
}))
</script>

<style lang="scss" scoped>
.pad {
    padding: var(--sp-4);
}

.hero {
    position: relative;
    height: 560rpx;
    overflow: hidden;
    background: var(--c-ink);

    &__img {
        width: 100%;
        height: 100%;
        display: block;
    }

    &__mask {
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        background: linear-gradient(
            180deg,
            rgba(20, 17, 14, 0.5) 0%,
            rgba(20, 17, 14, 0) 34%,
            rgba(20, 17, 14, 0.55) 100%
        );
    }

    &__play {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        width: 108rpx;
        height: 108rpx;
        border-radius: var(--r-pill);
        background: rgba(20, 17, 14, 0.42);
        border: 1rpx solid rgba(255, 253, 248, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__place {
        position: absolute;
        left: 42rpx;
        bottom: 26rpx;
        font-size: 20rpx;
        letter-spacing: 0.08em;
        color: rgba(255, 253, 248, 0.86);
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

    &__reply {
        margin-top: 12rpx;
        padding: 14rpx 16rpx;
        border-radius: var(--r-sm);
        background: var(--c-surface-2);
        font-size: var(--fs-22);
        line-height: 1.65;
        color: var(--c-ink-2);
    }

    &__reply-b {
        color: var(--c-ink);
        font-weight: var(--fw-medium);
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
    }

    &__like-num {
        font-size: var(--fs-19);
        color: var(--c-ink-3);
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
        color: var(--c-ink-3);
    }
}

/* tn-icon 颜色工具类（本地 scoped 版）：tn-icon 的 color prop 不认 var()，
   颜色必须靠 CSS 落在图标根节点上再由内部 <text> 继承。 */
.ic-on-dark {
    color: var(--c-on-dark);
}
.ic-brand-text {
    color: var(--c-brand-text);
}
.ic-ink-2 {
    color: var(--c-ink-2);
}

/* 动态图标颜色：状态由父级/自身的 is-on 表达，颜色留在 CSS（tn-icon 的 color prop 不认 var()） */
.ic-like-c {
    color: var(--c-ink-3);
}
.ic-like-c.is-on {
    color: var(--c-brand-text);
}
.ic-like-p {
    color: var(--c-ink-2);
}
.ic-like-p.is-on {
    color: var(--c-brand-text);
}
</style>
