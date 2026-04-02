<template>
    <tn-sticky
        v-if="!isLargeScreen"
        :enable="true"
        :offset-top="0"
        h5-nav-height="0"
        bg-color="transparent"
    >
        <view class="search-navbar" :style="navbarWrapperStyle">
            <view
                class="search-navbar__status"
                :style="{ height: `${navBarMetrics.statusBarHeight}px` }"
            ></view>
            <view
                class="search-navbar__body"
                :style="{ height: `${navBarMetrics.contentHeight}px` }"
            >
                <view class="search-navbar__side" :style="sideSlotStyle">
                    <view class="mini-search" :style="miniSearchStyle" @tap="handleSearchClick">
                        <tn-icon name="search" size="28" color="#64748B" />
                    </view>
                </view>
                <view class="search-navbar__title">
                    <image
                        v-if="metaData.title_type === 2 && metaData.title_img"
                        class="search-title-image"
                        :src="metaData.title_img"
                        mode="widthFix"
                    ></image>
                    <text
                        v-else
                        class="search-navbar__title-text"
                        :style="{ color: navbarTitleColor }"
                    >
                        {{ metaData.title }}
                    </text>
                </view>
                <view
                    class="search-navbar__side search-navbar__side--placeholder"
                    :style="sideSlotStyle"
                ></view>
            </view>
        </view>
    </tn-sticky>

    <view v-if="isLargeScreen" class="search-floating" :style="floatingSearchWrapperStyle">
        <view class="mini-search mini-search--floating" @tap="handleSearchClick">
            <tn-icon name="search" size="28" color="#64748B" />
        </view>
    </view>

    <view
        v-if="showSearchPanel && !isLargeScreen"
        class="search-container-full"
        :style="{ opacity: 1 - percent }"
    >
        <view class="search-box-wrapper-full">
            <view class="search-input-box" :style="searchInputStyle" @tap="handleSearchClick">
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
import { useNavBarMetrics } from '@/hooks/useNavBarMetrics'

const $theme = useThemeStore()
const navBarMetrics = useNavBarMetrics()

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
    if (props.percent > 0.5) return '#1E2432'
    return Number(metaData.value.text_color) === 1 ? '#FFFFFF' : '#1E2432'
})

const navbarWrapperStyle = computed(() => ({
    background: `rgba(255, 255, 255, ${Math.min(Math.max(props.percent, 0), 0.96)})`,
    boxShadow:
        props.percent > 0.2 ? '0 8rpx 24rpx rgba(15, 23, 42, 0.06)' : '0 0 0 rgba(0, 0, 0, 0)',
    backdropFilter: props.percent > 0.2 ? 'blur(16rpx)' : 'none'
}))

const sideSlotStyle = computed(() => ({
    width: `${Math.max(navBarMetrics.safeInset, 88)}px`
}))

const miniSearchStyle = computed(() => ({
    opacity: props.percent
}))

const floatingSearchWrapperStyle = computed(() => ({
    top: `${Math.max(navBarMetrics.statusBarHeight + 8, 12)}px`
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
.search-navbar {
    position: relative;
    z-index: 20;
    transition: all 0.2s ease;

    &__body {
        display: flex;
        align-items: center;
        width: 100%;
        padding: 0 20rpx;
        box-sizing: border-box;
    }

    &__side {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        height: 100%;
    }

    &__title {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 12rpx;
        overflow: hidden;
    }

    &__title-text {
        max-width: 100%;
        font-size: 32rpx;
        font-weight: 400;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
}

.search-floating {
    position: fixed;
    left: 20rpx;
    z-index: 30;
}

.search-title-image {
    width: 240rpx;
    max-width: 100%;
}

.mini-search {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 64rpx;
    height: 64rpx;
    margin-left: 20rpx;
    background-color: rgba(255, 255, 255, 0.96);
    border-radius: 50%;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;

    &:active {
        transform: scale(0.95);
        box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
    }
}

.mini-search--floating {
    margin-left: 0;
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
    font-size: 28rpx;
    color: #999999;
}

.hot-words {
    padding: 20rpx 24rpx 28rpx;
    background: #ffffff;
}

.hot-words-label {
    display: flex;
    align-items: center;
    gap: 8rpx;
    margin-bottom: 18rpx;
}

.hot-words-text {
    font-size: 24rpx;
    color: #666666;
}

.hot-words-list {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.hot-word-tag {
    padding: 12rpx 20rpx;
    border-radius: 999rpx;
    font-size: 24rpx;
    line-height: 1;
}
</style>
