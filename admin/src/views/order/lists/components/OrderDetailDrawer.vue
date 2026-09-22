<template>
    <el-drawer
        v-model="drawerVisible"
        :title="drawerTitle"
        size="880px"
        destroy-on-close
        class="order-detail-drawer"
    >
        <div v-if="order" class="order-detail-content">
            <!-- 头部状态栏 -->
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg mb-4">
                <div>
                    <span class="text-gray-500 text-sm mr-2">订单状态:</span>
                    <el-tag :type="getOrderStatusTagType(order.order_status)" size="large">
                        {{ order.order_status_desc }}
                    </el-tag>
                    <el-tag
                        v-if="Number(order.payment_channel || 1) === 2"
                        type="success"
                        size="large"
                        class="ml-2"
                    >
                        线下付款
                    </el-tag>
                </div>
                <div class="flex items-center gap-2">
                    <el-button
                        v-if="order.can_admin_refund"
                        type="danger"
                        plain
                        size="small"
                        @click="$emit('refund', order)"
                    >
                        发起退款
                    </el-button>
                </div>
            </div>

            <!-- 基本信息 -->
            <el-descriptions :column="2" border class="mb-4">
                <el-descriptions-item label="订单编号">{{ order.order_sn }}</el-descriptions-item>
                <el-descriptions-item label="订单来源">{{ order.source_desc || '-' }}</el-descriptions-item>
                <el-descriptions-item label="剩余确认时间">{{ getConfirmRemainText(order) }}</el-descriptions-item>
                <el-descriptions-item label="超时处理">{{ order.confirm_timeout_action_desc || '-' }}</el-descriptions-item>
                <el-descriptions-item label="联系人">{{ getDisplayContactName(order) }}</el-descriptions-item>
                <el-descriptions-item label="联系电话">{{ getDisplayContactMobile(order) }}</el-descriptions-item>
                <el-descriptions-item label="服务日期">{{ getDisplayServiceDate(order) }}</el-descriptions-item>
                <el-descriptions-item label="服务地区">{{ order.service_region_text || order.service_address || '-' }}</el-descriptions-item>
                <el-descriptions-item label="服务地址" :span="2">{{ order.service_address || '-' }}</el-descriptions-item>
                <el-descriptions-item label="订单总额">¥{{ formatAmount(order.total_amount) }}</el-descriptions-item>
                <el-descriptions-item v-if="Number(order.addon_amount || 0) > 0" label="附加服务金额">
                    ¥{{ formatAmount(order.addon_amount) }}
                </el-descriptions-item>
                <el-descriptions-item label="优惠金额">¥{{ formatAmount(order.discount_amount) }}</el-descriptions-item>
                <el-descriptions-item label="应付金额">¥{{ formatAmount(order.pay_amount) }}</el-descriptions-item>
                <el-descriptions-item label="已付金额">
                    <span class="text-red-500 font-bold">¥{{ getDisplayPaidAmount(order) }}</span>
                </el-descriptions-item>
                <el-descriptions-item label="支付模式">{{ order.payment_mode_desc || '全款支付' }}</el-descriptions-item>
                <el-descriptions-item label="付款渠道">{{ order.payment_channel_desc || '-' }}</el-descriptions-item>
                <el-descriptions-item label="当前待支付">{{ getNeedPayStageText(order) }}</el-descriptions-item>
                <el-descriptions-item label="剩余支付时间">{{ getPayRemainText(order) }}</el-descriptions-item>
                <el-descriptions-item label="支付超时处理">{{ order.pay_timeout_action_desc || '-' }}</el-descriptions-item>
                <el-descriptions-item v-if="Number(order.deposit_amount || 0) > 0" label="定金金额">
                    ¥{{ formatAmount(order.deposit_amount) }}
                </el-descriptions-item>
                <el-descriptions-item v-if="Number(order.balance_amount || 0) > 0" label="尾款金额">
                    ¥{{ formatAmount(order.balance_amount) }}
                </el-descriptions-item>
                <el-descriptions-item v-if="Number(order.unpaid_amount || 0) >= 0" label="待付金额">
                    ¥{{ formatAmount(order.unpaid_amount) }}
                </el-descriptions-item>
                <el-descriptions-item v-if="order.deposit_remark" label="支付说明" :span="2">
                    {{ order.deposit_remark }}
                </el-descriptions-item>
                <el-descriptions-item label="支付方式">{{ order.pay_type_desc || '-' }}</el-descriptions-item>
                <el-descriptions-item label="支付状态">
                    {{ order.pay_status_display_desc || order.pay_status_desc || '-' }}
                </el-descriptions-item>
                <el-descriptions-item label="线下凭证" :span="2">
                    <el-image
                        v-if="order.pay_voucher"
                        :src="order.pay_voucher"
                        :preview-src-list="[order.pay_voucher]"
                        preview-teleported
                        fit="contain"
                        style="width: 160px; max-height: 160px; border-radius: 4px"
                    />
                    <span v-else class="text-gray-400">未上传</span>
                </el-descriptions-item>
                <el-descriptions-item label="凭证状态">{{ order.pay_voucher_status_desc || '-' }}</el-descriptions-item>
                <el-descriptions-item label="审核备注">{{ order.pay_voucher_audit_remark || '-' }}</el-descriptions-item>
                <el-descriptions-item label="用户备注" :span="2">{{ order.user_remark || '-' }}</el-descriptions-item>
                <el-descriptions-item label="管理备注" :span="2">{{ order.admin_remark || '-' }}</el-descriptions-item>
            </el-descriptions>

            <!-- 服务人员收款申请 -->
            <div v-if="order.receipt_requests?.length" class="mt-6">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-bold text-gray-800">服务人员线下收款申请</h4>
                </div>
                <el-alert
                    title="核实到账后再审核。通过后才记账并锁档；驳回须填写原因。"
                    type="info"
                    :closable="false"
                    class="mb-3"
                />
                <el-table :data="order.receipt_requests" border size="small">
                    <el-table-column prop="phase_desc" label="阶段" width="80" />
                    <el-table-column prop="amount" label="金额" width="100">
                        <template #default="{ row }">¥{{ formatAmount(row.amount) }}</template>
                    </el-table-column>
                    <el-table-column label="收款归属" width="110">
                        <template #default="{ row }">
                            <el-tag size="small" :type="row.collection_owner === 1 ? 'primary' : 'warning'">
                                {{ row.collection_owner === 1 ? '平台收款' : '人员代收' }}
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column prop="status_desc" label="状态" width="90" />
                    <el-table-column label="凭证" width="80">
                        <template #default="{ row }">
                            <el-image
                                v-if="row.pay_voucher"
                                :src="row.pay_voucher"
                                :preview-src-list="[row.pay_voucher]"
                                preview-teleported
                                style="width: 48px; height: 48px; border-radius: 4px"
                            />
                            <span v-else class="text-gray-400">-</span>
                        </template>
                    </el-table-column>
                    <el-table-column prop="reason" label="审核原因／待处理原因" min-width="140" />
                    <el-table-column label="操作" width="140" fixed="right">
                        <template #default="{ row }">
                            <div v-if="row.status === 0" class="flex gap-2">
                                <el-button
                                    type="primary"
                                    link
                                    size="small"
                                    :disabled="receiptAuditing"
                                    @click="auditReceipt(row, true)"
                                >
                                    通过
                                </el-button>
                                <el-button
                                    type="danger"
                                    link
                                    size="small"
                                    :disabled="receiptAuditing"
                                    @click="auditReceipt(row, false)"
                                >
                                    驳回
                                </el-button>
                            </div>
                            <span v-else class="text-gray-400 text-xs">已处理</span>
                        </template>
                    </el-table-column>
                </el-table>
            </div>

            <!-- 服务项目卡片 -->
            <div class="service-project-panel mt-6">
                <div class="service-project-panel__header">
                    <div>
                        <h4 class="service-project-panel__title">服务项目</h4>
                        <div class="service-project-panel__summary">{{ serviceSummaryText }}</div>
                    </div>
                </div>

                <!-- 主套餐 -->
                <div v-if="primaryItem" class="service-project-main">
                    <div class="service-project-main__header">
                        <div class="service-project-main__copy">
                            <div class="service-project-main__label">主套餐</div>
                            <div class="service-project-main__title">{{ primaryTitle }}</div>
                        </div>
                        <div class="service-project-main__aside">
                            <div class="service-project-main__price">¥{{ formatAmount(primaryAmount) }}</div>
                            <el-tag
                                size="small"
                                :type="getOrderItemStatusType(Number(primaryItem?.item_status || 0))"
                            >
                                {{ getOrderItemStatusText(Number(primaryItem?.item_status || 0)) }}
                            </el-tag>
                        </div>
                    </div>

                    <div class="service-project-main__meta-grid">
                        <div
                            v-for="meta in primaryMetaList"
                            :key="meta.label"
                            class="service-project-main__meta-card"
                        >
                            <span class="service-project-main__meta-label">{{ meta.label }}</span>
                            <strong class="service-project-main__meta-value">{{ meta.value }}</strong>
                        </div>
                    </div>

                    <div v-if="primaryDescription" class="service-project-main__desc">
                        {{ primaryDescription }}
                    </div>

                    <div class="service-project-main__address">
                        <span>服务地址:</span>
                        <strong>{{ primaryAddress }}</strong>
                    </div>
                </div>
                <div v-else class="service-project-empty">当前订单暂无主套餐信息</div>

                <!-- 附加套餐 -->
                <div class="service-project-group mt-4">
                    <div class="service-project-group__header">
                        <span class="service-project-group__title">附加套餐</span>
                        <span class="service-project-group__count">{{ addonRows.length }} 项</span>
                    </div>
                    <div v-if="addonRows.length" class="service-project-grid">
                        <div
                            v-for="row in addonRows"
                            :key="row.key"
                            class="service-sub-card"
                        >
                            <div class="service-sub-card__header">
                                <div class="service-sub-card__title-row">
                                    <span class="service-sub-card__title">{{ row.title }}</span>
                                    <el-tag size="small" :type="row.typeTagType">{{ row.typeText }}</el-tag>
                                </div>
                                <span class="service-sub-card__price">{{ row.priceText }}</span>
                            </div>
                            <div v-if="row.metaText" class="service-sub-card__meta">{{ row.metaText }}</div>
                            <div v-if="row.description" class="service-sub-card__desc">{{ row.description }}</div>
                        </div>
                    </div>
                    <div v-else class="service-project-empty service-project-empty--sub">
                        当前订单未配置附加套餐
                    </div>
                </div>

                <!-- 协作服务 -->
                <div v-if="relatedRows.length" class="service-project-group mt-4">
                    <div class="service-project-group__header">
                        <span class="service-project-group__title">协作服务</span>
                        <span class="service-project-group__count">{{ relatedRows.length }} 项</span>
                    </div>
                    <div class="service-project-grid">
                        <div
                            v-for="row in relatedRows"
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
            </div>

            <!-- 支付记录 -->
            <div v-if="order.payments?.length" class="mt-6">
                <h4 class="font-bold text-gray-800 mb-2">支付流水记录</h4>
                <el-table :data="order.payments" border size="small">
                    <el-table-column label="流水号" prop="payment_sn" min-width="180" />
                    <el-table-column label="支付阶段" min-width="90">
                        <template #default="{ row }">{{ row.pay_type_desc || '-' }}</template>
                    </el-table-column>
                    <el-table-column label="支付方式" min-width="100">
                        <template #default="{ row }">{{ row.pay_way_desc || '-' }}</template>
                    </el-table-column>
                    <el-table-column label="收款归属" min-width="110">
                        <template #default="{ row }">
                            <el-tag size="small" :type="row.collection_owner === 2 ? 'warning' : 'primary'">
                                {{ row.collection_owner === 2 ? '服务人员代收' : '平台收款' }}
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="凭证" width="80">
                        <template #default="{ row }">
                            <el-image
                                v-if="row.pay_voucher"
                                :src="row.pay_voucher"
                                :preview-src-list="[row.pay_voucher]"
                                preview-teleported
                                style="width: 44px; height: 44px; border-radius: 4px"
                            />
                            <span v-else class="text-gray-400">-</span>
                        </template>
                    </el-table-column>
                    <el-table-column label="支付金额" min-width="100">
                        <template #default="{ row }">¥{{ formatAmount(row.pay_amount) }}</template>
                    </el-table-column>
                    <el-table-column label="支付状态" min-width="100">
                        <template #default="{ row }">{{ row.pay_status_desc || '-' }}</template>
                    </el-table-column>
                    <el-table-column label="支付时间" prop="pay_time" min-width="160" />
                </el-table>
            </div>

            <!-- 操作日志 -->
            <div v-if="order.logs?.length" class="mt-6">
                <h4 class="font-bold text-gray-800 mb-3">操作日志</h4>
                <el-timeline class="pl-2">
                    <el-timeline-item
                        v-for="log in order.logs"
                        :key="log.id"
                        :timestamp="log.create_time"
                        placement="top"
                    >
                        <span class="font-semibold text-gray-700 mr-2">[{{ log.operator_type_desc }}]</span>
                        <span class="text-gray-600">{{ log.content }}</span>
                    </el-timeline-item>
                </el-timeline>
            </div>
        </div>
    </el-drawer>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { ElMessageBox } from 'element-plus'
