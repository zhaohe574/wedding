<template>
    <page-meta :page-style="homePageMetaStyle"> </page-meta>
    <PageShell
        scene="consumer"
        tone="editorial"
        hasTabbar
        :shell-style="homePageStyle"
        :suppress-overlay="isBannerBackgroundLinked"
    >
        <view class="home-page" :style="homePageStyle">
            <view class="home-page__content">
                <view class="home-page__hero" :style="heroStyle">
                    <view
                        v-if="currentBannerItem"
                        class="home-page__hero-media"
                        @tap="handleBannerTap(currentBannerItem)"
                    >
                        <image
                            class="home-page__hero-image"
                            :src="currentBannerItem.image"
                            mode="aspectFill"
                            lazy-load
                        />
                    </view>
                    <view v-else class="home-page__hero-media home-page__hero-media--fallback">
                        <view class="home-page__hero-placeholder"></view>
                    </view>
                </view>

                <view class="home-page__body" :style="homeBodyStyle">
                    <view class="home-page__intro-panel">
                        <view class="home-page__intro-head">
                            <view class="home-page__intro-copy">
                                <text class="home-page__hello">{{ homeBrand.greeting }}</text>
                                <text class="home-page__intro-title">{{ homeBrand.teamName }}</text>
                                <text class="home-page__intro-subtitle">{{ homeBrand.subtitle }}</text>
                            </view>
                            <view class="home-page__booking-btn" @tap="handleBrandCtaTap">
                                <text class="home-page__booking-btn-text">{{ homeBrand.ctaText }}</text>
                            </view>
                        </view>

                        <view
                            v-if="showFeatureCarousel"
                            class="home-page__feature"
                            :style="{ height: `${featureHeight}rpx` }"
                            @tap="handleFeatureTap(currentFeatureItem)"
                        >
                            <swiper
                                v-if="featureSlides.length > 1"
                                class="home-page__feature-swiper"
                                circular
                                :autoplay="featureAutoplay"
                                :interval="featureInterval"
                                duration="450"
                                @change="handleFeatureChange"
                            >
                                <swiper-item v-for="item in featureSlides" :key="item.key">
                                    <image
                                        class="home-page__feature-image"
                                        :src="item.image"
                                        mode="aspectFill"
                                        lazy-load
                                    />
                                </swiper-item>
                            </swiper>
                            <image
                                v-else-if="currentFeatureItem?.image"
                                class="home-page__feature-image"
                                :src="currentFeatureItem.image"
                                mode="aspectFill"
                                lazy-load
                            />
                            <view v-else class="home-page__feature-image home-page__feature-fallback" />
                            <view v-if="featureSlides.length > 1" class="home-page__feature-dots">
                                <view
                                    v-for="(_, index) in featureSlides"
                                    :key="index"
                                    class="home-page__feature-dot"
                                    :class="{
                                        'home-page__feature-dot--active': index === safeFeatureIndex
                                    }"
                                />
                            </view>
                        </view>

                        <view v-if="categoryTiles.length" class="home-page__tile-grid">
                            <view
                                v-for="tile in categoryTiles"
                                :key="tile.key"
                                class="home-page__tile"
                                :class="[
                                    `home-page__tile--${tile.size}`,
                                    { 'home-page__tile--no-image': !tile.image }
                                ]"
                                @tap="handleCategoryTap(tile)"
                            >
                                <image
                                    v-if="tile.image"
                                    class="home-page__tile-image"
                                    :src="tile.image"
                                    mode="aspectFill"
                                    lazy-load
                                />
                                <view class="home-page__tile-scrim"></view>
                                <view
                                    class="home-page__tile-copy"
                                    :class="`home-page__tile-copy--${tile.textPosition}`"
                                >
                                    <text class="home-page__tile-title">{{ tile.title }}</text>
                                    <text class="home-page__tile-subtitle">{{ tile.subtitle }}</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <!--  #ifdef MP  -->
            <MpPrivacyPopup></MpPrivacyPopup>
            <!--  #endif  -->

            <tabbar :badge-refresh-key="tabbarRefreshKey" />
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { getIndex } from '@/api/shop'
import PageShell from '@/components/base/PageShell.vue'
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import { hasConfiguredLink, navigateTo } from '@/utils/util'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { computed, ref } from 'vue'

