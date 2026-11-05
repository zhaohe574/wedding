<template>
    <view class="search-page">
        <!-- 搜索框 -->
        <view class="bar">
            <view class="bar__input-wrap">
                <u-icon name="search" :size="32" color="var(--c-ink-3)" />
                <input
                    v-model="keyword"
                    class="bar__input"
                    placeholder="搜人员、资讯"
                    placeholder-class="bar__ph"
                    confirm-type="search"
                    :focus="autoFocus"
                    :adjust-position="false"
                    @confirm="handleSearch"
                />
                <view hover-class="is-pressed" v-if="keyword" class="bar__clear" @tap="clearKeyword">
                    <u-icon name="close-circle-fill" :size="32" color="var(--c-ink-3)" />
                </view>
            </view>
            <view hover-class="is-pressed" class="bar__cancel" @tap="handleCancel">
                <text class="bar__cancel-text">取消</text>
            </view>
        </view>

        <!-- 未搜索：热搜 + 历史 -->
        <view v-if="!searched" class="page-pad panels">
            <view v-if="history.length" class="panel">
                <view class="panel__head">
                    <text class="panel__title">搜索历史</text>
                    <text class="panel__action" @tap="clearHistory">清空</text>
                </view>
                <view class="chips">
                    <view hover-class="is-pressed" v-for="(h, i) in history" :key="i" class="chip" @tap="applyKeyword(h)">
                        <text class="chip__text">{{ h }}</text>
                    </view>
                </view>
            </view>

            <view v-if="hotWords.length" class="panel">
                <view class="panel__head">
                    <text class="panel__title">热门搜索</text>
                </view>
                <view class="chips">
                    <view hover-class="is-pressed" v-for="(w, i) in hotWords" :key="i" class="chip" @tap="applyKeyword(w)">
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
                <view hover-class="is-pressed"
                    v-for="t in TABS"
                    :key="t.key"
                    class="tabs__item"
                    :class="{ 'is-active': activeTab === t.key }"
                    @tap="switchTab(t.key)"
                >
                    <text class="tabs__text">{{ t.label }}</text>
                    <text v-if="countOf(t.key)" class="tabs__count">{{ countOf(t.key) }}</text>
                </view>
            </view>

            <view v-if="loading" class="page-pad">
                <base-skeleton :rows="3" />
            </view>

            <base-empty v-else-if="error" status="error" error-kind="network" fixed @retry="handleSearch" />

            <base-empty
                v-else-if="!currentList.length"
                status="empty" empty-icon="empty-search"
                :empty-text="`没有找到与「${submitted}」相关的内容`"
                fixed
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
                        <image class="staff__avatar" :src="imageUrl(item.avatar)" mode="aspectFill" />
                        <view class="staff__body">
                            <text class="staff__name ellipsis">{{ item.name }}</text>
                            <view class="staff__meta">
                                <text v-if="item.rating" class="staff__rating">★ {{ item.rating }}</text>
                                <text v-if="item.order_count" class="staff__muted">{{ item.order_count }} 单</text>
                            </view>
                            <text v-if="item.profile" class="staff__profile ellipsis">{{ item.profile }}</text>
                        </view>
                        <u-icon class="staff__arrow" name="arrow-right" :size="24" color="var(--c-ink-3)" />
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
                        <image v-if="item.image" class="article__img" :src="imageUrl(item.image)" mode="aspectFill" />
                        <view class="article__body">
                            <text class="article__title ellipsis-2">{{ item.title }}</text>
                            <text class="article__meta">{{ item.create_time }}</text>
                        </view>
                    </base-card>
                </template>

                <view class="loadmore">
                    <text v-if="loadingMore" class="loadmore__text">加载中…</text>
                    <text v-else-if="!hasMore" class="loadmore__text">没有更多了</text>
                </view>
            </view>
        </template>
    </view>
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
import { navigate } from '@/utils/link'

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

const currentList = computed(() => (activeTab.value === 'staff' ? staffList.value : articleList.value))

function countOf(key: SearchTab) {
    return key === 'staff' ? staffTotal.value : articleTotal.value
}

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
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
        page: page.value,
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
    }
    if (!hasMore.value && !reset) return

    loadingMore.value = true
    try {
        if (activeTab.value === 'staff') await fetchStaff(reset)
        else await fetchArticle(reset)
    } catch {
        if (reset) error.value = true
    } finally {
        loadingMore.value = false
        loading.value = false
    }
}

function handleSearch() {
    const word = keyword.value.trim()
    if (!word) {
        uni.showToast({ title: '请输入关键词', icon: 'none' })
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
    navigate({ path: '/packages/pkg-catalog/pages/staff-detail/staff-detail', query: { id: item.id } })
}

function goArticle(item: any) {
    navigate({ path: '/packages/pkg-catalog/pages/article-detail/article-detail', query: { id: item.id } })
}

onLoad((options: any) => {
    loadHistory()
    loadHot()
    if (options?.keyword) {
        keyword.value = String(options.keyword)
        handleSearch()
    }
})

onReachBottom(() => {
    if (searched.value) runQuery(false)
})
</script>

<style lang="scss" scoped>
.search-page {
    min-height: 100vh;
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
        min-width: 0;
        height: 72rpx;
        padding: 0 var(--sp-1) 0 var(--sp-3);
        border: 1rpx solid var(--c-line);
        border-radius: var(--r-pill);
        background: var(--c-surface-2);
        box-sizing: border-box;
    }

    &__input {
        flex: 1;
        min-width: 0;
        height: 68rpx;
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
        height: 68rpx;

        &::before {
            content: '';
            position: absolute;
            top: -10rpx;
            right: 0;
            bottom: -10rpx;
            left: 0;
        }
    }

    &__cancel {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        min-width: 72rpx;
        min-height: 88rpx;
        margin: -8rpx 0;
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

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 64rpx;
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: 500;
        color: var(--c-ink);
    }

    &__action {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex-shrink: 0;
        min-width: 88rpx;
        min-height: 88rpx;
        margin: -12rpx 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__hint {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.chips {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
}

.chip {
    display: flex;
    align-items: center;
    max-width: 100%;
    min-height: 56rpx;
    box-sizing: border-box;
    padding: var(--sp-1) var(--sp-3);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-pill);
    background: var(--c-surface-2);

    &__text {
        display: block;
        max-width: 100%;
        overflow: hidden;
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        line-height: 1.4;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}

.tabs {
    position: sticky;
    top: 0;
    z-index: 10;
    display: flex;
    gap: var(--sp-5);
    padding: 0 var(--sp-4);
    background: var(--c-surface);
    border-bottom: 1rpx solid var(--c-line);

    &__item {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--sp-1);
        min-width: 88rpx;
        min-height: 88rpx;

        &::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            width: 40rpx;
            height: 4rpx;
            border-radius: var(--r-pill);
            background: transparent;
            transform: translateX(-50%);
        }

        &.is-active::after {
            background: var(--c-brand);
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

    &__item.is-active &__text {
        color: var(--c-brand-text);
        font-weight: 500;
    }

    &__item.is-active &__count {
        color: var(--c-brand-text);
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
        width: 100rpx;
        height: 120rpx;
        border-radius: var(--r-sm);
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
        font-size: var(--fs-md);
        font-weight: 500;
        color: var(--c-ink);
    }

    &__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: var(--sp-1) var(--sp-2);
    }

    &__rating {
        font-size: var(--fs-xs);
        color: var(--c-warning);
    }

    &__muted {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__profile {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        line-height: 1.5;
    }

    &__arrow {
        flex-shrink: 0;
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
        line-height: 1.5;
    }

    &__meta {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: 1.4;
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
