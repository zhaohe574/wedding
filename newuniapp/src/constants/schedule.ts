/**
 * 查档期规则的兜底常量。
 *
 * 真实口径来自后台 `la_schedule_rule`（staff_id = 0 的全局规则）：
 * 页面启动时请求 `GET /schedule/rule`，用返回的 `today` / `advance_days` /
 * `min_selectable_date` / `max_selectable_date` 决定可选范围。
 *
 * 这里的值**只在接口失败或字段非法时**使用（例如弱网、后端未部署新接口），
 * 目的只是让页面仍可用，不代表业务配置。后台改配置不需要改前端。
 */

/** 兜底提前预订天数（与后端 ScheduleRule::DEFAULT_ADVANCE_DAYS 一致） */
export const SCHEDULE_LEAD_DAYS_FALLBACK = 3

/** 兜底最远可查年数（与后端 ScheduleLogic::MAX_SELECTABLE_YEARS 一致） */
export const SCHEDULE_MAX_YEARS_FALLBACK = 10
