import { HOME_FLOW_DEFAULTS, HOME_WIDGET_TITLES } from '@shared/contracts/decorate'

// 服务承诺步骤
export interface HomeFlowStep {
    /** 左侧时间标记，如 T-30 / T-01 / T-0 */
    tag: string
    /** 步骤标题 */
    title: string
    /** 步骤描述 */
    desc: string
    /** 是否显示：'1'=显示 '0'=隐藏 */
    is_show: '0' | '1'
}

export default () => {
    /**
     * 必须深拷贝：buildWidgetOptions 对 content 只做浅展开，
     * 直接引用 HOME_FLOW_DEFAULTS 会让所有实例共享同一个 data 数组，编辑一次就污染契约常量。
     */
    const defaults = JSON.parse(JSON.stringify(HOME_FLOW_DEFAULTS)) as {
        enabled: 0 | 1
        title: string
        data: HomeFlowStep[]
    }

    return {
        title: HOME_WIDGET_TITLES['home-flow'],
        name: 'home-flow',
        pageScope: ['home'], // 仅在首页显示
        content: defaults,
        styles: {}
    }
}
