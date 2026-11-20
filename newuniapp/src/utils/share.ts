import { useAppStore } from '@/stores/app'

/**
 * 分享卡片统一出口。
 *
 * 微信分享卡片的标题 / 封面是「品牌名 + 图标」最容易被漏掉的展示位：
 * 页面里各自拼「品牌名 + 婚礼主持」会出现「后台改了名称，分享出去还是旧名」。
 * 所有 `onShareAppMessage` 一律走这里，标题缺省值由品牌配置补。
 */

export interface SharePayload {
    title: string
    path: string
    imageUrl?: string
}

export interface ShareOptions {
    /** 内容自身的标题（主持人名 / 动态标题）；为空则用「品牌名 + suffix」 */
    title?: string
    /** 分享落地页（含 query） */
    path: string
    /** 内容封面；为空则用后台配置的品牌图标 */
    imageUrl?: string
    /** 标题后缀，如「婚礼主持」。只在 title 为空时参与拼接 */
    suffix?: string
}

export function buildShare(options: ShareOptions): SharePayload {
    const appStore = useAppStore()
    const brand = appStore.brand

    const own = (options.title || '').trim()
    const title = own || `${brand.name}${options.suffix || ''}`

    const payload: SharePayload = { title, path: options.path }

    // imageUrl 传 undefined 时微信会用页面截图，比传空串更稳
    const image = (options.imageUrl || '').trim() || brand.logo
    if (image) payload.imageUrl = image

    return payload
}
