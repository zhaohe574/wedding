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
        bgColor: '#f3f4f6'
    }
)

const { getImageUrl } = useAppStore()
const currentIndex = ref(props.current)

// 将 indicatorPos 转换为 TuniaoUI 的格式
const getIndicatorPosition = computed(() => {
    const posMap: Record<string, string> = {
        'topLeft': 'left-top',
        'topCenter': 'center-top',
        'topRight': 'right-top',
        'bottomLeft': 'left-bottom',
        'bottomCenter': 'center-bottom',
        'bottomRight': 'right-bottom'
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
            // 兼容不同的图片字段名：bg（首页轮播图）或 image（其他轮播图）
            const imageField = item.bg || item.image
            if (imageField) {
                item.image = getImageUrl(imageField)
            }
        }
        
        emit('change', 0)
    } catch (error) {
        console.error('轮播图数据错误:', error)
    }
})

const lists = computed(() => props.content.data || [])

const handleClick = (index: number) => {
    const link = props.content.data[index]?.link
    if (!link || !link.path) return
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
