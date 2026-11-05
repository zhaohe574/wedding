/**
 * 微信登录能力封装。
 * 只负责拿 code / 手机号 code，换 token 由 api/auth.ts 负责。
 */

export interface WxLoginResult {
    code: string
}

/** uni.login → code（小程序端） */
export function wxLogin(): Promise<WxLoginResult> {
    return new Promise((resolve, reject) => {
        uni.login({
            provider: 'weixin',
            success: (res) => {
                if (res.code) resolve({ code: res.code })
                else reject(new Error('微信登录失败：未返回 code'))
            },
            fail: (err) => reject(new Error(err?.errMsg || '微信登录失败'))
        })
    })
}

/**
 * getPhoneNumber 回调里拿到的 code（手机号快速验证）。
 * 注意：需要在按钮上写 open-type="getPhoneNumber" 并绑定 getphonenumber 事件，
 * 回调 detail.code 才是可用的，detail.encryptedData 已不推荐。
 */
export function extractPhoneCode(e: any): string | null {
    const code = e?.detail?.code
    if (typeof code === 'string' && code) return code
    return null
}

/** 用户是否取消了手机号授权 */
export function isPhoneAuthDenied(e: any): boolean {
    const msg = String(e?.detail?.errMsg || '')
    return msg.includes('deny') || msg.includes('cancel')
}