// #ifdef MP
import MpPrivacyPopup from './component/mp-privacy-popup.vue'
// #endif

type AppLink = Record<string, any> | string | null | undefined

interface DecorateWidget {
    name?: string
    content?: Record<string, any>
}

interface DecorateBannerContent {
    style?: number | string
    bg_style?: number | string
    height?: number | string | null
    overlap_height?: number | string | null
    data?: DecorateBannerItem[]
}

interface DecorateBannerItem {
    id?: string | number
    is_show?: string | number
    image?: string
    bg?: string
    bg_color?: string
    slogan?: string | null
    slogan_top?: number | string | null
    slogan_color?: string | null
    link?: AppLink
}

interface BannerItem {
    key: string | number
    image: string
    bgImage: string
    bgColor: string
    link?: AppLink
}

interface HomeBrandView {
    greeting: string
    teamName: string
    subtitle: string
    ctaText: string
    ctaLink: AppLink
}

interface FeatureSlideItem {
    id?: string | number
    is_show?: string | number
    image?: string
    link?: AppLink
}

interface HomeCategoryTile {
    key: string
    title: string
    subtitle: string
    image: string
    size: 'large' | 'small' | 'wide'
    textPosition: 'top' | 'middle' | 'bottom'
    link?: AppLink
    url?: string
}

const appStore = useAppStore()
const themeStore = useThemeStore()

const widgets = ref<DecorateWidget[]>([])
const metaList = ref<any[]>([])
const currentFeatureIndex = ref(0)
const tabbarRefreshKey = ref(0)
const DEFAULT_BANNER_HEIGHT = 690
const DEFAULT_LARGE_BANNER_HEIGHT = 760
const MIN_EDITORIAL_HERO_HEIGHT = 640
const DEFAULT_BANNER_OVERLAP_HEIGHT = 112
const DEFAULT_FEATURE_HEIGHT = 300
const DEFAULT_FEATURE_INTERVAL_SECONDS = 5
const DEFAULT_PAGE_BACKGROUND = '#ffffff'
const DEFAULT_LINKED_BACKGROUND = '#000000'
const SYSTEM_HOME_MODULE_IMAGE =
    'data:image/svg+xml;utf8,<svg%20xmlns="http://www.w3.org/2000/svg"%20viewBox="0%200%20750%20420"><rect%20width="750"%20height="420"%20fill="%230B0B0B"/><path%20d="M0%20308L750%20196V420H0Z"%20fill="%231A1A1A"/><circle%20cx="610"%20cy="122"%20r="88"%20fill="%23C8A45D"%20opacity=".78"/><path%20d="M116%20292c95-86%20159-114%20246-72%2060%2029%20118%2020%20178-35"%20fill="none"%20stroke="%23FFFFFF"%20stroke-opacity=".5"%20stroke-width="14"/><path%20d="M90%20334h570"%20stroke="%23C8A45D"%20stroke-opacity=".72"%20stroke-width="8"/></svg>'
const DEFAULT_SCHEDULE_LINK = {
    path: '/pages/schedule_query/schedule_query',
    type: 'shop'
}
const DEFAULT_CATEGORY_CONFIG: HomeCategoryTile[] = [
    {
        key: 'western',
        title: '西式主持',
        subtitle: 'WEDDING HOST',
        image: '',
        size: 'large',
        textPosition: 'bottom',
        link: {
            ...DEFAULT_SCHEDULE_LINK,
            query: { keyword: '西式主持' }
        }
    },
    {
        key: 'chinese',
        title: '中式主持',
        subtitle: 'CHINESE HOST',
        image: '',
        size: 'small',
        textPosition: 'bottom',
        link: {
            ...DEFAULT_SCHEDULE_LINK,
            query: { keyword: '中式主持' }
        }
    },
    {
        key: 'business',
        title: '商务主持',
        subtitle: 'BUSINESS HOST',
        image: '',
        size: 'small',
        textPosition: 'bottom',
        link: {
            ...DEFAULT_SCHEDULE_LINK,
            query: { keyword: '商务主持' }
        }
    },
    {
        key: 'training',
        title: '主持培训课程',
        subtitle: 'HOST TRAINING',
        image: '',
        size: 'wide',
        textPosition: 'bottom',
        link: {
            path: '/pages/news/news',
            type: 'shop'
        }
    }
]

