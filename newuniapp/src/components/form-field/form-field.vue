<template>
    <view class="ff" :class="{ 'is-error': !!error, 'is-disabled': disabled, 'is-focus': focused, 'is-readonly': readonly }">
        <text class="ff__label">{{ label }}<text v-if="required" class="ff__req" aria-hidden="true">*</text></text>
        <view class="ff__control">
            <!-- 默认插槽优先：页面可覆盖任意控件的自定义实现 -->
            <slot>
                <base-input
                    v-if="type === 'input' || type === 'textarea'"
                    :model-value="String(modelValue ?? '')"
                    :label="''"
                    :error="''"
                    :disabled="disabled"
                    :readonly="readonly"
                    :cursor-spacing="cursorSpacing"
                    :aria-required="required ? 'true' : undefined"
                    :aria-invalid="error ? 'true' : undefined"
                    :aria-describedby="error || hint ? descId : undefined"
                    @update:model-value="(v: string) => emit('update:modelValue', v)"
                    @focus="onFocus"
                    @blur="onBlur"
                />
                <view
                    v-else-if="type === 'picker'"
                    class="ff__picker"
                    hover-class="is-pressed"
                    :aria-label="label"
                    @tap="emit('tap')"
                >
                    <text :class="modelValue ? 'ff__value' : 'ff__placeholder'">{{ modelValue || placeholder || '请选择' }}</text>
                    <u-icon name="arrow-right" :size="32" color="var(--c-ink-3)" aria-hidden="true" />
                </view>
                <!-- O6：评分星标（控件态不豁免 §6.6，未选星标禁用 --c-line-strong） -->
                <view v-else-if="type === 'rating'" class="ff__rating" aria-role="radiogroup" :aria-label="label">
                    <view
                        v-for="n in 5"
                        :key="n"
                        class="ff__star"
                        hover-class="is-pressed"
                        :aria-label="`第 ${n} 星，共 5 星`"
                        :aria-checked="n <= ratingValue ? 'true' : 'false'"
                        aria-role="radio"
                        @tap="onRate(n)"
                    >
                        <u-icon
                            :name="n <= ratingValue ? 'star-fill' : 'star'"
                            :size="48"
                            :color="n <= ratingValue ? 'var(--c-warning)' : 'var(--c-ink-3)'"
                            aria-hidden="true"
                        />
                    </view>
                </view>
                <!-- radio / checkbox / upload：由页面通过具名/default 插槽或公共类提供 -->
                <view v-else-if="type === 'upload'" class="ff__upload ff__placeholder">
                    <text>{{ placeholder || '待补充' }}</text>
                </view>
            </slot>
        </view>
        <text v-if="error" :id="descId" class="ff__err" aria-live="polite">{{ error }}</text>
        <text v-else-if="hint" :id="descId" class="ff__hint">{{ hint }}</text>
    </view>
</template>

<script setup lang="ts">
/**
 * 表单字段容器（收编 .field / .row / .q / .picker / .input 私有样式）。
 * 契约：不写业务校验规则、不决定能否提交、不做提交后滚动到首个错误（页面职责）、
 * 不发起请求、不用 placeholder 代替 label、错误贴字段下方、禁用态不靠 opacity。
 * 内部复用现有 base-input，不重写输入框。
 */
import { computed, ref, getCurrentInstance } from 'vue'

const props = withDefaults(
    defineProps<{
        modelValue: string | number | boolean | string[]
        label: string
        type?: 'input' | 'textarea' | 'picker' | 'radio' | 'checkbox' | 'rating' | 'upload'
        required?: boolean
        error?: string
        hint?: string
        disabled?: boolean
        readonly?: boolean
        placeholder?: string
        options?: { label: string; value: string | number }[]
        cursorSpacing?: number
    }>(),
    { type: 'input', cursorSpacing: 120 }
)

const emit = defineEmits<{
    (e: 'update:modelValue', v: any): void
    (e: 'blur'): void
    (e: 'focus'): void
    (e: 'tap'): void
    (e: 'change', v: any): void
}>()

/** 实例级唯一 id，供 aria-describedby 关联错误/提示 */
const descId = `ff-desc-${getCurrentInstance()?.uid ?? 0}`

const focused = ref(false)
const onFocus = () => { focused.value = true; emit('focus') }
const onBlur = () => { focused.value = false; emit('blur') }

const ratingValue = computed(() => Number(props.modelValue) || 0)
function onRate(n: number) {
    if (props.disabled || props.readonly) return
    emit('update:modelValue', n)
    emit('change', n)
}
</script>

<style lang="scss" scoped>
.ff {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--sp-3);
    min-height: 88rpx;
    border-bottom: 1rpx solid var(--c-control-border);

    &__label {
        width: 140rpx;
        flex-shrink: 0;
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__req {
        margin-left: var(--sp-1);
        color: var(--c-danger);
    }

    &__control {
        flex: 1;
        min-width: 0;
    }

    &__picker {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 88rpx;
        border-bottom: 1rpx solid var(--c-control-border);
    }

    &__value {
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__placeholder {
        font-size: var(--fs-md);
        color: var(--c-ink-3);
    }

    /* O6 评分：每星 ≥88rpx 热区，间距 --sp-2 */
    &__rating {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__star {
        min-width: 88rpx;
        min-height: 88rpx;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__err,
    &__hint {
        display: block;
        width: 100%;
        padding: var(--sp-1) 0 0 calc(140rpx + var(--sp-3));
        font-size: var(--fs-md);
        line-height: var(--lh-body);
    }

    &__err {
        color: var(--c-danger);
    }

    &__hint {
        color: var(--c-ink-3);
    }

    &.is-focus {
        box-shadow: inset 0 -2rpx 0 var(--c-ink);
    }
    &.is-error {
        border-bottom-color: var(--c-danger);
    }
    &.is-disabled {
        background: var(--c-neutral-bg);
        color: var(--c-ink-3);
        opacity: 1;
    }
    &.is-readonly {
        border-bottom-color: transparent;
    }
}

/* 上传 / 占位：1rpx 虚线仅「待补充/未生成/占位」语义（视觉规范 §5.2） */
.ff__upload {
    border: 1rpx dashed var(--c-control-border);
    border-radius: var(--r-sm);
    padding: var(--sp-2) var(--sp-3);
}
</style>
