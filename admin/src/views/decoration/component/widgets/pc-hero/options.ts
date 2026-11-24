import { PC_HERO_DEFAULTS, PC_WIDGET_META, type PcHeroContent } from '@shared/contracts/pc-decorate'

/** 品牌首屏（pc-hero）：默认值与 shared/contracts/pc-decorate.ts 逐字对齐 */
export default () => ({
    title: PC_WIDGET_META['pc-hero'].title,
    name: 'pc-hero',
    content: { ...PC_HERO_DEFAULTS } as PcHeroContent,
    styles: {}
})
