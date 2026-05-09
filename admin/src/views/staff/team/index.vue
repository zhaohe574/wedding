<template>
    <div class="staff-team-page">
        <el-card class="!border-none" shadow="never">
            <template #header>
                <div class="flex justify-between">
                    <span>服务团队</span>
                    <el-button type="primary" @click="openForm()">新增团队</el-button>
                </div>
            </template>

            <el-form :model="queryParams" inline>
                <el-form-item label="团队名称">
                    <el-input v-model="queryParams.name" placeholder="请输入团队名称" clearable />
                </el-form-item>
                <el-form-item label="状态">
                    <el-select v-model="queryParams.status" placeholder="全部" clearable class="w-[160px]">
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
                <el-table-column prop="name" label="团队名称" min-width="160" />
                <el-table-column label="队长" width="140">
                    <template #default="{ row }">{{ row.leader?.name || '-' }}</template>
                </el-table-column>
                <el-table-column prop="member_count" label="队员数" width="90" align="center" />
                <el-table-column label="队员" min-width="260" show-overflow-tooltip>
                    <template #default="{ row }">
                        <span>{{ (row.members || []).map((item: any) => item.staff?.name).filter(Boolean).join('、') || '-' }}</span>
                    </template>
                </el-table-column>
                <el-table-column prop="sort" label="排序" width="80" align="center" />
                <el-table-column prop="status" label="状态" width="90">
                    <template #default="{ row }">
                        <el-tag :type="row.status === 1 ? 'success' : 'info'">{{ row.status_text }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="remark" label="备注" min-width="160" show-overflow-tooltip />
                <el-table-column label="操作" width="170" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="openForm(row)">编辑</el-button>
                        <el-button type="warning" link @click="toggleStatus(row)">
                            {{ row.status === 1 ? '禁用' : '启用' }}
                        </el-button>
                        <el-button type="danger" link @click="removeTeam(row)">删除</el-button>
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

        <el-dialog v-model="dialogVisible" :title="form.id ? '编辑团队' : '新增团队'" width="640px">
            <el-form :model="form" label-width="100px">
                <el-form-item label="团队名称" required>
                    <el-input v-model="form.name" maxlength="100" placeholder="请输入团队名称" />
                </el-form-item>
                <el-form-item label="队长" required>
                    <el-select v-model="form.leader_staff_id" filterable placeholder="请选择队长" class="w-full">
                        <el-option v-for="item in staffOptions" :key="item.id" :label="item.name" :value="item.id" />
                    </el-select>
                </el-form-item>
                <el-form-item label="队员">
                    <el-select v-model="form.member_ids" multiple filterable placeholder="请选择队员" class="w-full">
                        <el-option
                            v-for="item in staffOptions"
                            :key="item.id"
                            :label="item.name"
                            :value="item.id"
                            :disabled="item.id === form.leader_staff_id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="状态">
                    <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item label="排序">
                    <el-input-number v-model="form.sort" :min="0" />
                </el-form-item>
                <el-form-item label="备注">
                    <el-input v-model="form.remark" type="textarea" :rows="3" maxlength="255" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="dialogVisible = false">取消</el-button>
                <el-button type="primary" @click="saveTeam">保存</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
    staffAll,
    staffTeamAdd,
    staffTeamChangeStatus,
    staffTeamDelete,
    staffTeamEdit,
    staffTeamLists
} from '@/api/staff'

const loading = ref(false)
const tableData = ref<any[]>([])
const total = ref(0)
const staffOptions = ref<any[]>([])
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
    leader_staff_id: '',
    member_ids: [],
    status: 1,
    sort: 0,
    remark: ''
})

const fetchStaffOptions = async () => {
    staffOptions.value = await staffAll()
}

const fetchList = async () => {
    loading.value = true
    try {
        const res = await staffTeamLists(queryParams)
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

const openForm = (row?: any) => {
    Object.assign(form, {
        id: row?.id || 0,
        name: row?.name || '',
        leader_staff_id: row?.leader_staff_id || '',
        member_ids: row?.member_ids || [],
        status: row?.status ?? 1,
        sort: row?.sort || 0,
        remark: row?.remark || ''
    })
    dialogVisible.value = true
}

const saveTeam = async () => {
    if (!form.name || !form.leader_staff_id) {
        ElMessage.warning('请填写团队名称并选择队长')
        return
    }
    const payload = { ...form, member_ids: form.member_ids.filter((id: number) => id !== form.leader_staff_id) }
    if (form.id) {
        await staffTeamEdit(payload)
    } else {
        await staffTeamAdd(payload)
    }
    ElMessage.success('保存成功')
    dialogVisible.value = false
    fetchList()
}

const toggleStatus = async (row: any) => {
    const nextStatus = row.status === 1 ? 0 : 1
    await staffTeamChangeStatus({ id: row.id, status: nextStatus })
    ElMessage.success('操作成功')
    fetchList()
}

const removeTeam = async (row: any) => {
    await ElMessageBox.confirm(`确定删除团队“${row.name}”？`, '删除确认')
    await staffTeamDelete({ id: row.id })
    ElMessage.success('删除成功')
    fetchList()
}

onMounted(() => {
    fetchStaffOptions()
    fetchList()
})
</script>
