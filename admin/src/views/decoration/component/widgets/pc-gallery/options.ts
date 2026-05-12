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
        title: '真实现场中的仪式质感',
        subtitle: '用于展示婚礼仪式、庆典活动、团队服务和现场统筹的专业质感。',
        data: [
            {
                image: '/resource/image/adminapi/default/banner003.png',
                title: '婚礼仪式现场',
                description: '以稳定表达承接情绪，让重要瞬间自然发生。'
            },
            {
                image: '/resource/image/adminapi/default/banner001.png',
                title: '高端庆典现场',
                description: '兼顾秩序、节奏与仪式感，强化现场记忆点。'
            },
            {
                image: '/resource/image/adminapi/default/banner002.png',
                title: '团队统筹服务',
                description: '提前拆解每个细节，让执行在现场更从容。'
            }
        ]
    } as PcGalleryContent,
    styles: createPcStyles(2160, 760)
})
