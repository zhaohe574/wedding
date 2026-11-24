<template>
    <tn-popup
        :model-value="modelValue"
        open-direction="bottom"
        bg-color="transparent"
        radius="0"
        :safe-area-inset-bottom="true"
        @update:model-value="onPopupChange"
    >
        <view class="sheet">
            <view class="sheet__head">
                <text class="sheet__title">{{ isEdit ? '编辑我的文案模板' : '新建我的文案模板' }}</text>
                <view
                    hover-class="is-pressed"
                    class="sheet__close"
                    aria-role="button"
                    aria-label="关闭"
                    @tap="close"
                >
                    <tn-icon name="close" :size="28" class="sheet__close-icon" />
                </view>
            </view>

            <scroll-view class="sheet__body" scroll-y>
                <view class="field">
                    <text class="field__label">适用场景 *</text>
                    <view class="scene-list">
                        <view
                            v-for="item in scenes"
                            :key="item.value"
                            hover-class="is-pressed"
                            class="scene-chip"
                            :class="{ 'is-active': form.scene === item.value }"
                            @tap="form.scene = item.value"
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
                        <text class="field__count">{{ form.content.length }}/1000</text>
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
            </scroll-view>

            <view class="sheet__foot">
                <view class="sheet__btn">
                    <base-button text="取消" variant="ghost" size="md" block @click="close" />
                </view>
                <view class="sheet__btn">
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
        </view>
    </tn-popup>
</template>

<script setup lang="ts">
/**
 * 个人文案模板新建 / 编辑底部弹窗。
 */
import { computed, reactive, watch } from 'vue'
import type { StaffCopyTemplateCustom } from '@/api/staff'
import { toast } from '@/composables/useToast'

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

function onPopupChange(val: boolean): void {
    if (!val && props.saving) return
    emit('update:modelValue', val)
}

function close(): void {
    if (props.saving) return
    emit('update:modelValue', false)
}

function appendVariable(key: string): void {
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
.sheet {
    background: var(--c-surface);
    padding: var(--sp-4) var(--sp-4) calc(var(--sp-4) + var(--safe-bottom));
    border-radius: var(--r-lg) var(--r-lg) 0 0;

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__title {
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__close {
        width: 56rpx;
        height: 56rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--c-ink-3);
    }

    &__close-icon {
        color: inherit;
    }

    &__body {
        max-height: 70vh;
        margin-top: var(--sp-3);
    }

    &__foot {
        display: flex;
        gap: var(--sp-2);
        margin-top: var(--sp-4);
    }

    &__btn {
        flex: 1;
        min-width: 0;
    }
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
        font-variant-numeric: tabular-nums;
    }

    &__input {
        width: 100%;
        height: 84rpx;
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

.scene-chip {
    padding: var(--sp-1) var(--sp-3);
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);

    &.is-active {
        border-color: var(--c-brand-text);
        background: var(--c-brand-bg);
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink);
        font-weight: var(--fw-medium);
    }
}

.var-list {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-1);
    margin-top: var(--sp-2);
}

.var-chip {
    padding: var(--sp-1) var(--sp-2);
    border-radius: var(--r-pill);
    background: var(--c-brand-bg);
    border: 1rpx solid var(--c-primary-border);

    &__text {
        font-size: var(--fs-20);
        color: var(--c-brand-text);
        font-weight: var(--fw-medium);
    }
}
</style>
