<template>
    <admin-page-shell
        class="crm-advisor-lists"
        title="销售顾问"
        description="维护顾问信息、客户负载、业绩转化、状态切换与客户交接。"
    >
        <template #search>
            <search-panel>
                <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                    <el-form-item class="w-[320px]" label="关键词">
                        <el-input
                            v-model="queryParams.keyword"
                            placeholder="顾问姓名/手机号/邮箱"
                            clearable
                            @keyup.enter="resetPage"
                        />
                    </el-form-item>
                    <el-form-item class="w-[200px]" label="状态">
                        <el-select v-model="queryParams.status" placeholder="全部状态" clearable>
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
            <div class="mb-4 flex items-center gap-3 flex-wrap">
                <el-button v-perms="['crm.salesAdvisor/add']" type="primary" @click="handleAdd">
                    <template #icon>
                        <icon name="el-icon-Plus" />
                    </template>
                    新增顾问
                </el-button>
                <el-button
                    v-perms="['crm.salesAdvisor/syncAllCustomerCount', 'crm.salesAdvisor/syncCustomerCount']"
                    :loading="syncingAll"
                    @click="handleSyncAll"
                >
                    一键全量校准负载与业绩
                </el-button>
            </div>

            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="顾问" min-width="230" fixed="left">
                    <template #default="{ row }">
                        <div class="crm-advisor-lists__advisor">
                            <el-avatar :size="44" :src="row.avatar">
                                {{ String(row.advisor_name || '-').slice(0, 1) }}
                            </el-avatar>
                            <div class="crm-advisor-lists__stack">
                                <span class="font-medium">{{ row.advisor_name || '-' }}</span>
                                <span class="text-xs text-tx-secondary">手机：{{ row.mobile || '-' }}</span>
                                <span v-if="row.email" class="text-xs text-tx-secondary">邮箱：{{ row.email }}</span>
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="关联管理员" min-width="135">
                    <template #default="{ row }">
                        {{ row.admin?.name || row.admin?.account || (row.admin_id ? `#${row.admin_id}` : '未绑定') }}
                    </template>
                </el-table-column>
                <el-table-column label="负责区域" min-width="150">
                    <template #default="{ row }">
                        <div v-if="normalizeTagList(row.areas).length" class="crm-advisor-lists__tags">
                            <el-tag
                                v-for="item in normalizeTagList(row.areas)"
                                :key="item"
                                type="info"
                                effect="plain"
                                size="small"
                            >
                                {{ item }}
                            </el-tag>
                        </div>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="擅长服务" min-width="160">
                    <template #default="{ row }">
                        <div v-if="normalizeTagList(row.specialties).length" class="crm-advisor-lists__tags">
                            <el-tag
                                v-for="item in normalizeTagList(row.specialties)"
                                :key="item"
                                effect="plain"
                                size="small"
                            >
                                {{ item }}
                            </el-tag>
                        </div>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="客户负载" min-width="170">
                    <template #default="{ row }">
                        <div class="crm-advisor-lists__load">
                            <div class="crm-advisor-lists__load-top">
                                <el-tag :type="getLoadTagType(row)" size="small" effect="plain">
                                    {{ row.load_text || `${Number(row.current_customer_count || 0)}/${Number(row.max_customer_count || 0)}` }}
                                </el-tag>
                                <el-button
                                    type="primary"
                                    link
                                    size="small"
                                    @click="goAdvisorCustomers(row.id)"
                                >
                                    活跃 {{ row.active_customer_count || 0 }}
                                </el-button>
                            </div>
                            <el-progress
                                :percentage="getLoadPercent(row)"
                                :stroke-width="6"
                                :show-text="false"
                                :status="getLoadProgressStatus(row)"
                                class="w-full"
                            />
                            <span v-if="Number(row.pending_warning_count || 0) > 0" class="text-xs text-danger">
                                待处理预警：{{ row.pending_warning_count }}
                            </span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="业绩与转化" min-width="165">
                    <template #default="{ row }">
                        <div class="crm-advisor-lists__stack">
                            <span>签单：{{ Number(row.signed_customer_count || 0) }}/{{ Number(row.total_customer_count || 0) }}（{{ Number(row.conversion_rate || 0).toFixed(1) }}%）</span>
                            <span class="text-xs text-tx-secondary">
                                订单：{{ Number(row.total_order_count || 0) }}笔 · ¥{{ Number(row.total_order_amount || 0).toFixed(0) }}
                            </span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="140" align="center">
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
                <el-table-column label="排序" prop="sort" width="70" align="center" />
                <el-table-column label="操作" width="230" fixed="right">
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
                            v-if="Number(row.active_customer_count || 0) > 0"
                            v-perms="['crm.salesAdvisor/handover', 'crm.salesAdvisor/changeStatus']"
                            type="warning"
                            link
                            @click="openHandoverDialog(row)"
                        >
                            交接
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

        <!-- 新增 / 编辑顾问弹窗 -->
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

        <!-- 一键客户交接弹窗 -->
        <el-dialog v-model="showHandoverDialog" title="顾问活跃客户交接" width="540px" destroy-on-close>
            <el-form label-width="100px">
                <el-form-item label="转出顾问">
                    <span>{{ handoverForm.from_advisor_name }}（当前活跃客户 {{ handoverForm.active_count }} 名）</span>
                </el-form-item>
                <el-form-item label="交接方式">
                    <el-radio-group v-model="handoverForm.mode">
                        <el-radio value="auto">按当前负载自动均衡分配</el-radio>
                        <el-radio value="target">指定目标顾问全部接收</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item v-if="handoverForm.mode === 'target'" label="目标顾问" required>
                    <el-select v-model="handoverForm.to_advisor_id" placeholder="请选择在岗未满载顾问" filterable class="w-full">
                        <el-option
                            v-for="item in assignableAdvisors"
                            :key="item.id"
                            :disabled="item.id === handoverForm.from_advisor_id"
                            :label="`${item.advisor_name}（${item.load_text}）`"
                            :value="item.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="交接原因">
                    <el-input
                        v-model="handoverForm.reason"
                        maxlength="200"
                        placeholder="如：休假交接 / 离职交接"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showHandoverDialog = false">取消</el-button>
                <el-button type="primary" :loading="handoverSubmitting" @click="handleSubmitHandover">
                    确认交接
                </el-button>
            </template>
        </el-dialog>
    </admin-page-shell>
