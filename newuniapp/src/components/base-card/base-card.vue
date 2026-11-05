<template>
    <view
        hover-class="is-pressed"
        class="card"
        :class="{
            'card--flush': flush,
            'card--compact': compact,
            'card--selected': selected
        }"
        :aria-role="clickable ? 'button' : undefined"
        :aria-label="ariaLabel || undefined"
        @tap="onTap"
    >
        <slot />
        <!-- 选中态形状编码：不只靠颜色（视觉规范 §7.4 / AC-A-11） -->
        <u-icon v-if="selected" name="checkmark-circle-fill" :size="32" class="card__check" color="var(--c-brand-text)" aria-hidden="true" />
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
        /** clickable 时的读屏标签（含条目主信息） */
        ariaLabel?: string
    }>(),
    { flush: false, clickable: false, compact: false, selected: false, ariaLabel: '' }
)

const emit = defineEmits<{ (e: 'tap'): void }>()

function onTap() {
    emit('tap')
}
</script>

<style lang="scss" scoped>
.card {
    position: relative;
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-md);   /* --r-lg 28rpx → --r-md 20rpx（A7） */
    padding: var(--sp-4);
    box-shadow: var(--shadow-card);   /* = none，保持 */

    &--compact {
        padding: var(--sp-3);
        border-radius: var(--r-md);
    }

    &--selected {
        border-color: var(--c-brand-text);   /* --c-brand → --c-brand-text（A8，5.34:1 ≥3:1） */
        /* 只改颜色，不增 border-width，不引起尺寸跳动 */
    }

    &--flush {
        padding: 0;
        overflow: hidden;
    }

    &__check {
        position: absolute;
        top: var(--sp-2);
        right: var(--sp-2);
    }
}
</style>
