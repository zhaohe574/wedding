<template>
    <admin-page-shell class="staff-center-certificate" title="我的证书">
        <template #search>
            <search-panel>
                <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                    <el-form-item class="w-[220px]" label="证书名称">
                        <el-input v-model="queryParams.name" placeholder="输入证书名称" clearable @keyup.enter="resetPage" />
                    </el-form-item>
                    <el-form-item class="w-[220px]" label="证书编号">
                        <el-input v-model="queryParams.sn" placeholder="输入证书编号" clearable @keyup.enter="resetPage" />
                    </el-form-item>
                    <el-form-item class="w-[180px]" label="审核状态">
                        <el-select v-model="queryParams.verify_status" placeholder="选择状态" clearable>
                            <el-option label="待审核" :value="0" />
                            <el-option label="已通过" :value="1" />
                            <el-option label="已拒绝" :value="2" />
                        </el-select>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="resetPage">查询</el-button>
                        <el-button @click="resetSelfParams">重置</el-button>
                        <el-button type="primary" @click="openForm()">
                            <template #icon>
                                <icon name="el-icon-Plus" />
                            </template>
                            新增证书
                        </el-button>
                    </el-form-item>
                </el-form>
            </search-panel>
        </template>

        <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
            <el-card v-for="card in statusCards" :key="card.key" class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">{{ card.label }}</div>
                    <div class="text-2xl font-bold mt-2" :class="card.className">{{ card.count }}</div>
                </div>
            </el-card>
        </div>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="证书图片" width="100">
                    <template #default="{ row }">
                        <el-image
                            v-if="row.image"
                            :src="row.image"
                            fit="cover"
                            class="w-[64px] h-[48px] rounded"
                            :preview-src-list="[row.image]"
                        />
                        <span v-else class="text-gray-400">未上传</span>
                    </template>
                </el-table-column>
                <el-table-column label="证书名称" prop="name" min-width="160" show-overflow-tooltip />
                <el-table-column label="类型" prop="type" width="120" show-overflow-tooltip />
                <el-table-column label="证书编号" prop="sn" min-width="150" show-overflow-tooltip />
                <el-table-column label="发证机构" prop="issue_org" min-width="150" show-overflow-tooltip />
                <el-table-column label="有效期" min-width="200">
                    <template #default="{ row }">
                        <div class="flex flex-col">
                            <span>{{ row.issue_date || '-' }} 至 {{ row.expire_date || '长期有效' }}</span>
                            <span :class="row.is_expired ? 'text-red-500' : 'text-gray-400'">
                                {{ row.is_expired ? '已过期' : '状态正常' }}
                            </span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="审核状态" width="110">
                    <template #default="{ row }">
                        <el-tag :type="getVerifyTagType(getRowVerifyStatus(row))">
                            {{ row.verify_status_desc || getVerifyStatusText(row) }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="180" fixed="right">
                    <template #default="{ row }">
                        <el-button link type="primary" @click="openDetail(row.id)">详情</el-button>
                        <el-button link type="primary" @click="openForm(row.id)">编辑</el-button>
                        <el-button link type="danger" @click="handleDelete(row.id)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-dialog v-model="detailVisible" title="证书详情" width="760px">
            <el-descriptions v-if="detailData" :column="2" border>
                <el-descriptions-item label="证书名称">{{ detailData.name || '-' }}</el-descriptions-item>
                <el-descriptions-item label="证书类型">{{ detailData.type || '-' }}</el-descriptions-item>
                <el-descriptions-item label="证书编号">{{ detailData.sn || '-' }}</el-descriptions-item>
                <el-descriptions-item label="审核状态">
                    <el-tag :type="getVerifyTagType(getRowVerifyStatus(detailData))">
                        {{ detailData.verify_status_desc || getVerifyStatusText(detailData) }}
                    </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="发证机构">{{ detailData.issue_org || '-' }}</el-descriptions-item>
                <el-descriptions-item label="发证日期">{{ detailData.issue_date || '-' }}</el-descriptions-item>
                <el-descriptions-item label="有效期">{{ detailData.expire_date || '长期有效' }}</el-descriptions-item>
                <el-descriptions-item label="状态说明">
                    <span :class="detailData.is_expired ? 'text-red-500' : 'text-green-600'">
                        {{ detailData.is_expired ? '证书已过期' : '证书有效' }}
                    </span>
                </el-descriptions-item>
                <el-descriptions-item label="证书图片" :span="2">
                    <el-image
                        v-if="detailData.image"
                        :src="detailData.image"
                        fit="cover"
                        class="w-[240px] h-[160px] rounded"
                        :preview-src-list="[detailData.image]"
                    />
                    <span v-else class="text-gray-400">未上传证书图片</span>
                </el-descriptions-item>
                <el-descriptions-item v-if="detailData.reject_reason" label="拒绝原因" :span="2">
                    {{ detailData.reject_reason }}
                </el-descriptions-item>
            </el-descriptions>
        </el-dialog>

        <el-dialog v-model="formVisible" :title="formMode === 'add' ? '新增证书' : '编辑证书'" width="720px" @closed="clearFormValidate">
            <el-form ref="formRef" :model="formData" :rules="formRules" label-width="100px">
                <el-form-item label="证书名称" prop="name">
                    <el-input v-model="formData.name" maxlength="100" placeholder="输入证书名称" />
                </el-form-item>
                <el-form-item label="证书类型" prop="type">
                    <el-input v-model="formData.type" maxlength="50" placeholder="输入证书类型" />
                </el-form-item>
                <el-form-item label="证书编号" prop="sn">
                    <el-input v-model="formData.sn" maxlength="100" placeholder="输入证书编号" />
                </el-form-item>
                <el-form-item label="发证机构" prop="issue_org">
                    <el-input v-model="formData.issue_org" maxlength="100" placeholder="输入发证机构" />
                </el-form-item>
                <el-form-item label="发证日期" prop="issue_date">
                    <el-date-picker v-model="formData.issue_date" type="date" value-format="YYYY-MM-DD" placeholder="选择发证日期" />
                </el-form-item>
                <el-form-item label="有效期至" prop="expire_date">
                    <el-date-picker v-model="formData.expire_date" type="date" value-format="YYYY-MM-DD" placeholder="选择有效期" />
                </el-form-item>
                <el-form-item label="证书图片" prop="image">
                    <material-picker v-model="formData.image" type="image" :limit="1" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="formVisible = false">取消</el-button>
                <el-button type="primary" :loading="formLoading" @click="handleSubmitForm">保存</el-button>
            </template>
        </el-dialog>
    </admin-page-shell>
</template>

<script setup lang="ts" name="staffCenterCertificate">
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import { computed, nextTick, onActivated, reactive, ref } from 'vue'
import { myProfile } from '@/api/staff-center'
import {
    staffCertificateAdd,
    staffCertificateDelete,
    staffCertificateDetail,
    staffCertificateEdit,
    staffCertificateLists,
} from '@/api/staff'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'
import MaterialPicker from '@/components/material/picker.vue'

const formRef = ref<FormInstance>()
const currentStaffId = ref(0)
const detailVisible = ref(false)
const detailData = ref<any>(null)
const formVisible = ref(false)
const formLoading = ref(false)
const formMode = ref<'add' | 'edit'>('add')

const queryParams = reactive({
    staff_id: 0,
    name: '',
    sn: '',
    verify_status: '',
})

const formData = reactive({
    id: 0,
    name: '',
    type: '',
    sn: '',
    image: '',
    issue_org: '',
    issue_date: '',
    expire_date: '',
})

const formRules: FormRules = {
    name: [{ required: true, message: '请输入证书名称', trigger: 'blur' }],
}

const { pager, getLists, resetPage } = usePaging({
    fetchFun: staffCertificateLists,
    params: queryParams,
})

const statusCards = computed(() => {
    const lists = pager.lists || []
    return [
        { key: 'total', label: '当前页证书', count: lists.length, className: 'text-gray-900' },
        { key: 'pending', label: '待审核', count: lists.filter((item) => getRowVerifyStatus(item) === 0).length, className: 'text-orange-500' },
        { key: 'passed', label: '已通过', count: lists.filter((item) => getRowVerifyStatus(item) === 1).length, className: 'text-green-500' },
        { key: 'rejected', label: '已拒绝', count: lists.filter((item) => getRowVerifyStatus(item) === 2).length, className: 'text-red-500' },
    ]
})

const getRowVerifyStatus = (row: Record<string, any> | null | undefined) => {
    return Number(row?.verify_status ?? row?.audit_status ?? -1)
}

const getVerifyTagType = (status: number) => {
    const map = {
        0: 'warning',
        1: 'success',
        2: 'danger',
    } as const
    return map[status as keyof typeof map] ?? 'info'
}

const getVerifyStatusText = (row: Record<string, any> | null | undefined) => {
    const map: Record<number, string> = {
        0: '待审核',
        1: '已通过',
        2: '已拒绝',
    }
    return map[getRowVerifyStatus(row)] || '-'
}

const resetFormData = () => {
    formData.id = 0
    formData.name = ''
    formData.type = ''
    formData.sn = ''
    formData.image = ''
    formData.issue_org = ''
    formData.issue_date = ''
    formData.expire_date = ''
}

const resetSelfParams = () => {
    queryParams.staff_id = currentStaffId.value
    queryParams.name = ''
    queryParams.sn = ''
    queryParams.verify_status = ''
    resetPage()
}

const loadCurrentStaff = async () => {
    const profile = await myProfile()
    currentStaffId.value = Number(profile?.id || profile?.staff_id || 0)
    queryParams.staff_id = currentStaffId.value
}

const openDetail = async (id: number) => {
    detailData.value = await staffCertificateDetail({ id })
    detailVisible.value = true
}

const openForm = async (id = 0) => {
    resetFormData()
    formMode.value = id > 0 ? 'edit' : 'add'

    if (id > 0) {
        const detail = await staffCertificateDetail({ id })
        formData.id = Number(detail.id || 0)
        formData.name = detail.name || ''
        formData.type = detail.type || ''
        formData.sn = detail.sn || ''
        formData.image = detail.image || ''
        formData.issue_org = detail.issue_org || ''
        formData.issue_date = detail.issue_date || ''
        formData.expire_date = detail.expire_date || ''
    }

    formVisible.value = true
}

const handleSubmitForm = async () => {
    await formRef.value?.validate()

    if (!currentStaffId.value) {
        ElMessage.error('当前后台账号未关联服务人员档案')
        return
    }

    formLoading.value = true
    try {
        const payload = {
            id: formData.id,
            staff_id: currentStaffId.value,
            name: formData.name.trim(),
            type: formData.type.trim(),
            sn: formData.sn.trim(),
            image: formData.image,
            issue_org: formData.issue_org.trim(),
            issue_date: formData.issue_date,
            expire_date: formData.expire_date,
        }

        if (formMode.value === 'add') {
            await staffCertificateAdd(payload)
        } else {
            await staffCertificateEdit(payload)
        }

        ElMessage.success(formMode.value === 'add' ? '新增成功，证书已提交审核' : '编辑成功，证书已重新提交审核')
        formVisible.value = false
        getLists()
    } finally {
        formLoading.value = false
    }
}

const handleDelete = async (id: number) => {
    await feedback.confirm('确定要删除该证书吗？')
    await staffCertificateDelete({ id })
    ElMessage.success('删除成功')
    getLists()
}

const clearFormValidate = () => {
    nextTick(() => {
        formRef.value?.clearValidate()
    })
}

const bootstrap = async () => {
    await loadCurrentStaff()
    getLists()
}

onActivated(() => {
    if (currentStaffId.value) {
        getLists()
    }
})

bootstrap()
</script>
