export default () => ({
    title: '客服设置',
    name: 'customer-service',
    pageScope: ['service'], // 仅在客服设置页显示
    content: {
        enabled: 1,
        title: '婚礼管家客服',
        subtitle: '扫码添加专属顾问，一对一解答婚礼筹备问题',
        qrTitle: '微信扫码咨询',
        time: '',
        mobile: '',
        phoneText: '一键拨打客服',
        qrcode: '',
        remark: '添加后请备注婚期与城市，方便顾问快速响应',
        tips: '工作时间内优先回复，节假日可能存在排队'
    },
    styles: {
        themeColor: '#E56B6F',
        pageBgColor: '#F7F8FC',
        cardRadius: 20,
        qrSize: 120,
        cardGap: 16
    },
})
