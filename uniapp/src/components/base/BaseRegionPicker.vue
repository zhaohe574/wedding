<template>
    <tn-region-picker
        :model-value="modelValue"
        :open="open"
        :cancel-text="cancelText"
        :confirm-text="confirmText"
        :cancel-color="cancelColor"
        :confirm-color="confirmColor"
        :z-index="zIndex"
        @update:model-value="emit('update:modelValue', $event)"
        @update:open="emit('update:open', $event)"
        @confirm="handleConfirm"
        @change="emit('change', $event)"
        @cancel="emit('cancel')"
        @close="emit('close')"
    />
</template>

<script setup lang="ts">
type RegionValue = string[]

interface Props {
    modelValue?: RegionValue
    open?: boolean
    cancelText?: string
    confirmText?: string
    cancelColor?: string
    confirmColor?: string
    zIndex?: number
}

withDefaults(defineProps<Props>(), {
    modelValue: () => [],
    open: false,
    cancelText: '取消',
    confirmText: '确认',
    cancelColor: '#665E52',
    confirmColor: '#B8954A',
    zIndex: 20080
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: RegionValue): void
    (event: 'update:open', value: boolean): void
    (event: 'confirm', value: RegionValue, item?: any): void
    (event: 'change', value: RegionValue): void
    (event: 'cancel'): void
    (event: 'close'): void
}>()

const handleConfirm = (value: RegionValue, item?: any) => {
    emit('update:modelValue', value)
    emit('confirm', value, item)
}
</script>

<script lang="ts">
export default {
    name: 'BaseRegionPicker',
    options: {
        virtualHost: true
    }
}
</script>
