import { merge } from 'lodash-es'
import { HttpRequestOptions, RequestConfig, RequestInput, RequestOptions, RequestTask, UploadFileOption } from './type'
import { RequestErrMsgEnum, RequestMethodsEnum } from '@/enums/requestEnums'
import requestCancel, { createRequestKey, getRequestSession } from './cancel'

type CachedResponse = { expire: number; value: any }
const responseCache = new Map<string, CachedResponse>()
const MAX_RESPONSE_CACHE = 50
let cacheVersion = 0

export const clearResponseCache = () => {
    cacheVersion++
    responseCache.clear()
}

export default class HttpRequest {
    constructor(private readonly options: HttpRequestOptions) {}

    get<T = any>(options: RequestInput, config?: Partial<RequestConfig>): Promise<T> {
        return this.request({ ...(typeof options === 'string' ? { url: options } : options), method: RequestMethodsEnum.GET }, config)
    }

    post<T = any>(options: RequestInput, config?: Partial<RequestConfig>): Promise<T> {
        return this.request({ ...(typeof options === 'string' ? { url: options } : options), method: RequestMethodsEnum.POST }, config)
    }

    uploadFile(options: UploadFileOption, config?: Partial<RequestConfig>) {
        const settings: RequestConfig = merge({}, this.options, config)
        const hooks = settings.requestHooks || {}
        let input: any = merge({}, this.options.requestOptions, options)
        if (hooks.requestInterceptorsHook) input = hooks.requestInterceptorsHook(input, settings)
        const session = getRequestSession()
        return new Promise((resolve, reject) => {
            uni.uploadFile({
                ...input,
                success: async (response: UniApp.UploadFileSuccessCallbackResult) => {
                    try {
                        if (session !== getRequestSession()) throw new Error('登录状态已变化')
                        if (response.statusCode < 200 || response.statusCode >= 300) throw new Error(`上传失败（HTTP ${response.statusCode}）`)
                        try {
                            response.data = typeof response.data === 'string' ? JSON.parse(response.data) : response.data
                        } catch {
                            throw new Error('服务器返回格式错误')
                        }
                        const result = hooks.responseInterceptorsHook ? await hooks.responseInterceptorsHook(response, settings) : response
                        if (session !== getRequestSession()) throw new Error('登录状态已变化')
                        resolve(result)
                    } catch (error) { reject(error) }
                },
                fail: async (error: UniApp.GeneralCallbackResult) => {
                    try {
                        if (hooks.responseInterceptorsCatchHook) await hooks.responseInterceptorsCatchHook(input, error)
                        reject(error)
                    } catch (failure) { reject(failure) }
                }
            })
        })
    }

    async request(options: RequestOptions, config?: Partial<RequestConfig>): Promise<any> {
        const settings: RequestConfig = merge({}, this.options, config)
        const hooks = settings.requestHooks || {}
        let input: RequestOptions = merge({}, this.options.requestOptions, options)
        if (hooks.requestInterceptorsHook) input = hooks.requestInterceptorsHook(input, settings)
        const reading = String(input.method || 'GET').toUpperCase() === RequestMethodsEnum.GET
        const key = createRequestKey(input)
        const session = getRequestSession()
        const strategy = settings.forceRefresh ? 'cancel' : settings.duplicateStrategy
        const tracked = !settings.ignoreCancel && strategy !== 'allow'
        const ttl = reading ? Number(settings.cacheTtl || 0) : 0
        if (!reading || settings.forceRefresh) clearResponseCache()
        const revision = cacheVersion
        if (ttl > 0 && !settings.forceRefresh) {
            const cached = responseCache.get(key)
            if (cached && cached.expire > Date.now()) return merge(Array.isArray(cached.value) ? [] : {}, cached.value)
            responseCache.delete(key)
        }
        if (tracked) {
            const pending = requestCancel.get(key)
            if (pending) {
                if (strategy === 'join') return pending.promise
                requestCancel.cancel(key)
            }
        }
        let task: RequestTask | undefined
        let cancelled = false
        let retryTimer: ReturnType<typeof setTimeout> | undefined
        let rejectRequest: (reason: any) => void = () => {}
        // 同一逻辑请求在超时重试期间仍共享一个 Promise，退出账号会终止重试。
        const trackingTask = {
            abort() {
                cancelled = true
                if (retryTimer) clearTimeout(retryTimer)
                task?.abort()
                rejectRequest(new Error('请求已取消'))
            }
        } as RequestTask
        const checkCurrent = () => {
            if (cancelled || session !== getRequestSession()) throw new Error('请求已失效')
            if (ttl > 0 && revision !== cacheVersion) throw new Error('数据已更新，请刷新后重试')
        }
        const promise = new Promise((resolve, reject) => {
            rejectRequest = reject
            const perform = (attempt: number) => {
                try {
                    checkCurrent()
                    task = uni.request({
                        ...input,
                        success: async (response) => {
                            try {
                                checkCurrent()
                                if (response.statusCode < 200 || response.statusCode >= 300) throw new Error(`请求失败（HTTP ${response.statusCode}）`)
                                const result = hooks.responseInterceptorsHook ? await hooks.responseInterceptorsHook(response, settings) : response
                                checkCurrent()
                                if (!reading) clearResponseCache()
                                if (ttl > 0) {
                                    responseCache.set(key, { expire: Date.now() + ttl * 1000, value: result })
                                    while (responseCache.size > MAX_RESPONSE_CACHE) responseCache.delete(responseCache.keys().next().value as string)
                                }
                                resolve(ttl > 0 && result && typeof result === 'object' ? merge(Array.isArray(result) ? [] : {}, result) : result)
                            } catch (error) { reject(error) }
                        },
                        fail: async (error) => {
                            try {
                                checkCurrent()
                                if (reading && error.errMsg === RequestErrMsgEnum.TIMEOUT && attempt < settings.retryCount) {
                                    retryTimer = setTimeout(() => perform(attempt + 1), settings.retryTimeout)
                                    return
                                }
                                if (hooks.responseInterceptorsCatchHook) await hooks.responseInterceptorsCatchHook(input, error)
                                reject(error)
                            } catch (failure) { reject(failure) }
                        }
                    })
                } catch (error) { reject(error) }
            }
            perform(0)
        })
        if (tracked) requestCancel.add(key, trackingTask, promise)
        try {
            return await promise
        } finally {
            if (tracked) requestCancel.remove(key, trackingTask)
            if (retryTimer) clearTimeout(retryTimer)
        }
    }
}
