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
    ['order.refund/', 'ops.refund/'],
    ['order.payment/', 'ops.payment/'],
    ['aftersale.aftersale/', 'ops.aftersaleTicket/'],
    ['aftersale.complaint/', 'ops.complaint/'],
    ['aftersale.callback/', 'ops.callback/'],
    ['dynamic.dynamic/', 'growth.dynamic/'],
    ['dynamic.dynamicComment/', 'growth.dynamicComment/'],
    ['review.review/', 'growth.review/'],
    ['review.reviewTag/', 'growth.reviewTag/'],
    ['review.review_tag/', 'growth.reviewTag/'],
    ['review.sensitiveWord/', 'growth.sensitiveWord/'],
    ['review.sensitive_word/', 'growth.sensitiveWord/'],
    ['notification.notification/', 'growth.notification/'],
    ['financial.', 'finance.'],
    ['user.user/', 'content.user/'],
    ['article.article/', 'content.article/'],
    ['article.article_cate/', 'content.articleCategory/'],
    ['article.articleCate/', 'content.articleCategory/'],
    ['file/listCate', 'content.material/listCate'],
    ['channel.', 'experience.channel.'],
    ['decorate.', 'experience.decorate.']
] as const

interface BackendMenuRoute {
    paths: string
    name: string
    component?: string
    /** 菜单稳定身份，来自 la_system_menu.menu_key（见 server/database/menu/menu_manifest.php） */
    menu_key?: string
    is_show: number | boolean
    is_cache?: number | boolean
    perms?: string
    params?: string
    icon?: string
    type: number
    selected?: string
    children?: BackendMenuRoute[]
}

const staffCenterTitleMap: Record<string, string> = {
    'staff_center/profile/index': '基本资料',
    'staff_center/showcase/index': '服务展示',
    'staff_center/work/index': '我的作品',
    'staff_center/certificate/index': '我的证书',
    'staff_center/package/index': '专属套餐',
    'staff_center/couple_questionnaire/index': '新人问卷',
    'staff_center/schedule_confirm_letter/index': '档期确认函',
    'staff_center/order/index': '订单管理',
    'staff_center/dynamic/index': '内容发布',
    'staff_center/calendar/index': '我的档期',
    'staff_center/booking/index': '预约确认',
    'staff_center/team/index': '队员管理',
    'staff_center/team_review/index': '队员审核',
}

const replaceLastPathSegment = (path: string, target: string) => {
    const segments = String(path || '')
        .split('/')
        .filter(Boolean)
    if (!segments.length) {
        return target
    }
    segments[segments.length - 1] = target
    return segments.join('/')
}

const createStaffCenterSiblingRoute = (
    baseRoute: BackendMenuRoute,
    target: { component: string; name: string; path: string; perms: string },
): BackendMenuRoute => ({
    ...baseRoute,
    name: target.name,
    component: target.component,
    paths: replaceLastPathSegment(baseRoute.paths, target.path),
    perms: target.perms,
    is_show: 1,
})

// 旧版未分组老库中 profile 同级可能遗漏的页面，按展示顺序排列
const staffCenterProfileSiblings = [
    {
        component: 'staff_center/showcase/index',
        name: '服务展示',
        path: 'showcase',
        perms: 'ops.staff/myProfile'
    },
    {
        component: 'staff_center/work/index',
        name: '我的作品',
        path: 'work',
        perms: 'ops.staffWork/lists'
    },
    {
        component: 'staff_center/certificate/index',
        name: '我的证书',
        path: 'certificate',
        perms: 'ops.staffCertificate/lists'
    },
    {
        component: 'staff_center/package/index',
        name: '专属套餐',
        path: 'package',
        perms: 'ops.staff/myProfilePackageConfig'
    },
    {
        component: 'staff_center/couple_questionnaire/index',
        name: '新人问卷',
        path: 'couple-questionnaire',
        perms: 'ops.staff/myCoupleQuestionnaireConfig'
    },
    {
        component: 'staff_center/schedule_confirm_letter/index',
        name: '档期确认函',
        path: 'schedule-confirm-letter',
        perms: 'ops.staff/myScheduleConfirmLetterConfig'
    }
]

// 递归收集整棵路由树已有的 component，供 normalizeStaffCenterMenus 去重
const collectExistingComponents = (routes: BackendMenuRoute[], acc: Set<string> = new Set()): Set<string> => {
    routes.forEach((route) => {
        if (route.component) {
            acc.add(route.component)
        }
        if (Array.isArray(route.children) && route.children.length) {
            collectExistingComponents(route.children, acc)
        }
    })
    return acc
}

function normalizeStaffCenterMenus(routes: BackendMenuRoute[], existingComponents: Set<string>): BackendMenuRoute[] {
    return routes.map((route) => {
        const normalized: BackendMenuRoute = {
            ...route,
            name: route.component ? staffCenterTitleMap[route.component] || route.name : route.name,
        }

        if (normalized.component === 'staff_center/booking/index') {
            normalized.is_show = 0
        }

        if (Array.isArray(route.children) && route.children.length) {
            const nextChildren = normalizeStaffCenterMenus(route.children, existingComponents)
            const profileIndex = nextChildren.findIndex((item) => item.component === 'staff_center/profile/index')

            if (profileIndex >= 0) {
                const profileRoute = nextChildren[profileIndex]
                // 只补入整棵树尚不存在的（旧版未分组老库遗漏）；已在其他分组（如「履约与日程」）的绝不重复插入
                const missingSiblings = staffCenterProfileSiblings
                    .filter((target) => !existingComponents.has(target.component))
                    .map((target) => createStaffCenterSiblingRoute(profileRoute, target))
                if (missingSiblings.length) {
                    nextChildren.splice(profileIndex + 1, 0, ...missingSiblings)
                }
            }

            normalized.children = nextChildren
        }

        return normalized
    })
}

//
export function getModulesKey() {
    return Object.keys(modules).map((item) => item.replace('/src/views/', '').replace('.vue', ''))
}

// 过滤路由所需要的数据
export function filterAsyncRoutes(routes: any[], firstRoute = true) {
    // normalizeStaffCenterMenus 自身已递归处理 children，只在顶层跑一次，
    // 避免每层递归重复执行把已在其他分组的页面重复插回 profile 同级
    const normalizedRoutes = firstRoute
        ? normalizeStaffCenterMenus(
              routes as BackendMenuRoute[],
              collectExistingComponents(routes as BackendMenuRoute[])
          )
        : (routes as BackendMenuRoute[])
    return normalizedRoutes.map((route) => {
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
            activeMenu: route.selected,
            // 菜单稳定身份：跳转用 getMenuPath(menuKey) 而不是硬编码 perms/path，
            // 这样 B2 重排菜单、改 perms 都不会打断页面之间的跳转。
            menuKey: route.menu_key
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

/**
 * 按菜单稳定身份查路由路径。
 *
 * 比 getRoutePath(perms) 更可靠：perms 是后端控制器的物理路径，改目录/改名就会断；
 * menuKey 来自清单，重排、改名、改 perms 都不变。B2 起新代码一律用这个。
 * 查不到返回 ''（与 getRoutePath 一致，调用方按「没有就隐藏入口」处理，不抛错）。
 */
export function getMenuPath(menuKey: string) {
    if (!menuKey) {
        return ''
    }
    const routerObj = useRouter() || router
    const hit = routerObj.getRoutes().find((item) => item.meta?.menuKey === menuKey)
    return hit?.path || ''
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
