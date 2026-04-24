<template>
    <view class="staff-banner-wrapper">
        <!-- 轮播图容器 -->
        <view
            class="banner-container"
            :class="{ 'is-expanded': isExpanded }"
            :style="containerStyle"
        >
            <swiper
                v-if="bannerList.length"
                class="banner-swiper"
                :indicator-dots="showIndicator"
                :indicator-color="indicatorColor"
                :indicator-active-color="indicatorActiveColor"
                :autoplay="config.banner_autoplay === 1 && !isVideoPlaying"
                :interval="config.banner_interval || 3000"
                :circular="true"
                :current="currentIndex"
                @change="onSwiperChange"
            >
                <swiper-item v-for="(item, index) in bannerList" :key="item.id">
                    <!-- 图片 -->
                    <view v-if="item.type === 1" class="media-container">
                        <image
                            :src="resolveBannerImageSrc(index, 'image', item.file_url)"
                            mode="aspectFill"
                            class="banner-media"
                            @click="handleMediaClick(index)"
                            lazy-load
                            @error="handleBannerImageError(index, 'image', item.file_url, $event)"
                        />
                    </view>

                    <!-- 视频 -->
                    <view
                        v-else
                        class="media-container video-wrapper"
                        @click="handleMediaClick(index)"
                    >
                        <video
                            v-if="currentIndex === index && !failedVideoMap[index]"
                            :id="`video-${index}`"
                            :src="item.file_url"
                            :poster="resolveBannerImageSrc(index, 'poster', item.cover_url)"
                            :autoplay="item.is_autoplay === 1"
                            :controls="true"
                            :show-center-play-btn="true"
                            :enable-progress-gesture="true"
                            class="banner-video"
                            object-fit="cover"
                            @play="handleVideoPlay"
                            @ended="handleVideoEnded"
                            @pause="handleVideoPause"
                            @error="handleBannerVideoError(index, item.file_url, $event)"
                        />
                        <image
                            v-else
                            :src="resolveBannerImageSrc(index, 'poster', item.cover_url)"
                            mode="aspectFill"
                            class="banner-media"
                            @error="handleBannerImageError(index, 'poster', item.cover_url, $event)"
                        />
                        <!-- 播放按钮遮罩 -->
                        <view
                            v-if="currentIndex !== index || item.is_autoplay !== 1"
                            class="play-overlay"
                        >
                            <view class="play-icon">
                                <tn-icon
                                    name="play-circle-fill"
                                    size="80"
                                    color="rgba(255, 255, 255, 0.9)"
                                />
                            </view>
                        </view>
                    </view>
                </swiper-item>
            </swiper>

            <!-- 默认图片（无轮播图时） -->
            <image
                v-else-if="defaultImage"
                :src="defaultImage"
                mode="aspectFill"
                class="banner-media default-image"
                @error="handleBannerImageError(-1, 'image', defaultImage, $event)"
            />

            <!-- 自定义指示器 -->
            <view
                v-if="bannerList.length > 1 && config.banner_indicator_style === 2"
                class="custom-indicator"
            >
                <text class="indicator-text">{{ currentIndex + 1 }}/{{ bannerList.length }}</text>
            </view>

            <!-- 进度条指示器 -->
            <view
                v-if="bannerList.length > 1 && config.banner_indicator_style === 3"
                class="progress-indicator"
            >
                <view class="progress-bar" :style="{ width: progressWidth }"></view>
            </view>

            <!-- 长条形指示器 -->
            <view v-if="showCapsuleIndicator" class="capsule-indicator">
                <view
                    v-for="(_, index) in bannerList"
                    :key="`capsule-${index}`"
                    class="capsule-indicator__item"
                    :class="{ 'capsule-indicator__item--active': currentIndex === index }"
                ></view>
            </view>

            <!-- 小图模式：底部渐变遮罩（仅视觉，不拦截点击） -->
            <view
                v-if="config.banner_mode === 1 && !isExpanded && bannerList.length"
                class="bottom-gradient"
            ></view>

            <!-- 小图模式：顶部悬浮入口，避免遮挡下方信息卡 -->
            <view
                v-if="config.banner_mode === 1 && !isExpanded && bannerList.length"
                class="expand-chip"
                @click.stop="toggleExpand"
            >
                <tn-icon name="arrow-down" size="24" color="#FFFFFF" />
                <text class="chip-text">查看完整图</text>
            </view>

            <!-- 展开/收起按钮（小图模式已展开时） -->
            <view
                v-if="config.banner_mode === 1 && isExpanded && bannerList.length"
                class="collapse-chip"
                @click.stop="toggleExpand"
            >
                <tn-icon name="up-arrow" size="32" color="#FFFFFF" />
                <text class="chip-text">收起</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useThemeStore } from '@/stores/theme'
