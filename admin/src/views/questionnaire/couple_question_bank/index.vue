<template>
    <div class="couple-question-bank">
        <el-card class="!border-none" shadow="never">
            <div class="toolbar">
                <div>
                    <h2 class="page-title">基础题库样例</h2>
                    <div class="page-desc">维护服务人员配置问卷时可复制参考的问题样例，不限制问卷题目来源。</div>
                </div>
                <el-button type="primary" @click="openForm()">新增题目</el-button>
            </div>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-form :inline="true" :model="search" class="mb-4">
                <el-form-item label="分类">
                    <el-input v-model="search.category" clearable placeholder="输入分类" />
                </el-form-item>
                <el-form-item label="状态">
                    <el-select v-model="search.status" clearable placeholder="全部" style="width: 140px">
                        <el-option label="启用" :value="1" />
                        <el-option label="禁用" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="loadList">查询</el-button>
                    <el-button @click="resetSearch">重置</el-button>
                </el-form-item>
            </el-form>

            <el-table :data="list" v-loading="loading" border>
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="category" label="分类" width="140" />
                <el-table-column prop="title" label="题目" min-width="260" show-overflow-tooltip />
                <el-table-column label="题型" width="100">
                    <template #default="{ row }">{{ getQuestionTypeLabel(row.type) }}</template>
                </el-table-column>
                <el-table-column label="选项" min-width="180" show-overflow-tooltip>
                    <template #default="{ row }">{{ (row.options || []).join('、') || '-' }}</template>
                </el-table-column>
                <el-table-column prop="required" label="必填" width="80">
                    <template #default="{ row }">{{ row.required ? '是' : '否' }}</template>
                </el-table-column>
                <el-table-column prop="sort" label="排序" width="90" />
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-switch
                            :model-value="row.status === 1"
                            @change="changeStatus(row)"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="150" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="openForm(row)">编辑</el-button>
                        <el-button type="danger" link @click="deleteRow(row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>

        <el-dialog v-model="formDialog.visible" :title="form.id ? '编辑题目' : '新增题目'" width="640px">
            <el-form :model="form" label-width="96px">
                <el-form-item label="分类">
                    <el-input v-model="form.category" placeholder="例如：恋爱经过" />
                </el-form-item>
                <el-form-item label="分类排序">
                    <el-input-number v-model="form.category_sort" :min="0" class="w-full" />
                </el-form-item>
                <el-form-item label="题型">
                    <el-select v-model="form.type" class="w-full" @change="normalizeOptions">
                        <el-option label="短文本" value="text" />
                        <el-option label="文本" value="textarea" />
                        <el-option label="单选" value="single" />
                        <el-option label="多选" value="multiple" />
                        <el-option label="评分" value="rating" />
                    </el-select>
                </el-form-item>
                <el-form-item label="标题">
                    <el-input v-model="form.title" maxlength="120" show-word-limit />
                </el-form-item>
                <el-form-item label="提示">
                    <el-input v-model="form.placeholder" maxlength="120" />
                </el-form-item>
                <el-form-item v-if="['single', 'multiple'].includes(form.type)" label="选项">
                    <el-select
                        v-model="form.options"
                        multiple
                        filterable
                        allow-create
                        default-first-option
                        class="w-full"
                        placeholder="输入选项后回车"
                    />
                </el-form-item>
                <el-form-item label="必填">
                    <el-switch v-model="form.required" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item label="排序">
                    <el-input-number v-model="form.sort" :min="0" class="w-full" />
                </el-form-item>
                <el-form-item label="状态">
                    <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="formDialog.visible = false">取消</el-button>
                <el-button type="primary" @click="saveForm">保存</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
    coupleQuestionBankChangeStatus,
    coupleQuestionBankDelete,
    coupleQuestionBankLists,
    coupleQuestionBankSave
} from '@/api/questionnaire'

const loading = ref(false)
const list = ref<any[]>([])
const search = reactive({
    category: '',
    status: ''
})
const formDialog = reactive({
    visible: false
})
const form = reactive<any>({
    id: 0,
    category: '',
    category_sort: 0,
    type: 'textarea',
    title: '',
    placeholder: '',
    options: [],
    required: 0,
    sort: 0,
    status: 1
})

const getQuestionTypeLabel = (type: string) =>
    ({ text: '短文本', textarea: '文本', single: '单选', multiple: '多选', rating: '评分' })[type] || type

const loadList = async () => {
    loading.value = true
    try {
        const res = await coupleQuestionBankLists(search)
        list.value = res?.data || res || []
    } finally {
        loading.value = false
    }
}

const resetSearch = () => {
    search.category = ''
    search.status = ''
    loadList()
}

const resetForm = () => {
    Object.assign(form, {
        id: 0,
        category: '',
        category_sort: 0,
        type: 'textarea',
        title: '',
        placeholder: '',
        options: [],
        required: 0,
        sort: 0,
        status: 1
    })
}

const openForm = (row?: any) => {
    resetForm()
    if (row) {
        Object.assign(form, {
            ...row,
            options: Array.isArray(row.options) ? row.options : []
        })
    }
    formDialog.visible = true
}

const normalizeOptions = () => {
    if (!['single', 'multiple'].includes(form.type)) {
        form.options = []
    } else if (!form.options.length) {
        form.options = ['选项一', '选项二']
    }
}

const saveForm = async () => {
    await coupleQuestionBankSave(form)
    ElMessage.success('保存成功')
    formDialog.visible = false
    await loadList()
}

const changeStatus = async (row: any) => {
    await coupleQuestionBankChangeStatus({
        id: row.id,
        status: row.status === 1 ? 0 : 1
    })
    ElMessage.success('操作成功')
    await loadList()
}

const deleteRow = async (row: any) => {
    await ElMessageBox.confirm(`确定删除题目“${row.title}”吗？`, '删除题目', { type: 'warning' })
    await coupleQuestionBankDelete({ id: row.id })
    ElMessage.success('删除成功')
    await loadList()
}

onMounted(loadList)
</script>

<style scoped lang="scss">
.toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.page-title {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

.page-desc {
    margin-top: 6px;
    color: var(--el-text-color-secondary);
}
</style>
