import appConfig from '@/config'
import { ApiError, fromEnvelope, isApiError, networkError } from './error'
import { rawRequest, rawUpload } from './http'
import type { RequestConfig, UploadConfig } from './types'

export * from './types'
export { ApiError, isApiError } from './error'
export * from './normalize'

// ---------------------------------------------------------------- 运行时依赖注入
// 不在本文件 import store，避免 store → api → request → store 的循环依赖。

interface RequestRuntime {
    getToken: () => string
    /** token 失效时的处理（清 token + 跳登录）；透传 ApiError 供上层识别 need_bind_mobile */
    onUnauthorized: (error?: ApiError) => void
    /** 错误上报（可选） */
    onError?: (error: ApiError) => void
}

let runtime: RequestRuntime = {
    getToken: () => '',
    // 未注入前的占位：表达式体（非空块）避免 no-empty-function，行为仍是「什么都不做」
    onUnauthorized: () => undefined
}

export function configureRequest(next: Partial<RequestRuntime>) {
    runtime = { ...runtime, ...next }
}

// ---------------------------------------------------------------- loading 引用计数
// 老实现由各页面手写 showLoading/hideLoading，漏写就卡死遮罩。

let loadingCount = 0

function acquireLoading() {
    if (loadingCount === 0) {
        uni.showLoading({ title: '加载中', mask: true })
    }
    loadingCount += 1
}

function releaseLoading() {
    loadingCount = Math.max(0, loadingCount - 1)
    if (loadingCount === 0) {
        uni.hideLoading()
    }
}

// ---------------------------------------------------------------- 并发去重
// 老实现是「后来的 abort 先来的」，两个页面同时调同一接口时先发的会失败。
// 这里改为「后来者复用先来者的 Promise」，语义完全不同。

const inflight = new Map<string, Promise<any>>()

function stableStringify(value: unknown): string {
    if (value === null || typeof value !== 'object') return String(value)
    if (Array.isArray(value)) return `[${value.map(stableStringify).join(',')}]`
    const obj = value as Record<string, unknown>
    return `{${Object.keys(obj)
        .sort()
        .map((k) => `${k}:${stableStringify(obj[k])}`)
        .join(',')}}`
}

