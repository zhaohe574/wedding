<template>
    <!-- 02 动态（Tab 2）｜只读浏览：无发布入口，无编辑态 -->
    <page-shell
        title="动态"
        nav-variant="solid"
        :show-back="false"
        title-align="left"
        title-variant="display"
        :tabbar="true"
        current="/pages/feed/feed"
        bg="page"
    >
        <!-- 分类栏：按动态类型（图文 / 视频 / 活动）分，不按标签分；排序收在右侧同一行 -->
        <view v-if="categories.length" class="catbar">
            <view class="catbar__tabs">
                <view
                    v-for="c in categories"
                    :key="c.key"
                    class="cat"
                    :class="{ 'is-active': activeCat === c.key }"
                    @tap="selectCat(c.key)"
                >
                    <text class="cat__text">{{ c.label }}</text>
                    <view v-if="activeCat === c.key" class="cat__line" aria-hidden="true" />
                </view>
            </view>

            <view class="catbar__sort">
                <view
                    class="sorter"
                    aria-role="button"
                    :aria-label="`排序：${sortLabel}`"
                    @tap="sorterOpen = !sorterOpen"
                >
                    <text class="sorter__text">{{ sortLabel }}</text>
                    <tn-icon name="down-arrow" :size="22" />
                </view>

                <!-- 排序面板：只提供后端真实支持的两种排序（order_by=create_time / hot） -->
                <view v-if="sorterOpen" class="sortpanel">
                    <view
                        v-for="o in SORTS"
                        :key="o.key"
                        class="sortpanel__item"
                        :class="{ 'is-on': sortKey === o.key }"
                        @tap="selectSort(o.key)"
                    >
                        <text class="sortpanel__text">{{ o.label }}</text>
                        <tn-icon v-if="sortKey === o.key" name="check" :size="22" />
                    </view>
                </view>
            </view>
        </view>

        <!-- 装修/跳转预设筛选条：仅携带标签或关键词时展示，支持一键清除 -->
        <view v-if="activeTag || activeKeyword" class="feed-filters">
            <view class="feed-filters__tags">
                <text v-if="activeTag" class="feed-filters__pill">#{{ activeTag }}</text>
                <text v-if="activeKeyword" class="feed-filters__pill">
                    关键词：{{ activeKeyword }}
                </text>
            </view>
            <text class="feed-filters__clear" @tap="clearActiveFilters">清空筛选</text>
        </view>

        <!-- 骨架：3 组卡片骨架 -->
        <view v-if="phase === 'loading'" class="pads">
            <view v-for="i in 3" :key="i" class="sk">
                <view class="sk__head">
                    <view class="sk__avatar" />
                    <view class="sk__who">
                        <view class="sk__line sk__line--name" />
                        <view class="sk__line sk__line--time" />
                    </view>
                </view>
                <view class="sk__line sk__line--text" />
                <view class="sk__line sk__line--text2" />
                <view class="sk__media" />
            </view>
        </view>

        <!-- 错误（可重试） -->
        <base-empty
            v-else-if="phase === 'error'"
            status="error"
            :error-kind="errorKind"
            error-text="网络不太稳"
            desc="动态没有加载出来，你之前的点赞都还在"
            fixed
            @retry="reload"
        />

        <!-- 空 -->
        <base-empty
            v-else-if="phase === 'empty'"
            status="empty"
            empty-text="还没有动态"
            desc="婚礼现场与团队日常会在这里更新"
            action-text="去看看可约的主持人"
            :show-action="true"
            fixed
            @action="goQuery"
        />

        <!-- 列表 -->
        <view v-else-if="phase === 'ready'">
            <view v-for="post in list" :key="post.id" class="post">
                <!-- 置顶标识：4rpx 竖条只占左侧留白（内容仍是 42rpx 对齐），与暖米底协调但一眼可辨。
                     用 v-if 而非常驻节点：非置顶卡片连一个空 view 都不多占 -->
                <view v-if="isTop(post)" class="post__pin" aria-hidden="true" />
                <view class="post__head">
                    <view class="avatar">
                        <image
                            v-if="post.user_avatar"
                            class="avatar__img"
                            :src="post.user_avatar"
                            mode="aspectFill"
                            lazy-load
                            @error="onAvatarError(post)"
                        />
                        <text v-else class="avatar__plain">{{
                            initialOf(post.user_nickname)
                        }}</text>
                    </view>
                    <view class="post__who">
                        <view class="post__row">
                            <text class="post__name">{{ post.user_nickname || '匿名用户' }}</text>
                            <!-- 徽章顺序固定：官方 → 置顶 → 热门。
                                 整组 flex:none，昵称过长时只省略昵称，徽章不会被挤出屏幕或换行 -->
                            <view class="post__tags">
                                <text v-if="post.user_type === 3" class="badge badge--gold"
                                    >官方</text
                                >
                                <text v-if="isTop(post)" class="badge badge--top">置顶</text>
                                <text v-if="isHot(post)" class="badge badge--hot">热门</text>
                            </view>
                        </view>
                        <text class="post__time">{{ postTimeText(post) }}</text>
                    </view>
                    <view
                        class="post__more"
                        hover-class="is-pressed"
                        aria-role="button"
                        aria-label="更多操作"
                        @tap.stop="onMore(post)"
                    >
                        <tn-icon name="more-horizontal" :size="32" />
                    </view>
                </view>

                <!-- 文案：点整块进详情 -->
                <text class="post__text" @tap="goDetail(post)">{{ post.content }}</text>

                <!-- 视频：只出静帧，点播放圆进详情全屏播放（列表内联播放会同时出声、耗流量） -->
                <view v-if="post.media_type === 'video'" class="post__media" @tap="goDetail(post)">
                    <image
                        class="post__media-img"
                        :src="post.media_cover"
                        mode="aspectFill"
                        lazy-load
                    />
                    <view class="post__play">
                        <view class="post__play-btn">
                            <tn-icon name="play-fill" :size="30" />
                        </view>
                    </view>
                </view>

                <!-- 图片：按张数自适应栅格（1 大图 / 2 双列 / 3 三列 / 4 2×2 / ≥5 九宫格）。
                     点图预览（不误触进详情），点失败格子只重试这一张 -->
                <view
                    v-else-if="post.media_images && post.media_images.length"
                    class="gallery"
                    :class="'gallery--g' + post.media_layout"
                >
                    <view
                        v-for="(img, i) in post.media_images"
                        :key="post.id + '-img' + i + '-' + (img.reloadKey || 0)"
                        class="gallery__cell"
                        @tap.stop="onGalleryTap(post, i)"
                    >
                        <image
                            v-if="!img.broken"
                            class="gallery__img"
                            :src="img.url"
                            mode="aspectFill"
                            lazy-load
                            @error="onImageError(img)"
                        />
                        <!-- 单张失败才补一行重试文案：多图时每格都写字会糊成一片 -->
                        <view v-else class="gallery__fail">
                            <tn-icon name="image" :size="48" />
                            <text v-if="post.media_count === 1" class="gallery__fail-text">
                                图片没加载出来 · 点此重试
                            </text>
                        </view>
                        <!-- 超过 9 张：只渲染前 9 张，第 9 格压深色叠层报剩余张数 -->
                        <view v-if="post.media_more && i === 8" class="gallery__more">
                            <text class="gallery__more-text">+{{ post.media_more }}</text>
                        </view>
                    </view>
                </view>

                <!-- 内嵌活动卡：仅当后端 activity_enabled=1 才出现 -->
                <view
                    v-if="post.activity && post.activity.activity_enabled === 1"
                    class="event"
                    @tap="goDetail(post)"
                >
                    <view class="event__date">
                        <text class="event__date-d">{{ eventDayText(post.activity) }}</text>
                        <text class="event__date-w">{{ eventWeekText(post.activity) }}</text>
                    </view>
                    <view class="event__main">
                        <text class="event__title">{{ post.title || '活动报名' }}</text>
                        <text v-if="post.location" class="event__place">{{ post.location }}</text>
                        <view class="event__going">
                            <text class="event__going-text">
                                {{ post.activity.activity_registered_count || 0 }} 人已报名
                            </text>
                            <text v-if="post.activity.activity_price_label" class="event__price">
                                {{ post.activity.activity_price_label }}
                            </text>
                        </view>
                    </view>
                    <tn-icon name="right" :size="26" />
                </view>

                <view class="post__stats">
                    <view
                        hover-class="is-pressed"
                        class="pstat"
                        :class="{ 'is-liked': isLiked(post) }"
                        aria-role="button"
                        :aria-label="isLiked(post) ? '取消点赞' : '点赞'"
                        @tap="onLike(post)"
                    >
                        <tn-icon :name="isLiked(post) ? 'like-fill' : 'like'" :size="29" />
                        <text class="pstat__text">{{ post.like_count || 0 }}</text>
                    </view>
                    <view hover-class="is-pressed" class="pstat" @tap="goDetail(post)">
                        <tn-icon name="comment" :size="29" />
                        <text class="pstat__text">{{ post.comment_count || 0 }}</text>
                    </view>
                    <!-- 分享走小程序原生面板（button open-type=share），不自绘 -->
                    <button class="pstat pstat--share" open-type="share" :data-id="post.id">
                        <tn-icon name="share" :size="29" />
                        <text v-if="post.share_count" class="pstat__text">{{
                            post.share_count
                        }}</text>
                    </button>
                </view>
            </view>

            <view v-if="hasMore" class="feed-more">
                <load-more :status="loadMoreStatus" @retry="loadMore" />
            </view>
            <view v-else class="feed-end">
                <text class="feed-end__text">✦ 已呈现全部动态 ✦</text>
            </view>
        </view>
    </page-shell>
    <app-toast />
