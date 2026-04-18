<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="动态详情" @back="handleBack" />

        <view v-if="detail" class="dynamic-detail">
            <scroll-view scroll-y class="dynamic-detail__scroll" :style="scrollStyle">
                <view class="dynamic-detail__content">
                    <view
                        v-if="detail.video"
                        class="dynamic-detail__hero dynamic-detail__hero--video"
                    >
                        <video
                            :src="detail.video"
                            class="dynamic-detail__hero-video"
                            :poster="detail.video_cover"
                            controls
                            object-fit="cover"
                        />
                    </view>
                    <view v-else-if="heroImage" class="dynamic-detail__hero">
                        <image
                            :src="heroImage"
                            class="dynamic-detail__hero-image"
                            mode="aspectFill"
                            @click="previewImage(detail.images, 0)"
                        />
                    </view>

                    <view v-if="galleryImages.length > 0" class="dynamic-detail__gallery">
                        <view
                            v-for="(img, idx) in galleryImages"
                            :key="`${img}-${idx}`"
                            class="dynamic-detail__gallery-item"
                            @click="previewImage(detail.images, idx + 1)"
                        >
                            <image
                                class="dynamic-detail__gallery-image"
                                :src="img"
                                mode="aspectFill"
                            />
                        </view>
                    </view>

                    <view class="dynamic-detail__lead-shell">
                        <view class="dynamic-detail__author-card">
                            <view class="dynamic-detail__author-main">
                                <image
                                    :src="
                                        detail.user_avatar ||
                                        '/static/images/user/default_avatar.png'
                                    "
                                    class="dynamic-detail__avatar"
                                    mode="aspectFill"
                                />
                                <view class="dynamic-detail__author-copy">
                                    <view class="dynamic-detail__author-row">
                                        <text class="dynamic-detail__author-name">
                                            {{ detail.user_nickname }}
                                        </text>
                                        <view
                                            v-if="detail.user_type === 2"
                                            class="dynamic-detail__author-badge dynamic-detail__author-badge--staff"
                                        >
                                            服务人员
                                        </view>
                                        <view
                                            v-if="detail.user_type === 3"
                                            class="dynamic-detail__author-badge dynamic-detail__author-badge--official"
                                        >
                                            官方
                                        </view>
                                        <view
                                            v-if="detail.is_top === 1"
                                            class="dynamic-detail__author-badge dynamic-detail__author-badge--top"
                                        >
                                            置顶
                                        </view>
                                        <view
                                            v-if="detail.is_hot === 1"
                                            class="dynamic-detail__author-badge dynamic-detail__author-badge--hot"
                                        >
                                            热门
                                        </view>
                                    </view>
                                    <text class="dynamic-detail__author-meta">
                                        {{ authorMetaText }}
                                    </text>
                                </view>
                            </view>

                            <view
                                v-if="detail.can_favorite"
                                class="dynamic-detail__favorite-btn"
                                :class="{ 'is-active': detail.is_favorite }"
                                @click="handleFavorite"
                            >
                                <tn-icon
                                    :name="detail.is_favorite ? 'star-fill' : 'star'"
                                    size="26"
                                />
                                <text>{{ detail.is_favorite ? '已收藏' : '收藏' }}</text>
                            </view>
                        </view>

                        <view v-if="showMetaTags" class="dynamic-detail__tag-row">
                            <text
                                v-if="detail.dynamic_type && detail.dynamic_type !== 1"
                                class="dynamic-detail__type-tag"
                                :class="getTypeClass(detail.dynamic_type)"
                            >
                                {{ getTypeText(detail.dynamic_type) }}
                            </text>
                            <view
                                v-for="(tag, tagIdx) in detailTags"
                                :key="`${tag}-${tagIdx}`"
                                class="dynamic-detail__topic-tag"
                            >
                                <text>#{{ tag }}</text>
                            </view>
                        </view>

                        <text class="dynamic-detail__content-text">{{ detail.content }}</text>
                    </view>

                    <view class="dynamic-detail__support-band">
                        <view class="dynamic-detail__stats">
                            <view
                                class="dynamic-detail__stat-pill"
                                :class="{ 'is-active': detail.is_liked }"
                                @click="handleLike"
                            >
                                <text class="dynamic-detail__stat-label">点赞</text>
                                <text class="dynamic-detail__stat-text">
                                    {{ formatCount(detail.like_count) }}
                                </text>
                            </view>
                            <view class="dynamic-detail__stat-pill" @click="showCommentInput">
                                <text class="dynamic-detail__stat-label">评论</text>
                                <text class="dynamic-detail__stat-text">
                                    {{ formatCount(detail.comment_count) }}
                                </text>
                            </view>
                            <view class="dynamic-detail__stat-pill">
                                <text class="dynamic-detail__stat-label">浏览</text>
                                <text class="dynamic-detail__stat-text">
                                    {{ formatCount(detail.view_count) }}
                                </text>
                            </view>
                        </view>

                        <view class="dynamic-detail__detail-actions">
                            <view
                                class="dynamic-detail__detail-action"
                                :class="{ 'is-active': detail.is_collected }"
                                @click="handleCollect"
                            >
                                <tn-icon
                                    :name="detail.is_collected ? 'star-fill' : 'star'"
                                    size="26"
                                />
                                <text>{{ detail.is_collected ? '已收藏动态' : '收藏动态' }}</text>
                            </view>
                            <button
                                class="dynamic-detail__detail-action dynamic-detail__detail-action--share"
                                hover-class="none"
                                open-type="share"
                            >
                                <tn-icon name="share" size="26" />
                                <text>分享动态</text>
                            </button>
                        </view>
                    </view>

                    <view class="dynamic-detail__comments">
                        <view class="dynamic-detail__comments-head">
                            <text class="dynamic-detail__comments-title">
                                评论 {{ formatCount(detail.comment_count) }}
                            </text>
                            <view class="dynamic-detail__comments-sort">
                                <text
                                    class="dynamic-detail__sort-item"
                                    :class="{ 'is-active': commentSort === 'hot' }"
                                    @click="changeCommentSort('hot')"
                                >
                                    最热
                                </text>
                                <text class="dynamic-detail__sort-divider">|</text>
                                <text
                                    class="dynamic-detail__sort-item"
                                    :class="{ 'is-active': commentSort === 'new' }"
                                    @click="changeCommentSort('new')"
                                >
                                    最新
                                </text>
                            </view>
                        </view>

                        <view v-if="comments.length === 0" class="dynamic-detail__comment-empty">
                            暂无评论
                        </view>
                        <view v-else class="dynamic-detail__comment-list">
                            <view class="dynamic-detail__comment-stack">
                                <view
                                    v-for="item in comments"
                                    :key="`comment-${item.id}`"
                                    class="dynamic-detail__comment-item"
                                >
                                    <view class="dynamic-detail__comment-main">
                                        <view class="dynamic-detail__comment-meta">
                                            <text class="dynamic-detail__comment-author">
                                                {{ getCommentAuthorText(item) }}
                                            </text>
                                            <view class="dynamic-detail__comment-meta-right">
                                                <text class="dynamic-detail__comment-time">
                                                    {{ formatCommentTime(item.date) }}
                                                </text>
                                                <text class="dynamic-detail__comment-meta-dot"
                                                    >·</text
                                                >
                                                <text
                                                    class="dynamic-detail__comment-like-meta"
                                                    :class="{ 'is-active': item.likeActive }"
                                                    @tap.stop="handleLikeComment(item.id)"
                                                >
                                                    赞 {{ formatCommentLikeCount(item.likeCount) }}
                                                </text>
                                            </view>
                                        </view>
                                        <text class="dynamic-detail__comment-content">
                                            {{ item.content }}
                                        </text>
                                        <view class="dynamic-detail__comment-actions">
                                            <text
                                                class="dynamic-detail__comment-action"
                                                @tap.stop="replyComment(item)"
                                            >
                                                回复
                                            </text>
                                            <text
                                                v-if="item.allowDelete"
                                                class="dynamic-detail__comment-action is-danger"
                                                @tap.stop="deleteCommentItem(item.id)"
                                            >
                                                删除
                                            </text>
                                        </view>
                                    </view>

                                    <view
                                        v-if="item.replyExpanded && item.comment.length > 0"
                                        class="dynamic-detail__reply-list"
                                    >
                                        <view
                                            v-for="reply in item.comment"
                                            :key="`reply-${reply.id}`"
                                            class="dynamic-detail__reply-item"
                                        >
                                            <view class="dynamic-detail__comment-main">
                                                <view class="dynamic-detail__comment-meta">
                                                    <text class="dynamic-detail__comment-author">
                                                        {{ getCommentAuthorText(reply) }}
                                                    </text>
                                                    <view
                                                        class="dynamic-detail__comment-meta-right"
                                                    >
                                                        <text class="dynamic-detail__comment-time">
                                                            {{ formatCommentTime(reply.date) }}
                                                        </text>
                                                        <text
                                                            class="dynamic-detail__comment-meta-dot"
                                                        >
                                                            ·
                                                        </text>
                                                        <text
                                                            class="dynamic-detail__comment-like-meta"
                                                            :class="{
                                                                'is-active': reply.likeActive
                                                            }"
                                                            @tap.stop="handleLikeComment(reply.id)"
                                                        >
                                                            赞
                                                            {{
                                                                formatCommentLikeCount(
                                                                    reply.likeCount
                                                                )
                                                            }}
                                                        </text>
                                                    </view>
                                                </view>
                                                <text class="dynamic-detail__comment-content">
                                                    {{ reply.content }}
                                                </text>
                                                <view class="dynamic-detail__comment-actions">
                                                    <text
                                                        class="dynamic-detail__comment-action"
                                                        @tap.stop="replyComment(reply)"
                                                    >
                                                        回复
                                                    </text>
                                                    <text
                                                        v-if="reply.allowDelete"
                                                        class="dynamic-detail__comment-action is-danger"
                                                        @tap.stop="deleteCommentItem(reply.id)"
                                                    >
                                                        删除
                                                    </text>
                                                </view>
                                            </view>
                                        </view>
                                    </view>

                                    <view
                                        v-if="item.commentCount > 0"
                                        class="dynamic-detail__reply-toggle"
                                        @tap.stop="toggleReplies(item)"
                                    >
                                        {{ getReplyToggleText(item) }}
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view
                            v-if="commentHasMore && comments.length > 0"
                            class="dynamic-detail__comment-more"
                        >
                            <text v-if="commentLoading">加载中...</text>
                            <text v-else @click="loadMoreComments">点击加载更多</text>
                        </view>
                    </view>
                </view>
            </scroll-view>

            <BaseOverlayMask
                :show="showComment"
                :z-index="commentPopupMaskZIndex"
                :background="$theme.maskColor || 'rgba(8, 10, 16, 0.58)'"
                @close="closeCommentPopup"
            />

            <TnPopup
                v-model="showComment"
                open-direction="bottom"
                :overlay="false"
                :safe-area-inset-bottom="true"
                :radius="28"
                height="68%"
                :z-index="commentPopupZIndex"
            >
                <view class="dynamic-detail__popup">
                    <view class="dynamic-detail__popup-head">
                        <text class="dynamic-detail__popup-title">
                            {{ replyTo ? `回复 @${replyTo.user_nickname}` : '发表评论' }}
                        </text>
                        <view class="dynamic-detail__popup-close" @click="closeCommentPopup">
                            <tn-icon name="close" size="30" color="#978B83" />
                        </view>
                    </view>
                    <view class="dynamic-detail__popup-body">
                        <view class="dynamic-detail__textarea-panel">
                            <textarea
                                v-model="commentContent"
                                class="dynamic-detail__textarea"
                                :placeholder="
                                    replyTo ? `回复 @${replyTo.user_nickname}` : '说点什么...'
                                "
                                :maxlength="commentMaxLength"
                                :focus="commentFocused && showComment"
                                :selection-start="commentSelectionStart"
                                :selection-end="commentSelectionEnd"
                                cursor-spacing="120"
                                fixed
                                placeholder-class="dynamic-detail__textarea-placeholder"
                                @focus="handleCommentFocus"
                                @blur="handleCommentBlur"
                                @input="handleCommentInput"
                            />
                        </view>
                    </view>
                    <view
                        class="dynamic-detail__popup-footer"
                        :class="{ 'is-emoji-open': showEmojiPanel }"
                    >
                        <view class="dynamic-detail__popup-actions">
                            <text class="dynamic-detail__char-count">
                                {{ commentDisplayLength }}/{{ commentMaxLength }}
                            </text>
                            <view class="dynamic-detail__composer-actions">
                                <button
                                    class="dynamic-detail__emoji-btn"
                                    :class="{ 'is-active': showEmojiPanel }"
                                    @click="toggleEmojiPanel"
                                >
                                    表情
                                </button>
                                <button
                                    class="dynamic-detail__submit-btn"
                                    :class="{ 'is-disabled': !canSubmitComment }"
                                    :disabled="!canSubmitComment"
                                    @click="submitComment"
                                >
                                    发送
                                </button>
                            </view>
                        </view>
                        <view
                            v-if="showEmojiPanel"
                            class="dynamic-detail__emoji-panel"
                            @tap.stop="() => {}"
                        >
                            <view
                                v-for="emoji in emojiList"
                                :key="emoji"
                                class="dynamic-detail__emoji-item"
                                @tap.stop="insertEmoji(emoji)"
                            >
                                <text class="dynamic-detail__emoji-char">{{ emoji }}</text>
                            </view>
                        </view>
                    </view>
                </view>
            </TnPopup>
        </view>

        <view v-else class="dynamic-detail__loading-view">
            <text class="dynamic-detail__loading-text">加载中...</text>
        </view>
    </PageShell>
