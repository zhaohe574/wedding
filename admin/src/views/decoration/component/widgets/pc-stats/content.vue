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
    min-height: 320px;
    box-sizing: border-box;
    display: grid;
    grid-template-columns: 330px minmax(0, 1fr);
    gap: 48px;
    padding: 58px 68px;
    background: #ffffff;

    &__eyebrow {
        color: #c8a45d;
        font-size: 13px;
        font-weight: 800;
    }

    h2 {
        margin: 14px 0 0;
        color: #111111;
        font-size: 34px;
        line-height: 1.2;
        font-weight: 800;
    }

    &__copy p {
        margin: 16px 0 0;
        color: #6f6a61;
        font-size: 15px;
        line-height: 1.8;
    }

    &__list {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
    }

    article {
        padding: 28px;
        border-left: 3px solid #c8a45d;
        background: #f7f3ec;
    }

    strong {
        display: block;
        color: #111111;
        font-size: 44px;
        line-height: 1;
        font-weight: 900;
    }

    span {
        display: block;
        margin-top: 14px;
        color: #111111;
        font-size: 16px;
        font-weight: 800;
    }

    article p {
        margin: 10px 0 0;
        color: #6f6a61;
        font-size: 13px;
        line-height: 1.6;
    }
}
</style>
