<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell
        :title="fromQuery ? '可约人员' : '服务人员'"
        bg="page"
        :show-back="true"
        :pull-refresh="true"
        @refresh="onRefresh"
        @reach-bottom="onReachBottomLoad"
    >
        <view class="page-wrap list-page">
            <!-- 来自查档期：条件只读；上下文条吸顶，改条件回主页面（唯一条件真源） -->
            <view v-if="fromQuery" class="ctx">
                <view class="ctx__row">
                    <text class="ctx__where">{{ summaryText || '可约人员' }}</text>
                    <view
                        hover-class="is-pressed"
                        class="ctx__change hit-88"
                        aria-role="button"
                        @tap="goBack"
                    >
                        <text class="ctx__change-text">改条件</text>
                        <tn-icon name="right" :size="18" class="ctx__change-ic" />
                    </view>
                </view>
                <view class="ctx__row ctx__row--meta">
                    <view class="ctx__count">
                        <view v-if="firstLoading" class="ctx__sk" />
                        <template v-else-if="error">
                            <text class="ctx__num">—</text>
                            <text class="ctx__unit">未加载</text>
                        </template>
                        <template v-else>
                            <text class="ctx__num t-num">{{ total }}</text>
                            <text class="ctx__unit">{{ countUnit }}</text>
                        </template>
                    </view>
                    <view
                        hover-class="is-pressed"
                        class="ctx__sort hit-88"
                        aria-role="button"
                        @tap="openSort"
                    >
                        <text class="ctx__sort-text">{{ sortLabel }}</text>
                        <tn-icon name="down-arrow" :size="18" class="ctx__sort-ic" />
                    </view>
                </view>
            </view>

            <!-- 筛选栏：日期 / 分类 / 排序 三组条件常驻，改条件不必退回上一页 -->
            <view v-if="!fromQuery" class="filters">
                <view class="filters__row">
                    <!-- 横向滚动留出尾部缓冲：窄屏时 chip 不被计数挤压，也不产生死区 -->
                    <scroll-view scroll-x class="filters__scroll" :show-scrollbar="false">
                        <view class="filters__chips">
                            <picker
                                mode="date"
                                :value="date"
                                :start="today"
                                @change="onDateChange"
                            >
                                <view
                                    hover-class="is-pressed"
                                    class="filters__chip hit-88"
                                    :class="{ 'is-active': !!date }"
                                    @tap="onFilterTap"
                                >
                                    <text class="filters__text">{{ date || '日期' }}</text>
                                    <!-- v1 u-icon → v2 tn-icon：arrow-down → down-arrow -->
                                    <tn-icon name="down-arrow" :size="24" class="ic-ink-3" />
                                </view>
                            </picker>

                            <picker
                                mode="selector"
                                :range="categoryLabels"
                                @change="onCategoryChange"
                            >
                                <view
                                    hover-class="is-pressed"
                                    class="filters__chip hit-88"
                                    :class="{ 'is-active': categoryId > 0 }"
                                    @tap="onFilterTap"
                                >
                                    <text class="filters__text ellipsis">{{ categoryName }}</text>
                                    <!-- v1 u-icon → v2 tn-icon：arrow-down → down-arrow -->
                                    <tn-icon name="down-arrow" :size="24" class="ic-ink-3" />
                                </view>
                            </picker>

                            <view
                                hover-class="is-pressed"
                                class="filters__chip hit-88"
                                :class="{ 'is-active': sort !== 'default' }"
                                @tap="openSort"
                            >
                                <text class="filters__text">{{ sortLabel }}</text>
                                <!-- v1 u-icon → v2 tn-icon：arrow-down → down-arrow -->
                                <tn-icon name="down-arrow" :size="24" class="ic-ink-3" />
                            </view>
                        </view>
                    </scroll-view>

                    <view class="filters__meta">
                        <text class="filters__count t-num">{{ countLabel }}</text>
                    </view>
                </view>
                <view v-if="hasPresetExtraFilters" class="filters__extra">
                    <view class="filters__extra-tags">
                        <text v-if="districtName || cityName" class="filters__extra-pill">
                            地区：{{ districtName || cityName }}
                        </text>
                        <text v-if="keyword.trim()" class="filters__extra-pill">
                            关键词：{{ keyword.trim() }}
                        </text>
                        <text v-if="tagIds.length" class="filters__extra-pill">
                            风格标签 ×{{ tagIds.length }}
                        </text>
                    </view>
                    <view
                        class="filters__extra-clear hit-88"
                        aria-role="button"
                        @tap="clearExtraFilters"
                    >
                        <text class="filters__extra-clear-text">清空</text>
                    </view>
                </view>
            </view>

            <view v-if="firstLoading" :class="fromQuery ? 'qsk-wrap' : 'page-pad'">
                <view v-if="fromQuery" class="qgrid">
                    <view v-for="n in 4" :key="n" class="qcard qcard--sk">
                        <view class="qcard__media" />
                        <view class="qcard__info qcard__info--sk">
                            <view class="qsk-line qsk-line--title" />
                            <view class="qsk-line qsk-line--short" />
                            <view class="qsk-line qsk-line--wide" />
                        </view>
                    </view>
                </view>
                <base-skeleton v-else variant="card" :rows="4" />
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

            <view v-else-if="fromQuery && !list.length" class="qempty">
                <tn-icon name="empty-search" :size="96" class="qempty__icon" aria-hidden="true" />
                <text class="qempty__title">{{ emptyTitle }}</text>
                <text class="qempty__desc">{{ emptyDesc }}</text>
                <view hover-class="is-pressed" class="qempty__btn" aria-role="button" @tap="goBack">
                    <text class="qempty__btn-text">{{ emptyAction }}</text>
                </view>
            </view>

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

            <view v-else-if="fromQuery">
                <view class="qgrid">
                    <view
                        v-for="item in list"
                        :key="item.id"
                        class="qcard"
                        hover-class="is-pressed"
                        :aria-label="cardAria(item)"
                        aria-role="button"
                        @tap="goDetail(item)"
                    >
                        <view class="qcard__media">
                            <image
                                v-if="showAvatar(item)"
                                class="qcard__img"
                                :src="imageUrl(item.avatar)"
                                :alt="item.name"
                                mode="aspectFill"
                                @error="onAvatarError(item.id)"
                            />
                            <view v-else class="qcard__fallback">
                                <text class="qcard__initial">{{ initialOf(item.name) }}</text>
                                <text class="qcard__noimg">暂无照片</text>
                            </view>
                            <view v-if="item.is_recommend" class="qcard__rec">
                                <tn-icon name="star-fill" :size="16" class="qcard__rec-ic" />
                                <text class="qcard__rec-text">推荐</text>
                            </view>
                            <!-- 收藏：和详情页同一接口，状态直接读列表下发的 is_favorite -->
                            <view
                                class="qcard__fav hit-88"
                                :class="{ 'is-on': item.is_favorite }"
                                hover-class="is-pressed"
                                aria-role="button"
                                :aria-label="item.is_favorite ? '取消收藏' : '收藏'"
                                @tap.stop="toggleFav(item)"
                            >
                                <tn-icon
                                    :name="item.is_favorite ? 'like-fill' : 'like'"
                                    :size="30"
                                    class="qcard__fav-ic"
                                />
                            </view>
                        </view>

                        <view class="qcard__info">
                            <view class="qcard__name-row">
                                <text class="qcard__name">{{ item.name }}</text>
                                <view
                                    v-if="availabilityOf(item)"
                                    class="qcard__status"
                                    :class="{ 'is-warn': availabilityOf(item)?.warn }"
                                >
                                    <view class="qcard__dot" />
                                    <text class="qcard__status-text">{{
                                        availabilityOf(item)?.label
                                    }}</text>
                                </view>
                            </view>

                            <view class="qcard__meta">
                                <text class="qcard__role">{{ roleText(item) }}</text>
                            <view v-if="hasPrice(item)" class="qcard__price">
                                <text class="qcard__price-num t-num">{{ priceText(item) }}</text>
                                <text class="qcard__price-unit">起</text>
                            </view>
                            </view>

                            <text class="qcard__intro">{{ introText(item) }}</text>
                        </view>
                    </view>
                </view>
                <load-more :status="loadMoreStatus" @retry="fetchPage(false)" />
            </view>

            <view v-else class="page-pad cards">
                <view
                    hover-class="is-pressed"
                    v-for="item in list"
                    :key="item.id"
                    class="card"
                    :class="{ 'card--featured': item.is_recommend }"
                    @tap="goDetail(item)"
                >
                    <view class="card__top">
                        <view class="card__avatar">
                            <image
                                v-if="imageUrl(item.avatar)"
                                class="card__avatar-img"
                                :src="imageUrl(item.avatar)"
                                mode="aspectFill"
                            />
                            <text v-else class="card__avatar-letter">{{
                                initialOf(item.name)
                            }}</text>
                        </view>

                        <view class="card__info">
                            <view class="card__head">
                                <text class="card__name ellipsis">{{ item.name }}</text>
                                <view v-if="item.is_recommend" class="card__badge">
                                    <text class="card__badge-text">推荐</text>
                                </view>
                                <!-- 档期角标：形状(dot)+颜色(tone)+文字三重编码；满档的人不会出现在列表里 -->
                                <status-tag
                                    v-if="scheduleBadge(item)"
                                    class="card__avail"
                                    :tone="scheduleBadge(item)!.tone"
                                    :label="scheduleBadge(item)!.label"
                                    :dot="true"
                                />
                            </view>

                            <view class="card__meta">
                                <text v-if="item.rating" class="card__rating t-num"
                                    >★ {{ ratingText(item.rating) }}</text
                                >
                                <text v-if="item.rating && item.order_count" class="card__sep"
                                    >·</text
                                >
                                <text v-if="item.order_count" class="card__muted t-num"
                                    >{{ item.order_count }} 单</text
                                >
                                <text
                                    v-if="item.order_count && item.experience_years"
                                    class="card__sep"
                                    >·</text
                                >
                                <text v-if="item.experience_years" class="card__muted t-num"
                                    >{{ item.experience_years }} 年</text
                                >
                            </view>

                            <!-- 有标签优先展示标签（可扫读）；简介独立成通栏，不再挤在头像右侧 -->
                            <view v-if="tagsOf(item).length" class="card__tags">
                                <text v-for="t in tagsOf(item)" :key="t" class="card__tag">{{
                                    t
                                }}</text>
                            </view>
                        </view>
                    </view>

                    <!-- 通栏简介：列表页简介优先，未填且无标签时才回退详细简介 -->
                    <view v-if="cardIntroText(item)" class="card__intro-wrap">
                        <text class="card__intro">{{ cardIntroText(item) }}</text>
                    </view>

                    <view class="card__foot">
                        <view class="card__price">
                            <template v-if="hasPrice(item)">
                                <price size="md" :content="item.price" />
                                <text class="card__price-unit">起 / 场</text>
                            </template>
                            <text v-else class="card__muted">价格待定</text>
                        </view>

                        <view class="card__acts">
                            <view
                                class="card__fav hit-88"
                                :aria-label="item.is_favorite ? '取消收藏' : '收藏'"
                                aria-role="button"
                                @tap.stop="toggleFav(item)"
                            >
                                <tn-icon
                                    :name="item.is_favorite ? 'like-fill' : 'like'"
                                    :size="34"
                                    class="ic-fav"
                                    :class="{ 'is-on': item.is_favorite }"
                                />
                            </view>
                            <view class="card__link">
                                <text class="card__link-text">查看档期</text>
                                <tn-icon name="right" :size="20" class="ic-ink-3" />
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
import { onLoad } from '@dcloudio/uni-app'
import { getStaffList, toggleStaffFavorite, getCategoryTree } from '@/api/catalog'
import { normalizePage } from '@/request'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { navigate } from '@/utils/link'
import { haptic } from '@/utils/haptics'
import { regionParams, type RegionContext } from '@/utils/region'
import { saveBookingContext } from '@/utils/booking-context'
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

