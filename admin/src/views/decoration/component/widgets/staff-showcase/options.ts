// 员工展示组件数据项接口
export interface StaffShowcaseItem {
    staff_id: number
    is_show: string
    sort?: number
    // 以下字段由后端动态填充，前端不保存
    avatar?: string
    name?: string
    role?: string
    rating?: string
    order_count?: number
    tags?: string[]
    link?: {
        path: string
        query: { id: number }
        type: string
    }
}

export default () => ({
    title: '人员推荐',
    name: 'staff-showcase',
    pageScope: ['home'], // 仅在首页显示
    content: {
        enabled: 1,
        title: '推荐人员',
        style: 1, // 1=横向滑动卡片, 2=列表样式
        show_count: 6, // 显示数量
        show_more: 0, // 是否显示查看更多
        more_link: {}, // 查看更多链接
        // 人员列表：只存储引用ID和控制信息，业务数据由后端动态填充
        data: [] as StaffShowcaseItem[]
    },
    styles: {}
})
