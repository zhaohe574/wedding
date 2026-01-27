// 快捷入口组件数据项接口
export interface QuickEntryItem {
    icon: string // 图标
    title: string // 标题
    link: any // 跳转链接
    is_show: string // 是否显示 '1'=显示 '0'=隐藏
    sort?: number // 排序
}

export default () => ({
    title: '快捷入口',
    name: 'quick-entry',
    pageScope: ['home', 'user'], // 首页和个人中心页面可用
    content: {
        enabled: 1,
        style: 1, // 1=网格布局, 2=横向滑动
        per_line: 4, // 每行显示数量（4或5）
        // 快捷入口列表
        data: [
            {
                icon: 'icon-fuwu',
                title: '预约服务',
                link: { path: '/pages/service/index', type: 'shop' },
                is_show: '1',
                sort: 1
            },
            {
                icon: 'icon-dingdan',
                title: '我的订单',
                link: { path: '/pages/order/list', type: 'shop' },
                is_show: '1',
                sort: 2
            },
            {
                icon: 'icon-youhuiquan',
                title: '优惠券',
                link: { path: '/pages/coupon/list', type: 'shop' },
                is_show: '1',
                sort: 3
            },
            {
                icon: 'icon-kefu',
                title: '联系客服',
                link: { path: '/pages/customer-service/index', type: 'shop' },
                is_show: '1',
                sort: 4
            }
        ] as QuickEntryItem[]
    },
    styles: {}
})
