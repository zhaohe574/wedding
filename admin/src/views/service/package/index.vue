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
                <el-table-column label="服务分类" prop="category_name" min-width="120" />
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
                <el-form-item label="套餐名称" prop="name">
                    <el-input v-model="editForm.name" placeholder="请输入套餐名称" maxlength="100" />
                </el-form-item>
                <el-form-item label="服务分类" prop="category_id">
                    <el-cascader
                        v-model="editForm.category_id"
                        :options="optionsData.categories"
                        :props="{ value: 'id', label: 'name', checkStrictly: true, emitPath: false }"
                        placeholder="请选择服务分类"
                        class="w-full"
                    />
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
                <el-form-item label="套餐内容" prop="content">
                    <div class="w-full">
                        <el-tag
                            v-for="(item, index) in editForm.content"
                            :key="`${index}-${item}`"
                            closable
                            class="mr-2 mb-2"
                            @close="editForm.content.splice(index, 1)"
                        >
                            {{ item }}
                        </el-tag>
                        <el-input
                            v-if="showContentInput"
                            v-model="contentInputValue"
                            size="small"
                            class="w-[220px]"
                            @keyup.enter="handleAddContent"
                            @blur="handleAddContent"
                        />
                        <el-button v-else size="small" @click="showContentInput = true">+ 添加内容</el-button>
                    </div>
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
    packageEdit,
    packageLists
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
const showContentInput = ref(false)
const contentInputValue = ref('')
const editFormRef = shallowRef<FormInstance>()

const createDefaultForm = () => ({
    id: '',
    staff_id: 0,
    name: '',
    category_id: '',
    price: 0,
    original_price: 0,
    content: [] as string[],
    image: '',
    description: '',
    sort: 0,
    is_recommend: 0,
    is_show: 1
})

const editForm = reactive(createDefaultForm())

const editRules = reactive({
    staff_id: [{ required: true, message: '请选择所属人员', trigger: 'change' }],
    name: [{ required: true, message: '请输入套餐名称', trigger: 'blur' }],
    category_id: [{ required: true, message: '请选择服务分类', trigger: 'change' }],
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
    showContentInput.value = false
    contentInputValue.value = ''
}

const handleAddContent = () => {
    const value = contentInputValue.value.trim()
    if (value) {
        editForm.content.push(value)
    }
    contentInputValue.value = ''
    showContentInput.value = false
}

const handleAdd = () => {
    resetEditForm()
    showEditDialog.value = true
}

const handleEdit = (row: any) => {
    resetEditForm()
    Object.assign(editForm, {
        id: row.id,
        staff_id: row.staff_id || 0,
        name: row.name || '',
        category_id: row.category_id || '',
        price: Number(row.price || 0),
        original_price: Number(row.original_price || 0),
        content: Array.isArray(row.content) ? row.content : [],
        image: row.image || '',
        description: row.description || '',
        sort: Number(row.sort || 0),
        is_recommend: Number(row.is_recommend || 0),
        is_show: Number(row.is_show || 0)
    })
    showEditDialog.value = true
}

const handleSave = async () => {
    await editFormRef.value?.validate()
    const payload = {
        ...editForm,
        content: editForm.content.filter((item) => item)
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

const handleChangeStatus = async (is_show: number, id: number) => {
    try {
        await packageChangeStatus({ id, is_show })
    } finally {
        getLists()
    }
}

onActivated(() => {
    getLists()
})

getLists()
</script>
