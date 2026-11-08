<template>
    <page-shell
        bg="page"
        :bottom-bar="true"
        :title="loading || error ? '' : staff.name || '主持人'"
        nav-variant="scroll-solid"
        :scroll-top="scrollTop"
        :solid-threshold="40"
        :tone="loading || error ? 'dark' : 'light'"
        :nav-capsule-avoid="true"
    >
        <template #nav-right>
            <!-- open-type=share 的 button 必须自己抹掉默认样式 -->
            <button class="nav__share" open-type="share" aria-label="分享这位主持人">
                <tn-icon name="share" :size="36" />
            </button>
        </template>

        <!-- 加载态：hero 主色块 + 姓名位骨架，页签立即可见（§4.5 L） -->
        <view v-if="loading" class="ph">
            <view class="ph__hero">
                <base-skeleton :rows="2" />
            </view>
            <view class="ph__body">
                <base-skeleton :rows="4" />
            </view>
        </view>

        <!-- 错误态：整页重试 + 返回列表 -->
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

        <template v-else>
            <!-- Detail Hero 320px → 615rpx -->
            <view class="hero">
                <image
                    v-if="heroImage"
                    class="hero__img"
                    :src="imageUrl(heroImage)"
                    mode="aspectFill"
                />
                <view v-else class="hero__blank" />
                <view class="hero__scrim" aria-hidden="true" />

                <view class="hero__info">
                    <text class="hero__eyebrow">{{ eyebrow }}</text>
                    <view class="hero__name-row">
                        <text class="hero__name ellipsis">{{ staff.name || '主持人' }}</text>
                        <!-- 无 rank 不显示职级徽章（03 §A 组降级规则） -->
                        <text v-if="rankName" class="hero__badge">{{ rankName }}</text>
                    </view>
                    <view v-if="heroMeta.length" class="hero__meta">
                        <template v-for="(m, i) in heroMeta" :key="i">
                            <text v-if="i > 0" class="hero__meta-sep" aria-hidden="true" />
                            <text class="hero__meta-item">
                                <tn-icon
                                    v-if="m.icon"
                                    :name="m.icon"
                                    :size="20"
                                    class="hero__meta-icon"
                                />
                                {{ m.text }}
                            </text>
                        </template>
                    </view>
                </view>
            </view>

            <!-- 三页签吸顶：sticky-holder 用页面级滚动 + onPageScroll 替代 position:sticky -->
            <sticky-holder :scroll-top="scrollTop" :top="navTotalPx">
                <view class="dtabs">
                    <view
                        v-for="t in TABS"
                        :key="t.key"
                        class="dtab"
                        :class="{ 'is-on': tab === t.key }"
                        hover-class="is-pressed"
                        aria-role="tab"
                        :aria-selected="tab === t.key"
                        @tap="switchTab(t.key)"
                    >
                        <text class="dtab__text">{{ t.label }}</text>
                        <text v-if="tabCount(t.key)" class="dtab__count">{{
                            tabCount(t.key)
                        }}</text>
                    </view>
                </view>
            </sticky-holder>

            <!-- 页签一：详情 -->
            <view v-if="tab === 'about'" class="panel">
                <template v-if="introParagraphs.length">
                    <text v-for="(p, i) in introParagraphs" :key="i" class="intro">{{ p }}</text>
                </template>
                <base-empty
                    v-else
                    status="empty"
                    empty-text="还没有填写介绍"
                    desc="可以先看作品与真实评价"
                />

                <!-- 资历一行：只渲染服务端真实返回的字段，缺了就少一格 -->
                <view v-if="facts.length" class="facts">
                    <template v-for="(f, i) in facts" :key="f.key">
                        <text v-if="i > 0" class="facts__sep" aria-hidden="true" />
                        <view class="facts__item">
                            <text class="facts__num">{{ f.value }}</text>
                            <text class="facts__unit">{{ f.unit }}</text>
                        </view>
                    </template>
                </view>

                <!-- 规格表 2×2 -->
                <view v-if="specs.length" class="specs">
                    <view v-for="s in specs" :key="s.key" class="spec">
                        <text class="spec__k">{{ s.label }}</text>
                        <text class="spec__v">{{ s.value }}</text>
                    </view>
                </view>

                <!-- 服务包含：取当前默认套餐的 desc 拆分，不编条目 -->
                <view v-if="includes.length" class="inc">
                    <text class="inc__title">服务包含</text>
                    <view v-for="(row, i) in includes" :key="i" class="inc__row">
                        <tn-icon name="check" :size="24" class="inc__icon" />
                        <text class="inc__text">{{ row }}</text>
                    </view>
                </view>
            </view>

            <!-- 页签二：作品 -->
            <view v-else-if="tab === 'works'" class="panel">
                <view v-if="workCategories.length" class="wfilter">
                    <chip-group
                        v-model="workCategory"
                        :items="workCategories"
                        size="md"
                        @change="onWorkFilter"
                    />
                </view>

                <view v-if="filteredWorks.length" class="wgrid">
                    <view v-for="w in filteredWorks" :key="w.id" class="wtile">
                        <image
                            v-if="w.cover"
                            class="wtile__img"
                            :src="imageUrl(w.cover)"
                            mode="aspectFill"
                        />
                        <view v-else class="wtile__blank" />
                        <view class="wtile__scrim" aria-hidden="true" />
                        <text v-if="w.caption" class="wtile__cap ellipsis">{{ w.caption }}</text>
                    </view>
                </view>
                <base-empty
                    v-else
                    status="empty"
                    empty-text="作品整理中"
                    desc="可以先看详情与真实评价"
                />

                <load-more
                    v-if="filteredWorks.length"
                    :status="worksLoadStatus"
                    @retry="loadWorks(false)"
                />
            </view>

            <!-- 页签三：评价 -->
            <view v-else class="panel">
                <view v-if="reviewCount" class="rsum">
                    <text class="rsum__num">{{ scoreText }}</text>
                    <view class="rsum__mid">
                        <view class="stars" :aria-label="`评分 ${scoreText} 分，满分 5 分`">
                            <tn-icon
                                v-for="n in 5"
                                :key="n"
                                name="star-fill"
                                :size="22"
                                :class="['stars__i', { 'is-off': n > starFilled }]"
                            />
                        </view>
                        <text class="rsum__sub">{{ reviewSubText }}</text>
                    </view>
                    <view v-if="reviewTags.length" class="rtags">
                        <text v-for="t in reviewTags" :key="t" class="rtags__i">{{ t }}</text>
                    </view>
                </view>

                <view v-if="reviews.length" class="reviews">
                    <view v-for="r in reviews" :key="r.id" class="review">
                        <view class="review__head">
                            <text class="review__avatar">{{ firstChar(r.user?.nickname) }}</text>
                            <text class="review__name ellipsis">{{
                                r.user?.nickname || '匿名用户'
                            }}</text>
                            <text v-if="r.create_time" class="review__date">{{
                                formatDay(r.create_time)
                            }}</text>
                            <view class="stars stars--sm" :aria-label="`${r.score} 分`">
                                <tn-icon
                                    v-for="n in 5"
                                    :key="n"
                                    name="star-fill"
                                    :size="18"
                                    :class="['stars__i', { 'is-off': n > Number(r.score || 0) }]"
                                />
                            </view>
                        </view>
                        <text v-if="r.content" class="review__text">{{ r.content }}</text>
                    </view>
                    <load-more :status="reviewLoadStatus" @retry="loadReviews(false)" />
                </view>
                <base-empty
                    v-else
                    status="empty"
                    empty-text="还没有评价"
                    desc="这位主持人刚加入，先看看作品"
                />
            </view>
        </template>

        <!-- 吸底操作栏：服务起价 + 先咨询 / 预约档期 -->
        <template #bottom>
            <bottom-bar v-if="!loading && !error">
                <view class="abp">
                    <text class="abp__label">服务起价</text>
                    <price v-if="startPrice != null" size="md" :content="startPrice" />
                    <text v-else class="abp__na">面议</text>
                    <text v-if="!scheduleAvailable" class="abp__note">{{ fullNote }}</text>
                </view>
                <template #actions>
                    <view class="abtns">
                        <base-button
                            text="先咨询"
                            variant="ghost"
                            size="md"
                            :block="false"
                            @click="goConsult"
                        />
                        <base-button
                            :text="scheduleAvailable ? '预约档期' : '加入候补'"
                            variant="ink"
                            size="md"
                            :block="false"
                            @click="handlePrimaryAction"
                        />
                    </view>
                </template>
            </bottom-bar>
        </template>
    </page-shell>
    <app-toast />
