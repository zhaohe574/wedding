<template>
    <!-- 03 查档期（Tab3）｜日期优先：月历 + 当日档期 + 条件 + 唯一 CTA -->
    <page-shell
        :tabbar="true"
        current="/pages/query/query"
        bg="page"
        title="查档期"
        :show-back="false"
    >
        <view class="page-wrap query-page">
            <!-- 首屏直入月历：顶部标题只由 nav-bar 承担，不再重复说明文案 -->
            <view class="cal-card">
                <query-calendar-card
                    :year="viewYear"
                    :month="viewMonth"
                    :selected="form.date"
                    :today="todayStr"
                    :min-selectable="minSelectable"
                    :max-date="maxDate"
                    :metas="calendarMetas"
                    :availability="availabilityMap"
                    :loading="calLoading"
                    :status-loading="overviewLoading"
                    :error="calError"
                    @prev="shiftMonth(-1)"
                    @next="shiftMonth(1)"
                    @today="goCurrentMonth"
                    @select="onDatePick"
                    @jump="jumpToMonth"
                    @retry="loadCalendar"
                />
            </view>

            <!-- 当日可约摘要：独立平铺在页面底面上，不再套第二层卡 -->
            <view class="query-avail">
                <query-availability
                    :date="form.date"
                    :weekday="selectedWeekday"
                    :lunar="selectedLunar"
                    :is-lucky="selectedIsLucky"
                    :region-selected="!!form.region"
                    :loading="dayLoading"
                    :error="dayError"
                    :count="dayCount"
                    :neighbors="neighbors"
                    :neighbors-loading="neighborsLoading"
                    @retry="loadDay"
                    @go-region="openSheet('region')"
                    @pick-neighbor="onDatePick"
                    @contact="goContact"
                />
            </view>

            <view class="query-conds">
                <query-condition-chips
                    :region="form.region"
                    :category-name="categoryName"
                    :style-text="styleText"
                    :style-selected="form.tagIds.length > 0"
                    :keyword="keywordInput"
                    :search-open="searchOpen"
                    @region="openSheet('region')"
                    @category="openSheet('category')"
                    @category-clear="clearCondition('category')"
                    @style="openSheet('style')"
                    @style-clear="clearCondition('style')"
                    @toggle-search="toggleSearch"
                    @update:keyword="onKeywordInput"
                    @confirm-keyword="applyKeywordNow"
                    @clear-keyword="clearKeyword"
                />
            </view>

            <!-- CTA 走文档流底部容器：query 是 TabBar 页，fixed bottom-bar 会盖住 app-tabbar。
                 40rpx 缓冲 + 一行轻提示放在按钮上方，上滑翻月不误触。 -->
            <view class="cal-cta-wrap">
                <text class="cal-hint">档期以老师当天是否可接单为准，最终以下单确认为准</text>
                <base-button
                    :text="ctaText"
                    variant="ink"
                    size="lg"
                    block
                    haptic-level="medium"
                    :loading="dayLoading || neighborsLoading"
                    :loading-text="
                        dayLoading ? '正在查询档期…' : neighborsLoading ? '正在查找可约日…' : ''
                    "
                    @click="onCta"
                />
            </view>
        </view>

        <!-- 三个弹窗：地区/类型/风格；日期已内联，不再有日期弹窗。
             首次打开前不实例化，避免首切 Tab 时同时创建 3 个 tn-popup 全屏层。
             打开过之后保持挂载，关闭动画与草稿状态沿用原有行为。 -->
        <query-region-sheet
            v-if="sheetMounted.region"
            v-model:open="sheets.region"
            :model-value="form.region"
            :tree="regionTree"
            :loading="regionLoading"
            :error="regionError"
            @pick="onRegionPick"
            @retry="loadRegion(true)"
        />
        <query-category-sheet
            v-if="sheetMounted.category"
            v-model:open="sheets.category"
            :model-value="form.categoryId"
            :categories="categories"
            :loading="catLoading"
            @pick="onCategoryPick"
            @retry="loadCategories"
        />
        <query-style-sheet
            v-if="sheetMounted.style"
            v-model:open="sheets.style"
            :model-value="form.tagIds"
            :tags="renderableTags"
            :invalid-tags="invalidTags"
            :max="5"
            :category-name="categoryName"
            :loading="tagsLoading"
            :error="tagsError"
            @commit="onStyleCommit"
            @retry="loadTags"
            @change-category="switchToCategory"
        />
    </page-shell>
    <app-toast />
</template>

<script setup lang="ts">
/**
 * 03 查档期 · 日期优先版（v3「档期册」）。
 *
 * 核心纪律：
 *  - 主页面仍然**不请求人员列表**；只请求
 *      ① monthCalendar（吉日/农历/节假日）
 *      ② 选中日期的 getStaffList({ page_size: 1 }) 取 total（P0 过渡方案，60s 缓存）
 *    聚合接口 `schedule.availabilityOverview` 就绪后，把 ② 换成区间聚合，
 *    就能给日历铺满每日可约点；当前只给选中日一个点，未知不补 0。
 *  - 地区是硬必选：未选时不发人数请求、不显示可约点，CTA 只能打开地区 sheet。
 *  - 今天与提前期（后台 `la_schedule_rule.advance_days`）不可选；默认选最早可订日。
 *  - 条件真源仍在这里；列表页只读，改条件必须返回本页。
 */
