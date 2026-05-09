<template>
    <div class="team-review-page">
        <el-card class="!border-none" shadow="never">
            <el-tabs v-model="activeTab" @tab-change="resetPage">
                <el-tab-pane label="作品" name="work" />
                <el-tab-pane label="证书" name="certificate" />
                <el-tab-pane label="动态" name="dynamic" />
                <el-tab-pane label="标签申请" name="tag" />
            </el-tabs>

            <el-form :model="queryParams" inline>
                <el-form-item label="关键词">
                    <el-input v-model="queryParams.keyword" placeholder="队员或内容关键词" clearable />
                </el-form-item>
                <el-form-item label="状态">
                    <el-select v-model="queryParams.status" placeholder="全部" clearable class="w-[150px]">
                        <el-option label="待审核" :value="0" />
                        <el-option label="已通过" :value="1" />
                        <el-option label="已拒绝" :value="2" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetSearch">重置</el-button>
                </el-form-item>
            </el-form>

            <el-table :data="tableData" v-loading="loading">
                <el-table-column label="队员" width="140">
                    <template #default="{ row }">{{ resolveStaffName(row) }}</template>
                </el-table-column>
                <el-table-column label="内容" min-width="240" show-overflow-tooltip>
                    <template #default="{ row }">{{ resolveTitle(row) }}</template>
                </el-table-column>
                <el-table-column label="状态" width="110">
                    <template #default="{ row }">
                        <el-tag :type="getStatusType(resolveStatus(row))">{{ resolveStatusText(row) }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="create_time" label="提交时间" width="170" />
                <el-table-column label="操作" width="150" fixed="right">
                    <template #default="{ row }">
                        <el-button type="success" link @click="approve(row)">通过</el-button>
                        <el-button type="danger" link @click="reject(row)">拒绝</el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="flex justify-end mt-4">
                <el-pagination
                    v-model:current-page="queryParams.page_no"
                    v-model:page-size="queryParams.page_size"
                    :total="total"
                    :page-sizes="[15, 30, 50]"
                    layout="total, sizes, prev, pager, next"
                    @size-change="resetPage"
                    @current-change="fetchList"
                />
            </div>
        </el-card>
    </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
    teamCertificateAudit,
    teamCertificateLists,
    teamDynamicAudit,
    teamDynamicLists,
    teamTagReviewApprove,
    teamTagReviewLists,
    teamTagReviewReject,
    teamWorkAudit,
    teamWorkLists
} from '@/api/staff-center'

type TabName = 'work' | 'certificate' | 'dynamic' | 'tag'

const activeTab = ref<TabName>('work')
const loading = ref(false)
const tableData = ref<any[]>([])
const total = ref(0)
const queryParams = reactive({
    page_no: 1,
    page_size: 15,
    keyword: '',
    status: '' as any
})

const getStatusType = (status: number) => {
    return ({ 0: 'warning', 1: 'success', 2: 'danger' } as any)[status] || 'info'
}

const buildParams = () => {
    const params: any = { page_no: queryParams.page_no, page_size: queryParams.page_size }
    if (queryParams.keyword) {
        if (activeTab.value === 'tag') params.keyword = queryParams.keyword
        else if (activeTab.value === 'certificate') params.name = queryParams.keyword
        else params.content = queryParams.keyword
    }
    if (queryParams.status !== '') {
        if (activeTab.value === 'dynamic') params.status = queryParams.status
        else if (activeTab.value === 'certificate') params.verify_status = queryParams.status
        else if (activeTab.value === 'work') params.audit_status = queryParams.status
        else params.status = queryParams.status
    }
    return params
}

const fetchList = async () => {
    loading.value = true
    try {
        const params = buildParams()
        const apiMap = {
            work: teamWorkLists,
            certificate: teamCertificateLists,
            dynamic: teamDynamicLists,
            tag: teamTagReviewLists
        }
        const res = await apiMap[activeTab.value](params)
        tableData.value = res.lists || []
        total.value = res.count || 0
    } finally {
        loading.value = false
    }
}

const resetPage = () => {
    queryParams.page_no = 1
    fetchList()
}

const resetSearch = () => {
    Object.assign(queryParams, { keyword: '', status: '' })
    resetPage()
}

const resolveStaffName = (row: any) => row.staff?.name || row.staff_name || row.publisher?.nickname || '-'
const resolveTitle = (row: any) => row.title || row.name || row.content || row.apply_tag_names?.join('、') || '-'
const resolveStatus = (row: any) => Number(row.audit_status ?? row.verify_status ?? row.status ?? 0)
const resolveStatusText = (row: any) => row.audit_status_desc || row.verify_status_desc || row.status_desc || row.status_text || ['待审核', '已通过', '已拒绝'][resolveStatus(row)] || '-'

const approve = async (row: any) => {
    await ElMessageBox.confirm('确定审核通过？', '审核确认')
    if (activeTab.value === 'work') await teamWorkAudit({ id: row.id, audit_status: 1 })
    if (activeTab.value === 'certificate') await teamCertificateAudit({ id: row.id, audit_status: 1, verify_status: 1 })
    if (activeTab.value === 'dynamic') await teamDynamicAudit({ id: row.id, approved: true })
    if (activeTab.value === 'tag') await teamTagReviewApprove({ id: row.id })
    ElMessage.success('审核成功')
    fetchList()
}

const reject = async (row: any) => {
    const result = await ElMessageBox.prompt('请输入拒绝原因', '审核拒绝', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        inputValue: ''
    })
    const reason = result.value || '不符合展示要求'
    if (activeTab.value === 'work') await teamWorkAudit({ id: row.id, audit_status: 2 })
    if (activeTab.value === 'certificate') await teamCertificateAudit({ id: row.id, audit_status: 2, verify_status: 2, reject_reason: reason })
    if (activeTab.value === 'dynamic') await teamDynamicAudit({ id: row.id, approved: false, remark: reason })
    if (activeTab.value === 'tag') await teamTagReviewReject({ id: row.id, reject_reason: reason })
    ElMessage.success('已拒绝')
    fetchList()
}

onMounted(fetchList)
</script>
