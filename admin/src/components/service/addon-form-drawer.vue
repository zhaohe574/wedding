<template>
    <el-drawer
        v-model="visible"
        :title="drawerTitle"
        size="560px"
        destroy-on-close
        append-to-body
        class="service-form-drawer"
    >
        <el-form ref="formRef" :model="form" :rules="rules" label-position="top" class="service-form">
            <section class="service-form__section">
                <div class="service-form__section-title">基础信息</div>

                <el-form-item v-if="mode === 'admin'" label="所属人员" prop="staff_id">
                    <el-select
                        v-model="form.staff_id"
                        placeholder="选择服务人员"
                        filterable
                        clearable
                        class="w-full"
                        @change="handleStaffChange"
                    >
                        <el-option
                            v-for="staff in staffOptions"
                            :key="staff.id"
                            :label="staff.name"
                            :value="Number(staff.id)"
                        />
                    </el-select>
                </el-form-item>

                <el-form-item label="附加项名称" prop="name">
                    <el-input v-model="form.name" placeholder="如：加急布置、额外跟妆" maxlength="100" />
                </el-form-item>

                <el-form-item label="展示图">
                    <material-picker v-model="form.image" :limit="1" />
                </el-form-item>

                <div class="service-form__grid">
                    <el-form-item label="售价" prop="price">
                        <el-input-number
                            v-model="form.price"
                            :min="0"
                            :precision="2"
                            controls-position="right"
                            class="w-full"
                        />
                    </el-form-item>
                    <el-form-item label="原价">
                        <el-input-number
                            v-model="form.original_price"
                            :min="0"
                            :precision="2"
                            controls-position="right"
                            class="w-full"
                        />
                        <div v-if="originalPriceInvalid" class="service-form__hint">
                            原价低于当前售价，前台不展示划线原价
                        </div>
                    </el-form-item>
                </div>
            </section>

            <section class="service-form__section">
                <div class="service-form__section-head">
                    <div class="service-form__section-title">适用套餐</div>
                    <div class="service-form__section-actions">
                        <el-button link type="primary" :disabled="!optionPackages.length" @click="selectAllPackages">
                            全选
                        </el-button>
                        <el-button link :disabled="!form.package_ids.length" @click="clearPackages">清空</el-button>
                    </div>
                </div>

                <div v-if="packageLoading" class="service-form__empty">加载中…</div>
                <div v-else-if="!optionPackages.length" class="service-form__empty">{{ packageHint }}</div>
                <el-checkbox-group v-else v-model="form.package_ids" class="service-form__option-list">
                    <el-checkbox
                        v-for="pkg in optionPackages"
                        :key="pkg.id"
                        :value="Number(pkg.id)"
                        class="service-form__option"
                    >
                        <span class="service-form__option-name">{{ pkg.name }}</span>
                        <span class="service-form__option-meta">
                            <span class="service-form__option-price">¥{{ formatMoney(pkg.price) }}</span>
                            <el-tag
                                v-if="Number(pkg.is_show) === 0"
                                size="small"
                                type="info"
                                effect="plain"
                            >
                                已下架
                            </el-tag>
                        </span>
                    </el-checkbox>
                </el-checkbox-group>
            </section>

            <section class="service-form__section">
                <div class="service-form__section-title">附加说明</div>
                <el-form-item label-width="0">
                    <el-input
                        v-model="form.description"
                        type="textarea"
                        :rows="3"
                        maxlength="500"
                        show-word-limit
                        placeholder="补充适用场景与内容说明"
                    />
                </el-form-item>
                <div class="service-form__grid">
                    <el-form-item label="排序">
                        <el-input-number
                            v-model="form.sort"
                            :min="0"
                            :max="9999"
                            controls-position="right"
                            class="w-full"
                        />
                    </el-form-item>
                    <el-form-item label="上架状态">
                        <el-radio-group v-model="form.is_show">
                            <el-radio-button :value="1">上架</el-radio-button>
                            <el-radio-button :value="0">下架</el-radio-button>
                        </el-radio-group>
                    </el-form-item>
                </div>
            </section>
        </el-form>

        <template #footer>
            <div class="service-form__footer">
                <el-button @click="visible = false">取消</el-button>
                <el-button type="primary" :loading="saving" @click="handleSubmit">
                    {{ form.id ? '保存' : '创建' }}
                </el-button>
            </div>
        </template>
    </el-drawer>
</template>

<script setup lang="ts">
import { formatMoney } from '@shared/utils/format'
import type { FormInstance } from 'element-plus'

import { packageLists } from '@/api/service'

interface AddonFormState {
    id: number
    staff_id: number | undefined
    name: string
    price: number
    original_price: number
    image: string
    package_ids: number[]
    description: string
    sort: number
    is_show: number
}

const props = withDefaults(
    defineProps<{
        modelValue: boolean
        mode?: 'admin' | 'staff'
        staffOptions?: any[]
        packages?: any[]
        initialData?: Record<string, any> | null
        saving?: boolean
    }>(),
    {
        mode: 'staff',
        staffOptions: () => [],
        packages: () => [],
        initialData: null,
        saving: false
    }
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', payload: Record<string, any>): void
}>()

const visible = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})

const createEmptyForm = (): AddonFormState => ({
    id: 0,
    staff_id: undefined,
    name: '',
    price: 0,
    original_price: 0,
    image: '',
    package_ids: [],
    description: '',
    sort: 0,
    is_show: 1
})

const form = reactive<AddonFormState>(createEmptyForm())

