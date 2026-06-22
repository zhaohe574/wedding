import { useAppStore } from '@/stores/app'
import { getCurrentPageOptions, getCurrentPageRoutePath } from '@/utils/page-contract'

type SharePayload = {
    title: string
    path?: string
    query?: string
    imageUrl?: string
}

const DEFAULT_SHARE_TITLE = '服务中心'

const PUBLIC_SHARE_PATHS = new Set([
    '/pages/index/index',
    '/pages/dynamic/dynamic',
    '/pages/news/news',
    '/pages/staff_list/staff_list',
    '/pages/schedule_query/schedule_query',
    '/packages/pages/search/search',
    '/pages/as_us/as_us',
    '/packages/pages/dynamic_detail/dynamic_detail',
    '/packages/pages/news_detail/news_detail',
    '/packages/pages/staff_detail/staff_detail',
    '/packages/pages/staff_work_detail/staff_work_detail',
    '/packages/pages/customer_service/customer_service'
])

const PRIVATE_SHARE_PATHS = new Set([
    '/pages/login/login',
    '/pages/register/register',
    '/pages/forget_pwd/forget_pwd',
    '/pages/bind_mobile/bind_mobile',
    '/pages/user/user',
    '/pages/user_set/user_set',
    '/pages/user_data/user_data',
    '/pages/change_password/change_password',
    '/pages/order/order',
    '/pages/order_detail/order_detail',
    '/packages/pages/order_detail/order_detail',
    '/packages/pages/payment_result/payment_result',
    '/packages/pages/user_wallet/user_wallet',
    '/packages/pages/recharge/recharge',
    '/packages/pages/recharge_record/recharge_record',
    '/packages/pages/admin_dashboard/admin_dashboard'
])

const PRIVATE_SHARE_PREFIXES = [
    '/packages/pages/aftersale/',
    '/packages/pages/couple_questionnaire/',
    '/packages/pages/order_change/',
    '/packages/pages/staff_center/',
    '/packages/pages/staff_order_',
    '/packages/pages/staff_profile',
    '/packages/pages/staff_certificate_',
    '/packages/pages/staff_work_edit',
    '/packages/pages/staff_work_list',
    '/packages/pages/staff_package_',
    '/packages/pages/staff_addon_',
    '/packages/pages/staff_schedule',
    '/packages/pages/staff_settlement',
    '/packages/pages/staff_dynamic_',
    '/packages/pages/dynamic_publish',
    '/packages/pages/review/',
    '/packages/pages/notification/',
    '/packages/pages/wecom_notice',
    '/packages/pages/collection',
    '/packages/pages/waitlist',
    '/packages/pages/order_confirm'
]

const SENSITIVE_QUERY_KEYS = new Set([
    'token',
    'code',
    'password',
    'mobile',
    'phone',
    'session',
    'session_key',
    'openid',
    'unionid',
    'state'
])

const SHARE_TITLE_MAP: Record<string, string> = {
    '/pages/index/index': '首页',
    '/pages/dynamic/dynamic': '动态广场',
    '/pages/news/news': '婚礼资讯',
    '/pages/staff_list/staff_list': '人员列表',
    '/pages/schedule_query/schedule_query': '档期查询',
    '/packages/pages/search/search': '搜索',
    '/pages/as_us/as_us': '关于我们',
    '/packages/pages/dynamic_detail/dynamic_detail': '动态详情',
    '/packages/pages/news_detail/news_detail': '资讯详情',
    '/packages/pages/staff_detail/staff_detail': '人员详情',
    '/packages/pages/staff_work_detail/staff_work_detail': '作品详情',
    '/packages/pages/customer_service/customer_service': '联系顾问'
}

const CUSTOM_SHARE_APP_MESSAGE_PATHS = new Set([
    '/pages/dynamic/dynamic',
    '/pages/news/news',
    '/packages/pages/dynamic_detail/dynamic_detail',
    '/packages/pages/staff_detail/staff_detail'
])

const CUSTOM_SHARE_TIMELINE_PATHS = new Set(['/packages/pages/staff_detail/staff_detail'])

