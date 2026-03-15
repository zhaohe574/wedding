<template>
    <admin-page-shell
        class="review-share-reward"
        title="晒单奖励"
        description="审核评价晒单奖励申请，并查看对应评价与积分信息。"
    >
        <template #search>
            <search-panel>
                <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                    <el-form-item class="w-[160px]" label="审核状态">
                        <el-select v-model="queryParams.status" placeholder="全部状态" clearable>
                            <el-option label="全部" value="" />
                            <el-option label="待审核" :value="0" />
                            <el-option label="已通过" :value="1" />
                            <el-option label="已拒绝" :value="2" />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[160px]" label="晒单平台">
                        <el-select
                            v-model="queryParams.share_platform"
                            placeholder="全部平台"
                            clearable
                        >
                            <el-option label="全部" value="" />
                            <el-option
                                v-for="item in platformOptions"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[180px]" label="用户昵称">
                        <el-input
                            v-model="queryParams.nickname"
                            placeholder="输入用户昵称"
                            clearable
                            @keyup.enter="handleSearch"
                        />
                    </el-form-item>
                    <el-form-item class="w-[180px]" label="评价ID">
                        <el-input
                            v-model="queryParams.review_id"
                            placeholder="输入评价ID"
                            clearable
                            @keyup.enter="handleSearch"
                        />
                    </el-form-item>
                    <el-form-item class="w-[320px]" label="申请时间">
                        <el-date-picker
                            v-model="dateRange"
                            type="daterange"
                            value-format="YYYY-MM-DD"
                            start-placeholder="开始日期"
                            end-placeholder="结束日期"
                            clearable
                        />
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="handleSearch">查询</el-button>
                        <el-button @click="handleReset">重置</el-button>
                    </el-form-item>
                </el-form>
            </search-panel>
        </template>

        <div class="admin-page-section">
            <el-card class="!border-none" shadow="never">
                <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                    <el-table-column label="ID" prop="id" width="80" />
                    <el-table-column label="评价ID" prop="review_id" width="100" />
                    <el-table-column label="用户" min-width="180">
                        <template #default="{ row }">
                            <div class="flex items-center" v-if="row.user">
                                <el-avatar :src="row.user.avatar" :size="30" />
                                <span class="ml-2">{{ row.user.nickname || '-' }}</span>
                            </div>
                            <span v-else>-</span>
                        </template>
                    </el-table-column>
                    <el-table-column label="晒单平台" width="120">
                        <template #default="{ row }">
                            {{ row.platform_text || '-' }}
                        </template>
                    </el-table-column>
                    <el-table-column label="奖励积分" prop="reward_points" width="110" />
                    <el-table-column label="状态" width="110">
                        <template #default="{ row }">
                            <el-tag :type="getStatusTagType(row.status)">
                                {{ row.status_text }}
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="申请时间" prop="create_time_text" min-width="170" />
                    <el-table-column label="审核时间" min-width="170">
                        <template #default="{ row }">
                            {{ row.audit_time_text || '-' }}
                        </template>
                    </el-table-column>
                    <el-table-column label="操作" width="220" fixed="right">
                        <template #default="{ row }">
                            <el-button type="primary" link @click="handleDetail(row)">
                                详情
                            </el-button>
                            <el-button
                                v-if="row.status === 0"
                                type="success"
                                link
                                @click="openAudit(row, 1)"
                            >
                                通过
                            </el-button>
                            <el-button
                                v-if="row.status === 0"
                                type="danger"
                                link
                                @click="openAudit(row, 2)"
                            >
                                拒绝
                            </el-button>
                        </template>
                    </el-table-column>
                </el-table>

                <div class="flex justify-end mt-4">
                    <pagination v-model="pager" @change="getLists" />
                </div>
            </el-card>
        </div>

        <el-dialog v-model="detailVisible" title="晒单奖励详情" width="760px">
            <div v-if="detailData" class="share-reward-detail">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="记录ID">
                        {{ detailData.id }}
                    </el-descriptions-item>
                    <el-descriptions-item label="评价ID">
                        {{ detailData.review_id }}
                    </el-descriptions-item>
                    <el-descriptions-item label="用户昵称">
                        {{ detailData.user?.nickname || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="晒单平台">
                        {{ detailData.platform_text || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="奖励积分">
                        {{ detailData.reward_points }}
                    </el-descriptions-item>
                    <el-descriptions-item label="审核状态">
                        <el-tag :type="getStatusTagType(detailData.status)">
                            {{ detailData.status_text }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="申请时间">
                        {{ formatDateTime(detailData.create_time) }}
                    </el-descriptions-item>
                    <el-descriptions-item label="审核时间">
                        {{ detailData.audit_time ? formatDateTime(detailData.audit_time) : '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="审核备注" :span="2">
                        {{ detailData.audit_remark || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="评价内容" :span="2">
                        {{ detailData.review?.content || '无文字评价' }}
                    </el-descriptions-item>
                </el-descriptions>

                <div v-if="detailData.verify_image" class="mt-4">
                    <div class="mb-2 font-medium text-[#344054]">核验截图</div>
                    <el-image
                        :src="detailData.verify_image"
                        :preview-src-list="[detailData.verify_image]"
                        fit="cover"
                        class="h-[220px] w-[220px] rounded-lg"
                    />
                </div>
            </div>
        </el-dialog>

        <el-dialog
            v-model="auditVisible"
            :title="auditForm.status === 1 ? '通过晒单奖励' : '拒绝晒单奖励'"
            width="520px"
        >
            <el-form :model="auditForm" label-width="100px">
                <el-form-item label="审核备注">
                    <el-input
                        v-model="auditForm.audit_remark"
                        type="textarea"
                        :rows="4"
                        :placeholder="
                            auditForm.status === 1 ? '可填写通过备注' : '请输入拒绝原因'
                        "
                    />
                </el-form-item>
            </el-form>

            <template #footer>
                <el-button @click="auditVisible = false">取消</el-button>
                <el-button
                    :type="auditForm.status === 1 ? 'success' : 'danger'"
                    @click="submitAudit"
                >
                    确认
                </el-button>
            </template>
        </el-dialog>
    </admin-page-shell>
</template>

<script lang="ts" setup name="reviewShareReward">
import {
    getReviewShareRewardList,
    getReviewShareRewardDetail,
    auditReviewShareReward
} from '@/api/review'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

const platformOptions = [
    { label: '微信好友', value: 'wechat' },
    { label: '朋友圈', value: 'moments' },
    { label: '微博', value: 'weibo' },
    { label: '抖音', value: 'douyin' },
    { label: '小红书', value: 'xiaohongshu' }
]

const dateRange = ref<string[]>([])
const queryParams = reactive({
    status: '',
    share_platform: '',
    nickname: '',
    review_id: '',
    start_date: '',
    end_date: ''
})

const detailVisible = ref(false)
const detailData = ref<any>(null)
const auditVisible = ref(false)
const auditForm = reactive({
    id: 0,
    status: 1,
    audit_remark: ''
})

const { pager, getLists } = usePaging({
    fetchFun: getReviewShareRewardList,
    params: queryParams
})

const getStatusTagType = (status: number): 'warning' | 'success' | 'danger' | 'info' => {
    const statusMap: Record<number, 'warning' | 'success' | 'danger' | 'info'> = {
        0: 'warning',
        1: 'success',
        2: 'danger'
    }
    return statusMap[status] || 'info'
}

const formatDateTime = (timestamp: number) => {
    if (!timestamp) {
        return '-'
    }
    return new Date(timestamp * 1000).toLocaleString()
}

const syncDateRange = () => {
    queryParams.start_date = dateRange.value?.[0] || ''
    queryParams.end_date = dateRange.value?.[1] || ''
}

const handleSearch = () => {
    syncDateRange()
    pager.page = 1
    getLists()
}

const handleReset = () => {
    dateRange.value = []
    queryParams.status = ''
    queryParams.share_platform = ''
    queryParams.nickname = ''
    queryParams.review_id = ''
    queryParams.start_date = ''
    queryParams.end_date = ''
    pager.page = 1
    getLists()
}

const handleDetail = async (row: any) => {
    detailData.value = await getReviewShareRewardDetail({ id: row.id })
    detailVisible.value = true
}

const openAudit = (row: any, status: number) => {
    auditForm.id = Number(row.id)
    auditForm.status = status
    auditForm.audit_remark = ''
    auditVisible.value = true
}

const submitAudit = async () => {
    if (auditForm.status === 2 && !auditForm.audit_remark.trim()) {
        feedback.msgError('请填写拒绝原因')
        return
    }

    await auditReviewShareReward({
        id: auditForm.id,
        status: auditForm.status,
        audit_remark: auditForm.audit_remark
    })

    feedback.msgSuccess('审核成功')
    auditVisible.value = false
    getLists()
}

onActivated(() => {
    getLists()
})

getLists()
</script>

<style scoped>
.share-reward-detail :deep(.el-descriptions__label) {
    width: 110px;
}
</style>
