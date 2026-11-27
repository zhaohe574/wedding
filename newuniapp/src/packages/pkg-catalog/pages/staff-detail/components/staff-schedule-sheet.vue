<template>
    <!--
        档期与地区切换：直接进入详情页时，用户必须能改这两个条件。
        日期只决定「哪天可约」，地区决定「按哪算价、有哪些可约人员」，两者一起提交才刷新。
    -->
    <snap-sheet
        :model-value="modelValue"
        title="档期与地区"
        :enable-full="true"
        :has-foot="true"
        @update:model-value="emit('update:modelValue', $event)"
    >
        <view class="scal">
            <view class="scal__nav">
                <view
                    class="scal__btn press-spring"
                    :class="{ 'is-disabled': !canShiftMonth(-1) }"
                    hover-class="is-pressed"
                    aria-role="button"
                    aria-label="上个月"
                    @tap="shiftMonth(-1)"
                >
                    <tn-icon name="left" :size="28" />
                </view>
                <text class="scal__title t-num">{{ pickerMonthLabel }}</text>
                <view
                    class="scal__btn press-spring"
                    :class="{ 'is-disabled': !canShiftMonth(1) }"
                    hover-class="is-pressed"
                    aria-role="button"
                    aria-label="下个月"
                    @tap="shiftMonth(1)"
                >
                    <tn-icon name="right" :size="28" />
                </view>
            </view>

            <view class="scal__week">
                <text v-for="w in WEEK_LABELS" :key="w" class="scal__week-i">{{ w }}</text>
            </view>

            <view class="scal__grid">
                <view
                    v-for="cell in calendarCells"
                    :key="cell.key"
                    class="scal__cell"
                    :class="{
                        'is-blank': !cell.day,
                        'is-disabled': cell.disabled,
                        'is-on': cell.selected,
                        'is-today': cell.today
                    }"
                    hover-class="is-pressed"
                    @tap="pickDay(cell)"
                >
                    <text v-if="cell.day" class="scal__day">{{ cell.day }}</text>
                </view>
            </view>
        </view>

        <view class="region-block">
            <region-columns
                :key="pickerKey"
                v-model="pickerRegion"
                height="288rpx"
                title="服务地区"
            />
        </view>

        <template #foot>
            <text v-if="schedTip" class="sched-tip">{{ schedTip }}</text>
            <base-button
                :text="schedConfirmText"
                variant="ink"
                size="md"
                :disabled="!schedConfirmable"
                @click="confirmSchedule"
            />
        </template>
    </snap-sheet>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { parseDateValue } from '@shared/utils/format'
import { hasRegionContext, type RegionContext } from '@/utils/region'
import { haptic } from '@/utils/haptics'
import { dateKeyOf, tomorrowKey } from '../useStaffDetail'

const WEEK_LABELS = ['日', '一', '二', '三', '四', '五', '六']

const props = defineProps<{
    modelValue: boolean
    date: string
    region: RegionContext
    minDate: string
    maxDate: string
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', visible: boolean): void
    (e: 'confirm', payload: { date: string; region: RegionContext }): void
}>()

const pickerDate = ref('')
const pickerRegion = ref<RegionContext | null>(null)
const pickerKey = ref(0)
const pickerMonth = reactive({ year: 0, month: 0 })

const todayKey = computed(() => {
    const d = new Date()
    return dateKeyOf(d.getFullYear(), d.getMonth() + 1, d.getDate())
})

const pickerMonthLabel = computed(() => `${pickerMonth.year} 年 ${pickerMonth.month} 月`)

/**
 * 自绘月历：与 booking 的日期弹层同一口径（6 行固定高度，切月不跳）。
 * 早于规则最早可约日 / 晚于最晚可约日一律禁用。
 */
const calendarCells = computed(() => {
    const { year, month } = pickerMonth
    if (!year || !month) return [] as any[]
    const startWeekday = new Date(year, month - 1, 1).getDay()
    const daysInMonth = new Date(year, month, 0).getDate()
    const min = props.minDate || todayKey.value
    const max = props.maxDate

    const cells: any[] = []
    for (let i = 0; i < startWeekday; i++) cells.push({ key: `blank-${i}`, day: 0 })
    for (let d = 1; d <= daysInMonth; d++) {
        const key = dateKeyOf(year, month, d)
        cells.push({
            key,
            day: d,
            date: key,
            disabled: (!!min && key < min) || (!!max && key > max),
            selected: key === pickerDate.value,
            today: key === todayKey.value
        })
    }
    while (cells.length % 7 !== 0) cells.push({ key: `tail-${cells.length}`, day: 0 })
    return cells
})

