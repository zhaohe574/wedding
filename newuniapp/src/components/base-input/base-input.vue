<template>
    <view
        class="field"
        :class="[
            `field--${layout}`,
            {
                'is-error': !!error,
                'is-disabled': disabled,
                'is-focus': focused,
                'is-readonly': readonly
            }
        ]"
    >
        <text v-if="label" class="field__label">
            {{ label }}<text v-if="required" class="field__req">*</text>
        </text>
        <view class="field__control">
            <input
                class="field__input"
                :class="{ 't-num': isNumeric }"
                :value="modelValue"
                :type="type"
                :maxlength="maxlength"
                :placeholder="placeholder"
                :disabled="disabled"
                :focus="focus"
                :confirm-type="confirmType"
                :cursor-spacing="cursorSpacing"
                :aria-required="required ? 'true' : undefined"
                :aria-invalid="error ? 'true' : undefined"
                :aria-describedby="error || hint ? descId : undefined"
                placeholder-class="field__ph"
                @input="onInput"
                @blur="onBlur"
                @focus="onFocus"
                @confirm="emit('confirm')"
            />
            <slot name="suffix" />
        </view>
        <text v-if="error" :id="descId" class="field__err" aria-live="polite">{{ error }}</text>
        <text v-else-if="hint" :id="descId" class="field__hint">{{ hint }}</text>
    </view>
</template>

<script setup lang="ts">
/**
 * 表单输入基座。
 *
 * 约定：
 *   · label 常驻，不用 placeholder 代替标签
 *   · 错误贴字段下方，不集中到页尾
 *   · 必填加星号；禁用态由显式色承担，不靠 opacity
 *   · row 布局用于「标签左、输入右」，stack 用于整行输入
 */
import { ref, computed, getCurrentInstance } from 'vue'

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
        /** 只读态：无底线、无 placeholder、无右箭头 */
        readonly?: boolean
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
        readonly: false,
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

/** 组件实例级唯一 id，供 aria-describedby 关联错误/提示（Vue 实例 uid 全局唯一） */
const descId = `field-desc-${getCurrentInstance()?.uid ?? 0}`

const focused = ref(false)

/** 数字类输入锁定等宽：金额/数量/身份证逐位对齐，退格时不左右抖 */
const isNumeric = computed(() => ['number', 'digit', 'idcard'].includes(props.type))

function onInput(e: any) {
    emit('update:modelValue', e?.detail?.value ?? '')
}

function onBlur(e: any) {
    focused.value = false
    emit('blur', e?.detail?.value ?? props.modelValue)
}

function onFocus() {
    focused.value = true
    emit('focus')
}
</script>

<style lang="scss" scoped>
.field {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    min-height: 88rpx;
    border-bottom: 1rpx solid var(--c-control-border); /* 保持：控件边界，非 --c-line */
    flex-wrap: wrap;
    /* 聚焦线/错误色在深浅两态都平滑切换，不闪 */
    transition:
        box-shadow var(--dur-fast) var(--ease-out),
        border-color var(--dur-fast) var(--ease-out),
        background-color var(--dur-fast) var(--ease-out);

    &--stack {
        flex-direction: column;
        align-items: stretch;
        gap: var(--sp-1);
        padding: var(--sp-2) 0;
    }

    /* 新增聚焦态：2rpx inset，不占布局（视觉规范 §5.2） */
    &.is-focus {
        box-shadow: inset 0 -2rpx 0 var(--c-ink);
    }
    &.is-error {
        border-bottom-color: var(--c-danger);
    }
    &.is-disabled {
        background: var(--c-neutral-bg);
        color: var(--c-ink-3);
        opacity: 1; /* 去 opacity:.6 */
    }
    &.is-readonly {
        border-bottom-color: transparent;
    }

    &__label {
        flex-shrink: 0;
        width: 140rpx;
        font-size: var(--fs-md); /* --fs-xs → --fs-md（A11） */
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &--stack &__label {
        width: auto;
    }

    &__req {
        margin-left: var(--sp-1);
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
        height: 88rpx; /* 保持：88rpx 热区 */
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__err,
    &__hint {
        display: block;
        width: 100%;
        padding: var(--sp-1) 0 0 calc(140rpx + var(--sp-3)); /* 左缘对齐，保持 */
        font-size: var(--fs-md); /* 中文最低档 */
        line-height: var(--lh-body); /* 1.5 → --lh-body 1.55 */
    }

    &__err {
        color: var(--c-danger);
    }

    &__hint {
        color: var(--c-ink-3);
    }

    &--stack &__err,
    &--stack &__hint {
        padding-left: 0;
    }
}

/* v10 §3.13：减弱动效下聚焦线直接到位，不做颜色过渡 */
@media (prefers-reduced-motion: reduce) {
    .field {
        transition: none;
    }
}
</style>
