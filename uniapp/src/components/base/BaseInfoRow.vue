<template>
    <view :class="rowClass">
        <text class="base-info-row__label">{{ label }}</text>

        <view class="base-info-row__value-wrap">
            <slot name="value">
                <text :class="valueClass">{{ value }}</text>
            </slot>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

type InfoRowTone = 'default' | 'muted' | 'price' | 'danger' | 'success' | 'warning'

interface Props {
    label: string
    value?: string | number
    dark?: boolean
    multiline?: boolean
    tone?: InfoRowTone
}

const props = withDefaults(defineProps<Props>(), {
    value: '',
    dark: false,
    multiline: false,
    tone: 'default'
})

const safeTone = computed<InfoRowTone>(() =>
    ['default', 'muted', 'price', 'danger', 'success', 'warning'].includes(props.tone)
        ? props.tone
        : 'default'
)

const rowClass = computed(() => [
    'base-info-row',
    {
        'base-info-row--dark': props.dark,
        'base-info-row--multiline': props.multiline
    }
])

const valueClass = computed(() => ['base-info-row__value', `base-info-row__value--${safeTone.value}`])
</script>

<script lang="ts">
export default {
    name: 'BaseInfoRow',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24rpx;
    min-height: 88rpx;

    &__label {
        flex-shrink: 0;
        font-size: 24rpx;
        font-weight: 800;
        color: var(--wm-text-secondary, #665E52);
    }

    &__value-wrap {
        flex: 1;
        min-width: 0;
        display: flex;
        justify-content: flex-end;
    }

    &__value {
        min-width: 0;
        text-align: right;
        font-size: 26rpx;
        font-weight: 900;
        color: var(--wm-text-primary, #191713);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__value--muted {
        font-weight: 800;
        color: var(--wm-text-secondary, #665E52);
    }

    &__value--price {
        color: var(--wm-color-gold, #B8954A);
    }

    &__value--danger {
        color: var(--wm-color-danger, #9A6B35);
    }

    &__value--success {
        color: var(--wm-color-success, #71806F);
    }

    &__value--warning {
        color: var(--wm-color-clay, #9A6B35);
    }

    &--multiline {
        align-items: flex-start;
        padding: 18rpx 0;
    }

    &--multiline &__label {
        padding-top: 2rpx;
    }

    &--multiline &__value-wrap {
        justify-content: flex-start;
    }

    &--multiline &__value {
        width: 100%;
        text-align: left;
        line-height: 1.6;
        white-space: normal;
        word-break: break-word;
        overflow: visible;
        text-overflow: clip;
    }

    &--dark &__label {
        color: rgba(255, 253, 248, 0.68);
    }

    &--dark &__value {
        color: var(--wm-text-inverse, #FFFDF8);
    }

    &--dark &__value--muted {
        color: rgba(255, 253, 248, 0.68);
    }
}
</style>