</template>

<script setup lang="ts">
/**
 * 05 主持人详情（设计稿 2432–2585）。
 *
 * 结构：hero + 三页签（详情 / 作品 / 评价）+ 吸底栏。
 * 数据：优先 v2 聚合 getStaffDetailV2（1 次请求），失败降级 v1（detail + works + reviews 并发）。
 *
 * 降级口径（03 §A 组）：
 *  - 无 rank → 不渲染职级徽章；
 *  - 无 schedule_remaining → 不出现「仅剩 N 档」；
 *  - 作品条目只有 title/cover（无年份/场景/人数）→ 图墙只标标题，筛选条在有分类字段时才出现；
 *  - 评价推荐率、好评率只用 stats.good / stats.total 真实比值，算不出就不显示。
 *
 * 吸顶实现：小程序里 scroll-view 内 position:sticky 不生效，本页用「页面级滚动 + onPageScroll
 * 把 scrollTop 传给 sticky-holder」，由它渲染等高空位（不跳动）。详见 sticky-holder 注释。
 */
import { computed, ref } from 'vue'
import { onLoad, onPageScroll, onShareAppMessage } from '@dcloudio/uni-app'
import { getStaffDetail, getStaffDetailV2, getStaffWorks, getStaffPackages } from '@/api/catalog'
import { getStaffReviews, getReviewTags } from '@/api/content'
import { useAppStore } from '@/stores/app'
import { getNavBarHeight } from '@/platform/device'
import { navigate } from '@/utils/link'
import { report } from '@/platform/report'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { formatDate } from '@shared/utils/format'
import { BRAND_NAME } from '@/constants/brand'

