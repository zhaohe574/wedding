import { isObject } from '@vue/shared'
import { parseQuery } from 'uniapp-router-next'

/**
 * @description 获取元素节点信息（在组件中的元素必须要传ctx）
 * @param  { String } selector 选择器 '.app' | '#app'
 * @param  { Boolean } all 是否多选
 * @param  { ctx } context 当前组件实例
 */
export const getRect = <T = any>(selector: string, all = false, context?: any) => {
    return new Promise<T>((resolve, reject) => {
        let qurey = uni.createSelectorQuery()
        if (context) {
            qurey = uni.createSelectorQuery().in(context)
        }
        qurey[all ? 'selectAll' : 'select'](selector)
            .boundingClientRect(function (rect) {
                if (all && Array.isArray(rect) && rect.length) {
                    return resolve(rect as T)
                }
                if (!all && rect) {
                    return resolve(rect as T)
                }
                reject('找不到元素')
            })
            .exec()
    })
}

/**
 * @description 获取当前页面实例
 */
export function currentPage() {
    const pages = getCurrentPages()
    const currentPage = pages[pages.length - 1]
    return currentPage || {}
}

/**
 * @description 后台选择链接专用跳转
 */
interface Link {
    path: string
    name?: string
    type?: string
    canTab?: boolean
    query?: Record<string, any>
}

type LinkInput = Partial<Link> | string | null | undefined

export enum LinkTypeEnum {
    'SHOP_PAGES' = 'shop',
    'CUSTOM_LINK' = 'custom',
    'MINI_PROGRAM' = 'mini_program'
}

const LEGACY_LINK_MAP: Record<string, string> = {
    '/pages/service/index': '/pages/schedule_query/schedule_query',
    '/pages/staff/list': '/pages/staff_list/staff_list',
    '/packages/pages/staff_list/staff_list': '/pages/staff_list/staff_list',
    '/pages/order/list': '/pages/order/order',
    '/pages/customer-service/index': '/packages/pages/customer_service/customer_service',
    '/pages/customer_service/customer_service': '/packages/pages/customer_service/customer_service',
    '/pages/news_detail/news_detail': '/packages/pages/news_detail/news_detail',
    '/pages/collection/collection': '/packages/pages/collection/collection',
    '/pages/agreement/agreement': '/packages/pages/agreement/agreement',
    '/pages/dynamic_publish/dynamic_publish': '/packages/pages/dynamic_publish/dynamic_publish',
    '/pages/notification/index': '/packages/pages/notification/index',
    '/pages/review/list': '/packages/pages/review/list',
    '/pages/review/publish': '/packages/pages/review/publish',
    '/pages/review/detail': '/packages/pages/review/detail',
    '/pages/order_change/list': '/packages/pages/order_change/list',
    '/pages/order_change/change_detail': '/packages/pages/order_change/change_detail',
    '/pages/order_change/apply_date': '/packages/pages/order_change/apply_date',
    '/pages/order_change/apply_pause': '/packages/pages/order_change/apply_pause',
    '/pages/order_change/pause_detail': '/packages/pages/order_change/pause_detail',
    '/pages/order_change/apply_add_item': '/packages/pages/order_change/apply_add_item',
    '/pages/aftersale/index': '/packages/pages/aftersale/index',
    '/pages/aftersale/ticket': '/packages/pages/aftersale/ticket',
    '/pages/aftersale/create_ticket': '/packages/pages/aftersale/create_ticket',
    '/pages/aftersale/ticket_detail': '/packages/pages/aftersale/ticket_detail',
    '/pages/aftersale/complaint': '/packages/pages/aftersale/complaint',
    '/pages/aftersale/create_complaint': '/packages/pages/aftersale/create_complaint',
    '/pages/aftersale/complaint_detail': '/packages/pages/aftersale/complaint_detail',
    '/pages/aftersale/callback': '/packages/pages/aftersale/callback',
    '/pages/aftersale/callback_detail': '/packages/pages/aftersale/callback_detail',
    '/pages/user_wallet/user_wallet': '/packages/pages/user_wallet/user_wallet',
    '/pages/recharge_record/recharge_record': '/packages/pages/recharge_record/recharge_record'
}

export const normalizeAppPath = (path = '') => {
    let nextPath = path.trim()
    if (!nextPath) return ''

    if (nextPath.startsWith('/mobile/')) {
        nextPath = nextPath.replace(/^\/mobile/, '')
    }

    if (!nextPath.startsWith('/')) {
        nextPath = `/${nextPath}`
    }

    return LEGACY_LINK_MAP[nextPath] || nextPath
}

const parsePathQuery = (rawPath = '') => {
    const [pathPart, queryString = ''] = rawPath.split('?')
    const parsedQuery = queryString ? (parseQuery(queryString) as Record<string, any>) : {}

    return {
        path: normalizeAppPath(pathPart),
        query: parsedQuery
    }
}

export const resolveAppLink = (link: LinkInput) => {
    if (!link) return null

    if (typeof link === 'string') {
        const resolved = parsePathQuery(link.trim())
        return resolved.path
            ? {
                  path: resolved.path,
                  query: resolved.query
              }
            : null
    }

    if (typeof link !== 'object') {
        return null
    }

    const rawPath = String(link.path || '').trim()
    if (!rawPath) {
        return null
    }

    const resolved = parsePathQuery(rawPath)
    if (!resolved.path) {
        return null
    }

    return {
        path: resolved.path,
        query: {
            ...resolved.query,
            ...(link.query || {})
        }
    }
}

export const getLinkPath = (link: LinkInput) => resolveAppLink(link)?.path || ''

export const hasConfiguredLink = (link: LinkInput) => Boolean(getLinkPath(link))

