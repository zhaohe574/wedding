<template>
    <view
        class="field"
        :class="[`field--${layout}`, { 'is-error': !!error, 'is-disabled': disabled }]"
    >
        <text v-if="label" class="field__label">
            {{ label }}<text v-if="required" class="field__req">*</text>
        </text>
        <view class="field__control">
            <input
                class="field__input"
                :value="modelValue"
                :type="type"
                :maxlength="maxlength"
                :placeholder="placeholder"
                :disabled="disabled"
                :focus="focus"
                :confirm-type="confirmType"
                :cursor-spacing="cursorSpacing"
                placeholder-class="field__ph"
                @input="onInput"
                @blur="onBlur"
                @focus="onFocus"
                @confirm="emit('confirm')"
            />
            <slot name="suffix" />
        </view>
        <text v-if="error" class="field__err">{{ error }}</text>
        <text v-else-if="hint" class="field__hint">{{ hint }}</text>
    </view>
</template>

<script setup lang="ts">
/**
 * 表单输入基座。
 *
 * 约定：
 *   · label 常驻，不用 placeholder 代替标签
 *   · 错误贴字段下方，不集中到页尾
 *   · 必填加星号；禁用态由 --c-disabled 承担，不靠 opacity
 *   · row 布局用于「标签左、输入右」，stack 用于整行输入
 */
const props = withDefaults(
    defineProps<{
        modelValue: string | number
        label?: string
        placeholder?: string
        type?: 'text' | 'number' | 'idcard' | 'digit'
        maxlength?: number
        error?: string
        hint?: string
        required?: boolean
        disabled?: boolean
        focus?: boolean
        layout?: 'row' | 'stack'
        confirmType?: 'done' | 'search' | 'next' | 'go' | 'send'
        /** 输入框与键盘的距离（px），避免被软键盘顶住 */
        cursorSpacing?: number
    }>(),
    {
        label: '',
        placeholder: '',
        type: 'text',
        maxlength: 140,
        error: '',
        hint: '',
        required: false,
        disabled: false,
        focus: false,
        layout: 'row',
        confirmType: 'done',
        cursorSpacing: 120
    }
)

const emit = defineEmits<{
    (e: 'update:modelValue', v: string): void
    (e: 'blur', v: string): void
    (e: 'focus'): void
    (e: 'confirm'): void
}>()

function onInput(e: any) {
    emit('update:modelValue', e?.detail?.value ?? '')
}

function onBlur(e: any) {
    emit('blur', e?.detail?.value ?? props.modelValue)
}

function onFocus() {
    emit('focus')
}
</script>

<style lang="scss" scoped>
.field {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    min-height: 88rpx;
    border-bottom: 1rpx solid var(--c-control-border);

    &--stack {
        flex-direction: column;
        align-items: stretch;
        gap: 8rpx;
        padding: var(--sp-2) 0;
    }

    &__label {
        flex-shrink: 0;
        width: 140rpx;
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &--stack &__label {
        width: auto;
    }

    &__req {
        margin-left: 4rpx;
        color: var(--c-danger);
    }

    &__control {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__input {
        flex: 1;
        min-width: 0;
        height: 88rpx;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__err {
        display: block;
        width: 100%;
        padding: 6rpx 0 0 calc(140rpx + var(--sp-3));
        font-size: var(--fs-xs);
        color: var(--c-danger);
        line-height: 1.5;
    }

    &--stack &__err {
        padding-left: 0;
    }

    &__hint {
        display: block;
        width: 100%;
        padding: 6rpx 0 0 calc(140rpx + var(--sp-3));
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: 1.5;
    }

    &--stack &__hint {
        padding-left: 0;
    }

    &.is-error {
        border-bottom-color: var(--c-danger);
    }

    &.is-disabled {
        opacity: 0.6;
    }
}
</style>
