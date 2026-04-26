<template>
    <div class="home-brand-preview">
        <div class="home-brand-preview__head">
            <div class="home-brand-preview__copy">
                <div class="home-brand-preview__hello">{{ content.greeting || 'Hello,' }}</div>
                <div class="home-brand-preview__title">
                    {{ content.team_name || '我们是星意主持人工作室' }}
                </div>
                <div class="home-brand-preview__subtitle">
                    {{ content.subtitle || '选星意，有心意' }}
                </div>
            </div>
            <div class="home-brand-preview__button">
                {{ content.cta_text || '立即预定' }}
            </div>
        </div>
        <div v-if="stats.length" class="home-brand-preview__stats">
            <template v-for="(item, index) in stats" :key="`${item.value}-${index}`">
                <span v-if="index > 0" class="home-brand-preview__stats-dot"></span>
                <div class="home-brand-preview__stats-item">
                    <div class="home-brand-preview__stats-value">{{ item.value }}</div>
                    <div class="home-brand-preview__stats-label">{{ item.label }}</div>
                </div>
            </template>
        </div>
    </div>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'

import type options from './options'

type OptionsType = ReturnType<typeof options>
const DEFAULT_STATS = [
    { value: '1000+', label: '场仪式' },
    { value: '98%', label: '好评' },
    { value: '30+', label: '城市' }
]

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

const stats = computed(() => {
    const normalizedList = Array.isArray(props.content.stats) ? props.content.stats : []

    return (normalizedList.length ? normalizedList : DEFAULT_STATS)
        .map((item, index) => ({
            value: item?.value || DEFAULT_STATS[index]?.value || '',
            label: item?.label || DEFAULT_STATS[index]?.label || ''
        }))
        .filter((item) => item.value || item.label)
        .slice(0, 3)
})
</script>

<style lang="scss" scoped>
.home-brand-preview {
    margin: -40px 15px 12px;
    padding: 16px 17px 14px;
    border: 1px solid rgba(11, 11, 11, 0.08);
    border-radius: 10px;
    background: #ffffff;
    position: relative;
    z-index: 2;
    box-shadow: 0 6px 15px rgba(11, 11, 11, 0.1);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding-bottom: 12px;
    }

    &__copy {
        min-width: 0;
        flex: 1;
    }

    &__hello {
        font-size: 13px;
        font-weight: 700;
        line-height: 1.2;
        color: #c8a45d;
    }

    &__title {
        margin-top: 5px;
        font-size: 15px;
        font-weight: 700;
        line-height: 1.35;
        color: #111111;
        word-break: break-word;
    }

    &__subtitle {
        margin-top: 5px;
        font-size: 12px;
        line-height: 1.4;
        color: #4a4a4a;
    }

    &__button {
        flex-shrink: 0;
        min-width: 66px;
        height: 31px;
        padding: 0 12px;
        border-radius: 6px;
        background: #0b0b0b;
        color: #ffffff;
        font-size: 12px;
        font-weight: 700;
        line-height: 31px;
        text-align: center;
    }

    &__stats {
        min-height: 39px;
        border-top: 1px solid rgba(11, 11, 11, 0.08);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    &__stats-item {
        flex: 1;
        min-width: 0;
        text-align: center;
    }

    &__stats-value {
        color: #333333;
        font-size: 15px;
        font-weight: 700;
        line-height: 1.15;
    }

    &__stats-label {
        margin-top: 2px;
        color: #777777;
        font-size: 10px;
        line-height: 1.15;
    }

    &__stats-dot {
        width: 3px;
        height: 3px;
        border-radius: 999px;
        background: #c8a45d;
        flex-shrink: 0;
    }
}
</style>
