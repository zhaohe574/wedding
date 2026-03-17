<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
        <!-- #endif -->
    </page-meta>
    <view class="index page-with-tabbar-safe-bottom" :style="pageStyle">
        <!-- 动态装修组件渲染 -->
        <template v-for="(item, index) in state.pages" :key="index">
            <!-- 搜索组件 -->
            <template v-if="item.name == 'search' && isComponentEnabled(item)">
                <w-search
                    :pageMeta="state.meta"
                    :content="item.content"
                    :styles="item.styles"
                    :percent="percent"
                    :isLargeScreen="isLargeScreen"
                />
            </template>
            <!-- 轮播图组件 -->
            <template v-if="item.name == 'banner' && isComponentEnabled(item)">
                <w-banner
                    :content="item.content"
                    :styles="item.styles"
                    :isLargeScreen="isLargeScreen"
                />
            </template>
            <!-- 导航菜单组件 -->
            <template v-if="item.name == 'nav' && isComponentEnabled(item)">
                <w-nav :content="item.content" :styles="item.styles" />
            </template>
            <!-- 中间banner组件 -->
            <template v-if="item.name == 'middle-banner' && isComponentEnabled(item)">
                <w-middle-banner :content="item.content" :styles="item.styles" />
            </template>
            <!-- 人员推荐组件 -->
            <template v-if="item.name == 'staff-showcase' && isComponentEnabled(item)">
                <w-staff-showcase :content="item.content" :styles="item.styles" />
            </template>
            <!-- 案例作品组件 -->
            <template v-if="item.name == 'portfolio-gallery' && isComponentEnabled(item)">
                <w-portfolio-gallery :content="item.content" :styles="item.styles" />
            </template>
            <!-- 客户评价组件 -->
            <template v-if="item.name == 'customer-reviews' && isComponentEnabled(item)">
                <w-customer-reviews :content="item.content" :styles="item.styles" />
            </template>
            <!-- 活动专区组件 -->
            <template v-if="item.name == 'activity-zone' && isComponentEnabled(item)">
                <w-activity-zone :content="item.content" :styles="item.styles" />
            </template>
            <!-- 订单快捷入口组件 -->
            <template v-if="item.name == 'order-quick-entry' && isComponentEnabled(item)">
                <w-order-quick-entry :content="item.content" :styles="item.styles" />
            </template>
            <!-- 快捷入口组件 -->
            <template v-if="item.name == 'quick-entry' && isComponentEnabled(item)">
                <w-quick-entry :content="item.content" :styles="item.styles" />
            </template>
            <!-- 优惠券领取组件 -->
            <template v-if="item.name == 'coupon-receive' && isComponentEnabled(item)">
                <w-coupon-receive :content="item.content" :styles="item.styles" />
            </template>
            <!-- 数据统计卡片组件 -->
            <template v-if="item.name == 'data-stats' && isComponentEnabled(item)">
                <w-data-stats :content="item.content" :styles="item.styles" />
            </template>
            <!-- 常见问题组件 -->
            <template v-if="item.name == 'faq' && isComponentEnabled(item)">
                <w-faq :content="item.content" :styles="item.styles" />
            </template>
            <!-- 服务流程组件 -->
            <template v-if="item.name == 'service-process' && isComponentEnabled(item)">
                <w-service-process :content="item.content" :styles="item.styles" />
            </template>
            <!-- 公告通知组件 -->
            <template v-if="item.name == 'notice-bar' && isComponentEnabled(item)">
                <w-notice-bar :content="item.content" :styles="item.styles" />
            </template>
            <!-- 热门话题组件 -->
            <template v-if="item.name == 'hot-topics' && isComponentEnabled(item)">
                <w-hot-topics :content="item.content" :styles="item.styles" />
            </template>
            <!-- 门店地图组件 -->
            <template v-if="item.name == 'store-map' && isComponentEnabled(item)">
                <w-store-map :content="item.content" :styles="item.styles" />
            </template>
            <!-- 婚礼倒计时组件 -->
            <template v-if="item.name == 'wedding-countdown' && isComponentEnabled(item)">
                <w-wedding-countdown :content="item.content" :styles="item.styles" />
            </template>
        </template>

        <!-- 最新资讯（根据装修配置显示） -->
        <view class="article" v-if="showNewsSection && state.article.length">
            <view
                class="article-title mx-md my-md text-[32rpx] font-semibold"
                :style="articleTitleStyle"
            >
                <view class="article-title__bar" :style="articleBarStyle"></view>
                <text class="article-title__text">{{
                    newsTitle
                }}</text>
            </view>
            <news-card
                v-for="item in state.article"
                :key="item.id"
                :news-id="item.id"
                :item="item"
            />
        </view>

        <!--  #ifdef H5  -->
        <view class="text-center py-lg mb-[96rpx]">
            <router-navigate
                class="mx-sm text-xs text-muted"
                :to="{
                    path: '/pages/webview/webview',
                    query: { url: item.value }
                }"
                v-for="item in appStore.getCopyrightConfig"
                :key="item.key"
            >
                {{ item.key }}
            </router-navigate>
        </view>
        <!--  #endif  -->

        <!-- 返回顶部按钮 -->
        <view
            v-if="showBackTop"
            class="back-top-button"
            :style="backTopStyle"
            @tap="handleBackTop"
        >
            <tn-icon name="up-arrow" size="34" :color="primaryColor" />
        </view>

        <!--  #ifdef MP  -->
        <MpPrivacyPopup></MpPrivacyPopup>
        <!--  #endif  -->

        <tabbar />
    </view>
