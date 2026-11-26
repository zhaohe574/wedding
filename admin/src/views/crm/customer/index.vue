<template>
    <admin-page-shell class="crm-customer-lists" title="客户管理">
        <template #stats>
            <div class="crm-customer-lists__stats">
                <div
                    class="crm-customer-lists__stat"
                    :class="{ 'is-active': activeQuickView === 'all' }"
                    @click="applyQuickView('all')"
                >
                    <span>全部客户</span>
                    <strong>{{ overview.total }}</strong>
                </div>
                <div
                    class="crm-customer-lists__stat"
                    :class="{ 'is-active': activeQuickView === 'today' }"
                    @click="applyQuickView('today')"
                >
                    <span>今日待跟进</span>
                    <strong class="text-primary">{{ overview.today_follow }}</strong>
                </div>
                <div
                    class="crm-customer-lists__stat"
                    :class="{ 'is-active': activeQuickView === 'overdue' }"
                    @click="applyQuickView('overdue')"
                >
                    <span>跟进已逾期</span>
                    <strong class="text-danger">{{ overview.overdue_follow }}</strong>
                </div>
                <div
                    class="crm-customer-lists__stat"
                    :class="{ 'is-active': activeQuickView === 'unassigned' }"
                    @click="applyQuickView('unassigned')"
                >
                    <span>待分配顾问</span>
                    <strong class="text-warning">{{ overview.unassigned }}</strong>
                </div>
                <div
                    class="crm-customer-lists__stat"
                    :class="{ 'is-active': activeQuickView === 'intention_a' }"
                    @click="applyQuickView('intention_a')"
                >
                    <span>A 级高意向</span>
                    <strong class="text-danger">{{ overview.intention_a }}</strong>
                </div>
                <div
                    class="crm-customer-lists__stat"
                    :class="{ 'is-active': activeQuickView === 'signed' }"
                    @click="applyQuickView('signed')"
                >
                    <span>本月签单 / 累计</span>
                    <strong class="text-success">{{ overview.month_signed }} / {{ overview.signed }}</strong>
                </div>
            </div>
        </template>

        <template #search>
            <search-panel>
                <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                    <el-form-item class="w-[260px]" label="关键词">
                        <el-input
                            v-model="queryParams.keyword"
                            placeholder="姓名/手机号/微信/城市/场地"
                            clearable
                            @keyup.enter="resetPage"
                        />
                    </el-form-item>
                    <el-form-item class="w-[155px]" label="状态">
                        <el-select v-model="queryParams.customer_status" placeholder="全部状态" clearable>
                            <el-option
                                v-for="item in optionData.status_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[155px]" label="意向">
                        <el-select v-model="queryParams.intention_level" placeholder="全部意向" clearable>
                            <el-option
                                v-for="item in optionData.intention_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[165px]" label="跟进节拍">
                        <el-select v-model="queryParams.follow_stage" placeholder="全部" clearable>
                            <el-option label="今日待跟进" value="today" />
                            <el-option label="已逾期跟进" value="overdue" />
                            <el-option label="7天未跟进" value="no_follow_7d" />
                            <el-option label="从未跟进" value="never" />
                            <el-option label="未分配顾问" value="unassigned" />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[155px]" label="来源">
                        <el-select v-model="queryParams.source_channel" placeholder="全部来源" clearable>
                            <el-option
                                v-for="item in optionData.source_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[180px]" label="顾问">
                        <el-select v-model="queryParams.advisor_id" placeholder="全部顾问" clearable filterable>
                            <el-option label="未分配" :value="0" />
                            <el-option
                                v-for="item in allAdvisorOptions"
                                :key="item.id"
                                :label="`${item.advisor_name}（${item.load_text}）`"
                                :value="item.id"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[240px]" label="婚期">
                        <el-date-picker
                            v-model="weddingDateRange"
                            type="daterange"
                            value-format="YYYY-MM-DD"
                            start-placeholder="开始"
                            end-placeholder="结束"
                            clearable
                        />
                    </el-form-item>
                    <el-form-item class="w-[165px]" label="排序">
                        <el-select v-model="queryParams.sort_by" placeholder="默认最新" clearable>
                            <el-option label="最新录入" value="id" />
                            <el-option label="下次跟进优先" value="next_follow" />
                            <el-option label="婚期临近优先" value="wedding_date" />
                            <el-option label="最久未跟进优先" value="days_no_follow" />
                        </el-select>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="resetPage">查询</el-button>
                        <el-button @click="handleResetAll">重置</el-button>
                    </el-form-item>
                </el-form>
            </search-panel>
        </template>

        <div class="admin-page-section">
            <div class="crm-customer-lists__toolbar">
                <div class="crm-customer-lists__toolbar-left">
                    <el-button
                        v-perms="['crm.customer/add', 'crm.customer/edit']"
                        type="primary"
                        @click="handleAdd"
                    >
                        <template #icon>
                            <icon name="el-icon-Plus" />
                        </template>
                        新增客户
                    </el-button>
                    <el-button
                        v-if="selectedTransferableIds.length"
                        v-perms="['crm.customer/batchTransfer', 'crm.customer/transferAdvisor']"
                        type="warning"
                        plain
                        @click="handleOpenBatchTransfer"
                    >
                        批量转移顾问（已选 {{ selectedTransferableIds.length }} 名可转客户）
                    </el-button>
                </div>
                <span class="text-xs text-tx-secondary">点击客户姓名可直接打开 360° 跟进档案抽屉</span>
            </div>

            <el-table
                ref="tableRef"
                size="large"
                v-loading="pager.loading"
                :data="pager.lists"
                @selection-change="handleSelectionChange"
            >
                <el-table-column type="selection" width="46" fixed="left" />
                <el-table-column label="客户" min-width="220" fixed="left">
                    <template #default="{ row }">
                        <div class="crm-customer-lists__stack">
                            <div class="crm-customer-lists__line">
                                <el-button
                                    type="primary"
                                    link
                                    class="!p-0 !h-auto font-medium"
                                    @click="openCustomerDrawer(row.id)"
                                >
                                    {{ row.customer_name || '-' }}
                                </el-button>
                                <el-tag size="small" :type="getGenderType(row.gender)" effect="plain">
                                    {{ row.gender_desc || getOptionLabel(optionData.gender_options, row.gender) }}
                                </el-tag>
                                <el-tag
                                    v-if="Number(row.pending_warning_count || 0) > 0"
                                    size="small"
                                    type="danger"
                                    effect="dark"
                                >
                                    预警
                                </el-tag>
                            </div>
                            <span class="text-xs text-tx-secondary">手机：{{ row.customer_mobile || '-' }}</span>
                            <span class="text-xs text-tx-secondary">微信：{{ row.customer_wechat || '-' }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="婚礼信息" min-width="230">
                    <template #default="{ row }">
                        <div class="crm-customer-lists__stack">
                            <span>{{ formatArea(row) }}</span>
                            <div class="crm-customer-lists__line">
                                <span>婚期：{{ row.wedding_date || '-' }}</span>
                                <el-tag
                                    v-if="row.days_to_wedding !== null && row.days_to_wedding !== undefined && row.days_to_wedding >= 0 && row.days_to_wedding <= 60"
                                    size="small"
                                    type="warning"
                                    effect="plain"
                                >
                                    {{ row.days_to_wedding }}天后
                                </el-tag>
                            </div>
                            <span class="text-xs text-tx-secondary">场地：{{ row.wedding_venue || '-' }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="意向 / 来源" min-width="150" align="center">
                    <template #default="{ row }">
                        <div class="crm-customer-lists__center-stack">
                            <el-tag :type="getIntentionType(row.intention_level)" effect="plain">
                                {{ row.intention_level_desc || getOptionLabel(optionData.intention_options, row.intention_level) }}
                            </el-tag>
                            <span class="text-xs text-tx-secondary" :title="row.source_detail_text || row.source_detail || ''">
                                {{ row.source_channel_desc || getOptionLabel(optionData.source_options, row.source_channel) }}
                            </span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="跟进节拍" min-width="200">
                    <template #default="{ row }">
                        <div class="crm-customer-lists__stack">
                            <div class="crm-customer-lists__line">
                                <span>已跟 {{ Number(row.follow_count || 0) }} 次</span>
                                <el-tag v-if="row.is_follow_overdue" size="small" type="danger" effect="plain">
                                    已逾期
                                </el-tag>
                                <el-tag v-else-if="row.is_follow_today" size="small" type="warning" effect="plain">
                                    今日跟进
                                </el-tag>
                                <span
                                    v-else-if="Number(row.days_no_follow || 0) >= 7 && [1, 2].includes(Number(row.customer_status))"
                                    class="text-xs text-danger"
                                >
                                    {{ row.days_no_follow }}天未跟
                                </span>
                            </div>
                            <span class="text-xs text-tx-secondary">上次：{{ row.last_follow_time_text || '暂无' }}</span>
                            <span class="text-xs" :class="row.is_follow_overdue ? 'text-danger' : 'text-tx-secondary'">
                                下次：{{ row.next_follow_time_text || '未排期' }}
                            </span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="当前顾问" min-width="160">
                    <template #default="{ row }">
                        <div v-if="row.advisor" class="crm-customer-lists__stack">
                            <span class="font-medium">{{ row.advisor.advisor_name || '-' }}</span>
                            <span class="text-xs text-tx-secondary">{{ row.advisor.mobile || '-' }}</span>
                        </div>
                        <el-tag v-else type="info" effect="plain">未分配</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="状态 / 订单" width="130" align="center">
                    <template #default="{ row }">
                        <div class="crm-customer-lists__center-stack">
                            <el-tag :type="getCustomerStatusType(row.customer_status)">
                                {{ row.customer_status_desc || getOptionLabel(optionData.status_options, row.customer_status) }}
                            </el-tag>
                            <span v-if="Number(row.real_order_count || 0) > 0" class="text-xs text-success">
                                {{ row.real_order_count }}单 · ¥{{ Number(row.real_total_amount || 0).toFixed(0) }}
                            </span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="更新时间" prop="update_time_text" min-width="165" />
                <el-table-column label="操作" width="230" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="openCustomerDrawer(row.id)">详情</el-button>
                        <el-button
                            v-perms="['crm.followRecord/add']"
                            type="primary"
                            link
                            :disabled="!row.advisor_id"
                            @click="handleAddFollowRecord(row)"
                        >
                            写跟进
                        </el-button>
                        <el-button
                            v-perms="['crm.customer/edit']"
                            type="primary"
                            link
                            @click="handleEdit(row)"
                        >
                            编辑
                        </el-button>
                        <el-dropdown trigger="click" @command="(cmd) => handleRowCommand(cmd, row)">
                            <el-button type="primary" link class="ml-2">
                                更多<icon name="el-icon-ArrowDown" class="ml-1" />
                            </el-button>
                            <template #dropdown>
                                <el-dropdown-menu>
                                    <el-dropdown-item
                                        v-if="row.can_transfer"
                                        command="transfer"
                                    >
                                        转移顾问
                                    </el-dropdown-item>
                                    <el-dropdown-item command="follow_lists">查看跟进列表</el-dropdown-item>
                                    <el-dropdown-item v-if="questionnaireTaskPath" command="questionnaire">
                                        新人问卷任务
                                    </el-dropdown-item>
                                </el-dropdown-menu>
                            </template>
                        </el-dropdown>
                    </template>
                </el-table-column>
            </el-table>

            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </div>

        <!-- 新增 / 编辑客户弹窗 -->
        <el-dialog
            v-model="showEditDialog"
            :title="editForm.id ? '编辑客户' : '新增客户'"
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
                        <el-input v-model="editForm.source_detail" placeholder="如：小红书私信 / 朋友老林推荐" maxlength="100" />
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
                        <el-input v-model="editForm.budget_range" placeholder="如：3-5万" maxlength="50" />
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

                    <!-- 新增模式下的顾问分配选项 -->
                    <template v-if="!editForm.id">
                        <el-form-item label="顾问分配" prop="assign_mode">
                            <el-radio-group v-model="editForm.assign_mode">
                                <el-radio value="auto">按负载自动分配</el-radio>
                                <el-radio value="manual">指定顾问</el-radio>
                                <el-radio value="none">暂不分配</el-radio>
                            </el-radio-group>
                        </el-form-item>
                        <el-form-item v-if="editForm.assign_mode === 'manual'" label="承接顾问" prop="advisor_id">
                            <el-select v-model="editForm.advisor_id" placeholder="请选择在岗未满载顾问" filterable>
                                <el-option
                                    v-for="item in assignableAdvisorOptions"
                                    :key="item.id"
                                    :label="`${item.advisor_name}（${item.load_text}）`"
                                    :value="item.id"
                                />
                            </el-select>
                        </el-form-item>
                    </template>
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

        <!-- 单个 / 批量转移顾问弹窗 -->
        <el-dialog
            v-model="showTransferDialog"
            :title="transferForm.is_batch ? '批量转移顾问' : '转移顾问'"
            width="560px"
            destroy-on-close
            @closed="handleTransferDialogClosed"
        >
            <el-form ref="transferFormRef" :model="transferForm" :rules="transferRules" label-width="100px">
                <el-form-item label="转移对象">
                    <div class="crm-customer-lists__readonly">
                        <template v-if="transferForm.is_batch">
                            已选择 {{ transferForm.customer_ids.length }} 名可转移的活跃客户
                        </template>
                        <template v-else>
                            {{ transferForm.customer_name || '-' }}（当前顾问：{{ transferForm.current_advisor_name || '未分配' }}）
                        </template>
                    </div>
                </el-form-item>
                <el-form-item label="目标顾问" prop="advisor_id">
                    <el-select v-model="transferForm.advisor_id" placeholder="请选择目标顾问" filterable class="w-full">
                        <el-option
                            v-for="item in assignableAdvisorOptions"
                            :key="item.id"
                            :disabled="!transferForm.is_batch && item.id === transferForm.current_advisor_id"
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

        <!-- 复用新增跟进弹窗 -->
        <QuickFollowDialog
            v-model="showFollowDialog"
            :customer="followTargetCustomer"
            @submitted="handleAfterMutation"
        />

        <!-- 客户 360° 详情抽屉 -->
        <CustomerDetailDrawer
            ref="detailDrawerRef"
            v-model="showDetailDrawer"
            :customer-id="drawerCustomerId"
            @edit="handleEdit"
            @transfer="handleTransfer"
            @updated="handleAfterMutation"
        />
    </admin-page-shell>
</template>

<script lang="ts" setup name="crmCustomerLists">
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'

import {
    customerAdd,
    customerAdvisorOptions,
    customerBatchTransfer,
    customerDetail,
    customerEdit,
    customerLists,
    customerOptions,
    customerOverview,
    customerTransferAdvisor
} from '@/api/crm/customer'
import { usePaging } from '@/hooks/usePaging'
import { getMenuPath, getRoutePath } from '@/router'
import feedback from '@/utils/feedback'

import CustomerDetailDrawer from '../components/CustomerDetailDrawer.vue'
import QuickFollowDialog from '../components/QuickFollowDialog.vue'

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

const overview = reactive({
    total: 0,
    today_follow: 0,
    overdue_follow: 0,
    unassigned: 0,
    intention_a: 0,
    month_signed: 0,
    signed: 0
})

const activeQuickView = ref<'all' | 'today' | 'overdue' | 'unassigned' | 'intention_a' | 'signed'>('all')
const weddingDateRange = ref<[string, string] | []>([])

const queryParams = reactive({
    keyword: '',
    customer_status: '' as '' | number,
    intention_level: '' as '' | string,
    source_channel: '' as '' | number,
    advisor_id: '' as '' | number,
    follow_stage: '',
    wedding_date_start: '',
    wedding_date_end: '',
    sort_by: ''
})

watch(weddingDateRange, (val) => {
    queryParams.wedding_date_start = Array.isArray(val) && val[0] ? val[0] : ''
    queryParams.wedding_date_end = Array.isArray(val) && val[1] ? val[1] : ''
})

const optionData = reactive({ ...defaultOptions })
const allAdvisorOptions = ref<AdvisorOption[]>([])
const assignableAdvisorOptions = ref<AdvisorOption[]>([])
const selectedRows = ref<any[]>([])

const showEditDialog = ref(false)
const showTransferDialog = ref(false)
const showFollowDialog = ref(false)
const showDetailDrawer = ref(false)
const drawerCustomerId = ref(0)
const followTargetCustomer = ref<any>(null)
const submitting = ref(false)
const transferSubmitting = ref(false)
const editFormRef = shallowRef<FormInstance>()
const transferFormRef = shallowRef<FormInstance>()
const detailDrawerRef = shallowRef<InstanceType<typeof CustomerDetailDrawer>>()

const selectedTransferableIds = computed(() =>
    selectedRows.value
        .filter((row) => [1, 2].includes(Number(row.customer_status || 0)))
        .map((row) => Number(row.id))
)

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
    source_channel: 3,
    source_detail: '',
    tags: [] as string[],
    customer_status: 1,
    loss_reason: '',
    next_follow_time: '',
    remark: '',
    assign_mode: 'auto' as 'auto' | 'manual' | 'none',
    advisor_id: undefined as number | undefined
})