/**
 * 双模式：
 *  - 带 `from=query` 进入（来自查档期）→ 条件只读，展示摘要条 + 「改条件」返回；
 *  - 无参进入（首页四宫格「选主持」、搜索页、后台装修链接）→ 完全保留原有筛选器。
 * 用显式标记而不是「有没有参数」，否则装修链接 `?category_id=3` 会误入只读模式、picker 消失。
 */
const fromQuery = ref(false)
const provinceCode = ref('')
const provinceName = ref('')
const cityCode = ref('')
const cityName = ref('')
const districtCode = ref('')
const districtName = ref('')
const tagIds = ref<number[]>([])
const categoryNameRaw = ref('')
const summaryText = ref('')

const categories = ref<any[]>([])
const today = new Date().toISOString().slice(0, 10)

const categoryLabels = computed(() => ['全部分类', ...categories.value.map((c: any) => c.name)])
const categoryName = computed(() => {
    const hit = categories.value.find((c: any) => Number(c.id) === Number(categoryId.value))
    return hit ? hit.name : '分类'
})
// 可约人员页把「综合」叫「推荐排序」；全量人员页保持原「综合」文案
const sortLabel = computed(() => {
    if (fromQuery.value && sort.value === 'default') return '推荐排序'
    return SORTS.find((s) => s.key === sort.value)?.label || '排序'
})

