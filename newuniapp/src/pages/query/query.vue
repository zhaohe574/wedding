<template>
    <!-- 03 查档期（Tab 3）｜头部统一走 page-shell 的 nav-bar（pages.json 已配 navigationStyle:custom） -->
    <page-shell
        :tabbar="true"
        current="/pages/query/query"
        bg="page"
        title="查档期"
        :show-back="false"
        title-align="left"
        title-variant="display"
    >
        <!-- 药丸搜索框 -->
        <view class="searchbar">
            <tn-icon name="search" :size="32" />
            <input
                v-model="keywordInput"
                class="searchbar__input"
                type="text"
                confirm-type="search"
                placeholder="搜索主持人、城市或风格"
                placeholder-class="searchbar__ph"
                @confirm="applyKeyword"
            />
            <view hover-class="is-pressed" class="searchbar__btn" @tap="applyKeyword">
                <text class="searchbar__btn-text">搜索</text>
            </view>
        </view>

        <!-- 婚礼日期 -->
        <view class="fblock">
            <view class="frow">
                <text class="frow__title">婚礼日期</text>
                <view class="frow__link" @tap="openCalendar">
                    <text class="frow__link-text">自定义</text>
                    <tn-icon name="right" :size="22" />
                </view>
            </view>
            <view class="days">
                <view
                    v-for="d in days"
                    :key="d.date"
                    class="day"
                    :class="{ 'is-on': selectedDate === d.date }"
                    :aria-label="`${d.weekLabel} ${d.dayNum} 日${d.lucky ? '，吉日' : ''}`"
                    @tap="selectDate(d.date)"
                >
                    <text class="day__w">{{ d.weekLabel }}</text>
                    <text class="day__d">{{ d.dayNum }}</text>
                    <!-- 批 1.5 G：去掉 aria-hidden，吉日信息改由 .day 的 aria-label 承载 -->
                    <view class="day__dot" :class="{ 'is-hide': !d.lucky }" />
                </view>
            </view>
            <!-- 自定义日期：日期条之外独立回显 -->
            <view
                v-if="customDate"
                class="customday"
                :class="{ 'is-on': selectedDate === customDate }"
                @tap="selectDate(customDate)"
            >
                <text class="customday__text">{{ customDateLabel }}</text>
                <tn-icon name="close" :size="22" @tap.stop="clearCustomDate" />
            </view>
        </view>

        <!-- 服务地区 -->
        <view class="fblock">
            <view class="frow">
                <text class="frow__title">服务地区</text>
            </view>
            <view class="chips">
                <view
                    v-for="c in visibleCities"
                    :key="c.city_code || c.city_name"
                    hover-class="is-pressed"
                    class="chip"
                    :class="{ 'is-on': cityCode === c.city_code }"
                    @tap="selectCity(c.city_code)"
                >
                    <text class="chip__text">{{ c.city_name }}</text>
                </view>
                <view
                    v-if="restCityCount > 0"
                    hover-class="is-pressed"
                    class="chip"
                    @tap="cityPanelOpen = true"
                >
                    <text class="chip__text">更多 {{ restCityCount }}</text>
                    <tn-icon name="right" :size="20" />
                </view>
                <view
                    hover-class="is-pressed"
                    class="chip"
                    :class="{ 'is-on': cityCode === '' }"
                    @tap="selectCity('')"
                >
                    <text class="chip__text">全国</text>
                </view>
            </view>
        </view>

        <!-- 服务类型 -->
        <view v-if="categories.length" class="fblock">
            <view class="frow">
                <text class="frow__title">服务类型</text>
            </view>
            <view class="chips">
                <view
                    hover-class="is-pressed"
                    class="chip"
                    :class="{ 'is-on': categoryId === 0 }"
                    @tap="selectCategory(0)"
                >
                    <text class="chip__text">全部</text>
                </view>
                <view
                    v-for="c in categories"
                    :key="c.id"
                    hover-class="is-pressed"
                    class="chip"
                    :class="{ 'is-on': categoryId === c.id }"
                    @tap="selectCategory(c.id)"
                >
                    <text class="chip__text">{{ c.name }}</text>
                </view>
            </view>
        </view>

        <!-- 已选回显 -->
        <view v-if="selectedSummary" class="selbar">
            <text class="selbar__text"
                >已选 <text class="selbar__strong">{{ selectedSummary }}</text></text
            >
            <text class="selbar__reset" @tap="resetAll">重置</text>
        </view>

        <!-- 结果头：数字取接口 total，不写死 -->
        <view class="res-head">
            <text class="res-head__count">
                <text class="res-head__num">{{ total }}</text> 位可约
            </text>
            <view class="res-head__sort" @tap="sortPanelOpen = !sortPanelOpen">
                <text class="res-head__sort-text">{{ sortLabel }}</text>
                <tn-icon name="down-arrow" :size="22" />
            </view>
            <view v-if="sortPanelOpen" class="sortpanel">
                <view
                    v-for="s in SORTS"
                    :key="s.key"
                    class="sortpanel__item"
                    @tap="selectSort(s.key)"
                >
                    <text class="sortpanel__text" :class="{ 'is-on': sort === s.key }">{{
                        s.label
                    }}</text>
                    <tn-icon v-if="sort === s.key" name="check" :size="22" />
                </view>
            </view>
        </view>

        <!-- 骨架：筛选区立即可用，只有结果区出骨架 -->
        <view v-if="phase === 'loading'" class="list">
            <view v-for="i in 3" :key="i" class="skcard">
                <view class="skcard__top">
                    <view class="skcard__avatar" />
                    <view class="skcard__info">
                        <view class="skcard__line skcard__line--name" />
                        <view class="skcard__line skcard__line--rate" />
                        <view class="skcard__line skcard__line--tag" />
                    </view>
                </view>
                <view class="skcard__foot" />
            </view>
        </view>

        <!-- 错误（可重试，条件不丢） -->
        <base-empty
            v-else-if="phase === 'error'"
            status="error"
            :error-kind="errorKind"
            error-text="网络不太稳"
            desc="结果没有加载出来，你的筛选条件还在"
            @retry="fetchList(true)"
        />

        <!-- 空：三种口径分文案 -->
        <base-empty
            v-else-if="phase === 'empty'"
            status="empty"
            :empty-text="emptyTitle"
            :desc="emptyDesc"
            :action-text="emptyAction"
            :show-action="true"
            :fixed="false"
            @action="onEmptyAction"
        />

        <!-- 结果列表 -->
        <view v-else class="list">
            <view
                v-for="s in list"
                :key="s.id"
                hover-class="is-pressed"
                class="staff"
                @tap="goDetail(s)"
            >
                <view class="staff__top">
                    <view class="staff__avatar">
                        <image
                            v-if="s.avatar"
                            class="staff__avatar-img"
                            :src="s.avatar"
                            mode="aspectFill"
                            lazy-load
                            @error="s.avatar = ''"
                        />
                        <text v-else class="staff__avatar-plain">{{ initialOf(s.name) }}</text>
                    </view>
                    <view class="staff__info">
                        <view class="staff__name-row">
                            <text class="staff__name">{{ s.name }}</text>
                            <text v-if="s.category_name" class="badge badge--ink">{{
                                s.category_name
                            }}</text>
                        </view>
                        <view v-if="rateText(s)" class="staff__rate">
                            <tn-icon name="star-fill" :size="20" />
                            <text class="staff__rate-text">{{ rateText(s) }}</text>
                        </view>
                        <text v-if="tagsText(s)" class="staff__tags">{{ tagsText(s) }}</text>
                    </view>
                    <!-- 可约角标：只在「有日期筛选」时才有依据（列表本身已按日期过滤） -->
                    <view
                        v-if="s.available_label"
                        class="avail"
                        :class="`avail--${s.available_tone}`"
                    >
                        <view class="avail__dot" aria-hidden="true" />
                        <text class="avail__text">{{ s.available_label }}</text>
                    </view>
                </view>
                <view class="staff__foot">
                    <view class="staff__price">
                        <text class="staff__price-num">{{ priceText(s) }}</text>
                        <text v-if="s.has_price" class="staff__price-unit">起 / 场</text>
                    </view>
                    <view class="staff__link">
                        <text class="staff__link-text">预约了解</text>
                        <tn-icon name="right" :size="22" />
                    </view>
                </view>
            </view>

            <view class="tail">
                <load-more v-if="hasMore" :status="loadMoreStatus" @retry="loadMore" />
                <text v-else class="tail__text">已显示 {{ list.length }} / {{ total }} 位</text>
            </view>
        </view>

        <!-- 自定义日期抽屉 -->
        <tn-popup
            v-model="calendarOpen"
            open-direction="bottom"
            :radius="24"
            :safe-area-inset-bottom="true"
        >
            <view class="sheet">
                <view class="sheet__head">
                    <text class="sheet__title">选择婚礼日期</text>
                    <tn-icon name="close" :size="34" @tap="calendarOpen = false" />
                </view>
                <tn-calendar
                    v-model="calendarValue"
                    mode="date"
                    :min-date="todayStr"
                    :allow-change-year="true"
                    :allow-change-month="true"
                    @change="onCalendarChange"
                />
                <view class="sheet__foot">
                    <base-button text="确定" variant="ink" size="lg" @click="confirmCalendar" />
                </view>
            </view>
        </tn-popup>

        <!-- 更多城市抽屉 -->
        <tn-popup
            v-model="cityPanelOpen"
            open-direction="bottom"
            :radius="24"
            :safe-area-inset-bottom="true"
        >
            <view class="sheet">
                <view class="sheet__head">
                    <text class="sheet__title">服务地区</text>
                    <tn-icon name="close" :size="34" @tap="cityPanelOpen = false" />
                </view>
                <scroll-view class="sheet__scroll" scroll-y>
                    <view
                        v-for="c in cities"
                        :key="c.city_code"
                        class="cityrow"
                        :class="{ 'is-on': cityCode === c.city_code }"
                        @tap="selectCity(c.city_code, true)"
                    >
                        <text class="cityrow__name">{{ c.city_name }}</text>
                        <tn-icon v-if="cityCode === c.city_code" name="check" :size="26" />
                    </view>
                </scroll-view>
            </view>
        </tn-popup>
    </page-shell>
    <app-toast />
