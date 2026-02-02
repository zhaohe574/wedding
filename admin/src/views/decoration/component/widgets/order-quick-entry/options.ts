export default () => ({
    title: '订单快捷入口',
    name: 'order-quick-entry',
    pageScope: ['home', 'user'], // 首页和个人中心都可以使用
    content: {
        enabled: 1,
        title: '我的订单',
        show_shadow: true,
        show_recent: false,
        currency: '¥',
        columns: 5, // 每行显示个数，默认5个
        data: [
            {
                is_show: '1',
                icon: '',
                name: '待确认',
                status: 0, // 订单状态：0-待确认
                count: 0,
                link: {
                    path: '/pages/order/order',
                    name: '我的订单',
                    type: 'shop',
                    query: { status: 0 }
                }
            },
            {
                is_show: '1',
                icon: '',
                name: '待付款',
                status: 1, // 订单状态：1-待付款
                count: 0,
                link: {
                    path: '/pages/order/order',
                    name: '我的订单',
                    type: 'shop',
                    query: { status: 1 }
                }
            },
            {
                is_show: '1',
                icon: '',
                name: '已支付',
                status: 2, // 订单状态：2-已支付
                count: 0,
                link: {
                    path: '/pages/order/order',
                    name: '我的订单',
                    type: 'shop',
                    query: { status: 2 }
                }
            },
            {
                is_show: '1',
                icon: '',
                name: '服务中',
                status: 3, // 订单状态：3-服务中
                count: 0,
                link: {
                    path: '/pages/order/order',
                    name: '我的订单',
                    type: 'shop',
                    query: { status: 3 }
                }
            },
            {
                is_show: '1',
                icon: '',
                name: '已完成',
                status: 4, // 订单状态：4-已完成
                count: 0,
                link: {
                    path: '/pages/order/order',
                    name: '我的订单',
                    type: 'shop',
                    query: { status: 4 }
                }
            }
        ],
        recent_orders: []
    },
    styles: {
        paddingTop: 24,
        paddingBottom: 24,
        paddingLeft: 24,
        paddingRight: 24,
        marginTop: 0,
        marginBottom: 0,
        backgroundColor: 'transparent'
    }
})
