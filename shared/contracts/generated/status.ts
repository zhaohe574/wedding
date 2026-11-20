// ===============================================================
// 本文件由 scripts/contracts/export-status.mjs 自动生成，请勿手改。
// 事实源：server/app/common/model/**/*.php 的 const 常量。
// 修改状态值请改 PHP，然后执行：npm run contracts:gen
// ===============================================================

// ------------------------- order -------------------------
/** 订单主状态机，对应 Order.php:51-77 */
export enum OrderStatus {
    PENDING_CONFIRM =  0, // 待确认
    PENDING_PAY     =  1, // 待支付
    PENDING_SERVICE =  2, // 待服务
    IN_SERVICE      =  3, // 服务中
    COMPLETED       =  4, // 已完成
    REVIEWED        =  5, // 已评价
    CANCELLED       =  6, // 已取消
    PAUSED          =  7, // 已暂停
    REFUNDED        =  8, // 已退款
    USER_DELETED    =  9, // 用户已删除
    REFUNDING       = 10, // 退款中
}

export const ORDER_STATUS_NAME_MAP: Record<OrderStatus, string> = {
    [OrderStatus.PENDING_CONFIRM]: '待确认',
    [OrderStatus.PENDING_PAY]: '待支付',
    [OrderStatus.PENDING_SERVICE]: '待服务',
    [OrderStatus.IN_SERVICE]: '服务中',
    [OrderStatus.COMPLETED]: '已完成',
    [OrderStatus.REVIEWED]: '已评价',
    [OrderStatus.CANCELLED]: '已取消',
    [OrderStatus.PAUSED]: '已暂停',
    [OrderStatus.REFUNDED]: '已退款',
    [OrderStatus.USER_DELETED]: '用户已删除',
    [OrderStatus.REFUNDING]: '退款中',
};

export enum OrderPayStatus {
    UNPAID         = 0, // 未支付
    PAID           = 1, // 已支付
    PARTIAL_REFUND = 2, // 部分退款
    FULL_REFUND    = 3, // 全额退款
}

export const ORDER_PAY_STATUS_NAME_MAP: Record<OrderPayStatus, string> = {
    [OrderPayStatus.UNPAID]: '未支付',
    [OrderPayStatus.PAID]: '已支付',
    [OrderPayStatus.PARTIAL_REFUND]: '部分退款',
    [OrderPayStatus.FULL_REFUND]: '全额退款',
};

export enum OrderPayWay {
    NONE    = 0, // 未支付
    WECHAT  = 1, // 微信
    OFFLINE = 4, // 线下
}

export const ORDER_PAY_WAY_NAME_MAP: Record<OrderPayWay, string> = {
    [OrderPayWay.NONE]: '未支付',
    [OrderPayWay.WECHAT]: '微信',
    [OrderPayWay.OFFLINE]: '线下',
};

export enum PaymentChannel {
    ONLINE  = 1, // 线上支付
    OFFLINE = 2, // 线下支付
}

export const PAYMENT_CHANNEL_NAME_MAP: Record<PaymentChannel, string> = {
    [PaymentChannel.ONLINE]: '线上支付',
    [PaymentChannel.OFFLINE]: '线下支付',
};

export enum VoucherStatus {
    PENDING  = 0, // 待审核
    APPROVED = 1, // 已通过
    REJECTED = 2, // 已拒绝
}

export const VOUCHER_STATUS_NAME_MAP: Record<VoucherStatus, string> = {
    [VoucherStatus.PENDING]: '待审核',
    [VoucherStatus.APPROVED]: '已通过',
    [VoucherStatus.REJECTED]: '已拒绝',
};

export enum OrderSource {
    MINIAPP = 1, // 小程序
    ADMIN   = 3, // 后台
    STAFF   = 4, // 服务人员录入
}

export const ORDER_SOURCE_NAME_MAP: Record<OrderSource, string> = {
    [OrderSource.MINIAPP]: '小程序',
    [OrderSource.ADMIN]: '后台',
    [OrderSource.STAFF]: '服务人员录入',
};

