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
    const relativeUrl = isAbsoluteUrl(rawUrl)
        ? isAbsoluteUrl(rawUri)
            ? ''
            : rawUri
        : rawUrl || (isAbsoluteUrl(rawUri) ? '' : rawUri)

    return {
        ...rawResult,
        uri: fullUrl,
        url: fullUrl,
        relativeUrl
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
    return request.get({ url: '/index/config' }, { duplicateStrategy: 'join' })
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



// 获取公众号通知绑定状态。
export function oaSubscribeStatus() {
    return request.get({ url: '/wechat/oaSubscribeStatus' }, { isAuth: true })
}

// 业务提醒采用独立静默响应处理，失败不弹网络提示或触发登录跳转。
const oaReminderRequestConfig = {
    isAuth: false, retryCount: 0, cacheTtl: 0, duplicateStrategy: 'allow' as const,
    requestHooks: {
        responseInterceptorsHook(response: any) {
            if (response.statusCode !== 200 || response.data?.code !== 1) throw new Error('提醒状态不可用')
            return response.data.data
        },
        responseInterceptorsCatchHook(_options: any, error: any) { return Promise.reject(error) }
    }
}
export function oaReminderStatus() {
    return request.get({ url: '/wechat/oaSubscribeStatus', timeout: 2000 }, oaReminderRequestConfig)
}
export function oaReminderSkipToday() {
    return request.post({ url: '/wechat/oaReminderSkipToday', timeout: 2000 }, oaReminderRequestConfig)
}

export function oaInvitationStatus(invitation: string) {
    return request.post({ url: '/wechat/oaInvitationStatus', data: { invitation } }, { isAuth: true })
}

export function oaInvitationConfirm(invitation: string) {
    return request.post({ url: '/wechat/oaInvitationConfirm', data: { invitation } }, { isAuth: true })
}

export function oaSubscribeUnbind() {
    return request.post({ url: '/wechat/oaSubscribeUnbind' }, { isAuth: true })
}


export function claimAdminBinding(bindingCode: string) {
    return request.post({ url: '/wechat/claimAdminBinding', data: { binding_code: bindingCode } }, { isAuth: true })
}
