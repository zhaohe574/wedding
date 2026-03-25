<template>
    <div class="banner" :style="styles">
        <div class="banner-image relative w-full h-full">
            <decoration-img
                width="100%"
                :height="previewHeight"
                :src="getImage"
                fit="contain"
            />
            <div v-if="previewSloganLines.length" class="banner-copy" :style="previewCopyStyle">
                <div class="banner-copy__accent"></div>
                <div
                    v-for="(line, index) in previewSloganLines"
                    :key="`${index}-${line}`"
                    class="banner-copy__title"
                    :style="{ color: previewSloganColor }"
                >
                    {{ line }}
                </div>
            </div>
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

const previewHeight = computed(() => {
    const configHeight = props.content.height
    const defaultHeight = 321
    const height = configHeight || defaultHeight
    const pxHeight = height / 2

    return `${pxHeight}px`
})

const defaultSloganTop = computed(() => {
    return 120
})

const previewSloganLines = computed(() => {
    const slogan = typeof activeBanner.value?.slogan === 'string' ? activeBanner.value.slogan : ''
    return slogan
        .split(/\r?\n/)
        .map((line: string) => line.trim())
        .filter(Boolean)
})

const previewSloganTop = computed(() => {
    const sloganTop = Number(activeBanner.value?.slogan_top)
    if (Number.isFinite(sloganTop) && sloganTop >= 0) {
        return sloganTop
    }

    return defaultSloganTop.value
})

const previewSloganColor = computed(() => {
    const sloganColor = typeof activeBanner.value?.slogan_color === 'string'
        ? activeBanner.value.slogan_color.trim()
        : ''

    return sloganColor || '#FFFFFF'
})

const previewCopyStyle = computed(() => ({
    top: `${previewSloganTop.value / 2}px`
}))
</script>

<style lang="scss" scoped>
.banner-copy {
    position: absolute;
    left: 0;
    right: 0;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 0 28px;
    text-align: center;
    pointer-events: none;

    &__accent {
        width: 26px;
        height: 3px;
        border-radius: 999px;
        background: #ef5b4c;
    }

    &__title {
        font-size: 22px;
        line-height: 1.35;
        font-weight: 700;
        text-shadow:
            0 2px 6px rgba(47, 28, 24, 0.45),
            0 8px 18px rgba(47, 28, 24, 0.68);
    }
}
</style>
