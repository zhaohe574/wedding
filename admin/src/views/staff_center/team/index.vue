<template>
    <admin-page-shell class="team-member-page" title="队员管理">
        <template #extra>
            <el-tag effect="plain" type="info">{{ summary.team?.team_name || '我的团队' }}</el-tag>
            <el-tag effect="plain">队员 {{ summary.member_count || 0 }} 人</el-tag>
        </template>

        <template #search>
            <search-panel>
                <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                    <el-form-item class="w-[240px]" label="队员">
                        <el-input
                            v-model="queryParams.name"
                            placeholder="姓名、工号、手机号"
                            clearable
                            @keyup.enter="resetPage"
                        />
                    </el-form-item>
                    <el-form-item class="w-[160px]" label="状态">
                        <el-select v-model="queryParams.status" placeholder="全部" clearable>
                            <el-option label="全部" value="" />
                            <el-option label="启用" :value="1" />
                            <el-option label="禁用" :value="0" />
                        </el-select>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="resetPage">查询</el-button>
                        <el-button @click="resetSearch">重置</el-button>
                    </el-form-item>
                </el-form>
            </search-panel>
        </template>

        <div class="admin-page-section">
            <admin-table-toolbar :hint="`共 ${total} 位队员`" />

            <el-table :data="tableData" v-loading="loading">
                <el-table-column label="队员" min-width="200">
                    <template #default="{ row }">
                        <div class="staff-cell">
                            <el-avatar :src="row.avatar" :size="34">
                                {{ String(row.name || '?').slice(0, 1) }}
                            </el-avatar>
                            <div class="staff-cell__main">
                                <div class="cell-main">{{ row.name || '-' }}</div>
                                <div class="cell-sub">{{ row.sn || '-' }}</div>
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column prop="category_name" label="服务分类" width="130">
                    <template #default="{ row }">{{ row.category_name || '-' }}</template>
                </el-table-column>
                <el-table-column prop="mobile" label="手机号" width="130" />
                <el-table-column prop="rating" label="评分" width="80" align="center">
                    <template #default="{ row }">{{ Number(row.rating || 0) > 0 ? row.rating : '-' }}</template>
                </el-table-column>
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
        </div>

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
                <el-form-item label="列表页简介">
                    <el-input
                        v-model="form.list_intro"
                        type="textarea"
                        :rows="2"
                        maxlength="36"
                        show-word-limit
                        placeholder="用于小程序人员列表卡片展示，建议 15~36 字概括风格与核心亮点"
                    />
                </el-form-item>
                <el-form-item label="个人简介">
                    <el-input v-model="form.profile" type="textarea" :rows="3" maxlength="500" show-word-limit />
                </el-form-item>
                <el-form-item label="服务说明">
                    <el-input v-model="form.service_desc" type="textarea" :rows="3" maxlength="1000" show-word-limit />
                </el-form-item>
                <el-form-item label="详细介绍">
                    <div class="w-full">
                        <long-detail-editor
                            v-if="longDetailMode !== 'text'"
                            v-model="form.long_detail"
                        />
                        <template v-else>
                            <el-alert
                                class="mb-2"
                                type="warning"
                                :closable="false"
                                show-icon
                                title="当前为历史纯文本内容，已保留原文编辑，避免覆盖原有图文结构"
                            />
                            <el-input v-model="form.long_detail" type="textarea" :rows="5" />
                        </template>
                    </div>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="dialogVisible = false">取消</el-button>
                <el-button type="primary" @click="saveMember">保存</el-button>
            </template>
        </el-dialog>
    </admin-page-shell>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { computed, onMounted, reactive, ref } from 'vue'

import { myTeamMemberDetail, myTeamMembers, myTeamMemberUpdate, myTeamSummary } from '@/api/staff-center'
import LongDetailEditor from '@/components/staff/long-detail-editor.vue'

const loading = ref(false)
const tableData = ref<any[]>([])
const total = ref(0)
const summary = ref<any>({})
const dialogVisible = ref(false)

const queryParams = reactive({
    page_no: 1,
    page_size: 15,
    name: '',
    status: '' as string | number,
})

const form = reactive<any>({
    id: 0,
    name: '',
    mobile: '',
    experience_years: 0,
    list_intro: '',
    profile: '',
    service_desc: '',
    long_detail: '',
})

/** 历史队员的长图详情可能是纯文本，非 JSON 数组时不进可视化编辑器，避免结构被覆盖。 */
const longDetailMode = computed<'empty' | 'json' | 'text'>(() => {
    const raw = String(form.long_detail || '').trim()
    if (!raw) {
        return 'empty'
    }
    try {
        const parsed = JSON.parse(raw)
        return Array.isArray(parsed) ? 'json' : 'text'
    } catch {
        return 'text'
    }
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
        list_intro: detail.list_intro || '',
        profile: detail.profile || '',
        service_desc: detail.service_desc || '',
        long_detail: detail.long_detail || '',
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

<style lang="scss" scoped>
.team-member-page {
    .staff-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .staff-cell__main {
        min-width: 0;
    }
}

.cell-main {
    font-size: 13px;
    font-weight: 500;
    line-height: 1.5;
    color: var(--admin-color-title);
}

.cell-sub {
    margin-top: 2px;
    font-size: 12px;
    line-height: 1.5;
    color: var(--admin-color-muted);
}
</style>
