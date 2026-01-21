<template>
    <view class="activity-zone mx-[20rpx] mt-[20rpx]" v-if="content.enabled && showList.length">
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
                <text>更多活动</text>
                <u-icon name="arrow-right" size="12" color="#9ca3af" class="ml-1"></u-icon>
            </view>
        </view>

        <!-- 大图样式 -->
        <view v-if="content.style == 1" class="activity-banner space-y-[16rpx]">
            <view
                v-for="(item, index) in showList"
                :key="index"
                class="activity-item relative rounded-xl overflow-hidden"
                @click="handleClick(item.link)"
            >
                <u-image
                    width="100%"
                    height="320rpx"
                    :src="getImageUrl(item.image)"
                    mode="aspectFill"
                />
                <!-- 活动信息遮罩 -->
                <view class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent flex flex-col justify-end p-[24rpx]">
                    <!-- 标签 -->
                    <view v-if="item.tag" class="absolute top-[16rpx] left-[16rpx] px-[16rpx] py-[6rpx] bg-red-500 rounded-full">
                        <text class="text-white text-xs font-medium">{{ item.tag }}</text>
                    </view>
                    <!-- 倒计时 -->
                    <view v-if="item.show_countdown && item.end_time" class="absolute top-[16rpx] right-[16rpx]">
                        <countdown :end-time="item.end_time" />
                    </view>
                    
                    <text class="text-white text-lg font-bold">{{ item.title }}</text>
                    <text v-if="item.desc" class="text-white/80 text-sm mt-[8rpx] line-clamp-1">{{ item.desc }}</text>
                    <view class="flex items-center justify-between mt-[12rpx]">
                        <view v-if="item.price" class="flex items-baseline">
                            <text class="text-sm text-white/80">¥</text>
                            <text class="text-xl font-bold text-white">{{ item.price }}</text>
                            <text v-if="item.original_price" class="text-sm text-white/50 line-through ml-[8rpx]">
                                ¥{{ item.original_price }}
                            </text>
                        </view>
                        <view class="px-[24rpx] py-[12rpx] bg-white rounded-full">
                            <text class="text-primary text-sm font-medium">立即参与</text>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <!-- 卡片网格样式 -->
        <view v-if="content.style == 2" class="activity-grid grid grid-cols-2 gap-[16rpx]">
            <view
                v-for="(item, index) in showList"
                :key="index"
                class="activity-item bg-white rounded-xl overflow-hidden shadow-sm"
                @click="handleClick(item.link)"
            >
                <view class="relative">
                    <u-image
                        width="100%"
                        height="200rpx"
                        :src="getImageUrl(item.image)"
                        mode="aspectFill"
                    />
                    <view v-if="item.tag" class="absolute top-[12rpx] left-[12rpx] px-[12rpx] py-[4rpx] bg-red-500 rounded-full">
                        <text class="text-white text-xs">{{ item.tag }}</text>
                    </view>
                </view>
                <view class="p-[16rpx]">
                    <text class="text-sm font-medium text-gray-900 line-clamp-1">{{ item.title }}</text>
                    <view v-if="item.price" class="flex items-baseline mt-[8rpx]">
                        <text class="text-xs text-primary">¥</text>
                        <text class="text-base font-bold text-primary">{{ item.price }}</text>
                        <text v-if="item.original_price" class="text-xs text-gray-400 line-through ml-[8rpx]">
                            ¥{{ item.original_price }}
                        </text>
                    </view>
                </view>
            </view>
        </view>

        <!-- 横向滑动样式 -->
        <scroll-view 
            v-if="content.style == 3" 
            scroll-x 
            class="activity-scroll"
            :show-scrollbar="false"
        >
            <view class="flex gap-[16rpx]">
                <view
                    v-for="(item, index) in showList"
                    :key="index"
                    class="activity-item relative rounded-xl overflow-hidden flex-shrink-0"
                    style="width: 560rpx"
                    @click="handleClick(item.link)"
                >
                    <u-image
                        width="560rpx"
                        height="280rpx"
                        :src="getImageUrl(item.image)"
                        mode="aspectFill"
                    />
                    <!-- 活动信息 -->
                    <view class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex flex-col justify-end p-[20rpx]">
                        <view v-if="item.tag" class="absolute top-[12rpx] left-[12rpx] px-[12rpx] py-[4rpx] bg-red-500 rounded-full">
                            <text class="text-white text-xs">{{ item.tag }}</text>
                        </view>
                        <text class="text-white text-base font-bold line-clamp-1">{{ item.title }}</text>
                        <view class="flex items-center justify-between mt-[8rpx]">
                            <view v-if="item.price" class="flex items-baseline">
                                <text class="text-xs text-white/80">¥</text>
                                <text class="text-lg font-bold text-white">{{ item.price }}</text>
                            </view>
                            <view class="px-[16rpx] py-[8rpx] bg-white/20 rounded-full">
                                <text class="text-white text-xs">查看详情</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </scroll-view>

        <!-- 列表样式 -->
        <view v-if="content.style == 4" class="activity-list space-y-[16rpx]">
            <view
                v-for="(item, index) in showList"
                :key="index"
                class="activity-item bg-white rounded-xl overflow-hidden shadow-sm"
                @click="handleClick(item.link)"
            >
                <view class="flex">
                    <view class="relative flex-shrink-0">
                        <u-image
                            width="240rpx"
                            height="180rpx"
                            :src="getImageUrl(item.image)"
                            mode="aspectFill"
                        />
                        <view v-if="item.tag" class="absolute top-[12rpx] left-[12rpx] px-[12rpx] py-[4rpx] bg-red-500 rounded-full">
                            <text class="text-white text-xs">{{ item.tag }}</text>
                        </view>
                    </view>
                    <view class="flex-1 p-[20rpx] flex flex-col justify-between">
                        <view>
                            <text class="text-base font-medium text-gray-900 line-clamp-1">{{ item.title }}</text>
                            <text v-if="item.desc" class="text-xs text-gray-500 mt-[8rpx] line-clamp-2">{{ item.desc }}</text>
                        </view>
                        <view class="flex items-center justify-between">
                            <view v-if="item.price" class="flex items-baseline">
                                <text class="text-xs text-primary">¥</text>
                                <text class="text-lg font-bold text-primary">{{ item.price }}</text>
                                <text v-if="item.original_price" class="text-xs text-gray-400 line-through ml-[8rpx]">
                                    ¥{{ item.original_price }}
                                </text>
                            </view>
                            <view class="px-[20rpx] py-[8rpx] bg-primary rounded-full">
                                <text class="text-white text-xs">立即参与</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
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

// 过滤显示的列表
const showList = computed(() => {
    const data = props.content.data?.filter((item: any) => item.is_show !== '0') || []
    const limit = props.content.show_count || data.length
    return data.slice(0, limit)
})

// 点击活动
const handleClick = (link: any) => {
    if (link && Object.keys(link).length > 0) {
        navigateTo(link)
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
.activity-zone {
    .activity-item {
        transition: transform 0.2s ease;
        &:active {
            transform: scale(0.98);
        }
    }
    
    .activity-scroll {
        margin: 0 -20rpx;
        padding: 0 20rpx;
    }
}
</style>
