import { PC_MINIPROGRAM_DEFAULTS, PC_WIDGET_META, type PcMiniProgramContent } from '@shared/contracts/pc-decorate'

/** 小程序引导（pc-miniprogram）：默认值与 shared/contracts/pc-decorate.ts 逐字对齐 */
export default () => ({
    title: PC_WIDGET_META['pc-miniprogram'].title,
    name: 'pc-miniprogram',
    content: {
        ...PC_MINIPROGRAM_DEFAULTS,
        highlights: PC_MINIPROGRAM_DEFAULTS.highlights.map((item) => ({ ...item }))
    } as PcMiniProgramContent,
    styles: {}
})
