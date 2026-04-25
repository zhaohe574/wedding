<template>
    <div class="home-feature-preview">
        <decoration-img
            v-if="getImage"
            width="100%"
            :height="previewHeight"
            :src="getImage"
            fit="cover"
        />
        <div v-else class="home-feature-preview__empty" :style="{ height: previewHeight }"></div>
        <div v-if="showList.length > 1" class="home-feature-preview__dots">
            <span
                v-for="(_, index) in showList"
                :key="index"
                class="home-feature-preview__dot"
                :class="{ 'home-feature-preview__dot--active': index === 0 }"
            ></span>
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
    }
})

const showList = computed(() => {
    return props.content.data?.filter((item: any) => item.is_show == '1') || []
})

const getImage = computed(() => showList.value[0]?.image || '')

const previewHeight = computed(() => `${(Number(props.content.height) || 300) / 2}px`)
</script>

<style lang="scss" scoped>
.home-feature-preview {
    position: relative;
    overflow: hidden;
    margin: 0 16px 12px;
    border-radius: 10px;
    background: #111111;

    &__empty {
        background: linear-gradient(135deg, #111111 0%, #342b24 100%);
    }

    &__dots {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 8px;
        display: flex;
        justify-content: center;
        gap: 5px;
    }

    &__dot {
        width: 5px;
        height: 5px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.48);
    }

    &__dot--active {
        width: 14px;
        background: #ffffff;
    }
}
</style>