const normalizeRoutePath = (path = '') => {
    const normalized = String(path || '').trim().replace(/^\/+/, '')
    return normalized ? `/${normalized}` : '/pages/index/index'
}

const isShareAllowed = (path: string) => {
    if (PRIVATE_SHARE_PATHS.has(path)) return false
    if (PRIVATE_SHARE_PREFIXES.some((prefix) => path.startsWith(prefix))) return false
    return PUBLIC_SHARE_PATHS.has(path)
}

const hasCustomAppMessageShare = (path: string) => CUSTOM_SHARE_APP_MESSAGE_PATHS.has(path)

const hasCustomTimelineShare = (path: string) => CUSTOM_SHARE_TIMELINE_PATHS.has(path)

const encodeQueryValue = (value: unknown) => encodeURIComponent(String(value ?? ''))

const buildShareQuery = (options: Record<string, any>) => {
    return Object.keys(options || {})
        .filter((key) => !SENSITIVE_QUERY_KEYS.has(key.toLowerCase()))
        .filter((key) => options[key] !== undefined && options[key] !== null && options[key] !== '')
        .map((key) => `${encodeURIComponent(key)}=${encodeQueryValue(options[key])}`)
        .join('&')
}

const getShareTitle = (path: string) => {
    const appStore = useAppStore()
    const pageTitle = SHARE_TITLE_MAP[path]
    const siteTitle = String(appStore.getWebsiteConfig.shop_name || '').trim()
    return pageTitle || siteTitle || DEFAULT_SHARE_TITLE
}

const getSharePayload = (): SharePayload => {
    const path = normalizeRoutePath(getCurrentPageRoutePath())
    const query = buildShareQuery(getCurrentPageOptions())
    return {
        title: getShareTitle(path),
        path: query ? `${path}?${query}` : path
    }
}

const getStaffDetailFallbackSharePayload = (): SharePayload | undefined => {
    const options = getCurrentPageOptions()
    const staffId = options.id || options.staff_id || options.staffId

    if (!staffId) {
        return undefined
    }

    const query = buildShareQuery({
        ...options,
        id: staffId,
        from_share: 1
    })

    return {
        title: getShareTitle('/packages/pages/staff_detail/staff_detail'),
        path: `/packages/pages/staff_detail/staff_detail?${query}`
    }
}

const getTimelinePayload = (): SharePayload => {
    const payload = getSharePayload()
    const [path, query = ''] = String(payload.path || '').split('?')
    return {
        title: payload.title,
        query,
        path
    }
}

const applyShareMenu = () => {
    const path = normalizeRoutePath(getCurrentPageRoutePath())

    if (!isShareAllowed(path)) {
        uni.hideShareMenu({
            hideShareItems: ['shareAppMessage', 'shareTimeline']
        })
        return
    }

    uni.showShareMenu({
        withShareTicket: true,
        menus: ['shareAppMessage', 'shareTimeline']
    })
}

export default {
    // #ifdef MP-WEIXIN
    onLoad() {
        applyShareMenu()
    },
    onShow() {
        applyShareMenu()
    },
    onShareAppMessage() {
        const path = normalizeRoutePath(getCurrentPageRoutePath())
        if (!isShareAllowed(path)) return undefined
        if (path === '/packages/pages/staff_detail/staff_detail') {
            return getStaffDetailFallbackSharePayload()
        }
        if (hasCustomAppMessageShare(path)) return undefined
        return getSharePayload()
    },
    onShareTimeline() {
        const path = normalizeRoutePath(getCurrentPageRoutePath())
        if (!isShareAllowed(path)) return undefined
        if (path === '/packages/pages/staff_detail/staff_detail') {
            const payload = getStaffDetailFallbackSharePayload()
            const [sharePath = '', query = ''] = String(payload?.path || '').split('?')

            return payload
                ? {
                      title: payload.title,
                      query,
                      path: sharePath
                  }
                : undefined
        }
        if (hasCustomTimelineShare(path)) return undefined
        return getTimelinePayload()
    }
    // #endif
}