</template>

<script setup lang="ts">
/**
 * 02 动态。
 *
 * 数据：`dynamic/lists`（GET，免登录）。
 *  · 后端真实参数：page / page_size / tag / keyword / dynamic_type / order_by(create_time|hot) / order_dir
 *  · 分类条 = 「全部」+ 图文 / 视频 / 活动（按 dynamic_type 过滤，不按标签）；
 *    标签只是内容的一部分，不再作为一级分类。
 *  · 排序只给后端支持的两种（最新 / 最热），不做「距离最近」这类无数据支撑的假选项。
 *
 * 交互：
 *  · 点赞乐观更新，失败回滚 + 轻提示；未登录先跳登录，登录回来补执行原动作。
 *  · 视频只出静帧，点进详情播放（列表内联播放会同时出声、耗流量）。
 *  · 只读：没有发布入口、没有编辑/删除入口。
 */
import { computed, ref } from 'vue'
import { onLoad, onPullDownRefresh, onReachBottom, onShow, onUnload } from '@dcloudio/uni-app'
import { getCaseList, toggleCaseLike } from '@/api/content'
import { normalizePage, toArray } from '@/request'
import { navigate, safeDecodeParam } from '@/utils/link'
import { toast } from '@/composables/useToast'
import { useUserStore } from '@/stores/user'
import { useInteractionStore } from '@/stores/interaction'
import { useTabQueryStore } from '@/stores/tabQuery'
import { useBadgeStore } from '@/stores/badge'
import { hide, hiddenIdSet } from '@/utils/hiddenFeed'
import { useBrand } from '@/composables/useBrand'