import { orderAuditVoucher } from '@/api/order'
import { getOrderStatusTagType } from '@/enums/orderEnums'
import feedback from '@/utils/feedback'

const props = defineProps<{
    modelValue: boolean
    order: any
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', val: boolean): void
    (e: 'refund', order: any): void
    (e: 'refresh', orderId: number): void
}>()

const drawerVisible = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val)
})

const drawerTitle = computed(() =>
    props.order?.order_sn ? `订单详情 - ${props.order.order_sn}` : '订单详情'
)

const receiptAuditing = ref(false)

const formatAmount = (val: any) => Number(val || 0).toFixed(2)

const getDisplayContactName = (o: any) => o?.contact_name || o?.user?.nickname || '-'
const getDisplayContactMobile = (o: any) => o?.contact_mobile || o?.user?.mobile || '-'
const getDisplayPaidAmount = (o: any) => Number(o?.paid_amount ?? 0).toFixed(2)

const getDisplayServiceDate = (o: any) => {
    if (o?.service_date) return o.service_date
    const dates = (o?.items || []).map((item: any) => item.service_date || item.schedule_date).filter(Boolean)
    return dates.length ? Array.from(new Set(dates)).join('、') : '-'
}

const getNeedPayStageText = (o: any) => {
    if (!o) return '无需支付'
    const needPay = String(o?.need_pay || 'none')
    if (Number(o?.payment_channel || 1) === 2) {
        if (needPay === 'deposit') return '待上传首笔凭证'
        if (needPay === 'balance') return '待上传尾款凭证'
        if (needPay === 'full') return '待上传线下凭证'
        return '无需支付'
    }
    if (needPay === 'deposit') return '定金'
    if (needPay === 'balance') return '尾款'
    if (needPay === 'full') return '全款'
    return '无需支付'
}

