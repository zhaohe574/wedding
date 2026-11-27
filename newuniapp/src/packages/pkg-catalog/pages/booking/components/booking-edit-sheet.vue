<template>
    <!--
        联系 / 地址 / 备注拆成三个弹层：
        改电话不用跨过地址字段，改地址不用看到备注；地区单独标注「决定价格」，
        改地区必须回到「日期 + 地区」弹层，因为两者共同决定报价与锁档。
    -->
    <snap-sheet
        :model-value="modelValue"
        :title="sheetTitle"
        :has-foot="true"
        @update:model-value="emit('update:modelValue', $event)"
    >
        <view class="edit-body">
            <template v-if="mode === 'contact'">
                <base-input
                    :model-value="draft.contact_name"
                    label="联系人"
                    placeholder="请输入姓名"
                    :maxlength="20"
                    required
                    :error="draftErrors.contact_name"
                    @update:model-value="onFieldInput('contact_name', String($event ?? ''))"
                    @blur="validateDraftField('contact_name')"
                />
                <base-input
                    :model-value="draft.contact_mobile"
                    label="手机号"
                    type="number"
                    placeholder="请输入手机号"
                    :maxlength="11"
                    required
                    :error="draftErrors.contact_mobile"
                    @update:model-value="onFieldInput('contact_mobile', String($event ?? ''))"
                    @blur="validateDraftField('contact_mobile')"
                />
                <text class="edit-hint">仅用于档期确认与到场联系，不会对外展示。</text>
            </template>

            <template v-else-if="mode === 'address'">
                <view class="reg">
                    <view class="reg__main">
                        <text class="reg__k">服务地区 · 决定价格</text>
                        <text class="reg__v">{{ serviceRegionText || '未选择' }}</text>
                    </view>
                    <text class="reg__a" hover-class="is-pressed" @tap="emit('switchRegion')"
                        >修改地区</text
                    >
                </view>
                <base-input
                    :model-value="draft.service_address"
                    label="详细地址"
                    placeholder="请输入详细地址"
                    :maxlength="80"
                    required
                    :error="draftErrors.service_address"
                    @update:model-value="onFieldInput('service_address', String($event ?? ''))"
                    @blur="validateDraftField('service_address')"
                />
                <text class="edit-hint"
                    >修改地区会重新计算价格并重新锁定档期，需要回到日期与地区选择。</text
                >
            </template>

            <template v-else>
                <textarea
                    :value="draft.remark"
                    class="ta"
                    placeholder="选填，例如场地限制、特殊流程、希望避开的时间…"
                    placeholder-class="field__ph"
                    maxlength="200"
                    :cursor-spacing="120"
                    @input="onRemarkInput"
                />
                <text class="ta__count">{{ (draft.remark || '').length }}/200</text>
            </template>
        </view>

        <template #foot>
            <base-button
                :text="mode === 'remark' ? '保存备注' : '保存'"
                variant="ink"
                size="md"
                @click="onSave"
            />
        </template>
    </snap-sheet>
</template>

<script setup lang="ts">
import { computed, reactive, watch } from 'vue'
import { toast } from '@/composables/useToast'
import {
    validateBookingField,
    type BookingFormData,
    type BookingSheetMode,
    type BookingValidateField,
    type BookingEditSheetSavePayload
} from '../booking-helpers'

/**
 * 弹层内维护本地草稿态（draft），点击「保存」并校验通过后才 emit 给父级回写 form；
 * 关闭弹层（点关闭 / 遮罩）不回写，杜绝非法值污染父级表单。
 */
const props = defineProps<{
    modelValue: boolean
    mode: BookingSheetMode
    form: BookingFormData
    serviceRegionText: string
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', visible: boolean): void
    (e: 'switchRegion'): void
    (e: 'save', payload: BookingEditSheetSavePayload): void
}>()

const draft = reactive<BookingFormData>({
    contact_name: '',
    contact_mobile: '',
    service_address: '',
    remark: ''
})
const draftErrors = reactive<Record<BookingValidateField, string>>({
    contact_name: '',
    contact_mobile: '',
    service_address: ''
})

const sheetTitle = computed(() =>
    props.mode === 'address' ? '服务地址' : props.mode === 'remark' ? '备注' : '联系人'
)

watch(
    () => [props.modelValue, props.mode],
    ([visible]) => {
        if (visible) syncDraft()
    },
    { immediate: true }
)

function syncDraft() {
    draft.contact_name = props.form.contact_name || ''
    draft.contact_mobile = props.form.contact_mobile || ''
    draft.service_address = props.form.service_address || ''
    draft.remark = props.form.remark || ''
    draftErrors.contact_name = ''
    draftErrors.contact_mobile = ''
    draftErrors.service_address = ''
}

function onFieldInput(key: BookingValidateField, value: string) {
    draft[key] = value
    draftErrors[key] = ''
}

function validateDraftField(key: BookingValidateField) {
    draftErrors[key] = validateBookingField(key, draft[key])
}

function onRemarkInput(e: any) {
    draft.remark = String(e?.detail?.value ?? '')
}

function onSave() {
    if (props.mode === 'contact') {
        validateDraftField('contact_name')
        validateDraftField('contact_mobile')
        if (draftErrors.contact_name || draftErrors.contact_mobile) {
            toast.warning(draftErrors.contact_name || draftErrors.contact_mobile)
            return
        }
        emit('save', {
            mode: 'contact',
            contact_name: draft.contact_name.trim(),
            contact_mobile: draft.contact_mobile.trim()
        })
        return
    }
    if (props.mode === 'address') {
        validateDraftField('service_address')
        if (draftErrors.service_address) {
            toast.warning(draftErrors.service_address)
            return
        }
        emit('save', { mode: 'address', service_address: draft.service_address.trim() })
        return
    }
    emit('save', { mode: 'remark', remark: draft.remark.trim() })
}
</script>

<style lang="scss" scoped>
/* 模板里已没有 .pop 包裹节点：以下类直接挂在 snap-sheet 的 body / foot 插槽内，
   选择器必须平铺为顶层完整类名，不能再嵌在 .pop { &__x } 里。 */
.edit-body {
    padding: var(--sp-1) 0 var(--sp-2);
}

.edit-hint {
    display: block;
    margin-top: var(--sp-2);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-ink-3);
}

.reg {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    margin-bottom: var(--sp-3);
    padding: 20rpx 24rpx;
    border-radius: var(--r-sm);
    background: var(--c-surface-2);

    &__main {
        min-width: 0;
    }

    &__k {
        display: block;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__v {
        display: block;
        margin-top: 6rpx;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    /* 88rpx 热区：用负外边距撑开，视觉仍是行内文字链接 */
    &__a {
        flex: none;
        display: flex;
        align-items: center;
        min-height: 88rpx;
        padding: 0 var(--sp-1);
        margin: -28rpx 0;
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
    }
}

.ta {
    width: 100%;
    height: 180rpx;
    padding: 20rpx 24rpx;
    border-radius: var(--r-sm);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);
    font-size: var(--fs-md);
    line-height: var(--lh-body);
    color: var(--c-ink);
    box-sizing: border-box;
}

.ta__count {
    display: block;
    margin-top: 10rpx;
    text-align: right;
    font-size: var(--fs-xs);
    color: var(--c-ink-3);
}
</style>
