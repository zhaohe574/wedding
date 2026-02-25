<template>
    <view class="activity-zone mx-[20rpx] mt-[20rpx]" v-if="content.enabled && showList.length">
        <!-- 标题栏 -->
        <view v-if="content.title" class="flex items-center justify-between mb-[20rpx]">
            <view class="flex items-center">
                <view
                    class="title-bar w-[8rpx] h-[40rpx] rounded-full mr-[12rpx]"
                    :style="titleBarStyle"
                ></view>
                <text class="text-xl font-semibold" :style="titleTextStyle">
                    {{ content.title }}
                </text>
            </view>
            <view
                v-if="content.show_more"
                class="flex items-center text-sm hover-item"
                :style="moreLinkStyle"
                @click="handleMore"
            >
                <text>更多活动</text>
                <tn-icon name="right" size="14" :color="primaryColor" class="ml-1"></tn-icon>
            </view>
        </view>

        <!-- 大图样式 -->
        <view v-if="content.style == 1" class="activity-banner space-y-[16rpx]">
            <view
                v-for="(item, index) in showList"
                :key="index"
                class="activity-item relative rounded-3xl overflow-hidden"
                :style="cardShadowStyle"
                @click="handleClick(item)"
            >
                <image
                    class="activity-image"
                    style="width: 100%; height: 360rpx; display: block"
                    :src="getImageUrl(item.image)"
                    mode="aspectFill"
                />
                <!-- 活动信息遮罩 -->
                <view class="activity-overlay">
                    <!-- 标签 -->
                    <view
                        v-if="item.tag && item.tag !== ''"
                        class="activity-tag"
                        :style="tagBadgeStyle"
                    >
                        <text class="text-white text-xs font-medium">{{ item.tag }}</text>
                    </view>

                    <text class="text-white text-lg font-bold line-clamp-1 mb-[10rpx]">{{
                        item.title
                    }}</text>
                    <view class="flex items-center">
                        <view
                            class="cta-button px-[24rpx] py-[12rpx] rounded-full"
                            style="
                                background: rgba(255, 255, 255, 0.25);
                                backdrop-filter: blur(10rpx);
                            "
                        >
                            <text class="text-white text-sm font-semibold">立即参与</text>
                        </view>
                        <view v-if="item.price" class="flex items-baseline ml-[16rpx]">
                            <text class="text-sm text-white/80">¥</text>
                            <text class="text-2xl font-bold text-white">{{ item.price }}</text>
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
                class="activity-item bg-white rounded-3xl overflow-hidden"
                :style="gridShadowStyle"
                @click="handleClick(item)"
            >
                <view class="relative overflow-hidden">
                    <image
                        class="activity-image"
                        style="width: 100%; height: 280rpx; display: block"
                        :src="getImageUrl(item.image)"
                        mode="aspectFill"
                    />
                    <view
                        v-if="item.tag && item.tag !== ''"
                        class="absolute top-[12rpx] left-[12rpx] px-[12rpx] py-[4rpx] rounded-full"
                        :style="tagBadgeStyle"
                    >
                        <text class="text-white text-xs font-medium">{{ item.tag }}</text>
                    </view>
                </view>
                <view class="p-[20rpx]">
                    <text
                        class="text-base font-semibold line-clamp-1 mb-[12rpx]"
                        :style="titleTextStyle"
                    >
                        {{ item.title }}
                    </text>
                    <text
                        v-if="item.desc"
                        class="text-xs line-clamp-1 mb-[8rpx]"
                        style="color: #9ca3af"
                    >
                        {{ item.desc }}
                    </text>
                    <view v-if="item.price" class="flex items-baseline">
                        <text class="text-xs" :style="priceTextStyle">¥</text>
                        <text class="text-lg font-bold" :style="priceTextStyle">{{
                            item.price
                        }}</text>
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
            <view class="flex gap-[16rpx] px-[4rpx]">
                <view
                    v-for="(item, index) in showList"
                    :key="index"
                    class="activity-item relative rounded-3xl overflow-hidden flex-shrink-0"
                    :style="[{ width: '600rpx' }, cardShadowStyle]"
                    @click="handleClick(item)"
                >
                    <image
                        class="activity-image"
                        style="width: 600rpx; height: 320rpx; display: block"
                        :src="getImageUrl(item.image)"
                        mode="aspectFill"
                    />
                    <!-- 活动信息 -->
                    <view class="activity-overlay-scroll">
                        <view
                            v-if="item.tag && item.tag !== ''"
                            class="activity-tag"
                            :style="tagBadgeStyle"
                        >
                            <text class="text-white text-xs font-medium">{{ item.tag }}</text>
                        </view>
                        <text class="text-white text-lg font-bold line-clamp-1 mb-[10rpx]">
                            {{ item.title }}
                        </text>
                        <view class="flex items-center">
                            <view
                                class="px-[20rpx] py-[8rpx] rounded-full"
                                style="
                                    background: rgba(255, 255, 255, 0.25);
                                    backdrop-filter: blur(10rpx);
                                "
                            >
                                <text class="text-white text-xs font-medium">查看详情</text>
                            </view>
                            <view v-if="item.price" class="flex items-baseline ml-[16rpx]">
                                <text class="text-xs text-white/80">¥</text>
                                <text class="text-xl font-bold text-white">{{ item.price }}</text>
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
                class="activity-item bg-white rounded-3xl overflow-hidden"
                :style="gridShadowStyle"
                @click="handleClick(item)"
            >
                <view class="flex">
                    <view class="relative flex-shrink-0 overflow-hidden">
                        <image
                            class="activity-image"
                            style="width: 280rpx; height: 200rpx; display: block"
                            :src="getImageUrl(item.image)"
                            mode="aspectFill"
                        />
                        <view
                            v-if="item.tag && item.tag !== ''"
                            class="absolute top-[12rpx] left-[12rpx] px-[12rpx] py-[4rpx] rounded-full"
                            :style="tagBadgeStyle"
                        >
                            <text class="text-white text-xs font-medium">{{ item.tag }}</text>
                        </view>
                    </view>
                    <view class="flex-1 p-[20rpx] flex flex-col justify-between">
                        <view>
                            <text
                                class="text-base font-semibold line-clamp-1 mb-[8rpx]"
                                :style="titleTextStyle"
                            >
                                {{ item.title }}
                            </text>
                            <text
                                v-if="item.desc"
                                class="text-xs line-clamp-2"
                                style="color: #9ca3af"
                            >
                                {{ item.desc }}
                            </text>
                        </view>
                        <view class="flex items-center justify-between mt-[12rpx]">
                            <view v-if="item.price" class="flex items-baseline">
                                <text class="text-xs" :style="priceTextStyle">¥</text>
                                <text class="text-xl font-bold" :style="priceTextStyle">{{
                                    item.price
                                }}</text>
                            </view>
                            <view
                                class="cta-button px-[20rpx] py-[8rpx] rounded-full"
                                :style="ctaButtonStyle"
                            >
                                <text class="text-white text-xs font-medium">立即参与</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import { navigateTo } from '@/utils/util'
