<template>
    <div class="relative mx-md mt-md">
        <swiper
            class="bg-white rounded-[16rpx]"
            :style="{
                height: swiperHeight
            }"
            :autoplay="false"
            :indicator-dots="navList.length > 1"
            :indicator-color="indicatorColor"
            :indicator-active-color="indicatorActiveColor"
            @change="swiperChange"
        >
            <swiper-item v-for="(sItem, sIndex) in navList" :key="sIndex">
                <view class="nav px-xs pt-md pb-sm" v-if="navList.length && content.enabled">
                    <view
                        class="grid grid-rows-auto gap-y-md w-full"
                        :style="{ 'grid-template-columns': `repeat(${perLine}, 1fr)` }"
                    >
                        <view
                            v-for="(item, index) in sItem"
                            :key="index"
                            class="flex flex-col items-center transition-all duration-200"
                            @click="handleClick(item.link)"
                        >
                            <!-- 使用图鸟UI图标或图片 -->
                            <view v-if="item.icon" class="nav-icon-wrapper">
                                <tn-icon :name="item.icon" :size="88" :color="iconColor" />
                            </view>
                            <image
                                v-else
                                class="nav-image"
                                :src="getImageUrl(item.image)"
                                mode="aspectFit"
                            />
                            <view class="mt-sm text-[26rpx] text-center text-content">
                                {{ item.name }}
                            </view>
                        </view>
                    </view>
                </view>
            </swiper-item>
        </swiper>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import { navigateTo, sliceArray } from '@/utils/util'
import { alphaColor } from '@/utils/color'

const props = defineProps({
    content: {
        type: Object,
        default: () => ({
            data: [],
            enabled: true,
            per_line: 4, // 每行显示数量，支持4列或5列
            show_line: 2, // 显示行数
            style: 1 // 样式类型
        })
    },
    styles: {
        type: Object,
        default: () => ({
            iconColor: '#666666' // 图标颜色
        })
    }
})

const { getImageUrl } = useAppStore()
const themeStore = useThemeStore()

const swiperCurrent = ref<number>(0)
const navList = ref<Record<string, any>>([])

// 每行显示数量
const perLine = computed(() => props.content.per_line || 4)

// 每页显示数量
const pagesNum = computed<number>(() => {
    return perLine.value * (props.content.show_line || 2)
})

// 图标颜色
const iconColor = computed(() => props.styles.iconColor || themeStore.primaryColor || '#666666')

// swiper 高度计算（padding 在内部 view 上：pt-md=24 + pb-sm=16 = 40rpx）
// 每行：图标88 + mt-sm(16) + 文字(40) = 144rpx，行间距 gap-y-md = 24rpx
const swiperHeight = computed(() => {
    if (!navList.value.length || !navList.value[0]?.length) return '200rpx'
    const rows = navList.value[0].length > perLine.value ? 2 : 1
    const rowHeight = 144 // 图标88 + mt-sm(16) + 文字(40)
    const paddingTop = 24 // pt-md
    const paddingBottom = 16 // pb-sm
    const gap = rows > 1 ? 24 : 0 // gap-y-md
    const indicator = navList.value.length > 1 ? 40 : 0 // 指示器点高度+间距
    return `${rows * rowHeight + paddingTop + paddingBottom + gap + indicator}rpx`
})

// 指示器颜色
const indicatorColor = computed(() => alphaColor(themeStore.primaryColor || '#7C3AED', 0.16))
const indicatorActiveColor = computed(() => themeStore.primaryColor || '#7C3AED')

// 过滤后的可见数据（与admin端预览逻辑保持一致）
const visibleData = computed(() => {
    return (props.content.data || []).filter((item: any) => item.is_show !== '0')
})

watch(
    visibleData,
    (val) => {
        const num = props.content.style === 1 ? val.length : pagesNum.value
        navList.value = sliceArray(val, num)
    },
    { deep: true, immediate: true }
)

const handleClick = (link: any) => {
    navigateTo(link)
}

const swiperChange = (e: any) => {
    swiperCurrent.value = e.detail.current
}
</script>

<style scoped lang="scss">
.nav-icon-wrapper {
    width: 88rpx;
    height: 88rpx;
    display: flex;
    align-items: center;
    justify-content: center;
}

.nav-image {
    width: 88rpx;
    height: 88rpx;
    border-radius: 8rpx;
}
</style>
