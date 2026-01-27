export default () => ({
    title: '活动专区',
    name: 'activity-zone',
    pageScope: ['home'], // 仅在首页显示
    content: {
        enabled: 1,
        title: '限时活动',
        style: 1, // 1=大图, 2=网格, 3=横向滑动, 4=列表
        show_count: 3, // 显示数量
        show_more: 0, // 是否显示查看更多
        more_link: {}, // 查看更多链接
        data_source: 'auto', // 数据来源：auto=自动获取最新, manual=手动选择
        activity_ids: [] // 手动选择的活动ID列表
    },
    styles: {}
})
