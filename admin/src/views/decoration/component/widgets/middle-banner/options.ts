export default () => ({
    title: '首页中部轮播图',
    name: 'middle-banner',
    pageScope: ['home'], // 仅在首页显示
    content: {
        enabled: 1,
        data: [
            {
                is_show: '1',
                image: '',
                name: '',
                link: {}
            }
        ]
    },
    styles: {}
})