import { computed, reactive, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import {
    getAvailabilityOverview,
    getCategoryTree,
    getMonthCalendar,
    getScheduleRule,
    getStaffList
} from '@/api/catalog'
import { SERVICE_TAG_TYPE, type ServiceTagVO } from '@/api/types/catalog'
import {
    useCatalogCache,
    type PickedRegion,
    type SlimProvince
} from '@/composables/useCatalogCache'
import { navigate, safeDecodeParam } from '@/utils/link'
import { saveBookingContext } from '@/utils/booking-context'
import { toast } from '@/composables/useToast'
import cache from '@/utils/cache'
import { useTabQueryStore } from '@/stores/tabQuery'
import { useBadgeStore } from '@/stores/badge'
import { normalizePage } from '@/request'
import {
    SCHEDULE_LEAD_DAYS_FALLBACK,
    SCHEDULE_MAX_YEARS_FALLBACK
} from '@/constants/schedule'
// 页面私有组件不在 easycom autoscan 范围（只扫 src/components），必须显式引入
import QueryCalendarCard from './components/query-calendar-card.vue'
import QueryAvailability from './components/query-availability.vue'
import QueryConditionChips from './components/query-condition-chips.vue'
import QueryRegionSheet from './components/query-region-sheet.vue'
import QueryCategorySheet from './components/query-category-sheet.vue'
import QueryStyleSheet from './components/query-style-sheet.vue'
import type { CalendarMeta, DayAvailability, NeighborDay } from './components/types'

interface QueryForm {
    date: string
    region: PickedRegion | null
    categoryId: number
    tagIds: number[]
    keyword: string
}

type SheetKey = 'region' | 'category' | 'style'

const CACHE_KEY = 'query_filters'
const CACHE_VER = 2
const DAY_CACHE_TTL = 60 * 1000
const SEARCH_DEBOUNCE = 300

// ---------------------------------------------------------------- 日期工具

function pad(n: number) {
    return String(n).padStart(2, '0')
}

function fmtDate(d: Date) {
    const m = pad(d.getMonth() + 1)
    const day = pad(d.getDate())
    return `${d.getFullYear()}-${m}-${day}`
}

function parseDate(date: string) {
    const [y, m, d] = String(date || '')
        .split('-')
        .map(Number)
    return new Date(y || 1970, (m || 1) - 1, d || 1)
}

function addDays(d: Date, n: number) {
    return new Date(d.getFullYear(), d.getMonth(), d.getDate() + n)
}

function addYears(d: Date, n: number) {
    return new Date(d.getFullYear() + n, d.getMonth(), d.getDate())
}

function monthOf(date: string) {
    return { y: Number(date.slice(0, 4)), m: Number(date.slice(5, 7)) }
}

function weekdayOf(date: string) {
    return ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][parseDate(date).getDay()]
}

function fmtCardDate(date: string) {
    const [y, m, d] = date.split('-')
    const md = `${Number(m)}.${Number(d)}`
    const year = Number(y) !== new Date().getFullYear() ? `${String(y).slice(2)}.` : ''
    return `${year}${md} ${weekdayOf(date)}`
}

// ---------------------------------------------------------------- 可选范围（后台配置）
//
// 首帧先用「设备日 + 兜底天数」渲染，避免等接口时白屏；
// `/schedule/rule` 返回后再用后台口径覆盖，并重新夹选中日与月视图。
// 页面里不存在 `today + 3` 这种写死的业务日期。

const NOW = new Date()
const todayStr = ref(fmtDate(NOW))
const leadDays = ref(SCHEDULE_LEAD_DAYS_FALLBACK)
const minSelectable = ref(fmtDate(addDays(NOW, SCHEDULE_LEAD_DAYS_FALLBACK)))
const maxDate = ref(fmtDate(addYears(NOW, SCHEDULE_MAX_YEARS_FALLBACK)))
/** 规则接口是否已给出结论（成功或失败都算），未就绪前不消费入口传参 */
const ruleReady = ref(false)
const initialMonth = monthOf(minSelectable.value)

// ---------------------------------------------------------------- 页面状态

const tabQuery = useTabQueryStore()
const badgeStore = useBadgeStore()
const {
    loadTags: fetchTags,
    loadRegionTree: fetchRegionTree,
    readTagsSync,
    readRegionSync
} = useCatalogCache()

const keywordInput = ref('')
const searchOpen = ref(false)
const form = ref<QueryForm>({
    date: minSelectable.value,
    region: null,
    categoryId: 0,
    tagIds: [],
    keyword: ''
})

const viewYear = ref(initialMonth.y)
const viewMonth = ref(initialMonth.m)

const calendarMetas = ref<Record<string, CalendarMeta>>({})
const calLoading = ref(true)
const calError = ref(false)

const dayLoading = ref(false)
const dayError = ref(false)
const dayCount = ref<number | null>(null)
const neighbors = ref<NeighborDay[]>([])
const neighborsLoading = ref(false)

/** 整月概览：date → 可约人数；缺省 = 未知，不补 0 */
const overviewMap = ref<Record<string, DayAvailability>>({})
const overviewLoading = ref(false)

const sheets = reactive<Record<SheetKey, boolean>>({
    region: false,
    category: false,
    style: false
})

/**
 * 弹窗是否已经创建过。
 * 三个 sheet 内部都是 tn-popup（关闭时也会渲染全屏 fixed 容器），
 * 首切「查档期」时不创建它们，只有用户真的点开某个筛选才挂载。
 * 打开过之后保持挂载，关闭动画和草稿状态不受影响。
 */
const sheetMounted = reactive<Record<SheetKey, boolean>>({
    region: false,
    category: false,
    style: false
})

