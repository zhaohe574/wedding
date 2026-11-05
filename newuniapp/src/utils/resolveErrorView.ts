/**
 * 统一错误视图映射（体验规范 §4.6；主理人裁决 O13/O14/O5）
 * 来源：docs/redesign/03-组件样式清单与验收.md §4.4
 *
 * 落地说明：03 §4.4 将「TS 签名」与「完整实现」分为两个代码块。合并为单一模块时，
 * 删除签名段的 `export declare function resolveErrorView(...)` 声明行（与下方实现同名会冲突），
 * 保留全部 type / interface 定义与完整实现，import 去重合并。
 *
 * 边界：本文件是纯函数，不发起请求、不持有状态、不修改 src/request/error.ts。
 * ApiError / RetryStrategy 只作类型与只读引用（E1/E2：定义在 shared/contracts/error-codes.ts）。
 */
import { isApiError, type ApiError } from '@/request/error'
import { getErrorMeta, type RetryStrategy } from '@shared/contracts/error-codes'
import { FB } from '@/constants/feedback'

/* ==================== 类型定义（03 §4.4 签名段） ==================== */
export type ErrorKind = 'network' | 'business' | 'permission' | 'unknown'
/** 恢复动作类型：与 base-empty 的 primaryAction.kind 一一对应 */
export type ErrorActionKind = 'retry' | 'refresh' | 'reselect' | 'login' | 'back' | 'contact' | 'toast' | 'none'
export interface ErrorAction { label: string; kind: ErrorActionKind; to?: string; keepForm?: boolean }
export interface ErrorView {
    /** 传给 base-empty 的 :error-kind */
    errorKind: ErrorKind
    title: string
    desc: string
    icon: string
    primaryAction?: ErrorAction
    secondaryAction?: ErrorAction
    /** false = 不渲染整页错误（静默降级 / 已跳登录 / 只弹 Toast） */
    renderPageError: boolean
    /** 仅 Toast 场景使用；已经 sanitizeServerMessage 清洗 */
    toastText?: string
}
export interface ErrorContext {
    /** O13 白名单判定域；payment/poll/fund 一律 FB.PAY_* 优先 */
    domain?: 'payment' | 'poll' | 'fund' | 'general'
    /** 前端校验类（无 ApiError）：参数缺失等 */
    reason?: 'param-missing' | 'unknown-notification'
    /** reselect 回退目标，缺省由调用页决定 */
    reselectTo?: 'query' | 'staff-list' | 'schedule'
    /** 是否已填表单（决定 keepForm） */
    hasForm?: boolean
}

/* ==================== 完整实现（03 §4.4 实现段） ==================== */
/* ---------- O13：资金类白名单底线（产品可后续追加，只需扩展此数组） ---------- */
export const PAY_TEXT_FIRST_DOMAINS: ReadonlyArray<NonNullable<ErrorContext['domain']>> =
    ['payment', 'poll', 'fund']

const isPayFirst = (ctx: ErrorContext) => !!ctx.domain && PAY_TEXT_FIRST_DOMAINS.includes(ctx.domain)

/* ---------- O13：任何情况不暴露 HTTP 码与接口信息 ---------- */
/** 把后端 msg / error.ts 的 message 清洗为用户可见文案；原始值只进上报。 */
export function sanitizeServerMessage(raw: string | undefined | null, fallback: string): string {
    if (!raw) return fallback
    const s = String(raw)
    // ① error.ts:httpError() 的 `服务异常(500)` / `服务异常(5xx)` → 统一服务暂不可用
    if (/服务异常\s*\(?\s*\d{3}\s*\)?/.test(s)) return FB.ERR_SERVER
    // ② 裸 HTTP 状态码 / 接口路径 / request_id / URL → 一律降级为兜底文案
    if (/\b(4\d{2}|5\d{2})\b/.test(s) || /\/api\/|https?:\/\/|request[_-]?id|接口|endpoint/i.test(s)) {
        return fallback
    }
    return s
}

/* ---------- 判定辅助 ---------- */
function isNetworkLayer(e: ApiError | Error | null | undefined): boolean {
    if (!isApiError(e)) return false
    if (e.errorCode === 'NETWORK_ERROR') return true
    if (typeof e.statusCode === 'number' && e.statusCode >= 500) return true
    // networkError() 的 code=-99 且无 errorCode / 无 statusCode
    return e.code === -99 && !e.errorCode && !e.statusCode
}
function isPermission(e: ApiError | Error | null | undefined): boolean {
    if (!isApiError(e)) return false
    return e.errorCode === 'RISK_BLOCKED' || e.statusCode === 403 || e.retryStrategy === 'relogin'
}
function isDuplicateOrQuota(e: ApiError | Error | null | undefined): boolean {
    return isApiError(e) && (e.errorCode === 'IDEMPOTENT_REPLAY' || e.errorCode === 'QUOTA_EXCEEDED')
}
function serverTitle(e: ApiError | Error | null | undefined, ctx: ErrorContext, fallback: string): string {
    // O13：支付/轮询/资金类结果一律 FB.PAY_* 优先，后端 msg 仅作补充说明（进 desc 而非 title）
    if (isPayFirst(ctx)) return FB.PAY_UNCONFIRMED_TITLE
    const raw = isApiError(e) ? e.message : e?.message
    return sanitizeServerMessage(raw, fallback)
}
function serverHint(e: ApiError | Error | null | undefined, ctx: ErrorContext): string {
    if (isPayFirst(ctx)) return FB.PAY_UNCONFIRMED_DESC
    const raw = isApiError(e) ? e.message : e?.message
    return sanitizeServerMessage(raw, '')
}

