<template>
    <admin-page-shell class="staff-center-package" title="我的套餐">
        <StaffProfileHeader active="package" :profile="formData" :routes="routes" />

        <el-alert
            v-if="loadError"
            type="error"
            :title="loadError"
            :closable="false"
            show-icon
        >
            <template #default>
                <el-button type="primary" link @click="loadProfile">重试</el-button>
            </template>
        </el-alert>

        <section class="admin-page-section">
            <admin-table-toolbar>
                <template #right>
                    <el-button type="primary" @click="openCreateStaffPackage">创建专属套餐</el-button>
                </template>
            </admin-table-toolbar>

            <el-table v-loading="loading" :data="staffPackages" border>
                <el-table-column label="套餐名称" prop="name" min-width="150" />
                <el-table-column label="地区价" min-width="150">
                    <template #default="{ row }">
                        <package-region-price-summary
                            :region-prices="row.region_prices"
                            empty-class="admin-edit-muted"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="价格" prop="price" width="140">
                    <template #default="{ row }">¥{{ row.price }}</template>
                </el-table-column>
                <el-table-column label="原价" width="140">
                    <template #default="{ row }">
                        <span v-if="Number(row.original_price || 0) > 0">¥{{ row.original_price }}</span>
                        <span v-else class="admin-edit-muted">-</span>
                    </template>
                </el-table-column>
                <el-table-column label="推荐" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.is_recommend ? 'warning' : 'info'" effect="plain">
                            {{ row.is_recommend ? '推荐' : '普通' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.is_show ? 'success' : 'info'" effect="plain">
                            {{ row.is_show ? '上架' : '下架' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="160">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="openEditStaffPackage(row)">编辑</el-button>
                        <el-button type="danger" link @click="handleDeleteStaffPackage(row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>
        </section>

        <section class="admin-page-section">
            <admin-table-toolbar>
                <template #right>
                    <el-button type="primary" @click="openCreateStaffAddon">新增附加项</el-button>
                </template>
            </admin-table-toolbar>

            <el-table v-loading="addonLoading" :data="staffAddons" border>
                <el-table-column label="展示图" width="120">
                    <template #default="{ row }">
                        <el-image
                            v-if="row.image"
                            :src="row.image"
                            fit="cover"
                            style="width: 72px; height: 72px; border-radius: 12px"
                            :preview-src-list="[row.image]"
                        />
                        <span v-else class="admin-edit-muted">未设置</span>
                    </template>
                </el-table-column>
                <el-table-column label="附加项名称" prop="name" min-width="160" />
                <el-table-column label="价格" width="140">
                    <template #default="{ row }">¥{{ row.price }}</template>
                </el-table-column>
                <el-table-column label="介绍" min-width="220">
                    <template #default="{ row }">
                        <span v-if="row.description">{{ row.description }}</span>
                        <span v-else class="admin-edit-muted">未填写</span>
                    </template>
                </el-table-column>
                <el-table-column label="排序" prop="sort" width="100" />
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.is_show ? 'success' : 'info'" effect="plain">
                            {{ row.is_show ? '上架' : '下架' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="160">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="openEditStaffAddon(row)">编辑</el-button>
                        <el-button type="danger" link @click="deleteStaffAddon(row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>
        </section>

        <el-dialog
            v-model="showStaffPackageDialog"
            :title="isEditingStaffPackage ? '编辑专属套餐' : '创建专属套餐'"
            width="960px"
            class="staff-edit-dialog"
            @closed="resetStaffPackageForm"
        >
            <el-form
                ref="staffPackageFormRef"
                :model="staffPackageForm"
                :rules="staffPackageRules"
                label-width="100px"
            >
                <el-form-item label="套餐名称" prop="name">
                    <el-input v-model="staffPackageForm.name" placeholder="请输入套餐名称" />
                </el-form-item>
                <el-form-item label="默认价格" prop="price">
                    <el-input-number
                        v-model="staffPackageForm.price"
                        :min="0"
                        :precision="2"
                        class="w-full"
                    />
                </el-form-item>
                <el-form-item label="原价" prop="original_price">
                    <el-input-number
                        v-model="staffPackageForm.original_price"
                        :min="0"
                        :precision="2"
                        class="w-full"
                    />
                </el-form-item>
                <el-form-item label="封面图" prop="image">
                    <material-picker v-model="staffPackageForm.image" :limit="1" />
                </el-form-item>
                <el-form-item label="套餐说明" prop="description">
                    <el-input
                        v-model="staffPackageForm.description"
                        type="textarea"
                        :rows="3"
                        placeholder="请输入套餐说明"
                    />
                </el-form-item>
                <el-form-item label="地区价格">
                    <package-region-price-editor
                        v-model="staffPackageForm.region_prices"
                        options-api-scene="staffCenter"
                    />
                </el-form-item>
                <el-form-item label="可选附加项">
                    <div class="w-full">
                        <el-checkbox-group
                            v-if="staffAddons.length"
                            v-model="staffPackageForm.addon_ids"
                            class="staff-package-addon-grid"
                        >
                            <el-checkbox v-for="addon in staffAddons" :key="addon.id" :label="addon.id">
                                {{ addon.name }}（¥{{ addon.price }}）
                            </el-checkbox>
                        </el-checkbox-group>
                        <div v-else class="admin-edit-muted">请先新增附加项，再为套餐勾选可售附加内容。</div>
                    </div>
                </el-form-item>
                <div class="grid staff-edit-grid gap-x-8">
                    <el-form-item label="排序" prop="sort">
                        <el-input-number v-model="staffPackageForm.sort" :min="0" :max="9999" class="w-full" />
                    </el-form-item>
                    <el-form-item label="推荐" prop="is_recommend">
                        <el-radio-group v-model="staffPackageForm.is_recommend">
                            <el-radio :value="1">是</el-radio>
                            <el-radio :value="0">否</el-radio>
                        </el-radio-group>
                    </el-form-item>
                </div>
                <el-form-item label="状态" prop="is_show">
                    <el-radio-group v-model="staffPackageForm.is_show">
                        <el-radio :value="1">上架</el-radio>
                        <el-radio :value="0">下架</el-radio>
                    </el-radio-group>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showStaffPackageDialog = false">取消</el-button>
                <el-button type="primary" @click="handleSubmitStaffPackage">
                    {{ isEditingStaffPackage ? '保存' : '创建' }}
                </el-button>
            </template>
        </el-dialog>

        <el-dialog
            v-model="showStaffAddonDialog"
            :title="isEditingStaffAddon ? '编辑附加项' : '新增附加项'"
            width="680px"
            class="staff-edit-dialog"
            @closed="resetStaffAddonForm"
        >
            <el-form
                ref="staffAddonFormRef"
                :model="staffAddonForm"
                :rules="staffAddonRules"
                label-width="100px"
            >
                <el-form-item label="附加项名称" prop="name">
                    <el-input v-model="staffAddonForm.name" placeholder="例如：晨袍跟拍" maxlength="100" />
                </el-form-item>
                <el-form-item label="附加项价格" prop="price">
                    <el-input-number
                        v-model="staffAddonForm.price"
                        :min="0"
                        :precision="2"
                        class="w-full"
                    />
                </el-form-item>
                <el-form-item label="展示图" prop="image">
                    <material-picker v-model="staffAddonForm.image" :limit="1" />
                </el-form-item>
                <el-form-item label="附加项介绍" prop="description">
                    <el-input
                        v-model="staffAddonForm.description"
                        type="textarea"
                        :rows="4"
                        maxlength="500"
                        show-word-limit
                        placeholder="请输入附加项介绍，便于客户理解服务场景和内容"
                    />
                </el-form-item>
                <div class="grid staff-edit-grid gap-x-8">
                    <el-form-item label="排序" prop="sort">
                        <el-input-number v-model="staffAddonForm.sort" :min="0" :max="9999" class="w-full" />
                    </el-form-item>
                    <el-form-item label="状态" prop="is_show">
                        <el-radio-group v-model="staffAddonForm.is_show">
                            <el-radio :value="1">上架</el-radio>
                            <el-radio :value="0">下架</el-radio>
                        </el-radio-group>
                    </el-form-item>
                </div>
            </el-form>
            <template #footer>
                <el-button @click="showStaffAddonDialog = false">取消</el-button>
                <el-button type="primary" @click="handleSubmitStaffAddon">
                    {{ isEditingStaffAddon ? '保存' : '创建' }}
                </el-button>
            </template>
        </el-dialog>
    </admin-page-shell>
</template>

<script setup lang="ts" name="staffCenterPackage">
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import { onMounted, reactive, ref, shallowRef } from 'vue'

import {
    myProfileAddonAdd,
    myProfileAddonDelete,
    myProfileAddonList,
    myProfileAddonUpdate,
} from '@/api/staff-center'
import PackageRegionPriceEditor from '@/components/service/package-region-price-editor.vue'
import PackageRegionPriceSummary from '@/components/service/package-region-price-summary.vue'
import feedback from '@/utils/feedback'

import StaffProfileHeader from '../profile/components/StaffProfileHeader.vue'
import {
    staffPackageRules,
    useStaffCenterProfile,
} from '../profile/use-staff-center-profile'
import type { StaffPackageItem } from '../profile/use-staff-center-profile'

type StaffAddonRow = {
    id: number
    name: string
    price: number
    original_price: number
    image: string
    description: string
    sort: number
    is_show: number
} & Record<string, unknown>

const staffPackageFormRef = shallowRef<FormInstance>()
const staffAddonFormRef = shallowRef<FormInstance>()
const addonLoading = ref(false)
const showStaffAddonDialog = ref(false)
const isEditingStaffAddon = ref(false)
const staffAddons = ref<StaffAddonRow[]>([])

const staffAddonForm = reactive({
    addon_id: 0,
    name: '',
    price: 0,
    image: '',
    description: '',
    sort: 0,
    is_show: 1,
})

const staffAddonRules: FormRules = {
    name: [{ required: true, message: '请输入附加项名称', trigger: 'blur' }],
    price: [{ required: true, message: '请输入附加项价格', trigger: 'blur' }],
}

const {
    deleteStaffPackage,
    formData,
    isEditingStaffPackage,
    loadError,
    loadPackageConfig,
    loadProfile,
    loading,
    openCreateStaffPackage,
    openEditStaffPackage,
    resetStaffPackageForm,
    resolveStaffCenterPath,
    showStaffPackageDialog,
    staffPackageForm,
    staffPackages,
    submitStaffPackage,
} = useStaffCenterProfile()

const routes = {
    profile: resolveStaffCenterPath('page.staff_center.profile', 'profile'),
    showcase: resolveStaffCenterPath('page.staff_center.showcase', 'showcase'),
    package: resolveStaffCenterPath('page.staff_center.package', 'package'),
    work: resolveStaffCenterPath('page.staff_center.work', 'work'),
    certificate: resolveStaffCenterPath('page.staff_center.certificate', 'certificate'),
}

const loadAddonConfig = async () => {
    addonLoading.value = true
    try {
        const res = await myProfileAddonList()
        const addonList = Array.isArray(res) ? res : []
        staffAddons.value = addonList.map((item) => ({
            ...item,
            id: Number(item.id ?? 0),
            name: String(item.name ?? ''),
            price: Number(item.price ?? 0),
            original_price: Number(item.original_price ?? 0),
            image: String(item.image ?? ''),
            description: String(item.description ?? ''),
            sort: Number(item.sort ?? 0),
            is_show: Number(item.is_show ?? 1),
        })) as StaffAddonRow[]
    } finally {
        addonLoading.value = false
    }
}

const resetStaffAddonForm = () => {
    Object.assign(staffAddonForm, {
        addon_id: 0,
        name: '',
        price: 0,
        image: '',
        description: '',
        sort: 0,
        is_show: 1,
    })
    isEditingStaffAddon.value = false
}

const openCreateStaffAddon = () => {
    resetStaffAddonForm()
    showStaffAddonDialog.value = true
}

const openEditStaffAddon = (row: Record<string, unknown>) => {
    resetStaffAddonForm()
    isEditingStaffAddon.value = true
    Object.assign(staffAddonForm, {
        addon_id: Number(row.id || 0),
        name: String(row.name || ''),
        price: Number(row.price || 0),
        image: String(row.image || ''),
        description: String(row.description || ''),
        sort: Number(row.sort || 0),
        is_show: Number(row.is_show ?? 1),
    })
    showStaffAddonDialog.value = true
}

const submitStaffAddon = async () => {
    const payload = {
        addon_id: staffAddonForm.addon_id,
        name: staffAddonForm.name,
        price: Number(staffAddonForm.price ?? 0),
        image: staffAddonForm.image,
        description: staffAddonForm.description,
        sort: Number(staffAddonForm.sort ?? 0),
        is_show: Number(staffAddonForm.is_show ?? 1),
    }
    if (isEditingStaffAddon.value) {
        await myProfileAddonUpdate(payload)
        ElMessage.success('更新成功')
    } else {
        await myProfileAddonAdd(payload)
        ElMessage.success('创建成功')
    }
    showStaffAddonDialog.value = false
    resetStaffAddonForm()
    await loadAddonConfig()
}

const deleteStaffAddon = async (row: Record<string, unknown>) => {
    await feedback.confirm(`确定要删除附加项「${String(row.name || '')}」吗？`)
    await myProfileAddonDelete({ addon_id: row.id })
    ElMessage.success('删除成功')
    await loadAddonConfig()
    await loadPackageConfig()
}

const handleSubmitStaffPackage = async () => {
    await staffPackageFormRef.value?.validate()
    await submitStaffPackage()
    void loadProfile()
}

const handleDeleteStaffPackage = async (row: StaffPackageItem) => {
    await deleteStaffPackage(row)
    void loadProfile()
}

const handleSubmitStaffAddon = async () => {
    await staffAddonFormRef.value?.validate()
    await submitStaffAddon()
}

onMounted(() => {
    void Promise.allSettled([loadProfile(), loadPackageConfig(), loadAddonConfig()])
})
</script>

<style lang="scss" scoped>
.staff-center-package {
    .staff-edit-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        row-gap: 18px;
    }

    .staff-package-addon-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 16px;
    }
}

@media (max-width: 1200px) {
    .staff-center-package {
        .staff-edit-grid {
            grid-template-columns: minmax(0, 1fr);
        }
    }
}
</style>