const categories = ref<{ id: number; name: string }[]>([])
const catLoading = ref(false)
const catError = ref(false)

const allTags = ref<ServiceTagVO[]>([])
const tagsLoading = ref(false)
const tagsError = ref(false)

const regionTree = ref<SlimProvince[]>([])
const regionLoading = ref(false)
const regionError = ref(false)

// ---------------------------------------------------------------- 请求竞态

let calSeq = 0
let daySeq = 0
let neighborSeq = 0
let overviewSeq = 0
let dayTimer: ReturnType<typeof setTimeout> | null = null
let searchTimer: ReturnType<typeof setTimeout> | null = null
const dayCache = new Map<string, { count: number; ts: number }>()
const overviewCache = new Map<string, { map: Record<string, DayAvailability>; ts: number }>()
const OVERVIEW_CACHE_TTL = 60 * 1000

// ---------------------------------------------------------------- 派生

const categoryName = computed(
    () => categories.value.find((c) => c.id === form.value.categoryId)?.name || ''
)

/** 可选集：type ∈ {1,2} 且 category_id ∈ {0, 当前类型} */
const renderableTags = computed(() => {
    const cid = form.value.categoryId
    if (!cid) return []
    return allTags.value.filter(
        (t) =>
            (Number(t.type) === SERVICE_TAG_TYPE.Style ||
                Number(t.type) === SERVICE_TAG_TYPE.Skill) &&
            (Number(t.category_id) === 0 || Number(t.category_id) === cid)
    )
})

/** 已失效 = 已选 − 可选集 */
const invalidTags = computed(() => {
    if (!allTags.value.length) return []
    const valid = new Set(renderableTags.value.map((t) => t.id))
    return form.value.tagIds
        .filter((id) => !valid.has(id))
        .map((id) => ({ id, name: tagNameOf(id) }))
})

function tagNameOf(id: number) {
    return allTags.value.find((t) => t.id === id)?.name || `标签${id}`
}

/** 风格 chip 的最短可读文案 */
const styleText = computed(() => {
    if (!form.value.categoryId) return '先选类型'
    if (!form.value.tagIds.length) return '风格不限'
    if (!allTags.value.length) return `已选 ${form.value.tagIds.length} 项`
    const names = form.value.tagIds.map(tagNameOf).filter(Boolean)
    if (!names.length) return `已选 ${form.value.tagIds.length} 项`
    if (names.length >= 4) return `${names.slice(0, 2).join('、')} 等${names.length}项`
    return names.join('、')
})

const selectedMeta = computed<CalendarMeta | null>(
    () => calendarMetas.value[form.value.date] || null
)
const selectedWeekday = computed(() => weekdayOf(form.value.date))
const selectedLunar = computed(() => selectedMeta.value?.lunar || '')
const selectedIsLucky = computed(() => !!selectedMeta.value?.isLucky)

/**
 * 日历角标数据。
 *
 * 两份来源，优先级不同：
 *   1. overviewMap：整月每天的聚合人数（后台 availabilityOverview）；
 *   2. dayCount：选中日的精确人数（staff/lists count）。
 * 选中日必须用 ② 覆盖 ① —— 状态行、CTA、日历角标三处数字要来自同一次请求，
 * 否则会出现「日历点说 1 位、按钮说 2 位」。
 * 没有地区、或两份数据都没有时返回空对象：角标不显示 = 未知，不用 0 冒充。
 */
const availabilityMap = computed<Record<string, DayAvailability>>(() => {
    if (!form.value.region) return {}

    const map: Record<string, DayAvailability> = { ...overviewMap.value }
    if (dayError.value || dayCount.value === null) return map

    const count = dayCount.value
    map[form.value.date] = {
        count,
        status: count === 0 ? 'full' : count === 1 ? 'limited' : 'available'
    }
    return map
})

const ctaText = computed(() => {
    if (!form.value.region) return '选择服务地区'
    if (dayLoading.value) return '正在查询档期…'
    if (dayCount.value === 0) {
        if (neighborsLoading.value) return '正在查找可约日…'
        return neighbors.value.length ? '看看相邻可约日' : '换个日期'
    }
    if (dayError.value || dayCount.value === null) return '查看该日人员'
    return `查看 ${dayCount.value} 位可约人员`
})

// ---------------------------------------------------------------- 后台档期规则

const DATE_RE = /^\d{4}-\d{2}-\d{2}$/

function isDateString(value: unknown): value is string {
    return typeof value === 'string' && DATE_RE.test(value)
}

/**
 * 读取后台规则并校正可选范围。
 *
 * 成功：today / advance_days / 最早可订日 / 最晚可查日全部以接口为准；
 * 失败或字段非法：保留 setup 时的兜底值（设备日 + SCHEDULE_LEAD_DAYS_FALLBACK），
 * 只影响可选范围，不弹错、不阻断页面。
 *
 * 注意不要改成「等接口回来再渲染」：请求最长 60s，弱网会把首屏拖死。
 */