type TabKey = 'about' | 'works' | 'reviews'

const appStore = useAppStore()

/** 导航总高 = 状态栏 + 44px 内容行（吸顶停留位置）；口径与 nav-bar 占位块完全一致 */
const navTotalPx = getNavBarHeight()

const TABS: Array<{ key: TabKey; label: string }> = [
    { key: 'about', label: '详情' },
    { key: 'works', label: '作品' },
    { key: 'reviews', label: '评价' }
]

const staffId = ref(0)
const date = ref('')
const loading = ref(true)
const error = ref(false)
const tab = ref<TabKey>('about')
const scrollTop = ref(0)

const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const staff = ref<any>({})
const banners = ref<any[]>([])
const packages = ref<any[]>([])
const works = ref<any[]>([])
const reviews = ref<any[]>([])
const reviewStats = ref<any>({})
const reviewTags = ref<string[]>([])

const worksPage = ref(1)
const worksHasMore = ref(false)
const worksLoading = ref(false)
const reviewsPage = ref(1)
const reviewsHasMore = ref(false)
const reviewsLoading = ref(false)
const workCategory = ref<string | number>('all')

const scheduleAvailable = computed(() => staff.value.schedule_available !== false)

/* ---------------- 派生展示（全部来自真实字段，缺则隐藏） ---------------- */

const heroImage = computed(() => {
    const first = banners.value[0]
    return (first && (first.image || first.url)) || staff.value.cover || staff.value.avatar || ''
})

const rankName = computed(() => staff.value.rank_name || staff.value.rank || '')

/** eyebrow：角色 · 城市（两者都没有时只写「主持人」） */
const eyebrow = computed(() => {
    const role = String(staff.value.role_name || '主持人')
    const city = staff.value.city || staff.value.city_name || ''
    return city ? `${role} · ${city}` : role
})

const heroMeta = computed(() => {
    const list: Array<{ text: string; icon?: string }> = []
    const rating = Number(staff.value.rating || 0)
    if (rating > 0) list.push({ text: `${Number(rating.toFixed(1))}`, icon: 'star-fill' })
    const count = Number(staff.value.order_count || 0)
    if (count > 0) list.push({ text: `${count} 场` })
    const tags = Array.isArray(staff.value.tags) ? staff.value.tags.filter(Boolean) : []
    if (tags.length) list.push({ text: tags.slice(0, 2).join(' · ') })
    return list
})

const introParagraphs = computed(() => {
    const raw = String(staff.value.intro || staff.value.profile || '').trim()
    if (!raw) return []
    return raw
        .split(/\n+/)
        .map((s) => s.trim())
        .filter(Boolean)
})

