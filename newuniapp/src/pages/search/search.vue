<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell title="搜索" bg="page" :show-back="true">
        <view class="search-page">
            <!-- 搜索框 -->
            <view class="bar">
                <view class="bar__input-wrap">
                    <!-- v1 u-icon → v2 tn-icon：search → search -->
                    <tn-icon name="search" :size="32" />
                    <input
                        :cursor-spacing="120"
                        v-model="keyword"
                        class="bar__input"
                        placeholder="搜人员、资讯"
                        placeholder-class="bar__ph"
                        confirm-type="search"
                        :focus="autoFocus"
                        :adjust-position="false"
                        @confirm="handleSearch"
                    />
                    <view
                        hover-class="is-pressed"
                        v-if="keyword"
                        class="bar__clear"
                        @tap="clearKeyword"
                    >
                        <!-- v1 u-icon → v2 tn-icon：close-circle-fill → close-circle（字体无 fill 版） -->
                        <tn-icon name="close-circle" :size="32" />
                    </view>
                </view>
                <view hover-class="is-pressed" class="bar__cancel" @tap="handleCancel">
                    <text class="bar__cancel-text">取消</text>
                </view>
            </view>

            <!-- 未搜索：热搜 + 历史 -->
            <view v-if="!searched" class="page-pad panels">
                <view v-if="history.length" class="panel">
                    <!-- P0 §2-1：手写 .panel__head → section-header（标题 + 右侧「清空」文字链） -->
                    <section-header title="搜索历史" more="清空" flush @more="clearHistory" />
                    <view class="chips">
                        <view
                            hover-class="is-pressing"
                            :hover-stay-time="90"
                            v-for="(h, i) in history"
                            :key="i"
                            class="chip press-spring"
                            @tap="applyKeyword(h)"
                        >
                            <text class="chip__text">{{ h }}</text>
                        </view>
                    </view>
                </view>

                <view v-if="hotWords.length" class="panel">
                    <section-header title="热门搜索" flush />
                    <view class="chips">
                        <view
                            hover-class="is-pressing"
                            :hover-stay-time="90"
                            v-for="(w, i) in hotWords"
                            :key="i"
                            class="chip press-spring"
                            @tap="applyKeyword(w)"
                        >
                            <text class="chip__text">{{ w }}</text>
                        </view>
                    </view>
                </view>

                <view v-if="!history.length && !hotWords.length" class="panel panel--empty">
                    <text class="panel__hint">输入关键词开始搜索</text>
                </view>
            </view>

            <!-- 搜索结果 -->
            <template v-else>
                <view class="tabs">
                    <view
                        hover-class="is-pressed"
                        v-for="t in TABS"
                        :key="t.key"
                        class="tabs__item"
                        :class="{ 'is-active': activeTab === t.key }"
                        @tap="switchTab(t.key)"
                    >
                        <text class="tabs__text">{{ t.label }}</text>
                        <text v-if="countOf(t.key)" class="tabs__count t-num">{{ countOf(t.key) }}</text>
                    </view>
                </view>

                <!-- P0 §2-1：补「搜索中」态。原来点搜索后直接进 skeleton，
                     .tabs__count 会从有到无再回来跳两下；这里给一行「正在搜索」承接 -->
                <view v-if="loading" class="page-pad searching">
                    <text class="searching__text">正在搜索「{{ submitted }}」</text>
                    <base-skeleton variant="card" :rows="3" />
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
                    @retry="handleSearch"
                />

                <!-- 空态：跨 tab 分叉（v2 补丁①）—— 本 tab 空 ≠ 全网无结果。
                     另一 tab 有结果时标题直接报数并给「看看资讯」，否则才用动态回显。 -->
                <base-empty
                    v-else-if="!currentList.length"
                    status="empty"
                    empty-icon="empty-search"
                    :empty-text="emptyTitle"
                    :desc="crossTabEmpty ? '' : FB.EMPTY_HINT_SEARCH"
                    :action-text="crossTabEmpty ? `看看${otherTab.label}` : '清空关键词'"
                    :show-action="true"
                    fixed
                    @action="onEmptyAction"
                />

                <view v-else class="page-pad results">
                    <!-- 人员 -->
                    <template v-if="activeTab === 'staff'">
                        <base-card
                            v-for="item in staffList"
                            :key="item.id"
                            compact
                            clickable
                            class="staff"
                            @tap="goStaff(item)"
                        >
                            <image
                                class="staff__avatar"
                                :src="imageUrl(item.avatar)"
                                mode="aspectFill"
                            />
                            <view class="staff__body">
                                <text class="staff__name ellipsis">{{ item.name }}</text>
                                <view v-if="item.rating || item.order_count" class="staff__meta">
                                    <text v-if="item.rating" class="staff__rating"
                                        >评分 {{ item.rating }}</text
                                    >
                                    <text v-if="item.rating && item.order_count" class="staff__sep"
                                        >·</text
                                    >
                                    <text v-if="item.order_count" class="staff__muted"
                                        >{{ item.order_count }} 单</text
                                    >
                                </view>
                                <!-- 简介：搜索是「比较」场景，一句话简介是帮用户区分候选人的信息，
                                     不能因为 v2 卡规格里没有这一行就删掉（删内容 ≠ 改样式）。
                                     优先列表页简介（≤36 字），最多 3 行完整展示，无数据不渲染。 -->
                                <text
                                    v-if="item.list_intro || item.profile"
                                    class="staff__profile"
                                    >{{ item.list_intro || item.profile }}</text
                                >
                            </view>
                            <!-- 状态标签的 dot 表达「档期是否成立」，是状态编码，故 :dot="true"。
                                 与 message/detail 类型标签的 :dot="false"（分类不是状态）是两种用法，
                                 不是不一致 —— 不要为「统一」把这里改成 false。 -->
                            <status-tag
                                v-if="availOf(item)"
                                :tone="availOf(item)!.tone"
                                :label="availOf(item)!.label"
                                :dot="true"
                            />
                        </base-card>
                    </template>

                    <!-- 资讯 -->
                    <template v-else>
                        <base-card
                            v-for="item in articleList"
                            :key="item.id"
                            compact
                            clickable
                            class="article"
                            @tap="goArticle(item)"
                        >
                            <image
                                v-if="item.image"
                                class="article__img"
                                :src="imageUrl(item.image)"
                                mode="aspectFill"
                            />
                            <view class="article__body">
                                <text class="article__title ellipsis-2">{{ item.title }}</text>
                                <text class="article__meta">{{ item.create_time }}</text>
                            </view>
                        </base-card>
                    </template>

                    <load-more :status="loadMoreStatus" @retry="runQuery(false)" />
                </view>
            </template>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 搜索。
 *
 * 后端没有全局搜索接口，只有 /search/hotLists 提供热搜词。
 * 人员走 staff/lists?keyword=，资讯走 article/lists?keyword=，两个 tab 各查各的。
 * 历史记录存本地（最多 10 条），不上报。
 */
