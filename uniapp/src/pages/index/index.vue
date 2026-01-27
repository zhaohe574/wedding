<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
        <!-- #endif -->
    </page-meta>
    <view class="index" :style="pageStyle">
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
                    @change="handleBanner"
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
            <!-- 服务套餐组件 -->
            <template v-if="item.name == 'service-packages' && isComponentEnabled(item)">
                <w-service-packages :content="item.content" :styles="item.styles" />
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
            <view class="flex items-center article-title mx-md my-md text-[32rpx] font-semibold">
                {{ newsConfig?.content?.title || '最新资讯' }}
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
        <u-back-top
            :scroll-top="scrollTop"
            :top="100"
            :custom-style="backTopStyle"
        ></u-back-top>

        <!--  #ifdef MP  -->
        <MpPrivacyPopup></MpPrivacyPopup>
        <!--  #endif  -->

        <tabbar />
    </view>
</template>

<script setup lang="ts">
import { getIndex } from '@/api/shop'
import { onLoad, onPageScroll, onShow } from '@dcloudio/uni-app'
import { computed, reactive, ref } from 'vue'
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'

// #ifdef MP
import MpPrivacyPopup from './component/mp-privacy-popup.vue'
// #endif

const appStore = useAppStore()
const themeStore = useThemeStore()

const state = reactive<{
    pages: any[]
    meta: any[]
    article: any[]
    bannerImage: string
}>({
    pages: [],
    meta: [],
    article: [],
    bannerImage: ''
})

const scrollTop = ref<number>(0)
const percent = ref<number>(0)

// 判断组件是否启用（使用computed缓存）
const isComponentEnabled = (item: any) => {
    return item.content?.enabled !== 0
}

// 是否联动背景图（使用computed缓存）
const isLinkage = computed(() => {
    return state.pages.find((item: any) => item.name === 'banner')?.content?.bg_style === 1
})

// 是否大屏banner（使用computed缓存）
const isLargeScreen = computed(() => {
    return state.pages.find((item: any) => item.name === 'banner')?.content?.style === 2
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

// 返回顶部按钮样式
const backTopStyle = computed(() => ({
    backgroundColor: '#FFFFFF',
    color: themeStore.primaryColor || '#7C3AED',
    boxShadow: '0 2rpx 12rpx rgba(0, 0, 0, 0.08)',
    borderRadius: '50%',
    transition: 'all 0.2s ease'
}))

// 根页面样式
const pageStyle = computed(() => {
    const { bg_type, bg_color, bg_image } = state.meta[0]?.content ?? {}
    
    // 如果没有配置背景，使用默认的浅紫色渐变
    if (!bg_type && !bg_color && !bg_image && !isLinkage.value) {
        return {
            'background': 'linear-gradient(180deg, #FAF5FF 0%, #FFFFFF 100%)'
        }
    }
    
    if (!isLinkage.value) {
        if (bg_type == 1) {
            return { 'background-color': bg_color || '#FAF5FF' }
        } else {
            // 添加图片存在性检查，避免 404 错误
            return bg_image 
                ? { 'background-image': `url(${bg_image})` }
                : { 'background': 'linear-gradient(180deg, #FAF5FF 0%, #FFFFFF 100%)' }
        }
    }
    // 联动模式下也添加检查
    return state.bannerImage 
        ? { 'background-image': `url(${state.bannerImage})` }
        : { 'background': 'linear-gradient(180deg, #FAF5FF 0%, #FFFFFF 100%)' }
})

const handleBanner = (url: string) => {
    state.bannerImage = url
}

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
        }
        if (data?.page?.meta) {
            // 处理 data.page.meta，可能是字符串或对象
            if (typeof data.page.meta === 'string') {
                state.meta = JSON.parse(data.page.meta)
            } else {
                state.meta = data.page.meta
            }
            const title = state.meta[0]?.content?.title
            if (title) {
                uni.setNavigationBarTitle({ title })
            }
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

onLoad(() => {
    getData()
})

onShow(() => {
    // 页面显示时刷新数据
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
    
    // 默认使用浅紫色渐变背景（#FAF5FF → #FFFFFF）
    background: linear-gradient(180deg, var(--color-primary-light-9, #FAF5FF) 0%, #FFFFFF 100%);
}

// 资讯区域
.article {
    margin-top: 32rpx; // 使用lg间距
}

// 资讯标题
.article-title {
    color: var(--color-main);
    
    &::before {
        content: '';
        width: 8rpx; // 使用xs间距
        height: 34rpx;
        display: block;
        margin-right: 16rpx; // 使用sm间距
        background: linear-gradient(180deg, var(--color-primary) 0%, var(--color-primary-light-3) 100%);
        border-radius: 999rpx;
        box-shadow: 0 2rpx 8rpx rgba(124, 58, 237, 0.3);
    }
}
</style>