function newIdempotencyKey(): string {
    return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`
}

// ---------------------------------------------------------------- 工具

function joinUrl(baseUrl: string, urlPrefix: string, url: string): string {
    const base = baseUrl.endsWith('/') ? baseUrl.slice(0, -1) : baseUrl
    const prefix = urlPrefix ? (urlPrefix.startsWith('/') ? urlPrefix : `/${urlPrefix}`) : ''
    const path = url.startsWith('/') ? url : `/${url}`
    return `${base}${prefix}${path}`
}

function toast(message: string) {
    uni.showToast({ title: message, icon: 'none', duration: 2200 })
}

function sleep(ms: number) {
    return new Promise((resolve) => setTimeout(resolve, ms))
}

/** 只有幂等安全的请求才重试：GET 天然安全，POST 需显式声明 idempotent */
function shouldRetry(config: RequestConfig, attempt: number): boolean {
    const max = config.retry ?? (config.method === 'GET' || !config.method ? 2 : 0)
    if (attempt >= max) return false
    if ((config.method ?? 'GET') === 'GET') return true
    return config.idempotent === true
}

function isRetryableError(error: ApiError): boolean {
    // 网络层错误与 5xx 可重试；业务错误（code=0）不重试
    return error.statusCode === undefined || error.statusCode >= 500
}

// ---------------------------------------------------------------- v2 可用性熔断（按端点）
// 后端未部署某个 /api/v2 子接口时，客户端不该每次都白等一个 RTT，也不该让控制台被 404 刷屏。
// 但「一个 404 关掉全部 v2」是错的：报价路径写错曾把 v2.home / v2.me / v2.staff / v2.order 一起关掉，
// 首页与我的全部退化成多个 v1 请求。所以按端点（相对路径）分别记录，互不牵连。

const v2Broken = new Set<string>()

function isV2Url(url: string): boolean {
    return /^\/v2(\/|$)/.test(url)
}

/** 端点键：去掉 query 与尾部斜杠，保证 /v2/home 与 /v2/home?x=1 记成同一个 */
function v2Key(url: string): string {
    const path = url.split('?')[0] || url
    return path.length > 1 && path.endsWith('/') ? path.slice(0, -1) : path
}

function isV2Broken(url: string): boolean {
    return v2Broken.has(v2Key(url))
}

function v2UnavailableError(url?: string): ApiError {
    return new ApiError({
        code: -98,
        message: '当前服务版本不支持该接口',
        errorCode: 'V2_UNAVAILABLE',
        retryStrategy: 'none',
        data: url ? { url: v2Key(url) } : undefined
    })
}

/**
 * 供调试面板/日志使用。
 * 不传 url：全部 v2 端点都健康才返回 true（兼容旧签名）；
 * 传 url：只看该端点是否被熔断。
 */
export function isV2Available(url?: string): boolean {
    if (!url) return v2Broken.size === 0
    return !isV2Broken(url)
}

/** 测试/调试用：清空熔断记录（例如切环境后） */
export function resetV2Circuit(): void {
    v2Broken.clear()
}

// ---------------------------------------------------------------- 查询参数清洗
// uni.request 把 data 拼到 query string 时，undefined 会变成字面量 "undefined"。
// 后果不是 404，而是更隐蔽的错：后端 intval('undefined') = 0，
// 于是「全部」tab 变成只查 status=0，用户以为数据丢了。
// 所以 GET 请求必须先把 undefined / null 摘掉。

function compactQuery(data: unknown): Record<string, unknown> | undefined {
    if (!data || typeof data !== 'object' || Array.isArray(data)) {
        return data as Record<string, unknown> | undefined
    }
    const result: Record<string, unknown> = {}
    for (const [key, value] of Object.entries(data as Record<string, unknown>)) {
        if (value === undefined || value === null) continue
        result[key] = value
    }
    return result
}

// ---------------------------------------------------------------- 核心请求

/**
 * @param idempotencyKey 由 `request()` 统一生成后传入，**重试之间复用同一个**。
 *   之前每个重试都在这里新生成一个 key，后端无法识别为重放，幂等形同虚设。
 */
async function doRequest<T>(config: RequestConfig, idempotencyKey = ''): Promise<T> {
    const method = config.method ?? 'GET'

    // 该 v2 端点已确认不可用：直接短路，连请求都不发
    if (isV2Url(config.url) && isV2Broken(config.url)) {
        throw v2UnavailableError(config.url)
    }

    const url = joinUrl(config.baseUrl ?? appConfig.baseUrl, appConfig.urlPrefix, config.url)

    const header: Record<string, string> = {
        'Content-Type': 'application/json;charset=UTF-8',
        version: appConfig.version,
        ...(config.header || {})
    }

    // 调用方可显式传 header.token：登录态尚未持久化时的临时凭证请求（如强制绑定手机号）
    const token = header.token || runtime.getToken()
    if (token) header.token = token
    // ⚠️ 幂等 key 的不变式：**必须在 request() 里定一次，所有重试复用同一个**
    //    （生成点在下方 request() 的 `const idempotencyKey = ...`，doRequest 只负责塞进 header）。
    //    这里的 `|| newIdempotencyKey()` 只是兜底 —— 若哪天有人把生成挪进 doRequest，
    //    每次重试都会换新 key，后端认不出是重放，幂等就形同虚设。
    //    **结论：任何写接口都不许开 retry>0；要开先确认 key 是复用的。**
    //    当前全仓 `retry:` 只有 activity.ts / pay.ts 两处，且都是 retry: 0（安全）。
    if (config.idempotent) header['Idempotency-Key'] = idempotencyKey || newIdempotencyKey()

    if (config.auth && !token) {
        const err = new ApiError({
            code: -1,
            message: '请先登录',
            errorCode: 'TOKEN_EXPIRED',
            retryStrategy: 'relogin'
        })
        // 这里只抛错：跳登录由 request() 的 catch 统一触发（尊重 skipAuthRedirect）
        throw err
    }

    const res = await rawRequest({
        url,
        method,
        data: method === 'GET' ? compactQuery(config.data) : config.data,
        header,
        timeout: config.timeout ?? appConfig.timeout,
        dataType: 'json'
    })

    if (res.statusCode !== 200) {
        // 该 v2 端点未部署：只熔断这一个端点，静默降级，不当成故障上报
        if (res.statusCode === 404 && isV2Url(config.url)) {
            v2Broken.add(v2Key(config.url))
            throw v2UnavailableError(config.url)
        }

        // 后端在 403 时也返回 JSON 信封，优先按信封解析
        const payload = res.data as any
        if (payload && typeof payload === 'object' && 'code' in payload) {
            // 传真实状态码：403 能落到 resolveErrorView 的 permission 分支，5xx 仍判可重试
            const error = fromEnvelope(payload, res.header?.request_id, res.statusCode)
            throw error
        }
        throw new ApiError({
            code: -99,
            message: `服务异常(${res.statusCode})`,
            statusCode: res.statusCode,
            retryStrategy: res.statusCode >= 500 ? 'refresh' : 'none'
        })
    }

    const payload = res.data as any

    // 非信封响应（如某些第三方回调）原样返回
    if (!payload || typeof payload !== 'object' || !('code' in payload)) {
        return payload as T
    }

    const code = Number(payload.code)

    if (code === 1 || code === 2) {
        if (payload.msg && Number(payload.show) === 1 && !config.silent) {
            toast(payload.msg)
        }
        return payload.data as T
    }

    if (code === -1) {
        // 这里只抛错：跳登录由 request() 的 catch 统一触发（尊重 skipAuthRedirect）
        throw fromEnvelope(payload, res.header?.request_id, res.statusCode)
    }

    throw fromEnvelope(payload, res.header?.request_id, res.statusCode)
}

async function request<T = any>(config: RequestConfig): Promise<T> {
    const useDedupe = config.dedupe !== false
    const key = useDedupe
        ? `${config.method ?? 'GET'}|${config.url}|${stableStringify(config.data ?? {})}`
        : ''

    if (useDedupe) {
        const existing = inflight.get(key)
        if (existing) return existing as Promise<T>
    }

    if (config.loading) acquireLoading()

    // 幂等键在这里定一次：
    //   外部传了 → 用它（同一份业务数据多次重试共用同一个 key，后端才能识别为重放）
    //   没传      → 本次调用生成一个，**所有重试都用它**，不再每次重试换新 key
    const idempotencyKey = config.idempotent ? config.idempotencyKey || newIdempotencyKey() : ''

    const task = (async () => {
        let attempt = 0
        for (;;) {
            try {
                return await doRequest<T>(config, idempotencyKey)
            } catch (raw) {
                const error = isApiError(raw)
                    ? raw
                    : new ApiError({ code: -99, message: '请求失败', retryStrategy: 'none' })

                if (error.needRelogin && !config.skipAuthRedirect) {
                    runtime.onUnauthorized(error)
                }

                if (isRetryableError(error) && shouldRetry(config, attempt)) {
                    attempt += 1
                    await sleep(300 * attempt)
                    continue
                }

                if (error.shouldReport) runtime.onError?.(error)
                if (!config.silent) toast(error.message)
                throw error
            }
        }
    })()

    if (useDedupe) {
        inflight.set(key, task)
        task.finally(() => inflight.delete(key))
    }

    try {
        return await task
    } finally {
        if (config.loading) releaseLoading()
    }
}

// ---------------------------------------------------------------- 上传

async function upload<T = any>(config: UploadConfig): Promise<T> {
    const url = joinUrl(config.baseUrl ?? appConfig.baseUrl, appConfig.urlPrefix, config.url)
    const header: Record<string, string> = { ...(config.header || {}) }
    // 调用方可显式传 header.token：登录态尚未持久化时的临时凭证请求（如强制绑定手机号）
    const token = header.token || runtime.getToken()
    if (token) header.token = token
    if (config.auth && !token) {
        const err = new ApiError({
            code: -1,
            message: '请先登录',
            errorCode: 'TOKEN_EXPIRED',
            retryStrategy: 'relogin'
        })
        runtime.onUnauthorized(err)
        throw err
    }

    if (config.loading) acquireLoading()
    try {
        const res = await rawUpload(
            {
                url,
                filePath: config.filePath,
                name: config.name ?? 'file',
                formData: config.formData,
                header
            },
            config.onProgress
        )

        let payload: any
        try {
            payload = JSON.parse(res.data)
        } catch {
            throw new ApiError({ code: -99, message: '上传返回格式错误', retryStrategy: 'none' })
        }

        if (Number(payload?.code) === 1) return payload.data as T

        // upload 走独立路径、不进 shouldRetry，但为保持一致：信封错误一律带状态码
        const error = fromEnvelope(payload, undefined, res.statusCode)
        if (error.needRelogin) runtime.onUnauthorized(error)
        if (!config.silent) toast(error.message)
        throw error
    } catch (raw) {
        const error = isApiError(raw) ? raw : networkError((raw as any)?.message)
        if (!config.silent) toast(error.message)
        throw error
    } finally {
        if (config.loading) releaseLoading()
    }
}

// ---------------------------------------------------------------- 便捷方法

type Extra = Omit<RequestConfig, 'url' | 'method' | 'data'>

/** 兼容 likeadmin 模板的历史写法：isAuth / ignoreCancel / withToken */
type LegacyExtra = Extra & {
    isAuth?: boolean
    ignoreCancel?: boolean
    withToken?: boolean
    urlPrefix?: string
    isTransformResponse?: boolean
    isReturnDefaultResponse?: boolean
}

function normalizeExtra(extra?: LegacyExtra): Extra {
    if (!extra) return {}
    const {
        isAuth,
        ignoreCancel,
        withToken,
        urlPrefix,
        isTransformResponse,
        isReturnDefaultResponse,
        ...rest
    } = extra
    return { ...rest, auth: rest.auth ?? isAuth }
}

/** 同时支持 request.post('/order/create', data) 与 request.post({ url, data }, { isAuth }) */
function normalizeCall(
    a: string | (RequestConfig & LegacyExtra),
    b?: any,
    c?: LegacyExtra
): RequestConfig {
    if (typeof a === 'string') {
        return { ...normalizeExtra(c), url: a, data: b }
    }
    const {
        isAuth,
        ignoreCancel,
        withToken,
        urlPrefix,
        isTransformResponse,
        isReturnDefaultResponse,
        ...rest
    } = a
    return { ...normalizeExtra(b), ...rest, auth: rest.auth ?? isAuth }
}

const requestApi = {
    raw: request,
    get<T = any>(a: string | (RequestConfig & LegacyExtra), b?: any, c?: LegacyExtra): Promise<T> {
        return request<T>({ ...normalizeCall(a, b, c), method: 'GET' })
    },
    post<T = any>(a: string | (RequestConfig & LegacyExtra), b?: any, c?: LegacyExtra): Promise<T> {
        return request<T>({ ...normalizeCall(a, b, c), method: 'POST' })
    },
    upload,
    /** 历史别名：模板 api/app.ts 用 uploadFile */
    uploadFile: upload,
    configure: configureRequest
}

export { requestApi as request }
export default requestApi
