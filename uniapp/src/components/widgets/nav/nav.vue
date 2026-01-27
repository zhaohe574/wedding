<template>
    <div class="relative mx-md mt-md">
        <swiper
            class="py-md bg-white rounded-[16rpx]"
            :style="{
                height: navList[0].length > content.per_line ? '288rpx' : '132rpx'
            }"
            :autoplay="false"
            :indicator-dots="navList.length > 1"
            :indicator-color="indicatorColor"
            :indicator-active-color="indicatorActiveColor"
            @change="swiperChange"
        >
            <swiper-item v-for="(sItem, sIndex) in navList" :key="sIndex">
                <view class="nav" v-if="navList.length && content.enabled">
                    <view
                        class="grid grid-rows-auto gap-y-lg w-full"
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
                                <tn-icon :name="item.icon" :size="64" :color="iconColor" />
                            </view>
                            <image 
                                v-else
                                class="nav-image"
                                width="64" 
                                height="64" 
                                :src="getImageUrl(item.image)" 
                                mode="aspectFit"
                                alt="" 
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
const iconColor = computed(() => props.styles.iconColor || '#666666')

// 指示器颜色
const indicatorColor = computed(() => 'rgba(124, 58, 237, 0.3)')
const indicatorActiveColor = computed(() => themeStore.primaryColor || '#7C3AED')

watch(
    () => props.content.data,
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
    width: 64rpx;
    height: 64rpx;
    display: flex;
    align-items: center;
    justify-content: center;
}

.nav-image {
    width: 64rpx;
    height: 64rpx;
    border-radius: 8rpx;
}
</style>
