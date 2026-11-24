<template>
    <!-- 首屏主图：真机是「单图铺满 + 底部文案」，面板内只示意主图，不重复画文案 -->
    <div class="banner">
        <decoration-img v-if="image" width="100%" :height="previewHeight" :src="image" fit="cover" />
        <div v-else class="banner__empty" :style="{ height: previewHeight }">
            未上传首屏主图，真机使用内置主图
        </div>
    </div>
</template>
<script lang="ts" setup>
import type { PropType } from 'vue'

import DecorationImg from '../../decoration-img.vue'
import { HOME_HERO_HEIGHT_DEFAULT, resolveHomeHeroHeight } from '@shared/contracts/decorate'
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

const image = computed(() => props.content.data?.[0]?.image || '')

/** 面板内小预览高度：1054rpx → 150px 基准，按装修配置的 hero_height 等比缩放 */
const heroHeight = computed(() => resolveHomeHeroHeight(props.content))
const previewHeight = computed(
    () => `${Math.round((heroHeight.value / HOME_HERO_HEIGHT_DEFAULT) * 150)}px`
)
</script>

<style lang="scss" scoped>
.banner {
    position: relative;
    width: 100%;
    background: #181614;

    &__empty {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 150px;
        color: rgba(255, 255, 255, 0.45);
        font-size: 12px;
    }
}
</style>
