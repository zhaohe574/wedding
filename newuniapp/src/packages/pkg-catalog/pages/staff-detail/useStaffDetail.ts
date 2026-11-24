import { computed, ref, type Ref } from 'vue'
import {
    getScheduleRule,
    getStaffActiveOrder,
    getStaffDetail,
    getStaffDetailV2,
    getStaffPackages,
    getStaffWorks,
    toggleStaffFavorite
} from '@/api/catalog'
import { getReviewTags, getStaffReviews } from '@/api/content'
import { report } from '@/platform/report'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { navigate } from '@/utils/link'
import { hasRegionContext, regionCodeParams, regionParams, type RegionContext } from '@/utils/region'
import { resolveErrorView } from '@/utils/resolveErrorView'
import {
    DEFAULT_BANNER_CONFIG,
    normalizeBannerConfig,
    type BannerConfig,
    type BannerSlide
} from './banner'

export type StaffDetailTabKey = 'about' | 'works' | 'reviews'

export function pad2(n: number) {
    return String(n).padStart(2, '0')
}

export function dateKeyOf(year: number, month: number, day: number) {
    return `${year}-${pad2(month)}-${pad2(day)}`
}

/** 直接进入详情页（无 date 参数）时的默认档期：第二天 */
export function tomorrowKey() {
    const d = new Date()
    d.setDate(d.getDate() + 1)
    return dateKeyOf(d.getFullYear(), d.getMonth() + 1, d.getDate())
}

