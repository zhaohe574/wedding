import {
    HOME_LUCKY_DAYS_DEFAULTS,
    HOME_WIDGET_TITLES,
    type HomeLuckyDaysContent
} from '@shared/contracts/decorate'

/**
 * 近期吉日（home-lucky-days）。
 *
 * 内容来自档期吉日接口，装修只负责「显不显示」：
 * 关掉或接口无数据时真机整块不渲染（含区块标题）。
 */
export default () => {
    const defaults = { ...HOME_LUCKY_DAYS_DEFAULTS } as HomeLuckyDaysContent

    return {
        title: HOME_WIDGET_TITLES['home-lucky-days'],
        name: 'home-lucky-days',
        pageScope: ['home'],
        content: defaults,
        styles: {}
    }
}
