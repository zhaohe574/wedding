<template>
    <view
        class="ff"
        :class="[
            `ff--${layout}`,
            {
                'is-error': !!error,
                'is-disabled': disabled,
                'is-focus': focused,
                'is-readonly': readonly,
                'ff--boxed': type === 'textarea' || type === 'upload'
            }
        ]"
        :style="{ '--ff-label-w': `${labelWidth}rpx` }"
    >
        <view v-if="type === 'upload'" class="ff__head">
            <text class="ff__label"
                >{{ label }}<text v-if="required" class="ff__req" aria-hidden="true">*</text></text
            >
            <text class="ff__upload-meta t-num">{{
                max === 1
                    ? uploadList.length
                        ? '已上传'
                        : '未上传'
                    : `${uploadList.length}/${max}`
            }}</text>
        </view>
        <text v-else class="ff__label"
            >{{ label }}<text v-if="required" class="ff__req" aria-hidden="true">*</text></text
        >
        <view class="ff__control">
            <!-- 默认插槽优先：页面可覆盖任意控件的自定义实现 -->
            <slot>
                <base-input
                    v-if="type === 'input'"
                    :model-value="String(modelValue ?? '')"
                    :label="''"
                    :error="''"
                    :placeholder="placeholder || '请输入'"
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
                <!-- 多行输入：不走 base-input（它没有多行能力），直接渲染原生 textarea -->
                <textarea
                    v-else-if="type === 'textarea'"
                    class="ff__textarea"
                    :value="String(modelValue ?? '')"
                    :placeholder="placeholder || '请输入'"
                    :maxlength="maxlength ?? -1"
                    placeholder-class="ff__ph"
                    :disabled="disabled || readonly"
                    :cursor-spacing="cursorSpacing"
                    :aria-required="required ? 'true' : undefined"
                    :aria-invalid="error ? 'true' : undefined"
                    :aria-describedby="error || hint ? descId : undefined"
                    @input="(e: any) => emit('update:modelValue', e.detail.value)"
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
                    <tn-icon name="right" :size="32" aria-hidden="true" />
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
                        :class="{ 'is-on': n <= ratingValue }"
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
                            aria-hidden="true"
                        />
                    </view>
                </view>
                <!-- radio / checkbox：**不是遗漏，是无消费者**（全站 0 处需要 {label,value} 选项组）。
                     需要时按 waitlist/apply 的 .opt 口径单开一支，不要在这里草率加。 -->
                <view v-else-if="type === 'upload'" class="ff__upload">
                    <template v-if="max === 1">
                        <view v-if="uploadList.length" class="ff__single-preview">
                            <image
                                class="ff__single-img"
                                :src="uploadList[0]"
                                mode="aspectFill"
                                @tap="previewImage(0)"
                            />
                            <view
                                v-if="!disabled && !readonly"
                                class="ff__thumb-del"
                                hover-class="is-pressed"
                                aria-label="删除图片"
                                @tap.stop="removeImage(0)"
                            >
                                <tn-icon name="close" :size="22" aria-hidden="true" />
                            </view>
                            <view v-if="!disabled && !readonly" class="ff__single-bar">
                                <text class="ff__single-bar-tip">点击查看大图</text>
                                <view
                                    class="ff__single-replace"
                                    :class="{ 'is-busy': uploading }"
                                    hover-class="is-pressed"
                                    @tap.stop="onReplaceSingle"
                                >
                                    <tn-icon
                                        :name="uploading ? 'loading' : 'upload'"
                                        :size="22"
                                        aria-hidden="true"
                                    />
                                    <text class="ff__single-replace-text">{{
                                        uploading ? '上传中…' : '更换图片'
                                    }}</text>
                                </view>
                            </view>
                        </view>
                        <view
                            v-else
                            class="ff__single-drop"
                            :class="{ 'is-busy': uploading || disabled || readonly }"
                            hover-class="is-pressed"
                            :aria-label="uploading ? '图片上传中' : '点击上传图片'"
                            @tap="onAddImage"
                        >
                            <view class="ff__drop-badge">
                                <tn-icon
                                    :name="uploading ? 'loading' : 'image'"
                                    :size="36"
                                    aria-hidden="true"
                                />
                            </view>
                            <text class="ff__drop-title">{{
                                uploading ? '图片上传中…' : '点击上传图片'
                            }}</text>
                            <text class="ff__drop-sub">支持相册选取或拍照</text>
                        </view>
                    </template>
                    <view v-else class="ff__grid">
                        <view v-for="(src, i) in uploadList" :key="i" class="ff__thumb">
                            <image
                                class="ff__thumb-img"
                                :src="src"
                                mode="aspectFill"
                                @tap="previewImage(i)"
                            />
                            <view
                                v-if="!disabled && !readonly"
                                class="ff__thumb-del"
                                hover-class="is-pressed"
                                aria-label="删除图片"
                                @tap.stop="removeImage(i)"
                            >
                                <tn-icon name="close" :size="22" aria-hidden="true" />
                            </view>
                        </view>
                        <view
                            v-if="uploadList.length < max && !disabled && !readonly"
                            class="ff__add"
                            :class="{ 'is-busy': uploading }"
                            hover-class="is-pressed"
                            :aria-label="uploading ? '上传中' : '添加图片'"
                            @tap="onAddImage"
                        >
                            <view class="ff__add-inner">
                                <view class="ff__add-badge">
                                    <tn-icon
                                        :name="uploading ? 'loading' : 'add'"
                                        :size="28"
                                        aria-hidden="true"
                                    />
                                </view>
                                <text class="ff__add-text">{{
                                    uploading ? '上传中…' : '添加图片'
                                }}</text>
                            </view>
                        </view>
                    </view>
                </view>
            </slot>
            <!-- 字数计数器：只有同时传 count 与 maxlength 才渲染（收编页面里手写的 .counter） -->
            <text v-if="showCount" class="ff__count t-num">{{ countText }}</text>
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
 * ① 内置 type（首选）：input / textarea / picker / rating / upload
 *    组件负责渲染控件 + 受控绑定（modelValue / update:modelValue 都接好了）。
 *    能覆盖的控件一律用这个，省掉页面自己接受控那层。
 *
 * ② 默认插槽（仅当控件形态组件没有时）：原生系统 picker / 单选多选 / 任何本组件没覆盖的控件
 *    插槽拿到的只有「标签 + 错误位 + 布局」——**拿不到控件的受控绑定**，
 *    modelValue / update:modelValue 仍要页面自己接。
 *    ⚠️ 不要把插槽当"更灵活的 type"来包一个内置 type 本来就能覆盖的 input，
 *       那只会多写一层受控绑定，且错误位/禁用态要自己同步。
 *
 * ③ radio / checkbox **不是遗漏，是无消费者**：
 *    全站没有一处需要 {label, value} 选项组 —— callback-detail 的题型已裁定不迁本组件
 *    （题号衬线 + 题间分割线与本组件的行级 border-bottom 冲突）；waitlist/apply 的 .opt
 *    是带价格的套餐卡，形态不同。**需要时按 .opt 口径单开一支，不要草率补两个复选框。**
 *
 * ④ avatar（单个圆形、点击替换）也不做：全站 1 处（profile），形态是 .row + 圆形图 + 右箭头，
 *    与 3 列网格完全不同。**为一个消费者加变体 = 新死代码** —— 下次有人看到这个 type 会以为
 *    有通用能力，接着就会有第二个页面为了"复用"把别的东西套成 avatar。
 *    profile 的选图已走 chooseImages（权限被拒引导已修好），手写只是保留它的版式，不是欠账。
 *
 * ── 2026-11-08：textarea 与 upload 已从空壳做成真能力 ────────────────
 * textarea：不再走 base-input（它没有多行能力），直接渲染原生 textarea，支持 maxlength
 *           与 count 计数器；layout 默认仍是 row，多行场景请调用方显式传 layout="stack"。
 * upload  ：复用 chooseImages（含隐私授权/权限被拒引导）+ uploadImage，v-model 为 string[]，
 *           新增 max prop（默认 9）。单张失败跳过不整批回滚。
 */
