<template>
    <BaseCard variant="surface" class="base-schedule-calendar" title="10 月档期" description="点击日期可切换状态">
        <view class="base-schedule-calendar__week">
            <text v-for="week in weeks" :key="week" class="base-schedule-calendar__week-text">{{ week }}</text>
        </view>
        <view class="base-schedule-calendar__grid">
            <BaseDateCell
                v-for="day in days"
                :key="day.day"
                :day="day.day"
                :label="day.label"
                :state="day.state"
                :selected="day.day === modelValue"
                @click="handleSelect(day)"
            />
        </view>
    </BaseCard>
</template>

<script setup lang="ts">
import BaseCard from './BaseCard.vue'
import BaseDateCell from './BaseDateCell.vue'

interface CalendarDay {
    day: number | string
    label?: string
    state?: 'default' | 'selected' | 'booked' | 'busy' | 'disabled' | 'today'
}

interface Props {
    days: CalendarDay[]
    modelValue?: number | string
}

const props = withDefaults(defineProps<Props>(), {
    days: () => [],
    modelValue: ''
})

const emit = defineEmits<{
    (event: 'select', day: number | string): void
    (event: 'update:modelValue', day: number | string): void
}>()

const handleSelect = (day: CalendarDay) => {
    if (day.state === 'disabled') return
    emit('update:modelValue', day.day)
    emit('select', day.day)
}

const weeks = ['一', '二', '三', '四', '五', '六', '日']
</script>

<script lang="ts">
export default {
    name: 'BaseScheduleCalendar',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-schedule-calendar {
    &__week,
    &__grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 8rpx;
    }

    &__week {
        margin-bottom: 12rpx;
    }

    &__week-text {
        text-align: center;
        font-size: 20rpx;
        font-weight: 900;
        color: var(--wm-text-secondary, #665E52);
    }
}
</style>
