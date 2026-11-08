<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell title="服务人员" bg="page" :show-back="true">
        <view class="page-wrap list-page">
            <!-- 筛选栏：日期 / 分类 / 排序 三组条件常驻，改条件不必退回上一页 -->
            <view class="filters">
                <view class="filters__row">
                    <picker mode="date" :value="date" :start="today" @change="onDateChange">
                        <view
                            hover-class="is-pressed"
                            class="filters__chip"
                            :class="{ 'is-active': !!date }"
                        >
                            <text class="filters__text">{{ date || '日期' }}</text>
                            <!-- v1 u-icon → v2 tn-icon：arrow-down → down-arrow -->
                            <tn-icon name="down-arrow" :size="24" color="var(--c-ink-3)" />
                        </view>
                    </picker>

                    <picker mode="selector" :range="categoryLabels" @change="onCategoryChange">
                        <view
                            hover-class="is-pressed"
                            class="filters__chip"
                            :class="{ 'is-active': categoryId > 0 }"
                        >
                            <text class="filters__text ellipsis">{{ categoryName }}</text>
                            <!-- v1 u-icon → v2 tn-icon：arrow-down → down-arrow -->
                            <tn-icon name="down-arrow" :size="24" color="var(--c-ink-3)" />
                        </view>
                    </picker>

                    <view
                        hover-class="is-pressed"
                        class="filters__chip"
                        :class="{ 'is-active': sort !== 'default' }"
                        @tap="openSort"
                    >
                        <text class="filters__text">{{ sortLabel }}</text>
                        <!-- v1 u-icon → v2 tn-icon：arrow-down → down-arrow -->
                        <tn-icon name="down-arrow" :size="24" color="var(--c-ink-3)" />
                    </view>

                    <view class="filters__meta">
                        <text class="filters__count">{{ total }} 位</text>
                    </view>
                </view>
            </view>

            <view v-if="firstLoading" class="page-pad">
                <base-skeleton :rows="4" />
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
                @retry="reload"
            />

            <base-empty
                v-else-if="!list.length"
                status="empty"
                empty-icon="empty-search"
                :empty-text="FB.EMPTY_STAFF"
                :desc="FB.EMPTY_HINT_STAFF"
                action-text="去查档期"
                :show-action="true"
                fixed
                @action="goQuery"
            />

            <view v-else class="page-pad cards">
                <view
                    hover-class="is-pressed"
                    v-for="item in list"
                    :key="item.id"
                    class="card"
                    @tap="goDetail(item)"
                >
                    <image class="card__avatar" :src="imageUrl(item.avatar)" mode="aspectFill" />

                    <view class="card__body">
                        <view class="card__head">
                            <text class="card__name ellipsis">{{ item.name }}</text>
                            <view v-if="item.is_recommend" class="card__badge">
                                <text class="card__badge-text">推荐</text>
                            </view>
                            <!-- 档期状态：原来用 ○/● 字符当图标（v1 写法，且只靠单一编码）。
                                 换成 status-tag 后是「形状(dot) + 颜色(tone) + 文字」三重编码。
                                 :dot="true" —— 这里表达「档期是否成立」，是状态编码；
                                 与 message/detail 类型标签的 :dot="false"（分类不是状态）是两种用法，
                                 不要为「统一」改成 false。 -->
                            <status-tag
                                v-if="availOf(item)"
                                class="card__avail"
                                :tone="availOf(item)!.tone"
                                :label="availOf(item)!.label"
                                :dot="true"
                            />
                        </view>

                        <view class="card__meta">
                            <text v-if="item.rating" class="card__rating">★ {{ item.rating }}</text>
                            <text v-if="item.order_count" class="card__dot">·</text>
                            <text v-if="item.order_count" class="card__muted"
                                >{{ item.order_count }} 单</text
                            >
                            <text v-if="item.experience_years" class="card__dot">·</text>
                            <text v-if="item.experience_years" class="card__muted"
                                >{{ item.experience_years }} 年</text
                            >
                        </view>

                        <text v-if="item.profile" class="card__profile ellipsis-2">{{
                            item.profile
                        }}</text>

                        <view class="card__foot">
                            <view class="card__price">
                                <price
                                    size="md"
                                    v-if="item.has_price !== 0"
                                    :content="item.price ?? 0"
                                />
                                <text v-else class="card__muted">价格待定</text>
                            </view>
                            <view class="card__actions">
                                <view
                                    class="card__fav"
                                    :aria-label="item.is_favorite ? '取消收藏' : '收藏'"
                                    aria-role="button"
                                    @tap.stop="toggleFav(item)"
                                >
                                    <!-- v1 u-icon → v2 tn-icon：收藏语义由星改心（star→like） -->
                                    <tn-icon
                                        :name="item.is_favorite ? 'like-fill' : 'like'"
                                        :size="32"
                                        :color="
                                            item.is_favorite
                                                ? 'var(--c-brand-text)'
                                                : 'var(--c-ink-3)'
                                        "
                                    />
                                </view>
                            </view>
                        </view>
                    </view>
                </view>

                <load-more :status="loadMoreStatus" @retry="fetchPage(false)" />
            </view>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 人员列表。
 *
 * 分页风格 A（page_no + dataLists），由 normalizePage 统一成 Paginated。
 * 收藏接口读的是 id 字段（后端 toggleFavorite 用 $this->request->post('id/d')），不是 staff_id。
 */
