<template>
    <view hover-class="is-pressed"
        class="card"
        :class="{
            'card--flush': flush,
            'card--tap': clickable,
            'card--compact': compact,
            'card--selected': selected
        }"
        @tap="onTap"
    >
        <slot />
    </view>
</template>

<script setup lang="ts">
/** 统一卡片容器：圆角、边框、阴影与内边距。 */
withDefaults(
    defineProps<{
        /** 去掉内边距（用于整图卡片） */
        flush?: boolean
        clickable?: boolean
        /** 紧凑卡片：24rpx 内边距、20rpx 圆角 */
        compact?: boolean
        /** 选中态复用默认边框，不引起尺寸跳动 */
        selected?: boolean
    }>(),
    { flush: false, clickable: false, compact: false, selected: false }
)

const emit = defineEmits<{ (e: 'tap'): void }>()

function onTap() {
    emit('tap')
}
</script>

<style lang="scss" scoped>
.card {
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-lg);
    padding: var(--sp-4);
    box-shadow: var(--shadow-card);

    &--compact {
        padding: var(--sp-3);
        border-radius: var(--r-md);
    }

    &--selected {
        border-color: var(--c-brand);
    }

    &--flush {
        padding: 0;
        overflow: hidden;
    }

    &--tap:active {
        opacity: 0.9;
    }
}
</style>
