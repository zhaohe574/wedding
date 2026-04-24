<template>
    <tn-swiper
        v-if="lists.length"
        v-model="currentIndex"
        :data="lists"
        :width="'100%'"
        :height="height"
        :autoplay="autoplay"
        :interval="interval"
        :duration="duration"
        :loop="circular"
        :indicator="mode !== 'none'"
        :indicator-type="mode === 'number' ? 'number' : 'dot'"
        :indicator-position="getIndicatorPosition"
        @item-click="handleClick"
        @change="handleSwiperChange"
    >
        <template #default="{ data }">
            <view class="swiper-item-wrap">
                <image
                    :src="data[name]"
                    mode="aspectFill"
                    class="swiper-image"
                    :style="{ borderRadius: borderRadius + 'rpx' }"
                />
            </view>
        </template>
    </tn-swiper>
</template>

<script setup lang="ts">
import { useAppStore } from '@/stores/app'
import { navigateTo } from '@/utils/util'
import { watchEffect, computed, ref } from 'vue'

const emit = defineEmits(['change'])
const props = withDefaults(
    defineProps<{
        content?: any // 轮播图数据
        mode?: string // 指示器模式 rect / dot / number / none
        height?: string // 轮播图组件高度
        indicatorPos?: string // 指示器的位置 topLeft / topCenter / topRight / bottomLeft / bottomRight / bottomCenter
        effect3d?: boolean // 是否开启3D效果
        autoplay?: boolean // 是否自动播放
        interval?: number | string // 自动轮播时间间隔，单位ms
        duration?: number | string // 切换一张轮播图所需的时间，单位ms
        circular?: boolean // 是否衔接播放
        current?: number // 默认显示第几项
        name?: string // 显示的属性
        borderRadius?: string // 轮播图圆角值，单位rpx
        bgColor?: string // 背景颜色
    }>(),
    {
        content: {
            data: []
        },
        mode: 'dot',
        indicatorPos: 'bottomCenter',
        height: '340',
        effect3d: false,
        autoplay: true,
        interval: '2500',
        duration: 300,
        circular: true,
        current: 0,
        name: 'image',
        borderRadius: '0',
        bgColor: '#f8f7f2'
    }
)

const { getImageUrl } = useAppStore()
const currentIndex = ref(props.current)

// 将 indicatorPos 转换为 TuniaoUI 的格式
const getIndicatorPosition = computed(() => {
    const posMap: Record<string, string> = {
        topLeft: 'left-top',
        topCenter: 'center-top',
        topRight: 'right-top',
        bottomLeft: 'left-bottom',
        bottomCenter: 'center-bottom',
        bottomRight: 'right-bottom'
    }
    return posMap[props.indicatorPos] || 'center-bottom'
})

watchEffect(() => {
    try {
        const content = props?.content
        const len = content?.data?.length

        if (!len) return

        for (let i = 0; i < len; i++) {
            const item = content.data[i]
            // 优先使用 image 字段（轮播图），bg 字段是背景联动用的
            const imageField = item.image || item.bg
            if (imageField) {
                item.image = getImageUrl(imageField)
            }
        }

        emit('change', 0)
    } catch (error) {
        console.error('轮播图数据错误:', error)
    }
})

const lists = computed(() => {
    return (props.content.data || []).filter((item: any) => item.is_show !== '0')
})

const handleClick = (index: number) => {
    const currentItem = lists.value[index]
    const link = currentItem?.link
    const path = typeof link?.path === 'string' ? link.path.trim() : ''
    if (!currentItem || !link || !path) return
    navigateTo(link)
}

const handleSwiperChange = (index: number) => {
    currentIndex.value = index
    emit('change', index)
}
</script>

<style lang="scss" scoped>
.swiper-item-wrap {
    width: 100%;
    height: 100%;
}

.swiper-image {
    width: 100%;
    height: 100%;
    display: block;
}
</style>
