import { createPcStyles } from '../pc-shared'

export interface PcGalleryItem {
    image: string
    title: string
    description: string
}

export interface PcGalleryContent {
    enabled: 0 | 1
    eyebrow: string
    title: string
    subtitle: string
    data: PcGalleryItem[]
}

export default () => ({
    title: '展示图集',
    name: 'pc-gallery',
    content: {
        enabled: 1,
        eyebrow: 'SHOWCASE',
        title: '真实场景中的专业呈现',
        subtitle: '用于展示企业活动、仪式现场、团队环境与服务质感。',
        data: [
            {
                image: '/resource/image/adminapi/default/banner003.png',
                title: '企业发布现场',
                description: '稳定推进流程，强化品牌表达。'
            },
            {
                image: '/resource/image/adminapi/default/banner001.png',
                title: '庆典仪式现场',
                description: '兼顾秩序、情绪与仪式感。'
            },
            {
                image: '/resource/image/adminapi/default/banner002.png',
                title: '团队服务场景',
                description: '让细节在现场自然发生。'
            }
        ]
    } as PcGalleryContent,
    styles: createPcStyles(1610, 600)
})