const getConfirmRemainText = (o: any) => {
    const text = o?.confirm_remain_text
    return text || '-'
}

const getPayRemainText = (o: any) => {
    const text = o?.pay_remain_text
    return text || '-'
}

const getOrderItemStatusText = (status: number) => {
    const map: Record<number, string> = { 0: '待服务', 1: '服务中', 2: '已完成', 3: '已取消' }
    return map[status] || '-'
}

const getOrderItemStatusType = (status: number): 'warning' | 'primary' | 'success' | 'info' => {
    const map: Record<number, 'warning' | 'primary' | 'success' | 'info'> = {
        0: 'warning', 1: 'primary', 2: 'success', 3: 'info'
    }
    return map[status] || 'info'
}

const orderItems = computed(() => {
    const items = props.order?.items
    return Array.isArray(items) ? items : []
})

const primaryItem = computed(() =>
    orderItems.value.find((item: any) => Number(item?.item_type || 1) === 1) || orderItems.value[0] || null
)

const primaryTitle = computed(() =>
    String(primaryItem.value?.package_name || primaryItem.value?.package?.name || '待确认主套餐').trim() || '待确认主套餐'
)

const primaryAmount = computed(() => {
    const item = primaryItem.value
    if (!item) return 0
    const price = Number(item.price || 0)
    const qty = Number(item.quantity || 1)
    return Number(item.subtotal || price * qty || 0)
})