import { computed, ref } from 'vue'
import { onLoad, onReachBottom } from '@dcloudio/uni-app'
import { getHotSearch } from '@/api/home'
import { getStaffList } from '@/api/catalog'
import { getArticleList } from '@/api/content'
import { normalizePage } from '@/request'
import { useAppStore } from '@/stores/app'
import { navigate, safeDecodeParam } from '@/utils/link'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { toast } from '@/composables/useToast'
import { haptic } from '@/utils/haptics'
import { FB } from '@/constants/feedback'

const HISTORY_KEY = 'search_history'
const HISTORY_MAX = 10

type SearchTab = 'staff' | 'article'

const TABS: Array<{ key: SearchTab; label: string }> = [
    { key: 'staff', label: '人员' },
    { key: 'article', label: '资讯' }
]

const appStore = useAppStore()

const keyword = ref('')
const submitted = ref('')
const searched = ref(false)
const autoFocus = ref(true)

const activeTab = ref<SearchTab>('staff')
const hotWords = ref<string[]>([])
const history = ref<string[]>([])

const staffList = ref<any[]>([])
const articleList = ref<any[]>([])
const staffTotal = ref(0)
const articleTotal = ref(0)

const page = ref(1)
const pageSize = 10
const hasMore = ref(true)
const loadingMore = ref(false)
const loading = ref(false)
const error = ref(false)

/** O5：真实错误驱动 errorKind / 文案 / 恢复动作 */
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        handleSearch()
        return
    }
    if (kind === 'reselect' || kind === 'back') {
        uni.navigateBack()
        return
    }
    if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    // contact / 其余：保持现有客服入口，不新增跳转
}

