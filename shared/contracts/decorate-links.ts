// 移动端装修「点击跳转与参数」唯一事实源。
//
// 三端共同消费：
//   - admin：链接选择器（分组页签、纯结构化参数表单、回显摘要）
//   - server：DecorateLinkService.php 镜像（读时归一老路径 + 保存清洗非白名单参数）
//   - newuniapp：统一跳转引擎（Tab query 中转 / 普通页 / 外链 / 小程序 / 快捷动作）
//
// 注意：本文件位于 shared/，禁止引入 Vue / UniApp / ElementPlus 等端上依赖。

export type DecorateLinkType = 'shop' | 'staff' | 'dynamic' | 'custom' | 'mini_program' | 'action'

export type DecorateActionName = 'make_phone_call' | 'wechat_contact' | 'copy_text'

export interface DecorateLink {
    path: string
    type?: DecorateLinkType | string
    name?: string
    id?: number | string
    canTab?: boolean
    query?: Record<string, string>
}

export type DecorateParamKind =
    | 'service_category'
    | 'style_tag'
    | 'region'
    | 'date'
    | 'enum'
    | 'text'

export interface DecorateParamOption {
    label: string
    value: string
}

export interface DecorateParamDef {
    key: string
    label: string
    kind: DecorateParamKind
    placeholder?: string
    hint?: string
    options?: DecorateParamOption[]
    defaultValue?: string
    maxLength?: number
    maxCount?: number
    withNameKey?: string
}

export interface DecorateLinkPageDef {
    path: string
    name: string
    desc?: string
    canTab?: boolean
    requiresAuth?: boolean
    internalOnly?: boolean
    params?: DecorateParamDef[]
}

export interface DecorateLinkGroupDef {
    key: string
    label: string
    description: string
    internalOnly?: boolean
    items: DecorateLinkPageDef[]
}

export interface DecorateActionDef {
    action: DecorateActionName
    path: string
    label: string
    desc: string
}

export const DECORATE_ACTION_DEFS: DecorateActionDef[] = [
    {
        action: 'make_phone_call',
        path: 'action://make_phone_call',
        label: '拨打客服电话',
        desc: '拉起系统拨号盘；号码留空时自动使用平台客服热线'
    },
    {
        action: 'wechat_contact',
        path: 'action://wechat_contact',
        label: '微信原生客服会话',
        desc: '小程序端直连微信原生客服窗口（H5 端降级跳转咨询页）'
    },
    {
        action: 'copy_text',
        path: 'action://copy_text',
        label: '一键复制文本',
        desc: '复制微信号、口令或地址到剪贴板并弹出轻提示'
    }
]

const VALID_ACTIONS = new Set<string>(DECORATE_ACTION_DEFS.map((a) => a.action))

/**
 * 历史路径 → newuniapp 真实路径。
 * 服务端 DecorateLinkService.php 必须与本映射保持一致。
 */
