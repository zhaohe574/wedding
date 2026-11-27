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
                    <section-header title="基础信息与定价" flush />
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
                    <form-field
                        label="展示图片"
                        type="upload"
                        layout="stack"
                        :max="1"
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
                    <view class="sec-head">
                        <section-header class="sec-head__sec" title="适用套餐" flush />
                        <view v-if="canWrite && packageOptions.length" class="sec-head__ops">
                            <view
                                hover-class="is-pressed"
                                class="mini-btn"
                                aria-role="button"
                                aria-label="全选套餐"
                                @tap="selectAllPackages"
                            >
                                <text class="mini-btn__text">全选</text>
                            </view>
                            <view
                                hover-class="is-pressed"
                                class="mini-btn"
                                aria-role="button"
                                aria-label="清空已选套餐"
                                @tap="clearPackages"
                            >
                                <text class="mini-btn__text">清空</text>
                            </view>
                        </view>
                    </view>

                    <view v-if="!packageOptions.length" class="empty-block">
                        <text class="empty-block__title">还没有套餐</text>
                        <text class="empty-block__desc"
                            >新增套餐后可在此关联，客户即可加购本附加项</text
                        >
                        <base-button
                            v-if="canWrite"
                            text="去新增套餐"
                            size="sm"
                            variant="ghost"
                            :block="false"
                            @click="goCreatePackage"
                        />
                    </view>

                    <view v-else class="pkg-list">
                        <view
                            v-for="pkg in packageOptions"
                            :key="pkg.id"
                            class="pkg"
                            :class="{ 'is-on': isPackageSelected(pkg.id) }"
                            hover-class="is-pressed"
                            aria-role="checkbox"
                            :aria-checked="isPackageSelected(pkg.id)"
                            :aria-label="pkg.name"
                            @tap="togglePackage(pkg.id)"
                        >
                            <view
                                class="pkg__check"
                                :class="{ 'is-on': isPackageSelected(pkg.id) }"
                            >
                                <tn-icon
                                    v-if="isPackageSelected(pkg.id)"
                                    name="check"
                                    :size="20"
                                    class="pkg__check-icon"
                                />
                            </view>
                            <view class="pkg__main">
                                <text class="pkg__name ellipsis">{{ pkg.name }}</text>
                                <status-tag
                                    v-if="pkg.is_show === 0"
                                    label="已停用"
                                    tone="neutral"
                                    size="sm"
                                    :dot="false"
                                />
                            </view>
                            <text class="pkg__price">¥{{ pkg.price }}</text>
                        </view>
                    </view>
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
                                <text class="pill__text">已停用</text>
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
                <text v-if="!canWrite" class="bar__note">{{ STAFF_WRITE_GATE_TIP }}</text>
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
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/** 服务人员：新增 / 编辑附加项（含「适用套餐」双向关联） */
import { computed, reactive, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import {
    getStaffAddonDetail,
    getStaffPackageList,
    staffAddonAdd,
    staffAddonRemove,
    staffAddonUpdate
} from '@/api/staff-center'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { useStaffWriteGate, STAFF_WRITE_GATE_TIP } from '@/composables/useStaffWriteGate'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { navigate } from '@/utils/link'
import { toast } from '@/composables/useToast'

const editId = ref(0)
const isEdit = computed(() => editId.value > 0)
/** 新增模式从套餐编辑页带过来的预选套餐 */
const prefillPackageId = ref(0)

const { canWrite } = useStaffWriteGate()
const { guardPhase, guardReason, guardError, guardAllowed, verify, onGuardRetry, onGuardAction } =
    useStaffGuard(() => init(true))

const loading = ref(false)
const rawError = ref<unknown>(null)
const error = computed(() => !!rawError.value)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: true }))

const submitting = ref(false)
const deleting = ref(false)
const packageOptions = ref<
    Array<{ id: number; name: string; price: string | number; is_show: number }>
>([])

const form = reactive({
    name: '',
    price: '',
    original_price: '',
    sort: '0',
    description: '',
    is_show: 1 as 0 | 1,
    package_ids: [] as number[]
})
const imageList = ref<string[]>([])
const errors = reactive<Record<string, string>>({})

/** package_ids 可能是数组或逗号串，统一转成去重正整数数组 */
function toIdList(raw: unknown): number[] {
    const source = Array.isArray(raw)
        ? raw
        : typeof raw === 'string'
        ? raw.split(',')
        : typeof raw === 'number'
        ? [raw]
        : []
    const ids = source.map((v) => Number(v)).filter((n) => Number.isInteger(n) && n > 0)
    return Array.from(new Set(ids))
}

function isPackageSelected(id: number): boolean {
    return form.package_ids.includes(Number(id))
}

