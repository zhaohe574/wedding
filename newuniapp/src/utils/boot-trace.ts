/**
 * 冷启动耗时打点（仅开发环境输出）。
 *
 * 用途：定位「启动后白屏 N 秒」——从小程序进程启动到某个阶段实际花了多少毫秒。
 * 控制台按 `[boot` 过滤即可，形如：
 *   [boot +12ms] app:onLaunch
 *   [boot +96ms] splash:config resolved
 *   [boot +104ms] splash:leave → /pages/index/index
 *   [boot +118ms] index:onLoad
 *   [boot +131ms] index:onReady
 *   [boot +210ms] index:home settled
 *
 * 生产环境完全不输出，也不会打包进 release 日志（条件在调用时判断）。
 */
const ORIGIN = Date.now()
const ENABLED = import.meta.env.DEV

export function bootTrace(stage: string, extra?: unknown): void {
    if (!ENABLED) return
    const ms = Date.now() - ORIGIN
    if (extra === undefined) {
        console.log(`[boot +${ms}ms] ${stage}`)
        return
    }
    console.log(`[boot +${ms}ms] ${stage}`, extra)
}
