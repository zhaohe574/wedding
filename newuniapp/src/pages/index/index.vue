<template>
    <page-shell
        nav
        nav-variant="transparent"
        bg="transparent"
        :show-back="false"
        tabbar
        current="/pages/index/index"
    >
        <!-- ============ 沉浸式 Hero ============ -->
        <!-- 主图铺满到状态栏下：page-shell 不铺底色，主图 position:absolute 自己吃住顶部 -->
        <view class="hero">
            <image class="hero__media" src="/static/images/hero-ceremony.jpg" mode="aspectFill" />
            <!-- 渐变遮罩承载文字层级：上深（压状态栏文字）、下更深（压 CTA） -->
            <view class="hero__scrim" />

            <!-- 品牌位：状态栏高度动态算（px → rpx，不写死） -->
            <view class="hero__brand" :style="{ top: heroTopRpx }">
                <brand-seal size="50" variant="dark" :aria-label="BRAND_NAME" />
                <text class="hero__wm">{{ BRAND_NAME }}</text>
            </view>

            <view class="hero__copy">
                <text class="hero__eyebrow">{{ BRAND_EYEBROW }}</text>
                <view class="hero__title">
                    <text class="hero__title-line">把一生一次的仪式</text>
                    <text class="hero__title-line">交给值得信赖的声音</text>
                </view>
                <text class="hero__scope">西式 · 中式 · 商务主持 · 全程统筹</text>

                <view class="hero__cta">
                    <!-- 唯一实心按钮：主行动 -->
                    <view hover-class="is-pressed" class="hero__primary" @tap="goQuery">
                        <text class="hero__primary-text">查看我的档期</text>
                        <tn-icon name="right" :size="28" color="var(--c-ink)" />
                    </view>
                    <text hover-class="is-pressed" class="hero__ghost" @tap="goAbout"
                        >了解{{ BRAND_NAME }}</text
                    >
                </view>
            </view>
        </view>

        <!-- ============ 数据条（叠压 Hero 底部 -65rpx） ============ -->
        <!-- 口径：stats 只认接口 home-brand.widget.stats；接口没有就整块不渲染，不写死数字 -->
        <view v-if="stats.length || loadingHome" class="stats">
            <view v-for="(s, i) in statsToShow" :key="i" class="stats__item">
                <text class="stats__value">{{ s.value }}</text>
                <text class="stats__label">{{ s.label }}</text>
            </view>
        </view>

        <!-- ============ 四宫格快捷入口（纯前端常量，与业务主链路一一对应） ============ -->
        <view class="quick">
            <view
                v-for="entry in QUICK"
                :key="entry.key"
                hover-class="is-pressed"
                class="quick__item"
                @tap="onQuick(entry)"
            >
                <view class="quick__ic">
                    <tn-icon :name="entry.icon" :size="38" color="var(--c-brand-text)" />
                </view>
                <text class="quick__label">{{ entry.label }}</text>
            </view>
        </view>

        <!-- ============ 本期推荐 ============ -->
        <view class="sec">
            <section-header
                title="本期推荐"
                :more="staff.length ? '查看全部' : ''"
                align="end"
                flush
                @more="goStaffList"
            />
        </view>

        <!-- 推荐位骨架：只占推荐位高度，Hero 与数据条不闪白 -->
        <view v-if="loadingHome" class="hosts">
            <view v-for="n in 2" :key="n" class="host-sk">
                <view class="host-sk__ph" />
                <view class="host-sk__line" />
                <view class="host-sk__line host-sk__line--short" />
            </view>
        </view>

        <view v-else-if="!staff.length" class="inline-note" @tap="loadAll">
            <text class="inline-note__text">{{
                homeError ? '推荐位没加载出来 · 刷新试试' : '本期推荐位正在排期'
            }}</text>
        </view>

        <view v-else class="hosts">
            <view
                v-for="item in staff"
                :key="item.id"
                hover-class="is-pressed"
                class="host"
                @tap="goStaffDetail(item.id)"
            >
                <view class="host__ph">
                    <image
                        class="host__img"
                        :src="imageUrl(item.cover || item.avatar)"
                        mode="aspectFill"
                    />
                    <!-- 职级徽章：接口无 rank 字段，不渲染，不猜 -->
                    <view v-if="item.rank" class="host__rank">
                        <text class="host__rank-text">{{ item.rank }}</text>
                    </view>
                </view>
                <view class="host__body">
                    <view class="host__name-row">
                        <text class="host__name">{{ item.name }}</text>
                        <view v-if="styleTag(item)" class="host__divider" />
                        <text v-if="styleTag(item)" class="host__style">{{ styleTag(item) }}</text>
                    </view>
                    <text v-if="metaText(item)" class="host__meta">{{ metaText(item) }}</text>
                    <view class="host__price">
                        <text class="host__price-value">{{ priceText(item.price) }}</text>
                        <text
                            v-if="
                                item.price !== undefined && item.price !== null && item.price !== ''
                            "
                            class="host__price-unit"
                        >
                            / 起
                        </text>
                    </view>
                </view>
            </view>
        </view>

        <!-- ============ 近期吉日 · 可约情况（深色卡） ============ -->
        <view class="sec sec--gap">
            <section-header title="近期吉日 · 可约情况" align="end" flush />
        </view>

        <view v-if="luckyState === 'ready' && luckySlots.length" class="darkcard">
            <view class="darkcard__top">
                <view class="darkcard__title-wrap">
                    <text class="darkcard__title">近期吉日 · 可约情况</text>
                    <text v-if="luckyRangeText" class="darkcard__sub">{{ luckyRangeText }}</text>
                </view>
                <view hover-class="is-pressed" class="darkcard__link" @tap="goQuery">
                    <text class="darkcard__link-text">全部档期</text>
                    <tn-icon name="right" :size="22" color="var(--c-brand-soft)" />
                </view>
            </view>
            <view class="slot-row">
                <view
                    v-for="slot in luckySlots"
                    :key="slot.date"
                    hover-class="is-pressed"
                    class="slot"
                    @tap="goQueryWithDate(slot.date)"
                >
                    <text class="slot__date">{{ slot.date_text }}</text>
                    <text class="slot__week">{{ slot.weekday_text }}</text>
                    <!-- 剩余名额后端无字段（G-02），不写「剩 N 位」，只给可约/已满 -->
                    <text class="slot__state">{{ slot.remaining_text }}</text>
                </view>
            </view>
        </view>

        <view v-else-if="luckyState === 'loading'" class="darkcard darkcard--sk">
            <view class="darkcard__title-wrap">
                <view class="sk sk--title" />
                <view class="sk sk--sub" />
            </view>
            <view class="slot-row">
                <view v-for="n in 3" :key="n" class="slot slot--sk" />
            </view>
        </view>

        <view v-else-if="luckyState === 'error'" class="inline-note" @tap="loadLucky">
            <text class="inline-note__text">近期档期暂时取不到 · 刷新</text>
        </view>

        <!-- ============ 一次交付，全程在场 ============ -->
        <view class="sec sec--gap">
            <section-header title="一次交付，全程在场" align="end" flush />
        </view>
        <view class="promise">
            <fulfill-timeline :items="FLOW" />
        </view>

        <!-- ============ 页脚 ============ -->
        <view class="foot">
            <view class="foot__rule" />
            <text class="foot__text">{{ BRAND_NAME_EN }}</text>
        </view>

        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 01 首页。
 *
 * 数据口径（本屏最容易犯错的地方）：
 *  - 数据条（场次 / 好评 / 覆盖城市）**只能来自接口** `home-brand` widget 的 stats；
 *    接口没下发就整块不渲染 —— 设计稿里的「1,280 场 / 98% / 32 城」是设计样例数字，不是可写死的常量。
 *  - 吉日卡走 `schedule.luckyDays`（独立请求，失败只降级本卡）；
 *    后端没有「剩余名额」字段（G-02），所以不显示「剩 N 位」，只给「可约 / 已满」。
 *  - 服务流程三行（T-30 / T-01 / T-0）是品牌文案，无接口承载（F-20），按设计稿静态渲染。
 *  - 推荐位「全部 N 位」的 N：v2/home 只回推荐 8 条，没有 total，故不写数字，写「查看全部」。
 */
