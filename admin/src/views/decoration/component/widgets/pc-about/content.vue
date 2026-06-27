<template>
    <section class="pc-about-preview">
        <div class="pc-about-preview__copy">
            <div class="pc-about-preview__eyebrow">{{ content.eyebrow }}</div>
            <h2>{{ content.title }}</h2>
            <p class="pc-about-preview__subtitle">{{ content.subtitle }}</p>
            <p class="pc-about-preview__description">{{ content.description }}</p>
            <div class="pc-about-preview__points">
                <article v-for="(item, index) in points" :key="item">
                    <span>{{ String(index + 1).padStart(2, '0') }}</span>
                    <strong>{{ item }}</strong>
                </article>
            </div>
        </div>
        <div class="pc-about-preview__media">
            <div class="pc-about-preview__image">
                <img v-if="content.image" :src="getImageUrl(content.image)" :alt="content.image_alt" />
                <span v-else>品牌介绍图</span>
            </div>
            <div class="pc-about-preview__caption">
                <strong>{{ content.caption_title }}</strong>
                <span>{{ content.caption_text }}</span>
            </div>
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
    }
})

const points = computed(() => normalizeList<string>(props.content.points).filter(Boolean).slice(0, 4))
</script>

<style lang="scss" scoped>
.pc-about-preview {
    width: 1200px;
    min-height: 700px;
    box-sizing: border-box;
    display: grid;
    grid-template-columns: minmax(0, 520px) 1fr;
    gap: 88px;
    align-items: center;
    padding: 92px 68px 86px;
    background:
        linear-gradient(180deg, #f8f2e8 0%, #fffaf1 100%);

    &__eyebrow {
        color: #a77a34;
        font-size: 13px;
        font-weight: 900;
    }

    h2 {
        margin: 16px 0 0;
        color: #17130f;
        font-size: 48px;
        line-height: 1.14;
        font-weight: 900;
    }

    &__subtitle {
        margin: 24px 0 0;
        color: #2d251f;
        font-size: 20px;
        line-height: 1.76;
        font-weight: 800;
    }

    &__description {
        margin: 18px 0 0;
        color: #71685c;
        font-size: 15px;
        line-height: 1.98;
    }

    &__points {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1px;
        margin-top: 38px;
        border-radius: 8px;
        background: rgba(167, 122, 52, 0.24);
        overflow: hidden;

        article {
            min-height: 112px;
            padding: 20px 18px;
            background: #fffaf1;
        }

        span {
            display: block;
            color: #a77a34;
            font-size: 12px;
            font-weight: 900;
        }

        strong {
            display: block;
            margin-top: 28px;
            color: #17130f;
            font-size: 18px;
            font-weight: 900;
        }
    }

    &__media {
        position: relative;
        min-height: 520px;
    }

    &__image {
        position: absolute;
        top: 0;
        right: 0;
        width: 500px;
        height: 470px;
        border-radius: 8px;
        background: #17130f;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, 0.72);
        box-shadow: 0 34px 90px rgba(71, 48, 24, 0.18);

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    }

    &__caption {
        position: absolute;
        left: 0;
        bottom: 0;
        width: 360px;
        padding: 28px 30px;
        border-left: 4px solid #d8b16a;
        border-radius: 8px;
        background: #17130f;
        color: #fffaf1;

        strong,
        span {
            display: block;
        }

        strong {
            font-size: 22px;
            line-height: 1.36;
            font-weight: 900;
        }

        span {
            margin-top: 12px;
            color: rgba(255, 250, 241, 0.68);
            font-size: 14px;
            line-height: 1.8;
        }
    }
}
</style>
