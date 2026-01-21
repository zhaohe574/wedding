export default () => ({
    title: '活动专区',
    name: 'activity-zone',
    content: {
        enabled: 1,
        title: '限时活动',
        style: 1, // 1=大图, 2=网格, 3=横向滑动, 4=列表
        show_count: 3, // 显示数量
        show_more: 0, // 是否显示查看更多
        more_link: {}, // 查看更多链接
        data: [
            {
                is_show: '1',
                image: '',
                title: '活动名称',
                desc: '活动描述',
                tag: '限时',
                price: '',
                original_price: '',
                show_countdown: 0,
                end_time: '',
                link: {}
            }
        ]
    },
    styles: {}
})
