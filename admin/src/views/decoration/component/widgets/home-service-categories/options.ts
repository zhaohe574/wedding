export interface HomeServiceCategoryLink {
    path?: string
    name?: string
    type?: string
    query?: Record<string, string | number | boolean | null | undefined>
    [key: string]: any
}

export interface HomeServiceCategoryItem {
    is_show: '0' | '1'
    title: string
    subtitle: string
    image: string
    size: 'large' | 'small' | 'wide'
    text_position: 'top' | 'middle' | 'bottom'
    text_align: 'left' | 'center' | 'right'
    link: HomeServiceCategoryLink
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
                text_position: 'bottom',
                text_align: 'left',
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
                text_position: 'bottom',
                text_align: 'left',
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
                text_position: 'bottom',
                text_align: 'left',
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
                text_position: 'bottom',
                text_align: 'left',
                link: {
                    path: '/pages/news/news',
                    type: 'shop'
                }
            }
        ] as HomeServiceCategoryItem[]
    },
    styles: {}
})