const facts = computed(() => {
    const list: Array<{ key: string; value: string; unit: string }> = []
    const count = Number(staff.value.order_count || 0)
    if (count > 0) list.push({ key: 'count', value: String(count), unit: '场婚礼' })
    const rating = Number(staff.value.rating || 0)
    if (rating > 0) list.push({ key: 'rating', value: rating.toFixed(1), unit: '分' })
    const good = Number(reviewStats.value.good || 0)
    const total = Number(reviewStats.value.total || 0)
    if (good > 0 && total > 0 && good <= total) {
        list.push({ key: 'good', value: `${Math.round((good / total) * 100)}%`, unit: '好评' })
    }
    const years = Number(staff.value.experience_years || 0)
    if (years > 0) list.push({ key: 'years', value: String(years), unit: '年经验' })
    return list
})

const specs = computed(() => {
    const list: Array<{ key: string; label: string; value: string }> = []
    const tags = Array.isArray(staff.value.tags) ? staff.value.tags.filter(Boolean) : []
    if (tags.length) list.push({ key: 'style', label: '擅长风格', value: tags.join(' / ') })
    const lang = staff.value.languages || staff.value.language || ''
    if (lang)
        list.push({
            key: 'lang',
            label: '语言',
            value: Array.isArray(lang) ? lang.join(' · ') : String(lang)
        })
    const city = staff.value.service_cities || staff.value.city || ''
    if (city) {
        list.push({
            key: 'city',
            label: '服务城市',
            value: Array.isArray(city) ? city.join(' · ') : String(city)
        })
    }
    const years = Number(staff.value.experience_years || 0)
    if (years > 0) list.push({ key: 'years', label: '从业年限', value: `${years} 年` })
    return list
})

/** 服务包含 = 默认套餐 desc 按 + / 、/ · 拆行（不编造条目） */
const includes = computed(() => {
    const first = packages.value[0]
    const desc = String(
        first?.desc || first?.package?.desc || first?.package?.description || ''
    ).trim()
    if (!desc) return []
    return desc
        .split(/[+＋、·,，/]/)
        .map((s) => s.trim())
        .filter(Boolean)
        .slice(0, 6)
})

const startPrice = computed(() => {
    if (staff.value.price !== undefined && staff.value.price !== null && staff.value.price !== '') {
        return staff.value.price as number | string
    }
    const p = packages.value[0]
    const v = p?.price ?? p?.package?.price
    return v === undefined || v === null || v === '' ? null : (v as number | string)
})

const fullNote = computed(() => {
    const msg = String(staff.value.schedule_message || '').trim()
    return msg || '这天已满'
})

const reviewCount = computed(() => Number(reviewStats.value.total || reviews.value.length || 0))
const scoreText = computed(() => {
    const s = Number(reviewStats.value.score || staff.value.rating || 0)
    return s > 0 ? s.toFixed(1) : '—'
})
const starFilled = computed(() => {
    const s = Number(reviewStats.value.score || staff.value.rating || 0)
    return s > 0 ? Math.round(s) : 0
})
const reviewSubText = computed(() => {
    const total = reviewCount.value
    const good = Number(reviewStats.value.good || 0)
    if (total > 0 && good > 0 && good <= total) {
        return `${total} 条 · ${Math.round((good / total) * 100)}% 推荐`
    }
    return total > 0 ? `${total} 条评价` : ''
})

function tabCount(key: TabKey) {
    if (key === 'works') {
        const n = Number(staff.value.works_count || works.value.length || 0)
        return n > 0 ? n : 0
    }
    if (key === 'reviews') return reviewCount.value
    return 0
}

/* ---------------- 作品 ---------------- */

const workCategories = computed(() => {
    const set = new Set<string>()
    for (const w of works.value) {
        const c = w.category_name || w.tag || w.style
        if (c) set.add(String(c))
    }
    if (!set.size) return []
    return [
        { label: '全部', value: 'all' },
        ...Array.from(set).map((v) => ({ label: v, value: v }))
    ]
})

const filteredWorks = computed(() => {
    if (workCategory.value === 'all') return works.value
    return works.value.filter(
        (w) => String(w.category_name || w.tag || w.style) === String(workCategory.value)
    )
})