const createDefaultTransferForm = () => ({
    is_batch: false,
    customer_id: 0,
    customer_ids: [] as number[],
    customer_name: '',
    current_advisor_id: 0,
    current_advisor_name: '',
    advisor_id: undefined as number | undefined,
    reason: ''
})

const editForm = reactive(createDefaultEditForm())
const transferForm = reactive(createDefaultTransferForm())

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

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: customerLists,
    params: queryParams
})

const router = useRouter()
const route = useRoute()

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

const applyQuickView = (view: typeof activeQuickView.value) => {
    activeQuickView.value = view
    queryParams.customer_status = ''
    queryParams.intention_level = ''
    queryParams.follow_stage = ''
    queryParams.sort_by = ''

    if (view === 'today') {
        queryParams.follow_stage = 'today'
        queryParams.sort_by = 'next_follow'
    } else if (view === 'overdue') {
        queryParams.follow_stage = 'overdue'
        queryParams.sort_by = 'next_follow'
    } else if (view === 'unassigned') {
        queryParams.follow_stage = 'unassigned'
    } else if (view === 'intention_a') {
        queryParams.intention_level = 'A'
    } else if (view === 'signed') {
        queryParams.customer_status = 3
    }
    resetPage()
}

const handleResetAll = () => {
    activeQuickView.value = 'all'
    weddingDateRange.value = []
    resetParams()
}

