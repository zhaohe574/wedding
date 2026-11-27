<template>
    <admin-page-shell class="staff-center-package" title="我的套餐">
        <StaffProfileHeader active="package" :profile="formData" :routes="routes" />

        <el-alert v-if="loadError" type="error" :title="loadError" :closable="false" show-icon>
            <template #default>
                <el-button type="primary" link @click="loadProfile">重试</el-button>
            </template>
        </el-alert>

        <section class="admin-page-card staff-center-package__workbench">
            <header class="workbench-head">
                <el-segmented v-model="activePane" :options="segmentOptions" />
                <el-button type="primary" @click="handleCreate">
                    {{ activePane === 'package' ? '新增套餐' : '新增附加项' }}
                </el-button>
            </header>

            <el-table
                v-if="activePane === 'package'"
                v-loading="packageLoading"
                :data="staffPackages"
                size="large"
            >
                <el-table-column label="套餐信息" min-width="220">
                    <template #default="{ row }">
                        <div class="entity-cell">
                            <div class="entity-cell__thumb">
                                <el-image
                                    v-if="row.image"
                                    :src="row.image"
                                    fit="cover"
                                    :preview-src-list="[row.image]"
                                    preview-teleported
                                />
                                <div v-else class="entity-cell__placeholder">
                                    <icon name="el-icon-Picture" size="16px" />
                                </div>
                            </div>
                            <div class="entity-cell__main">
                                <div class="entity-cell__name">{{ row.name }}</div>
                                <div class="entity-cell__tags">
                                    <el-tag
                                        v-if="Number(row.is_recommend) === 1"
                                        type="warning"
                                        size="small"
                                        effect="plain"
                                    >
                                        推荐
                                    </el-tag>
                                    <el-tag v-if="Number(row.duration) > 0" size="small" effect="plain">
                                        {{ row.duration }}小时
                                    </el-tag>
                                </div>
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="基础价" width="150">
                    <template #default="{ row }">
                        <span class="price-main">¥{{ formatMoney(row.price) }}</span>
                        <span
                            v-if="Number(row.original_price) > Number(row.price)"
                            class="price-original"
                        >
                            ¥{{ formatMoney(row.original_price) }}
                        </span>
                    </template>
                </el-table-column>
                <el-table-column label="地区价" min-width="150">
                    <template #default="{ row }">
                        <el-tag
                            v-if="!row.region_prices?.length"
                            type="warning"
                            size="small"
                            effect="plain"
                        >
                            未配置
                        </el-tag>
                        <package-region-price-summary v-else :region-prices="row.region_prices" />
                    </template>
                </el-table-column>
                <el-table-column label="附加项" width="100">
                    <template #default="{ row }">
                        <span v-if="row.addon_ids?.length">{{ row.addon_ids.length }} 项</span>
                        <span v-else class="text-placeholder">未关联</span>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="95">
                    <template #default="{ row }">
                        <el-switch
                            :model-value="Number(row.is_show) === 1"
                            :loading="statusLoadingKey === `package-${row.id}`"
                            @change="togglePackageStatus(row)"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="140" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="openEditPackage(row)">编辑</el-button>
                        <el-button type="danger" link @click="handleDeletePackage(row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>

            <el-table v-else v-loading="addonLoading" :data="staffAddons" size="large">
                <el-table-column label="附加项信息" min-width="240">
                    <template #default="{ row }">
                        <div class="entity-cell">
                            <div class="entity-cell__thumb">
                                <el-image
                                    v-if="row.image"
                                    :src="row.image"
                                    fit="cover"
                                    :preview-src-list="[row.image]"
                                    preview-teleported
                                />
                                <div v-else class="entity-cell__placeholder">
                                    <icon name="el-icon-Picture" size="16px" />
                                </div>
                            </div>
                            <div class="entity-cell__main">
                                <div class="entity-cell__name">{{ row.name }}</div>
                                <div v-if="row.description" class="entity-cell__desc">
                                    {{ row.description }}
                                </div>
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="价格" width="150">
                    <template #default="{ row }">
                        <span class="price-main">¥{{ formatMoney(row.price) }}</span>
                        <span
                            v-if="Number(row.original_price) > Number(row.price)"
                            class="price-original"
                        >
                            ¥{{ formatMoney(row.original_price) }}
                        </span>
                    </template>
                </el-table-column>
                <el-table-column label="适用套餐" width="110">
                    <template #default="{ row }">
                        <span v-if="row.package_ids?.length">{{ row.package_ids.length }} 个套餐</span>
                        <span v-else class="text-placeholder">未关联</span>
                    </template>
                </el-table-column>
                <el-table-column label="排序" prop="sort" width="80" />
                <el-table-column label="状态" width="95">
                    <template #default="{ row }">
                        <el-switch
                            :model-value="Number(row.is_show) === 1"
                            :loading="statusLoadingKey === `addon-${row.id}`"
                            @change="toggleAddonStatus(row)"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="140" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="openEditAddon(row)">编辑</el-button>
                        <el-button type="danger" link @click="handleDeleteAddon(row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>
        </section>

        <PackageFormDrawer
            v-model="showPackageDrawer"
            mode="staff"
            options-api-scene="staffCenter"
            :addons="staffAddons"
            :initial-data="editingPackage"
            :saving="packageSaving"
            @submit="submitPackage"
        />
        <AddonFormDrawer
            v-model="showAddonDrawer"
            mode="staff"
            :packages="staffPackages"
            :initial-data="editingAddon"
            :saving="addonSaving"
            @submit="submitAddon"
        />
    </admin-page-shell>
