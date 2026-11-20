<template>
    <view class="avail">
        <view class="avail__date">
            <text class="avail__day">{{ dateLabel }}</text>
            <text class="avail__sub">{{ weekday }}</text>
            <view v-if="lunar" class="avail__sep" />
            <text v-if="lunar" class="avail__sub">农历{{ lunar }}</text>
            <status-tag
                v-if="isLucky"
                class="avail__tag"
                tone="primary"
                size="sm"
                :dot="false"
                label="吉日"
            />
        </view>

        <view v-if="state === 'loading'" class="avail__sk">
            <view class="avail__sk-line avail__sk-line--wide" />
            <view class="avail__sk-line" />
        </view>

        <view v-else-if="state === 'no-region'" class="avail__row">
            <text class="avail__text">选择服务地区后查看可约人数</text>
            <text class="avail__link" @tap="emit('go-region')">去选择 ›</text>
        </view>

        <view v-else-if="state === 'error'" class="avail__row">
            <view class="avail__dot avail__dot--unknown" />
            <text class="avail__text">档期没加载出来</text>
            <text class="avail__link" @tap="emit('retry')">重试</text>
        </view>

        <view v-else-if="state === 'unknown'" class="avail__row">
            <view class="avail__dot avail__dot--unknown" />
            <text class="avail__text">档期信息待更新</text>
            <text class="avail__link" @tap="emit('retry')">重新获取</text>
        </view>

        <view v-else-if="state === 'full'" class="avail__row">
            <view class="avail__dot avail__dot--empty" />
            <text class="avail__full">当日档期已满</text>
            <text class="avail__link" @tap="emit('contact')">联系客服协调</text>
        </view>

        <view v-else class="avail__row">
            <view class="avail__dot" :class="{ 'avail__dot--warning': count === 1 }" />
            <text v-if="count === 1" class="avail__only">仅</text>
            <text class="avail__num">{{ count }}</text>
            <text class="avail__unit">位可约</text>
            <text class="avail__note">· {{ note }}</text>
        </view>

        <view v-if="state === 'full' && neighbors.length" class="avail__neighbors">
            <view
                v-for="n in neighbors"
                :key="n.date"
                class="avail__nb"
                hover-class="is-pressed"
                @tap="emit('pick-neighbor', n.date)"
            >
                <text class="avail__nb-text">{{ n.label }} · {{ n.count }} 位可约</text>
            </view>
        </view>

        <text v-else-if="state === 'full'" class="avail__hint">
            {{ neighborsLoading ? '正在查找相邻可约日…' : '相邻日期也已满，试试其他日期' }}
        </text>
    </view>
</template>

<script setup lang="ts">
/**
 * 已选日期 + 档期状态。
 *
 * 状态判定顺序很重要：
 *   未选地区 → 加载中 → 请求失败 → 未知（null）→ 已满（0）→ 有档期（>0）。
 * `count === null` 与 `count === 0` 必须是两个状态；未知不能写成 0，也不能写成已满。
 */
import { computed } from 'vue'
import type { NeighborDay } from './types'

const props = withDefaults(
    defineProps<{
        /** YYYY-MM-DD */
        date: string
        weekday: string
        lunar?: string
        isLucky?: boolean
        regionSelected: boolean
        loading: boolean
        error: boolean
        count: number | null
        neighbors?: NeighborDay[]
        neighborsLoading?: boolean
    }>(),
    {
        lunar: '',
        isLucky: false,
        neighbors: () => [],
        neighborsLoading: false
    }
)

const emit = defineEmits<{
    (e: 'retry'): void
    (e: 'go-region'): void
    (e: 'pick-neighbor', date: string): void
    (e: 'contact'): void
}>()

const dateLabel = computed(() => {
    const [, m, d] = props.date.split('-')
    return `${Number(m)}月${Number(d)}日`
})

const state = computed<
    'no-region' | 'loading' | 'error' | 'unknown' | 'full' | 'limited' | 'ready'
>(() => {
    if (!props.regionSelected) return 'no-region'
    if (props.loading) return 'loading'
    if (props.error) return 'error'
    if (props.count === null) return 'unknown'
    if (props.count === 0) return 'full'
    if (props.count === 1) return 'limited'
    return 'ready'
})

const note = computed(() => {
    const n = props.count ?? 0
    if (n <= 1) return '建议尽快确认'
    if (n <= 4) return '档期较紧'
    return '档期充足'
})
</script>

<style lang="scss" scoped>
.avail {
    padding-top: 4rpx;

    &__date {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8rpx;
        min-height: 44rpx;
    }

    &__day {
        font-size: var(--fs-33);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        line-height: var(--lh-tight);
    }

    &__sub {
        font-size: var(--fs-21);
        color: var(--c-ink-3);
        line-height: var(--lh-none);
    }

    &__sep {
        width: 1rpx;
        height: 18rpx;
        background: var(--c-line-strong);
    }

    &__tag {
        margin-left: 2rpx;
    }

    &__row {
        display: flex;
        align-items: center;
        gap: 10rpx;
        min-height: 48rpx;
        padding-top: 6rpx;
    }

    &__dot {
        width: 12rpx;
        height: 12rpx;
        border-radius: var(--r-pill);
        background: var(--c-success);
        flex: none;

        &--warning {
            background: var(--c-warning);
        }

        &--empty {
            background: transparent;
            border: 1rpx solid var(--c-ink-3);
        }

        &--unknown {
            background: transparent;
            border: 1rpx dashed var(--c-ink-3);
        }
    }

    &__num {
        font-family: var(--font-serif);
        font-size: var(--fs-33);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        line-height: var(--lh-none);
    }

    &__only {
        font-size: var(--fs-27);
        color: var(--c-ink-2);
        line-height: var(--lh-none);
    }

    &__unit {
        font-size: var(--fs-21);
        color: var(--c-ink-2);
    }

    &__note {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }

    &__text {
        font-size: var(--fs-27);
        color: var(--c-ink-2);
    }

    &__full {
        font-size: var(--fs-27);
        font-weight: var(--fw-medium);
        color: var(--c-ink-2);
    }

    &__link {
        margin-left: auto;
        font-size: var(--fs-20);
        color: var(--c-brand-text);
        white-space: nowrap;
    }

    &__neighbors {
        display: flex;
        align-items: center;
        gap: 12rpx;
        padding-top: 12rpx;
        flex-wrap: wrap;
    }

    &__nb {
        height: 48rpx;
        padding: 0 16rpx;
        border-radius: var(--r-pill);
        background: var(--c-brand-bg);
        display: flex;
        align-items: center;
    }

    &__nb-text {
        font-size: var(--fs-20);
        color: var(--c-brand-text);
        white-space: nowrap;
    }

    &__hint {
        display: block;
        padding-top: 10rpx;
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }

    &__sk {
        padding-top: 8rpx;
    }

    &__sk-line {
        width: 38%;
        height: 16rpx;
        border-radius: var(--r-xs);
        background: var(--c-surface-2);
        animation: availShimmer 1.2s ease-in-out infinite;

        &--wide {
            width: 62%;
            height: 24rpx;
            margin-bottom: 10rpx;
        }
    }
}

@keyframes availShimmer {
    0% {
        opacity: 0.55;
    }
    50% {
        opacity: 1;
    }
    100% {
        opacity: 0.55;
    }
}

@media (prefers-reduced-motion: reduce) {
    .avail__sk-line {
        animation: none;
    }
}
</style>
