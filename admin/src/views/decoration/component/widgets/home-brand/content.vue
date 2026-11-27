<template>
    <!-- 数据条：展示磨砂浮层 / 暖白卡片两种材质，真机同为三等分数字 -->
    <div class="home-brand-preview" :class="`home-brand-preview--${brandStyle}`">
        <div class="home-brand-preview__card">
            <div v-if="stats.length" class="home-brand-preview__stats">
                <template v-for="(item, index) in stats" :key="`${item.value}-${index}`">
                    <span v-if="index > 0" class="home-brand-preview__stats-dot"></span>
                    <div class="home-brand-preview__stats-item">
                        <div class="home-brand-preview__stats-value">{{ item.value }}</div>
                        <div class="home-brand-preview__stats-label">{{ item.label }}</div>
                    </div>
                </template>
            </div>
            <div v-else class="home-brand-preview__empty">三格都没填，真机不显示数据条</div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'

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

const brandStyle = computed(() => (props.content.style === 'card' ? 'card' : 'glass'))

const stats = computed(() => {
    const list = Array.isArray(props.content.stats) ? props.content.stats : []
    return list.filter((item) => !!item && (!!item.value || !!item.label)).slice(0, 3)
})
</script>

<style lang="scss" scoped>
.home-brand-preview {
    margin: 12px 15px;
    padding: 10px;
    border-radius: 18px;
    background: linear-gradient(135deg, #2a2622 0%, #14110f 100%);

    &--card {
        background: #f3ede1;
    }

    &__card {
        position: relative;
        z-index: 2;
        padding: 10px 17px;
        border-radius: 14px;
        background: #fffdf8;
        box-shadow:
            0 0 0 0.5px rgba(64, 48, 28, 0.06),
            0 2px 6px -1px rgba(64, 48, 28, 0.1),
            0 14px 30px -16px rgba(64, 48, 28, 0.22);
    }

    &--glass &__card {
        background: rgba(255, 253, 248, 0.68);
        backdrop-filter: blur(18px) saturate(1.1);
        box-shadow:
            inset 0 0.5px 0 rgba(255, 255, 255, 0.55),
            0 0 0 0.5px rgba(64, 48, 28, 0.06),
            0 4px 12px -5px rgba(64, 48, 28, 0.16),
            0 18px 40px -18px rgba(0, 0, 0, 0.5);
    }

    &__empty {
        padding: 10px 0;
        color: #999999;
        font-size: 12px;
        text-align: center;
    }

    &__stats {
        min-height: 39px;
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
        font-variant-numeric: tabular-nums;
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