</template>
<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { onLoad, onShareAppMessage } from '@dcloudio/uni-app'
import TnPopup from '@tuniao/tnui-vue3-uniapp/components/popup/src/popup.vue'
import { useNavBarMetrics } from '@/hooks/useNavBarMetrics'
import PageShell from '@/components/base/PageShell.vue'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import { DYNAMIC_LIST_REFRESH_KEY } from '@/enums/constantEnums'
import {
    getDynamicDetail,
    likeDynamic,
    collectDynamic,
    getCommentList,
    addComment,
    deleteComment,
    likeComment
} from '@/api/dynamic'
import { toggleStaffFavorite } from '@/api/staff'
import cache from '@/utils/cache'

const $theme = useThemeStore()
const userStore = useUserStore()
const navBarMetrics = useNavBarMetrics()
const commentPopupMaskZIndex = 20118
const commentPopupZIndex = 20120
const userId = computed(() => userStore.userInfo?.id)

type DynamicReplyItem = {
    id: string | number
    avatar: string
    nickname: string
    date: string
    content: string
    likeActive: boolean
    likeCount: number
    allowDelete: boolean
    replyUserNickname?: string
}

type DynamicCommentItem = DynamicReplyItem & {
    commentCount: number
    comment: DynamicReplyItem[]
    replyExpanded: boolean
    replyLoading: boolean
}

