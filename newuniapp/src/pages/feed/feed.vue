<template>
    <!-- 02 动态（Tab 2）｜只读浏览：无发布入口，无编辑态 -->
    <page-shell
        title="动态"
        nav-variant="solid"
        :show-back="false"
        title-align="left"
        title-variant="display"
        :nav-capsule-avoid="true"
        :tabbar="true"
        current="/pages/feed/feed"
        bg="page"
    >
        <template #nav-right>
            <!-- 搜索图标：02 设计稿有，但站内没有动态搜索页（缺口 G2）→ 落到「查档期」关键词
                 搜索不成立，改为提示，不做假跳转 -->
            <view
                hover-class="is-pressed"
                class="nav-act"
                aria-role="button"
                aria-label="搜索"
                @tap="onSearchTap"
            >
                <tn-icon name="search" :size="38" />
            </view>
        </template>

        <!-- 分类栏：数据为空时整条隐藏（03-施工分工 降级规则） -->
        <view v-if="categories.length" class="catbar">
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

        <!-- 副筛选：左「# 标签 ×」（选中才出现） / 右排序下拉 -->
        <view v-if="categories.length || activeTag" class="subfilter">
            <view v-if="activeTag" class="chip-tag" @tap="clearTag">
                <text class="chip-tag__text"># {{ activeTag }}</text>
                <tn-icon name="close" :size="22" />
            </view>
            <view v-else class="subfilter__ghost" />

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
                            <text v-if="post.user_type === 3" class="badge badge--gold">官方</text>
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

                <!-- 媒体：视频只出静帧，点播放圆进详情全屏播放 -->
                <view v-if="post.media_cover" class="post__media" @tap="goDetail(post)">
                    <image
                        class="post__media-img"
                        :src="post.media_cover"
                        mode="aspectFill"
                        lazy-load
                    />
                    <view v-if="post.media_type === 'video'" class="post__play">
                        <view class="post__play-btn">
                            <tn-icon name="play-fill" :size="30" />
                        </view>
                    </view>
                    <text v-if="(post.media_count || 0) > 1" class="post__count"
                        >共 {{ post.media_count }} 张</text
                    >
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
 * 数据：`dynamic/lists`（GET，免登录）+ `dynamic/hotTags`。
 *  · 后端真实参数：page / page_size / tag / keyword / dynamic_type / order_by(create_time|hot) / order_dir
 *  · 分类条 = 「全部」+ 热标签（hotTags 从已发布动态的 tags 统计而来，是真数据）；
 *    热标签为空 → 整条分类栏隐藏，不造假分类。
 *  · 排序只给后端支持的两种（最新 / 最热），不做「距离最近」这类无数据支撑的假选项。
 *
 * 交互：
 *  · 点赞乐观更新，失败回滚 + 轻提示；未登录先跳登录，登录回来补执行原动作。
 *  · 视频只出静帧，点进详情播放（列表内联播放会同时出声、耗流量）。
 *  · 只读：没有发布入口、没有编辑/删除入口。
 */
import { computed, ref } from 'vue'
import { onLoad, onPullDownRefresh, onReachBottom, onShow, onUnload } from '@dcloudio/uni-app'
import { getCaseList, getCaseHotTags, toggleCaseLike } from '@/api/content'
import { normalizePage, toArray } from '@/request'
import { navigate } from '@/utils/link'
import { toast } from '@/composables/useToast'
import { useUserStore } from '@/stores/user'
import { hide, hiddenIdSet } from '@/utils/hiddenFeed'
import { BRAND_SEAL } from '@/constants/brand'

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
    dynamic_type?: number
    images?: string[]
    video_url?: string
    video_cover?: string
    activity?: Record<string, any> | null
    /** 前端派生（不落库） */
    media_cover?: string
    media_type?: 'image' | 'video'
    media_count?: number
    avatar_broken?: boolean
}

const SORTS = [
    { key: 'latest', label: '最新', orderBy: 'create_time' },
    { key: 'hot', label: '最热', orderBy: 'hot' }
] as const

const userStore = useUserStore()

