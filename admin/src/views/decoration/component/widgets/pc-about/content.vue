<template>
    <section class="pc-about-preview">
        <div class="pc-about-preview__image">
            <img v-if="content.image" :src="getImageUrl(content.image)" alt="" />
            <span v-else>品牌介绍图</span>
        </div>
        <div class="pc-about-preview__copy">
            <div class="pc-about-preview__eyebrow">{{ content.eyebrow }}</div>
            <h2>{{ content.title }}</h2>
            <p class="pc-about-preview__subtitle">{{ content.subtitle }}</p>
            <p class="pc-about-preview__description">{{ content.description }}</p>
            <div class="pc-about-preview__points">
                <span v-for="item in points" :key="item">{{ item }}</span>
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
    min-height: 560px;
    box-sizing: border-box;
    display: grid;
    grid-template-columns: 480px minmax(0, 1fr);
    gap: 72px;
    align-items: center;
    padding: 70px 68px;
    background: #ffffff;

    &__image {
        height: 390px;
        background: #171717;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, 0.72);

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    }

    &__eyebrow {
        color: #c8a45d;
        font-size: 13px;
        font-weight: 800;
    }

    h2 {
        margin: 16px 0 0;
        color: #111111;
        font-size: 38px;
        line-height: 1.2;
        font-weight: 800;
    }

    &__subtitle {
        margin: 20px 0 0;
        color: #2e2b26;
        font-size: 19px;
        line-height: 1.7;
        font-weight: 600;
    }

    &__description {
        margin: 18px 0 0;
        color: #6f6a61;
        font-size: 15px;
        line-height: 1.9;
    }

    &__points {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 30px;

        span {
            padding: 9px 13px;
            background: #f7f3ec;
            color: #111111;
            font-size: 13px;
            font-weight: 700;
        }
    }
}
</style>
