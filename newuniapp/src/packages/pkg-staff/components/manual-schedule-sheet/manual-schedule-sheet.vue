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
                <text class="sheet__title">{{ isEdit ? '编辑线下档期' : '添加线下档期' }}</text>
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
                    <text class="field__label">服务日期 *</text>
                    <picker
                        mode="date"
                        :value="form.date || todayStr"
                        :start="todayStr"
                        @change="onPickDate"
                    >
                        <view hover-class="is-pressed" class="field__picker">
                            <text class="field__picker-text">{{ form.date || '请选择服务日期' }}</text>
                            <tn-icon name="right" :size="24" class="field__picker-icon" />
                        </view>
                    </picker>
                </view>

                <view class="field">
                    <text class="field__label">服务名称 *</text>
                    <input
                        v-model="form.service_name"
                        class="field__input"
                        placeholder="如：户外草坪婚礼主持"
                        placeholder-class="field__ph"
                        maxlength="100"
                    />
                </view>

                <view class="field">
                    <text class="field__label">客户姓名</text>
                    <input
                        v-model="form.customer_name"
                        class="field__input"
                        placeholder="选填"
                        placeholder-class="field__ph"
                        maxlength="50"
                    />
                </view>

                <view class="field">
                    <text class="field__label">客户手机号</text>
                    <input
                        v-model="form.customer_mobile"
                        class="field__input"
                        type="number"
                        placeholder="选填，11 位手机号"
                        placeholder-class="field__ph"
                        maxlength="11"
                    />
                </view>

                <view class="field">
                    <text class="field__label">服务地区</text>
                    <input
                        v-model="form.region_name"
                        class="field__input"
                        placeholder="选填，如：成都市高新区"
                        placeholder-class="field__ph"
                        maxlength="100"
                    />
                </view>

                <view class="field">
                    <text class="field__label">服务地址</text>
                    <input
                        v-model="form.service_address"
                        class="field__input"
                        placeholder="选填，详细酒店或场地名称"
                        placeholder-class="field__ph"
                        maxlength="255"
                    />
                </view>

                <view class="field">
                    <text class="field__label">备注</text>
                    <input
                        v-model="form.remark"
                        class="field__input"
                        placeholder="选填，流程或注意事项"
                        placeholder-class="field__ph"
                        maxlength="255"
                    />
                </view>
            </scroll-view>

            <view class="sheet__foot">
                <view class="sheet__btn">
                    <base-button text="取消" variant="ghost" size="md" block @click="close" />
                </view>
                <view class="sheet__btn">
                    <base-button
                        :text="submitting ? '保存中…' : '保存'"
                        variant="ink"
                        size="md"
                        block
                        :loading="submitting"
                        :disabled="submitting"
                        @click="submit"
                    />
                </view>
            </view>
        </view>
    </tn-popup>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { staffManualScheduleAdd, staffManualScheduleEdit } from '@/api/staff'
import { toast } from '@/composables/useToast'

const props = withDefaults(
    defineProps<{
        modelValue: boolean
        initialDate?: string
        editing?: Record<string, any> | null
    }>(),
    {
        initialDate: '',
        editing: null
    }
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'saved', date: string): void
}>()

function formatToday(): string {
    const d = new Date()
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${y}-${m}-${day}`
}

const todayStr = computed(() => formatToday())
const isEdit = computed(() => Number(props.editing?.manual_schedule_id || props.editing?.id || 0) > 0)
const submitting = ref(false)
const requestId = ref('')

const form = reactive({
    date: '',
    service_name: '',
    customer_name: '',
    customer_mobile: '',
    region_name: '',
    service_address: '',
    remark: ''
})

function newRequestId(): string {
    return `ms-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`
}

function resetForm(): void {
    const src = props.editing
    if (src) {
        form.date = String(src.schedule_date || props.initialDate || todayStr.value).slice(0, 10)
        form.service_name = String(src.service_name || '')
        form.customer_name = String(src.customer_name || '')
        form.customer_mobile = String(src.customer_mobile || '')
        form.region_name = String(src.region_name || '')
        form.service_address = String(src.service_address || '')
        form.remark = String(src.remark || '')
        requestId.value = ''
        return
    }

    form.date =
        props.initialDate && props.initialDate >= todayStr.value
            ? props.initialDate
            : todayStr.value
    form.service_name = ''
    form.customer_name = ''
    form.customer_mobile = ''
    form.region_name = ''
    form.service_address = ''
    form.remark = ''
    requestId.value = newRequestId()
}

watch(
    () => props.modelValue,
    (open) => {
        if (open) resetForm()
    },
    { immediate: true }
)

function onPopupChange(v: boolean): void {
    if (!v && submitting.value) return
    emit('update:modelValue', v)
}

function close(): void {
    if (submitting.value) return
    emit('update:modelValue', false)
}

function onPickDate(e: any): void {
    const picked = String(e?.detail?.value || '').trim().slice(0, 10)
    if (picked) form.date = picked
}

async function submit(): Promise<void> {
    if (submitting.value) return

    const date = form.date.trim()
    const serviceName = form.service_name.trim()
    const customerName = form.customer_name.trim()
    const customerMobile = form.customer_mobile.trim()
    const regionName = form.region_name.trim()
    const serviceAddress = form.service_address.trim()
    const remark = form.remark.trim()

    if (!/^\d{4}-\d{2}-\d{2}$/.test(date) || date < todayStr.value) {
        toast.warning('请选择今天或未来的有效日期')
        return
    }
    if (!serviceName) {
        toast.warning('请输入服务名称')
        return
    }
    if (customerMobile && !/^1\d{10}$/.test(customerMobile)) {
        toast.warning('请输入正确的 11 位客户手机号')
        return
    }

    const basePayload = {
        date,
        service_name: serviceName,
        customer_name: customerName || undefined,
        customer_mobile: customerMobile || undefined,
        region_name: regionName || undefined,
        service_address: serviceAddress || undefined,
        remark: remark || undefined
    }

    submitting.value = true
    try {
        if (isEdit.value) {
            const manualId = Number(props.editing?.manual_schedule_id || props.editing?.id || 0)
            const version = Number(props.editing?.version ?? 0)
            await staffManualScheduleEdit({
                ...basePayload,
                manual_schedule_id: manualId,
                version
            })
            toast.success('已保存修改')
        } else {
            if (!requestId.value) requestId.value = newRequestId()
            await staffManualScheduleAdd({
                ...basePayload,
                request_id: requestId.value
            })
            toast.success('已添加线下档期')
        }
        emit('update:modelValue', false)
        emit('saved', date)
    } catch (e: any) {
        toast.error(String(e?.message || '保存失败，请稍后重试'))
    } finally {
        submitting.value = false
    }
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

    &__label {
        display: block;
        margin-bottom: var(--sp-1);
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        color: var(--c-ink-2);
    }

    &__picker {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 84rpx;
        padding: 0 var(--sp-3);
        border-radius: var(--r-sm);
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        box-sizing: border-box;
    }

    &__picker-text {
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__picker-icon {
        color: var(--c-ink-3);
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

    &__ph {
        color: var(--c-ink-3);
    }
}
</style>
