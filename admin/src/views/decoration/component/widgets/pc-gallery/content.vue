<template>
    <section class="pc-gallery-preview">
        <div class="pc-gallery-preview__head">
            <div class="pc-gallery-preview__eyebrow">{{ content.eyebrow }}</div>
            <h2>{{ content.title }}</h2>
            <p>{{ content.subtitle }}</p>
        </div>
        <div class="pc-gallery-preview__grid">
            <article v-for="(item, index) in items" :key="`${item.title}-${index}`">
                <div class="pc-gallery-preview__image">
                    <img v-if="item.image" :src="getImageUrl(item.image)" alt="" />
                    <span v-else>展示图</span>
                </div>
                <h3>{{ item.title }}</h3>
                <p>{{ item.description }}</p>
            </article>
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

const items = computed(() => normalizeList(props.content.data).slice(0, 3))
</script>

<style lang="scss" scoped>
.pc-gallery-preview {
    width: 1200px;
    min-height: 600px;
    box-sizing: border-box;
    padding: 70px 68px;
    background: #f7f3ec;

    &__head {
        max-width: 700px;

        h2 {
            margin: 14px 0 0;
            color: #111111;
            font-size: 38px;
            line-height: 1.2;
            font-weight: 800;
        }

        p {
            margin: 14px 0 0;
            color: #6f6a61;
            font-size: 16px;
            line-height: 1.7;
        }
    }

    &__eyebrow {
        color: #9a7336;
        font-size: 13px;
        font-weight: 800;
    }

    &__grid {
        display: grid;
        grid-template-columns: 1.2fr 0.9fr 0.9fr;
        gap: 18px;
        margin-top: 42px;
    }

    article {
        background: #ffffff;
        overflow: hidden;
        box-shadow: 0 18px 50px rgba(17, 17, 17, 0.08);

        h3 {
            margin: 20px 22px 0;
            color: #111111;
            font-size: 18px;
            font-weight: 800;
        }

        p {
            margin: 10px 22px 24px;
            color: #6f6a61;
            font-size: 14px;
            line-height: 1.7;
        }
    }

    &__image {
        height: 230px;
        background: #151515;
        color: rgba(255, 255, 255, 0.72);
        display: flex;
        align-items: center;
        justify-content: center;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    }
}
</style>
