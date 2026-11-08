import {
    ERROR_CODES,
    getErrorMeta,
    type ErrorCodeKey,
    type RetryStrategy
} from '@shared/contracts/error-codes'

/**
 * 统一的请求异常。
 *
 * 老端把错误当成字符串（`Promise.reject(msg)`），导致上层只能用 `msg.includes('登录')`
 * 这种写法分类。这里改成结构化对象：errorCode 决定行为，msg 只负责展示。
 */
export class ApiError extends Error {
    readonly code: number
    readonly errorCode?: string
    readonly retryStrategy: RetryStrategy
    readonly statusCode?: number
    readonly requestId?: string
    readonly data?: unknown

    constructor(params: {
        code: number
        message: string
        errorCode?: string
        retryStrategy?: RetryStrategy
        statusCode?: number
        requestId?: string
        data?: unknown
    }) {
        super(params.message)
        this.name = 'ApiError'
        this.code = params.code
        this.errorCode = params.errorCode
        this.statusCode = params.statusCode
        this.requestId = params.requestId
        this.data = params.data
        this.retryStrategy =
            params.retryStrategy ??
            (params.errorCode ? getErrorMeta(params.errorCode).retry : 'none')
    }

    /** 是否需要重新登录 */
    get needRelogin(): boolean {
        return this.retryStrategy === 'relogin'
    }

    /** 是否需要让用户重选（日期/人员） */
    get needReselect(): boolean {
        return this.retryStrategy === 'reselect'
    }

    /** 是否需要刷新当前页数据 */
    get needRefresh(): boolean {
        return this.retryStrategy === 'refresh'
    }

    /** 是否值得上报（预期内的业务错误不报，避免噪音） */
    get shouldReport(): boolean {
        if (this.errorCode && this.errorCode in ERROR_CODES) {
            return ERROR_CODES[this.errorCode as ErrorCodeKey].report
        }
        return true
    }
}

/**
 * 从后端响应体构造 ApiError。
 *
 * ⚠️ `statusCode` 必须由调用方传入真实 HTTP 状态码 —— 不传会让 `isRetryableError()`
 * 的 `statusCode === undefined` 分支恒真，**所有信封错误都被当成可重试**（GET 会打 3 次）。
 * 后端 `ExceptionHandle::renderApiJson()` 对**所有**异常（含 500）都返回带 `code` 的 JSON 信封，
 * 所以 500 也走信封路径 —— 不传的话 500 同样丢状态码。
 */
export function fromEnvelope(payload: any, requestId?: string, statusCode?: number): ApiError {
    const code = Number(payload?.code)
    const errorCode: string | undefined = payload?.error_code || undefined
    const meta = errorCode ? getErrorMeta(errorCode) : null
    const message = payload?.msg || meta?.msg || '操作失败'

    return new ApiError({
        code: Number.isFinite(code) ? code : -99,
        message,
        errorCode,
        retryStrategy: meta?.retry ?? (code === -1 ? 'relogin' : 'none'),
        requestId: payload?.request_id || requestId,
        statusCode,
        data: payload?.data
    })
}

/** 网络层失败（无响应） */
export function networkError(errMsg?: string): ApiError {
    return new ApiError({
        code: -99,
        message: '网络异常，请稍后重试',
        errorCode: 'NETWORK_ERROR',
        retryStrategy: 'refresh',
        data: { raw: errMsg }
    })
}

/** HTTP 非 200 */
export function httpError(statusCode: number): ApiError {
    if (statusCode === 401 || statusCode === 403) {
        return new ApiError({
            code: -1,
            message: '登录已失效，请重新登录',
            errorCode: 'TOKEN_EXPIRED',
            retryStrategy: 'relogin',
            statusCode
        })
    }
    return new ApiError({
        code: -99,
        message: `服务异常(${statusCode})`,
        statusCode,
        retryStrategy: statusCode >= 500 ? 'refresh' : 'none'
    })
}

export function isApiError(e: unknown): e is ApiError {
    return e instanceof ApiError
}
