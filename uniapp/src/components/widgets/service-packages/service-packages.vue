<template>
    <view class="service-packages mx-[20rpx] mt-[20rpx]" v-if="content.enabled && showList.length">
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

        <!-- 横向滑动样式 -->
        <scroll-view 
            v-if="content.style == 1" 
            scroll-x 
            class="package-scroll"
            :show-scrollbar="false"
        >
            <view class="flex gap-[20rpx]">
                <view
                    v-for="(item, index) in showList"
                    :key="index"
                    class="package-card flex-shrink-0 bg-white rounded-xl overflow-hidden shadow-sm"
                    :style="{ width: content.card_width || '520rpx' }"
                    @click="handleClick(item.link)"
                >
                    <!-- 封面图 -->
                    <view class="relative">
                        <u-image
                            width="100%"
                            height="300rpx"
                            :src="getImageUrl(item.image)"
                            mode="aspectFill"
                        />
                        <!-- 热门/推荐标签 -->
                        <view 
                            v-if="item.tag" 
                            class="absolute top-[16rpx] left-[16rpx] px-[16rpx] py-[6rpx] bg-gradient-to-r from-primary to-primary/80 rounded-full"
                        >
                            <text class="text-white text-xs">{{ item.tag }}</text>
                        </view>
                    </view>
                    <!-- 信息 -->
                    <view class="p-[24rpx]">
                        <text class="text-base font-medium text-gray-900 line-clamp-1">{{ item.name }}</text>
                        <view class="flex items-baseline mt-[12rpx]">
                            <text class="text-xs text-primary">¥</text>
                            <text class="text-xl font-bold text-primary">{{ item.price }}</text>
                            <text v-if="item.original_price" class="text-xs text-gray-400 line-through ml-[12rpx]">
                                ¥{{ item.original_price }}
                            </text>
                        </view>
                        <!-- 服务项 -->
                        <view v-if="item.services && item.services.length" class="mt-[16rpx]">
                            <view class="flex flex-wrap gap-[8rpx]">
                                <view
                                    v-for="(service, sIndex) in item.services.slice(0, 4)"
                                    :key="sIndex"
                                    class="flex items-center text-xs text-gray-500"
                                >
                                    <u-icon name="checkmark" size="12" color="#7c3aed" class="mr-1"></u-icon>
                                    <text>{{ service }}</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </scroll-view>

        <!-- 纵向列表样式 -->
        <view v-if="content.style == 2" class="package-list space-y-[20rpx]">
            <view
                v-for="(item, index) in showList"
                :key="index"
                class="package-card bg-white rounded-xl overflow-hidden shadow-sm"
                @click="handleClick(item.link)"
            >
                <view class="flex">
                    <!-- 封面图 -->
                    <view class="relative flex-shrink-0">
                        <u-image
                            width="240rpx"
                            height="240rpx"
                            :src="getImageUrl(item.image)"
                            mode="aspectFill"
                        />
                        <!-- 热门/推荐标签 -->
                        <view 
                            v-if="item.tag" 
                            class="absolute top-[12rpx] left-[12rpx] px-[12rpx] py-[4rpx] bg-gradient-to-r from-primary to-primary/80 rounded-full"
                        >
                            <text class="text-white text-xs">{{ item.tag }}</text>
                        </view>
                    </view>
                    <!-- 信息 -->
                    <view class="flex-1 p-[24rpx] flex flex-col justify-between">
                        <view>
                            <text class="text-base font-medium text-gray-900 line-clamp-1">{{ item.name }}</text>
                            <text v-if="item.desc" class="text-xs text-gray-500 mt-[8rpx] line-clamp-2">{{ item.desc }}</text>
                        </view>
                        <view class="flex items-center justify-between mt-[12rpx]">
                            <view class="flex items-baseline">
                                <text class="text-xs text-primary">¥</text>
                                <text class="text-lg font-bold text-primary">{{ item.price }}</text>
                                <text v-if="item.original_price" class="text-xs text-gray-400 line-through ml-[8rpx]">
                                    ¥{{ item.original_price }}
                                </text>
                            </view>
                            <view class="px-[20rpx] py-[8rpx] bg-primary rounded-full">
                                <text class="text-white text-xs">查看详情</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <!-- 大卡片样式 -->
        <view v-if="content.style == 3" class="package-grid space-y-[20rpx]">
            <view
                v-for="(item, index) in showList"
                :key="index"
                class="package-card bg-white rounded-xl overflow-hidden shadow-sm"
                @click="handleClick(item.link)"
            >
                <!-- 封面图 -->
                <view class="relative">
                    <u-image
                        width="100%"
                        height="360rpx"
                        :src="getImageUrl(item.image)"
                        mode="aspectFill"
                    />
                    <!-- 热门/推荐标签 -->
                    <view 
                        v-if="item.tag" 
                        class="absolute top-[20rpx] left-[20rpx] px-[20rpx] py-[8rpx] bg-gradient-to-r from-primary to-primary/80 rounded-full"
                    >
                        <text class="text-white text-sm">{{ item.tag }}</text>
                    </view>
                    <!-- 价格悬浮 -->
                    <view class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-[24rpx]">
                        <view class="flex items-baseline">
                            <text class="text-sm text-white/80">¥</text>
                            <text class="text-2xl font-bold text-white">{{ item.price }}</text>
                            <text class="text-sm text-white/60 ml-[8rpx]">起</text>
                        </view>
                    </view>
                </view>
                <!-- 信息 -->
                <view class="p-[24rpx]">
                    <text class="text-lg font-medium text-gray-900">{{ item.name }}</text>
                    <text v-if="item.desc" class="text-sm text-gray-500 mt-[8rpx] line-clamp-2">{{ item.desc }}</text>
                    <!-- 服务项 -->
                    <view v-if="item.services && item.services.length" class="mt-[16rpx]">
                        <view class="flex flex-wrap gap-[12rpx]">
                            <view
                                v-for="(service, sIndex) in item.services.slice(0, 6)"
                                :key="sIndex"
                                class="px-[16rpx] py-[6rpx] bg-gray-100 rounded-full text-xs text-gray-600"
                            >
                                {{ service }}
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

// 点击套餐卡片
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
.service-packages {
    .package-card {
        transition: transform 0.2s ease;
        &:active {
            transform: scale(0.98);
        }
    }
    
    .package-scroll {
        margin: 0 -20rpx;
        padding: 0 20rpx;
        white-space: nowrap;
    }
}
</style>
