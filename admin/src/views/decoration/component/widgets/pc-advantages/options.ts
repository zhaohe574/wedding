import { createPcStyles } from '../pc-shared'

export interface PcAdvantageItem {
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
        title: '把控节奏、表达与现场秩序',
        subtitle: '适配企业展示、发布会、庆典仪式、商务活动等不同场景。',
        data: [
            { title: '表达策略', description: '先明确活动目标，再拆解台词、流程与现场节奏。' },
            { title: '流程统筹', description: '对接人员、环节、物料与时间点，减少现场不确定性。' },
            { title: '审美统一', description: '文案、画面、音乐与仪式感保持同一品牌语气。' }
        ]
    } as PcAdvantagesContent,
    styles: createPcStyles(1180, 430)
})
