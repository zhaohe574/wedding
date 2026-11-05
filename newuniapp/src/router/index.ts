import routes from 'uni-router-routes'
import { createRouter } from 'uniapp-router-next'

import { useUserStore } from '@/stores/user'
import cache from '@/utils/cache'
import { BACK_URL } from '@/enums/constantEnums'

/**
 * 路由守卫。
 *
 * 规则：
 *   1. 未登录访问 meta.auth 页面 → 跳登录，并把目标路径存进 BACK_URL，登录后回跳
 *   2. 登录页在白名单（meta.white），不拦截
 *   3. 只做跳转决策，不做业务判断 —— 业务判断放页面或 composable
 */
const router = createRouter({
    routes: [
        ...routes,
        {
            path: '*',
            redirect() {
                return { path: '/pages/index/index' }
            }
        }
    ],
    debug: import.meta.env.DEV,
    //@ts-ignore uni 平台标识
    platform: process.env.UNI_PLATFORM,
    h5: {}
})

router.beforeEach((to: any) => {
    const userStore = useUserStore()

    if (to?.meta?.auth && !userStore.isLogin) {
        cache.set(BACK_URL, to.fullPath)
        return '/pages/login/login'
    }

    return true
})

export default router