</template>

<script setup lang="ts">
/**
 * 03 查档期。
 *
 * 数据：
 *  · `staff/lists`（page_no + page_size + date + city_code + category_id + keyword + sort）
 *    返回 dataLists 结构（{ lists, count }），用 normalizePage 归一，total 取 count。
 *  · `service/categoryTree` 出「服务类型」；`region/serviceTree` 出「服务地区」。
 *  · `schedule/monthCalendar` 只用来打「周末吉日」小圆点。
 *
 * 关键约束（03 交互文档）：
 *  · 改任一条件立即重取（没有「确定」按钮），条件不丢；
 *  · tab 页实例常驻 → 筛选条件天然保留；额外落一份 cache，冷启动也不丢；
 *  · 选日期后列表已按 `date` 过滤，因此「当日可约」是有依据的；
 *    「仅剩 N 档」后端无字段 → 不显示，不编数。
 */
import { computed, ref } from 'vue'
import { onLoad, onReachBottom, onShow } from '@dcloudio/uni-app'
import { getCategoryTree, getRegionTree, getStaffList, getMonthCalendar } from '@/api/catalog'
import { normalizePage, toArray } from '@/request'
import { navigate } from '@/utils/link'
import { toast } from '@/composables/useToast'
import cache from '@/utils/cache'
import { useTabQueryStore } from '@/stores/tabQuery'

