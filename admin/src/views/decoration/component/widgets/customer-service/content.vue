<template>
    <div class="cs-card">
        <div class="cs-card__head">
            <span v-if="normalized.eyebrow" class="cs-card__eyebrow">{{ normalized.eyebrow }}</span>
            <span class="cs-card__status">礼宾名片</span>
        </div>
        <div class="cs-card__title">统一客服 · 专属婚礼顾问</div>
        <div class="cs-card__tips">{{ normalized.tips }}</div>
        <div class="cs-card__actions">
            <div class="cs-card__btn cs-card__btn--primary">{{ normalized.button_text }}</div>
            <div class="cs-card__btn cs-card__btn--ghost">{{ normalized.phone_button_text }}</div>
        </div>
        <div class="cs-card__meta">
            <span>自助服务：{{ normalized.show_quick_bar === 1 ? '开启' : '关闭' }}</span>
            <span>
                保障：{{
                    normalized.show_assurance === 1
                        ? `${normalized.assurances.filter((i) => i.title).length} 项`
                        : '隐藏'
                }}
            </span>
            <span>
                FAQ：{{
                    normalized.show_faq === 1
                        ? `${normalized.faqs.filter((i) => i.q).length} 条`
                        : '隐藏'
                }}
            </span>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { normalizeCustomerServiceContent } from '@shared/contracts/decorate'
import type { PropType } from 'vue'
import { computed } from 'vue'

import type options from './options'

type OptionsType = ReturnType<typeof options>

const props = defineProps({
    content: {
        type: Object as PropType<Partial<OptionsType['content']>>,
        default: () => ({})
    },
    styles: {
        type: Object as PropType<Record<string, any>>,
        default: () => ({})
    }
})

const normalized = computed(() => normalizeCustomerServiceContent(props.content))
</script>

<style lang="scss" scoped>
.cs-card {
    margin: 12px;
    padding: 16px;
    border-radius: 16px;
    background: linear-gradient(150deg, #211d19 0%, #14110f 100%);
    color: #ffffff;
    display: flex;
    flex-direction: column;
    gap: 10px;

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    &__eyebrow {
        font-family: Georgia, serif;
        font-size: 10px;
        letter-spacing: 0.2em;
        color: #d9be82;
    }

    &__status {
        font-size: 11px;
        color: rgba(255, 255, 255, 0.72);
    }

    &__title {
        font-size: 15px;
        font-weight: 600;
    }

    &__tips {
        font-size: 12px;
        line-height: 1.5;
        color: rgba(255, 255, 255, 0.72);
    }

    &__actions {
        display: flex;
        gap: 8px;
        margin-top: 2px;
    }

    &__btn {
        flex: 1;
        height: 36px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;

        &--primary {
            background: #fffdf8;
            color: #181614;
        }

        &--ghost {
            border: 1px solid rgba(255, 255, 255, 0.24);
            color: #ffffff;
        }
    }

    &__meta {
        display: flex;
        justify-content: space-between;
        padding-top: 8px;
        border-top: 1px solid rgba(255, 255, 255, 0.12);
        font-size: 11px;
        color: rgba(255, 255, 255, 0.68);
    }
}
</style>
