import { HOME_BRAND_DEFAULTS, HOME_WIDGET_TITLES, type HomeBrandContent } from '@shared/contracts/decorate'

/**
 * 数据条（home-brand）。
 *
 * 真机只消费 `content.enabled` + `stats`（3 格「数值 / 说明」）。
 * 旧的 greeting / team_name / subtitle / cta_text / cta_link 已从数据形状中删除。
 */
export default () => {
    /** 深拷贝：避免与契约常量共享 stats 数组 */
    const defaults = JSON.parse(JSON.stringify(HOME_BRAND_DEFAULTS)) as HomeBrandContent

    return {
        title: HOME_WIDGET_TITLES['home-brand'],
        name: 'home-brand',
        pageScope: ['home'], // 仅在首页显示
        content: defaults,
        styles: {}
    }
}
