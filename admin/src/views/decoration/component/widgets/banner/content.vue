<template>
    <div class="banner" :style="styles">
        <div class="banner-image w-full h-full">
            <decoration-img
                width="100%"
                :height="previewHeight"
                :src="getImage"
                fit="contain"
            />
        </div>
    </div>
</template>
<script lang="ts" setup>
import type { PropType } from 'vue'

import DecorationImg from '../../decoration-img.vue'
import type options from './options'

type OptionsType = ReturnType<typeof options>
const props = defineProps({
    content: {
        type: Object as PropType<OptionsType['content']>,
        default: () => ({})
    },
    styles: {
        type: Object as PropType<OptionsType['styles']>,
        default: () => ({})
    },
    height: {
        type: String,
        default: '170px'
    }
})

const showList = computed(() => {
    return props.content.data?.filter((item: any) => item.is_show == '1') || []
})

const getImage = computed(() => {
    if (Array.isArray(showList.value)) {
        return showList.value[0] ? showList.value[0].image : ''
    }
    return ''
})

// 预览高度计算：使用配置的高度或默认高度，并转换为预览区域的像素值
const previewHeight = computed(() => {
    // 使用配置的高度或默认高度
    const configHeight = props.content.height
    const defaultHeight = props.content.style === 1 ? 321 : 1100
    const height = configHeight || defaultHeight
    
    // 转换为预览区域的像素值 (rpx to px, 按2倍比例)
    const pxHeight = height / 2
    
    // 常规模式使用计算的高度，大屏模式使用固定高度以适应预览区域
    return props.content.style === 1 ? `${pxHeight}px` : '550px'
})
</script>

<style lang="scss" scoped></style>
