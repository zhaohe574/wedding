<template>
    <view class="portfolio-gallery mx-[20rpx] mt-[20rpx]" v-if="content.enabled && showList.length">
        <!-- 标题 -->
        <view v-if="content.title" class="flex items-center mb-[24rpx]">
            <view class="title-bar w-[8rpx] h-[34rpx] bg-primary rounded-full mr-[16rpx]"></view>
            <text class="text-lg font-medium text-gray-900">{{ content.title }}</text>
            <view class="flex-1"></view>
            <view 
                v-if="content.show_more" 
                class="flex items-center text-sm text-gray-500"
                @click="handleMore"
            >
                <text>查看更多</text>
                <u-icon name="arrow-right" size="12" color="#9ca3af" class="ml-1"></u-icon>
            </view>
        </view>

        <!-- 分类标签 -->
        <scroll-view 
            v-if="content.show_tabs && categories.length > 1" 
            scroll-x 
            class="category-tabs mb-[24rpx]"
            :show-scrollbar="false"
        >
            <view class="flex gap-[16rpx]">
                <view
                    v-for="(cat, index) in categories"
                    :key="index"
                    class="px-[24rpx] py-[12rpx] rounded-full text-sm flex-shrink-0 transition-all"
                    :class="activeCategory === cat ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600'"
                    @click="activeCategory = cat"
                >
                    {{ cat }}
                </view>
            </view>
        </scroll-view>

        <!-- 网格样式 -->
        <view v-if="content.style == 1" class="grid gap-[12rpx]" :style="gridStyle">
            <view
                v-for="(item, index) in filteredList"
                :key="index"
                class="portfolio-item relative rounded-lg overflow-hidden"
                @click="handleClick(item)"
            >
                <u-image
                    width="100%"
                    :height="content.per_line == 2 ? '340rpx' : '240rpx'"
                    :src="getImageUrl(item.cover)"
                    mode="aspectFill"
                />
                <!-- 视频标识 -->
                <view v-if="item.type === 'video'" class="absolute inset-0 flex items-center justify-center">
                    <view class="w-[80rpx] h-[80rpx] bg-black/50 rounded-full flex items-center justify-center">
                        <u-icon name="play-right-fill" size="32" color="#fff"></u-icon>
                    </view>
                </view>
                <!-- 标题遮罩 -->
                <view class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-[16rpx]">
                    <text class="text-white text-sm line-clamp-1">{{ item.title }}</text>
                </view>
            </view>
        </view>

        <!-- 瀑布流样式 -->
        <view v-if="content.style == 2" class="waterfall flex gap-[12rpx]">
            <view class="flex-1 flex flex-col gap-[12rpx]">
                <view
                    v-for="(item, index) in leftColumn"
                    :key="index"
                    class="portfolio-item relative rounded-lg overflow-hidden"
                    @click="handleClick(item)"
                >
                    <u-image
                        width="100%"
                        :height="item.height || '360rpx'"
                        :src="getImageUrl(item.cover)"
                        mode="aspectFill"
                    />
                    <view v-if="item.type === 'video'" class="absolute inset-0 flex items-center justify-center">
                        <view class="w-[80rpx] h-[80rpx] bg-black/50 rounded-full flex items-center justify-center">
                            <u-icon name="play-right-fill" size="32" color="#fff"></u-icon>
                        </view>
                    </view>
                    <view class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-[16rpx]">
                        <text class="text-white text-sm line-clamp-1">{{ item.title }}</text>
                        <view class="flex items-center mt-[8rpx]">
                            <u-icon name="eye" size="14" color="#fff"></u-icon>
                            <text class="text-white/80 text-xs ml-[8rpx]">{{ item.views || 0 }}</text>
                        </view>
                    </view>
                </view>
            </view>
            <view class="flex-1 flex flex-col gap-[12rpx]">
                <view
                    v-for="(item, index) in rightColumn"
                    :key="index"
                    class="portfolio-item relative rounded-lg overflow-hidden"
                    @click="handleClick(item)"
                >
                    <u-image
                        width="100%"
                        :height="item.height || '360rpx'"
                        :src="getImageUrl(item.cover)"
                        mode="aspectFill"
                    />
                    <view v-if="item.type === 'video'" class="absolute inset-0 flex items-center justify-center">
                        <view class="w-[80rpx] h-[80rpx] bg-black/50 rounded-full flex items-center justify-center">
                            <u-icon name="play-right-fill" size="32" color="#fff"></u-icon>
                        </view>
                    </view>
                    <view class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-[16rpx]">
                        <text class="text-white text-sm line-clamp-1">{{ item.title }}</text>
                        <view class="flex items-center mt-[8rpx]">
                            <u-icon name="eye" size="14" color="#fff"></u-icon>
                            <text class="text-white/80 text-xs ml-[8rpx]">{{ item.views || 0 }}</text>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <!-- 横向滑动样式 -->
        <scroll-view 
            v-if="content.style == 3" 
            scroll-x 
            class="portfolio-scroll"
            :show-scrollbar="false"
        >
            <view class="flex gap-[16rpx]">
                <view
                    v-for="(item, index) in filteredList"
                    :key="index"
                    class="portfolio-item relative rounded-xl overflow-hidden flex-shrink-0"
                    style="width: 480rpx"
                    @click="handleClick(item)"
                >
                    <u-image
                        width="480rpx"
                        height="360rpx"
                        :src="getImageUrl(item.cover)"
                        mode="aspectFill"
                    />
                    <view v-if="item.type === 'video'" class="absolute inset-0 flex items-center justify-center">
                        <view class="w-[100rpx] h-[100rpx] bg-black/50 rounded-full flex items-center justify-center">
                            <u-icon name="play-right-fill" size="40" color="#fff"></u-icon>
                        </view>
                    </view>
                    <view class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-[20rpx]">
                        <text class="text-white text-base font-medium line-clamp-1">{{ item.title }}</text>
                        <view class="flex items-center mt-[12rpx]">
                            <text v-if="item.category" class="text-white/70 text-xs px-[12rpx] py-[4rpx] bg-white/20 rounded-full">
                                {{ item.category }}
                            </text>
                            <view class="flex items-center ml-auto">
                                <u-icon name="eye" size="14" color="#fff"></u-icon>
                                <text class="text-white/80 text-xs ml-[8rpx]">{{ item.views || 0 }}</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </scroll-view>
    </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useAppStore } from '@/stores/app'