export const DECORATE_LEGACY_PATH_MAP: Record<string, string> = {
    '/pages/schedule_query/schedule_query': '/pages/query/query',
    '/pages/staff_list/staff_list': '/packages/pkg-catalog/pages/staff-list/staff-list',
    '/pages/staff_detail/staff_detail': '/packages/pkg-catalog/pages/staff-detail/staff-detail',
    '/pages/staff_booking/staff_booking': '/packages/pkg-catalog/pages/booking/booking',
    '/pages/news/news': '/packages/pkg-catalog/pages/case-list/case-list',
    '/pages/news_detail/news_detail': '/packages/pkg-catalog/pages/article-detail/article-detail',
    '/pages/dynamic/dynamic': '/pages/feed/feed',
    '/pages/dynamic_detail/dynamic_detail': '/packages/pkg-catalog/pages/feed-detail/feed-detail',
    '/pages/payment/payment': '/packages/pkg-order/pages/payment-result/payment-result',
    '/packages/pages/customer_service/customer_service': '/pages/customer-service/customer-service',
    '/packages/pages/customer_service/index': '/pages/customer-service/customer-service',
    '/packages/pages/staff_detail/staff_detail':
        '/packages/pkg-catalog/pages/staff-detail/staff-detail',
    '/packages/pages/staff_booking/staff_booking': '/packages/pkg-catalog/pages/booking/booking',
    '/packages/pages/staff_list/staff_list': '/packages/pkg-catalog/pages/staff-list/staff-list',
    '/packages/pages/dynamic_list/dynamic_list': '/pages/feed/feed',
    '/packages/pages/news/news': '/packages/pkg-catalog/pages/case-list/case-list',
    '/packages/pages/news_detail/news_detail':
        '/packages/pkg-catalog/pages/article-detail/article-detail',
    '/packages/pages/order_detail/order_detail': '/packages/pkg-order/pages/detail/detail',
    '/pages/order_detail/order_detail': '/packages/pkg-order/pages/detail/detail',
    '/packages/pages/order_confirm/order_confirm': '/packages/pkg-catalog/pages/booking/booking',
    '/packages/pages/aftersale/aftersale': '/packages/pkg-service/pages/aftersale/index',
    '/pages/user_set/user_set': '/packages/pkg-user/pages/settings/settings',
    '/pages/user_data/user_data': '/packages/pkg-user/pages/profile/profile',
    '/pages/change_password/change_password': '/packages/pkg-user/pages/settings/settings',
    '/pages/bind_mobile/bind_mobile': '/packages/pkg-user/pages/profile/profile',
    '/packages/pages/collection/collection': '/packages/pkg-user/pages/favorite/favorite',
    '/pages/collection/collection': '/packages/pkg-user/pages/favorite/favorite',
    '/packages/pages/staff_favorite/staff_favorite': '/packages/pkg-user/pages/favorite/favorite',
    '/packages/pages/my_activity/my_activity': '/packages/pkg-user/pages/activity/activity',
    '/pages/my_activity/my_activity': '/packages/pkg-user/pages/activity/activity',
    '/packages/pages/activity_registration/list': '/packages/pkg-user/pages/activity/activity',
    '/pages/activity_registration/list': '/packages/pkg-user/pages/activity/activity',
    '/packages/pages/activity_registration/detail': '/packages/pkg-user/pages/activity/activity',
    '/packages/pages/notification/index': '/pages/message/message',
    '/pages/notification/index': '/pages/message/message',
    '/pages/order/list': '/pages/order/order',
    '/packages/pages/waitlist/waitlist': '/packages/pkg-order/pages/waitlist/waitlist',
    '/packages/pages/order_change/list': '/packages/pkg-service/pages/change/list',
    '/packages/pages/order_change/apply_date': '/packages/pkg-service/pages/change/apply-date',
    '/packages/pages/order_change/apply_pause': '/packages/pkg-service/pages/change/apply-pause',
    '/packages/pages/order_change/apply_add_item': '/packages/pkg-service/pages/change/apply-addon',
    '/packages/pages/order_change/pause_detail': '/packages/pkg-service/pages/change/detail',
    '/packages/pages/order_change/change_detail': '/packages/pkg-service/pages/change/detail',
    '/packages/pages/aftersale/index': '/packages/pkg-service/pages/aftersale/index',
    '/pages/aftersale/index': '/packages/pkg-service/pages/aftersale/index',
    '/packages/pages/aftersale/ticket': '/packages/pkg-service/pages/aftersale/ticket-list',
    '/packages/pages/aftersale/create_ticket':
        '/packages/pkg-service/pages/aftersale/ticket-create',
    '/packages/pages/aftersale/complaint':
        '/packages/pkg-service/pages/aftersale/complaint-list',
    '/packages/pages/aftersale/create_complaint':
        '/packages/pkg-service/pages/aftersale/complaint-create',
    '/packages/pages/aftersale/callback': '/packages/pkg-service/pages/aftersale/callback-list',
    '/packages/pages/aftersale/ticket_detail':
        '/packages/pkg-service/pages/aftersale/ticket-detail',
    '/packages/pages/review/list': '/packages/pkg-order/pages/review/list',
    '/pages/review/list': '/packages/pkg-order/pages/review/list',
    '/packages/pages/review/detail': '/packages/pkg-order/pages/review/list',
    '/pages/as_us/as_us': '/pages/about/about',
    '/packages/pages/agreement/agreement': '/pages/agreement/agreement',
    '/packages/pages/case_list/case_list': '/packages/pkg-catalog/pages/case-list/case-list',
    '/packages/pages/search/search': '/pages/search/search',
    '/packages/pages/payment_result/payment_result':
        '/packages/pkg-order/pages/payment-result/payment-result',
    '/packages/pages/dynamic_detail/dynamic_detail':
        '/packages/pkg-catalog/pages/feed-detail/feed-detail',
    '/packages/pages/staff_center/staff_center': '/packages/pkg-staff/pages/center/center',
    '/packages/pages/staff_profile/staff_profile': '/packages/pkg-staff/pages/profile/profile',
    '/packages/pages/staff_order_list/staff_order_list':
        '/packages/pkg-staff/pages/order-list/order-list',
    '/packages/pages/staff_order_detail/staff_order_detail':
        '/packages/pkg-staff/pages/order-detail/order-detail',
    '/packages/pages/staff_schedule/staff_schedule': '/packages/pkg-staff/pages/schedule/schedule',
    '/packages/pages/staff_certificate_list/staff_certificate_list':
        '/packages/pkg-staff/pages/certificate-list/certificate-list',
    '/packages/pages/staff_work_list/staff_work_list':
        '/packages/pkg-staff/pages/work-list/work-list',
    '/packages/pages/staff_work_detail/staff_work_detail':
        '/packages/pkg-staff/pages/work-list/work-list',
    '/packages/pages/staff_package_list/staff_package_list':
        '/packages/pkg-staff/pages/package-list/package-list',
    '/packages/pages/staff_addon_list/staff_addon_list':
        '/packages/pkg-staff/pages/addon-list/addon-list',
    '/packages/pages/staff_dynamic_list/staff_dynamic_list':
        '/packages/pkg-staff/pages/dynamic-list/dynamic-list',
    '/packages/pages/admin_dashboard/admin_dashboard':
        '/packages/pkg-staff/pages/dashboard/dashboard',
    '/packages/pages/couple_questionnaire/detail':
        '/packages/pkg-service/pages/questionnaire/detail',
    '/packages/pages/staff_settlement/staff_settlement':
        '/packages/pkg-staff/pages/settlement-list/settlement-list',
    '/packages/pages/staff_schedule_confirm_letter/staff_schedule_confirm_letter':
        '/packages/pkg-staff/pages/card-poster/card-poster?tab=poster'
}

