import {
    PAGE_CONTRACT_FALLBACK,
    PAGE_SCOPE_MATRIX,
    TABBAR_PAGE_PATHS,
    USER_ROLE_ENTRY_DEFINITIONS,
    type PageContractBack,
    type PageContractDefinition,
    type PageContractSource
} from '@/constants/page-contract'
import type { WmScene } from '@/design/theme'

type MaybeRecord = Record<string, any> | undefined

export interface PageShellProtocolInput {
    routePath?: string
    declaredScene?: string
    declaredSource?: string
    declaredBack?: string
}

export interface PageShellProtocol {
    routePath: string
    contract: PageContractDefinition
    scene: WmScene
    source: PageContractSource
    back: PageContractBack
    isTabBar: boolean
    sceneFallbackApplied: boolean
}

export interface RoleEntryState {
    key: 'staff-center' | 'admin-dashboard'
    visible: boolean
    enabled: boolean
    title: string
    subtitle: string
    deniedSubtitle: string
    routePath: string
    scene: WmScene
    source: PageContractSource
    back: PageContractBack
}

const VALID_SCENES: WmScene[] = ['consumer', 'staff', 'admin']
const VALID_SOURCES: PageContractSource[] = [
    'tabbar',
    'user-hub',
    'staff-center',
    'admin-dashboard',
    'direct',
    'deeplink',
    'unknown'
]
const VALID_BACK_ACTIONS: PageContractBack[] = ['none', 'navigateBack', 'switchTab', 'reLaunch']

export const normalizeRoutePath = (routePath?: string) => {
    const normalized = String(routePath || '')
        .trim()
        .replace(/^\/+/, '')
    if (!normalized) {
        return PAGE_CONTRACT_FALLBACK.routePath
    }
    return `/${normalized}`
}

export const getCurrentPageOptions = (): Record<string, any> => {
    const pages = getCurrentPages()
    const currentPage = pages[pages.length - 1] as MaybeRecord
    return (currentPage?.options || {}) as Record<string, any>
}

export const getCurrentPageRoutePath = () => {
    const pages = getCurrentPages()
    const currentPage = pages[pages.length - 1] as MaybeRecord
    return normalizeRoutePath(currentPage?.route as string | undefined)
}

export const getPageContract = (routePath?: string): PageContractDefinition => {
    const normalizedPath = normalizeRoutePath(routePath)
    return (
        PAGE_SCOPE_MATRIX[normalizedPath] || {
            ...PAGE_CONTRACT_FALLBACK,
            routePath: normalizedPath
        }
    )
}

export const isTabBarPage = (routePath?: string) => {
    const normalizedPath = normalizeRoutePath(routePath)
    return TABBAR_PAGE_PATHS.includes(normalizedPath as typeof TABBAR_PAGE_PATHS[number])
}

export const normalizeScene = (scene?: string, fallback: WmScene = 'consumer'): WmScene => {
    const normalized = String(scene || '')
        .trim()
        .toLowerCase()
    if (VALID_SCENES.includes(normalized as WmScene)) {
        return normalized as WmScene
    }
    return fallback
}

export const normalizeSource = (
    source?: string,
    fallback: PageContractSource = 'unknown',
    allowedSources?: PageContractSource[]
): PageContractSource => {
    const normalized = String(source || '')
        .trim()
        .toLowerCase()
    if (!VALID_SOURCES.includes(normalized as PageContractSource)) {
        return fallback
    }
    if (allowedSources?.length && !allowedSources.includes(normalized as PageContractSource)) {
        return fallback
    }
    return normalized as PageContractSource
}

export const normalizeBackAction = (
    back?: string,
    fallback: PageContractBack = 'navigateBack'
): PageContractBack => {
    const normalized = String(back || '').trim()
    if (VALID_BACK_ACTIONS.includes(normalized as PageContractBack)) {
        return normalized as PageContractBack
    }
    return fallback
}