import { navigateTo } from '@/utils/util'

const props = defineProps({
    content: {
        type: Object,
        default: () => ({})
    },
    styles: {
        type: Object,
        default: () => ({})
    }
})

const { getImageUrl } = useAppStore()
const activeCategory = ref('全部')

// 过滤显示的列表
const showList = computed(() => {
    const data = props.content.data?.filter((item: any) => item.is_show !== '0') || []
    const limit = props.content.show_count || data.length
    return data.slice(0, limit)
})

// 分类列表
const categories = computed(() => {
    const cats = ['全部']
    showList.value.forEach((item: any) => {
        if (item.category && !cats.includes(item.category)) {
            cats.push(item.category)
        }
    })
    return cats
})

// 按分类过滤
const filteredList = computed(() => {
    if (activeCategory.value === '全部') {
        return showList.value
    }
    return showList.value.filter((item: any) => item.category === activeCategory.value)
})

// 网格样式
const gridStyle = computed(() => {
    const perLine = props.content.per_line || 2
    return {
        'grid-template-columns': `repeat(${perLine}, 1fr)`
    }
})

// 瀑布流左列
const leftColumn = computed(() => {
    return filteredList.value.filter((_: any, i: number) => i % 2 === 0)
})

// 瀑布流右列
const rightColumn = computed(() => {
    return filteredList.value.filter((_: any, i: number) => i % 2 === 1)
})

// 点击作品
const handleClick = (item: any) => {
    if (item.link && Object.keys(item.link).length > 0) {
        navigateTo(item.link)
    }
}

// 查看更多
const handleMore = () => {
    if (props.content.more_link && Object.keys(props.content.more_link).length > 0) {
        navigateTo(props.content.more_link)
    }
}
</script>

<style lang="scss" scoped>
.portfolio-gallery {
    .portfolio-item {
        transition: transform 0.2s ease;
        &:active {
            transform: scale(0.98);
        }
    }
    
    .category-tabs {
        margin: 0 -20rpx;
        padding: 0 20rpx;
    }
    
    .portfolio-scroll {
        margin: 0 -20rpx;
        padding: 0 20rpx;
    }
}
</style>
