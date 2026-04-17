<template>
    <admin-page-shell class="staff-center-order" title="履约订单">
        <search-panel>
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[180px]" label="订单编号">
                    <el-input v-model="queryParams.order_sn" placeholder="输入订单编号" clearable @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item class="w-[150px]" label="联系人">
                    <el-input
                        v-model="queryParams.contact_name"
                        placeholder="输入联系人"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[150px]" label="联系电话">
                    <el-input
                        v-model="queryParams.contact_mobile"
                        placeholder="输入联系电话"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[150px]" label="订单状态">
                    <el-select v-model="queryParams.order_status" placeholder="选择状态" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="待确认" :value="0" />
                        <el-option label="待支付" :value="1" />
                        <el-option label="待服务" :value="2" />
                        <el-option label="服务中" :value="3" />
                        <el-option label="已完成" :value="4" />
                        <el-option label="已评价" :value="5" />
                        <el-option label="已取消" :value="6" />
                        <el-option label="已暂停" :value="7" />
                        <el-option label="退款中" :value="10" />
                        <el-option label="已退款" :value="8" />
                        <el-option label="用户已删除" :value="9" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[320px]" label="创建时间">
                    <el-date-picker
                        v-model="createTimeRange"
                        type="daterange"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                        value-format="YYYY-MM-DD"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                    <el-button type="primary" plain @click="goToBookings">待确认预约项</el-button>
                </el-form-item>
            </el-form>
        </search-panel>

        <el-alert
            class="mt-4"
            title="这里聚焦履约动作与订单进度。退款、线下收款、凭证审核等整单支付处理已收回运营域，不再作为服务人员自助操作。"
            type="info"
            :closable="false"
            show-icon
        />

        <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">待确认</div>
                    <div class="text-2xl font-bold mt-2 text-yellow-500">{{ getStatusCount(0) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">待支付</div>
                    <div class="text-2xl font-bold mt-2 text-orange-500">{{ getStatusCount(1) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">待服务</div>
                    <div class="text-2xl font-bold mt-2 text-blue-500">{{ getStatusCount(2) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">服务中</div>
                    <div class="text-2xl font-bold mt-2 text-purple-500">{{ getStatusCount(3) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已完成</div>
                    <div class="text-2xl font-bold mt-2 text-green-500">{{ getStatusCount(4) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已评价</div>
                    <div class="text-2xl font-bold mt-2 text-emerald-500">{{ getStatusCount(5) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已取消</div>
                    <div class="text-2xl font-bold mt-2 text-gray-500">{{ getStatusCount(6) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已暂停</div>
                    <div class="text-2xl font-bold mt-2 text-amber-500">{{ getStatusCount(7) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">退款中</div>
                    <div class="text-2xl font-bold mt-2 text-cyan-500">{{ getStatusCount(10) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已退款</div>
                    <div class="text-2xl font-bold mt-2 text-red-500">{{ getStatusCount(8) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">用户已删除</div>
                    <div class="text-2xl font-bold mt-2 text-rose-500">{{ getStatusCount(9) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已取消/退款</div>
                    <div class="text-2xl font-bold mt-2 text-gray-500">
                        {{ getStatusCount(6) + getStatusCount(8) }}
                    </div>
                </div>
            </el-card>
        </div>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="订单编号" prop="order_sn" min-width="180" />
                <el-table-column label="用户信息" min-width="150">
                    <template #default="{ row }">
                        <div class="flex items-center" v-if="row.user">
                            <el-avatar :src="row.user.avatar" :size="32" />
                            <span class="ml-2">{{ row.user.nickname }}</span>
                        </div>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="联系信息" min-width="140">
                    <template #default="{ row }">
                        <div>{{ row.contact_name || row.user?.nickname || '-' }}</div>
                        <div class="text-gray-400 text-xs">{{ row.contact_mobile || row.user?.mobile || '-' }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="可见金额" width="140">
                    <template #default="{ row }">
                        <div class="text-red-500 font-bold">¥{{ formatAmount(row.pay_amount) }}</div>
                        <div class="text-gray-400 text-xs">{{ row.payment_mode_desc || '全款支付' }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="支付进度" width="150">
                    <template #default="{ row }">
                        <div>已付：¥{{ formatAmount(row.paid_amount) }}</div>
                        <div class="text-gray-400 text-xs">待付：¥{{ formatAmount(row.unpaid_amount) }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="付款渠道" width="110">
                    <template #default="{ row }">
                        <el-tag :type="Number(row.payment_channel || 1) === 2 ? 'success' : 'primary'" size="small">
                            {{ row.payment_channel_desc || '线上支付' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="订单状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getStatusType(row.order_status)">
                            {{ row.order_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="剩余确认时间" width="160">
                    <template #default="{ row }">
                        <span>{{ getConfirmRemainText(row) }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="超时处理" width="110">
                    <template #default="{ row }">
                        <span>{{ row.confirm_timeout_action_desc || '-' }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="剩余支付时间" width="160">
                    <template #default="{ row }">
                        <span>{{ getPayRemainText(row) }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="支付超时处理" width="110">
                    <template #default="{ row }">
                        <span>{{ row.pay_timeout_action_desc || '-' }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="支付状态" width="100">
                    <template #default="{ row }">
                        <el-tag
                            :type="getPayStatusType(row.pay_status_display_key, row.pay_status)"
                            size="small"
                        >
                            {{ row.pay_status_display_desc || row.pay_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="服务日期" prop="service_date" width="120" />
                <el-table-column label="来源" width="110">
                    <template #default="{ row }">
                        <span>{{ row.source_desc || '-' }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="220" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row)">详情</el-button>
                        <el-button
                            v-if="row.order_status === 0 && row.pending_confirm_count > 0"
                            type="success"
                            link
                            @click="handleConfirm(row)"
                        >
                            确认
                        </el-button>
                        <el-button
                            v-if="Number(row.can_staff_start || 0) === 1"
                            type="warning"
                            link
                            @click="handleStartService(row)"
                        >
                            开始服务
                        </el-button>
                        <el-button
                            v-if="Number(row.can_staff_complete || 0) === 1"
                            type="success"
                            link
                            @click="handleComplete(row)"
                        >
                            完成
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-dialog v-model="detailVisible" title="订单详情" width="820px">
            <div v-if="currentOrder" class="order-detail">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="订单编号">{{ currentOrder.order_sn }}</el-descriptions-item>
                    <el-descriptions-item label="订单状态">
                        <el-tag :type="getStatusType(currentOrder.order_status)">
                            {{ currentOrder.order_status_desc }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="剩余确认时间">{{ getConfirmRemainText(currentOrder) }}</el-descriptions-item>
                    <el-descriptions-item label="超时处理">{{ currentOrder.confirm_timeout_action_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="联系人">{{ getDisplayContactName(currentOrder) }}</el-descriptions-item>
                    <el-descriptions-item label="联系电话">{{ getDisplayContactMobile(currentOrder) }}</el-descriptions-item>
                    <el-descriptions-item label="服务日期">{{ getDisplayServiceDate(currentOrder) }}</el-descriptions-item>
                    <el-descriptions-item label="服务地区">{{ currentOrder.service_region_text || currentOrder.service_address || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="主服务金额">¥{{ formatAmount(currentOrder.service_amount) }}</el-descriptions-item>
                    <el-descriptions-item label="订单总额（本人）">¥{{ formatAmount(currentOrder.total_amount) }}</el-descriptions-item>
                    <el-descriptions-item label="优惠金额（本人）">¥{{ formatAmount(currentOrder.discount_amount) }}</el-descriptions-item>
                    <el-descriptions-item label="应付金额（本人）">¥{{ formatAmount(currentOrder.pay_amount) }}</el-descriptions-item>
                    <el-descriptions-item label="已付金额">
                        <span class="text-red-500 font-bold">¥{{ getDisplayPaidAmount(currentOrder) }}</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="支付模式">{{ currentOrder.payment_mode_desc || '全款支付' }}</el-descriptions-item>
                    <el-descriptions-item label="付款渠道">{{ currentOrder.payment_channel_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="来源">{{ currentOrder.source_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="当前待支付">{{ currentOrder.need_pay_label || '无需支付' }}</el-descriptions-item>
                    <el-descriptions-item label="剩余支付时间">{{ getPayRemainText(currentOrder) }}</el-descriptions-item>
                    <el-descriptions-item label="支付超时处理">{{ currentOrder.pay_timeout_action_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item v-if="Number(currentOrder.deposit_amount || 0) > 0" label="定金金额">¥{{ formatAmount(currentOrder.deposit_amount) }}</el-descriptions-item>
                    <el-descriptions-item v-if="Number(currentOrder.balance_amount || 0) > 0" label="尾款金额">¥{{ formatAmount(currentOrder.balance_amount) }}</el-descriptions-item>
                    <el-descriptions-item label="待付金额">¥{{ formatAmount(currentOrder.unpaid_amount) }}</el-descriptions-item>
                    <el-descriptions-item label="支付方式">{{ currentOrder.pay_type_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="支付状态">
                        {{ currentOrder.pay_status_display_desc || currentOrder.pay_status_desc || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="线下凭证" :span="2">
                        <el-image
                            v-if="currentOrder.pay_voucher"
                            :src="currentOrder.pay_voucher"
                            fit="contain"
                            style="width: 100%; max-height: 260px"
                        />
                        <span v-else>-</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="凭证状态">{{ currentOrder.pay_voucher_status_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="审核备注">{{ currentOrder.pay_voucher_audit_remark || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="用户备注" :span="2">{{ currentOrder.user_remark || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="管理备注" :span="2">{{ currentOrder.admin_remark || '-' }}</el-descriptions-item>
                </el-descriptions>

                <div class="mt-4 text-sm text-gray-400">
                    当前订单按服务人员履约视角展示；退款、线下收款、凭证审核等整单支付操作统一由管理员处理。
                </div>

                <div class="service-project-panel mt-4">
                    <div class="service-project-panel__header">
                        <div>
                            <h4 class="service-project-panel__title">服务项目</h4>
                            <div class="service-project-panel__summary">{{ currentServiceSummaryText }}</div>
                        </div>
                    </div>

                    <div v-if="currentPrimaryItem" class="service-project-main">
                        <div class="service-project-main__header">
                            <div class="service-project-main__copy">
                                <div class="service-project-main__label">主套餐</div>
                                <div class="service-project-main__title">{{ currentPrimaryTitle }}</div>
                            </div>
                            <div class="service-project-main__aside">
                                <div class="service-project-main__price">{{ currentPrimaryPriceText }}</div>
                                <el-tag
                                    size="small"
                                    :type="getItemStatusType(Number(currentPrimaryItem?.item_status || 0))"
                                >
                                    {{ getItemStatusText(Number(currentPrimaryItem?.item_status || 0)) }}
                                </el-tag>
                            </div>
                        </div>

                        <div class="service-project-main__meta-grid">
                            <div
                                v-for="meta in currentPrimaryMetaList"
                                :key="meta.label"
                                class="service-project-main__meta-card"
                            >
                                <span class="service-project-main__meta-label">{{ meta.label }}</span>
                                <strong class="service-project-main__meta-value">{{ meta.value }}</strong>
                            </div>
                        </div>

                        <div v-if="currentPrimaryDescription" class="service-project-main__desc">
                            {{ currentPrimaryDescription }}
                        </div>

                        <div class="service-project-main__address">
                            <span>服务地址</span>
                            <strong>{{ currentPrimaryAddress }}</strong>
                        </div>
                    </div>
                    <div v-else class="service-project-empty">当前订单暂无主套餐信息</div>

                    <div v-if="currentLegacyRows.length" class="service-project-group">
                        <div class="service-project-group__header">
                            <span class="service-project-group__title">旧版兼容服务项</span>
                            <span class="service-project-group__count">{{ currentLegacyRows.length }} 项</span>
                        </div>
                        <div class="service-project-grid">
                            <div
                                v-for="row in currentLegacyRows"
                                :key="row.key"
                                class="service-sub-card service-sub-card--related"
                            >
                                <div class="service-sub-card__header">
                                    <div class="service-sub-card__title-row">
                                        <span class="service-sub-card__title">{{ row.title }}</span>
                                        <el-tag size="small" :type="row.typeTagType">{{ row.typeText }}</el-tag>
                                        <el-tag v-if="row.statusText" size="small" :type="row.statusType || 'info'">
                                            {{ row.statusText }}
                                        </el-tag>
                                    </div>
                                    <span class="service-sub-card__price">{{ row.priceText }}</span>
                                </div>
                                <div v-if="row.metaText" class="service-sub-card__meta">{{ row.metaText }}</div>
                                <div v-if="row.description" class="service-sub-card__desc">{{ row.description }}</div>
                            </div>
                        </div>
                    </div>

                    <div v-if="currentRelatedRows.length" class="service-project-group">
                        <div class="service-project-group__header">
                            <span class="service-project-group__title">协作服务</span>
                            <span class="service-project-group__count">{{ currentRelatedRows.length }} 项</span>
                        </div>
                        <div class="service-project-grid">
                            <div
                                v-for="row in currentRelatedRows"
                                :key="row.key"
                                class="service-sub-card service-sub-card--related"
                            >
                                <div class="service-sub-card__header">
                                    <div class="service-sub-card__title-row">
                                        <span class="service-sub-card__title">{{ row.title }}</span>
                                        <el-tag size="small" :type="row.typeTagType">{{ row.typeText }}</el-tag>
                                        <el-tag
                                            v-if="row.statusText"
                                            size="small"
                                            :type="row.statusType || 'info'"
                                        >
                                            {{ row.statusText }}
                                        </el-tag>
                                    </div>
                                    <span class="service-sub-card__price">{{ row.priceText }}</span>
                                </div>
                                <div v-if="row.metaText" class="service-sub-card__meta">{{ row.metaText }}</div>
                                <div v-if="row.description" class="service-sub-card__desc">{{ row.description }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="text-xs text-gray-400 mt-2">
                        注：订单金额已按当前工作人员视角重算；非本人订单项仅保留必要履约信息，整单支付动作统一由管理员处理。
                    </div>
                </div>

                <div class="mt-4" v-if="currentOrder.payments && currentOrder.payments.length > 0">
                    <h4 class="font-bold mb-2">支付记录</h4>
                    <el-table :data="currentOrder.payments" border size="small">
                        <el-table-column label="流水号" prop="payment_sn" min-width="180" />
                        <el-table-column label="支付阶段" min-width="90">
                            <template #default="{ row }">{{ row.pay_type_desc || '-' }}</template>
                        </el-table-column>
                        <el-table-column label="支付方式" min-width="100">
                            <template #default="{ row }">{{ row.pay_way_desc || '-' }}</template>
                        </el-table-column>
                        <el-table-column label="支付金额" min-width="100">
                            <template #default="{ row }">¥{{ row.pay_amount }}</template>
                        </el-table-column>
                        <el-table-column label="支付状态" min-width="100">
                            <template #default="{ row }">{{ row.pay_status_desc || '-' }}</template>
                        </el-table-column>
                        <el-table-column label="支付时间" prop="pay_time" min-width="160" />
                    </el-table>
                </div>

                <div class="mt-4" v-if="currentOrder.logs && currentOrder.logs.length > 0">
                    <h4 class="font-bold mb-2">操作日志</h4>
                    <el-timeline>
                        <el-timeline-item v-for="log in currentOrder.logs" :key="log.id" :timestamp="log.create_time" placement="top">
                            <span class="text-gray-500">[{{ log.operator_type_desc }}]</span>
                            {{ log.content }}
                        </el-timeline-item>
                    </el-timeline>
                </div>
            </div>
        </el-dialog>

    </admin-page-shell>
</template>

<script setup lang="ts" name="staffCenterOrder">
import { computed, onActivated, onDeactivated, onUnmounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'
import {
    myOrderConfirm,
    myOrderComplete,
    myOrderDetail,
    myOrders,
    myOrderStartService,
    myOrderStatistics
} from '@/api/staff-center'

const router = useRouter()

const queryParams = reactive({
    order_sn: '',
    contact_name: '',
    contact_mobile: '',
    order_status: '',
    start_time: '',
    end_time: ''
})

const createTimeRange = computed<string[]>({
    get: () => {
        if (!queryParams.start_time || !queryParams.end_time) {
            return []
        }
        return [queryParams.start_time, queryParams.end_time]
    },
    set: (value) => {
        queryParams.start_time = value?.[0] || ''
        queryParams.end_time = value?.[1] || ''
    }
})

const statistics = ref<any>({})
const detailVisible = ref(false)
const currentOrder = ref<any>(null)
const countdownNowTs = ref(Date.now())
let countdownTimer: ReturnType<typeof setInterval> | null = null
let countdownRefreshing = false

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: myOrders,
    params: queryParams
})

const getStatistics = async () => {
    statistics.value = (await myOrderStatistics()) || {}
}

const getStatusCount = (status: number) => {
    const item = statistics.value?.status_counts?.find((s: any) => s.status === status)
    if (item) return item.count

    const keyMap: Record<number, string> = {
        0: 'pending_confirm',
        1: 'pending_pay',
        2: 'paid',
        3: 'in_service',
        4: 'completed',
        5: 'reviewed',
        6: 'cancelled',
        7: 'paused',
        8: 'refunded',
        9: 'user_deleted',
        10: 'refunding'
    }

    const key = keyMap[status]
    return key && statistics.value && statistics.value[key] !== undefined ? Number(statistics.value[key] || 0) : 0
}

const getStatusType = (
    status: number
): 'warning' | 'primary' | 'info' | 'success' | 'danger' => {
    const types: Record<number, 'warning' | 'primary' | 'info' | 'success' | 'danger'> = {
        0: 'warning',
        1: 'warning',
        2: 'primary',
        3: 'info',
        4: 'success',
        5: 'success',
        6: 'info',
        7: 'warning',
        8: 'danger',
        9: 'danger',
        10: 'danger'
    }
    return types[status] || 'info'
}

const getPayStatusType = (
    statusKey: string,
    payStatus?: number
): 'warning' | 'primary' | 'info' | 'success' | 'danger' => {
    const keyTypes: Record<string, 'warning' | 'primary' | 'info' | 'success' | 'danger'> = {
        unpaid: 'info',
        deposit_paid: 'warning',
        paid: 'success',
        partial_refund: 'warning',
        full_refund: 'danger'
    }

    const normalizedKey = String(statusKey || '').trim()
    if (normalizedKey && keyTypes[normalizedKey]) {
        return keyTypes[normalizedKey]
    }

    const payStatusTypes: Record<number, 'warning' | 'primary' | 'info' | 'success' | 'danger'> = {
        0: 'info',
        1: 'success',
        2: 'warning',
        3: 'danger'
    }

    return payStatusTypes[Number(payStatus ?? 0)] || 'info'
}

const getDisplayContactName = (order: any) => {
    return order?.contact_name || order?.user?.nickname || '-'
}

const getDisplayContactMobile = (order: any) => {
    return order?.contact_mobile || order?.user?.mobile || '-'
}

const getDisplayServiceDate = (order: any) => {
    if (order?.service_date) return order.service_date
    const dates = (order?.items || [])
        .map((item: any) => item.service_date || item.schedule_date)
        .filter(Boolean)
    return dates.length ? Array.from(new Set(dates)).join('、') : '-'
}

const getDisplayPaidAmount = (order: any) => {
    return Number(order?.paid_amount ?? 0).toFixed(2)
}

const formatAmount = (amount: number | string | undefined) => {
    return Number(amount ?? 0).toFixed(2)
}

const getItemQuantity = (item: any) => Math.max(Number(item?.quantity || 1), 1)

const getItemDisplayAmount = (item: any) => {
    const subtotal = Number(item?.subtotal)
    if (Number.isFinite(subtotal) && subtotal >= 0) {
        return subtotal
    }

    return Math.max(Number(item?.price || 0) * getItemQuantity(item), 0)
}

const formatCountdown = (seconds: number | string | undefined) => {
    const total = Math.max(Number(seconds || 0), 0)
    if (total <= 0) return '已超时，等待系统处理'
    const hours = Math.floor(total / 3600)
    const minutes = Math.floor((total % 3600) / 60)
    const remainSeconds = total % 60
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(remainSeconds).padStart(2, '0')}`
}

const buildExpireAt = (deadlineTime: number | string | undefined, remainSeconds: number | string | undefined) => {
    if (Number(deadlineTime || 0) <= 0) return 0
    return Date.now() + Math.max(Number(remainSeconds || 0), 0) * 1000
}

const syncRowCountdownTargets = (row: any) => {
    if (!row) return
    row.__confirmExpireAt = buildExpireAt(row.confirm_deadline_time, row.confirm_remain_seconds)
    row.__payExpireAt = buildExpireAt(row.pay_deadline_time, row.pay_remain_seconds)
}

const ensureRowCountdownTargets = (row: any) => {
    if (!row) return
    if (Number(row?.confirm_deadline_time || 0) > 0 && typeof row.__confirmExpireAt !== 'number') {
        row.__confirmExpireAt = buildExpireAt(row.confirm_deadline_time, row.confirm_remain_seconds)
    }
    if (Number(row?.pay_deadline_time || 0) > 0 && typeof row.__payExpireAt !== 'number') {
        row.__payExpireAt = buildExpireAt(row.pay_deadline_time, row.pay_remain_seconds)
    }
}

const getLiveRemainSeconds = (row: any, deadlineField: 'confirm_deadline_time' | 'pay_deadline_time', expireField: '__confirmExpireAt' | '__payExpireAt') => {
    countdownNowTs.value
    ensureRowCountdownTargets(row)
    if (Number(row?.[deadlineField] || 0) <= 0) return 0
    const expireAt = Number(row?.[expireField] || 0)
    if (expireAt <= 0) return 0
    return Math.max(Math.ceil((expireAt - countdownNowTs.value) / 1000), 0)
}

const hasActiveCountdown = (row: any) =>
    getLiveRemainSeconds(row, 'confirm_deadline_time', '__confirmExpireAt') > 0 ||
    getLiveRemainSeconds(row, 'pay_deadline_time', '__payExpireAt') > 0

const hasExpiredCountdown = (row: any) =>
    (Number(row?.confirm_deadline_time || 0) > 0 &&
        getLiveRemainSeconds(row, 'confirm_deadline_time', '__confirmExpireAt') <= 0) ||
    (Number(row?.pay_deadline_time || 0) > 0 &&
        getLiveRemainSeconds(row, 'pay_deadline_time', '__payExpireAt') <= 0)

const clearCountdownTimer = () => {
    if (countdownTimer) {
        clearInterval(countdownTimer)
        countdownTimer = null
    }
}

const refreshCountdownDrivenData = async () => {
    if (countdownRefreshing) return
    countdownRefreshing = true
    try {
        await Promise.all([getLists(), getStatistics()])
        if (detailVisible.value && Number(currentOrder.value?.id || 0) > 0) {
            currentOrder.value = await myOrderDetail({ id: currentOrder.value.id })
        }
    } finally {
        countdownRefreshing = false
    }
}

const hasAnyActiveCountdown = () =>
    (pager.lists || []).some((row: any) => hasActiveCountdown(row)) || hasActiveCountdown(currentOrder.value)

const hasAnyExpiredCountdown = () =>
    (pager.lists || []).some((row: any) => hasExpiredCountdown(row)) || hasExpiredCountdown(currentOrder.value)

const startCountdownTimer = () => {
    clearCountdownTimer()
    countdownNowTs.value = Date.now()

    if (hasAnyExpiredCountdown()) {
        refreshCountdownDrivenData()
        return
    }

    if (!hasAnyActiveCountdown()) {
        return
    }

    countdownTimer = setInterval(() => {
        countdownNowTs.value = Date.now()

        if (hasAnyExpiredCountdown()) {
            clearCountdownTimer()
            refreshCountdownDrivenData()
            return
        }

        if (!hasAnyActiveCountdown()) {
            clearCountdownTimer()
        }
    }, 1000)
}

const getConfirmRemainText = (row: any) => {
    if (Number(row?.confirm_deadline_time || 0) <= 0) return '-'
    return formatCountdown(getLiveRemainSeconds(row, 'confirm_deadline_time', '__confirmExpireAt'))
}

const getPayRemainText = (row: any) => {
    if (Number(row?.pay_deadline_time || 0) <= 0) return '-'
    return formatCountdown(getLiveRemainSeconds(row, 'pay_deadline_time', '__payExpireAt'))
}

const isMaskedItem = (item: any) => {
    return item?.package_name === '--'
}

const getItemStatusText = (status: number) => {
    const map: Record<number, string> = { 0: '待服务', 1: '服务中', 2: '已完成', 3: '已取消' }
    return map[status] || '-'
}

const getItemStatusType = (status: number): 'warning' | 'primary' | 'success' | 'info' | 'danger' => {
    const map: Record<number, 'warning' | 'primary' | 'success' | 'info' | 'danger'> = {
        0: 'warning',
        1: 'primary',
        2: 'success',
        3: 'info'
    }
    return map[status] || 'info'
}

type ServiceDetailRow = {
    key: string
    title: string
    typeText: string
    typeTagType: 'warning' | 'success' | 'primary' | 'info'
    description: string
    metaText: string
    priceText: string
    statusText?: string
    statusType?: 'warning' | 'primary' | 'success' | 'info' | 'danger'
}

const currentOrderItems = computed(() => {
    const items = currentOrder.value?.items
    return Array.isArray(items) ? items : []
})

const currentPrimaryItem = computed(() =>
    currentOrderItems.value.find((item: any) => Number(item?.item_type || 1) === 1) || null
)

const currentPrimaryMasked = computed(() => isMaskedItem(currentPrimaryItem.value))

const currentPrimaryTitle = computed(() => {
    if (currentPrimaryMasked.value) return '已脱敏服务项'
    return (
        String(
            currentPrimaryItem.value?.package_name || currentPrimaryItem.value?.package?.name || '待确认主套餐'
        ).trim() || '待确认主套餐'
    )
})

const currentPrimaryPriceText = computed(() =>
    currentPrimaryMasked.value ? '--' : `¥${formatAmount(getItemDisplayAmount(currentPrimaryItem.value))}`
)

const currentPrimaryDescription = computed(() => {
    if (currentPrimaryMasked.value) {
        return '仅展示履约结构，具体服务内容已脱敏。'
    }

    return String(
        currentPrimaryItem.value?.package_description || currentPrimaryItem.value?.package?.description || ''
    ).trim()
})

const currentPrimaryMetaList = computed(() => [
    {
        label: '服务人员',
        value: String(currentPrimaryItem.value?.staff_name || currentPrimaryItem.value?.staff?.name || '待分配服务人员').trim() || '待分配服务人员'
    },
    {
        label: '服务日期',
        value: currentPrimaryItem.value?.service_date || currentOrder.value?.service_date || '-'
    },
    {
        label: '服务地区',
        value: currentOrder.value?.service_region_text || currentOrder.value?.service_address || '-'
    },
    {
        label: '数量',
        value: currentPrimaryMasked.value ? '--' : `x${getItemQuantity(currentPrimaryItem.value)}`
    }
])

const currentPrimaryAddress = computed(() =>
    currentPrimaryMasked.value ? '--' : currentOrder.value?.service_address || currentOrder.value?.service_region_text || '-'
)

const getDetailItemDescription = (item: any, masked = false) => {
    if (masked) {
        return '仅展示履约结构，具体明细已脱敏。'
    }

    const parts: string[] = []
    const description = String(item?.package_description || item?.package?.description || '').trim()
    if (description) {
        parts.push(description)
    }

    const quantity = getItemQuantity(item)
    if (quantity > 1) {
        parts.push(`数量 x${quantity}`)
    }

    return parts.join(' · ')
}

const buildServiceRowKey = (
    kind: string,
    title: string,
    amount: number,
    quantity: number,
    extra = ''
) => `${kind}:${String(title || '').trim()}:${formatAmount(amount)}:${quantity}:${extra}`

const currentRelatedRows = computed<ServiceDetailRow[]>(() =>
    currentOrderItems.value
        .filter((item: any) => Number(item?.item_type || 1) === 3)
        .map((item: any) => {
            const masked = isMaskedItem(item)
            const amount = getItemDisplayAmount(item)
            const quantity = getItemQuantity(item)
            const staffName = String(item?.staff_name || item?.staff?.name || '').trim()
            const roleLabel = String(item?.item_meta?.role_label || '').trim() || '协作服务'
            return {
                key: buildServiceRowKey('related', `${roleLabel}:${staffName}`, amount, quantity, item?.service_date || ''),
                title: masked ? '已脱敏协作服务' : staffName ? `${roleLabel} · ${staffName}` : roleLabel,
                typeText: '协作服务',
                typeTagType: 'success',
                description: getDetailItemDescription(item, masked),
                metaText: masked ? '已脱敏' : [item?.service_date, `数量 x${quantity}`].filter(Boolean).join(' · '),
                priceText: masked ? '--' : `¥${formatAmount(amount)}`,
                statusText: getItemStatusText(Number(item?.item_status || 0)),
                statusType: getItemStatusType(Number(item?.item_status || 0))
            }
        })
)

const currentLegacyRows = computed<ServiceDetailRow[]>(() =>
    currentOrderItems.value
        .filter((item: any) => Number(item?.item_type || 1) === 2)
        .map((item: any) => {
            const masked = isMaskedItem(item)
            const amount = getItemDisplayAmount(item)
            const quantity = getItemQuantity(item)
            const title = String(item?.item_meta?.label || item?.package_name || '历史服务项').trim() || '历史服务项'
            return {
                key: buildServiceRowKey('legacy', title, amount, quantity, item?.service_date || ''),
                title: masked ? '已脱敏历史服务项' : title,
                typeText: '历史兼容',
                typeTagType: 'info',
                description: masked ? '旧版订单兼容展示，具体历史明细已脱敏。' : getDetailItemDescription(item, false),
                metaText: masked ? '已脱敏' : [item?.service_date, `数量 x${quantity}`].filter(Boolean).join(' · '),
                priceText: masked ? '--' : `¥${formatAmount(amount)}`,
                statusText: getItemStatusText(Number(item?.item_status || 0)),
                statusType: getItemStatusType(Number(item?.item_status || 0))
            }
        })
)

const currentServiceSummaryText = computed(() => {
    const parts = [currentPrimaryItem.value ? '1 个主套餐' : '0 个主套餐']

    if (currentLegacyRows.value.length) {
        parts.push(`${currentLegacyRows.value.length} 个旧版兼容项`)
    }

    if (currentRelatedRows.value.length) {
        parts.push(`${currentRelatedRows.value.length} 个协作服务`)
    }

    return parts.join(' · ')
})

const openOrderDetail = async (id: number) => {
    if (!id) return
    currentOrder.value = await myOrderDetail({ id })
    detailVisible.value = true
}

const handleDetail = async (row: any) => {
    await openOrderDetail(Number(row.id || 0))
}

const handleConfirm = async (row: any) => {
    await feedback.confirm('确认该订单后，将确认当前服务人员名下的全部待确认项目，是否继续？')
    await myOrderConfirm({ id: row.id })
    feedback.msgSuccess('确认成功')
    getLists()
    getStatistics()
}

const goToBookings = () => router.push('/staff_center/booking')

const handleStartService = async (row: any) => {
    await feedback.confirm('确定要开始服务吗？')
    await myOrderStartService({ id: row.id })
    feedback.msgSuccess('操作成功')
    getLists()
    getStatistics()
}

const handleComplete = async (row: any) => {
    await feedback.confirm('确定要完成订单吗？')
    await myOrderComplete({ id: row.id })
    feedback.msgSuccess('操作成功')
    getLists()
    getStatistics()
}

onActivated(() => {
    getLists()
    getStatistics()
    startCountdownTimer()
})

onDeactivated(() => {
    clearCountdownTimer()
})

onUnmounted(() => {
    clearCountdownTimer()
})

watch(
    () => pager.lists,
    (lists) => {
        ;(lists || []).forEach((row: any) => syncRowCountdownTargets(row))
        startCountdownTimer()
    },
    { deep: false }
)

watch(
    currentOrder,
    (value) => {
        syncRowCountdownTargets(value)
        startCountdownTimer()
    },
    { deep: false }
)

getLists()
getStatistics()
</script>

<style scoped>
.order-detail :deep(.el-descriptions__label) {
    width: 100px;
}

.service-project-panel {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.service-project-panel__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.service-project-panel__title {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
}

.service-project-panel__summary {
    margin-top: 4px;
    font-size: 12px;
    color: #6b7280;
}

.service-project-main {
    border: 1px solid #dbeafe;
    border-radius: 18px;
    padding: 18px;
    background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.service-project-main__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}

.service-project-main__copy {
    min-width: 0;
    flex: 1;
}

.service-project-main__label {
    display: inline-flex;
    align-items: center;
    min-height: 26px;
    padding: 0 10px;
    border-radius: 999px;
    background: #e0f2fe;
    color: #1d4ed8;
    font-size: 12px;
    font-weight: 600;
}

.service-project-main__title {
    margin-top: 10px;
    font-size: 20px;
    line-height: 1.5;
    font-weight: 700;
    color: #111827;
}

.service-project-main__aside {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
}

.service-project-main__price {
    font-size: 24px;
    line-height: 1.2;
    font-weight: 700;
    color: #2563eb;
}

.service-project-main__meta-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 12px;
}

.service-project-main__meta-card {
    padding: 12px 14px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.92);
    border: 1px solid #e5edf9;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.service-project-main__meta-label {
    font-size: 12px;
    color: #9ca3af;
}

.service-project-main__meta-value {
    font-size: 14px;
    line-height: 1.6;
    color: #1f2937;
    word-break: break-word;
}

.service-project-main__desc {
    padding: 14px 16px;
    border-radius: 14px;
    background: #ffffff;
    border: 1px dashed #d9e7fb;
    font-size: 13px;
    line-height: 1.8;
    color: #6b7280;
    white-space: pre-wrap;
}

.service-project-main__address {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 13px;
    line-height: 1.8;
    color: #6b7280;
}

.service-project-main__address strong {
    color: #374151;
    font-weight: 600;
}

.service-project-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.service-project-group__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.service-project-group__title {
    font-size: 14px;
    font-weight: 700;
    color: #1f2937;
}

.service-project-group__count {
    font-size: 12px;
    color: #9ca3af;
}

.service-project-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 12px;
}

.service-sub-card {
    border: 1px solid #e7edf5;
    border-radius: 16px;
    padding: 16px;
    background: #fff;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.service-sub-card--related {
    border-color: #d7f0e2;
    background: linear-gradient(180deg, #f6fffa 0%, #ffffff 100%);
}

.service-sub-card__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
}

.service-sub-card__title-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    min-width: 0;
    flex: 1;
}

.service-sub-card__title {
    font-size: 15px;
    line-height: 1.5;
    font-weight: 600;
    color: #111827;
    word-break: break-word;
}

.service-sub-card__price {
    flex-shrink: 0;
    font-size: 18px;
    line-height: 1.4;
    font-weight: 700;
    color: #2563eb;
}

.service-sub-card__meta {
    font-size: 12px;
    line-height: 1.7;
    color: #6b7280;
}

.service-sub-card__desc {
    font-size: 13px;
    line-height: 1.8;
    color: #4b5563;
    white-space: pre-wrap;
}

.service-project-empty {
    border-radius: 14px;
    padding: 18px 20px;
    background: #f9fafb;
    border: 1px dashed #e5e7eb;
    font-size: 13px;
    color: #9ca3af;
}

.service-project-empty--sub {
    padding: 14px 16px;
}
</style>
