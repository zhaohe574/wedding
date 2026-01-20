<template>
    <div class="tag-container">
        <el-card shadow="never" class="mb-4">
            <el-form :model="queryParams" inline>
                <el-form-item label="标签类型">
                    <el-select v-model="queryParams.type" placeholder="请选择" clearable style="width: 120px">
                        <el-option label="好评标签" :value="1" />
                        <el-option label="中评标签" :value="2" />
                        <el-option label="差评标签" :value="3" />
                    </el-select>
                </el-form-item>
                <el-form-item label="标签名称">
                    <el-input v-model="queryParams.name" placeholder="请输入" clearable style="width: 150px" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="getList">搜索</el-button>
                    <el-button @click="handleReset">重置</el-button>
                    <el-button type="success" @click="handleAdd">添加标签</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card shadow="never">
            <el-table v-loading="loading" :data="tableData">
                <el-table-column label="ID" width="80" prop="id" />
                <el-table-column label="标签名称" min-width="150">
                    <template #default="{ row }">
                        <el-tag :color="row.color || undefined" effect="plain">{{ row.name }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="类型" width="120">
                    <template #default="{ row }">
                        <el-tag :type="getTypeTagType(row.type)">{{ row.type_text }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="使用次数" width="100" prop="use_count" />
                <el-table-column label="排序" width="80" prop="sort" />
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-switch
                            v-model="row.status"
                            :active-value="1"
                            :inactive-value="0"
                            @change="handleStatusChange(row)"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="150">
                    <template #default="{ row }">
                        <el-button link type="primary" @click="handleEdit(row)">编辑</el-button>
                        <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>

        <!-- 编辑弹窗 -->
        <el-dialog v-model="dialogVisible" :title="dialogTitle" width="500px">
            <el-form :model="formData" label-width="80px" :rules="formRules" ref="formRef">
                <el-form-item label="标签名称" prop="name">
                    <el-input v-model="formData.name" placeholder="请输入标签名称" maxlength="50" />
                </el-form-item>
                <el-form-item label="标签类型" prop="type">
                    <el-select v-model="formData.type" placeholder="请选择">
                        <el-option label="好评标签" :value="1" />
                        <el-option label="中评标签" :value="2" />
                        <el-option label="差评标签" :value="3" />
                    </el-select>
                </el-form-item>
                <el-form-item label="标签颜色">
                    <el-color-picker v-model="formData.color" />
                </el-form-item>
                <el-form-item label="排序">
                    <el-input-number v-model="formData.sort" :min="0" />
                </el-form-item>
                <el-form-item label="状态">
                    <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="dialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSubmit">确定</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox, FormInstance } from 'element-plus'
import {
    getReviewTagList,
    addReviewTag,
    editReviewTag,
    deleteReviewTag,
    changeReviewTagStatus
} from '@/api/review'

const loading = ref(false)
const tableData = ref<any[]>([])
const dialogVisible = ref(false)
const formRef = ref<FormInstance>()

const queryParams = reactive({
    type: undefined as number | undefined,
    name: ''
})

const formData = reactive({
    id: 0,
    name: '',
    type: 1,
    color: '',
    sort: 0,
    status: 1
})

const formRules = {
    name: [{ required: true, message: '请输入标签名称', trigger: 'blur' }],
    type: [{ required: true, message: '请选择标签类型', trigger: 'change' }]
}

const dialogTitle = computed(() => formData.id ? '编辑标签' : '添加标签')

const getTypeTagType = (type: number) => {
    const map: Record<number, string> = { 1: 'success', 2: 'warning', 3: 'danger' }
    return map[type] || 'info'
}

const getList = async () => {
    loading.value = true
    try {
        const res = await getReviewTagList(queryParams)
        tableData.value = res.lists || []
    } finally {
        loading.value = false
    }
}

const handleReset = () => {
    queryParams.type = undefined
    queryParams.name = ''
    getList()
}

const handleAdd = () => {
    Object.assign(formData, { id: 0, name: '', type: 1, color: '', sort: 0, status: 1 })
    dialogVisible.value = true
}

const handleEdit = (row: any) => {
    Object.assign(formData, row)
    dialogVisible.value = true
}

const handleSubmit = async () => {
    await formRef.value?.validate()
    if (formData.id) {
        await editReviewTag(formData)
    } else {
        await addReviewTag(formData)
    }
    ElMessage.success('操作成功')
    dialogVisible.value = false
    getList()
}

const handleDelete = async (row: any) => {
    await ElMessageBox.confirm('确定删除该标签吗？', '提示')
    await deleteReviewTag({ id: row.id })
    ElMessage.success('删除成功')
    getList()
}

const handleStatusChange = async (row: any) => {
    await changeReviewTagStatus({ id: row.id })
    ElMessage.success('操作成功')
}

onMounted(() => {
    getList()
})
</script>