/** 计数口径：有 date 才叫「可约」（date 是真过滤，见 StaffLists.php:453），无 date 只能叫「老师」 */
const countLabel = computed(() => (date.value ? `${total.value} 位可约` : `${total.value} 位老师`))
const countUnit = computed(() => (date.value ? '位可约' : '位老师'))

/** 空态文案：区分「筛得太窄」与「这天就是没人」，两种恢复路径不同 */
const hasNarrowFilters = computed(
    () => !!categoryId.value || tagIds.value.length > 0 || !!keyword.value.trim()
)
const hasPresetExtraFilters = computed(
    () =>
        !fromQuery.value &&
        (!!keyword.value.trim() || !!districtName.value || !!cityName.value || tagIds.value.length > 0)
)
const emptyTitle = computed(() =>
    hasNarrowFilters.value ? '当前条件下没有可约人员' : '这天暂时没有可约人员'
)
const emptyDesc = computed(() =>
    hasNarrowFilters.value ? '回查档期放宽风格或关键词试试' : '换个日期或放宽条件试试'
)
const emptyAction = computed(() =>
    hasNarrowFilters.value ? '回查档期改条件' : '回查档期换日期'
)

const list = ref<any[]>([])
const page = ref(1)
const pageSize = ref(10)
const total = ref(0)
const hasMore = ref(true)
const loadingMore = ref(false)
const firstLoading = ref(true)
const error = ref(false)
/** 头像加载失败：按 staff id 记，失败后退回姓氏占位，不出现破图 */
const imgError = ref<Record<number, boolean>>({})

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
 * 档期角标：只有两态。
 *
 * `date` 已由服务端过滤（StaffLists.php:453 的 `!Schedule::isAvailable()` 直接 continue），
 * 所以列表里的每个人都是 available=true，「当日已满」不可能出现在这里 —— 写那个分支就是死代码。
 * `remaining` 是「剩余可接单数」，默认上限 1，所以只在剩 1 档时提示才有决策价值。
 */