// ------------------------- payment -------------------------
export enum PaymentFlowStatus {
    PENDING  = 0, // 待支付
    PAID     = 1, // 已支付
    REFUNDED = 2, // 已退款
    FAILED   = 3, // 支付失败
}

export const PAYMENT_FLOW_STATUS_NAME_MAP: Record<PaymentFlowStatus, string> = {
    [PaymentFlowStatus.PENDING]: '待支付',
    [PaymentFlowStatus.PAID]: '已支付',
    [PaymentFlowStatus.REFUNDED]: '已退款',
    [PaymentFlowStatus.FAILED]: '支付失败',
};

export enum PaymentType {
    DEPOSIT = 1, // 定金
    BALANCE = 2, // 尾款
    FULL    = 3, // 全款
}

export const PAYMENT_TYPE_NAME_MAP: Record<PaymentType, string> = {
    [PaymentType.DEPOSIT]: '定金',
    [PaymentType.BALANCE]: '尾款',
    [PaymentType.FULL]: '全款',
};

export enum PaymentWay {
    WECHAT  = 1, // 微信
    OFFLINE = 4, // 线下
}

export const PAYMENT_WAY_NAME_MAP: Record<PaymentWay, string> = {
    [PaymentWay.WECHAT]: '微信',
    [PaymentWay.OFFLINE]: '线下',
};

export enum CollectionOwner {
    PLATFORM = 1, // 平台收款
    STAFF    = 2, // 服务人员代收
}

export const COLLECTION_OWNER_NAME_MAP: Record<CollectionOwner, string> = {
    [CollectionOwner.PLATFORM]: '平台收款',
    [CollectionOwner.STAFF]: '服务人员代收',
};

// ------------------------- refund -------------------------
export enum RefundStatus {
    PENDING    = 0, // 待审核
    APPROVED   = 1, // 审核通过
    PROCESSING = 2, // 退款中
    COMPLETED  = 3, // 已退款
    REJECTED   = 4, // 已拒绝
    FAILED     = 5, // 退款失败
}

export const REFUND_STATUS_NAME_MAP: Record<RefundStatus, string> = {
    [RefundStatus.PENDING]: '待审核',
    [RefundStatus.APPROVED]: '审核通过',
    [RefundStatus.PROCESSING]: '退款中',
    [RefundStatus.COMPLETED]: '已退款',
    [RefundStatus.REJECTED]: '已拒绝',
    [RefundStatus.FAILED]: '退款失败',
};

export enum RefundType {
    USER   = 1, // 用户申请
    ADMIN  = 2, // 管理员操作
    SYSTEM = 3, // 系统自动
}

export const REFUND_TYPE_NAME_MAP: Record<RefundType, string> = {
    [RefundType.USER]: '用户申请',
    [RefundType.ADMIN]: '管理员操作',
    [RefundType.SYSTEM]: '系统自动',
};

// ------------------------- change -------------------------
export enum OrderChangeStatus {
    PENDING   = 0, // 待审核
    APPROVED  = 1, // 审核通过
    REJECTED  = 2, // 审核拒绝
    EXECUTED  = 3, // 已执行
    CANCELLED = 4, // 已取消
}

export const ORDER_CHANGE_STATUS_NAME_MAP: Record<OrderChangeStatus, string> = {
    [OrderChangeStatus.PENDING]: '待审核',
    [OrderChangeStatus.APPROVED]: '审核通过',
    [OrderChangeStatus.REJECTED]: '审核拒绝',
    [OrderChangeStatus.EXECUTED]: '已执行',
    [OrderChangeStatus.CANCELLED]: '已取消',
};

export enum OrderChangeType {
    DATE     = 1, // 改期
    ADD_ITEM = 3, // 加项
    ADDON    = 4, // 附加服务变更
}