const primaryDescription = computed(() =>
    String(primaryItem.value?.package_description || primaryItem.value?.package?.description || '').trim()
)

const primaryAddress = computed(() =>
    props.order?.service_address || props.order?.service_region_text || '-'
)

const primaryMetaList = computed(() => {
    const staffName = String(primaryItem.value?.staff_name || primaryItem.value?.staff?.name || '待分配服务人员').trim() || '待分配服务人员'
    return [
        { label: '服务人员', value: staffName },
        { label: '服务日期', value: primaryItem.value?.service_date || props.order?.service_date || '-' },
        { label: '服务地区', value: props.order?.service_region_text || props.order?.service_address || '-' },
        { label: '数量', value: `x${Number(primaryItem.value?.quantity || 1)}` }
    ].filter((item) => String(item.value || '').trim() !== '')
})

type ServiceDetailRow = {
    key: string
    title: string
    typeText: string
    typeTagType: 'warning' | 'success' | 'primary' | 'info'
    description: string
    metaText: string
    priceText: string
    statusText?: string
    statusType?: 'warning' | 'primary' | 'success' | 'info'
}

const addonRows = computed<ServiceDetailRow[]>(() => {
    const rows: ServiceDetailRow[] = []
    const items = orderItems.value
    items.forEach((item: any) => {
        const addons = item.addons || []
        addons.forEach((addon: any, idx: number) => {
            const price = Number(addon.price || 0)
            const qty = Number(addon.quantity || 1)
            rows.push({
                key: `addon-${item.id}-${addon.id || idx}`,
                title: addon.addon_name || '附加项',
                typeText: '附加项',
                typeTagType: 'warning',
                description: addon.description || '',
                metaText: `数量 x${qty} · 单价 ¥${formatAmount(price)}`,
                priceText: `¥${formatAmount(price * qty)}`
            })
        })
    })
    return rows
})

