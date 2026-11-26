<template>
    <div class="question-bank-panel">
        <search-panel>
            <el-form class="mb-[-16px]" :inline="true" :model="queryParams">
                <el-form-item class="w-[240px]" label="关键词">
                    <el-input
                        v-model="queryParams.keyword"
                        clearable
                        placeholder="题目、分类或提示"
                        @keyup.enter="loadList"
                    />
                </el-form-item>
                <el-form-item class="w-[170px]" label="分类">
                    <el-select v-model="queryParams.category" clearable filterable placeholder="全部分类">
                        <el-option
                            v-for="category in categoryOptions"
                            :key="category"
                            :label="category"
                            :value="category"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[140px]" label="题型">
                    <el-select v-model="queryParams.type" clearable placeholder="全部">
                        <el-option
                            v-for="item in QUESTION_TYPES"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[130px]" label="状态">
                    <el-select v-model="queryParams.status" clearable placeholder="全部">
                        <el-option
                            v-for="item in BANK_STATUS_OPTIONS"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="loadList">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>
        </search-panel>

        <el-alert
            v-if="listError"
            class="mt-4"
            type="error"
            :title="listError"
            :closable="false"
            show-icon
        >
            <template #default>
                <el-button type="primary" link @click="loadList">重试</el-button>
            </template>
        </el-alert>

        <admin-table-toolbar class="mt-4" :hint="toolbarHint">
            <template #right>
                <el-button
                    v-perms="['growth.coupleQuestionBank/save']"
                    type="primary"
                    @click="openForm()"
                >
                    新增题目
                </el-button>
                <el-button :loading="loading" @click="loadList()">刷新</el-button>
            </template>
        </admin-table-toolbar>

        <el-table v-loading="loading" :data="list" border>
            <el-table-column prop="id" label="ID" width="70" />
            <el-table-column label="分类" width="130">
                <template #default="{ row }">
                    <el-tag effect="plain" size="small">{{ row.category || '未分类' }}</el-tag>
                </template>
            </el-table-column>
            <el-table-column label="题目与提示" min-width="250">
                <template #default="{ row }">
                    <div class="cell-main">{{ row.title }}</div>
                    <div class="cell-sub">{{ row.placeholder || '暂无提示文案' }}</div>
                </template>
            </el-table-column>
            <el-table-column label="题型" width="90">
                <template #default="{ row }">
                    <el-tag size="small" effect="plain">
                        {{ getQuestionTypeLabel(row.type) }}
                    </el-tag>
                </template>
            </el-table-column>
            <el-table-column label="选项预览" min-width="220">
                <template #default="{ row }">
                    <div v-if="isChoiceType(row.type) && row.options?.length" class="option-preview">
                        <el-tag
                            v-for="option in row.options.slice(0, 3)"
                            :key="option"
                            size="small"
                            effect="plain"
                            type="info"
                        >
                            {{ option }}
                        </el-tag>
                        <span v-if="row.options.length > 3" class="option-preview__more">
                            +{{ row.options.length - 3 }}
                        </span>
                    </div>
                    <span v-else class="cell-sub">-</span>
                </template>
            </el-table-column>
            <el-table-column label="必填" width="80">
                <template #default="{ row }">
                    <el-tag
                        v-if="Number(row.required) === 1"
                        size="small"
                        type="danger"
                        effect="plain"
                    >
                        是
                    </el-tag>
                    <span v-else class="cell-sub">否</span>
                </template>
            </el-table-column>
            <el-table-column label="排序" width="110">
                <template #default="{ row }">
                    <el-input-number
                        v-if="canSort"
                        v-model="row.sort"
                        size="small"
                        :min="0"
                        :controls="false"
                        class="sort-input"
                        @change="handleSortChange(row)"
                    />
                    <span v-else>{{ Number(row.sort || 0) }}</span>
                </template>
            </el-table-column>
            <el-table-column label="状态" width="90">
                <template #default="{ row }">
                    <el-switch
                        v-perms="['growth.coupleQuestionBank/changeStatus']"
                        :model-value="Number(row.status) === 1"
                        :loading="togglingId === Number(row.id)"
                        @change="toggleStatus(row)"
                    />
                </template>
            </el-table-column>
            <el-table-column label="操作" width="130" fixed="right">
                <template #default="{ row }">
                    <el-button
                        v-perms="['growth.coupleQuestionBank/save']"
                        type="primary"
                        link
                        @click="openForm(row)"
                    >
                        编辑
                    </el-button>
                    <el-button
                        v-perms="['growth.coupleQuestionBank/delete']"
                        type="danger"
                        link
                        @click="deleteRow(row)"
                    >
                        删除
                    </el-button>
                </template>
            </el-table-column>
        </el-table>

        <el-dialog
            v-model="formDialogVisible"
            :title="form.id ? '编辑题目' : '新增题目'"
            width="640px"
            destroy-on-close
            append-to-body
        >
            <el-form ref="formRef" :model="form" :rules="rules" label-width="96px">
                <el-form-item label="分类" prop="category">
                    <el-select
                        v-model="form.category"
                        filterable
                        allow-create
                        default-first-option
                        class="w-full"
                        placeholder="选择预设分类或输入新分类"
                    >
                        <el-option
                            v-for="category in categoryOptions"
                            :key="category"
                            :label="category"
                            :value="category"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="分类排序">
                    <el-input-number v-model="form.category_sort" :min="0" class="w-full" />
                </el-form-item>
                <el-form-item label="题型" prop="type">
                    <el-select v-model="form.type" class="w-full" @change="handleFormTypeChange">
                        <el-option
                            v-for="item in QUESTION_TYPES"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="标题" prop="title">
                    <el-input
                        v-model="form.title"
                        maxlength="120"
                        show-word-limit
                        placeholder="例如：你们的求婚发生在什么场景"
                    />
                </el-form-item>
                <el-form-item label="提示">
                    <el-input
                        v-model="form.placeholder"
                        maxlength="120"
                        placeholder="填写示例或补充说明（选填）"
                    />
                </el-form-item>
                <el-form-item v-if="isChoiceType(form.type)" label="选项" prop="options">
                    <el-select
                        v-model="form.options"
                        multiple
                        filterable
                        allow-create
                        default-first-option
                        class="w-full"
                        placeholder="输入选项后回车，至少两个"
                    />
                </el-form-item>
                <el-form-item label="必填">
                    <el-switch v-model="form.required" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item label="排序">
                    <el-input-number v-model="form.sort" :min="0" class="w-full" />
                </el-form-item>
                <el-form-item label="状态">
                    <el-switch
                        v-model="form.status"
                        :active-value="1"
                        :inactive-value="0"
                        active-text="启用"
                        inactive-text="禁用"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <div class="dialog-footer">
                    <el-button @click="formDialogVisible = false">取消</el-button>
                    <el-button type="primary" :loading="saving" @click="saveForm">保存</el-button>
                </div>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage, ElMessageBox } from 'element-plus'

