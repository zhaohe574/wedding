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
    /**
     * 自定义幂等键。
     *
     * 不给时由请求层按「一次调用一个 key」生成，并在**重试之间复用**（同一次调用的多次网络重试
     * 会被后端识别为重放）。需要「跨多次用户操作复用同一个 key」时必须显式传：
     * 例如问卷提交失败后用户点重试，要传同一个 key 才能被后端判定为重复提交而非新提交。
     */
    idempotencyKey?: string
    /** 同参数并发去重（共享同一个 Promise），默认 true */
    dedupe?: boolean
    /** 失败重试次数，默认 GET 为 2、POST 为 0 */
    retry?: number
    /** 失败不弹 toast（由调用方自行处理） */
    silent?: boolean
    /**
     * token 失效时不触发跳登录。
     *
     * 静默检查类接口（如服务号提醒状态）即使 token 失效也不该把用户拽去登录页 ——
     * 提醒只是前置软引导，业务提交才是主流程；跳登录反而把提交打断。
     */
    skipAuthRedirect?: boolean
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