/**
 * 移动端可选页面分组（后台链接选择器唯一事实源）。
 * 所有路径均已对齐 newuniapp/src/pages.json。
 */
export const DECORATE_LINK_GROUPS: DecorateLinkGroupDef[] = [
    {
        key: 'main',
        label: '底部 Tab 主页',
        description: '小程序四段主导航页；切换 Tab 时自动通过 tabQuery 传递筛选条件',
        items: [
            { path: '/pages/index/index', name: '首页', canTab: true },
            {
                path: '/pages/feed/feed',
                name: '动态广场',
                canTab: true,
                params: [
                    {
                        key: 'dynamic_type',
                        label: '动态分类',
                        kind: 'enum',
                        defaultValue: '',
                        options: [
                            { label: '全部', value: '' },
                            { label: '图文', value: '1' },
                            { label: '视频', value: '2' },
                            { label: '活动', value: '4' }
                        ]
                    },
                    {
                        key: 'sort',
                        label: '默认排序',
                        kind: 'enum',
                        defaultValue: 'latest',
                        options: [
                            { label: '最新', value: 'latest' },
                            { label: '最热', value: 'hot' }
                        ]
                    },
                    {
                        key: 'tag',
                        label: '话题标签',
                        kind: 'text',
                        maxLength: 20,
                        placeholder: '可选，如 户外草坪'
                    },
                    {
                        key: 'keyword',
                        label: '关键词',
                        kind: 'text',
                        maxLength: 30,
                        placeholder: '可选，按标题或内容检索'
                    }
                ]
            },
            {
                path: '/pages/query/query',
                name: '查档期',
                canTab: true,
                params: [
                    {
                        key: 'category_id',
                        label: '服务类型',
                        kind: 'service_category',
                        placeholder: '可选，预选服务类型'
                    },
                    {
                        key: 'tag_ids',
                        label: '风格标签',
                        kind: 'style_tag',
                        maxCount: 5,
                        placeholder: '可选，最多选 5 个标签'
                    },
                    {
                        key: 'region',
                        label: '服务地区',
                        kind: 'region',
                        placeholder: '可选，省市区同步带入'
                    },
                    {
                        key: 'date',
                        label: '预设婚期',
                        kind: 'date',
                        placeholder: '可选，YYYY-MM-DD'
                    },
                    {
                        key: 'keyword',
                        label: '关键词',
                        kind: 'text',
                        maxLength: 30,
                        placeholder: '可选，人员姓氏或关键词'
                    }
                ]
            },
            { path: '/pages/user/user', name: '我的', canTab: true }
        ]
    },
    {
        key: 'catalog',
        label: '发现与咨询',
        description: '人员筛选、真实案例、站内搜索与客服咨询',
        items: [
            {
                path: '/packages/pkg-catalog/pages/staff-list/staff-list',
                name: '服务人员列表',
                params: [
                    {
                        key: 'category_id',
                        label: '服务类型',
                        kind: 'service_category',
                        withNameKey: 'category_name',
                        placeholder: '可选，如 司仪主持'
                    },
                    {
                        key: 'tag_ids',
                        label: '风格标签',
                        kind: 'style_tag',
                        maxCount: 5,
                        placeholder: '可选，最多选 5 个标签'
                    },
                    {
                        key: 'region',
                        label: '服务地区',
                        kind: 'region',
                        placeholder: '可选，城市 + 区县'
                    },
                    {
                        key: 'date',
                        label: '服务日期',
                        kind: 'date',
                        placeholder: '可选，过滤该日可约人员'
                    },
                    {
                        key: 'keyword',
                        label: '搜索关键词',
                        kind: 'text',
                        maxLength: 30,
                        placeholder: '可选，按姓名或简介筛选'
                    }
                ]
            },
            {
                path: '/packages/pkg-catalog/pages/case-list/case-list',
                name: '真实案例',
                params: [
                    {
                        key: 'tag',
                        label: '案例标签',
                        kind: 'text',
                        maxLength: 20,
                        placeholder: '可选，如 中式婚礼'
                    }
                ]
            },
            {
                path: '/pages/search/search',
                name: '站内搜索',
                params: [
                    {
                        key: 'tab',
                        label: '默认页签',
                        kind: 'enum',
                        defaultValue: 'staff',
                        options: [
                            { label: '人员', value: 'staff' },
                            { label: '资讯', value: 'article' }
                        ]
                    },
                    {
                        key: 'keyword',
                        label: '预填关键词',
                        kind: 'text',
                        maxLength: 30,
                        placeholder: '可选，进页自动触发搜索'
                    }
                ]
            },
            { path: '/pages/customer-service/customer-service', name: '客服管家' },
            {
                path: '/packages/pkg-service/pages/concierge/concierge',
                name: '我的咨询',
                requiresAuth: true,
                params: [
                    {
                        key: 'scene',
                        label: '咨询场景',
                        kind: 'enum',
                        defaultValue: 'support',
                        options: [
                            { label: '售前咨询', value: 'support' },
                            { label: '订单咨询', value: 'order' },
                            { label: '售后支持', value: 'aftersale' }
                        ]
                    }
                ]
            }
        ]
    },
    {
        key: 'order_service',
        label: '订单与售后',
        description: '订单分组列表、候补、评价与售后服务（需登录后访问）',
        items: [
            {
                path: '/pages/order/order',
                name: '我的订单',
                requiresAuth: true,
                params: [
                    {
                        key: 'tab',
                        label: '默认分组',
                        kind: 'enum',
                        defaultValue: 'all',
                        options: [
                            { label: '全部', value: 'all' },
                            { label: '待确认', value: 'confirm' },
                            { label: '待支付', value: 'unpaid' },
                            { label: '待服务', value: 'service' },
                            { label: '售后', value: 'refund' }
                        ]
                    }
                ]
            },
            { path: '/packages/pkg-order/pages/waitlist/waitlist', name: '我的候补', requiresAuth: true },
            { path: '/packages/pkg-order/pages/review/list', name: '我的评价', requiresAuth: true },
            { path: '/packages/pkg-service/pages/change/list', name: '变更记录', requiresAuth: true },
            { path: '/packages/pkg-service/pages/aftersale/index', name: '售后服务', requiresAuth: true },
            {
                path: '/packages/pkg-service/pages/aftersale/ticket-list',
                name: '服务工单',
                requiresAuth: true
            },
            {
                path: '/packages/pkg-service/pages/aftersale/ticket-create',
                name: '提交工单',
                requiresAuth: true
            },
            {
                path: '/packages/pkg-service/pages/aftersale/complaint-list',
                name: '投诉记录',
                requiresAuth: true
            },
            {
                path: '/packages/pkg-service/pages/aftersale/complaint-create',
                name: '发起投诉',
                requiresAuth: true
            },
            {
                path: '/packages/pkg-service/pages/aftersale/callback-list',
                name: '服务回访',
                requiresAuth: true
            }
        ]
    },
    {
        key: 'user_assets',
        label: '用户资产与内容',
        description: '收藏、活动报名、消息通知、个人资料与协议说明',
        items: [
            { path: '/packages/pkg-user/pages/favorite/favorite', name: '我的收藏', requiresAuth: true },
            { path: '/packages/pkg-user/pages/activity/activity', name: '我的活动', requiresAuth: true },
            { path: '/packages/pkg-user/pages/wallet/wallet', name: '我的钱包', requiresAuth: true },
            { path: '/pages/message/message', name: '消息中心', requiresAuth: true },
            { path: '/packages/pkg-user/pages/profile/profile', name: '个人资料', requiresAuth: true },
            { path: '/packages/pkg-user/pages/settings/settings', name: '设置', requiresAuth: true },
            { path: '/pages/about/about', name: '关于我们' },
            {
                path: '/pages/agreement/agreement',
                name: '协议与条款',
                params: [
                    {
                        key: 'type',
                        label: '协议类型',
                        kind: 'enum',
                        defaultValue: 'privacy',
                        options: [
                            { label: '隐私政策', value: 'privacy' },
                            { label: '服务协议', value: 'service' },
                            { label: '预约服务协议', value: 'booking' },
                            { label: '售后服务规则', value: 'aftersale' }
                        ]
                    }
                ]
            }
        ]
    },
    {
        key: 'staff_internal',
        label: '工作人员专属入口',
        description: '仅服务人员与管理员可访问，普通用户点击将被权限拦截',
        internalOnly: true,
        items: [
            {
                path: '/packages/pkg-staff/pages/center/center',
                name: '服务人员工作台',
                requiresAuth: true,
                internalOnly: true
            },
            {
                path: '/packages/pkg-staff/pages/order-list/order-list',
                name: '服务订单管理',
                requiresAuth: true,
                internalOnly: true
            },
            {
                path: '/packages/pkg-staff/pages/schedule/schedule',
                name: '档期管理',
                requiresAuth: true,
                internalOnly: true
            },
            {
                path: '/packages/pkg-staff/pages/profile/profile',
                name: '人员资料',
                requiresAuth: true,
                internalOnly: true
            },
            {
                path: '/packages/pkg-staff/pages/certificate-list/certificate-list',
                name: '证书管理',
                requiresAuth: true,
                internalOnly: true
            },
            {
                path: '/packages/pkg-staff/pages/work-list/work-list',
                name: '作品管理',
                requiresAuth: true,
                internalOnly: true
            },
            {
                path: '/packages/pkg-staff/pages/package-list/package-list',
                name: '套餐管理',
                requiresAuth: true,
                internalOnly: true
            },
            {
                path: '/packages/pkg-staff/pages/addon-list/addon-list',
                name: '附加项管理',
                requiresAuth: true,
                internalOnly: true
            },
            {
                path: '/packages/pkg-staff/pages/dynamic-list/dynamic-list',
                name: '动态管理',
                requiresAuth: true,
                internalOnly: true
            },
            {
                path: '/packages/pkg-staff/pages/settlement-list/settlement-list',
                name: '我的结算',
                requiresAuth: true,
                internalOnly: true
            },
            {
                path: '/packages/pkg-staff/pages/dashboard/dashboard',
                name: '经营驾驶舱',
                requiresAuth: true,
                internalOnly: true
            }
        ]
    }
]