</template>

<script lang="ts" setup name="crmAdvisorLists">
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage, ElMessageBox } from 'element-plus'

import {
    advisorAdd,
    advisorChangeStatus,
    advisorDelete,
    advisorDetail,
    advisorEdit,
    advisorHandover,
    advisorLists,
    advisorStatusOptions,
    advisorSyncAllCustomerCount,
    advisorSyncCustomerCount
} from '@/api/crm/advisor'
import { customerAdvisorOptions } from '@/api/crm/customer'
import { adminAll } from '@/api/perms/admin'
import { usePaging } from '@/hooks/usePaging'
import { getMenuPath } from '@/router'
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
const assignableAdvisors = ref<any[]>([])
const showEditDialog = ref(false)
const showHandoverDialog = ref(false)
const submitting = ref(false)
const handoverSubmitting = ref(false)
const syncingAll = ref(false)
const statusSavingId = ref(0)
const syncingId = ref(0)
const adminOptionsLoading = ref(false)
const adminOptions = ref<AdminOption[]>([])
const editFormRef = shallowRef<FormInstance>()

const router = useRouter()

const createDefaultForm = () => ({
    id: '',
    admin_id: 0,
    advisor_name: '',
    avatar: '',
    mobile: '',
    email: '',
    areas: [] as string[],
    specialties: [] as string[],
    max_customer_count: 100,
    current_customer_count: 0,
    status: 1,
    sort: 0
})

const editForm = reactive(createDefaultForm())

const handoverForm = reactive({
    from_advisor_id: 0,
    from_advisor_name: '',
    active_count: 0,
    mode: 'auto' as 'auto' | 'target',
    to_advisor_id: undefined as number | undefined,
    reason: ''
})

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

const getLoadPercent = (row: any) => {
    const current = Number(row.current_customer_count || 0)
    const max = Math.max(1, Number(row.max_customer_count || 0))
    return Math.min(100, Math.round((current / max) * 100))
}

const getLoadTagType = (row: any): 'success' | 'warning' | 'danger' => {
    const pct = getLoadPercent(row)
    if (pct >= 100) return 'danger'
    if (pct >= 80) return 'warning'
    return 'success'
}

const getLoadProgressStatus = (row: any): '' | 'warning' | 'exception' => {
    const pct = getLoadPercent(row)
    if (pct >= 100) return 'exception'
    if (pct >= 80) return 'warning'
    return ''
}

