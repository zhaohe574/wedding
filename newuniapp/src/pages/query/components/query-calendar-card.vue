<template>
    <view class="cal">
        <view class="cal__head">
            <!-- 年月快切：点月份标题打开原生「年-月」选择器，替代原来的全部/吉日/周末筛选 -->
            <picker
                class="cal__month-picker"
                mode="date"
                fields="month"
                :value="monthValue"
                :start="minSelectable"
                :end="maxDate"
                @change="onMonthChange"
            >
                <view class="cal__month" hover-class="is-pressed">
                    <text class="cal__year">{{ year }}</text>
                    <text class="cal__month-text">{{ month }}月</text>
                    <tn-icon name="down-arrow" :size="20" class="cal__month-arrow" />
                </view>
            </picker>
            <view class="cal__nav">
                <view
                    class="cal__nav-btn"
                    hover-class="is-pressed"
                    aria-role="button"
                    aria-label="上个月"
                    @tap="emit('prev')"
                >
                    <tn-icon name="left" :size="26" class="cal__nav-ic" />
                </view>
                <text class="cal__today" @tap="emit('today')">本月</text>
                <view
                    class="cal__nav-btn"
                    hover-class="is-pressed"
                    aria-role="button"
                    aria-label="下个月"
                    @tap="emit('next')"
                >
                    <tn-icon name="right" :size="26" class="cal__nav-ic" />
                </view>
            </view>
        </view>

        <view class="cal__week">
            <text v-for="w in WEEK" :key="w" class="cal__week-text">{{ w }}</text>
        </view>

        <!-- 首屏：网格骨架，与最终行高同构 -->
        <view v-if="loading && !hasData" class="cal__grid" aria-hidden="true">
            <view v-for="n in 35" :key="n" class="cal__cell">
                <view class="cal__sk" />
            </view>
        </view>

        <!-- 加载失败：区块级错误，不占整屏 -->
        <view v-else-if="error" class="cal__error">
            <text class="cal__error-text">档期日历没加载出来</text>
            <text class="cal__error-link" @tap="emit('retry')">重新加载</text>
        </view>

        <template v-else>
            <view v-if="hasData && (loading || statusLoading)" class="cal__progress" />
            <view
                class="cal__grid"
                :class="[{ 'is-loading': loading && hasData }, gridAnimClass]"
            >
                <view
                    v-for="cell in cells"
                    :key="cell.key"
                    class="cal__cell"
                    :class="cell.classes"
                    :aria-role="cell.date ? 'button' : undefined"
                    :aria-label="cell.aria || undefined"
                    :aria-disabled="cell.disabled ? 'true' : undefined"
                    :hover-class="cell.disabled ? '' : 'is-pressed'"
                    @tap="onCell(cell)"
                >
                    <template v-if="cell.date">
                        <view class="cal__day">
                            <text class="cal__day-num">{{ cell.day }}</text>
                        </view>
                        <view v-if="cell.isLucky" class="cal__lucky" />
                        <view v-if="cell.isToday || cell.dot" class="cal__sub">
                            <text v-if="cell.isToday" class="cal__today-tag">今</text>
                            <view v-if="cell.dot" class="cal__dot" :class="`cal__dot--${cell.dot}`" />
                        </view>
                    </template>
                </view>
            </view>
        </template>

        <view class="cal__legend">
            <view class="cal__legend-item">
                <view class="cal__legend-dot" />
                <text class="cal__legend-text">可约</text>
            </view>
            <view class="cal__legend-item">
                <view class="cal__legend-dot cal__legend-dot--empty" />
                <text class="cal__legend-text">已满</text>
            </view>
            <view class="cal__legend-item">
                <view class="cal__legend-dot cal__legend-dot--gold" />
                <text class="cal__legend-text">吉日</text>
            </view>
            <text v-if="showMinNote" class="cal__legend-note">最早可订 {{ minNote }}</text>
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 查档期 · 内联月历。
 *
 * 数据边界：
 *   - metas 只承载「日期属性」（吉日/农历/节假日），来自 `/schedule/monthCalendar`；
 *   - availability 只承载「已知的」某日可约状态；P0 阶段页面上只有选中日这一条，
 *     其余日期没有点就是「未知」，不能用 0 或已满补。
 *
 * 今天到 minSelectable 之前为提前期：不可点、无状态点；今天格显示「今」。
 * 月历不显示农历小字，农历只在已选日期行出现（35 个农历字会让网格密度翻倍）。
 */
