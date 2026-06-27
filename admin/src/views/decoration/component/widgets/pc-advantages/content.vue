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
                <span>{{ item.kicker || String(index + 1).padStart(2, '0') }}</span>
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
    min-height: 640px;
    box-sizing: border-box;
    padding: 88px 68px 92px;
    background:
        linear-gradient(135deg, rgba(216, 177, 106, 0.18), rgba(216, 177, 106, 0) 38%),
        repeating-linear-gradient(90deg, rgba(255, 250, 241, 0.055) 0, rgba(255, 250, 241, 0.055) 1px, transparent 1px, transparent 160px),
        #17130f;
    color: #fffaf1;

    &__head {
        display: grid;
        grid-template-columns: 1fr 410px;
        gap: 78px;
        align-items: end;

        h2 {
            margin: 15px 0 0;
            color: #fffaf1;
            font-size: 44px;
            line-height: 1.14;
            font-weight: 900;
        }

        p {
            margin: 0;
            color: rgba(255, 250, 241, 0.7);
            font-size: 16px;
            line-height: 1.85;
        }
    }

    &__eyebrow {
        color: #d8b16a;
        font-size: 13px;
        font-weight: 900;
    }

    &__grid {
        display: grid;
        grid-template-columns: 1.05fr 0.95fr 1.05fr;
        gap: 18px;
        margin-top: 58px;
        align-items: stretch;

        article {
            position: relative;
            min-height: 260px;
            padding: 34px 32px;
            border: 1px solid rgba(255, 250, 241, 0.16);
            border-radius: 8px;
            background: rgba(255, 250, 241, 0.055);
            overflow: hidden;

            &:nth-child(2) {
                transform: translateY(42px);
                background: rgba(216, 177, 106, 0.12);
            }

            &::after {
                content: '';
                position: absolute;
                left: 32px;
                right: 32px;
                bottom: 28px;
                height: 1px;
                background: rgba(216, 177, 106, 0.6);
            }
        }

        span {
            color: #d8b16a;
            font-size: 13px;
            font-weight: 900;
        }

        h3 {
            margin: 54px 0 0;
            color: #fffaf1;
            font-size: 25px;
            line-height: 1.28;
            font-weight: 900;
        }

        p {
            margin: 18px 0 0;
            color: rgba(255, 250, 241, 0.68);
            font-size: 14px;
            line-height: 1.86;
        }
    }
}
</style>