function scheduleBadge(item: any): { tone: 'success' | 'warning'; label: string } | null {
    if (!date.value) return null
    const remaining = Number(item?.schedule?.remaining)
    if (!Number.isFinite(remaining) || remaining <= 0) return null
    if (remaining === 1) return { tone: 'warning', label: '仅剩 1 档' }
    return null
}

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

/** 无头像时用首字占位：比空白圆更像「有人」，也不需要额外图片资源 */
function initialOf(name?: string) {
    const s = (name || '').trim()
    return s ? [...s][0] : '老'
}

function ratingText(v: any) {
    const n = Number(v) || 0
    return n > 0 ? n.toFixed(1) : ''
}

/** 标签最多 3 个：卡片是扫读场景，多了会挤掉名字与价格 */
function tagsOf(item: any) {
    const tags = item?.tags
    return Array.isArray(tags) ? tags.filter(Boolean).slice(0, 3) : []
}

/** 价格为 0 或缺失都按「待定」处理——¥0 不是价格，是脏数据 */
function hasPrice(item: any) {
    return item?.has_price !== 0 && Number(item?.price) > 0
}

/**
 * 双列卡的档期状态。
 *
 * 列表本身已由服务端按 date 过滤（满档的人不会出现），所以有 date 时默认就是「可约」；
 * 只有 remaining === 1 才升级成「仅 1 档」的琥珀提示。
 * remaining 缺失不代表不可约，不能因此把状态藏掉。
 */
function availabilityOf(item: any): { warn: boolean; label: string } | null {
    if (!date.value) return null
    const remaining = Number(item?.schedule?.remaining)
    if (Number.isFinite(remaining)) {
        if (remaining <= 0) return null
        if (remaining === 1) return { warn: true, label: '仅 1 档' }
    }
    return { warn: false, label: '可约' }
}

/** 卡片的「身份」：分类名；分类未加载或缺失时退回「服务人员」，不空一行 */
function categoryNameOf(item: any) {
    const id = Number(item?.category_id)
    if (!id) return ''
    // 分类树没加载出来时，用查档期带过来的分类名兜底（只对同分类的人员生效）
    if (id === Number(categoryId.value) && categoryNameRaw.value) return categoryNameRaw.value
    const hit = categories.value.find((c: any) => Number(c.id) === id)
    return hit?.name ? String(hit.name) : ''
}

function roleText(item: any) {
    const name = categoryNameOf(item) || '服务人员'
    const years = Number(item?.experience_years)
    return years > 0 ? `${name} · ${years} 年` : name
}

/** 列表页简介优先；为空再回退个人简介；仍为空时用风格标签拼成纯文本，不换成 chip */
function introText(item: any) {
    const listIntro = String(item?.list_intro || '').trim()
    if (listIntro) return listIntro
    const profile = String(item?.profile || '').trim()
    if (profile) return profile
    const tags = tagsOf(item)
    return tags.length ? `擅长${tags.join('、')}` : ''
}

/**
 * 单列人员卡的通栏简介。
 * list_intro 是列表专用短文案，只要填了就展示（无论是否有标签）；
 * 未填且该人员已有标签时返回空串——标签已能扫读，不把 500 字详细简介堆到卡上。
 */
function cardIntroText(item: any) {
    const listIntro = String(item?.list_intro || '').trim()
    if (listIntro) return listIntro
    if (tagsOf(item).length) return ''
    return String(item?.profile || '').trim()
}