const ICON: Record<ErrorKind, string> = {
    network: 'empty-wifi', business: 'empty-data', permission: 'empty-permission', unknown: 'empty-list'
}

function view(
    errorKind: ErrorKind, title: string, desc = '',
    primaryAction?: ErrorAction, secondaryAction?: ErrorAction
): ErrorView {
    return { errorKind, title, desc, icon: ICON[errorKind], primaryAction, secondaryAction, renderPageError: true }
}
function toastOnly(text: string): ErrorView {
    return { errorKind: 'business', title: '', desc: '', icon: '', renderPageError: false, toastText: text }
}

/* ---------- 主映射：errorKind 四分类 ↔ retryStrategy 四值（含 refresh 横跨） ---------- */
export function resolveErrorView(
    err: ApiError | Error | null | undefined,
    ctx: ErrorContext = {}
): ErrorView | null {
    const errorCode = isApiError(err) ? err.errorCode : undefined
    const strategy: RetryStrategy | undefined = isApiError(err)
        ? err.retryStrategy
        : (errorCode ? getErrorMeta(errorCode).retry : undefined)

    // ① V2_UNAVAILABLE：部署态静默降级，不展示任何错误、不上报（体验规范 §4.5-4 / §7.1.6）
    if (errorCode === 'V2_UNAVAILABLE') return null

    // ② 前端校验类（无 ApiError）：参数缺失 → business 不可重试（booking 手写 .fallback 合一）
    if (ctx.reason === 'param-missing') {
        return view('business', FB.ERR_PARAM_MISSING, '',
            { label: FB.ACTION_BACK, kind: 'back' })
    }

    // ③ relogin：请求层 onUnauthorized() 已统一跳登录；页面不渲染整页错误态
    //    仅给 Toast（可恢复，但动词不是「重新加载」）
    if (strategy === 'relogin') {
        return toastOnly(FB.ERR_RELOGIN)
    }

    // ④ reselect：可恢复（回上游重选），errorKind=business，保留已填表单
    if (strategy === 'reselect') {
        return view('business', serverTitle(err, ctx, FB.ERR_BUSINESS), serverHint(err, ctx),
            { label: FB.ACTION_RESELECT, kind: 'reselect', to: ctx.reselectTo ?? 'query', keepForm: ctx.hasForm ?? true },
            { label: FB.ACTION_BACK, kind: 'back' })
    }

    // ⑤ refresh 横跨两类：网络层可重试 → network；状态过期类 → business + 刷新（刷新页面数据，非重发失败请求）
    if (strategy === 'refresh') {
        if (isNetworkLayer(err)) {
            const is5xx = isApiError(err) && typeof err.statusCode === 'number' && err.statusCode >= 500
            const title = is5xx ? FB.ERR_SERVER : FB.ERR_NETWORK
            return view('network', title, is5xx ? '' : FB.ERR_NETWORK_HINT,
                { label: FB.ACTION_RETRY, kind: 'retry' },
                { label: FB.ACTION_CONTACT, kind: 'contact' })
        }
        // QUOTE_EXPIRED / ORDER_STATUS_INVALID / ORDER_NOT_PAYABLE / TICKET_STATUS_INVALID
        return view('business', serverTitle(err, ctx, FB.ERR_BUSINESS), serverHint(err, ctx),
            { label: FB.ACTION_REFRESH, kind: 'refresh' },   // 重拉当前页数据，不是重发失败请求
            { label: FB.ACTION_BACK, kind: 'back' })
    }

    // ⑥ none：确定性拒绝 / 权限 / 内容下架 / 限流
    if (strategy === 'none' || errorCode || isApiError(err)) {
        // ⑥-a 重复提交 / 限流：Toast，不渲染整页错误、不清空表单（等待自动解除）
        if (isDuplicateOrQuota(err)) {
            return toastOnly(errorCode === 'QUOTA_EXCEEDED' ? FB.ERR_RATE_LIMIT : FB.ERR_DUPLICATE)
        }
        // ⑥-b 权限类：不可重试，不给「重新加载」，给「联系客服 / 返回」
        if (isPermission(err)) {
            return view('permission', FB.ERR_PERMISSION, FB.ERR_PERMISSION_HINT,
                { label: FB.ACTION_CONTACT, kind: 'contact' },
                { label: FB.ACTION_BACK, kind: 'back' })
        }
        // ⑥-c 其余业务拒绝：后端 msg 优先（经清洗），缺省 FB.ERR_BUSINESS
        const removed = errorCode === 'ORDER_NOT_FOUND' || errorCode === 'QUESTIONNAIRE_NOT_FOUND'
        const title = removed ? (serverTitle(err, ctx, FB.EMPTY_REMOVED)) : serverTitle(err, ctx, FB.ERR_BUSINESS)
        return view('business', title, serverHint(err, ctx),
            { label: errorCode === 'PAY_TIMEOUT' || errorCode === 'PAY_AMOUNT_MISMATCH' || errorCode === 'VOUCHER_PENDING'
                ? FB.ACTION_VIEW_ORDER : FB.ACTION_BACK,
              kind: errorCode === 'PAY_TIMEOUT' ? 'back' : 'back' },
            { label: FB.ACTION_CONTACT, kind: 'contact' })
    }

    // ⑦ 兜底：unknown 按可重试处理（体验规范 §4.4 / §4.6）
    return view('unknown', FB.ERR_BUSINESS, '',
        { label: FB.ACTION_RETRY, kind: 'retry' })
}
