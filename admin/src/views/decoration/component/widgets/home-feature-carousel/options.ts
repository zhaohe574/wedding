export interface HomeFeatureCarouselItem {
    is_show: '0' | '1'
    image: string
    name: string
    link: Record<string, any>
}

export default () => ({
    title: '预约图片区',
    name: 'home-feature-carousel',
    pageScope: ['home'],
    content: {
        enabled: 1,
        height: 300,
        autoplay: 1,
        interval: 5000,
        data: [
            {
                is_show: '1',
                image: '',
                name: '',
                link: {
                    path: '/pages/schedule_query/schedule_query',
                    type: 'shop'
                }
            }
        ] as HomeFeatureCarouselItem[]
    },
    styles: {}
})
