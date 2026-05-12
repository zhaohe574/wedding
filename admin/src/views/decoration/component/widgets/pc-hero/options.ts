import { createPcStyles } from '../pc-shared'

export interface PcHeroContent {
    enabled: 0 | 1
    eyebrow: string
    title: string
    subtitle: string
    description: string
    image: string
    image_caption: string
    badges: string[]
}

export default () => ({
    title: '企业首屏',
    name: 'pc-hero',
    content: {
        enabled: 1,
        eyebrow: 'GLINSHE CEREMONY HOUSE',
        title: '让婚礼现场成为值得回看的仪式',
        subtitle: '以高级审美、稳健控场和细致统筹，呈现婚礼仪式与重要活动现场。',
        description: 'PC 首页定位为企业展示窗口，集中呈现品牌气质、主持能力、仪式统筹、案例现场与联系信息。',
        image: '/resource/image/adminapi/default/banner003.png',
        image_caption: '婚礼主持 · 仪式统筹 · 活动呈现',
        badges: ['婚礼主持', '仪式统筹', '高端庆典']
    } as PcHeroContent,
    styles: createPcStyles(0, 820)
})