export const DECORATE_LINK_PAGES: DecorateLinkPageDef[] = DECORATE_LINK_GROUPS.flatMap(
    (g) => g.items
)

const PAGE_MAP = new Map<string, DecorateLinkPageDef>(
    DECORATE_LINK_PAGES.map((page) => [page.path, page])
)

/** 非选择器直接暴露、但历史兼容路径可指向的详情/操作页允许参数 */
const EXTRA_ROUTE_ALLOWED_KEYS: Record<string, string[]> = {
    '/packages/pkg-catalog/pages/article-detail/article-detail': ['id'],
    '/packages/pkg-catalog/pages/booking/booking': ['staff_id', 'date'],
    '/packages/pkg-order/pages/detail/detail': ['id'],
    '/packages/pkg-order/pages/payment-result/payment-result': ['id', 'order_id'],
    '/packages/pkg-service/pages/aftersale/ticket-detail': ['id'],
    '/packages/pkg-service/pages/change/apply-date': ['order_id'],
    '/packages/pkg-service/pages/change/apply-pause': ['order_id'],
    '/packages/pkg-service/pages/change/apply-addon': ['order_id'],
    '/packages/pkg-service/pages/change/detail': ['id'],
    '/packages/pkg-service/pages/questionnaire/detail': ['id'],
    '/packages/pkg-staff/pages/order-detail/order-detail': ['id'],
    '/packages/pkg-staff/pages/card-poster/card-poster': ['tab']
}