const loadMoreFailed = ref(false)
const loadMoreStatus = computed<'idle' | 'loading' | 'nomore' | 'error'>(() => {
    if (loadMoreFailed.value) return 'error'
    if (loadingMore.value && currentList.value.length) return 'loading'
    if (!hasMore.value) return 'nomore'
    return 'idle'
})

const currentList = computed(() =>
    activeTab.value === 'staff' ? staffList.value : articleList.value
)

function countOf(key: SearchTab) {
    return key === 'staff' ? staffTotal.value : articleTotal.value
}

function tabLabel(key: SearchTab) {
    return TABS.find((t) => t.key === key)?.label ?? ''
}

/** 另一个 tab（跨 tab 空态分叉用） */
const otherTab = computed(() => TABS.find((t) => t.key !== activeTab.value) ?? TABS[0])

/**
 * 本 tab 空、但另一 tab 有结果 —— v2 补丁① 修的 bug。
 * 原实现只看 `!currentList.length`，于是「人员 0 条 / 资讯 3 条」停在人员 tab 时
 * 显示「没有找到相关内容」，**用户看到"没有"就退出了，而实际有**。
 */
const crossTabEmpty = computed(() => !currentList.value.length && countOf(otherTab.value.key) > 0)

const emptyTitle = computed(() =>
    crossTabEmpty.value
        ? `「${tabLabel(activeTab.value)}」里没有，但「${otherTab.value.label}」有 ${countOf(
              otherTab.value.key
          )} 条`
        : `没有找到与「${submitted.value}」相关的内容`
)

/** 空态动作：跨 tab 空 → 切到有结果的那个 tab；都空 → 清关键词（文案与动作必须一致） */
function onEmptyAction() {
    if (crossTabEmpty.value) {
        switchTab(otherTab.value.key)
        return
    }
    clearKeyword()
}

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

/**
 * 人员卡的档期状态（P0 §2-1：右侧状态标签）。
 * 搜索走的是同一套 staff/lists，档期字段是布尔；字段缺失时**不渲染标签**，
 * 不要用「可预约」兜底 —— 没数据不等于可约，那是把未知说成已知。
 */
function availOf(item: any): { tone: 'success' | 'neutral'; label: string } | null {
    if (item?.schedule_available === true) return { tone: 'success', label: '可预约' }
    if (item?.schedule_available === false) return { tone: 'neutral', label: '当日不可约' }
    return null
}

function loadHistory() {
    try {
        const raw = uni.getStorageSync(HISTORY_KEY)
        history.value = Array.isArray(raw) ? raw.filter((i: any) => typeof i === 'string') : []
    } catch {
        history.value = []
    }
}

function pushHistory(word: string) {
    const next = [word, ...history.value.filter((i) => i !== word)].slice(0, HISTORY_MAX)
    history.value = next
    try {
        uni.setStorageSync(HISTORY_KEY, next)
    } catch {
        // 存储失败不影响搜索
    }
}

function clearHistory() {
    history.value = []
    try {
        uni.removeStorageSync(HISTORY_KEY)
    } catch {
        // ignore
    }
}

async function loadHot() {
    try {
        const res = await getHotSearch()
        if (!res || Number(res.status) !== 1) {
            hotWords.value = []
            return
        }
        hotWords.value = (res.data || []).map((i) => i.name).filter(Boolean)
    } catch {
        hotWords.value = []
    }
}

function clearKeyword() {
    keyword.value = ''
    searched.value = false
    submitted.value = ''
    staffList.value = []
    articleList.value = []
    staffTotal.value = 0
    articleTotal.value = 0
}

function applyKeyword(word: string) {
    haptic('light', 'search-chip')
    keyword.value = word
    handleSearch()
}

async function fetchStaff(reset: boolean) {
    const res = await getStaffList({
        page_no: page.value,
        page_size: pageSize,
        keyword: submitted.value
    })
    const paged = normalizePage<any>(res, page.value, pageSize)
    staffList.value = reset ? paged.list : [...staffList.value, ...paged.list]
    staffTotal.value = paged.total
    hasMore.value = paged.hasMore
    if (hasMore.value) page.value += 1
}

async function fetchArticle(reset: boolean) {
    const res = await getArticleList({
        page_no: page.value,
        page_size: pageSize,
        keyword: submitted.value
    })
    const paged = normalizePage<any>(res, page.value, pageSize)
    articleList.value = reset ? paged.list : [...articleList.value, ...paged.list]
    articleTotal.value = paged.total
    hasMore.value = paged.hasMore
    if (hasMore.value) page.value += 1
}