const normalizePositiveNumber = (value: unknown): number | undefined => {
    if (value === '' || value === null || value === undefined) {
        return undefined
    }

    const parsedValue = Number(value)
    if (!Number.isFinite(parsedValue) || parsedValue <= 0) {
        return undefined
    }

    return parsedValue
}

const normalizeNonNegativeNumber = (value: unknown): number | undefined => {
    if (value === '' || value === null || value === undefined) {
        return undefined
    }

    const parsedValue = Number(value)
    if (!Number.isFinite(parsedValue) || parsedValue < 0) {
        return undefined
    }

    return parsedValue
}

const normalizeText = (value: unknown, fallback: string) => {
    return typeof value === 'string' && value.trim() ? value.trim() : fallback
}

const normalizeTextPosition = (value: unknown): HomeCategoryTile['textPosition'] => {
    return ['top', 'middle', 'bottom'].includes(String(value))
        ? (value as HomeCategoryTile['textPosition'])
        : 'bottom'
}

const bannerWidget = computed(() => {
    return widgets.value.find((item) => item?.name === 'banner')
})

const bannerContent = computed<DecorateBannerContent>(() => {
    return bannerWidget.value?.content || {}
})

const homeBrandWidget = computed(() => {
    return widgets.value.find((item) => item?.name === 'home-brand')
})

const featureWidget = computed(() => {
    return widgets.value.find((item) => item?.name === 'home-feature-carousel')
})

const serviceCategoriesWidget = computed(() => {
    return widgets.value.find((item) => item?.name === 'home-service-categories')
})

const heroHeight = computed(() => {
    const customHeight = normalizePositiveNumber(bannerContent.value.height)
    if (customHeight) {
        return Math.max(customHeight, MIN_EDITORIAL_HERO_HEIGHT)
    }

    const bannerStyle = normalizePositiveNumber(bannerContent.value.style)
    return bannerStyle === 2 ? DEFAULT_LARGE_BANNER_HEIGHT : DEFAULT_BANNER_HEIGHT
})

const bannerOverlapHeight = computed(() => {
    const customHeight = normalizeNonNegativeNumber(bannerContent.value.overlap_height)
    return Math.min(Math.max(customHeight ?? DEFAULT_BANNER_OVERLAP_HEIGHT, 0), 180)
})

const bannerList = computed<BannerItem[]>(() => {
    const rawList = normalizeJsonList(bannerContent.value.data) as DecorateBannerItem[]

    const [firstBanner] = rawList
        .filter((item) => String(item?.is_show ?? '1') !== '0')
        .map((item, index: number) => ({
            key: item?.id ?? index,
            image: appStore.getImageUrl(item?.image || item?.bg || ''),
            bgImage: appStore.getImageUrl(item?.bg || ''),
            bgColor: typeof item?.bg_color === 'string' ? item.bg_color.trim() : '',
            link: item?.link || {},
        }))
        .filter((item) => !!item.image)

    return firstBanner ? [firstBanner] : []
})

const currentBannerItem = computed(() => {
    return bannerList.value[0] || null
})

const isBannerBackgroundLinked = computed(() => {
    if (!bannerList.value.length) {
        return false
    }

    return Number(bannerContent.value.bg_style) === 1
})

const linkedBannerBackgroundColor = computed(() => {
    if (!isBannerBackgroundLinked.value) {
        return ''
    }

    return currentBannerItem.value?.bgColor || DEFAULT_LINKED_BACKGROUND
})

const homeBackgroundColor = computed(() => {
    return linkedBannerBackgroundColor.value || DEFAULT_PAGE_BACKGROUND
})