import { computed, reactive, ref } from 'vue'
import { onLoad, onPullDownRefresh } from '@dcloudio/uni-app'
import { findWidget, parseDecoratePage } from '@shared/contracts'
import { formatMoney } from '@shared/utils/format'
import { getHome, getIndex, getConfig } from '@/api/home'
import { getLuckyDays } from '@/api/catalog'
import { useAppStore } from '@/stores/app'
import { navigate } from '@/utils/link'
import { getDeviceInfo } from '@/platform/device'
import { report } from '@/platform/report'
import { BRAND_NAME, BRAND_NAME_EN, BRAND_EYEBROW } from '@/constants/brand'

interface HomeQuick {
    key: 'query' | 'staff' | 'case' | 'consult'
    label: string
    icon: string
    path: string
}

interface LuckySlot {
    date: string
    date_text: string
    weekday_text: string
    remaining_text: string
}

const appStore = useAppStore()

/**
 * 品牌位距顶（hero 内部定位，不是头部实现）：状态栏高度（px）换算成 rpx，
 * 避免样式里出现 px 字面量。390px 稿宽 → 750rpx；真机按 windowWidth 比例换算。
 * 变量名刻意不叫 statusTopRpx —— 头部高度一律由 nav-bar 自己算（check-header-consistency R-H2）。
 */
