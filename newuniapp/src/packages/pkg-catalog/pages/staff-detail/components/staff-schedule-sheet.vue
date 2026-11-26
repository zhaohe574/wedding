<template>
    <!--
        档期与地区切换：直接进入详情页时，用户必须能改这两个条件。
        日期只决定「哪天可约」，地区决定「按哪算价、有哪些可约人员」，两者一起提交才刷新。
    -->
    <tn-popup
        :model-value="modelValue"
        open-direction="bottom"
        :safe-area-inset-bottom="true"
        :radius="28"
        @update:model-value="emit('update:modelValue', $event)"
    >
        <view class="spop">
            <view class="spop__head">
                <text class="spop__title">档期与地区</text>
                <view
                    class="spop__close"
                    hover-class="is-pressed"
                    aria-label="关闭"
                    @tap="emit('update:modelValue', false)"
                >
                    <tn-icon name="close" :size="32" />
                </view>
            </view>

            <scroll-view scroll-y class="spop__scroll">
                <view class="scal">
                    <view class="scal__nav">
                        <view
                            class="scal__btn"
                            :class="{ 'is-disabled': !canShiftMonth(-1) }"
                            hover-class="is-pressed"
                            aria-role="button"
                            aria-label="上个月"
                            @tap="shiftMonth(-1)"
                        >
                            <tn-icon name="left" :size="28" />
                        </view>
                        <text class="scal__title">{{ pickerMonthLabel }}</text>
                        <view
                            class="scal__btn"
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
                            @tap="pickDay(cell)"
                        >
                            <text v-if="cell.day" class="scal__day">{{ cell.day }}</text>
                        </view>
                    </view>
                </view>

                <view class="spop__region">
                    <region-columns
                        :key="pickerKey"
                        v-model="pickerRegion"
                        height="288rpx"
                        title="服务地区"
                    />
                </view>
            </scroll-view>

            <view class="spop__foot">
                <text v-if="schedTip" class="spop__tip">{{ schedTip }}</text>
                <base-button
                    :text="schedConfirmText"
                    variant="ink"
                    size="md"
                    :disabled="!schedConfirmable"
                    @click="confirmSchedule"
                />
            </view>
        </view>
    </tn-popup>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { parseDateValue } from '@shared/utils/format'
import { hasRegionContext, type RegionContext } from '@/utils/region'
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
    const target = new Date(pickerMonth.year, pickerMonth.month - 1 + delta, 1)
    pickerMonth.year = target.getFullYear()
    pickerMonth.month = target.getMonth() + 1
}

function pickDay(cell: any) {
    if (!cell?.day || cell.disabled) return
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
.spop {
    display: flex;
    flex-direction: column;
    max-height: 86vh;
    background: var(--c-surface);
    border-radius: var(--r-md) var(--r-md) 0 0;
    overflow: hidden;

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex: none;
        height: 104rpx;
        padding: 0 var(--sp-2) 0 var(--sp-4);
        border-bottom: 1rpx solid var(--c-line);
    }

    &__title {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__close {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 88rpx;
        height: 88rpx;
        color: var(--c-ink-3);
    }

    &__scroll {
        flex: none;
        max-height: 58vh;
    }

    &__region {
        padding: var(--sp-2) var(--sp-4) var(--sp-4);
    }

    &__foot {
        display: flex;
        flex-direction: column;
        flex: none;
        gap: 12rpx;
        padding: var(--sp-2) var(--sp-4) var(--sp-4);
    }

    &__tip {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}

.scal {
    padding: var(--sp-3) var(--sp-4) 0;

    &__nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 72rpx;
        height: 72rpx;
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
        margin-top: var(--sp-2);
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
        height: 64rpx;
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
        }
    }

    &__day {
        display: block;
        width: 56rpx;
        height: 56rpx;
        line-height: 56rpx;
        text-align: center;
        font-size: var(--fs-xs);
        color: var(--c-ink);
    }
}
</style>