const dynamicId = ref(0)
const detail = ref<any>(null)
const comments = ref<DynamicCommentItem[]>([])
const commentSort = ref('hot')
const commentPage = ref(1)
const commentHasMore = ref(true)
const commentLoading = ref(false)

const showComment = ref(false)
const commentContent = ref('')
const commentFocused = ref(false)
const showEmojiPanel = ref(false)
const commentSelectionStart = ref(0)
const commentSelectionEnd = ref(0)
const replyTo = ref<any>(null)
const parentComment = ref<any>(null)
const commentMaxLength = 500
// 常用表情面板，避免引入额外资源依赖。
const emojiList = [
    '😀',
    '😄',
    '😊',
    '😍',
    '😘',
    '🤗',
    '🤔',
    '😅',
    '😭',
    '😡',
    '😎',
    '🥳',
    '😴',
    '👍',
    '👏',
    '🙏',
    '😇',
    '🤍',
    '💖',
    '🔥',
    '✨',
    '🎉',
    '💐',
    '🌹'
]

const scrollStyle = computed(() => ({
    height: `calc(100vh - ${navBarMetrics.navBarHeight}px)`
}))

const commentDisplayLength = computed(() => Array.from(commentContent.value).length)
const canSubmitComment = computed(() => Boolean(commentContent.value.trim()))

const heroImage = computed(() => {
    if (detail.value?.video) {
        return detail.value.video_cover || ''
    }
    return detail.value?.images?.[0] || ''
})

const galleryImages = computed(() => {
    if (detail.value?.video || !Array.isArray(detail.value?.images)) {
        return []
    }
    return detail.value.images.slice(1)
})

const detailTags = computed(() => {
    return Array.isArray(detail.value?.tags) ? detail.value.tags : []
})

const showMetaTags = computed(() => {
    return detailTags.value.length > 0 || Number(detail.value?.dynamic_type || 1) !== 1
})

const normalizeImageList = (images: any): string[] => {
    if (Array.isArray(images)) {
        return images.map((item) => String(item || '').trim()).filter(Boolean)
    }

    if (typeof images === 'string') {
        const value = images.trim()
        if (!value) return []

        try {
            const parsed = JSON.parse(value)
            if (Array.isArray(parsed)) {
                return parsed.map((item) => String(item || '').trim()).filter(Boolean)
            }
        } catch (error) {
            // ignore parse error and fallback to single-value parsing
        }

        return value
            .split(',')
            .map((item) => item.trim())
            .filter(Boolean)
    }

    return []
}

const toNumber = (value: any) => Number(value || 0)

const authorMetaText = computed(() => {
    const parts: string[] = []
    if (detail.value?.create_time) {
        parts.push(`发布于 ${detail.value.create_time}`)
    }
    if (detail.value?.location) {
        parts.push(detail.value.location)
    }
    return parts.join(' · ') || '发布于刚刚'
})

const markDynamicListShouldRefresh = () => {
    cache.set(DYNAMIC_LIST_REFRESH_KEY, 1)
}

const getTypeClass = (type: number) => {
    const classes: Record<number, string> = {
        2: 'dynamic-detail__type-tag--video',
        3: 'dynamic-detail__type-tag--case',
        4: 'dynamic-detail__type-tag--activity'
    }
    return classes[type] || 'dynamic-detail__type-tag--graphic'
}

const getTypeText = (type: number) => {
    const texts: Record<number, string> = {
        1: '图文',
        2: '视频',
        3: '案例',
        4: '活动'
    }
    return texts[type] || ''
}

const formatCount = (count: number) => {
    if (!count) return '0'
    if (count >= 10000) {
        return `${(count / 10000).toFixed(count >= 100000 ? 0 : 1).replace(/\.0$/, '')}万`
    }
    if (count >= 1000) {
        return `${(count / 1000).toFixed(1).replace(/\.0$/, '')}k`
    }
    return `${count}`
}

const padTimeUnit = (value: number) => `${value}`.padStart(2, '0')

