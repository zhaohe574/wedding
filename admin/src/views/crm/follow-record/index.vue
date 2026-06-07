<template>
    <admin-page-shell
        class="crm-follow-record-lists"
        title="跟进记录"
        description="记录客户沟通内容、意向变化、下次计划和重要跟进事项。"
    >
        <template #search>
            <search-panel>
                <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                    <el-form-item class="w-[280px]" label="关键词">
                        <el-input
                            v-model="queryParams.keyword"
                            placeholder="客户/手机号/跟进内容"
                            clearable
                            @keyup.enter="resetPage"
                        />
                    </el-form-item>
                    <el-form-item class="w-[180px]" label="客户">
                        <el-select v-model="queryParams.customer_id" placeholder="全部客户" clearable filterable>
                            <el-option
                                v-for="item in customerOptions"
                                :key="item.id"
                                :label="formatCustomerOption(item)"
                                :value="item.id"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[160px]" label="方式">
                        <el-select v-model="queryParams.follow_type" placeholder="全部方式" clearable>
                            <el-option
                                v-for="item in optionData.follow_type_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[160px]" label="结果">
                        <el-select v-model="queryParams.follow_result" placeholder="全部结果" clearable>
                            <el-option
                                v-for="item in optionData.follow_result_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="resetPage">查询</el-button>
                        <el-button @click="resetParams">重置</el-button>
                        <el-button v-perms="['crm.followRecord/add']" type="primary" @click="handleAdd()">
                            新增跟进
                        </el-button>
                    </el-form-item>
                </el-form>
            </search-panel>
        </template>

        <div class="admin-page-section">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="客户" min-width="220" fixed="left">
                    <template #default="{ row }">
                        <div class="crm-follow-record-lists__stack">
                            <span class="font-medium">{{ row.customer?.customer_name || '-' }}</span>
                            <span class="text-xs text-tx-secondary">手机号：{{ row.customer?.customer_mobile || '-' }}</span>
                            <span class="text-xs text-tx-secondary">顾问：{{ row.advisor?.advisor_name || '-' }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="跟进内容" min-width="280">
                    <template #default="{ row }">
                        <div class="crm-follow-record-lists__stack">
                            <span>{{ row.follow_content || '-' }}</span>
                            <span class="text-xs text-tx-secondary">
                                下次计划：{{ row.next_follow_content || '-' }}
                            </span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="方式/结果" min-width="150" align="center">
                    <template #default="{ row }">
                        <div class="crm-follow-record-lists__center-stack">
                            <el-tag effect="plain">{{ row.follow_type_desc }}</el-tag>
                            <el-tag :type="getResultType(row.follow_result)">
                                {{ row.follow_result_desc }}
                            </el-tag>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="意向变化" min-width="140" align="center">
                    <template #default="{ row }">
                        <span>{{ row.intention_before || '-' }} -> {{ row.intention_after || '-' }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="时长" width="90" align="center">
                    <template #default="{ row }">{{ Number(row.duration || 0) }} 分钟</template>
                </el-table-column>
                <el-table-column label="重要" width="90" align="center">
                    <template #default="{ row }">
                        <el-tag :type="row.is_important ? 'danger' : 'info'" effect="plain">
                            {{ row.is_important ? '重要' : '普通' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="下次跟进" prop="next_follow_time_text" min-width="170" />
                <el-table-column label="创建时间" prop="create_time_text" min-width="170" />
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </div>

        <el-dialog
            v-model="showAddDialog"
            title="新增跟进记录"
            width="760px"
            destroy-on-close
            @closed="handleAddDialogClosed"
        >
            <el-form ref="addFormRef" :model="addForm" :rules="addRules" label-width="110px">
                <div class="crm-follow-record-lists__form-grid">
                    <el-form-item label="客户" prop="customer_id">
                        <el-select v-model="addForm.customer_id" placeholder="请选择客户" filterable>
                            <el-option
                                v-for="item in customerOptions"
                                :key="item.id"
                                :label="formatCustomerOption(item)"
                                :value="item.id"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="跟进方式" prop="follow_type">
                        <el-select v-model="addForm.follow_type" placeholder="请选择跟进方式">
                            <el-option
                                v-for="item in optionData.follow_type_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="跟进结果" prop="follow_result">
                        <el-select v-model="addForm.follow_result" placeholder="请选择跟进结果">
                            <el-option
                                v-for="item in optionData.follow_result_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="跟进后意向" prop="intention_after">
                        <el-select v-model="addForm.intention_after" placeholder="请选择意向">
                            <el-option
                                v-for="item in optionData.intention_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="沟通时长" prop="duration">
                        <el-input-number v-model="addForm.duration" :min="0" :max="10000" class="w-full" />
                    </el-form-item>
                    <el-form-item label="是否重要" prop="is_important">
                        <el-switch v-model="addForm.is_important" :active-value="1" :inactive-value="0" />
                    </el-form-item>
                    <el-form-item label="下次跟进" prop="next_follow_time" class="crm-follow-record-lists__full">
                        <el-date-picker
                            v-model="addForm.next_follow_time"
                            type="datetime"
                            value-format="YYYY-MM-DD HH:mm:ss"
                            placeholder="请选择下次跟进时间"
                            class="w-full"
                        />
                    </el-form-item>
                    <el-form-item label="跟进内容" prop="follow_content" class="crm-follow-record-lists__full">
                        <el-input
                            v-model="addForm.follow_content"
                            type="textarea"
                            :rows="4"
                            maxlength="2000"
                            show-word-limit
                            placeholder="请输入本次跟进内容"
                        />
                    </el-form-item>
                    <el-form-item label="下次计划" prop="next_follow_content" class="crm-follow-record-lists__full">
                        <el-input
                            v-model="addForm.next_follow_content"
                            maxlength="255"
                            placeholder="请输入下次跟进计划"
                        />
                    </el-form-item>
                </div>
            </el-form>
            <template #footer>
                <el-button @click="showAddDialog = false">取消</el-button>
                <el-button type="primary" :loading="submitting" @click="handleSubmitAdd">
                    保存
                </el-button>
            </template>
        </el-dialog>
    </admin-page-shell>
</template>

<script lang="ts" setup name="crmFollowRecordLists">
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'

import {
    followRecordAdd,
    followRecordCustomerOptions,
    followRecordLists,
    followRecordOptions
} from '@/api/crm/followRecord'
import { usePaging } from '@/hooks/usePaging'

interface OptionItem {
    value: number | string
    label: string
}

interface CustomerOption {
    id: number
    customer_name: string
    customer_mobile: string
    intention_level: string
    intention_level_desc: string
    customer_status: number
    customer_status_desc: string
    advisor_id: number
}

const optionData = reactive({
    follow_type_options: [] as OptionItem[],
    follow_result_options: [] as OptionItem[],
    intention_options: [] as OptionItem[]
})

const queryParams = reactive({
    keyword: '',
    customer_id: '' as '' | number,
    advisor_id: '' as '' | number,
    follow_type: '' as '' | number,
    follow_result: '' as '' | number,
    is_important: '' as '' | number
})

const createDefaultAddForm = () => ({
    customer_id: undefined as number | undefined,
    follow_type: 1,
    follow_content: '',
    follow_result: 1,
    intention_after: 'D',
    duration: 0,
    next_follow_time: '',
    next_follow_content: '',
    attachments: [] as string[],
    is_important: 0
})

const customerOptions = ref<CustomerOption[]>([])
const showAddDialog = ref(false)
const submitting = ref(false)
const addFormRef = shallowRef<FormInstance>()
const addForm = reactive(createDefaultAddForm())

const addRules = reactive<FormRules>({
    customer_id: [{ required: true, message: '请选择客户', trigger: 'change' }],
    follow_type: [{ required: true, message: '请选择跟进方式', trigger: 'change' }],
    follow_result: [{ required: true, message: '请选择跟进结果', trigger: 'change' }],
    intention_after: [{ required: true, message: '请选择跟进后意向', trigger: 'change' }],
    follow_content: [{ required: true, message: '请输入跟进内容', trigger: 'blur' }]
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: followRecordLists,
    params: queryParams
})
const route = useRoute()

const formatCustomerOption = (item: CustomerOption) => {
    const mobile = item.customer_mobile ? ` / ${item.customer_mobile}` : ''
    return `${item.customer_name || '未命名客户'}${mobile}`
}

const getResultType = (result: number): 'success' | 'warning' | 'primary' | 'danger' | 'info' => {
    const map: Record<number, 'success' | 'warning' | 'primary' | 'danger' | 'info'> = {
        1: 'primary',
        2: 'success',
        3: 'warning',
        4: 'success',
        5: 'danger'
    }
    return map[Number(result)] || 'info'
}

const loadOptions = async () => {
    const data = await followRecordOptions()
    optionData.follow_type_options = data?.follow_type_options || []
    optionData.follow_result_options = data?.follow_result_options || []
    optionData.intention_options = data?.intention_options || []
}

const loadCustomerOptions = async () => {
    const data = await followRecordCustomerOptions()
    customerOptions.value = Array.isArray(data)
        ? data.map((item: any) => ({
              id: Number(item.id || 0),
              customer_name: String(item.customer_name || ''),
              customer_mobile: String(item.customer_mobile || ''),
              intention_level: String(item.intention_level || ''),
              intention_level_desc: String(item.intention_level_desc || ''),
              customer_status: Number(item.customer_status || 0),
              customer_status_desc: String(item.customer_status_desc || ''),
              advisor_id: Number(item.advisor_id || 0)
          }))
        : []
}

const resetAddForm = () => {
    Object.assign(addForm, createDefaultAddForm())
}

const handleAdd = async (customer?: any) => {
    resetAddForm()
    await loadCustomerOptions()
    if (customer?.id) {
        addForm.customer_id = Number(customer.id)
        addForm.intention_after = String(customer.intention_level || 'D')
    }
    showAddDialog.value = true
}

const handleSubmitAdd = async () => {
    await addFormRef.value?.validate()
    submitting.value = true
    try {
        await followRecordAdd({
            customer_id: Number(addForm.customer_id || 0),
            follow_type: Number(addForm.follow_type || 1),
            follow_content: addForm.follow_content.trim(),
            follow_result: Number(addForm.follow_result || 1),
            intention_after: addForm.intention_after,
            duration: Number(addForm.duration || 0),
            next_follow_time: addForm.next_follow_time || '',
            next_follow_content: addForm.next_follow_content.trim(),
            attachments: addForm.attachments,
            is_important: Number(addForm.is_important || 0)
        })
        ElMessage.success('跟进记录已新增')
        showAddDialog.value = false
        await loadCustomerOptions()
        getLists()
    } finally {
        submitting.value = false
    }
}

const handleAddDialogClosed = () => {
    addFormRef.value?.clearValidate()
}

onActivated(() => {
    applyRouteCustomer()
    loadOptions()
    loadCustomerOptions()
    getLists()
})

const applyRouteCustomer = () => {
    const customerId = Number(route.query.customer_id || 0)
    if (customerId > 0) {
        queryParams.customer_id = customerId
    }
}

applyRouteCustomer()
loadOptions()
loadCustomerOptions()
getLists()
</script>

<style lang="scss" scoped>
.crm-follow-record-lists {
    &__stack {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 0;
        line-height: 1.45;
    }

    &__center-stack {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        line-height: 1.45;
    }

    &__form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 16px;
    }

    &__full {
        grid-column: 1 / -1;
    }
}

@media (max-width: 900px) {
    .crm-follow-record-lists {
        &__form-grid {
            grid-template-columns: 1fr;
        }

        &__full {
            grid-column: auto;
        }
    }
}
</style>