async function loadScheduleRule() {
    try {
        const cfg = await getScheduleRule()
        const today = isDateString(cfg?.today) ? cfg.today : fmtDate(new Date())
        const advanceRaw = Number(cfg?.advance_days)
        // 后端校验区间是 0–365，非法值一律回落兜底，避免算出 NaN 日期
        const advance =
            Number.isFinite(advanceRaw) && advanceRaw >= 0
                ? Math.min(365, Math.floor(advanceRaw))
                : SCHEDULE_LEAD_DAYS_FALLBACK
        const min = isDateString(cfg?.min_selectable_date)
            ? cfg.min_selectable_date
            : fmtDate(addDays(parseDate(today), advance))
        const max = isDateString(cfg?.max_selectable_date)
            ? cfg.max_selectable_date
            : fmtDate(addYears(parseDate(today), SCHEDULE_MAX_YEARS_FALLBACK))

        applyScheduleRule(today, advance, min, max)
    } catch {
        // 保留兜底值：配置读不到不应该打断查档期
    } finally {
        // 成功或失败都算「有结论」，此后入口传参才能按最终范围夹取
        ruleReady.value = true
        if (applyPending()) {
            clampDate()
            setViewToDate(form.value.date)
            persist()
            loadCalendar()
            if (form.value.region) scheduleLoadDay(true)
        }
    }
}

function applyScheduleRule(today: string, advance: number, min: string, max: string) {
    const prevMin = minSelectable.value
    const before = form.value.date

    todayStr.value = today
    leadDays.value = advance
    minSelectable.value = min
    maxDate.value = max

    // 之前选中的只是「兜底算出的默认最早日」时，跟随配置；用户主动选过的日期不动
    if (before === prevMin && min !== prevMin) {
        form.value.date = min
    }
    clampDate()

    const moved = form.value.date !== before
    const { y, m } = monthOf(form.value.date)
    const monthChanged = y !== viewYear.value || m !== viewMonth.value
    if (monthChanged) {
        viewYear.value = y
        viewMonth.value = m
    }
    if (moved) persist()

    // 提前期变化会同时改变「哪些日期锁定」和「哪些日期可约」，必须重算日历与人数
    if (moved || monthChanged || prevMin !== min) {
        loadCalendar()
        if (form.value.region) scheduleLoadDay(true)
    }
}

// ---------------------------------------------------------------- 月历

async function loadCalendar() {
    const seq = ++calSeq
    calLoading.value = true
    calError.value = false

    try {
        const res = await getMonthCalendar({ year: viewYear.value, month: viewMonth.value })
        if (seq !== calSeq) return
        const rows = Array.isArray(res)
            ? res
            : Array.isArray((res as any)?.days)
            ? (res as any).days
            : []
        const map: Record<string, CalendarMeta> = {}
        for (const d of rows) {
            const date = String(d?.date || '')
            if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) continue
            map[date] = {
                date,
                isLucky: Number(d?.is_lucky_day) === 1,
                isHoliday: Number(d?.is_holiday) === 1,
                holidayName: String(d?.holiday_name || ''),
                lunar: String(d?.lunar_date || '')
            }
        }
        calendarMetas.value = map
    } catch {
        if (seq !== calSeq) return
        calError.value = true
        calendarMetas.value = {}
    } finally {
        if (seq === calSeq) {
            calLoading.value = false
            // 月历换了月份就同步重算整月角标；没选地区时 loadOverview 会直接清空
            loadOverview()
        }
    }
}

/**
 * 整月档期概览 → 日历角标。
 *
 * 边界：
 *   - 没选到区县：清空，不猜「全国」；
 *   - 相同条件 60s 内复用缓存，翻月来回切不重复请求；
 *   - 失败：清空 = 未知，不补 0、不标「已满」。
 */
async function loadOverview() {
    const seq = ++overviewSeq
    const r = form.value.region

    if (!r?.cityCode || !r?.districtCode) {
        overviewMap.value = {}
        overviewLoading.value = false
        return
    }

    const start = `${viewYear.value}-${pad(viewMonth.value)}-01`
    const end = fmtDate(new Date(viewYear.value, viewMonth.value, 0))
    const key = overviewKey(start, end)
    const hit = overviewCache.get(key)
    if (hit && Date.now() - hit.ts < OVERVIEW_CACHE_TTL) {
        overviewMap.value = hit.map
        overviewLoading.value = false
        return
    }

    overviewLoading.value = true
    try {
        const res = await getAvailabilityOverview({
            start_date: start,
            end_date: end,
            province_code: r.provinceCode || undefined,
            city_code: r.cityCode,
            district_code: r.districtCode,
            category_id: form.value.categoryId || undefined,
            tag_ids: form.value.tagIds.length ? form.value.tagIds.join(',') : undefined,
            keyword: form.value.keyword.trim() || undefined
        })
        if (seq !== overviewSeq) return
        // 月份已经切走：丢弃这次结果，避免旧月份的点闪在新月份上
        if (`${viewYear.value}-${pad(viewMonth.value)}-01` !== start) return

        const rows = Array.isArray(res?.days) ? res.days : []
        const map: Record<string, DayAvailability> = {}
        for (const row of rows) {
            const date = String(row?.date || '')
            if (!DATE_RE.test(date)) continue
            const raw = Number(row?.available_count)
            // 字段缺失 = 未知，不能当成 0（0 会被画成「已满」）
            if (!Number.isFinite(raw)) continue
            const count = Math.max(0, Math.floor(raw))
            map[date] = {
                count,
                status: count === 0 ? 'full' : count === 1 ? 'limited' : 'available'
            }
        }
        overviewCache.set(key, { map, ts: Date.now() })
        overviewMap.value = map
    } catch {
        if (seq !== overviewSeq) return
        overviewMap.value = {}
    } finally {
        if (seq === overviewSeq) overviewLoading.value = false
    }
}

function overviewKey(start: string, end: string) {
    const r = form.value.region
    return [
        start,
        end,
        r?.provinceCode || '',
        r?.cityCode || '',
        r?.districtCode || '',
        form.value.categoryId || 0,
        form.value.tagIds.join(','),
        form.value.keyword.trim()
    ].join('|')
}

