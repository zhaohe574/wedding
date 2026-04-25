export interface HomeServiceCategoryItem {
    is_show: '0' | '1'
    title: string
    subtitle: string
    image: string
    size: 'large' | 'small' | 'wide'
    link: Record<string, any>
}

export default () => ({
    title: '服务分类',
    name: 'home-service-categories',
    pageScope: ['home'],
    content: {
        enabled: 1,
        data: [
            {
                is_show: '1',
                title: '西式主持',
                subtitle: 'WEDDING HOST',
                image: '',
                size: 'large',
                link: {
                    path: '/pages/schedule_query/schedule_query',
                    type: 'shop',
                    query: { keyword: '西式主持' }
                }
            },
            {
                is_show: '1',
                title: '中式主持',
                subtitle: 'CHINESE HOST',
                image: '',
                size: 'small',
                link: {
                    path: '/pages/schedule_query/schedule_query',
                    type: 'shop',
                    query: { keyword: '中式主持' }
                }
            },
            {
                is_show: '1',
                title: '商务主持',
                subtitle: 'BUSINESS HOST',
                image: '',
                size: 'small',
                link: {
                    path: '/pages/schedule_query/schedule_query',
                    type: 'shop',
                    query: { keyword: '商务主持' }
                }
            },
            {
                is_show: '1',
                title: '主持培训课程',
                subtitle: 'HOST TRAINING',
                image: '',
                size: 'wide',
                link: {
                    path: '/pages/news/news',
                    type: 'shop'
                }
            }
        ] as HomeServiceCategoryItem[]
    },
    styles: {}
})