export const resolvePageShellProtocol = (input: PageShellProtocolInput = {}): PageShellProtocol => {
    const routePath = normalizeRoutePath(input.routePath || getCurrentPageRoutePath())
    const contract = getPageContract(routePath)
    const routeOptions = getCurrentPageOptions()
    const tabBar = contract.tabBar || isTabBarPage(routePath)

    const requestedScene = normalizeScene(
        input.declaredScene || routeOptions.scene,
        contract.defaultScene
    )
    const scene = contract.allowedScenes.includes(requestedScene)
        ? requestedScene
        : contract.defaultScene

    const source = tabBar
        ? contract.defaultSource
        : normalizeSource(
              input.declaredSource || routeOptions.source,
              contract.defaultSource,
              contract.allowedSources
          )
    const back = tabBar
        ? contract.defaultBack
        : normalizeBackAction(input.declaredBack || routeOptions.back, contract.defaultBack)

    return {
        routePath,
        contract,
        scene,
        source,
        back,
        isTabBar: !!tabBar,
        sceneFallbackApplied: scene !== requestedScene
    }
}

export const appendPageContractQuery = (params: {
    path: string
    scene?: WmScene
    source?: PageContractSource
    back?: PageContractBack
}) => {
    const { path } = params
    const queryEntries = [
        params.scene ? `scene=${encodeURIComponent(params.scene)}` : '',
        params.source ? `source=${encodeURIComponent(params.source)}` : '',
        params.back ? `back=${encodeURIComponent(params.back)}` : ''
    ].filter(Boolean)
    const serialized = queryEntries.join('&')
    return serialized ? `${path}?${serialized}` : path
}

const parseAdminDashboardUserIds = (rawValue: unknown) => {
    return String(rawValue || '')
        .split(/[\s,，]+/)
        .map((item) => Number(item.trim()))
        .filter((item) => Number.isFinite(item) && item > 0)
}

export const getRoleEntryStates = (params: {
    featureSwitch?: Record<string, any>
    userInfo?: Record<string, any>
    isLogin?: boolean
}) => {
    const featureSwitch = params.featureSwitch || {}
    const userInfo = params.userInfo || {}
    const isLogin = !!params.isLogin
    const currentUserId = Number(userInfo.id || userInfo.user_id || 0)
    const adminDashboardUserIds = parseAdminDashboardUserIds(featureSwitch.admin_dashboard_user_ids)

    return USER_ROLE_ENTRY_DEFINITIONS.map<RoleEntryState>((entry) => {
        const enabled =
            entry.key === 'staff-center'
                ? featureSwitch.staff_center === 1 && !!userInfo.is_staff
                : featureSwitch.admin_dashboard === 1 &&
                  currentUserId > 0 &&
                  adminDashboardUserIds.includes(currentUserId)

        return {
            ...entry,
            visible: isLogin && enabled,
            enabled,
            deniedSubtitle: entry.key === 'staff-center' ? '需服务人员身份' : '需管理员权限'
        }
    })
}

export const ensureSceneAccess = (params: {
    scene: WmScene
    featureSwitch?: Record<string, any>
    userInfo?: Record<string, any>
    isLogin?: boolean
}) => {
    const roleEntryStates = getRoleEntryStates(params)
    const matchedState = roleEntryStates.find((item) => item.scene === params.scene)

    if (!matchedState) {
        return {
            allowed: true,
            reason: ''
        }
    }

    return {
        allowed: matchedState.enabled,
        reason: matchedState.deniedSubtitle
    }
}

export const navigateByBackContract = (
    protocol: PageShellProtocol,
    fallbackUrl = '/pages/user/user'
) => {
    const pageCount = getCurrentPages().length

    if (protocol.back === 'navigateBack' && pageCount > 1) {
        uni.navigateBack()
        return
    }

    if (protocol.back === 'switchTab') {
        uni.switchTab({ url: fallbackUrl })
        return
    }

    if (protocol.back === 'reLaunch' || pageCount <= 1) {
        uni.reLaunch({ url: fallbackUrl })
        return
    }

    uni.navigateBack()
}
