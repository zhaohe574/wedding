<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
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
                <w-nav :content="item.content" :styles="item.styles"/>
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
        </template>

        <!-- 最新资讯（根据装修配置显示） -->
        <view class="article" v-if="showNewsSection && state.article.length">
            <view class="flex items-center article-title mx-[20rpx] my-[30rpx] text-lg font-medium">
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
        <view class="text-center py-4 mb-12">
            <router-navigate
                class="mx-1 text-xs text-[#495770]"
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
            :customStyle="{
                backgroundColor: '#FFF',
                color: '#000',
                boxShadow: '0px 3px 6px rgba(0, 0, 0, 0.1)'
            }"
        ></u-back-top>

        <!--  #ifdef MP  -->
        <MpPrivacyPopup></MpPrivacyPopup>
        <!--  #endif  -->

        <tabbar/>
    </view>
</template>

<script setup lang="ts">
import { getIndex } from '@/api/shop'
import { onLoad, onPageScroll, onShow } from "@dcloudio/uni-app"
import { computed, reactive, ref } from 'vue'
import { useAppStore } from '@/stores/app'

// #ifdef MP
import MpPrivacyPopup from './component/mp-privacy-popup.vue'
// #endif

const appStore = useAppStore()

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

// 判断组件是否启用
const isComponentEnabled = (item: any) => {
    return item.content?.enabled !== 0
}

// 是否联动背景图
const isLinkage = computed(() => {
    return state.pages.find((item: any) => item.name === 'banner')?.content?.bg_style === 1
})

// 是否大屏banner
const isLargeScreen = computed(() => {
    return state.pages.find((item: any) => item.name === 'banner')?.content?.style === 2
})

// 最新资讯配置
const newsConfig = computed(() => {
    return state.pages.find((item: any) => item.name === 'news')
})

// 是否显示最新资讯
const showNewsSection = computed(() => {
    const config = newsConfig.value
    return config && config.content?.enabled !== 0
})

// 根页面样式
const pageStyle = computed(() => {
    const { bg_type, bg_color, bg_image } = state.meta[0]?.content ?? {}
    if (!isLinkage.value) {
        return bg_type == 1 ?
            { 'background-color': bg_color || '#f8f8f8' } :
            { 'background-image': `url(${bg_image})` }
    }
    return { 'background-image': `url(${state.bannerImage})` }
})

const handleBanner = (url: string) => {
    state.bannerImage = url
}

// 获取装修数据
const getData = async () => {
    try {
        const data = await getIndex()
        if (data?.page?.data) {
            state.pages = JSON.parse(data.page.data)
        }
        if (data?.page?.meta) {
            state.meta = JSON.parse(data.page.meta)
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
    transition: all 1s;
    min-height: calc(100vh - env(safe-area-inset-bottom));
    background-color: #f8f8f8;
}

// 资讯
.article-title {
    &::before {
        content: '';
        width: 8rpx;
        height: 34rpx;
        display: block;
        margin-right: 10rpx;
        @apply bg-primary;
    }
}
</style>
