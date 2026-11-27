<template>
    <!-- 文案模板：磁吸半屏抽屉。标题 / 关闭 / 拖拽 / 脚部安全区由 snap-sheet 统一承担，
         原 .sheet 包裹层已去掉，样式全部扁平化到插槽内容上 -->
    <snap-sheet
        v-model="visible"
        :title="isEdit ? '编辑我的文案模板' : '新建我的文案模板'"
        :enable-full="true"
        :has-foot="true"
    >
        <view class="field">
            <text class="field__label">适用场景 *</text>
            <view class="scene-list">
                <view
                    v-for="item in scenes"
                    :key="item.value"
                    hover-class="is-pressed"
                    class="scene-chip"
                    :class="{ 'is-active': form.scene === item.value }"
                    @tap="pickScene(item.value)"
                >
                    <text class="scene-chip__text">{{ item.label }}</text>
                </view>
            </view>
        </view>

        <view class="field">
            <text class="field__label">模板标题 *</text>
            <input
                v-model="form.title"
                class="field__input"
                placeholder="如：接单后首日建群破冰话术"
                placeholder-class="field__ph"
                maxlength="60"
            />
        </view>

        <view class="field">
            <view class="field__head">
                <text class="field__label">话术正文 *</text>
                <text class="field__count t-num">{{ form.content.length }}/1000</text>
            </view>
            <textarea
                v-model="form.content"
                class="field__textarea"
                placeholder="输入话术正文，点击下方变量可快速代入婚礼场次信息"
                placeholder-class="field__ph"
                maxlength="1000"
            />
            <view class="var-list">
                <view
                    v-for="item in TEMPLATE_VARIABLES"
                    :key="item.key"
                    hover-class="is-pressed"
                    class="var-chip"
                    @tap="appendVariable(item.key)"
                >
                    <text class="var-chip__text">+ {{ item.label }}</text>
                </view>
            </view>
        </view>

        <template #foot>
            <view class="foot">
                <view class="foot__btn">
                    <base-button text="取消" variant="ghost" size="md" block @click="close" />
                </view>
                <view class="foot__btn">
                    <base-button
                        :text="saving ? '保存中…' : '保存模板'"
                        variant="ink"
                        size="md"
                        block
                        :loading="saving"
                        :disabled="saving"
                        @click="submit"
                    />
                </view>
            </view>
        </template>
    </snap-sheet>
</template>

<script setup lang="ts">
/**
 * 个人文案模板新建 / 编辑底部抽屉。
 *
 * 抽屉自身的关闭（遮罩 / 关闭键 / 下拉）不再用 saving 拦截：
 * snap-sheet 的 close 会自行播收起动画并 emit，若父级此时吞掉 update:modelValue，
 * 组件会停在「已收起但 modelValue 仍为 true」的锁死态，下次打不开。
 * 需要防误关的是脚部「取消」这类显式动作，见 close()。
 */
import { computed, reactive, watch } from 'vue'
import type { StaffCopyTemplateCustom } from '@/api/staff'
import { toast } from '@/composables/useToast'
import { haptic } from '@/utils/haptics'

const TEMPLATE_VARIABLES = [
    { key: '{customer_name}', label: '客户称呼' },
    { key: '{service_date}', label: '婚期日期' },
    { key: '{service_address}', label: '服务酒店' },
    { key: '{package_name}', label: '套餐名称' },
    { key: '{staff_name}', label: '我的名字' },
    { key: '{category_name}', label: '服务身份' },
    { key: '{price_text}', label: '参考报价' }
]

const props = withDefaults(
    defineProps<{
        modelValue: boolean
        scenes: Array<{ value: string; label: string }>
        editing?: StaffCopyTemplateCustom | null
        saving?: boolean
    }>(),
    {
        editing: null,
        saving: false
    }
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (
        e: 'submit',
        payload: { id?: number; scene: string; title: string; content: string }
    ): void
}>()

const visible = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})

const isEdit = computed(() => Number(props.editing?.id || 0) > 0)

