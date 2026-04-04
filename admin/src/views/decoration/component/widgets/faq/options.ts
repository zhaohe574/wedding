// 常见问题组件数据项接口
export interface FaqItem {
    question: string // 问题
    answer: string // 答案（支持富文本）
    category: string // 分类
    is_show: string // 是否显示 '1'=显示 '0'=隐藏
    sort?: number // 排序
}

export default () => ({
    title: '常见问题',
    name: 'faq',
    pageScope: ['home', 'user'], // 首页和个人中心页面可用
    content: {
        enabled: 1,
        title: '常见问题',
        style: 1, // 1=折叠面板, 2=列表式
        show_search: 0, // 是否显示搜索框
        // 常见问题列表
        data: [
            {
                question: '如何预约服务？',
                answer: '您可以在首页选择服务项目，选择日期和时间，填写相关信息后提交预约。',
                category: '预约相关',
                is_show: '1',
                sort: 1
            },
            {
                question: '可以取消预约吗？',
                answer: '可以的，您可以在"我的订单"中找到对应订单，点击取消预约。请注意取消时间限制。',
                category: '预约相关',
                is_show: '1',
                sort: 2
            }
        ] as FaqItem[]
    },
    styles: {}
})
