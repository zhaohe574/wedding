<template>
    <div class="couple-questionnaire">
        <el-card class="!border-none" shadow="never">
            <div class="toolbar">
                <div>
                    <h2 class="page-title">新人问卷</h2>
                    <div class="page-desc">查看全部服务人员问卷配置，并代服务人员维护问卷草稿和发布版本。</div>
                </div>
                <div class="flex gap-2">
                    <el-button @click="goTasks()">问卷任务</el-button>
                    <el-button type="primary" @click="goBank">基础题库样例</el-button>
                </div>
            </div>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-form :inline="true" :model="queryParams" class="mb-4">
                <el-form-item label="服务人员">
                    <el-input
                        v-model="queryParams.keyword"
                        clearable
                        placeholder="姓名、工号、手机号或问卷标题"
                        style="width: 260px"
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item label="推送方式">
                    <el-select v-model="queryParams.push_mode" clearable placeholder="全部" style="width: 140px">
                        <el-option label="自动推送" :value="1" />
                        <el-option label="手动触发" :value="2" />
                    </el-select>
                </el-form-item>
                <el-form-item label="启用状态">
                    <el-select v-model="queryParams.status" clearable placeholder="全部" style="width: 140px">
                        <el-option label="启用" :value="1" />
                        <el-option label="停用" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item label="发布状态">
                    <el-select v-model="queryParams.published" clearable placeholder="全部" style="width: 140px">
                        <el-option label="已发布" :value="1" />
                        <el-option label="未发布" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>

            <el-table v-loading="pager.loading" :data="pager.lists" border>
                <el-table-column label="服务人员" min-width="170">
                    <template #default="{ row }">
                        <div class="staff-cell">
                            <span class="staff-cell__name">{{ row.staff_name || '-' }}</span>
                            <span class="staff-cell__meta">{{ row.staff_sn || row.staff_mobile || '-' }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column prop="title" label="问卷标题" min-width="220" show-overflow-tooltip />
                <el-table-column label="推送模式" width="110">
                    <template #default="{ row }">
                        <el-tag :type="row.push_mode === 1 ? 'success' : 'warning'">
                            {{ row.push_mode_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="启用状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.status === 1 ? 'success' : 'info'">{{ row.status_desc }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="发布版本" width="110">
                    <template #default="{ row }">
                        <el-tag v-if="row.published" type="success">v{{ row.published_version_no }}</el-tag>
                        <el-tag v-else type="info">未发布</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="pending_task_count" label="待填写任务" width="110" />
                <el-table-column prop="update_time" label="更新时间" width="170" />
                <el-table-column label="操作" width="190" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="goConfig(row)">配置问卷</el-button>
                        <el-button type="success" link @click="goTasks(row)">查看任务</el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
    </div>
</template>

<script setup lang="ts">
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import { coupleQuestionnaireLists } from '@/api/questionnaire'
import { usePaging } from '@/hooks/usePaging'

const router = useRouter()
const queryParams = reactive({
    keyword: '',
    push_mode: '',
    status: '',
    published: ''
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: coupleQuestionnaireLists,
    params: queryParams,
    firstLoading: true
})

const goConfig = (row: any) => {
    router.push({
        path: '/couple-questionnaire/config',
        query: { staff_id: row.staff_id }
    })
}

const goTasks = (row?: any) => {
    router.push({
        path: '/couple-questionnaire/tasks',
        query: row?.staff_id ? { staff_id: row.staff_id } : {}
    })
}

const goBank = () => {
    router.push('/couple-questionnaire/bank')
}

getLists()
</script>

<style scoped lang="scss">
.toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.page-title {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

.page-desc {
    margin-top: 6px;
    color: var(--el-text-color-secondary);
}

.staff-cell {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.staff-cell__name {
    font-weight: 600;
    color: var(--el-text-color-primary);
}

.staff-cell__meta {
    font-size: 12px;
    color: var(--el-text-color-secondary);
}
</style>
