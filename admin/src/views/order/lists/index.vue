<template>
    <admin-page-shell class="order-lists" title="订单管理">
        <!-- 顶部搜索 -->
        <template #search>
            <order-search-form
                :params="queryParams"
                @search="resetPage"
                @reset="resetParams"
                @open-offline-drawer="handleOpenOfflineDrawer"
            />
        </template>

        <!-- 状态指标统计卡片 -->
        <order-status-metrics
            :statistics="statistics"
            :active-status="queryParams.order_status"
            @select-status="handleSelectStatus"
        />

        <!-- 订单主表格 -->
        <div class="admin-page-section mt-4">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="订单编号" prop="order_sn" min-width="180" />
                <el-table-column label="用户信息" min-width="150">
                    <template #default="{ row }">
                        <div class="flex items-center" v-if="row.user">
                            <el-avatar :src="row.user.avatar" :size="32" />
                            <span class="ml-2">{{ row.user.nickname }}</span>
                        </div>
                        <span v-else>{{ isOfflineOrder(row) ? '临时客户' : '-' }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="联系信息" min-width="140">
                    <template #default="{ row }">
                        <div>{{ row.contact_name }}</div>
                        <div class="text-gray-400 text-xs">{{ row.contact_mobile }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="订单金额" width="140">
                    <template #default="{ row }">
                        <div class="text-red-500 font-bold">¥{{ row.pay_amount }}</div>
                        <div class="text-gray-400 text-xs">{{ row.payment_mode_desc || '全款支付' }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="支付进度" width="160">
                    <template #default="{ row }">
                        <div>已付：¥{{ row.paid_amount || '0.00' }}</div>
                        <div class="text-gray-400 text-xs">待付：¥{{ row.unpaid_amount || '0.00' }}</div>
                        <div class="mt-1 flex gap-1 flex-wrap">
                            <el-tag size="small" :type="row.payment_mode === 'deposit' ? 'warning' : 'info'">
                                {{ row.payment_mode_desc || '全款支付' }}
                            </el-tag>
                            <el-tag v-if="row.payment_mode === 'deposit'" size="small" :type="row.deposit_paid ? 'success' : 'info'">
                                {{ row.deposit_paid ? '定金已付' : '定金未付' }}
                            </el-tag>
                            <el-tag v-if="row.payment_mode === 'deposit'" size="small" :type="row.balance_paid ? 'success' : 'info'">
                                {{ row.balance_paid ? '尾款已付' : '尾款未付' }}
                            </el-tag>
                        </div>
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
                        <el-tag :type="getOrderStatusTagType(row.order_status)">
                            {{ row.order_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="剩余确认时间" width="160">
                    <template #default="{ row }">
                        <span>{{ getConfirmRemainText(row) }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="超时处理" width="120">
                    <template #default="{ row }">
                        <span>{{ row.confirm_timeout_action_desc || '-' }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="剩余支付时间" width="160">
                    <template #default="{ row }">
                        <span>{{ getPayRemainText(row) }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="支付超时处理" width="120">
                    <template #default="{ row }">
                        <span>{{ row.pay_timeout_action_desc || '-' }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="支付状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getPayStatusType(row.pay_status_display_key)" size="small">
                            {{ row.pay_status_display_desc || row.pay_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="服务日期" prop="service_date" width="110" />
                <el-table-column label="来源" width="110">
                    <template #default="{ row }">
                        <div class="flex flex-wrap gap-1">
                            <el-tag v-if="Number(row.payment_channel || 1) === 2" size="small" type="success">线下付款</el-tag>
                            <span>{{ row.source_desc }}</span>
                            <el-tag v-if="row.receipt_pending" type="warning" size="small">收款待审核</el-tag>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="260" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row)">详情</el-button>
                        <el-button
                            type="warning"
                            link
                            :loading="confirmLetterOpeningId === Number(row.id || 0)"
                            @click="handleConfirmLetter(row)"
                        >
                            档期海报
                        </el-button>
                        <el-button
                            v-if="canAuditVoucher(row)"
                            type="warning"
                            link
                            @click="handleAuditVoucher(row)"
                        >
                            审核凭证
                        </el-button>
                        <el-dropdown
                            trigger="click"
                            class="inline-block ml-2 align-middle"
                            @command="(cmd: string) => handleOrderCommand(cmd, row)"
                        >
                            <el-button type="primary" link>
                                更多<icon name="el-icon-ArrowDown" class="ml-0.5" />
                            </el-button>
                            <template #dropdown>
                                <el-dropdown-menu>
                                    <el-dropdown-item command="questionnaire">问卷任务</el-dropdown-item>
                                    <el-dropdown-item
                                        v-if="row.order_status === 0 && row.pending_confirm_count > 0"
                                        command="confirm"
                                    >
                                        确认订单
                                    </el-dropdown-item>
                                    <el-dropdown-item
                                        v-if="canConfirmOfflinePay(row)"
                                        command="confirmOfflinePay"
                                    >
                                        确认线下收款
                                    </el-dropdown-item>
                                    <el-dropdown-item
                                        v-if="Number(row.can_direct_reschedule || 0) === 1"
                                        command="reschedule"
                                    >
                                        改期
                                    </el-dropdown-item>
                                    <el-dropdown-item
                                        v-if="row.order_status === 2"
                                        command="startService"
                                    >
                                        开始服务
                                    </el-dropdown-item>
                                    <el-dropdown-item
                                        v-if="row.order_status === 3"
                                        command="complete"
                                    >
                                        完成服务
                                    </el-dropdown-item>
                                    <el-dropdown-item
                                        v-if="row.can_admin_refund"
                                        command="refund"
                                        divided
                                    >
                                        退款
                                    </el-dropdown-item>
                                    <el-dropdown-item
                                        v-if="row.can_admin_cancel"
                                        command="cancel"
                                        divided
                                    >
                                        取消订单
                                    </el-dropdown-item>
                                    <el-dropdown-item
                                        v-if="row.order_status === 9"
                                        command="delete"
                                        divided
                                    >
                                        删除
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

        <!-- 线下建单抽屉 -->
        <offline-order-drawer
            v-model="offlineDrawerVisible"
            :add-offline="orderAddOffline"
            :estimate-offline="orderEstimateOffline"
            :offline-main-packages="orderOfflineMainPackages"
            :offline-role-candidates="orderOfflineRoleCandidates"
            :get-addon-config="staffGetAddonConfig"
            :load-staff-options="staffAll"
            @created="handleOfflineCreated"
        />

        <!-- 订单详情抽屉 (原 800px 弹窗已升级为专属右侧抽屉，解耦 1000+ 行服务卡片) -->
        <order-detail-drawer
            v-model="detailVisible"
            :order="currentOrder"
            @refund="handleRefund"
            @refresh="handleModalSuccess"
        />

        <!-- 档期确认海报生成对话框 -->
        <ConfirmLetterModal v-model="confirmLetterVisible" :order-data="currentOrder" />

        <!-- 独立解耦的业务弹窗池 -->
        <voucher-audit-modal
            v-model="auditVisible"
            :order-data="auditForm"
            @success="handleModalSuccess"
        />

        <offline-pay-confirm-modal
            v-model="confirmPayVisible"
            :pay-data="confirmPayForm"
            @success="handleModalSuccess"
        />

        <order-cancel-modal
            v-model="cancelVisible"
            :order-id="cancelForm.id"
            :order-sn="cancelForm.order_sn"
            @success="handleModalSuccess"
        />

        <order-refund-modal
            v-model="refundVisible"
            :refund-data="refundForm"
            @success="handleRefundSuccess"
        />

        <reschedule-modal
            v-model="directRescheduleVisible"
            :order-data="directRescheduleForm"
            @success="handleRescheduleSuccess"
        />
    </admin-page-shell>
</template>

<script lang="ts" setup name="orderLists">
import { onActivated, onDeactivated, onUnmounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import {
    orderAddOffline,
    orderComplete,
    orderConfirm,
    orderDelete,
    orderDetail,
    orderEstimateOffline,
    orderLists,
    orderOfflineMainPackages,
    orderOfflineRoleCandidates,
    orderStartService,
    orderStatistics
} from '@/api/order'
import { staffAll, staffGetAddonConfig } from '@/api/staff'
import OfflineOrderDrawer from '@/components/order/offline-order-drawer.vue'
import { getOrderStatusTagType } from '@/enums/orderEnums'
import { usePaging } from '@/hooks/usePaging'
import { getMenuPath, getRoutePath } from '@/router'
import feedback from '@/utils/feedback'

import ConfirmLetterModal from './components/dialogs/ConfirmLetterModal.vue'
import OfflinePayConfirmModal from './components/dialogs/OfflinePayConfirmModal.vue'
import OrderCancelModal from './components/dialogs/OrderCancelModal.vue'
import OrderRefundModal from './components/dialogs/OrderRefundModal.vue'
import RescheduleModal from './components/dialogs/RescheduleModal.vue'
import VoucherAuditModal from './components/dialogs/VoucherAuditModal.vue'
import OrderDetailDrawer from './components/OrderDetailDrawer.vue'
import OrderSearchForm from './components/OrderSearchForm.vue'
import OrderStatusMetrics from './components/OrderStatusMetrics.vue'

const route = useRoute()
const router = useRouter()

const queryParams = reactive({
    order_sn: '',
    contact_name: '',
    contact_mobile: '',
    order_status: '' as string | number,
    payment_mode: '',
    deposit_paid: '' as string | number,
    balance_paid: '' as string | number,
    has_voucher_pending: '' as string | number,
    start_time: '',
    end_time: ''
})

const statistics = ref<Record<string, any>>({})
const detailVisible = ref(false)
const currentOrder = ref<any>(null)
const offlineDrawerVisible = ref(false)

// 档期海报状态
const confirmLetterVisible = ref(false)
const confirmLetterOpeningId = ref(0)

// 倒计时刷新
const countdownNowTs = ref(Date.now())
let countdownTimer: ReturnType<typeof setInterval> | null = null
let countdownRefreshing = false

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: orderLists,
    params: queryParams
})

// 弹窗状态与表单
const auditVisible = ref(false)
const auditForm = reactive({
    id: 0,
    receipt_id: 0,
    order_sn: '',
    pay_amount: 0 as number | string,
    voucher: '',
    phase_desc: '',
    collection_owner: undefined as number | undefined,
    remark: ''
})

const confirmPayVisible = ref(false)
const confirmPayForm = reactive({
    id: 0,
    order_sn: '',
    pay_type: 3 as 2 | 3,
    pay_amount: 0 as number | string,
    pay_label: '全款',
    collection_owner: undefined as 1 | 2 | undefined,
    voucher: ''
})

const cancelVisible = ref(false)
const cancelForm = reactive({
    id: 0,
    order_sn: ''
})

const refundVisible = ref(false)
const refundForm = reactive({
    order_id: 0,
    order_sn: '',
    order_status: 0,
    refundable_amount: 0 as number | string
})

const directRescheduleVisible = ref(false)
const directRescheduleForm = reactive({
    id: 0,
    order_sn: '',
    service_date: ''
})

// 状态与计算
const isOfflineOrder = (row: any) => [3, 4].includes(Number(row?.source || 0)) && !row?.user

const getPayStatusType = (statusKey: string): 'warning' | 'primary' | 'info' | 'success' | 'danger' => {
    const types: Record<string, 'warning' | 'primary' | 'info' | 'success' | 'danger'> = {
        unpaid: 'info',
        deposit_paid: 'warning',
        paid: 'success',
        partial_refund: 'warning',
        full_refund: 'danger'
    }
    return types[String(statusKey || '').trim()] || 'info'
}

const canAuditVoucher = (row: any) =>
    !!row?.receipt_pending ||
    (Number(row?.order_status || 0) === 1 &&
        Number(row?.payment_channel || 1) === 2 &&
        !!row?.pay_voucher &&
        Number(row?.pay_voucher_status) === 0)

const canConfirmOfflinePay = (row: any) =>
    !row?.receipt_pending &&
    Number(row?.order_status || 0) === 1 &&
    Number(row?.payment_channel || 1) === 2 &&
    !(row?.pay_voucher && Number(row?.pay_voucher_status) === 0)

// 倒计时核心算法
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

const getLiveRemainSeconds = (
    row: any,
    deadlineField: 'confirm_deadline_time' | 'pay_deadline_time',
    expireField: '__confirmExpireAt' | '__payExpireAt'
) => {
    ensureRowCountdownTargets(row)
    if (Number(row?.[deadlineField] || 0) <= 0) return 0
    const expireAt = Number(row?.[expireField] || 0)
    if (expireAt <= 0) return 0
    return Math.max(Math.ceil((expireAt - countdownNowTs.value) / 1000), 0)
}

const formatCountdown = (totalSeconds: number) => {
    const total = Math.max(Number(totalSeconds || 0), 0)
    if (total <= 0) return '已超时，等待系统处理'
    const hours = Math.floor(total / 3600)
    const minutes = Math.floor((total % 3600) / 60)
    const remainSeconds = total % 60
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(remainSeconds).padStart(2, '0')}`
}

const getConfirmRemainText = (row: any) => {
    if (Number(row?.confirm_deadline_time || 0) <= 0) return '-'
    return formatCountdown(getLiveRemainSeconds(row, 'confirm_deadline_time', '__confirmExpireAt'))
}

const getPayRemainText = (row: any) => {
    if (Number(row?.pay_deadline_time || 0) <= 0) return '-'
    return formatCountdown(getLiveRemainSeconds(row, 'pay_deadline_time', '__payExpireAt'))
}

const hasActiveCountdown = (row: any) =>
    getLiveRemainSeconds(row, 'confirm_deadline_time', '__confirmExpireAt') > 0 ||
    getLiveRemainSeconds(row, 'pay_deadline_time', '__payExpireAt') > 0

const hasExpiredCountdown = (row: any) =>
    (Number(row?.confirm_deadline_time || 0) > 0 && getLiveRemainSeconds(row, 'confirm_deadline_time', '__confirmExpireAt') <= 0) ||
    (Number(row?.pay_deadline_time || 0) > 0 && getLiveRemainSeconds(row, 'pay_deadline_time', '__payExpireAt') <= 0)

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
            await openOrderDetail(Number(currentOrder.value.id))
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

// 档期海报：先拉取订单详情（含候选服务人员/模板），再交给确认函弹窗渲染
const handleConfirmLetter = async (row: any) => {
    const orderId = Number(row?.id || 0)
    if (!orderId) return
    confirmLetterOpeningId.value = orderId
    try {
        const res = await orderDetail({ id: orderId })
        currentOrder.value = res
        confirmLetterVisible.value = true
    } finally {
        confirmLetterOpeningId.value = 0
    }
}

// 统计与事件
const getStatistics = async () => {
    const res = await orderStatistics()
    statistics.value = res || {}
}

const handleSelectStatus = (status: number) => {
    queryParams.order_status = queryParams.order_status === status ? '' : status
    resetPage()
}

const handleOpenOfflineDrawer = () => {
    offlineDrawerVisible.value = true
}

const handleOfflineCreated = () => {
    getLists()
    getStatistics()
}

const clearDetailQuery = () => {
    if (!route.query.detail_id) return
    const nextQuery = { ...route.query }
    delete nextQuery.detail_id
    router.replace({ path: route.path, query: nextQuery })
}

const openOrderDetail = async (id: number, clearQuery = false) => {
    if (!id) return
    const res = await orderDetail({ id })
    currentOrder.value = res
    detailVisible.value = true
    if (clearQuery) clearDetailQuery()
}

const refreshCurrentOrderDetail = async (orderId: number) => {
    if (!detailVisible.value || Number(currentOrder.value?.id || 0) !== Number(orderId || 0)) {
        return
    }
    currentOrder.value = await orderDetail({ id: orderId })
}

const handleDetail = async (row: any) => {
    await openOrderDetail(Number(row.id))
}

const handleAuditVoucher = (row: any) => {
    auditForm.id = Number(row.id || 0)
    auditForm.order_sn = row.order_sn || ''
    const pendingReceipt = row.pending_receipt || null
    auditForm.receipt_id = Number(pendingReceipt?.id || 0)
    auditForm.pay_amount = Number(pendingReceipt?.amount || row.need_pay_amount || row.pay_amount || 0)
    auditForm.voucher = pendingReceipt?.pay_voucher || row.pay_voucher || ''
    auditForm.phase_desc = pendingReceipt?.phase_desc || ''
    auditForm.collection_owner = pendingReceipt?.collection_owner || (row.collection_owner ? Number(row.collection_owner) : undefined)
    auditForm.remark = ''
    auditVisible.value = true
}

const handleConfirmOfflinePay = (row: any) => {
    const suggestedAmount = Number(row.need_pay_amount || row.pay_amount || 0)
    confirmPayForm.id = Number(row.id || 0)
    confirmPayForm.order_sn = row.order_sn || ''
    if (row.need_pay === 'deposit') {
        feedback.msgError('定金必须通过微信支付')
        return
    }
    confirmPayForm.pay_type = row.need_pay === 'balance' ? 2 : 3
    confirmPayForm.pay_amount = suggestedAmount
    confirmPayForm.collection_owner = undefined
    confirmPayForm.pay_label = row.need_pay === 'deposit' ? '定金' : row.need_pay === 'balance' ? '尾款' : '全款'
    confirmPayForm.voucher = ''
    confirmPayVisible.value = true
}

const handleDirectReschedule = (row: any) => {
    directRescheduleForm.id = Number(row.id || 0)
    directRescheduleForm.order_sn = row.order_sn || ''
    directRescheduleForm.service_date = row.service_date || ''
    directRescheduleVisible.value = true
}

const handleStartService = async (row: any) => {
    await feedback.confirm('确定要开始服务吗？')
    await orderStartService({ id: row.id })
    feedback.msgSuccess('操作成功')
    getLists()
    getStatistics()
}

const handleComplete = async (row: any) => {
    await feedback.confirm('确定要完成订单吗？')
    await orderComplete({ id: row.id })
    feedback.msgSuccess('操作成功')
    getLists()
    getStatistics()
}

const handleRefund = (row: any) => {
    const refundableAmount = Number(row.refundable_amount || 0)
    refundForm.order_id = Number(row.id || row.order_id || 0)
    refundForm.order_sn = row.order_sn || ''
    refundForm.order_status = Number(row.order_status || 0)
    refundForm.refundable_amount = refundableAmount
    refundVisible.value = true
}

const handleCancel = (row: any) => {
    cancelForm.id = Number(row.id || 0)
    cancelForm.order_sn = row.order_sn || ''
    cancelVisible.value = true
}

const handleDelete = async (row: any) => {
    await feedback.confirm('确认彻底删除该订单吗？此操作仅用于用户已删除订单。')
    await orderDelete({ id: row.id })
    feedback.msgSuccess('订单已删除')
    getLists()
    getStatistics()
}

const handleConfirm = async (row: any) => {
    await feedback.confirm('确认后将处理当前账号可确认的待确认服务项，是否继续？')
    await orderConfirm({ id: row.id })
    feedback.msgSuccess('确认成功')
    await Promise.all([getLists(), getStatistics(), refreshCurrentOrderDetail(Number(row.id || 0))])
}

const handleQuestionnaireTasks = (row: any) => {
    const hubPath = getMenuPath('page.questionnaire.hub')
    const keyword = String(row.order_sn || row.id || '').trim()
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
    const targetPath =
        getMenuPath('page.questionnaire.couple_questionnaire_task') ||
        getRoutePath('growth.coupleQuestionnaire/tasks') ||
        '/couple-questionnaire/tasks'
    router.push({
        path: targetPath,
        query: {
            ...(keyword ? { keyword } : {})
        }
    })
}

const handleOrderCommand = (command: string, row: any) => {
    switch (command) {
        case 'questionnaire':
            handleQuestionnaireTasks(row)
            break
        case 'confirm':
            handleConfirm(row)
            break
        case 'confirmOfflinePay':
            handleConfirmOfflinePay(row)
            break
        case 'reschedule':
            handleDirectReschedule(row)
            break
        case 'startService':
            handleStartService(row)
            break
        case 'complete':
            handleComplete(row)
            break
        case 'refund':
            handleRefund(row)
            break
        case 'cancel':
            handleCancel(row)
            break
        case 'delete':
            handleDelete(row)
            break
    }
}

const handleModalSuccess = () => {
    getLists()
    getStatistics()
    if (currentOrder.value?.id) {
        refreshCurrentOrderDetail(Number(currentOrder.value.id))
    }
}

const handleRefundSuccess = (orderId: number) => {
    getLists()
    getStatistics()
    refreshCurrentOrderDetail(orderId)
}

const handleRescheduleSuccess = (orderId: number) => {
    getLists()
    getStatistics()
    refreshCurrentOrderDetail(orderId)
}

watch(() => pager.lists, (lists) => {
    ;(lists || []).forEach((row: any) => syncRowCountdownTargets(row))
    startCountdownTimer()
}, { deep: false })

watch(currentOrder, (value) => {
    syncRowCountdownTargets(value)
    startCountdownTimer()
}, { deep: false })

watch(() => route.query.detail_id, async (detailId) => {
    const id = Number(detailId || 0)
    if (!id) return
    await openOrderDetail(id, true)
}, { immediate: true })

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

getLists()
getStatistics()
</script>

<style lang="scss" scoped>
</style>
