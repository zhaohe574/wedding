export default () => ({
    title: '订单入口',
    name: 'order-quick-entry',
    pageScope: ['home'], // 仅在首页显示
    content: {
        enabled: 1,
        title: '我的订单',
        show_shadow: true,
        show_recent: false,
        currency: '¥',
        data: [
            {
                is_show: '1',
                icon: '',
                name: '待付款',
                status: 'pending_pay',
                count: 0,
                link: {
                    path: '/pages/order/list',
                    name: '订单列表',
                    type: 'page',
                    query: { status: 'pending_pay' }
                }
            },
            {
                is_show: '1',
                icon: '',
                name: '待确认',
                status: 'pending_confirm',
                count: 0,
                link: {
                    path: '/pages/order/list',
                    name: '订单列表',
                    type: 'page',
                    query: { status: 'pending_confirm' }
                }
            },
            {
                is_show: '1',
                icon: '',
                name: '进行中',
                status: 'processing',
                count: 0,
                link: {
                    path: '/pages/order/list',
                    name: '订单列表',
                    type: 'page',
                    query: { status: 'processing' }
                }
            },
            {
                is_show: '1',
                icon: '',
                name: '已完成',
                status: 'completed',
                count: 0,
                link: {
                    path: '/pages/order/list',
                    name: '订单列表',
                    type: 'page',
                    query: { status: 'completed' }
                }
            }
        ],
        recent_orders: []
    },
    styles: {}
})