const dev = getDeviceInfo()
const heroTopRpx = `${Math.round((dev.statusBarHeight * 750) / (dev.windowWidth || 375)) + 8}rpx`

/** 四宫格：设计稿指定的四条主链路，纯前端常量（HomeQuickEntry） */
const QUICK: HomeQuick[] = [
    { key: 'query', label: '查档期', icon: 'calendar', path: '/pages/query/query' },
    {
        key: 'staff',
        label: '选主持',
        icon: 'microphone',
        path: '/packages/pkg-catalog/pages/staff-list/staff-list'
    },
    {
        key: 'case',
        label: '真实案例',
        icon: 'image',
        path: '/packages/pkg-catalog/pages/case-list/case-list'
    },
    {
        key: 'consult',
        label: '在线咨询',
        icon: 'chat',
        path: '/pages/customer-service/customer-service'
    }
]

/** 服务承诺：设计稿原文，无接口承载（F-20 已登记） */
const FLOW = [
    {
        label: 'T-30',
        title: '流程梳理与台本共创',
        desc: '一对一访谈，把两个家庭的故事写成台上五分钟。'
    },
    {
        label: 'T-01',
        title: '全流程彩排',
        desc: '主持人到场走位，音乐、灯光、动线与伴郎伴娘逐一确认。'
    },
    {
        label: 'T-0',
        title: '提前两小时到场',
        desc: '全程在场控场，突发状况由主持人兜底，不让新人分心。'
    }
]

const loadingHome = ref(true)
const homeError = ref(false)

const page = reactive({ widgets: [] as any[], meta: {} as any, skipped: [] as string[] })
const staff = ref<any[]>([])

/** home-brand widget：数据条唯一来源 */
const brand = computed<any>(() => {
    const w = findWidget<any>(page as any, 'home-brand')
    return w && Number(w.enabled) === 1 ? w : null
})

const stats = computed<Array<{ value: string; label: string }>>(() => {
    const list = brand.value?.stats
    if (!Array.isArray(list)) return []
    return list.filter((s: any) => s && s.value !== undefined && s.value !== null && s.value !== '')
})

