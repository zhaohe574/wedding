import {
    HOME_RECOMMEND_DEFAULTS,
    HOME_WIDGET_TITLES,
    type HomeRecommendContent
} from '@shared/contracts/decorate'

/**
 * 本期推荐（home-recommend）。
 *
 * 内容来自接口（推荐服务人员），装修只负责「显不显示」：
 * 关掉或接口无数据时真机整块不渲染（含区块标题）。
 */
export default () => {
    const defaults = { ...HOME_RECOMMEND_DEFAULTS } as HomeRecommendContent

    return {
        title: HOME_WIDGET_TITLES['home-recommend'],
        name: 'home-recommend',
        pageScope: ['home'],
        content: defaults,
        styles: {}
    }
}
