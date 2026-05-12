import { createPcStyles } from '../pc-shared'

export interface PcContactContent {
    enabled: 0 | 1
    eyebrow: string
    title: string
    subtitle: string
    phone: string
    service_time: string
    address: string
    qrcode: string
    remark: string
}

export default () => ({
    title: '联系信息',
    name: 'pc-contact',
    content: {
        enabled: 1,
        eyebrow: 'CONTACT',
        title: '把重要时刻交给更稳的现场团队',
        subtitle: '欢迎通过电话、二维码或地址信息进一步了解团队。',
        phone: '1888888888',
        service_time: '周一至周日 09:30 - 19:00',
        address: '请在后台装修中填写企业地址',
        qrcode: '/resource/image/adminapi/default/kefu01.png',
        remark: '欢迎通过上述方式进一步了解团队服务与合作信息。'
    } as PcContactContent,
    styles: createPcStyles(3350, 560)
})
