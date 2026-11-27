<template>
    <admin-list-page title="预约附加项" :pager="pager" @change="getLists">
        <template #search>
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[200px]" label="附加项名称">
                    <el-input
                        v-model="queryParams.name"
                        placeholder="输入附加项名称"
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
                            v-for="staff in staffOptions"
                            :key="staff.id"
                            :label="staff.name"
                            :value="staff.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[180px]" label="服务分类">
                    <el-cascader
                        v-model="queryParams.category_id"
                        :options="categories"
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
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </template>

        <template #actions>
            <el-button v-perms="['service.addon/add']" type="primary" @click="handleAdd">
                新增附加项
            </el-button>
        </template>

        <el-table v-loading="pager.loading" :data="pager.lists" size="large">
            <el-table-column label="附加项信息" min-width="240">
                <template #default="{ row }">
                    <div class="addon-cell">
                        <div class="addon-cell__thumb">
                            <el-image
                                v-if="row.image"
                                :src="row.image"
                                fit="cover"
                                :preview-src-list="[row.image]"
                                preview-teleported
                            />
                            <div v-else class="addon-cell__placeholder">
                                <icon name="el-icon-Picture" size="16px" />
                            </div>
                        </div>
                        <div class="addon-cell__main">
                            <div class="addon-cell__name">{{ row.name }}</div>
                            <div v-if="row.description" class="addon-cell__desc">
                                {{ row.description }}
                            </div>
                        </div>
                    </div>
                </template>
            </el-table-column>
            <el-table-column label="所属人员" min-width="130">
                <template #default="{ row }">
                    <div class="staff-cell__name">
                        {{ row.staff_name || staffName(row.staff_id) }}
                    </div>
                    <div v-if="row.category_name" class="staff-cell__category">
                        {{ row.category_name }}
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
                    <el-button v-perms="['service.addon/edit']" type="primary" link @click="handleEdit(row)">
                        编辑
                    </el-button>
                    <el-button
                        v-perms="['service.addon/delete']"
                        type="danger"
                        link
                        @click="handleDelete(row.id)"
                    >
                        删除
                    </el-button>
                </template>
            </el-table-column>
        </el-table>

        <addon-form-drawer
            v-model="showForm"
            mode="admin"
            :staff-options="staffOptions"
            :initial-data="editData"
            :saving="saving"
            @submit="handleSubmit"
        />
    </admin-list-page>
</template>

<script lang="ts" setup name="serviceAddon">
import { formatMoney } from '@shared/utils/format'

import { categoryTree } from '@/api/service'
import {
    addonAdd,
    addonChangeStatus,
    addonDelete,
    addonDetail,
    addonEdit,
    addonLists
} from '@/api/serviceAddon'
import { staffAll } from '@/api/staff'
import AddonFormDrawer from '@/components/service/addon-form-drawer.vue'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'
import { hasPermission } from '@/utils/perm'

const queryParams = reactive({
    name: '',
    staff_id: '',
    category_id: '',
    is_show: ''
})

const staffOptions = ref<any[]>([])
const categories = ref<any[]>([])

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: addonLists,
    params: queryParams
})

const canChangeStatus = computed(() => hasPermission(['service.addon/changeStatus']))

const showForm = ref(false)
const saving = ref(false)
const editData = ref<Record<string, any> | null>(null)
const statusLoadingId = ref<number | null>(null)

const staffName = (id: number) =>
    staffOptions.value.find((staff) => Number(staff.id) === Number(id))?.name || '-'

const loadOptions = async () => {
    const [staffRes, categoryRes] = await Promise.allSettled([staffAll({}), categoryTree()])
    if (staffRes.status === 'fulfilled') {
        const data = staffRes.value
        staffOptions.value = Array.isArray(data) ? data : (data?.lists ?? [])
    }
    if (categoryRes.status === 'fulfilled') {
        categories.value = Array.isArray(categoryRes.value) ? categoryRes.value : []
    }
}

const handleAdd = () => {
    editData.value = null
    showForm.value = true
}

const handleEdit = async (row: any) => {
    try {
        const detail = await addonDetail({ id: row.id })
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
            await addonEdit(payload)
            feedback.msgSuccess('保存成功')
        } else {
            await addonAdd(payload)
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
        await addonChangeStatus({ id: row.id, is_show: next })
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
        await feedback.confirm('确定删除该附加项？')
    } catch {
        return
    }
    await addonDelete({ id })
    feedback.msgSuccess('删除成功')
    getLists()
}

onMounted(() => {
    loadOptions()
    getLists()
})
</script>

<style lang="scss" scoped>
.addon-cell {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.addon-cell__thumb {
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

.addon-cell__placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    color: var(--admin-color-muted);
}

.addon-cell__main {
    min-width: 0;
}

.addon-cell__name {
    overflow: hidden;
    font-size: 14px;
    font-weight: 500;
    color: var(--admin-color-title);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.addon-cell__desc {
    overflow: hidden;
    margin-top: 2px;
    font-size: 12px;
    color: var(--admin-color-muted);
    text-overflow: ellipsis;
    white-space: nowrap;
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
