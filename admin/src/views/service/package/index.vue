<template>
    <div class="package-page">
        <el-card class="!border-none" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[220px]" label="套餐名称">
                    <el-input
                        v-model="queryParams.name"
                        placeholder="输入套餐名称"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[220px]" label="所属人员">
                    <el-select
                        v-model="queryParams.staff_id"
                        placeholder="选择人员"
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
                <el-form-item class="w-[220px]" label="服务分类">
                    <el-cascader
                        v-model="queryParams.category_id"
                        :options="optionsData.categories"
                        :props="{ value: 'id', label: 'name', checkStrictly: true, emitPath: false }"
                        placeholder="选择服务分类"
                        clearable
                    />
                </el-form-item>
                <el-form-item class="w-[180px]" label="状态">
                    <el-select v-model="queryParams.is_show" placeholder="选择状态" clearable>
                        <el-option label="上架" :value="1" />
                        <el-option label="下架" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <div class="mb-4">
                <el-button v-perms="['ops.package/add']" type="primary" @click="handleAdd">
                    <template #icon>
                        <icon name="el-icon-Plus" />
                    </template>
                    新增套餐
                </el-button>
            </div>

            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" width="80" />
                <el-table-column label="所属人员" min-width="140">
                    <template #default="{ row }">
                        {{ row.staff_name || '-' }}
                    </template>
                </el-table-column>
                <el-table-column label="套餐名称" prop="name" min-width="180" />
                <el-table-column label="可选附加服务" min-width="140">
                    <template #default="{ row }">
                        <span v-if="Array.isArray(row.addon_ids) && row.addon_ids.length">
                            {{ row.addon_ids.length }} 项
                        </span>
                        <span v-else class="text-gray-400">未配置</span>
                    </template>
                </el-table-column>
                <el-table-column label="地区价" min-width="180">
                    <template #default="{ row }">
                        <package-region-price-summary
                            :region-prices="row.region_prices"
                            empty-class="text-gray-400"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="价格" width="180">
                    <template #default="{ row }">
                        <span class="text-red-500 font-bold">¥{{ row.price }}</span>
                        <span
                            v-if="Number(row.original_price || 0) > Number(row.price || 0)"
                            class="text-xs text-gray-400 line-through ml-2"
                        >
                            ¥{{ row.original_price }}
                        </span>
                    </template>
                </el-table-column>
                <el-table-column label="推荐" width="90">
                    <template #default="{ row }">
                        <el-tag :type="row.is_recommend ? 'warning' : 'info'">
                            {{ row.is_recommend ? '推荐' : '普通' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="90">
                    <template #default="{ row }">
                        <el-switch
                            v-model="row.is_show"
                            :active-value="1"
                            :inactive-value="0"
                            @change="handleChangeStatus($event, row.id)"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="排序" prop="sort" width="90" />
                <el-table-column label="创建时间" prop="create_time" width="180" />
                <el-table-column label="操作" width="160" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['ops.package/edit']" type="primary" link @click="handleEdit(row)">
                            编辑
                        </el-button>
                        <el-button v-perms="['ops.package/delete']" type="danger" link @click="handleDelete(row.id)">
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-dialog
            v-model="showEditDialog"
            :title="editForm.id ? '编辑套餐' : '新增套餐'"
            width="960px"
        >
            <el-form ref="editFormRef" :model="editForm" :rules="editRules" label-width="100px">
                <el-form-item label="所属人员" prop="staff_id">
                    <el-select
                        v-model="editForm.staff_id"
                        placeholder="请选择所属人员"
                        filterable
                        class="w-full"
                    >
                        <el-option
                            v-for="staff in optionsData.staffList"
                            :key="staff.id"
                            :label="staff.name"
                            :value="staff.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="套餐名称" prop="name">
                    <el-input v-model="editForm.name" placeholder="请输入套餐名称" maxlength="100" />
                </el-form-item>
                <div class="grid grid-cols-2 gap-4">
                    <el-form-item label="套餐价格" prop="price">
                        <el-input-number v-model="editForm.price" :min="0" :precision="2" class="w-full" />
                    </el-form-item>
                    <el-form-item label="原价" prop="original_price">
                        <el-input-number v-model="editForm.original_price" :min="0" :precision="2" class="w-full" />
                    </el-form-item>
                </div>
                <el-form-item label="封面图" prop="image">
                    <material-picker v-model="editForm.image" :limit="1" />
                </el-form-item>
                <el-form-item label="描述" prop="description">
                    <el-input
                        v-model="editForm.description"
                        type="textarea"
                        :rows="4"
                        maxlength="500"
                        show-word-limit
                        placeholder="请输入套餐描述"
                    />
                </el-form-item>
                <el-form-item label="可选附加服务" prop="addon_ids">
                    <el-select
                        v-model="editForm.addon_ids"
                        multiple
                        collapse-tags
                        collapse-tags-tooltip
                        clearable
                        filterable
                        class="w-full"
                        :disabled="!editForm.staff_id"
                        placeholder="先选择所属人员，再配置可选附加服务"
                    >
                        <el-option
                            v-for="addon in staffAddonOptions"
                            :key="addon.id"
                            :label="addon.is_show ? addon.name : `${addon.name}（已下架）`"
                            :value="addon.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="地区价格">
                    <package-region-price-editor v-model="editForm.region_prices" />
                </el-form-item>
                <div class="grid grid-cols-2 gap-4">
                    <el-form-item label="排序" prop="sort">
                        <el-input-number v-model="editForm.sort" :min="0" :max="9999" class="w-full" />
                    </el-form-item>
                    <el-form-item label="是否推荐" prop="is_recommend">
                        <el-radio-group v-model="editForm.is_recommend">
                            <el-radio :value="1">是</el-radio>
                            <el-radio :value="0">否</el-radio>
                        </el-radio-group>
                    </el-form-item>
                </div>
                <el-form-item label="状态" prop="is_show">
                    <el-radio-group v-model="editForm.is_show">
                        <el-radio :value="1">上架</el-radio>
                        <el-radio :value="0">下架</el-radio>
                    </el-radio-group>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showEditDialog = false">取消</el-button>
                <el-button type="primary" @click="handleSave">保存</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="packageLists">
import type { FormInstance } from 'element-plus'
import {
    categoryTree,
    packageAdd,
    packageChangeStatus,
    packageDelete,
    packageDetail,
    packageEdit,
    packageLists
} from '@/api/service'
import { staffAll, staffGetAddonConfig } from '@/api/staff'
import PackageRegionPriceEditor from '@/components/service/package-region-price-editor.vue'
import PackageRegionPriceSummary from '@/components/service/package-region-price-summary.vue'
import { useDictOptions } from '@/hooks/useDictOptions'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

const queryParams = reactive({
    name: '',
    staff_id: '',
    category_id: '',
    is_show: ''
})

const showEditDialog = ref(false)
const editFormRef = shallowRef<FormInstance>()
const staffAddonOptions = ref<any[]>([])

const createDefaultForm = () => ({
    id: '',
    staff_id: 0,
    name: '',
    price: 0,
    original_price: 0,
    image: '',
    description: '',
    addon_ids: [] as number[],
    region_prices: [] as Record<string, any>[],
    sort: 0,
    is_recommend: 0,
    is_show: 1
})

const editForm = reactive(createDefaultForm())

const editRules = reactive({
    staff_id: [{ required: true, message: '请选择所属人员', trigger: 'change' }],
    name: [{ required: true, message: '请输入套餐名称', trigger: 'blur' }],
    price: [{ required: true, message: '请输入套餐价格', trigger: 'blur' }]
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

const resetEditForm = () => {
    Object.assign(editForm, createDefaultForm())
    staffAddonOptions.value = []
}

const loadStaffAddonOptions = async (staffId: number) => {
    if (!staffId) {
        staffAddonOptions.value = []
        editForm.addon_ids = []
        return
    }

    const res = await staffGetAddonConfig({ staff_id: staffId })
    const list = Array.isArray(res) ? res : []
    staffAddonOptions.value = list.map((item: any) => ({
        ...item,
        id: Number(item.id || 0),
        is_show: Number(item.is_show ?? 1)
    }))

    const validIds = new Set(staffAddonOptions.value.map((item: any) => Number(item.id)))
    editForm.addon_ids = editForm.addon_ids
        .map((id: any) => Number(id))
        .filter((id: number) => validIds.has(id))
}

const handleAdd = () => {
    resetEditForm()
    showEditDialog.value = true
}

const handleEdit = async (row: any) => {
    resetEditForm()
    try {
        const detail = await packageDetail({ id: row.id })
        Object.assign(editForm, {
            id: detail.id,
            staff_id: Number(detail.staff_id || 0),
            name: detail.name || '',
            price: Number(detail.price || 0),
            original_price: Number(detail.original_price || 0),
            image: detail.image || '',
            description: detail.description || '',
            addon_ids: Array.isArray(detail.addon_ids)
                ? detail.addon_ids.map((id: any) => Number(id))
                : [],
            region_prices: Array.isArray(detail.region_prices) ? detail.region_prices : [],
            sort: Number(detail.sort || 0),
            is_recommend: Number(detail.is_recommend || 0),
            is_show: Number(detail.is_show || 0)
        })
        showEditDialog.value = true
    } catch (error) {
        console.error('加载套餐详情失败', error)
    }
}

const handleSave = async () => {
    await editFormRef.value?.validate()
    const payload = {
        ...editForm
    }
    if (editForm.id) {
        await packageEdit(payload)
    } else {
        await packageAdd(payload)
    }
    showEditDialog.value = false
    getLists()
}

const handleDelete = async (id: number) => {
    await feedback.confirm('确定要删除该套餐？')
    await packageDelete({ id })
    getLists()
}

const handleChangeStatus = async (is_show: string | number | boolean, id: number) => {
    try {
        await packageChangeStatus({ id, is_show: Number(is_show) })
    } finally {
        getLists()
    }
}

onActivated(() => {
    getLists()
})

watch(
    () => editForm.staff_id,
    (staffId) => {
        loadStaffAddonOptions(Number(staffId || 0)).catch((error) => {
            console.error('加载套餐附加服务选项失败', error)
        })
    }
)

getLists()
</script>