const homePageStyle = computed(() => {
    return {
        '--wm-color-bg-page': homeBackgroundColor.value,
        background: homeBackgroundColor.value,
        backgroundColor: homeBackgroundColor.value
    }
})

const homeBodyStyle = computed(() => ({
    marginTop: `-${bannerOverlapHeight.value}rpx`
}))

const homePageMetaStyle = computed(() => {
    const baseStyle = themeStore.pageStyle || ''
    const separator = baseStyle.trim().endsWith(';') || !baseStyle.trim() ? '' : ';'
    return `${baseStyle}${separator}background-color:${homeBackgroundColor.value};`
})

const heroStyle = computed(() => ({
    height: `${heroHeight.value}rpx`,
    backgroundColor: linkedBannerBackgroundColor.value || DEFAULT_LINKED_BACKGROUND
}))

const homeBrand = computed<HomeBrandView>(() => {
    const content = homeBrandWidget.value?.content || {}
    return {
        greeting: normalizeText(content.greeting, 'Hello,'),
        teamName: normalizeText(content.team_name, '我们是星意主持人工作室'),
        subtitle: normalizeText(content.subtitle, '选星意，有心意'),
        ctaText: normalizeText(content.cta_text, '立即预定'),
        ctaLink: content.cta_link || DEFAULT_SCHEDULE_LINK
    }
})

const featureContent = computed(() => featureWidget.value?.content || {})

const showFeatureCarousel = computed(() => String(featureContent.value.enabled ?? '1') !== '0')

const featureHeight = computed(() => {
    const customHeight = normalizePositiveNumber(featureContent.value.height)
    return Math.min(Math.max(customHeight || DEFAULT_FEATURE_HEIGHT, 180), 520)
})

const featureAutoplay = computed(() => String(featureContent.value.autoplay ?? '1') !== '0')

const normalizeFeatureIntervalMs = (value: unknown) => {
    const parsedValue = normalizePositiveNumber(value) || DEFAULT_FEATURE_INTERVAL_SECONDS
    if (parsedValue > 100) {
        return Math.min(Math.max(parsedValue, 2000), 10000)
    }

    return Math.min(Math.max(parsedValue, 2), 10) * 1000
}

const featureInterval = computed(() => normalizeFeatureIntervalMs(featureContent.value.interval))

const featureSlides = computed<BannerItem[]>(() => {
    if (!showFeatureCarousel.value) {
        return []
    }

    const rawList = normalizeJsonList(featureContent.value.data) as FeatureSlideItem[]
    const slides = rawList
        .filter((item) => String(item?.is_show ?? '1') !== '0')
        .map((item, index) => ({
            key: item?.id ?? index,
            image: appStore.getImageUrl(item?.image || ''),
            bgImage: '',
            bgColor: '',
            link: item?.link || DEFAULT_SCHEDULE_LINK
        }))
        .filter((item) => !!item.image)

    if (slides.length) {
        return slides
    }

    return [
        {
            key: 'feature-fallback',
            image: SYSTEM_HOME_MODULE_IMAGE,
            bgImage: '',
            bgColor: '',
            link: DEFAULT_SCHEDULE_LINK
        }
    ]
})

const safeFeatureIndex = computed(() => {
    if (!featureSlides.value.length) {
        return 0
    }

    return Math.min(currentFeatureIndex.value, featureSlides.value.length - 1)
})

const currentFeatureItem = computed(() => featureSlides.value[safeFeatureIndex.value] || null)

