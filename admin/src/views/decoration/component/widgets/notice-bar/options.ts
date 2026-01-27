// 公告通知组件数据项接口
export interface NoticeBarItem {
    notice_id: number // 公告ID（引用）
    is_show: string // 是否显示 '1'=显示 '0'=隐藏
    sort?: number // 排序
}

export default () => ({
    title: '公告通知',
    name: 'notice-bar',
    pageScope: ['home', 'user', 'news'], // 首页、个人中心、资讯页面可用
    content: {
        enabled: 1,
        style: 1, // 1=横向滚动, 2=纵向滚动, 3=静态展示
        show_count: 3, // 显示数量
        scroll_speed: 50, // 滚动速度（像素/秒）
        bg_color: '#FFF7ED', // 背景颜色
        text_color: '#EA580C', // 文字颜色
        // 公告列表（只保存引用ID）
        data: [] as NoticeBarItem[]
    },
    styles: {}
})