interface DayCell {
    date: string
    weekLabel: string
    dayNum: string
    lucky: boolean
}

interface StaffRow {
    id: number
    name: string
    avatar?: string
    rating?: number | string
    review_count?: number
    order_count?: number
    tags?: string[]
    category_name?: string
    price?: number | string | null
    has_price?: boolean | number
    price_text?: string
    available_label?: string
    available_tone?: 'ok' | 'tight'
}

interface CityRow {
    province_code: string
    province_name: string
    city_code: string
    city_name: string
}

const SORTS = [
    { key: 'default', label: '推荐排序' },
    { key: 'price_asc', label: '价格从低到高' },
    { key: 'rating', label: '评分从高到低' }
] as const

const CACHE_KEY = 'query_filters'

const tabQuery = useTabQueryStore()

// ---------------------------------------------------------------- 状态（切 tab 不重置）
const keywordInput = ref('')
const keyword = ref('')
const selectedDate = ref('')
const customDate = ref('')
const cityCode = ref('')
const categoryId = ref(0)
const sort = ref<'default' | 'price_asc' | 'rating'>('default')

const cities = ref<CityRow[]>([])
const categories = ref<{ id: number; name: string }[]>([])
const luckyMap = ref<Record<string, boolean>>({})

const calendarOpen = ref(false)
const cityPanelOpen = ref(false)
const sortPanelOpen = ref(false)
const calendarValue = ref('')

