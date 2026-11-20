import {
    HOME_POPUP_DEFAULTS,
    HOME_WIDGET_TITLES,
    type HomePopupAdContent
} from '@shared/contracts/decorate'

/** 兼容旧引用：类型以契约为准 */
export type {
    HomePopupAdContent,
    HomePopupType as HomePopupAdType,
    HomePopupFrequency as HomePopupAdFrequency
} from '@shared/contracts/decorate'

/**
 * 首页弹窗广告。
 *
 * 默认值直接复用 `HOME_POPUP_DEFAULTS`，不再维护第三份字面量
 * （之前 options / DecoratePageLogic / IndexLogic 各写一份，link 默认值已经漂移）。
 */
export default () => {
    /** 深拷贝：link 是对象，不能与契约常量共享引用 */
    const defaults = JSON.parse(JSON.stringify(HOME_POPUP_DEFAULTS)) as HomePopupAdContent

    return {
        title: HOME_WIDGET_TITLES['home-popup-ad'],
        name: 'home-popup-ad',
        pageScope: ['home'],
        content: defaults,
        styles: {}
    }
}
