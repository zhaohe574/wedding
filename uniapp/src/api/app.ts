import appConfig from '@/config'
import request from '@/utils/request'

export interface UploadResult {
    id?: number
    cid?: number
    type?: number | string
    name?: string
    uri: string
    url: string
    relativeUrl: string
    [key: string]: any
}

const getStringValue = (value: unknown) => {
    return typeof value === 'string' ? value.trim() : ''
}

const isAbsoluteUrl = (url: string) => /^https?:\/\//i.test(url)

const toAbsoluteUploadUrl = (url: string) => {
    if (!url) {
        return ''
    }
    if (isAbsoluteUrl(url)) {
        return url
    }
    const baseUrl = getStringValue(appConfig.baseUrl).replace(/\/+$/, '')
    const path = url.startsWith('/') ? url : `/${url}`
    return baseUrl ? `${baseUrl}${path}` : path
}

const normalizeUploadResult = (result: any): UploadResult => {
    const rawResult = result && typeof result === 'object' ? result : {}
    const rawUri = getStringValue(rawResult.uri)
    const rawUrl = getStringValue(rawResult.url)
    const fullUrl = toAbsoluteUploadUrl(rawUri || rawUrl)

    return {
        ...rawResult,
        uri: fullUrl,
        url: fullUrl,
        relativeUrl: isAbsoluteUrl(rawUrl) ? '' : rawUrl
    }
}

const uploadMedia = async (
    file: any,
    fileType: 'image' | 'video',
    url: string,
    token?: string
): Promise<UploadResult> => {
    const result = await request.uploadFile({
        url,
        filePath: file,
        name: 'file',
        header: {
            token
        },
        fileType
    })

    return normalizeUploadResult(result)
}

//发送短信
export function smsSend(data: any) {
    return request.post({ url: '/sms/sendCode', data: data })
}

export function getConfig() {
    return request.get({ url: '/index/config' })
}

export function getPolicy(data: any) {
    return request.get({ url: '/index/policy', data: data })
}

export function uploadImage(file: any, token?: string) {
    return uploadMedia(file, 'image', '/upload/image', token)
}

export function uploadVideo(file: any, token?: string) {
    return uploadMedia(file, 'video', '/upload/video', token)
}

export function wxJsConfig(data: any) {
    return request.get({ url: '/wechat/jsConfig', data })
}
