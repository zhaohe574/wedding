<template>
    <div class="appeal-container">
        <!-- 统计卡片 -->
        <el-row :gutter="16" class="mb-4">
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card">
                    <div class="stat-value">{{ statistics.total_count || 0 }}</div>
                    <div class="stat-label">总申诉数</div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card pending">
                    <div class="stat-value">{{ statistics.pending_count || 0 }}</div>
                    <div class="stat-label">待处理</div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card success">
                    <div class="stat-value">{{ statistics.approved_count || 0 }}</div>
                    <div class="stat-label">已通过</div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card danger">
                    <div class="stat-value">{{ statistics.rejected_count || 0 }}</div>
                    <div class="stat-label">已驳回</div>
                </el-card>
            </el-col>
        </el-row>

        <!-- 搜索栏 -->
        <el-card shadow="never" class="mb-4">
            <el-form :model="queryParams" inline>
                <el-form-item label="状态">
                    <el-select v-model="queryParams.status" placeholder="请选择" clearable style="width: 120px">
                        <el-option label="待处理" :value="0" />
                        <el-option label="已通过" :value="1" />
                        <el-option label="已驳回" :value="2" />
                    </el-select>
                </el-form-item>
                <el-form-item label="申诉类型">
                    <el-select v-model="queryParams.appeal_type" placeholder="请选择" clearable style="width: 120px">
                        <el-option label="恶意差评" :value="1" />
                        <el-option label="虚假评价" :value="2" />
                        <el-option label="侵犯隐私" :value="3" />
                        <el-option label="其他" :value="4" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">搜索</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <!-- 列表 -->
        <el-card shadow="never">
            <el-table v-loading="loading" :data="tableData">
                <el-table-column label="申诉ID" width="80" prop="id" />
                <el-table-column label="被申诉评价" min-width="200">
                    <template #default="{ row }">
                        <div class="review-brief">
                            <div class="user">{{ row.review?.user?.nickname || '匿名' }}</div>
                            <el-rate :model-value="row.review?.score" disabled size="small" />
                            <div class="content">{{ row.review?.content?.slice(0, 50) }}...</div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="申诉人" width="120">
                    <template #default="{ row }">
                        {{ row.appeal_user?.nickname || row.appeal_staff?.name || '-' }}
                    </template>
                </el-table-column>
                <el-table-column label="申诉类型" width="100">
                    <template #default="{ row }">
                        <el-tag type="warning">{{ row.appeal_type_text }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getStatusType(row.status)">{{ row.status_text }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="申诉时间" width="160" prop="create_time_text" />
                <el-table-column label="操作" width="150">
                    <template #default="{ row }">
                        <el-button link type="primary" @click="handleDetail(row)">详情</el-button>
                        <el-button v-if="row.status === 0" link type="success" @click="handleProcess(row)">
                            处理
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="pagination-wrap">
                <el-pagination
                    v-model:current-page="queryParams.page"
                    v-model:page-size="queryParams.limit"
                    :total="total"
                    layout="total, prev, pager, next"
                    @current-change="getList"
                />
            </div>
        </el-card>

        <!-- 详情/处理弹窗 -->
        <el-dialog v-model="dialogVisible" :title="currentAppeal?.status === 0 ? '处理申诉' : '申诉详情'" width="600px">
            <div v-if="currentAppeal">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="申诉人">
                        {{ currentAppeal.appeal_user?.nickname || currentAppeal.appeal_staff?.name }}
                    </el-descriptions-item>
                    <el-descriptions-item label="申诉类型">
                        <el-tag type="warning">{{ currentAppeal.appeal_type_text }}</el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="申诉原因" :span="2">
                        {{ currentAppeal.appeal_reason }}
                    </el-descriptions-item>
                </el-descriptions>

                <div v-if="currentAppeal.evidence_images?.length" class="evidence-images mt-4">
                    <div class="label">证据图片</div>
                    <div class="images">
                        <el-image
                            v-for="(img, index) in currentAppeal.evidence_images"
                            :key="index"
                            :src="img"
                            :preview-src-list="currentAppeal.evidence_images"
                            fit="cover"
                            style="width: 80px; height: 80px; border-radius: 4px;"
                        />
                    </div>
                </div>

                <el-divider />

                <div class="original-review">
                    <div class="label">原评价内容</div>
                    <div class="review-card">
                        <div class="user">{{ currentAppeal.review?.user?.nickname }}</div>
                        <el-rate :model-value="currentAppeal.review?.score" disabled />
                        <div class="content">{{ currentAppeal.review?.content }}</div>
                    </div>
                </div>

                <!-- 处理表单 -->
                <div v-if="currentAppeal.status === 0" class="process-form mt-4">
                    <el-divider />
                    <el-form :model="processForm" label-width="80px">
                        <el-form-item label="处理结果">
                            <el-radio-group v-model="processForm.status">
                                <el-radio :label="1">通过申诉</el-radio>
                                <el-radio :label="2">驳回申诉</el-radio>
                            </el-radio-group>
                        </el-form-item>
                        <el-form-item v-if="processForm.status === 1" label="处理动作">
                            <el-select v-model="processForm.handle_action">
                                <el-option label="无操作" :value="0" />
                                <el-option label="删除评价" :value="1" />
                                <el-option label="隐藏评价" :value="2" />
                                <el-option label="警告用户" :value="3" />
                            </el-select>
                        </el-form-item>
                        <el-form-item label="处理说明">
                            <el-input v-model="processForm.handle_result" type="textarea" :rows="3" />
                        </el-form-item>
                    </el-form>
                </div>
            </div>
            <template #footer>
                <el-button @click="dialogVisible = false">关闭</el-button>
                <el-button v-if="currentAppeal?.status === 0" type="primary" @click="submitProcess">
                    确认处理
                </el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getAppealList, getAppealDetail, getAppealStatistics, handleAppeal } from '@/api/review'

