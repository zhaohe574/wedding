<template>
    <div class="admin-page-section mt-4">
        <el-table size="large" v-loading="loading" :data="lists">
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
                    <div class="text-red-500 font-bold">¥{{ row.need_pay_amount || row.pay_amount }}</div>
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
                    <el-button type="primary" link @click="$emit('detail', row)">详情</el-button>
                    <el-button
                        type="warning"
                        link
                        :loading="confirmLetterOpeningId === Number(row.id || 0)"
                        @click="$emit('confirm-letter', row)"
                    >
                        档期海报
                    </el-button>
                    <el-button
                        v-if="canAuditVoucher(row)"
                        type="warning"
                        link
                        @click="$emit('audit-voucher', row)"
                    >
                        审核凭证
                    </el-button>
                    <el-dropdown
                        trigger="click"
                        class="inline-block ml-2 align-middle"
                        @command="(cmd: string) => handleCommand(cmd, row)"
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
                                    v-if="row.order_status <= 1"
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
            <pagination :model-value="pager" @update:model-value="$emit('update:pager', $event)" @change="$emit('page-change')" />
        </div>
    </div>
</template>

<script lang="ts" setup>
import { getOrderStatusTagType } from '@/enums/orderEnums'

const props = defineProps<{
    lists: any[]
    loading: boolean
    pager: any
    confirmLetterOpeningId: number
    nowTs: number
}>()

const emit = defineEmits<{
    (e: 'update:pager', val: any): void
    (e: 'page-change'): void
    (e: 'detail', row: any): void
    (e: 'confirm-letter', row: any): void
    (e: 'audit-voucher', row: any): void
    (e: 'confirm-offline-pay', row: any): void
    (e: 'reschedule', row: any): void
    (e: 'start-service', row: any): void
    (e: 'complete-service', row: any): void
    (e: 'refund', row: any): void
    (e: 'cancel', row: any): void
    (e: 'delete', row: any): void
    (e: 'confirm-order', row: any): void
    (e: 'questionnaire', row: any): void
}>()

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
    !row?.receipt_pending &&
    Number(row?.order_status || 0) === 1 &&
    Number(row?.payment_channel || 1) === 2 &&
    !!row?.pay_voucher &&
    Number(row?.pay_voucher_status) === 0

const canConfirmOfflinePay = (row: any) =>
    !row?.receipt_pending &&
    Number(row?.order_status || 0) === 1 &&
    Number(row?.payment_channel || 1) === 2 &&
    !(row?.pay_voucher && Number(row?.pay_voucher_status) === 0)

const buildExpireAt = (deadlineTime: number | string | undefined, remainSeconds: number | string | undefined) => {
    if (Number(deadlineTime || 0) <= 0) return 0
    return Date.now() + Math.max(Number(remainSeconds || 0), 0) * 1000
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
    return Math.max(Math.ceil((expireAt - props.nowTs) / 1000), 0)
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

const handleCommand = (command: string, row: any) => {
    switch (command) {
        case 'questionnaire':
            emit('questionnaire', row)
            break
        case 'confirm':
            emit('confirm-order', row)
            break
        case 'confirmOfflinePay':
            emit('confirm-offline-pay', row)
            break
        case 'reschedule':
            emit('reschedule', row)
            break
        case 'startService':
            emit('start-service', row)
            break
        case 'complete':
            emit('complete-service', row)
            break
        case 'refund':
            emit('refund', row)
            break
        case 'cancel':
            emit('cancel', row)
            break
        case 'delete':
            emit('delete', row)
            break
    }
}
</script>