</template>

<script setup lang="ts" name="staffCenterPackage">
import { formatMoney } from '@shared/utils/format'
import { ElMessage } from 'element-plus'
import { onMounted, ref } from 'vue'

import {
    myProfileAddonAdd,
    myProfileAddonDelete,
    myProfileAddonList,
    myProfileAddonUpdate,
    myProfileCreatePackage,
    myProfileUpdatePackageConfig,
    myProfileUpdateStaffPackage,
} from '@/api/staff-center'
import AddonFormDrawer from '@/components/service/addon-form-drawer.vue'
import PackageFormDrawer from '@/components/service/package-form-drawer.vue'
import PackageRegionPriceSummary from '@/components/service/package-region-price-summary.vue'
import feedback from '@/utils/feedback'

import StaffProfileHeader from '../profile/components/StaffProfileHeader.vue'
import { useStaffCenterProfile } from '../profile/use-staff-center-profile'
import type { StaffPackageItem } from '../profile/use-staff-center-profile'

type StaffAddonRow = {
    id: number
    name: string
    price: number
    original_price: number
    image: string
    description: string
    package_ids: number[]
    sort: number
    is_show: number
} & Record<string, unknown>

const activePane = ref<'package' | 'addon'>('package')
const addonLoading = ref(false)
const packageLoading = ref(false)
const staffAddons = ref<StaffAddonRow[]>([])
const statusLoadingKey = ref('')

const showPackageDrawer = ref(false)
const showAddonDrawer = ref(false)
const packageSaving = ref(false)
const addonSaving = ref(false)
const editingPackage = ref<Record<string, any> | null>(null)
const editingAddon = ref<Record<string, any> | null>(null)

const {
    deleteStaffPackage,
    formData,
    loadError,
    loadPackageConfig,
    loadProfile,
    resolveStaffCenterPath,
    staffPackages,
} = useStaffCenterProfile()

const routes = {
    profile: resolveStaffCenterPath('page.staff_center.profile', 'profile'),
    showcase: resolveStaffCenterPath('page.staff_center.showcase', 'showcase'),
    package: resolveStaffCenterPath('page.staff_center.package', 'package'),
    work: resolveStaffCenterPath('page.staff_center.work', 'work'),
    certificate: resolveStaffCenterPath('page.staff_center.certificate', 'certificate'),
}

const segmentOptions = computed(() => [
    { label: `人员套餐 (${staffPackages.value.length})`, value: 'package' },
    { label: `预约附加项 (${staffAddons.value.length})`, value: 'addon' },
])

const refreshPackages = async () => {
    packageLoading.value = true
    try {
        await loadPackageConfig()
    } finally {
        packageLoading.value = false
    }
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
            package_ids: Array.isArray(item.package_ids)
                ? item.package_ids.map((id: any) => Number(id))
                : [],
            sort: Number(item.sort ?? 0),
            is_show: Number(item.is_show ?? 1),
        })) as StaffAddonRow[]
    } finally {
        addonLoading.value = false
    }
}

const refreshAfterPackageChange = async () => {
    await Promise.all([refreshPackages(), loadAddonConfig()])
    void loadProfile()
}

const refreshAfterAddonChange = async () => {
    await Promise.all([loadAddonConfig(), refreshPackages()])
    void loadProfile()
}