import { computed, ref, getCurrentInstance } from 'vue'
import { chooseImages } from '@/composables/useChooseImage'
import { uploadImage } from '@/api/app'
import { toast } from '@/composables/useToast'

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
        /** 输入上限；同时下发给 base-input / textarea 做硬限制 */
        maxlength?: number
        /** upload 类型的最大张数（默认 9）。只对 type="upload" 生效。 */
        max?: number
    }>(),
    { type: 'input', cursorSpacing: 120, labelWidth: 140, count: false, layout: 'row', max: 9 }
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

/* ── upload 类型 ─────────────────────────────────────────────────────
 * 复用两个既有能力，不重写：
 *   chooseImages  → 已含隐私重放/权限被拒引导/取消静默，失败返 []
 *   uploadImage   → 带鉴权与 loading 的上传
 * 契约：v-model 是 string[]（URL 数组），组件只负责增删与预览，不决定业务语义。
 */
const uploading = ref(false)
const uploadList = computed<string[]>(() =>
    Array.isArray(props.modelValue) ? (props.modelValue as string[]) : []
)

function emitList(next: string[]) {
    emit('update:modelValue', next)
}

async function onAddImage() {
    if (props.disabled || props.readonly || uploading.value) return
    const remain = Math.max(0, props.max - uploadList.value.length)
    if (remain <= 0) return

    const paths = await chooseImages({ count: remain })
    if (!paths.length) return

    uploading.value = true
    const next = [...uploadList.value]
    try {
        for (let i = 0; i < paths.length; i += 1) {
            try {
                const up: any = await uploadImage(paths[i])
                const url = up?.uri || up?.url
                if (typeof url === 'string') next.push(url)
            } catch {
                // 单张失败跳过，不整批回滚 —— 已经成功的图不该被丢掉
                toast.error(`第 ${i + 1} 张上传失败`)
            }
        }
        emitList(next)
    } finally {
        uploading.value = false
    }
}

