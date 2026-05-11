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
        eyebrow: 'PROFESSIONAL EVENT HOSTING',
        title: '专业主持与企业活动表达服务',
        subtitle: '以稳健控场、清晰表达和高级审美，服务每一次重要亮相。',
        description: 'PC 首页定位为企业展示窗口，集中呈现团队能力、服务场景与联系方式。',
        image: '/resource/image/adminapi/default/banner003.png',
        image_caption: '企业活动 · 仪式表达 · 现场统筹',
        badges: ['企业活动', '品牌发布', '礼仪庆典']
    } as PcHeroContent,
    styles: createPcStyles(0, 620)
})