async function runQuery(reset: boolean) {
    if (!submitted.value) return
    if (reset) {
        page.value = 1
        hasMore.value = true
        error.value = false
        loading.value = true
        loadMoreFailed.value = false
    }
    if (!hasMore.value && !reset) return

    loadingMore.value = true
    loadMoreFailed.value = false
    try {
        if (activeTab.value === 'staff') await fetchStaff(reset)
        else await fetchArticle(reset)
    } catch (e) {
        rawError.value = e
        if (reset) error.value = true
        else loadMoreFailed.value = true
    } finally {
        loadingMore.value = false
        loading.value = false
    }
}

function handleSearch() {
    const word = keyword.value.trim()
    if (!word) {
        toast.info('请输入关键词')
        return
    }
    submitted.value = word
    searched.value = true
    autoFocus.value = false
    pushHistory(word)
    staffList.value = []
    articleList.value = []
    runQuery(true)
}

function switchTab(key: SearchTab) {
    if (key === activeTab.value) return
    activeTab.value = key
    // 切 tab 时该 tab 还没数据才查，避免来回切重复请求
    const list = key === 'staff' ? staffList.value : articleList.value
    if (!list.length) {
        page.value = 1
        hasMore.value = true
        runQuery(true)
    }
}

function handleCancel() {
    const pages = getCurrentPages()
    if (pages.length > 1) uni.navigateBack()
    else uni.switchTab({ url: '/pages/index/index' })
}

function goStaff(item: any) {
    navigate({
        path: '/packages/pkg-catalog/pages/staff-detail/staff-detail',
        query: { id: item.id }
    })
}

function goArticle(item: any) {
    navigate({
        path: '/packages/pkg-catalog/pages/article-detail/article-detail',
        query: { id: item.id }
    })
}

onLoad((options: any) => {
    loadHistory()
    loadHot()
    const tab = String(options?.tab || '').trim()
    if (tab === 'staff' || tab === 'article') {
        activeTab.value = tab
    }
    const kw = options?.keyword ? safeDecodeParam(options.keyword) : ''
    if (kw) {
        keyword.value = kw
        handleSearch()
    }
})

onReachBottom(() => {
    if (searched.value) runQuery(false)
})
</script>

<style lang="scss" scoped>
.search-page {
    /* 高度由 page-shell 承担：再写 100vh 会多出一个导航条的高度 */
    background: var(--c-bg);
}

.bar {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    padding: var(--sp-2) var(--sp-3);
    background: var(--c-surface);
    border-bottom: 1rpx solid var(--c-line);

    &__input-wrap {
        flex: 1;
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        /* 两个图标（搜索/清除）同色，由父级统一继承；
           .bar__input 自己声明了 --c-ink，输入文字不受影响 */
        color: var(--c-ink-3);
        min-width: 0;
        /* 触控热区 88rpx：输入区整块高 88，点击落在胶囊内任意位置都能聚焦 */
        height: 88rpx;
        padding: 0 var(--sp-2) 0 var(--sp-3);
        border: 1rpx solid var(--c-control-border);
        border-radius: var(--r-pill);
        background: var(--c-surface-2);
        box-sizing: border-box;
    }

    &__input {
        flex: 1;
        min-width: 0;
        height: 88rpx;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__ph {
        color: var(--c-ink-3);
    }

    &__clear {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: 64rpx;
        height: 88rpx;

        &::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            /* 横向补到 88rpx（64 + 12×2），纵向已由 88 高的行撑满 */
            left: -12rpx;
            right: -12rpx;
        }
    }

    &__cancel {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        min-width: 88rpx;
        min-height: 88rpx;
    }

    &__cancel-text {
        font-size: var(--fs-md);
        color: var(--c-ink-2);
    }
}

.panels {
    display: flex;
    flex-direction: column;
    gap: var(--sp-5);
    padding-top: var(--sp-4);
}

