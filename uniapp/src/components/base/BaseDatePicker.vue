<template>
    <view class="base-date-picker" :class="{ 'base-date-picker--disabled': disabled }" @click="handleClick">
        <text v-if="label" class="base-date-picker__label">{{ label }}</text>
        <view class="base-date-picker__display">
            <view class="base-date-picker__copy">
                <text v-if="displayText" class="base-date-picker__text">{{ displayText }}</text>
                <text v-else class="base-date-picker__placeholder">{{ placeholder }}</text>
            </view>
            <BaseIcon name="calendar" size="32" color="var(--wm-color-champagne, #D9BE82)" />
        </view>

        <BaseDateTimePicker
            v-model="innerValue"
            v-model:open="show"
            :mode="mode"
            :format="format"
            :min-time="minTimeText"
            :max-time="maxTimeText"
            @confirm="handleConfirm"
            @cancel="handleCancel"
        />
    </view>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import BaseIcon from './BaseIcon.vue'
import BaseDateTimePicker from './BaseDateTimePicker.vue'

type DateTimeMode = 'year' | 'yearmonth' | 'date' | 'datetime' | 'time' | 'datetimeNoSecond' | 'timeNoSecond'

interface Props {
    modelValue?: string | number
    label?: string
    placeholder?: string
    mode?: DateTimeMode
    format?: string
    defaultValue?: string | number
    minDate?: string | number
    maxDate?: string | number
    disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    label: '',
    placeholder: '请选择日期',
    mode: 'date',
    format: 'YYYY-MM-DD',
    defaultValue: '',
    minDate: '',
    maxDate: '',
    disabled: false
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: string | number): void
    (event: 'confirm', value: string | number): void
    (event: 'cancel'): void
}>()

const show = ref(false)
const innerValue = ref(String(props.modelValue || props.defaultValue || ''))
const displayText = computed(() => (props.modelValue ? formatDate(props.modelValue) : ''))
const minTimeText = computed(() => String(props.minDate || ''))
const maxTimeText = computed(() => String(props.maxDate || ''))

watch(
    () => props.modelValue,
    (value) => {
        innerValue.value = String(value || props.defaultValue || '')
    }
)

const formatDate = (value: string | number): string => {
    if (!value) return ''
    const date = new Date(String(value).replace(/-/g, '/'))
    if (Number.isNaN(date.getTime())) return String(value)
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    const hours = String(date.getHours()).padStart(2, '0')
    const minutes = String(date.getMinutes()).padStart(2, '0')
    return props.format
        .replace('YYYY', String(year))
        .replace('MM', month)
        .replace('DD', day)
        .replace('HH', hours)
        .replace('mm', minutes)
}

const handleClick = () => {
    if (props.disabled) return
    show.value = true
}

const handleConfirm = (value: string) => {
    emit('update:modelValue', value)
    emit('confirm', value)
    show.value = false
}

const handleCancel = () => {
    emit('cancel')
    show.value = false
}
</script>

<script lang="ts">
export default {
    name: 'BaseDatePicker',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-date-picker {
    display: flex;
    flex-direction: column;
    gap: 12rpx;

    &__label {
        font-size: 24rpx;
        font-weight: 900;
        color: var(--wm-text-secondary, #665E52);
    }

    &__display {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 96rpx;
        padding: 0 28rpx;
        background: var(--wm-color-bg-card, #FFFDF8);
        border-radius: var(--wm-radius-control, 44rpx);
        border: 1rpx solid var(--wm-color-border, #D8C9AD);
        box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));
    }

    &__copy {
        flex: 1;
        min-width: 0;
    }

    &__text,
    &__placeholder {
        font-size: 28rpx;
        font-weight: 800;
        line-height: 1.2;
    }

    &__text {
        color: var(--wm-text-primary, #191713);
    }

    &__placeholder {
        color: var(--wm-text-tertiary, #8A806F);
    }

    &--disabled {
        opacity: 0.58;
    }
}
</style>