import { isDevMode } from '@/utils/env'

interface BannerItem {
    id: number
    type: number // 1=图片 2=视频
    file_url: string
    cover_url: string
    is_autoplay: number
    sort: number
}

interface BannerConfig {
    banner_mode: number // 1=小图 2=大图
    banner_small_height: number
    banner_large_height: number
    banner_indicator_style: number // 0=无 1=圆点 2=数字 3=进度条 4=长条形
    banner_autoplay: number
    banner_interval: number
}

interface Props {
    bannerList: BannerItem[]
    config: BannerConfig
    defaultImage?: string
}

const props = withDefaults(defineProps<Props>(), {
    bannerList: () => [],
    config: () => ({
        banner_mode: 1,
        banner_small_height: 400,
        banner_large_height: 600,
        banner_indicator_style: 1,
        banner_autoplay: 1,
        banner_interval: 3000
    }),
    defaultImage: ''
})

const emit = defineEmits(['update:expanded'])

const $theme = useThemeStore()
const isExpanded = ref(false)
const currentIndex = ref(0)
const isVideoPlaying = ref(false) // 视频播放状态
const failedImageMap = ref<Record<string, string>>({})
const failedVideoMap = ref<Record<number, boolean>>({})
const bannerFallbackImage = '/static/images/user/default_avatar.png'

// 容器样式
const containerStyle = computed(() => {
    const mode = props.config.banner_mode
    const smallHeight = props.config.banner_small_height || 400
    const largeHeight = props.config.banner_large_height || 600

    let height = largeHeight
    if (mode === 1 && !isExpanded.value) {
        height = smallHeight
    }

    return {
        height: `${height}rpx`
    }
})

// 是否显示指示器
const showIndicator = computed(() => {
    return props.config.banner_indicator_style === 1 && props.bannerList.length > 1
})

const showCapsuleIndicator = computed(() => {
    return props.config.banner_indicator_style === 4 && props.bannerList.length > 1
})

// 指示器颜色
const indicatorColor = computed(() => 'rgba(255, 255, 255, 0.5)')
const indicatorActiveColor = computed(() => $theme.primaryColor)

// 进度条宽度
const progressWidth = computed(() => {
    if (props.bannerList.length === 0) return '0%'
    return `${((currentIndex.value + 1) / props.bannerList.length) * 100}%`
})

const getMediaKey = (index: number, type: 'image' | 'poster') => `${type}-${index}`

const resolveBannerImageSrc = (index: number, type: 'image' | 'poster', src = '') => {
    const mediaKey = getMediaKey(index, type)
    return failedImageMap.value[mediaKey] || src || props.defaultImage || bannerFallbackImage
}

const logResourceError = (section: string, src: string, error: any) => {
    if (!isDevMode()) {
        return
    }

    console.warn('人员详情资源加载失败', {
        section,
        src,
        error: error?.detail || error || null
    })
}

const handleBannerImageError = (
    index: number,
    type: 'image' | 'poster',
    src: string,
    error: any
) => {
    logResourceError(`staff-banner:${type}`, src, error)
    const fallback = props.defaultImage || bannerFallbackImage
    const mediaKey = getMediaKey(index, type)
    if (!src || src === fallback || failedImageMap.value[mediaKey] === fallback) {
        return
    }

    failedImageMap.value[mediaKey] = fallback
}

const handleBannerVideoError = (index: number, src: string, error: any) => {
    logResourceError('staff-banner:video', src, error)
    failedVideoMap.value[index] = true
    isVideoPlaying.value = false
}

// 切换展开/收起
const toggleExpand = () => {
    isExpanded.value = !isExpanded.value
    emit('update:expanded', isExpanded.value)
}