const TABBAR_PATHS = new Set(['/pages/index/index', '/pages/dynamic/dynamic', '/pages/user/user'])

const buildUrl = (path: string, query?: Record<string, any>) => {
    if (!query || !Object.keys(query).length) {
        return path
    }
    return `${path}?${objectToQuery(query)}`
}

const handleNavigateFail = (error: any) => {
    console.error('页面跳转失败:', error)
    uni.showToast({
        title: '页面跳转失败，请稍后重试',
        icon: 'none'
    })
}

export function navigateTo(
    link: LinkInput = {},
    navigateType: 'navigateTo' | 'switchTab' | 'reLaunch' = 'navigateTo'
) {
    if (!link) {
        uni.showToast({ title: '页面暂未配置', icon: 'none' })
        return
    }

    if (typeof link === 'string') {
        const resolvedLink = resolveAppLink(link)
        if (!resolvedLink) {
            uni.showToast({ title: '页面暂未配置', icon: 'none' })
            return
        }

        navigateTo(
            {
                path: resolvedLink.path,
                query: resolvedLink.query
            },
            navigateType
        )
        return
    }

    if (typeof link !== 'object') {
        uni.showToast({ title: '页面暂未配置', icon: 'none' })
        return
    }

    // 如果是小程序跳转
    if (link.type === LinkTypeEnum.MINI_PROGRAM) {
        navigateToMiniProgram(link as Link)
        return
    }

    const resolvedLink = resolveAppLink(link)
    if (!resolvedLink) {
        uni.showToast({ title: '页面暂未配置', icon: 'none' })
        return
    }
    const { path, query } = resolvedLink

    const shouldSwitchTab =
        TABBAR_PATHS.has(path) && (navigateType === 'switchTab' || !!link.canTab)
    const url = buildUrl(path, query)

    if (shouldSwitchTab) {
        uni.switchTab({
            url: path,
            fail: handleNavigateFail
        })
        return
    }

    if (navigateType === 'reLaunch') {
        uni.reLaunch({
            url,
            fail: handleNavigateFail
        })
        return
    }

    uni.navigateTo({
        url,
        fail: handleNavigateFail
    })
}

/**
 * @description 小程序跳转
 * @param link 跳转信息，由装修数据进行输入
 */
export function navigateToMiniProgram(link: Link) {
    const query = link.query
    // #ifdef H5
    window.open(
        `weixin://dl/business/?appid=${query?.appId}&path=${query?.path}&env_version=${
            query?.env_version
        }&query=${encodeURIComponent(query?.query)}`
    )
    // #endif
    // #ifdef MP
    uni.navigateToMiniProgram({
        appId: query?.appId,
        path: query?.path,
        extraData: parseQuery(query?.query),
        envVersion: query?.env_version
    })
    // #endif
}

/**
 * @description 将一个数组分成几个同等长度的数组
 * @param  { Array } array[分割的原数组]
 * @param  { Number } size[每个子数组的长度]
 */
export const sliceArray = (array: any[], size: number) => {
    const result = []
    for (let x = 0; x < Math.ceil(array.length / size); x++) {
        const start = x * size
        const end = start + size
        result.push(array.slice(start, end))
    }
    return result
}

/**
 * @description 是否为空
 * @param {unknown} value
 * @return {Boolean}
 */
export const isEmpty = (value: unknown) => {
    return value == null && typeof value == 'undefined'
}

/**
 * @description 对象格式化为Query语法
 * @param { Object } params
 * @return {string} Query语法
 */
export function objectToQuery(params: Record<string, any>): string {
    let query = ''
    for (const props of Object.keys(params)) {
        const value = params[props]
        const part = encodeURIComponent(props) + '='
        if (!isEmpty(value)) {
            console.log(encodeURIComponent(props), isObject(value))
            if (isObject(value)) {
                for (const key of Object.keys(value)) {
                    if (!isEmpty(value[key])) {
                        const params = props + '[' + key + ']'
                        const subPart = encodeURIComponent(params) + '='
                        query += subPart + encodeURIComponent(value[key]) + '&'
                    }
                }
            } else {
                query += part + encodeURIComponent(value) + '&'
            }
        }
    }
    return query.slice(0, -1)
}

/**
 * @description 添加单位
 * @param {String | Number} value 值 100
 * @param {String} unit 单位 px em rem
 */
export const addUnit = (value: string | number, unit = 'rpx') => {
    return !Object.is(Number(value), NaN) ? `${value}${unit}` : value
}

/**
 * @description 格式化输出价格
 * @param  { string } price 价格
 * @param  { string } take 小数点操作
 * @param  { string } prec 小数位补
 */
export function formatPrice({ price, take = 'all', prec = undefined }: any) {
    const parts = (price + '').split('.')
    const integer = parts[0]
    let decimals = parts[1] || ''

    // 小数位补
    if (prec !== undefined) {
        const LEN = decimals.length
        for (let i = prec - LEN; i > 0; --i) decimals += '0'
        decimals = decimals.substr(0, prec)
    }

    switch (take) {
        case 'int':
            return integer
        case 'dec':
            return decimals
        case 'all':
            return integer + '.' + decimals
    }
}

/**
 * @description 组合异步任务
 * @param  { string } task 异步任务
 */

export function series(...task: Array<(_arg: any) => any>) {
    return function (): Promise<any> {
        return new Promise((resolve, reject) => {
            const iteratorTask = task.values()
            const next = (res?: any) => {
                const nextTask = iteratorTask.next()
                if (nextTask.done) {
                    resolve(res)
                } else {
                    Promise.resolve(nextTask.value(res)).then(next).catch(reject)
                }
            }
            next()
        })
    }
}
