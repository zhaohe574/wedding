/**
 * @description 获取版本号
 */
export function getVersion() {
    return String(useRuntimeConfig().public.version || '')
}

/**
 * @description 获取请求域名
 */
export function getApiUrl() {
    return String(useRuntimeConfig().public.apiUrl || '')
}

/**
 * @description 获取请求前缀
 */
export function getApiPrefix() {
    return String(useRuntimeConfig().public.apiPrefix || '')
}