/** 画廊单格：url + 本格独立的失败/重试状态（重试只重建这一张，不影响其它图） */
interface MediaImage {
    url: string
    /** 加载失败标记：true 时该格显示占位图标，点一下清标记并递增 reloadKey 重建 image */
    broken?: boolean
    /** 重试计数：参与 :key，递增即强制微信重建 image 节点（重发请求） */
    reloadKey?: number
}

interface FeedRow {
    id: number
    user_type?: number
    /** 列表接口返回的是嵌套 user，详情接口才是这两个扁平字段，见 normalizeRow */
    user?: { id?: number; nickname?: string; name?: string; avatar?: string } | null
    user_nickname?: string
    user_avatar?: string
    title?: string
    content?: string
    location?: string
    create_time?: string
    tags?: string
    like_count?: number
    comment_count?: number
    share_count?: number
    is_liked?: boolean | number
    /** 后台置顶 / 热门标记：0/1，也兼容 boolean */
    is_top?: number | boolean
    is_hot?: number | boolean
    dynamic_type?: number
    /** 后端 getter 已返回补全域名的数组，理论上全是字符串；对象形态做兼容 */
    images?: Array<string | Record<string, any>>
    video_url?: string
    video_cover?: string
    activity?: Record<string, any> | null
    /** 前端派生（不落库） */
    media_cover?: string
    media_type?: 'image' | 'video'
    media_count?: number
    /** 全部图片 URL（预览用完整列表，不受 9 张上限影响） */
    image_urls?: string[]
    /** 画廊实际渲染的图片（最多 9 张） */
    media_images?: MediaImage[]
    /** 超过 9 张时的剩余数，0 表示不叠 +N */
    media_more?: number
    /** 栅格档位：1 / 2 / 3 / 4 / 9，对应 gallery--g1|g2|g3|g4|g9 */
    media_layout?: number
    avatar_broken?: boolean
}

const SORTS = [
    { key: 'latest', label: '最新', orderBy: 'create_time' },
    { key: 'hot', label: '最热', orderBy: 'hot' }
] as const

/** 一级分类按 dynamic_type：1 图文 / 2 视频 / 4 活动；0 = 全部 */
const categories = [
    { key: 0, label: '全部' },
    { key: 1, label: '图文' },
    { key: 2, label: '视频' },
    { key: 4, label: '活动' }
] as const

const userStore = useUserStore()
const interactionStore = useInteractionStore()
const tabQuery = useTabQueryStore()
const badgeStore = useBadgeStore()
/** 品牌印章字（后台名称首字），用于作者名缺失时的头像兜底 */
const { brand } = useBrand()

const list = ref<FeedRow[]>([])
const activeCat = ref(0)
const sortKey = ref<'latest' | 'hot'>('latest')
const activeTag = ref('')
const activeKeyword = ref('')
const sorterOpen = ref(false)

const phase = ref<'loading' | 'ready' | 'empty' | 'error'>('loading')
const errorKind = ref<'network' | 'unknown'>('network')
const page = ref(1)
const pageSize = 10
const hasMore = ref(true)
const loadingMore = ref(false)
const loadMoreFailed = ref(false)

/** 未登录时点了赞：记下 id，登录回来补执行（02 §P） */
const pendingLikeId = ref(0)

const sortLabel = computed(() => SORTS.find((s) => s.key === sortKey.value)?.label || '最新')

const loadMoreStatus = computed<'idle' | 'loading' | 'nomore' | 'error'>(() => {
    if (loadMoreFailed.value) return 'error'
    if (loadingMore.value) return 'loading'
    return 'idle'
})

// ---------------------------------------------------------------- 派生字段

/**
 * 头像兜底首字。
 * 作者名缺失时用**品牌名称首字**（后台配置派生），不再写死任何汉字。
 */
function initialOf(name?: string) {
    const s = (name || '').trim()
    return s ? [...s][0] : brand.value.seal
}

function toTime(value?: string | number) {
    if (!value) return 0
    if (typeof value === 'number') return value * (value > 1e12 ? 1 : 1000)
    const s = String(value)
    // 后端可能是秒级时间戳字符串，也可能是 'YYYY-MM-DD HH:mm:ss'
    if (/^\d{10}$/.test(s)) return Number(s) * 1000
    if (/^\d{13}$/.test(s)) return Number(s)
    const t = new Date(s.replace(/-/g, '/')).getTime()
    return Number.isFinite(t) ? t : 0
}

