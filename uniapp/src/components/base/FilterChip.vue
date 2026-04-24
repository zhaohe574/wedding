<template>
    <view :class="chipClass" @click="emit('click')">
        <text class="filter-chip__text">
            <slot />
        </text>
        <tn-icon
            v-if="closable"
            name="close"
            size="18"
            class="filter-chip__close"
            @click.stop="emit('close')"
        />
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    selected?: boolean
    closable?: boolean
    scene?: 'consumer' | 'staff' | 'admin'
}

const props = withDefaults(defineProps<Props>(), {
    selected: false,
    closable: false,
    scene: 'consumer'
})

const emit = defineEmits<{
    (event: 'click'): void
    (event: 'close'): void
}>()

const chipClass = computed(() => [
    'filter-chip',
    `filter-chip--${props.scene}`,
    { 'filter-chip--selected': props.selected }
])
</script>

<style lang="scss" scoped>
.filter-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6rpx;
    min-height: 64rpx;
    padding: 0 24rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    background: rgba(255, 255, 255, 0.9);
    transition: all var(--wm-motion-base, 220ms) ease;

    &--selected {
        background: var(--wm-color-primary-soft, #f3f2ee);
        border-color: var(--wm-color-border-strong, #d8c28a);
    }

    &__text {
        font-size: 24rpx;
        color: var(--wm-text-secondary, #5f5a50);
    }

    &--selected .filter-chip__text {
        color: var(--wm-color-primary, #0b0b0b);
        font-weight: 600;
    }
}
</style>
