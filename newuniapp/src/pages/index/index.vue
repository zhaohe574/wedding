<template>
    <view class="home">
        <nav-bar title="" transparent :show-back="false" />

        <!-- 搜索入口：浮在轮播之上，视觉上属于导航区 -->
        <view hover-class="is-pressed" class="search-entry" :style="{ paddingTop: searchTop + 'px' }" @tap="goSearch">
            <view class="search-entry__box">
                <u-icon name="search" :size="24" color="var(--c-ink-3)" />
                <text class="search-entry__ph">搜人员、资讯</text>
            </view>
        </view>

        <!-- 首屏骨架：不等接口，先占位 -->
        <view v-if="loading && !ready" class="home__skeleton page-pad">
            <view class="skeleton-hero" />
            <base-skeleton :rows="3" />
        </view>

        <!-- 接口失败：可重试，不白屏 -->
        <base-empty v-else-if="error" status="error" error-kind="network" fixed @retry="load" />

        <template v-else>
            <!-- 轮播 -->
            <view v-if="banners.length" class="hero">
                <swiper
                    class="hero__swiper"
                    :circular="true"
                    :autoplay="true"
                    :interval="5000"
                    :indicator-dots="banners.length > 1"
                    indicator-color="rgba(255,255,255,.35)"
                    indicator-active-color="#FFFFFF"
                >
                    <swiper-item v-for="(item, i) in banners" :key="i" @tap="navigate(item.link)">
                        <image class="hero__img" :src="imageUrl(item.image || item.bg)" mode="aspectFill" />
                        <view class="hero__mask" />
                        <view v-if="item.name || item.slogan" class="hero__text">
                            <text v-if="item.slogan" class="hero__slogan">{{ item.slogan }}</text>
                            <text v-if="item.name" class="hero__name">{{ item.name }}</text>
                        </view>
                    </swiper-item>
                </swiper>
            </view>

            <!-- 无轮播时的品牌头 -->
            <view v-else class="brand-head">
                <text class="brand-head__greeting">{{ brand?.greeting || 'Hello,' }}</text>
                <text class="brand-head__name">{{ brand?.team_name || '婚礼服务团队' }}</text>
                <text class="brand-head__sub">{{ brand?.subtitle || '专业团队，用心服务' }}</text>
            </view>

            <!-- 主 CTA -->
            <view class="cta-wrap page-pad">
                <view hover-class="is-pressed" class="cta" @tap="goQuery">
                    <view class="cta__left">
                        <text class="cta__title">查档期</text>
                        <text class="cta__desc">选日期 · 看谁有空 · 立即预约</text>
                    </view>
                    <view class="cta__btn">
                        <text class="cta__btn-text">开始</text>
                        <u-icon name="arrow-right" :size="24" color="var(--c-on-brand)" />
                    </view>
                </view>
            </view>

            <!-- 服务分类 -->
            <view v-if="categories.length" class="page-pad">
                <section-header title="服务分类" subtitle="按风格挑选" />
                <view class="cats">
                    <view hover-class="is-pressed"
                        v-for="(item, i) in categories"
                        :key="i"
                        class="cats__item"
                        :class="`cats__item--${item.size || 'small'}`"
                        @tap="navigate(item.link)"
                    >
                        <image v-if="item.image" class="cats__img" :src="imageUrl(item.image)" mode="aspectFill" />
                        <view v-else class="cats__placeholder">
                            <text class="cats__placeholder-text">{{ (item.title || '服')[0] }}</text>
                        </view>
                        <view class="cats__mask" />
                        <view class="cats__text">
                            <text class="cats__title">{{ item.title }}</text>
                            <text v-if="item.subtitle" class="cats__sub">{{ item.subtitle }}</text>
                        </view>
                    </view>
                </view>
            </view>

            <!-- 品牌数据 -->
            <view v-if="brand && brand.stats && brand.stats.length" class="page-pad">
                <base-card>
                    <view class="stats">
                        <view v-for="(s, i) in brand.stats" :key="i" class="stats__item">
                            <text class="stats__value">{{ s.value }}</text>
                            <text class="stats__label">{{ s.label }}</text>
                        </view>
                    </view>
                    <view v-if="brand.cta_text" class="stats__cta">
                        <base-button :text="brand.cta_text" size="md" @click="navigate(brand.cta_link)" />
                    </view>
                </base-card>
            </view>

            <!-- 预约引导图 -->
            <view v-if="features.length" class="page-pad">
                <swiper class="feature" :circular="features.length > 1" :autoplay="features.length > 1" :interval="5000">
                    <swiper-item v-for="(item, i) in features" :key="i" @tap="navigate(item.link)">
                        <image class="feature__img" :src="imageUrl(item.image)" mode="aspectFill" />
                    </swiper-item>
                </swiper>
            </view>

            <!-- 推荐人员 -->
            <view v-if="staff.length" class="page-pad">
                <section-header title="推荐服务人员" more="查看全部" @more="goStaffList" />
                <scroll-view class="staff-scroll" scroll-x :show-scrollbar="false">
                    <view class="staff-row">
                        <view hover-class="is-pressed" v-for="item in staff" :key="item.id" class="staff-card" @tap="goStaffDetail(item.id)">
                            <view class="staff-card__media">
                                <image class="staff-card__avatar" :src="imageUrl(item.avatar)" mode="aspectFill" />
                                <view v-if="item.rating" class="staff-card__rating">
                                    <text class="staff-card__rating-text">★ {{ item.rating }}</text>
                                </view>
                            </view>
                            <view class="staff-card__body">
                                <text class="staff-card__name ellipsis">{{ item.name }}</text>
                                <text v-if="item.tags && item.tags.length" class="staff-card__tag ellipsis">
                                    {{ item.tags[0] }}
                                </text>
                                <view class="staff-card__foot">
                                    <price size="sm"
                                        v-if="item.price"
                                        :content="item.price"
                                    />
                                    <text v-else class="staff-card__price-ph">价格待定</text>
                                    <text class="staff-card__book">预约</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>

            <!-- 最新资讯 -->
            <view v-if="articles.length" class="page-pad">
                <section-header title="最新资讯" />
                <view class="news">
                    <view hover-class="is-pressed" v-for="item in articles" :key="item.id" class="news__item" @tap="goArticle(item.id)">
                        <image v-if="item.image" class="news__img" :src="imageUrl(item.image)" mode="aspectFill" />
                        <view v-else class="news__img news__img--ph">
                            <u-icon name="photo" :size="32" color="var(--c-ink-3)" />
                        </view>
                        <view class="news__body">
                            <text class="news__title ellipsis-2">{{ item.title }}</text>
                            <view class="news__meta-row">
                                <text class="news__meta">{{ shortDate(item.create_time) }}</text>
                                <text v-if="item.click" class="news__meta">{{ item.click }} 阅读</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <view v-if="isEmpty" class="page-pad">
                <base-empty status="empty" empty-text="首页内容正在配置中" />
            </view>
        </template>

        <view class="home__tab-space" />
        <app-tabbar current="/pages/index/index" />
    </view>
