import { httpError, networkError } from './error'

export interface RawResponse {
    statusCode: number
    data: any
    header: Record<string, string>
}

/**
 * uni.request 的 Promise 包装。
 * 注意：uni.request 在 HTTP 非 200 时仍然会走 success，必须由调用方检查 statusCode。
 */
export function rawRequest(options: UniApp.RequestOptions): Promise<RawResponse> {
    return new Promise((resolve, reject) => {
        uni.request({
            ...options,
            success: (res) => {
                resolve({
                    statusCode: res.statusCode,
                    data: res.data,
                    header: (res.header || {}) as Record<string, string>
                })
            },
            fail: (err) => reject(networkError(err?.errMsg))
        })
    })
}

export interface RawUploadResponse {
    statusCode: number
    data: string
}

/**
 * uni.uploadFile 的 Promise 包装。
 *
 * 修复老实现的致命 bug：老代码在 statusCode !== 200 时既不 resolve 也不 reject，
 * 导致 Promise 永久挂起、hideLoading 永不执行、页面卡死。
 * 这里任何非 200 都必须 reject。
 */
export function rawUpload(
    options: UniApp.UploadFileOption,
    onProgress?: (percent: number) => void
): Promise<RawUploadResponse> {
    return new Promise((resolve, reject) => {
        const task = uni.uploadFile({
            ...options,
            success: (res) => {
                if (res.statusCode !== 200) {
                    reject(httpError(res.statusCode))
                    return
                }
                resolve({ statusCode: res.statusCode, data: String(res.data ?? '') })
            },
            fail: (err) => reject(networkError(err?.errMsg))
        })
        if (onProgress && task && typeof task.onProgressUpdate === 'function') {
            task.onProgressUpdate((res) => onProgress(res.progress))
        }
    })
}