const handleSelectionChange = (rows: any[]) => {
    selectedRows.value = rows || []
}

const openCustomerDrawer = (customerId: number) => {
    drawerCustomerId.value = Number(customerId || 0)
    showDetailDrawer.value = true
}

const resetEditForm = () => {
    Object.assign(editForm, createDefaultEditForm())
}

const resetTransferForm = () => {
    Object.assign(transferForm, createDefaultTransferForm())
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

const loadOverview = async () => {
    const data = await customerOverview()
    if (data) {
        Object.assign(overview, {
            total: Number(data.total || 0),
            today_follow: Number(data.today_follow || 0),
            overdue_follow: Number(data.overdue_follow || 0),
            unassigned: Number(data.unassigned || 0),
            intention_a: Number(data.intention_a || 0),
            month_signed: Number(data.month_signed || 0),
            signed: Number(data.signed || 0)
        })
    }
}

const loadOptions = async () => {
    const data = await customerOptions()
    if (data?.status_options?.length) optionData.status_options = data.status_options
    if (data?.intention_options?.length) optionData.intention_options = data.intention_options
    if (data?.source_options?.length) optionData.source_options = data.source_options
    if (data?.gender_options?.length) optionData.gender_options = data.gender_options
}

const mapAdvisorOptions = (data: any): AdvisorOption[] =>
    Array.isArray(data)
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

const loadAdvisorOptions = async () => {
    const [allData, assignableData] = await Promise.all([
        customerAdvisorOptions({ mode: 'all' }),
        customerAdvisorOptions({ mode: 'assignable' })
    ])
    allAdvisorOptions.value = mapAdvisorOptions(allData)
    assignableAdvisorOptions.value = mapAdvisorOptions(assignableData)
}

const handleAdd = async () => {
    resetEditForm()
    await loadAdvisorOptions()
    showEditDialog.value = true
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
    remark: editForm.remark.trim(),
    assign_mode: editForm.assign_mode,
    advisor_id: editForm.assign_mode === 'manual' ? Number(editForm.advisor_id || 0) : 0
})

