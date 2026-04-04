// 服务流程组件数据项接口
export interface ProcessStep {
    icon: string // 步骤图标
    title: string // 步骤标题
    description: string // 步骤描述
    is_show: string // 是否显示 '1'=显示 '0'=隐藏
    sort?: number // 排序
}

export default () => ({
    title: '服务流程',
    name: 'service-process',
    pageScope: ['home'], // 首页可用
    content: {
        enabled: 1,
        title: '服务流程',
        style: 1, // 1=时间轴, 2=步骤卡片, 3=横向流程图
        line_color: '#E5E7EB', // 流程线颜色
        icon_style: 1, // 1=圆形, 2=方形
        // 流程步骤列表
        data: [
            {
                icon: 'icon-zixun',
                title: '咨询沟通',
                description: '了解您的需求和预算',
                is_show: '1',
                sort: 1
            },
            {
                icon: 'icon-yuyue',
                title: '预约服务',
                description: '选择服务项目和时间',
                is_show: '1',
                sort: 2
            },
            {
                icon: 'icon-queren',
                title: '确认方案',
                description: '确定服务方案和细节',
                is_show: '1',
                sort: 3
            },
            {
                icon: 'icon-zhifu',
                title: '支付定金',
                description: '支付服务定金',
                is_show: '1',
                sort: 4
            },
            {
                icon: 'icon-fuwu',
                title: '服务执行',
                description: '按约定时间提供服务',
                is_show: '1',
                sort: 5
            }
        ] as ProcessStep[]
    },
    styles: {}
})