const formatCommentTime = (time: string) => {
    const value = String(time || '').trim()
    if (!value) return '刚刚'

    const normalizedValue = value.includes('T') ? value : value.replace(' ', 'T')
    const timestamp = new Date(normalizedValue).getTime()

    if (Number.isNaN(timestamp)) {
        const [dateText = '', timeText = ''] = value.split(' ')
        if (/^\d{4}-\d{2}-\d{2}$/.test(dateText)) {
            return `${dateText.slice(5)}${timeText ? ` ${timeText.slice(0, 5)}` : ''}`
        }
        return value
    }

    const diff = Date.now() - timestamp
    const minute = 60 * 1000
    const hour = 60 * minute
    const day = 24 * hour

    if (diff < minute) return '刚刚'
    if (diff < hour) return `${Math.floor(diff / minute)}分钟前`
    if (diff < day) return `${Math.floor(diff / hour)}小时前`
    if (diff < 7 * day) return `${Math.floor(diff / day)}天前`

    const date = new Date(timestamp)
    return `${padTimeUnit(date.getMonth() + 1)}-${padTimeUnit(date.getDate())} ${padTimeUnit(
        date.getHours()
    )}:${padTimeUnit(date.getMinutes())}`
}

const formatCommentLikeCount = (count: number) => `${Number(count || 0)}`

const getCommentAuthorText = (item: DynamicReplyItem) => {
    return item.replyUserNickname ? `${item.nickname} 回复` : item.nickname
}

const createCommentItem = (item: any): DynamicCommentItem => ({
    id: item.id,
    avatar: item.user?.avatar || '/static/images/user/default_avatar.png',
    nickname: item.user?.nickname || '匿名用户',
    date: item.create_time || '',
    content: item.content || '',
    likeActive: Boolean(item.is_liked),
    likeCount: toNumber(item.like_count),
    allowDelete: item.user_id === userId.value,
    commentCount: toNumber(item.reply_count),
    comment: [],
    replyExpanded: false,
    replyLoading: false
})

const createReplyItem = (reply: any): DynamicReplyItem => ({
    id: reply.id,
    avatar: reply.user_avatar || '/static/images/user/default_avatar.png',
    nickname: reply.user_nickname || '匿名用户',
    date: reply.create_time || '',
    content: reply.content || '',
    likeActive: Boolean(reply.is_liked),
    likeCount: toNumber(reply.like_count),
    allowDelete: reply.user_id === userId.value,
    replyUserNickname: reply.reply_user_nickname || ''
})

const findCommentLocation = (commentId: string | number) => {
    for (let commentIndex = 0; commentIndex < comments.value.length; commentIndex += 1) {
        const item = comments.value[commentIndex]
        if (item.id === commentId) {
            return { commentIndex, replyIndex: -1 }
        }
        const replyIndex = item.comment.findIndex((reply) => reply.id === commentId)
        if (replyIndex !== -1) {
            return { commentIndex, replyIndex }
        }
    }
    return null
}

const findCommentItem = (commentId: string | number) => {
    const location = findCommentLocation(commentId)
    if (!location) return null

    if (location.replyIndex === -1) {
        return comments.value[location.commentIndex]
    }

    return comments.value[location.commentIndex].comment[location.replyIndex]
}

const toggleLocalCommentLike = (commentId: string | number) => {
    const target = findCommentItem(commentId)
    if (!target) return

    target.likeActive = !target.likeActive
    target.likeCount = Math.max(0, Number(target.likeCount || 0) + (target.likeActive ? 1 : -1))
}

const getReplyToggleText = (item: DynamicCommentItem) => {
    if (item.replyLoading) return '加载中...'
    if (!item.replyExpanded) {
        const visibleCount = item.comment.length > 0 ? item.comment.length : item.commentCount
        return `查看${visibleCount}条回复`
    }
    if (item.comment.length < item.commentCount) {
        return '加载更多回复'
    }
    return '收起回复'
}

const handleBack = () => {
    const pages = getCurrentPages()
    if (pages.length > 1) {
        uni.navigateBack()
        return
    }

    uni.switchTab({ url: '/pages/dynamic/dynamic' })
}

const fetchDetail = async () => {
    try {
        const res = await getDynamicDetail({ id: dynamicId.value })

        let tags: string[] = []
        if (res.tags) {
            if (typeof res.tags === 'string') {
                tags = res.tags
                    .split(',')
                    .map((tag: string) => tag.trim())
                    .filter(Boolean)
            } else if (Array.isArray(res.tags)) {
                tags = res.tags
            }
        }

        detail.value = {
            ...res,
            images: normalizeImageList(res.images),
            tags,
            is_favorite: Boolean(res.is_favorite),
            is_collected: Boolean(res.is_collected),
            is_liked: Boolean(res.is_liked),
            can_favorite: Number(res.user_type) === 2 && Number(res.staff_id || 0) > 0,
            like_count: toNumber(res.like_count),
            comment_count: toNumber(res.comment_count),
            collect_count: toNumber(res.collect_count),
            view_count: toNumber(res.view_count),
            video: res.video_url || res.video || '',
            video_cover: res.video_cover || ''
        }
    } catch (error: any) {
        uni.showToast({ title: error.message || '加载失败', icon: 'none' })
        setTimeout(() => {
            handleBack()
        }, 1500)
    }
}

const fetchComments = async (refresh = false) => {
    if (commentLoading.value) return
    commentLoading.value = true

    try {
        if (refresh) {
            commentPage.value = 1
            comments.value = []
        }

        const res = await getCommentList({
            dynamic_id: dynamicId.value,
            page: commentPage.value,
            page_size: 20,
            sort: commentSort.value
        })

        const list = (res.data || []).map(createCommentItem)

        if (refresh) {
            comments.value = list
        } else {
            comments.value.push(...list)
        }

        commentHasMore.value = list.length === 20
    } catch (error) {
        console.error(error)
    } finally {
        commentLoading.value = false
    }
}

const changeCommentSort = (sort: string) => {
    commentSort.value = sort
    fetchComments(true)
}

const loadMoreComments = () => {
    if (commentHasMore.value && !commentLoading.value) {
        commentPage.value += 1
        fetchComments()
    }
}