const worksLoadStatus = computed<'idle' | 'loading' | 'nomore' | 'error'>(() => {
    if (worksLoading.value) return 'loading'
    return worksHasMore.value ? 'idle' : 'nomore'
})

const reviewLoadStatus = computed<'idle' | 'loading' | 'nomore' | 'error'>(() => {
    if (reviewsLoading.value) return 'loading'
    return reviewsHasMore.value ? 'idle' : 'nomore'
})

function onWorkFilter() {
    // 筛选不重新请求：本地按分类字段过滤，避免空分类反复打接口
    if (workCategory.value !== 'all' && worksHasMore.value) loadWorks(false)
}

/* ---------------- 工具 ---------------- */

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

function firstChar(name?: string) {
    const s = String(name || '').trim()
    return s ? Array.from(s)[0] : '匿'
}

function formatDay(v: any) {
    return formatDate(v, 'YYYY.MM.DD', '')
}

function goBack() {
    const pages = getCurrentPages()
    if (pages.length > 1) uni.navigateBack()
    else uni.switchTab({ url: '/pages/index/index' })
}

function switchTab(key: TabKey) {
    if (tab.value === key) return
    tab.value = key
    // 页签切换后把滚动停在吸顶位置（不是回到 0），否则会丢失 hero
    uni.pageScrollTo({ scrollTop: uni.upx2px(615) - navTotalPx, duration: 180 })
    if (key === 'works' && !works.value.length) loadWorks(true)
    if (key === 'reviews' && !reviews.value.length) loadReviews(true)
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        load()
        return
    }
    if (kind === 'reselect' || kind === 'back') {
        goBack()
        return
    }
    if (kind === 'login') {
        navigate({ path: '/pages/login/login' })
    }
}

function goConsult() {
    navigate({
        path: '/pages/customer-service/customer-service',
        query: { scene: 'staff', staff_id: staffId.value }
    })
}

/** 主操作：可约 → 06 选套餐；不可约 → 19 候补申请 */
function handlePrimaryAction() {
    const pkg = packages.value[0]
    if (scheduleAvailable.value) {
        navigate({
            path: '/packages/pkg-catalog/pages/booking/booking',
            query: {
                staff_id: staffId.value,
                package_id: pkg ? pkg.package_id || pkg.id : '',
                date: date.value
            }
        })
        return
    }
    navigate({
        path: '/packages/pkg-order/pages/waitlist/apply',
        query: {
            staff_id: staffId.value,
            package_id: pkg ? pkg.package_id || pkg.id : '',
            date: date.value
        }
    })
}

/* ---------------- 数据 ---------------- */

async function load() {
    if (!staffId.value) {
        rawError.value = new Error('missing-staff-id')
        error.value = true
        loading.value = false
        return
    }
    loading.value = true
    error.value = false
    try {
        // 优先 v2 聚合（1 次请求）
        try {
            const v2: any = await getStaffDetailV2(staffId.value, { date: date.value || undefined })
            applyV2(v2)
            loading.value = false
            return
        } catch (e) {
            // v2 未上线/异常：降级 v1（3 个接口并发），不在此处报错
        }

        const detail: any = await getStaffDetail({
            id: staffId.value,
            date: date.value || undefined
        })
        if (!detail || !detail.id) throw new Error('empty')
        staff.value = detail
        banners.value = Array.isArray(detail.banners) ? detail.banners : []
        packages.value = Array.isArray(detail.packages) ? detail.packages : []

        const [worksRes, reviewRes, pkgRes] = await Promise.allSettled([
            getStaffWorks({ staff_id: staffId.value, page: 1, page_size: 8 }),
            getStaffReviews({ staff_id: staffId.value, page: 1, page_size: 5 }),
            getStaffPackages({ staff_id: staffId.value })
        ])
        if (worksRes.status === 'fulfilled') applyWorks(worksRes.value as any, true)
        if (reviewRes.status === 'fulfilled') applyReviews(reviewRes.value as any, true)
        if (pkgRes.status === 'fulfilled' && !packages.value.length) {
            const raw: any = pkgRes.value
            packages.value = Array.isArray(raw) ? raw : raw?.lists || raw?.data || []
        }
        loadReviewTags()
    } catch (e) {
        rawError.value = e
        error.value = true
        report('api_error', { endpoint: 'staff.detail', message: '主持人详情加载失败' })
    } finally {
        loading.value = false
    }
}

