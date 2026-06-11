<template>
    <view v-if="open" class="base-multi-text-picker" :style="{ zIndex }">
        <BaseOverlayMask :show="open" :z-index="zIndex" @close="handleCancel" />
        <view class="base-multi-text-picker__panel" :style="{ zIndex: zIndex + 1 }" @touchmove.stop="stopPanelTouchMove">
            <view class="base-multi-text-picker__handle"></view>
            <view class="base-multi-text-picker__head">
                <view class="base-multi-text-picker__copy">
                    <text class="base-multi-text-picker__title">{{ title }}</text>
                    <text v-if="description" class="base-multi-text-picker__desc">{{ description }}</text>
                </view>
                <view class="base-multi-text-picker__clear" @click="clearDraft">
                    <text>清空</text>
                </view>
            </view>

            <scroll-view class="base-multi-text-picker__scroll" scroll-y enhanced>
                <view v-if="safeOptions.length" class="base-multi-text-picker__list">
                    <view
                        v-for="option in safeOptions"
                        :key="option.value"
                        class="base-multi-text-picker__chip"
                        :class="{
                            'base-multi-text-picker__chip--selected': draftValue.includes(option.value),
                            'base-multi-text-picker__chip--disabled': option.disabled
                        }"
                        @click="toggleOption(option)"
                    >
                        <text class="base-multi-text-picker__chip-text">{{ option.label }}</text>
                        <BaseIcon
                            v-if="draftValue.includes(option.value)"
                            name="check"
                            size="24"
                            color="currentColor"
                        />
                    </view>
                </view>
                <view v-else class="base-multi-text-picker__empty">
                    <BaseIcon name="empty-data" size="56" color="var(--wm-text-tertiary, #B4A89C)" />
                    <text class="base-multi-text-picker__empty-text">暂无可选内容</text>
                </view>
            </scroll-view>

            <view class="base-multi-text-picker__foot">
                <BaseButton label="取消" variant="secondary" size="md" block @click="handleCancel" />
                <BaseButton :label="confirmLabel" variant="dark" size="md" block @click="handleConfirm" />
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import BaseButton from './BaseButton.vue'
import BaseIcon from './BaseIcon.vue'
import BaseOverlayMask from './BaseOverlayMask.vue'

export interface MultiTextOption {
    label: string
    value: string
    disabled?: boolean
}

interface Props {
    modelValue?: string[]
    open?: boolean
    options?: MultiTextOption[]
    title?: string
    description?: string
    max?: number
    zIndex?: number
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => [],
    open: false,
    options: () => [],
    title: '选择服务标签',
    description: '',
    max: 0,
    zIndex: 20080
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: string[]): void
    (event: 'update:open', value: boolean): void
    (event: 'confirm', value: string[]): void
    (event: 'cancel'): void
}>()

const draftValue = ref<string[]>([])

watch(
    () => props.open,
    (open) => {
        if (open) {
            draftValue.value = [...props.modelValue]
        }
    },
    { immediate: true }
)

watch(
    () => props.modelValue,
    (value) => {
        if (!props.open) {
            draftValue.value = [...value]
        }
    }
)

const confirmLabel = computed(() =>
    draftValue.value.length ? `确认 ${draftValue.value.length} 项` : '确认选择'
)
const safeOptions = computed(() =>
    props.options
        .map((option) => ({
            label: typeof option.label === 'string' ? option.label.trim() : '',
            value: typeof option.value === 'string' ? option.value.trim() : '',
            disabled: Boolean(option.disabled)
        }))
        .filter((option) => option.label && option.value)
)

const toggleOption = (option: MultiTextOption) => {
    if (option.disabled) return
    const nextValue = draftValue.value.includes(option.value)
        ? draftValue.value.filter((value) => value !== option.value)
        : [...draftValue.value, option.value]
    if (props.max > 0 && nextValue.length > props.max) {
        uni.showToast({
            title: `最多选择 ${props.max} 项`,
            icon: 'none'
        })
        return
    }
    draftValue.value = nextValue
}

const clearDraft = () => {
    draftValue.value = []
}

const handleCancel = () => {
    draftValue.value = [...props.modelValue]
    emit('update:open', false)
    emit('cancel')
}

const handleConfirm = () => {
    const nextValue = [...draftValue.value]
    emit('update:modelValue', nextValue)
    emit('confirm', nextValue)
    emit('update:open', false)
}

const stopPanelTouchMove = () => {
    return undefined
}
</script>

<script lang="ts">
export default {
    name: 'BaseMultiTextPicker',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-multi-text-picker {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    pointer-events: none;
    overflow: hidden;
}

.base-multi-text-picker__panel {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: auto;
    max-height: 72vh;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    padding: 18rpx var(--wm-space-page-x, 32rpx) calc(32rpx + env(safe-area-inset-bottom));
    border-radius: var(--wm-radius-popup, 44rpx) var(--wm-radius-popup, 44rpx) 0 0;
    background: var(--wm-color-bg-card, #FFFDF8);
    box-shadow: 0 -28rpx 68rpx rgba(74, 43, 24, 0.2);
    overflow: hidden;
}

.base-multi-text-picker__handle {
    width: 72rpx;
    height: 8rpx;
    margin: 0 auto 24rpx;
    border-radius: 999rpx;
    background: var(--wm-color-border, #E3D7C9);
}

.base-multi-text-picker__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24rpx;
}

.base-multi-text-picker__copy {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.base-multi-text-picker__title {
    font-size: 34rpx;
    font-weight: 900;
    color: var(--wm-text-primary, #1A1A1A);
}

.base-multi-text-picker__desc {
    font-size: 22rpx;
    line-height: 1.45;
    color: var(--wm-text-secondary, #6B625A);
}

.base-multi-text-picker__clear {
    min-height: 54rpx;
    padding: 0 20rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    background: var(--wm-color-gold-soft, #F6E2D6);
    color: var(--wm-color-secondary-strong, #7D4C35);
    font-size: 22rpx;
    font-weight: 900;
}

.base-multi-text-picker__scroll {
    flex: 1;
    min-height: 180rpx;
    max-height: 48vh;
    margin-top: 30rpx;
}

.base-multi-text-picker__list {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
    padding-bottom: 4rpx;
}

.base-multi-text-picker__chip {
    min-height: 68rpx;
    padding: 0 24rpx;
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    border-radius: 999rpx;
    border: 1rpx solid var(--wm-color-border, #E3D7C9);
    background: var(--wm-color-bg-soft, #F8F1EA);
    color: var(--wm-text-secondary, #6B625A);
}

.base-multi-text-picker__chip--selected {
    background: var(--wm-color-primary, #1A1A1A);
    border-color: var(--wm-color-champagne, #E9C7A7);
    color: var(--wm-text-inverse, #FFFDF8);
}

.base-multi-text-picker__chip--disabled {
    opacity: 0.36;
}

.base-multi-text-picker__chip-text {
    font-size: 24rpx;
    font-weight: 900;
}

.base-multi-text-picker__empty {
    min-height: 180rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    color: var(--wm-text-tertiary, #B4A89C);
}

.base-multi-text-picker__empty-text {
    font-size: 24rpx;
    font-weight: 800;
}

.base-multi-text-picker__foot {
    flex-shrink: 0;
    display: grid;
    grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr);
    gap: 18rpx;
    margin-top: 34rpx;
}
</style>