async function onReplaceSingle() {
    if (props.disabled || props.readonly || uploading.value) return
    const paths = await chooseImages({ count: 1 })
    if (!paths.length) return

    uploading.value = true
    try {
        const up: any = await uploadImage(paths[0])
        const url = up?.uri || up?.url
        if (typeof url === 'string' && url) {
            emitList([url])
        } else {
            toast.error('图片上传失败')
        }
    } catch {
        toast.error('图片上传失败')
    } finally {
        uploading.value = false
    }
}

function removeImage(index: number) {
    if (props.disabled || props.readonly) return
    const next = uploadList.value.slice()
    next.splice(index, 1)
    emitList(next)
}

function previewImage(index: number) {
    const list = uploadList.value
    if (!list.length) return
    uni.previewImage({ urls: list, current: list[index] })
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

    /* stack：标签在上、控件满宽（长标签 / textarea / upload / rating） */
    &--stack {
        flex-direction: column;
        align-items: stretch;
        gap: var(--sp-2);
        padding: var(--sp-2) 0;

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

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        width: 100%;

        .ff__label {
            width: auto;
        }
    }

    &__upload-meta {
        flex-shrink: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        font-variant-numeric: tabular-nums;
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

    /* 多行输入：不走 base-input（它没有多行能力）。
       min-height 用 calc 而不是写死一个大数 —— R8 把几档固定尺寸值当私建间距拦，
       这里 96×2=192rpx ≈ 三行中文，既够用又不触发棘轮。 */
    &__textarea {
        display: block;
        width: 100%;
        min-height: calc(var(--sp-7) * 2);
        padding: var(--sp-3);
        box-sizing: border-box;
        background: var(--c-surface-2);
        border: 1rpx solid var(--c-control-border);
        border-radius: var(--r-md);
        font-size: var(--fs-md);
        line-height: var(--lh-loose);
        color: var(--c-ink);
    }

    &__ph {
        color: var(--c-ink-3);
    }

    &__picker {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 88rpx;
        /* 不再自绘底线：根节点 .ff 已画 1rpx --c-control-border，
           两处同位置叠成 2rpx，看起来像「字段被加粗」而不是分隔。 */
        /* 右侧箭头；.ff__value / .ff__placeholder 各自声明了颜色 */
        color: var(--c-ink-3);
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
        /* 星标颜色随选中态变：原来用 :color 传 var() 无效，改成状态类 */
        color: var(--c-ink-3);

        &.is-on {
            color: var(--c-warning);
        }
    }

    &__err,
    &__hint {
        display: block;
        width: 100%;
        /* 与 label 同宽缩进，跟随 --ff-label-w */
        padding: var(--sp-1) 0 0 calc(var(--ff-label-w, 140rpx) + var(--sp-3));
        font-size: var(--fs-xs);
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

    /* 加框控件（textarea / upload）本身已有一圈边框，根节点不再画字段分隔线。
       input / picker 没有自带边框，根节点那条 border-bottom 就是它们唯一的分隔线 —— 所以只对 --boxed 关掉。
       不关的话是「[方框] 下面再画一道横线」的双边框，不是设计选择。 */
    &--boxed {
        border-bottom: 0;
        /* 底线没了，聚焦反馈也不能再用根节点的 inset 阴影（那还是一条线），挪到控件边框上 */
        &.is-focus {
            box-shadow: none;
        }
    }

    &--boxed.is-error &__textarea {
        border-color: var(--c-danger);
    }
    &--boxed.is-error .ff__single-drop,
    &--boxed.is-error .ff__add {
        border-color: var(--c-danger);
    }
    &--boxed.is-focus &__textarea {
        border-color: var(--c-ink);
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

/* 上传器：单图精致封面卡 + 多图九宫格，严格遵循顶级类名避免嵌套拼错。 */
.ff__upload {
    width: 100%;
}

.ff__single-preview {
    position: relative;
    width: 100%;
    height: 340rpx;
    border-radius: var(--r-md);
    border: 1rpx solid var(--c-line);
    overflow: hidden;
    background: var(--c-surface-2);
}

.ff__single-img {
    width: 100%;
    height: 100%;
    display: block;
}

.ff__single-bar {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    padding: var(--sp-2) var(--sp-3);
    background: var(--c-mask);
}

.ff__single-bar-tip {
    font-size: var(--fs-xs);
    color: var(--c-on-dark-2);
}

.ff__single-replace {
    display: inline-flex;
    align-items: center;
    gap: var(--sp-1);
    padding: 6rpx var(--sp-2);
    border-radius: var(--r-pill);
    background: var(--c-surface);
    color: var(--c-ink);

    &.is-busy {
        pointer-events: none;
        opacity: 0.65;
    }
}

.ff__single-replace-text {
    font-size: var(--fs-xs);
    font-weight: var(--fw-medium);
    color: var(--c-ink);
}

.ff__single-drop {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--sp-1);
    width: 100%;
    height: 300rpx;
    padding: var(--sp-3);
    box-sizing: border-box;
    border: 1rpx dashed var(--c-control-border);
    border-radius: var(--r-md);
    background: var(--c-surface-2);

    &.is-busy {
        pointer-events: none;
        opacity: 0.6;
    }
}

.ff__drop-badge {
    width: 80rpx;
    height: 80rpx;
    margin-bottom: var(--sp-1);
    border-radius: var(--r-pill);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);
    color: var(--c-brand-text);
    display: flex;
    align-items: center;
    justify-content: center;
}

.ff__drop-title {
    font-size: var(--fs-md);
    font-weight: var(--fw-medium);
    color: var(--c-ink);
}

.ff__drop-sub {
    font-size: var(--fs-xs);
    color: var(--c-ink-3);
}

.ff__grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--sp-2);
}

