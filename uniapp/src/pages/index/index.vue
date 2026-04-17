<template>
    <page-meta :page-style="$theme.pageStyle"> </page-meta>
    <PageShell scene="consumer" hasTabbar>
        <view class="home-page">
            <view class="home-page__content">
                <view class="home-page__hero" :style="heroStyle">
                    <swiper
                        v-if="bannerList.length"
                        class="home-page__hero-swiper"
                        circular
                        autoplay
                        interval="5000"
                        duration="500"
                        @change="handleBannerChange"
                    >
                        <swiper-item v-for="item in bannerList" :key="item.key">
                            <view class="home-page__hero-slide" @tap="handleBannerTap(item)">
                                <image
                                    class="home-page__hero-image"
                                    :src="item.image"
                                    mode="aspectFill"
                                />
                            </view>
                        </swiper-item>
                    </swiper>
                    <view v-else class="home-page__hero-swiper home-page__hero-swiper--fallback">
                        <view class="home-page__hero-placeholder"></view>
                    </view>

                    <view
                        v-if="currentBannerSloganLines.length"
                        class="home-page__hero-copy"
                        :style="heroCopyStyle"
                    >
                        <view class="home-page__hero-accent"></view>
                        <text
                            v-for="(line, index) in currentBannerSloganLines"
                            :key="`${safeBannerIndex}-${index}-${line}`"
                            class="home-page__hero-title"
                            :style="{ color: currentBannerSloganColor }"
                        >
                            {{ line }}
                        </text>
                    </view>
                    <view v-if="bannerList.length > 1" class="home-page__hero-dots">
                        <view
                            v-for="(_, index) in bannerList"
                            :key="index"
                            class="home-page__hero-dot"
                            :class="{
                                'home-page__hero-dot--active': index === safeBannerIndex
                            }"
                        />
                    </view>
                </view>

                <view class="home-page__body">
                    <view class="home-page__cta" @tap="goToScheduleQuery">
                        <text class="home-page__cta-text">查询档期</text>
                    </view>

                    <view class="home-page__section">
                        <view class="home-page__section-head">
                            <view class="home-page__section-copy">
                                <text class="home-page__section-title">推荐人员</text>
                            </view>
                            <view class="home-page__section-link" @tap="goToStaffList">
                                <text class="home-page__section-link-text">查看全部</text>
                            </view>
                        </view>

                        <view class="home-page__team-list">
                            <view
                                v-for="(item, index) in displayStaffList"
                                :key="item ? item.id : `placeholder-${index}`"
                                class="home-page__team-card"
                                :class="{ 'home-page__team-card--placeholder': !item }"
                                @tap="handleStaffTap(item)"
                            >
                                <image
                                    v-if="item"
                                    class="home-page__team-image"
                                    :src="item.avatar"
                                    mode="aspectFill"
                                    lazy-load
                                />
                                <view
                                    v-else
                                    class="home-page__team-image home-page__team-image--placeholder"
                                />

                                <view class="home-page__team-copy">
                                    <text class="home-page__team-kicker">
                                        {{ item ? '婚礼服务顾问' : '即将开放' }}
                                    </text>
                                    <text class="home-page__team-name">
                                        {{ item ? item.name : '更多人员即将上线' }}
                                    </text>
                                    <view class="home-page__team-role-wrap">
                                        <text class="home-page__team-role">
                                            {{ item ? getStaffSubtitle(item) : '敬请期待' }}
                                        </text>
                                    </view>
                                </view>
                                <view v-if="item" class="home-page__team-action" aria-hidden="true">
                                    <text class="home-page__team-action-text">›</text>
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
import { getRecommendStaff } from '@/api/staff'
import PageShell from '@/components/base/PageShell.vue'
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import { navigateTo } from '@/utils/util'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { computed, ref } from 'vue'

// #ifdef MP
import MpPrivacyPopup from './component/mp-privacy-popup.vue'
// #endif

