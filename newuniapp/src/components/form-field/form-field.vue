<template>
    <view
        class="ff"
        :class="[
            `ff--${layout}`,
            {
                'is-error': !!error,
                'is-disabled': disabled,
                'is-focus': focused,
                'is-readonly': readonly
            }
        ]"
        :style="{ '--ff-label-w': `${labelWidth}rpx` }"
    >
        <text class="ff__label"
            >{{ label }}<text v-if="required" class="ff__req" aria-hidden="true">*</text></text
        >
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
                    :maxlength="maxlength"
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
                    <text :class="modelValue ? 'ff__value' : 'ff__placeholder'">{{
                        modelValue || placeholder || '请选择'
                    }}</text>
                    <!-- v1 u-icon → v2 tn-icon：arrow-right → right -->
                    <tn-icon
                        name="right"
                        :size="32"
                        color="var(--c-ink-3)"
                        aria-hidden="true"
                    />
                </view>
                <!-- O6：评分星标（控件态不豁免 §6.6，未选星标禁用 --c-line-strong） -->
                <view
                    v-else-if="type === 'rating'"
                    class="ff__rating"
                    aria-role="radiogroup"
                    :aria-label="label"
                >
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
                        <!-- v1 u-icon → v2 tn-icon：评分星形保留（star-fill / star） -->
                        <tn-icon
                            :name="n <= ratingValue ? 'star-fill' : 'star'"
                            :size="48"
                            :color="n <= ratingValue ? 'var(--c-warning)' : 'var(--c-ink-3)'"
                            aria-hidden="true"
                        />
                    </view>
                </view>
                <!-- radio / checkbox：组件内**未实现**（无渲染分支，已从 type 联合移除），请走默认插槽自带控件 -->
                <view v-else-if="type === 'upload'" class="ff__upload ff__placeholder">
                    <text>{{ placeholder || '待补充' }}</text>
                </view>
            </slot>
            <!-- 字数计数器：只有同时传 count 与 maxlength 才渲染（收编页面里手写的 .counter） -->
            <text v-if="showCount" class="ff__count">{{ countText }}</text>
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
 *
 * ── 两种用法，选错会多写一层没必要的代码 ────────────────────────────
 * ① 内置 type（首选）：input / picker / rating（radio / checkbox 未实现，见下）
 *    组件负责渲染控件 + 受控绑定（modelValue / update:modelValue 都接好了）。
 *    能覆盖的控件一律用这个，省掉页面自己接受控那层。
 *
 * ② 默认插槽（仅当控件形态组件没有时）：textarea / upload / 单选多选 / 原生系统 picker
 *    插槽拿到的只有「标签 + 错误位 + 布局」——**拿不到控件的受控绑定**，
 *    modelValue / update:modelValue 仍要页面自己接。
 *    ⚠️ 不要把插槽当"更灵活的 type"来包一个内置 type 本来就能覆盖的 input，
 *       那只会多写一层受控绑定，且错误位/禁用态要自己同步。
 *
 * ⚠️ type="textarea" 与 type="upload" 是**空壳**：
 *    - textarea 会渲染成单行 input（base-input 本身没有 textarea 类型）；
 *    - upload 只是一个带"待补充"的虚线占位框，不是上传器。
 *    多行输入与上传请走插槽（见 ②），不要按 type 名以为能用。
 */
import { computed, ref, getCurrentInstance } from 'vue'

const props = withDefaults(
    defineProps<{
        modelValue: string | number | boolean | string[]
        label: string
        /* ⚠️ 2026-11-08：从联合里移除了 radio 与 checkbox 两个值 —— 它们有类型、没有渲染分支，
         * 传了 TS 通过、运行时一片空白（假保证第三型）。需要单/多选请走默认插槽自带控件。
         * textarea 与 upload 保留但同样是降级实现，见下方说明。 */
        type?: 'input' | 'textarea' | 'picker' | 'rating' | 'upload'
        required?: boolean
        error?: string
        hint?: string
        disabled?: boolean
        readonly?: boolean
        placeholder?: string
        options?: { label: string; value: string | number }[]
        cursorSpacing?: number
        /**
         * 标签宽度（rpx）。默认 140 = 既有样式值，老页面零变化。
         * 长标签页传更大值（如「证明材料（选填，最多 5 张）」传 200）。
         * 超过 200rpx 仍装不下时，请把括号说明移到 hint，不要继续加宽 ——
         * 标签宽度无上限增长会把右侧值区压没。
         */
        labelWidth?: number
        /**
         * 布局：
         *   row（默认）= 标签在左、控件在右（适合短标签 + input/picker）
         *   stack      = 标签在上、控件满宽（适合长标签 ≥8 字、textarea、upload、rating）
         * 判据：中文标签超过约 7 字（200rpx）时右侧控件区会被压没；且 textarea / upload / rating
         *      本身需要宽度 —— 这两类都用 stack。
         *
         * ⚠️ 这个 prop 若不被组件识别，Vue 会把它当普通 attribute 挂到根节点 DOM 上，
         * **不报错、不警告、不生效** —— 改动后必须看产物 wxml 的 class 里有没有 `ff--stack`。
         */
        layout?: 'row' | 'stack'
        /** 是否显示字数计数器（需同时传 maxlength） */
        count?: boolean
        /** 输入上限；同时下发给 base-input 做硬限制 */
        maxlength?: number
    }>(),
    { type: 'input', cursorSpacing: 120, labelWidth: 140, count: false, layout: 'row' }
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
const onFocus = () => {
    focused.value = true
    emit('focus')
}
const onBlur = () => {
    focused.value = false
    emit('blur')
}

const ratingValue = computed(() => Number(props.modelValue) || 0)
function onRate(n: number) {
    if (props.disabled || props.readonly) return
    emit('update:modelValue', n)
    emit('change', n)
}

/** 计数器：count 与 maxlength 同时具备才显示，避免出现「12/undefined」 */
const showCount = computed(() => props.count && typeof props.maxlength === 'number')
const countText = computed(() => `${String(props.modelValue ?? '').length}/${props.maxlength}`)
</script>

<style lang="scss" scoped>
.ff {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--sp-3);
    min-height: 88rpx;
    border-bottom: 1rpx solid var(--c-control-border);

    /* stack：标签在上、控件满宽（长标签 / textarea / upload / rating） */
    &--stack {
        flex-direction: column;
        align-items: stretch;
        gap: var(--sp-2);

        .ff__label {
            width: auto;
        }

        .ff__control {
            width: 100%;
        }

        /* 控件满宽后，错误/提示不需要再缩进对齐标签 */
        .ff__err,
        .ff__hint {
            padding-left: 0;
        }
    }

    &__label {
        /* 宽度由根节点内联的 --ff-label-w 控制（默认 140rpx）。
           行内自定义属性不被支持时（老基础库）var() 取兜底 140rpx，退化为既有表现。 */
        width: var(--ff-label-w, 140rpx);
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
        /* 与 label 同宽缩进，跟随 --ff-label-w */
        padding: var(--sp-1) 0 0 calc(var(--ff-label-w, 140rpx) + var(--sp-3));
        font-size: var(--fs-md);
        line-height: var(--lh-body);
    }

    /* 字数计数器：右对齐贴在控件下方（收编页面手写 .counter，样式口径一致） */
    &__count {
        display: block;
        text-align: right;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
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