const list = ref<StaffRow[]>([])
const total = ref(0)
const page = ref(1)
const pageSize = 10
const hasMore = ref(false)
const phase = ref<'loading' | 'ready' | 'empty' | 'error'>('loading')
const errorKind = ref<'network' | 'unknown'>('network')
const loadingMore = ref(false)
const loadMoreFailed = ref(false)

const todayStr = (() => {
    const d = new Date()
    return fmtDate(d)
})()

function fmtDate(d: Date) {
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${d.getFullYear()}-${m}-${day}`
}

/** 7 天日期条：首格起算日为今天 */
const days = computed<DayCell[]>(() => {
    const base = new Date(`${todayStr.replace(/-/g, '/')} 00:00:00`)
    return Array.from({ length: 7 }).map((_, i) => {
        const d = new Date(base.getTime() + i * 86400000)
        const date = fmtDate(d)
        return {
            date,
            weekLabel: ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][d.getDay()],
            dayNum: String(d.getDate()).padStart(2, '0'),
            lucky: !!luckyMap.value[date]
        }
    })
})

const customDateLabel = computed(() => {
    if (!customDate.value) return ''
    const d = new Date(`${customDate.value.replace(/-/g, '/')} 00:00:00`)
    const week = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][d.getDay()]
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${m}.${day} ${week}`
})

const visibleCities = computed(() => cities.value.slice(0, 5))
const restCityCount = computed(() => Math.max(0, cities.value.length - 5))

const sortLabel = computed(() => SORTS.find((s) => s.key === sort.value)?.label || '推荐排序')

const selectedSummary = computed(() => {
    const parts: string[] = []
    if (selectedDate.value) {
        const d = new Date(`${selectedDate.value.replace(/-/g, '/')} 00:00:00`)
        parts.push(`${d.getMonth() + 1}月${String(d.getDate()).padStart(2, '0')}日`)
    }
    const city = cities.value.find((c) => c.city_code === cityCode.value)
    if (city) parts.push(city.city_name)
    else if (cityCode.value) parts.push('已选地区')
    if (categoryId.value) {
        const c = categories.value.find((x) => x.id === categoryId.value)
        if (c) parts.push(c.name)
    }
    if (keyword.value) parts.push(`「${keyword.value}」`)
    return parts.join(' · ')
})

const loadMoreStatus = computed<'idle' | 'loading' | 'nomore' | 'error'>(() => {
    if (loadMoreFailed.value) return 'error'
    if (loadingMore.value) return 'loading'
    return 'idle'
})

const emptyTitle = computed(() => {
    if (keyword.value) return `没找到「${keyword.value}」`
    if (cityCode.value || categoryId.value) return '这几个条件没有可约的人'
    return '暂时没有可约的主持人'
})

const emptyDesc = computed(() => {
    if (keyword.value) return '换个说法，或直接按日期和地区筛'
    if (cityCode.value || categoryId.value) return '试试点「重置」，或换个临近日期'
    return '换个日期或放宽地区试试'
})

const emptyAction = computed(() => (keyword.value ? '清空关键词' : '重置条件'))

// ---------------------------------------------------------------- 派生展示

function initialOf(name?: string) {
    const s = (name || '').trim()
    return s ? [...s][0] : '主'
}

function rateText(s: StaffRow) {
    const rating = Number(s.rating || 0)
    if (!rating) return ''
    const count = Number(s.review_count || s.order_count || 0)
    return count > 0 ? `${rating.toFixed(1)} · ${count} 场评价` : rating.toFixed(1)
}

function tagsText(s: StaffRow) {
    return toArray<string>(s.tags).filter(Boolean).slice(0, 3).join(' · ')
}

function priceText(s: StaffRow) {
    if (s.price_text) return s.price_text
    if (s.has_price && s.price !== null && s.price !== undefined) return `¥${s.price}`
    return '价格待确认'
}

// ---------------------------------------------------------------- 数据

function decorate(rows: StaffRow[]) {
    return rows.map((s) => ({
        ...s,
        // 列表按 date 过滤过，只要选了日期，卡上「当日可约」就是有依据的
        available_label: selectedDate.value ? '当日可约' : '',
        available_tone: 'ok' as const
    }))
}