const relatedRows = computed<ServiceDetailRow[]>(() => {
    const rows: ServiceDetailRow[] = []
    const items = orderItems.value.filter((item: any) => item !== primaryItem.value)
    items.forEach((item: any) => {
        const price = Number(item.price || 0)
        const qty = Number(item.quantity || 1)
        const staffName = item.staff_name || item.staff?.name || '待分配'
        const status = Number(item.item_status || 0)
        rows.push({
            key: `item-${item.id}`,
            title: item.package_name || item.package?.name || '协作服务',
            typeText: item.item_type_desc || '协作服务',
            typeTagType: 'success',
            description: item.package_description || '',
            metaText: `服务人员: ${staffName} · 服务日期: ${item.service_date || '-'}`,
            priceText: `¥${formatAmount(item.subtotal || price * qty)}`,
            statusText: getOrderItemStatusText(status),
            statusType: getOrderItemStatusType(status)
        })
    })
    return rows
})

const serviceSummaryText = computed(() => {
    const totalCount = orderItems.value.length
    const addonCount = addonRows.value.length
    return `包含 1 个主服务、${addonCount} 个附加项、${relatedRows.value.length} 个协作服务`
})

const auditReceipt = async (receipt: any, approved: boolean) => {
    if (receiptAuditing.value) return
    receiptAuditing.value = true
    try {
        let remark = ''
        if (approved) {
            await feedback.confirm(`确认已核实${receipt.phase_desc} ¥${receipt.amount}到账？审核通过后将记账并检查锁档。`)
        } else {
            const result = await ElMessageBox.prompt('请说明驳回原因，原凭证和申请记录将保留。', '驳回收款申请', {
                inputValidator: (value: string) => !!value?.trim() || '请填写驳回原因'
            })
            remark = result.value
        }
        await orderAuditVoucher({
            id: props.order.id,
            receipt_id: receipt.id,
            approved: approved ? 1 : 0,
            remark
        })
        feedback.msgSuccess('审核完成')
        emit('refresh', Number(props.order.id))
    } catch (err) {
        console.error(err)
    } finally {
        receiptAuditing.value = false
    }
}
</script>

