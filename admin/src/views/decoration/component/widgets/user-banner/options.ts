export default () => ({
    title: '个人中心广告图',
    name: 'user-banner',
    pageScope: ['user'], // 仅在个人中心显示
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