function postTimeText(post: FeedRow) {
    const t = toTime(post.create_time)
    const parts: string[] = []
    if (t) {
        const diff = Date.now() - t
        const min = Math.floor(diff / 60000)
        if (min < 1) parts.push('刚刚')
        else if (min < 60) parts.push(`${min} 分钟前`)
        else if (min < 60 * 24) parts.push(`${Math.floor(min / 60)} 小时前`)
        else if (min < 60 * 24 * 7) parts.push(`${Math.floor(min / (60 * 24))} 天前`)
        else {
            const d = new Date(t)
            parts.push(
                `${String(d.getMonth() + 1).padStart(2, '0')}.${String(d.getDate()).padStart(
                    2,
                    '0'
                )}`
            )
        }
    }
    if (post.location) parts.push(post.location)
    return parts.join(' · ')
}

function isLiked(post: FeedRow) {
    return post.is_liked === true || Number(post.is_liked) === 1
}

/**
 * 置顶 / 热门：后台口径是 0/1，也见过 true/false，统一按「true 或 Number(...) === 1」判定。
 * 不要用 if (post.is_top)：字符串 '0' 是真值，会把所有动态都判成置顶。
 */
function isTop(post: FeedRow) {
    return post.is_top === true || Number(post.is_top) === 1
}

function isHot(post: FeedRow) {
    return post.is_hot === true || Number(post.is_hot) === 1
}

function eventDayText(activity: Record<string, any>) {
    const t = toTime(activity.activity_start_time)
    if (!t) return '待定'
    const d = new Date(t)
    return `${String(d.getMonth() + 1).padStart(2, '0')}.${String(d.getDate()).padStart(2, '0')}`
}

function eventWeekText(activity: Record<string, any>) {
    const t = toTime(activity.activity_start_time)
    if (!t) return ''
    const d = new Date(t)
    const week = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][d.getDay()]
    return `${week} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(
        2,
        '0'
    )}`
}

/** 画廊最多渲染 9 格（三列九宫格），超出部分折成 +N */
const GALLERY_MAX = 9

/**
 * images 归一化：后端 getter 正常返回字符串数组，这里再兜一层对象形态（{url|image|path|src}）。
 * 必须过滤空值 —— 空串进 <image src> 在微信端会直接触发 error，被误判成「加载失败」。
 */
function collectImageUrls(value: unknown): string[] {
    return toArray<string | Record<string, any>>(value)
        .map((item) => {
            if (!item) return ''
            if (typeof item === 'string') return item.trim()
            if (typeof item === 'object') {
                return String(item.url || item.image || item.path || item.src || '').trim()
            }
            return ''
        })
        .filter(Boolean)
}

/** 图片张数 → 栅格档位。2 与 4 目前都是双列，档位仍分开，方便以后单独调 2×2 */
function galleryLayout(count: number): number {
    if (count <= 1) return 1
    if (count === 2) return 2
    if (count === 3) return 3
    if (count === 4) return 4
    return 9
}

/** 把后端字段整理成模板直接可用的形状（列表里不做 v-if 计算，减少渲染开销） */
function normalizeRow(raw: any): FeedRow {
    const row: FeedRow = { ...(raw || {}) }

    // 作者字段口径不一致（真机反馈：列表页头像/名称看起来像占位数据）：
    //   列表 /dynamic/lists → Dynamic::getList 把作者放在嵌套 user:{nickname,avatar}
    //   详情 /dynamic/detail → DynamicLogic::getDynamicDetail 是扁平 user_nickname/user_avatar
    // 模板统一读扁平字段，所以这里把嵌套对象摊平。不修的话列表会 fallback 成
    // 品牌名 + 文字首字头像，与详情页显示「官方 + 官方 logo」对不上。
    // 只在扁平字段为空时补，避免覆盖后端已经直出的值。
    const author = row.user
    if (author && typeof author === 'object') {
        if (!row.user_nickname) row.user_nickname = author.nickname || author.name || ''
        if (!row.user_avatar) row.user_avatar = author.avatar || ''
    }

    const images = collectImageUrls(row.images)
    row.image_urls = images
    if (row.dynamic_type === 2 && (row.video_cover || row.video_url)) {
        row.media_type = 'video'
        row.media_cover = row.video_cover || ''
    } else if (images.length) {
        row.media_type = 'image'
        row.media_cover = images[0]
        row.media_count = images.length
        // 只渲染前 9 张；第 9 格用 +N 报剩余张数，总数保留在 media_count 里
        row.media_images = images.slice(0, GALLERY_MAX).map((url) => ({
            url,
            broken: false,
            reloadKey: 0
        }))
        row.media_more = Math.max(0, images.length - GALLERY_MAX)
        row.media_layout = galleryLayout(images.length)
    }
    if (!row.media_cover) {
        row.media_type = undefined
        row.media_cover = ''
    }
    return row
}

// ---------------------------------------------------------------- 数据

