export default () => ({
    title: '客服设置',
    name: 'customer-service',
    pageScope: ['service'], // 仅在客服设置页显示
    content: {
        enabled: 1,
        title: '联系专属顾问',
        subtitle: '',
        qrTitle: '统一客服二维码',
        time: '',
        mobile: '',
        wechat: '',
        contactLink: '',
        buttonText: '联系专属顾问',
        phoneText: '一键拨打客服',
        qrcode: '',
        remark: '',
        tips: ''
    },
    styles: {
        themeColor: '#E56B6F',
        pageBgColor: '#F7F8FC',
        cardRadius: 20,
        qrSize: 120,
        cardGap: 16
    },
})
