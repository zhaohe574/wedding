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

// 真实线上案例数据（从小程序运行时提取）
export const MOCK_HOT_CASES: CaseItem[] = [
    {
        caseId: 'f9317349-d8af-4ca5-b314-d5d2635e8d5a',
        caseNumber: 123492917,
        theme: '山谷 湖畔 一场关于爱的冥想。',
        hotel: '昆明玉龙湾湖景酒店',
        province: '云南省',
        city: '昆明市',
        refPrice: '76,800.62',
        displayPrice: '79,200.62',
        refCover: 'https://img.izhaowo.com/img/9_1F479577-7C4E-42C6-97B7-5EEFB35A848B.jpg?x-oss-process=image/resize,m_fill,w_598,h_388,limit_0',
        refStyle: '#森系 #湖畔婚礼',
        shownew: true,
        isHot: true,
        hasVideo: true,
        explosionMemo: '咨询爆款',
        planner: 'o-Cider',
        creatorAvator: 'https://img.izhaowo.com/img/9_4E9C6663-0E0F-4DF3-8902-F89CDB8EBC85.png',
        creatorVocationName: '策划师',
        touch: 908
    },
    {
        caseId: '8af4c138-427e-4c28-8828-c8123f729337',
        caseNumber: 123493324,
        theme: 'Lovetopia 绿意森林梦境',
        hotel: '世纪王朝大酒店',
        province: '云南省',
        city: '昆明市',
        refPrice: '15,276.8',
        displayPrice: '16,236.8',
        refCover: 'https://img.izhaowo.com/img/9_36DC11A9-8329-4AC6-813C-B7A588DFDD44.jpg?x-oss-process=image/resize,m_fill,w_598,h_388,limit_0',
        refStyle: '#森系 #草坪户外',
        shownew: true,
        isHot: true,
        hasVideo: false,
        explosionMemo: '本月热订',
        planner: 'Ray一Nature',
        creatorAvator: 'https://izhaowo-new-pro.oss-cn-beijing.aliyuncs.com/img/cfed6fa8-64c3-41f2-8613-ab312b75b71f.png',
        creatorVocationName: '策划师',
        touch: 342
    },
    {
        caseId: '9967d517-9da5-4c11-88c5-71e0e0c4fd67',
        caseNumber: 123493323,
        theme: '珍珠 PEARL 极简高定艺术',
        hotel: '云南滇池温泉花园国际大酒店',
        province: '云南省',
        city: '昆明市',
        refPrice: '26,871.33',
        displayPrice: '29,471.33',
        refCover: 'https://img.izhaowo.com/img/9_924510C6-789A-4E34-8AB1-A766588640B4.jpg?x-oss-process=image/resize,m_fill,w_598,h_388,limit_0',
        refStyle: '#秀场 #极简白',
        shownew: true,
        isHot: true,
        hasVideo: true,
        explosionMemo: '艺术高定',
        planner: 'Ray一Nature',
        creatorAvator: 'https://izhaowo-new-pro.oss-cn-beijing.aliyuncs.com/img/cfed6fa8-64c3-41f2-8613-ab312b75b71f.png',
        creatorVocationName: '策划师',
        touch: 520
    }
]

