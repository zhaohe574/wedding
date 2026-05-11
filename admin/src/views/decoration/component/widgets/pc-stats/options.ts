import { createPcStyles } from '../pc-shared'

export interface PcStatsItem {
    value: string
    label: string
    description: string
}

export interface PcStatsContent {
    enabled: 0 | 1
    eyebrow: string
    title: string
    subtitle: string
    data: PcStatsItem[]
}

export default () => ({
    title: '数据背书',
    name: 'pc-stats',
    content: {
        enabled: 1,
        eyebrow: 'TRACK RECORD',
        title: '长期服务沉淀',
        subtitle: '用持续稳定的交付能力支撑每一次公开亮相。',
        data: [
            { value: '1000+', label: '活动服务经验', description: '覆盖仪式、发布与商务场景' },
            { value: '98%', label: '客户好评率', description: '来自长期合作与现场反馈' },
            { value: '30+', label: '覆盖城市', description: '支持跨区域活动执行' }
        ]
    } as PcStatsContent,
    styles: createPcStyles(2210, 320)
})
