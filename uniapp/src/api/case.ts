import request from '@/utils/request'

export interface CaseItem {
    caseId: string
    caseNumber: number
    theme: string
    hotel: string
    province: string
    city?: string
    refPrice: string
    displayPrice?: string
    refCover: string
    refStyle: string
    shownew?: boolean
    isHot?: boolean
    hasVideo?: boolean
    explosionMemo?: string
    planner: string
    creatorAvator: string
    creatorVocationName?: string
    touch: number
}

export interface CaseFilterParams {
    page?: number
    pageSize?: number
    order?: 'DESC' | 'ASC'
    orderBy?: 'ORDER_BY_CTIME' | 'ORDER_BY_PRICE' | 'ORDER_BY_TOUCH'
    styles?: string
    scenes?: string
    colors?: string
    minPrice?: number
    maxPrice?: number
    city?: string
    province?: string
    tags?: string[]
    keyword?: string
}

/**
 * 案例接口错误标记。
 * 说明：接口不可用时不允许回退到任何内嵌/第三方案例数据，
 * 由调用方根据该标记渲染空态与重试入口。
 */
export type CaseApiErrorCode = 'CASE_LIST_UNAVAILABLE' | 'HOT_CASE_UNAVAILABLE' | 'CASE_COUNT_UNAVAILABLE'

export interface CaseListResult {
    list: CaseItem[]
    total: number
    /** 存在该字段表示本次请求失败，列表为空数组 */
    error_code?: CaseApiErrorCode
}

export interface CaseFilterOption {
    id: string
    name: string
    hex?: string
}

export interface CaseFilterPriceOption {
    name: string
    minPrice: number
    maxPrice: number
}

export interface CaseFilterProvinceOption {
    province: string
    cities: string[]
}

export interface CaseFilterOptions {
    sortList: string[]
    tags: Array<{ name: string; checked: boolean }>
    scenes: CaseFilterOption[]
    styles: CaseFilterOption[]
    prices: CaseFilterPriceOption[]
    colors: CaseFilterOption[]
    provinces: CaseFilterProvinceOption[]
}

/**
 * 案例筛选面板的静态选项配置。
 * 说明：这是前端固定的筛选项字典（风格/场景/色系/预算/地区），不是业务数据，
 * 也不含任何第三方案例内容；业务案例数据一律来自服务端接口。
 */
export const CASE_FILTER_OPTIONS: CaseFilterOptions = {
    sortList: ['默认排序', '浏览优先', '价格降序', '价格升序'],
    tags: [
        { name: '视频案例', checked: false },
        { name: '设计解说', checked: false },
        { name: '爆款案例', checked: false }
    ],
    scenes: [
        { id: '1', name: '室内宴会厅' },
        { id: '2', name: '户外草坪' },
        { id: '3', name: '目的地婚礼' },
        { id: '4', name: '教堂仪式' },
        { id: '5', name: '民宿露台' },
        { id: '6', name: '特色餐厅' }
    ],
    styles: [
        { id: '1', name: '森系' },
        { id: '2', name: '法式' },
        { id: '3', name: '新中式' },
        { id: '4', name: '现代极简' },
        { id: '5', name: '复古风' },
        { id: '6', name: '轻奢' },
        { id: '7', name: '秀场风' },
        { id: '8', name: '小众主题' }
    ],
    prices: [
        { name: '1.5万以下', minPrice: 0, maxPrice: 15000 },
        { name: '1.5-2.5万', minPrice: 15000, maxPrice: 25000 },
        { name: '2.5-3.5万', minPrice: 25000, maxPrice: 35000 },
        { name: '3.5-5万', minPrice: 35000, maxPrice: 50000 },
        { name: '5-8万', minPrice: 50000, maxPrice: 80000 },
        { name: '8万以上', minPrice: 80000, maxPrice: -1 }
    ],
    colors: [
        { id: '1', name: '红色', hex: '#E53E3E' },
        { id: '2', name: '白色', hex: '#FFFFFF' },
        { id: '3', name: '香槟', hex: '#F6E05E' },
        { id: '4', name: '粉色', hex: '#ED64A6' },
        { id: '5', name: '蓝色', hex: '#3182CE' },
        { id: '6', name: '绿色', hex: '#38A169' },
        { id: '7', name: '金色', hex: '#C29B6B' },
        { id: '8', name: '紫色', hex: '#805AD5' },
        { id: '9', name: '橘色', hex: '#DD6B20' },
        { id: '10', name: '黑色', hex: '#1A202C' }
    ],
    provinces: [
        {
            province: '四川省',
            cities: ['成都市', '绵阳市', '德阳市', '乐山市', '宜宾市', '南充市']
        },
        {
            province: '云南省',
            cities: ['昆明市', '大理白族自治州', '丽江市', '玉溪市']
        },
        {
            province: '陕西省',
            cities: ['西安市', '咸阳市', '宝鸡市']
        },
        {
            province: '江西省',
            cities: ['南昌市', '九江市', '赣州市']
        },
        {
            province: '广东省',
            cities: ['广州市', '深圳市', '东莞市', '佛山市']
        }
    ]
}

/** 空列表结果，用于接口异常时的降级返回（不携带任何第三方数据） */
const emptyCaseListResult = (errorCode: CaseApiErrorCode): CaseListResult => ({
    list: [],
    total: 0,
    error_code: errorCode
})

/**
 * 获取案例列表数据
 * 接口异常时返回空列表并附带 error_code，由页面渲染空态，绝不回退到内嵌案例数据。
 */
export async function getCaseList(params: CaseFilterParams = {}): Promise<CaseListResult> {
    try {
        const res = await request.get({
            url: '/production/weddingCase/v1/query/all/case/page',
            data: params
        })
        if (res && Array.isArray(res.list)) {
            return {
                list: res.list as CaseItem[],
                total: Number(res.total ?? res.list.length)
            }
        }
        return emptyCaseListResult('CASE_LIST_UNAVAILABLE')
    } catch (error) {
        console.error('[case] 案例列表加载失败', error)
        return emptyCaseListResult('CASE_LIST_UNAVAILABLE')
    }
}

/**
 * 获取热门爆款案例
 * 接口异常时返回空数组，页面不展示热门区。
 */
export async function getHotCaseList(): Promise<CaseItem[]> {
    try {
        const res = await request.get({ url: '/production/weddingCase/v1/query/hot/list' })
        if (Array.isArray(res)) {
            return res as CaseItem[]
        }
        return []
    } catch (error) {
        console.error('[case] 热门案例加载失败', error)
        return []
    }
}

/**
 * 获取近7天新案例数
 * 接口异常时返回 0，页面在 count === 0 时不展示角标。
 */
export async function getNewCaseCount(params?: { province?: string; city?: string }): Promise<number> {
    try {
        const res = await request.post({
            url: '/production/weddingCase/v1/queryNewWeddingCaseNum',
            data: params
        })
        const count = Number(res?.sevenDayNum ?? 0)
        return Number.isFinite(count) && count > 0 ? count : 0
    } catch (error) {
        console.error('[case] 近7天新案例数加载失败', error)
        return 0
    }
}