import { computed, ref } from 'vue'
import { onLoad, onPullDownRefresh, onReachBottom } from '@dcloudio/uni-app'
import { getStaffList, toggleStaffFavorite, getCategoryTree } from '@/api/catalog'
import { normalizePage } from '@/request'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { navigate } from '@/utils/link'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { FB } from '@/constants/feedback'

// 排序值必须与后端 StaffLists::getOrderRaw 的 switch 分支一致，
// 传错会静默落到 default（综合），用户会以为排序坏了。
const SORTS = [
    { key: 'default', label: '综合' },
    { key: 'rating', label: '好评' },
    { key: 'order_count', label: '热门' },
    { key: 'price_asc', label: '价格' }
]

const appStore = useAppStore()
const userStore = useUserStore()

const date = ref('')
const categoryId = ref(0)
const keyword = ref('')
const sort = ref('default')

const categories = ref<any[]>([])
const today = new Date().toISOString().slice(0, 10)

const categoryLabels = computed(() => ['全部分类', ...categories.value.map((c: any) => c.name)])
const categoryName = computed(() => {
    const hit = categories.value.find((c: any) => Number(c.id) === Number(categoryId.value))
    return hit ? hit.name : '分类'
})
const sortLabel = computed(() => SORTS.find((s) => s.key === sort.value)?.label || '排序')

const list = ref<any[]>([])
const page = ref(1)
const pageSize = ref(10)
const total = ref(0)
const hasMore = ref(true)
const loadingMore = ref(false)
const firstLoading = ref(true)
const error = ref(false)

/** O5：保存真实错误对象，由 resolveErrorView 驱动 errorKind / 文案 / 恢复动作 */
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        reload()
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

/** LoadMore 四态（受控）：idle 静默 / loading / nomore / error 可点重试 */
const loadMoreFailed = ref(false)
const loadMoreStatus = computed<'idle' | 'loading' | 'nomore' | 'error'>(() => {
    if (loadMoreFailed.value) return 'error'
    if (loadingMore.value && list.value.length) return 'loading'
    if (!hasMore.value) return 'nomore'
    return 'idle'
})

/**
 * 档期状态（施工单 §2-2 三态映射：success 可预约 / warning 仅剩 1 档 / neutral 当日不可约）。
 *
 * ⚠️ 当前契约只有 `schedule_available` 布尔字段，**没有"剩余档数"**，所以「仅剩 1 档」
 * 这一态暂时不可达。后端补上剩余数后，在此加一条判断即可 ——
 * 不要为了凑三态去猜别的字段，那是编数据。
 * 字段缺失时返回 null（不渲染标签）：没数据不等于可约。
 */
function availOf(item: any): { tone: 'success' | 'warning' | 'neutral'; label: string } | null {
    if (item?.schedule_available === false) return { tone: 'neutral', label: '当日不可约' }
    if (item?.schedule_available === true) return { tone: 'success', label: '可预约' }
    return null
}

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

async function fetchPage(reset = false) {
    if (reset) {
        page.value = 1
        hasMore.value = true
        error.value = false
        loadMoreFailed.value = false
    }
    if (!hasMore.value && !reset) return

    loadingMore.value = true
    loadMoreFailed.value = false
    try {
        const res = await getStaffList({
            page_no: page.value,
            page_size: pageSize.value,
            date: date.value || undefined,
            category_id: categoryId.value || undefined,
            keyword: keyword.value || undefined,
            sort: sort.value
        })
        const paged = normalizePage<any>(res, page.value, pageSize.value)
        list.value = reset ? paged.list : [...list.value, ...paged.list]
        total.value = paged.total
        pageSize.value = paged.pageSize
        hasMore.value = paged.hasMore
        if (hasMore.value) page.value += 1
    } catch (e) {
        rawError.value = e
        if (reset) error.value = true
        else loadMoreFailed.value = true
    } finally {
        loadingMore.value = false
        firstLoading.value = false
        uni.stopPullDownRefresh()
    }
}

function reload() {
    firstLoading.value = true
    fetchPage(true)
}

/** 条件变化：清空旧列表、回到顶部、重新拉第一页 */
function reloadFromTop() {
    list.value = []
    uni.pageScrollTo({ scrollTop: 0, duration: 0 })
    reload()
}

function changeSort(key: string) {
    if (key === sort.value) return
    sort.value = key
    reloadFromTop()
}

function openSort() {
    uni.showActionSheet({
        itemList: SORTS.map((s) => s.label),
        success: (res) => {
            const target = SORTS[res.tapIndex]
            if (target) changeSort(target.key)
        }
    })
}