export function findDecorateLinkPage(path?: string): DecorateLinkPageDef | null {
    const clean = String(path || '').split('?')[0].trim()
    return PAGE_MAP.get(clean) || null
}

function cleanScalar(value: unknown, maxLen = 200): string {
    if (value === null || value === undefined) return ''
    if (typeof value === 'object') return ''
    return String(value).trim().slice(0, maxLen)
}

function cleanPositiveIntString(value: unknown): string {
    const raw = cleanScalar(value, 20)
    return /^[1-9]\d*$/.test(raw) ? raw : ''
}

function cleanDateString(value: unknown): string {
    const raw = cleanScalar(value, 20)
    return /^\d{4}-\d{2}-\d{2}$/.test(raw) ? raw : ''
}

function cleanTagIds(value: unknown, maxCount = 5): string {
    const raw = cleanScalar(value, 120)
    if (!raw) return ''
    const seen = new Set<string>()
    const out: string[] = []
    for (const seg of raw.split(',')) {
        const id = cleanPositiveIntString(seg)
        if (!id || seen.has(id)) continue
        seen.add(id)
        out.push(id)
        if (out.length >= maxCount) break
    }
    return out.join(',')
}

function parseInlineQuery(rawPath: string): { path: string; inlineQuery: Record<string, string> } {
    const idx = rawPath.indexOf('?')
    if (idx < 0) return { path: rawPath, inlineQuery: {} }
    const path = rawPath.slice(0, idx).trim()
    const search = rawPath.slice(idx + 1)
    const inlineQuery: Record<string, string> = {}
    for (const pair of search.split('&')) {
        if (!pair) continue
        const eq = pair.indexOf('=')
        const k = eq >= 0 ? pair.slice(0, eq) : pair
        const v = eq >= 0 ? pair.slice(eq + 1) : ''
        try {
            const key = decodeURIComponent(k).trim()
            const val = decodeURIComponent(v).trim()
            if (key && val) inlineQuery[key] = val
        } catch {
            const key = k.trim()
            const val = v.trim()
            if (key && val) inlineQuery[key] = val
        }
    }
    return { path, inlineQuery }
}

function mergeRawQuery(
    inlineQuery: Record<string, string>,
    rawQuery: unknown
): Record<string, string> {
    const out: Record<string, string> = { ...inlineQuery }
    if (rawQuery && typeof rawQuery === 'object' && !Array.isArray(rawQuery)) {
        for (const [k, v] of Object.entries(rawQuery as Record<string, unknown>)) {
            const key = String(k || '').trim()
            const val = cleanScalar(v, 500)
            if (!key) continue
            if (val === '') {
                delete out[key]
            } else {
                out[key] = val
            }
        }
    }
    return out
}

