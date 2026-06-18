// 快捷入口组件数据项接口
export interface QuickEntryItem {
    key?: string // 系统入口标识，用于前台匹配实时统计
    icon: string // 图标
    title: string // 标题
    subtitle?: string // 副标题
    link: any // 跳转链接
    is_show: string // 是否显示 '1'=显示 '0'=隐藏
    requiresLogin?: boolean // 是否需要登录
    sort?: number // 排序
}

export default () => ({
    title: '快捷入口',
    name: 'quick-entry',
    pageScope: ['home', 'user'], // 首页和个人中心页面可用
    content: {
        enabled: 1,
        title: '账户入口',
        subtitle: '',
        style: 3,
        per_line: 1,
        data: [
            {
                key: 'order',
                icon: '',
                title: '我的订单',
                subtitle: '进行中订单',
                link: { path: '/pages/order/order', type: 'shop' },
                is_show: '1',
                requiresLogin: true,
                sort: 1
            },
            {
                key: 'activity',
                icon: '',
                title: '我的活动',
                subtitle: '报名进度',
                link: { path: '/packages/pages/my_activity/my_activity', type: 'shop' },
                is_show: '1',
                requiresLogin: true,
                sort: 2
            },
            {
                key: 'review',
                icon: '',
                title: '我的评价',
                subtitle: '评价记录',
                link: { path: '/packages/pages/review/list', type: 'shop' },
                is_show: '1',
                requiresLogin: true,
                sort: 3
            },
            {
                key: 'notification',
                icon: '',
                title: '通知中心',
                subtitle: '消息更新',
                link: { path: '/packages/pages/notification/index', type: 'shop' },
                is_show: '1',
                requiresLogin: true,
                sort: 4
            },
            {
                key: 'favorite',
                icon: '',
                title: '我的收藏',
                subtitle: '已收藏',
                link: { path: '/packages/pages/staff_favorite/staff_favorite', type: 'shop' },
                is_show: '1',
                requiresLogin: true,
                sort: 5
            },
            {
                key: 'aftersale',
                icon: '',
                title: '售后服务',
                subtitle: '售后进度',
                link: { path: '/packages/pages/aftersale/index', type: 'shop' },
                is_show: '1',
                requiresLogin: true,
                sort: 6
            },
            {
                key: 'waitlist',
                icon: '',
                title: '我的候补',
                subtitle: '候补进度',
                link: { path: '/packages/pages/waitlist/waitlist', type: 'shop' },
                is_show: '1',
                requiresLogin: true,
                sort: 7
            },
            {
                key: 'settings',
                icon: '',
                title: '设置',
                subtitle: '账号设置',
                link: { path: '/pages/user_set/user_set', type: 'shop' },
                is_show: '1',
                requiresLogin: true,
                sort: 8
            }
        ] as QuickEntryItem[]
    },
    styles: {}
})
