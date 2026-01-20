<template>
    <div class="sensitive-word-container">
        <!-- 搜索栏 -->
        <el-card shadow="never" class="mb-4">
            <el-form :model="queryParams" inline>
                <el-form-item label="敏感词">
                    <el-input v-model="queryParams.word" placeholder="请输入" clearable style="width: 150px" />
                </el-form-item>
                <el-form-item label="类型">
                    <el-select v-model="queryParams.type" placeholder="请选择" clearable style="width: 120px">
                        <el-option label="广告" :value="1" />
                        <el-option label="违法" :value="2" />
                        <el-option label="政治" :value="3" />
                        <el-option label="色情" :value="4" />
                        <el-option label="其他" :value="5" />
                    </el-select>
                </el-form-item>
                <el-form-item label="级别">
                    <el-select v-model="queryParams.level" placeholder="请选择" clearable style="width: 120px">
                        <el-option label="警告" :value="1" />
                        <el-option label="禁止" :value="2" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">搜索</el-button>
                    <el-button @click="handleReset">重置</el-button>
                    <el-button type="success" @click="handleAdd">添加</el-button>
                    <el-button type="warning" @click="handleImport">批量导入</el-button>
                    <el-button type="danger" :disabled="!selectedIds.length" @click="handleBatchDelete">
                        批量删除
                    </el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <!-- 列表 -->
        <el-card shadow="never">
            <el-table v-loading="loading" :data="tableData" @selection-change="handleSelectionChange">
                <el-table-column type="selection" width="50" />
                <el-table-column label="ID" width="80" prop="id" />
                <el-table-column label="敏感词" min-width="150" prop="word" />
                <el-table-column label="替换词" width="120" prop="replace_word" />
                <el-table-column label="类型" width="100">
                    <template #default="{ row }">
                        <el-tag>{{ row.type_text }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="级别" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.level === 2 ? 'danger' : 'warning'">{{ row.level_text }}</el-tag>
                    </template>
                </el-table-column>
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

            <div class="pagination-wrap">
                <el-pagination
                    v-model:current-page="queryParams.page"
                    v-model:page-size="queryParams.limit"
                    :total="total"
                    layout="total, sizes, prev, pager, next"
                    @size-change="getList"
                    @current-change="getList"
                />
            </div>
        </el-card>

        <!-- 编辑弹窗 -->
        <el-dialog v-model="dialogVisible" :title="formData.id ? '编辑敏感词' : '添加敏感词'" width="500px">
            <el-form :model="formData" label-width="80px" ref="formRef">
                <el-form-item label="敏感词" required>
                    <el-input v-model="formData.word" placeholder="请输入敏感词" />
                </el-form-item>
                <el-form-item label="替换词">
                    <el-input v-model="formData.replace_word" placeholder="默认为***" />
                </el-form-item>
                <el-form-item label="类型">
                    <el-select v-model="formData.type">
                        <el-option label="广告" :value="1" />
                        <el-option label="违法" :value="2" />
                        <el-option label="政治" :value="3" />
                        <el-option label="色情" :value="4" />
                        <el-option label="其他" :value="5" />
                    </el-select>
                </el-form-item>
                <el-form-item label="级别">
                    <el-select v-model="formData.level">
                        <el-option label="警告" :value="1" />
                        <el-option label="禁止" :value="2" />
                    </el-select>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="dialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSubmit">确定</el-button>
            </template>
        </el-dialog>

        <!-- 批量导入弹窗 -->
        <el-dialog v-model="importVisible" title="批量导入敏感词" width="500px">
            <el-form :model="importForm" label-width="80px">
                <el-form-item label="敏感词">
                    <el-input
                        v-model="importForm.content"
                        type="textarea"
                        :rows="8"
                        placeholder="每行一个敏感词"
                    />
                </el-form-item>
                <el-form-item label="类型">
                    <el-select v-model="importForm.type">
                        <el-option label="广告" :value="1" />
                        <el-option label="违法" :value="2" />
                        <el-option label="政治" :value="3" />
                        <el-option label="色情" :value="4" />
                        <el-option label="其他" :value="5" />
                    </el-select>
                </el-form-item>
                <el-form-item label="级别">
                    <el-select v-model="importForm.level">
                        <el-option label="警告" :value="1" />
                        <el-option label="禁止" :value="2" />
                    </el-select>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="importVisible = false">取消</el-button>
                <el-button type="primary" @click="submitImport">导入</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
    getSensitiveWordList,
    addSensitiveWord,
    editSensitiveWord,
    deleteSensitiveWord,
    batchDeleteSensitiveWord,
    changeSensitiveWordStatus,
    importSensitiveWords
} from '@/api/review'

const loading = ref(false)
const tableData = ref<any[]>([])
const total = ref(0)
const selectedIds = ref<number[]>([])
const dialogVisible = ref(false)
const importVisible = ref(false)

const queryParams = reactive({
    page: 1,
    limit: 20,
    word: '',
    type: undefined as number | undefined,
    level: undefined as number | undefined
})

const formData = reactive({
    id: 0,
    word: '',
    replace_word: '***',
    type: 5,
    level: 1,
    status: 1
})

const importForm = reactive({
    content: '',
    type: 5,
    level: 1
})

const getList = async () => {
    loading.value = true
    try {
        const res = await getSensitiveWordList(queryParams)
        tableData.value = res.lists || []
        total.value = res.count || 0
    } finally {
        loading.value = false
    }
}

const handleSearch = () => {
    queryParams.page = 1
    getList()
}

const handleReset = () => {
    Object.assign(queryParams, { page: 1, word: '', type: undefined, level: undefined })
    getList()
}

const handleSelectionChange = (selection: any[]) => {
    selectedIds.value = selection.map(item => item.id)
}

const handleAdd = () => {
    Object.assign(formData, { id: 0, word: '', replace_word: '***', type: 5, level: 1, status: 1 })
    dialogVisible.value = true
}

const handleEdit = (row: any) => {
    Object.assign(formData, row)
    dialogVisible.value = true
}

const handleSubmit = async () => {
    if (!formData.word) {
        ElMessage.warning('请输入敏感词')
        return
    }
    if (formData.id) {
        await editSensitiveWord(formData)
    } else {
        await addSensitiveWord(formData)
    }
    ElMessage.success('操作成功')
    dialogVisible.value = false
    getList()
}

const handleDelete = async (row: any) => {
    await ElMessageBox.confirm('确定删除该敏感词吗？', '提示')
    await deleteSensitiveWord({ id: row.id })
    ElMessage.success('删除成功')
    getList()
}

const handleBatchDelete = async () => {
    await ElMessageBox.confirm(`确定删除选中的 ${selectedIds.value.length} 个敏感词吗？`, '提示')
    await batchDeleteSensitiveWord({ ids: selectedIds.value })
    ElMessage.success('删除成功')
    getList()
}

const handleStatusChange = async (row: any) => {
    await changeSensitiveWordStatus({ id: row.id })
    ElMessage.success('操作成功')
}

const handleImport = () => {
    importForm.content = ''
    importForm.type = 5
    importForm.level = 1
    importVisible.value = true
}

const submitImport = async () => {
    if (!importForm.content) {
        ElMessage.warning('请输入敏感词')
        return
    }
    const res = await importSensitiveWords(importForm)
    ElMessage.success(res.msg || '导入成功')
    importVisible.value = false
    getList()
}

onMounted(() => {
    getList()
})
</script>

<style scoped>
.pagination-wrap {
    display: flex;
    justify-content: flex-end;
    margin-top: 16px;
}
</style>
