<template>
    <view :class="sectionClass" :style="sectionStyle">
        <view v-if="title || description || $slots.header" class="wm-section__header">
            <slot name="header">
                <view class="wm-section__copy">
                    <text v-if="eyebrow" class="wm-section__eyebrow">{{ eyebrow }}</text>
                    <text v-if="title" class="wm-section__title">{{ title }}</text>
                    <text v-if="description" class="wm-section__description">{{ description }}</text>
                </view>
                <view v-if="$slots.action" class="wm-section__action">
                    <slot name="action" />
                </view>
            </slot>
        </view>
        <slot />
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    variant?: 'plain' | 'list' | 'hero' | 'panel' | 'form' | 'dashboard' | 'media' | 'showcase'
    title?: string
    eyebrow?: string
    description?: string
    padding?: string
    gap?: string
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'plain',
    title: '',
    eyebrow: '',
    description: '',
    padding: '',
    gap: ''
})

const sectionClass = computed(() => ['wm-section', `wm-section--${props.variant}`])
const sectionStyle = computed(() => ({
    ...(props.padding ? { padding: props.padding } : {}),
    ...(props.gap ? { gap: props.gap } : {})
}))
</script>

<script lang="ts">
export default {
    name: 'PageSection',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.wm-section {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: var(--wm-space-section-gap-lg, 36rpx);
    padding: 0 var(--wm-space-page-x, 32rpx);

    &--hero {
        gap: var(--wm-space-card-padding-lg, 36rpx);
        padding-top: var(--wm-space-4, 32rpx);
    }

    &--media {
        gap: var(--wm-space-section-gap-md, 28rpx);
        padding: 0;
    }

    &--panel,
    &--dashboard,
    &--form,
    &--list,
    &--showcase {
        padding-top: var(--wm-space-4, 32rpx);
    }

    &__header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 24rpx;
        padding: 0 8rpx;
    }

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 8rpx;
    }

    &__eyebrow {
        font-size: 22rpx;
        font-weight: 900;
        color: var(--wm-color-gold, #B8954A);
    }

    &__title {
        font-size: 36rpx;
        font-weight: 900;
        line-height: 1.25;
        color: var(--wm-text-primary, #191713);
    }

    &__description {
        font-size: 24rpx;
        line-height: 1.55;
        color: var(--wm-text-secondary, #665E52);
    }

    &__action {
        flex-shrink: 0;
    }
}
</style>
