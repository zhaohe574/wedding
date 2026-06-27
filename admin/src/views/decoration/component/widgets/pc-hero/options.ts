import { createPcStyles } from '../pc-shared'

export interface PcHeroContent {
    enabled: 0 | 1
    eyebrow: string
    brand_name: string
    brand_tagline: string
    title: string
    subtitle: string
    description: string
    image: string
    image_alt: string
    image_caption: string
    panel_eyebrow: string
    panel_description: string
    primary_action: string
    secondary_action: string
    badges: string[]
}

export default () => ({
    title: '企业首屏',
    name: 'pc-hero',
    content: {
        enabled: 1,
        eyebrow: 'GLINSHE CEREMONY HOUSE',
        brand_name: '格林社婚礼服务',
        brand_tagline: 'Ceremony House',
        title: '让婚礼现场成为值得回看的仪式',
        subtitle: '以高级审美、稳健控场和细致统筹，呈现婚礼仪式与重要活动现场。',
        description: 'PC 首页定位为企业展示窗口，集中呈现品牌气质、主持能力、仪式统筹、案例现场与联系信息。',
        image: '/resource/image/adminapi/default/banner003.png',
        image_alt: '格林社婚礼仪式现场',
        image_caption: '婚礼主持 · 仪式统筹 · 活动呈现',
        panel_eyebrow: 'Scene Direction',
        panel_description: '从沟通、脚本、音乐节点到现场控场，保持审美和情绪在同一个节奏里。',
        primary_action: '联系顾问',
        secondary_action: '查看案例',
        badges: ['婚礼主持', '仪式统筹', '高端庆典']
    } as PcHeroContent,
    styles: createPcStyles(0, 820)
})
