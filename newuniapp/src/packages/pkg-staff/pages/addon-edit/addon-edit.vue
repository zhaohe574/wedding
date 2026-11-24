<template>
    <page-shell
        :title="isEdit ? '编辑附加项' : '新增附加项'"
        bg="page"
        :show-back="true"
        :bottom-bar="guardAllowed"
    >
        <staff-guard
            v-if="!guardAllowed"
            :phase="guardPhase"
            :reason="guardReason"
            :error="guardError"
            :skeleton-rows="5"
            @retry="onGuardRetry"
            @action="onGuardAction"
        />

        <template v-else>
            <view v-if="loading" class="page-pad">
                <base-skeleton :rows="5" />
            </view>

            <base-empty
                v-else-if="error"
                status="error"
                :error-kind="view?.errorKind || 'unknown'"
                :error-text="view?.title"
                :desc="view?.desc"
                :primary-action="view?.primaryAction"
                fixed
                @primary="onPrimary"
                @retry="() => init(true)"
            />

            <view v-else class="ae">
                <view v-if="!canWrite" class="gate-note">
                    <tn-icon name="tips" :size="24" class="gate-note__icon" />
                    <text class="gate-note__text">{{ STAFF_WRITE_GATE_TIP }}</text>
                </view>

                <base-card>
                    <section-header title="基础信息" flush />
                    <form-field
                        label="附加项名称"
                        type="input"
                        :required="true"
                        :maxlength="100"
                        placeholder="如：航拍 4K 延时片段"
                        :error="errors.name"
                        :model-value="form.name"
                        @update:model-value="(v: string) => (form.name = v)"
                    />
                    <form-field label="销售价格" :required="true" :error="errors.price">
                        <base-input
                            type="digit"
                            placeholder="元，如 399"
                            :model-value="form.price"
                            @update:model-value="(v: string) => (form.price = v)"
                        >
                            <template #suffix>
                                <text class="unit">元</text>
                            </template>
                        </base-input>
                    </form-field>
                    <form-field label="市场原价" :error="errors.original_price">
                        <base-input
                            type="digit"
                            placeholder="选填，元"
                            :model-value="form.original_price"
                            @update:model-value="(v: string) => (form.original_price = v)"
                        >
                            <template #suffix>
                                <text class="unit">元</text>
                            </template>
                        </base-input>
                    </form-field>
                    <form-field label="排序权重">
                        <base-input
                            type="number"
                            placeholder="选填，数字越大越靠前"
                            :model-value="form.sort"
                            @update:model-value="(v: string) => (form.sort = v)"
                        />
                    </form-field>
                </base-card>

                <base-card>
                    <section-header title="图片与说明" flush />
                    <form-field
                        label="展示图片"
                        type="upload"
                        layout="stack"
                        :max="1"
                        hint="选填，建议上传附加项示意图"
                        :model-value="imageList"
                        @update:model-value="(v: string[]) => (imageList = v)"
                    />
                    <form-field
                        label="服务说明"
                        type="textarea"
                        layout="stack"
                        :maxlength="500"
                        placeholder="介绍具体包含的服务内容或加购说明（选填，最多 500 字）"
                        :model-value="form.description"
                        @update:model-value="(v: string) => (form.description = v)"
                    />
                </base-card>

                <base-card>
                    <section-header title="上架设置" flush />
                    <form-field label="上架状态">
                        <view class="pills">
                            <view
                                class="pill"
                                :class="{ 'is-on': form.is_show === 1 }"
                                hover-class="is-pressed"
                                @tap="form.is_show = 1"
                            >
                                <text class="pill__text">已上架</text>
                            </view>
                            <view
                                class="pill"
                                :class="{ 'is-on': form.is_show === 0 }"
                                hover-class="is-pressed"
                                @tap="form.is_show = 0"
                            >
                                <text class="pill__text">已下架</text>
                            </view>
                        </view>
                    </form-field>
                </base-card>

                <view v-if="isEdit && canWrite" class="danger-zone">
                    <base-button
                        text="删除附加项"
                        variant="danger"
                        size="md"
                        :loading="deleting"
                        @click="onDelete"
                    />
                </view>
            </view>
        </template>

        <template #bottom>
            <bottom-bar v-if="guardAllowed">
                <text class="bar__note">{{
                    canWrite ? '保存后可在套餐编辑页关联加购' : STAFF_WRITE_GATE_TIP
                }}</text>
                <template #actions>
                    <base-button
                        v-if="canWrite"
                        :text="submitting ? '保存中…' : isEdit ? '保存修改' : '确认新增'"
                        variant="ink"
                        size="lg"
                        :block="false"
                        :loading="submitting"
                        :disabled="submitting"
                        @click="submit"
                    />
                </template>
            </bottom-bar>
        </template>
    </page-shell>
    <app-toast />
</template>

<script setup lang="ts">
/** 服务人员：新增 / 编辑附加项 */
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import {
    getStaffAddonDetail,
    staffAddonAdd,
    staffAddonRemove,
    staffAddonUpdate
} from '@/api/staff-center'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { useStaffWriteGate, STAFF_WRITE_GATE_TIP } from '@/composables/useStaffWriteGate'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { toast } from '@/composables/useToast'