function canShiftMonth(delta: number) {
    if (!pickerMonth.year) return false
    const target = new Date(pickerMonth.year, pickerMonth.month - 1 + delta, 1)
    const key = dateKeyOf(target.getFullYear(), target.getMonth() + 1, 1).slice(0, 7)
    if (delta < 0 && props.minDate && key < props.minDate.slice(0, 7)) return false
    if (delta > 0 && props.maxDate && key > props.maxDate.slice(0, 7)) return false
    return true
}

function shiftMonth(delta: number) {
    if (!canShiftMonth(delta)) return
    haptic('light', 'schedule-month')
    const target = new Date(pickerMonth.year, pickerMonth.month - 1 + delta, 1)
    pickerMonth.year = target.getFullYear()
    pickerMonth.month = target.getMonth() + 1
}

function pickDay(cell: any) {
    if (!cell?.day || cell.disabled) return
    if (cell.date !== pickerDate.value) haptic('light', 'schedule-day')
    pickerDate.value = String(cell.date || '')
}

const schedConfirmable = computed(() => !!pickerDate.value && !!pickerRegion.value?.district_code)

const schedConfirmText = computed(() => {
    if (!pickerDate.value) return '请选择日期'
    if (!pickerRegion.value) return '请选择地区'
    return '查看该档期'
})

const schedTip = computed(() => {
    if (!pickerDate.value) return '请选择服务日期'
    if (!pickerRegion.value) return '请选择省 / 市 / 区，价格与可约人员按区县计算'
    return ''
})

function syncDraft() {
    let nextDate = props.date || tomorrowKey()
    if (props.minDate && nextDate < props.minDate) nextDate = props.minDate
    if (props.maxDate && nextDate > props.maxDate) nextDate = props.maxDate
    pickerDate.value = nextDate
    pickerRegion.value = hasRegionContext(props.region) ? { ...props.region } : null
    const base = parseDateValue(nextDate) || new Date()
    pickerMonth.year = base.getFullYear()
    pickerMonth.month = base.getMonth() + 1
    pickerKey.value += 1
}

watch(
    () => props.modelValue,
    (open) => {
        if (open) syncDraft()
    },
    { immediate: true }
)

function confirmSchedule() {
    if (!schedConfirmable.value || !pickerRegion.value) return
    emit('confirm', {
        date: pickerDate.value,
        region: { ...pickerRegion.value }
    })
    emit('update:modelValue', false)
}
</script>

<style lang="scss" scoped>
/* 模板里已没有 .spop 包裹节点：以下类直接挂在 snap-sheet 的 body / foot 插槽内，
   选择器必须平铺为顶层完整类名，不能再嵌在 .spop { &__x } 里（否则样式整体失效）。 */
.region-block {
    padding: var(--sp-2) 0 var(--sp-2);
}

.sched-tip {
    display: block;
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-ink-3);
    padding-bottom: 12rpx;
}

.scal {
    padding: var(--sp-1) 0 0;

    &__nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    /* 翻月热区 88rpx（§2.5）：视觉圆形仍 72rpx，外框撑满 88rpx 不吃掉日历宽度 */
    &__btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 88rpx;
        height: 88rpx;
        border-radius: var(--r-pill);
        color: var(--c-ink-2);

        &.is-disabled {
            color: var(--c-control-border);
        }
    }

    &__title {
        flex: 1;
        text-align: center;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__week {
        display: flex;
        margin-top: var(--sp-1);
    }

    &__week-i {
        flex: 1;
        text-align: center;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__grid {
        display: flex;
        flex-wrap: wrap;
        margin-top: 8rpx;
    }

    &__cell {
        width: calc(100% / 7);
        height: 72rpx;
        display: flex;
        align-items: center;
        justify-content: center;

        &.is-blank {
            visibility: hidden;
        }

        &.is-disabled .scal__day {
            color: var(--c-control-border);
            text-decoration: line-through;
        }

        &.is-today .scal__day {
            color: var(--c-brand-text);
        }

        &.is-on .scal__day {
            width: 56rpx;
            height: 56rpx;
            line-height: 56rpx;
            border-radius: var(--r-pill);
            background: var(--c-active-bg);
            color: var(--c-active-fg);
            box-shadow: inset 0 0 0 1rpx var(--c-active-border);
            /* 选中日弹性微缩→放大：从 0.82 过冲到 1，落点跟手指 */
            animation: dayPop var(--dur-release) var(--ease-spring);
        }
    }

    &__day {
        display: block;
        width: 56rpx;
        height: 56rpx;
        line-height: 56rpx;
        text-align: center;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }
}

@keyframes dayPop {
    0% {
        transform: scale(0.82);
    }
    55% {
        transform: scale(1.06);
    }
    100% {
        transform: scale(1);
    }
}

@media (prefers-reduced-motion: reduce) {
    .scal__cell.is-on .scal__day {
        animation: none;
    }
}
</style>
