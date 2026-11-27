<template>
    <admin-list-page title="人员套餐" :pager="pager" @change="getLists">
        <template #search>
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[200px]" label="套餐名称">
                    <el-input
                        v-model="queryParams.name"
                        placeholder="输入套餐名称"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[180px]" label="所属人员">
                    <el-select
                        v-model="queryParams.staff_id"
                        placeholder="全部人员"
                        clearable
                        filterable
                    >
                        <el-option
                            v-for="staff in optionsData.staffList"
                            :key="staff.id"
                            :label="staff.name"
                            :value="staff.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[180px]" label="服务分类">
                    <el-cascader
                        v-model="queryParams.category_id"
                        :options="optionsData.categories"
                        :props="{ value: 'id', label: 'name', checkStrictly: true, emitPath: false }"
                        placeholder="全部分类"
                        clearable
                    />
                </el-form-item>
                <el-form-item class="w-[140px]" label="状态">
                    <el-select v-model="queryParams.is_show" placeholder="全部" clearable>
                        <el-option label="上架" :value="1" />
                        <el-option label="下架" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[140px]" label="推荐">
                    <el-select v-model="queryParams.is_recommend" placeholder="全部" clearable>
                        <el-option label="推荐" :value="1" />
                        <el-option label="普通" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </template>

        <template #actions>
            <el-button v-perms="['ops.package/add']" type="primary" @click="handleAdd">
                新增套餐
            </el-button>
        </template>

        <el-table v-loading="pager.loading" :data="pager.lists" size="large">
            <el-table-column label="套餐信息" min-width="220">
                <template #default="{ row }">
                    <div class="package-cell">
                        <div class="package-cell__thumb">
                            <el-image
                                v-if="row.image"
                                :src="row.image"
                                fit="cover"
                                :preview-src-list="[row.image]"
                                preview-teleported
                            />
                            <div v-else class="package-cell__placeholder">
                                <icon name="el-icon-Picture" size="16px" />
                            </div>
                        </div>
                        <div class="package-cell__main">
                            <div class="package-cell__name">{{ row.name }}</div>
                            <div class="package-cell__tags">
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
            <el-table-column label="所属人员" min-width="130">
                <template #default="{ row }">
                    <div class="staff-cell__name">{{ row.staff_name || '-' }}</div>
                    <div v-if="row.category_name" class="staff-cell__category">{{ row.category_name }}</div>
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
            <el-table-column label="地区价" min-width="160">
                <template #default="{ row }">
                    <package-region-price-summary
                        :region-prices="row.region_prices"
                        empty-text="未配置"
                        empty-class="text-warning text-xs"
                    />
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
                        :disabled="!canChangeStatus"
                        :loading="statusLoadingId === row.id"
                        @change="handleChangeStatus(row)"
                    />
                </template>
            </el-table-column>
            <el-table-column label="排序" prop="sort" width="80" />
            <el-table-column label="创建时间" prop="create_time" width="170" />
            <el-table-column label="操作" width="140" fixed="right">
                <template #default="{ row }">
                    <el-button v-perms="['ops.package/edit']" type="primary" link @click="handleEdit(row)">
                        编辑
                    </el-button>
                    <el-button
                        v-perms="['ops.package/delete']"
                        type="danger"
                        link
                        @click="handleDelete(row.id)"
                    >
                        删除
                    </el-button>
                </template>
            </el-table-column>
        </el-table>

        <package-form-drawer
            v-model="showForm"
            mode="admin"
            options-api-scene="service"
            :staff-options="optionsData.staffList"
            :initial-data="editData"
            :saving="saving"
            @submit="handleSubmit"
        />
    </admin-list-page>
</template>

<script lang="ts" setup name="packageLists">
import { formatMoney } from '@shared/utils/format'

import {
    categoryTree,
    packageAdd,
    packageChangeStatus,
    packageDelete,
    packageDetail,
    packageEdit,
    packageLists
} from '@/api/service'
import { staffAll } from '@/api/staff'
import PackageFormDrawer from '@/components/service/package-form-drawer.vue'
import PackageRegionPriceSummary from '@/components/service/package-region-price-summary.vue'
import { useDictOptions } from '@/hooks/useDictOptions'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'
import { hasPermission } from '@/utils/perm'

const queryParams = reactive({
    name: '',
    staff_id: '',
    category_id: '',
    is_show: '',
    is_recommend: ''
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: packageLists,
    params: queryParams
})

const { optionsData } = useDictOptions<{
    categories: any[]
    staffList: any[]
}>({
    categories: {
        api: categoryTree
    },
    staffList: {
        api: staffAll
    }
})

const canChangeStatus = computed(() => hasPermission(['ops.package/changeStatus']))

const showForm = ref(false)
const saving = ref(false)
const editData = ref<Record<string, any> | null>(null)
const statusLoadingId = ref<number | null>(null)

const handleAdd = () => {
    editData.value = null
    showForm.value = true
}

const handleEdit = async (row: any) => {
    try {
        const detail = await packageDetail({ id: row.id })
        editData.value = detail as Record<string, any>
    } catch {
        editData.value = { ...row }
    }
    showForm.value = true
}

const handleSubmit = async (payload: Record<string, any>) => {
    saving.value = true
    try {
        if (payload.id) {
            await packageEdit(payload)
            feedback.msgSuccess('保存成功')
        } else {
            await packageAdd(payload)
            feedback.msgSuccess('创建成功')
        }
        showForm.value = false
        getLists()
    } finally {
        saving.value = false
    }
}

const handleChangeStatus = async (row: any) => {
    const next = Number(row.is_show) === 1 ? 0 : 1
    statusLoadingId.value = row.id
    try {
        await packageChangeStatus({ id: row.id, is_show: next })
        row.is_show = next
        feedback.msgSuccess('操作成功')
    } catch {
        // 失败保持原状态，开关按 model-value 自动回位
    } finally {
        statusLoadingId.value = null
    }
}

const handleDelete = async (id: number) => {
    try {
        await feedback.confirm('确定要删除该套餐？')
    } catch {
        return
    }
    await packageDelete({ id })
    feedback.msgSuccess('删除成功')
    getLists()
}

onActivated(() => {
    getLists()
})

getLists()
</script>

<style lang="scss" scoped>
.package-cell {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.package-cell__thumb {
    flex: none;
    width: 48px;
    height: 48px;
    overflow: hidden;
    border-radius: var(--admin-radius-sm);
    background: var(--admin-surface-soft);

    :deep(.el-image) {
        width: 100%;
        height: 100%;
    }
}

.package-cell__placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    color: var(--admin-color-muted);
}

.package-cell__main {
    min-width: 0;
}

.package-cell__name {
    overflow: hidden;
    font-size: 14px;
    font-weight: 500;
    color: var(--admin-color-title);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.package-cell__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 4px;
}

.staff-cell__name {
    font-size: 14px;
    color: var(--admin-color-title);
}

.staff-cell__category {
    margin-top: 2px;
    font-size: 12px;
    color: var(--admin-color-muted);
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
</style>
