<template>
    <admin-page-shell class="staff-certificate" title="人员证书">
        <template #search>
            <search-panel>
                <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                    <el-form-item class="w-[220px]" label="工作人员">
                        <el-select
                            v-model="queryParams.staff_id"
                            placeholder="选择人员"
                            clearable
                            filterable
                        >
                            <el-option
                                v-for="item in staffOptions"
                                :key="item.id"
                                :label="item.name"
                                :value="item.id"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[220px]" label="证书名称">
                        <el-input
                            v-model="queryParams.name"
                            placeholder="输入证书名称"
                            clearable
                            @keyup.enter="resetPage"
                        />
                    </el-form-item>
                    <el-form-item class="w-[220px]" label="证书编号">
                        <el-input
                            v-model="queryParams.sn"
                            placeholder="输入证书编号"
                            clearable
                            @keyup.enter="resetPage"
                        />
                    </el-form-item>
                    <el-form-item class="w-[180px]" label="审核状态">
                        <el-select v-model="queryParams.verify_status" placeholder="选择状态" clearable>
                            <el-option label="全部" value="" />
                            <el-option label="待审核" :value="0" />
                            <el-option label="已通过" :value="1" />
                            <el-option label="已拒绝" :value="2" />
                        </el-select>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="resetPage">查询</el-button>
                        <el-button @click="handleResetParams">重置</el-button>
                    </el-form-item>
                </el-form>
            </search-panel>
        </template>

        <div class="admin-page-section">
            <div class="mb-4">
                <el-button v-perms="['ops.staffCertificate/add']" type="primary" @click="openForm()">
                    <template #icon>
                        <icon name="el-icon-Plus" />
                    </template>
                    新增证书
                </el-button>
            </div>

            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" width="80" />
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
                <el-table-column label="证书名称" prop="name" min-width="160" />
                <el-table-column label="工作人员" min-width="160">
                    <template #default="{ row }">
                        <div class="flex flex-col">
                            <span>{{ row.staff?.name || '-' }}</span>
                            <span class="text-xs text-gray-400">{{ row.staff?.sn || '' }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="类型" prop="type" width="120" />
                <el-table-column label="证书编号" prop="sn" min-width="160" show-overflow-tooltip />
                <el-table-column label="发证机构" prop="issue_org" min-width="160" show-overflow-tooltip />
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
                        <el-tag :type="getVerifyTagType(row.verify_status)">
                            {{ row.verify_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="260" fixed="right">
                    <template #default="{ row }">
                        <el-button link type="primary" @click="openDetail(row.id)">详情</el-button>
                        <el-button
                            v-perms="['ops.staffCertificate/edit']"
                            link
                            type="primary"
                            @click="openForm(row.id)"
                        >
                            编辑
                        </el-button>
                        <el-button
                            v-if="row.verify_status === 0"
                            v-perms="['ops.staffCertificate/audit']"
                            link
                            type="success"
                            @click="openAudit(row, 1)"
                        >
                            通过
                        </el-button>
                        <el-button
                            v-if="row.verify_status === 0"
                            v-perms="['ops.staffCertificate/audit']"
                            link
                            type="danger"
                            @click="openAudit(row, 2)"
                        >
                            拒绝
                        </el-button>
                        <el-button
                            v-perms="['ops.staffCertificate/delete']"
                            link
                            type="danger"
                            @click="handleDelete(row.id)"
                        >
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </div>

        <el-dialog v-model="detailVisible" title="证书详情" width="760px">
            <el-descriptions v-if="detailData" :column="2" border>
                <el-descriptions-item label="工作人员">
                    {{ detailData.staff?.name || '-' }}
                </el-descriptions-item>
                <el-descriptions-item label="工号">
                    {{ detailData.staff?.sn || '-' }}
                </el-descriptions-item>
                <el-descriptions-item label="证书名称">
                    {{ detailData.name || '-' }}
                </el-descriptions-item>
                <el-descriptions-item label="证书类型">
                    {{ detailData.type || '-' }}
                </el-descriptions-item>
                <el-descriptions-item label="证书编号">
                    {{ detailData.sn || '-' }}
                </el-descriptions-item>
                <el-descriptions-item label="审核状态">
                    <el-tag :type="getVerifyTagType(detailData.verify_status)">
                        {{ detailData.verify_status_desc || '-' }}
                    </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="发证机构">
                    {{ detailData.issue_org || '-' }}
                </el-descriptions-item>
                <el-descriptions-item label="发证日期">
                    {{ detailData.issue_date || '-' }}
                </el-descriptions-item>
                <el-descriptions-item label="有效期">
                    {{ detailData.expire_date || '长期有效' }}
                </el-descriptions-item>
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
                <el-descriptions-item
                    v-if="detailData.reject_reason"
                    label="拒绝原因"
                    :span="2"
                >
                    {{ detailData.reject_reason }}
                </el-descriptions-item>
            </el-descriptions>
        </el-dialog>

        <el-dialog v-model="formVisible" :title="formMode === 'add' ? '新增证书' : '编辑证书'" width="720px">
            <el-form ref="formRef" :model="formData" :rules="formRules" label-width="100px">
                <el-form-item label="工作人员" prop="staff_id">
                    <el-select
                        v-model="formData.staff_id"
                        placeholder="选择工作人员"
                        filterable
                        clearable
                        :disabled="formMode === 'edit'"
                    >
                        <el-option
                            v-for="item in staffOptions"
                            :key="item.id"
                            :label="item.name"
                            :value="item.id"
                        />
                    </el-select>
                </el-form-item>
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
                    <el-date-picker
                        v-model="formData.issue_date"
                        type="date"
                        value-format="YYYY-MM-DD"
                        placeholder="选择发证日期"
                    />
                </el-form-item>
                <el-form-item label="有效期至" prop="expire_date">
                    <el-date-picker
                        v-model="formData.expire_date"
                        type="date"
                        value-format="YYYY-MM-DD"
                        placeholder="选择有效期"
                    />
                </el-form-item>
                <el-form-item label="证书图片" prop="image">
                    <material-picker v-model="formData.image" type="image" :limit="1" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="formVisible = false">取消</el-button>
                <el-button type="primary" :loading="formLoading" @click="handleSubmitForm">
                    保存
                </el-button>
            </template>
        </el-dialog>

        <el-dialog v-model="auditVisible" :title="auditForm.verify_status === 1 ? '通过证书' : '拒绝证书'" width="520px">
            <el-form label-width="100px">
                <el-form-item label="证书名称">
                    <span>{{ auditForm.name || '-' }}</span>
                </el-form-item>
                <el-form-item v-if="auditForm.verify_status === 2" label="拒绝原因" required>
                    <el-input
                        v-model="auditForm.reject_reason"
                        type="textarea"
                        :rows="4"
                        maxlength="255"
                        show-word-limit
                        placeholder="请输入拒绝原因"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="auditVisible = false">取消</el-button>
                <el-button type="primary" :loading="auditLoading" @click="handleAuditSubmit">
                    确认
                </el-button>
            </template>
        </el-dialog>
    </admin-page-shell>
</template>

<script lang="ts" setup name="staffCertificateLists">
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import { useRoute } from 'vue-router'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'
import {
    staffAll,
    staffCertificateAdd,
    staffCertificateAudit,
    staffCertificateDelete,
    staffCertificateDetail,
    staffCertificateEdit,
    staffCertificateLists,
} from '@/api/staff'

const route = useRoute()
const formRef = ref<FormInstance>()

const queryParams = reactive({
    staff_id: '' as number | string,
    name: '',
    sn: '',
    verify_status: '',
})

const staffOptions = ref<any[]>([])
const detailVisible = ref(false)
const detailData = ref<any>(null)
const formVisible = ref(false)
const formLoading = ref(false)
const formMode = ref<'add' | 'edit'>('add')
const auditVisible = ref(false)
const auditLoading = ref(false)
const formData = reactive({
    id: 0,
    staff_id: '' as number | string,
    name: '',
    type: '',
    sn: '',
    image: '',
    issue_org: '',
    issue_date: '',
    expire_date: '',
})
const auditForm = reactive({
    id: 0,
    name: '',
    verify_status: 1,
    reject_reason: '',
})

const formRules: FormRules = {
    staff_id: [{ required: true, message: '请选择工作人员', trigger: 'change' }],
    name: [{ required: true, message: '请输入证书名称', trigger: 'blur' }],
}

const { pager, getLists, resetPage } = usePaging({
    fetchFun: staffCertificateLists,
    params: queryParams,
})

const syncRouteStaffId = () => {
    const staffId = Number(route.query.staff_id || 0)
    queryParams.staff_id = staffId > 0 ? staffId : ''
}

const handleResetParams = () => {
    syncRouteStaffId()
    queryParams.name = ''
    queryParams.sn = ''
    queryParams.verify_status = ''
    resetPage()
}

const resetFormData = () => {
    formData.id = 0
    formData.staff_id = queryParams.staff_id || ''
    formData.name = ''
    formData.type = ''
    formData.sn = ''
    formData.image = ''
    formData.issue_org = ''
    formData.issue_date = ''
    formData.expire_date = ''
}

const getVerifyTagType = (status: number) => {
    const map = {
        0: 'warning',
        1: 'success',
        2: 'danger',
    } as const
    return map[status as keyof typeof map] ?? 'info'
}

const fetchStaffOptions = async () => {
    try {
        staffOptions.value = await staffAll()
    } catch (error) {
        staffOptions.value = []
    }
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
        formData.id = detail.id
        formData.staff_id = detail.staff_id
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
    formLoading.value = true
    try {
        const payload = {
            id: formData.id,
            staff_id: Number(formData.staff_id || 0),
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

        ElMessage.success(formMode.value === 'add' ? '新增成功' : '编辑成功')
        formVisible.value = false
        getLists()
    } finally {
        formLoading.value = false
    }
}

const openAudit = (row: any, status: number) => {
    auditForm.id = row.id
    auditForm.name = row.name || ''
    auditForm.verify_status = status
    auditForm.reject_reason = ''
    auditVisible.value = true
}

const handleAuditSubmit = async () => {
    if (auditForm.verify_status === 2 && !auditForm.reject_reason.trim()) {
        ElMessage.warning('请输入拒绝原因')
        return
    }

    auditLoading.value = true
    try {
        await staffCertificateAudit({
            id: auditForm.id,
            verify_status: auditForm.verify_status,
            reject_reason: auditForm.reject_reason.trim(),
        })
        ElMessage.success('审核完成')
        auditVisible.value = false
        getLists()
    } finally {
        auditLoading.value = false
    }
}

const handleDelete = async (id: number) => {
    await feedback.confirm('确定要删除该证书吗？')
    await staffCertificateDelete({ id })
    ElMessage.success('删除成功')
    getLists()
}

syncRouteStaffId()
fetchStaffOptions()
getLists()

watch(
    () => route.query.staff_id,
    () => {
        syncRouteStaffId()
        resetPage()
    }
)

watch(formVisible, (visible) => {
    if (!visible) {
        nextTick(() => {
            formRef.value?.clearValidate()
        })
    }
})

onActivated(() => {
    syncRouteStaffId()
    getLists()
})
</script>