function formatAmount(n: number) {
    return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

function priceText(item: any) {
    return `¥${formatAmount(Number(item?.price))}`
}

function showAvatar(item: any) {
    return !!imageUrl(item.avatar) && !imgError.value[item.id]
}

function onAvatarError(id: number) {
    imgError.value[id] = true
}

function cardAria(item: any) {
    const bits = [String(item?.name || '服务人员'), roleText(item)]
    const status = availabilityOf(item)
    if (status) bits.push(status.label)
    if (hasPrice(item)) bits.push(`${formatAmount(Number(item.price))} 元起`)
    return bits.join('，')
}

async function fetchPage(reset = false) {
    if (reset) {
        page.value = 1
        hasMore.value = true
        error.value = false
        loadMoreFailed.value = false
        // 重新拉第一页时清空头像失败记录，换图后能重新尝试
        imgError.value = {}
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
            /**
             * 地区必须三级同进同退：后端 hasRegionContext() 要求 city_code 与 district_code
             * 同时非空（PackageRegionPriceService.php:225-231），缺区县时异常被 catch 成空 context
             * → 界面写杭州、实际查全国、零报错。
             */
            province_code: provinceCode.value || undefined,
            city_code: cityCode.value || undefined,
            district_code: districtCode.value || undefined,
            tag_ids: tagIds.value.length ? tagIds.value.join(',') : undefined,
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
    haptic('light', 'staff-sort')
    uni.showActionSheet({
        itemList: SORTS.map((s) =>
            fromQuery.value && s.key === 'default' ? '推荐排序' : s.label
        ),
        success: (res) => {
            const target = SORTS[res.tapIndex]
            if (target) changeSort(target.key)
        }
    })
}

function onDateChange(e: any) {
    date.value = String(e?.detail?.value || '')
    saveBookingContext({ date: date.value })
    reloadFromTop()
}

/** 三个 picker 触发器共用一处 light 触觉：连点由 haptic 内部 300ms 节流兜底 */
function onFilterTap() {
    haptic('light', 'staff-filter')
}

function onCategoryChange(e: any) {
    const idx = Number(e?.detail?.value || 0)
    categoryId.value = idx === 0 ? 0 : Number(categories.value[idx - 1]?.id || 0)
    reloadFromTop()
}

function clearExtraFilters() {
    keyword.value = ''
    provinceCode.value = ''
    provinceName.value = ''
    cityCode.value = ''
    cityName.value = ''
    districtCode.value = ''
    districtName.value = ''
    tagIds.value = []
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
    haptic('light', 'staff-fav')
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

/** 300ms 内只响应一次：双列卡面积大，连点很容易连开两个详情页 */
let lastNavAt = 0

/** 当前列表的地区六字段：进详情/写预约上下文共用一份，避免字段漏传 */
function currentRegion(): RegionContext {
    return {
        province_code: provinceCode.value,
        province_name: provinceName.value,
        city_code: cityCode.value,
        city_name: cityName.value,
        district_code: districtCode.value,
        district_name: districtName.value
    }
}

function goDetail(item: any) {
    const now = Date.now()
    if (now - lastNavAt < 300) return
    lastNavAt = now
    haptic('light', 'staff-open')
    saveBookingContext({ date: date.value, region: currentRegion() })
    // 地区六字段必须一起带走：详情页/预约/报价靠它复算同一个区县价，丢了就会退回全国价
    navigate({
        path: '/packages/pkg-catalog/pages/staff-detail/staff-detail',
        query: {
            id: item.id,
            ...(date.value ? { date: date.value } : {}),
            ...regionParams(currentRegion())
        }
    })
}

function goQuery() {
    navigate({ path: '/pages/query/query' })
}

/** onLoad 的 query 是否已被框架 decode，微信 / H5 行为不一致；只在含 %XX 时解一次，失败原样返回 */
function safeDecode(v: string) {
    if (!/%[0-9A-Fa-f]{2}/.test(v)) return v
    try {
        return decodeURIComponent(v)
    } catch {
        return v
    }
}

function weekdayOf(date: string) {
    const [y, m, d] = date.split('-').map(Number)
    const wd = new Date(y || 1970, (m || 1) - 1, d || 1).getDay()
    return ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][wd]
}

/** 只读摘要条：条件值用 · 分隔；风格不下钻标签名（已失效标签拿不到名字，硬拼会误导） */
function buildSummary() {
    const parts: string[] = []
    if (date.value) {
        const [, m, d] = date.value.split('-')
        parts.push(`${Number(m)}月${Number(d)}日 ${weekdayOf(date.value)}`)
    }
    if (districtName.value) parts.push(`${cityName.value}·${districtName.value}`)
    else if (cityName.value) parts.push(cityName.value)
    const cat =
        categoryNameRaw.value ||
        (categoryName.value && categoryName.value !== '分类' ? categoryName.value : '')
    if (cat) parts.push(cat)
    if (tagIds.value.length) parts.push(`风格等${tagIds.value.length}项`)
    if (keyword.value) parts.push(`「${keyword.value}」`)
    summaryText.value = parts.join(' · ')
}

/** 「改条件」= 回查档期主页面；装修链接直跳（栈里没有上一页）时兜底切 tab */
function goBack() {
    uni.navigateBack({
        fail: () => {
            uni.switchTab({ url: '/pages/query/query' })
        }
    })
}

onLoad((options: any) => {
    fromQuery.value = options?.from === 'query'

    date.value = options?.date ? String(options.date) : ''
    categoryId.value = Number(options?.category_id || 0)
    keyword.value = options?.keyword ? safeDecode(String(options.keyword)) : ''
    categoryNameRaw.value = options?.category_name ? safeDecode(String(options.category_name)) : ''

    // 地区三级六字段：省市区同进同退，缺任一项就不该带出去（否则后端会静默按全国查）
    provinceCode.value = options?.province_code ? String(options.province_code) : ''
    provinceName.value = options?.province_name ? safeDecode(String(options.province_name)) : ''
    cityCode.value = options?.city_code ? String(options.city_code) : ''
    cityName.value = options?.city_name ? safeDecode(String(options.city_name)) : ''
    districtCode.value = options?.district_code ? String(options.district_code) : ''
    districtName.value = options?.district_name ? safeDecode(String(options.district_name)) : ''

    tagIds.value = String(options?.tag_ids || '')
        .split(',')
        .map((s) => Number(s.trim()))
        .filter((n) => Number.isFinite(n) && n > 0)
        .slice(0, 5)

    // 列表是日期/地区的主要入口之一：进页即写入预约上下文，后续从详情/分享进入也能复用
    saveBookingContext({ date: date.value, region: currentRegion() })

    loadCategories().then(() => {
        if (fromQuery.value) buildSummary()
    })
    fetchPage(true)
})

/** page-shell 内置下拉刷新：无论同步/异步成功或异常，都必定调 done 收起刷新态 */
function onRefresh(done: () => void) {
    Promise.resolve(fetchPage(true)).finally(done)
}

/** page-shell 内置触底加载 */
function onReachBottomLoad() {
    fetchPage(false)
}
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

    /* 胶囊行横向滚动：尾部留 24rpx 缓冲，最后一个 chip 不会顶在计数上 */
    &__scroll {
        flex: 1;
        min-width: 0;
        white-space: nowrap;
    }

    &__chips {
        display: inline-flex;
        align-items: center;
        gap: var(--sp-2);
        padding-right: var(--sp-3);
    }

    &__chip {
        display: flex;
        align-items: center;
        gap: 4rpx;
        max-width: 220rpx;
        /* P0 §2-2：对齐 chip 视觉规格（高 56rpx / --r-pill / --c-control-border 边 / 选中 --c-ink 底）。
           仍是 picker 触发器 —— chip-group 是纯受控单选，唤不起日期/分类选择器，换过去会坏功能。
           热区由 .hit-88 扩到 88rpx（上下各溢出 16rpx，横向不越过 16rpx 间距）。 */
        height: 56rpx;
        padding: 0 var(--sp-2);
        box-sizing: border-box;
        border-radius: var(--r-pill);
        background: var(--c-surface-2);
        border: 1rpx solid var(--c-control-border); /* 控件边界 ≥3:1 */
        transition:
            background-color var(--dur-base) var(--ease-out),
            border-color var(--dur-base) var(--ease-out),
            box-shadow var(--dur-base) var(--ease-out);

        &.is-active {
            background: var(--c-active-bg);
            border-color: var(--c-active-border);
            /* 选中胶囊轻微浮起：深浅两套都靠 ink/金实底对比，不额外加色 */
            box-shadow: 0 6rpx 16rpx -8rpx rgba(var(--c-ink-rgb), 0.35);
        }
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        max-width: 160rpx;
    }

    &__chip.is-active &__text {
        color: var(--c-active-fg);
        font-weight: var(--fw-medium);
    }

    &__meta {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: var(--sp-2);
        flex: none;
    }

    &__date {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
    }

    &__count {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__extra {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-top: 12rpx;
        padding-top: 12rpx;
        border-top: 1rpx solid var(--c-line);
    }

    &__extra-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 10rpx;
        min-width: 0;
        flex: 1;
    }

    &__extra-pill {
        padding: 4rpx 14rpx;
        border-radius: var(--r-pill);
        background: var(--c-surface-2);
        border: 1rpx solid var(--c-control-border);
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__extra-clear {
        flex: none;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        min-width: 88rpx;
        min-height: 88rpx;
    }

    &__extra-clear-text {
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
    background: var(--c-surface);
    border: 1rpx solid var(--c-card-border);
    border-radius: var(--r-md);
    box-shadow: var(--shadow-card);
    padding: 26rpx;
    transition: box-shadow var(--dur-base) var(--ease-out);

    /* 推荐位比普通卡高一层：同宽同心圆角，只提升投影，不换底色 */
    &--featured {
        box-shadow: var(--shadow-card-hover);
    }

    &__top {
        display: flex;
        gap: 22rpx;
    }

    &__avatar {
        width: 104rpx;
        height: 104rpx;
        border-radius: var(--r-25);
        background: var(--c-brand-bg);
        border: 1rpx solid var(--c-line-strong);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
    }

    &__avatar-img {
        width: 100%;
        height: 100%;
    }

    &__avatar-letter {
        font-family: var(--font-serif);
        font-size: var(--fs-40);
        color: var(--c-brand-text);
    }

    &__info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 10rpx;
        padding-top: 2rpx;
    }

    &__head {
        display: flex;
        align-items: center;
        gap: 12rpx;
        min-width: 0;
    }

    /* 档期状态标签：由 status-tag 自带样式，这里只负责靠右 */
    &__avail {
        margin-left: auto;
        flex-shrink: 0;
    }

    &__name {
        font-size: var(--fs-31);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        max-width: 55%;
    }

    &__badge {
        padding: 2rpx 10rpx;
        border-radius: var(--r-xs);
        background: var(--c-brand);
        flex-shrink: 0;
    }

    &__badge-text {
        font-size: var(--fs-18);
        color: var(--c-ink);
    }

    &__meta {
        display: flex;
        align-items: center;
        gap: 6rpx;
    }

    &__rating {
        font-size: var(--fs-21);
        color: var(--c-warning);
    }

    &__sep {
        font-size: var(--fs-21);
        color: var(--c-line-strong);
    }

    &__muted {
        font-size: var(--fs-21);
        color: var(--c-ink-3);
    }

    &__tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8rpx;
    }

    &__tag {
        font-size: var(--fs-20);
        color: var(--c-ink-2);
        background: var(--c-neutral-bg);
        border-radius: var(--r-xs);
        padding: 6rpx 12rpx;
        line-height: 1.2;
    }

    /* 通栏简介：独占卡片全宽，36 字列表简介 1~2 行完整展示
       圆角取同心档：卡片 --r-md 32rpx − 内边距 26rpx ≈ 6rpx，落到 --r-xs */
    &__intro-wrap {
        margin-top: var(--sp-2);
        padding: 14rpx 18rpx;
        border-radius: var(--r-xs);
        background: var(--c-surface-2);
    }

    &__intro {
        display: -webkit-box;
        overflow: hidden;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        font-size: var(--fs-md);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
        white-space: normal;
        word-break: break-word;
    }

    &__foot {
        margin-top: 22rpx;
        padding-top: 20rpx;
        border-top: 1rpx solid var(--c-line);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    &__price {
        display: flex;
        align-items: baseline;
        gap: 6rpx;
    }

    &__price-unit {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }

    &__acts {
        display: flex;
        align-items: center;
        gap: 12rpx;
    }

    &__fav {
        width: 64rpx;
        height: 64rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    &__link {
        display: flex;
        align-items: center;
        gap: 2rpx;
    }

    &__link-text {
        font-size: var(--fs-21);
        color: var(--c-ink-2);
    }
}

/* tn-icon 颜色工具类（本地 scoped 版）：tn-icon 的 color prop 不认 var()，
   颜色必须靠 CSS 落在图标根节点上再由内部 <text> 继承。 */
.ic-ink-3 {
    color: var(--c-ink-3);
}

/* 动态图标颜色：状态由父级/自身的 is-on 表达，颜色留在 CSS（tn-icon 的 color prop 不认 var()） */
.ic-fav {
    color: var(--c-ink-3);

    /* 已收藏必须有明确状态：原实现只挂了 is-on 类却没写样式，图标永远是灰的 */
    &.is-on {
        color: var(--c-danger);
    }
}
/* 只读上下文条：日期/地区 + 计数/排序，吸顶。
   z-index 必须 < nav-bar 的 99（体验规范 §2.5） */
.ctx {
    position: sticky;
    top: 0;
    z-index: 50;
    padding: 16rpx 30rpx 14rpx;
    background: var(--c-surface);
    border-bottom: 1rpx solid var(--c-line);

    &__row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__row--meta {
        margin-top: 8rpx;
    }

    &__where {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-21);
        color: var(--c-ink-3);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__change {
        display: flex;
        align-items: center;
        gap: 2rpx;
        flex: none;
    }

    &__change-text {
        font-size: var(--fs-21);
        color: var(--c-brand-text);
    }

    &__change-ic {
        color: var(--c-brand-text);
    }

    &__count {
        display: flex;
        align-items: baseline;
        gap: 6rpx;
        min-height: 44rpx;
    }

    &__num {
        font-family: var(--font-serif);
        font-size: var(--fs-33);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-none);
        color: var(--c-ink);
    }

    &__unit {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }

    &__sk {
        width: 52rpx;
        height: 28rpx;
        margin-bottom: 6rpx;
        border-radius: var(--r-xs);
        background: var(--c-surface-2);
        animation: qShimmer 1.2s ease-in-out infinite;
    }

    &__sort {
        display: flex;
        align-items: center;
        gap: 6rpx;
        height: 52rpx;
        padding: 0 20rpx;
        border: 1rpx solid var(--c-control-border);
        border-radius: var(--r-pill);
        background: var(--c-surface);
        flex: none;
    }

    &__sort-text {
        font-size: var(--fs-20);
        color: var(--c-ink-2);
    }

    &__sort-ic {
        color: var(--c-ink-3);
    }
}

/* 双列人像网格：用 flex + calc 而不是 grid，兼容低版本基础库 */
.qgrid {
    display: flex;
    flex-wrap: wrap;
    gap: 20rpx;
    padding: 20rpx 24rpx 40rpx;
}

.qsk-wrap {
    padding-top: 20rpx;
}

.qcard {
    width: calc((100% - 20rpx) / 2);
    background: var(--c-surface);
    border: 1rpx solid var(--c-card-border);
    border-radius: var(--r-md);
    box-shadow: var(--shadow-card);
    overflow: hidden;

    &__media {
        position: relative;
        width: 100%;
        padding-top: 133.3333%;
        background: var(--c-surface-2);
        overflow: hidden;

        /* 浅色衣服/浅背景与卡片之间留一道极轻的内描边，避免人像“浮”在卡上 */
        &::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: var(--r-md) var(--r-md) 0 0;
            box-shadow: inset 0 0 0 1rpx rgba(var(--c-ink-rgb), 0.06);
            pointer-events: none;
        }
    }

    &__img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }

    &__fallback {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--c-brand-bg);
    }

    &__initial {
        font-family: var(--font-serif);
        font-size: var(--fs-69);
        line-height: var(--lh-none);
        color: var(--c-brand-text);
    }

    &__noimg {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 16rpx;
        text-align: center;
        font-size: var(--fs-18);
        color: var(--c-ink-3);
        opacity: 0.65;
    }

    &__rec {
        position: absolute;
        top: 14rpx;
        left: 14rpx;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        gap: 4rpx;
        height: 34rpx;
        padding: 0 10rpx;
        border-radius: var(--r-xs);
        background: var(--c-brand);
        font-size: var(--fs-18);
        line-height: var(--lh-none);
        color: var(--c-ink);
    }

    &__rec-ic {
        color: var(--c-ink);
    }

    &__rec-text {
        color: var(--c-ink);
    }

    /* 收藏：图像右上角的独立热区，不参与卡片点击域 */
    &__fav {
        position: absolute;
        top: 14rpx;
        right: 14rpx;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 56rpx;
        height: 56rpx;
        border-radius: var(--r-pill);
        background: rgba(var(--c-surface-rgb), 0.92);
    }

    &__fav-ic {
        color: var(--c-ink-3);

        .qcard__fav.is-on & {
            color: var(--c-danger);
        }
    }

    &__info {
        padding: 18rpx 18rpx 22rpx;
    }

    &__name-row {
        display: flex;
        align-items: center;
        gap: 10rpx;
        min-height: 44rpx;
    }

    &__name {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-33);
        font-weight: var(--fw-semibold);
        line-height: 1.2;
        color: var(--c-ink);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__status {
        display: flex;
        align-items: center;
        gap: 6rpx;
        flex: none;
        color: var(--c-success);

        &.is-warn {
            color: var(--c-warning);
        }
    }

    &__dot {
        width: 8rpx;
        height: 8rpx;
        border-radius: var(--r-pill);
        background: currentColor;
    }

    &__status-text {
        font-size: var(--fs-20);
        font-weight: var(--fw-medium);
        line-height: var(--lh-none);
    }

    &__meta {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 12rpx;
        margin-top: 8rpx;
        min-height: 32rpx;
    }

    &__role {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-21);
        color: var(--c-ink-3);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__price {
        display: flex;
        align-items: baseline;
        gap: 2rpx;
        flex: none;
        font-family: var(--font-serif);
        font-size: var(--fs-25);
        color: var(--c-ink);
    }

    &__price-unit {
        font-family: var(--font-sans);
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }

    /* 简介区放宽到至少 3 行、最多 4 行：36 字列表简介可完整展开，超长历史 profile 兜底收口 */
    &__intro {
        display: -webkit-box;
        margin-top: 10rpx;
        min-height: 108rpx;
        height: auto;
        max-height: 144rpx;
        overflow: hidden;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
        font-size: var(--fs-22);
        line-height: 36rpx;
        color: var(--c-ink-2);
        white-space: normal;
        word-break: break-all;
    }
}

