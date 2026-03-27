<template>
    <div class="category-lists">
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[200px]" label="分类名称">
                    <el-input
                        v-model="queryParams.name"
                        placeholder="输入分类名称"
                        clearable
                        @keyup.enter="resetPage"
                    />
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
                    v-perms="['ops.category/add']"
                    type="primary"
                    @click="handleAdd()"
                >
                    <template #icon>
                        <icon name="el-icon-Plus" />
                    </template>
                    新增分类
                </el-button>
            </div>
            <el-table
                size="large"
                v-loading="pager.loading"
                :data="pager.lists"
            >
                <el-table-column label="分类名称" prop="name" min-width="200" />
                <el-table-column label="图标" width="80">
                    <template #default="{ row }">
                        <el-image
                            v-if="row.icon"
                            :src="row.icon"
                            :style="{ width: '40px', height: '40px' }"
                            fit="contain"
                        />
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="人员数" prop="staff_count" width="100" />
                <el-table-column label="预约关联" min-width="180">
                    <template #default="{ row }">
                        <span>{{ row.booking_relation_summary || '未配置' }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="排序" prop="sort" width="80" />
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-switch
                            v-perms="['ops.category/changeStatus']"
                            v-model="row.is_show"
                            :active-value="1"
                            :inactive-value="0"
                            @change="handleChangeStatus($event, row.id)"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['ops.category/edit']"
                            type="primary"
                            link
                            @click="handleEdit(row)"
                        >
                            编辑
                        </el-button>
                        <el-button
                            v-perms="['ops.category/delete']"
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
            :title="editForm.id ? '编辑分类' : '新增分类'"
            width="500px"
        >
            <el-form
                ref="editFormRef"
                :model="editForm"
                :rules="editRules"
                label-width="100px"
            >
                <el-form-item label="分类名称" prop="name">
                    <el-input v-model="editForm.name" placeholder="请输入分类名称" maxlength="50" />
                </el-form-item>
                <el-form-item label="分类图标" prop="icon">
                    <material-picker v-model="editForm.icon" :limit="1" />
                </el-form-item>
                <el-form-item label="排序" prop="sort">
                    <el-input-number v-model="editForm.sort" :min="0" :max="9999" />
                </el-form-item>
                <el-form-item label="婚礼管家" prop="booking_butler_enabled">
                    <el-switch v-model="editForm.booking_butler_enabled" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item v-if="editForm.booking_butler_enabled === 1" label="管家关联分类" prop="booking_butler_category_id">
                    <el-select v-model="editForm.booking_butler_category_id" placeholder="请选择服务分类" class="w-full">
                        <el-option
                            v-for="item in relatedCategoryOptions"
                            :key="`butler-${item.id}`"
                            :label="item.name"
                            :value="item.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="婚礼督导" prop="booking_director_enabled">
                    <el-switch v-model="editForm.booking_director_enabled" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item v-if="editForm.booking_director_enabled === 1" label="督导关联分类" prop="booking_director_category_id">
                    <el-select v-model="editForm.booking_director_category_id" placeholder="请选择服务分类" class="w-full">
                        <el-option
                            v-for="item in relatedCategoryOptions"
                            :key="`director-${item.id}`"
                            :label="item.name"
                            :value="item.id"
                        />
                    </el-select>
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

<script lang="ts" setup name="categoryLists">
import type { FormInstance } from 'element-plus'
import {
    categoryLists,
    categoryAdd,
    categoryEdit,
    categoryDelete,
    categoryChangeStatus,
    categoryAll
} from '@/api/service'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

const queryParams = reactive({
    name: '',
    is_show: ''
})

const showEditDialog = ref(false)
const editFormRef = shallowRef<FormInstance>()
const categoryOptions = ref<any[]>([])

const editForm = reactive({
    id: '',
    name: '',
    icon: '',
    booking_butler_enabled: 0,
    booking_butler_category_id: 0,
    booking_director_enabled: 0,
    booking_director_category_id: 0,
    sort: 0,
    is_show: 1
})

const editRules = reactive({
    name: [{ required: true, message: '请输入分类名称', trigger: 'blur' }],
    booking_butler_category_id: [
        {
            validator: (_rule: any, value: any, callback: (error?: Error) => void) => {
                if (editForm.booking_butler_enabled === 1 && !Number(value)) {
                    callback(new Error('请选择管家关联分类'))
                    return
                }
                callback()
            },
            trigger: 'change'
        }
    ],
    booking_director_category_id: [
        {
            validator: (_rule: any, value: any, callback: (error?: Error) => void) => {
                if (editForm.booking_director_enabled === 1 && !Number(value)) {
                    callback(new Error('请选择督导关联分类'))
                    return
                }
                callback()
            },
            trigger: 'change'
        }
    ]
})

const relatedCategoryOptions = computed(() => {
    const currentId = Number(editForm.id || 0)
    return categoryOptions.value.filter((item) => Number(item.id) !== currentId)
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: categoryLists,
    params: queryParams
})

const handleAdd = () => {
    Object.assign(editForm, {
        id: '',
        name: '',
        icon: '',
        booking_butler_enabled: 0,
        booking_butler_category_id: 0,
        booking_director_enabled: 0,
        booking_director_category_id: 0,
        sort: 0,
        is_show: 1
    })
    showEditDialog.value = true
}

const handleEdit = (row: any) => {
    Object.assign(editForm, {
        id: row.id,
        name: row.name,
        icon: row.icon,
        booking_butler_enabled: Number(row.booking_butler_enabled || 0),
        booking_butler_category_id: Number(row.booking_butler_category_id || 0),
        booking_director_enabled: Number(row.booking_director_enabled || 0),
        booking_director_category_id: Number(row.booking_director_category_id || 0),
        sort: row.sort,
        is_show: row.is_show
    })
    showEditDialog.value = true
}

const loadCategoryOptions = async () => {
    categoryOptions.value = (await categoryAll()) || []
}

const handleSave = async () => {
    await editFormRef.value?.validate()
    if (editForm.id) {
        await categoryEdit(editForm)
    } else {
        await categoryAdd(editForm)
    }
    showEditDialog.value = false
    getLists()
}

const handleChangeStatus = async (is_show: any, id: number) => {
    try {
        await categoryChangeStatus({ id, is_show })
        getLists()
    } catch (error) {
        getLists()
    }
}

const handleDelete = async (id: number) => {
    await feedback.confirm('确定要删除该分类？')
    await categoryDelete({ id })
    getLists()
}

onActivated(() => {
    loadCategoryOptions()
    getLists()
})

loadCategoryOptions()
getLists()
</script>
