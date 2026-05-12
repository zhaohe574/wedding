<template>
    <section class="pc-gallery-preview">
        <div class="pc-gallery-preview__head">
            <div>
                <div class="pc-gallery-preview__eyebrow">{{ content.eyebrow }}</div>
                <h2>{{ content.title }}</h2>
            </div>
            <p>{{ content.subtitle }}</p>
        </div>
        <div class="pc-gallery-preview__grid">
            <article
                v-for="(item, index) in items"
                :key="`${item.title}-${index}`"
                :class="{ 'is-featured': index === 0 }"
            >
                <div class="pc-gallery-preview__image">
                    <img v-if="item.image" :src="getImageUrl(item.image)" alt="" />
                    <span v-else>展示图</span>
                </div>
                <div class="pc-gallery-preview__content">
                    <span>{{ String(index + 1).padStart(2, '0') }}</span>
                    <h3>{{ item.title }}</h3>
                    <p>{{ item.description }}</p>
                </div>
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
    min-height: 760px;
    box-sizing: border-box;
    padding: 94px 68px 100px;
    background: #fffaf1;

    &__head {
        display: grid;
        grid-template-columns: 1fr 410px;
        gap: 78px;
        align-items: end;

        h2 {
            margin: 15px 0 0;
            color: #17130f;
            font-size: 44px;
            line-height: 1.14;
            font-weight: 900;
        }

        p {
            margin: 0;
            color: #6f665a;
            font-size: 16px;
            line-height: 1.85;
        }
    }

    &__eyebrow {
        color: #a77a34;
        font-size: 13px;
        font-weight: 900;
    }

    &__grid {
        display: grid;
        grid-template-columns: 1.2fr 0.9fr;
        grid-template-rows: 255px 255px;
        gap: 18px;
        margin-top: 54px;
    }

    article {
        position: relative;
        min-height: 0;
        border-radius: 8px;
        background: #f8f2e8;
        overflow: hidden;

        &.is-featured {
            grid-row: 1 / 3;

            .pc-gallery-preview__image {
                height: 100%;
            }

            .pc-gallery-preview__content {
                left: 30px;
                right: 30px;
                bottom: 30px;
                color: #fffaf1;
                background: rgba(23, 19, 15, 0.68);

                h3 {
                    color: #fffaf1;
                    font-size: 28px;
                }

                p {
                    color: rgba(255, 250, 241, 0.74);
                }
            }
        }
    }

    &__image {
        height: 100%;
        background: #17130f;
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

    &__content {
        position: absolute;
        left: 22px;
        right: 22px;
        bottom: 22px;
        padding: 20px 22px;
        border-radius: 8px;
        background: rgba(255, 250, 241, 0.94);

        > span {
            color: #a77a34;
            font-size: 13px;
            font-weight: 900;
        }

        h3 {
            margin: 12px 0 0;
            color: #17130f;
            font-size: 21px;
            line-height: 1.28;
            font-weight: 900;
        }

        p {
            margin: 10px 0 0;
            color: #6f665a;
            font-size: 14px;
            line-height: 1.7;
        }
    }
}
</style>