const handleSaveEdit = async () => {
    await editFormRef.value?.validate()
    submitting.value = true
    try {
        const payload = buildEditPayload()
        if (editForm.id) {
            await customerEdit(payload)
            ElMessage.success('客户已更新')
        } else {
            await customerAdd(payload)
            ElMessage.success('客户已新增')
        }
        showEditDialog.value = false
        await handleAfterMutation()
    } finally {
        submitting.value = false
    }
}

const handleTransfer = async (row: any) => {
    resetTransferForm()
    await loadAdvisorOptions()
    Object.assign(transferForm, {
        is_batch: false,
        customer_id: Number(row.id || 0),
        customer_ids: [],
        customer_name: String(row.customer_name || ''),
        current_advisor_id: Number(row.advisor_id || 0),
        current_advisor_name: String(row.advisor?.advisor_name || ''),
        advisor_id: undefined,
        reason: ''
    })
    showTransferDialog.value = true
}

const handleOpenBatchTransfer = async () => {
    if (!selectedTransferableIds.value.length) return
    resetTransferForm()
    await loadAdvisorOptions()
    Object.assign(transferForm, {
        is_batch: true,
        customer_id: 0,
        customer_ids: [...selectedTransferableIds.value],
        advisor_id: undefined,
        reason: ''
    })
    showTransferDialog.value = true
}

