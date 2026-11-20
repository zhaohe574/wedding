<template>
    <!-- 数据条：真机是一条三等分的数字卡，面板内只示意数字，不再画团队信息 -->
    <div class="home-brand-preview">
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

const stats = computed(() => {
    const list = Array.isArray(props.content.stats) ? props.content.stats : []
    return list.filter((item) => !!item && (!!item.value || !!item.label)).slice(0, 3)
})
</script>

<style lang="scss" scoped>
.home-brand-preview {
    margin: 12px 15px;
    padding: 8px 17px;
    border: 1px solid rgba(11, 11, 11, 0.08);
    border-radius: 10px;
    background: #ffffff;
    position: relative;
    z-index: 2;
    box-shadow: 0 6px 15px rgba(11, 11, 11, 0.1);

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
