<template>
    <div class="tag-lists">
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[200px]" label="标签名称">
                    <el-input
                        v-model="queryParams.name"
                        placeholder="输入标签名称"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[200px]" label="标签类型">
                    <el-select v-model="queryParams.type" placeholder="选择类型" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="风格" :value="1" />
                        <el-option label="特长" :value="2" />
                        <el-option label="其他" :value="3" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[200px]" label="服务分类">
                    <el-select v-model="queryParams.category_id" placeholder="选择分类" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="未分类" :value="0" />
                        <el-option
                            v-for="item in categoryOptions"
                            :key="item.id"
                            :label="item.name"
                            :value="item.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[200px]" label="状态">
                    <el-select v-model="queryParams.is_show" placeholder="选择状态" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="显示" :value="1" />
                        <el-option label="隐藏" :value="0" />
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
                <el-button
                    v-perms="['service.styleTag/add']"
                    type="primary"
                    @click="handleAdd()"
                >
                    <template #icon>
                        <icon name="el-icon-Plus" />
                    </template>
                    新增标签
                </el-button>
            </div>
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" width="80" />
                <el-table-column label="标签名称" prop="name" min-width="150" />
                <el-table-column label="标签类型" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getTypeTagType(row.type)">{{ row.type_desc }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="服务分类" min-width="140">
                    <template #default="{ row }">
                        <span>{{ row.category_name || '-' }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="使用人数" prop="staff_count" width="100" />
                <el-table-column label="排序" prop="sort" width="80" />
                <el-table-column label="状态" width="80">
                    <template #default="{ row }">
                        <el-switch
                            v-perms="['service.styleTag/changeStatus']"
                            v-model="row.is_show"
                            :active-value="1"
                            :inactive-value="0"
                            @change="handleChangeStatus($event, row.id)"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="130" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['service.styleTag/edit']"
                            type="primary"
                            link
                            @click="handleEdit(row)"
                        >
                            编辑
                        </el-button>
                        <el-button
                            v-perms="['service.styleTag/delete']"
                            type="danger"
                            link
                            @click="handleDelete(row.id)"
                        >
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <!-- 编辑弹窗 -->
        <el-dialog
            v-model="showEditDialog"
            :title="editForm.id ? '编辑标签' : '新增标签'"
            width="500px"
        >
            <el-form
                ref="editFormRef"
                :model="editForm"
                :rules="editRules"
                label-width="100px"
            >
                <el-form-item label="标签名称" prop="name">
                    <el-input v-model="editForm.name" placeholder="请输入标签名称" maxlength="50" />
                </el-form-item>
                <el-form-item label="标签类型" prop="type">
                    <el-radio-group v-model="editForm.type">
                        <el-radio :value="1">风格</el-radio>
                        <el-radio :value="2">特长</el-radio>
                        <el-radio :value="3">其他</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="服务分类" prop="category_id">
                    <el-select v-model="editForm.category_id" placeholder="选择服务分类" class="w-full">
                        <el-option
                            v-for="item in categoryOptions"
                            :key="item.id"
                            :label="item.name"
                            :value="item.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="排序" prop="sort">
                    <el-input-number v-model="editForm.sort" :min="0" :max="9999" />
                </el-form-item>
                <el-form-item label="状态" prop="is_show">
                    <el-radio-group v-model="editForm.is_show">
                        <el-radio :value="1">显示</el-radio>
                        <el-radio :value="0">隐藏</el-radio>
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

<script lang="ts" setup name="tagLists">
import type { FormInstance } from 'element-plus'
import {
    styleTagLists,
    styleTagAdd,
    styleTagEdit,
    styleTagDelete,
    styleTagChangeStatus,
    categoryAll
} from '@/api/service'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

const queryParams = reactive({
    name: '',
    type: '',
    category_id: '',
    is_show: ''
})

const showEditDialog = ref(false)
const editFormRef = shallowRef<FormInstance>()
const categoryOptions = ref<any[]>([])

const editForm = reactive({
    id: '',
    name: '',
    type: 1,
    category_id: '',
    sort: 0,
    is_show: 1
})

const editRules = reactive({
    name: [{ required: true, message: '请输入标签名称', trigger: 'blur' }],
    type: [{ required: true, message: '请选择标签类型', trigger: 'change' }],
    category_id: [{ required: true, message: '请选择服务分类', trigger: 'change' }]
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: styleTagLists,
    params: queryParams
})

const getTypeTagType = (type: number) => {
    const map: Record<number, string> = {
        1: 'primary',
        2: 'success',
        3: 'info'
    }
    return map[type] || 'info'
}

const handleAdd = () => {
    Object.assign(editForm, {
        id: '',
        name: '',
        type: 1,
        category_id: '',
        sort: 0,
        is_show: 1
    })
    showEditDialog.value = true
}

const handleEdit = (row: any) => {
    Object.assign(editForm, {
        id: row.id,
        name: row.name,
        type: row.type,
        category_id: row.category_id,
        sort: row.sort,
        is_show: row.is_show
    })
    showEditDialog.value = true
}

const handleSave = async () => {
    await editFormRef.value?.validate()
    if (editForm.id) {
        await styleTagEdit(editForm)
    } else {
        await styleTagAdd(editForm)
    }
    showEditDialog.value = false
    getLists()
}

const handleChangeStatus = async (is_show: any, id: number) => {
    try {
        await styleTagChangeStatus({ id, is_show })
        getLists()
    } catch (error) {
        getLists()
    }
}

const handleDelete = async (id: number) => {
    await feedback.confirm('确定要删除该标签？')
    await styleTagDelete({ id })
    getLists()
}

const getCategories = async () => {
    const res = await categoryAll()
    categoryOptions.value = Array.isArray(res) ? res : []
}

onActivated(() => {
    getLists()
})

getCategories()
getLists()
</script>