const handleAddFollowRecord = (row: any) => {
    followTargetCustomer.value = row
    showFollowDialog.value = true
}

const handleRowCommand = (command: string | number | object, row: any) => {
    if (command === 'transfer') {
        void handleTransfer(row)
    } else if (command === 'follow_lists') {
        router.push({ path: followRecordPath.value, query: { customer_id: row.id } })
    } else if (command === 'questionnaire') {
        const hubPath = getMenuPath('page.questionnaire.hub')
        const keyword = String(row.customer_mobile || row.customer_name || '').trim()
        if (hubPath) {
            router.push({
                path: hubPath,
                query: {
                    tab: 'tasks',
                    ...(keyword ? { keyword } : {})
                }
            })
            return
        }
        if (!questionnaireTaskPath.value) return
        router.push({
            path: questionnaireTaskPath.value,
            query: keyword ? { keyword } : undefined
        })
    }
}

const followRecordPath = computed(
    () => getMenuPath('page.crm.follow-record') || getRoutePath('crm.followRecord/lists') || '/crm/follow-record'
)
const questionnaireTaskPath = computed(
    () =>
        getMenuPath('page.questionnaire.hub') ||
        getMenuPath('page.questionnaire.couple_questionnaire_task') ||
        getRoutePath('growth.coupleQuestionnaire/tasks')
)