const list = ref<FeedRow[]>([])
const hotTags = ref<string[]>([])
const activeCat = ref('all')
const activeTag = ref('')
const sortKey = ref<'latest' | 'hot'>('latest')
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

const categories = computed(() => {
    if (!hotTags.value.length) return []
    return [
        { key: 'all', label: '全部' },
        ...hotTags.value.slice(0, 6).map((t) => ({ key: t, label: t }))
    ]
})

const sortLabel = computed(() => SORTS.find((s) => s.key === sortKey.value)?.label || '最新')

const loadMoreStatus = computed<'idle' | 'loading' | 'nomore' | 'error'>(() => {
    if (loadMoreFailed.value) return 'error'
    if (loadingMore.value) return 'loading'
    return 'idle'
})

// ---------------------------------------------------------------- 派生字段

function initialOf(name?: string) {
    const s = (name || '').trim()
    // 旧默认 '声' 是模板演示品牌「声宴」的占位字，真实品牌是格林社。
    // 作者名缺失时统一用品牌印章字，不要再出现与品牌无关的汉字。
    return s ? [...s][0] : BRAND_SEAL
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

/** 把后端字段整理成模板直接可用的形状（列表里不做 v-if 计算，减少渲染开销） */
function normalizeRow(raw: any): FeedRow {
    const row: FeedRow = { ...(raw || {}) }

    // 作者字段口径不一致（真机反馈：列表页头像/名称看起来像占位数据）：
    //   列表 /dynamic/lists → Dynamic::getList 把作者放在嵌套 user:{nickname,avatar}
    //   详情 /dynamic/detail → DynamicLogic::getDynamicDetail 是扁平 user_nickname/user_avatar
    // 模板统一读扁平字段，所以这里把嵌套对象摊平。不修的话列表会 fallback 成
    // BRAND_NAME（"格林社"）+ 文字首字头像，与详情页显示「官方 + 官方 logo」对不上。
    // 只在扁平字段为空时补，避免覆盖后端已经直出的值。
    const author = row.user
    if (author && typeof author === 'object') {
        if (!row.user_nickname) row.user_nickname = author.nickname || author.name || ''
        if (!row.user_avatar) row.user_avatar = author.avatar || ''
    }

    const images = toArray<string>(row.images).filter(Boolean)
    if (row.dynamic_type === 2 && (row.video_cover || row.video_url)) {
        row.media_type = 'video'
        row.media_cover = row.video_cover || ''
    } else if (images.length) {
        row.media_type = 'image'
        row.media_cover = images[0]
        row.media_count = images.length
    }
    if (!row.media_cover) {
        row.media_type = undefined
        row.media_cover = ''
    }
    return row
}

// ---------------------------------------------------------------- 数据

async function fetchTags() {
    try {
        const tags = await getCaseHotTags()
        hotTags.value = toArray<string>(tags).filter(Boolean)
    } catch {
        // 标签失败不影响列表；分类条按空处理（整条隐藏）
        hotTags.value = []
    }
}

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
            ...(activeTag.value ? { tag: activeTag.value } : {})
        } as any)
        const paged = normalizePage<FeedRow>(res, page.value, pageSize)
        const hidden = hiddenIdSet()
        const rows = paged.list.filter((raw: any) => !hidden.has(Number(raw?.id))).map(normalizeRow)
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

function selectCat(key: string) {
    if (activeCat.value === key) return
    activeCat.value = key
    activeTag.value = key === 'all' ? '' : key
    sorterOpen.value = false
    list.value = []
    fetchList(true)
}

