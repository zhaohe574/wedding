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
                <navigator
                    url="/pages/search/search"
                    class="mini-search"
                    hover-class="none"
                    :style="miniSearchStyle"
                >
                    <tn-icon name="search" :size="32" :color="searchIconColor"></tn-icon>
                </navigator>
            </template>
            <template #title>
                <image class="!h-[54rpx]" :src="metaData.title_img" mode="widthFix"></image>
            </template>
        </tn-navbar>
    </tn-sticky>
    <!-- #endif -->
    <navigator
        v-if="!isLargeScreen"
        url="/pages/search/search"
        class="px-md mt-md mb-[30rpx]"
        :style="{ opacity: 1 - percent }"
        hover-class="none"
    >
        <tn-search-box
            :placeholder="searchPlaceholder"
            :height="72"
            :disabled="true"
            :show-action="false"
            :bg-color="searchBgColor"
            :border-radius="searchBorderRadius"
        >
            <template #prefix>
                <tn-icon name="search" :size="32" :color="searchIconColor"></tn-icon>
            </template>
        </tn-search-box>
        
        <!-- 热词显示 -->
        <view v-if="hotWords.length > 0" class="flex flex-wrap gap-sm mt-sm">
            <text
                v-for="(word, index) in hotWords"
                :key="index"
                class="text-sm text-primary"
                @click="handleHotWordClick(word)"
            >
                {{ word }}
            </text>
        </view>
    </navigator>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useThemeStore } from '@/stores/theme'

const props = defineProps({
    pageMeta: {
        type: Object,
        default: () => []
    },
    content: {
        type: Object,
        default: () => ({
            placeholder: '请输入关键词搜索',
            hotWords: [] // 热词列表
        })
    },
    styles: {
        type: Object,
        default: () => ({
            bgColor: '#ffffff',
            textColor: '#666666',
            borderRadius: 36 // 圆角36rpx
        })
    },
    isLargeScreen: {
        type: Boolean
    },
    percent: {
        type: Number
    }
})

const themeStore = useThemeStore()

// 页面元数据
const metaData: any = computed(() => {
    return props.pageMeta[0].content
})

// 搜索框配置
const searchPlaceholder = computed(() => props.content.placeholder || '请输入关键词搜索')
const searchBgColor = computed(() => props.styles.bgColor || '#ffffff')
const searchBorderRadius = computed(() => props.styles.borderRadius || 36)
const searchIconColor = computed(() => props.styles.textColor || '#666666')

// 热词列表
const hotWords = computed(() => props.content.hotWords || [])

// 迷你搜索框样式
const miniSearchStyle = computed(() => ({
    opacity: props.isLargeScreen ? 1 : props.percent
}))

// 热词点击事件
const handleHotWordClick = (word: string) => {
    uni.navigateTo({
        url: `/pages/search/search?keyword=${encodeURIComponent(word)}`
    })
}
</script>

<style scoped lang="scss">
.mini-search {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 60rpx;
    height: 60rpx;
    margin-left: 20rpx;
    background-color: #ffffff;
    border-radius: 50%;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);
    transition: all 0.2s ease;
    
    &:active {
        transform: scale(0.98);
        opacity: 0.9;
    }
}
</style>
