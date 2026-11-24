import request from '@/request'

/**
 * 通用/平台级接口。
 * 路径均对应 server/app/api/controller 下的真实 action，改路径前先跑 `npm run endpoints:audit`。
 */

// 发送短信验证码（scene: YZMDL 登录 / BDSJHM 绑定手机 / BGSJHM 变更手机 / ZHDLMM 找回密码）
// @endpoint sms.sendCode
export function smsSend(data: { mobile: string; scene: string; [k: string]: any }) {
    return request.post<{ code: string; expire?: number }>('/sms/sendCode', data)
}

// @endpoint index.config
export function getConfig() {
    return request.get<any>('/index/config')
}

// @endpoint index.policy
export function getPolicy(data: { type: string }) {
    return request.get<{ title: string; content: string }>('/index/policy', data)
}

// @endpoint upload.image
export function uploadImage(
    filePath: string,
    onProgress?: (p: number) => void,
    header?: Record<string, string>
) {
    return request.upload<{ uri: string; url?: string }>({
        url: '/upload/image',
        filePath,
        name: 'file',
        auth: true,
        loading: true,
        header,
        onProgress
    })
}

// @endpoint upload.video
export function uploadVideo(filePath: string, onProgress?: (p: number) => void) {
    return request.upload<{ uri: string; url?: string }>({
        url: '/upload/video',
        filePath,
        name: 'file',
        auth: true,
        loading: true,
        onProgress
    })
}
