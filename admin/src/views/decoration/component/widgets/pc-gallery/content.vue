<template>
    <!-- 精选现场精简预览：整页预览由 pc-preview.vue 统一镜像真机几何，这里只做兜底渲染 -->
    <section class="pc-gallery-preview">
        <div class="pc-gallery-preview__head">
            <div class="pc-gallery-preview__eyebrow">{{ content.eyebrow }}</div>
            <h2>{{ content.title }}</h2>
        </div>
        <div class="pc-gallery-preview__grid">
            <article v-for="(item, index) in items" :key="index">
                <div class="pc-gallery-preview__image">
                    <img v-if="item.image" :src="item.image" :alt="item.alt || item.title" />
                    <span v-else>精选现场图</span>
                </div>
                <div class="pc-gallery-preview__content">
                    <span>{{ item.scene }}</span>
                    <h3>{{ item.title }}</h3>
                    <p>{{ item.description }}</p>
                </div>
            </article>
        </div>
    </section>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'

import { normalizeList } from '../pc-shared'
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

const items = computed(() =>
    normalizeList<OptionsType['content']['data'][number]>(props.content.data).filter(Boolean).slice(0, 3)
)
</script>

<style lang="scss" scoped>
.pc-gallery-preview {
    width: 1200px;
    box-sizing: border-box;
    padding: 88px 68px;
    background: #1b1714;

    &__eyebrow {
        color: #c9a96e;
        font-size: 13px;
        font-weight: 900;
    }

    &__head {
        h2 {
            margin: 15px 0 0;
            color: #efe5d3;
            font-size: 42px;
            line-height: 1.18;
            font-weight: 900;
        }
    }

    &__grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
        margin-top: 44px;

        article {
            border: 1px solid rgba(201, 169, 110, 0.22);
            border-radius: 8px;
            background: rgba(201, 169, 110, 0.06);
            overflow: hidden;
        }
    }

    &__image {
        height: 190px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #8b8073;
        font-size: 13px;
        background: rgba(201, 169, 110, 0.08);

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
    }

    &__content {
        padding: 18px 20px 22px;

        > span {
            color: #c9a96e;
            font-size: 12px;
            font-weight: 900;
        }

        h3 {
            margin: 10px 0 0;
            color: #efe5d3;
            font-size: 19px;
            font-weight: 900;
        }

        p {
            margin: 10px 0 0;
            color: #b9aea0;
            font-size: 13px;
            line-height: 1.7;
        }
    }
}
</style>