import { getDynamicList } from '@/api/dynamic'
import { alphaColor, tintColor, shadeColor } from '@/utils/color'

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
const themeStore = useThemeStore()

const primaryColor = computed(() => themeStore.primaryColor || '#7C3AED')
const ctaColor = computed(() => themeStore.ctaColor || '#F97316')

const titleBarStyle = computed(() => ({
    background: `linear-gradient(180deg, ${primaryColor.value} 0%, ${tintColor(
        primaryColor.value,
        0.35
    )} 100%)`,
    boxShadow: `0 4rpx 12rpx ${alphaColor(primaryColor.value, 0.12)}`
}))
const titleTextStyle = computed(() => ({ color: shadeColor(primaryColor.value, 0.25) }))
const moreLinkStyle = computed(() => ({ color: primaryColor.value }))
const tagBadgeStyle = computed(() => ({
    background: ctaColor.value,
    boxShadow: `0 2rpx 8rpx ${alphaColor(ctaColor.value, 0.3)}`
}))
const cardShadowStyle = computed(() => ({
    boxShadow: `0 8rpx 24rpx ${alphaColor(primaryColor.value, 0.08)}`
}))
const gridShadowStyle = computed(() => ({
    boxShadow: `0 4rpx 16rpx ${alphaColor(primaryColor.value, 0.06)}`
}))
const priceTextStyle = computed(() => ({ color: primaryColor.value }))
const ctaButtonStyle = computed(() => ({
    background: `linear-gradient(135deg, ${primaryColor.value} 0%, ${shadeColor(
        primaryColor.value,
        0.15
    )} 100%)`,
    boxShadow: `0 4rpx 12rpx ${alphaColor(primaryColor.value, 0.2)}`
}))
const highlightTextStyle = computed(() => ({ color: shadeColor(primaryColor.value, 0.2) }))

const activityList = ref<any[]>([])
const loading = ref(false)