import type { QuestionBankItem } from '@/api/questionnaire'
import {
    coupleQuestionBankChangeStatus,
    coupleQuestionBankDelete,
    coupleQuestionBankLists,
    coupleQuestionBankSave,
    coupleQuestionBankSort
} from '@/api/questionnaire'
import { hasPermission } from '@/utils/perm'

import {
    DEFAULT_CATEGORIES,
    extractErrorMessage,
    getQuestionTypeLabel,
    QUESTION_TYPES
} from '../shared'

const emit = defineEmits<{
    (e: 'changed'): void
}>()

const BANK_STATUS_OPTIONS = [
    { value: 1, label: '启用' },
    { value: 0, label: '禁用' }
]

const queryParams = reactive<{
    keyword: string
    category: string
    type: string
    status: number | ''
}>({
    keyword: '',
    category: '',
    type: '',
    status: ''
})

const list = ref<QuestionBankItem[]>([])
const loading = ref(false)
const listError = ref('')
const togglingId = ref<number | null>(null)
const saving = ref(false)
const formDialogVisible = ref(false)
const formRef = ref<FormInstance>()

const createEmptyForm = () => ({
    id: 0,
    category: '',
    category_sort: 0,
    type: 'textarea',
    title: '',
    placeholder: '',
    options: [] as string[],
    required: 0,
    sort: 0,
    status: 1
})

const form = reactive(createEmptyForm())

const canSort = computed(() => hasPermission(['growth.coupleQuestionBank/sort']))

const enabledCount = computed(
    () => list.value.filter((item) => Number(item.status) === 1).length
)

const toolbarHint = computed(
    () => `当前显示 ${list.value.length} 道题目（其中启用 ${enabledCount.value} 道）`
)

const categoryOptions = computed(() => {
    const categories = new Set<string>(DEFAULT_CATEGORIES)
    list.value.forEach((item) => {
        if (item.category) {
            categories.add(String(item.category))
        }
    })
    if (form.category) {
        categories.add(form.category)
    }
    return Array.from(categories)
})

const isChoiceType = (type?: string) => ['single', 'multiple'].includes(String(type || ''))

const cleanOptions = (options: string[]) =>
    Array.from(
        new Set((Array.isArray(options) ? options : []).map((item) => String(item ?? '').trim()).filter(Boolean))
    )

const validateOptions = (_rule: any, value: any, callback: any) => {
    if (!isChoiceType(form.type)) {
        callback()
        return
    }
    if (cleanOptions(value).length < 2) {
        callback(new Error('单选或多选题至少需要两个选项'))
        return
    }
    callback()
}

const rules: FormRules = {
    category: [{ required: true, message: '请选择或输入分类', trigger: 'change' }],
    title: [{ required: true, message: '请填写题目标题', trigger: 'blur' }],
    options: [{ validator: validateOptions, trigger: 'change' }]
}