async function fetchList(reset = false) {
    if (reset) {
        page.value = 1
        loadMoreFailed.value = false
        phase.value = 'loading'
    }
    loadingMore.value = true
    try {
        const res = await getStaffList({
            page_no: page.value,
            page_size: pageSize,
            ...(selectedDate.value ? { date: selectedDate.value } : {}),
            ...(cityCode.value ? { city_code: cityCode.value } : {}),
            ...(categoryId.value ? { category_id: categoryId.value } : {}),
            ...(keyword.value ? { keyword: keyword.value } : {}),
            sort: sort.value
        })
        const paged = normalizePage<StaffRow>(res, page.value, pageSize)
        const rows = decorate(paged.list)
        list.value = reset ? rows : [...list.value, ...rows]
        total.value = paged.total
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
    }
}

async function loadMore() {
    if (!hasMore.value || loadingMore.value) return
    await fetchList(false)
}

/** 筛选条件落缓存：tab 页实例常驻已足够，这一层只为冷启动不丢 */
function persist() {
    cache.set(CACHE_KEY, {
        date: selectedDate.value,
        customDate: customDate.value,
        cityCode: cityCode.value,
        categoryId: categoryId.value,
        keyword: keyword.value,
        sort: sort.value
    })
}

async function loadMeta() {
    const tasks: Promise<any>[] = []
    tasks.push(
        getCategoryTree()
            .then((rows) => {
                categories.value = toArray<any>(rows).map((c) => ({
                    id: Number(c.id),
                    name: String(c.name)
                }))
            })
            .catch(() => {
                categories.value = []
            })
    )
    tasks.push(
        getRegionTree()
            .then((tree) => {
                const flat: CityRow[] = []
                toArray<any>(tree).forEach((p) => {
                    toArray<any>(p.cities).forEach((c) => {
                        flat.push({
                            province_code: String(c.province_code || p.province_code || ''),
                            province_name: String(c.province_name || p.province_name || ''),
                            city_code: String(c.city_code || ''),
                            city_name: String(c.city_name || '')
                        })
                    })
                })
                cities.value = flat.filter((c) => c.city_code && c.city_name)
            })
            .catch(() => {
                cities.value = []
            })
    )
    tasks.push(loadLuckyDays())
    await Promise.all(tasks)
}

/** 吉日小圆点：只取 7 天窗口覆盖到的月份，跨月补一次 */
async function loadLuckyDays() {
    try {
        const now = new Date()
        const months = new Set<string>()
        for (let i = 0; i < 7; i++) {
            const d = new Date(now.getTime() + i * 86400000)
            months.add(`${d.getFullYear()}-${d.getMonth() + 1}`)
        }
        const map: Record<string, boolean> = {}
        await Promise.all(
            [...months].map(async (key) => {
                const [y, m] = key.split('-').map(Number)
                const res = await getMonthCalendar({ year: y, month: m })
                toArray<any>((res as any)?.days).forEach((d) => {
                    if (d?.date) map[String(d.date)] = !!d.is_lucky_day
                })
            })
        )
        luckyMap.value = map
    } catch {
        luckyMap.value = {}
    }
}

// ---------------------------------------------------------------- 交互

function applyKeyword() {
    keyword.value = keywordInput.value.trim()
    persist()
    fetchList(true)
}

function selectDate(date: string) {
    selectedDate.value = date
    if (customDate.value && date !== customDate.value) customDate.value = ''
    persist()
    fetchList(true)
}

function selectCity(code: string, closePanel = false) {
    cityCode.value = code
    if (closePanel) cityPanelOpen.value = false
    persist()
    fetchList(true)
}

function selectCategory(id: number) {
    categoryId.value = id
    persist()
    fetchList(true)
}

function selectSort(key: 'default' | 'price_asc' | 'rating') {
    sortPanelOpen.value = false
    if (sort.value === key) return
    sort.value = key
    persist()
    fetchList(true)
}

function resetAll() {
    keywordInput.value = ''
    keyword.value = ''
    selectedDate.value = todayStr
    customDate.value = ''
    cityCode.value = ''
    categoryId.value = 0
    sort.value = 'default'
    persist()
    fetchList(true)
}

function onEmptyAction() {
    if (keyword.value) {
        keywordInput.value = ''
        keyword.value = ''
        persist()
        fetchList(true)
        return
    }
    resetAll()
}

function openCalendar() {
    calendarValue.value = selectedDate.value || todayStr
    calendarOpen.value = true
}

function onCalendarChange(value: any) {
    calendarValue.value = Array.isArray(value) ? String(value[0] || '') : String(value || '')
}