const handleCreate = () => {
    if (activePane.value === 'package') {
        editingPackage.value = null
        showPackageDrawer.value = true
        return
    }
    editingAddon.value = null
    showAddonDrawer.value = true
}

const openEditPackage = (row: StaffPackageItem) => {
    editingPackage.value = { ...row }
    showPackageDrawer.value = true
}

const openEditAddon = (row: StaffAddonRow) => {
    editingAddon.value = { ...row }
    showAddonDrawer.value = true
}

const submitPackage = async (payload: Record<string, any>) => {
    packageSaving.value = true
    try {
        const body = { ...payload }
        delete body.id
        if (payload.id) {
            await myProfileUpdateStaffPackage({ package_id: payload.id, ...body })
            ElMessage.success('保存成功')
        } else {
            await myProfileCreatePackage(body)
            ElMessage.success('创建成功')
        }
        showPackageDrawer.value = false
        await refreshAfterPackageChange()
    } finally {
        packageSaving.value = false
    }
}

const submitAddon = async (payload: Record<string, any>) => {
    addonSaving.value = true
    try {
        const body = { ...payload }
        delete body.id
        if (payload.id) {
            await myProfileAddonUpdate({ addon_id: payload.id, ...body })
            ElMessage.success('保存成功')
        } else {
            await myProfileAddonAdd(body)
            ElMessage.success('创建成功')
        }
        showAddonDrawer.value = false
        await refreshAfterAddonChange()
    } finally {
        addonSaving.value = false
    }
}

const togglePackageStatus = async (row: StaffPackageItem) => {
    const next = Number(row.is_show) === 1 ? 0 : 1
    statusLoadingKey.value = `package-${row.id}`
    try {
        await myProfileUpdatePackageConfig({ package_id: row.id, is_show: next })
        row.is_show = next
        ElMessage.success('操作成功')
    } catch {
        // 失败保持原状态，开关按 model-value 自动回位
    } finally {
        statusLoadingKey.value = ''
    }
}

const toggleAddonStatus = async (row: StaffAddonRow) => {
    const next = Number(row.is_show) === 1 ? 0 : 1
    statusLoadingKey.value = `addon-${row.id}`
    try {
        await myProfileAddonUpdate({ addon_id: row.id, is_show: next })
        row.is_show = next
        ElMessage.success('操作成功')
    } catch {
        // 失败保持原状态，开关按 model-value 自动回位
    } finally {
        statusLoadingKey.value = ''
    }
}

const handleDeletePackage = async (row: StaffPackageItem) => {
    const deleted = await deleteStaffPackage(row)
    if (!deleted) return
    await loadAddonConfig()
    void loadProfile()
}

const handleDeleteAddon = async (row: StaffAddonRow) => {
    try {
        await feedback.confirm(`确定要删除附加项「${row.name}」吗？`)
    } catch {
        return
    }
    await myProfileAddonDelete({ addon_id: row.id })
    ElMessage.success('删除成功')
    await refreshAfterAddonChange()
}

onMounted(() => {
    void Promise.allSettled([loadProfile(), refreshPackages(), loadAddonConfig()])
})
</script>

<style lang="scss" scoped>
.staff-center-package__workbench {
    padding: 16px;
}

.workbench-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}

.entity-cell {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.entity-cell__thumb {
    flex: none;
    width: 40px;
    height: 40px;
    overflow: hidden;
    border-radius: var(--admin-radius-sm);
    background: var(--admin-surface-soft);

    :deep(.el-image) {
        width: 100%;
        height: 100%;
    }
}

.entity-cell__placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    color: var(--admin-color-muted);
}

.entity-cell__main {
    min-width: 0;
}

.entity-cell__name {
    overflow: hidden;
    font-size: 14px;
    font-weight: 500;
    color: var(--admin-color-title);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.entity-cell__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 4px;
}

.entity-cell__desc {
    overflow: hidden;
    margin-top: 2px;
    font-size: 12px;
    color: var(--admin-color-muted);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.price-main {
    font-weight: 600;
    color: var(--el-color-danger);
}

.price-original {
    margin-left: 6px;
    font-size: 12px;
    color: var(--admin-color-muted);
    text-decoration: line-through;
}

.text-placeholder {
    font-size: 12px;
    color: var(--admin-color-muted);
}

@media (max-width: 720px) {
    .workbench-head {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