interface DecorateWidget {
    name?: string
    content?: DecorateBannerContent & Record<string, any>
}

interface DecorateBannerContent {
    style?: number | string
    height?: number | string | null
    data?: DecorateBannerItem[]
}

interface DecorateBannerItem {
    id?: string | number
    is_show?: string | number
    image?: string
    bg?: string
    slogan?: string | null
    slogan_top?: number | string | null
    slogan_color?: string | null
    link?: Record<string, any>
}

interface BannerItem {
    key: string | number
    image: string
    link?: Record<string, any>
    slogan: string
    sloganTop?: number
    sloganColor?: string
}

interface RecommendStaffItem {
    id: number
    name: string
    avatar: string
    category_name?: string
    tags?: string[]
    profile?: string
}

const appStore = useAppStore()
const themeStore = useThemeStore()

const widgets = ref<DecorateWidget[]>([])
const metaList = ref<any[]>([])
const recommendStaffList = ref<RecommendStaffItem[]>([])
const currentBannerIndex = ref(0)
const tabbarRefreshKey = ref(0)
const DEFAULT_BANNER_HEIGHT = 321
const DEFAULT_LARGE_BANNER_HEIGHT = 1100
const DEFAULT_BANNER_SLOGAN_TOP = 120
const DEFAULT_LARGE_BANNER_SLOGAN_TOP = 180

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

const getDefaultBannerSloganTop = (style: unknown) => {
    return Number(style) === 2 ? DEFAULT_LARGE_BANNER_SLOGAN_TOP : DEFAULT_BANNER_SLOGAN_TOP
}

const splitSloganLines = (slogan: string) => {
    return slogan
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter(Boolean)
}

const bannerWidget = computed(() => {
    return widgets.value.find((item) => item?.name === 'banner')
})

const bannerContent = computed<DecorateBannerContent>(() => {
    return bannerWidget.value?.content || {}
})

const heroHeight = computed(() => {
    const customHeight = normalizePositiveNumber(bannerContent.value.height)
    if (customHeight) {
        return customHeight
    }

    const bannerStyle = normalizePositiveNumber(bannerContent.value.style)
    return bannerStyle === 2 ? DEFAULT_LARGE_BANNER_HEIGHT : DEFAULT_BANNER_HEIGHT
})

const heroStyle = computed(() => ({
    height: `${heroHeight.value}rpx`
}))

const bannerList = computed<BannerItem[]>(() => {
    const rawList = normalizeJsonList(bannerContent.value.data) as DecorateBannerItem[]

    return rawList
        .filter((item) => String(item?.is_show ?? '1') !== '0')
        .map((item, index: number) => ({
            key: item?.id ?? index,
            image: appStore.getImageUrl(item?.image || item?.bg || ''),
            link: item?.link || {},
            slogan: typeof item?.slogan === 'string' ? item.slogan : '',
            sloganTop: normalizeNonNegativeNumber(item?.slogan_top),
            sloganColor: typeof item?.slogan_color === 'string' ? item.slogan_color.trim() : ''
        }))
        .filter((item) => !!item.image)
})

const safeBannerIndex = computed(() => {
    if (!bannerList.value.length) {
        return 0
    }

    return Math.min(currentBannerIndex.value, bannerList.value.length - 1)
})

const currentBannerItem = computed(() => {
    return bannerList.value[safeBannerIndex.value] || null
})

const currentBannerSloganLines = computed(() => {
    return splitSloganLines(currentBannerItem.value?.slogan || '')
})

const currentBannerSloganTop = computed(() => {
    return (
        currentBannerItem.value?.sloganTop ?? getDefaultBannerSloganTop(bannerContent.value.style)
    )
})

const currentBannerSloganColor = computed(() => {
    const sloganColor =
        typeof currentBannerItem.value?.sloganColor === 'string'
            ? currentBannerItem.value.sloganColor.trim()
            : ''

    return sloganColor || '#FFFFFF'
})