function confirmCalendar() {
    const value = calendarValue.value
    if (!value) {
        toast.info('先选一个日期')
        return
    }
    const date = value.replace(/\//g, '-')
    customDate.value = date
    calendarOpen.value = false
    selectDate(date)
}

function clearCustomDate() {
    customDate.value = ''
    if (selectedDate.value === customDate.value) selectedDate.value = todayStr
    persist()
    fetchList(true)
}

function goDetail(s: StaffRow) {
    navigate({
        path: '/packages/pkg-catalog/pages/staff-detail/staff-detail',
        query: { id: s.id, ...(selectedDate.value ? { date: selectedDate.value } : {}) }
    })
}

// ---------------------------------------------------------------- 生命周期

function restore() {
    const saved = cache.get(CACHE_KEY) as Record<string, any> | null
    if (!saved) return
    if (saved.date) selectedDate.value = String(saved.date)
    if (saved.customDate) customDate.value = String(saved.customDate)
    if (saved.cityCode) cityCode.value = String(saved.cityCode)
    if (saved.categoryId) categoryId.value = Number(saved.categoryId) || 0
    if (saved.keyword) {
        keyword.value = String(saved.keyword)
        keywordInput.value = String(saved.keyword)
    }
    if (saved.sort && ['default', 'price_asc', 'rating'].includes(saved.sort))
        sort.value = saved.sort
}

onLoad((options: any) => {
    restore()
    // tab 页 switchTab 不能带 query；首页吉日格等入口通过 tabQuery 中转
    const pending = tabQuery.consumeQuery('query')
    const date = pending?.date || options?.date
    const city = pending?.city || pending?.city_code || options?.city || options?.city_code
    const type = pending?.type || options?.type
    if (date) selectedDate.value = String(date)
    if (city) cityCode.value = String(city)
    if (type) {
        const matched = categories.value.find((c) => c.name === String(type))
        if (matched) categoryId.value = matched.id
    }
    if (!selectedDate.value) selectedDate.value = todayStr

    loadMeta()
    fetchList(true)
})

onShow(() => {
    // 从 05 返回：结果用缓存，只在超过 5 分钟后静默刷新角标（此处以整表刷新代替，成本一次请求）
    const pending = tabQuery.consumeQuery('query')
    if (pending?.date) {
        selectedDate.value = String(pending.date)
        fetchList(true)
    }
})

onReachBottom(() => loadMore())
</script>

<style lang="scss" scoped>
/* ---------- 搜索 ---------- */
.searchbar {
    /* 搜索图标由父级继承；.searchbar__input 自带 --c-ink */
    color: var(--c-ink-3);
    display: flex;
    align-items: center;
    gap: 17rpx;
    margin: 12rpx 42rpx 0;
    height: 81rpx;
    padding: 0 27rpx;
    border-radius: var(--r-pill);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line-strong);

    &__input {
        flex: 1;
        min-width: 0;
        font-size: 24rpx;
        color: var(--c-ink);
    }

    &__ph {
        font-size: 24rpx;
        color: var(--c-ink-3);
    }

    &__btn {
        height: 54rpx;
        padding: 0 25rpx;
        border-radius: var(--r-pill);
        background: var(--c-ink);
        display: flex;
        align-items: center;

        &-text {
            font-size: var(--fs-21);
            color: var(--c-on-dark);
            letter-spacing: 0.06em;
        }
    }
}

/* ---------- 筛选块 ---------- */
.fblock {
    padding: 38rpx 42rpx 0;
}

.frow {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 21rpx;

    &__title {
        font-size: var(--fs-22);
        color: var(--c-ink-2);
        letter-spacing: 0.06em;
    }

    &__link {
        /* 箭头与「自定义」同色 */
        color: var(--c-brand-text);
        display: flex;
        align-items: center;
        gap: 4rpx;

        &-text {
            font-size: 20rpx;
            color: var(--c-brand-text);
        }
    }
}

.days {
    display: flex;
    gap: 13rpx;
}

.day {
    flex: 1;
    min-width: 0;
    padding: 15rpx 0 13rpx;
    border-radius: var(--r-25);
    border: 1rpx solid var(--c-line-strong);
    background: var(--c-surface);
    text-align: center;

    &__w {
        display: block;
        font-size: var(--fs-17);
        color: var(--c-ink-3);
    }

    &__d {
        display: block;
        margin-top: 6rpx;
        font-family: var(--font-serif);
        font-size: 28rpx;
        color: var(--c-ink);
    }

    &__dot {
        width: 6rpx;
        height: 6rpx;
        border-radius: var(--r-pill);
        /* 批 1.5 G：原 --c-brand 在白底仅 2.39:1，6rpx 的点等于看不见；--c-brand-text 为 5.26:1 */
        background: var(--c-brand-text);
        margin: 8rpx auto 0;

        &.is-hide {
            opacity: 0;
        }
    }

    &.is-on {
        background: var(--c-ink);
        border-color: var(--c-ink);

        .day__w {
            color: var(--c-on-dark-2);
        }

        .day__d {
            color: var(--c-on-dark);
        }

        .day__dot {
            background: var(--c-brand-soft);
        }
    }
}

