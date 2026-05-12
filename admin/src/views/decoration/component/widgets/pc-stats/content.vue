<template>
    <section class="pc-stats-preview">
        <div class="pc-stats-preview__copy">
            <div class="pc-stats-preview__eyebrow">{{ content.eyebrow }}</div>
            <h2>{{ content.title }}</h2>
            <p>{{ content.subtitle }}</p>
        </div>
        <div class="pc-stats-preview__list">
            <article v-for="(item, index) in items" :key="`${item.label}-${index}`">
                <strong>{{ item.value }}</strong>
                <span>{{ item.label }}</span>
                <p>{{ item.description }}</p>
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
    }
})

const items = computed(() => normalizeList(props.content.data).slice(0, 3))
</script>

<style lang="scss" scoped>
.pc-stats-preview {
    width: 1200px;
    min-height: 430px;
    box-sizing: border-box;
    display: grid;
    grid-template-columns: 320px minmax(0, 1fr);
    gap: 58px;
    align-items: center;
    padding: 78px 68px;
    background: #f8f2e8;

    &__eyebrow {
        color: #a77a34;
        font-size: 13px;
        font-weight: 900;
    }

    h2 {
        margin: 14px 0 0;
        color: #17130f;
        font-size: 40px;
        line-height: 1.18;
        font-weight: 900;
    }

    &__copy p {
        margin: 18px 0 0;
        color: #71685c;
        font-size: 15px;
        line-height: 1.84;
    }

    &__list {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        border-top: 1px solid rgba(23, 19, 15, 0.16);
        border-radius: 8px;
        border-bottom: 1px solid rgba(23, 19, 15, 0.16);
        overflow: hidden;
    }

    article {
        min-height: 206px;
        padding: 34px 28px;
        border-right: 1px solid rgba(23, 19, 15, 0.16);
        background: rgba(255, 250, 241, 0.58);

        &:last-child {
            border-right: 0;
        }
    }

    strong {
        display: block;
        color: #17130f;
        font-size: 54px;
        line-height: 1;
        font-weight: 900;
    }

    span {
        display: block;
        margin-top: 20px;
        color: #17130f;
        font-size: 16px;
        font-weight: 900;
    }

    article p {
        margin: 12px 0 0;
        color: #71685c;
        font-size: 13px;
        line-height: 1.7;
    }
}
</style>