// ---------------------------------------------------------------- 档期人数

function countKey(date: string) {
    const r = form.value.region
    return [
        date,
        r?.provinceCode || '',
        r?.cityCode || '',
        r?.districtCode || '',
        form.value.categoryId || 0,
        form.value.tagIds.join(','),
        form.value.keyword.trim()
    ].join('|')
}

async function fetchCount(date: string): Promise<number> {
    const key = countKey(date)
    const hit = dayCache.get(key)
    if (hit && Date.now() - hit.ts < DAY_CACHE_TTL) return hit.count

    const r = form.value.region
    if (!r) throw new Error('no-region')

    const res = await getStaffList({
        page_no: 1,
        page_size: 1,
        date,
        province_code: r.provinceCode || undefined,
        city_code: r.cityCode,
        district_code: r.districtCode,
        category_id: form.value.categoryId || undefined,
        tag_ids: form.value.tagIds.length ? form.value.tagIds.join(',') : undefined,
        keyword: form.value.keyword.trim() || undefined
    })
    const total = normalizePage(res, 1, 1).total
    dayCache.set(key, { count: total, ts: Date.now() })
    return total
}

async function loadDay() {
    const seq = ++daySeq
    neighborSeq += 1

    if (!form.value.region) {
        dayLoading.value = false
        dayError.value = false
        dayCount.value = null
        neighbors.value = []
        neighborsLoading.value = false
        return
    }

    dayLoading.value = true
    dayError.value = false
    dayCount.value = null
    neighbors.value = []
    neighborsLoading.value = false

    try {
        const total = await fetchCount(form.value.date)
        if (seq !== daySeq) return
        dayCount.value = total
        if (total === 0) loadNeighbors()
    } catch {
        if (seq !== daySeq) return
        dayError.value = true
        dayCount.value = null
    } finally {
        if (seq === daySeq) dayLoading.value = false
    }
}

/** 已满时只查前后各 1 天；两次请求并发，失败静默 */
async function loadNeighbors() {
    const seq = ++neighborSeq
    neighborsLoading.value = true
    const base = parseDate(form.value.date)
    const dates = [fmtDate(addDays(base, -1)), fmtDate(addDays(base, 1))].filter(
        (d) => d >= minSelectable.value && d <= maxDate.value
    )

    try {
        const rows = await Promise.all(
            dates.map(async (date) => {
                try {
                    const count = await fetchCount(date)
                    return count > 0
                        ? { date, label: fmtCardDate(date), count }
                        : null
                } catch {
                    return null
                }
            })
        )
        if (seq !== neighborSeq) return
        neighbors.value = rows.filter(Boolean) as NeighborDay[]
    } finally {
        if (seq === neighborSeq) neighborsLoading.value = false
    }
}

function scheduleLoadDay(immediate = false) {
    // 日期/地区/类型变化时，若搜索还在防抖窗口内，先把输入落库，
    // 保证这次人数请求用的是最新关键词，而不是上一次的 form.keyword。
    if (searchTimer) {
        clearSearchTimer()
        const kw = keywordInput.value.trim()
        if (kw !== form.value.keyword) {
            form.value.keyword = kw
            persist()
        }
    }

    if (dayTimer) clearTimeout(dayTimer)
    dayTimer = setTimeout(
        () => {
            dayTimer = null
            loadDay()
        },
        immediate ? 0 : 200
    )
}

/**
 * 筛选条件变化后的统一刷新：整月角标 + 选中日人数。
 * 只改日期时不要走这里（选中日有精确人数，整月概览没变）。
 */
function reloadAvailability() {
    loadOverview()
    scheduleLoadDay(true)
}

// ---------------------------------------------------------------- 日期交互

function selectDate(date: string) {
    const next = date < minSelectable.value ? minSelectable.value : date
    if (next === form.value.date) return
    form.value.date = next
    persist()

    // 相邻日/入口传参可能跨月：视图跟着选中日走，否则会出现「日期在 12 月、月历停在 11 月」
    const m = monthOf(next)
    if (m.y !== viewYear.value || m.m !== viewMonth.value) {
        viewYear.value = m.y
        viewMonth.value = m.m
        loadCalendar()
    }

    scheduleLoadDay(true)
}

function onDatePick(date: string) {
    if (!date || date < minSelectable.value) return
    selectDate(date)
}

function firstBookableOfMonth(y: number, m: number) {
    const first = `${y}-${pad(m)}-01`
    if (first >= minSelectable.value) return first
    const minM = monthOf(minSelectable.value)
    return y === minM.y && m === minM.m ? minSelectable.value : first
}

function setViewToDate(date: string) {
    const { y, m } = monthOf(date)
    viewYear.value = y
    viewMonth.value = m
}

/**
 * 跳到指定年月：年月快切与左右箭头共用。
 * 切换后：视图、选中日、当月日历、选中日人数一起更新；选中日不在目标月时选该月第一个可订日。
 */
function jumpToMonth(year: number, month: number) {
    const minM = monthOf(minSelectable.value)
    const maxM = monthOf(maxDate.value)
    let y = Number(year) || viewYear.value
    let m = Number(month) || viewMonth.value

    if (y < minM.y || (y === minM.y && m < minM.m)) {
        y = minM.y
        m = minM.m
    }
    if (y > maxM.y || (y === maxM.y && m > maxM.m)) {
        y = maxM.y
        m = maxM.m
    }
    if (y === viewYear.value && m === viewMonth.value) return

    viewYear.value = y
    viewMonth.value = m

    const selM = monthOf(form.value.date)
    if (selM.y !== y || selM.m !== m) {
        selectDate(firstBookableOfMonth(y, m))
    }
    loadCalendar()
}