/** 加载中显示「—」占位，避免先闪 0 再跳数字 */
const statsToShow = computed(() => {
    if (stats.value.length) return stats.value
    return [
        { value: '—', label: '' },
        { value: '—', label: '' },
        { value: '—', label: '' }
    ]
})

const luckyState = ref<'loading' | 'ready' | 'error'>('loading')
const luckySlots = ref<LuckySlot[]>([])
const luckyRangeText = ref('')

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && (appStore.config as any).domain) || ''
    return domain ? `${domain}${url}` : url
}

/** 风格标签：接口 tags 是字符串数组，取第一个；没有就不显示分隔线 */
function styleTag(item: any) {
    return Array.isArray(item.tags) && item.tags.length ? String(item.tags[0]) : ''
}

/** 卡片副信息：评分 / 评价数按接口字段拼，缺哪个少哪个，不补假数 */
function metaText(item: any) {
    const parts: string[] = []
    if (item.rating !== undefined && item.rating !== null && item.rating !== '') {
        parts.push(`评分 ${item.rating}`)
    }
    if (Number(item.order_count) > 0) parts.push(`${item.order_count} 场服务`)
    return parts.join(' · ')
}

function priceText(price: any) {
    if (price === undefined || price === null || price === '') return '价格待定'
    const num = Number(price)
    if (!Number.isFinite(num)) return '价格待定'
    return `¥${formatMoney(num, { thousand: true, trimZeros: true, decimals: 0 })}`
}

// ---------------------------------------------------------------- 数据加载

async function loadHome() {
    loadingHome.value = true
    homeError.value = false
    try {
        let pageData: { data: any; meta: any } | null = null
        try {
            const home = await getHome()
            pageData = home.page
            staff.value = Array.isArray(home.staff_recommend) ? home.staff_recommend : []
            if (home.config && !appStore.config?.domain) appStore.config = home.config as any
        } catch {
            // 降级：老接口（v2 未上线时）
            const [index, config] = await Promise.all([getIndex(), getConfig().catch(() => null)])
            pageData = (index && index.page) || null
            staff.value = []
            if (config) appStore.config = config
        }

        if (!pageData) throw new Error('empty page')

        const parsed = parseDecoratePage(pageData.data, pageData.meta)
        page.widgets = parsed.widgets
        page.meta = parsed.meta
        page.skipped = parsed.skipped
    } catch {
        // 首屏静态结构（Hero / 快捷入口 / 服务流程）不依赖接口，失败只降级推荐位
        homeError.value = true
        staff.value = []
    } finally {
        loadingHome.value = false
    }
}

function pad2(n: number) {
    return n < 10 ? `0${n}` : String(n)
}

