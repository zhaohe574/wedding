<template>
    <!-- 日期 + 地区选择：弹层内一次选完，内容整块可滚，不会被裁切 -->
    <tn-popup
        :model-value="modelValue"
        open-direction="bottom"
        :safe-area-inset-bottom="true"
        :radius="28"
        @update:model-value="emit('update:modelValue', $event)"
    >
        <view class="pop pop--picker">
            <view class="pop__head">
                <text class="pop__title">服务日期与地区</text>
                <view
                    class="pop__close"
                    hover-class="is-pressed"
                    aria-label="关闭"
                    @tap="emit('update:modelValue', false)"
                >
                    <tn-icon name="close" :size="32" />
                </view>
            </view>

            <scroll-view scroll-y class="pop__scroll">
                <!-- 日历：自绘月历，块高固定，不依赖组件测量 -->
                <view class="dcal">
                    <view class="dcal__nav">
                        <view
                            class="dcal__nav-btn"
                            :class="{ 'is-disabled': !canShiftMonth(-1) }"
                            hover-class="is-pressed"
                            aria-role="button"
                            aria-label="上个月"
                            @tap="shiftMonth(-1)"
                        >
                            <tn-icon name="left" :size="28" />
                        </view>
                        <text class="dcal__nav-title">{{ pickerMonthLabel }}</text>
                        <view
                            class="dcal__nav-btn"
                            :class="{ 'is-disabled': !canShiftMonth(1) }"
                            hover-class="is-pressed"
                            aria-role="button"
                            aria-label="下个月"
                            @tap="shiftMonth(1)"
                        >
                            <tn-icon name="right" :size="28" />
                        </view>
                    </view>

                    <view class="dcal__week">
                        <text v-for="w in WEEK_LABELS" :key="w" class="dcal__week-item">{{
                            w
                        }}</text>
                    </view>

                    <view class="dcal__grid">
                        <view
                            v-for="cell in calendarCells"
                            :key="cell.key"
                            class="dcal__cell"
                            :class="{
                                'is-blank': !cell.day,
                                'is-disabled': cell.disabled,
                                'is-on': cell.selected,
                                'is-today': cell.today
                            }"
                            @tap="pickDay(cell)"
                        >
                            <text v-if="cell.day" class="dcal__day">{{ cell.day }}</text>
                        </view>
                    </view>
                </view>

                <!-- 地区：与查档期页共用 components/region-columns（三栏联动、同一套选中态） -->
                <view class="pop__region">
                    <region-columns
                        :key="pickerKey"
                        v-model="pickerRegion"
                        height="288rpx"
                        title="服务地区"
                    />
                </view>
            </scroll-view>

            <view class="pop__foot">
                <text v-if="pickerTip" class="pop__tip">{{ pickerTip }}</text>
                <base-button
                    :text="pickerConfirmText"
                    variant="ink"
                    size="md"
                    :disabled="!pickerConfirmable"
                    @click="confirmPicker"
                />
            </view>
        </view>
    </tn-popup>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { parseDateValue } from '@shared/utils/format'
import { hasRegionContext, type RegionContext } from '@/utils/region'
import { dateKeyOf } from '../booking-helpers'

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

const pickerConfirmable = computed(() => !!pickerDate.value && !!pickerRegion.value?.district_code)

const pickerConfirmText = computed(() => {
    if (!pickerDate.value) return '请选择日期'
    if (!pickerRegion.value) return '请选择地区'
    return '确定'
})

const pickerTip = computed(() => {
    if (!pickerDate.value) return '请先在上方日历选择服务日期'
    if (!pickerRegion.value) return '请选择省 / 市 / 区，价格按区县计算'
    return ''
})

function syncDraft() {
    pickerDate.value = props.date || ''
    pickerRegion.value = hasRegionContext(props.region) ? { ...props.region } : null
    const base = parseDateValue(pickerDate.value) || new Date()
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

function confirmPicker() {
    if (!pickerConfirmable.value || !pickerRegion.value) return
    emit('confirm', {
        date: pickerDate.value,
        region: { ...pickerRegion.value }
    })
    emit('update:modelValue', false)
}
</script>

<style lang="scss" scoped>
.pop {
    background: var(--c-surface);
    border-radius: var(--r-md) var(--r-md) 0 0;
    overflow: hidden;

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 104rpx;
        padding: 0 var(--sp-2) 0 var(--sp-4);
        border-bottom: 1rpx solid var(--c-line);
        flex: none;
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

    &__foot {
        display: flex;
        flex-direction: column;
        gap: 12rpx;
        padding: var(--sp-2) var(--sp-4) var(--sp-4);
        border-top: 1rpx solid var(--c-line);
        flex: none;
    }

    &__tip {
        font-size: var(--fs-18);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}

.pop--picker {
    display: flex;
    flex-direction: column;
    max-height: 82vh;
}

.pop__scroll {
    flex: none;
    max-height: 62vh;
}

.dcal {
    padding: var(--sp-3) var(--sp-4) 0;
}

.dcal__nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
}

.dcal__nav-btn {
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

.dcal__nav-title {
    flex: 1;
    text-align: center;
    font-size: var(--fs-24);
    font-weight: var(--fw-semibold);
    color: var(--c-ink);
}

.dcal__week {
    display: flex;
    margin-top: var(--sp-2);
}

.dcal__week-item {
    flex: 1;
    text-align: center;
    font-size: var(--fs-18);
    color: var(--c-ink-3);
}

.dcal__grid {
    display: flex;
    flex-wrap: wrap;
    margin-top: 8rpx;
}

.dcal__cell {
    width: calc(100% / 7);
    height: 64rpx;
    display: flex;
    align-items: center;
    justify-content: center;

    &.is-blank {
        visibility: hidden;
    }

    &.is-disabled .dcal__day {
        color: var(--c-control-border);
        text-decoration: line-through;
    }

    &.is-today .dcal__day {
        color: var(--c-brand-text);
    }

    &.is-on {
        .dcal__day {
            width: 56rpx;
            height: 56rpx;
            line-height: 56rpx;
            border-radius: var(--r-pill);
            background: var(--c-active-bg);
            color: var(--c-active-fg);
            box-shadow: inset 0 0 0 1rpx var(--c-active-border);
        }
    }
}

.dcal__day {
    display: block;
    width: 56rpx;
    height: 56rpx;
    line-height: 56rpx;
    text-align: center;
    font-size: var(--fs-22);
    color: var(--c-ink);
}

.pop__region {
    padding: var(--sp-3) var(--sp-4) var(--sp-4);
}
</style>
