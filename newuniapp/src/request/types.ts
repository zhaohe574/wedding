import type { RetryStrategy } from '@shared/contracts/error-codes'

export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'DELETE'

export interface RequestConfig {
    /** 相对路径，如 '/order/lists'；不需要带 /api 前缀 */
    url: string
    method?: HttpMethod
    data?: Record<string, any> | any[]
    header?: Record<string, string>
    /** 超时毫秒，默认取全局配置 */
    timeout?: number
    /** 是否显示全局 loading（引用计数，多请求并发只显示一次） */
    loading?: boolean
    /** 需要登录：无 token 时直接抛 TOKEN_EXPIRED，不发请求 */
    auth?: boolean
    /** 写接口幂等：自动注入 Idempotency-Key */
    idempotent?: boolean
    /** 同参数并发去重（共享同一个 Promise），默认 true */
    dedupe?: boolean
    /** 失败重试次数，默认 GET 为 2、POST 为 0 */
    retry?: number
    /** 失败不弹 toast（由调用方自行处理） */
    silent?: boolean
    /** 覆盖 baseUrl（极少使用，如上传走独立 CDN） */
    baseUrl?: string
}

export interface UploadConfig {
    url: string
    filePath: string
    name?: string
    formData?: Record<string, any>
    header?: Record<string, string>
    loading?: boolean
    auth?: boolean
    silent?: boolean
    /** 覆盖 baseUrl（如独立上传域名） */
    baseUrl?: string
    onProgress?: (percent: number) => void
}

export interface RequestContext {
    errorCode?: string
    retryStrategy: RetryStrategy
    statusCode?: number
    requestId?: string
    /** 业务失败时服务端可能带回的结构化数据（如冲突日期列表） */
    data?: unknown
}

/** 统一分页结构：屏蔽后端三种分页形态 */
export interface Paginated<T> {
    list: T[]
    total: number
    page: number
    pageSize: number
    hasMore: boolean
}
