export default () => ({
    title: '客服设置',
    name: 'customer-service',
    pageScope: ['service'], // 仅在客服设置页显示
    content: {
        enabled: 1,
        title: '联系专属顾问',
        subtitle: '所有咨询统一进入企业微信顾问体系，由顾问持续跟进。',
        qrTitle: '统一客服二维码',
        time: '',
        mobile: '',
        wechat: '',
        contactLink: '',
        buttonText: '联系专属顾问',
        phoneText: '一键拨打客服',
        qrcode: '',
        remark: '无可分配顾问时将展示统一客服资料',
        tips: '订单状态变化仍通过站内消息中心与订阅通知触达'
    },
    styles: {
        themeColor: '#E56B6F',
        pageBgColor: '#F7F8FC',
        cardRadius: 20,
        qrSize: 120,
        cardGap: 16
    },
})
