import { merge } from 'lodash-es'
import { isFunction } from '@vue/shared'
import {
    HttpRequestOptions,
    RequestConfig,
    RequestInput,
    RequestOptions,
    RequestTask,
    UploadFileOption
} from './type'
import { RequestErrMsgEnum, RequestMethodsEnum } from '@/enums/requestEnums'
import requestCancel, { createRequestKey } from './cancel'

export default class HttpRequest {
    private readonly options: HttpRequestOptions
    constructor(options: HttpRequestOptions) {
        this.options = options
    }

    private normalizeRequestOptions(
        options: RequestInput,
        method?: RequestMethodsEnum
    ): RequestOptions {
        const normalized = typeof options === 'string' ? { url: options } : { ...options }
        if (method) {
            normalized.method = method
        }
        return normalized
    }
    /**
     * @description 重新请求
     */
    retryRequest(options: RequestOptions, config: RequestConfig) {
        const { retryCount, retryTimeout } = config
        if (!retryCount || options.method?.toUpperCase() == RequestMethodsEnum.POST) {
            return Promise.reject()
        }
        uni.showLoading({ title: '加载中...' })
        config.hasRetryCount = config.hasRetryCount ?? 0
        if (config.hasRetryCount >= retryCount) {
            return Promise.reject()
        }
        config.hasRetryCount++
        config.requestHooks.requestInterceptorsHook = (options) => options
        return new Promise((resolve) => setTimeout(resolve, retryTimeout))
            .then(() => this.request(options, config))
            .finally(() => uni.hideLoading())
    }
    /**
     * @description get请求
     */
    get<T = any>(options: RequestInput, config?: Partial<RequestConfig>): Promise<T> {
        return this.request(this.normalizeRequestOptions(options, RequestMethodsEnum.GET), config)
    }

    /**
     * @description post请求
     */
    post<T = any>(options: RequestInput, config?: Partial<RequestConfig>): Promise<T> {
        return this.request(this.normalizeRequestOptions(options, RequestMethodsEnum.POST), config)
    }

    /**
     * @description 上传图片
     */
    uploadFile(options: UploadFileOption, config?: Partial<RequestConfig>) {
        let mergeOptions: RequestOptions = merge({}, this.options.requestOptions, options)
        const mergeConfig: RequestConfig = merge({}, this.options, config)
        const { requestInterceptorsHook, responseInterceptorsHook, responseInterceptorsCatchHook } =
            mergeConfig.requestHooks || {}
        if (requestInterceptorsHook && isFunction(requestInterceptorsHook)) {
            mergeOptions = requestInterceptorsHook(mergeOptions, mergeConfig)
        }
        return new Promise((resolve, reject) => {
            uni.uploadFile({
                ...mergeOptions,
                success: async (response) => {
                    if (response.statusCode == 200) {
                        response.data = JSON.parse(response.data)
                        if (responseInterceptorsHook && isFunction(responseInterceptorsHook)) {
                            try {
                                response = await responseInterceptorsHook(response, mergeConfig)
                                resolve(response)
                            } catch (error) {
                                reject(error)
                            }
                            return
                        }
                        resolve(response)
                    }
                },
                fail: async (err) => {
                    if (
                        responseInterceptorsCatchHook &&
                        isFunction(responseInterceptorsCatchHook)
                    ) {
                        reject(await responseInterceptorsCatchHook(mergeOptions, err))
                        return
                    }
                    reject(err)
                }
            })
        })
    }
    /**
     * @description 请求函数
     */
    async request(options: RequestOptions, config?: Partial<RequestConfig>): Promise<any> {
        let mergeOptions: RequestOptions = merge({}, this.options.requestOptions, options)
        const mergeConfig: RequestConfig = merge({}, this.options, config)
        const { requestInterceptorsHook, responseInterceptorsHook, responseInterceptorsCatchHook } =
            mergeConfig.requestHooks || {}
        if (requestInterceptorsHook && isFunction(requestInterceptorsHook)) {
            mergeOptions = requestInterceptorsHook(mergeOptions, mergeConfig)
        }

        const { ignoreCancel, duplicateStrategy } = mergeConfig
        const shouldTrackRequest = !ignoreCancel && duplicateStrategy !== 'allow'
        const requestKey = shouldTrackRequest ? createRequestKey(mergeOptions) : ''

        if (shouldTrackRequest) {
            const pendingRequest = requestCancel.get(requestKey)
            if (pendingRequest) {
                if (duplicateStrategy === 'join') {
                    return pendingRequest.promise
                }
                requestCancel.cancel(requestKey)
            }
        }

        let requestTask: RequestTask | undefined
        const requestPromise = new Promise((resolve, reject) => {
            requestTask = uni.request({
                ...mergeOptions,
                async success(response) {
                    if (responseInterceptorsHook && isFunction(responseInterceptorsHook)) {
                        try {
                            response = await responseInterceptorsHook(response, mergeConfig)
                            resolve(response)
                        } catch (error) {
                            reject(error)
                        }
                        return
                    }
                    resolve(response)
                },
                fail: async (err) => {
                    if (err.errMsg == RequestErrMsgEnum.TIMEOUT) {
                        this.retryRequest(mergeOptions, mergeConfig)
                            .then((res) => resolve(res))
                            .catch((err) => reject(err))
                        return
                    }

                    if (
                        responseInterceptorsCatchHook &&
                        isFunction(responseInterceptorsCatchHook)
                    ) {
                        reject(await responseInterceptorsCatchHook(mergeOptions, err))
                        return
                    }
                    reject(err)
                },
                complete() {
                    if (shouldTrackRequest && requestTask) {
                        requestCancel.remove(requestKey, requestTask)
                    }
                }
            })
        })

        if (shouldTrackRequest && requestTask) {
            requestCancel.add(requestKey, requestTask, requestPromise)
        }

        return requestPromise
    }
}