const loadMoreReplies = async (item: DynamicCommentItem) => {
    if (item.replyLoading) return
    item.replyLoading = true

    try {
        const res = await getCommentList({
            dynamic_id: dynamicId.value,
            parent_id: item.id,
            page: Math.floor(item.comment.length / 20) + 1,
            page_size: 20
        })

        const replyList = (res.data || []).map(createReplyItem)
        item.comment.push(...replyList)
        item.replyExpanded = true
    } catch (error: any) {
        uni.showToast({ title: error.message || '加载失败', icon: 'none' })
    } finally {
        item.replyLoading = false
    }
}

const toggleReplies = (item: DynamicCommentItem) => {
    if (item.replyLoading) return

    if (!item.replyExpanded) {
        if (item.comment.length === 0) {
            loadMoreReplies(item)
            return
        }
        item.replyExpanded = true
        return
    }

    if (item.comment.length < item.commentCount) {
        loadMoreReplies(item)
        return
    }

    item.replyExpanded = false
}

const handleLike = async () => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    try {
        await likeDynamic({ id: dynamicId.value })
        detail.value.is_liked = !detail.value.is_liked
        detail.value.like_count += detail.value.is_liked ? 1 : -1
        markDynamicListShouldRefresh()
    } catch (error: any) {
        uni.showToast({ title: error.message || '操作失败', icon: 'none' })
    }
}

const handleCollect = async () => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    try {
        await collectDynamic({ id: dynamicId.value })
        detail.value.is_collected = !detail.value.is_collected
        detail.value.collect_count += detail.value.is_collected ? 1 : -1
        markDynamicListShouldRefresh()
        uni.showToast({
            title: detail.value.is_collected ? '收藏成功' : '取消收藏',
            icon: 'none'
        })
    } catch (error: any) {
        uni.showToast({ title: error.message || '操作失败', icon: 'none' })
    }
}

const handleFavorite = async () => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    if (!detail.value?.staff_id) {
        return
    }
    try {
        await toggleStaffFavorite({ id: detail.value.staff_id })
        detail.value.is_favorite = !detail.value.is_favorite
        markDynamicListShouldRefresh()
        uni.showToast({
            title: detail.value.is_favorite ? '收藏成功' : '已取消收藏',
            icon: 'none'
        })
    } catch (error: any) {
        uni.showToast({ title: error.message || '操作失败', icon: 'none' })
    }
}

const handleLikeComment = async (id: string | number) => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    try {
        await likeComment({ id })
        toggleLocalCommentLike(id)
    } catch (error: any) {
        uni.showToast({ title: error.message || '操作失败', icon: 'none' })
    }
}

const resetCommentSelection = (value = commentContent.value) => {
    const end = value.length
    commentSelectionStart.value = end
    commentSelectionEnd.value = end
}

const syncCommentSelection = (detail?: Record<string, any>) => {
    const valueLength = commentContent.value.length
    const fallbackCursor =
        typeof detail?.cursor === 'number' ? detail.cursor : commentSelectionEnd.value
    const start =
        typeof detail?.selectionStart === 'number' ? detail.selectionStart : fallbackCursor
    const end = typeof detail?.selectionEnd === 'number' ? detail.selectionEnd : fallbackCursor

    commentSelectionStart.value = Math.max(0, Math.min(start, valueLength))
    commentSelectionEnd.value = Math.max(0, Math.min(end, valueLength))
}

const focusCommentInput = () => {
    showEmojiPanel.value = false
    commentFocused.value = false
    nextTick(() => {
        commentFocused.value = true
    })
}

const closeCommentPopup = () => {
    commentFocused.value = false
    uni.hideKeyboard()
    showComment.value = false
}

const handleCommentFocus = (event: any) => {
    commentFocused.value = true
    showEmojiPanel.value = false
    syncCommentSelection(event?.detail)
}

const handleCommentBlur = (event: any) => {
    commentFocused.value = false
    syncCommentSelection(event?.detail)
}

const handleCommentInput = (event: any) => {
    commentContent.value = event?.detail?.value ?? ''
    syncCommentSelection(event?.detail)
}

const toggleEmojiPanel = () => {
    if (showEmojiPanel.value) {
        focusCommentInput()
        return
    }

    commentFocused.value = false
    uni.hideKeyboard()
    setTimeout(() => {
        if (showComment.value) {
            showEmojiPanel.value = true
        }
    }, 80)
}

const insertEmoji = (emoji: string) => {
    const start = Math.max(0, Math.min(commentSelectionStart.value, commentContent.value.length))
    const end = Math.max(start, Math.min(commentSelectionEnd.value, commentContent.value.length))
    const nextValue = `${commentContent.value.slice(0, start)}${emoji}${commentContent.value.slice(
        end
    )}`

    if (Array.from(nextValue).length > commentMaxLength) {
        uni.showToast({
            title: `评论内容最多 ${commentMaxLength} 个字符`,
            icon: 'none'
        })
        return
    }

    commentContent.value = nextValue
    const nextCursor = start + emoji.length
    commentSelectionStart.value = nextCursor
    commentSelectionEnd.value = nextCursor
}

const showCommentInput = () => {
    if (detail.value.allow_comment !== 1) {
        uni.showToast({
            title: '该动态不允许评论',
            icon: 'none'
        })
        return
    }

    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }

    replyTo.value = null
    parentComment.value = null
    commentContent.value = ''
    showEmojiPanel.value = false
    commentFocused.value = false
    resetCommentSelection('')
    showComment.value = true
}

const replyComment = (comment: DynamicCommentItem | DynamicReplyItem) => {
    if (detail.value.allow_comment !== 1) {
        uni.showToast({
            title: '该动态不允许评论',
            icon: 'none'
        })
        return
    }

    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }

    replyTo.value = { id: comment.id, user_nickname: comment.nickname }
    parentComment.value = comment
    commentContent.value = ''
    showEmojiPanel.value = false
    commentFocused.value = false
    resetCommentSelection('')
    showComment.value = true
}

