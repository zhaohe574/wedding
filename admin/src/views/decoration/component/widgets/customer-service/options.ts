export default () => ({
    title: '客服设置',
    name: 'customer-service',
    pageScope: ['service'], // 仅在客服设置页显示
    content: {
        enabled: 1,
        title: '联系专属顾问',
        subtitle: '',
        time: '',
        buttonText: '联系专属顾问',
        tips: ''
    },
    styles: {
        themeColor: '#E56B6F',
        pageBgColor: '#F7F8FC',
        cardRadius: 20,
        cardGap: 16
    },
})