function toDateString(d: Date) {
    return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`
}

/** 吉日卡：取「本月起两个月」区间；不传 year/month 会更短，这里明确给区间 */
async function loadLucky() {
    luckyState.value = 'loading'
    const now = new Date()
    const start = new Date(now.getFullYear(), now.getMonth(), now.getDate())
    const end = new Date(now.getFullYear(), now.getMonth() + 2, 0) // 下个月最后一天
    try {
        const rows = await getLuckyDays({
            start_date: toDateString(start),
            end_date: toDateString(end),
            marriage_only: 1
        })
        const list = Array.isArray(rows) ? rows : []
        luckySlots.value = list.slice(0, 3).map((row: any) => {
            const date = String(row.event_date || '')
            const d = new Date(`${date}T00:00:00`)
            const valid = !Number.isNaN(d.getTime())
            const week = valid
                ? ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][d.getDay()]
                : ''
            return {
                date,
                date_text: valid ? `${pad2(d.getMonth() + 1)}.${pad2(d.getDate())}` : date,
                weekday_text: week,
                // 后端只有 is_lucky_day（本接口已过滤吉日），没有剩余名额 → 只写「可约」
                remaining_text: '可约'
            }
        })
        const months = [
            start.getMonth() + 1,
            start.getMonth() + 2 > 12 ? start.getMonth() + 2 - 12 : start.getMonth() + 2
        ]
        luckyRangeText.value = `${months[0]} 月 – ${months[1]} 月`
        luckyState.value = 'ready'
    } catch {
        luckyState.value = 'error'
    }
}

function loadAll() {
    loadHome()
    loadLucky()
}

// ---------------------------------------------------------------- 跳转

function onQuick(entry: HomeQuick) {
    navigate({ path: entry.path })
}

function goQuery() {
    navigate({ path: '/pages/query/query' })
}

/** 点吉日格带日期进 03 */
function goQueryWithDate(date: string) {
    if (!date) {
        goQuery()
        return
    }
    uni.setStorageSync('query_prefill_date', date)
    goQuery()
}

function goStaffList() {
    navigate({ path: '/packages/pkg-catalog/pages/staff-list/staff-list' })
}

function goStaffDetail(id: number) {
    navigate({ path: '/packages/pkg-catalog/pages/staff-detail/staff-detail', query: { id } })
}

function goAbout() {
    navigate({ path: '/pages/about/about' })
}

onLoad(() => {
    loadAll()
    // 装修解析异常只是降级，不阻断首屏；落一次上报便于排查
    if (page.skipped.length)
        report('page_error', { message: `首页跳过装修组件：${page.skipped.join(',')}` })
})

onPullDownRefresh(() => {
    Promise.all([loadHome(), loadLucky()]).finally(() => uni.stopPullDownRefresh())
})
</script>

<style lang="scss" scoped>
/* ---------------- Hero ---------------- */
.hero {
    position: relative;
    height: 1054rpx; /* 设计稿 548px */
    padding: 0 42rpx; /* 设计稿 22px */
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    overflow: hidden;

    &__media {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        height: 100%;
    }

    /* 遮罩：--c-ink（#181614）的透明度阶梯。渐变无法用 hex 变量算 alpha，
       与 tokens.css 的 --c-mask 同一取值来源；此处是唯一允许出现 rgba 数值的场景（图片压字必须有遮罩） */
    &__scrim {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(
            180deg,
            rgba(24, 22, 20, 0.58) 0%,
            rgba(24, 22, 20, 0.16) 26%,
            rgba(24, 22, 20, 0.48) 58%,
            rgba(24, 22, 20, 0.94) 100%
        );
    }

    &__brand {
        position: absolute;
        left: 42rpx;
        display: flex;
        align-items: center;
        gap: 18rpx; /* 设计稿 9px */
        z-index: 3;
    }

    &__wm {
        font-size: var(--fs-29); /* 设计稿 15px */
        letter-spacing: 0.16em;
        font-weight: var(--fw-medium);
        color: var(--c-on-dark);
    }

    &__copy {
        position: relative;
        z-index: 2;
        padding-bottom: 100rpx; /* 设计稿 52px */
    }

    &__eyebrow {
        display: block;
        font-family: var(--font-serif);
        font-size: var(--fs-xs);
        letter-spacing: 0.24em;
        color: var(--c-brand-soft);
    }

    &__title {
        margin-top: 27rpx; /* 设计稿 14px */
    }

    &__title-line {
        display: block;
        font-size: var(--fs-52); /* 设计稿 27px */
        line-height: 1.52;
        font-weight: var(--fw-medium);
        color: var(--c-on-dark);
        letter-spacing: 0.01em;
    }

    &__scope {
        display: block;
        margin-top: 23rpx; /* 设计稿 12px */
        font-size: var(--fs-xs);
        letter-spacing: 0.08em;
        color: var(--c-on-dark-2);
    }

    &__cta {
        display: flex;
        align-items: center;
        gap: 34rpx; /* 设计稿 18px */
        margin-top: 50rpx; /* 设计稿 26px */
    }

    &__primary {
        height: 85rpx; /* 设计稿 44px */
        padding: 0 46rpx;
        border-radius: var(--r-pill);
        background: var(--c-surface);
        display: inline-flex;
        align-items: center;
        gap: 12rpx;
    }

    &__primary-text {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        letter-spacing: 0.04em;
        color: var(--c-ink);
    }

    /* 次行动降为文字链：首屏只有一个主行动 */
    &__ghost {
        height: 85rpx;
        display: inline-flex;
        align-items: center;
        padding: 0 8rpx;
        font-size: var(--fs-xs);
        letter-spacing: 0.06em;
        color: var(--c-on-dark);
        border-bottom: 1rpx solid var(--c-on-dark-2);
    }
}

/* ---------------- 数据条 ---------------- */
.stats {
    position: relative;
    z-index: 6;
    margin: -65rpx 38rpx 0; /* 设计稿 -34px 叠压 / 左右 20px */
    background: var(--c-surface);
    border-radius: var(--r-md);
    border: 1rpx solid var(--c-line);
    display: flex;
    align-items: center;
    padding: 35rpx 12rpx; /* 设计稿 18px 6px */

    &__item {
        flex: 1;
        text-align: center;
        position: relative;

        & + &::before {
            content: '';
            position: absolute;
            left: 0;
            top: 12rpx;
            bottom: 12rpx;
            width: 1rpx;
            background: var(--c-line);
        }
    }

    &__value {
        display: block;
        font-family: var(--font-serif);
        font-size: var(--fs-40); /* 设计稿 21px */
        font-weight: var(--fw-medium);
        color: var(--c-ink);
        letter-spacing: 0.01em;
    }

    &__label {
        display: block;
        margin-top: 10rpx; /* 设计稿 5px */
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        letter-spacing: 0.06em;
    }
}

/* ---------------- 四宫格 ---------------- */
.quick {
    display: flex;
    padding: 50rpx 27rpx 8rpx; /* 设计稿 26px 14px 4px */

    &__item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 17rpx; /* 设计稿 9px */
        min-height: 88rpx; /* 热区 */
    }

    &__ic {
        width: 88rpx; /* 设计稿 46px */
        height: 88rpx;
        border-radius: var(--r-29); /* 设计稿 15px */
        background: var(--c-brand-bg);
        border: 1rpx solid var(--c-line);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__label {
        font-size: var(--fs-xs);
        letter-spacing: 0.04em;
        color: var(--c-ink-2);
    }
}

/* ---------------- 区块间距 ---------------- */
.sec {
    padding: 0 42rpx;
    margin-top: 50rpx; /* 设计稿 26px */

    &--gap {
        margin-top: 54rpx; /* 设计稿 28px */
    }
}

/* ---------------- 推荐位 ---------------- */
.hosts {
    display: flex;
    gap: 27rpx; /* 设计稿 14px */
    padding: 0 42rpx;
}

.host {
    flex: 1;
    min-width: 0;
    border-radius: var(--r-lg);
    overflow: hidden;
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);

    &__ph {
        position: relative;
        height: 323rpx; /* 设计稿 168px */
        overflow: hidden;
        background: var(--c-neutral-bg);
    }

    &__img {
        width: 100%;
        height: 100%;
    }

    &__rank {
        position: absolute;
        top: 19rpx;
        left: 19rpx;
        z-index: 2;
        height: 38rpx;
        padding: 0 15rpx;
        border-radius: var(--r-xs);
        background: var(--c-toast-bg);
        display: inline-flex;
        align-items: center;
    }

    &__rank-text {
        font-size: var(--fs-xs);
        letter-spacing: 0.12em;
        color: var(--c-brand-soft);
    }

    &__body {
        padding: 21rpx 23rpx 25rpx; /* 设计稿 11px 12px 13px */
    }

    &__name-row {
        display: flex;
        align-items: center;
        gap: 10rpx;
    }

    &__name {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        letter-spacing: 0.02em;
        max-width: 55%;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__divider {
        width: 1rpx;
        height: 18rpx;
        background: var(--c-line-strong);
        flex: none;
    }

    &__style {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        letter-spacing: 0.06em;
        flex: 1;
        min-width: 0;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__meta {
        display: block;
        margin-top: 12rpx;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        letter-spacing: 0.03em;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__price {
        margin-top: 17rpx;
        display: flex;
        align-items: baseline;
        gap: 6rpx;
    }

    &__price-value {
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-brand-text);
    }

    &__price-unit {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

/* 推荐位骨架（不闪白，占位与真卡同高） */
.host-sk {
    flex: 1;
    border-radius: var(--r-lg);
    border: 1rpx solid var(--c-line);
    background: var(--c-surface);
    overflow: hidden;

    &__ph {
        height: 323rpx;
        background: var(--c-neutral-bg);
    }

    &__line {
        height: 22rpx;
        margin: 20rpx 23rpx;
        border-radius: var(--r-xs);
        background: var(--c-neutral-bg);

        &--short {
            width: 50%;
        }
    }
}

/* ---------------- 吉日深色卡 ---------------- */
.darkcard {
    margin: 0 42rpx;
    border-radius: var(--r-md);
    background: var(--c-ink);
    border: 1rpx solid var(--c-line-strong);
    padding: 38rpx 38rpx 35rpx; /* 设计稿 20px 20px 18px */

    &__top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__title-wrap {
        flex: 1;
        min-width: 0;
    }

    &__title {
        display: block;
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-on-dark);
        letter-spacing: 0.04em;
    }

    &__sub {
        display: block;
        margin-top: 10rpx;
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
        letter-spacing: 0.04em;
    }

    &__link {
        display: flex;
        align-items: center;
        gap: 4rpx;
        min-height: 60rpx;
        flex: none;
    }

    &__link-text {
        font-size: var(--fs-xs);
        color: var(--c-brand-soft);
        letter-spacing: 0.04em;
    }

    &--sk {
        display: flex;
        flex-direction: column;
        gap: var(--sp-3);
    }
}

.slot-row {
    display: flex;
    gap: 15rpx; /* 设计稿 8px */
    margin-top: 31rpx; /* 设计稿 16px */
}

.slot {
    flex: 1;
    min-width: 0;
    padding: 23rpx 0;
    border-radius: var(--r-sm);
    border: 1rpx solid var(--c-line-strong);
    background: rgba(255, 255, 255, 0.06);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6rpx;

    &--sk {
        height: 130rpx;
        background: rgba(255, 255, 255, 0.08);
        border-color: transparent;
    }

    &__date {
        font-family: var(--font-serif);
        font-size: var(--fs-29); /* 设计稿 15px */
        color: var(--c-on-dark);
        font-weight: var(--fw-medium);
    }

    &__week {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
        letter-spacing: 0.04em;
    }

    &__state {
        margin-top: 8rpx;
        font-size: var(--fs-xs);
        color: var(--c-brand-soft);
        letter-spacing: 0.03em;
    }
}

.sk {
    height: 24rpx;
    border-radius: var(--r-xs);
    background: rgba(255, 255, 255, 0.12);

    &--title {
        width: 55%;
    }

    &--sub {
        width: 32%;
    }
}

/* ---------------- 服务承诺 ---------------- */
.promise {
    padding: 0 42rpx;
}

/* 行内降级提示：单区块失败不占整屏（F 态） */
.inline-note {
    margin: 0 42rpx;
    min-height: 88rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--r-sm);
    border: 1rpx dashed var(--c-control-border);

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

/* ---------------- 页脚 ---------------- */
.foot {
    padding: 50rpx 42rpx 16rpx; /* 设计稿 26px 22px 8px */
    text-align: center;

    &__rule {
        width: 42rpx;
        height: 1rpx;
        background: var(--c-brand);
        margin: 0 auto 23rpx;
    }

    &__text {
        font-family: var(--font-serif);
        font-size: var(--fs-xs);
        letter-spacing: 0.28em;
        color: var(--c-ink-3);
    }
}
</style>
