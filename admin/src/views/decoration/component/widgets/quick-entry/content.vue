<template>
    <div class="qe-preview">
        <div v-if="data.enabled !== 1" class="qe-preview__off">
            未启用：个人中心不显示「{{ data.title }}」面板
        </div>

        <div v-else-if="!rows.length" class="qe-preview__off">
            列表为空：真机不显示「{{ data.title }}」面板
        </div>

        <div v-else class="qe-preview__panel">
            <div class="qe-preview__head">{{ data.title }}</div>
            <div
                v-for="(row, index) in rows"
                :key="row.key || index"
                class="qe-preview__row"
            >
                <div class="qe-preview__txt">
                    <div class="qe-preview__title">{{ row.title }}</div>
                    <div v-if="row.subtitle" class="qe-preview__sub">{{ row.subtitle }}</div>
                </div>
                <span class="qe-preview__arrow">›</span>
            </div>
        </div>
    </div>
</template>
<script lang="ts" setup>
import { normalizeQuickEntryContent } from '@shared/contracts/decorate'
import { computed, type PropType } from 'vue'

import type options from './options'

type OptionsType = ReturnType<typeof options>

const props = defineProps({
    content: {
        type: Object as PropType<OptionsType['content']>,
        default: () => ({})
    }
})

const data = computed(() => normalizeQuickEntryContent(props.content))
const rows = computed(() => data.value.data.filter((item) => item.is_show === '1'))
</script>

<style lang="scss" scoped>
.qe-preview {
    padding: 10px;
    background: #f5f5f5;

    &__off {
        padding: 12px;
        border: 1px dashed #d8c9ad;
        border-radius: 8px;
        font-size: 12px;
        color: #8c8c8c;
    }

    &__panel {
        border: 1px solid #e7e0d3;
        border-radius: 12px;
        background: #fffdf8;
    }

    &__head {
        padding: 12px 14px 4px;
        font-size: 14px;
        font-weight: 600;
        color: #181614;
    }

    &__row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 0 14px;
        height: 44px;
        border-top: 1px solid #e7e0d3;

        &:first-of-type {
            margin-top: 8px;
        }
    }

    &__txt {
        min-width: 0;
    }

    &__title {
        font-size: 13px;
        color: #181614;
    }

    &__sub {
        margin-top: 2px;
        font-size: 11px;
        color: #8c8c8c;
    }

    &__arrow {
        flex: none;
        font-size: 18px;
        line-height: 1;
        color: #8c8c8c;
    }
}
</style>