const heroCopyStyle = computed(() => ({
    top: `${currentBannerSloganTop.value}rpx`
}))

const displayStaffList = computed(() => {
    const result = [...recommendStaffList.value.slice(0, 2)]
    while (result.length < 2) {
        result.push(null as unknown as RecommendStaffItem)
    }
    return result
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
const getStaffSubtitle = (item: RecommendStaffItem) => {
    const candidates = [
        item.category_name,
        Array.isArray(item.tags) ? item.tags[0] : '',
        item.profile
    ]

    const subtitle = candidates.find((value) => typeof value === 'string' && value.trim().length)

    return subtitle ? subtitle.trim() : '婚礼服务顾问'
}

const syncNavigationTitle = () => {
    const pageTitle = metaList.value?.[0]?.content?.title
    if (typeof pageTitle === 'string' && pageTitle.trim()) {
        uni.setNavigationBarTitle({ title: pageTitle })
    }
}

const getData = async () => {
    try {
        const [indexData, recommendData] = await Promise.all([
            getIndex(),
            getRecommendStaff({ limit: 2 })
        ])

        widgets.value = normalizeJsonList(indexData?.page?.data).filter(
            (item: DecorateWidget) => item?.name !== 'service-packages'
        )
        metaList.value = normalizeJsonList(indexData?.page?.meta)
        recommendStaffList.value = Array.isArray(recommendData) ? recommendData : []
        currentBannerIndex.value = 0
        syncNavigationTitle()
    } catch (error) {
        console.error('获取首页数据失败：', error)
    }
}

const goToStaffList = () => {
    uni.navigateTo({ url: '/pages/schedule_query/schedule_query' })
}

const goToScheduleQuery = () => {
    uni.navigateTo({ url: '/pages/schedule_query/schedule_query' })
}

const handleBannerTap = (item: BannerItem) => {
    if (!item?.link?.path) {
        return
    }

    navigateTo(item.link)
}

const handleBannerChange = (event: any) => {
    currentBannerIndex.value = Number(event?.detail?.current || 0)
}

const handleStaffTap = (item: RecommendStaffItem | null) => {
    if (!item?.id) {
        return
    }

    uni.navigateTo({
        url: `/packages/pages/staff_detail/staff_detail?id=${item.id}`
    })
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
}

.home-page__content {
    display: flex;
    flex-direction: column;
    gap: 30rpx;
}

.home-page__body {
    padding: 0 var(--wm-space-page-x, 37rpx) 37rpx;
}

.home-page__hero {
    position: relative;
    overflow: hidden;
    border-radius: 0 0 56rpx 56rpx;
    box-shadow: var(--wm-shadow-hero, 0 24rpx 56rpx rgba(177, 108, 95, 0.18));
}

.home-page__hero-swiper,
.home-page__hero-slide,
.home-page__hero-placeholder,
.home-page__hero-image {
    width: 100%;
    height: 100%;
}

.home-page__hero-swiper--fallback {
    background: radial-gradient(
            circle at top,
            rgba(255, 255, 255, 0.35) 0,
            rgba(255, 255, 255, 0) 28%
        ),
        linear-gradient(180deg, rgba(109, 62, 52, 0.4) 0%, rgba(74, 43, 36, 0.76) 100%);
}

.home-page__hero-slide {
    position: relative;
}

.home-page__hero-placeholder {
    position: absolute;
    inset: 0;
    background: linear-gradient(
            180deg,
            rgba(27, 19, 16, 0.06) 0%,
            rgba(27, 19, 16, 0.16) 42%,
            rgba(27, 19, 16, 0.58) 100%
        ),
        linear-gradient(180deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0) 38%);
}

.home-page__hero-copy {
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10rpx;
    padding: 0 52rpx;
    text-align: center;
    pointer-events: none;
}

.home-page__hero-accent {
    width: 52rpx;
    height: 6rpx;
    border-radius: 999rpx;
    background: var(--wm-color-primary, #e85a4f);
}

.home-page__hero-title {
    font-size: 44rpx;
    line-height: 1.32;
    font-weight: 700;
    text-shadow: 0 4rpx 10rpx rgba(47, 28, 24, 0.52), 0 12rpx 28rpx rgba(47, 28, 24, 0.72);
}

.home-page__hero-dots {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 45rpx;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
}

.home-page__hero-dot {
    width: 12rpx;
    height: 12rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.48);
}

.home-page__hero-dot--active {
    width: 28rpx;
    background: var(--wm-color-primary, #e85a4f);
}

.home-page__cta {
    min-height: 124rpx;
    border-radius: 40rpx;
    padding: 0 30rpx;
    background: linear-gradient(135deg, rgba(232, 90, 79, 1) 0%, rgba(214, 118, 92, 0.98) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 24rpx 48rpx rgba(176, 106, 91, 0.24);
}

.home-page__cta-text {
    font-size: 34rpx;
    line-height: 1;
    font-weight: 700;
    letter-spacing: 2rpx;
    color: #ffffff;
    text-align: center;
}

.home-page__section {
    padding: 24rpx 0 8rpx;
}

.home-page__section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20rpx;
    margin-bottom: 22rpx;
}

.home-page__section-copy {
    min-width: 0;
    flex: 1;
}

.home-page__section-title {
    display: block;
    font-size: 32rpx;
    line-height: 1.3;
    font-weight: 700;
    letter-spacing: 1rpx;
    color: var(--wm-text-primary, #1e2432);
}

.home-page__section-link {
    padding: 6rpx 0 6rpx 18rpx;
}

.home-page__section-link-text {
    font-size: 22rpx;
    font-weight: 600;
    color: #9b8478;
}

.home-page__team-list {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.home-page__team-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 20rpx;
    padding: 18rpx 20rpx;
    border-radius: 32rpx;
    background: linear-gradient(
        180deg,
        rgba(255, 255, 255, 0.98) 0%,
        rgba(252, 247, 242, 0.94) 100%
    );
    border: 1rpx solid rgba(231, 222, 216, 0.98);
    box-shadow: 0 12rpx 28rpx rgba(94, 64, 54, 0.06);
}

.home-page__team-card--placeholder {
    opacity: 0.88;
}

.home-page__team-image {
    width: 120rpx;
    height: 120rpx;
    display: block;
    flex-shrink: 0;
    border-radius: 24rpx;
    border: 1rpx solid rgba(231, 222, 216, 0.9);
}

.home-page__team-image--placeholder {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.4) 0%, rgba(230, 221, 215, 0.9) 100%),
        linear-gradient(180deg, rgba(239, 91, 76, 0.1) 0%, rgba(239, 91, 76, 0) 100%);
}

.home-page__team-copy {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 8rpx;
}

.home-page__team-kicker {
    display: block;
    font-size: 18rpx;
    line-height: 1.2;
    font-weight: 700;
    letter-spacing: 2rpx;
    text-transform: uppercase;
    color: #ab8f81;
}

.home-page__team-name,
.home-page__team-role {
    display: block;
}

.home-page__team-name {
    font-size: 28rpx;
    font-weight: 700;
    letter-spacing: 0.5rpx;
    color: #2b221f;
    line-height: 1.35;
}

.home-page__team-role-wrap {
    display: flex;
    align-items: center;
}

.home-page__team-role {
    max-width: 100%;
    padding: 10rpx 16rpx;
    border-radius: 999rpx;
    font-size: 21rpx;
    line-height: 1;
    font-weight: 600;
    color: #7b6458;
    background: rgba(245, 236, 230, 0.95);
    border: 1rpx solid rgba(231, 222, 216, 0.98);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.home-page__team-action {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding-left: 8rpx;
}

.home-page__team-action-text {
    font-size: 34rpx;
    line-height: 1;
    font-weight: 500;
    color: #8b766b;
}
</style>