function shiftMonth(delta: number) {
    const d = new Date(viewYear.value, viewMonth.value - 1 + delta, 1)
    jumpToMonth(d.getFullYear(), d.getMonth() + 1)
}

function goCurrentMonth() {
    const target = minSelectable.value
    setViewToDate(target)
    selectDate(target)
    loadCalendar()
}

// ---------------------------------------------------------------- 弹窗与元数据

function openSheet(key: SheetKey) {
    if (key === 'style' && !form.value.categoryId) {
        toast.info('先选服务类型')
        return
    }
    if (key === 'category' && catError.value) {
        loadCategories()
    }
    if (key === 'style') loadTags()
    if (key === 'region') loadRegion()
    sheetMounted[key] = true
    sheets[key] = true
}

/** 风格弹窗空态里的「换一个服务类型」：先关风格再开类型，避免两个 popup 同时挂载 */
function switchToCategory() {
    sheets.style = false
    setTimeout(() => {
        sheetMounted.category = true
        sheets.category = true
    }, 60)
}

async function loadCategories() {
    if (catLoading.value) return
    catLoading.value = true
    try {
        const rows = await getCategoryTree()
        categories.value = (Array.isArray(rows) ? rows : [])
            .map((c: any) => ({ id: Number(c?.id) || 0, name: String(c?.name || '') }))
            .filter((c) => c.id > 0 && c.name)
        catError.value = false
    } catch {
        catError.value = true
        categories.value = []
    } finally {
        catLoading.value = false
    }
}

async function loadTags() {
    if (tagsLoading.value || allTags.value.length) return
    const cached = readTagsSync()
    if (cached) {
        allTags.value = cached
        tagsError.value = false
        return
    }
    tagsLoading.value = true
    try {
        allTags.value = await fetchTags()
        tagsError.value = false
    } catch {
        tagsError.value = true
    } finally {
        tagsLoading.value = false
    }
}

async function loadRegion(force = false) {
    if (regionLoading.value) return
    if (!force) {
        const cached = readRegionSync()
        if (cached) {
            regionTree.value = cached
            regionError.value = false
            return
        }
    }
    regionLoading.value = true
    regionError.value = false
    try {
        const rows = await fetchRegionTree()
        regionTree.value = Array.isArray(rows) ? rows : []
    } catch {
        regionError.value = true
        regionTree.value = []
        toast.info('地区数据没加载出来，稍后重试')
    } finally {
        regionLoading.value = false
    }
}

// ---------------------------------------------------------------- 条件变更

function onRegionPick(region: PickedRegion) {
    form.value.region = region
    persist()
    reloadAvailability()
}

/**
 * 切换服务类型 → 已选风格全清，不是保留交集。
 * 保留交集会让行为取决于「这个标签恰好是不是全局标签」，用户无法形成稳定预期。
 */
function onCategoryPick(id: number) {
    const next = Number(id) || 0
    if (next === form.value.categoryId) return
    const had = form.value.tagIds.length
    form.value.categoryId = next
    form.value.tagIds = []
    if (had > 0) toast.info('已切换服务类型，风格偏好已清空')
    persist()
    reloadAvailability()
}

function onStyleCommit(ids: number[]) {
    form.value.tagIds = Array.isArray(ids) ? ids.slice(0, 5) : []
    persist()
    reloadAvailability()
}

function clearCondition(key: 'category' | 'style') {
    if (key === 'category') {
        form.value.categoryId = 0
        form.value.tagIds = []
    }
    if (key === 'style') form.value.tagIds = []
    persist()
    reloadAvailability()
}

// ---------------------------------------------------------------- 搜索（输入即筛选）

function clearSearchTimer() {
    if (searchTimer) {
        clearTimeout(searchTimer)
        searchTimer = null
    }
}

/**
 * 输入过程中的唯一写入口：
 *  - keywordInput 立即更新，输入框永远显示用户刚敲的内容；
 *  - 与已生效的 form.keyword 不同时，先作废在途请求并进入 loading，
 *    避免旧关键词的人数与新输入短暂并存；
 *  - 300ms 防抖后才把 keywordInput 写入 form.keyword 并重新拉人数。
 */
function onKeywordInput(value: string) {
    keywordInput.value = value
    clearSearchTimer()

    const kw = value.trim()
    if (form.value.region && kw !== form.value.keyword) {
        daySeq += 1
        dayLoading.value = true
        dayCount.value = null
        neighbors.value = []
        neighborsLoading.value = false
    }

    searchTimer = setTimeout(() => {
        searchTimer = null
        applyKeyword()
    }, SEARCH_DEBOUNCE)
}

/** 把输入框当前值落到筛选条件；相同则撤销 pending loading，恢复已有结果 */
function applyKeyword() {
    const kw = keywordInput.value.trim()
    if (kw === form.value.keyword) {
        if (dayCount.value !== null) dayLoading.value = false
        else if (form.value.region) scheduleLoadDay(true)
        else dayLoading.value = false
        return
    }
    form.value.keyword = kw
    persist()
    reloadAvailability()
}

/** 回车：不强制，但按了就立即应用，不再等到 300ms */
function applyKeywordNow() {
    clearSearchTimer()
    applyKeyword()
}

