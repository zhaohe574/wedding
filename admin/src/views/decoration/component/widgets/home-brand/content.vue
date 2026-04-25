<template>
    <div class="home-brand-preview">
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
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'

import type options from './options'

type OptionsType = ReturnType<typeof options>

defineProps({
    content: {
        type: Object as PropType<OptionsType['content']>,
        default: () => ({})
    },
    styles: {
        type: Object as PropType<OptionsType['styles']>,
        default: () => ({})
    }
})
</script>

<style lang="scss" scoped>
.home-brand-preview {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin: -40px 15px 0;
    padding: 15px 14px 12px;
    border-radius: 16px 16px 0 0;
    background: #ffffff;
    position: relative;
    z-index: 2;
    box-shadow: 0 9px 22px rgba(11, 11, 11, 0.13);

    &__copy {
        min-width: 0;
        flex: 1;
    }

    &__hello {
        font-size: 13px;
        font-weight: 700;
        line-height: 1.2;
        color: #000000;
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
        color: #8e887d;
    }

    &__button {
        flex-shrink: 0;
        min-width: 66px;
        height: 28px;
        padding: 0 12px;
        border-radius: 999px;
        background: #e28b4f;
        color: #ffffff;
        font-size: 12px;
        font-weight: 700;
        line-height: 28px;
        text-align: center;
    }
}
</style>