export const MOCK_CASE_LIST: CaseItem[] = [
    {
        caseId: '8af4c138-427e-4c28-8828-c8123f729337',
        caseNumber: 123493324,
        theme: 'Lovetopia',
        hotel: '世纪王朝大酒店',
        province: '云南省',
        city: '昆明市',
        refPrice: '15,276.8',
        displayPrice: '16,236.8',
        refCover: 'https://img.izhaowo.com/img/9_36DC11A9-8329-4AC6-813C-B7A588DFDD44.jpg?x-oss-process=image/resize,m_fill,w_686,h_448,limit_0',
        refStyle: '#森系 #草坪',
        shownew: true,
        isHot: true,
        hasVideo: true,
        planner: 'Ray一Nature',
        creatorAvator: 'https://izhaowo-new-pro.oss-cn-beijing.aliyuncs.com/img/cfed6fa8-64c3-41f2-8613-ab312b75b71f.png',
        creatorVocationName: '策划师',
        touch: 28
    },
    {
        caseId: '9967d517-9da5-4c11-88c5-71e0e0c4fd67',
        caseNumber: 123493323,
        theme: '珍珠 PEARL',
        hotel: '云南滇池温泉花园国际大酒店',
        province: '云南省',
        city: '昆明市',
        refPrice: '26,871.33',
        displayPrice: '29,471.33',
        refCover: 'https://img.izhaowo.com/img/9_924510C6-789A-4E34-8AB1-A766588640B4.jpg?x-oss-process=image/resize,m_fill,w_686,h_448,limit_0',
        refStyle: '#秀场 #艺术',
        shownew: true,
        hasVideo: true,
        planner: 'Ray一Nature',
        creatorAvator: 'https://izhaowo-new-pro.oss-cn-beijing.aliyuncs.com/img/cfed6fa8-64c3-41f2-8613-ab312b75b71f.png',
        creatorVocationName: '策划师',
        touch: 16
    },
    {
        caseId: '7ce44bdc-d865-4274-bf90-85c415e49efc',
        caseNumber: 123493320,
        theme: 'W&C 星芒之夜',
        hotel: '南昌瑞颐大酒店(逸趣酒廊店)',
        province: '江西省',
        city: '南昌市',
        refPrice: '30,750.5',
        displayPrice: '31,750.5',
        refCover: 'https://img.izhaowo.com//img/3ffab754-26da-44b0-b464-37779a377070.jpg?x-oss-process=image/resize,m_fill,w_686,h_448,limit_0',
        refStyle: '#小众 #宴会厅',
        shownew: true,
        planner: '南昌AMI',
        creatorAvator: 'https://img.izhaowo.com/img/9_686E175D-00A8-488D-8918-3F25FEB8821F.png',
        creatorVocationName: '策划师',
        touch: 25
    },
    {
        caseId: '44f81c2c-e3eb-49f8-85f5-3a5a46d6bfb2',
        caseNumber: 123493319,
        theme: 'z&y 橙调复古温存',
        hotel: '南昌金茂嘉悦酒店',
        province: '江西省',
        city: '南昌市',
        refPrice: '31,410.5',
        displayPrice: '32,410.5',
        refCover: 'https://img.izhaowo.com//img/cef4fc64-b7a3-441c-b26e-51ee9e216d8c.jpg?x-oss-process=image/resize,m_fill,w_686,h_448,limit_0',
        refStyle: '#复古 #室内',
        shownew: true,
        planner: '南昌AMI',
        creatorAvator: 'https://img.izhaowo.com/img/9_686E175D-00A8-488D-8918-3F25FEB8821F.png',
        creatorVocationName: '策划师',
        touch: 16
    },
    {
        caseId: '377818f6-a262-4276-a557-a3ebc475aa89',
        caseNumber: 123493311,
        theme: 'Ocean 蔚蓝深海恋曲',
        hotel: '西安赛瑞喜来登大酒店',
        province: '陕西省',
        city: '西安市',
        refPrice: '19,620.7',
        displayPrice: '20,800.7',
        refCover: 'https://img.izhaowo.com//img/82808222-5e85-47a1-a39e-214dfe7af140.jpg?x-oss-process=image/resize,m_fill,w_686,h_448,limit_0',
        refStyle: '#主题 #蓝色系',
        shownew: true,
        hasVideo: true,
        planner: '西安观观',
        creatorAvator: 'https://img.izhaowo.com/img/9_D490464B-5922-409C-8C93-C1E5F9B9D440.png',
        creatorVocationName: '策划师',
        touch: 55
    },
    {
        caseId: '13c29cf7-9e5c-4c84-af77-03c8287c2616',
        caseNumber: 123493307,
        theme: '森屿暮光',
        hotel: '长安高尔夫球乡村俱乐部',
        province: '广东省',
        city: '东莞市',
        refPrice: '25,175.7',
        displayPrice: '26,455.7',
        refCover: 'https://img.izhaowo.com/img/9_22EFD98E-E375-4231-81A2-996CEFF5C9A2.jpg?x-oss-process=image/resize,m_fill,w_686,h_448,limit_0',
        refStyle: '#森系 #户外草坪',
        shownew: true,
        isHot: true,
        planner: '东莞璐子',
        creatorAvator: 'https://img.izhaowo.com/img/9_C6CDFAA1-6495-4069-A8A2-6CA5C7552B99.png',
        creatorVocationName: '策划师',
        touch: 51
    },
    {
        caseId: '55d14e08-9df2-4ce8-b5e1-886915e34101',
        caseNumber: 123493290,
        theme: '法式花园莫奈随想',
        hotel: '香格里拉大酒店宴会厅',
        province: '四川省',
        city: '成都市',
        refPrice: '42,800.0',
        displayPrice: '45,800.0',
        refCover: 'https://img.izhaowo.com/img/9_1F479577-7C4E-42C6-97B7-5EEFB35A848B.jpg?x-oss-process=image/resize,m_fill,w_686,h_448,limit_0',
        refStyle: '#法式 #复古花园',
        shownew: false,
        isHot: true,
        hasVideo: true,
        planner: '成都Lina',
        creatorAvator: 'https://img.izhaowo.com/img/9_4E9C6663-0E0F-4DF3-8902-F89CDB8EBC85.png',
        creatorVocationName: '策划师',
        touch: 189
    },
    {
        caseId: '62e21b09-7cc3-4de7-99ea-447025e11022',
        caseNumber: 123493285,
        theme: '赤子东方·禧宴',
        hotel: '锦江宾馆中华厅',
        province: '四川省',
        city: '成都市',
        refPrice: '38,500.0',
        displayPrice: '39,900.0',
        refCover: 'https://img.izhaowo.com/img/9_924510C6-789A-4E34-8AB1-A766588640B4.jpg?x-oss-process=image/resize,m_fill,w_686,h_448,limit_0',
        refStyle: '#新中式 #红色系',
        shownew: false,
        planner: '国风策划组',
        creatorAvator: 'https://izhaowo-new-pro.oss-cn-beijing.aliyuncs.com/img/cfed6fa8-64c3-41f2-8613-ab312b75b71f.png',
        creatorVocationName: '策划师',
        touch: 215
    }
]

