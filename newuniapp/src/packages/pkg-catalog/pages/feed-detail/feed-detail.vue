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
            <feed-comments
                :comments="comments"
                :comment-count="commentCount"
                :comment-loading="commentLoading"
                :sort="sort"
                :load-more-status="loadMoreStatus"
                :can-comment="canComment"
                :reply-states="replyStates"
                @switch-sort="switchSort"
                @like="onLikeComment"
                @reply="startReply"
                @toggle-replies="toggleReplies"
                @load-replies="loadReplies"
                @load-more="loadMore"
            />
        </template>

        <template #bottom>
            <!-- 底部操作条：评论 / 点赞 / 分享 -->
            <view v-if="post" class="bar">
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

                    <view
                        class="bar__act hit-88"
                        :class="{ 'is-on': post.is_liked }"
                        hover-class="is-pressed"
                        hover-stop-propagation="true"
                        aria-role="button"
                        :aria-label="post.is_liked ? '取消点赞' : '点赞'"
                        @touchstart.stop
                        @tap.stop="onLikePost"
                    >
                        <view class="bar__act-icon">
                            <tn-icon :name="post.is_liked ? 'like-fill' : 'like'" :size="30" />
                            <like-burst :active="!!post.is_liked" />
                        </view>
                        <view class="bar__act-text">
                            <rolling-number :value="likeCount" />
                        </view>
                    </view>

                    <button
                        class="bar__act bar__act--btn hit-88"
                        open-type="share"
                        hover-stop-propagation="true"
                    >
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
    </page-shell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad, onReachBottom, onShareAppMessage } from '@dcloudio/uni-app'
import { getCaseDetail, toggleCaseLike } from '@/api/content'
import { navigate } from '@/utils/link'
import { haptic } from '@/utils/haptics'
import { toast } from '@/composables/useToast'
import { useUserStore } from '@/stores/user'
import { useAppStore } from '@/stores/app'
import { useInteractionStore } from '@/stores/interaction'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { report } from '@/platform/report'
import { buildShare } from '@/utils/share'
import FeedComments from './components/feed-comments.vue'
import { useFeedComments } from './useFeedComments'

const userStore = useUserStore()
const appStore = useAppStore()
const interactionStore = useInteractionStore()

const post = ref<any>(null)
const loading = ref(true)
const rawError = ref<unknown>(null)
const error = computed(() => !!rawError.value)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const id = ref(0)

const {
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
} = useFeedComments(id, post)

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
const likeCount = computed(() => Math.max(0, Number(post.value?.like_count || 0)))

const dateText = computed(() => {
    const t = String(post.value?.create_time || '')
    return t ? `${t.slice(5, 10).replace('-', '.')} 发布` : ''
})

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

function previewDetailImage(index: number): void {
    const urls = imageUrls.value
    if (!urls.length) return
    uni.previewImage({ urls, current: urls[index] })
}

function onVideoError(): void {
    toast.error('视频加载失败，请稍后重试')
}

async function onLikePost() {
    if (!userStore.isLogin) {
        navigate({ path: '/pages/login/login' })
        return
    }
    const p = post.value
    if (!p) return
    haptic('medium', 'feed-like')
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

function goStaff() {
    if (!staffId.value) return
    navigate({
        path: '/packages/pkg-catalog/pages/staff-detail/staff-detail',
        query: { id: staffId.value }
    })
}

const registerShow = ref(false)

function goActivity() {
    registerShow.value = true
}

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

.media-video {
    margin-top: var(--sp-3);
    border-radius: var(--r-16);
    border: 1rpx solid var(--c-card-border);
    overflow: hidden;
    background: var(--c-dark-2);

    &__player {
        display: block;
        width: 100%;
        height: 375rpx;
    }
}

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
        border-radius: var(--r-16);
        border: 1rpx solid var(--c-card-border);
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
        font-size: var(--fs-md);
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
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__meta {
        display: block;
        margin-top: 6rpx;
        font-size: var(--fs-20);
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
        font-size: var(--fs-20);
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
    border: 1rpx solid var(--c-card-border);
    box-shadow: var(--shadow-card);

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
        font-size: var(--fs-md);
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
        font-size: var(--fs-20);
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

.bar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 90;
    padding: 12rpx 24rpx calc(12rpx + var(--safe-bottom));
    /* 浮层表面：底部输入条属于第三层，与抽屉同一套底色 */
    background: var(--c-surface-float);
    border-top: 1rpx solid var(--c-card-border);
    box-shadow: var(--shadow-sheet);

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
        font-size: var(--fs-xs);
        color: var(--c-ink);

        &--off {
            display: flex;
            align-items: center;
        }
    }

    &__ph {
        color: var(--c-ink-3);
    }

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

    &__act-icon {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__act-text {
        font-size: var(--fs-20);
        color: inherit;
    }
}

.ic-brand-text {
    color: var(--c-brand-text);
}
.ic-ink-2 {
    color: var(--c-ink-2);
}
</style>
