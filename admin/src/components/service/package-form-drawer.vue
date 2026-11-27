<template>
    <el-drawer
        v-model="visible"
        :title="drawerTitle"
        size="780px"
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

                <el-form-item label="套餐名称" prop="name">
                    <el-input v-model="form.name" placeholder="输入套餐名称" maxlength="100" />
                </el-form-item>

                <el-form-item label="封面图">
                    <material-picker v-model="form.image" :limit="1" />
                </el-form-item>

                <div class="service-form__grid service-form__grid--3">
                    <el-form-item label="服务时长">
                        <div class="service-form__suffix-field">
                            <el-input-number
                                v-model="form.duration"
                                :min="0"
                                :max="240"
                                placeholder="0 表示不限"
                                controls-position="right"
                                class="w-full"
                            />
                            <span class="service-form__suffix">小时</span>
                        </div>
                    </el-form-item>
                    <el-form-item label="排序">
                        <el-input-number
                            v-model="form.sort"
                            :min="0"
                            :max="9999"
                            controls-position="right"
                            class="w-full"
                        />
                    </el-form-item>
                </div>

                <div class="service-form__grid">
                    <el-form-item label="上架状态">
                        <el-radio-group v-model="form.is_show">
                            <el-radio-button :value="1">上架</el-radio-button>
                            <el-radio-button :value="0">下架</el-radio-button>
                        </el-radio-group>
                    </el-form-item>
                    <el-form-item label="推荐">
                        <el-radio-group v-model="form.is_recommend">
                            <el-radio-button :value="1">推荐</el-radio-button>
                            <el-radio-button :value="0">普通</el-radio-button>
                        </el-radio-group>
                    </el-form-item>
                </div>
            </section>

            <section class="service-form__section">
                <div class="service-form__section-title">定价与地区覆盖</div>

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

                <div class="service-form__block">
                    <div class="service-form__block-head">
                        <span class="service-form__block-title">地区价</span>
                        <el-tag
                            v-if="!form.region_prices.length"
                            type="warning"
                            size="small"
                            effect="plain"
                        >
                            未配置地区价，按区县预约时不可见
                        </el-tag>
                        <span v-else class="service-form__block-count">
                            {{ form.region_prices.length }} 条
                        </span>
                    </div>
                    <package-region-price-editor
                        v-model="form.region_prices"
                        :options-api-scene="optionsApiScene"
                    />
                </div>
            </section>

            <section class="service-form__section">
                <div class="service-form__section-head">
                    <div class="service-form__section-title">可选附加项</div>
                    <div class="service-form__section-actions">
                        <el-button link type="primary" :disabled="!optionAddons.length" @click="selectAllAddons">
                            全选
                        </el-button>
                        <el-button link :disabled="!form.addon_ids.length" @click="clearAddons">清空</el-button>
                    </div>
                </div>

                <div v-if="addonLoading" class="service-form__empty">加载中…</div>
                <div v-else-if="!optionAddons.length" class="service-form__empty">{{ addonHint }}</div>
                <el-checkbox-group v-else v-model="form.addon_ids" class="service-form__addon-grid">
                    <el-checkbox
                        v-for="addon in optionAddons"
                        :key="addon.id"
                        :value="Number(addon.id)"
                        class="service-form__addon-option"
                    >
                        <span class="service-form__addon-name">{{ addon.name }}</span>
                        <span class="service-form__addon-meta">
                            <span class="service-form__addon-price">¥{{ formatMoney(addon.price) }}</span>
                            <el-tag
                                v-if="Number(addon.is_show) === 0"
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
                <div class="service-form__section-title">服务说明</div>
                <el-form-item label-width="0">
                    <el-input
                        v-model="form.description"
                        type="textarea"
                        :rows="3"
                        maxlength="500"
                        show-word-limit
                        placeholder="补充服务内容、交付标准等说明"
                    />
                </el-form-item>
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

import { addonLists } from '@/api/serviceAddon'

import PackageRegionPriceEditor from './package-region-price-editor.vue'

interface PackageFormState {
    id: number
    staff_id: number | undefined
    name: string
    image: string
    duration: number
    sort: number
    is_show: number
    is_recommend: number
    price: number
    original_price: number
    region_prices: Record<string, any>[]
    addon_ids: number[]
    description: string
}

