<template>
    <view class="price" :class="[`price--${size}`, { 'price--through': lineThrough }]" :aria-label="ariaLabel">
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
 *   xl 64/40  支付结果大金额（仅 ≤6 位整数，超出由页面降 lg）
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
        /** 是否去掉小数末尾多余的 0；合计/应付/实收/支付结果（lg/xl）显式传 false 保留 2 位 */
        trimZeros?: boolean
    }>(),
    {
        prec: 2,
        color: 'var(--c-ink)',
        size: 'md',
        lineThrough: false,
        fontWeight: '600',
        prefix: '¥',
        suffix: '',
        trimZeros: true
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
    // F2：shared/utils/format.ts 已支持 thousand / trimZeros，无需改共享层
    const fixed = formatMoney(props.content as any, {
        decimals: props.prec > 0 ? props.prec : 0,
        thousand: true,
        trimZeros: props.trimZeros
    })
    const [int, dec] = fixed.split('.')
    return { int, dec: dec ? `.${dec}` : '' }
})

const integer = computed(() => parts.value.int)
const decimals = computed(() => parts.value.dec)

/** 读屏：整组读「人民币 X 元」，划线原价说明「原价」 */
const ariaLabel = computed(() => {
    const value = `${parts.value.int}${parts.value.dec}`
    return `${props.lineThrough ? '原价，' : ''}人民币 ${value} 元${props.suffix ? `，${props.suffix}` : ''}`
})

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
    font-weight: var(--fw-semibold);
    letter-spacing: -0.01em;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;

    &__prefix,
    &__suffix {
        color: var(--c-ink-2);
        margin: 0 2rpx;
    }

    /* ¥ 按档位定字号（AC-A-06 白名单已登记 20/24/28/32rpx） */
    &--sm .price__prefix { font-size: 20rpx; }
    &--md .price__prefix { font-size: 24rpx; }
    &--lg .price__prefix { font-size: 28rpx; }
    &--xl .price__prefix { font-size: 32rpx; }

    &__suffix {
        font-size: var(--fs-xs);
    }

    &--through {
        text-decoration: line-through;
        color: var(--c-ink-3);   /* 去 opacity:.6，避免对比度被拉低 */
        .price__int,
        .price__dec,
        .price__prefix,
        .price__suffix {
            color: var(--c-ink-3);
        }
    }
}
</style>
