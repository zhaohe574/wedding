import { createPcStyles } from '../pc-shared'

export interface PcAdvantageItem {
    kicker: string
    title: string
    description: string
}

export interface PcAdvantagesContent {
    enabled: 0 | 1
    eyebrow: string
    title: string
    subtitle: string
    data: PcAdvantageItem[]
}

export default () => ({
    title: '核心优势',
    name: 'pc-advantages',
    content: {
        enabled: 1,
        eyebrow: 'CAPABILITIES',
        title: '从表达、节奏、秩序到画面统一落地',
        subtitle: '适配婚礼仪式、答谢晚宴、企业庆典、品牌发布等不同场景。',
        data: [
            { kicker: 'Script', title: '仪式文本定制', description: '围绕人物关系与活动目标，打磨有分寸感的主持文本。' },
            { kicker: 'Rhythm', title: '全流程节奏管理', description: '梳理环节、人员、物料与时间点，降低现场不确定性。' },
            { kicker: 'Aesthetic', title: '现场审美协同', description: '让文案、音乐、影像与仪式氛围保持统一的品牌语气。' }
        ]
    } as PcAdvantagesContent,
    styles: createPcStyles(1520, 640)
})
