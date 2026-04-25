export interface HomeBrandContent {
    enabled: 0 | 1
    greeting: string
    team_name: string
    subtitle: string
    cta_text: string
    cta_link: Record<string, any>
}

export default () => ({
    title: '团队信息',
    name: 'home-brand',
    pageScope: ['home'],
    content: {
        enabled: 1,
        greeting: 'Hello,',
        team_name: '我们是星意主持人工作室',
        subtitle: '选星意，有心意',
        cta_text: '立即预定',
        cta_link: {
            path: '/pages/schedule_query/schedule_query',
            type: 'shop'
        }
    } as HomeBrandContent,
    styles: {}
})
