export default () => ({
    title: '案例作品',
    name: 'portfolio-gallery',
    content: {
        enabled: 1,
        title: '精选案例',
        style: 1, // 1=网格, 2=瀑布流, 3=横向滑动
        per_line: 2, // 每行显示数量（网格样式）
        show_count: 6, // 显示数量
        show_tabs: 0, // 是否显示分类标签
        show_more: 0, // 是否显示查看更多
        more_link: {}, // 查看更多链接
        data: [
            {
                is_show: '1',
                cover: '',
                title: '案例名称',
                category: '婚礼跟拍',
                type: 'image', // image/video
                views: 0,
                height: '360rpx', // 瀑布流高度
                link: {}
            }
        ]
    },
    styles: {}
})
