import routes from 'uni-router-routes'
import { createRouter } from 'uniapp-router-next'

import { ClientEnum } from '@/enums/appEnums'
import { useUserStore } from '@/stores/user'
import { client } from '@/utils/client'
import { captureOaInvitation, OA_BINDING_PATH } from '@/utils/oa-invitation'



import cache from '@/utils/cache'
import { BACK_URL } from '@/enums/constantEnums'
import {
    consumeSplashHomeBypass,
    fetchSplashConfigSafely,
    shouldShowSplash,
    SPLASH_HOME_PATH,
    SPLASH_PAGE_PATH
} from '@/utils/splash'

const router = createRouter({
    routes: [
        ...routes,
        {
            path: '*',
            redirect() {
                return {
                    name: '404'
                }
            }
        }
    ],
    // 路由可能包含短期绑定邀请，开发环境也不输出完整跳转参数。
    debug: false,
    //@ts-ignore
    platform: process.env.UNI_PLATFORM,
    h5: {}
})

router.beforeEach((to) => {
    captureOaInvitation(to.path, to.query as Record<string, any>)
    // 当前路由库的同步守卫必须明确放行，否则所有后续跳转都会一直等待。
    return true
})

// 开屏广告首页入口保护：直接进入首页时按频率引导到独立开屏页。
router.beforeEach(async (to, from) => {
    if (to.path !== SPLASH_HOME_PATH || from.path === SPLASH_PAGE_PATH) {
        return
    }
    if (consumeSplashHomeBypass()) {
        return
    }

    try {
        const config = await fetchSplashConfigSafely()
        if (shouldShowSplash(config, to.query as Record<string, any>)) {
            return SPLASH_PAGE_PATH
        }
    } catch (error) {
        console.error('开屏广告守卫失败', error)
    }
})

//存储登陆前的页面
let isFirstEach = true
router.beforeEach(async (to, from) => {
    //保存第一次进来时的页面路径（需要登陆才能访问的页面）
    if (isFirstEach) {
        const userStore = useUserStore()
        if (!userStore.isLogin && !to.meta.white) {
            cache.set(BACK_URL, to.path === OA_BINDING_PATH ? OA_BINDING_PATH : to.fullPath)
        }
        isFirstEach = false
    }
})
router.afterEach((to, from) => {
    const userStore = useUserStore()
    if (!userStore.isLogin && !to.meta.white) {
        cache.set(BACK_URL, to.path === OA_BINDING_PATH ? OA_BINDING_PATH : to.fullPath)
    }
})

// 登录拦截
router.beforeEach(async (to, from) => {
    const userStore = useUserStore()
    if (!userStore.isLogin && to.meta.auth) {
        return '/pages/login/login'
    }
})


































export default router