function applyV2(v2: any) {
    const s = v2?.staff || {}
    staff.value = {
        ...s,
        schedule_available: v2?.availability?.is_available !== false,
        schedule_message: v2?.availability?.message || ''
    }
    banners.value = Array.isArray(v2?.banner?.list) ? v2.banner.list : []
    packages.value = Array.isArray(v2?.packages) ? v2.packages : []
    reviewStats.value = v2?.review?.stats || {}
    applyWorks(v2?.works, true)
    applyReviews(v2?.review, true)
    loadReviewTags()
}

function applyWorks(raw: any, reset: boolean) {
    const list = Array.isArray(raw) ? raw : raw?.list || raw?.data || raw?.lists || []
    works.value = reset ? list : works.value.concat(list)
    worksHasMore.value = !!(
        raw?.has_more ??
        (typeof raw?.total === 'number' && works.value.length < raw.total)
    )
    worksLoading.value = false
}

function applyReviews(raw: any, reset: boolean) {
    const list = Array.isArray(raw) ? raw : raw?.list || raw?.data || raw?.lists || []
    reviews.value = reset ? list : reviews.value.concat(list)
    if (raw?.stats) reviewStats.value = raw.stats
    reviewsHasMore.value = !!(
        raw?.has_more ??
        (typeof raw?.total === 'number' && reviews.value.length < raw.total)
    )
    reviewsLoading.value = false
}

async function loadReviewTags() {
    try {
        const res: any = await getReviewTags({ staff_id: staffId.value })
        const raw = Array.isArray(res) ? res : res?.lists || res?.data || []
        reviewTags.value = raw
            .map((t: any) => (typeof t === 'string' ? t : t?.name || t?.tag_name || ''))
            .filter(Boolean)
            .slice(0, 3)
    } catch {
        reviewTags.value = []
    }
}

async function loadWorks(reset: boolean) {
    if (worksLoading.value) return
    if (reset) {
        worksPage.value = 1
        worksHasMore.value = true
    }
    if (!worksHasMore.value) return
    worksLoading.value = true
    try {
        const raw: any = await getStaffWorks({
            staff_id: staffId.value,
            page: worksPage.value,
            page_size: 8
        })
        applyWorks(raw, reset)
        worksPage.value += 1
    } catch {
        worksLoading.value = false
        worksHasMore.value = false
    }
}

async function loadReviews(reset: boolean) {
    if (reviewsLoading.value) return
    if (reset) {
        reviewsPage.value = 1
        reviewsHasMore.value = true
    }
    if (!reviewsHasMore.value) return
    reviewsLoading.value = true
    try {
        const raw: any = await getStaffReviews({
            staff_id: staffId.value,
            page: reviewsPage.value,
            page_size: 5
        })
        applyReviews(raw, reset)
        reviewsPage.value += 1
    } catch {
        reviewsLoading.value = false
        reviewsHasMore.value = false
    }
}

/**
 * 滚动只做一件事：把 scrollTop 交给 sticky-holder（受控吸顶）与 nav-bar（scroll-solid 切实底）。
 * 实底阈值由 nav-bar 的 solidThreshold 自己判，页面不再维护一份 navSolid 状态。
 * 不在 onPageScroll 里做任何数据计算 —— 每帧 setData 是详情页卡顿的头号原因。
 */
onPageScroll((e: any) => {
    scrollTop.value = Number(e?.scrollTop || 0)
})

onShareAppMessage(() => ({
    title: staff.value.name ? `${staff.value.name} · 婚礼主持` : `${BRAND_NAME}婚礼主持`,
    path: `/packages/pkg-catalog/pages/staff-detail/staff-detail?id=${staffId.value}`
}))

onLoad((options: any) => {
    staffId.value = Number(options?.id || 0)
    date.value = options?.date ? String(options.date) : ''
    if (!staffId.value) {
        rawError.value = new Error('missing-staff-id')
        error.value = true
        loading.value = false
        return
    }
    load()
})
</script>

<style lang="scss" scoped>
/* ========== 导航右侧分享按钮（open-type=share 的 button 必须自己抹掉默认样式） ========== */
.nav__share {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 88rpx;
    height: 88rpx;
    padding: 0;
    margin: 0;
    border: 0;
    background: transparent;
    line-height: var(--lh-none);
    color: inherit;

    &::after {
        border: 0;
    }
}

