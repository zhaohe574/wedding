<template>
    <section class="pc-advantages-preview">
        <div class="pc-advantages-preview__head">
            <div>
                <div class="pc-advantages-preview__eyebrow">{{ content.eyebrow }}</div>
                <h2>{{ content.title }}</h2>
            </div>
            <p>{{ content.subtitle }}</p>
        </div>
        <div class="pc-advantages-preview__grid">
            <article v-for="(item, index) in items" :key="`${item.title}-${index}`">
                <span>{{ String(index + 1).padStart(2, '0') }}</span>
                <h3>{{ item.title }}</h3>
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

const items = computed(() => normalizeList(props.content.data).slice(0, 4))
</script>

<style lang="scss" scoped>
.pc-advantages-preview {
    width: 1200px;
    min-height: 430px;
    box-sizing: border-box;
    padding: 66px 68px;
    background: #111111;
    color: #ffffff;

    &__head {
        display: grid;
        grid-template-columns: 1fr 420px;
        gap: 60px;
        align-items: end;

        h2 {
            margin: 14px 0 0;
            font-size: 36px;
            line-height: 1.18;
            font-weight: 800;
        }

        p {
            margin: 0;
            color: rgba(255, 255, 255, 0.68);
            font-size: 16px;
            line-height: 1.75;
        }
    }

    &__eyebrow {
        color: #c8a45d;
        font-size: 13px;
        font-weight: 800;
    }

    &__grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
        margin-top: 42px;

        article {
            min-height: 138px;
            padding: 26px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            background: rgba(255, 255, 255, 0.04);
        }

        span {
            color: #c8a45d;
            font-size: 13px;
            font-weight: 800;
        }

        h3 {
            margin: 18px 0 0;
            color: #ffffff;
            font-size: 20px;
            font-weight: 800;
        }

        p {
            margin: 12px 0 0;
            color: rgba(255, 255, 255, 0.68);
            font-size: 14px;
            line-height: 1.7;
        }
    }
}
</style>