function onDateChange(e: any) {
    date.value = String(e?.detail?.value || '')
    reloadFromTop()
}

function onCategoryChange(e: any) {
    const idx = Number(e?.detail?.value || 0)
    categoryId.value = idx === 0 ? 0 : Number(categories.value[idx - 1]?.id || 0)
    reloadFromTop()
}

async function loadCategories() {
    try {
        const res = await getCategoryTree()
        categories.value = Array.isArray(res) ? res : []
    } catch {
        // 分类失败不影响列表，筛选条降级为只有日期与排序
        categories.value = []
    }
}

async function toggleFav(item: any) {
    if (!userStore.isLogin) {
        navigate({ path: '/pages/login/login' })
        return
    }
    try {
        const res = await toggleStaffFavorite({ id: item.id })
        const next =
            res && typeof res.is_favorite !== 'undefined'
                ? Number(res.is_favorite)
                : item.is_favorite
                ? 0
                : 1
        item.is_favorite = next
    } catch {
        // 请求层已提示
    }
}

function goDetail(item: any) {
    navigate({
        path: '/packages/pkg-catalog/pages/staff-detail/staff-detail',
        query: { id: item.id, date: date.value }
    })
}

function goQuery() {
    navigate({ path: '/pages/query/query' })
}

onLoad((options: any) => {
    date.value = options?.date ? String(options.date) : ''
    categoryId.value = Number(options?.category_id || 0)
    keyword.value = options?.keyword ? String(options.keyword) : ''
    loadCategories()
    fetchPage(true)
})

onPullDownRefresh(() => fetchPage(true))
onReachBottom(() => fetchPage(false))
</script>

<style lang="scss" scoped>
.list-page {
    background: var(--c-bg);
}

.filters {
    padding: var(--sp-2) var(--sp-4);
    background: var(--c-surface);
    position: sticky;
    top: 0;
    z-index: 50; /* 必须 < nav-bar 的 99（体验规范 §2.5） */
    border-bottom: 1rpx solid var(--c-line);

    &__row {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__chip {
        display: flex;
        align-items: center;
        gap: 4rpx;
        max-width: 220rpx;
        /* P0 §2-2：对齐 chip 视觉规格（高 56rpx / --r-pill / --c-control-border 边 / 选中 --c-ink 底）。
           仍是 picker 触发器 —— chip-group 是纯受控单选，唤不起日期/分类选择器，换过去会坏功能。 */
        height: 56rpx;
        padding: 0 var(--sp-2);
        box-sizing: border-box;
        border-radius: var(--r-pill);
        background: var(--c-surface-2);
        border: 1rpx solid var(--c-control-border); /* 控件边界 ≥3:1 */

        &.is-active {
            background: var(--c-ink);
            border-color: var(--c-ink);
        }
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        max-width: 160rpx;
    }

    &__chip.is-active &__text {
        /* 批 1.5 B：底色是 --c-ink，文字原 --c-brand-text 只有 3.38:1（金墨压墨黑，发灰）。
           改 --c-on-dark 后 18.05:1；与 complaint-create / apply-pause / profile / publish
           的选中 chip 口径一致。 */
        color: var(--c-on-dark);
        font-weight: 500;
    }

    &__meta {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__date {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
    }

    &__count {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.cards {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding-top: var(--sp-3);
}

.card {
    display: flex;
    gap: var(--sp-3);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-md); /* 卡片圆角回正 */
    padding: var(--sp-3);

    &__avatar {
        /* P0 §2-2：竖图 180×220 → v2 84rpx 圆形，与搜索结果页同一版式（同一实体，不能两套） */
        width: 84rpx;
        height: 84rpx;
        border-radius: var(--r-pill);
        background: var(--c-neutral-bg);
        flex-shrink: 0;
    }

    &__body {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8rpx;
        min-width: 0;
    }

    &__head {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    /* 档期状态标签：由 status-tag 自带样式，这里只负责靠右。
       旧的 .is-on/.is-off 文字样式已随 ○/● 字符一起删除（字符当图标是 v1 写法）。 */
    &__avail {
        margin-left: auto;
        flex-shrink: 0;
    }

    &__name {
        font-size: var(--fs-lg);
        font-weight: 500;
        color: var(--c-ink);
        max-width: 60%;
    }

    &__badge {
        padding: 2rpx 10rpx;
        border-radius: var(--r-xs);
        background: var(--c-brand-bg);
    }

    &__badge-text {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
    }

    &__meta {
        display: flex;
        align-items: center;
        gap: 6rpx;
    }

    &__rating {
        font-size: var(--fs-xs);
        color: var(--c-warning);
    }

    &__dot {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__muted {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__profile {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        line-height: var(--lh-body);
    }

    &__foot {
        margin-top: auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    &__actions {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__fav {
        width: 88rpx;
        height: 88rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin: calc(-1 * var(--sp-2));
    }
}
</style>
