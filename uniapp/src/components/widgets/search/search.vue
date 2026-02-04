<template>
    <!-- #ifndef H5 -->
    <tn-sticky h5-nav-height="0" bg-color="transparent">
        <tn-navbar
            :class="{ 'fixed top-0 z-10': isLargeScreen }"
            :is-back="false"
            :is-fixed="true"
            :title="metaData.title"
            :custom-title="metaData.title_type == 2"
            :border-bottom="false"
            :title-bold="true"
            :background="{ background: 'rgba(256,256, 256, 0)' }"
            :title-color="percent > 0.5 ? '#000' : metaData.text_color == 1 ? '#fff' : '#000'"
        >
            <template #default>
                <!-- #ifndef H5 -->
                <navigator
                    url="/pages/search/search"
                    class="mini-search"
                    hover-class="none"
                    :style="miniSearchStyle"
                >
                    <tn-icon name="search" :size="32" :color="$theme.primaryColor"></tn-icon>
                </navigator>
                <!-- #endif -->
                <!-- #ifdef H5 -->
                <router-navigate
                    to="/pages/search/search"
                    class="mini-search"
                    :style="miniSearchStyle"
                >
                    <tn-icon name="search" :size="32" :color="$theme.primaryColor"></tn-icon>
                </router-navigate>
                <!-- #endif -->
            </template>
            <template #title>
                <image class="!h-[54rpx]" :src="metaData.title_img" mode="widthFix"></image>
            </template>
        </tn-navbar>
    </tn-sticky>
    <!-- #endif -->
    
    <!-- 主搜索框区域 -->
    <!-- #ifndef H5 -->
    <navigator
        v-if="!isLargeScreen"
        url="/pages/search/search"
        class="search-container"
        :style="{ opacity: 1 - percent }"
        hover-class="none"
    >
        <tn-search-box
            :placeholder="searchPlaceholder"
            :height="88"
            :disabled="true"
            :show-action="false"
            :bg-color="searchBgColor"
            :border-radius="0"
        >
            <template #prefix>
                <tn-icon name="search" :size="36" color="#999999"></tn-icon>
            </template>
        </tn-search-box>

        <!-- 热词标签 -->
        <view v-if="hotWords.length > 0" class="hot-words">
            <view class="hot-words-label">
                <tn-icon name="fire" :size="28" :color="$theme.primaryColor" />
                <text class="hot-words-text">热门搜索</text>
            </view>
            <view class="hot-words-list">
                <view
                    v-for="(word, index) in hotWords"
                    :key="index"
                    class="hot-word-tag"
                    :style="{ 
                        backgroundColor: getLightColor($theme.primaryColor, 0.08),
                        color: $theme.primaryColor
                    }"
                    @click.stop="handleHotWordClick(word)"
                >
                    {{ word }}
                </view>
            </view>
        </view>
    </navigator>
    <!-- #endif -->
    
    <!-- #ifdef H5 -->
    <router-navigate
        v-if="!isLargeScreen"
        to="/pages/search/search"
        class="search-container"
        :style="{ opacity: 1 - percent }"
    >
        <tn-search-box
            :placeholder="searchPlaceholder"
            :height="88"
            :disabled="true"
            :show-action="false"
            :bg-color="searchBgColor"
            :border-radius="0"
        >
            <template #prefix>
                <tn-icon name="search" :size="36" color="#999999"></tn-icon>
            </template>
        </tn-search-box>

        <!-- 热词标签 -->
        <view v-if="hotWords.length > 0" class="hot-words">
            <view class="hot-words-label">
                <tn-icon name="fire" :size="28" :color="$theme.primaryColor" />
                <text class="hot-words-text">热门搜索</text>
            </view>
            <view class="hot-words-list">
                <view
                    v-for="(word, index) in hotWords"
                    :key="index"
                    class="hot-word-tag"
                    :style="{ 
                        backgroundColor: getLightColor($theme.primaryColor, 0.08),
                        color: $theme.primaryColor
                    }"
                    @click.stop="handleHotWordClick(word)"
                >
                    {{ word }}
                </view>
            </view>
        </view>
    </router-navigate>
    <!-- #endif -->
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()

const props = defineProps({
    pageMeta: {
        type: Object,
        default: () => []
    },
    content: {
        type: Object,
        default: () => ({
            placeholder: '搜索人员/服务/作品',
            hotWords: [] // 热词列表
        })
    },
    styles: {
        type: Object,
        default: () => ({
            bgColor: '#ffffff',
            textColor: '#666666',
            borderRadius: 48 // 圆角48rpx（更圆润）
        })
    },
    isLargeScreen: {
        type: Boolean
    },
    percent: {
        type: Number
    }
})

// 页面元数据
const metaData: any = computed(() => {
    return props.pageMeta[0].content
})

// 搜索框配置
const searchPlaceholder = computed(() => props.content.placeholder || '搜索人员/服务/作品')
const searchBgColor = computed(() => props.styles.bgColor || '#F5F5F5')

// 热词列表
const hotWords = computed(() => props.content.hotWords || [])

// 迷你搜索框样式
const miniSearchStyle = computed(() => ({
    opacity: props.isLargeScreen ? 1 : props.percent
}))

// 获取浅色变体
const getLightColor = (color: string, opacity: number) => {
    const hex = color.replace('#', '')
    const r = parseInt(hex.substring(0, 2), 16)
    const g = parseInt(hex.substring(2, 4), 16)
    const b = parseInt(hex.substring(4, 6), 16)
    return `rgba(${r}, ${g}, ${b}, ${opacity})`
}

// 热词点击事件
const handleHotWordClick = (word: string) => {
    uni.navigateTo({
        url: `/pages/search/search?keyword=${encodeURIComponent(word)}`
    })
}
</script>

<style scoped lang="scss">
// 迷你搜索按钮（导航栏）
.mini-search {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 64rpx;
    height: 64rpx;
    margin-left: 20rpx;
    background-color: #ffffff;
    border-radius: 50%;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
    
    &:active {
        transform: scale(0.95);
        box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
    }
}

// 搜索容器（无左右边距，全屏宽度）
.search-container {
    display: block;
    padding: 24rpx 0;
}

// 热词区域（有左右内边距）
.hot-words {
    margin-top: 24rpx;
    padding: 0 24rpx;
}

.hot-words-label {
    display: flex;
    align-items: center;
    gap: 8rpx;
    margin-bottom: 16rpx;
}

.hot-words-text {
    font-size: 26rpx;
    font-weight: 500;
    color: #666666;
}

.hot-words-list {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.hot-word-tag {
    padding: 10rpx 24rpx;
    border-radius: 999rpx;
    font-size: 26rpx;
    font-weight: 500;
    transition: all 0.2s ease;
    
    &:active {
        transform: scale(0.96);
        opacity: 0.8;
    }
}
</style>
