<template>
    <admin-page-shell
        class="crm-advisor-lists"
        title="销售顾问"
        description="维护顾问信息、客户负载、状态切换和联系入口。"
    >
        <template #search>
            <search-panel>
                <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                    <el-form-item class="w-[320px]" label="关键词">
                        <el-input
                            v-model="queryParams.keyword"
                            placeholder="顾问姓名/手机号/微信/企微成员ID"
                            clearable
                            @keyup.enter="resetPage"
                        />
                    </el-form-item>
                    <el-form-item class="w-[200px]" label="状态">
                        <el-select v-model="queryParams.status" placeholder="全部状态" clearable>
                            <el-option label="全部" value="" />
                            <el-option
                                v-for="item in statusOptions"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="resetPage">查询</el-button>
                        <el-button @click="resetParams">重置</el-button>
                    </el-form-item>
                </el-form>
            </search-panel>
        </template>

        <div class="admin-page-section">
            <div class="mb-4">
                <el-button v-perms="['crm.salesAdvisor/add']" type="primary" @click="handleAdd">
                    <template #icon>
                        <icon name="el-icon-Plus" />
                    </template>
                    新增顾问
                </el-button>
            </div>

            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="顾问" min-width="220" fixed="left">
                    <template #default="{ row }">
                        <div class="crm-advisor-lists__advisor">
                            <el-avatar :size="44" :src="row.avatar">
                                {{ String(row.advisor_name || '-').slice(0, 1) }}
                            </el-avatar>
                            <div class="crm-advisor-lists__stack">
                                <span class="font-medium">{{ row.advisor_name || '-' }}</span>
                                <span class="text-xs text-tx-secondary">手机号：{{ row.mobile || '-' }}</span>
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="关联管理员" min-width="130">
                    <template #default="{ row }">
                        {{ row.admin?.name || row.admin?.account || row.admin_id || '-' }}
                    </template>
                </el-table-column>
                <el-table-column label="联系方式" min-width="260" show-overflow-tooltip>
                    <template #default="{ row }">
                        <div class="crm-advisor-lists__stack">
                            <span>微信：{{ row.wechat || '-' }}</span>
                            <span>企微成员：{{ row.wecom_userid || '-' }}</span>
                            <span>
                                联系链接：
                                <el-link
                                    v-if="row.contact_link"
                                    type="primary"
                                    :href="row.contact_link"
                                    target="_blank"
                                >
                                    打开
                                </el-link>
                                <template v-else>-</template>
                            </span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="负责区域" min-width="180">
                    <template #default="{ row }">
                        <div v-if="normalizeTagList(row.areas).length" class="crm-advisor-lists__tags">
                            <el-tag
                                v-for="item in normalizeTagList(row.areas)"
                                :key="item"
                                type="info"
                                effect="plain"
                            >
                                {{ item }}
                            </el-tag>
                        </div>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="擅长服务" min-width="180">
                    <template #default="{ row }">
                        <div v-if="normalizeTagList(row.specialties).length" class="crm-advisor-lists__tags">
                            <el-tag
                                v-for="item in normalizeTagList(row.specialties)"
                                :key="item"
                                effect="plain"
                            >
                                {{ item }}
                            </el-tag>
                        </div>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="客户负载" width="150" align="center">
                    <template #default="{ row }">
                        <div class="crm-advisor-lists__load">
                            <el-tag :type="getLoadTagType(row)" effect="plain">
                                {{ row.load_text || `${Number(row.current_customer_count || 0)}/${Number(row.max_customer_count || 0)}` }}
                            </el-tag>
                            <span class="text-xs text-tx-secondary">活跃：{{ row.active_customer_count || 0 }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="150" align="center">
                    <template #default="{ row }">
                        <div class="crm-advisor-lists__status">
                            <el-tag :type="getStatusType(row.status)">
                                {{ getStatusLabel(row.status, row.status_desc) }}
                            </el-tag>
                            <el-select
                                v-perms="['crm.salesAdvisor/changeStatus']"
                                v-model="row.status"
                                size="small"
                                :disabled="statusSavingId === Number(row.id)"
                                @change="handleChangeStatus(row)"
                            >
                                <el-option
                                    v-for="item in statusOptions"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value"
                                />
                            </el-select>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="排序" prop="sort" width="80" align="center" />
                <el-table-column label="更新时间" prop="update_time" min-width="170" />
                <el-table-column label="操作" width="190" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['crm.salesAdvisor/edit']"
                            type="primary"
                            link
                            @click="handleEdit(row)"
                        >
                            编辑
                        </el-button>
                        <el-button
                            v-perms="['crm.salesAdvisor/syncCustomerCount']"
                            type="primary"
                            link
                            :loading="syncingId === Number(row.id)"
                            @click="handleSyncCustomerCount(row)"
                        >
                            校准
                        </el-button>
                        <el-button
                            v-perms="['crm.salesAdvisor/delete']"
                            type="danger"
                            link
                            @click="handleDelete(row)"
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

        <el-dialog
            v-model="showEditDialog"
            :title="editForm.id ? '编辑顾问' : '新增顾问'"
            width="820px"
            @closed="handleDialogClosed"
        >
            <el-form ref="editFormRef" :model="editForm" :rules="editRules" label-width="120px">
                <div class="crm-advisor-lists__form-grid">
                    <el-form-item label="关联管理员" prop="admin_id">
                        <el-select
                            v-model="editForm.admin_id"
                            filterable
                            clearable
                            :loading="adminOptionsLoading"
                            placeholder="请选择关联管理员"
                            class="w-full"
                        >
                            <el-option label="不关联管理员" :value="0" />
                            <el-option
                                v-for="item in adminOptions"
                                :key="item.id"
                                :label="getAdminOptionLabel(item)"
                                :value="item.id"
                            >
                                <div class="crm-advisor-lists__admin-option">
                                    <span>{{ item.name || item.account || `管理员 ${item.id}` }}</span>
                                    <span class="text-xs text-tx-secondary">
                                        {{ item.account ? `账号：${item.account}` : '' }} ID：{{ item.id }}
                                    </span>
                                </div>
                            </el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item label="顾问姓名" prop="advisor_name">
                        <el-input v-model="editForm.advisor_name" placeholder="请输入顾问姓名" maxlength="50" />
                    </el-form-item>
                    <el-form-item label="手机号" prop="mobile">
                        <el-input v-model="editForm.mobile" placeholder="请输入手机号" maxlength="20" />
                    </el-form-item>
                    <el-form-item label="微信号" prop="wechat">
                        <el-input v-model="editForm.wechat" placeholder="请输入微信号" maxlength="50" />
                    </el-form-item>
                    <el-form-item label="企微成员ID" prop="wecom_userid">
                        <el-input v-model="editForm.wecom_userid" placeholder="请输入企业微信成员ID" maxlength="64" />
                    </el-form-item>
                    <el-form-item label="邮箱" prop="email">
                        <el-input v-model="editForm.email" placeholder="请输入邮箱" maxlength="100" />
                    </el-form-item>
                    <el-form-item label="最大客户数" prop="max_customer_count">
                        <el-input-number
                            v-model="editForm.max_customer_count"
                            :min="1"
                            :max="999999"
                            controls-position="right"
                            class="w-full"
                        />
                    </el-form-item>
                    <el-form-item label="排序" prop="sort">
                        <el-input-number
                            v-model="editForm.sort"
                            :min="0"
                            :max="999999"
                            controls-position="right"
                            class="w-full"
                        />
                    </el-form-item>
                    <el-form-item label="当前客户数">
                        <el-input :model-value="editForm.current_customer_count" disabled />
                    </el-form-item>
                    <el-form-item label="状态" prop="status">
                        <el-radio-group v-model="editForm.status">
                            <el-radio
                                v-for="item in statusOptions"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </el-radio>
                        </el-radio-group>
                    </el-form-item>
                </div>

                <el-form-item label="头像" prop="avatar">
                    <material-picker v-model="editForm.avatar" :limit="1" />
                </el-form-item>
                <el-form-item label="联系二维码" prop="contact_qr_code">
                    <material-picker v-model="editForm.contact_qr_code" :limit="1" />
                </el-form-item>
                <el-form-item label="联系链接" prop="contact_link">
                    <el-input v-model="editForm.contact_link" placeholder="请输入联系链接" maxlength="255" />
                </el-form-item>
                <el-form-item label="负责区域" prop="areas">
                    <el-select
                        v-model="editForm.areas"
                        multiple
                        filterable
                        allow-create
                        default-first-option
                        placeholder="输入后回车添加区域标签"
                        class="w-full"
                    >
                        <el-option
                            v-for="item in areaOptions"
                            :key="item"
                            :label="item"
                            :value="item"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="擅长服务" prop="specialties">
                    <el-select
                        v-model="editForm.specialties"
                        multiple
                        filterable
                        allow-create
                        default-first-option
                        placeholder="输入后回车添加服务标签"
                        class="w-full"
                    >
                        <el-option
                            v-for="item in specialtyOptions"
                            :key="item"
                            :label="item"
                            :value="item"
                        />
                    </el-select>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showEditDialog = false">取消</el-button>
                <el-button type="primary" :loading="submitting" @click="handleSave">保存</el-button>
            </template>
        </el-dialog>
    </admin-page-shell>
</template>

<script lang="ts" setup name="crmAdvisorLists">
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'

import {
    advisorAdd,
    advisorChangeStatus,
    advisorDelete,
    advisorDetail,
    advisorEdit,
    advisorLists,
    advisorStatusOptions,
    advisorSyncCustomerCount
} from '@/api/crm/advisor'
import { adminAll } from '@/api/perms/admin'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

interface AdminOption {
    id: number
    name: string
    account: string
    disable: number
}

interface StatusOption {
    value: number
    label: string
}

const defaultStatusOptions: StatusOption[] = [
    { value: 1, label: '正常' },
    { value: 2, label: '休假' },
    { value: 0, label: '离职' }
]

const queryParams = reactive({
    keyword: '',
    status: '' as '' | number
})

const statusOptions = ref<StatusOption[]>(defaultStatusOptions)
const showEditDialog = ref(false)
const submitting = ref(false)
const statusSavingId = ref(0)
const syncingId = ref(0)
const adminOptionsLoading = ref(false)
const adminOptions = ref<AdminOption[]>([])
const editFormRef = shallowRef<FormInstance>()

const createDefaultForm = () => ({
    id: '',
    admin_id: 0,
    advisor_name: '',
    avatar: '',
    mobile: '',
    wechat: '',
    wecom_userid: '',
    contact_qr_code: '',
    contact_link: '',
    email: '',
    areas: [] as string[],
    specialties: [] as string[],
    max_customer_count: 100,
    current_customer_count: 0,
    status: 1,
    sort: 0
})

const editForm = reactive(createDefaultForm())

const editRules = reactive<FormRules>({
    advisor_name: [{ required: true, message: '请输入顾问姓名', trigger: 'blur' }],
    max_customer_count: [{ required: true, message: '请输入最大客户数', trigger: 'blur' }],
    status: [{ required: true, message: '请选择状态', trigger: 'change' }]
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: advisorLists,
    params: queryParams
})

const normalizeTagList = (value: any): string[] => {
    if (Array.isArray(value)) {
        return value.map((item) => String(item || '').trim()).filter(Boolean)
    }
    if (typeof value !== 'string' || value === '') {
        return []
    }

    try {
        const decoded = JSON.parse(value)
        if (Array.isArray(decoded)) {
            return decoded.map((item) => String(item || '').trim()).filter(Boolean)
        }
    } catch {}

    return value
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean)
}

