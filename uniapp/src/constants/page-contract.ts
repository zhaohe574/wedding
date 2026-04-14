import type { WmScene } from '@/design/theme'

export type PageAudience = 'consumer' | 'staff' | 'admin'
export type PageScopeLevel = 'included' | 'compatible' | 'excluded'
export type PageContractSource =
    | 'tabbar'
    | 'user-hub'
    | 'staff-center'
    | 'admin-dashboard'
    | 'direct'
    | 'deeplink'
    | 'unknown'
export type PageContractBack = 'none' | 'navigateBack' | 'switchTab' | 'reLaunch'

export interface PageAudienceMatrix {
    consumer: PageScopeLevel
    staff: PageScopeLevel
    admin: PageScopeLevel
}

export interface PageContractDefinition {
    key: string
    routePath: string
    defaultScene: WmScene
    allowedScenes: WmScene[]
    audience: PageAudienceMatrix
    defaultSource: PageContractSource
    defaultBack: PageContractBack
    allowedSources?: PageContractSource[]
    tabBar?: boolean
    touchpointGroup?: string
    notes: string
}

export interface UserRoleEntryDefinition {
    key: 'staff-center' | 'admin-dashboard'
    title: string
    subtitle: string
    routePath: string
    scene: WmScene
    source: PageContractSource
    back: PageContractBack
}

export const TABBAR_PAGE_PATHS = [
    '/pages/index/index',
    '/pages/dynamic/dynamic',
    '/pages/user/user'
] as const

export const CONSUMER_COMPAT_TOUCHPOINT_PATHS = [
    '/packages/pages/order_change/list',
    '/packages/pages/order_change/change_detail',
    '/packages/pages/order_change/apply_date',
    '/packages/pages/order_change/apply_pause',
    '/packages/pages/order_change/pause_detail',
    '/packages/pages/order_change/apply_add_item',
    '/packages/pages/aftersale/index',
    '/packages/pages/aftersale/ticket',
    '/packages/pages/aftersale/create_ticket',
    '/packages/pages/aftersale/ticket_detail',
    '/packages/pages/aftersale/complaint',
    '/packages/pages/aftersale/complaint_detail',
    '/packages/pages/aftersale/create_complaint',
    '/packages/pages/aftersale/callback',
    '/packages/pages/aftersale/callback_detail',
    '/packages/pages/review/list',
    '/packages/pages/review/publish',
    '/packages/pages/review/detail',
    '/packages/pages/notification/index',
    '/packages/pages/collection/collection',
    '/packages/pages/user_wallet/user_wallet',
    '/packages/pages/recharge/recharge',
    '/packages/pages/recharge_record/recharge_record'
] as const

const compatibleAudience: PageAudienceMatrix = {
    consumer: 'included',
    staff: 'compatible',
    admin: 'compatible'
}

export const PAGE_SCOPE_MATRIX: Record<string, PageContractDefinition> = {
    '/pages/user/user': {
        key: 'user-center-tab',
        routePath: '/pages/user/user',
        defaultScene: 'consumer',
        allowedScenes: ['consumer'],
        audience: compatibleAudience,
        defaultSource: 'tabbar',
        defaultBack: 'switchTab',
        allowedSources: ['tabbar', 'direct', 'deeplink'],
        tabBar: true,
        notes: '消费者聚合入口。固定骨架区与可变装修区都从这里开始收口。'
    },
    '/packages/pages/staff_center/staff_center': {
        key: 'staff-center-workspace',
        routePath: '/packages/pages/staff_center/staff_center',
        defaultScene: 'staff',
        allowedScenes: ['staff'],
        audience: {
            consumer: 'excluded',
            staff: 'included',
            admin: 'excluded'
        },
        defaultSource: 'user-hub',
        defaultBack: 'switchTab',
        allowedSources: ['user-hub', 'staff-center', 'direct', 'deeplink'],
        notes: '服务人员工作台，只允许 staff scene 生效。'
    },
    '/packages/pages/admin_dashboard/admin_dashboard': {
        key: 'admin-dashboard-workspace',
        routePath: '/packages/pages/admin_dashboard/admin_dashboard',
        defaultScene: 'admin',
        allowedScenes: ['admin'],
        audience: {
            consumer: 'excluded',
            staff: 'excluded',
            admin: 'included'
        },
        defaultSource: 'user-hub',
        defaultBack: 'switchTab',
        allowedSources: ['user-hub', 'admin-dashboard', 'direct', 'deeplink'],
        notes: '移动管理员驾驶舱，只允许 admin scene 生效。'
    }
}

for (const routePath of CONSUMER_COMPAT_TOUCHPOINT_PATHS) {
    PAGE_SCOPE_MATRIX[routePath] = {
        key: `consumer-touchpoint:${routePath}`,
        routePath,
        defaultScene: 'consumer',
        allowedScenes: ['consumer'],
        audience: compatibleAudience,
        defaultSource: 'user-hub',
        defaultBack: 'navigateBack',
        allowedSources: ['user-hub', 'staff-center', 'admin-dashboard', 'direct', 'deeplink'],
        touchpointGroup: 'consumer-compatible-touchpoint',
        notes: '兼容触点保留 consumer scene，不能把它们提升成独立 staff/admin 页面。'
    }
}

export const USER_ROLE_ENTRY_DEFINITIONS: UserRoleEntryDefinition[] = [
    {
        key: 'staff-center',
        title: '服务人员入口',
        subtitle: '服务人员管理页面',
        routePath: '/packages/pages/staff_center/staff_center',
        scene: 'staff',
        source: 'user-hub',
        back: 'switchTab'
    },
    {
        key: 'admin-dashboard',
        title: '驾驶舱',
        subtitle: '管理员驾驶舱入口',
        routePath: '/packages/pages/admin_dashboard/admin_dashboard',
        scene: 'admin',
        source: 'user-hub',
        back: 'switchTab'
    }
]

export const PAGE_CONTRACT_FALLBACK: PageContractDefinition = {
    key: 'fallback-consumer-page',
    routePath: '/pages/user/user',
    defaultScene: 'consumer',
    allowedScenes: ['consumer'],
    audience: compatibleAudience,
    defaultSource: 'direct',
    defaultBack: 'reLaunch',
    allowedSources: ['direct', 'deeplink', 'unknown'],
    notes: '未知页面统一回退到 consumer，避免残留越权 scene。'
}