/* ========== 骨架 ========== */
.ph {
    &__hero {
        height: 615rpx;
        padding: var(--sp-4);
        background: var(--c-hero-bg);
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
    }

    &__body {
        padding: var(--sp-4);
    }
}

/* ========== Hero 320px → 615rpx ========== */
.hero {
    position: relative;
    height: 615rpx;
    overflow: hidden;
    background: var(--c-ink);

    &__img {
        width: 100%;
        height: 100%;
    }

    &__blank {
        width: 100%;
        height: 100%;
        background: var(--c-hero-bg);
    }

    /* 图上渐变遮罩：中性黑透明（token 无媒体压暗色阶，此处为唯一例外，见交付说明） */
    &__scrim {
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        background: linear-gradient(
            180deg,
            rgba(14, 12, 10, 0.5) 0%,
            rgba(14, 12, 10, 0.06) 32%,
            rgba(14, 12, 10, 0.3) 62%,
            rgba(14, 12, 10, 0.92) 100%
        );
    }

    &__info {
        position: absolute;
        left: 42rpx;
        right: 42rpx;
        bottom: 46rpx;
        z-index: 2;
        color: var(--c-on-dark);
    }

    &__eyebrow {
        display: block;
        font-family: var(--font-serif);
        font-size: var(--fs-18);
        letter-spacing: 2rpx;
        text-transform: uppercase;
        color: var(--c-brand-soft);
    }

    &__name-row {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
        margin-top: 21rpx;
        min-width: 0;
    }

    &__name {
        font-size: var(--fs-52);
        font-weight: var(--fw-semibold);
        letter-spacing: 2rpx;
        line-height: var(--lh-tight);
        max-width: 460rpx;
    }

    &__badge {
        flex: none;
        height: 34rpx;
        padding: 0 10rpx;
        border-radius: var(--r-xs);
        border: 1rpx solid var(--c-brand-soft);
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
        font-size: var(--fs-18);
        line-height: 32rpx;
    }

    &__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: var(--sp-2);
        margin-top: 19rpx;
        font-size: var(--fs-22);
        color: var(--c-on-dark-2);
    }

    &__meta-item {
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
    }

    &__meta-icon {
        color: var(--c-brand-soft);
    }

    &__meta-sep {
        width: 1rpx;
        height: 18rpx;
        background: var(--c-on-dark-2);
        opacity: 0.4;
    }
}

/* ========== 三页签 ========== */
.dtabs {
    display: flex;
    align-items: stretch;
    height: 88rpx;
    background: var(--c-bg);
    border-bottom: 1rpx solid var(--c-line);
}

.dtab {
    flex: 1;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6rpx;
    font-size: 24rpx;
    color: var(--c-ink-3);

    &__text {
        line-height: var(--lh-none);
    }

    &__count {
        font-family: var(--font-serif);
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }

    &.is-on {
        color: var(--c-ink);
        font-weight: var(--fw-semibold);

        .dtab__count {
            color: var(--c-brand-text);
        }

        &::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 42rpx;
            height: 4rpx;
            border-radius: var(--r-2);
            background: var(--c-ink);
        }
    }
}

/* ========== 详情页签 ========== */
.panel {
    padding: 34rpx 42rpx 0;
}

.intro {
    display: block;
    font-size: var(--fs-25);
    line-height: var(--lh-loose);
    color: var(--c-ink-2);

    & + & {
        margin-top: 18rpx;
    }
}

.facts {
    display: flex;
    align-items: baseline;
    flex-wrap: wrap;
    gap: var(--sp-2);
    margin-top: 23rpx;
    font-size: 20rpx;
    color: var(--c-ink-3);

    &__sep {
        width: 1rpx;
        height: 16rpx;
        background: var(--c-line-strong);
    }

    &__item {
        display: inline-flex;
        align-items: baseline;
        gap: 4rpx;
    }

    &__num {
        font-family: var(--font-serif);
        font-size: 24rpx;
        color: var(--c-ink);
    }
}

.specs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18rpx 27rpx;
    margin-top: 23rpx;
    padding-top: 23rpx;
    border-top: 1rpx solid var(--c-line);
}

