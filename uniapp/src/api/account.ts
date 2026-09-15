import { client } from '@/utils/client'
import request from '@/utils/request'

// 登录
export function login(data: Record<string, any>) {
    return request.post(
        { url: '/login/account', data: { ...data, terminal: client } },
        { withToken: false }
    )
}

//注册
export function register(data: Record<string, any>) {
    return request.post({ url: '/login/register', data: { ...data, channel: client } })
}





export function mnpLogin(data: Record<string, any>) {
    return request.post({ url: '/login/mnpLogin', data }, { withToken: false })
}

//更新微信小程序头像昵称
export function updateUser(data: Record<string, any>, header: any) {
    return request.post({ url: '/login/updateUser', data, header })
}

//小程序绑定微信
export function mnpAuthBind(data: Record<string, any>) {
    return request.post({ url: '/login/mnpAuthBind', data })
}
