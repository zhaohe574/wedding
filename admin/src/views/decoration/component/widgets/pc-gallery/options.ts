import { PC_GALLERY_DEFAULTS, PC_WIDGET_META, type PcGalleryContent } from '@shared/contracts/pc-decorate'

/** 精选现场（pc-gallery）：默认值与 shared/contracts/pc-decorate.ts 逐字对齐 */
export default () => ({
    title: PC_WIDGET_META['pc-gallery'].title,
    name: 'pc-gallery',
    content: {
        ...PC_GALLERY_DEFAULTS,
        data: PC_GALLERY_DEFAULTS.data.map((item) => ({ ...item }))
    } as PcGalleryContent,
    styles: {}
})
