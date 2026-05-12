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
        title: '不是把流程走完，而是让每一段关系被看见',
        subtitle: '我们为婚礼仪式、品牌庆典、企业活动与私享宴会提供主持表达和现场流程统筹。',
        description: '从前期沟通、仪式脚本、音乐节点到现场控场，团队以成熟流程协调新人、家庭、场地方和执行团队，让现场节奏自然、情绪饱满、表达得体。',
        image: '/resource/image/adminapi/default/banner002.png',
        points: ['需求沟通', '仪式脚本', '现场控场']
    } as PcAboutContent,
    styles: createPcStyles(820, 700)
})
