import { createRouter, createWebHistory, type RouteRecordRaw, RouterView } from 'vue-router'

import { MenuEnum } from '@/enums/appEnums'
import useUserStore from '@/stores/modules/user'
import { isExternal } from '@/utils/validate'

import { constantRoutes, INDEX_ROUTE_NAME } from './routes'

// 匹配views里面所有的.vue文件，动态引入
const modules = import.meta.glob('/src/views/**/*.vue')
const ROUTE_VIEW = () => import('@/layout/components/route-view.vue')
const permissionAliasMap = [
    ['staff.staff/', 'ops.staff/'],
    ['staff.staffWork/', 'ops.staffWork/'],
    ['staff.staffCertificate/', 'ops.staffCertificate/'],
    ['staff.work/', 'ops.work/'],
    ['service.category/', 'ops.category/'],
    ['service.package/', 'ops.package/'],
    ['service.region/', 'ops.region/'],
    ['service.service_category/', 'ops.category/'],
    ['service.service_package/', 'ops.package/'],
    ['service.styleTag/', 'ops.styleTag/'],
    ['service.style_tag/', 'ops.styleTag/'],
    ['schedule.schedule/', 'ops.schedule/'],
    ['schedule.scheduleRule/', 'ops.scheduleRule/'],
    ['schedule.booking/', 'ops.booking/'],
    ['schedule.waitlist/', 'ops.waitlist/'],
    ['order.order/', 'ops.order/'],
    ['order.orderChange/', 'ops.orderChange/'],
    ['order.order_change/', 'ops.orderChange/'],
    ['order.orderPause/', 'ops.orderPause/'],
    ['order.order_pause/', 'ops.orderPause/'],
    ['order.refund/', 'ops.refund/'],
    ['order.payment/', 'ops.payment/'],
    ['aftersale.aftersale/', 'ops.aftersaleTicket/'],
    ['aftersale.complaint/', 'ops.complaint/'],
    ['aftersale.callback/', 'ops.callback/'],
    ['dynamic.dynamic/', 'growth.dynamic/'],
    ['dynamic.dynamicComment/', 'growth.dynamicComment/'],
    ['review.review/', 'growth.review/'],
    ['review.reviewAppeal/', 'growth.reviewAppeal/'],
    ['review.review_appeal/', 'growth.reviewAppeal/'],
    ['review.reviewTag/', 'growth.reviewTag/'],
    ['review.review_tag/', 'growth.reviewTag/'],
    ['review.reviewShareReward/', 'growth.reviewShareReward/'],
    ['review.sensitiveWord/', 'growth.sensitiveWord/'],
    ['review.sensitive_word/', 'growth.sensitiveWord/'],
    ['notification.notification/', 'growth.notification/'],
    ['subscribe.subscribe/', 'growth.subscribe/'],
    ['financial.', 'finance.'],
    ['finance.account_log/', 'finance.accountLog/'],
    ['recharge.recharge/', 'finance.recharge/'],
    ['user.user/', 'content.user/'],
    ['article.article/', 'content.article/'],
    ['article.article_cate/', 'content.articleCategory/'],
    ['article.articleCate/', 'content.articleCategory/'],
    ['file/listCate', 'content.material/listCate'],
    ['channel.', 'experience.channel.'],
    ['decorate.', 'experience.decorate.']
] as const

//
export function getModulesKey() {
    return Object.keys(modules).map((item) => item.replace('/src/views/', '').replace('.vue', ''))
}

// 过滤路由所需要的数据
export function filterAsyncRoutes(routes: any[], firstRoute = true) {
    return routes.map((route) => {
        const routeRecord = createRouteRecord(route, firstRoute)
        if (route.children != null && route.children && route.children.length) {
            routeRecord.children = filterAsyncRoutes(route.children, false)
        }
        return routeRecord
    })
}

// 创建一条路由记录
export function createRouteRecord(route: any, firstRoute: boolean): RouteRecordRaw {
    //@ts-ignore
    const routeRecord: RouteRecordRaw = {
        path: isExternal(route.paths) ? route.paths : firstRoute ? `/${route.paths}` : route.paths,
        name: Symbol(route.paths),
        meta: {
            hidden: !route.is_show,
            keepAlive: !!route.is_cache,
            title: route.name,
            perms: route.perms,
            query: route.params,
            icon: route.icon,
            type: route.type,
            activeMenu: route.selected
        }
    }
    switch (route.type) {
        case MenuEnum.CATALOGUE:
            routeRecord.component = ROUTE_VIEW
            break
        case MenuEnum.MENU:
            routeRecord.component = loadRouteView(route.component)
            break
    }
    return routeRecord
}

// 动态加载组件
export function loadRouteView(component: string) {
    try {
        const key = Object.keys(modules).find((key) => {
            return key.includes(`/${component}.vue`)
        })
        if (key) {
            return modules[key]
        }
        throw Error(`找不到组件${component}，请确保组件路径正确`)
    } catch (error) {
        console.error(error)
        return RouterView
    }
}

// 找到第一个有效的路由
export function findFirstValidRoute(routes: RouteRecordRaw[]): string | undefined {
    for (const route of routes) {
        if (route.meta?.type == MenuEnum.MENU && !route.meta?.hidden && !isExternal(route.path)) {
            return route.name as string
        }
        if (route.children) {
            const name = findFirstValidRoute(route.children)
            if (name) {
                return name
            }
        }
    }
}

export function expandPermissionAliases(permission: string): string[] {
    if (!permission) {
        return []
    }

    const results = new Set<string>([permission])

    permissionAliasMap.forEach(([oldPrefix, newPrefix]) => {
        if (permission.startsWith(oldPrefix)) {
            results.add(newPrefix + permission.slice(oldPrefix.length))
        }

        if (permission.startsWith(newPrefix)) {
            results.add(oldPrefix + permission.slice(newPrefix.length))
        }
    })

    return Array.from(results)
}

//通过权限字符查询路由路径
export function getRoutePath(perms: string) {
    const routerObj = useRouter() || router
    const routes = routerObj.getRoutes()
    const targetPerms = expandPermissionAliases(perms)

    return targetPerms
        .map((currentPerms) => routes.find((item) => item.meta?.perms == currentPerms)?.path || '')
        .find(Boolean) || ''
}

// 重置路由
export function resetRouter() {
    router.removeRoute(INDEX_ROUTE_NAME)
    const { routes } = useUserStore()
    routes.forEach((route) => {
        const name = route.name
        if (name && router.hasRoute(name)) {
            router.removeRoute(name)
        }
    })
}

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: constantRoutes
})

export default router