import { computed, nextTick, onUnmounted, ref, watch } from 'vue'
import type { CalendarMeta, DayAvailability, DayStatus } from './types'

const props = withDefaults(
    defineProps<{
        year: number
        month: number
        /** YYYY-MM-DD */
        selected: string
        /** YYYY-MM-DD */
        today: string
        /** YYYY-MM-DD，最早可订日 */
        minSelectable: string
        /** YYYY-MM-DD，最晚可查日（年月快切的 end） */
        maxDate: string
        metas: Record<string, CalendarMeta>
        availability?: Record<string, DayAvailability>
        loading: boolean
        /** 整月角标刷新中：只加进度线，不清空已有点 */
        statusLoading?: boolean
        error: boolean
    }>(),
    {
        metas: () => ({}),
        availability: () => ({}),
        loading: false,
        statusLoading: false,
        error: false
    }
)

const emit = defineEmits<{
    (e: 'prev'): void
    (e: 'next'): void
    (e: 'today'): void
    (e: 'select', date: string): void
    (e: 'jump', year: number, month: number): void
    (e: 'retry'): void
}>()

const WEEK = ['日', '一', '二', '三', '四', '五', '六']

interface Cell {
    key: string
    date: string
    day: number | ''
    classes: Record<string, boolean>
    aria: string
    disabled: boolean
    isToday: boolean
    isLucky: boolean
    dot: '' | DayStatus
}

function pad(n: number) {
    return String(n).padStart(2, '0')
}

function onMonthChange(e: any) {
    const value = String(e?.detail?.value || '')
    const hit = /^(\d{4})-(\d{2})/.exec(value)
    if (!hit) return
    const year = Number(hit[1])
    const month = Number(hit[2])
    if (!year || month < 1 || month > 12) return
    if (year === props.year && month === props.month) return
    emit('jump', year, month)
}

const hasData = computed(() => Object.keys(props.metas).length > 0)

const monthValue = computed(() => `${props.year}-${pad(props.month)}`)

const firstWeekday = computed(() => new Date(props.year, props.month - 1, 1).getDay())
const daysInMonth = computed(() => new Date(props.year, props.month, 0).getDate())

const cells = computed<Cell[]>(() => {
    const list: Cell[] = []
    const total = Math.ceil((firstWeekday.value + daysInMonth.value) / 7) * 7

    for (let i = 0; i < total; i++) {
        const day = i - firstWeekday.value + 1

        if (day < 1 || day > daysInMonth.value) {
            list.push({
                key: `blank-${i}`,
                date: '',
                day: '',
                classes: { 'is-blank': true },
                aria: '',
                disabled: true,
                isToday: false,
                isLucky: false,
                dot: ''
            })
            continue
        }

        const date = `${props.year}-${pad(props.month)}-${pad(day)}`
        const meta = props.metas[date]
        const known = props.availability[date]
        const isPast = date < props.today
        const isLocked = date >= props.today && date < props.minSelectable
        const isToday = date === props.today
        const isSelected = date === props.selected
        const isLucky = !!meta?.isLucky
        const weekday = (firstWeekday.value + day - 1) % 7
        const disabled = isPast || isLocked
        // 过去日 / 提前期锁定日不画状态点：它们本来就不可点，画点会被误读成「能约」
        const dot: '' | DayStatus = disabled ? '' : known?.status ?? ''

        const classes: Record<string, boolean> = {
            'is-past': isPast,
            'is-locked': isLocked,
            'is-today': isToday,
            'is-selected': isSelected,
            'is-lucky': isLucky,
            'is-full': dot === 'full',
            'is-limited': dot === 'limited',
            'is-available': dot === 'available'
        }

        const bits = [`${props.month}月${day}日 周${WEEK[weekday]}`]
        if (isPast) bits.push('已过，不可选')
        else if (isLocked) bits.push('提前预订期内，不可选')
        else if (dot === 'full') bits.push('当日已满')
        else if (dot === 'limited') bits.push('仅 1 位可约')
        else if (dot === 'available') bits.push(`${known?.count ?? ''} 位可约`)
        if (isLucky) bits.push('吉日')
        if (isSelected) bits.push('已选中')

        list.push({
            key: date,
            date,
            day,
            classes,
            aria: bits.join('，'),
            disabled,
            isToday,
            isLucky,
            dot
        })
    }

    return list
})

