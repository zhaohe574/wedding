/**
 * 19 候补申请 / 20 我的候补。
 *
 * 现状：schedule.joinWaitlist / myWaitlist / cancelWaitlist 三个方法已有 ✔，
 * 但返回结构与设计稿差三件事（全部是 P0，缺了就只能造假数据）：
 *   G-19 排队位次（「当前第 3 位 · 前面 2 人」）
 *   G-20 释放后的锁定时效（「请在 02:14:36 内锁定」）与锁定入口
 *   G-21 备选日期（「最多 2 个 · 可提高成功率」）—— 现 joinWaitlist 只接单个 date
 */

import type { WaitlistNotifyStatus } from '@shared/contracts'
import type { DateString, TimeText } from './common'
import type { StaffCardVO } from './catalog'

/** 20 屏三段进度：已排队 → 已释放 → 待锁定 */
export type WaitlistStageKey = 'queued' | 'released' | 'locked' | 'expired'

/** 19 屏提交入参（在现有 joinWaitlist 基础上补两个字段） */
export interface WaitlistJoinPayload {
    staff_id: number
    date: DateString
    /** ✘ G-21 备选日期，最多 2 个 */
    alt_dates?: DateString[]
    /** ✘ G-21 备注（户外仪式 / 语言偏好） */
    remark?: string
    mobile?: string
    package_id?: number
}

/** 一条候补记录（20 屏卡片） */
export interface WaitlistVO {
    id: number
    staff_id?: number
    staff_name?: string
    staff_avatar?: string
    /** ◐ 现网兼容两种字段名 */
    schedule_date?: DateString
    date?: DateString
    week_text?: string
    city_name?: string
    category_name?: string
    create_time: TimeText
    notify_status: WaitlistNotifyStatus | number
    notify_status_desc?: string
    /** 映射出的展示阶段 */
    stage?: WaitlistStageKey
    /** ✘ G-19 位次 */
    position?: number
    /** ✘ G-19 前面人数 */
    ahead_count?: number
    /** ✘ G-21 备选日期 */
    alt_dates?: DateString[]
    /** ✘ G-20 释放后的锁定倒计时（秒） */
    lock_remain_seconds?: number
    /** ✘ G-20 锁定截止时间戳 */
    lock_expire_time?: number
    /** ✘ G-20 是否可立即锁定（按钮置灰依据） */
    can_lock?: boolean
    /** ✘ G-19 失效原因（「超时未锁定，名额已顺延」） */
    expire_reason?: string
    released_at?: TimeText
}

/** 20 屏页面数据 */
export interface WaitlistScreenVO {
    /** 有释放的排在最前，直接驱动横幅 */
    released?: WaitlistVO
    active: WaitlistVO[]
    history: WaitlistVO[]
    rule_text?: string
}

/** 19 屏页面数据 */
export interface WaitlistApplyScreenVO {
    staff: Pick<StaffCardVO, 'id' | 'name' | 'avatar' | 'price'>
    date: DateString
    city_name?: string
    category_name?: string
    /** 档期已满的确认信息（checkAvailable.is_available === false） */
    full: boolean
    /** ✘ G-19 提交前预估位次 */
    estimated_position?: number
    /** 同档期还有几位可约（换个思路的出口） */
    alternative_staff_count?: number
    mobile?: string
    /** 候补规则正文（可走 index.policy） */
    rule_text?: string
}