const collectOptions = (field: 'areas' | 'specialties', extra: string[] = []) => {
    const result = new Set<string>()
    pager.lists.forEach((item: any) => {
        normalizeTagList(item[field]).forEach((tag) => result.add(tag))
    })
    extra.forEach((tag) => {
        const value = String(tag || '').trim()
        if (value) {
            result.add(value)
        }
    })
    return Array.from(result)
}

const areaOptions = computed(() => collectOptions('areas', editForm.areas))
const specialtyOptions = computed(() => collectOptions('specialties', editForm.specialties))

const getStatusType = (status: number): 'success' | 'warning' | 'info' => {
    const map: Record<number, 'success' | 'warning' | 'info'> = {
        1: 'success',
        2: 'warning',
        0: 'info'
    }
    return map[Number(status)] || 'info'
}

const getStatusLabel = (status: number, statusDesc = '') => {
    return statusOptions.value.find((item) => item.value === Number(status))?.label || statusDesc || '未知'
}

const getAdminOptionLabel = (item: AdminOption) => {
    const name = item.name || item.account || `管理员 ${item.id}`
    return `${name}（${item.account || '无账号'} / ID:${item.id}）`
}

const getLoadTagType = (row: any): 'success' | 'warning' | 'danger' => {
    const current = Number(row.current_customer_count || 0)
    const max = Math.max(1, Number(row.max_customer_count || 0))
    if (current >= max) {
        return 'danger'
    }
    if (current / max >= 0.8) {
        return 'warning'
    }
    return 'success'
}