async function fetchList(reset = false) {
    if (reset) {
        page.value = 1
        hasMore.value = true
        loadMoreFailed.value = false
        phase.value = 'loading'
    }
    loadingMore.value = true
    try {
        const res = await getCaseList({
            page: page.value,
            page_size: pageSize,
            ...(activeCat.value > 0 ? { dynamic_type: activeCat.value } : {}),
            ...(activeTag.value ? { tag: activeTag.value } : {}),
            ...(activeKeyword.value ? { keyword: activeKeyword.value } : {}),
            order_by: sortKey.value === 'hot' ? 'hot' : 'create_time',
            order_dir: 'desc'
        })
        const paged = normalizePage<FeedRow>(res, page.value, pageSize)
        const hidden = hiddenIdSet()
        const rows = paged.list.filter((raw: any) => !hidden.has(Number(raw?.id))).map(normalizeRow)
        // 点赞态以本次会话快照为准：详情页点过赞/取消赞后，回到列表不再被列表接口的旧值覆盖
        for (const row of rows) {
            const cached = interactionStore.getDynamicLike(row.id)
            if (cached) {
                row.is_liked = cached.liked
                row.like_count = cached.count
            } else {
                interactionStore.seedDynamicLike(row.id, isLiked(row), Number(row.like_count || 0))
            }
        }
        list.value = reset ? rows : [...list.value, ...rows]
        hasMore.value = paged.hasMore
        if (hasMore.value) page.value += 1
        phase.value = list.value.length ? 'ready' : 'empty'
    } catch (e: any) {
        if (reset || !list.value.length) {
            phase.value = 'error'
            errorKind.value =
                e?.errorCode === 'NETWORK_ERROR' || !e?.statusCode ? 'network' : 'unknown'
        } else {
            loadMoreFailed.value = true
        }
    } finally {
        loadingMore.value = false
        uni.stopPullDownRefresh()
    }
}

function reload() {
    fetchList(true)
}

async function loadMore() {
    if (!hasMore.value || loadingMore.value) return
    await fetchList(false)
}

// ---------------------------------------------------------------- 交互

function selectCat(key: number) {
    if (activeCat.value === key) return
    activeCat.value = key
    sorterOpen.value = false
    list.value = []
    fetchList(true)
}

function selectSort(key: 'latest' | 'hot') {
    sorterOpen.value = false
    if (sortKey.value === key) return
    sortKey.value = key
    list.value = []
    fetchList(true)
}

function clearActiveFilters() {
    activeTag.value = ''
    activeKeyword.value = ''
    list.value = []
    fetchList(true)
}

/** 从 tabQuery 消费一次性跳转筛选条件（dynamic_type / order_by / tag / keyword） */
function applyPending(): boolean {
    const pending = tabQuery.consumeQuery('feed')
    if (!pending) return false
    let changed = false

    if (pending.dynamic_type !== undefined) {
        const rawType = Number(pending.dynamic_type || 0)
        const nextCat = categories.some((c) => c.key === rawType) ? rawType : 0
        if (nextCat !== activeCat.value) {
            activeCat.value = nextCat
            changed = true
        }
    }

    if (pending.order_by !== undefined) {
        const rawOrder = String(pending.order_by || '').trim()
        const nextSort = rawOrder === 'hot' ? 'hot' : 'latest'
        if (nextSort !== sortKey.value) {
            sortKey.value = nextSort
            changed = true
        }
    }

    if (pending.tag !== undefined) {
        const nextTag = safeDecodeParam(pending.tag)
        if (nextTag !== activeTag.value) {
            activeTag.value = nextTag
            changed = true
        }
    }

    if (pending.keyword !== undefined) {
        const nextKeyword = safeDecodeParam(pending.keyword)
        if (nextKeyword !== activeKeyword.value) {
            activeKeyword.value = nextKeyword
            changed = true
        }
    }

    return changed
}

async function onLike(post: FeedRow) {
    if (!userStore.isLogin) {
        pendingLikeId.value = post.id
        navigate({ path: '/pages/login/login' })
        return
    }
    const liked = isLiked(post)
    // 乐观更新：先变色变数，失败回滚；同时写会话快照，详情页读同一份状态
    post.is_liked = !liked
    post.like_count = Math.max(0, Number(post.like_count || 0) + (liked ? -1 : 1))
    interactionStore.setDynamicLike(post.id, post.is_liked, post.like_count)
    try {
        const res = await toggleCaseLike({ id: post.id })
        if (res && typeof res.is_like === 'number') {
            post.is_liked = res.is_like === 1
            interactionStore.setDynamicLike(post.id, post.is_liked, post.like_count)
        }
    } catch {
        post.is_liked = liked
        post.like_count = Math.max(0, Number(post.like_count || 0) + (liked ? 1 : -1))
        interactionStore.setDynamicLike(post.id, liked, post.like_count)
        toast.error('点赞没成功，再点一次')
    }
}

/** 「不感兴趣」只做本地隐藏：读写统一走 utils/hiddenFeed，设置页用同一份数据做恢复 */

function onMore(post: FeedRow) {
    uni.showActionSheet({
        // 只列真实可用的操作：复制对内容页价值低，举报/内容偏好后端没有接口，不摆出来骗点击
        itemList: ['不感兴趣'],
        success: (res) => {
            if (res.tapIndex === 0) {
                const id = Number(post.id)
                hide(post)
                // 隐藏后本地签名变了，同步基线，避免 onShow 又触发一次无意义的刷新
                lastHiddenSig = hiddenSignature()
                list.value = list.value.filter((p) => Number(p.id) !== id)
                if (!list.value.length) phase.value = 'empty'
                toast.success('已减少此类内容推荐')
            }
        },
        fail: () => undefined // 用户点取消，不提示
    })
}

function onAvatarError(post: FeedRow) {
    post.user_avatar = ''
}

function goDetail(post: FeedRow) {
    navigate({
        path: '/packages/pkg-catalog/pages/feed-detail/feed-detail',
        query: { id: post.id }
    })
}

/** 单图失败：只标这一格，不让整条动态消失，也不 toast 刷屏 */
function onImageError(img: MediaImage) {
    img.broken = true
}

