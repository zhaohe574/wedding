<template>
    <div class="splash-ad-preview">
        <div v-if="content.image" class="splash-ad-preview__image">
            <decoration-img width="100%" height="100%" :src="content.image" fit="cover" />
        </div>
        <div v-else class="splash-ad-preview__empty">
            <el-icon size="36"><Picture /></el-icon>
            <span>请上传开屏广告图片</span>
        </div>
        <div v-if="content.logo_image" class="splash-ad-preview__logo">
            <decoration-img
                class="splash-ad-preview__logo-image"
                width="142px"
                height="96px"
                :src="content.logo_image"
                fit="contain"
            />
        </div>
        <div class="splash-ad-preview__footer">
            <button class="splash-ad-preview__button">
                {{ content.button_text || '点击进入' }}
            </button>
        </div>
    </div>
</template>
<script lang="ts" setup>
import { Picture } from '@element-plus/icons-vue'
import type { PropType } from 'vue'

import DecorationImg from '../../decoration-img.vue'
import type options from './options'

type OptionsType = ReturnType<typeof options>

defineProps({
    content: {
        type: Object as PropType<OptionsType['content']>,
        default: () => ({})
    },
    styles: {
        type: Object as PropType<OptionsType['styles']>,
        default: () => ({})
    }
})
</script>

<style lang="scss" scoped>
.splash-ad-preview {
    position: relative;
    width: 100%;
    height: 640px;
    overflow: hidden;
    background: linear-gradient(180deg, #f8f4ec 0%, #111 100%);

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

    &__logo {
        position: absolute;
        top: 96px;
        left: 50%;
        z-index: 1;
        display: flex;
        justify-content: center;
        transform: translateX(-50%);
    }

    &__logo-image {
        animation: splash-preview-logo-breathe 2.35s ease-in-out infinite;
        transform-origin: center center;
        will-change: transform;
    }

    &__footer {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 74px;
        z-index: 1;
        display: flex;
        justify-content: center;
    }

    &__button {
        width: 112px;
        height: 36px;
        padding: 0;
        border: 1px solid rgba(255, 255, 255, 0.72);
        border-radius: 999px;
        background: rgba(156, 158, 166, 0.78);
        color: #ffffff;
        font-size: 14px;
        font-weight: 600;
        line-height: 34px;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.5),
            0 6px 14px rgba(0, 0, 0, 0.24);
        animation: splash-preview-button-breathe 2.4s ease-in-out infinite;
        backdrop-filter: blur(5px);
    }
}

@keyframes splash-preview-logo-breathe {
    0%,
    100% {
        opacity: 0.96;
        transform: scale(1);
    }

    50% {
        opacity: 1;
        transform: scale(1.08);
    }
}

@keyframes splash-preview-button-breathe {
    0%,
    100% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.045);
    }
}
</style>