const handleSubmitTransfer = async () => {
    await transferFormRef.value?.validate()
    const confirmMsg = transferForm.is_batch
        ? `确定将所选 ${transferForm.customer_ids.length} 名客户批量转移给所选顾问？`
        : `确定将客户「${transferForm.customer_name || ''}」转移给所选顾问？`
    await feedback.confirm(confirmMsg)
    transferSubmitting.value = true
    try {
        if (transferForm.is_batch) {
            const res = await customerBatchTransfer({
                customer_ids: transferForm.customer_ids,
                advisor_id: Number(transferForm.advisor_id || 0),
                reason: transferForm.reason.trim()
            })
            ElMessage.success(`批量转移完成，成功 ${Number(res?.transferred || 0)} 名`)
        } else {
            await customerTransferAdvisor({
                customer_id: Number(transferForm.customer_id || 0),
                advisor_id: Number(transferForm.advisor_id || 0),
                reason: transferForm.reason.trim()
            })
            ElMessage.success('顾问已转移')
        }
        showTransferDialog.value = false
        await handleAfterMutation()
    } finally {
        transferSubmitting.value = false
    }
}

const handleAfterMutation = async () => {
    await Promise.all([loadOverview(), loadAdvisorOptions(), getLists()])
    if (showDetailDrawer.value && drawerCustomerId.value > 0) {
        await detailDrawerRef.value?.refresh()
    }
}

