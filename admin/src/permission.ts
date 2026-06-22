/**
 * 权限控制
 */

import 'nprogress/nprogress.css'

import NProgress from 'nprogress'

import config from './config'
import { PageEnum } from './enums/pageEnum'
import router, { findFirstValidRoute } from './router'
import { INDEX_ROUTE, INDEX_ROUTE_NAME } from './router/routes'
import useTabsStore from './stores/modules/multipleTabs'
import useUserStore from './stores/modules/user'
import { clearAuthInfo } from './utils/auth'

// NProgress配置
NProgress.configure({ showSpinner: false })

const loginPath = PageEnum.LOGIN
const defaultPath = PageEnum.INDEX
const forcePasswordResetPath = PageEnum.USER_SETTING
// 免登录白名单
const whiteList: string[] = [PageEnum.LOGIN, PageEnum.ERROR_403]
const hasRoutePermission = (perms: string[], routePerms: unknown) => {
    if (!routePerms) {
        return true
    }
    if (perms.includes('*')) {
        return true
    }
    const values = Array.isArray(routePerms) ? routePerms : [routePerms]
    return values.some((item) => typeof item === 'string' && perms.includes(item))
}
router.beforeEach(async (to, from, next) => {
    // 开始 Progress Bar
    NProgress.start()
    document.title = to.meta.title ?? config.title
    const userStore = useUserStore()
    const tabsStore = useTabsStore()
    if (whiteList.includes(to.path)) {
        // 在免登录白名单，直接进入
        next()
    } else if (userStore.token) {
        // 获取用户信息
        const hasGetUserInfo = Object.keys(userStore.userInfo).length !== 0
        if (hasGetUserInfo) {
            if (to.path === loginPath) {
                next({ path: defaultPath })
            } else if (userStore.forcePasswordReset && to.path !== forcePasswordResetPath) {
                next({ path: forcePasswordResetPath, query: { force: '1' } })
            } else if (!hasRoutePermission(userStore.perms, to.meta?.perms)) {
                next(PageEnum.ERROR_403)
            } else {
                next()
            }
        } else {
            try {
                await userStore.getUserInfo()
                const routes = userStore.routes
                // 找到第一个有效路由
                const routeName = findFirstValidRoute(routes)
                // 没有有效路由跳转到403页面
                if (!routeName) {
                    clearAuthInfo()
                    next(PageEnum.ERROR_403)
                    return
                }
                tabsStore.setRouteName(routeName!)
                INDEX_ROUTE.redirect = { name: routeName }

                // 动态添��index路由
                router.addRoute(INDEX_ROUTE)

                // 直接添加已转换好的路由，所有路由都作为 INDEX_ROUTE_NAME 的子路由
                routes.forEach((route) => {
                    router.addRoute(INDEX_ROUTE_NAME, route)
                })

                if (userStore.forcePasswordReset && to.path !== forcePasswordResetPath) {
                    next({ path: forcePasswordResetPath, query: { force: '1' }, replace: true })
                    return
                }

                if (!hasRoutePermission(userStore.perms, to.meta?.perms)) {
                    next(PageEnum.ERROR_403)
                    return
                }

                next({ ...to, replace: true })
            } catch (err) {
                clearAuthInfo()
                next({ path: loginPath, query: { redirect: to.fullPath } })
            }
        }
    } else {
        next({ path: loginPath, query: { redirect: to.fullPath } })
    }
})

router.afterEach(() => {
    NProgress.done()
})
