import { createRequest } from '~~/utils/http'

export default defineNuxtPlugin(() => {
    const request = createRequest()
    // 项目内统一请求实例，供 api/*.ts 直接使用（$request.xxx）
    //@ts-ignore 添加
    globalThis.$request = request

    // 注意：这里**不能**覆盖 globalThis.$fetch。
    // Nuxt 的 $fetch / useAsyncData / useFetch 依赖全局 $fetch 契约（payload 序列化、
    // 请求去重、错误规范化等），覆盖后 SSR 与预渲染拿不到数据，只能生成空壳 HTML。
    // 业务请求请统一走 $request，或使用 $fetch.create() 派生的独立实例。
})