const resetEditForm = () => {
    Object.assign(editForm, createDefaultForm())
}

const fillEditForm = (data: any = {}) => {
    Object.assign(editForm, {
        id: data.id ? String(data.id) : '',
        admin_id: Number(data.admin_id || 0),
        advisor_name: String(data.advisor_name || ''),
        avatar: String(data.avatar || ''),
        mobile: String(data.mobile || ''),
        wechat: String(data.wechat || ''),
        wecom_userid: String(data.wecom_userid || ''),
        contact_qr_code: String(data.contact_qr_code || ''),
        contact_link: String(data.contact_link || ''),
        email: String(data.email || ''),
        areas: normalizeTagList(data.areas),
        specialties: normalizeTagList(data.specialties),
        max_customer_count: Math.max(1, Number(data.max_customer_count || 100)),
        current_customer_count: Number(data.current_customer_count || 0),
        status: Number(data.status ?? 1),
        sort: Number(data.sort || 0)
    })
}

const loadStatusOptions = async () => {
    const data = await advisorStatusOptions()
    if (Array.isArray(data) && data.length) {
        statusOptions.value = data.map((item: any) => ({
            value: Number(item.value),
            label: String(item.label)
        }))
    }
}

const loadAdminOptions = async () => {
    if (adminOptions.value.length) {
        return
    }
    adminOptionsLoading.value = true
    try {
        const data = await adminAll({ disable: 0 })
        adminOptions.value = Array.isArray(data)
            ? data.map((item: any) => ({
                  id: Number(item.id || 0),
                  name: String(item.name || ''),
                  account: String(item.account || ''),
                  disable: Number(item.disable || 0)
              })).filter((item) => item.id > 0)
            : []
    } finally {
        adminOptionsLoading.value = false
    }
}

