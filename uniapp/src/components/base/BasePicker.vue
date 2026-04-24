<template>
    <view class="base-picker" @click="handleClick">
        <view class="base-picker__display">
            <text v-if="displayText" class="base-picker__text">{{ displayText }}</text>
            <text v-else class="base-picker__placeholder">{{ placeholder }}</text>
            <tn-icon name="arrow-down" size="32" color="#9A9388" />
        </view>

        <tn-picker
            v-model="show"
            :columns="columns"
            :default-value="defaultValue"
            @confirm="handleConfirm"
            @cancel="handleCancel"
        />
    </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface Props {
    modelValue?: any
    placeholder?: string
    columns?: any[]
    defaultValue?: any[]
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    placeholder: '请选择',
    columns: () => [],
    defaultValue: () => []
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: any): void
    (event: 'confirm', value: any): void
    (event: 'cancel'): void
}>()

const show = ref(false)

// 计算显示文本
const displayText = computed(() => {
    if (props.modelValue) {
        // 如果是对象，尝试获取label或text属性
        if (typeof props.modelValue === 'object') {
            return props.modelValue.label || props.modelValue.text || props.modelValue.name || ''
        }
        return String(props.modelValue)
    }
    return ''
})

// 处理点击事件
const handleClick = () => {
    show.value = true
}

// 处理确认事件
const handleConfirm = (value: any) => {
    emit('update:modelValue', value)
    emit('confirm', value)
    show.value = false
}

// 处理取消事件
const handleCancel = () => {
    emit('cancel')
    show.value = false
}
</script>

<script lang="ts">
export default {
    name: 'BasePicker',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-picker {
    &__display {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 88rpx;
        padding: 0 var(--wm-space-card-padding, 20rpx);
        background: var(--wm-color-bg-soft, #ffffff);
        border-radius: var(--wm-radius-control, 18rpx);
        border: 1rpx solid var(--wm-color-border, #e7e2d6);
        transition: all var(--wm-motion-base, 220ms) ease;
        cursor: pointer;

        &:active {
            background: #ffffff;
            border-color: var(--wm-color-border-strong, #d8c28a);
        }
    }

    &__text {
        font-size: 28rpx;
        color: var(--wm-text-primary, #111111);
    }

    &__placeholder {
        font-size: 28rpx;
        color: var(--wm-text-tertiary, #9a9388);
    }
}
</style>