/** 当月是否含提前期锁定日：决定「最早可订」注记是否出现 */
const hasLocked = computed(() => cells.value.some((c) => c.classes['is-locked']))

/* ---- 翻月位移淡入（§4.3）：不加 3D 翻转，只做 24rpx 横移 + 淡入 ---- */
const gridAnimClass = ref('')
let gridTimer: ReturnType<typeof setTimeout> | null = null

watch(
    () => [props.year, props.month],
    (next, prev) => {
        const [y, m] = next
        const [py, pm] = prev || []
        if (y === py && m === pm) return
        const dir = y * 12 + m > py * 12 + pm ? 'next' : 'prev'
        if (gridTimer) {
            clearTimeout(gridTimer)
            gridTimer = null
        }
        gridAnimClass.value = ''
        nextTick(() => {
            gridAnimClass.value = `is-enter-${dir}`
            gridTimer = setTimeout(() => {
                gridAnimClass.value = ''
                gridTimer = null
            }, 280)
        })
    }
)

onUnmounted(() => {
    if (gridTimer) clearTimeout(gridTimer)
})

const minNote = computed(() => {
    const [, m, d] = props.minSelectable.split('-')
    return `${Number(m)}.${Number(d)}`
})

const showMinNote = computed(() => hasLocked.value && !!props.minSelectable)

function onCell(cell: Cell) {
    if (!cell.date || cell.disabled) return
    emit('select', cell.date)
}
</script>

