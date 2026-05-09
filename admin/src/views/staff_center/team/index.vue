<template>
    <div class="team-member-page">
        <el-card class="!border-none" shadow="never">
            <template #header>
                <div class="flex justify-between">
                    <span>{{ summary.team?.team_name || '队员管理' }}</span>
                    <span class="text-muted">队员 {{ summary.member_count || 0 }} 人</span>
                </div>
            </template>

            <el-form :model="queryParams" inline>
                <el-form-item label="队员">
                    <el-input v-model="queryParams.name" placeholder="姓名、工号、手机号" clearable />
                </el-form-item>
                <el-form-item label="状态">
                    <el-select v-model="queryParams.status" placeholder="全部" clearable class="w-[150px]">
                        <el-option label="启用" :value="1" />
                        <el-option label="禁用" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetSearch">重置</el-button>
                </el-form-item>
            </el-form>

            <el-table :data="tableData" v-loading="loading">
                <el-table-column label="队员" min-width="180">
                    <template #default="{ row }">
                        <div class="staff-cell">
                            <el-avatar :src="row.avatar" :size="32" />
                            <div>
                                <div>{{ row.name }}</div>
                                <div class="helper-line">{{ row.sn }}</div>
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column prop="category_name" label="分类" width="120" />
                <el-table-column prop="mobile" label="手机号" width="130" />
                <el-table-column prop="rating" label="评分" width="80" align="center" />
                <el-table-column prop="order_count" label="接单数" width="90" align="center" />
                <el-table-column prop="status_desc" label="状态" width="90" />
                <el-table-column label="操作" width="100" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="openEdit(row)">编辑</el-button>
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

        <el-dialog v-model="dialogVisible" title="队员资料" width="720px">
            <el-form :model="form" label-width="100px">
                <el-form-item label="姓名">
                    <el-input v-model="form.name" maxlength="50" />
                </el-form-item>
                <el-form-item label="手机号">
                    <el-input v-model="form.mobile" maxlength="20" />
                </el-form-item>
                <el-form-item label="从业年限">
                    <el-input-number v-model="form.experience_years" :min="0" />
                </el-form-item>
                <el-form-item label="简介">
                    <el-input v-model="form.profile" type="textarea" :rows="3" />
                </el-form-item>
                <el-form-item label="服务说明">
                    <el-input v-model="form.service_desc" type="textarea" :rows="3" />
                </el-form-item>
                <el-form-item label="详细介绍">
                    <el-input v-model="form.long_detail" type="textarea" :rows="5" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="dialogVisible = false">取消</el-button>
                <el-button type="primary" @click="saveMember">保存</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { myTeamMemberDetail, myTeamMembers, myTeamMemberUpdate, myTeamSummary } from '@/api/staff-center'

const loading = ref(false)
const tableData = ref<any[]>([])
const total = ref(0)
const summary = ref<any>({})
const dialogVisible = ref(false)

const queryParams = reactive({
    page_no: 1,
    page_size: 15,
    name: '',
    status: '' as any
})

const form = reactive<any>({
    id: 0,
    name: '',
    mobile: '',
    experience_years: 0,
    profile: '',
    service_desc: '',
    long_detail: ''
})

const fetchSummary = async () => {
    summary.value = await myTeamSummary()
}

const fetchList = async () => {
    loading.value = true
    try {
        const res = await myTeamMembers(queryParams)
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
    Object.assign(queryParams, { name: '', status: '' })
    resetPage()
}

const openEdit = async (row: any) => {
    const detail = await myTeamMemberDetail({ id: row.id })
    Object.assign(form, {
        id: detail.id,
        name: detail.name,
        mobile: detail.mobile_full || detail.mobile,
        experience_years: detail.experience_years || 0,
        profile: detail.profile || '',
        service_desc: detail.service_desc || '',
        long_detail: detail.long_detail || ''
    })
    dialogVisible.value = true
}

const saveMember = async () => {
    await myTeamMemberUpdate(form)
    ElMessage.success('保存成功')
    dialogVisible.value = false
    fetchList()
}

onMounted(() => {
    fetchSummary()
    fetchList()
})
</script>

<style scoped>
.staff-cell { display: flex; align-items: center; gap: 10px; }
.helper-line { margin-top: 2px; font-size: 12px; color: #909399; }
.text-muted { color: #909399; font-size: 13px; }
</style>