/** 点输入框里的 ×：清空并撤销筛选，输入行保留，方便继续输入 */
function clearKeyword() {
    clearSearchTimer()
    keywordInput.value = ''

    if (form.value.keyword) {
        form.value.keyword = ''
        persist()
    }
    // 无条件重算：即使关键词还没落库（防抖窗口内清空），也要把 pending loading 收掉
    reloadAvailability()
}

/** 点「取消」：关闭输入行 + 清空 + 撤销筛选，恢复完整结果 */
function cancelSearch() {
    clearSearchTimer()
    searchOpen.value = false
    keywordInput.value = ''

    if (form.value.keyword) {
        form.value.keyword = ''
        persist()
    }
    reloadAvailability()
}

function toggleSearch() {
    if (searchOpen.value) {
        cancelSearch()
        return
    }
    searchOpen.value = true
}

function goContact() {
    navigate({ path: '/pages/customer-service/customer-service' })
}

// ---------------------------------------------------------------- 提交

/**
 * 尽力校验：地区树是懒加载的，存在「冷启动恢复条件后没开过地区弹窗、直接查询」的路径，
 * 此时树未加载 → 直接放行（PickedRegion 的类型已保证不会有「有市无区」）。
 */
function assertRegionConsistent(): boolean {
    const r = form.value.region
    if (!r) return false
    if (!regionTree.value.length) return true
    const city = regionTree.value.flatMap((p) => p.cities).find((c) => c.city_code === r.cityCode)
    const ok = !!city?.districts?.some((d) => d.district_code === r.districtCode)
    if (!ok) {
        form.value.region = null
        toast.info('服务地区已失效，请重新选择')
        persist()
        openSheet('region')
    }
    return ok
}

function onCta() {
    if (!form.value.region) {
        openSheet('region')
        return
    }
    if (dayLoading.value || neighborsLoading.value) return
    if (dayCount.value === 0) {
        if (neighbors.value.length) {
            onDatePick(neighbors.value[0].date)
            return
        }
        toast.info('邻近日期也已满，试试其他日期')
        uni.pageScrollTo({ scrollTop: 0, duration: 200 })
        return
    }
    submit()
}

function submit() {
    if (!form.value.region) {
        openSheet('region')
        toast.info('请选择服务地区')
        return
    }
    if (!assertRegionConsistent()) return

    if (form.value.date < minSelectable.value) {
        selectDate(minSelectable.value)
        toast.info(`需提前 ${leadDays.value} 天预订`)
        return
    }

    const q: Record<string, string | number> = { from: 'query' }
    q.date = form.value.date

    const r = form.value.region
    q.province_code = r.provinceCode
    q.province_name = r.provinceName
    q.city_code = r.cityCode
    q.city_name = r.cityName
    q.district_code = r.districtCode
    q.district_name = r.districtName

    if (form.value.categoryId > 0) {
        q.category_id = form.value.categoryId
        if (categoryName.value) q.category_name = categoryName.value
    }
    if (form.value.tagIds.length) q.tag_ids = form.value.tagIds.join(',')
    const kw = form.value.keyword.trim()
    if (kw) q.keyword = kw

    persist()
    // 把这次选择的日期/地区写入预约上下文：从列表进详情、再从分享/搜索进详情，
    // 都能复用同一份条件，不会出现“日期、地点未带入”。
    saveBookingContext({
        date: form.value.date,
        region: {
            province_code: r.provinceCode,
            province_name: r.provinceName,
            city_code: r.cityCode,
            city_name: r.cityName,
            district_code: r.districtCode,
            district_name: r.districtName
        }
    })
    navigate({ path: '/packages/pkg-catalog/pages/staff-list/staff-list', query: q })
}

// ---------------------------------------------------------------- 持久化

function persist() {
    cache.set(CACHE_KEY, {
        v: CACHE_VER,
        date: form.value.date,
        region: form.value.region,
        categoryId: form.value.categoryId,
        tagIds: form.value.tagIds,
        keyword: form.value.keyword
    })
}

/** 旧缓存（v1：有 customDate/sort、无 tagIds/region）逐字段兜底，不整块丢 */
function restore() {
    const saved = cache.get(CACHE_KEY) as Record<string, any> | null
    if (!saved || typeof saved !== 'object') return

    if (typeof saved.date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(saved.date)) {
        form.value.date = saved.date
    }
    const r = saved.region
    if (r && typeof r === 'object' && String(r.cityCode || '') && String(r.districtCode || '')) {
        form.value.region = {
            provinceCode: String(r.provinceCode || ''),
            provinceName: String(r.provinceName || ''),
            cityCode: String(r.cityCode || ''),
            cityName: String(r.cityName || ''),
            districtCode: String(r.districtCode || ''),
            districtName: String(r.districtName || '')
        }
    }
    const cid = Number(saved.categoryId)
    form.value.categoryId = Number.isFinite(cid) && cid > 0 ? cid : 0
    form.value.tagIds = Array.isArray(saved.tagIds)
        ? saved.tagIds
              .map(Number)
              .filter((n: number) => Number.isFinite(n) && n > 0)
              .slice(0, 5)
        : []
    if (typeof saved.keyword === 'string') {
        form.value.keyword = saved.keyword
        keywordInput.value = saved.keyword
    }
}

function clampDate() {
    if (!form.value.date || form.value.date < minSelectable.value) {
        form.value.date = minSelectable.value
    }
    if (form.value.date > maxDate.value) form.value.date = maxDate.value
}

// ---------------------------------------------------------------- 入口传参

function sameRegion(a: PickedRegion | null, b: PickedRegion | null) {
    if (!a || !b) return a === b
    return (
        a.provinceCode === b.provinceCode &&
        a.cityCode === b.cityCode &&
        a.districtCode === b.districtCode
    )
}

