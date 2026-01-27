// 热门话题组件数据项接口
export interface HotTopicItem {
    topic_id: number // 话题ID（引用）
    is_show: string // 是否显示 '1'=显示 '0'=隐藏
    sort?: number // 排序
}

export default () => ({
    title: '热门话题',
    name: 'hot-topics',
    pageScope: ['home', 'news'], // 首页和资讯页面可用
    content: {
        enabled: 1,
        title: '热门话题',
        source: 1, // 1=手动选择, 2=自动获取热门
        style: 1, // 1=标签云, 2=横向滑动, 3=列表式
        show_count: 10, // 显示数量
        // 话题列表（只保存引用ID）
        data: [] as HotTopicItem[]
    },
    styles: {}
})
