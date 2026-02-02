<template>
    <view
        class="user-banner mx-[24rpx] mt-[24rpx]"
        v-if="showList.length && content.enabled"
    >
        <swiper
            class="banner-swiper"
            :indicator-dots="showList.length > 1"
            :indicator-color="$theme.primaryColor + '30'"
            :indicator-active-color="$theme.primaryColor"
            :autoplay="true"
            :circular="true"
            :interval="5000"
            :duration="300"
        >
            <swiper-item
                v-for="(item, index) in showList"
                :key="index"
                @click="handleClick(item.link)"
            >
                <view class="banner-item">
                    <image
                        class="banner-image"
                        mode="aspectFill"
                        :src="getImageUrl(item.image)"
                    />
                </view>
            </swiper-item>
        </swiper>
    </view>
</template>

<script setup lang="ts">
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import { navigateTo } from '@/utils/util'
import { computed } from 'vue'

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
const $theme = useThemeStore()

const handleClick = (link: any) => {
    navigateTo(link)
}

const showList = computed(() => {
    return props.content.data?.filter((item: any) => item.is_show == '1') || []
})
</script>

<style lang="scss" scoped>
.user-banner {
    .banner-swiper {
        height: 200rpx;
        border-radius: 16rpx;
        overflow: hidden;
    }
    
    .banner-item {
        width: 100%;
        height: 100%;
        border-radius: 16rpx;
        overflow: hidden;
        transition: all 0.2s ease;
        
        &:active {
            transform: scale(0.98);
        }
    }
    
    .banner-image {
        width: 100%;
        height: 100%;
        border-radius: 16rpx;
    }
}
</style>
