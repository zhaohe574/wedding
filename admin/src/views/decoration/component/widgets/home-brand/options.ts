export interface HomeBrandContent {
    enabled: 0 | 1
    greeting: string
    team_name: string
    subtitle: string
    cta_text: string
    cta_link: Record<string, any>
    stats: Array<{
        value: string
        label: string
    }>
}

export default () => ({
    title: '团队信息',
    name: 'home-brand',
    pageScope: ['home'],
    content: {
        enabled: 1,
        greeting: 'Hello,',
        team_name: '主持人',
        subtitle: '专业主持，用心服务',
        cta_text: '立即预定',
        cta_link: {
            path: '/pages/schedule_query/schedule_query',
            type: 'shop'
        },
        stats: [
            { value: '1000+', label: '场仪式' },
            { value: '98%', label: '好评' },
            { value: '30+', label: '城市' }
        ]
    } as HomeBrandContent,
    styles: {}
})
