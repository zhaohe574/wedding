<template>
    <view
        class="banner mx-[20rpx] mt-[20rpx]"
        v-if="showList.length && content.enabled"
    >
        <swiper
            class="swiper"
            style="height: 260rpx;"
            :indicator-dots="showList.length > 1"
            :indicator-color="indicatorColor"
            :indicator-active-color="indicatorActiveColor"
            :autoplay="true"
        >
            <swiper-item
                v-for="(item, index) in showList"
                :key="index"
                @click="handleClick(item.link)"
            >
                <image
                    class="banner-image"
                    mode="aspectFill"
                    :src="getImageUrl(item.image)"
                />
            </swiper-item>
        </swiper>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import { navigateTo } from '@/utils/util'
import { alphaColor } from '@/utils/color'

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
const handleClick = (link: any) => {
    navigateTo(link)
}
const { getImageUrl } = useAppStore()
const themeStore = useThemeStore()

const showList = computed(() => {
    return props.content.data?.filter((item: any) => item.is_show == '1') || []
})

const indicatorColor = computed(() => alphaColor(themeStore.primaryColor || '#7C3AED', 0.16))
const indicatorActiveColor = computed(() => themeStore.primaryColor || '#7C3AED')
</script>

<style scoped lang="scss">
.banner-image {
    width: 100%;
    height: 100%;
    border-radius: 14rpx;
}
</style>
