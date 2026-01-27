export default () => ({
    title: '婚礼倒计时',
    name: 'wedding-countdown',
    pageScope: ['home', 'user'], // 首页、个人中心可用
    content: {
        enabled: 1,
        title: '距离我们的婚礼还有',
        style: 1, // 1=卡片式, 2=简约式, 3=浪漫式
        bg_color: '#FFF5F5', // 背景颜色
        text_color: '#333333', // 文字颜色
        number_color: '#FF6B9D', // 数字颜色
        show_days: 1, // 是否显示天数
        show_hours: 1, // 是否显示小时
        show_minutes: 1, // 是否显示分钟
        show_seconds: 1 // 是否显示秒数
    },
    styles: {}
})