</template>

<script setup lang="ts">
/**
 * 首页。
 *
 * 数据优先级：/api/v2/home（1 次请求）→ 失败则降级到 /api/index/index + /api/index/config。
 * 装修数据一律经 parseDecoratePage 解析，未知 widget 跳过并上报，绝不因装修异常白屏。
 */
import { computed, reactive, ref } from 'vue'
import { onLoad, onPullDownRefresh } from '@dcloudio/uni-app'
import { parseDecoratePage, findWidget } from '@shared/contracts'
import { getHome, getIndex, getConfig } from '@/api/home'
import { useAppStore } from '@/stores/app'
import { navigate } from '@/utils/link'
import { getDeviceInfo } from '@/platform/device'

interface BannerItem {
    is_show?: string
    image?: string
    bg?: string
    name?: string
    slogan?: string
    link?: any
}
interface CategoryItem {
    is_show?: string
    title?: string
    subtitle?: string
    image?: string
    size?: string
    link?: any
}
interface FeatureItem {
    is_show?: string
    image?: string
    name?: string
    link?: any
}

const appStore = useAppStore()

/** 搜索入口距顶：状态栏 + 导航条 44 + 8px 间距 */
const searchTop = getDeviceInfo().statusBarHeight + 52

const loading = ref(true)
const ready = ref(false)
const error = ref(false)

const page = reactive({
    widgets: [] as any[],
    meta: { title: '', bg_type: 1, bg_color: '', bg_image: '' },
    skipped: [] as string[]
})
const staff = ref<any[]>([])
const articles = ref<any[]>([])

const banners = computed<BannerItem[]>(() => {
    const w = findWidget<any>(page as any, 'banner')
    if (!w || Number(w.enabled) !== 1) return []
    return (w.data || []).filter((i: BannerItem) => i.is_show !== '0')
})

const categories = computed<CategoryItem[]>(() => {
    const w = findWidget<any>(page as any, 'home-service-categories')
    if (!w || Number(w.enabled) !== 1) return []
    return (w.data || []).filter((i: CategoryItem) => i.is_show !== '0')
})

