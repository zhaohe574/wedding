<template>
    <div class="wd-countdown">
        <div v-if="data.enabled === 1" class="wd-countdown__card">
            <div class="wd-countdown__label">{{ data.label }}</div>
            <div class="wd-countdown__num-row">
                <span class="wd-countdown__num">88</span>
                <span class="wd-countdown__unit">{{ data.unit_text }}</span>
            </div>
            <div v-if="data.show_progress === 1" class="wd-countdown__progress">
                <div class="wd-countdown__progress-head">
                    <span>筹备进度</span>
                    <span class="wd-countdown__progress-value">60%</span>
                </div>
                <div class="wd-countdown__progress-bar">
                    <span
                        v-for="seg in 5"
                        :key="seg"
                        class="wd-countdown__seg"
                        :class="{ 'is-done': seg <= 3, 'is-current': seg === 4 }"
                    />
                </div>
                <div class="wd-countdown__nodes">
                    <span
                        v-for="(label, index) in milestones"
                        :key="label"
                        class="wd-countdown__node"
                        :class="{ 'is-done': index < 3, 'is-current': index === 3 }"
                    >
                        <i class="wd-countdown__dot" />{{ label }}
                    </span>
                </div>
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

/** 与真机同序的 5 个里程碑；示例完成 3 个（60%） */
const milestones = ['锁定档期', '新人问卷', '尾款结清', '婚礼当天', '服务完成']
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

    &__progress {
        margin-top: 14px;
        padding-top: 12px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    &__progress-head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 8px;
        font-size: 10px;
        letter-spacing: 0.08em;
        color: rgba(255, 255, 255, 0.55);
    }

    &__progress-value {
        font-family: Georgia, 'Times New Roman', serif;
        font-size: 15px;
        color: #d9be82;
    }

    &__progress-bar {
        display: flex;
        gap: 4px;
        margin-top: 8px;
    }

    &__seg {
        flex: 1;
        height: 3px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.14);

        &.is-done {
            background: #d9be82;
        }

        &.is-current {
            background: linear-gradient(90deg, #d9be82 0%, rgba(217, 190, 130, 0.4) 100%);
        }
    }

    &__nodes {
        display: flex;
        justify-content: space-between;
        gap: 4px;
        margin-top: 8px;
    }

    &__node {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 9px;
        line-height: 1.3;
        color: rgba(255, 255, 255, 0.35);
        white-space: nowrap;

        &.is-done {
            color: rgba(217, 190, 130, 0.85);
        }

        &.is-current {
            color: #d9be82;
        }
    }

    &__dot {
        width: 4px;
        height: 4px;
        border-radius: 999px;
        background: currentColor;
        flex: none;
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
