// 服务套餐组件数据项接口
export interface ServicePackageItem {
    package_id: number
    is_show: string
    sort?: number
    // 以下字段由后端动态填充，前端不保存
    image?: string
    name?: string
    desc?: string
    price?: string
    original_price?: string
    tag?: string
    services?: string[]
    link?: {
        path: string
        query: { id: number }
        type: string
    }
}

export default () => ({
    title: '服务套餐',
    name: 'service-packages',
    pageScope: ['home'], // 仅在首页显示
    content: {
        enabled: 1,
        title: '精选套餐',
        style: 1, // 1=横向滑动, 2=纵向列表, 3=大卡片
        card_width: '520rpx', // 卡片宽度（横向滑动样式）
        show_count: 4, // 显示数量
        show_more: 0, // 是否显示查看更多
        more_link: {}, // 查看更多链接
        // 套餐列表：只存储引用ID和控制信息，业务数据由后端动态填充
        data: [] as ServicePackageItem[]
    },
    styles: {}
})
