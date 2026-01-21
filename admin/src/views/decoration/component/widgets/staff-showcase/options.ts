export default () => ({
    title: '人员推荐',
    name: 'staff-showcase',
    content: {
        enabled: 1,
        title: '推荐人员',
        style: 1, // 1=卡片样式, 2=列表样式
        per_line: 2, // 每行显示数量（卡片样式）
        show_count: 4, // 显示数量
        show_more: 0, // 是否显示查看更多
        more_link: {}, // 查看更多链接
        data: [
            {
                is_show: '1',
                avatar: '',
                name: '人员名称',
                role: '摄影师',
                rating: '5.0',
                order_count: 0,
                tags: ['专业', '耐心'],
                link: {}
            }
        ]
    },
    styles: {}
})