const loading = ref(false)
const tableData = ref<any[]>([])
const total = ref(0)
const statistics = ref<any>({})
const dialogVisible = ref(false)
const currentAppeal = ref<any>(null)

const queryParams = reactive({
    page: 1,
    limit: 10,
    status: undefined as number | undefined,
    appeal_type: undefined as number | undefined
})

const processForm = reactive({
    status: 1,
    handle_action: 0,
    handle_result: ''
})

const getStatusType = (status: number) => {
    const map: Record<number, string> = { 0: 'warning', 1: 'success', 2: 'danger' }
    return map[status] || 'info'
}

const getList = async () => {
    loading.value = true
    try {
        const res = await getAppealList(queryParams)
        tableData.value = res.lists || []
        total.value = res.count || 0
    } finally {
        loading.value = false
    }
}

const getStatistics = async () => {
    const res = await getAppealStatistics()
    statistics.value = res
}

const handleSearch = () => {
    queryParams.page = 1
    getList()
}

const handleReset = () => {
    queryParams.status = undefined
    queryParams.appeal_type = undefined
    queryParams.page = 1
    getList()
}

const handleDetail = async (row: any) => {
    const res = await getAppealDetail({ id: row.id })
    currentAppeal.value = res
    dialogVisible.value = true
}

const handleProcess = async (row: any) => {
    const res = await getAppealDetail({ id: row.id })
    currentAppeal.value = res
    processForm.status = 1
    processForm.handle_action = 0
    processForm.handle_result = ''
    dialogVisible.value = true
}

const submitProcess = async () => {
    await handleAppeal({
        id: currentAppeal.value.id,
        ...processForm
    })
    ElMessage.success('处理成功')
    dialogVisible.value = false
    getList()
    getStatistics()
}

onMounted(() => {
    getList()
    getStatistics()
})
</script>

<style scoped lang="scss">
.stat-card {
    text-align: center;
    .stat-value { font-size: 28px; font-weight: bold; }
    .stat-label { color: #666; margin-top: 8px; }
    &.pending .stat-value { color: #ff9800; }
    &.success .stat-value { color: #52c41a; }
    &.danger .stat-value { color: #f44336; }
}

.review-brief {
    .user { font-weight: 500; margin-bottom: 4px; }
    .content { color: #666; font-size: 12px; margin-top: 4px; }
}

.pagination-wrap {
    display: flex;
    justify-content: flex-end;
    margin-top: 16px;
}

.label {
    font-weight: 500;
    margin-bottom: 12px;
}

.images {
    display: flex;
    gap: 8px;
}

.review-card {
    background: #f5f5f5;
    padding: 12px;
    border-radius: 4px;
    .user { font-weight: 500; margin-bottom: 4px; }
    .content { color: #666; margin-top: 8px; }
}
</style>