const submitComment = async () => {
    const submittedContent = commentContent.value.trim()
    if (!submittedContent) return

    try {
        const params: any = {
            dynamic_id: dynamicId.value,
            content: submittedContent
        }

        if (replyTo.value) {
            params.parent_id = parentComment.value.id
            params.reply_user_id = replyTo.value.id
        }

        const res = await addComment(params)

        uni.showToast({ title: '评论成功' })

        if (replyTo.value) {
            const location = findCommentLocation(parentComment.value.id)
            const newReplyData: DynamicReplyItem = {
                id: res.comment_id || Date.now(),
                avatar: userStore.userInfo?.avatar || '/static/images/user/default_avatar.png',
                nickname: userStore.userInfo?.nickname || '我',
                date: '刚刚',
                content: submittedContent,
                likeActive: false,
                likeCount: 0,
                allowDelete: true,
                replyUserNickname: replyTo.value.user_nickname
            }

            if (location) {
                const parent = comments.value[location.commentIndex]
                parent.replyExpanded = true

                if (location.replyIndex === -1) {
                    parent.comment.unshift(newReplyData)
                } else {
                    parent.comment.splice(location.replyIndex + 1, 0, newReplyData)
                }

                parent.commentCount += 1
            } else {
                fetchComments(true)
            }
        } else {
            const newCommentData: DynamicCommentItem = {
                id: res.comment_id || Date.now(),
                avatar: userStore.userInfo?.avatar || '/static/images/user/default_avatar.png',
                nickname: userStore.userInfo?.nickname || '我',
                date: '刚刚',
                content: submittedContent,
                likeActive: false,
                likeCount: 0,
                allowDelete: true,
                commentCount: 0,
                comment: [],
                replyExpanded: false,
                replyLoading: false
            }

            comments.value.unshift(newCommentData)
        }

        commentContent.value = ''
        resetCommentSelection('')
        showComment.value = false
        replyTo.value = null
        parentComment.value = null
        detail.value.comment_count += 1
        markDynamicListShouldRefresh()
    } catch (error: any) {
        uni.showToast({ title: error.message || '评论失败', icon: 'none' })
    }
}

const deleteCommentItem = async (id: string | number) => {
    const res = await uni.showModal({
        title: '提示',
        content: '确定要删除该评论吗？'
    })
    if (res.confirm) {
        try {
            await deleteComment({ comment_id: id })
            uni.showToast({ title: '删除成功' })

            const location = findCommentLocation(id)
            if (location) {
                if (location.replyIndex === -1) {
                    const [removedComment] = comments.value.splice(location.commentIndex, 1)
                    const removedCount = 1 + Number(removedComment?.commentCount || 0)
                    detail.value.comment_count = Math.max(
                        0,
                        Number(detail.value.comment_count || 0) - removedCount
                    )
                } else {
                    const parent = comments.value[location.commentIndex]
                    parent.comment.splice(location.replyIndex, 1)
                    parent.commentCount = Math.max(0, Number(parent.commentCount || 0) - 1)
                    detail.value.comment_count = Math.max(
                        0,
                        Number(detail.value.comment_count || 0) - 1
                    )
                }
            }

            markDynamicListShouldRefresh()
        } catch (error: any) {
            uni.showToast({ title: error.message || '删除失败', icon: 'none' })
        }
    }
}

const previewImage = (images: string[], current: number) => {
    const previewImages = normalizeImageList(images)
    if (previewImages.length === 0) {
        return
    }

    uni.previewImage({
        urls: previewImages,
        current: Math.max(0, Math.min(current, previewImages.length - 1))
    })
}

onLoad((options: any) => {
    if (options.id) {
        dynamicId.value = Number(options.id)
        fetchDetail()
        fetchComments(true)
    }
})

onShareAppMessage(() => ({
    title: detail.value?.content?.slice(0, 30) || '精彩动态',
    path: `/pages/dynamic_detail/dynamic_detail?id=${dynamicId.value}`
}))

watch(showComment, (visible) => {
    if (visible) {
        showEmojiPanel.value = false
        nextTick(() => {
            resetCommentSelection()
            commentFocused.value = true
        })
        return
    }

    showEmojiPanel.value = false
    commentFocused.value = false
    commentContent.value = ''
    replyTo.value = null
    parentComment.value = null
    resetCommentSelection('')
})
</script>

<style lang="scss" scoped>
@import '../../styles/dynamic.scss';

