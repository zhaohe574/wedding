/**
 * ECharts 按需注册（异步）。
 *
 * 改造背景：原实现把 echarts 全量注册放在 `src/install/plugins/echart.ts`，
 * 由 `install/index.ts` 的 `import.meta.glob(..., { eager: true })` 静态加载，
 * 导致 echarts(≈800KB) + zrender(≈210KB) 被并入后台首屏 chunk。
 *
 * 现在改为导出 `ensureEcharts()`：只有真正需要图表的页面才触发动态 import，
 * 其余页面首屏不再加载 echarts。所有模块只注册一次（幂等）。
 */
import type * as EChartsCore from 'echarts/core'

type EChartsCoreModule = typeof EChartsCore

/** 已注册的 echarts core 模块缓存 */
let coreModule: EChartsCoreModule | null = null

/** 注册中的 Promise，避免并发调用重复注册 */
let loadingPromise: Promise<EChartsCoreModule> | null = null

/**
 * 确保 echarts 相关模块已注册，并返回 `echarts/core` 模块对象。
 * 调用方可直接使用返回值的 `init` / `graphic` 等能力。
 */
export function ensureEcharts(): Promise<EChartsCoreModule> {
    if (coreModule) {
        return Promise.resolve(coreModule)
    }
    if (loadingPromise) {
        return loadingPromise
    }

    loadingPromise = (async () => {
        const [core, charts, components, features, renderers] = await Promise.all([
            import('echarts/core'),
            import('echarts/charts'),
            import('echarts/components'),
            import('echarts/features'),
            import('echarts/renderers')
        ])

        core.use([
            // 组件
            components.LegendComponent,
            components.TitleComponent,
            components.TooltipComponent,
            components.GridComponent,
            components.PolarComponent,
            components.AriaComponent,
            components.ParallelComponent,
            components.RadarComponent,
            components.ToolboxComponent,
            components.DataZoomComponent,
            components.VisualMapComponent,
            components.TimelineComponent,
            components.CalendarComponent,
            components.GraphicComponent,
            // 图表
            charts.BarChart,
            charts.LineChart,
            charts.PieChart,
            charts.MapChart,
            charts.RadarChart,
            charts.PictorialBarChart,
            charts.ScatterChart,
            // 特性与渲染器
            features.LabelLayout,
            features.UniversalTransition,
            renderers.CanvasRenderer
        ])

        coreModule = core
        return core
    })()

    return loadingPromise
}

export default { ensureEcharts }
