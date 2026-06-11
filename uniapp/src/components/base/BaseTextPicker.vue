<template>
    <tn-picker
        :model-value="modelValue"
        :open="open"
        :data="data"
        :label-key="labelKey"
        :value-key="valueKey"
        :children-key="childrenKey"
        :cancel-text="cancelText"
        :confirm-text="confirmText"
        :cancel-color="cancelColor"
        :confirm-color="confirmColor"
        :z-index="zIndex"
        :indicator-height="indicatorHeight"
        @update:model-value="emit('update:modelValue', $event)"
        @update:open="emit('update:open', $event)"
        @confirm="handleConfirm"
        @change="emit('change', $event)"
        @cancel="emit('cancel')"
        @close="emit('close')"
    />
</template>

<script setup lang="ts">
type PickerValue = string | number | Array<string | number>
type PickerData = Array<string | number | Record<string, any>> | Array<Array<string | number | Record<string, any>>>

interface Props {
    modelValue?: PickerValue
    open?: boolean
    data?: PickerData
    labelKey?: string
    valueKey?: string
    childrenKey?: string
    cancelText?: string
    confirmText?: string
    cancelColor?: string
    confirmColor?: string
    indicatorHeight?: number
    zIndex?: number
}

withDefaults(defineProps<Props>(), {
    modelValue: '',
    open: false,
    data: () => [],
    labelKey: 'label',
    valueKey: 'value',
    childrenKey: 'children',
    cancelText: '取消',
    confirmText: '确认',
    cancelColor: '#6B625A',
    confirmColor: '#D4916E',
    indicatorHeight: 44,
    zIndex: 20080
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: PickerValue): void
    (event: 'update:open', value: boolean): void
    (event: 'confirm', value: PickerValue, item?: any): void
    (event: 'change', value: PickerValue): void
    (event: 'cancel'): void
    (event: 'close'): void
}>()

const handleConfirm = (value: PickerValue, item?: any) => {
    emit('update:modelValue', value)
    emit('confirm', value, item)
}
</script>

<script lang="ts">
export default {
    name: 'BaseTextPicker',
    options: {
        virtualHost: true
    }
}
</script>
