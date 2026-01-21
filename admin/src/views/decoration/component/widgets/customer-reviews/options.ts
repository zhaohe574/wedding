export default () => ({
    title: '客户评价',
    name: 'customer-reviews',
    content: {
        enabled: 1,
        title: '客户好评',
        style: 1, // 1=卡片样式, 2=横向滑动, 3=简洁列表
        show_count: 3, // 显示数量
        show_stats: 1, // 是否显示统计信息
        show_more: 0, // 是否显示查看更多
        more_link: {}, // 查看更多链接
        data: [
            {
                is_show: '1',
                avatar: '',
                name: '客户姓名',
                rating: 5,
                content: '评价内容',
                date: '2024-01-01',
                tag: '好评',
                service: '婚礼跟拍',
                images: []
            }
        ]
    },
    styles: {}
})
