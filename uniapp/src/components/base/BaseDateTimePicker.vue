<template>
    <tn-date-time-picker
        :model-value="modelValue"
        :open="open"
        :mode="mode"
        :format="format"
        :min-time="minTime"
        :max-time="maxTime"
        :init-current-date-time="initCurrentDateTime"
        :cancel-text="cancelText"
        :confirm-text="confirmText"
        :cancel-color="cancelColor"
        :confirm-color="confirmColor"
        :z-index="zIndex"
        @update:model-value="emit('update:modelValue', $event)"
        @update:open="emit('update:open', $event)"
        @confirm="handleConfirm"
        @cancel="emit('cancel')"
        @close="emit('close')"
    />
</template>

<script setup lang="ts">
type DateTimeMode = 'year' | 'yearmonth' | 'date' | 'datetime' | 'time' | 'datetimeNoSecond' | 'timeNoSecond'

interface Props {
    modelValue?: string
    open?: boolean
    mode?: DateTimeMode
    format?: string
    minTime?: string
    maxTime?: string
    initCurrentDateTime?: boolean
    cancelText?: string
    confirmText?: string
    cancelColor?: string
    confirmColor?: string
    zIndex?: number
}

withDefaults(defineProps<Props>(), {
    modelValue: '',
    open: false,
    mode: 'date',
    format: 'YYYY-MM-DD',
    minTime: '',
    maxTime: '',
    initCurrentDateTime: true,
    cancelText: '取消',
    confirmText: '确认',
    cancelColor: '#6B625A',
    confirmColor: '#D4916E',
    zIndex: 20080
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: string): void
    (event: 'update:open', value: boolean): void
    (event: 'confirm', value: string): void
    (event: 'cancel'): void
    (event: 'close'): void
}>()

const handleConfirm = (value: string) => {
    emit('update:modelValue', value)
    emit('confirm', value)
}
</script>

<script lang="ts">
export default {
    name: 'BaseDateTimePicker',
    options: {
        virtualHost: true
    }
}
</script>
