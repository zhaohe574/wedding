<template>
    <view class="base-info-row" :class="{ 'base-info-row--dark': dark }">
        <text class="base-info-row__label">{{ label }}</text>
        <text class="base-info-row__value">{{ value }}</text>
    </view>
</template>

<script setup lang="ts">
interface Props {
    label: string
    value: string | number
    dark?: boolean
}

withDefaults(defineProps<Props>(), {
    dark: false
})
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
        color: var(--wm-text-secondary, #6B625A);
    }

    &__value {
        min-width: 0;
        text-align: right;
        font-size: 26rpx;
        font-weight: 900;
        color: var(--wm-text-primary, #1A1A1A);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &--dark &__label {
        color: rgba(255, 253, 248, 0.68);
    }

    &--dark &__value {
        color: var(--wm-text-inverse, #FFFDF8);
    }
}
</style>