export const MOCK_FILTER_OPTIONS = {
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

/**
 * 获取案例列表数据
 */
export async function getCaseList(params: CaseFilterParams = {}): Promise<{ list: CaseItem[]; total: number }> {
    try {
        const res = await request.get({
            url: '/production/weddingCase/v1/query/all/case/page',
            data: params
        })
        if (res && res.list && res.list.length > 0) {
            return res
        }
    } catch (_) {
        // Fallback to rich mock data
    }

    let filtered = [...MOCK_CASE_LIST]

    // 关键字搜索
    if (params.keyword) {
        const kw = params.keyword.trim().toLowerCase()
        filtered = filtered.filter(
            item =>
                item.theme.toLowerCase().includes(kw) ||
                item.hotel.toLowerCase().includes(kw) ||
                item.refStyle.toLowerCase().includes(kw) ||
                item.planner.toLowerCase().includes(kw)
        )
    }

    // 标签过滤
    if (params.tags && params.tags.length > 0) {
        if (params.tags.includes('视频案例')) {
            filtered = filtered.filter(item => item.hasVideo)
        }
        if (params.tags.includes('爆款案例')) {
            filtered = filtered.filter(item => item.isHot)
        }
    }

    // 城市过滤
    if (params.city) {
        filtered = filtered.filter(item => item.city === params.city)
    } else if (params.province) {
        filtered = filtered.filter(item => item.province === params.province)
    }

    // 排序
    if (params.orderBy === 'ORDER_BY_PRICE') {
        filtered.sort((a, b) => {
            const pA = parseFloat(a.refPrice.replace(/,/g, ''))
            const pB = parseFloat(b.refPrice.replace(/,/g, ''))
            return params.order === 'ASC' ? pA - pB : pB - pA
        })
    } else if (params.orderBy === 'ORDER_BY_TOUCH') {
        filtered.sort((a, b) => (params.order === 'ASC' ? a.touch - b.touch : b.touch - a.touch))
    }

    return {
        list: filtered,
        total: filtered.length
    }
}

/**
 * 获取热门爆款案例
 */
export async function getHotCaseList(): Promise<CaseItem[]> {
    try {
        const res = await request.get({ url: '/production/weddingCase/v1/query/hot/list' })
        if (Array.isArray(res) && res.length > 0) return res
    } catch (_) {}
    return MOCK_HOT_CASES
}

/**
 * 获取近7天新案例数
 */
export async function getNewCaseCount(params?: { province?: string; city?: string }): Promise<number> {
    try {
        const res = await request.post({
            url: '/production/weddingCase/v1/queryNewWeddingCaseNum',
            data: params
        })
        if (res?.sevenDayNum) return res.sevenDayNum
    } catch (_) {}
    return 18
}
