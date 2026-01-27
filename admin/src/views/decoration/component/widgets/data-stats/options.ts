// 数据统计卡片数据项接口
export interface DataStatsItem {
    icon: string // 图标
    title: string // 标题
    value: string // 数值（动态数据标识）
    unit: string // 单位
    is_show: string // 是否显示 '1'=显示 '0'=隐藏
    sort?: number // 排序
}

export default () => ({
    title: '数据统计卡片',
    name: 'data-stats',
    pageScope: ['user'], // 个人中心页面可用
    content: {
        enabled: 1,
        style: 1, // 1=横向排列, 2=纵向排列, 3=网格布局
        // 统计数据列表
        data: [
            {
                icon: 'icon-dingdan',
                title: '我的订单',
                value: 'order_count', // 动态数据标识
                unit: '个',
                is_show: '1',
                sort: 1
            },
            {
                icon: 'icon-youhuiquan',
                title: '优惠券',
                value: 'coupon_count',
                unit: '张',
                is_show: '1',
                sort: 2
            },
            {
                icon: 'icon-shoucang',
                title: '我的收藏',
                value: 'collect_count',
                unit: '个',
                is_show: '1',
                sort: 3
            },
            {
                icon: 'icon-liulan',
                title: '浏览记录',
                value: 'view_count',
                unit: '条',
                is_show: '1',
                sort: 4
            }
        ] as DataStatsItem[]
    },
    styles: {}
})
