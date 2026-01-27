<template>
    <view class="customer-reviews mx-[20rpx] mt-[20rpx]" v-if="content.enabled && showList.length">
        <!-- 标题 -->
        <view v-if="content.title" class="flex items-center mb-[24rpx]">
            <view class="title-bar w-[8rpx] h-[34rpx] bg-primary rounded-full mr-[16rpx]"></view>
            <text class="text-lg font-medium text-gray-900">{{ content.title }}</text>
            <!-- 统计信息 -->
            <view v-if="content.show_stats" class="ml-auto flex items-center">
                <view class="flex items-center mr-[24rpx]">
                    <tn-icon name="star-fill" size="16" color="#f59e0b"></tn-icon>
                    <text class="text-base font-bold text-amber-500 ml-[8rpx]">{{
                        avgRating
                    }}</text>
                </view>
                <text class="text-sm text-gray-500">{{ showList.length }}条评价</text>
            </view>
        </view>

        <!-- 卡片样式 -->
        <view v-if="content.style == 1" class="review-cards space-y-[20rpx]">
            <view
                v-for="(item, index) in showList"
                :key="index"
                class="review-card bg-white rounded-xl p-[24rpx] shadow-sm"
            >
                <!-- 用户信息 -->
                <view class="flex items-center mb-[16rpx]">
                    <image
                        width="80rpx"
                        height="80rpx"
                        :src="getImageUrl(item.avatar) || defaultAvatar"
                        border-radius="40rpx"
                        mode="aspectFill"
                    />
                    <view class="ml-[16rpx] flex-1">
                        <text class="text-base font-medium text-gray-900">{{ item.name }}</text>
                        <view class="flex items-center mt-[4rpx]">
                            <view class="flex items-center">
                                <tn-icon
                                    v-for="star in 5"
                                    :key="star"
                                    :name="star <= item.rating ? 'star-fill' : 'star'"
                                    size="14"
                                    :color="star <= item.rating ? '#f59e0b' : '#e5e7eb'"
                                ></tn-icon>
                            </view>
                            <text class="text-xs text-gray-400 ml-[12rpx]">{{ item.date }}</text>
                        </view>
                    </view>
                    <!-- 标签 -->
                    <view v-if="item.tag" class="px-[16rpx] py-[6rpx] bg-primary/10 rounded-full">
                        <text class="text-xs text-primary">{{ item.tag }}</text>
                    </view>
                </view>

                <!-- 评价内容 -->
                <text class="text-sm text-gray-600 leading-relaxed">{{ item.content }}</text>

                <!-- 评价图片 -->
                <view
                    v-if="item.images && item.images.length"
                    class="flex flex-wrap gap-[12rpx] mt-[16rpx]"
                >
                    <view
                        v-for="(img, imgIndex) in item.images.slice(0, 4)"
                        :key="imgIndex"
                        class="relative"
                    >
                        <image
                            width="160rpx"
                            height="160rpx"
                            :src="getImageUrl(img)"
                            border-radius="8rpx"
                            mode="aspectFill"
                            @click="previewImage(item.images, imgIndex)"
                        />
                        <view
                            v-if="imgIndex === 3 && item.images.length > 4"
                            class="absolute inset-0 bg-black/50 rounded-lg flex items-center justify-center"
                        >
                            <text class="text-white text-sm">+{{ item.images.length - 4 }}</text>
                        </view>
                    </view>
                </view>

                <!-- 服务项目 -->
                <view v-if="item.service" class="mt-[16rpx] pt-[16rpx] border-t border-gray-100">
                    <text class="text-xs text-gray-400">服务项目：{{ item.service }}</text>
                </view>
            </view>
        </view>

        <!-- 横向滑动样式 -->
        <scroll-view
            v-if="content.style == 2"
            scroll-x
            class="review-scroll"
            :show-scrollbar="false"
        >
            <view class="flex gap-[16rpx]">
                <view
                    v-for="(item, index) in showList"
                    :key="index"
                    class="review-card bg-white rounded-xl p-[24rpx] shadow-sm flex-shrink-0"
                    style="width: 560rpx"
                >
                    <!-- 用户信息 -->
                    <view class="flex items-center mb-[16rpx]">
                        <image
                            width="72rpx"
                            height="72rpx"
                            :src="getImageUrl(item.avatar) || defaultAvatar"
                            border-radius="36rpx"
                            mode="aspectFill"
                        />
                        <view class="ml-[12rpx] flex-1">
                            <text class="text-sm font-medium text-gray-900">{{ item.name }}</text>
                            <view class="flex items-center mt-[4rpx]">
                                <tn-icon
                                    v-for="star in 5"
                                    :key="star"
                                    :name="star <= item.rating ? 'star-fill' : 'star'"
                                    size="12"
                                    :color="star <= item.rating ? '#f59e0b' : '#e5e7eb'"
                                ></tn-icon>
                            </view>
                        </view>
                    </view>

                    <!-- 评价内容 -->
                    <text class="text-sm text-gray-600 line-clamp-3">{{ item.content }}</text>

                    <!-- 评价图片 -->
                    <view
                        v-if="item.images && item.images.length"
                        class="flex gap-[8rpx] mt-[12rpx]"
                    >
                        <image
                            v-for="(img, imgIndex) in item.images.slice(0, 3)"
                            :key="imgIndex"
                            width="100rpx"
                            height="100rpx"
                            :src="getImageUrl(img)"
                            border-radius="6rpx"
                            mode="aspectFill"
                        />
                    </view>
                </view>
            </view>
        </scroll-view>

        <!-- 简洁列表样式 -->
        <view v-if="content.style == 3" class="review-list bg-white rounded-xl overflow-hidden">
            <view
                v-for="(item, index) in showList"
                :key="index"
                class="review-item p-[24rpx]"
                :class="{ 'border-b border-gray-100': index < showList.length - 1 }"
            >
                <view class="flex items-start">
                    <image
                        width="64rpx"
                        height="64rpx"
                        :src="getImageUrl(item.avatar) || defaultAvatar"
                        border-radius="32rpx"
                        mode="aspectFill"
                    />
                    <view class="ml-[16rpx] flex-1">
                        <view class="flex items-center justify-between">
                            <text class="text-sm font-medium text-gray-900">{{ item.name }}</text>
                            <view class="flex items-center">
                                <tn-icon name="star-fill" size="14" color="#f59e0b"></tn-icon>
                                <text class="text-sm text-amber-500 ml-[4rpx]">{{
                                    item.rating
                                }}</text>
                            </view>
                        </view>
                        <text class="text-sm text-gray-600 mt-[8rpx] line-clamp-2">{{
                            item.content
                        }}</text>
                        <text class="text-xs text-gray-400 mt-[8rpx]">{{ item.date }}</text>
                    </view>
                </view>
            </view>
        </view>

        <!-- 查看更多 -->
        <view
            v-if="content.show_more"
            class="flex items-center justify-center mt-[24rpx] py-[20rpx] bg-white rounded-xl"
            @click="handleMore"
        >
            <text class="text-sm text-primary">查看全部评价</text>
            <tn-icon name="right" size="14" color="#7c3aed" class="ml-[8rpx]"></tn-icon>
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
const defaultAvatar = '/static/images/default-avatar.png'

// 过滤显示的列表
const showList = computed(() => {
    const data = props.content.data?.filter((item: any) => item.is_show !== '0') || []
    const limit = props.content.show_count || data.length
    return data.slice(0, limit)
})

// 平均评分
const avgRating = computed(() => {
    if (!showList.value.length) return '5.0'
    const total = showList.value.reduce((sum: number, item: any) => sum + (item.rating || 5), 0)
    return (total / showList.value.length).toFixed(1)
})

// 预览图片
const previewImage = (images: string[], current: number) => {
    uni.previewImage({
        urls: images.map((img: string) => getImageUrl(img)),
        current
    })
}

// 查看更多
const handleMore = () => {
    if (props.content.more_link && Object.keys(props.content.more_link).length > 0) {
        navigateTo(props.content.more_link)
    }
}
</script>

<style lang="scss" scoped>
.customer-reviews {
    .review-card {
        transition: transform 0.2s ease;
        &:active {
            transform: scale(0.99);
        }
    }

    .review-scroll {
        margin: 0 -20rpx;
        padding: 0 20rpx;
    }
}
</style>
