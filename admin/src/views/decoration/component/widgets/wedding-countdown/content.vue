<template>
    <div class="wd-countdown">
        <div v-if="data.enabled === 1" class="wd-countdown__card">
            <div class="wd-countdown__label">{{ data.label }}</div>
            <div class="wd-countdown__num-row">
                <span class="wd-countdown__num">88</span>
                <span class="wd-countdown__unit">{{ data.unit_text }}</span>
            </div>
            <div class="wd-countdown__foot">
                <span>还有 3 项待确认</span>
                <span class="wd-countdown__venue">宴会厅</span>
            </div>
        </div>
        <div v-else class="wd-countdown__off">未启用：个人中心不显示婚期卡</div>

        <div class="wd-countdown__empty">
            <div class="wd-countdown__empty-title">无已预约订单时</div>
            <div class="wd-countdown__empty-row">
                <span class="wd-countdown__empty-name">{{ data.empty_title }}</span>
                <span class="wd-countdown__empty-action">{{ data.empty_action_text }}</span>
            </div>
            <div class="wd-countdown__empty-desc">{{ data.empty_desc }}</div>
        </div>
    </div>
</template>
<script lang="ts" setup>
import { normalizeWeddingCountdownContent } from '@shared/contracts/decorate'
import { computed, type PropType } from 'vue'

import type options from './options'

type OptionsType = ReturnType<typeof options>

const props = defineProps({
    content: {
        type: Object as PropType<OptionsType['content']>,
        default: () => ({})
    }
})

/** 归一化后使用，后台草稿缺字段时仍能预览 */
const data = computed(() => normalizeWeddingCountdownContent(props.content))
</script>

<style lang="scss" scoped>
.wd-countdown {
    padding: 10px;
    background: #f5f5f5;

    &__card {
        padding: 18px;
        border: 1px solid #d8c9ad;
        border-radius: 12px;
        background: #181614;
    }

    &__label {
        font-family: Georgia, 'Times New Roman', serif;
        font-size: 11px;
        letter-spacing: 0.2em;
        color: rgba(255, 255, 255, 0.72);
    }

    &__num-row {
        display: flex;
        align-items: baseline;
        gap: 6px;
        margin-top: 8px;
    }

    &__num {
        font-family: Georgia, 'Times New Roman', serif;
        font-size: 36px;
        line-height: 1;
        color: #ffffff;
    }

    &__unit {
        font-size: 12px;
        letter-spacing: 0.06em;
        color: rgba(255, 255, 255, 0.72);
    }

    &__foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-top: 12px;
        font-size: 11px;
        color: rgba(255, 255, 255, 0.72);
    }

    &__venue {
        flex: none;
        color: #d9be82;
    }

    &__off {
        padding: 12px;
        border: 1px dashed #d8c9ad;
        border-radius: 8px;
        font-size: 12px;
        color: #8c8c8c;
    }

    &__empty {
        margin-top: 10px;
        padding: 10px 12px;
        border: 1px solid #e7e0d3;
        border-radius: 8px;
        background: #fffdf8;
    }

    &__empty-title {
        font-size: 11px;
        color: #8c8c8c;
    }

    &__empty-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-top: 6px;
    }

    &__empty-name {
        font-size: 14px;
        font-weight: 600;
        color: #181614;
    }

    &__empty-action {
        flex: none;
        padding: 2px 10px;
        border: 1px solid #8a8175;
        border-radius: 999px;
        font-size: 11px;
        color: #181614;
    }

    &__empty-desc {
        margin-top: 6px;
        font-size: 11px;
        line-height: 1.6;
        color: #5a554e;
    }
}
</style>
