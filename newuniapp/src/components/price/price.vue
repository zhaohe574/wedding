<template>
    <view class="price" :class="[`price--${size}`, { 'price--through': lineThrough }]">
        <text class="price__prefix">{{ prefix }}</text>
        <text class="price__int" :style="mainStyle">{{ integer }}</text>
        <text v-if="decimals" class="price__dec" :style="minorStyle">{{ decimals }}</text>
        <text v-if="suffix" class="price__suffix">{{ suffix }}</text>
    </view>
</template>

<script setup lang="ts">
/**
 * 金额展示：整数与小数分字号。
 *
 * 字号只允许 4 档语义值，调用处不再传裸 rpx —— 之前 7 档裸值导致
 * 列表内嵌价 28rpx（14px）与正文同号，金额毫无权重。
 *   sm 28/20  列表卡内嵌价、人员卡起价
 *   md 36/24  卡片主价、订单行小计、套餐价
 *   lg 48/30  金额摘要「合计」、订单总额
 *   xl 64/40  支付结果大金额
 */
import { computed } from 'vue'
import { formatMoney } from '@shared/utils/format'

const props = withDefaults(
    defineProps<{
        content: string | number
        /** 小数位，0 表示不显示小数 */
        prec?: number
        color?: string
        size?: 'sm' | 'md' | 'lg' | 'xl'
        lineThrough?: boolean
        fontWeight?: string
        prefix?: string
        suffix?: string
    }>(),
    {
        prec: 2,
        color: 'var(--c-ink)',
        size: 'md',
        lineThrough: false,
        fontWeight: '500',
        prefix: '¥',
        suffix: ''
    }
)

const SIZE_MAP: Record<'sm' | 'md' | 'lg' | 'xl', { main: string; minor: string }> = {
    sm: { main: '28rpx', minor: '20rpx' },
    md: { main: '36rpx', minor: '24rpx' },
    lg: { main: '48rpx', minor: '30rpx' },
    xl: { main: 'var(--fs-number)', minor: '40rpx' }
}

const scale = computed(() => SIZE_MAP[props.size] || SIZE_MAP.md)

const parts = computed(() => {
    const fixed = formatMoney(props.content as any, { decimals: props.prec > 0 ? props.prec : 0 })
    const [int, dec] = fixed.split('.')
    // 末尾 0 去掉：1.50 → 1.5，1.00 → 1
    const trimmed = (dec || '').replace(/0+$/, '')
    return { int, dec: trimmed ? `.${trimmed}` : '' }
})

const integer = computed(() => parts.value.int)
const decimals = computed(() => parts.value.dec)

/** 字重是 CSS 联合类型，props 传 string 需要收窄 */
const mainStyle = computed(() => ({
    fontSize: scale.value.main,
    color: props.color,
    fontWeight: props.fontWeight as any
}))

const minorStyle = computed(() => ({
    fontSize: scale.value.minor,
    color: props.color,
    fontWeight: props.fontWeight as any
}))
</script>

<style lang="scss" scoped>
.price {
    display: inline-flex;
    align-items: baseline;
    font-weight: 500;

    &__prefix,
    &__suffix {
        color: var(--c-ink-2);
        margin: 0 2rpx;
    }

    &__prefix {
        font-size: var(--fs-xs);
    }

    &__suffix {
        font-size: var(--fs-xs);
    }

    &--through {
        text-decoration: line-through;
        opacity: 0.6;
    }
}
</style>
