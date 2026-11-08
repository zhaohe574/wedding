/**
 * 页面消费层通用类型。
 *
 * 分层约定：
 *   shared/contracts/**   事实源（后端常量 / 已确认 DTO），只读
 *   src/api/types/**      页面契约（本目录）：把「设计稿要展示的字段」显式声明出来，
 *                         后端还没给的字段一律 optional，并注明缺口编号（G/F 见
 *                         docs/redesign/v2/01-接口契约.md），禁止写成必选去骗编译器。
 *
 * 这里不做业务枚举的重复定义：状态机枚举统一从 @shared/contracts 引。
 */

/** 金额：后端大量返回 number | string 混用，页面统一过 formatMoney */
export type Money = number | string

/** 服务端约定的日期字符串 YYYY-MM-DD（不带时区） */
export type DateString = string

/** 服务端返回的展示型时间文案，如 "11.02 15:06" / "昨天 20:14" */
export type TimeText = string

/** 服务端时间戳（秒） */
export type ServerSeconds = number

/** 后端 0/1 布尔，含历史字符串形态 */
export type BitFlag = 0 | 1 | number | string

/** 统一分页外壳（v2 风格）。名字避开 shared 的 Paged，防止两处同时 export * 冲突 */
export interface ApiPaged<T> {
    list: T[]
    total: number
    page: number
    page_size: number
    has_more: boolean
}

/** 列表页通用查询参数（三种分页风格并列，由 normalizePage 归一） */
export interface PageQuery {
    page?: number
    page_no?: number
    page_size?: number
    limit?: number
}

/** 页面数据相位：只用这五种，禁止页面各自发明 loading 语义 */
export type LoadPhase = 'idle' | 'loading' | 'ready' | 'empty' | 'error'

/** 单实体查询参数 */
export interface IdQuery {
    id: number
}
