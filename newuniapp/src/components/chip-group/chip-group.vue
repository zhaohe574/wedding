<template>
    <scroll-view v-if="scroll" class="cg cg--scroll" scroll-x :show-scrollbar="false">
        <view class="cg__row">
            <view
                v-for="it in normalized"
                :key="String(it.value)"
                class="cg__chip"
                :class="[`cg__chip--${size}`, { 'is-active': it.value === modelValue }]"
                hover-class="is-pressed"
                :aria-role="'radio'"
                :aria-checked="it.value === modelValue"
                :aria-label="it.label"
                @tap="pick(it.value)"
            >
                <text class="cg__text">{{ it.label }}</text>
            </view>
        </view>
    </scroll-view>

    <view v-else class="cg">
        <view class="cg__row">
            <view
                v-for="it in normalized"
                :key="String(it.value)"
                class="cg__chip"
                :class="[`cg__chip--${size}`, { 'is-active': it.value === modelValue }]"
                hover-class="is-pressed"
                aria-role="radio"
                :aria-checked="it.value === modelValue"
                :aria-label="it.label"
                @tap="pick(it.value)"
            >
                <text class="cg__text">{{ it.label }}</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 标签选择器（筛选 chip）。全站唯一实现，**只允许两档尺寸**：
 *   md 56rpx 高（页面顶部筛选条、表单里的类型选择）
 *   sm 48rpx 高（卡片内嵌的小标签、次级筛选）
 * 设计稿里同一类标签出现了 4 种尺寸，是靠这个组件收口的——不要在第 3 处自己写 chip。
 *
 * 契约：
 *  - 纯受控：value 由页面持有，组件只 emit('update:modelValue')；
 *  - 不做「多选」：多选请用 tn-checkbox-group 或自建（多选的视觉语言必须是方框，不是胶囊）；
 *  - items 支持 ['全部','中式'] 或 [{label:'全部', value:0}] 两种写法，value 省略时取索引。
 *  - 横向滚动（scroll）用于筛选条超出屏宽的场景，滚动容器本身不带内边距，由页面控制。
 */
import { computed } from 'vue'

type ChipValue = string | number
type ChipItem = string | { label: string; value: ChipValue }

const props = withDefaults(
    defineProps<{
        items: ChipItem[]
        modelValue?: ChipValue | null
        size?: 'md' | 'sm'
        /** 是否横向滚动（筛选条超宽时） */
        scroll?: boolean
    }>(),
    { modelValue: null, size: 'md', scroll: false }
)

const emit = defineEmits<{
    (e: 'update:modelValue', v: ChipValue): void
    (e: 'change', v: ChipValue): void
}>()

const normalized = computed(() =>
    props.items.map((it, i) => (typeof it === 'string' ? { label: it, value: i } : it))
)

function pick(v: ChipValue) {
    if (v === props.modelValue) return
    emit('update:modelValue', v)
    emit('change', v)
}
</script>

<style lang="scss" scoped>
.cg {
    width: 100%;

    &--scroll {
        white-space: nowrap;
    }

    &__row {
        display: inline-flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: none;
        box-sizing: border-box;
        padding: 0 var(--sp-3);
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-control-border); /* 控件边界 ≥3:1，禁用 --c-line */
        background: var(--c-surface);
        position: relative;

        /* 视觉高度之外补足 88rpx 热区 */
        &::before {
            content: '';
            position: absolute;
            top: -14rpx;
            bottom: -14rpx;
            left: 0;
            right: 0;
        }

        &--md {
            height: 56rpx;
        }

        &--sm {
            height: 48rpx;
        }

        &.is-active {
            background: var(--c-ink);
            border-color: var(--c-ink);

            .cg__text {
                color: var(--c-on-dark);
                font-weight: var(--fw-semibold);
            }
        }
    }

    &__text {
        /* 字号只有一档：设计稿的 21rpx / 19rpx 低于全站 24rpx 可读底线，统一收到 --fs-xs */
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        line-height: var(--lh-none);
    }
}
</style>
