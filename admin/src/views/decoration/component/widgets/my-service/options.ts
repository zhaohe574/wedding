export default () => ({
    title: '我的服务',
    name: 'my-service',
    pageScope: ['user'], // 仅在个人中心显示
    content: {
        style: 1,
        title: '我的服务',
        data: [
            {
                image: '',
                name: '导航名称',
                link: {}
            }
        ]
    },
    styles: {}
})