/** 首页吉日格等入口：switchTab 不带 query，只能走 tabQuery 中转 */
function applyPending(): boolean {
    // 规则未就绪：先不消费，避免用兜底天数把入口日期夹掉
    if (!ruleReady.value) return false
    const pending = tabQuery.consumeQuery('query')
    if (!pending) return false

    let changed = false

    if (pending.date && /^\d{4}-\d{2}-\d{2}$/.test(String(pending.date))) {
        const date = String(pending.date)
        const next = date < minSelectable.value ? minSelectable.value : date
        if (next !== form.value.date) {
            form.value.date = next
            changed = true
        }
        if (date < minSelectable.value) {
            toast.info(
                date < todayStr.value
                    ? '该日期已过，已选最早可订日'
                    : `需提前 ${leadDays.value} 天预订，已选最早可订日`
            )
        }
    }

    if (pending.city_code && pending.district_code) {
        const next: PickedRegion = {
            provinceCode: String(pending.province_code || ''),
            provinceName: safeDecodeParam(pending.province_name),
            cityCode: String(pending.city_code),
            cityName: safeDecodeParam(pending.city_name),
            districtCode: String(pending.district_code),
            districtName: safeDecodeParam(pending.district_name)
        }
        if (!sameRegion(form.value.region, next)) {
            form.value.region = next
            changed = true
        }
    }

    const cid = Number(pending.category_id || 0)
    if (cid > 0 && cid !== form.value.categoryId) {
        form.value.categoryId = cid
        form.value.tagIds = []
        changed = true
    }

    if (pending.tag_ids) {
        const ids = String(pending.tag_ids)
            .split(',')
            .map(Number)
            .filter((n) => Number.isFinite(n) && n > 0)
            .slice(0, 5)
        if (ids.join(',') !== form.value.tagIds.join(',')) {
            form.value.tagIds = ids
            changed = true
        }
    }

    if (pending.keyword) {
        const kw = safeDecodeParam(pending.keyword)
        if (kw !== form.value.keyword) {
            form.value.keyword = kw
            keywordInput.value = kw
            changed = true
        }
    }

    if (changed) persist()
    return changed
}

/** meta 就绪后回收失效条件（城市/区县停用、分类下线、标签失效） */
function reconcile() {
    let changed = false

    if (form.value.region && regionTree.value.length) {
        const city = regionTree.value
            .flatMap((p) => p.cities)
            .find((c) => c.city_code === form.value.region?.cityCode)
        const ok = !!city?.districts?.some(
            (d) => d.district_code === form.value.region?.districtCode
        )
        if (!ok) {
            form.value.region = null
            toast.info('所选地区已暂停服务，已为你移除')
            changed = true
        }
    }

    if (form.value.categoryId > 0 && categories.value.length) {
        const hit = categories.value.some((c) => c.id === form.value.categoryId)
        if (!hit) {
            form.value.categoryId = 0
            form.value.tagIds = []
            toast.info('所选服务类型已下线，已为你移除')
            changed = true
        }
    }

    if (form.value.tagIds.length && allTags.value.length) {
        const valid = new Set(renderableTags.value.map((t) => t.id))
        const next = form.value.tagIds.filter((id) => valid.has(id))
        if (next.length !== form.value.tagIds.length) {
            form.value.tagIds = next
            changed = true
        }
    }

    if (changed) {
        persist()
        reloadAvailability()
    }
}

// ---------------------------------------------------------------- 生命周期

onLoad(() => {
    restore()
    // 规则未就绪时 applyPending 只返回 false，不会消费入口参数
    applyPending()
    clampDate()
    setViewToDate(form.value.date)
    persist()
    loadCategories()
    loadCalendar()
    if (form.value.region) scheduleLoadDay(true)
    // 不 await：首屏先用兜底范围渲染，配置回来后由 applyScheduleRule 校正
    void loadScheduleRule()
})

onShow(() => {
    badgeStore.refresh()
    const changed = applyPending()
    clampDate()
    if (changed) {
        setViewToDate(form.value.date)
        loadCalendar()
        scheduleLoadDay(true)
    }
    reconcile()
})
</script>

<style lang="scss" scoped>
.query-page {
    /* 高度与底部 TabBar 安全区由 page-shell 承担，不继承 .page-wrap 的 100vh 与重复 padding-bottom */
    min-height: 0;
    padding-top: 16rpx;
    padding-bottom: 0;
}

/* 独立月历卡：唯一一层卡片，--shadow-card 承担分层 */
.cal-card {
    margin: 0 30rpx;
    padding: 26rpx;
    background: var(--c-surface);
    border: 1rpx solid var(--c-card-border);
    border-radius: var(--r-24);
    box-shadow: var(--shadow-card);
}

/* 当日可约摘要与条件条：直接铺在页面底面上，不再套第二层卡 */
.query-avail {
    margin: 24rpx 30rpx 0;
}

.query-conds {
    margin-top: 24rpx;
    padding-left: 30rpx;
}

/* CTA 文档流底部容器：query 是 TabBar 页，不能用 fixed bottom-bar（会遮住 app-tabbar）。
   底部安全区由 page-shell 的 tabbar 留白承担，这里只补内容间距。 */
.cal-cta-wrap {
    margin: 40rpx 30rpx 0;
    padding-bottom: var(--sp-4);
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.cal-hint {
    text-align: center;
    font-size: var(--fs-20);
    line-height: var(--lh-body);
    color: var(--c-ink-3);
}
</style>