export const ORDER_CHANGE_TYPE_NAME_MAP: Record<OrderChangeType, string> = {
    [OrderChangeType.DATE]: '改期',
    [OrderChangeType.ADD_ITEM]: '加项',
    [OrderChangeType.ADDON]: '附加服务变更',
};

export enum OrderPauseStatus {
    PENDING   = 0, // 待审核
    PAUSED    = 1, // 暂停中
    RESUMED   = 2, // 已恢复
    REJECTED  = 3, // 已拒绝
    CANCELLED = 4, // 已取消
}

export const ORDER_PAUSE_STATUS_NAME_MAP: Record<OrderPauseStatus, string> = {
    [OrderPauseStatus.PENDING]: '待审核',
    [OrderPauseStatus.PAUSED]: '暂停中',
    [OrderPauseStatus.RESUMED]: '已恢复',
    [OrderPauseStatus.REJECTED]: '已拒绝',
    [OrderPauseStatus.CANCELLED]: '已取消',
};

export enum OrderPauseType {
    EPIDEMIC  = 1, // 疫情
    EMERGENCY = 2, // 突发事件
    PERSONAL  = 3, // 个人原因
    OTHER     = 4, // 其他
}

export const ORDER_PAUSE_TYPE_NAME_MAP: Record<OrderPauseType, string> = {
    [OrderPauseType.EPIDEMIC]: '疫情',
    [OrderPauseType.EMERGENCY]: '突发事件',
    [OrderPauseType.PERSONAL]: '个人原因',
    [OrderPauseType.OTHER]: '其他',
};

// ------------------------- aftersale -------------------------
/** 0=待分配、3=已完成。旧小程序写作「待处理/已解决」，以本契约为准 */
export enum AfterSaleTicketStatus {
    PENDING    = 0, // 待分配
    PROCESSING = 1, // 处理中
    CONFIRMING = 2, // 待确认
    COMPLETED  = 3, // 已完成
    CLOSED     = 4, // 已关闭
    CANCELLED  = 5, // 已取消
}

export const AFTER_SALE_TICKET_STATUS_NAME_MAP: Record<AfterSaleTicketStatus, string> = {
    [AfterSaleTicketStatus.PENDING]: '待分配',
    [AfterSaleTicketStatus.PROCESSING]: '处理中',
    [AfterSaleTicketStatus.CONFIRMING]: '待确认',
    [AfterSaleTicketStatus.COMPLETED]: '已完成',
    [AfterSaleTicketStatus.CLOSED]: '已关闭',
    [AfterSaleTicketStatus.CANCELLED]: '已取消',
};

export enum AfterSaleTicketType {
    COMPLAINT  = 1, // 投诉
    CONSULT    = 2, // 咨询
    AFTER_SALE = 3, // 售后
    SUGGEST    = 4, // 建议
    OTHER      = 5, // 其他
}

export const AFTER_SALE_TICKET_TYPE_NAME_MAP: Record<AfterSaleTicketType, string> = {
    [AfterSaleTicketType.COMPLAINT]: '投诉',
    [AfterSaleTicketType.CONSULT]: '咨询',
    [AfterSaleTicketType.AFTER_SALE]: '售后',
    [AfterSaleTicketType.SUGGEST]: '建议',
    [AfterSaleTicketType.OTHER]: '其他',
};

export enum AfterSalePriority {
    LOW    = 1, // 低
    MEDIUM = 2, // 中
    HIGH   = 3, // 高
    URGENT = 4, // 紧急
}

export const AFTER_SALE_PRIORITY_NAME_MAP: Record<AfterSalePriority, string> = {
    [AfterSalePriority.LOW]: '低',
    [AfterSalePriority.MEDIUM]: '中',
    [AfterSalePriority.HIGH]: '高',
    [AfterSalePriority.URGENT]: '紧急',
};

export enum ComplaintStatus {
    PENDING    = 0, // 待处理
    PROCESSING = 1, // 处理中
    HANDLED    = 2, // 已处理
    APPEALED   = 3, // 已申诉
    CLOSED     = 4, // 已关闭
}