const props = withDefaults(
    defineProps<{
        modelValue: boolean
        mode?: 'admin' | 'staff'
        optionsApiScene?: 'service' | 'staffCenter'
        staffOptions?: any[]
        addons?: any[]
        initialData?: Record<string, any> | null
        saving?: boolean
    }>(),
    {
        mode: 'staff',
        optionsApiScene: 'service',
        staffOptions: () => [],
        addons: () => [],
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

const formRef = shallowRef<FormInstance>()

const createEmptyForm = (): PackageFormState => ({
    id: 0,
    staff_id: undefined,
    name: '',
    image: '',
    duration: 0,
    sort: 0,
    is_show: 1,
    is_recommend: 0,
    price: 0,
    original_price: 0,
    region_prices: [],
    addon_ids: [],
    description: ''
})

const form = reactive<PackageFormState>(createEmptyForm())

const drawerTitle = computed(() => (form.id ? '编辑套餐' : '新增套餐'))

const rules = {
    staff_id: [{ required: true, message: '请选择所属人员', trigger: 'change' }],
    name: [{ required: true, message: '请输入套餐名称', trigger: 'blur' }],
    price: [{ required: true, message: '请输入套餐价格', trigger: 'blur' }]
}

const originalPriceInvalid = computed(() => {
    const original = Number(form.original_price)
    const current = Number(form.price)
    return original > 0 && original < current
})

const adminAddonOptions = ref<any[]>([])
const addonLoading = ref(false)
const addonHint = ref('请先选择所属人员')
let addonRequestId = 0

const optionAddons = computed(() => (props.mode === 'admin' ? adminAddonOptions.value : props.addons))

const loadAddonOptions = async (staffId: number | undefined) => {
    adminAddonOptions.value = []
    if (!staffId) {
        addonHint.value = '请先选择所属人员'
        return
    }
    const requestId = ++addonRequestId
    addonLoading.value = true
    try {
        const res = await addonLists({ staff_id: staffId, page_no: 1, page_size: 200 })
        if (requestId !== addonRequestId) return
        const list = Array.isArray(res?.lists) ? res.lists : Array.isArray(res) ? res : []
        adminAddonOptions.value = list
        addonHint.value = list.length ? '' : '该人员暂无可选附加项'
    } catch {
        if (requestId === addonRequestId) {
            addonHint.value = '附加项加载失败，可稍后重试'
        }
    } finally {
        if (requestId === addonRequestId) {
            addonLoading.value = false
        }
    }
}

const normalizeForm = (data: Record<string, any> | null | undefined): PackageFormState => ({
    id: Number(data?.id ?? 0),
    staff_id: data?.staff_id ? Number(data.staff_id) : undefined,
    name: String(data?.name ?? ''),
    image: String(data?.image ?? ''),
    duration: Number(data?.duration ?? 0),
    sort: Number(data?.sort ?? 0),
    is_show: Number(data?.is_show ?? 1),
    is_recommend: Number(data?.is_recommend ?? 0),
    price: Number(data?.price ?? 0),
    original_price: Number(data?.original_price ?? 0),
    region_prices: Array.isArray(data?.region_prices) ? data.region_prices.map((item: any) => ({ ...item })) : [],
    addon_ids: Array.isArray(data?.addon_ids)
        ? data.addon_ids.map((id: any) => Number(id)).filter((id: number) => id > 0)
        : [],
    description: String(data?.description ?? '')
})

watch(
    () => props.modelValue,
    (open) => {
        if (!open) return
        Object.assign(form, createEmptyForm(), normalizeForm(props.initialData))
        formRef.value?.clearValidate()
        if (props.mode === 'admin') {
            void loadAddonOptions(form.staff_id)
        }
    }
)

const handleStaffChange = () => {
    form.addon_ids = []
    void loadAddonOptions(form.staff_id)
}

const selectAllAddons = () => {
    form.addon_ids = optionAddons.value.map((addon) => Number(addon.id)).filter((id) => id > 0)
}

const clearAddons = () => {
    form.addon_ids = []
}

const handleSubmit = async () => {
    const valid = await formRef.value?.validate().catch(() => false)
    if (!valid) return

    const payload: Record<string, any> = {
        id: form.id,
        name: form.name.trim(),
        image: form.image,
        duration: Number(form.duration ?? 0),
        sort: Number(form.sort ?? 0),
        is_show: Number(form.is_show ?? 1),
        is_recommend: Number(form.is_recommend ?? 0),
        price: Number(form.price ?? 0),
        original_price: Number(form.original_price ?? 0),
        region_prices: form.region_prices.map((item) => ({ ...item })),
        addon_ids: [...form.addon_ids],
        description: form.description
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

.service-form__grid--3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.service-form__suffix-field {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
}

.service-form__suffix {
    flex: none;
    font-size: 13px;
    color: var(--admin-color-muted);
}

.service-form__hint {
    margin-top: 4px;
    font-size: 12px;
    line-height: 1.5;
    color: var(--el-color-warning);
}

.service-form__block {
    margin-top: 4px;
}

.service-form__block-head {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}

.service-form__block-title {
    font-size: 13px;
    font-weight: 500;
    color: var(--admin-color-title);
}

.service-form__block-count {
    font-size: 12px;
    color: var(--admin-color-muted);
}

.service-form__empty {
    padding: 12px;
    border-radius: var(--admin-radius-sm);
    background: var(--admin-surface-soft);
    font-size: 13px;
    color: var(--admin-color-muted);
}

.service-form__addon-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.service-form__addon-option {
    display: flex;
    align-items: center;
    height: auto;
    margin-right: 0;
    padding: 10px 12px;
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

.service-form__addon-name {
    overflow: hidden;
    font-size: 13px;
    color: var(--admin-color-title);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.service-form__addon-meta {
    display: inline-flex;
    flex: none;
    align-items: center;
    gap: 6px;
}

.service-form__addon-price {
    font-size: 13px;
    color: var(--el-color-danger);
}

.service-form__footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

@media (max-width: 720px) {
    .service-form__grid,
    .service-form__grid--3,
    .service-form__addon-grid {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