function clearTag() {
    activeTag.value = ''
    activeCat.value = 'all'
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

async function onLike(post: FeedRow) {
    if (!userStore.isLogin) {
        pendingLikeId.value = post.id
        navigate({ path: '/pages/login/login' })
        return
    }
    const liked = isLiked(post)
    // 乐观更新：先变色变数，失败回滚
    post.is_liked = !liked
    post.like_count = Math.max(0, Number(post.like_count || 0) + (liked ? -1 : 1))
    try {
        const res = await toggleCaseLike({ id: post.id })
        if (res && typeof res.is_like === 'number') {
            post.is_liked = res.is_like === 1
        }
    } catch {
        post.is_liked = liked
        post.like_count = Math.max(0, Number(post.like_count || 0) + (liked ? 1 : -1))
        toast.error('点赞没成功，再点一次')
    }
}

function onSearchTap() {
    // 站内没有动态搜索页（G2）：按设计稿口径不新造页面，给出可行替代入口
    toast.info('想找主持人？去「查档期」按日期和地区筛')
    setTimeout(() => navigate({ path: '/pages/query/query' }), 900)
}

/** 「不感兴趣」只做本地隐藏：读写统一走 utils/hiddenFeed，设置页用同一份数据做恢复 */

function onMore(post: FeedRow) {
    uni.showActionSheet({
        // 只列真实可用的操作：举报/内容偏好后端没有接口，不摆出来骗点击
        itemList: ['复制内容', '不感兴趣'],
        success: (res) => {
            if (res.tapIndex === 0) {
                const text = String(post.content || post.title || '').trim()
                if (!text) {
                    toast.info('这条动态没有可复制的文字')
                    return
                }
                uni.setClipboardData({
                    data: text,
                    success: () => toast.success('已复制')
                })
            } else if (res.tapIndex === 1) {
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
    fetchTags()
    fetchList(true)
})

onShow(() => {
    syncHiddenChange()
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
    fetchTags()
    fetchList(true)
})

onReachBottom(() => loadMore())
</script>

<style lang="scss" scoped>
.nav-act {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 88rpx;
    height: 88rpx;
    /* 只放图标 */
    color: var(--c-ink-2);
}

/* ---------- 分类条 ---------- */
.catbar {
    display: flex;
    gap: 42rpx;
    padding: 4rpx 42rpx 0;
    overflow: hidden;
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

/* ---------- 副筛选 ---------- */
.subfilter {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 23rpx 42rpx 8rpx;

    &__ghost {
        flex: 1;
    }
}

.chip-tag {
    display: inline-flex;
    align-items: center;
    gap: 12rpx;
    height: 48rpx;
    padding: 0 19rpx;
    border-radius: var(--r-pill);
    background: var(--c-brand-bg);
    /* 可点控件边界（@tap="clearTag" 清除筛选标签）：原 --c-primary-border 对 --c-bg 仅 1.59:1，
       用户看不出这是按钮。按项目既有约定改 --c-control-border（对 --c-bg 3.56:1）。
       填充 --c-brand-bg（1.08:1）刻意不动：控件边界由边框表达，填充只是氛围。 */
    border: 1rpx solid var(--c-control-border);
    /* 关闭图标与标签文字同色；.chip-tag__text 自己声明了 --c-brand-text */
    color: var(--c-brand-text);

    &__text {
        font-size: 20rpx;
        color: var(--c-brand-text);
    }
}

.sorter {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    padding: 8rpx 0 8rpx 16rpx;
    /* 箭头与排序文字同色；.sorter__text 自己声明了 --c-ink-3 */
    color: var(--c-ink-3);

    &__text {
        font-size: var(--fs-21);
        color: var(--c-ink-3);
    }
}

.sortpanel {
    position: absolute;
    right: 42rpx;
    top: 76rpx;
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
        font-size: 24rpx;
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
        height: 420rpx;
        border-radius: var(--r-27);
        background: var(--c-neutral-bg);
    }
}

/* ---------- 卡片 ---------- */
.post {
    padding: 35rpx 42rpx 0;

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

    &__name {
        font-size: 24rpx;
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
        height: 480rpx;
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

    &__count {
        position: absolute;
        right: 20rpx;
        bottom: 20rpx;
        height: 37rpx;
        padding: 0 14rpx;
        border-radius: var(--r-10);
        background: var(--c-toast-bg);
        color: var(--c-on-dark);
        font-size: var(--fs-18);
        line-height: 37rpx;
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

    &--gold {
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
        border: 1rpx solid var(--c-primary-border);
    }

    &--ink {
        background: var(--c-neutral-bg);
        color: var(--c-ink-2);
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
        font-size: 20rpx;
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
        font-size: 20rpx;
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
