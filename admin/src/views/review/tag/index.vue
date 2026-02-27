<template>
    <div class="tag-container">
        <!-- 统计卡片 -->
        <div class="stat-cards">
            <div class="stat-card stat-card--total">
                <div class="stat-card__icon">
                    <el-icon :size="24"><PriceTag /></el-icon>
                </div>
                <div class="stat-card__info">
                    <div class="stat-card__value">{{ tableData.length }}</div>
                    <div class="stat-card__label">全部标签</div>
                </div>
            </div>
            <div class="stat-card stat-card--good">
                <div class="stat-card__icon">
                    <el-icon :size="24"><CircleCheck /></el-icon>
                </div>
                <div class="stat-card__info">
                    <div class="stat-card__value">{{ goodCount }}</div>
                    <div class="stat-card__label">好评标签</div>
                </div>
            </div>
            <div class="stat-card stat-card--medium">
                <div class="stat-card__icon">
                    <el-icon :size="24"><Warning /></el-icon>
                </div>
                <div class="stat-card__info">
                    <div class="stat-card__value">{{ mediumCount }}</div>
                    <div class="stat-card__label">中评标签</div>
                </div>
            </div>
            <div class="stat-card stat-card--bad">
                <div class="stat-card__icon">
                    <el-icon :size="24"><CircleClose /></el-icon>
                </div>
                <div class="stat-card__info">
                    <div class="stat-card__value">{{ badCount }}</div>
                    <div class="stat-card__label">差评标签</div>
                </div>
            </div>
        </div>

        <!-- 搜索与操作 -->
        <el-card shadow="never" class="filter-card">
            <div class="filter-bar">
                <div class="filter-left">
                    <el-select v-model="queryParams.type" placeholder="标签类型" clearable style="width: 140px">
                        <el-option label="好评标签" :value="1" />
                        <el-option label="中评标签" :value="2" />
                        <el-option label="差评标签" :value="3" />
                    </el-select>
                    <el-input v-model="queryParams.name" placeholder="搜索标签名称" clearable style="width: 180px" :prefix-icon="Search" />
                    <el-button type="primary" @click="getList">
                        <el-icon class="mr-1"><Search /></el-icon>搜索
                    </el-button>
                    <el-button @click="handleReset">重置</el-button>
                </div>
                <div class="filter-right">
                    <el-button type="primary" @click="handleAdd">
                        <el-icon class="mr-1"><Plus /></el-icon>添加标签
                    </el-button>
                </div>
            </div>
        </el-card>

        <!-- 标签列表 -->
        <el-card shadow="never">
            <el-table v-loading="loading" :data="tableData" stripe>
                <el-table-column label="ID" width="70" prop="id" align="center" />
                <el-table-column label="标签名称" min-width="160">
                    <template #default="{ row }">
                        <el-tag
                            :type="getTypeTagType(row.type)"
                            effect="light"
                            round
                        >{{ row.name }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="类型" width="110" align="center">
                    <template #default="{ row }">
                        <el-tag :type="getTypeTagType(row.type)" size="small" effect="plain">{{ row.type_text }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="使用次数" width="100" prop="use_count" align="center" />
                <el-table-column label="排序" width="80" prop="sort" align="center" />
                <el-table-column label="状态" width="90" align="center">
                    <template #default="{ row }">
                        <el-switch
                            v-model="row.status"
                            :active-value="1"
                            :inactive-value="0"
                            size="small"
                            @change="handleStatusChange(row)"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="140" align="center">
                    <template #default="{ row }">
                        <el-button link type="primary" size="small" @click="handleEdit(row)">
                            <el-icon class="mr-1"><Edit /></el-icon>编辑
                        </el-button>
                        <el-button link type="danger" size="small" @click="handleDelete(row)">
                            <el-icon class="mr-1"><Delete /></el-icon>删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>

        <!-- 编辑弹窗 -->
        <el-dialog v-model="dialogVisible" :title="dialogTitle" width="480px" destroy-on-close>
            <el-form :model="formData" label-width="80px" :rules="formRules" ref="formRef">
                <el-form-item label="标签名称" prop="name">
                    <el-input v-model="formData.name" placeholder="请输入标签名称" maxlength="50" />
                </el-form-item>
                <el-form-item label="标签类型" prop="type">
                    <el-radio-group v-model="formData.type">
                        <el-radio-button :value="1">好评标签</el-radio-button>
                        <el-radio-button :value="2">中评标签</el-radio-button>
                        <el-radio-button :value="3">差评标签</el-radio-button>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="排序">
                    <el-input-number v-model="formData.sort" :min="0" />
                    <span class="ml-2 text-xs text-gray-400">数值越小越靠前</span>
                </el-form-item>
                <el-form-item label="状态">
                    <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" active-text="启用" inactive-text="禁用" />
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
import { ElMessage, ElMessageBox } from 'element-plus'
import type { FormInstance } from 'element-plus'
import { Search, Plus, Edit, Delete, PriceTag, CircleCheck, Warning, CircleClose } from '@element-plus/icons-vue'
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
    sort: 0,
    status: 1
})

const formRules = {
    name: [{ required: true, message: '请输入标签名称', trigger: 'blur' }],
    type: [{ required: true, message: '请选择标签类型', trigger: 'change' }]
}

const dialogTitle = computed(() => formData.id ? '编辑标签' : '添加标签')

// 分组计数
const goodCount = computed(() => tableData.value.filter(t => t.type === 1).length)
const mediumCount = computed(() => tableData.value.filter(t => t.type === 2).length)
const badCount = computed(() => tableData.value.filter(t => t.type === 3).length)

// 类型对应 el-tag 样式
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
    Object.assign(formData, { id: 0, name: '', type: 1, sort: 0, status: 1 })
    dialogVisible.value = true
}

const handleEdit = (row: any) => {
    Object.assign(formData, { id: row.id, name: row.name, type: row.type, sort: row.sort, status: row.status })
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
    await ElMessageBox.confirm('确定删除该标签吗？', '提示', { type: 'warning' })
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

<style scoped lang="scss">
.tag-container {
    padding: 0;
}

/* 统计卡片 */
.stat-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 16px;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px 24px;
    background: #fff;
    border-radius: 8px;
    border: 1px solid #ebeef5;
    transition: box-shadow 0.2s;

    &:hover {
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    }
}

.stat-card__icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-card--total .stat-card__icon {
    background: rgba(64, 158, 255, 0.1);
    color: #409eff;
}

.stat-card--good .stat-card__icon {
    background: rgba(103, 194, 58, 0.1);
    color: #67c23a;
}

.stat-card--medium .stat-card__icon {
    background: rgba(230, 162, 60, 0.1);
    color: #e6a23c;
}

.stat-card--bad .stat-card__icon {
    background: rgba(245, 108, 108, 0.1);
    color: #f56c6c;
}

.stat-card__value {
    font-size: 28px;
    font-weight: 700;
    color: #303133;
    line-height: 1;
}

.stat-card__label {
    font-size: 13px;
    color: #909399;
    margin-top: 6px;
}

/* 搜索栏 */
.filter-card {
    margin-bottom: 16px;
}

.filter-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.filter-left {
    display: flex;
    gap: 12px;
    align-items: center;
}

.mr-1 {
    margin-right: 4px;
}
</style>