function sanitizeShopQuery(
    path: string,
    rawQuery: Record<string, string>
): Record<string, string> {
    const pageDef = PAGE_MAP.get(path)
    if (!pageDef) {
        const extraKeys = EXTRA_ROUTE_ALLOWED_KEYS[path]
        if (!extraKeys?.length) return {}
        const result: Record<string, string> = {}
        for (const key of extraKeys) {
            const val = rawQuery[key]
            if (!val) continue
            if (key === 'id' || key === 'order_id' || key === 'staff_id') {
                const n = cleanPositiveIntString(val)
                if (n) result[key] = n
            } else if (key === 'date') {
                const d = cleanDateString(val)
                if (d) result[key] = d
            } else {
                result[key] = cleanScalar(val, 40)
            }
        }
        return result
    }

    const params = pageDef.params || []
    if (!params.length) return {}

    const result: Record<string, string> = {}
    for (const def of params) {
        if (def.kind === 'region') {
            const cityCode = cleanScalar(rawQuery.city_code, 20)
            const districtCode = cleanScalar(rawQuery.district_code, 20)
            if (cityCode && districtCode) {
                const pCode = cleanScalar(rawQuery.province_code, 20)
                const pName = cleanScalar(rawQuery.province_name, 30)
                const cName = cleanScalar(rawQuery.city_name, 30)
                const dName = cleanScalar(rawQuery.district_name, 30)
                if (pCode) result.province_code = pCode
                if (pName) result.province_name = pName
                result.city_code = cityCode
                if (cName) result.city_name = cName
                result.district_code = districtCode
                if (dName) result.district_name = dName
            }
            continue
        }

        if (def.kind === 'service_category') {
            const cid = cleanPositiveIntString(rawQuery[def.key])
            if (cid) {
                result[def.key] = cid
                if (def.withNameKey) {
                    const cname = cleanScalar(rawQuery[def.withNameKey], 24)
                    if (cname) result[def.withNameKey] = cname
                }
            }
            continue
        }

        if (def.kind === 'style_tag') {
            const tags = cleanTagIds(rawQuery[def.key], def.maxCount || 5)
            if (tags) result[def.key] = tags
            continue
        }

        if (def.kind === 'date') {
            const d = cleanDateString(rawQuery[def.key])
            if (d) result[def.key] = d
            continue
        }

        if (def.kind === 'enum') {
            const val = cleanScalar(rawQuery[def.key], 30)
            const allowed = new Set((def.options || []).map((o) => o.value).filter(Boolean))
            if (val && allowed.has(val)) {
                result[def.key] = val
            }
            continue
        }

        if (def.kind === 'text') {
            const txt = cleanScalar(rawQuery[def.key], def.maxLength || 30)
            if (txt) result[def.key] = txt
        }
    }

    return result
}

function mapLegacyPath(rawPath: string): { path: string; mappedQuery: Record<string, string> } {
    const first = parseInlineQuery(rawPath)
    const mapped = DECORATE_LEGACY_PATH_MAP[first.path]
    if (!mapped) {
        return { path: first.path, mappedQuery: first.inlineQuery }
    }
    const second = parseInlineQuery(mapped)
    return {
        path: second.path,
        mappedQuery: { ...second.inlineQuery, ...first.inlineQuery }
    }
}

/**
 * 链接对象归一化：唯一清洗入口（支持 6 类链接与历史路径升级）。
 */
