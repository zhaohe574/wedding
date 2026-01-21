<template>
    <view class="staff-showcase mx-[20rpx] mt-[20rpx]" v-if="content.enabled && showList.length">
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

        <!-- 卡片样式 -->
        <view v-if="content.style == 1" class="staff-grid">
            <view
                class="grid gap-[20rpx]"
                :style="{ 'grid-template-columns': `repeat(${content.per_line || 2}, 1fr)` }"
            >
                <view
                    v-for="(item, index) in showList"
                    :key="index"
                    class="staff-card bg-white rounded-xl overflow-hidden shadow-sm"
                    @click="handleClick(item.link)"
                >
                    <!-- 头像 -->
                    <view class="relative">
                        <u-image
                            width="100%"
                            height="280rpx"
                            :src="getImageUrl(item.avatar)"
                            mode="aspectFill"
                        />
                        <!-- 角色标签 -->
                        <view class="absolute top-[16rpx] left-[16rpx] px-[16rpx] py-[6rpx] bg-primary/90 rounded-full">
                            <text class="text-white text-xs">{{ item.role || '服务人员' }}</text>
                        </view>
                    </view>
                    <!-- 信息 -->
                    <view class="p-[20rpx]">
                        <view class="flex items-center justify-between mb-[12rpx]">
                            <text class="text-base font-medium text-gray-900 truncate flex-1">{{ item.name }}</text>
                        </view>
                        <!-- 评分和订单数 -->
                        <view class="flex items-center mb-[12rpx]">
                            <view class="flex items-center">
                                <u-icon name="star-fill" size="14" color="#f59e0b"></u-icon>
                                <text class="text-sm text-amber-500 ml-1">{{ item.rating || '5.0' }}</text>
                            </view>
                            <text class="text-xs text-gray-400 ml-[20rpx]">{{ item.order_count || 0 }}单</text>
                        </view>
                        <!-- 标签 -->
                        <view v-if="item.tags && item.tags.length" class="flex flex-wrap gap-[8rpx]">
                            <view
                                v-for="(tag, tagIndex) in item.tags.slice(0, 3)"
                                :key="tagIndex"
                                class="px-[12rpx] py-[4rpx] bg-primary/10 rounded text-xs text-primary"
                            >
                                {{ tag }}
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <!-- 列表样式 -->
        <view v-if="content.style == 2" class="staff-list bg-white rounded-xl overflow-hidden">
            <view
                v-for="(item, index) in showList"
                :key="index"
                class="staff-item flex items-center p-[24rpx]"
                :class="{ 'border-b border-gray-100': index < showList.length - 1 }"
                @click="handleClick(item.link)"
            >
                <!-- 头像 -->
                <view class="relative flex-shrink-0">
                    <u-image
                        width="120rpx"
                        height="120rpx"
                        :src="getImageUrl(item.avatar)"
                        border-radius="60rpx"
                        mode="aspectFill"
                    />
                </view>
                <!-- 信息 -->
                <view class="flex-1 ml-[24rpx] overflow-hidden">
                    <view class="flex items-center mb-[8rpx]">
                        <text class="text-base font-medium text-gray-900 truncate">{{ item.name }}</text>
                        <view class="ml-[12rpx] px-[12rpx] py-[4rpx] bg-primary/10 rounded">
                            <text class="text-xs text-primary">{{ item.role || '服务人员' }}</text>
                        </view>
                    </view>
                    <!-- 评分和订单数 -->
                    <view class="flex items-center mb-[8rpx]">
                        <view class="flex items-center">
                            <u-icon name="star-fill" size="14" color="#f59e0b"></u-icon>
                            <text class="text-sm text-amber-500 ml-1">{{ item.rating || '5.0' }}</text>
                        </view>
                        <text class="text-xs text-gray-400 ml-[20rpx]">已服务{{ item.order_count || 0 }}单</text>
                    </view>
                    <!-- 标签 -->
                    <view v-if="item.tags && item.tags.length" class="flex flex-wrap gap-[8rpx]">
                        <text
                            v-for="(tag, tagIndex) in item.tags.slice(0, 3)"
                            :key="tagIndex"
                            class="text-xs text-gray-500"
                        >
                            {{ tag }}{{ tagIndex < Math.min(item.tags.length, 3) - 1 ? ' · ' : '' }}
                        </text>
                    </view>
                </view>
                <!-- 箭头 -->
                <view class="flex-shrink-0 ml-[16rpx]">
                    <u-icon name="arrow-right" size="16" color="#9ca3af"></u-icon>
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

// 点击人员卡片
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
.staff-showcase {
    .staff-card {
        transition: transform 0.2s ease;
        &:active {
            transform: scale(0.98);
        }
    }
    
    .staff-item {
        transition: background-color 0.2s ease;
        &:active {
            background-color: #f9fafb;
        }
    }
}
</style>
