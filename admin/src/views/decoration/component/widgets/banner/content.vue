<template>
    <div class="banner" :style="[styles, previewBackgroundStyle]">
        <div class="banner-image relative w-full h-full">
            <decoration-img
                width="100%"
                :height="previewHeight"
                :src="getImage"
                fit="cover"
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

const activeBanner = computed(() => {
    return Array.isArray(showList.value) ? showList.value[0] || null : null
})

const getImage = computed(() => {
    return activeBanner.value?.image || ''
})

const previewBackgroundStyle = computed(() => {
    const bgColor = activeBanner.value?.bg_color || '#000000'
    const bgImage = activeBanner.value?.bg || ''

    if (props.content.bg_style != 1) {
        return { background: '#000000' }
    }

    if (bgColor) {
        return { background: bgColor }
    }

    if (bgImage) {
        return {
            backgroundImage: `url(${bgImage})`,
            backgroundSize: 'cover',
            backgroundPosition: 'center'
        }
    }

    return { background: '#000000' }
})

const previewHeight = computed(() => {
    const configHeight = props.content.height
    const defaultHeight = 321
    const height = configHeight || defaultHeight
    const pxHeight = height / 2

    return `${pxHeight}px`
})

</script>

<style lang="scss" scoped>
.banner {
    overflow: hidden;
}
</style>