const categoryTiles = computed<HomeCategoryTile[]>(() => {
    const content = serviceCategoriesWidget.value?.content || {}
    if (String(content.enabled ?? '1') === '0') {
        return []
    }

    const configuredList = normalizeJsonList(content.data)
        .filter((item: any) => String(item?.is_show ?? '1') !== '0')
        .map((item: any, index: number) => ({
            key: String(item?.id ?? `${item?.title || 'category'}-${index}`),
            title: normalizeText(item?.title, DEFAULT_CATEGORY_CONFIG[index]?.title || '服务分类'),
            subtitle: normalizeText(item?.subtitle, DEFAULT_CATEGORY_CONFIG[index]?.subtitle || 'SERVICE'),
            image:
                appStore.getImageUrl(item?.image || '') ||
                DEFAULT_CATEGORY_CONFIG[index]?.image ||
                SYSTEM_HOME_MODULE_IMAGE,
            size: (['large', 'small', 'wide'].includes(item?.size) ? item.size : 'small') as HomeCategoryTile['size'],
            textPosition: normalizeTextPosition(
                item?.text_position || DEFAULT_CATEGORY_CONFIG[index]?.textPosition
            ),
            link: item?.link || DEFAULT_CATEGORY_CONFIG[index]?.link,
            url: DEFAULT_CATEGORY_CONFIG[index]?.url
        }))

    if (configuredList.length) {
        return configuredList
    }

    return DEFAULT_CATEGORY_CONFIG.map((item) => ({
        ...item,
        image: item.image || SYSTEM_HOME_MODULE_IMAGE
    }))
})

const normalizeJsonList = (value: any): any[] => {
    if (value && !Array.isArray(value) && typeof value === 'object') {
        const keys = Object.keys(value)
        if (keys.length && keys.every((key) => /^\d+$/.test(key))) {
            return Object.values(value) as any[]
        }
    }

    if (typeof value === 'string') {
        try {
            return normalizeJsonList(JSON.parse(value))
        } catch (error) {
            console.error('首页装修数据解析失败：', error)
            return []
        }
    }

    return Array.isArray(value) ? value : []
}
const syncNavigationTitle = () => {
    const pageTitle = metaList.value?.[0]?.content?.title
    if (typeof pageTitle === 'string' && pageTitle.trim()) {
        uni.setNavigationBarTitle({ title: pageTitle })
    }
}

const getData = async () => {
    try {
        const indexData = await getIndex()

        widgets.value = normalizeJsonList(indexData?.page?.data).filter(
            (item: DecorateWidget) => item?.name !== 'service-packages'
        )
        metaList.value = normalizeJsonList(indexData?.page?.meta)
        currentFeatureIndex.value = 0
        syncNavigationTitle()
    } catch (error) {
        console.error('获取首页数据失败：', error)
    }
}

const goToScheduleQuery = () => {
    uni.navigateTo({ url: '/pages/schedule_query/schedule_query' })
}

const handleBannerTap = (item: BannerItem | null) => {
    const link = item?.link
    if (!hasConfiguredLink(link)) {
        return
    }

    navigateTo(link)
}

const handleFeatureChange = (event: any) => {
    currentFeatureIndex.value = Number(event?.detail?.current || 0)
}

const handleBrandCtaTap = () => {
    if (hasConfiguredLink(homeBrand.value.ctaLink)) {
        navigateTo(homeBrand.value.ctaLink)
        return
    }

    goToScheduleQuery()
}

const handleFeatureTap = (item: BannerItem | null) => {
    if (hasConfiguredLink(item?.link)) {
        navigateTo(item?.link)
        return
    }

    goToScheduleQuery()
}

const handleCategoryTap = (tile: HomeCategoryTile) => {
    if (hasConfiguredLink(tile.link)) {
        navigateTo(tile.link)
        return
    }

    if (tile.url) {
        navigateTo(tile.url)
    }
}

onLoad(() => {
    themeStore.setScene('consumer')
    getData()
})

onShow(() => {
    themeStore.setScene('consumer')
    tabbarRefreshKey.value += 1
})
</script>

