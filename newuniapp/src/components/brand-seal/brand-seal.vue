<template>
    <!-- 压印印章：品牌位（hero 左上 brandmark）+ 结果页成功印共用同一形状语言 -->
    <view
        class="seal"
        :class="[`seal--${variant}`, { 'seal--round': round }]"
        :style="{ width: size + 'rpx', height: size + 'rpx' }"
        :aria-label="ariaLabel || undefined"
        :aria-hidden="ariaLabel ? undefined : 'true'"
    >
        <text class="seal__label" :style="{ fontSize: Math.round(size * 0.5) + 'rpx' }">{{
            label
        }}</text>
    </view>
</template>

<script setup lang="ts">
import { BRAND_SEAL } from '@/constants/brand'
/**
 * 压印印章（设计稿 brandmark 里的金框方印；默认字取品牌印章字）。
 *
 * 契约：
 *  - 纯装饰。默认 aria-hidden；只有作为品牌名替代（页面上没有品牌中文名）时才传 ariaLabel。
 *  - 金色在深底上直接用 --c-brand-soft（对墨黑 7.4:1），在米白/白底上必须换成 --c-brand-text
 *    （--c-brand 对米白仅 2.25:1），所以变体参数是「底色」而不是「颜色」——调用方不传色值。
 *  - 不内含业务状态（成功/失败用 result-page 或 u-icon），印章只表达品牌。
 */
withDefaults(
    defineProps<{
        /** 印章内容，默认取品牌印章字（constants/brand 的 BRAND_SEAL） */
        label?: string
        /** 边长（rpx）。设计稿 hero 26px≈50rpx，结果页 64px≈123rpx */
        size?: number
        /** dark = 深色底上（hero 主图）；light = 米白/白底上 */
        variant?: 'dark' | 'light'
        /** 圆形（结果页成功印）还是圆角方印（brandmark） */
        round?: boolean
        ariaLabel?: string
    }>(),
    { label: BRAND_SEAL, size: 50, variant: 'dark', round: false, ariaLabel: '' }
)
</script>

<style lang="scss" scoped>
.seal {
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    border-radius: var(--r-sm); /* 设计稿 7px≈13.5rpx，取 12rpx 档 */
    border: 1rpx solid;
    flex: none;

    &--round {
        border-radius: var(--r-pill);
    }

    &__label {
        font-family: var(--font-serif);
        line-height: var(--lh-none);
        letter-spacing: 0;
    }

    /* 深色底（主图 / 墨黑块）：香槟金描边 + 香槟金字 */
    &--dark {
        border-color: var(--c-brand-soft);
        background: rgba(255, 255, 255, 0.06);

        .seal__label {
            color: var(--c-brand-soft);
        }
    }

    /* 浅色底：金色降到可读档，描边仍用香槟（装饰线不受对比度约束） */
    &--light {
        border-color: var(--c-brand);
        background: var(--c-brand-bg);

        .seal__label {
            color: var(--c-brand-text);
        }
    }
}
</style>
