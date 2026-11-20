import routes from 'uni-router-routes'
import { createRouter } from 'uniapp-router-next'

import { useUserStore } from '@/stores/user'
import cache from '@/utils/cache'
import { BACK_URL, BACK_URL_AT } from '@/enums/constantEnums'

/**
 * 路由守卫。
 *
 * 规则：
 *   1. 未登录访问 meta.auth 页面 → 跳登录，并把目标路径存进 BACK_URL，登录后回跳
 *   2. 登录页在白名单（meta.white），不拦截
 *   3. 只做跳转决策，不做业务判断 —— 业务判断放页面或 composable
 *
 * ⚠️ BACK_URL 只代表「被拦下来的那一页」，不等于「登录后一律去那里」。
 *   内页里点某个动作（收藏 / 预约 / 提交）才触发登录的，用户本该回到原页面、
 *   并且保留已选内容，那种情况不能写 BACK_URL —— 写了就会把用户带到别处、
 *   甚至因为页面被重建而丢掉选择。登录页据此区分两种来源，见 login.vue 的 finish()。
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
        // 时间戳：登录页只认「刚刚被拦下」的目标，避免用户放弃登录后再进来被带到旧页面
        cache.set(BACK_URL_AT, Date.now())
        return '/pages/login/login'
    }

    return true
})

export default router