/**
 * 点画廊：
 *  · 失败的格子 → 只重试这一张（清标记 + reloadKey 递增换 :key 重建 image），不弹 toast
 *  · 正常的格子 → 原生大图预览，current 定位到点的那张；不误触进详情
 * urls 用完整的 image_urls（不止 9 张），预览里可以左右滑到第 10 张以后。
 */
function onGalleryTap(post: FeedRow, index: number) {
    const img = (post.media_images || [])[index]
    if (!img) return
    if (img.broken) {
        img.broken = false
        img.reloadKey = (img.reloadKey || 0) + 1
        return
    }
    const urls = post.image_urls || []
    if (!urls.length) return
    uni.previewImage({ urls, current: img.url })
}

function goQuery() {
    navigate({ path: '/pages/query/query' })
}

// ---------------------------------------------------------------- 生命周期

/**
 * 设置页恢复/全部恢复后会写缓存并 emit 'feed:hidden-changed'。
 * 这里用「隐藏 id 集合的签名」做幂等：事件监听与 onShow 都调同一个 syncHiddenChange()，
 * 谁先到谁刷新并更新基线，后者看到签名没变就跳过 —— 不会双重请求。
 * 选 onShow 兜底的原因：feed 是 tab 页，若用户没进过动态页（无监听）就直接从设置页返回，
 * 事件没人接；onShow 对比缓存是唯一必然发生的路径。事件监听则让「动态页已在后台」
 * 时提前把列表刷好，回到 tab 无需等待。
 */
function hiddenSignature(): string {
    return [...hiddenIdSet()].sort((a, b) => a - b).join(',')
}

let lastHiddenSig = hiddenSignature()

function syncHiddenChange() {
    const sig = hiddenSignature()
    if (sig === lastHiddenSig) return
    lastHiddenSig = sig
    fetchList(true)
}

const onHiddenChanged = () => syncHiddenChange()

onLoad(() => {
    lastHiddenSig = hiddenSignature()
    uni.$on('feed:hidden-changed', onHiddenChanged)
    applyPending()
    fetchList(true)
})

onShow(() => {
    badgeStore.refresh()
    if (applyPending()) {
        list.value = []
        fetchList(true)
    }
    syncHiddenChange()
    // 从详情页返回：详情里点的赞/取消赞用会话快照回填，避免列表还显示旧状态
    for (const p of list.value) {
        const cached = interactionStore.getDynamicLike(p.id)
        if (cached) {
            p.is_liked = cached.liked
            p.like_count = cached.count
        }
    }
    // 登录回来后补执行原点赞动作
    if (pendingLikeId.value && userStore.isLogin) {
        const target = list.value.find((p) => p.id === pendingLikeId.value)
        pendingLikeId.value = 0
        if (target) onLike(target)
    }
})

onUnload(() => {
    // 传同一函数引用，避免把其它监听一起清掉
    uni.$off('feed:hidden-changed', onHiddenChanged)
})

onPullDownRefresh(() => {
    fetchList(true)
})

onReachBottom(() => loadMore())
</script>

<style lang="scss" scoped>
/* ---------- 分类条（类型 + 排序同一行） ---------- */
.catbar {
    position: relative;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20rpx;
    padding: 4rpx 42rpx 0;

    &__tabs {
        display: flex;
        flex: 1;
        min-width: 0;
        gap: 42rpx;
    }

    &__sort {
        position: relative;
        flex: none;
    }
}

.cat {
    position: relative;
    padding: 12rpx 0 16rpx;

    &__text {
        font-size: var(--fs-26);
        color: var(--c-ink-3);
        letter-spacing: 0.04em;
    }

    &__line {
        position: absolute;
        left: 50%;
        bottom: 0;
        width: 31rpx;
        height: 4rpx;
        border-radius: var(--r-2);
        background: var(--c-ink);
        transform: translateX(-50%);
    }

    &.is-active .cat__text {
        color: var(--c-ink);
        font-weight: var(--fw-semibold);
    }
}

.feed-filters {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    padding: 12rpx 42rpx 4rpx;

    &__tags {
        display: flex;
        flex-wrap: wrap;
        gap: 12rpx;
        min-width: 0;
        flex: 1;
    }

    &__pill {
        padding: 4rpx 16rpx;
        border-radius: var(--r-pill);
        background: var(--c-surface);
        border: 1rpx solid var(--c-line);
        font-size: var(--fs-21);
        color: var(--c-ink-2);
    }

    &__clear {
        flex: none;
        font-size: var(--fs-21);
        color: var(--c-ink-3);
        padding: 4rpx 8rpx;
    }
}

.sorter {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    /* 与分类文字基线对齐；排序不再单独占一行 */
    padding: 14rpx 0 8rpx 8rpx;
    /* 箭头与排序文字同色；.sorter__text 自己声明了 --c-ink-3 */
    color: var(--c-ink-3);

    &__text {
        font-size: var(--fs-21);
        color: var(--c-ink-3);
    }
}

.sortpanel {
    position: absolute;
    right: 0;
    top: 66rpx;
    z-index: 30;
    min-width: 168rpx;
    padding: 8rpx 0;
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);
    box-shadow: var(--shadow-float);

    &__item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12rpx;
        padding: 18rpx 24rpx;
        /* 选中勾与文字同色；.sortpanel__text 及 .is-on 变体都自己声明了颜色 */
        color: var(--c-brand-text);
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__item.is-on &__text {
        color: var(--c-ink);
        font-weight: var(--fw-semibold);
    }
}