const form = reactive({
    scene: '',
    title: '',
    content: ''
})

function resetForm(): void {
    const defaultScene = props.scenes?.[0]?.value || 'inquiry'
    if (props.editing) {
        form.scene = String(props.editing.scene || defaultScene)
        form.title = String(props.editing.title || '')
        form.content = String(props.editing.content || '')
        return
    }
    form.scene = defaultScene
    form.title = ''
    form.content = ''
}

watch(
    () => props.modelValue,
    (open) => {
        if (open) resetForm()
    },
    { immediate: true }
)

function close(): void {
    if (props.saving) return
    emit('update:modelValue', false)
}

function pickScene(value: string): void {
    if (form.scene === value) return
    haptic('light', 'copy-template-scene')
    form.scene = value
}

function appendVariable(key: string): void {
    haptic('light', 'copy-template-var')
    form.content = `${form.content}${key}`.slice(0, 1000)
}

function submit(): void {
    if (props.saving) return
    const scene = form.scene.trim()
    const title = form.title.trim()
    const content = form.content.trim()

    if (!scene) {
        toast.warning('请选择适用场景')
        return
    }
    if (!title) {
        toast.warning('请输入模板标题')
        return
    }
    if (!content) {
        toast.warning('请输入话术正文')
        return
    }

    emit('submit', {
        id: isEdit.value ? Number(props.editing?.id || 0) : undefined,
        scene,
        title,
        content
    })
}
</script>

<style lang="scss" scoped>
.foot {
    display: flex;
    gap: var(--sp-2);
}

.foot__btn {
    flex: 1;
    min-width: 0;
}

.field {
    & + & {
        margin-top: var(--sp-3);
    }

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-bottom: var(--sp-1);
    }

    &__label {
        display: block;
        margin-bottom: var(--sp-1);
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        color: var(--c-ink-2);
    }

    &__count {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__input {
        width: 100%;
        height: 88rpx;
        padding: 0 var(--sp-3);
        border-radius: var(--r-sm);
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        font-size: var(--fs-md);
        color: var(--c-ink);
        box-sizing: border-box;
    }

    &__textarea {
        width: 100%;
        min-height: 280rpx;
        padding: var(--sp-2) var(--sp-3);
        border-radius: var(--r-sm);
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        font-size: var(--fs-md);
        line-height: var(--lh-body);
        color: var(--c-ink);
        box-sizing: border-box;
    }
}

.scene-list {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
}

/* 场景胶囊：视觉仍是小胶囊，热区直接给足 88rpx（换行容器不用伪元素外扩，避免相邻项抢点击） */
.scene-chip {
    display: inline-flex;
    align-items: center;
    min-height: 88rpx;
    padding: 0 var(--sp-3);
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);
    transition:
        background-color var(--dur-fast) var(--ease-out),
        border-color var(--dur-fast) var(--ease-out),
        transform var(--dur-press) var(--ease-out);

    &.is-pressed {
        transform: scale(0.97);
    }

    &.is-active {
        border-color: var(--c-brand-text);
        background: var(--c-brand-bg);
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink);
        font-weight: var(--fw-medium);
    }

    &.is-active &__text {
        color: var(--c-brand-text);
        font-weight: var(--fw-semibold);
    }
}

.var-list {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-1);
    margin-top: var(--sp-2);
}

.var-chip {
    display: inline-flex;
    align-items: center;
    min-height: 88rpx;
    padding: 0 var(--sp-2);
    border-radius: var(--r-pill);
    background: var(--c-brand-bg);
    border: 1rpx solid var(--c-primary-border);
    transition: transform var(--dur-press) var(--ease-out);

    &.is-pressed {
        transform: scale(0.97);
    }

    &__text {
        font-size: var(--fs-20);
        color: var(--c-brand-text);
        font-weight: var(--fw-medium);
    }
}

@media (prefers-reduced-motion: reduce) {
    .scene-chip,
    .var-chip {
        transition: none;

        &.is-pressed {
            transform: none;
        }
    }
}
</style>