.customday {
    /* 关闭图标；.customday__text 自带 --c-ink-2 */
    color: var(--c-ink-3);
    display: inline-flex;
    align-items: center;
    gap: 12rpx;
    margin-top: 16rpx;
    height: 56rpx;
    padding: 0 20rpx;
    border-radius: var(--r-pill);
    border: 1rpx dashed var(--c-line-strong);
    background: var(--c-surface-2);

    &__text {
        font-size: var(--fs-22);
        color: var(--c-ink-2);
    }

    &.is-on {
        border-style: solid;
        border-color: var(--c-ink);
        background: var(--c-neutral-bg);
    }
}

.chips {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.chip {
    /* 箭头；.chip__text 自带 --c-ink-2 */
    color: var(--c-ink-3);
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    height: 56rpx;
    padding: 0 21rpx;
    border-radius: var(--r-pill);
    /* 可点控件边界（模板 @tap="selectCity" / @tap="cityPanelOpen = true" + is-on）：
       --c-line-strong 1.60:1 → --c-control-border 3.77:1 */
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);

    &__text {
        font-size: var(--fs-21);
        color: var(--c-ink-2);
    }

    &.is-on {
        background: var(--c-ink);
        border-color: var(--c-ink);

        .chip__text {
            color: var(--c-on-dark);
        }
    }
}

/* ---------- 已选回显 ---------- */
.selbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 38rpx 42rpx 0;
    padding-top: 30rpx;
    border-top: 1rpx dashed var(--c-line-strong);

    &__text {
        font-size: 20rpx;
        color: var(--c-ink-3);
    }

    &__strong {
        color: var(--c-ink-2);
    }

    &__reset {
        font-size: 20rpx;
        color: var(--c-ink-3);
        text-decoration: underline;
    }
}

/* ---------- 结果头 ---------- */
.res-head {
    position: relative;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    padding: 35rpx 42rpx 23rpx;

    &__count {
        font-size: 28rpx;
        color: var(--c-ink);
    }

    &__num {
        font-family: var(--font-serif);
        font-size: var(--fs-33);
        color: var(--c-brand-text);
        margin-right: 6rpx;
    }

    &__sort {
        /* 箭头与排序文字同色；&-text 自带 --c-ink-3 */
        color: var(--c-ink-3);
        display: flex;
        align-items: center;
        gap: 6rpx;
        padding: 8rpx 0 8rpx 16rpx;

        &-text {
            font-size: 20rpx;
            color: var(--c-ink-3);
        }
    }
}

.sortpanel {
    position: absolute;
    right: 42rpx;
    bottom: -8rpx;
    z-index: 30;
    min-width: 240rpx;
    padding: 8rpx 0;
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);
    box-shadow: var(--shadow-float);
    transform: translateY(100%);

    &__item {
        /* 选中勾与选项文字；.sortpanel__text 自带 --c-ink-2 */
        color: var(--c-brand-text);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
        padding: 20rpx 24rpx;
    }

    &__text {
        font-size: 24rpx;
        color: var(--c-ink-2);

        &.is-on {
            color: var(--c-ink);
            font-weight: var(--fw-semibold);
        }
    }
}

/* ---------- 人员卡 ---------- */
.list {
    padding: 0 42rpx;
}

