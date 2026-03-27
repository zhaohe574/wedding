<template>
    <view :class="sectionClass" :style="sectionStyle">
        <slot />
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    variant?: 'plain' | 'list' | 'hero' | 'panel' | 'form' | 'dashboard'
    padding?: string
    gap?: string
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'plain',
    padding: '',
    gap: ''
})

const sectionClass = computed(() => ['wm-section', `wm-section--${props.variant}`])
const sectionStyle = computed(() => ({
    ...(props.padding ? { padding: props.padding } : {}),
    ...(props.gap ? { gap: props.gap } : {})
}))
</script>

<style lang="scss" scoped>
.wm-section {
    display: flex;
    flex-direction: column;
    gap: var(--wm-space-4, 16rpx);
    padding: 0 var(--wm-space-5, 20rpx);

    &--hero {
        gap: var(--wm-space-6, 24rpx);
        padding-top: var(--wm-space-4, 16rpx);
    }

    &--panel,
    &--dashboard,
    &--form,
    &--list {
        padding-top: var(--wm-space-4, 16rpx);
    }
}
</style>