/* ---------- 骨架 ---------- */
.pads {
    padding: 0 42rpx;
}

.sk {
    padding: 35rpx 0 24rpx;
    border-bottom: 1rpx solid var(--c-line);

    &__head {
        display: flex;
        align-items: center;
        gap: 19rpx;
        /* 「更多」图标；同级 .post__name / .post__time 都自带颜色 */
        color: var(--c-ink-3);
    }

    &__avatar {
        width: 65rpx;
        height: 65rpx;
        border-radius: var(--r-pill);
        background: var(--c-neutral-bg);
        flex-shrink: 0;
    }

    &__who {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 10rpx;
    }

    &__line {
        height: 20rpx;
        border-radius: var(--r-xs);
        background: var(--c-neutral-bg);

        &--name {
            width: 34%;
            height: 24rpx;
        }

        &--time {
            width: 46%;
        }

        &--text {
            margin-top: 21rpx;
            width: 100%;
        }

        &--text2 {
            margin-top: 12rpx;
            width: 72%;
        }
    }

    &__media {
        margin-top: 21rpx;
        width: 100%;
        height: 500rpx;
        border-radius: var(--r-27);
        background: var(--c-neutral-bg);
    }
}

/* ---------- 卡片 ---------- */
.post {
    position: relative;
    padding: 35rpx 42rpx 0;

    /*
     * 置顶标识：卡片左缘 4rpx 竖条，只用留白区（内容仍是 42rpx 对齐，不改卡片底色、不加阴影）。
     * top 与 .post 的 padding-top 对齐；bottom 0 落在 .post__stats 的 padding-bottom 下沿
     * （.post 自身 padding-bottom 为 0，stats 的 1rpx 分隔线就是卡片最后一行像素）。
     */
    &__pin {
        position: absolute;
        left: 18rpx;
        top: 35rpx;
        bottom: 0;
        width: 4rpx;
        border-radius: var(--r-2);
        background: var(--c-warning);
    }

    &__head {
        display: flex;
        align-items: center;
        gap: 19rpx;
    }

    /* 三点：88rpx 热区 + 继承色（tn-icon 的 color prop 不认 var()，只能靠父级继承） */
    &__more {
        flex: none;
        width: 88rpx;
        height: 88rpx;
        margin-right: -22rpx; /* 抵消热区，视觉位置不变 */
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--c-ink-3);
    }

    &__who {
        flex: 1;
        min-width: 0;
    }

    &__row {
        display: flex;
        align-items: center;
        gap: 12rpx;
    }

    /* 徽章组整体不压缩：官方 + 置顶 + 热门三枚并存也不换行、不出框 */
    &__tags {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        flex: none;
    }

    &__name {
        /* 长昵称只吃自己的空间，省略号收尾，不许把徽章顶出去 */
        flex: 0 1 auto;
        min-width: 0;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        letter-spacing: 0.02em;
    }

    &__time {
        display: block;
        margin-top: 8rpx;
        font-size: var(--fs-19);
        color: var(--c-ink-3);
        letter-spacing: 0.03em;
    }

    &__text {
        display: block;
        margin-top: 21rpx;
        font-size: var(--fs-25);
        line-height: 1.78;
        color: var(--c-ink-2);
    }

    &__media {
        position: relative;
        margin-top: 21rpx;
        height: 500rpx;
        border-radius: var(--r-27);
        overflow: hidden;
        background: var(--c-neutral-bg);
    }

    &__media-img {
        width: 100%;
        height: 100%;
    }

    &__play {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--c-mask);
    }

    &__play-btn {
        width: 88rpx;
        height: 88rpx;
        border-radius: var(--r-pill);
        background: var(--c-surface-2);
        display: flex;
        color: var(--c-ink);
        align-items: center;
        justify-content: center;
    }

    &__stats {
        display: flex;
        align-items: center;
        gap: 42rpx;
        margin-top: 23rpx;
        padding-bottom: 31rpx;
        border-bottom: 1rpx solid var(--c-line);
    }
}

/*
 * 图片画廊：按张数切栅格。
 * 宽度必须精确扣掉真实 gap 之和，否则右边缘对不齐 666rpx 内容宽：
 *   2 列 → 1 个 gap = var(--sp-1)
 *   3 列 → 2 个 gap = 2 × 8rpx = var(--sp-2)
 * 高度用明确 rpx 而不是 aspect-ratio（小程序端兼容性不稳）：
 *   单图 666×500（≈4:3）· 双列单格 329 · 三列单格 216
 */
