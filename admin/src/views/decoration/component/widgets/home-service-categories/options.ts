import {
    HOME_SERVICE_CATEGORIES_DEFAULTS,
    HOME_WIDGET_TITLES,
    type HomeServiceCategoriesContent
} from '@shared/contracts/decorate'

/** 兼容旧引用：入口类型以契约为准 */
export type { HomeServiceCategoryItem } from '@shared/contracts/decorate'

/**
 * 四宫格入口（home-service-categories）。
 *
 * 真机只消费每个入口的 `title` / `image` / `link` / `is_show`。
 * 旧版的 subtitle / size / text_position / text_align 已删除（真机从未渲染）。
 * 默认四条与小程序 QUICK_FALLBACK 同文案同路由，避免新站默认值指向已下线页面。
 */
export default () => {
    /** 深拷贝：避免与契约常量共享 data 数组 */
    const defaults = JSON.parse(
        JSON.stringify(HOME_SERVICE_CATEGORIES_DEFAULTS)
    ) as HomeServiceCategoriesContent

    return {
        title: HOME_WIDGET_TITLES['home-service-categories'],
        name: 'home-service-categories',
        pageScope: ['home'],
        content: defaults,
        styles: {}
    }
}