<style lang="scss" scoped>
.home-page {
    --wm-space-page-x: 37rpx;
    min-height: 100%;
    background: var(--wm-color-bg-page, #ffffff);
    transition: background 260ms ease;
}

.home-page__content {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.home-page__body {
    position: relative;
    z-index: 3;
    margin-top: -112rpx;
    padding: 0 32rpx 40rpx;
}

.home-page__hero {
    position: relative;
    overflow: hidden;
    border-radius: 0;
    background: #000000;
}

.home-page__hero-media,
.home-page__hero-placeholder,
.home-page__hero-image {
    width: 100%;
    height: 100%;
}

.home-page__hero-media--fallback {
    background: radial-gradient(
            circle at top,
            rgba(255, 255, 255, 0.35) 0,
            rgba(255, 255, 255, 0) 28%
        ),
        linear-gradient(180deg, rgba(11, 11, 11, 0.32) 0%, rgba(11, 11, 11, 0.72) 100%);
}

.home-page__hero-media {
    position: relative;
}

.home-page__hero-placeholder {
    position: absolute;
    inset: 0;
    background: linear-gradient(
            180deg,
            rgba(11, 11, 11, 0.06) 0%,
            rgba(11, 11, 11, 0.16) 42%,
            rgba(11, 11, 11, 0.58) 100%
        ),
        linear-gradient(180deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0) 38%);
}

.home-page__intro-panel {
    padding: 28rpx;
    border-radius: 16rpx;
    border: 1rpx solid rgba(11, 11, 11, 0.08);
    background: #ffffff;
    box-shadow: 0 10rpx 24rpx rgba(11, 11, 11, 0.08);
}

.home-page__intro-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24rpx;
    padding: 0 0 28rpx;
}

.home-page__intro-copy {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.home-page__hello {
    font-size: 24rpx;
    font-weight: 700;
    line-height: 1.2;
    color: #c8a45d;
}

.home-page__intro-title {
    font-size: 32rpx;
    font-weight: 700;
    line-height: 1.35;
    color: #111111;
    word-break: break-word;
}

.home-page__intro-subtitle {
    font-size: 22rpx;
    line-height: 1.5;
    color: #4a4a4a;
}

.home-page__booking-btn {
    flex-shrink: 0;
    min-width: 138rpx;
    min-height: 60rpx;
    padding: 0 28rpx;
    border-radius: 999rpx;
    background: #0b0b0b;
    display: inline-flex;
    align-items: center;
    justify-content: center;

    &:active {
        transform: translateY(1rpx) scale(0.98);
    }
}

.home-page__booking-btn-text {
    font-size: 24rpx;
    line-height: 1;
    font-weight: 700;
    letter-spacing: 0;
    color: #ffffff;
}

.home-page__feature {
    position: relative;
    overflow: hidden;
    border-radius: 18rpx;
    background: #111111;
}

.home-page__feature-swiper,
.home-page__feature-image,
.home-page__feature-fallback {
    width: 100%;
    height: 100%;
}

.home-page__feature-fallback {
    background: linear-gradient(135deg, #000000 0%, #2a241a 100%);
}

.home-page__feature-dots {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 18rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
}

.home-page__feature-dot {
    width: 10rpx;
    height: 10rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.54);
}

.home-page__feature-dot--active {
    width: 26rpx;
    background: #c8a45d;
}

.home-page__tile-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    grid-auto-rows: 156rpx;
    gap: 14rpx;
    margin-top: 28rpx;
}

.home-page__tile {
    position: relative;
    min-width: 0;
    overflow: hidden;
    border-radius: 18rpx;
    background: #111111;
}

.home-page__tile--large {
    grid-row: span 2;
}

.home-page__tile--wide {
    grid-column: span 2;
}

.home-page__tile--no-image {
    background: linear-gradient(135deg, #000000 0%, #2a241a 100%);
}

.home-page__tile-image {
    width: 100%;
    height: 100%;
}

.home-page__tile-scrim {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0, 0, 0, 0.2) 0%, rgba(0, 0, 0, 0.68) 100%);
}

.home-page__tile-copy {
    position: absolute;
    left: 20rpx;
    right: 20rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8rpx;
    text-align: center;
}

.home-page__tile-copy--top {
    top: 24rpx;
}

.home-page__tile-copy--middle {
    top: 50%;
    transform: translateY(-50%);
}

.home-page__tile-copy--bottom {
    bottom: 22rpx;
}

.home-page__tile-title {
    font-size: 29rpx;
    line-height: 1.2;
    font-weight: 800;
    color: #ffffff;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    word-break: break-word;
}

.home-page__tile-subtitle {
    font-size: 16rpx;
    line-height: 1.2;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.88);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

</style>