.spec {
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);
    min-width: 0;

    &__k {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
        letter-spacing: 1rpx;
    }

    &__v {
        font-size: var(--fs-22);
        color: var(--c-ink);
        line-height: var(--lh-body);
    }
}

.inc {
    margin-top: 31rpx;
    padding-top: 27rpx;
    border-top: 1rpx solid var(--c-line);

    &__title {
        display: block;
        font-size: var(--fs-22);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        margin-bottom: 19rpx;
    }

    &__row {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
        padding: 10rpx 0;
    }

    &__icon {
        color: var(--c-success);
        flex: none;
    }

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-22);
        color: var(--c-ink-2);
        line-height: var(--lh-body);
    }
}

/* ========== 作品页签 ========== */
.wfilter {
    display: flex;
    gap: var(--sp-2);
    margin-bottom: 25rpx;
}

.wgrid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18rpx;
}

.wtile {
    position: relative;
    height: 242rpx;
    border-radius: var(--r-md);
    overflow: hidden;
    background: var(--c-hero-bg);

    &__img {
        width: 100%;
        height: 100%;
    }

    &__blank {
        width: 100%;
        height: 100%;
        background: var(--c-hero-bg);
    }

    &__scrim {
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        background: linear-gradient(
            180deg,
            rgba(16, 14, 12, 0) 40%,
            rgba(16, 14, 12, 0.55) 74%,
            rgba(16, 14, 12, 0.9) 100%
        );
    }

    &__cap {
        position: absolute;
        left: 18rpx;
        right: 12rpx;
        bottom: 16rpx;
        z-index: 2;
        font-size: var(--fs-18);
        color: var(--c-on-dark);
    }
}

/* ========== 评价页签 ========== */
.rsum {
    display: flex;
    align-items: center;
    gap: var(--sp-4);
    padding-bottom: 29rpx;
    border-bottom: 1rpx solid var(--c-line);

    &__num {
        font-family: var(--font-serif);
        font-size: var(--fs-63);
        font-weight: var(--fw-medium);
        line-height: var(--lh-none);
        color: var(--c-ink);
    }

    &__mid {
        flex: 1;
        min-width: 0;
    }

    &__sub {
        display: block;
        margin-top: 12rpx;
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }
}

.stars {
    display: flex;
    align-items: center;
    gap: 4rpx;

    &__i {
        /* G1 ⑧：已选星原 --c-brand(#c6a15b) 对白底 2.39:1，与评分控件真源
           form-field.vue 的 --c-warning 对齐（一个语义一个值，不用两套） */
        color: var(--c-warning);

        &.is-off {
            /* G1 ⑨：未选星原 --c-line-strong 仅 1.60:1（看不出有几颗星）→ --c-ink-3 5.67:1 */
            color: var(--c-ink-3);
        }
    }

    &--sm {
        margin-left: auto;
    }
}

.rtags {
    display: flex;
    flex-wrap: wrap;
    gap: 10rpx;
    justify-content: flex-end;
    max-width: 280rpx;

    &__i {
        height: 38rpx;
        padding: 0 14rpx;
        border-radius: var(--r-xs);
        border: 1rpx solid var(--c-primary-border);
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
        font-size: var(--fs-18);
        line-height: 36rpx;
        white-space: nowrap;
    }
}

.reviews {
    display: flex;
    flex-direction: column;
}

.review {
    padding: 25rpx 0;
    border-bottom: 1rpx solid var(--c-line);

    &:last-child {
        border-bottom: 0;
    }

    &__head {
        display: flex;
        align-items: center;
        gap: 14rpx;
    }

    &__avatar {
        flex: none;
        width: 46rpx;
        height: 46rpx;
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-primary-border);
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
        font-family: var(--font-serif);
        font-size: 20rpx;
        text-align: center;
        line-height: 44rpx;
    }

    &__name {
        font-size: var(--fs-22);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        max-width: 220rpx;
    }

    &__date {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }

    &__text {
        display: block;
        margin-top: 16rpx;
        font-size: var(--fs-22);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
    }
}

/* ========== 吸底栏 ========== */
.abp {
    display: flex;
    flex-direction: column;
    gap: 4rpx;
    min-width: 0;

    &__label {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
        letter-spacing: 1rpx;
    }

    &__na {
        font-size: 24rpx;
        color: var(--c-ink-2);
    }

    &__note {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }
}

.abtns {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
}
</style>
