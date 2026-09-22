import {
    CommonPayWayEnum,
    isOfflineChannel,
    isOfflinePayment,
    isOnlineChannel,
    isWechatPayment,
    ORDER_STATUS_NAME_MAP,
    OrderPayStatus,
    OrderStatus,
    PaymentChannelEnum,
    PaymentFlowStatus,
    PaymentReceiptPayWayEnum,
    type PaymentStage
} from '../../../shared/contracts/core'

export {
    CommonPayWayEnum,
    isOfflineChannel,
    isOfflinePayment,
    isOnlineChannel,
    isWechatPayment,
    ORDER_STATUS_NAME_MAP,
    OrderPayStatus,
    OrderStatus,
    PaymentChannelEnum,
    PaymentFlowStatus,
    PaymentReceiptPayWayEnum,
    type PaymentStage
}

export type TagType = 'info' | 'warning' | 'primary' | 'success' | 'danger'

/**
 * 订单状态标签颜色映射
 */
export const ORDER_STATUS_TAG_MAP: Record<number, TagType> = {
    [OrderStatus.PENDING_CONFIRM]: 'info',
    [OrderStatus.PENDING_PAY]: 'warning',
    [OrderStatus.PENDING_SERVICE]: 'primary',
    [OrderStatus.IN_SERVICE]: 'primary',
    [OrderStatus.COMPLETED]: 'success',
    [OrderStatus.REVIEWED]: 'success',
    [OrderStatus.CANCELLED]: 'info',
    [OrderStatus.PAUSED]: 'warning',
    [OrderStatus.REFUNDED]: 'danger',
    [OrderStatus.USER_DELETED]: 'danger',
    [OrderStatus.REFUNDING]: 'warning'
}

/**
 * 支付状态标签颜色映射
 */
export const ORDER_PAY_STATUS_TAG_MAP: Record<number, TagType> = {
    [OrderPayStatus.UNPAID]: 'danger',
    [OrderPayStatus.PAID]: 'success',
    [OrderPayStatus.PARTIAL_REFUND]: 'warning',
    [OrderPayStatus.FULL_REFUND]: 'info'
}

/**
 * 订单搜索状态下拉选项
 */
export const ORDER_STATUS_OPTIONS = [
    { label: '全部', value: '' },
    { label: '待确认', value: OrderStatus.PENDING_CONFIRM },
    { label: '待支付', value: OrderStatus.PENDING_PAY },
    { label: '待服务', value: OrderStatus.PENDING_SERVICE },
    { label: '服务中', value: OrderStatus.IN_SERVICE },
    { label: '已完成', value: OrderStatus.COMPLETED },
    { label: '已评价', value: OrderStatus.REVIEWED },
    { label: '已取消', value: OrderStatus.CANCELLED },
    { label: '已暂停', value: OrderStatus.PAUSED },
    { label: '退款中', value: OrderStatus.REFUNDING },
    { label: '已退款', value: OrderStatus.REFUNDED },
    { label: '用户已删除', value: OrderStatus.USER_DELETED }
] as const

/**
 * 支付模式下拉选项
 */
export const PAYMENT_MODE_OPTIONS = [
    { label: '全部', value: '' },
    { label: '全款支付', value: 'full' },
    { label: '定金支付', value: 'deposit' }
] as const

/**
 * 获取订单状态标签类型
 */
export function getOrderStatusTagType(status: number | undefined | null): TagType {
    if (status === undefined || status === null) return 'info'
    return ORDER_STATUS_TAG_MAP[Number(status)] || 'info'
}

/**
 * 获取订单状态文本
 */
export function getOrderStatusText(status: number | undefined | null, fallback = '-'): string {
    if (status === undefined || status === null) return fallback
    return ORDER_STATUS_NAME_MAP[status as OrderStatus] || fallback
}