const ensureSelectedAdminOption = (data: any = {}) => {
    const adminId = Number(data.admin_id || 0)
    if (adminId <= 0 || adminOptions.value.some((item) => item.id === adminId)) {
        return
    }

    const admin = data.admin || {}
    adminOptions.value.unshift({
        id: adminId,
        name: String(admin.name || ''),
        account: String(admin.account || ''),
        disable: Number(admin.disable || 0)
    })
}

const handleAdd = async () => {
    await loadAdminOptions()
    resetEditForm()
    showEditDialog.value = true
}

const handleEdit = async (row: any) => {
    await loadAdminOptions()
    resetEditForm()
    const data = await advisorDetail({ id: row.id })
    ensureSelectedAdminOption(data || row)
    fillEditForm(data || row)
    showEditDialog.value = true
}

const buildPayload = () => ({
    id: Number(editForm.id || 0),
    admin_id: Number(editForm.admin_id || 0),
    advisor_name: editForm.advisor_name.trim(),
    avatar: editForm.avatar.trim(),
    mobile: editForm.mobile.trim(),
    wechat: editForm.wechat.trim(),
    wecom_userid: editForm.wecom_userid.trim(),
    contact_qr_code: editForm.contact_qr_code.trim(),
    contact_link: editForm.contact_link.trim(),
    email: editForm.email.trim(),
    areas: editForm.areas,
    specialties: editForm.specialties,
    max_customer_count: Number(editForm.max_customer_count || 1),
    status: Number(editForm.status),
    sort: Number(editForm.sort || 0)
})

const handleSave = async () => {
    await editFormRef.value?.validate()
    submitting.value = true
    try {
        const payload = buildPayload()
        if (editForm.id) {
            await advisorEdit(payload)
            ElMessage.success('顾问已更新')
        } else {
            await advisorAdd(payload)
            ElMessage.success('顾问已新增')
        }
        showEditDialog.value = false
        getLists()
    } finally {
        submitting.value = false
    }
}

const handleChangeStatus = async (row: any) => {
    const id = Number(row.id || 0)
    statusSavingId.value = id
    try {
        await advisorChangeStatus({ id, status: Number(row.status) })
        ElMessage.success(`状态已切换为${statusOptions.value.find((item) => item.value === Number(row.status))?.label || '新状态'}`)
        getLists()
    } catch (error) {
        getLists()
    } finally {
        statusSavingId.value = 0
    }
}

const handleSyncCustomerCount = async (row: any) => {
    await feedback.confirm(`确定按当前活跃客户重新校准「${row.advisor_name || '该顾问'}」的客户数？`)
    syncingId.value = Number(row.id || 0)
    try {
        await advisorSyncCustomerCount({ id: row.id })
        ElMessage.success('客户数已校准')
        getLists()
    } finally {
        syncingId.value = 0
    }
}

const handleDelete = async (row: any) => {
    await feedback.confirm(`确定要删除顾问「${row.advisor_name || ''}」？`)
    await advisorDelete({ id: row.id })
    ElMessage.success('顾问已删除')
    getLists()
}

const handleDialogClosed = () => {
    editFormRef.value?.clearValidate()
}

onActivated(() => {
    loadStatusOptions()
    loadAdminOptions()
    getLists()
})

loadStatusOptions()
loadAdminOptions()
getLists()
</script>

<style lang="scss" scoped>
.crm-advisor-lists {
    &__advisor {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    &__stack {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 0;
        line-height: 1.45;
    }

    &__tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    &__load {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
    }

    &__status {
        display: flex;
        flex-direction: column;
        gap: 8px;
        align-items: center;
    }

    &__admin-option {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        width: 100%;
    }

    &__form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 20px;
    }
}

@media (max-width: 768px) {
    .crm-advisor-lists {
        &__form-grid {
            grid-template-columns: 1fr;
        }
    }
}
</style>
