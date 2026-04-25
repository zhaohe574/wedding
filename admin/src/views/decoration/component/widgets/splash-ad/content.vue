<template>
    <div class="splash-ad-preview" :style="previewStyle">
        <div v-if="content.image" class="splash-ad-preview__image">
            <decoration-img width="100%" height="100%" :src="content.image" fit="cover" />
        </div>
        <div v-else class="splash-ad-preview__empty">
            <el-icon size="36"><Picture /></el-icon>
            <span>请上传开屏广告图片</span>
        </div>
        <div class="splash-ad-preview__footer">
            <button class="splash-ad-preview__button" :style="buttonStyle">
                {{ content.button_text || '点击进入' }}
            </button>
            <div class="splash-ad-preview__tip">
                图片仅展示，不配置点击跳转
            </div>
        </div>
    </div>
</template>
<script lang="ts" setup>
import { Picture } from '@element-plus/icons-vue'
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

const previewStyle = computed(() => ({
    '--splash-mask-color': props.styles.mask_color || 'rgba(0, 0, 0, 0.24)'
}))

const buttonStyle = computed(() => ({
    backgroundColor: props.styles.button_bg_color || '#111111',
    color: props.styles.button_text_color || '#FFFFFF',
    borderRadius: `${props.styles.button_radius ?? 999}px`
}))
</script>

<style lang="scss" scoped>
.splash-ad-preview {
    position: relative;
    width: 100%;
    height: 640px;
    overflow: hidden;
    background: linear-gradient(180deg, #f8f4ec 0%, #111 100%);

    &::after {
        content: '';
        position: absolute;
        inset: 0;
        background: var(--splash-mask-color);
        pointer-events: none;
    }

    &__image {
        width: 100%;
        height: 100%;
    }

    &__empty {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        color: #8c8c8c;
        background: #f5f5f5;
    }

    &__footer {
        position: absolute;
        left: 24px;
        right: 24px;
        bottom: 42px;
        z-index: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    &__button {
        width: 100%;
        height: 44px;
        border: 0;
        font-size: 15px;
        font-weight: 600;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.16);
    }

    &__tip {
        color: rgba(255, 255, 255, 0.82);
        font-size: 12px;
    }
}
</style>