export const COMPLAINT_STATUS_NAME_MAP: Record<ComplaintStatus, string> = {
    [ComplaintStatus.PENDING]: '待处理',
    [ComplaintStatus.PROCESSING]: '处理中',
    [ComplaintStatus.HANDLED]: '已处理',
    [ComplaintStatus.APPEALED]: '已申诉',
    [ComplaintStatus.CLOSED]: '已关闭',
};

/** 注意与 AfterSaleTicketType 区分：投诉类型是「服务态度/履约偏差/沟通问题/执行落差/其他」 */
export enum ComplaintType {
    SERVICE   = 1, // 服务态度
    ABILITY   = 2, // 履约偏差
    PUNCTUAL  = 3, // 沟通问题
    VIOLATION = 4, // 执行落差
    OTHER     = 5, // 其他
}

export const COMPLAINT_TYPE_NAME_MAP: Record<ComplaintType, string> = {
    [ComplaintType.SERVICE]: '服务态度',
    [ComplaintType.ABILITY]: '履约偏差',
    [ComplaintType.PUNCTUAL]: '沟通问题',
    [ComplaintType.VIOLATION]: '执行落差',
    [ComplaintType.OTHER]: '其他',
};

export enum ComplaintLevel {
    NORMAL  = 1, // 一般
    SERIOUS = 2, // 严重
    URGENT  = 3, // 紧急
}

export const COMPLAINT_LEVEL_NAME_MAP: Record<ComplaintLevel, string> = {
    [ComplaintLevel.NORMAL]: '一般',
    [ComplaintLevel.SERIOUS]: '严重',
    [ComplaintLevel.URGENT]: '紧急',
};

export enum ServiceCallbackStatus {
    PENDING     = 0, // 待回访
    COMPLETED   = 1, // 已回访
    UNREACHABLE = 2, // 无法联系
    CANCELLED   = 3, // 已取消
}

export const SERVICE_CALLBACK_STATUS_NAME_MAP: Record<ServiceCallbackStatus, string> = {
    [ServiceCallbackStatus.PENDING]: '待回访',
    [ServiceCallbackStatus.COMPLETED]: '已回访',
    [ServiceCallbackStatus.UNREACHABLE]: '无法联系',
    [ServiceCallbackStatus.CANCELLED]: '已取消',
};

// ------------------------- review -------------------------
export enum ReviewStatus {
    PENDING  = 0, // 待审核
    APPROVED = 1, // 已通过
    REJECTED = 2, // 已拒绝
}

export const REVIEW_STATUS_NAME_MAP: Record<ReviewStatus, string> = {
    [ReviewStatus.PENDING]: '待审核',
    [ReviewStatus.APPROVED]: '已通过',
    [ReviewStatus.REJECTED]: '已拒绝',
};

// ------------------------- schedule -------------------------
export enum ScheduleStatus {
    UNAVAILABLE = 0, // 不可用
    AVAILABLE   = 1, // 可预约
    BOOKED      = 2, // 已预约
    LOCKED      = 3, // 已锁定
    RESERVED    = 4, // 内部预留
}

export const SCHEDULE_STATUS_NAME_MAP: Record<ScheduleStatus, string> = {
    [ScheduleStatus.UNAVAILABLE]: '不可用',
    [ScheduleStatus.AVAILABLE]: '可预约',
    [ScheduleStatus.BOOKED]: '已预约',
    [ScheduleStatus.LOCKED]: '已锁定',
    [ScheduleStatus.RESERVED]: '内部预留',
};

export enum WaitlistNotifyStatus {
    PENDING  = 0, // 未通知
    NOTIFIED = 1, // 已通知
    ORDERED  = 2, // 已下单
    EXPIRED  = 3, // 已过期
}

