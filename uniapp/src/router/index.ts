import routes from 'uni-router-routes'
import { createRouter } from 'uniapp-router-next'

import { ClientEnum } from '@/enums/appEnums'
import { useUserStore } from '@/stores/user'
import { client } from '@/utils/client'
// #ifdef H5
import wechatOa from '@/utils/wechat'
// #endif
import cache from '@/utils/cache'
import { BACK_URL } from '@/enums/constantEnums'
import {
    consumeSplashHomeBypass,
    fetchSplashConfig,
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
    debug: import.meta.env.DEV,
    //@ts-ignore
    platform: process.env.UNI_PLATFORM,
    h5: {}
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
        const config = await fetchSplashConfig()
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
            cache.set(BACK_URL, to.fullPath)
        }
        isFirstEach = false
    }
})
router.afterEach((to, from) => {
    const userStore = useUserStore()
    if (!userStore.isLogin && !to.meta.white) {
        cache.set(BACK_URL, to.fullPath)
    }
})

// 登录拦截
router.beforeEach(async (to, from) => {
    const userStore = useUserStore()
    if (!userStore.isLogin && to.meta.auth) {
        return '/pages/login/login'
    }
})

// #ifdef H5
//用于收集微信公众号的授权的code，并清除路径上微信带的参数
router.beforeEach(async (to, form) => {
    const { code, state, scene } = to.query

    if (code && state && scene) {
        wechatOa.setAuthData({
            code,
            scene
        })
        //收集完删除路径上的参数
        delete to.query.code
        delete to.query.state
        return {
            path: to.path,
            force: true,
            navType: 'reLaunch',
            query: to.query
        }
    }
})
// #endif

// #ifdef H5
router.afterEach((to, from) => {
    setTimeout(async () => {
        if (client == ClientEnum.OA_WEIXIN && !to.meta.webview) {
            // jssdk配置
            await wechatOa.config()
        }
    })
})
// #endif
export default router