const editId = ref(0)
const isEdit = computed(() => editId.value > 0)

const { canWrite } = useStaffWriteGate()
const {
    guardPhase,
    guardReason,
    guardError,
    guardAllowed,
    verify,
    onGuardRetry,
    onGuardAction
} = useStaffGuard(() => init(true))

const loading = ref(false)
const rawError = ref<unknown>(null)
const error = computed(() => !!rawError.value)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: true }))

const submitting = ref(false)
const deleting = ref(false)

const form = reactive({
    name: '',
    price: '',
    original_price: '',
    sort: '0',
    description: '',
    is_show: 1 as 0 | 1
})
const imageList = ref<string[]>([])
const errors = reactive<Record<string, string>>({})

function validate(): boolean {
    Object.keys(errors).forEach((k) => delete errors[k])
    if (!form.name.trim()) {
        errors.name = '请填写附加项名称'
        toast.warning(errors.name)
        return false
    }
    if (form.price.trim() === '' || Number.isNaN(Number(form.price)) || Number(form.price) < 0) {
        errors.price = '请填写有效的销售价格'
        toast.warning(errors.price)
        return false
    }
    if (
        form.original_price.trim() !== '' &&
        (Number.isNaN(Number(form.original_price)) || Number(form.original_price) < 0)
    ) {
        errors.original_price = '市场原价不能小于 0'
        toast.warning(errors.original_price)
        return false
    }
    return true
}

async function submit() {
    if (!canWrite.value) {
        toast.info(STAFF_WRITE_GATE_TIP)
        return
    }
    if (submitting.value || !validate()) return

    submitting.value = true
    try {
        const payload = {
            name: form.name.trim(),
            price: Number(form.price),
            original_price: form.original_price.trim() ? Number(form.original_price) : 0,
            sort: form.sort.trim() ? Math.max(0, parseInt(form.sort, 10) || 0) : 0,
            description: form.description.trim(),
            image: imageList.value[0] || '',
            is_show: form.is_show
        }
        if (isEdit.value) {
            await staffAddonUpdate({ ...payload, addon_id: editId.value })
            toast.success('保存成功')
        } else {
            await staffAddonAdd(payload)
            toast.success('新增附加项成功')
        }
        setTimeout(() => uni.navigateBack(), 450)
    } catch (e: any) {
        toast.error(String(e?.message || '提交失败，请稍后重试'))
    } finally {
        submitting.value = false
    }
}

function onDelete() {
    if (!isEdit.value || deleting.value) return
    uni.showModal({
        title: '删除附加项',
        content: '删除后不可恢复，并会解除套餐关联，确认删除？',
        confirmText: '删除',
        cancelText: '取消',
        success: async (r) => {
            if (!r.confirm) return
            deleting.value = true
            try {
                await staffAddonRemove({ addon_id: editId.value })
                toast.success('已删除')
                setTimeout(() => uni.navigateBack(), 450)
            } catch (e: any) {
                toast.error(String(e?.message || '删除失败，请稍后重试'))
            } finally {
                deleting.value = false
            }
        }
    })
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        void init(true)
        return
    }
    if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    uni.navigateBack()
}

async function init(forceGuard = false) {
    if (!(await verify(forceGuard))) return
    if (!isEdit.value) return
    loading.value = true
    rawError.value = null
    try {
        const detail = await getStaffAddonDetail({ addon_id: editId.value })
        if (detail) {
            form.name = String(detail.name || '')
            form.price = detail.price !== undefined && detail.price !== null ? String(detail.price) : ''
            form.original_price =
                Number(detail.original_price || 0) > 0 ? String(detail.original_price) : ''
            form.sort = String(detail.sort ?? 0)
            form.description = String(detail.description || '')
            form.is_show = Number(detail.is_show ?? 1) === 1 ? 1 : 0
            imageList.value = detail.image ? [String(detail.image)] : []
        }
    } catch (e) {
        rawError.value = e
    } finally {
        loading.value = false
    }
}

onLoad((options?: Record<string, any>) => {
    editId.value = Number(options?.id || 0)
    void init()
})
</script>

<style lang="scss" scoped>
.ae {
    padding: var(--sp-3) var(--sp-4) calc(var(--sp-6) + var(--safe-bottom));
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
}

.gate-note {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    padding: var(--sp-2) var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-warning-bg);
    border: 1rpx solid var(--c-warning-border);

    &__icon {
        color: var(--c-warning);
    }

    &__text {
        flex: 1;
        font-size: var(--fs-xs);
        color: var(--c-warning);
        line-height: var(--lh-body);
    }
}

.unit {
    font-size: var(--fs-xs);
    color: var(--c-ink-3);
}

.pills {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
}

.pill {
    padding: 10rpx 24rpx;
    border-radius: var(--r-pill);
    background: var(--c-bg);
    border: 1rpx solid var(--c-control-border);

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &.is-on {
        background: var(--c-ink);
        border-color: var(--c-ink);

        .pill__text {
            color: var(--c-on-dark);
            font-weight: var(--fw-medium);
        }
    }
}

.danger-zone {
    padding-top: var(--sp-2);
}

.bar__note {
    font-size: var(--fs-xs);
    color: var(--c-ink-3);
}
</style>