const handleEditDialogClosed = () => {
    editFormRef.value?.clearValidate()
}

const handleTransferDialogClosed = () => {
    transferFormRef.value?.clearValidate()
}

const applyRouteQuery = () => {
    const advisorIdParam = route.query.advisor_id
    if (advisorIdParam !== undefined && advisorIdParam !== '') {
        queryParams.advisor_id = Number(advisorIdParam)
    }
}

onActivated(() => {
    applyRouteQuery()
    void loadOverview()
    void loadOptions()
    void loadAdvisorOptions()
    void getLists()
})

applyRouteQuery()
void loadOverview()
void loadOptions()
void loadAdvisorOptions()
void getLists()
</script>

<style lang="scss" scoped>
.crm-customer-lists {
    &__stats {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 12px;
    }

    &__stat {
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding: 12px 14px;
        border: 1px solid var(--el-border-color-light);
        border-radius: 8px;
        background: var(--el-bg-color);
        cursor: pointer;
        transition: border-color 0.2s, background-color 0.2s;

        span {
            color: var(--el-text-color-secondary);
            font-size: 12px;
        }

        strong {
            color: var(--el-text-color-primary);
            font-size: 20px;
            line-height: 1.1;
        }

        &:hover,
        &.is-active {
            border-color: var(--el-color-primary);
            background: var(--el-color-primary-light-9);
        }
    }

    &__toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
        flex-wrap: wrap;
    }

    &__toolbar-left {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    &__stack {
        display: flex;
        flex-direction: column;
        gap: 5px;
        min-width: 0;
        line-height: 1.45;
    }

    &__center-stack {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
        line-height: 1.45;
    }

    &__line {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        min-width: 0;
    }

    &__form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 16px;
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

@media (max-width: 1200px) {
    .crm-customer-lists {
        &__stats {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
}

@media (max-width: 768px) {
    .crm-customer-lists {
        &__stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        &__form-grid {
            grid-template-columns: 1fr;
        }
    }
}
</style>
