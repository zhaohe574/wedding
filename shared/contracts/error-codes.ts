// 结构化错误码。
//
// 背景：老端靠匹配中文关键词判断错误类型（order_confirm.vue 里 13 个关键词、
// page-recovery.ts 里 token/登录/超时 的字符串包含判断）。后端改一个字就失效。
//
// 新接口在响应体里返回 error_code，客户端只认它，不再匹配 msg。
// 过渡期后端同时保留原中文 msg，老客户端不受影响。

export type RetryStrategy = 'relogin' | 'reselect' | 'refresh' | 'none'

export interface ErrorCodeMeta {
    /** 默认用户文案（后端会覆盖为更具体的 msg） */
    msg: string
    /** 客户端应采取的恢复动作 */
    retry: RetryStrategy
    /** 是否应当上报（预期内的业务错误不上报，避免噪音） */
    report: boolean
}

/** 用函数约束而非 satisfies —— 项目 TS 版本为 4.7，不支持 satisfies */
const defineErrorCodes = <T extends Record<string, ErrorCodeMeta>>(map: T): T => map

export const ERROR_CODES = defineErrorCodes({
    // ---------- 鉴权 ----------
    TOKEN_EXPIRED: { msg: '登录已过期，请重新登录', retry: 'relogin', report: false },
    RISK_BLOCKED: { msg: '当前账号安全等级较高，暂时无法使用该功能', retry: 'none', report: true },
    // ---------- 档期 ----------
    SCHEDULE_LOCKED: { msg: '该档期已被占用，请选择其他日期', retry: 'reselect', report: false },
    SCHEDULE_UNAVAILABLE: { msg: '该人员当日不可预约', retry: 'reselect', report: false },
    SCHEDULE_LEAD_TIME: { msg: '预约需提前一定天数，请调整日期', retry: 'reselect', report: false },
    // ---------- 订单 ----------
    ORDER_STATUS_INVALID: { msg: '当前订单状态不支持该操作', retry: 'refresh', report: false },
    ORDER_NOT_PAYABLE: { msg: '订单当前不可支付', retry: 'refresh', report: false },
    ORDER_NOT_FOUND: { msg: '订单不存在或已删除', retry: 'none', report: false },
    PAY_TIMEOUT: { msg: '订单支付超时，已自动取消', retry: 'none', report: false },
    PAY_AMOUNT_MISMATCH: { msg: '支付金额与订单不一致，请联系客服', retry: 'none', report: true },
    QUOTE_EXPIRED: { msg: '价格已更新，请重新确认', retry: 'refresh', report: false },
    VOUCHER_PENDING: { msg: '凭证正在审核中', retry: 'none', report: false },
    // ---------- 售后 ----------
    TICKET_STATUS_INVALID: { msg: '工单当前状态不支持该操作', retry: 'refresh', report: false },
    // ---------- 问卷 ----------
    QUESTIONNAIRE_NOT_FOUND: { msg: '问卷不存在', retry: 'none', report: false },
    QUESTIONNAIRE_ALREADY_SUBMITTED: { msg: '问卷已提交', retry: 'none', report: false },
    QUESTIONNAIRE_CANCELLED: { msg: '问卷已取消', retry: 'none', report: false },
    QUESTIONNAIRE_EXPIRED: { msg: '问卷已过期', retry: 'none', report: false },
    QUESTIONNAIRE_REQUIRED_MISSING: { msg: '请完成必填题目', retry: 'none', report: false },
    // ---------- 通用 ----------
    IDEMPOTENT_REPLAY: { msg: '请勿重复提交', retry: 'none', report: false },
    QUOTA_EXCEEDED: { msg: '操作过于频繁，请稍后再试', retry: 'none', report: false },
    FEATURE_OFF: { msg: '该功能暂未开放', retry: 'none', report: false },
    REVIEW_MODE_ON: { msg: '该功能暂不可用', retry: 'none', report: false },
    VALIDATE_FAILED: { msg: '参数校验失败', retry: 'none', report: false },
    // v2 聚合接口未部署（后端还没上线该版本）。属于部署态而非故障，不上报，
    // 客户端命中一次后本会话内不再尝试，直接走 v1 降级。
    V2_UNAVAILABLE: { msg: '当前服务版本不支持该接口', retry: 'none', report: false },
    NETWORK_ERROR: { msg: '网络异常，请稍后重试', retry: 'refresh', report: true },
    UNKNOWN: { msg: '操作失败，请稍后重试', retry: 'none', report: true }
} as const)

export type ErrorCodeKey = keyof typeof ERROR_CODES

export function isErrorCode(value: unknown): value is ErrorCodeKey {
    return typeof value === 'string' && value in ERROR_CODES
}

export function getErrorMeta(code: string | undefined | null): ErrorCodeMeta {
    if (isErrorCode(code)) return ERROR_CODES[code]
    return ERROR_CODES.UNKNOWN
}
