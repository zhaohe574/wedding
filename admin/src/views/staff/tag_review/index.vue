<template>
    <admin-page-shell class="staff-tag-review" title="标签审核">
        <template #search>
            <search-panel>
                <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                    <el-form-item class="w-[220px]" label="关键词">
                        <el-input
                            v-model="queryParams.keyword"
                            placeholder="姓名/手机号/工号"
                            clearable
                            @keyup.enter="resetPage"
                        />
                    </el-form-item>
                    <el-form-item class="w-[180px]" label="审核状态">
                        <el-select v-model="queryParams.status" placeholder="选择状态" clearable>
                            <el-option label="全部" value="" />
                            <el-option label="待审核" :value="0" />
                            <el-option label="已通过" :value="1" />
                            <el-option label="已拒绝" :value="2" />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[180px]" label="提交来源">
                        <el-select v-model="queryParams.source" placeholder="选择来源" clearable>
                            <el-option label="全部" value="" />
                            <el-option label="uniapp" :value="1" />
                            <el-option label="后台自助" :value="2" />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[220px]" label="服务分类">
                        <el-cascader
                            v-model="queryParams.category_id"
                            :options="optionsData.categories"
                            :props="{ value: 'id', label: 'name', checkStrictly: true, emitPath: false }"
                            placeholder="选择服务分类"
                            clearable
                        />
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="resetPage">查询</el-button>
                        <el-button @click="resetParams">重置</el-button>
                    </el-form-item>
                </el-form>
            </search-panel>
        </template>

        <div class="admin-page-section">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" width="80" />
                <el-table-column label="服务人员" min-width="180">
                    <template #default="{ row }">
                        <div class="staff-info">
                            <el-avatar :src="row.staff_avatar" :size="36" />
                            <div class="staff-info__copy">
                                <div class="staff-info__name">{{ row.staff_name || '-' }}</div>
                                <div class="staff-info__meta">{{ row.category_name || '未设置分类' }}</div>
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="当前标签" min-width="240">
                    <template #default="{ row }">
                        <div class="tag-list">
                            <el-tag v-for="name in row.current_tag_names || []" :key="`current-${row.id}-${name}`" size="small">
                                {{ name }}
                            </el-tag>
                            <span v-if="!(row.current_tag_names || []).length" class="text-gray-400">未设置</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="申请标签" min-width="240">
                    <template #default="{ row }">
                        <div class="tag-list">
                            <el-tag
                                v-for="name in row.apply_tag_names || []"
                                :key="`apply-${row.id}-${name}`"
                                size="small"
                                type="warning"
                            >
                                {{ name }}
                            </el-tag>
                            <span v-if="!(row.apply_tag_names || []).length" class="text-gray-400">清空标签</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="来源" prop="source_desc" width="120" />
                <el-table-column label="状态" width="110">
                    <template #default="{ row }">
                        <el-tag :type="getStatusType(row.status)">
                            {{ row.status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="拒绝原因" min-width="180" show-overflow-tooltip>
                    <template #default="{ row }">
                        <span v-if="row.reject_reason">{{ row.reject_reason }}</span>
                        <span v-else class="text-gray-400">-</span>
                    </template>
                </el-table-column>
                <el-table-column label="提交时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="220" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['ops.staffTagReview/detail']" type="primary" link @click="handleDetail(row.id)">
                            详情
                        </el-button>
                        <el-button
                            v-if="row.status === 0"
                            v-perms="['ops.staffTagReview/approve']"
                            type="success"
                            link
                            @click="handleApprove(row.id)"
                        >
                            通过
                        </el-button>
                        <el-button
                            v-if="row.status === 0"
                            v-perms="['ops.staffTagReview/reject']"
                            type="danger"
                            link
                            @click="openRejectDialog(row.id)"
                        >
                            拒绝
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </div>

        <el-dialog v-model="detailVisible" title="标签申请详情" width="760px">
            <template v-if="detailData">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="服务人员">{{ detailData.staff_name || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="服务分类">{{ detailData.category_name || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="提交来源">{{ detailData.source_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="审核状态">
                        <el-tag :type="getStatusType(detailData.status)">
                            {{ detailData.status_desc || '-' }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="当前标签" :span="2">
                        <div class="tag-list">
                            <el-tag
                                v-for="name in detailData.current_tag_names || []"
                                :key="`detail-current-${name}`"
                                size="small"
                            >
                                {{ name }}
                            </el-tag>
                            <span v-if="!(detailData.current_tag_names || []).length" class="text-gray-400">未设置</span>
                        </div>
                    </el-descriptions-item>
                    <el-descriptions-item label="申请标签" :span="2">
                        <div class="tag-list">
                            <el-tag
                                v-for="name in detailData.apply_tag_names || []"
                                :key="`detail-apply-${name}`"
                                size="small"
                                type="warning"
                            >
                                {{ name }}
                            </el-tag>
                            <span v-if="!(detailData.apply_tag_names || []).length" class="text-gray-400">清空标签</span>
                        </div>
                    </el-descriptions-item>
                    <el-descriptions-item label="拒绝原因" :span="2">
                        <span v-if="detailData.reject_reason">{{ detailData.reject_reason }}</span>
                        <span v-else class="text-gray-400">-</span>
                    </el-descriptions-item>
                </el-descriptions>
            </template>
        </el-dialog>

        <el-dialog v-model="rejectDialogVisible" title="拒绝原因" width="520px">
            <el-form label-width="80px">
                <el-form-item label="原因" required>
                    <el-input
                        v-model="rejectForm.reject_reason"
                        type="textarea"
                        :rows="4"
                        maxlength="255"
                        show-word-limit
                        placeholder="请输入拒绝原因"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="rejectDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleReject">确定</el-button>
            </template>
        </el-dialog>
    </admin-page-shell>
</template>

<script lang="ts" setup name="staffTagReview">
import { ElMessage } from 'element-plus'
import { usePaging } from '@/hooks/usePaging'
import { useDictOptions } from '@/hooks/useDictOptions'
import feedback from '@/utils/feedback'
import { categoryTree } from '@/api/service'
import {
    staffTagReviewApprove,
    staffTagReviewDetail,
    staffTagReviewLists,
    staffTagReviewReject,
} from '@/api/staff-tag-review'

const queryParams = reactive({
    keyword: '',
    status: 0,
    source: '',
    category_id: '',
})

const detailVisible = ref(false)
const rejectDialogVisible = ref(false)
const detailData = ref<any>(null)
const rejectForm = reactive({
    id: 0,
    reject_reason: '',
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: staffTagReviewLists,
    params: queryParams,
})

const { optionsData } = useDictOptions<{
    categories: any[]
}>({
    categories: {
        api: categoryTree,
    },
})

const getStatusType = (status: number) => {
    if (status === 1) return 'success'
    if (status === 2) return 'danger'
    return 'warning'
}

const handleDetail = async (id: number) => {
    detailData.value = await staffTagReviewDetail({ id })
    detailVisible.value = true
}

const handleApprove = async (id: number) => {
    await feedback.confirm('确认通过该标签申请吗？')
    await staffTagReviewApprove({ id })
    ElMessage.success('审核通过')
    getLists()
}

const openRejectDialog = (id: number) => {
    rejectForm.id = id
    rejectForm.reject_reason = ''
    rejectDialogVisible.value = true
}

const handleReject = async () => {
    if (!rejectForm.reject_reason.trim()) {
        ElMessage.warning('请输入拒绝原因')
        return
    }

    await staffTagReviewReject({
        id: rejectForm.id,
        reject_reason: rejectForm.reject_reason.trim(),
    })
    ElMessage.success('已拒绝')
    rejectDialogVisible.value = false
    getLists()
}

onActivated(() => {
    getLists()
})

getLists()
</script>

<style lang="scss" scoped>
.staff-tag-review {
    .staff-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .staff-info__copy {
        min-width: 0;
    }

    .staff-info__name {
        font-weight: 600;
        color: #1f2937;
    }

    .staff-info__meta {
        margin-top: 4px;
        font-size: 12px;
        color: #6b7280;
    }

    .tag-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
}
</style>
