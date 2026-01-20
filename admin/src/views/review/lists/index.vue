<template>
    <div class="review-container">
        <!-- 统计卡片 -->
        <el-row :gutter="16" class="mb-4">
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card">
                    <div class="stat-content">
                        <div class="stat-label">总评价数</div>
                        <div class="stat-value">{{ statistics.total_count || 0 }}</div>
                    </div>
                    <el-icon class="stat-icon" :size="40"><ChatDotRound /></el-icon>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card pending">
                    <div class="stat-content">
                        <div class="stat-label">待审核</div>
                        <div class="stat-value">{{ statistics.pending_count || 0 }}</div>
                    </div>
                    <el-icon class="stat-icon" :size="40"><Clock /></el-icon>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card success">
                    <div class="stat-content">
                        <div class="stat-label">好评数</div>
                        <div class="stat-value">{{ statistics.good_count || 0 }}</div>
                    </div>
                    <el-icon class="stat-icon" :size="40"><Star /></el-icon>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card info">
                    <div class="stat-content">
                        <div class="stat-label">好评率</div>
                        <div class="stat-value">{{ statistics.good_rate || 0 }}%</div>
                    </div>
                    <el-icon class="stat-icon" :size="40"><TrendCharts /></el-icon>
                </el-card>
            </el-col>
        </el-row>

        <!-- 搜索栏 -->
        <el-card shadow="never" class="mb-4">
            <el-form :model="queryParams" inline>
                <el-form-item label="状态">
                    <el-select v-model="queryParams.status" placeholder="请选择" clearable style="width: 120px">
                        <el-option label="待审核" :value="0" />
                        <el-option label="已通过" :value="1" />
                        <el-option label="已拒绝" :value="2" />
                    </el-select>
                </el-form-item>
                <el-form-item label="评分">
                    <el-select v-model="queryParams.score_level" placeholder="请选择" clearable style="width: 120px">
                        <el-option label="好评" value="good" />
                        <el-option label="中评" value="medium" />
                        <el-option label="差评" value="bad" />
                    </el-select>
                </el-form-item>
                <el-form-item label="类型">
                    <el-select v-model="queryParams.review_type" placeholder="请选择" clearable style="width: 120px">
                        <el-option label="文字" :value="1" />
                        <el-option label="图文" :value="2" />
                        <el-option label="视频" :value="3" />
                    </el-select>
                </el-form-item>
                <el-form-item label="用户昵称">
                    <el-input v-model="queryParams.nickname" placeholder="请输入" clearable style="width: 150px" />
                </el-form-item>
                <el-form-item label="人员姓名">
                    <el-input v-model="queryParams.staff_name" placeholder="请输入" clearable style="width: 150px" />
                </el-form-item>
                <el-form-item label="日期范围">
                    <el-date-picker
                        v-model="dateRange"
                        type="daterange"
                        value-format="YYYY-MM-DD"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                        style="width: 240px"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">搜索</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <!-- 操作栏 -->
        <el-card shadow="never" class="mb-4">
            <el-button type="success" :disabled="!selectedIds.length" @click="handleBatchAudit(1)">
                批量通过
            </el-button>
            <el-button type="danger" :disabled="!selectedIds.length" @click="handleBatchAudit(2)">
                批量拒绝
            </el-button>
        </el-card>

        <!-- 列表 -->
        <el-card shadow="never">
            <el-table 
                v-loading="loading" 
                :data="tableData" 
                @selection-change="handleSelectionChange"
                row-key="id"
            >
                <el-table-column type="selection" width="50" />
                <el-table-column label="评价信息" min-width="300">
                    <template #default="{ row }">
                        <div class="review-info">
                            <div class="user-info">
                                <el-avatar :size="36" :src="row.user?.avatar">
                                    {{ row.user?.nickname?.charAt(0) }}
                                </el-avatar>
                                <span class="nickname">{{ row.user?.nickname || '匿名用户' }}</span>
                                <el-tag v-if="row.is_anonymous" size="small" type="info">匿名</el-tag>
                            </div>
                            <div class="score-info">
                                <el-rate v-model="row.score" disabled :max="5" />
                                <el-tag :type="getScoreType(row.score)" size="small">
                                    {{ row.score_level }}
                                </el-tag>
                            </div>
                            <div class="content" v-if="row.content">{{ row.content }}</div>
                            <div class="images" v-if="row.images?.length">
                                <el-image
                                    v-for="(img, index) in row.images.slice(0, 3)"
                                    :key="index"
                                    :src="img"
                                    :preview-src-list="row.images"
                                    fit="cover"
                                    class="review-image"
                                />
                                <span v-if="row.images.length > 3" class="more-images">
                                    +{{ row.images.length - 3 }}
                                </span>
                            </div>
                            <el-tag v-if="row.video" type="warning" size="small">有视频</el-tag>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="服务人员" width="120">
                    <template #default="{ row }">
                        <div class="staff-info">
                            <el-avatar :size="28" :src="row.staff?.avatar">
                                {{ row.staff?.name?.charAt(0) }}
                            </el-avatar>
                            <span>{{ row.staff?.name }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="订单号" width="150">
                    <template #default="{ row }">
                        {{ row.order?.order_sn || '-' }}
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getStatusType(row.status)">{{ row.status_text }}</el-tag>
                        <el-icon v-if="row.is_top" color="#ff9800" class="ml-1"><Top /></el-icon>
                    </template>
                </el-table-column>
                <el-table-column label="评价时间" width="160" prop="create_time_text" />
                <el-table-column label="操作" width="200" fixed="right">
                    <template #default="{ row }">
                        <el-button link type="primary" @click="handleDetail(row)">详情</el-button>
                        <el-button 
                            v-if="row.status === 0" 
                            link 
                            type="success" 
                            @click="handleAudit(row, 1)"
                        >
                            通过
                        </el-button>
                        <el-button 
                            v-if="row.status === 0" 
                            link 
                            type="danger" 
                            @click="handleAudit(row, 2)"
                        >
                            拒绝
                        </el-button>
                        <el-button 
                            v-if="row.status === 1" 
                            link 
                            type="warning" 
                            @click="handleReply(row)"
                        >
                            回复
                        </el-button>
                        <el-dropdown @command="(cmd: string) => handleMore(cmd, row)">
                            <el-button link type="primary">更多</el-button>
                            <template #dropdown>
                                <el-dropdown-menu>
                                    <el-dropdown-item command="top">
                                        {{ row.is_top ? '取消置顶' : '置顶' }}
                                    </el-dropdown-item>
                                    <el-dropdown-item command="show">
                                        {{ row.is_show ? '隐藏' : '显示' }}
                                    </el-dropdown-item>
                                    <el-dropdown-item command="delete" divided>删除</el-dropdown-item>
                                </el-dropdown-menu>
                            </template>
                        </el-dropdown>
                    </template>
                </el-table-column>
            </el-table>

            <div class="pagination-wrap">
                <el-pagination
                    v-model:current-page="queryParams.page"
                    v-model:page-size="queryParams.limit"
                    :total="total"
                    :page-sizes="[10, 20, 50, 100]"
                    layout="total, sizes, prev, pager, next, jumper"
                    @size-change="getList"
                    @current-change="getList"
                />
            </div>
        </el-card>

        <!-- 详情弹窗 -->
        <el-dialog v-model="detailVisible" title="评价详情" width="700px">
            <div v-if="currentReview" class="review-detail">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="用户">
                        <div class="flex items-center">
                            <el-avatar :size="24" :src="currentReview.user?.avatar" />
                            <span class="ml-2">{{ currentReview.user?.nickname }}</span>
                        </div>
                    </el-descriptions-item>
                    <el-descriptions-item label="服务人员">
                        {{ currentReview.staff?.name }}
                    </el-descriptions-item>
                    <el-descriptions-item label="订单号">
                        {{ currentReview.order?.order_sn }}
                    </el-descriptions-item>
                    <el-descriptions-item label="服务日期">
                        {{ currentReview.service_date }}
                    </el-descriptions-item>
                    <el-descriptions-item label="综合评分">
                        <el-rate v-model="currentReview.score" disabled />
                    </el-descriptions-item>
                    <el-descriptions-item label="评分等级">
                        <el-tag :type="getScoreType(currentReview.score)">
                            {{ currentReview.score_level }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="服务态度" :span="1">
                        <el-rate v-model="currentReview.score_service" disabled />
                    </el-descriptions-item>
                    <el-descriptions-item label="专业水平" :span="1">
                        <el-rate v-model="currentReview.score_professional" disabled />
                    </el-descriptions-item>
                    <el-descriptions-item label="时间守约" :span="1">
                        <el-rate v-model="currentReview.score_punctual" disabled />
                    </el-descriptions-item>
                    <el-descriptions-item label="整体效果" :span="1">
                        <el-rate v-model="currentReview.score_effect" disabled />
                    </el-descriptions-item>
                    <el-descriptions-item label="评价内容" :span="2">
                        {{ currentReview.content || '无' }}
                    </el-descriptions-item>
                </el-descriptions>

                <div v-if="currentReview.images?.length" class="detail-images mt-4">
                    <div class="label">评价图片</div>
                    <div class="images">
                        <el-image
                            v-for="(img, index) in currentReview.images"
                            :key="index"
                            :src="img"
                            :preview-src-list="currentReview.images"
                            fit="cover"
                            class="detail-image"
                        />
                    </div>
                </div>

                <div v-if="currentReview.video" class="detail-video mt-4">
                    <div class="label">评价视频</div>
                    <video :src="currentReview.video" controls class="video-player" />
                </div>

                <div v-if="currentReview.replies?.length" class="detail-replies mt-4">
                    <div class="label">回复记录</div>
                    <div v-for="reply in currentReview.replies" :key="reply.id" class="reply-item">
                        <div class="reply-header">
                            <el-tag size="small" :type="reply.reply_type === 1 ? 'info' : 'primary'">
                                {{ reply.reply_type === 1 ? '用户追评' : '商家回复' }}
                            </el-tag>
                            <span class="time">{{ formatTime(reply.create_time) }}</span>
                        </div>
                        <div class="reply-content">{{ reply.content }}</div>
                    </div>
                </div>
            </div>
        </el-dialog>

        <!-- 回复弹窗 -->
        <el-dialog v-model="replyVisible" title="回复评价" width="500px">
            <el-form :model="replyForm" label-width="80px">
                <el-form-item label="回复内容" required>
                    <el-input
                        v-model="replyForm.content"
                        type="textarea"
                        :rows="4"
                        placeholder="请输入回复内容"
                        maxlength="500"
                        show-word-limit
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="replyVisible = false">取消</el-button>
                <el-button type="primary" @click="submitReply">确定</el-button>
            </template>
        </el-dialog>

        <!-- 拒绝原因弹窗 -->
        <el-dialog v-model="rejectVisible" title="拒绝原因" width="400px">
            <el-input
                v-model="rejectReason"
                type="textarea"
                :rows="3"
                placeholder="请输入拒绝原因"
                maxlength="255"
            />
            <template #footer>
                <el-button @click="rejectVisible = false">取消</el-button>
                <el-button type="primary" @click="submitReject">确定</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { ChatDotRound, Clock, Star, TrendCharts, Top } from '@element-plus/icons-vue'
import {
    getReviewList,
    getReviewDetail,
    getReviewStatistics,
    auditReview,
    batchAuditReview,
    toggleReviewTop,
    toggleReviewShow,
    deleteReview,
    replyReview
} from '@/api/review'

const loading = ref(false)
const tableData = ref<any[]>([])
const total = ref(0)
const selectedIds = ref<number[]>([])
const statistics = ref<any>({})
const dateRange = ref<string[]>([])

const queryParams = reactive({
    page: 1,
    limit: 10,
    status: undefined as number | undefined,
    score_level: '',
    review_type: undefined as number | undefined,
    nickname: '',
    staff_name: '',
    start_date: '',
    end_date: ''
})

// 详情
const detailVisible = ref(false)
const currentReview = ref<any>(null)

// 回复
const replyVisible = ref(false)
const replyForm = reactive({
    id: 0,
    content: ''
})

// 拒绝
const rejectVisible = ref(false)
const rejectReason = ref('')
const pendingRejectData = ref<any>(null)

const getStatusType = (status: number) => {
    const map: Record<number, string> = {
        0: 'warning',
        1: 'success',
        2: 'danger'
    }
    return map[status] || 'info'
}

const getScoreType = (score: number) => {
    if (score >= 4) return 'success'
    if (score === 3) return 'warning'
    return 'danger'
}

const formatTime = (timestamp: number) => {
    return new Date(timestamp * 1000).toLocaleString()
}

const getList = async () => {
    loading.value = true
    try {
        if (dateRange.value?.length === 2) {
            queryParams.start_date = dateRange.value[0]
            queryParams.end_date = dateRange.value[1]
        } else {
            queryParams.start_date = ''
            queryParams.end_date = ''
        }
        const res = await getReviewList(queryParams)
        tableData.value = res.lists || []
        total.value = res.count || 0
    } finally {
        loading.value = false
    }
}

const getStatistics = async () => {
    const res = await getReviewStatistics()
    statistics.value = res
}

const handleSearch = () => {
    queryParams.page = 1
    getList()
}

const handleReset = () => {
    Object.assign(queryParams, {
        page: 1,
        status: undefined,
        score_level: '',
        review_type: undefined,
        nickname: '',
        staff_name: '',
        start_date: '',
        end_date: ''
    })
    dateRange.value = []
    getList()
}

const handleSelectionChange = (selection: any[]) => {
    selectedIds.value = selection.map(item => item.id)
}

const handleDetail = async (row: any) => {
    const res = await getReviewDetail({ id: row.id })
    currentReview.value = res
    detailVisible.value = true
}

const handleAudit = async (row: any, status: number) => {
    if (status === 2) {
        pendingRejectData.value = { id: row.id, status }
        rejectReason.value = ''
        rejectVisible.value = true
        return
    }

    await ElMessageBox.confirm('确定通过该评价吗？', '提示')
    await auditReview({ id: row.id, status })
    ElMessage.success('操作成功')
    getList()
    getStatistics()
}

const submitReject = async () => {
    if (!rejectReason.value) {
        ElMessage.warning('请输入拒绝原因')
        return
    }
    await auditReview({
        ...pendingRejectData.value,
        reject_reason: rejectReason.value
    })
    ElMessage.success('操作成功')
    rejectVisible.value = false
    getList()
    getStatistics()
}

const handleBatchAudit = async (status: number) => {
    if (status === 2) {
        pendingRejectData.value = { ids: selectedIds.value, status }
        rejectReason.value = ''
        rejectVisible.value = true
        return
    }

    await ElMessageBox.confirm(`确定批量通过选中的 ${selectedIds.value.length} 条评价吗？`, '提示')
    await batchAuditReview({ ids: selectedIds.value, status })
    ElMessage.success('操作成功')
    getList()
    getStatistics()
}

const handleReply = (row: any) => {
    replyForm.id = row.id
    replyForm.content = ''
    replyVisible.value = true
}

const submitReply = async () => {
    if (!replyForm.content) {
        ElMessage.warning('请输入回复内容')
        return
    }
    await replyReview(replyForm)
    ElMessage.success('回复成功')
    replyVisible.value = false
    getList()
}

const handleMore = async (command: string, row: any) => {
    switch (command) {
        case 'top':
            await toggleReviewTop({ id: row.id })
            ElMessage.success('操作成功')
            getList()
            break
        case 'show':
            await toggleReviewShow({ id: row.id })
            ElMessage.success('操作成功')
            getList()
            break
        case 'delete':
            await ElMessageBox.confirm('确定删除该评价吗？删除后不可恢复', '警告', { type: 'warning' })
            await deleteReview({ id: row.id })
            ElMessage.success('删除成功')
            getList()
            getStatistics()
            break
    }
}

onMounted(() => {
    getList()
    getStatistics()
})
</script>

<style scoped lang="scss">
.stat-card {
    position: relative;
    overflow: hidden;
    
    .stat-content {
        position: relative;
        z-index: 1;
    }
    
    .stat-label {
        font-size: 14px;
        color: #666;
    }
    
    .stat-value {
        font-size: 28px;
        font-weight: bold;
        color: #333;
        margin-top: 8px;
    }
    
    .stat-icon {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #e0e0e0;
    }
    
    &.pending .stat-value { color: #ff9800; }
    &.success .stat-value { color: #52c41a; }
    &.info .stat-value { color: #1890ff; }
}

.review-info {
    .user-info {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        
        .nickname {
            font-weight: 500;
        }
    }
    
    .score-info {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }
    
    .content {
        color: #666;
        font-size: 13px;
        margin-bottom: 8px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .images {
        display: flex;
        gap: 4px;
        align-items: center;
        
        .review-image {
            width: 60px;
            height: 60px;
            border-radius: 4px;
        }
        
        .more-images {
            color: #999;
            font-size: 12px;
        }
    }
}

.staff-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.pagination-wrap {
    display: flex;
    justify-content: flex-end;
    margin-top: 16px;
}

.review-detail {
    .detail-images, .detail-video, .detail-replies {
        .label {
            font-weight: 500;
            margin-bottom: 12px;
            color: #333;
        }
    }
    
    .images {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        
        .detail-image {
            width: 100px;
            height: 100px;
            border-radius: 4px;
        }
    }
    
    .video-player {
        width: 100%;
        max-width: 400px;
        border-radius: 4px;
    }
    
    .reply-item {
        padding: 12px;
        background: #f5f5f5;
        border-radius: 4px;
        margin-bottom: 8px;
        
        .reply-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            
            .time {
                color: #999;
                font-size: 12px;
            }
        }
        
        .reply-content {
            color: #666;
            font-size: 14px;
        }
    }
}
</style>