<style lang="scss" scoped>
.cal {
    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 88rpx;
    }

    &__month-picker {
        display: flex;
        align-items: center;
        flex: none;
    }

    &__month {
        display: flex;
        align-items: baseline;
        gap: 12rpx;
        min-width: 0;
    }

    &__month-arrow {
        align-self: center;
        margin-left: 2rpx;
        color: var(--c-ink-3);
    }

    &__year {
        font-family: var(--font-serif);
        font-size: var(--fs-20);
        letter-spacing: 0.16em;
        color: var(--c-ink-3);
    }

    &__month-text {
        font-size: var(--fs-33);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        line-height: var(--lh-none);
    }

    &__nav {
        display: flex;
        align-items: center;
        gap: 0;
        flex: none;
    }

    /* 视觉 56rpx，热区 88rpx：负 margin 让按钮只占 56rpx 布局宽，
       相邻两颗热区首尾相接，翻月不会点空 */
    &__nav-btn {
        width: 88rpx;
        height: 88rpx;
        margin: 0 -16rpx;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__nav-ic {
        color: var(--c-ink-2);
    }

    &__today {
        height: 88rpx;
        line-height: 88rpx;
        padding: 0 12rpx;
        font-size: var(--fs-21);
        color: var(--c-brand-text);
    }

    &__week {
        display: flex;
        padding: 12rpx 0 4rpx;
    }

    &__week-text {
        width: 14.2857%;
        flex: 0 0 14.2857%;
        text-align: center;
        font-size: var(--fs-21);
        color: var(--c-ink-3);
    }

    &__progress {
        height: 2rpx;
        margin-bottom: 4rpx;
        background: var(--c-brand);
    }

    &__grid {
        display: flex;
        flex-wrap: wrap;
        position: relative;

        &.is-loading {
            opacity: 0.4;
        }

        /* 翻月：仅横移 + 淡入，不做 3D 翻转（信息密度高，翻转会晕） */
        &.is-enter-next {
            animation: cal-grid-next 240ms var(--ease-out);
        }

        &.is-enter-prev {
            animation: cal-grid-prev 240ms var(--ease-out);
        }
    }

    &__cell {
        width: 14.2857%;
        flex: 0 0 14.2857%;
        height: 92rpx;
        padding-top: 6rpx;
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;

        &.is-blank {
            pointer-events: none;
        }

        &.is-past {
            opacity: 0.35;
        }

        &.is-locked .cal__day,
        &.is-locked .cal__lucky {
            opacity: 0.45;
        }

        &.is-today:not(.is-locked) .cal__day {
            box-shadow: inset 0 0 0 1rpx var(--c-ink);
        }

        &.is-selected .cal__day {
            background: var(--c-active-bg);
            box-shadow: inset 0 0 0 1rpx var(--c-active-border);
            /* 选中日弹性放大：0.84 → 1.08 → 1（320ms） */
            animation: cal-day-pop 320ms var(--ease-spring);
        }

        &.is-selected .cal__day-num {
            color: var(--c-active-fg);
        }

        &.is-selected.is-today:not(.is-locked) .cal__day {
            box-shadow: 0 0 0 1rpx var(--c-surface), 0 0 0 2rpx var(--c-active-border);
        }

        &.is-full .cal__day-num {
            color: var(--c-ink-3);
            text-decoration: line-through;
        }

        &.is-selected.is-full .cal__day {
            background: transparent;
            box-shadow: inset 0 0 0 1rpx var(--c-ink);
        }

        &.is-selected.is-full .cal__day-num {
            color: var(--c-ink);
            text-decoration: line-through;
        }
    }

    &__day {
        width: 62rpx;
        height: 62rpx;
        flex: none;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-pill);
        color: var(--c-ink);
    }

    &__day-num {
        font-family: var(--font-serif);
        font-size: var(--fs-29);
        font-weight: var(--fw-medium);
        line-height: var(--lh-none);
        color: var(--c-ink);
    }

    &__sub {
        height: 20rpx;
        margin-top: 4rpx;
        flex: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4rpx;
    }

    &__today-tag {
        font-size: var(--fs-16);
        line-height: var(--lh-none);
        color: var(--c-ink-3);
    }

    &__lucky {
        position: absolute;
        top: 6rpx;
        right: 10rpx;
        width: 8rpx;
        height: 8rpx;
        border-radius: var(--r-pill);
        background: var(--c-brand);
    }

    &__dot {
        width: 8rpx;
        height: 8rpx;
        flex: none;
        border-radius: var(--r-pill);
        background: var(--c-success);

        &--limited {
            background: var(--c-warning);
        }

        &--full {
            background: transparent;
            border: 1rpx solid var(--c-ink-3);
        }
    }

    &__sk {
        width: 48rpx;
        height: 28rpx;
        margin-top: 17rpx;
        border-radius: var(--r-xs);
        background: var(--c-surface-2);
        animation: calShimmer 1.2s ease-in-out infinite;
    }

    &__error {
        margin: 12rpx 0;
        padding: 32rpx 24rpx;
        border: 1rpx dashed var(--c-control-border);
        border-radius: var(--r-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16rpx;
    }

    &__error-text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__error-link {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
    }

    &__legend {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12rpx 24rpx;
        padding: 16rpx 0 20rpx;
    }

    &__legend-item {
        display: flex;
        align-items: center;
        gap: 8rpx;
    }

    &__legend-dot {
        width: 10rpx;
        height: 10rpx;
        border-radius: var(--r-pill);
        background: var(--c-success);

        &--empty {
            background: transparent;
            border: 1rpx solid var(--c-ink-3);
        }

        &--gold {
            width: 8rpx;
            height: 8rpx;
            background: var(--c-brand);
        }
    }

    &__legend-text {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }

    &__legend-note {
        margin-left: auto;
        font-size: var(--fs-18);
        color: var(--c-ink-3);
        white-space: nowrap;
    }
}


@keyframes calShimmer {
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

@keyframes cal-day-pop {
    0% {
        transform: scale(0.84);
    }
    55% {
        transform: scale(1.08);
    }
    100% {
        transform: scale(1);
    }
}

@keyframes cal-grid-next {
    from {
        opacity: 0.35;
        transform: translate3d(24rpx, 0, 0);
    }
    to {
        opacity: 1;
        transform: translate3d(0, 0, 0);
    }
}

@keyframes cal-grid-prev {
    from {
        opacity: 0.35;
        transform: translate3d(-24rpx, 0, 0);
    }
    to {
        opacity: 1;
        transform: translate3d(0, 0, 0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .cal__sk {
        animation: none;
    }
    .cal__cell.is-selected .cal__day,
    .cal__grid.is-enter-next,
    .cal__grid.is-enter-prev {
        animation: none;
    }
}
</style>
