<template>
    <div class="crm-customer-lists">
        <el-card class="!border-none" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[300px]" label="关键词">
                    <el-input
                        v-model="queryParams.keyword"
                        placeholder="姓名/手机号/微信/城市/场地"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[170px]" label="状态">
                    <el-select v-model="queryParams.customer_status" placeholder="全部状态" clearable>
                        <el-option label="全部" value="" />
                        <el-option
                            v-for="item in optionData.status_options"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[170px]" label="意向">
                    <el-select v-model="queryParams.intention_level" placeholder="全部意向" clearable>
                        <el-option label="全部" value="" />
                        <el-option
                            v-for="item in optionData.intention_options"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[170px]" label="来源">
                    <el-select v-model="queryParams.source_channel" placeholder="全部来源" clearable>
                        <el-option label="全部" value="" />
                        <el-option
                            v-for="item in optionData.source_options"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[190px]" label="顾问">
                    <el-select v-model="queryParams.advisor_id" placeholder="全部顾问" clearable filterable>
                        <el-option label="未分配" :value="0" />
                        <el-option
                            v-for="item in advisorOptions"
                            :key="item.id"
                            :label="`${item.advisor_name}（${item.load_text}）`"
                            :value="item.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="客户" min-width="230" fixed="left">
                    <template #default="{ row }">
                        <div class="crm-customer-lists__stack">
                            <div class="crm-customer-lists__line">
                                <span class="font-medium">{{ row.customer_name || '-' }}</span>
                                <el-tag size="small" :type="getGenderType(row.gender)" effect="plain">
                                    {{ row.gender_desc || getOptionLabel(optionData.gender_options, row.gender) }}
                                </el-tag>
                            </div>
                            <span class="text-xs text-tx-secondary">手机号：{{ row.customer_mobile || '-' }}</span>
                            <span class="text-xs text-tx-secondary">微信：{{ row.customer_wechat || '-' }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="婚礼信息" min-width="260">
                    <template #default="{ row }">
                        <div class="crm-customer-lists__stack">
                            <span>{{ formatArea(row) }}</span>
                            <span>婚期：{{ row.wedding_date || '-' }}</span>
                            <span class="text-xs text-tx-secondary">场地：{{ row.wedding_venue || '-' }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="意向/来源" min-width="180" align="center">
                    <template #default="{ row }">
                        <div class="crm-customer-lists__center-stack">
                            <el-tag :type="getIntentionType(row.intention_level)" effect="plain">
                                {{ row.intention_level_desc || getOptionLabel(optionData.intention_options, row.intention_level) }}
                            </el-tag>
                            <span class="text-xs text-tx-secondary">
                                {{ row.source_channel_desc || getOptionLabel(optionData.source_options, row.source_channel) }}
                            </span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="当前顾问" min-width="180">
                    <template #default="{ row }">
                        <div v-if="row.advisor" class="crm-customer-lists__stack">
                            <span class="font-medium">{{ row.advisor.advisor_name || '-' }}</span>
                            <span class="text-xs text-tx-secondary">{{ row.advisor.mobile || '-' }}</span>
                        </div>
                        <el-tag v-else type="info" effect="plain">未分配</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="120" align="center">
                    <template #default="{ row }">
                        <el-tag :type="getCustomerStatusType(row.customer_status)">
                            {{ row.customer_status_desc || getOptionLabel(optionData.status_options, row.customer_status) }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="分配时间" prop="assign_time_text" min-width="170" />
                <el-table-column label="更新时间" prop="update_time_text" min-width="170" />
                <el-table-column label="操作" width="230" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['crm.followRecord/lists']"
                            type="primary"
                            link
                            @click="handleViewFollowRecords(row)"
                        >
                            跟进记录
                        </el-button>
                        <el-button
                            v-perms="['crm.followRecord/add']"
                            type="primary"
                            link
                            :disabled="!row.advisor_id"
                            @click="handleAddFollowRecord(row)"
                        >
                            新增跟进
                        </el-button>
                        <el-button
                            v-perms="['crm.customer/edit']"
                            type="primary"
                            link
                            @click="handleEdit(row)"
                        >
                            编辑
                        </el-button>
                        <el-button
                            v-perms="['crm.customer/transferAdvisor']"
                            type="primary"
                            link
                            :disabled="!row.can_transfer"
                            @click="handleTransfer(row)"
                        >
                            转移顾问
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-dialog
            v-model="showEditDialog"
            title="编辑客户"
            width="860px"
            destroy-on-close
            @closed="handleEditDialogClosed"
        >
            <el-form ref="editFormRef" :model="editForm" :rules="editRules" label-width="110px">
                <div class="crm-customer-lists__form-grid">
                    <el-form-item label="客户姓名" prop="customer_name">
                        <el-input v-model="editForm.customer_name" placeholder="请输入客户姓名" maxlength="50" />
                    </el-form-item>
                    <el-form-item label="手机号" prop="customer_mobile">
                        <el-input v-model="editForm.customer_mobile" placeholder="请输入手机号" maxlength="20" />
                    </el-form-item>
                    <el-form-item label="微信号" prop="customer_wechat">
                        <el-input v-model="editForm.customer_wechat" placeholder="请输入微信号" maxlength="50" />
                    </el-form-item>
                    <el-form-item label="性别" prop="gender">
                        <el-select v-model="editForm.gender" placeholder="请选择性别">
                            <el-option
                                v-for="item in optionData.gender_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="年龄" prop="age">
                        <el-input-number v-model="editForm.age" :min="0" :max="120" class="w-full" />
                    </el-form-item>
                    <el-form-item label="客户状态" prop="customer_status">
                        <el-select v-model="editForm.customer_status" placeholder="请选择客户状态">
                            <el-option
                                v-for="item in optionData.status_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="意向等级" prop="intention_level">
                        <el-select v-model="editForm.intention_level" placeholder="请选择意向等级">
                            <el-option
                                v-for="item in optionData.intention_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="意向评分" prop="intention_score">
                        <el-input-number v-model="editForm.intention_score" :min="0" :max="100" class="w-full" />
                    </el-form-item>
                    <el-form-item label="来源渠道" prop="source_channel">
                        <el-select v-model="editForm.source_channel" placeholder="请选择来源渠道">
                            <el-option
                                v-for="item in optionData.source_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="来源详情" prop="source_detail">
                        <el-input v-model="editForm.source_detail" placeholder="请输入来源详情" maxlength="100" />
                    </el-form-item>
                    <el-form-item label="城市" prop="city">
                        <el-input v-model="editForm.city" placeholder="请输入城市" maxlength="50" />
                    </el-form-item>
                    <el-form-item label="区域" prop="district">
                        <el-input v-model="editForm.district" placeholder="请输入区域" maxlength="50" />
                    </el-form-item>
                    <el-form-item label="计划婚期" prop="wedding_date">
                        <el-date-picker
                            v-model="editForm.wedding_date"
                            type="date"
                            value-format="YYYY-MM-DD"
                            placeholder="请选择计划婚期"
                            class="w-full"
                        />
                    </el-form-item>
                    <el-form-item label="婚礼场地" prop="wedding_venue">
                        <el-input v-model="editForm.wedding_venue" placeholder="请输入婚礼场地" maxlength="200" />
                    </el-form-item>
                    <el-form-item label="预算金额" prop="wedding_budget">
                        <el-input-number
                            v-model="editForm.wedding_budget"
                            :min="0"
                            :precision="2"
                            class="w-full"
                        />
                    </el-form-item>
                    <el-form-item label="预算范围" prop="budget_range">
                        <el-input v-model="editForm.budget_range" placeholder="请输入预算范围" maxlength="50" />
                    </el-form-item>
                    <el-form-item label="下次跟进" prop="next_follow_time">
                        <el-date-picker
                            v-model="editForm.next_follow_time"
                            type="datetime"
                            value-format="YYYY-MM-DD HH:mm:ss"
                            placeholder="请选择下次跟进时间"
                            class="w-full"
                        />
                    </el-form-item>
                    <el-form-item v-if="editForm.customer_status === 4" label="流失原因" prop="loss_reason">
                        <el-input v-model="editForm.loss_reason" placeholder="请输入流失原因" maxlength="200" />
                    </el-form-item>
                </div>
                <el-form-item label="服务需求" prop="service_needs">
                    <el-select
                        v-model="editForm.service_needs"
                        multiple
                        filterable
                        allow-create
                        default-first-option
                        placeholder="输入后回车添加服务需求"
                        class="w-full"
                    >
                        <el-option
                            v-for="item in serviceNeedOptions"
                            :key="item"
                            :label="item"
                            :value="item"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="客户标签" prop="tags">
                    <el-select
                        v-model="editForm.tags"
                        multiple
                        filterable
                        allow-create
                        default-first-option
                        placeholder="输入后回车添加客户标签"
                        class="w-full"
                    >
                        <el-option v-for="item in tagOptions" :key="item" :label="item" :value="item" />
                    </el-select>
                </el-form-item>
                <el-form-item label="备注" prop="remark">
                    <el-input
                        v-model="editForm.remark"
                        type="textarea"
                        :rows="3"
                        placeholder="请输入备注"
                        maxlength="500"
                        show-word-limit
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showEditDialog = false">取消</el-button>
                <el-button type="primary" :loading="submitting" @click="handleSaveEdit">保存</el-button>
            </template>
        </el-dialog>

        <el-dialog
            v-model="showTransferDialog"
            title="转移顾问"
            width="560px"
            destroy-on-close
            @closed="handleTransferDialogClosed"
        >
            <el-form ref="transferFormRef" :model="transferForm" :rules="transferRules" label-width="100px">
                <el-form-item label="客户">
                    <div class="crm-customer-lists__readonly">
                        {{ transferForm.customer_name || '-' }}
                    </div>
                </el-form-item>
                <el-form-item label="当前顾问">
                    <div class="crm-customer-lists__readonly">
                        {{ transferForm.current_advisor_name || '未分配' }}
                    </div>
                </el-form-item>
                <el-form-item label="目标顾问" prop="advisor_id">
                    <el-select v-model="transferForm.advisor_id" placeholder="请选择目标顾问" filterable class="w-full">
                        <el-option
                            v-for="item in advisorOptions"
                            :key="item.id"
                            :disabled="item.id === transferForm.current_advisor_id"
                            :label="`${item.advisor_name}（${item.load_text}）`"
                            :value="item.id"
                        >
                            <div class="crm-customer-lists__option">
                                <span>{{ item.advisor_name }}</span>
                                <span>{{ item.mobile || '-' }} · {{ item.load_text }}</span>
                            </div>
                        </el-option>
                    </el-select>
                </el-form-item>
                <el-form-item label="转移原因" prop="reason">
                    <el-input
                        v-model="transferForm.reason"
                        type="textarea"
                        :rows="3"
                        placeholder="请输入转移原因"
                        maxlength="200"
                        show-word-limit
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showTransferDialog = false">取消</el-button>
                <el-button type="primary" :loading="transferSubmitting" @click="handleSubmitTransfer">
                    确认转移
                </el-button>
            </template>
        </el-dialog>

        <el-dialog
            v-model="showFollowDialog"
            title="新增跟进记录"
            width="760px"
            destroy-on-close
            @closed="handleFollowDialogClosed"
        >
            <el-form ref="followFormRef" :model="followForm" :rules="followRules" label-width="110px">
                <div class="crm-customer-lists__form-grid">
                    <el-form-item label="客户">
                        <el-input v-model="followForm.customer_name" disabled />
                    </el-form-item>
                    <el-form-item label="跟进方式" prop="follow_type">
                        <el-select v-model="followForm.follow_type" placeholder="请选择跟进方式">
                            <el-option
                                v-for="item in followOptionData.follow_type_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="跟进结果" prop="follow_result">
                        <el-select v-model="followForm.follow_result" placeholder="请选择跟进结果">
                            <el-option
                                v-for="item in followOptionData.follow_result_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="跟进后意向" prop="intention_after">
                        <el-select v-model="followForm.intention_after" placeholder="请选择意向">
                            <el-option
                                v-for="item in followOptionData.intention_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="沟通时长" prop="duration">
                        <el-input-number v-model="followForm.duration" :min="0" :max="10000" class="w-full" />
                    </el-form-item>
                    <el-form-item label="是否重要" prop="is_important">
                        <el-switch v-model="followForm.is_important" :active-value="1" :inactive-value="0" />
                    </el-form-item>
                    <el-form-item label="下次跟进" prop="next_follow_time" class="crm-customer-lists__full">
                        <el-date-picker
                            v-model="followForm.next_follow_time"
                            type="datetime"
                            value-format="YYYY-MM-DD HH:mm:ss"
                            placeholder="请选择下次跟进时间"
                            class="w-full"
                        />
                    </el-form-item>
                    <el-form-item label="跟进内容" prop="follow_content" class="crm-customer-lists__full">
                        <el-input
                            v-model="followForm.follow_content"
                            type="textarea"
                            :rows="4"
                            maxlength="2000"
                            show-word-limit
                            placeholder="请输入本次跟进内容"
                        />
                    </el-form-item>
                    <el-form-item label="下次计划" prop="next_follow_content" class="crm-customer-lists__full">
                        <el-input
                            v-model="followForm.next_follow_content"
                            maxlength="255"
                            placeholder="请输入下次跟进计划"
                        />
                    </el-form-item>
                </div>
            </el-form>
            <template #footer>
                <el-button @click="showFollowDialog = false">取消</el-button>
                <el-button type="primary" :loading="followSubmitting" @click="handleSubmitFollow">
                    保存
                </el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="crmCustomerLists">
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'

import {
    customerAdvisorOptions,
    customerDetail,
    customerEdit,
    customerLists,
    customerOptions,
    customerTransferAdvisor
} from '@/api/crm/customer'
import { followRecordAdd, followRecordOptions } from '@/api/crm/followRecord'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

interface OptionItem {
    value: number | string
    label: string
}

interface AdvisorOption {
    id: number
    advisor_name: string
    mobile: string
    status: number
    status_desc: string
    current_customer_count: number
    max_customer_count: number
    load_text: string
}

const defaultOptions = {
    status_options: [
        { value: 1, label: '新客户' },
        { value: 2, label: '跟进中' },
        { value: 3, label: '已签单' },
        { value: 4, label: '已流失' },
        { value: 5, label: '已完成' }
    ] as OptionItem[],
    intention_options: [
        { value: 'A', label: '高意向' },
        { value: 'B', label: '中意向' },
        { value: 'C', label: '低意向' },
        { value: 'D', label: '待跟进' }
    ] as OptionItem[],
    source_options: [
        { value: 1, label: '小程序' },
        { value: 2, label: 'H5' },
        { value: 3, label: '线下' },
        { value: 4, label: '转介绍' },
        { value: 5, label: '广告' },
        { value: 6, label: '其他' }
    ] as OptionItem[],
    gender_options: [
        { value: 0, label: '未知' },
        { value: 1, label: '男' },
        { value: 2, label: '女' }
    ] as OptionItem[]
}

const queryParams = reactive({
    keyword: '',
    customer_status: '' as '' | number,
    intention_level: '' as '' | string,
    source_channel: '' as '' | number,
    advisor_id: '' as '' | number
})

const optionData = reactive({ ...defaultOptions })
const advisorOptions = ref<AdvisorOption[]>([])
const showEditDialog = ref(false)
const showTransferDialog = ref(false)
const showFollowDialog = ref(false)
const submitting = ref(false)
const transferSubmitting = ref(false)
const followSubmitting = ref(false)
const editFormRef = shallowRef<FormInstance>()
const transferFormRef = shallowRef<FormInstance>()
const followFormRef = shallowRef<FormInstance>()

const createDefaultEditForm = () => ({
    id: 0,
    customer_name: '',
    customer_mobile: '',
    customer_wechat: '',
    gender: 0,
    age: 0,
    city: '',
    district: '',
    intention_level: 'D',
    intention_score: 0,
    wedding_date: '',
    wedding_venue: '',
    wedding_budget: 0,
    budget_range: '',
    service_needs: [] as string[],
    source_channel: 1,
    source_detail: '',
    tags: [] as string[],
    customer_status: 1,
    loss_reason: '',
    next_follow_time: '',
    remark: ''
})

const createDefaultTransferForm = () => ({
    customer_id: 0,
    customer_name: '',
    current_advisor_id: 0,
    current_advisor_name: '',
    advisor_id: undefined as number | undefined,
    reason: ''
})

const createDefaultFollowForm = () => ({
    customer_id: 0,
    customer_name: '',
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

const editForm = reactive(createDefaultEditForm())
const transferForm = reactive(createDefaultTransferForm())
const followForm = reactive(createDefaultFollowForm())
const followOptionData = reactive({
    follow_type_options: [] as OptionItem[],
    follow_result_options: [] as OptionItem[],
    intention_options: [] as OptionItem[]
})

const editRules = reactive<FormRules>({
    customer_name: [{ required: true, message: '请输入客户姓名', trigger: 'blur' }],
    gender: [{ required: true, message: '请选择性别', trigger: 'change' }],
    intention_level: [{ required: true, message: '请选择意向等级', trigger: 'change' }],
    source_channel: [{ required: true, message: '请选择来源渠道', trigger: 'change' }],
    customer_status: [{ required: true, message: '请选择客户状态', trigger: 'change' }]
})

const transferRules = reactive<FormRules>({
    advisor_id: [{ required: true, message: '请选择目标顾问', trigger: 'change' }]
})

const followRules = reactive<FormRules>({
    follow_type: [{ required: true, message: '请选择跟进方式', trigger: 'change' }],
    follow_result: [{ required: true, message: '请选择跟进结果', trigger: 'change' }],
    intention_after: [{ required: true, message: '请选择跟进后意向', trigger: 'change' }],
    follow_content: [{ required: true, message: '请输入跟进内容', trigger: 'blur' }]
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: customerLists,
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

const collectOptions = (field: 'service_needs' | 'tags', extra: string[] = []) => {
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

const serviceNeedOptions = computed(() => collectOptions('service_needs', editForm.service_needs))
const tagOptions = computed(() => collectOptions('tags', editForm.tags))

const getOptionLabel = (options: OptionItem[], value: number | string) => {
    return options.find((item) => String(item.value) === String(value))?.label || '未知'
}

const getGenderType = (gender: number): 'info' | 'primary' | 'danger' => {
    const map: Record<number, 'info' | 'primary' | 'danger'> = {
        0: 'info',
        1: 'primary',
        2: 'danger'
    }
    return map[Number(gender)] || 'info'
}

const getCustomerStatusType = (status: number): 'success' | 'warning' | 'primary' | 'danger' | 'info' => {
    const map: Record<number, 'success' | 'warning' | 'primary' | 'danger' | 'info'> = {
        1: 'success',
        2: 'warning',
        3: 'primary',
        4: 'danger',
        5: 'info'
    }
    return map[Number(status)] || 'info'
}

const getIntentionType = (level: string): 'danger' | 'warning' | 'primary' | 'info' => {
    const map: Record<string, 'danger' | 'warning' | 'primary' | 'info'> = {
        A: 'danger',
        B: 'warning',
        C: 'primary',
        D: 'info'
    }
    return map[String(level)] || 'info'
}

const formatArea = (row: any) => {
    const city = String(row.city || '').trim()
    const district = String(row.district || '').trim()
    if (!city && !district) {
        return '城市：-'
    }
    return `城市：${[city, district].filter(Boolean).join(' / ')}`
}

const resetEditForm = () => {
    Object.assign(editForm, createDefaultEditForm())
}

const resetTransferForm = () => {
    Object.assign(transferForm, createDefaultTransferForm())
}

const resetFollowForm = () => {
    Object.assign(followForm, createDefaultFollowForm())
}

const fillEditForm = (data: any = {}) => {
    Object.assign(editForm, {
        id: Number(data.id || 0),
        customer_name: String(data.customer_name || ''),
        customer_mobile: String(data.customer_mobile || ''),
        customer_wechat: String(data.customer_wechat || ''),
        gender: Number(data.gender || 0),
        age: Number(data.age || 0),
        city: String(data.city || ''),
        district: String(data.district || ''),
        intention_level: String(data.intention_level || 'D'),
        intention_score: Number(data.intention_score || 0),
        wedding_date: data.wedding_date ? String(data.wedding_date) : '',
        wedding_venue: String(data.wedding_venue || ''),
        wedding_budget: Number(data.wedding_budget || 0),
        budget_range: String(data.budget_range || ''),
        service_needs: normalizeTagList(data.service_needs),
        source_channel: Number(data.source_channel || 1),
        source_detail: String(data.source_detail || ''),
        tags: normalizeTagList(data.tags),
        customer_status: Number(data.customer_status || 1),
        loss_reason: String(data.loss_reason || ''),
        next_follow_time: normalizeDateTimeValue(data.next_follow_time_text || data.next_follow_time),
        remark: String(data.remark || '')
    })
}

const normalizeDateTimeValue = (value: any) => {
    if (!value || value === '0') {
        return ''
    }
    if (typeof value === 'number') {
        return value > 0 ? value : ''
    }
    return String(value)
}

const loadOptions = async () => {
    const data = await customerOptions()
    if (data?.status_options?.length) {
        optionData.status_options = data.status_options
    }
    if (data?.intention_options?.length) {
        optionData.intention_options = data.intention_options
    }
    if (data?.source_options?.length) {
        optionData.source_options = data.source_options
    }
    if (data?.gender_options?.length) {
        optionData.gender_options = data.gender_options
    }
}

const loadAdvisorOptions = async () => {
    const data = await customerAdvisorOptions()
    advisorOptions.value = Array.isArray(data)
        ? data.map((item: any) => ({
              id: Number(item.id || 0),
              advisor_name: String(item.advisor_name || ''),
              mobile: String(item.mobile || ''),
              status: Number(item.status || 0),
              status_desc: String(item.status_desc || ''),
              current_customer_count: Number(item.current_customer_count || 0),
              max_customer_count: Number(item.max_customer_count || 0),
              load_text: String(item.load_text || '')
          }))
        : []
}

const loadFollowOptions = async () => {
    const data = await followRecordOptions()
    followOptionData.follow_type_options = data?.follow_type_options || []
    followOptionData.follow_result_options = data?.follow_result_options || []
    followOptionData.intention_options = data?.intention_options || optionData.intention_options
}

const handleEdit = async (row: any) => {
    resetEditForm()
    const data = await customerDetail({ id: row.id })
    fillEditForm(data || row)
    showEditDialog.value = true
}

const buildEditPayload = () => ({
    id: Number(editForm.id || 0),
    customer_name: editForm.customer_name.trim(),
    customer_mobile: editForm.customer_mobile.trim(),
    customer_wechat: editForm.customer_wechat.trim(),
    gender: Number(editForm.gender || 0),
    age: Number(editForm.age || 0),
    city: editForm.city.trim(),
    district: editForm.district.trim(),
    intention_level: editForm.intention_level,
    intention_score: Number(editForm.intention_score || 0),
    wedding_date: editForm.wedding_date || '',
    wedding_venue: editForm.wedding_venue.trim(),
    wedding_budget: Number(editForm.wedding_budget || 0),
    budget_range: editForm.budget_range.trim(),
    service_needs: editForm.service_needs,
    source_channel: Number(editForm.source_channel || 1),
    source_detail: editForm.source_detail.trim(),
    tags: editForm.tags,
    customer_status: Number(editForm.customer_status || 1),
    loss_reason: editForm.loss_reason.trim(),
    next_follow_time: editForm.next_follow_time || '',
    remark: editForm.remark.trim()
})

const handleSaveEdit = async () => {
    await editFormRef.value?.validate()
    submitting.value = true
    try {
        await customerEdit(buildEditPayload())
        ElMessage.success('客户已更新')
        showEditDialog.value = false
        getLists()
    } finally {
        submitting.value = false
    }
}

const handleTransfer = async (row: any) => {
    resetTransferForm()
    await loadAdvisorOptions()
    Object.assign(transferForm, {
        customer_id: Number(row.id || 0),
        customer_name: String(row.customer_name || ''),
        current_advisor_id: Number(row.advisor_id || 0),
        current_advisor_name: String(row.advisor?.advisor_name || ''),
        advisor_id: undefined,
        reason: ''
    })
    showTransferDialog.value = true
}

const handleViewFollowRecords = (row: any) => {
    router.push({
        path: '/crm/follow-record',
        query: {
            customer_id: row.id
        }
    })
}

const handleAddFollowRecord = async (row: any) => {
    resetFollowForm()
    await loadFollowOptions()
    Object.assign(followForm, {
        customer_id: Number(row.id || 0),
        customer_name: String(row.customer_name || ''),
        intention_after: String(row.intention_level || 'D')
    })
    showFollowDialog.value = true
}

const handleSubmitFollow = async () => {
    await followFormRef.value?.validate()
    followSubmitting.value = true
    try {
        await followRecordAdd({
            customer_id: Number(followForm.customer_id || 0),
            follow_type: Number(followForm.follow_type || 1),
            follow_content: followForm.follow_content.trim(),
            follow_result: Number(followForm.follow_result || 1),
            intention_after: followForm.intention_after,
            duration: Number(followForm.duration || 0),
            next_follow_time: followForm.next_follow_time || '',
            next_follow_content: followForm.next_follow_content.trim(),
            attachments: followForm.attachments,
            is_important: Number(followForm.is_important || 0)
        })
        ElMessage.success('跟进记录已新增')
        showFollowDialog.value = false
        getLists()
    } finally {
        followSubmitting.value = false
    }
}

const handleSubmitTransfer = async () => {
    await transferFormRef.value?.validate()
    await feedback.confirm(`确定将客户「${transferForm.customer_name || ''}」转移给所选顾问？`)
    transferSubmitting.value = true
    try {
        await customerTransferAdvisor({
            customer_id: Number(transferForm.customer_id || 0),
            advisor_id: Number(transferForm.advisor_id || 0),
            reason: transferForm.reason.trim()
        })
        ElMessage.success('顾问已转移')
        showTransferDialog.value = false
        await loadAdvisorOptions()
        getLists()
    } finally {
        transferSubmitting.value = false
    }
}

const handleEditDialogClosed = () => {
    editFormRef.value?.clearValidate()
}

const handleTransferDialogClosed = () => {
    transferFormRef.value?.clearValidate()
}

const handleFollowDialogClosed = () => {
    followFormRef.value?.clearValidate()
}

const router = useRouter()

onActivated(() => {
    loadOptions()
    loadAdvisorOptions()
    loadFollowOptions()
    getLists()
})

loadOptions()
loadAdvisorOptions()
loadFollowOptions()
getLists()
</script>

<style lang="scss" scoped>
.crm-customer-lists {
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

    &__line {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }

    &__form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 16px;
    }

    &__full {
        grid-column: 1 / -1;
    }

    &__readonly {
        color: var(--el-text-color-regular);
        min-height: 32px;
        display: flex;
        align-items: center;
    }

    &__option {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        min-width: 0;

        span:last-child {
            color: var(--el-text-color-secondary);
            font-size: 12px;
        }
    }

    :deep(.el-select),
    :deep(.el-date-editor.el-input),
    :deep(.el-input-number) {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .crm-customer-lists {
        &__form-grid {
            grid-template-columns: 1fr;
        }
    }
}
</style>