export function useStaffDetail(options: {
    staffId: Ref<number>
    date: Ref<string>
    region: Ref<RegionContext>
}) {
    const { staffId, date, region } = options
    const appStore = useAppStore()
    const userStore = useUserStore()

    const loading = ref(true)
    const error = ref(false)
    const rawError = ref<unknown>(null)
    const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

    const staff = ref<any>({})
    const banners = ref<any[]>([])
    const bannerConfig = ref<BannerConfig>({ ...DEFAULT_BANNER_CONFIG })
    const bannerExpanded = ref(false)
    const bannerHasWideVisual = ref(true)
    const longDetail = ref('')
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

    const minSelectableDate = ref('')
    const maxSelectableDate = ref('')

    let firstLoadDone = false
    let refreshingOrder = false

    function imageUrl(url?: string) {
        if (!url) return ''
        if (/^https?:/.test(url)) return url
        const domain = (appStore.config && appStore.config.domain) || ''
        return domain ? `${domain}${url}` : url
    }

    const scheduleAvailable = computed(() => staff.value.schedule_available !== false)

    /**
     * 当前用户在该人员「当前日期」的有效订单。
     * 后端已按「本人 + 该人员 + 当前服务日期 + 有效状态」过滤。
     */
    const myOrder = computed(() => staff.value.my_order || null)
    const hasActiveOrder = computed(() => Number(myOrder.value?.order_id || 0) > 0)

    /** 收藏态：v2 下发布尔 0/1，v1 下发 true/false，两种都收 */
    const isFavorite = computed(
        () => Number(staff.value.is_favorite) === 1 || staff.value.is_favorite === true
    )

    /** 推荐态：与首页/列表推荐标同一数据源，v2 已补该字段 */
    const isRecommend = computed(() => Number(staff.value.is_recommend) === 1)

    const fallbackImage = computed(() => imageUrl(staff.value.cover || staff.value.avatar || ''))

    /** cover 是横图可全宽铺；只有 avatar 时用居中方图，避免被 aspectFill 裁成胸口 */
    const fallbackKind = computed<'cover' | 'avatar'>(() =>
        staff.value.cover ? 'cover' : staff.value.avatar ? 'avatar' : 'cover'
    )

    /** 轮播条目：只做字段归一化 + URL 补全，不在这里过滤类型（组件负责失败剔除） */
    const bannerItems = computed<BannerSlide[]>(() => {
        const list = Array.isArray(banners.value) ? banners.value : []
        return list
            .map((item: any, index: number) => {
                const type = Number(item?.type) === 2 ? 'video' : 'image'
                return {
                    key: String(item?.id ?? index),
                    type,
                    src: imageUrl(item?.file_url || item?.fileUrl || ''),
                    poster: imageUrl(item?.cover_url || item?.coverUrl || ''),
                    autoplay: Number(item?.is_autoplay) === 1
                } as BannerSlide
            })
            .filter((item) =>
                item.type === 'video' ? Boolean(item.src || item.poster) : Boolean(item.src)
            )
    })

    const rankName = computed(() => staff.value.rank_name || staff.value.rank || '')

    const eyebrow = computed(() => {
        const role = String(staff.value.role_name || staff.value.category_name || '主持人')
        const city = staff.value.city || staff.value.city_name || ''
        return city ? `${role} · ${city}` : role
    })

    const dateLabel = computed(() => {
        const hit = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(date.value || ''))
        if (!hit) return ''
        const year = Number(hit[1])
        const month = Number(hit[2])
        const day = Number(hit[3])
        const dt = new Date(year, month - 1, day)
        if (Number.isNaN(dt.getTime())) return ''
        return `${month}月${day}日 周${'日一二三四五六'[dt.getDay()]}`
    })

    const availabilityText = computed(() => {
        if (hasActiveOrder.value) return '已为你预约'
        if (scheduleAvailable.value) return '可约'
        const message = String(staff.value.schedule_message || '').trim()
        return message || '当日已满'
    })

    const regionCity = computed(
        () => region.value.city_name || staff.value.city || staff.value.city_name || ''
    )
    const regionReady = computed(() => hasRegionContext(region.value))

    const heroMeta = computed(() => {
        const list: Array<{ text: string; icon?: string }> = []
        const rating = Number(staff.value.rating || 0)
        if (rating > 0) list.push({ text: `${Number(rating.toFixed(1))}`, icon: 'star-fill' })
        const count = Number(staff.value.order_count || 0)
        if (count > 0) list.push({ text: `${count} 场` })
        const years = Number(staff.value.experience_years || 0)
        if (years > 0) list.push({ text: `${years} 年经验` })
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
        return list
    })

    const specs = computed(() => {
        const list: Array<{ key: string; label: string; value: string }> = []
        const tags = Array.isArray(staff.value.tags) ? staff.value.tags.filter(Boolean) : []
        if (tags.length) list.push({ key: 'style', label: '擅长风格', value: tags.join(' / ') })
        const lang = staff.value.languages || staff.value.language || ''
        if (lang) {
            list.push({
                key: 'lang',
                label: '语言',
                value: Array.isArray(lang) ? lang.join(' · ') : String(lang)
            })
        }
        const city = staff.value.service_cities || staff.value.city || ''
        if (city) {
            list.push({
                key: 'city',
                label: '服务城市',
                value: Array.isArray(city) ? city.join(' · ') : String(city)
            })
        }
        return list
    })

    /** 服务说明 = 后台人员资料 service_desc，按换行分段（未配置则整块隐藏） */
    const serviceParagraphs = computed(() => {
        const raw = String(staff.value.service_desc || '').trim()
        if (!raw) return []
        return raw
            .split(/\n+/)
            .map((s) => s.trim())
            .filter(Boolean)
    })

    const startPrice = computed(() => {
        if (
            staff.value.price !== undefined &&
            staff.value.price !== null &&
            staff.value.price !== ''
        ) {
            return staff.value.price as number | string
        }
        const p = packages.value[0]
        const v = p?.price ?? p?.package?.price
        return v === undefined || v === null || v === '' ? null : (v as number | string)
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

    const worksLoadStatus = computed<'idle' | 'loading' | 'nomore' | 'error'>(() => {
        if (worksLoading.value) return 'loading'
        return worksHasMore.value ? 'idle' : 'nomore'
    })

    const reviewLoadStatus = computed<'idle' | 'loading' | 'nomore' | 'error'>(() => {
        if (reviewsLoading.value) return 'loading'
        return reviewsHasMore.value ? 'idle' : 'nomore'
    })

    function tabCount(key: StaffDetailTabKey) {
        if (key === 'works') {
            const n = Number(staff.value.works_count || works.value.length || 0)
            return n > 0 ? n : 0
        }
        if (key === 'reviews') return reviewCount.value
        return 0
    }

    function applyBanner(source: any) {
        bannerConfig.value = normalizeBannerConfig({
            mode: source?.banner_mode ?? source?.mode,
            smallHeight: source?.banner_small_height ?? source?.small_height,
            largeHeight: source?.banner_large_height ?? source?.large_height,
            indicatorStyle: source?.banner_indicator_style ?? source?.indicator_style,
            autoplay: source?.banner_autoplay ?? source?.autoplay,
            interval: source?.banner_interval ?? source?.interval
        })
        bannerExpanded.value = false
    }

    /**
     * 作品分页落表。
     * 后端偶发同页重复 / 翻页边界重叠，这里按 id 去重（保留首次出现顺序）；
     * id 缺失的条目不参与去重，原样保留。翻页整页都是重复时直接判定没有更多。
     */
    function applyWorks(raw: any, reset: boolean) {
        const list = Array.isArray(raw) ? raw : raw?.list || raw?.data || raw?.lists || []
        const seen = new Set<number>()
        const deduped = list.filter((item: any) => {
            const id = Number(item?.id || 0)
            if (!id) return true
            if (seen.has(id)) return false
            seen.add(id)
            return true
        })
        let appended: any[] = deduped
        if (reset) {
            works.value = deduped
        } else {
            const existIds = new Set<number>(
                works.value.map((w: any) => Number(w?.id || 0)).filter(Boolean)
            )
            appended = deduped.filter((item: any) => {
                const id = Number(item?.id || 0)
                return !id || !existIds.has(id)
            })
            works.value = works.value.concat(appended)
        }
        // 翻页响应若是裸数组（无 has_more/total 可读）或整页全是重复，认定已到末页
        const noMore = !reset && (Array.isArray(raw) || appended.length === 0)
        worksHasMore.value = noMore
            ? false
            : !!(raw?.has_more ?? (typeof raw?.total === 'number' && works.value.length < raw.total))
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

    function applyV2(v2: any) {
        const s = v2?.staff || {}
        staff.value = {
            ...s,
            schedule_available: v2?.availability?.is_available !== false,
            schedule_message: v2?.availability?.message || '',
            my_order: v2?.my_order || null
        }
        banners.value = Array.isArray(v2?.banner?.list) ? v2.banner.list : []
        longDetail.value = String(v2?.long_detail || '')
        applyBanner(v2?.banner || {})
        packages.value = Array.isArray(v2?.packages) ? v2.packages : []
        reviewStats.value = v2?.review?.stats || {}
        applyWorks(v2?.works, true)
        applyReviews(v2?.review, true)
        loadReviewTags()
    }

    async function loadScheduleRule() {
        try {
            const rule: any = await getScheduleRule()
            minSelectableDate.value = String(rule?.min_selectable_date || '')
            maxSelectableDate.value = String(rule?.max_selectable_date || '')
        } catch {
            // 规则失败不阻断：日历按「今天起」兜底
        }
    }

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
            // 优先 v2 聚合（1 次请求）；地区编码必须带上，否则价格退回全国价
            try {
                const v2: any = await getStaffDetailV2(staffId.value, {
                    date: date.value || undefined,
                    ...regionCodeParams(region.value)
                })
                applyV2(v2)
                loading.value = false
                return
            } catch {
                // v2 未上线/异常：降级 v1（3 个接口并发），不在此处报错
            }

            const detail: any = await getStaffDetail({
                id: staffId.value,
                date: date.value || undefined,
                ...regionParams(region.value)
            })
            if (!detail || !detail.id) throw new Error('empty')
            staff.value = detail
            banners.value = Array.isArray(detail.banners) ? detail.banners : []
            longDetail.value = String(detail.long_detail || '')
            applyBanner(detail)
            packages.value = Array.isArray(detail.packages) ? detail.packages : []

            const [worksRes, reviewRes, pkgRes] = await Promise.allSettled([
                getStaffWorks({ staff_id: staffId.value, page: 1, page_size: 8 }),
                getStaffReviews({ staff_id: staffId.value, page: 1, page_size: 5 }),
                getStaffPackages({ staff_id: staffId.value, ...regionParams(region.value) })
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
            firstLoadDone = true
        }
    }

    async function refreshActiveOrder() {
        if (!firstLoadDone || !userStore.isLogin || !staffId.value || refreshingOrder) return
        refreshingOrder = true
        try {
            const res = await getStaffActiveOrder({
                staff_id: staffId.value,
                date: date.value || undefined
            })
            staff.value = { ...staff.value, my_order: res?.order || null }
        } catch {
            // 刷新失败保留旧状态，下一次 onShow 再试
        } finally {
            refreshingOrder = false
        }
    }

    async function toggleFavorite() {
        if (!staffId.value) return
        if (!userStore.isLogin) {
            navigate({ path: '/pages/login/login' })
            return
        }
        try {
            const res: any = await toggleStaffFavorite({ id: staffId.value })
            const next =
                res && typeof res.is_favorite !== 'undefined'
                    ? Number(res.is_favorite) === 1
                    : !isFavorite.value
            staff.value = { ...staff.value, is_favorite: next ? 1 : 0 }
        } catch {
            // 请求层已提示，状态保持原样
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

    return {
        loading,
        error,
        rawError,
        view,
        staff,
        bannerItems,
        bannerConfig,
        bannerExpanded,
        bannerHasWideVisual,
        longDetail,
        packages,
        works,
        worksHasMore,
        reviews,
        reviewTags,
        minSelectableDate,
        maxSelectableDate,
        scheduleAvailable,
        myOrder,
        hasActiveOrder,
        isFavorite,
        isRecommend,
        fallbackImage,
        fallbackKind,
        rankName,
        eyebrow,
        dateLabel,
        availabilityText,
        regionCity,
        regionReady,
        heroMeta,
        introParagraphs,
        facts,
        specs,
        serviceParagraphs,
        startPrice,
        reviewCount,
        scoreText,
        starFilled,
        reviewSubText,
        worksLoadStatus,
        reviewLoadStatus,
        tabCount,
        imageUrl,
        loadScheduleRule,
        load,
        refreshActiveOrder,
        toggleFavorite,
        loadWorks,
        loadReviews
    }
}
