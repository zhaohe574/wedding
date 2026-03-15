<template>
    <!-- #ifndef H5 -->
    <u-sticky h5-nav-height="0" bg-color="transparent">
        <u-navbar
            :class="{ 'fixed top-0 z-10': isLargeScreen }"
            :is-back="false"
            :is-fixed="true"
            :title="metaData.title"
            :custom-title="metaData.title_type === 2"
            :border-bottom="false"
            :title-bold="true"
            :background="{ background: 'rgba(256,256,256,0)' }"
            :title-color="navbarTitleColor"
        >
            <template #default>
                <navigator
                    url="/pages/search/search"
                    class="mini-search"
                    hover-class="none"
                    :style="miniSearchStyle"
                >
                    <u-icon name="search"></u-icon>
                </navigator>
            </template>
            <template #title>
                <image
                    v-if="metaData.title_type === 2 && metaData.title_img"
                    class="search-title-image"
                    :src="metaData.title_img"
                    mode="widthFix"
                ></image>
                <text v-else class="search-navbar__title">{{ metaData.title }}</text>
            </template>
        </u-navbar>
    </u-sticky>
    <!-- #endif -->

    <!-- #ifdef H5 -->
    <u-sticky h5-nav-height="0" bg-color="transparent">
        <u-navbar
            :class="{ 'fixed top-0 z-10': isLargeScreen }"
            :is-back="false"
            :is-fixed="true"
            :title="metaData.title"
            :custom-title="metaData.title_type === 2"
            :border-bottom="false"
            :title-bold="true"
            :background="{ background: 'rgba(256,256,256,0)' }"
            :title-color="navbarTitleColor"
        >
            <template #default>
                <router-navigate to="/pages/search/search" class="mini-search" :style="miniSearchStyle">
                    <u-icon name="search"></u-icon>
                </router-navigate>
            </template>
            <template #title>
                <image
                    v-if="metaData.title_type === 2 && metaData.title_img"
                    class="search-title-image"
                    :src="metaData.title_img"
                    mode="widthFix"
                ></image>
                <text v-else class="search-navbar__title">{{ metaData.title }}</text>
            </template>
        </u-navbar>
    </u-sticky>
    <!-- #endif -->

    <view
        v-if="showSearchPanel && !isLargeScreen"
        class="search-container-full"
        :style="{ opacity: 1 - percent }"
    >
        <view class="search-box-wrapper-full" @tap="handleSearchClick">
            <view class="search-input-box" :style="searchInputStyle">
                <tn-icon name="search" :size="36" color="#CCCCCC"></tn-icon>
                <text class="search-placeholder">{{ searchPlaceholder }}</text>
            </view>
        </view>

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
                    @tap="handleHotWordClick(word)"
                >
                    {{ word }}
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { PropType } from 'vue'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()

const props = defineProps({
    pageMeta: {
        type: Array as PropType<any[]>,
        default: () => []
    },
    content: {
        type: Object,
        default: () => ({
            enabled: 1,
            showSearchPanel: 1,
            placeholder: '请输入关键词搜索',
            hotWords: []
        })
    },
    styles: {
        type: Object,
        default: () => ({
            bgColor: '#ffffff',
            textColor: '#666666',
            borderRadius: 48
        })
    },
    isLargeScreen: {
        type: Boolean,
        default: false
    },
    percent: {
        type: Number,
        default: 0
    }
})

const metaData = computed(() => {
    const defaultTitle = '首页'
    const meta = props.pageMeta?.[0]?.content || {}
    const title = typeof meta.title === 'string' && meta.title.trim() ? meta.title : defaultTitle

    return {
        title_type: Number(meta.title_type) === 2 ? 2 : 1,
        title,
        title_img: typeof meta.title_img === 'string' ? meta.title_img : '',
        text_color: meta.text_color
    }
})

const navbarTitleColor = computed(() => {
    return props.percent > 0.5 ? '#000' : Number(metaData.value.text_color) === 1 ? '#fff' : '#000'
})

const miniSearchStyle = computed(() => ({
    opacity: props.isLargeScreen ? 1 : props.percent
}))

const showSearchPanel = computed(() => Number(props.content?.showSearchPanel ?? 1) !== 0)

const searchPlaceholder = computed(() => {
    const ph = props.content.placeholder
    return typeof ph === 'string' && ph.trim() ? ph : '请输入关键词搜索'
})

const hotWords = computed(() => {
    return Array.isArray(props.content.hotWords) ? props.content.hotWords : []
})

const searchInputStyle = computed(() => ({
    background: props.styles.bgColor || '#f5f5f5',
    borderRadius: `${Number(props.styles.borderRadius || 48)}rpx`,
    color: props.styles.textColor || '#666666'
}))

const getLightColor = (color: string, opacity: number) => {
    const hex = color.replace('#', '')
    const r = parseInt(hex.substring(0, 2), 16)
    const g = parseInt(hex.substring(2, 4), 16)
    const b = parseInt(hex.substring(4, 6), 16)
    return `rgba(${r}, ${g}, ${b}, ${opacity})`
}

const navigateToSearch = (keyword = '') => {
    const normalizedKeyword = String(keyword || '').trim()
    uni.navigateTo({
        url: normalizedKeyword
            ? `/pages/search/search?keyword=${encodeURIComponent(normalizedKeyword)}`
            : '/pages/search/search'
    })
}

const handleSearchClick = () => {
    navigateToSearch()
}

const handleHotWordClick = (word: string) => {
    navigateToSearch(word)
}
</script>

<style scoped lang="scss">
.search-navbar__title {
    max-width: 100%;
    font-size: 32rpx;
    font-weight: 400;
    line-height: 1.2;
    color: currentColor;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.search-title-image {
    height: 54rpx !important;
}

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

.search-container-full {
    display: block;
    padding: 0;
}

.search-box-wrapper-full {
    background: #ffffff;
    padding: 16rpx 24rpx;
    border-bottom: 1rpx solid #f0f0f0;
}

.search-input-box {
    display: flex;
    align-items: center;
    gap: 16rpx;
    height: 72rpx;
    padding: 0 24rpx;
    transition: all 0.2s ease;
}

.search-placeholder {
    flex: 1;
    font-size: 28rpx;
    color: #cccccc;
}

.hot-words {
    margin-top: 0;
    padding: 16rpx 24rpx 24rpx;
    background: #ffffff;
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
