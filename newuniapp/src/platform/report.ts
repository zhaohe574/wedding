/**
 * 监控上报。
 *
 * 契约缺失、未知状态、接口异常都从这里出去，统一字段，方便在后台按 kind 聚合。
 * 当前实现只打日志 + 本地缓冲，接入后端后把 flush 换成真实上报即可。
 */

export type ReportKind =
    | 'contract_miss'
    | 'api_error'
    | 'page_error'
    | 'performance'
    | 'unknown_widget'

interface ReportPayload {
    kind: ReportKind
    domain?: string
    value?: unknown
    endpoint?: string
    message?: string
    appVersion?: string
    extra?: Record<string, unknown>
}

const BUFFER_LIMIT = 20
const buffer: Array<ReportPayload & { ts: number }> = []

function push(payload: ReportPayload) {
    const item = { ...payload, ts: Date.now() }
    buffer.push(item)
    if (buffer.length > BUFFER_LIMIT) buffer.shift()

    if (import.meta.env.DEV) {
        console.warn('[report]', item.kind, item.domain || '', item.value ?? '', item.message || '')
    }

    // TODO(M5): 接入后端 /api/report 或第三方埋点，批量上报
}

export function report(kind: ReportKind, payload: Omit<ReportPayload, 'kind'> = {}) {
    push({ kind, ...payload })
}

/** 契约缺失：后端返回了前端未登记的状态值/widget/字段 */
export function reportContractMiss(domain: string, value: unknown, endpoint?: string) {
    push({ kind: 'contract_miss', domain, value, endpoint })
}

/** 接口异常 */
export function reportApiError(endpoint: string, message: string, extra?: Record<string, unknown>) {
    push({ kind: 'api_error', endpoint, message, extra })
}

export function getReportBuffer() {
    return [...buffer]
}