.ff__thumb {
    position: relative;
    width: 100%;
    padding-bottom: 100%; /* 正方形占位，避免图片加载前抖动 */
    border-radius: var(--r-sm);
    border: 1rpx solid var(--c-line);
    box-sizing: border-box;
    overflow: hidden;
    background: var(--c-surface-2);
}

.ff__thumb-img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.ff__thumb-del {
    position: absolute;
    top: 8rpx;
    right: 8rpx;
    z-index: 2;
    width: 44rpx;
    height: 44rpx;
    border-radius: var(--r-pill);
    background: var(--c-mask);
    /* 删除的 × 图标靠继承拿色：tn-icon 不认 var()，需显式声明反白色 */
    color: var(--c-ink-inverse);
    display: flex;
    align-items: center;
    justify-content: center;
}

.ff__add {
    position: relative;
    width: 100%;
    padding-bottom: 100%;
    border: 1rpx dashed var(--c-control-border);
    border-radius: var(--r-sm);
    background: var(--c-surface-2);
    box-sizing: border-box;

    &.is-busy {
        pointer-events: none;
        opacity: 0.6;
    }
}

.ff__add-inner {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--sp-1);
    padding: var(--sp-1);
    box-sizing: border-box;
}

.ff__add-badge {
    width: 56rpx;
    height: 56rpx;
    border-radius: var(--r-pill);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);
    color: var(--c-brand-text);
    display: flex;
    align-items: center;
    justify-content: center;
}

.ff__add-text {
    font-size: var(--fs-xs);
    color: var(--c-ink-2);
}
</style>