const drawerTitle = computed(() => (form.id ? '编辑附加项' : '新增附加项'))

const formRef = shallowRef<FormInstance>()

const rules = {
    staff_id: [{ required: true, message: '请选择所属人员', trigger: 'change' }],
    name: [{ required: true, message: '请输入附加项名称', trigger: 'blur' }],
    price: [{ required: true, message: '请输入附加项价格', trigger: 'blur' }]
}

const originalPriceInvalid = computed(() => {
    const original = Number(form.original_price)
    const current = Number(form.price)
    return original > 0 && original < current
})

const adminPackageOptions = ref<any[]>([])
const packageLoading = ref(false)
const packageHint = ref('请先选择所属人员')
let packageRequestId = 0

const optionPackages = computed(() => (props.mode === 'admin' ? adminPackageOptions.value : props.packages))

const loadPackageOptions = async (staffId: number | undefined) => {
    adminPackageOptions.value = []
    if (!staffId) {
        packageHint.value = '请先选择所属人员'
        return
    }
    const requestId = ++packageRequestId
    packageLoading.value = true
    try {
        const res = await packageLists({ staff_id: staffId, page_no: 1, page_size: 200 })
        if (requestId !== packageRequestId) return
        const list = Array.isArray(res?.lists) ? res.lists : Array.isArray(res) ? res : []
        adminPackageOptions.value = list
        packageHint.value = list.length ? '' : '该人员暂无可选套餐'
    } catch {
        if (requestId === packageRequestId) {
            packageHint.value = '套餐加载失败，可稍后重试'
        }
    } finally {
        if (requestId === packageRequestId) {
            packageLoading.value = false
        }
    }
}

const normalizeForm = (data: Record<string, any> | null | undefined): AddonFormState => ({
    id: Number(data?.id ?? data?.addon_id ?? 0),
    staff_id: data?.staff_id ? Number(data.staff_id) : undefined,
    name: String(data?.name ?? ''),
    price: Number(data?.price ?? 0),
    original_price: Number(data?.original_price ?? 0),
    image: String(data?.image ?? ''),
    package_ids: Array.isArray(data?.package_ids)
        ? data.package_ids.map((id: any) => Number(id)).filter((id: number) => id > 0)
        : [],
    description: String(data?.description ?? ''),
    sort: Number(data?.sort ?? 0),
    is_show: Number(data?.is_show ?? 1)
})

watch(
    () => props.modelValue,
    (open) => {
        if (!open) return
        Object.assign(form, createEmptyForm(), normalizeForm(props.initialData))
        formRef.value?.clearValidate()
        if (props.mode === 'admin') {
            void loadPackageOptions(form.staff_id)
        }
    }
)

const handleStaffChange = () => {
    form.package_ids = []
    void loadPackageOptions(form.staff_id)
}

const selectAllPackages = () => {
    form.package_ids = optionPackages.value.map((pkg) => Number(pkg.id)).filter((id) => id > 0)
}

const clearPackages = () => {
    form.package_ids = []
}

const handleSubmit = async () => {
    const valid = await formRef.value?.validate().catch(() => false)
    if (!valid) return

    const payload: Record<string, any> = {
        id: form.id,
        name: form.name.trim(),
        price: Number(form.price ?? 0),
        original_price: Number(form.original_price ?? 0),
        image: form.image,
        package_ids: [...form.package_ids],
        description: form.description,
        sort: Number(form.sort ?? 0),
        is_show: Number(form.is_show ?? 1)
    }
    if (props.mode === 'admin') {
        payload.staff_id = form.staff_id
    }
    emit('submit', payload)
}
</script>

<style lang="scss" scoped>
.service-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.service-form__section {
    padding: 16px;
    border: 1px solid var(--admin-color-border);
    border-radius: var(--admin-radius-md);
    background: var(--admin-surface-card);
}

.service-form__section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 12px;
}

.service-form__section-title {
    margin-bottom: 12px;
    font-size: 14px;
    font-weight: 600;
    color: var(--admin-color-title);
}

.service-form__section-head .service-form__section-title {
    margin-bottom: 0;
}

.service-form__section-actions {
    display: flex;
    align-items: center;
    gap: 4px;
}

.service-form__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0 16px;
}

.service-form__hint {
    margin-top: 4px;
    font-size: 12px;
    line-height: 1.5;
    color: var(--el-color-warning);
}

.service-form__empty {
    padding: 12px;
    border-radius: var(--admin-radius-sm);
    background: var(--admin-surface-soft);
    font-size: 13px;
    color: var(--admin-color-muted);
}

.service-form__option-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.service-form__option {
    display: flex;
    align-items: center;
    height: auto;
    margin-right: 0;
    padding: 9px 12px;
    border: 1px solid var(--admin-color-border);
    border-radius: var(--admin-radius-sm);
    transition: border-color 0.16s ease, background 0.16s ease;

    &.is-checked {
        border-color: var(--el-color-primary);
        background: var(--el-color-primary-light-9);
    }

    :deep(.el-checkbox__label) {
        display: flex;
        flex: 1;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        min-width: 0;
        padding-left: 8px;
    }
}

.service-form__option-name {
    overflow: hidden;
    font-size: 13px;
    color: var(--admin-color-title);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.service-form__option-meta {
    display: inline-flex;
    flex: none;
    align-items: center;
    gap: 6px;
}

.service-form__option-price {
    font-size: 13px;
    color: var(--el-color-danger);
}

.service-form__footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

@media (max-width: 720px) {
    .service-form__grid {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