.dynamic-detail {
    background: transparent;

    &__scroll {
        width: 100%;
    }

    &__content {
        padding: 24rpx var(--wm-space-page-x, 37rpx) calc(44rpx + env(safe-area-inset-bottom));
    }

    &__hero {
        overflow: hidden;
        border-radius: 40rpx;
        background: linear-gradient(
            180deg,
            rgba(255, 255, 255, 0.98) 0%,
            rgba(255, 247, 244, 0.94) 100%
        );
        border: 1rpx solid rgba(239, 230, 225, 0.9);
        box-shadow: 0 18rpx 36rpx rgba(214, 185, 167, 0.16);
    }

    &__hero-image,
    &__hero-video {
        display: block;
        width: 100%;
        height: 468rpx;
    }

    &__gallery {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12rpx;
        margin-top: 16rpx;
    }

    &__gallery-item {
        overflow: hidden;
        border-radius: 24rpx;
        background: $dynamic-surface-solid;
        border: 1rpx solid rgba(239, 230, 225, 0.82);
        box-shadow: 0 10rpx 22rpx rgba(214, 185, 167, 0.12);
    }

    &__gallery-image {
        display: block;
        width: 100%;
        height: 184rpx;
    }

    &__lead-shell {
        margin-top: 22rpx;
        padding: 34rpx 30rpx 32rpx;
        border-radius: 34rpx;
        border: 1rpx solid rgba(239, 230, 225, 0.9);
        background: linear-gradient(
            180deg,
            rgba(255, 255, 255, 0.96) 0%,
            rgba(255, 249, 246, 0.98) 100%
        );
        box-shadow: 0 18rpx 38rpx rgba(214, 185, 167, 0.14);
    }

    &__author-card {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 22rpx;
    }

    &__author-main {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 20rpx;
    }

    &__avatar {
        width: 88rpx;
        height: 88rpx;
        flex-shrink: 0;
        border-radius: 50%;
        background: $dynamic-soft;
        border: 2rpx solid rgba(255, 255, 255, 0.92);
        box-shadow: 0 8rpx 18rpx rgba(214, 185, 167, 0.14);
    }

    &__author-copy {
        flex: 1;
        min-width: 0;
    }

    &__author-row {
        display: flex;
        align-items: center;
        gap: 8rpx;
        flex-wrap: wrap;
    }

    &__author-name {
        font-size: 30rpx;
        line-height: 1.25;
        font-weight: 700;
        color: $dynamic-text;
    }

    &__author-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 36rpx;
        padding: 0 14rpx;
        border-radius: $dynamic-radius-pill;
        border: 1rpx solid transparent;
        font-size: 18rpx;
        font-weight: 600;
        line-height: 1;

        &--staff {
            color: #8b5cf6;
            background: rgba(139, 92, 246, 0.12);
            border-color: rgba(139, 92, 246, 0.1);
        }

        &--official {
            color: #a86d28;
            background: rgba(243, 215, 163, 0.34);
            border-color: rgba(168, 109, 40, 0.08);
        }

        &--top {
            color: $dynamic-accent;
            background: rgba(232, 90, 79, 0.12);
            border-color: rgba(232, 90, 79, 0.1);
        }

        &--hot {
            color: #bf6f2d;
            background: rgba(244, 191, 117, 0.22);
            border-color: rgba(191, 111, 45, 0.08);
        }
    }

    &__author-meta {
        display: block;
        margin-top: 10rpx;
        font-size: 23rpx;
        line-height: 1.6;
        color: $dynamic-text-muted;
        @include dynamic-line-clamp(2);
    }

    &__favorite-btn {
        min-width: 146rpx;
        height: 68rpx;
        padding: 0 22rpx;
        border-radius: $dynamic-radius-pill;
        border: 1rpx solid rgba(232, 90, 79, 0.16);
        background: rgba(232, 90, 79, 0.1);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8rpx;
        color: $dynamic-accent;
        font-size: 24rpx;
        font-weight: 600;
        line-height: 1;
        flex-shrink: 0;

        &.is-active {
            background: $dynamic-accent;
            border-color: transparent;
            box-shadow: $dynamic-shadow-accent;
            color: #ffffff;
        }
    }

    &__tag-row {
        display: flex;
        align-items: center;
        gap: 10rpx;
        flex-wrap: wrap;
        margin-top: 28rpx;
        margin-bottom: 18rpx;
    }

    &__type-tag {
        @include dynamic-pill(rgba(255, 255, 255, 0.94), $dynamic-text-secondary);
        min-height: 52rpx;
        padding: 0 18rpx;

        &--graphic {
            color: $dynamic-text-secondary;
        }

        &--video {
            color: #8b5cf6;
            background: rgba(139, 92, 246, 0.12);
            border-color: rgba(139, 92, 246, 0.1);
        }

        &--case {
            color: #0f9f6e;
            background: rgba(15, 159, 110, 0.12);
            border-color: rgba(15, 159, 110, 0.08);
        }

        &--activity {
            color: #bf6f2d;
            background: rgba(244, 191, 117, 0.2);
            border-color: rgba(191, 111, 45, 0.08);
        }
    }

    &__topic-tag {
        @include dynamic-pill(rgba(255, 241, 238, 0.9), $dynamic-accent);
        min-height: 52rpx;
        padding: 0 18rpx;

        text {
            font-size: 22rpx;
            line-height: 1;
            font-weight: 500;
        }
    }

    &__content-text {
        font-size: 31rpx;
        line-height: 1.82;
        color: $dynamic-text;
        white-space: pre-wrap;
        word-break: break-word;
    }

    &__support-band {
        margin-top: 20rpx;
        padding: 26rpx;
        border-radius: 30rpx;
        border: 1rpx solid rgba(239, 230, 225, 0.78);
        background: rgba(255, 252, 250, 0.96);
        box-shadow: 0 12rpx 28rpx rgba(214, 185, 167, 0.08);
    }

    &__stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12rpx;
    }

    &__stat-pill {
        min-height: 114rpx;
        padding: 20rpx 18rpx;
        border-radius: 24rpx;
        border: 1rpx solid rgba(239, 230, 225, 0.82);
        background: rgba(255, 255, 255, 0.92);
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 8rpx;
        box-shadow: inset 0 1rpx 0 rgba(255, 255, 255, 0.7);

        &.is-active {
            border-color: rgba(232, 90, 79, 0.16);
            background: $dynamic-accent-soft;
        }
    }

    &__stat-label {
        font-size: 21rpx;
        line-height: 1.2;
        color: $dynamic-text-muted;
    }

    &__stat-text {
        font-size: 28rpx;
        line-height: 1.2;
        font-weight: 700;
        color: $dynamic-text;
    }

    &__detail-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12rpx;
        margin-top: 14rpx;
    }

    &__detail-action {
        min-height: 84rpx;
        padding: 0 16rpx;
        border-radius: 22rpx;
        border: 1rpx solid rgba(239, 230, 225, 0.86);
        background: rgba(255, 255, 255, 0.94);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10rpx;
        color: $dynamic-text-secondary;
        font-size: 23rpx;
        font-weight: 600;
        line-height: 1;

        &.is-active {
            color: $dynamic-accent;
            background: $dynamic-accent-soft;
            border-color: rgba(232, 90, 79, 0.16);
        }

        &--share {
            width: auto;
            margin: 0;

            &::after {
                display: none;
            }
        }
    }

    &__comments {
        margin-top: 26rpx;
        padding: 28rpx 26rpx 20rpx;
        border-radius: 30rpx;
        border: 1rpx solid rgba(239, 230, 225, 0.74);
        background: rgba(255, 255, 255, 0.9);
        box-shadow: 0 12rpx 26rpx rgba(214, 185, 167, 0.08);
    }

    &__comments-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20rpx;
        padding-bottom: 18rpx;
    }

    &__comments-title {
        display: block;
        font-size: 30rpx;
        line-height: 1.3;
        font-weight: 700;
        color: $dynamic-text;
    }

    &__comments-sort {
        display: inline-flex;
        align-items: center;
        gap: 12rpx;
        padding: 10rpx 18rpx;
        border-radius: $dynamic-radius-pill;
        background: rgba(255, 247, 244, 0.88);
        border: 1rpx solid rgba(239, 230, 225, 0.76);
        flex-shrink: 0;
    }

    &__sort-item {
        font-size: 22rpx;
        font-weight: 600;
        color: $dynamic-text-muted;

        &.is-active {
            color: $dynamic-accent;
        }
    }

    &__sort-divider {
        color: #d8ccc5;
        font-size: 20rpx;
    }

    &__comment-empty {
        padding: 84rpx 0 36rpx;
        text-align: center;
        font-size: 24rpx;
        color: $dynamic-text-muted;
    }

    &__comment-list {
        padding: 0;
    }

    &__comment-stack {
        display: flex;
        flex-direction: column;
        gap: 10rpx;
    }

    &__comment-item {
        padding: 24rpx 0 18rpx;

        & + & {
            border-top: 1rpx solid rgba(243, 235, 230, 0.92);
        }
    }

    &__comment-main {
        display: flex;
        flex-direction: column;
        gap: 12rpx;
    }

    &__comment-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24rpx;
    }

    &__comment-author {
        flex: 1;
        min-width: 0;
        font-size: 25rpx;
        line-height: 1.35;
        font-weight: 700;
        color: $dynamic-text;
    }

    &__comment-meta-right {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8rpx;
        font-size: 21rpx;
        line-height: 1.3;
        font-weight: 600;
        color: $dynamic-text-muted;
    }

    &__comment-time,
    &__comment-meta-dot {
        color: $dynamic-text-muted;
    }

    &__comment-like-meta {
        color: $dynamic-text-muted;

        &.is-active {
            color: $dynamic-accent;
        }
    }

    &__comment-content {
        font-size: 25rpx;
        line-height: 1.72;
        font-weight: 500;
        color: #5f534b;
        white-space: pre-wrap;
        word-break: break-word;
    }

    &__comment-actions {
        display: inline-flex;
        align-items: center;
        gap: 20rpx;
    }

    &__comment-action {
        font-size: 22rpx;
        line-height: 1.3;
        font-weight: 600;
        color: #6b615b;

        &.is-danger {
            color: #c96e64;
        }
    }

    &__reply-list {
        margin-top: 14rpx;
        padding-left: 20rpx;
        border-left: 2rpx solid rgba(243, 235, 230, 0.9);
    }

    &__reply-item {
        padding-top: 18rpx;

        &:first-child {
            padding-top: 0;
        }
    }

    &__reply-toggle {
        margin-top: 16rpx;
        font-size: 22rpx;
        line-height: 1.3;
        font-weight: 600;
        color: $dynamic-text-muted;
    }

    &__comment-more {
        padding: 22rpx 0 6rpx;
        text-align: center;
        font-size: 22rpx;
        color: $dynamic-text-muted;
    }

    &__popup {
        height: 100%;
        background: linear-gradient(
            180deg,
            rgba(255, 251, 249, 0.98) 0%,
            rgba(252, 248, 246, 1) 100%
        );
        display: flex;
        flex-direction: column;
    }

    &__popup-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 28rpx 28rpx 20rpx;
        border-bottom: 1rpx solid rgba(243, 235, 230, 0.92);
    }

    &__popup-title {
        font-size: 32rpx;
        font-weight: 700;
        color: $dynamic-text;
    }

    &__popup-close {
        width: 56rpx;
        height: 56rpx;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.94);
        border: 1rpx solid rgba(232, 222, 216, 0.78);
    }

    &__popup-body {
        flex: 1;
        min-height: 0;
        padding: 24rpx 24rpx 16rpx;
        display: flex;
        flex-direction: column;
    }

    &__textarea-panel {
        flex: 1;
        min-height: 0;
        border-radius: 26rpx;
        border: 1rpx solid rgba(232, 222, 216, 0.82);
        background: rgba(255, 255, 255, 0.94);
        box-shadow: inset 0 1rpx 0 rgba(255, 255, 255, 0.72),
            0 10rpx 24rpx rgba(214, 185, 167, 0.06);
        overflow: hidden;
    }

    &__textarea {
        display: block;
        width: 100%;
        height: 100%;
        min-height: 320rpx;
        padding: 24rpx 24rpx 20rpx;
        border: none;
        background: transparent;
        box-sizing: border-box;
        font-size: 28rpx;
        line-height: 1.7;
        color: $dynamic-text;
    }

    &__textarea-placeholder {
        color: $dynamic-text-placeholder;
    }

    &__popup-footer {
        position: relative;
        z-index: 2;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        gap: 20rpx;
        padding: 18rpx 24rpx 24rpx;
        border-top: 1rpx solid rgba(243, 235, 230, 0.92);

        &.is-emoji-open {
            z-index: 3;
        }
    }

    &__popup-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20rpx;
    }

    &__char-count {
        flex: 1;
        min-width: 0;
        font-size: 22rpx;
        color: $dynamic-text-muted;
    }

    &__composer-actions {
        display: inline-flex;
        align-items: center;
        gap: 16rpx;
        flex-shrink: 0;
    }

    &__emoji-btn {
        min-width: 116rpx;
        height: 72rpx;
        padding: 0 28rpx;
        border-radius: $dynamic-radius-pill;
        border: 1rpx solid rgba(232, 222, 216, 0.82);
        background: rgba(255, 255, 255, 0.94);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: $dynamic-text-secondary;
        font-size: 24rpx;
        font-weight: 600;
        line-height: 1;

        &::after {
            display: none;
        }

        &.is-active {
            color: $dynamic-accent;
            background: $dynamic-accent-soft;
            border-color: rgba(232, 90, 79, 0.14);
        }
    }

    &__submit-btn {
        min-width: 228rpx;
        height: 84rpx;
        padding: 0 36rpx;
        border-radius: $dynamic-radius-pill;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: $dynamic-accent;
        box-shadow: $dynamic-shadow-accent;
        color: #ffffff;
        font-size: 28rpx;
        font-weight: 600;

        &::after {
            display: none;
        }

        &.is-disabled {
            opacity: 0.42;
            box-shadow: none;
        }
    }

    &__emoji-panel {
        position: absolute;
        left: 24rpx;
        right: 24rpx;
        bottom: calc(100% + 16rpx);
        height: 296rpx;
        padding: 20rpx 8rpx 6rpx;
        border-radius: 28rpx;
        border: 1rpx solid rgba(232, 222, 216, 0.8);
        background: rgba(255, 255, 255, 0.94);
        box-shadow: 0 12rpx 28rpx rgba(214, 185, 167, 0.12);
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 14rpx 8rpx;
        box-sizing: border-box;
        overflow-y: auto;
        z-index: 4;
    }

    &__emoji-item {
        height: 72rpx;
        border-radius: 22rpx;
        border: 1rpx solid rgba(232, 222, 216, 0.72);
        background: rgba(255, 255, 255, 0.96);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8rpx 18rpx rgba(214, 185, 167, 0.08);
    }

    &__emoji-char {
        font-size: 38rpx;
        line-height: 1;
    }

    &__loading-view {
        min-height: 100vh;
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__loading-text {
        font-size: 28rpx;
        color: $dynamic-text-muted;
    }
}

.dynamic-detail :deep(.tn-popup) {
    pointer-events: none;
}

.dynamic-detail :deep(.tn-popup__content) {
    pointer-events: auto;
}
</style>
