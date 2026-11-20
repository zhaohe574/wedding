import {
    QUICK_ENTRY_DEFAULTS,
    type QuickEntryContent,
    type QuickEntryItem
} from '@shared/contracts/decorate'

export type { QuickEntryContent, QuickEntryItem }

export type QuickEntryStyles = Record<string, never>

export interface QuickEntryOptions {
    title: string
    name: string
    pageScope: string[]
    content: QuickEntryContent
    styles: QuickEntryStyles
}

/**
 * 个人中心「我的服务」列表。
 *
 * ⚠ 默认值必须直接复用契约，不要在这里另写一份；旧模板的网格/横滑/副标题配置已经删掉，
 * 真机只支持列表一种形态。
 */
export default (): QuickEntryOptions => ({
    title: '快捷入口',
    name: 'quick-entry',
    pageScope: ['user'],
    content: {
        ...QUICK_ENTRY_DEFAULTS,
        data: QUICK_ENTRY_DEFAULTS.data.map((item) => ({ ...item, link: { ...item.link } }))
    },
    styles: {}
})
