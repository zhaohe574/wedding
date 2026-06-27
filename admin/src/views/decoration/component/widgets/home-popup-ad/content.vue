<template>
    <div class="home-popup-ad-preview">
        <div class="home-popup-ad-preview__mask">
            <div class="home-popup-ad-preview__panel" :class="panelClass" :style="panelStyle">
                <button v-if="content.show_close != 0" class="home-popup-ad-preview__close">×</button>
                <div v-if="showImage" class="home-popup-ad-preview__image" :style="panelStyle">
                    <decoration-img width="100%" height="100%" :src="content.image" fit="contain" />
                </div>
                <div v-if="showText" class="home-popup-ad-preview__body">
                    <div v-if="content.title" class="home-popup-ad-preview__title">
                        {{ content.title }}
                    </div>
                    <div v-if="content.content" class="home-popup-ad-preview__content">
                        {{ content.content }}
                    </div>
                </div>
                <div v-if="!hasConfiguredContent" class="home-popup-ad-preview__empty">
                    未配置弹窗内容
                </div>
                <div class="home-popup-ad-preview__footer">
                    {{ content.button_text || '查看详情' }}
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
        type: Object as PropType<Record<string, never>>,
        default: () => ({})
    }
})

const showImage = computed(() => props.content.type !== 'text' && !!props.content.image)
const showText = computed(() => props.content.type !== 'image' && (!!props.content.title || !!props.content.content))
const hasConfiguredContent = computed(() => showImage.value || showText.value)
const panelStyle = computed(() => ({
    backgroundColor: props.content.background_color || '#FFFDF8'
}))
const panelClass = computed(() => ({
    'home-popup-ad-preview__panel--disabled': props.content.enabled != 1,
    'home-popup-ad-preview__panel--image': props.content.type === 'image',
    'home-popup-ad-preview__panel--text': props.content.type === 'text'
}))
</script>

<style lang="scss" scoped>
.home-popup-ad-preview {
    position: relative;
    height: 360px;
    margin: 14px 16px;
    overflow: hidden;
    border-radius: 16px;
    background: linear-gradient(180deg, #fffdf8 0%, #efe4cf 100%);

    &__mask {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(11, 11, 11, 0.56);
    }

    &__panel {
        position: relative;
        width: 258px;
        overflow: hidden;
        border-radius: 18px;
        background: #fffdf8;
        box-shadow: 0 18px 42px rgba(0, 0, 0, 0.24);
    }

    &__panel--disabled {
        opacity: 0.5;
    }

    &__close {
        position: absolute;
        top: 8px;
        right: 8px;
        z-index: 2;
        width: 24px;
        height: 24px;
        padding: 0;
        border: 0;
        border-radius: 999px;
        background: rgba(11, 11, 11, 0.56);
        color: #ffffff;
        line-height: 24px;
    }

    &__image {
        width: 100%;
        height: 198px;
        background: #fffdf8;
    }

    &__panel--image &__image {
        height: 278px;
    }

    &__body {
        padding: 20px 22px 4px;
        text-align: center;
    }

    &__title {
        color: #111111;
        font-size: 18px;
        font-weight: 700;
        line-height: 1.35;
    }

    &__content {
        display: -webkit-box;
        margin-top: 8px;
        overflow: hidden;
        color: #6c6254;
        font-size: 13px;
        line-height: 1.7;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
    }

    &__empty {
        padding: 78px 24px 30px;
        color: #8c8c8c;
        text-align: center;
    }

    &__footer {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 38px;
        margin: 18px 24px 24px;
        border-radius: 999px;
        background: linear-gradient(135deg, #191713 0%, #342514 100%);
        color: #fffdf8;
        font-size: 14px;
        font-weight: 600;
        box-shadow: 0 8px 18px rgba(74, 43, 24, 0.2);
    }
}
</style>