const loadList = async () => {
    loading.value = true
    listError.value = ''
    try {
        const res: any = await coupleQuestionBankLists(queryParams)
        const payload: any[] = Array.isArray(res)
            ? res
            : Array.isArray(res?.data)
              ? res.data
              : []
        list.value = payload.map((item) => ({
            ...item,
            options: Array.isArray(item?.options) ? item.options.map(String) : []
        }))
    } catch (error) {
        list.value = []
        listError.value = extractErrorMessage(error, '题库加载失败，请稍后重试')
    } finally {
        loading.value = false
    }
}

const handleReset = () => {
    queryParams.keyword = ''
    queryParams.category = ''
    queryParams.type = ''
    queryParams.status = ''
    loadList()
}

const openForm = (row?: any) => {
    Object.assign(form, createEmptyForm())
    if (row) {
        Object.assign(form, {
            id: Number(row.id || 0),
            category: String(row.category || ''),
            category_sort: Number(row.category_sort || 0),
            type: String(row.type || 'textarea'),
            title: String(row.title || ''),
            placeholder: String(row.placeholder || ''),
            options: Array.isArray(row.options) ? row.options.map(String) : [],
            required: Number(row.required || 0) ? 1 : 0,
            sort: Number(row.sort || 0),
            status: Number(row.status ?? 1) === 0 ? 0 : 1
        })
    }
    formDialogVisible.value = true
    nextTick(() => formRef.value?.clearValidate())
}

const handleFormTypeChange = () => {
    if (!isChoiceType(form.type)) {
        form.options = []
        return
    }
    const options = cleanOptions(form.options)
    form.options = options.length >= 2 ? options : ['选项一', '选项二']
}

const saveForm = async () => {
    if (!formRef.value || saving.value) {
        return
    }
    const valid = await formRef.value.validate().catch(() => false)
    if (!valid) {
        return
    }
    saving.value = true
    try {
        await coupleQuestionBankSave({
            id: form.id,
            category: form.category.trim(),
            category_sort: form.category_sort,
            type: form.type,
            title: form.title.trim(),
            placeholder: form.placeholder.trim(),
            options: isChoiceType(form.type) ? cleanOptions(form.options) : [],
            required: form.required,
            sort: form.sort,
            status: form.status
        })
        ElMessage.success(form.id ? '题目已更新' : '题目已新增')
        formDialogVisible.value = false
        await loadList()
        emit('changed')
    } catch {
        // 业务错误已由 request 层统一提示，这里不重复弹窗
    } finally {
        saving.value = false
    }
}

const toggleStatus = async (row: any) => {
    const id = Number(row?.id || 0)
    if (!id || togglingId.value) {
        return
    }
    togglingId.value = id
    try {
        await coupleQuestionBankChangeStatus({
            id,
            status: Number(row.status) === 1 ? 0 : 1
        })
        ElMessage.success('状态已更新')
        await loadList()
        emit('changed')
    } catch {
        // 业务错误已由 request 层统一提示，这里不重复弹窗
    } finally {
        togglingId.value = null
    }
}

const deleteRow = async (row: any) => {
    try {
        await ElMessageBox.confirm(`确定删除题目「${row?.title || ''}」吗？`, '删除题目', {
            type: 'warning'
        })
    } catch {
        return
    }
    try {
        await coupleQuestionBankDelete({ id: Number(row?.id || 0) })
        ElMessage.success('删除成功')
        await loadList()
        emit('changed')
    } catch {
        // 业务错误已由 request 层统一提示，这里不重复弹窗
    }
}

const pendingSorts = new Map<number, { sort: number; category_sort: number }>()
let sortTimer: ReturnType<typeof setTimeout> | null = null

const handleSortChange = (row: any) => {
    const id = Number(row?.id || 0)
    if (!id) {
        return
    }
    pendingSorts.set(id, {
        sort: Number(row.sort || 0),
        category_sort: Number(row.category_sort || 0)
    })
    if (sortTimer) {
        clearTimeout(sortTimer)
    }
    sortTimer = setTimeout(async () => {
        sortTimer = null
        const items = Array.from(pendingSorts.entries()).map(([itemId, payload]) => ({
            id: itemId,
            sort: payload.sort,
            category_sort: payload.category_sort
        }))
        pendingSorts.clear()
        if (!items.length) {
            return
        }
        try {
            await coupleQuestionBankSort({ items })
            ElMessage.success('排序已更新')
            await loadList()
            emit('changed')
        } catch {
            await loadList()
        }
    }, 600)
}

onBeforeUnmount(() => {
    if (sortTimer) {
        clearTimeout(sortTimer)
    }
})

onMounted(loadList)

defineExpose({ reload: loadList })
</script>

<style scoped lang="scss">
.question-bank-panel {
    min-width: 0;
}

.cell-main {
    font-size: 13px;
    font-weight: 500;
    line-height: 1.5;
    color: var(--admin-color-title);
}

.cell-sub {
    margin-top: 2px;
    font-size: 12px;
    line-height: 1.5;
    color: var(--admin-color-muted);
}

.option-preview {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;

    &__more {
        font-size: 12px;
        color: var(--admin-color-muted);
    }
}

.sort-input {
    width: 82px;
}

.dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}
</style>