// 轮播图切换
const onSwiperChange = (e: any) => {
    currentIndex.value = e.detail.current
}

// 点击媒体
const handleMediaClick = (index: number) => {
    if (props.config.banner_mode === 1 && !isExpanded.value) {
        // 小图模式未展开时，点击展开
        isExpanded.value = true
        emit('update:expanded', isExpanded.value)
    } else {
        // 已展开或大图模式，预览图片
        if (props.bannerList[index].type === 1) {
            const images = props.bannerList
                .filter((item) => item.type === 1)
                .map((item) => item.file_url)
            const current = images.indexOf(props.bannerList[index].file_url)

            uni.previewImage({
                urls: images,
                current: current >= 0 ? current : 0
            })
        }
    }
}

// 监听轮播图列表变化，重置状态
watch(
    () => props.bannerList,
    () => {
        currentIndex.value = 0
        isExpanded.value = false
        failedImageMap.value = {}
        failedVideoMap.value = {}
    },
    { deep: true }
)

// 视频播放事件处理
const handleVideoPlay = () => {
    isVideoPlaying.value = true
}

// 视频结束事件处理
const handleVideoEnded = () => {
    isVideoPlaying.value = false
}

// 视频暂停事件处理
const handleVideoPause = () => {
    isVideoPlaying.value = false
}
</script>

<style lang="scss" scoped>
.staff-banner-wrapper {
    width: 100%;
    position: relative;
}

.banner-container {
    width: 100%;
    position: relative;
    overflow: hidden;
    background-color: #F8F7F2;
    transition: height 0.3s ease;
}

.banner-swiper {
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
}

.media-container {
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
}

.banner-media {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
    position: absolute;
    top: 0;
    left: 0;
}

.default-image {
    position: relative;
}

.video-wrapper {
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
}

.banner-video {
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
}

.play-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(0, 0, 0, 0.3);
    z-index: 1;
}

.play-icon {
    display: flex;
    align-items: center;
    justify-content: center;
}

.custom-indicator {
    position: absolute;
    bottom: 20rpx;
    right: 20rpx;
    padding: 8rpx 16rpx;
    background-color: rgba(0, 0, 0, 0.5);
    border-radius: 20rpx;
    z-index: 12;
}

.indicator-text {
    font-size: 24rpx;
    color: #ffffff;
}

.progress-indicator {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 6rpx;
    background-color: rgba(255, 255, 255, 0.3);
    z-index: 12;
}

.progress-bar {
    height: 100%;
    background-color: #ffffff;
    transition: width 0.3s ease;
}

.capsule-indicator {
    position: absolute;
    left: 50%;
    bottom: 24rpx;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 8rpx;
    z-index: 12;
}

.capsule-indicator__item {
    width: 20rpx;
    height: 6rpx;
    border-radius: 999rpx;
    background-color: rgba(255, 255, 255, 0.36);
    transition: width 0.25s ease, background-color 0.25s ease;
}

.capsule-indicator__item--active {
    width: 44rpx;
    background-color: rgba(255, 255, 255, 0.96);
}

/* 小图模式：底部渐变遮罩（仅视觉层） */
.bottom-gradient {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 120rpx;
    background: linear-gradient(
        to top,
        rgba(0, 0, 0, 0.7) 0%,
        rgba(0, 0, 0, 0.4) 50%,
        transparent 100%
    );
    display: flex;
    z-index: 8;
    pointer-events: none;
}

.expand-chip,
.collapse-chip {
    position: absolute;
    top: 24rpx;
    right: 24rpx;
    height: 56rpx;
    padding: 0 20rpx;
    border-radius: 28rpx;
    background-color: rgba(17, 17, 17, 0.52);
    backdrop-filter: blur(8rpx);
    display: flex;
    align-items: center;
    gap: 8rpx;
    justify-content: center;
    z-index: 20;
    border: 1rpx solid rgba(255, 255, 255, 0.26);

    &:active {
        transform: scale(0.96);
        background-color: rgba(17, 17, 17, 0.72);
    }
}

.chip-text {
    font-size: 22rpx;
    color: #ffffff;
    font-weight: 500;
}
</style>