.staff {
    margin-bottom: 21rpx;
    padding: 27rpx;
    border-radius: var(--r-31);
    border: 1rpx solid var(--c-line-strong);
    background: var(--c-surface);

    &__top {
        display: flex;
        align-items: flex-start;
        gap: 23rpx;
    }

    &__avatar {
        width: 100rpx;
        height: 100rpx;
        border-radius: var(--r-29);
        overflow: hidden;
        flex-shrink: 0;
        background: var(--c-brand-bg);
        border: 1rpx solid var(--c-line-strong);
        display: flex;
        align-items: center;
        justify-content: center;

        &-img {
            width: 100%;
            height: 100%;
        }

        &-plain {
            font-family: var(--font-serif);
            font-size: var(--fs-38);
            color: var(--c-brand-text);
        }
    }

    &__info {
        flex: 1;
        min-width: 0;
    }

    &__name-row {
        display: flex;
        align-items: center;
        gap: 12rpx;
    }

    &__name {
        font-size: 28rpx;
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__rate {
        /* 星标图标；&-text 自带 --c-ink-3 */
        color: var(--c-brand-text);
        display: flex;
        align-items: center;
        gap: 6rpx;
        margin-top: 10rpx;

        &-text {
            font-size: var(--fs-19);
            color: var(--c-ink-3);
        }
    }

    &__tags {
        display: block;
        margin-top: 13rpx;
        font-size: 20rpx;
        color: var(--c-ink-3);
    }

    &__foot {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        margin-top: 23rpx;
        padding-top: 21rpx;
        border-top: 1rpx solid var(--c-line);
    }

    &__price {
        display: flex;
        align-items: baseline;
        gap: 6rpx;

        &-num {
            font-family: var(--font-serif);
            font-size: var(--fs-31);
            color: var(--c-ink);
        }

        &-unit {
            font-size: var(--fs-18);
            color: var(--c-ink-3);
        }
    }

    &__link {
        /* 「预约了解」箭头；&-text 自带 --c-ink-2 */
        color: var(--c-ink-3);
        display: flex;
        align-items: center;
        gap: 4rpx;

        &-text {
            font-size: 20rpx;
            color: var(--c-ink-2);
        }
    }
}

.badge {
    height: 31rpx;
    padding: 0 10rpx;
    border-radius: var(--r-xs);
    font-size: var(--fs-16);
    line-height: 31rpx;

    &--ink {
        background: var(--c-neutral-bg);
        color: var(--c-ink-2);
    }
}

.avail {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    height: 42rpx;
    padding: 0 15rpx;
    border-radius: var(--r-11);
    flex-shrink: 0;
    margin-top: 2rpx;

    &__dot {
        width: 8rpx;
        height: 8rpx;
        border-radius: var(--r-pill);
        background: currentColor;
    }

    &__text {
        font-size: var(--fs-18);
        white-space: nowrap;
    }

    &--ok {
        background: var(--c-success-bg);
        color: var(--c-success);
    }

    &--tight {
        background: var(--c-warning-bg);
        color: var(--c-warning);
    }
}

.tail {
    padding: 16rpx 0 24rpx;
    text-align: center;

    &__text {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
        letter-spacing: 0.04em;
    }
}

/* ---------- 骨架卡 ---------- */
.skcard {
    margin-bottom: 21rpx;
    padding: 27rpx;
    border-radius: var(--r-31);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);

    &__top {
        display: flex;
        gap: 23rpx;
    }

    &__avatar {
        width: 100rpx;
        height: 100rpx;
        border-radius: var(--r-29);
        background: var(--c-neutral-bg);
        flex-shrink: 0;
    }

    &__info {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 14rpx;
        padding-top: 6rpx;
    }

    &__line {
        height: 22rpx;
        border-radius: var(--r-xs);
        background: var(--c-neutral-bg);

        &--name {
            width: 40%;
            height: 28rpx;
        }

        &--rate {
            width: 52%;
        }

        &--tag {
            width: 68%;
        }
    }

    &__foot {
        margin-top: 23rpx;
        padding-top: 21rpx;
        border-top: 1rpx solid var(--c-line);
        height: 30rpx;
    }
}

/* ---------- 抽屉 ---------- */
.sheet {
    padding: 30rpx 42rpx calc(30rpx + var(--safe-bottom));

    &__head {
        /* 弹层关闭图标；.sheet__title 自带 --c-ink-2 */
        color: var(--c-ink-3);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 20rpx;
    }

    &__title {
        font-size: var(--fs-30);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__foot {
        padding-top: 24rpx;
    }

    &__scroll {
        max-height: 60vh;
    }
}

.cityrow {
    /* 选中勾；.cityrow__name 自带 --c-ink */
    color: var(--c-brand-text);
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 96rpx;
    border-bottom: 1rpx solid var(--c-line);

    &__name {
        font-size: var(--fs-26);
        color: var(--c-ink-2);
    }

    &.is-on &__name {
        color: var(--c-ink);
        font-weight: var(--fw-semibold);
    }
}
</style>