export function normalizeDecorateLink(raw: unknown): DecorateLink {
    if (!raw || typeof raw !== 'object') return { path: '' }
    const source = raw as Record<string, any>
    const rawPath = cleanScalar(source.path, 500)
    const rawType = cleanScalar(source.type, 40)
    const rawName = cleanScalar(source.name, 60)

    // 1. 快捷动作（action://xxx 或 type='action'）
    if (rawType === 'action' || rawPath.startsWith('action://')) {
        const inlineAction = rawPath.startsWith('action://') ? rawPath.slice('action://'.length) : ''
        const q = mergeRawQuery({}, source.query)
        const action = (q.action || inlineAction) as DecorateActionName
        if (!VALID_ACTIONS.has(action)) return { path: '' }
        const def = DECORATE_ACTION_DEFS.find((a) => a.action === action)!
        const query: Record<string, string> = { action }
        if (action === 'make_phone_call') {
            const phone = cleanScalar(q.phone, 24)
            if (phone) query.phone = phone
        } else if (action === 'copy_text') {
            const text = cleanScalar(q.text, 200)
            const toastText = cleanScalar(q.toast, 30)
            if (text) query.text = text
            if (toastText) query.toast = toastText
        }
        return {
            path: def.path,
            type: 'action',
            name: rawName || def.label,
            query
        }
    }

    // 2. 跳转其他小程序
    if (rawType === 'mini_program' || rawPath === 'mini_program') {
        const q = mergeRawQuery({}, source.query)
        const appId = cleanScalar(q.appId, 40)
        const mpPath = cleanScalar(q.path, 200)
        const mpQuery = cleanScalar(q.query, 200)
        const envVersion =
            q.env_version === 'trial' || q.env_version === 'develop' ? q.env_version : 'release'
        if (!appId && rawPath !== 'mini_program') return { path: '' }
        const query: Record<string, string> = { env_version: envVersion }
        if (appId) query.appId = appId
        if (mpPath) query.path = mpPath
        if (mpQuery) query.query = mpQuery
        return {
            path: 'mini_program',
            type: 'mini_program',
            name: rawName || '跳转小程序',
            query
        }
    }

    // 3. 外部 H5（http(s) 直接写在 path 里，或 webview 链接）
    if (/^https?:\/\//i.test(rawPath)) {
        return {
            path: '/pages/webview/webview',
            type: 'custom',
            name: rawName || '外部网页',
            query: { url: rawPath }
        }
    }

    if (!rawPath) return { path: '' }

    // 先做历史路径映射（含内联 query 拆解）
    const { path, mappedQuery } = mapLegacyPath(rawPath)
    const mergedQuery = mergeRawQuery(mappedQuery, source.query)

    if (path === '/pages/webview/webview' || rawType === 'custom') {
        const url = cleanScalar(mergedQuery.url, 500)
        if (!/^https?:\/\//i.test(url)) return { path: '' }
        return {
            path: '/pages/webview/webview',
            type: 'custom',
            name: rawName || '外部网页',
            query: { url }
        }
    }

    // 4. 服务人员详情（含历史 staff_detail 映射过来）
    if (path === '/packages/pkg-catalog/pages/staff-detail/staff-detail' || rawType === 'staff') {
        const id = cleanPositiveIntString(mergedQuery.id || source.id)
        if (!id) return { path: '' }
        const date = cleanDateString(mergedQuery.date)
        const query: Record<string, string> = { id }
        if (date) query.date = date
        return {
            path: '/packages/pkg-catalog/pages/staff-detail/staff-detail',
            type: 'staff',
            id,
            name: rawName || '服务人员详情',
            query
        }
    }

    // 5. 动态详情（含历史 dynamic_detail 映射过来）
    if (
        path === '/packages/pkg-catalog/pages/feed-detail/feed-detail' ||
        rawType === 'dynamic'
    ) {
        const id = cleanPositiveIntString(mergedQuery.id || source.id)
        if (!id) return { path: '' }
        return {
            path: '/packages/pkg-catalog/pages/feed-detail/feed-detail',
            type: 'dynamic',
            id,
            name: rawName || '动态详情',
            query: { id }
        }
    }

    // 6. 移动端注册页（shop）与兼容映射页
    const pageDef = PAGE_MAP.get(path)
    const sanitizedQuery = sanitizeShopQuery(path, mergedQuery)
    const link: DecorateLink = {
        path,
        type: 'shop'
    }
    const resolvedName = pageDef?.name || rawName
    if (resolvedName) link.name = resolvedName
    if (pageDef?.canTab || source.canTab === true) link.canTab = true
    if (Object.keys(sanitizedQuery).length) link.query = sanitizedQuery
    return link
}

/** 精确判断一条链接是否已完整配置、可用于前端触发 */
export function isDecorateLinkConfigured(raw?: DecorateLink | null): boolean {
    if (!raw || typeof raw !== 'object') return false
    const link = normalizeDecorateLink(raw)
    if (!link.path) return false

    switch (link.type) {
        case 'custom':
            return /^https?:\/\//i.test(String(link.query?.url || ''))
        case 'mini_program':
            return Boolean(String(link.query?.appId || '').trim())
        case 'staff':
        case 'dynamic':
            return /^[1-9]\d*$/.test(String(link.query?.id || ''))
        case 'action': {
            const action = String(link.query?.action || '')
            if (!VALID_ACTIONS.has(action)) return false
            if (action === 'copy_text') return Boolean(String(link.query?.text || '').trim())
            return true
        }
        default:
            return link.path.startsWith('/')
    }
}

/** 判断是否为「微信原生客服」快捷动作（需要渲染 <button open-type="contact">） */
export function isWechatContactLink(raw?: DecorateLink | null): boolean {
    if (!raw || typeof raw !== 'object') return false
    return (
        raw.path === 'action://wechat_contact' ||
        (raw.type === 'action' && raw.query?.action === 'wechat_contact')
    )
}

export interface DecorateLinkSummary {
    configured: boolean
    typeLabel: string
    title: string
    paramTags: string[]
}

const VALUE_LABELS: Record<string, Record<string, string>> = {
    dynamic_type: { '1': '图文', '2': '视频', '4': '活动' },
    sort: { latest: '最新', hot: '最热' },
    tab_order: {
        all: '全部',
        confirm: '待确认',
        unpaid: '待支付',
        service: '待服务',
        refund: '售后'
    },
    tab_search: { staff: '人员', article: '资讯' },
    scene: { support: '售前咨询', order: '订单咨询', aftersale: '售后支持' },
    agreement_type: {
        privacy: '隐私政策',
        service: '服务协议',
        booking: '预约服务协议',
        aftersale: '售后服务规则'
    },
    env_version: { release: '正式版', trial: '体验版', develop: '开发版' }
}

/** 生成可读摘要（后台 LinkPicker 回显、开屏预览摘要共用） */
export function formatDecorateLinkSummary(raw?: DecorateLink | null): DecorateLinkSummary {
    if (!isDecorateLinkConfigured(raw)) {
        return { configured: false, typeLabel: '', title: '', paramTags: [] }
    }
    const link = normalizeDecorateLink(raw)
    const q = link.query || {}

    if (link.type === 'action') {
        const def = DECORATE_ACTION_DEFS.find((a) => a.action === q.action)
        const paramTags: string[] = []
        if (q.action === 'make_phone_call') {
            paramTags.push(q.phone ? `号码：${q.phone}` : '平台客服热线')
        } else if (q.action === 'copy_text' && q.text) {
            paramTags.push(q.text.length > 16 ? `${q.text.slice(0, 16)}…` : q.text)
        }
        return {
            configured: true,
            typeLabel: '快捷动作',
            title: def?.label || link.name || '快捷动作',
            paramTags
        }
    }

    if (link.type === 'mini_program') {
        const paramTags: string[] = []
        if (q.appId) paramTags.push(q.appId)
        if (q.path) paramTags.push(q.path)
        const envLabel = VALUE_LABELS.env_version[q.env_version || 'release']
        if (envLabel && q.env_version && q.env_version !== 'release') {
            paramTags.push(envLabel)
        }
        return {
            configured: true,
            typeLabel: '跳转小程序',
            title:
                link.name && link.name !== '小程序跳转' && link.name !== '跳转小程序'
                    ? link.name
                    : q.appId || '跳转小程序',
            paramTags
        }
    }

    if (link.type === 'custom') {
        return {
            configured: true,
            typeLabel: '外部网页',
            title: q.url || '',
            paramTags: []
        }
    }

    if (link.type === 'staff') {
        const paramTags: string[] = []
        if (q.id) paramTags.push(`ID:${q.id}`)
        if (q.date) paramTags.push(`日期:${q.date}`)
        return {
            configured: true,
            typeLabel: '服务人员',
            title: link.name || '服务人员详情',
            paramTags
        }
    }

    if (link.type === 'dynamic') {
        return {
            configured: true,
            typeLabel: '动态详情',
            title: link.name || '动态详情',
            paramTags: q.id ? [`ID:${q.id}`] : []
        }
    }

    const pageDef = findDecorateLinkPage(link.path)
    const paramTags: string[] = []
    if (q.dynamic_type && VALUE_LABELS.dynamic_type[q.dynamic_type]) {
        paramTags.push(`分类:${VALUE_LABELS.dynamic_type[q.dynamic_type]}`)
    }
    if (q.sort && VALUE_LABELS.sort[q.sort]) {
        paramTags.push(`排序:${VALUE_LABELS.sort[q.sort]}`)
    }
    if (q.tab) {
        const label =
            link.path === '/pages/order/order'
                ? VALUE_LABELS.tab_order[q.tab]
                : link.path === '/pages/search/search'
                  ? VALUE_LABELS.tab_search[q.tab]
                  : q.tab
        if (label) paramTags.push(`页签:${label}`)
    }
    if (q.scene && VALUE_LABELS.scene[q.scene]) {
        paramTags.push(`场景:${VALUE_LABELS.scene[q.scene]}`)
    }
    if (q.type && link.path === '/pages/agreement/agreement' && VALUE_LABELS.agreement_type[q.type]) {
        paramTags.push(VALUE_LABELS.agreement_type[q.type])
    }
    if (q.category_id) {
        paramTags.push(q.category_name ? `分类:${q.category_name}` : `分类#${q.category_id}`)
    }
    if (q.tag_ids) {
        const count = q.tag_ids.split(',').filter(Boolean).length
        if (count > 0) paramTags.push(`风格:${count}项`)
    }
    if (q.city_name || q.district_name) {
        const area = [q.city_name, q.district_name].filter(Boolean).join('·')
        if (area) paramTags.push(`地区:${area}`)
    }
    if (q.date) {
        paramTags.push(`日期:${q.date}`)
    }
    if (q.tag) {
        paramTags.push(`标签:${q.tag}`)
    }
    if (q.keyword) {
        paramTags.push(`关键词:${q.keyword}`)
    }

    return {
        configured: true,
        typeLabel: pageDef?.canTab ? '底部 Tab 页' : pageDef?.internalOnly ? '工作人员页' : '移动端页面',
        title: pageDef?.name || link.name || link.path,
        paramTags
    }
}