</template>

<script setup lang="ts">
import { getIndex } from '@/api/shop'
import { onLoad, onPageScroll } from '@dcloudio/uni-app'
import { computed, reactive, ref } from 'vue'
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import { alphaColor, tintColor } from '@/utils/color'

// #ifdef MP
import MpPrivacyPopup from './component/mp-privacy-popup.vue'
// #endif

const appStore = useAppStore()
const themeStore = useThemeStore()
const primaryColor = computed(() => themeStore.primaryColor || '#7C3AED')
const primaryLight9 = computed(() => tintColor(primaryColor.value, 0.9))
const primaryLight3 = computed(() => tintColor(primaryColor.value, 0.3))
const primaryShadow = computed(() => alphaColor(primaryColor.value, 0.12))

const state = reactive<{
    pages: any[]
    meta: any[]
    article: any[]
}>({
    pages: [],
    meta: [],
    article: []
})

const scrollTop = ref<number>(0)
const percent = ref<number>(0)

// 判断组件是否启用（使用computed缓存）
const isComponentEnabled = (item: any) => {
    return item.content?.enabled !== 0
}

const bannerConfig = computed(() => {
    return state.pages.find((item: any) => item.name === 'banner')
})

// 是否启用大屏轮播（仅轮播启用且样式为大屏时生效）
const isLargeScreen = computed(() => {
    const banner = bannerConfig.value
    if (!banner) {
        return false
    }

    return Number(banner.content?.enabled ?? 1) !== 0 && Number(banner.content?.style) === 2
})

// 最新资讯配置（使用computed缓存）
const newsConfig = computed(() => {
    return state.pages.find((item: any) => item.name === 'news')
})

// 是否显示最新资讯（使用computed缓存）
const showNewsSection = computed(() => {
    const config = newsConfig.value
    return config && config.content?.enabled !== 0
})

// 资讯标题（防御非字符串值）
const newsTitle = computed(() => {
    const title = newsConfig.value?.content?.title
    return typeof title === 'string' && title.trim() ? title : '最新资讯'
})

// 资讯标题样式
const articleTitleStyle = computed(() => ({
    display: 'flex',
    alignItems: 'center',
    gap: '16rpx',
    color: '#333333'
}))

const articleBarStyle = computed(() => ({
    width: '8rpx',
    height: '34rpx',
    borderRadius: '999rpx',
    background: `linear-gradient(180deg, ${primaryColor.value} 0%, ${primaryLight3.value} 100%)`,
    boxShadow: `0 2rpx 8rpx ${primaryShadow.value}`
}))

// 返回顶部按钮样式
const backTopStyle = computed(() => ({
    backgroundColor: '#FFFFFF',
    color: primaryColor.value,
    boxShadow: `0 2rpx 12rpx ${alphaColor(primaryColor.value, 0.12)}`,
    border: `1rpx solid ${tintColor(primaryColor.value, 0.75)}`,
    borderRadius: '50%',
    transition: 'all 0.2s ease'
}))

// 根页面样式
const defaultBackground = computed(() => {
    return `linear-gradient(180deg, ${primaryLight9.value} 0%, #FFFFFF 100%)`
})

const pageStyle = computed(() => ({
    background: defaultBackground.value
}))

const showBackTop = computed(() => scrollTop.value > uni.upx2px(320))

// 获取装修数据（优化性能）
const getData = async () => {
    try {
        const data = await getIndex()
        if (data?.page?.data) {
            // 处理 data.page.data，可能是字符串或对象
            if (typeof data.page.data === 'string') {
                state.pages = JSON.parse(data.page.data)
            } else {
                state.pages = data.page.data
            }
            state.pages = state.pages.filter((item: any) => item?.name !== 'service-packages')
        }
        if (data?.page?.meta) {
            if (typeof data.page.meta === 'string') {
                state.meta = JSON.parse(data.page.meta)
            } else {
                state.meta = data.page.meta
            }
            const title = state.meta?.[0]?.content?.title
            if (typeof title === 'string' && title.trim()) {
                uni.setNavigationBarTitle({ title })
            }
        } else {
            state.meta = []
        }
        state.article = data?.article || []
    } catch (e) {
        console.error('获取首页数据失败:', e)
    }
}

// 页面滚动事件（节流优化）
onPageScroll((event: any) => {
    scrollTop.value = event.scrollTop
    const top = uni.upx2px(100)
    percent.value = event.scrollTop / top > 1 ? 1 : event.scrollTop / top
})

const handleBackTop = () => {
    uni.pageScrollTo({
        scrollTop: 0,
        duration: 220
    })
}

onLoad(() => {
    // 首页数据仅在首次加载时初始化，避免首屏 onLoad/onShow 重复请求
    getData()
})
</script>

<style lang="scss" scoped>
.index {
    position: relative;
    background-repeat: no-repeat;
    background-size: 100% auto;
    overflow: hidden;
    width: 100%;
    transition: all 0.3s ease;
    min-height: calc(100vh - env(safe-area-inset-bottom));

    // 默认使用浅色渐变背景（随主题调整）
    background: linear-gradient(180deg, #f5edff 0%, #ffffff 100%);
}

// 资讯区域
.article {
    margin-top: 32rpx; // 使用lg间距
}

// 资讯标题
.article-title {
    display: flex;
    align-items: center;
}

.article-title__text {
    color: #333333;
}

.back-top-button {
    position: fixed;
    right: 28rpx;
    bottom: calc(140rpx + env(safe-area-inset-bottom));
    width: 88rpx;
    height: 88rpx;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 30;
}
</style>
