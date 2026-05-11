<template>
    <section class="pc-hero-preview">
        <div class="pc-hero-preview__copy">
            <div class="pc-hero-preview__eyebrow">{{ content.eyebrow }}</div>
            <h1>{{ content.title }}</h1>
            <p class="pc-hero-preview__subtitle">{{ content.subtitle }}</p>
            <p class="pc-hero-preview__description">{{ content.description }}</p>
            <div class="pc-hero-preview__badges">
                <span v-for="item in badges" :key="item">{{ item }}</span>
            </div>
        </div>
        <div class="pc-hero-preview__media">
            <img v-if="content.image" :src="getImageUrl(content.image)" alt="" />
            <div v-else class="pc-hero-preview__placeholder">企业首屏图</div>
            <div class="pc-hero-preview__caption">{{ content.image_caption }}</div>
        </div>
    </section>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'

import { getImageUrl, normalizeList } from '../pc-shared'
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

const badges = computed(() => normalizeList<string>(props.content.badges).filter(Boolean).slice(0, 4))
</script>

<style lang="scss" scoped>
.pc-hero-preview {
    width: 1200px;
    min-height: 620px;
    box-sizing: border-box;
    display: grid;
    grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.05fr);
    gap: 72px;
    align-items: center;
    padding: 86px 68px;
    color: #111111;
    background:
        linear-gradient(120deg, rgba(17, 17, 17, 0.05), rgba(200, 164, 93, 0.12)),
        #f7f3ec;
    overflow: hidden;

    &__copy {
        min-width: 0;
    }

    &__eyebrow {
        color: #9a7336;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 0;
    }

    h1 {
        margin: 18px 0 0;
        color: #111111;
        font-size: 58px;
        line-height: 1.05;
        font-weight: 800;
        letter-spacing: 0;
    }

    &__subtitle {
        margin: 24px 0 0;
        color: #2a2722;
        font-size: 22px;
        line-height: 1.55;
        font-weight: 600;
    }

    &__description {
        margin: 16px 0 0;
        color: #6f6a61;
        font-size: 16px;
        line-height: 1.8;
    }

    &__badges {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 34px;

        span {
            border: 1px solid rgba(17, 17, 17, 0.14);
            padding: 9px 14px;
            color: #111111;
            background: rgba(255, 255, 255, 0.48);
            font-size: 13px;
            font-weight: 700;
        }
    }

    &__media {
        position: relative;
        height: 430px;
        background: #111111;
        overflow: hidden;
        box-shadow: 0 28px 70px rgba(17, 17, 17, 0.24);

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
    }

    &__placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, 0.72);
        font-size: 18px;
    }

    &__caption {
        position: absolute;
        left: 22px;
        bottom: 22px;
        max-width: calc(100% - 44px);
        padding: 10px 14px;
        color: #ffffff;
        background: rgba(17, 17, 17, 0.68);
        font-size: 14px;
    }
}
</style>
