<template>
    <admin-page-shell
        class="crm-follow-record-lists"
        title="跟进记录"
        description="记录客户沟通内容、意向变化、下次计划和重要跟进事项。"
    >
        <template #stats>
            <div class="crm-follow-record-lists__stats">
                <div class="crm-follow-record-lists__stat">
                    <span>今日跟进</span>
                    <strong class="text-primary">{{ stats.today_count }}</strong>
                </div>
                <div class="crm-follow-record-lists__stat">
                    <span>近 7 日跟进</span>
                    <strong>{{ stats.week_count }}</strong>
                </div>
                <div class="crm-follow-record-lists__stat">
                    <span>重要跟进累计</span>
                    <strong class="text-danger">{{ stats.important_count }}</strong>
                </div>
                <div class="crm-follow-record-lists__stat">
                    <span>近 7 日跟进签单</span>
                    <strong class="text-success">{{ stats.week_signed_count }}</strong>
                </div>
            </div>
        </template>

        <template #search>
            <search-panel>
                <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                    <el-form-item class="w-[240px]" label="关键词">
                        <el-input
                            v-model="queryParams.keyword"
                            placeholder="客户/手机号/跟进内容"
                            clearable
                            @keyup.enter="resetPage"
                        />
                    </el-form-item>
                    <el-form-item class="w-[200px]" label="客户">
                        <el-select
                            v-model="queryParams.customer_id"
                            placeholder="搜索或选择客户"
                            clearable
                            filterable
                            remote
                            :remote-method="searchCustomerOptions"
                            :loading="customerLoading"
                        >
                            <el-option
                                v-for="item in customerOptions"
                                :key="item.id"
                                :label="formatCustomerOption(item)"
                                :value="item.id"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[170px]" label="顾问">
                        <el-select v-model="queryParams.advisor_id" placeholder="全部顾问" clearable filterable>
                            <el-option
                                v-for="item in advisorOptions"
                                :key="item.id"
                                :label="item.advisor_name"
                                :value="item.id"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[145px]" label="方式">
                        <el-select v-model="queryParams.follow_type" placeholder="全部方式" clearable>
                            <el-option
                                v-for="item in optionData.follow_type_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[145px]" label="结果">
                        <el-select v-model="queryParams.follow_result" placeholder="全部结果" clearable>
                            <el-option
                                v-for="item in optionData.follow_result_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[135px]" label="重要">
                        <el-select v-model="queryParams.is_important" placeholder="全部" clearable>
                            <el-option label="仅看重要" :value="1" />
                            <el-option label="普通" :value="0" />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[240px]" label="日期">
                        <el-date-picker
                            v-model="dateRange"
                            type="daterange"
                            value-format="YYYY-MM-DD"
                            start-placeholder="开始"
                            end-placeholder="结束"
                            clearable
                        />
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="resetPage">查询</el-button>
                        <el-button @click="handleResetAll">重置</el-button>
                        <el-button v-perms="['crm.followRecord/add']" type="primary" @click="handleAdd">
                            新增跟进
                        </el-button>
                    </el-form-item>
                </el-form>
            </search-panel>
        </template>

        <div class="admin-page-section">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="客户" min-width="210" fixed="left">
                    <template #default="{ row }">
                        <div class="crm-follow-record-lists__stack">
                            <el-button
                                type="primary"
                                link
                                class="!p-0 !h-auto !justify-start font-medium"
                                @click="openCustomerDrawer(row.customer_id)"
                            >
                                {{ row.customer?.customer_name || '-' }}
                            </el-button>
                            <span class="text-xs text-tx-secondary">手机：{{ row.customer?.customer_mobile || '-' }}</span>
                            <span class="text-xs text-tx-secondary">顾问：{{ row.advisor?.advisor_name || '-' }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="跟进内容与附件" min-width="300">
                    <template #default="{ row }">
                        <div class="crm-follow-record-lists__stack">
                            <span>{{ row.follow_content || '-' }}</span>
                            <span class="text-xs text-tx-secondary">
                                下次计划：{{ row.next_follow_content || '-' }}
                            </span>
                            <div v-if="row.attachments?.length" class="crm-follow-record-lists__imgs">
                                <el-image
                                    v-for="(img, idx) in row.attachments"
                                    :key="idx"
                                    :src="img"
                                    :preview-src-list="row.attachments"
                                    :initial-index="idx"
                                    fit="cover"
                                    class="crm-follow-record-lists__img"
                                    preview-teleported
                                />
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="方式 / 结果" min-width="150" align="center">
                    <template #default="{ row }">
                        <div class="crm-follow-record-lists__center-stack">
                            <el-tag effect="plain">{{ row.follow_type_desc }}</el-tag>
                            <el-tag :type="getResultType(row.follow_result)">
                                {{ row.follow_result_desc }}
                            </el-tag>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="意向变化" min-width="120" align="center">
                    <template #default="{ row }">
                        <span>{{ row.intention_before || '-' }} → {{ row.intention_after || '-' }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="时长 / 重要" width="120" align="center">
                    <template #default="{ row }">
                        <div class="crm-follow-record-lists__center-stack">
                            <span>{{ Number(row.duration || 0) }} 分钟</span>
                            <el-tag :type="row.is_important ? 'danger' : 'info'" size="small" effect="plain">
                                {{ row.is_important ? '重要' : '普通' }}
                            </el-tag>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="记录人" width="110" align="center">
                    <template #default="{ row }">
                        {{ row.admin?.name || row.admin?.account || '系统' }}
                    </template>
                </el-table-column>
                <el-table-column label="下次跟进" prop="next_follow_time_text" min-width="165" />
                <el-table-column label="创建时间" prop="create_time_text" min-width="165" />
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </div>

        <QuickFollowDialog v-model="showAddDialog" @submitted="handleAfterMutation" />

        <CustomerDetailDrawer
            v-model="showDetailDrawer"
            :customer-id="drawerCustomerId"
            @updated="handleAfterMutation"
        />
    </admin-page-shell>
</template>

<script lang="ts" setup name="crmFollowRecordLists">
import { customerAdvisorOptions } from '@/api/crm/customer'
import {
    followRecordCustomerOptions,
    followRecordLists,
    followRecordOptions,
    followRecordStats
} from '@/api/crm/followRecord'
import { usePaging } from '@/hooks/usePaging'

import CustomerDetailDrawer from '../components/CustomerDetailDrawer.vue'
import QuickFollowDialog from '../components/QuickFollowDialog.vue'

interface OptionItem {
    value: number | string
    label: string
}

interface CustomerOption {
    id: number
    customer_name: string
    customer_mobile: string
}

const stats = reactive({
    today_count: 0,
    week_count: 0,
    important_count: 0,
    week_signed_count: 0
})

const optionData = reactive({
    follow_type_options: [] as OptionItem[],
    follow_result_options: [] as OptionItem[],
    intention_options: [] as OptionItem[]
})

const dateRange = ref<[string, string] | []>([])

const queryParams = reactive({
    keyword: '',
    customer_id: '' as '' | number,
    advisor_id: '' as '' | number,
    follow_type: '' as '' | number,
    follow_result: '' as '' | number,
    is_important: '' as '' | number,
    start_time: '',
    end_time: ''
})

watch(dateRange, (val) => {
    queryParams.start_time = Array.isArray(val) && val[0] ? `${val[0]} 00:00:00` : ''
    queryParams.end_time = Array.isArray(val) && val[1] ? `${val[1]} 23:59:59` : ''
})

const customerOptions = ref<CustomerOption[]>([])
const advisorOptions = ref<Array<{ id: number; advisor_name: string }>>([])
const customerLoading = ref(false)
const showAddDialog = ref(false)
const showDetailDrawer = ref(false)
const drawerCustomerId = ref(0)

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

const loadStats = async () => {
    const data = await followRecordStats()
    if (data) {
        Object.assign(stats, {
            today_count: Number(data.today_count || 0),
            week_count: Number(data.week_count || 0),
            important_count: Number(data.important_count || 0),
            week_signed_count: Number(data.week_signed_count || 0)
        })
    }
}

const loadOptions = async () => {
    const data = await followRecordOptions()
    optionData.follow_type_options = data?.follow_type_options || []
    optionData.follow_result_options = data?.follow_result_options || []
    optionData.intention_options = data?.intention_options || []
}

const loadAdvisorOptions = async () => {
    const data = await customerAdvisorOptions({ mode: 'all' })
    advisorOptions.value = Array.isArray(data) ? data : []
}

const searchCustomerOptions = async (keyword = '') => {
    customerLoading.value = true
    try {
        const data = await followRecordCustomerOptions({ keyword, include_all: 1 })
        customerOptions.value = Array.isArray(data) ? data : []
    } finally {
        customerLoading.value = false
    }
}

const openCustomerDrawer = (customerId: number) => {
    if (!customerId) return
    drawerCustomerId.value = Number(customerId)
    showDetailDrawer.value = true
}

const handleAdd = () => {
    showAddDialog.value = true
}

const handleResetAll = () => {
    dateRange.value = []
    resetParams()
}

const handleAfterMutation = async () => {
    await Promise.all([loadStats(), getLists()])
}

const applyRouteCustomer = () => {
    const customerId = Number(route.query.customer_id || 0)
    if (customerId > 0) {
        queryParams.customer_id = customerId
    }
}

onActivated(() => {
    applyRouteCustomer()
    void loadStats()
    void loadOptions()
    void loadAdvisorOptions()
    void searchCustomerOptions('')
    void getLists()
})

applyRouteCustomer()
void loadStats()
void loadOptions()
void loadAdvisorOptions()
void searchCustomerOptions('')
void getLists()
</script>

<style lang="scss" scoped>
.crm-follow-record-lists {
    &__stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    &__stat {
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding: 12px 14px;
        border: 1px solid var(--el-border-color-light);
        border-radius: 8px;

        span {
            color: var(--el-text-color-secondary);
            font-size: 12px;
        }

        strong {
            color: var(--el-text-color-primary);
            font-size: 20px;
            line-height: 1.1;
        }
    }

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

    &__imgs {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 2px;
    }

    &__img {
        width: 44px;
        height: 44px;
        border-radius: 4px;
    }
}

@media (max-width: 900px) {
    .crm-follow-record-lists {
        &__stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
}
</style>
