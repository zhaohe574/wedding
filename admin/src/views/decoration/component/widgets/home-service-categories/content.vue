<template>
    <div class="home-categories-preview">
        <div
            v-for="(item, index) in showList"
            :key="index"
            class="home-categories-preview__tile"
            :class="[
                `home-categories-preview__tile--${item.size || 'small'}`,
                { 'home-categories-preview__tile--empty': !item.image }
            ]"
        >
            <decoration-img
                v-if="item.image"
                width="100%"
                height="100%"
                :src="item.image"
                fit="cover"
            />
            <div class="home-categories-preview__shade"></div>
            <div
                class="home-categories-preview__copy"
                :class="[
                    `home-categories-preview__copy--${normalizeTextPosition(item.text_position)}`,
                    `home-categories-preview__copy--align-${normalizeTextAlign(item.text_align)}`
                ]"
            >
                <div class="home-categories-preview__title">{{ item.title }}</div>
                <div class="home-categories-preview__subtitle">{{ item.subtitle }}</div>
                <div class="home-categories-preview__line"></div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'

import DecorationImg from '../../decoration-img.vue'
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

const showList = computed(() => {
    return props.content.data?.filter((item: any) => item.is_show == '1') || []
})

const normalizeTextPosition = (value?: string) => {
    return ['top', 'middle', 'bottom'].includes(String(value)) ? value : 'bottom'
}

const normalizeTextAlign = (value?: string) => {
    return ['left', 'center', 'right'].includes(String(value)) ? value : 'left'
}
</script>

<style lang="scss" scoped>
.home-categories-preview {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    grid-auto-rows: 78px;
    gap: 7px;
    margin: 14px 16px 18px;

    &__tile {
        position: relative;
        min-width: 0;
        overflow: hidden;
        border-radius: 10px;
        background: #111111;
    }

    &__tile--large {
        grid-row: span 2;
    }

    &__tile--wide {
        grid-column: span 2;
    }

    &__tile--empty {
        background: linear-gradient(135deg, #111111 0%, #342b24 100%);
    }

    &__shade {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(0, 0, 0, 0.12) 0%, rgba(0, 0, 0, 0.68) 100%);
    }

    &__copy {
        position: absolute;
        left: 9px;
        right: 9px;
        display: flex;
        flex-direction: column;
    }

    &__copy--align-left {
        align-items: flex-start;
        text-align: left;
    }

    &__copy--align-center {
        align-items: center;
        text-align: center;
    }

    &__copy--align-right {
        align-items: flex-end;
        text-align: right;
    }

    &__copy--top {
        top: 13px;
    }

    &__copy--middle {
        top: 50%;
        transform: translateY(-50%);
    }

    &__copy--bottom {
        bottom: 13px;
    }

    &__title {
        color: #ffffff;
        font-size: 15px;
        font-weight: 800;
        line-height: 1.2;
        word-break: break-word;
    }

    &__subtitle {
        margin-top: 4px;
        color: #c8a45d;
        font-size: 9px;
        font-weight: 700;
        line-height: 1.2;
    }

    &__line {
        width: 24px;
        height: 1px;
        margin-top: 5px;
        background: #c8a45d;
    }
}
</style>
