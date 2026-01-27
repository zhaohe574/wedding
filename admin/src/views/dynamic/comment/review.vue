<template>
    <div class="comment-review-page">
        <el-card class="!border-none" shadow="never">
            <!-- 搜索栏 -->
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[150px]" label="审核状态">
                    <el-select v-model="queryParams.review_status" placeholder="选择状态" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="待审核" :value="0" />
                        <el-option label="已通过" :value="1" />
                        <el-option label="已拒绝" :value="2" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[150px]" label="动态ID">
                    <el-input
                        v-model="queryParams.dynamic_id"
                        placeholder="输入动态ID"
                        clearable
                    />
                </el-form-item>
                <el-form-item class="w-[150px]" label="用户ID">
                    <el-input
                        v-model="queryParams.user_id"
                        placeholder="输入用户ID"
                        clearable
                    />
                </el-form-item>
                <el-form-item class="w-[200px]" label="内容">
                    <el-input
                        v-model="queryParams.content"
                        placeholder="搜索评论内容"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <!-- 工具栏 -->
            <div class="mb-4 flex items-center justify-between">
                <div class="text-lg font-medium">评论管理</div>
                <div class="flex gap-2">
                    <el-button
                        v-if="selectedIds.length > 0"
                        type="success"
                        @click="handleBatchApprove"
                    >
                        批量通过 ({{ selectedIds.length }})
                    </el-button>
                    <el-button
                        v-if="selectedIds.length > 0"
                        type="danger"
                        @click="handleBatchReject"
                    >
                        批量拒绝 ({{ selectedIds.length }})
                    </el-button>
                    <el-button
                        v-if="selectedIds.length > 0"
                        type="danger"
                        plain
                        @click="handleBatchDelete"
                    >
                        批量删除 ({{ selectedIds.length }})
                    </el-button>
                </div>
            </div>

            <!-- 列表 -->
            <el-table
                v-loading="pager.loading"
                :data="pager.lists"
                @selection-change="handleSelectionChange"
            >
                <el-table-column type="selection" width="55" />
                <el-table-column label="ID" prop="id" width="80" />
                <el-table-column label="评论内容" prop="content" min-width="200" show-overflow-tooltip />
                <el-table-column label="发表用户" width="150">
                    <template #default="{ row }">
                        <div class="flex items-center" v-if="row.user">
                            <el-avatar :src="row.user.avatar" :size="32" />
                            <span class="ml-2">{{ row.user.nickname }}</span>
                        </div>
                        <span v-else>未知用户</span>
                    </template>
                </el-table-column>
                <el-table-column label="所属动态" min-width="180" show-overflow-tooltip>
                    <template #default="{ row }">
                        <div v-if="row.dynamic">
                            <div v-if="row.dynamic.title" class="font-bold">{{ row.dynamic.title }}</div>
                            <div class="text-gray-500 text-sm">{{ row.dynamic.content?.substring(0, 30) }}...</div>
                        </div>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="审核状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getReviewStatusType(row.review_status)">
                            {{ row.review_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="发表时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="220" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row)">
                            详情
                        </el-button>
                        <el-button 
                            v-if="row.review_status === 0" 
                            type="success" 
                            link 
                            @click="handleApprove(row.id)"
                        >
                            通过
                        </el-button>
                        <el-button 
                            v-if="row.review_status === 0" 
                            type="danger" 
                            link 
                            @click="handleReject(row.id)"
                        >
                            拒绝
                        </el-button>
                        <el-button 
                            type="danger" 
                            link 
                            @click="handleDelete(row)"
                        >
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>

            <!-- 分页 -->
            <div class="mt-4 flex justify-end">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>


        <!-- 拒绝原因弹窗 -->
        <el-dialog v-model="rejectDialogVisible" title="拒绝原因" width="500px">
            <el-form :model="rejectForm" label-width="80px">
                <el-form-item label="拒绝原因">
                    <el-input
                        v-model="rejectForm.remark"
                        type="textarea"
                        :rows="4"
                        placeholder="请输入拒绝原因"
                        maxlength="255"
                        show-word-limit
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="rejectDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="confirmReject">确定</el-button>
            </template>
        </el-dialog>

        <!-- 详情弹窗 -->
        <el-dialog v-model="detailVisible" title="评论详情" width="800px">
            <div v-if="currentComment">
                <!-- 评论信息 -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold mb-3">评论信息</h3>
                    <el-descriptions :column="2" border>
                        <el-descriptions-item label="评论ID">{{ currentComment.id }}</el-descriptions-item>
                        <el-descriptions-item label="审核状态">
                            <el-tag :type="currentComment.review_status === 0 ? 'warning' : (currentComment.review_status === 1 ? 'success' : 'danger')">
                                {{ currentComment.review_status_desc }}
                            </el-tag>
                        </el-descriptions-item>
                        <el-descriptions-item label="评论用户" :span="2">
                            <div class="flex items-center" v-if="currentComment.user">
                                <el-avatar :src="currentComment.user.avatar" :size="32" />
                                <div class="ml-2">
                                    <div>{{ currentComment.user.nickname }}</div>
                                    <div class="text-gray-400 text-xs">{{ currentComment.user.mobile || '-' }}</div>
                                </div>
                            </div>
                            <span v-else>未知用户</span>
                        </el-descriptions-item>
                        <el-descriptions-item label="评论内容" :span="2">
                            <div class="whitespace-pre-wrap">{{ currentComment.content }}</div>
                        </el-descriptions-item>
                        <el-descriptions-item label="点赞数">{{ currentComment.like_count }}</el-descriptions-item>
                        <el-descriptions-item label="回复数">{{ currentComment.reply_count }}</el-descriptions-item>
                        <el-descriptions-item label="发表时间" :span="2">{{ currentComment.create_time }}</el-descriptions-item>
                        <el-descriptions-item label="审核时间" :span="2" v-if="currentComment.review_time">
                            {{ currentComment.review_time }}
                        </el-descriptions-item>
                        <el-descriptions-item label="审核备注" :span="2" v-if="currentComment.review_remark">
                            {{ currentComment.review_remark }}
                        </el-descriptions-item>
                    </el-descriptions>
                </div>

                <!-- 父评论信息（如果是回复） -->
                <div class="mb-6" v-if="currentComment.parent_comment">
                    <h3 class="text-lg font-bold mb-3">回复的评论</h3>
                    <el-card class="bg-gray-50">
                        <div class="flex items-start">
                            <el-avatar :src="currentComment.parent_comment.user?.avatar" :size="32" />
                            <div class="ml-3 flex-1">
                                <div class="font-bold">{{ currentComment.parent_comment.user?.nickname || '未知用户' }}</div>
                                <div class="mt-1 text-gray-600">{{ currentComment.parent_comment.content }}</div>
                            </div>
                        </div>
                    </el-card>
                </div>

                <!-- 关联动态信息 -->
                <div v-if="currentComment.dynamic">
                    <h3 class="text-lg font-bold mb-3">关联动态</h3>
                    <el-descriptions :column="2" border>
                        <el-descriptions-item label="动态ID">{{ currentComment.dynamic.id }}</el-descriptions-item>
                        <el-descriptions-item label="动态类型">
                            <el-tag size="small">{{ currentComment.dynamic.type_desc }}</el-tag>
                        </el-descriptions-item>
                        <el-descriptions-item label="发布者" :span="2">
                            <div class="flex items-center" v-if="currentComment.dynamic.publisher">
                                <el-avatar :src="currentComment.dynamic.publisher.avatar" :size="32" />
                                <span class="ml-2">{{ currentComment.dynamic.publisher.nickname }}</span>
                            </div>
                            <span v-else>-</span>
                        </el-descriptions-item>
                        <el-descriptions-item label="标题" :span="2" v-if="currentComment.dynamic.title">
                            {{ currentComment.dynamic.title }}
                        </el-descriptions-item>
                        <el-descriptions-item label="内容" :span="2">
                            <div class="whitespace-pre-wrap max-h-32 overflow-y-auto">
                                {{ currentComment.dynamic.content }}
                            </div>
                        </el-descriptions-item>
                        <el-descriptions-item label="位置" :span="2" v-if="currentComment.dynamic.location">
                            {{ currentComment.dynamic.location }}
                        </el-descriptions-item>
                        <el-descriptions-item label="标签" :span="2" v-if="currentComment.dynamic.tags">
                            {{ currentComment.dynamic.tags }}
                        </el-descriptions-item>
                    </el-descriptions>

                    <!-- 动态图片 -->
                    <div class="mt-4" v-if="currentComment.dynamic.images && currentComment.dynamic.images.length > 0">
                        <h4 class="font-bold mb-2">动态图片</h4>
                        <div class="flex flex-wrap gap-2">
                            <el-image
                                v-for="(img, idx) in currentComment.dynamic.images"
                                :key="idx"
                                :src="img"
                                style="width: 100px; height: 100px"
                                fit="cover"
                                :preview-src-list="currentComment.dynamic.images"
                                :initial-index="idx"
                            />
                        </div>
                    </div>

                    <!-- 动态视频 -->
                    <div class="mt-4" v-if="currentComment.dynamic.video_url">
                        <h4 class="font-bold mb-2">动态视频</h4>
                        <video :src="currentComment.dynamic.video_url" controls style="max-width: 400px" />
                    </div>

                    <!-- 动态互动数据 -->
                    <div class="mt-4">
                        <h4 class="font-bold mb-2">互动数据</h4>
                        <el-descriptions :column="5">
                            <el-descriptions-item label="浏览">{{ currentComment.dynamic.view_count }}</el-descriptions-item>
                            <el-descriptions-item label="点赞">{{ currentComment.dynamic.like_count }}</el-descriptions-item>
                            <el-descriptions-item label="评论">{{ currentComment.dynamic.comment_count }}</el-descriptions-item>
                            <el-descriptions-item label="收藏">{{ currentComment.dynamic.collect_count }}</el-descriptions-item>
                        </el-descriptions>
                    </div>
                </div>
            </div>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="commentReview">
import { 
    apiGetReviewList, 
    apiGetCommentDetail,
    apiApproveComment, 
    apiRejectComment,
    apiBatchApproveComment,
    apiBatchRejectComment,
    apiDeleteComment,
    apiBatchDeleteComment
} from '@/api/dynamic'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

const selectedIds = ref<number[]>([])
const rejectDialogVisible = ref(false)
const detailVisible = ref(false)
const currentComment = ref<any>(null)
const rejectForm = reactive({
    id: 0,
    ids: [] as number[],
    remark: '',
    isBatch: false
})

const queryParams = reactive({
    review_status: 0,
    dynamic_id: '',
    user_id: '',
    content: ''
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: apiGetReviewList,
    params: queryParams
})

// 获取审核状态类型
const getReviewStatusType = (status: number) => {
    const types: Record<number, string> = {
        0: 'warning',
        1: 'success',
        2: 'danger'
    }
    return types[status] || 'info'
}

// 选择变化
const handleSelectionChange = (selection: any[]) => {
    selectedIds.value = selection.map(item => item.id)
}

// 查看详情
const handleDetail = async (row: any) => {
    const res = await apiGetCommentDetail({ id: row.id })
    currentComment.value = res
    detailVisible.value = true
}

// 审核通过
const handleApprove = async (id: number) => {
    await feedback.confirm('确认通过该评论？')
    await apiApproveComment({ id })
    feedback.msgSuccess('审核通过')
    getLists()
}

// 拒绝
const handleReject = (id: number) => {
    rejectForm.id = id
    rejectForm.ids = []
    rejectForm.remark = ''
    rejectForm.isBatch = false
    rejectDialogVisible.value = true
}

// 确认拒绝
const confirmReject = async () => {
    if (!rejectForm.remark.trim()) {
        feedback.msgWarning('请输入拒绝原因')
        return
    }

    try {
        if (rejectForm.isBatch) {
            await apiBatchRejectComment({
                ids: rejectForm.ids,
                remark: rejectForm.remark
            })
        } else {
            await apiRejectComment({
                id: rejectForm.id,
                remark: rejectForm.remark
            })
        }
        feedback.msgSuccess('已拒绝')
        rejectDialogVisible.value = false
        getLists()
    } catch (error) {
        feedback.msgError('操作失败')
    }
}

// 批量通过
const handleBatchApprove = async () => {
    await feedback.confirm(`确认通过选中的 ${selectedIds.value.length} 条评论？`)
    const res: any = await apiBatchApproveComment({ ids: selectedIds.value })
    feedback.msgSuccess(`批量审核完成：成功 ${res.success_count} 条，失败 ${res.fail_count} 条`)
    getLists()
}

// 批量拒绝
const handleBatchReject = () => {
    rejectForm.id = 0
    rejectForm.ids = selectedIds.value
    rejectForm.remark = ''
    rejectForm.isBatch = true
    rejectDialogVisible.value = true
}

// 删除评论
const handleDelete = async (row: any) => {
    await feedback.confirm('确定要删除该评论吗？删除后不可恢复')
    await apiDeleteComment({ id: row.id })
    feedback.msgSuccess('删除成功')
    getLists()
}

// 批量删除
const handleBatchDelete = async () => {
    await feedback.confirm(`确定要删除选中的 ${selectedIds.value.length} 条评论吗？删除后不可恢复`)
    const res: any = await apiBatchDeleteComment({ ids: selectedIds.value })
    feedback.msgSuccess(`批量删除完成：成功 ${res.success_count} 条，失败 ${res.fail_count} 条`)
    getLists()
}

onActivated(() => {
    getLists()
})

getLists()
</script>