.qcard--sk {
    .qcard__media {
        animation: qShimmer 1.2s ease-in-out infinite;
    }

    .qcard__info--sk {
        /* 与真实卡片信息区同高（含 4 行简介高度），避免骨架→内容跳动 */
        height: 246rpx;
    }
}

.qsk-line {
    height: 18rpx;
    border-radius: var(--r-xs);
    background: var(--c-surface-2);
    animation: qShimmer 1.2s ease-in-out infinite;

    &--title {
        width: 58%;
        height: 28rpx;
    }

    &--short {
        width: 38%;
        margin-top: 14rpx;
    }

    &--wide {
        width: 86%;
        margin-top: 14rpx;
    }
}

/* 条件内空态：一个明确的恢复动作，不给两个同义按钮 */
.qempty {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-top: var(--sp-6);
    padding: var(--sp-7) var(--sp-6) 0;
    text-align: center;

    &__icon {
        color: var(--c-brand);
    }

    &__title {
        margin-top: 24rpx;
        font-size: var(--fs-33);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__desc {
        margin-top: 12rpx;
        font-size: var(--fs-22);
        line-height: 1.7;
        color: var(--c-ink-3);
    }

    &__btn {
        display: flex;
        align-items: center;
        height: 80rpx;
        margin-top: 32rpx;
        padding: 0 40rpx;
        border: 1rpx solid var(--c-ink);
        border-radius: var(--r-pill);
        background: var(--c-surface);
    }

    &__btn-text {
        font-size: var(--fs-26);
        color: var(--c-ink);
    }
}

@keyframes qShimmer {
    0% {
        opacity: 0.55;
    }
    50% {
        opacity: 1;
    }
    100% {
        opacity: 0.55;
    }
}

/* 320 窄屏：页边距与列间距各降一档，保持 2 列不折行并尽量多留文本宽度 */
@media screen and (max-width: 359px) {
    .ctx {
        padding-left: 24rpx;
        padding-right: 24rpx;
    }

    .qgrid {
        padding-left: 20rpx;
        padding-right: 20rpx;
        gap: 16rpx;
    }

    .qcard {
        width: calc((100% - 16rpx) / 2);
    }
}
</style>