const brand = computed<any>(() => {
    const w = findWidget<any>(page as any, 'home-brand')
    return w && Number(w.enabled) === 1 ? w : null
})

const features = computed<FeatureItem[]>(() => {
    const w = findWidget<any>(page as any, 'home-feature-carousel')
    if (!w || Number(w.enabled) !== 1) return []
    return (w.data || []).filter((i: FeatureItem) => i.is_show !== '0' && i.image)
})

const isEmpty = computed(
    () =>
        !banners.value.length &&
        !categories.value.length &&
        !articles.value.length &&
        !staff.value.length &&
        !features.value.length &&
        !brand.value?.stats?.length
)

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

async function load() {
    loading.value = true
    error.value = false
    try {
        let pageData: { data: any; meta: any } | null = null

        try {
            // 优先 v2 聚合接口
            const home = await getHome()
            pageData = home.page
            staff.value = home.staff_recommend || []
            articles.value = home.article_latest || []
            if (home.config && !appStore.config?.domain) appStore.config = home.config as any
        } catch {
            // 降级：老接口
            const [index, config] = await Promise.all([getIndex(), getConfig().catch(() => null)])
            pageData = (index && index.page) || null
            articles.value = (index && index.article) || []
            staff.value = []
            if (config) appStore.config = config
        }

        if (!pageData) throw new Error('empty page')

        const parsed = parseDecoratePage(pageData.data, pageData.meta)
        page.widgets = parsed.widgets
        page.meta = parsed.meta
        page.skipped = parsed.skipped
        ready.value = true
    } catch {
        error.value = true
    } finally {
        loading.value = false
        uni.stopPullDownRefresh()
    }
}

function goQuery() {
    navigate({ path: '/packages/pkg-catalog/pages/query/query' })
}
function goSearch() {
    navigate({ path: '/pages/search/search' })
}
function goStaffList() {
    navigate({ path: '/packages/pkg-catalog/pages/staff-list/staff-list' })
}
function goStaffDetail(id: number) {
    navigate({ path: '/packages/pkg-catalog/pages/staff-detail/staff-detail', query: { id } })
}
function goArticle(id: number) {
    navigate({ path: '/packages/pkg-catalog/pages/article-detail/article-detail', query: { id } })
}

/** 资讯卡片只显示日期，完整时间戳在列表里又长又没用 */
function shortDate(value?: string) {
    if (!value) return ''
    const matched = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/)
    return matched ? `${matched[1]}-${matched[2]}-${matched[3]}` : String(value).slice(0, 10)
}

onLoad(() => load())
onPullDownRefresh(() => load())
</script>

<style lang="scss" scoped>
.home {
    min-height: 100vh;
    background: var(--c-bg);

    &__skeleton {
        padding-top: 120rpx;
    }

    &__tab-space {
        height: calc(100rpx + var(--safe-bottom) + var(--sp-4));
    }
}

.skeleton-hero {
    height: 420rpx;
    border-radius: var(--r-lg);
    background: var(--c-neutral-bg);
    margin-bottom: var(--sp-4);
}

.search-entry {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    z-index: 20;
    padding-left: var(--sp-4);
    padding-right: var(--sp-4);

    &__box {
        display: flex;
        align-items: center;
        gap: 8rpx;
        height: 64rpx;
        padding: 0 var(--sp-3);
        border-radius: var(--r-pill);
        background: rgba(255, 255, 255, 0.92);
        box-shadow: var(--shadow-float);
    }

    &__ph {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.hero {
    &__swiper {
        height: 560rpx;
    }
    &__img {
        width: 100%;
        height: 100%;
    }
    &__mask {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(180deg, rgba(24, 22, 20, 0.35) 0%, rgba(24, 22, 20, 0) 45%, rgba(24, 22, 20, 0.55) 100%);
    }
    &__text {
        position: absolute;
        left: var(--sp-4);
        right: var(--sp-4);
        bottom: 120rpx;
    }
    &__slogan {
        display: block;
        font-size: var(--fs-xl);
        color: #ffffff;
        font-weight: 500;
        letter-spacing: 2rpx;
    }
    &__name {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-xs);
        color: rgba(255, 255, 255, 0.82);
    }
}

.brand-head {
    padding: 160rpx var(--sp-4) var(--sp-5);
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);

    &__greeting {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
    &__name {
        font-size: var(--fs-2xl);
        font-weight: 500;
        color: var(--c-ink);
    }
    &__sub {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }
}

.cta-wrap {
    margin-top: -80rpx;
    position: relative;
    z-index: 2;
}

.cta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--c-ink);
    border-radius: var(--r-lg);
    padding: var(--sp-4);
    box-shadow: var(--shadow-float);

    &__title {
        display: block;
        font-size: var(--fs-xl);
        color: #ffffff;
        font-weight: 500;
    }
    &__desc {
        display: block;
        margin-top: 6rpx;
        font-size: var(--fs-xs);
        color: rgba(255, 255, 255, 0.6);
    }
    &__btn {
        display: flex;
        align-items: center;
        gap: 4rpx;
        background: var(--c-brand);
        border-radius: var(--r-pill);
        padding: 14rpx 26rpx;
    }
    &__btn-text {
        font-size: var(--fs-xs);
        color: var(--c-on-brand);
    }
}