<style lang="scss" scoped>
.order-detail-content {
    padding: 0 4px;
}

.service-project-panel {
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 12px;
    padding: 16px;
    background: #fff;

    &__header {
        margin-bottom: 12px;
    }

    &__title {
        font-size: 15px;
        font-weight: 700;
        color: #1f2937;
    }

    &__summary {
        font-size: 12px;
        color: #9ca3af;
        margin-top: 2px;
    }
}

.service-project-main {
    border: 1px solid #f9d8e7;
    border-radius: 12px;
    padding: 16px;
    background: linear-gradient(180deg, #fffafd 0%, #ffffff 100%);
    display: flex;
    flex-direction: column;
    gap: 12px;

    &__header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    &__label {
        font-size: 11px;
        font-weight: 600;
        color: #be185d;
        background: #fce7f3;
        padding: 2px 8px;
        border-radius: 4px;
        display: inline-block;
        margin-bottom: 4px;
    }

    &__title {
        font-size: 16px;
        font-weight: 700;
        color: #111827;
    }

    &__aside {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 6px;
    }

    &__price {
        font-size: 20px;
        font-weight: 700;
        color: #be185d;
    }

    &__meta-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 8px;
    }

    &__meta-card {
        padding: 8px 12px;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.85);
        border: 1px solid #f5e5eb;
        display: flex;
        flex-direction: column;
    }

    &__meta-label {
        font-size: 11px;
        color: #9ca3af;
    }

    &__meta-value {
        font-size: 13px;
        color: #1f2937;
        margin-top: 2px;
    }

    &__desc {
        padding: 10px 12px;
        border-radius: 8px;
        background: #fff;
        border: 1px dashed #f2dce6;
        font-size: 12px;
        color: #6b7280;
    }

    &__address {
        font-size: 12px;
        color: #6b7280;

        strong {
            color: #374151;
            margin-left: 4px;
        }
    }
}

.service-project-group {
    &__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    &__title {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }

    &__count {
        font-size: 11px;
        color: #9ca3af;
    }
}

.service-project-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 10px;
}

.service-sub-card {
    border: 1px solid #eee7e3;
    border-radius: 10px;
    padding: 12px;
    background: #fff;
    display: flex;
    flex-direction: column;
    gap: 6px;

    &--related {
        border-color: #d7f0e2;
        background: linear-gradient(180deg, #f6fffa 0%, #ffffff 100%);
    }

    &__header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 8px;
    }

    &__title-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
    }

    &__title {
        font-size: 13px;
        font-weight: 600;
        color: #111827;
    }

    &__price {
        font-size: 15px;
        font-weight: 700;
        color: #be185d;
    }

    &__meta {
        font-size: 11px;
        color: #6b7280;
    }

    &__desc {
        font-size: 12px;
        color: #4b5563;
    }
}

.service-project-empty {
    border-radius: 8px;
    padding: 16px;
    background: #f9fafb;
    border: 1px dashed #e5e7eb;
    font-size: 12px;
    color: #9ca3af;
    text-align: center;

    &--sub {
        padding: 10px;
    }
}
</style>