function togglePackage(id: number) {
    if (!canWrite.value) return
    const numId = Number(id)
    if (isPackageSelected(numId)) {
        form.package_ids = form.package_ids.filter((x) => x !== numId)
    } else {
        form.package_ids = [...form.package_ids, numId]
    }
}

function selectAllPackages() {
    if (!canWrite.value) return
    form.package_ids = packageOptions.value.map((pkg) => pkg.id)
}

function clearPackages() {
    if (!canWrite.value) return
    form.package_ids = []
}

function goCreatePackage() {
    navigate({ path: '/packages/pkg-staff/pages/package-edit/package-edit' })
}

/**
 * 拉取自己的套餐可选项。
 * prune=true 时剔除已不存在的已选项（套餐被删除后不该再提交它）；
 * 其余表单字段不动，从套餐编辑页返回时未保存的编辑内容不丢。
 */
async function loadPackageOptions(prune = true) {
    try {
        const res = await getStaffPackageList({ page: 1, page_size: 100 })
        const rows = Array.isArray(res?.data)
            ? res.data
            : Array.isArray(res?.lists)
            ? res.lists
            : []
        packageOptions.value = rows.map((x) => ({
            id: Number(x.id),
            name: String(x.name || '套餐'),
            price: x.price ?? 0,
            is_show: Number(x.is_show ?? 1)
        }))
        if (prune) {
            const valid = new Set(packageOptions.value.map((pkg) => pkg.id))
            form.package_ids = form.package_ids.filter((id) => valid.has(id))
        }
    } catch {
        // 套餐拉取失败不阻断附加项表单，用户可以先去维护套餐再回来
    }
}

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
            is_show: form.is_show,
            package_ids: form.package_ids
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

let hasLoaded = false

async function init(forceGuard = false) {
    if (!(await verify(forceGuard))) return
    loading.value = true
    rawError.value = null
    try {
        const detailPromise = isEdit.value
            ? getStaffAddonDetail({ addon_id: editId.value })
            : Promise.resolve(null)
        const [detail] = await Promise.all([detailPromise, loadPackageOptions(false)])

        if (detail) {
            form.name = String(detail.name || '')
            form.price =
                detail.price !== undefined && detail.price !== null ? String(detail.price) : ''
            form.original_price =
                Number(detail.original_price || 0) > 0 ? String(detail.original_price) : ''
            form.sort = String(detail.sort ?? 0)
            form.description = String(detail.description || '')
            form.is_show = Number(detail.is_show ?? 1) === 1 ? 1 : 0
            form.package_ids = toIdList(detail.package_ids)
            imageList.value = detail.image ? [String(detail.image)] : []
        } else if (prefillPackageId.value > 0) {
            form.package_ids = [prefillPackageId.value]
        }
    } catch (e) {
        rawError.value = e
    } finally {
        loading.value = false
        hasLoaded = true
    }
}

onLoad((options?: Record<string, any>) => {
    editId.value = Number(options?.id || 0)
    prefillPackageId.value = Number(options?.package_id || 0)
    void init()
})

/** 从套餐编辑页返回时刷新可选项；只动选项，不覆盖用户未保存的表单 */
onShow(() => {
    if (hasLoaded) void loadPackageOptions()
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

.sec-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);

    &__sec {
        flex: 1;
        min-width: 0;
    }

    &__ops {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }
}

.mini-btn {
    min-height: 56rpx;
    padding: 0 var(--sp-3);
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-control-border);
    display: flex;
    align-items: center;

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }
}

.empty-block {
    margin-top: var(--sp-2);
    padding: var(--sp-4) var(--sp-3);
    border-radius: var(--r-md);
    border: 1rpx dashed var(--c-control-border);
    background: var(--c-surface-2);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--sp-1);

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__desc {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        text-align: center;
        line-height: var(--lh-body);
    }
}

.pkg-list {
    margin-top: var(--sp-2);
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.pkg {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    padding: var(--sp-2) var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-surface-2);
    border: 1rpx solid var(--c-line);

    &.is-on {
        border-color: var(--c-brand-text);
        background: var(--c-brand-bg);
    }

    &__check {
        flex-shrink: 0;
        width: 40rpx;
        height: 40rpx;
        border-radius: var(--r-xs);
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        display: flex;
        align-items: center;
        justify-content: center;

        &.is-on {
            border-color: var(--c-brand-text);
            background: var(--c-brand-text);
        }
    }

    &__check-icon {
        color: var(--c-surface);
    }

    &__main {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: var(--sp-1);
    }

    &__name {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__price {
        flex-shrink: 0;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
        font-variant-numeric: tabular-nums;
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
        background: var(--c-active-bg);
        border-color: var(--c-active-border);

        .pill__text {
            color: var(--c-active-fg);
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