export const WAITLIST_NOTIFY_STATUS_NAME_MAP: Record<WaitlistNotifyStatus, string> = {
    [WaitlistNotifyStatus.PENDING]: '未通知',
    [WaitlistNotifyStatus.NOTIFIED]: '已通知',
    [WaitlistNotifyStatus.ORDERED]: '已下单',
    [WaitlistNotifyStatus.EXPIRED]: '已过期',
};

// ------------------------- questionnaire -------------------------
export enum QuestionnaireTaskStatus {
    PENDING   = 0, // 待填写
    SUBMITTED = 1, // 已提交
    CANCELLED = 2, // 已取消
    VIEWED    = 3, // 已查看
    EXPIRED   = 4, // 已过期
}

export const QUESTIONNAIRE_TASK_STATUS_NAME_MAP: Record<QuestionnaireTaskStatus, string> = {
    [QuestionnaireTaskStatus.PENDING]: '待填写',
    [QuestionnaireTaskStatus.SUBMITTED]: '已提交',
    [QuestionnaireTaskStatus.CANCELLED]: '已取消',
    [QuestionnaireTaskStatus.VIEWED]: '已查看',
    [QuestionnaireTaskStatus.EXPIRED]: '已过期',
};

export enum QuestionnaireSendStatus {
    PENDING = 0, // 待发送
    SENT    = 1, // 已发送
    FAILED  = 2, // 发送失败
    SENDING = 3, // 发送中
}

export const QUESTIONNAIRE_SEND_STATUS_NAME_MAP: Record<QuestionnaireSendStatus, string> = {
    [QuestionnaireSendStatus.PENDING]: '待发送',
    [QuestionnaireSendStatus.SENT]: '已发送',
    [QuestionnaireSendStatus.FAILED]: '发送失败',
    [QuestionnaireSendStatus.SENDING]: '发送中',
};

// ------------------------- settlement -------------------------
export enum StaffSettlementStatus {
    PENDING             = 0, // 待结算
    SETTLED             = 1, // 已结算
    CANCELLED           = 2, // 已取消
    FAILED              = 3, // 结算失败
    TRANSFER_PROCESSING = 4, // 转账处理中/待确认
    NO_PAYOUT           = 5, // 无需打款，仅核算平台抽成
}

export const STAFF_SETTLEMENT_STATUS_NAME_MAP: Record<StaffSettlementStatus, string> = {
    [StaffSettlementStatus.PENDING]: '待结算',
    [StaffSettlementStatus.SETTLED]: '已结算',
    [StaffSettlementStatus.CANCELLED]: '已取消',
    [StaffSettlementStatus.FAILED]: '结算失败',
    [StaffSettlementStatus.TRANSFER_PROCESSING]: '转账处理中/待确认',
    [StaffSettlementStatus.NO_PAYOUT]: '无需打款，仅核算平台抽成',
};

export enum StaffSettleWay {
    BANK      = 2, // 银行卡
    WECHAT    = 3, // 微信
    NO_PAYOUT = 5, // 无需打款
}

export const STAFF_SETTLE_WAY_NAME_MAP: Record<StaffSettleWay, string> = {
    [StaffSettleWay.BANK]: '银行卡',
    [StaffSettleWay.WECHAT]: '微信',
    [StaffSettleWay.NO_PAYOUT]: '无需打款',
};

// ------------------------- dynamic -------------------------
/** 动态主状态机：服务人员发布动态后待审核，审核通过才对外可见 */
export enum DynamicStatus {
    PENDING   = 0, // 待审核
    PUBLISHED = 1, // 已发布
    OFFLINE   = 2, // 已下架
    REJECTED  = 3, // 审核拒绝
}

export const DYNAMIC_STATUS_NAME_MAP: Record<DynamicStatus, string> = {
    [DynamicStatus.PENDING]: '待审核',
    [DynamicStatus.PUBLISHED]: '已发布',
    [DynamicStatus.OFFLINE]: '已下架',
    [DynamicStatus.REJECTED]: '审核拒绝',
};

