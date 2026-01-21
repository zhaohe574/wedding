export default () => ({
    title: '服务套餐',
    name: 'service-packages',
    content: {
        enabled: 1,
        title: '精选套餐',
        style: 1, // 1=横向滑动, 2=纵向列表, 3=大卡片
        card_width: '520rpx', // 卡片宽度（横向滑动样式）
        show_count: 4, // 显示数量
        show_more: 0, // 是否显示查看更多
        more_link: {}, // 查看更多链接
        data: [
            {
                is_show: '1',
                image: '',
                name: '套餐名称',
                desc: '套餐描述',
                price: '9999',
                original_price: '',
                tag: '热门',
                services: ['摄影师', '化妆师', '婚纱礼服'],
                link: {}
            }
        ]
    },
    styles: {}
})
