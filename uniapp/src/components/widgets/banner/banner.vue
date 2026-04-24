<template>
    <view class="banner translate-y-0" v-if="visibleData.length && content.enabled">
        <LSwiper
            :content="content"
            :height="bannerHeight"
            :circular="true"
            :effect3d="false"
            :border-radius="borderRadius"
            :interval="interval"
            :indicator-color="indicatorColor"
            :indicator-active-color="indicatorActiveColor"
            bg-color="transparent"
            @change="handleChange"
        ></LSwiper>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import LSwiper from '@/components/l-swiper/l-swiper.vue'
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import { alphaColor } from '@/utils/color'

const emit = defineEmits(['change'])
const props = defineProps({
    content: {
        type: Object,
        default: () => ({
            data: [],
            enabled: true,
            height: 320 // 默认高度320rpx
        })
    },
    styles: {
        type: Object,
        default: () => ({
            height: 320, // 默认高度320rpx
            borderRadius: 16, // 默认圆角16rpx
            interval: 7000 // 默认切换间隔7秒
        })
    },
    isLargeScreen: {
        type: Boolean
    }
})

const { getImageUrl } = useAppStore()
const themeStore = useThemeStore()

// 过滤可见的 banner 数据
const visibleData = computed(() => {
    return (props.content.data || []).filter((item: any) => item.is_show !== '0')
})

// 计算轮播图高度：优先使用样式配置，其次使用内容配置，最后使用默认值
const bannerHeight = computed(() => {
    if (props.styles.height) {
        return String(props.styles.height)
    }
    if (props.content.height) {
        return String(props.content.height)
    }
    // 使用默认高度：大屏模式1100rpx，常规模式320rpx
    return props.isLargeScreen ? '1100' : '320'
})

// 圆角配置（常规模式下无圆角，大屏模式也无圆角）
const borderRadius = computed(() => {
    return '0'
})

// 切换间隔
const interval = computed(() => props.styles.interval || 7000)

// 指示器颜色（使用紫色圆点）
const indicatorColor = computed(() => alphaColor(themeStore.primaryColor || '#0B0B0B', 0.16))
const indicatorActiveColor = computed(() => themeStore.primaryColor || '#0B0B0B')

const handleChange = (index: number) => {
    const item = visibleData.value[index]
    if (item?.bg) {
        emit('change', getImageUrl(item.bg))
    }
}
</script>

<style scoped lang="scss">
.banner {
    margin: 20rpx 20rpx 0;
    overflow: hidden;
    border-radius: 30rpx;
    box-shadow: var(--cinema-shadow-strong, 0 24rpx 60rpx rgba(11, 11, 11, 0.18));
}
</style>