// ------------------------- staff-work -------------------------
/** 服务人员作品审核状态，对应 StaffWork.audit_status 字段 */
export enum StaffWorkAuditStatus {
    PENDING = 0, // 待审核
    PASS    = 1, // 已通过
    REJECT  = 2, // 已拒绝
}

export const STAFF_WORK_AUDIT_STATUS_NAME_MAP: Record<StaffWorkAuditStatus, string> = {
    [StaffWorkAuditStatus.PENDING]: '待审核',
    [StaffWorkAuditStatus.PASS]: '已通过',
    [StaffWorkAuditStatus.REJECT]: '已拒绝',
};

// ------------------------- staff-certificate -------------------------
/** 服务人员证书核验状态，对应 StaffCertificate.verify_status 字段（AUDIT_* 为同名别名常量，值一致） */
export enum StaffCertificateVerifyStatus {
    PENDING = 0, // 待审核
    PASS    = 1, // 已通过
    REJECT  = 2, // 已拒绝
}

export const STAFF_CERTIFICATE_VERIFY_STATUS_NAME_MAP: Record<StaffCertificateVerifyStatus, string> = {
    [StaffCertificateVerifyStatus.PENDING]: '待审核',
    [StaffCertificateVerifyStatus.PASS]: '已通过',
    [StaffCertificateVerifyStatus.REJECT]: '已拒绝',
};

// ------------------------- 元数据 -------------------------
/** 全部状态中文文案，供 check-contract-usage.mjs 做重复定义检测 */
export const ALL_STATUS_LABELS = [
    '一般',
    '不可用',
    '严重',
    '个人原因',
    '中',
    '低',
    '全款',
    '全额退款',
    '其他',
    '内部预留',
    '加项',
    '发送中',
    '发送失败',
    '可预约',
    '后台',
    '咨询',
    '售后',
    '处理中',
    '定金',
    '审核拒绝',
    '审核通过',
    '小程序',
    '尾款',
    '履约偏差',
    '已下单',
    '已下架',
    '已关闭',
    '已发布',
    '已发送',
    '已取消',
    '已回访',
    '已处理',
    '已完成',
    '已恢复',
    '已执行',
    '已拒绝',
    '已提交',
    '已支付',
    '已暂停',
    '已查看',
    '已申诉',
    '已结算',
    '已评价',
    '已过期',
    '已退款',
    '已通知',
    '已通过',
    '已锁定',
    '已预约',
    '平台收款',
    '建议',
    '待分配',
    '待发送',
    '待回访',
    '待填写',
    '待处理',
    '待审核',
    '待支付',
    '待服务',
    '待确认',
    '待结算',
    '微信',
    '执行落差',
    '投诉',
    '支付失败',
    '改期',
    '无法联系',
    '无需打款',
    '无需打款，仅核算平台抽成',
    '暂停中',
    '服务中',
    '服务人员代收',
    '服务人员录入',
    '服务态度',
    '未支付',
    '未通知',
    '沟通问题',
    '用户已删除',
    '用户申请',
    '疫情',
    '突发事件',
    '管理员操作',
    '系统自动',
    '紧急',
    '线上支付',
    '线下',
    '线下支付',
    '结算失败',
    '转账处理中/待确认',
    '退款中',
    '退款失败',
    '部分退款',
    '银行卡',
    '附加服务变更',
    '高'
] as const;

export type StatusLabel = (typeof ALL_STATUS_LABELS)[number];

// ------------------------- 运行时兜底 -------------------------
/**
 * 安全取文案：后端新增状态但前端未同步时，不崩、不显示 undefined。
 * 返回 { label, known }，known=false 时上层应上报监控。
 */
export function resolveStatusLabel(
    map: Record<number, string>,
    value: number | string | null | undefined
): { label: string; known: boolean } {
    const key = Number(value);
    const label = map[key];
    if (label) return { label, known: true };
    return { label: Number.isFinite(key) ? `未知状态(${key})` : '未知状态', known: false };
}