.panel {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);

    &--empty {
        align-items: center;
        padding-top: var(--sp-6);
    }

    &__hint {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

/* 搜索中态（P0 §2-1）：一行说明 + 骨架，避免 tabs 计数跳变 */
.searching {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding-top: var(--sp-3);

    &__text {
        font-size: var(--fs-md);
        color: var(--c-ink-3);
        line-height: var(--lh-body);
    }
}

.chips {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
}

.chip {
    position: relative;
    display: flex;
    align-items: center;
    max-width: 100%;
    min-height: 56rpx;
    box-sizing: border-box;
    padding: var(--sp-1) var(--sp-3);
    /* P0 §2-1：对齐 chip 规格（高 56rpx / --r-pill / --c-control-border 边）。
       这里是「点一下执行搜索」的动作，不是选中值，故无选中态、不换 chip-group
       —— chip-group 是纯受控单选，换过去会直接坏功能。 */
    border: 1rpx solid var(--c-control-border);
    border-radius: var(--r-pill);
    background: var(--c-surface-2);
    box-shadow: var(--shadow-card);

    /* 视觉 56rpx，纵向热区扩到 88rpx（上下各 16rpx）；横向不动，避免相邻 chip 热区重叠 */
    &::before {
        content: '';
        position: absolute;
        top: -16rpx;
        bottom: -16rpx;
        left: 0;
        right: 0;
    }

    &__text {
        display: block;
        max-width: 100%;
        overflow: hidden;
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        line-height: var(--lh-tight);
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}

.tabs {
    position: sticky;
    top: 0;
    z-index: 10;
    display: flex;
    gap: var(--sp-2);
    padding: var(--sp-2) var(--sp-4);
    background: var(--c-surface);
    border-bottom: 1rpx solid var(--c-line);

    /* P0 §2-1：下划线 tab → 胶囊版式（高 56rpx / --r-pill / --c-line-strong 边 /
       选中 --c-ink 底 + --c-ink-inverse 字） */
    &__item {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--sp-1);
        height: 56rpx;
        padding: 0 var(--sp-3);
        border: 1rpx solid var(--c-line-strong);
        border-radius: var(--r-pill);
        background: var(--c-surface);
        box-sizing: border-box;

        /* ⚠️ 视觉高度 56rpx，触控高度 88rpx —— 这是两个约束，可同时满足，不要"按 56 就 56"改回去。
           上下各扩 16rpx：56 + 16×2 = 88（= 触控下限）。只做纵向扩展，
           横向留 0 避免与相邻 tab 的热区重叠（.tabs 的 gap 是 --sp-2）。 */
        &::before {
            content: '';
            position: absolute;
            top: -16rpx;
            bottom: -16rpx;
            left: 0;
            right: 0;
        }
    }

    &__text {
        font-size: var(--fs-md);
        color: var(--c-ink-2);
    }

    &__count {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__item.is-active {
        background: var(--c-active-bg);
        border-color: var(--c-active-border);
    }

    &__item.is-active &__text,
    &__item.is-active &__count {
        color: var(--c-active-fg);
    }
}

.results {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
    padding-top: var(--sp-3);
}

.staff {
    display: flex;
    align-items: center;
    gap: var(--sp-3);

    &__avatar {
        flex-shrink: 0;
        /* P0 §2-1：竖图 100×120 → v2 84rpx 圆头像 */
        width: 84rpx;
        height: 84rpx;
        border-radius: var(--r-pill);
        background: var(--c-neutral-bg);
    }

    &__body {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
        min-width: 0;
    }

    &__name {
        /* v2 staff 卡：姓名 --fs-lg 32rpx / 600 */
        font-size: var(--fs-lg);
        font-weight: 600;
        color: var(--c-ink);
    }

    &__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: var(--sp-1);
    }

    &__rating {
        font-size: var(--fs-xs);
        color: var(--c-warning);
    }

    &__sep {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__muted {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__profile {
        /* 中文句子 → 语义档 --fs-md（不得用 --fs-xs 承载中文句子） */
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 3;
        overflow: hidden;
        word-break: break-all;
        font-size: var(--fs-md);
        color: var(--c-ink-2);
        line-height: var(--lh-body);
        white-space: normal;
    }
}

.article {
    display: flex;
    align-items: center;
    gap: var(--sp-3);

    &__img {
        flex-shrink: 0;
        width: 160rpx;
        height: 112rpx;
        border-radius: var(--r-sm);
        background: var(--c-neutral-bg);
    }

    &__body {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: var(--sp-2);
        min-width: 0;
    }

    &__title {
        font-size: var(--fs-md);
        color: var(--c-ink);
        line-height: var(--lh-body);
    }

    &__meta {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: var(--lh-tight);
        overflow-wrap: anywhere;
    }
}

.loadmore {
    padding: var(--sp-3) 0 var(--sp-6);
    text-align: center;

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}
</style>