.gallery {
    display: flex;
    flex-wrap: wrap;
    /* 末行左对齐：flex-start 天然不拉伸，不需要额外补位 */
    justify-content: flex-start;
    gap: var(--sp-1);
    margin-top: 21rpx;

    &__cell {
        position: relative;
        /* 不参与 flex 伸缩，宽度完全由档位决定 */
        flex: none;
        overflow: hidden;
        border-radius: var(--r-sm);
        /* 加载占位：图片没到之前不露白 */
        background: var(--c-neutral-bg);
    }

    &__img {
        display: block;
        width: 100%;
        height: 100%;
    }

    /* 失败态：格子内居中图标；单图另补一行重试文案 */
    &__fail {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: var(--sp-1);
        padding: var(--sp-2);
        /* 图标继承 --c-ink-3；tn-icon 的 color prop 不认 var()，只能靠父级继承 */
        color: var(--c-ink-3);
    }

    &__fail-text {
        font-size: var(--fs-19);
        color: var(--c-ink-3);
        text-align: center;
    }

    /* >9 张：第 9 格压深色叠层报剩余张数 */
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

    /* 1 图：整宽大图 666×500，用大图圆角 */
    &--g1 .gallery__cell {
        width: 100%;
        height: 500rpx;
        border-radius: var(--r-27);
    }

    /* 2 图：两列等宽方形，(666 - 8) / 2 = 329 */
    &--g2 .gallery__cell {
        width: calc((100% - var(--sp-1)) / 2);
        height: 329rpx;
    }

    /* 3 图：三列方形，(666 - 16) / 3 ≈ 216 */
    &--g3 .gallery__cell {
        width: calc((100% - var(--sp-2)) / 3);
        height: 216rpx;
    }

    /* 4 图：2×2，列宽同 2 图 */
    &--g4 .gallery__cell {
        width: calc((100% - var(--sp-1)) / 2);
        height: 329rpx;
    }

    /* 5–9 图：三列九宫格，末行不足三张时左对齐留白 */
    &--g9 .gallery__cell {
        width: calc((100% - var(--sp-2)) / 3);
        height: 216rpx;
    }
}

.avatar {
    width: 65rpx;
    height: 65rpx;
    border-radius: var(--r-pill);
    overflow: hidden;
    flex-shrink: 0;
    background: var(--c-brand-bg);
    border: 1rpx solid var(--c-line-strong);
    display: flex;
    align-items: center;
    justify-content: center;

    &__img {
        width: 100%;
        height: 100%;
    }

    &__plain {
        font-family: var(--font-serif);
        font-size: var(--fs-25);
        color: var(--c-brand-text);
    }
}

.badge {
    height: 31rpx;
    padding: 0 10rpx;
    border-radius: var(--r-xs);
    font-size: var(--fs-16);
    letter-spacing: 0.06em;
    line-height: 31rpx;
    /* 徽章不被昵称挤压；三枚并存时保持原尺寸 */
    flex: none;

    &--gold {
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
        border: 1rpx solid var(--c-primary-border);
    }

    &--ink {
        background: var(--c-neutral-bg);
        color: var(--c-ink-2);
    }

    /* 置顶：warning 语义（浅底 / 深字 / 1rpx 同色调边），与后台 is_top 口径对齐 */
    &--top {
        background: var(--c-warning-bg);
        color: var(--c-warning);
        border: 1rpx solid var(--c-warning-border);
    }

    /* 热门：danger 语义，和置顶拉开色相，避免两枚标签糊成一片 */
    &--hot {
        background: var(--c-danger-bg);
        color: var(--c-danger);
        border: 1rpx solid var(--c-danger-border);
    }
}

.pstat {
    display: inline-flex;
    align-items: center;
    gap: 10rpx;
    padding: 0;
    margin: 0;
    background: none;
    border: 0;
    line-height: 1;
    /* 点赞/评论/分享三个图标同色，由父级继承；.pstat__text 自己声明了 --c-ink-3 */
    color: var(--c-ink-3);

    /* 点赞选中：原来靠 tn-icon 的 :color 动态传 var()，那条路走不通（tn-icon 只认 tn-* / hex）。
       改成状态类，图标和文字一起变红。 */
    &.is-liked {
        color: var(--c-danger);
    }

    &__text {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }

    &--share {
        margin-left: auto;
        &::after {
            border: 0;
        }
    }

    &.is-liked .pstat__text {
        color: var(--c-danger);
    }
}

/* ---------- 活动卡 ---------- */
.event {
    display: flex;
    align-items: center;
    gap: 25rpx;
    /* 右侧箭头；卡片内所有文字元素都自带颜色（--c-ink / --c-ink-3 / --c-brand-text） */
    color: var(--c-ink-3);
    margin-top: 21rpx;
    padding: 25rpx;
    border-radius: var(--r-27);
    border: 1rpx solid var(--c-line-strong);
    background: var(--c-surface-2);

    &__date {
        width: 100rpx;
        flex-shrink: 0;
        text-align: center;
        border-right: 1rpx solid var(--c-line-strong);
        padding-right: 23rpx;
    }

    &__date-d {
        display: block;
        font-family: var(--font-serif);
        font-size: var(--fs-37);
        color: var(--c-brand-text);
        line-height: 1.1;
    }

    &__date-w {
        display: block;
        margin-top: 8rpx;
        font-size: var(--fs-17);
        color: var(--c-ink-3);
        letter-spacing: 0.08em;
    }

    &__main {
        flex: 1;
        min-width: 0;
    }

    &__title {
        display: block;
        font-size: var(--fs-23);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        line-height: var(--lh-body);
    }

    &__place {
        display: block;
        margin-top: 8rpx;
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }

    &__going {
        display: flex;
        align-items: center;
        gap: 14rpx;
        margin-top: 12rpx;
    }

    &__going-text,
    &__price {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }
}

/* ---------- 列表尾 ---------- */
.feed-more {
    padding: 8rpx 0 4rpx;
}

.feed-end {
    padding: 50rpx 0 12rpx;
    text-align: center;

    &__text {
        font-family: var(--font-serif);
        font-size: var(--fs-18);
        letter-spacing: 0.2em;
        color: var(--c-ink-3);
    }
}
</style>
