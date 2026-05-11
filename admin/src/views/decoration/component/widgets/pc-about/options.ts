import { createPcStyles } from '../pc-shared'

export interface PcAboutContent {
    enabled: 0 | 1
    eyebrow: string
    title: string
    subtitle: string
    description: string
    image: string
    points: string[]
}

export default () => ({
    title: '品牌介绍',
    name: 'pc-about',
    content: {
        enabled: 1,
        eyebrow: 'ABOUT US',
        title: '以专业流程完成每一次重要表达',
        subtitle: '我们为企业发布、品牌活动、礼仪庆典与高端仪式提供主持与现场统筹支持。',
        description: '从前期沟通、流程梳理、主持文本到现场控场，团队用成熟方法帮助客户把重要场合表达得更清晰、更稳妥。',
        image: '/resource/image/adminapi/default/banner002.png',
        points: ['流程策划', '主持执行', '现场统筹']
    } as PcAboutContent,
    styles: createPcStyles(620, 560)
})
