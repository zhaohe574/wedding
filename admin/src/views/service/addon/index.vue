<template>
    <div class="addon-page">
        <el-card class="!border-none" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[220px]" label="附加服务名称">
                    <el-input
                        v-model="queryParams.name"
                        placeholder="输入附加服务名称"
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
                <el-button v-perms="['ops.addon/add']" type="primary" @click="handleAdd">
                    <template #icon>
                        <icon name="el-icon-Plus" />
                    </template>
                    新增附加服务
                </el-button>
            </div>

            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" width="80" />
                <el-table-column label="所属人员" min-width="140">
                    <template #default="{ row }">
                        {{ row.staff_name || '-' }}
                    </template>
                </el-table-column>
                <el-table-column label="所属分类" min-width="140">
                    <template #default="{ row }">
                        {{ row.category_name || '-' }}
                    </template>
                </el-table-column>
                <el-table-column label="附加服务名称" prop="name" min-width="180" />
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
                <el-table-column label="状态" width="90">
                    <template #default="{ row }">
                        <el-switch
                            v-perms="['ops.addon/changeStatus']"
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
                        <el-button v-perms="['ops.addon/edit']" type="primary" link @click="handleEdit(row)">
                            编辑
                        </el-button>
                        <el-button v-perms="['ops.addon/delete']" type="danger" link @click="handleDelete(row.id)">
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
            :title="editForm.id ? '编辑附加服务' : '新增附加服务'"
            width="720px"
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
                <el-form-item label="附加服务名称" prop="name">
                    <el-input v-model="editForm.name" placeholder="请输入附加服务名称" maxlength="100" />
                </el-form-item>
                <div class="grid grid-cols-2 gap-4">
                    <el-form-item label="售价" prop="price">
                        <el-input-number v-model="editForm.price" :min="0" :precision="2" class="w-full" />
                    </el-form-item>
                    <el-form-item label="原价" prop="original_price">
                        <el-input-number
                            v-model="editForm.original_price"
                            :min="0"
                            :precision="2"
                            class="w-full"
                        />
                    </el-form-item>
                </div>
                <el-form-item label="图片" prop="image">
                    <material-picker v-model="editForm.image" :limit="1" />
                </el-form-item>
                <el-form-item label="描述" prop="description">
                    <el-input
                        v-model="editForm.description"
                        type="textarea"
                        :rows="4"
                        maxlength="500"
                        show-word-limit
                        placeholder="请输入附加服务描述"
                    />
                </el-form-item>
                <el-form-item label="排序" prop="sort">
                    <el-input-number v-model="editForm.sort" :min="0" :max="9999" class="w-full" />
                </el-form-item>
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

<script lang="ts" setup name="addonLists">
import type { FormInstance } from 'element-plus'
import {
    addonAdd,
    addonChangeStatus,
    addonDelete,
    addonDetail,
    addonEdit,
    addonLists,
    categoryTree
} from '@/api/service'
import { staffAll } from '@/api/staff'
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

const createDefaultForm = () => ({
    id: '',
    staff_id: 0,
    name: '',
    price: 0,
    original_price: 0,
    image: '',
    description: '',
    sort: 0,
    is_show: 1
})

const editForm = reactive(createDefaultForm())

const editRules = reactive({
    staff_id: [{ required: true, message: '请选择所属人员', trigger: 'change' }],
    name: [{ required: true, message: '请输入附加服务名称', trigger: 'blur' }],
    price: [{ required: true, message: '请输入附加服务售价', trigger: 'blur' }]
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: addonLists,
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
}

const handleAdd = () => {
    resetEditForm()
    showEditDialog.value = true
}

const handleEdit = async (row: any) => {
    resetEditForm()
    const detail = await addonDetail({ id: row.id })
    Object.assign(editForm, {
        id: detail.id || '',
        staff_id: Number(detail.staff_id || 0),
        name: detail.name || '',
        price: Number(detail.price || 0),
        original_price: Number(detail.original_price || 0),
        image: detail.image || '',
        description: detail.description || '',
        sort: Number(detail.sort || 0),
        is_show: Number(detail.is_show || 0)
    })
    showEditDialog.value = true
}

const handleSave = async () => {
    await editFormRef.value?.validate()
    const payload = {
        ...editForm
    }
    if (editForm.id) {
        await addonEdit(payload)
    } else {
        await addonAdd(payload)
    }
    showEditDialog.value = false
    getLists()
}

const handleDelete = async (id: number) => {
    await feedback.confirm('确定要删除该附加服务？')
    await addonDelete({ id })
    getLists()
}

const handleChangeStatus = async (is_show: string | number | boolean, id: number) => {
    try {
        await addonChangeStatus({ id, is_show: Number(is_show) })
    } finally {
        getLists()
    }
}

onActivated(() => {
    getLists()
})

getLists()
</script>