.cats {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);

    &__item {
        position: relative;
        border-radius: var(--r-md);
        overflow: hidden;
        background: var(--c-surface-2);

        &--large {
            width: 100%;
            height: 280rpx;
        }
        &--wide {
            width: calc(50% - var(--sp-1));
            height: 200rpx;
        }
        &--small {
            width: calc(50% - var(--sp-1));
            height: 200rpx;
        }
    }

    &__img,
    &__placeholder {
        width: 100%;
        height: 100%;
        display: block;
        background: var(--c-neutral-bg);
    }

    &__placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--c-surface-2) 0%, var(--c-brand-bg) 100%);
    }

    &__placeholder-text {
        font-size: 64rpx;
        font-weight: 500;
        color: var(--c-brand-text);
        opacity: 0.5;
    }

    &__mask {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(180deg, rgba(24, 22, 20, 0) 40%, rgba(24, 22, 20, 0.6) 100%);
    }

    &__text {
        position: absolute;
        left: var(--sp-3);
        bottom: var(--sp-3);
        right: var(--sp-3);
    }

    &__title {
        display: block;
        font-size: var(--fs-md);
        color: #ffffff;
        font-weight: 500;
    }

    &__sub {
        display: block;
        font-size: var(--fs-xs);
        color: rgba(255, 255, 255, 0.7);
        letter-spacing: 1rpx;
    }
}

.stats {
    display: flex;
    justify-content: space-around;

    &__item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4rpx;
    }
    &__value {
        font-size: var(--fs-xl);
        font-weight: 500;
        color: var(--c-brand-text);
    }
    &__label {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
    &__cta {
        margin-top: var(--sp-4);
    }
}

.feature {
    height: 300rpx;
    border-radius: var(--r-lg);
    overflow: hidden;
    margin-top: var(--sp-4);

    &__img {
        width: 100%;
        height: 100%;
    }
}

.staff-scroll {
    white-space: nowrap;
}

.staff-row {
    display: inline-flex;
    gap: var(--sp-3);
    padding-bottom: var(--sp-1);
}

.staff-card {
    width: 200rpx;
    display: flex;
    flex-direction: column;
    gap: 8rpx;

    &__media {
        position: relative;
    }

    &__avatar {
        width: 200rpx;
        height: 240rpx;
        border-radius: var(--r-md);
        background: var(--c-neutral-bg);
    }

    &__rating {
        position: absolute;
        left: 8rpx;
        top: 8rpx;
        padding: 2rpx 10rpx;
        border-radius: var(--r-pill);
        background: rgba(24, 22, 20, 0.55);
    }

    &__rating-text {
        font-size: var(--fs-xs);
        color: #ffffff;
    }

    &__body {
        display: flex;
        flex-direction: column;
        gap: 4rpx;
        padding: 0 2rpx;
    }

    &__name {
        font-size: var(--fs-xs);
        font-weight: 500;
        color: var(--c-ink);
        max-width: 196rpx;
    }

    &__tag {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        max-width: 196rpx;
    }

    &__foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 2rpx;
    }

    &__price-ph {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__book {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
        background: var(--c-brand-bg);
        padding: 2rpx 10rpx;
        border-radius: var(--r-xs);
    }
}

.news {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);

    &__item {
        display: flex;
        gap: var(--sp-3);
        background: var(--c-surface);
        border-radius: var(--r-md);
        padding: var(--sp-3);
    }

    &__img {
        width: 160rpx;
        height: 110rpx;
        border-radius: var(--r-sm);
        flex-shrink: 0;
        background: var(--c-neutral-bg);

        &--ph {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }

    &__body {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-width: 0;
    }

    &__title {
        font-size: var(--fs-md);
        color: var(--c-ink);
        line-height: 1.45;
    }

    &__meta-row {
        display: flex;
        align-items: center;
        gap: var(--sp-3);
    }

    &__meta {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}
</style>