const goAdvisorCustomers = (advisorId: number) => {
    router.push({
        path: getMenuPath('page.crm.customer') || '/crm/customer',
        query: { advisor_id: advisorId }
    })
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
    if (adminOptions.value.length) return
    adminOptionsLoading.value = true
    try {
        const data = await adminAll({ disable: 0 })
        adminOptions.value = Array.isArray(data)
            ? data
                  .map((item: any) => ({
                      id: Number(item.id || 0),
                      name: String(item.name || ''),
                      account: String(item.account || ''),
                      disable: Number(item.disable || 0)
                  }))
                  .filter((item) => item.id > 0)
            : []
    } finally {
        adminOptionsLoading.value = false
    }
}

const ensureSelectedAdminOption = (data: any = {}) => {
    const adminId = Number(data.admin_id || 0)
    if (adminId <= 0 || adminOptions.value.some((item) => item.id === adminId)) return
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

const openHandoverDialog = async (row: any) => {
    const data = await customerAdvisorOptions({ mode: 'assignable' })
    assignableAdvisors.value = Array.isArray(data) ? data : []
    Object.assign(handoverForm, {
        from_advisor_id: Number(row.id || 0),
        from_advisor_name: String(row.advisor_name || ''),
        active_count: Number(row.active_customer_count || 0),
        mode: 'auto',
        to_advisor_id: undefined,
        reason: Number(row.status) === 0 ? '顾问离职客户交接' : '顾问休假客户交接'
    })
    showHandoverDialog.value = true
}

const handleSubmitHandover = async () => {
    if (handoverForm.mode === 'target' && !handoverForm.to_advisor_id) {
        ElMessage.warning('请选择接收客户的目标顾问')
        return
    }
    handoverSubmitting.value = true
    try {
        const res = await advisorHandover({
            from_advisor_id: handoverForm.from_advisor_id,
            mode: handoverForm.mode,
            to_advisor_id: handoverForm.mode === 'target' ? Number(handoverForm.to_advisor_id) : 0,
            reason: handoverForm.reason.trim()
        })
        ElMessage.success(`交接完成，已转移 ${Number(res?.transferred || 0)} 名活跃客户`)
        showHandoverDialog.value = false
        getLists()
    } finally {
        handoverSubmitting.value = false
    }
}

const handleChangeStatus = async (row: any) => {
    const id = Number(row.id || 0)
    const newStatus = Number(row.status)
    statusSavingId.value = id
    try {
        await advisorChangeStatus({ id, status: newStatus })
        ElMessage.success(
            `状态已切换为${statusOptions.value.find((item) => item.value === newStatus)?.label || '新状态'}`
        )
        await getLists()

        if ((newStatus === 0 || newStatus === 2) && Number(row.active_customer_count || 0) > 0) {
            try {
                await ElMessageBox.confirm(
                    `顾问「${row.advisor_name}」名下仍有 ${row.active_customer_count} 名活跃客户，是否立即一键交接给其他顾问？`,
                    '客户交接提示',
                    {
                        confirmButtonText: '立即交接',
                        cancelButtonText: '稍后处理',
                        type: 'warning'
                    }
                )
                await openHandoverDialog(row)
            } catch {}
        }
    } catch {
        getLists()
    } finally {
        statusSavingId.value = 0
    }
}

const handleSyncCustomerCount = async (row: any) => {
    await feedback.confirm(`确定按当前活跃客户重新校准「${row.advisor_name || '该顾问'}」的负载与业绩？`)
    syncingId.value = Number(row.id || 0)
    try {
        await advisorSyncCustomerCount({ id: row.id })
        ElMessage.success('客户数与业绩已校准')
        getLists()
    } finally {
        syncingId.value = 0
    }
}

const handleSyncAll = async () => {
    await feedback.confirm('确定按当前最新客户和真实订单数据，一键校准全部顾问的负载与转化率？')
    syncingAll.value = true
    try {
        const res = await advisorSyncAllCustomerCount()
        ElMessage.success(`已全量校准 ${Number(res?.synced || 0)} 名顾问`)
        getLists()
    } finally {
        syncingAll.value = false
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
    void loadStatusOptions()
    void loadAdminOptions()
    void getLists()
})

void loadStatusOptions()
void loadAdminOptions()
void getLists()
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
        gap: 5px;
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
        gap: 6px;
    }

    &__load-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
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
