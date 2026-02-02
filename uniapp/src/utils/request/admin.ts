import HttpRequest from './http'
import { merge } from 'lodash-es'
import { HttpRequestOptions, RequestHooks } from './type'
import { RequestCodeEnum, RequestMethodsEnum } from '@/enums/requestEnums'
import appConfig from '@/config'
import { useAdminStore } from '@/stores/admin'
import { getAdminToken } from '@/utils/admin-auth'

const ADMIN_LOGIN_PAGE = '/packages/pages/admin_login/admin_login'

const requestHooks: RequestHooks = {
    requestInterceptorsHook(options, config) {
        const { urlPrefix, baseUrl, withToken } = config
        options.header = options.header ?? {}
        if (urlPrefix) {
            options.url = `${urlPrefix}${options.url}`
        }
        if (baseUrl) {
            options.url = `${baseUrl}${options.url}`
        }
        if (withToken && !options.header.token) {
            const token = getAdminToken()
            if (token) {
                options.header.token = token
            }
        }
        options.header.version = appConfig.version
        if (options.params) {
            options.data = options.params
            delete options.params
        }
        return options
    },
    async responseInterceptorsHook(response, config) {
        const { isTransformResponse, isReturnDefaultResponse, isAuth } = config
        if (isReturnDefaultResponse) {
            return response
        }
        if (!isTransformResponse) {
            return response.data
        }
        const { code, data, msg } = response.data as any
        switch (code) {
            case RequestCodeEnum.SUCCESS:
                msg && uni.$u.toast(msg)
                return data
            case RequestCodeEnum.FAILED:
                msg && uni.$u.toast(msg)
                return Promise.reject(msg)
            case RequestCodeEnum.TOKEN_INVALID:
                const adminStore = useAdminStore()
                await adminStore.logout()
                if (isAuth && !getAdminToken()) {
                    uni.navigateTo({
                        url: ADMIN_LOGIN_PAGE
                    })
                }
                return Promise.reject(msg)
            default:
                return data
        }
    },
    async responseInterceptorsCatchHook(options, error) {
        if (options.method?.toUpperCase() == RequestMethodsEnum.POST) {
            uni.$u.toast('请求失败，请重试')
        }
        return Promise.reject(error)
    }
}

const defaultOptions: HttpRequestOptions = {
    requestOptions: {
        timeout: appConfig.timeout
    },
    baseUrl: appConfig.baseUrl,
    isReturnDefaultResponse: false,
    isTransformResponse: true,
    urlPrefix: 'adminapi',
    ignoreCancel: false,
    withToken: true,
    isAuth: true,
    retryCount: 2,
    retryTimeout: 1000,
    requestHooks: requestHooks
}

function createAdminRequest(opt?: HttpRequestOptions) {
    return new HttpRequest(merge(defaultOptions, opt || {}))
}

const adminRequest = createAdminRequest()

export default adminRequest