// 加载活动数据
const loadActivities = async () => {
    try {
        loading.value = true

        // 确保有默认的data_source值
        const dataSource = props.content.data_source || 'auto'

        // 根据数据来源加载活动
        if (dataSource === 'manual' && props.content.activity_ids?.length) {
            // 手动选择模式：获取指定ID的活动
            const params: any = {
                dynamic_type: 4, // 活动类型
                page_size: 100
            }
            const res = await getDynamicList(params)
            const allActivities = res.data || []

            // 按照选择的顺序筛选活动
            activityList.value = props.content.activity_ids
                .map((id: number) => allActivities.find((item: any) => item.id === id))
                .filter(Boolean)
        } else {
            // 自动模式：获取最新活动
            const params: any = {
                dynamic_type: 4, // 活动类型
                page_size: props.content.show_count || 10
            }
            const res = await getDynamicList(params)
            activityList.value = res.data || []
        }
    } catch (error) {
        console.error('加载活动数据失败:', error)
        activityList.value = []
    } finally {
        loading.value = false
    }
}

// 监听配置变化
watch(
    () => [props.content.data_source, props.content.activity_ids, props.content.show_count],
    () => {
        loadActivities()
    },
    { immediate: true, deep: true }
)

// 过滤显示的列表
const showList = computed(() => {
    const limit = props.content.show_count || activityList.value.length
    return activityList.value.slice(0, limit).map((item: any) => {
        // 获取第一张图片作为封面
        const images = item.images || []
        const coverImage = images.length > 0 ? images[0] : ''

        // 获取第一个标签 - 处理tags字段（字符串或数组）
        let tag = ''
        if (item.tags_arr && item.tags_arr.length > 0) {
            // 优先使用tags_arr（后台返回的数组格式）
            tag = item.tags_arr[0]
        } else if (item.tags) {
            if (typeof item.tags === 'string') {
                // 如果tags是字符串，直接使用或按逗号分割取第一个
                const trimmedTags = item.tags.trim()
                if (trimmedTags) {
                    const tagsArray = trimmedTags.split(',').filter((t: string) => t.trim())
                    tag = tagsArray.length > 0 ? tagsArray[0].trim() : trimmedTags
                }
            } else if (Array.isArray(item.tags) && item.tags.length > 0) {
                // 如果tags是数组，取第一个
                tag = item.tags[0]
            }
        }

        // 处理描述文本
        let desc = ''
        if (item.content_preview) {
            desc = item.content_preview
        } else if (item.content) {
            desc = item.content.replace(/<[^>]+>/g, '').substring(0, 50)
        }

        return {
            id: item.id,
            image: coverImage || item.cover_image || '',
            title: item.title || '活动标题',
            desc: desc,
            tag: tag || '热门',
            price: item.price || '',
            original_price: item.original_price || '',
            is_show: '1',
            view_count: item.view_count || 0,
            like_count: item.like_count || 0
        }
    })
})

// 点击活动
const handleClick = (item: any) => {
    navigateTo({
        path: '/pages/dynamic_detail/dynamic_detail',
        query: { id: item.id },
        name: '活动详情'
    })
}

// 查看更多
const handleMore = () => {
    if (props.content.more_link && Object.keys(props.content.more_link).length > 0) {
        navigateTo(props.content.more_link)
    } else {
        // 默认跳转到动态列表页，筛选活动类型
        navigateTo({
            path: '/pages/dynamic/list',
            query: { type: 4 },
            name: '活动列表'
        })
    }
}
</script>

<style lang="scss" scoped>
.activity-zone {
    .activity-item {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);

        &:active {
            transform: scale(0.97);
        }
    }

    .activity-image {
        transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    // 活动信息遮罩层 - 大图样式
    .activity-overlay {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        top: 0;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 24rpx;
        background: linear-gradient(
            180deg,
            transparent 0%,
            transparent 50%,
            rgba(0, 0, 0, 0.4) 75%,
            rgba(0, 0, 0, 0.8) 100%
        );
    }

    // 活动信息遮罩层 - 横向滑动样式
    .activity-overlay-scroll {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        top: 0;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 20rpx;
        background: linear-gradient(
            180deg,
            transparent 0%,
            transparent 50%,
            rgba(0, 0, 0, 0.4) 75%,
            rgba(0, 0, 0, 0.8) 100%
        );
    }

    // 活动标签
    .activity-tag {
        position: absolute;
        top: 16rpx;
        left: 16rpx;
        padding: 6rpx 16rpx;
        border-radius: 999rpx;
        background: #f97316;
        box-shadow: 0 3rpx 10rpx rgba(249, 115, 22, 0.3);
    }

    .cta-button {
        transition: all 0.2s ease;
    }

    .activity-scroll {
        margin: 0 -20rpx;
        padding: 0 20rpx;
    }
}

/* 支持 prefers-reduced-motion */
@media (prefers-reduced-motion: reduce) {
    .activity-zone {
        .activity-item,
        .activity-image,
        .cta-button {
            transition: none !important;
            transform: none !important;
        }
    }
}
</style>
