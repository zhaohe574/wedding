<template>
    <!-- 品牌首屏精简预览：整页预览由 pc-preview.vue 统一镜像真机几何，这里只做兜底渲染 -->
    <section class="pc-hero-preview" :style="coverStyle">
        <div class="pc-hero-preview__veil"></div>
        <div class="pc-hero-preview__body">
            <div class="pc-hero-preview__brand">
                <strong>{{ content.brand_name }}</strong>
                <span>{{ content.brand_en }}</span>
            </div>
            <div class="pc-hero-preview__eyebrow">{{ content.eyebrow }}</div>
            <h1>{{ content.title }}</h1>
            <div class="pc-hero-preview__qr">
                <i></i>
                <span>{{ content.qr_caption }}</span>
            </div>
        </div>
    </section>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'

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

const coverStyle = computed(() => (props.content.image ? { backgroundImage: `url(${props.content.image})` } : {}))
</script>

<style lang="scss" scoped>
.pc-hero-preview {
    position: relative;
    width: 1200px;
    min-height: 520px;
    box-sizing: border-box;
    padding: 64px 68px;
    background-color: #151210;
    background-size: cover;
    background-position: center;
    color: #efe5d3;
    overflow: hidden;

    &__veil {
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(17, 14, 12, 0.94) 0%, rgba(17, 14, 12, 0.62) 52%, rgba(17, 14, 12, 0.3) 100%);
    }

    &__body {
        position: relative;
        z-index: 1;
    }

    &__brand {
        display: flex;
        align-items: baseline;
        gap: 14px;

        strong {
            font-size: 20px;
            font-weight: 900;
        }

        span {
            color: rgba(201, 169, 110, 0.85);
            font-size: 12px;
            font-weight: 800;
        }
    }

    &__eyebrow {
        margin-top: 42px;
        color: #dfd0b0;
        font-size: 13px;
        font-weight: 900;
    }

    h1 {
        max-width: 640px;
        margin: 20px 0 0;
        font-size: 60px;
        line-height: 1.08;
        font-weight: 900;
        white-space: pre-line;
    }

    &__qr {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin-top: 40px;
        padding: 10px 18px;
        border: 1px solid rgba(201, 169, 110, 0.42);
        border-radius: 999px;
        background: rgba(17, 14, 12, 0.55);

        i {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #c9a96e;
        }

        span {
            color: #dfd0b0;
            font-size: 13px;
            font-weight: 700;
        }
    }
}
</style>
