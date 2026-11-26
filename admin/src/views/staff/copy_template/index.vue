<template>
    <div class="copy-template-lists">
        <el-card class="!border-none" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[200px]" label="适用场景">
                    <el-select v-model="queryParams.scene" placeholder="选择场景" clearable>
                        <el-option label="全部" value="" />
                        <el-option
                            v-for="item in sceneOptions"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[220px]" label="关键词">
                    <el-input
                        v-model="queryParams.keyword"
                        placeholder="搜索标题或话术内容"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[160px]" label="状态">
                    <el-select v-model="queryParams.status" placeholder="选择状态" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="启用" :value="1" />
                        <el-option label="停用" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <div class="mb-4 flex items-start justify-between gap-4">
                <div class="flex flex-wrap gap-2">
                    <el-button
                        v-perms="['ops.staffCopyTemplate/add']"
                        type="primary"
                        @click="handleAdd()"
                    >
                        <template #icon>
                            <icon name="el-icon-Plus" />
                        </template>
                        新增统一模板
                    </el-button>
                    <el-button
                        v-perms="['ops.staffCopyTemplate/importDefaults']"
                        :loading="importing"
                        @click="handleImportDefaults()"
                    >
                        导入默认话术
                    </el-button>
                </div>
                <span class="text-tx-secondary text-xs leading-5">
                    启用后的统一话术将实时同步至小程序「服务人员工作台 › 文案模板库 › 平台统一话术」，支持场次与名片变量自动代入。
                </span>
            </div>
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" width="75" />
                <el-table-column label="适用场景" width="120">
                    <template #default="{ row }">
                        <el-tag type="info">{{ row.scene_label || row.scene }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="模板标题" prop="title" min-width="180" />
                <el-table-column
                    label="话术正文"
                    prop="content"
                    min-width="320"
                    show-overflow-tooltip
                />
                <el-table-column label="累计复制" prop="use_count" width="100" />
                <el-table-column label="排序" prop="sort" width="90" />
                <el-table-column label="状态" width="90">
                    <template #default="{ row }">
                        <el-switch
                            v-perms="['ops.staffCopyTemplate/changeStatus']"
                            v-model="row.status"
                            :active-value="1"
                            :inactive-value="0"
                            @change="handleChangeStatus($event, row.id)"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="更新时间" prop="update_time" width="170" />
                <el-table-column label="操作" width="130" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['ops.staffCopyTemplate/edit']"
                            type="primary"
                            link
                            @click="handleEdit(row)"
                        >
                            编辑
                        </el-button>
                        <el-button
                            v-perms="['ops.staffCopyTemplate/delete']"
                            type="danger"
                            link
                            @click="handleDelete(row)"
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

        <!-- 新增/编辑弹窗 -->
        <el-dialog
            v-model="showEditDialog"
            :title="editForm.id ? '编辑统一话术模板' : '新增统一话术模板'"
            width="640px"
        >
            <el-form ref="editFormRef" :model="editForm" :rules="editRules" label-width="100px">
                <el-form-item label="适用场景" prop="scene">
                    <el-select v-model="editForm.scene" placeholder="请选择适用场景" class="!w-[240px]">
                        <el-option
                            v-for="item in sceneOptions"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="模板标题" prop="title">
                    <el-input
                        v-model="editForm.title"
                        placeholder="请输入模板标题"
                        maxlength="80"
                        show-word-limit
                    />
                </el-form-item>
                <el-form-item label="话术正文" prop="content">
                    <el-input
                        v-model="editForm.content"
                        type="textarea"
                        :rows="5"
                        placeholder="请输入话术正文"
                        maxlength="1000"
                        show-word-limit
                    />
                    <div class="mt-2 w-full">
                        <div class="mb-1 text-tx-secondary text-xs">动态变量快捷插入</div>
                        <div class="flex flex-wrap gap-2">
                            <el-button
                                v-for="item in variableOptions"
                                :key="item.key"
                                size="small"
                                @click="appendVariable(item.key)"
                            >
                                {{ item.label }}
                            </el-button>
                        </div>
                        <div class="mt-1 text-tx-secondary text-xs">
                            服务人员在小程序选中场次后，变量将自动替换为客户称呼、婚期、酒店、套餐与个人名片信息。
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="排序权重" prop="sort">
                    <el-input-number v-model="editForm.sort" :min="0" :max="9999" />
                    <span class="ml-2 text-tx-secondary text-xs">数值越大越靠前</span>
                </el-form-item>
                <el-form-item label="状态" prop="status">
                    <el-radio-group v-model="editForm.status">
                        <el-radio :value="1">启用</el-radio>
                        <el-radio :value="0">停用</el-radio>
                    </el-radio-group>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showEditDialog = false">取消</el-button>
                <el-button type="primary" :loading="saving" @click="handleSave">保存</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="copyTemplateLists">
import type { FormInstance } from 'element-plus'

import {
    staffCopyTemplateAdd,
    staffCopyTemplateChangeStatus,
    staffCopyTemplateDelete,
    staffCopyTemplateEdit,
    staffCopyTemplateImportDefaults,
    staffCopyTemplateLists,
    staffCopyTemplateSceneOptions
} from '@/api/staff-copy-template'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

interface SceneOption {
    value: string
    label: string
}

interface VariableOption {
    key: string
    label: string
}

const queryParams = reactive({
    scene: '',
    keyword: '',
    status: ''
})

const showEditDialog = ref(false)
const editFormRef = shallowRef<FormInstance>()
const saving = ref(false)
const importing = ref(false)
const sceneOptions = ref<SceneOption[]>([])
const variableOptions = ref<VariableOption[]>([])

const editForm = reactive({
    id: 0,
    scene: '',
    title: '',
    content: '',
    sort: 0,
    status: 1
})

const editRules = reactive({
    scene: [{ required: true, message: '请选择适用场景', trigger: 'change' }],
    title: [{ required: true, message: '请输入模板标题', trigger: 'blur' }],
    content: [{ required: true, message: '请输入话术正文', trigger: 'blur' }]
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: staffCopyTemplateLists,
    params: queryParams
})

const appendVariable = (key: string) => {
    editForm.content = (editForm.content + key).slice(0, 1000)
}

const handleAdd = () => {
    Object.assign(editForm, {
        id: 0,
        scene: '',
        title: '',
        content: '',
        sort: 0,
        status: 1
    })
    showEditDialog.value = true
}

const handleEdit = (row: any) => {
    Object.assign(editForm, {
        id: row.id,
        scene: row.scene,
        title: row.title,
        content: row.content,
        sort: row.sort,
        status: row.status
    })
    showEditDialog.value = true
}

const handleSave = async () => {
    if (saving.value) return
    await editFormRef.value?.validate()
    saving.value = true
    try {
        if (editForm.id) {
            await staffCopyTemplateEdit(editForm)
        } else {
            await staffCopyTemplateAdd(editForm)
        }
        showEditDialog.value = false
        getLists()
    } finally {
        saving.value = false
    }
}

const handleChangeStatus = async (status: any, id: number) => {
    try {
        await staffCopyTemplateChangeStatus({ id, status })
        getLists()
    } catch (error) {
        getLists()
    }
}

const handleDelete = async (row: any) => {
    await feedback.confirm(`确定删除统一话术「${row.title}」？删除后将不再在小程序展示。`)
    await staffCopyTemplateDelete({ id: row.id })
    getLists()
}

const handleImportDefaults = async () => {
    await feedback.confirm('将补齐缺失的系统默认参考话术（不会覆盖已有同名模板），是否继续？')
    importing.value = true
    try {
        const res = await staffCopyTemplateImportDefaults()
        feedback.msgSuccess(
            res?.imported_count ? `已导入 ${res.imported_count} 条默认话术` : '默认话术已完整，无需重复导入'
        )
        getLists()
    } finally {
        importing.value = false
    }
}

const loadOptions = async () => {
    const res = await staffCopyTemplateSceneOptions()
    sceneOptions.value = Array.isArray(res?.scenes) ? res.scenes : []
    variableOptions.value = Array.isArray(res?.variables) ? res.variables : []
}

onActivated(() => {
    getLists()
})

loadOptions()
getLists()
</script>
