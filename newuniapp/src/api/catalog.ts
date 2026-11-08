import request from '@/request'
import type { StaffCard, StaffDetailResponse, PackageItem, AddonItem, AvailabilityInfo } from '@shared/contracts'

/**
 * 目录域：服务分类 / 地区 / 人员 / 档期。
 * 这些接口大多免登录，且数据变化慢，适合本地缓存。
 */

export interface ServiceCategory {
    id: number
    name: string
    image?: string
    children?: ServiceCategory[]
}

export interface RegionNode {
    id: number
    name: string
    children?: RegionNode[]
}

export interface StaffListParams {
    page?: number
    page_size?: number
    page_no?: number
    category_id?: number
    province_code?: string
    city_code?: string
    district_code?: string
    tag_ids?: string
    keyword?: string
    sort?: string
    service_date?: string
    [k: string]: any
}

/** @endpoint service.categoryTree */
export function getCategoryTree() {
    return request.get<ServiceCategory[]>('/service/categoryTree', {}, { silent: true })
}

/** @endpoint service.tags */
export function getServiceTags(data?: { grouped?: number }) {
    return request.get<any>('/service/tags', data ?? {}, { silent: true })
}

/** @endpoint region.serviceTree */
export function getRegionTree() {
    return request.get<RegionNode[]>('/region/serviceTree', {}, { silent: true })
}

/** 人员列表（分页风格 A：page_no + dataLists） @endpoint staff.lists */
export function getStaffList(params: StaffListParams) {
    return request.get<any>('/staff/lists', params, { silent: true })
}

/** 推荐人员 @endpoint staff.recommend */
export function getStaffRecommend(params?: { limit?: number }) {
    return request.get<StaffCard[]>('/staff/recommend', params ?? {}, { silent: true })
}

/** 人员详情聚合（v2，1 次请求替代 6 次） @endpoint v2.staff */
export function getStaffDetailV2(id: number, params?: { date?: string; city_code?: string; province_code?: string; district_code?: string }) {
    return request.get<StaffDetailResponse>('/v2/staff', { id, ...(params || {}) }, { silent: true })
}

/** 旧版人员详情（v2 未上线时降级） @endpoint staff.detail */
export function getStaffDetail(params: { id: number; [k: string]: any }) {
    return request.get<any>('/staff/detail', params, { silent: true })
}

/** @endpoint staff.packages */
export function getStaffPackages(params: { staff_id: number }) {
    return request.get<PackageItem[]>('/staff/packages', params, { silent: true })
}

/** @endpoint staff.addons */
export function getStaffAddons(params: { staff_id: number }) {
    return request.get<AddonItem[]>('/staff/addons', params, { silent: true })
}

/** @endpoint staff.works */
export function getStaffWorks(params: { staff_id: number; page?: number; page_size?: number }) {
    return request.get<any>('/staff/works', params, { silent: true })
}

/** @endpoint staff.workLists */
export function getWorkList(params: { page?: number; page_size?: number; staff_id?: number }) {
    return request.get<any>('/staff/workLists', params, { silent: true })
}

/** @endpoint staff.workDetail */
export function getWorkDetail(params: { id: number }) {
    return request.get<any>('/staff/workDetail', params, { silent: true })
}

/** 预约角色候选（婚礼管家 / 督导） @endpoint staff.bookingRoleCandidates */
export function getBookingRoleCandidates(params: {
    staff_id: number
    role_key: string
    date?: string
    package_id?: number
    [k: string]: any
}) {
    return request.get<any>('/staff/bookingRoleCandidates', params, { silent: true })
}

/** 收藏/取消收藏（后端读的是 id，不是 staff_id） @endpoint staff.toggleFavorite */
export function toggleStaffFavorite(params: { id: number }) {
    return request.post<{ is_favorited?: boolean; is_favorite?: number }>('/staff/toggleFavorite', params, { auth: true })
}

/** 我的收藏（无分页，直接返回数组） @endpoint staff.myFavorites */
export function getMyFavorites() {
    return request.get<any[]>('/staff/myFavorites', {}, { auth: true, silent: true })
}

/** 档期可用性 @endpoint schedule.checkAvailable */
export function checkAvailable(params: { staff_id: number; date: string; [k: string]: any }) {
    return request.get<AvailabilityInfo>('/schedule/checkAvailable', params, { silent: true })
}

/**
 * 全站月历（含吉日标记）。
 *
 * 后端 `ScheduleLogic::getMonthCalendar` 按整月逐日返回：
 *   date / week / is_today / is_past / is_lucky_day / is_holiday / holiday_name /
 *   lunar_date / lucky_events / unlucky_events / congestion_level / remark
 * 也就是说 **吉日标记已经具备**，03 屏日期条的香槟色圆点用本方法即可；
 * 仍缺的是「每日可约人数 / 剩余名额」（G-02 只剩这一项）。
 * @endpoint schedule.monthCalendar
 */
export function getMonthCalendar(params: { year?: number; month?: number; staff_id?: number }) {
    return request.get<any>('/schedule/monthCalendar', params, { silent: true })
}

/**
 * 吉日列表。
 * 支持区间（`start_date` + `end_date`，可选 `marriage_only` 只看「嫁娶」），
 * 首页「11 月 – 12 月吉日」直接传区间；不传则按月（`year` + `month`）。
 * @endpoint schedule.luckyDays
 */
export function getLuckyDays(params?: {
    year?: number
    month?: number
    start_date?: string
    end_date?: string
    /** 1 = 只返回宜嫁娶的吉日 */
    marriage_only?: number
}) {
    return request.get<any>('/schedule/luckyDays', params ?? {}, { silent: true })
}

/**
 * 加入候补。
 *
 * ⚠ C-10 修正：后端 `ScheduleLogic::joinWaitlist` **必填 `package_id`**（≤0 直接返回「请选择套餐」），
 * 旧签名只有 `{ staff_id, date }`，调用必然失败。同时：
 *   · 不支持备选日期（`alt_dates`）→ 暂不封装，等后端加；
 *   · 支持 `remark`；
 *   · 返回体不是 null，而是 `{ success, message, waitlist_id, waitlist_ids, batch_no }`。
 *
 * 幂等：写接口必须带 `Idempotency-Key`，否则连点会排两次队（后端不去重）。
 * @endpoint schedule.joinWaitlist
 */
export function joinWaitlist(params: {
    staff_id: number
    date: string
    /** 必填：候补要绑定套餐，未传后端返回「请选择套餐」 */
    package_id: number
    /** 备注（户外仪式 / 语言偏好等），后端已接收 */
    remark?: string
    mobile?: string
}) {
    return request.post<{
        success?: boolean
        message?: string
        waitlist_id?: number | null
        waitlist_ids?: number[]
        batch_no?: string
    }>('/schedule/joinWaitlist', params, { auth: true, idempotent: true, dedupe: false, loading: true })
}

/**
 * 我的候补。
 *
 * 后端逐条返回：`notify_status`(0 未通知/1 已通知/2 已下单/3 已过期)、`expire_time`（绝对时间戳）、
 * `expire_time_text`、`can_cancel`、**`can_book_now`**、**`book_block_reason`**、`status_hint`、
 * `schedule_date`、`staff{id,name,avatar,category_id}`、`package{id,name}`、`create_time`、`remark`。
 *
 * 因此 20 屏的倒计时（用 `expire_time` 本地算，离线可走）与「立即预约锁定」（用 `can_book_now`）
 * **不需要新接口**；仍缺的只有排队位次（G-03）。
 * @endpoint schedule.myWaitlist
 */
export function getMyWaitlist(params?: { status?: number }) {
    return request.get<any[]>('/schedule/myWaitlist', params ?? {}, { auth: true, silent: true })
}

/** @endpoint schedule.cancelWaitlist */
export function cancelWaitlist(params: { id: number }) {
    return request.post<null>('/schedule/cancelWaitlist', params, { auth: true })
}

/**
 * 某服务人员的月度档期（M-06 接线）。
 *
 * 返回逐日：`date` / `schedules` / `schedule` / `is_available` / `slot_availability` /
 * `calendar` / `is_lucky_day` / `is_holiday` / `lunar_date` / `congestion_level` …
 * 05 屏「档期小日历」与 03 屏「指定主持人的可约日」都用它；跨人员批量可用性仍需 G-02。
 * @endpoint schedule.staffSchedule
 */
export function getStaffSchedule(params: { staff_id: number; year?: number; month?: number }) {
    return request.get<any>('/schedule/staffSchedule', params, { silent: true })
}

/**
 * 锁定档期（08 确认页进入即调用）。
 *
 * 背景：`order/create` **不占位、不锁档**（只做 ensureScheduleAvailable 校验），
 * 锁档发生在首笔实收之后。所以「下单→支付」之间存在档期被抢的窗口，
 * 前端用这个接口把窗口压到最小：进入确认页先锁 15 分钟，支付成功后由后端转正，
 * 退出页面未支付则主动 releaseLock。
 *
 * 约束：`lock_duration` 后端校验区间 60–3600 秒，默认 900。
 * @endpoint schedule.lockSchedule
 */
export function lockSchedule(params: { staff_id: number; date: string; lock_duration?: number }) {
    return request.post<null>(
        '/schedule/lockSchedule',
        { ...params, lock_duration: params.lock_duration ?? 900 },
        { auth: true, silent: true }
    )
}

/**
 * 释放自己持有的档期锁（离开确认页且未支付时调用）。
 *
 * 不释放的后果：这 15 分钟里该档期对其他人都是不可约状态，等于替用户白占一个档期。
 * @endpoint schedule.releaseLock
 */
export function releaseLock(params: { staff_id: number; date: string }) {
    return request.post<null>('/schedule/releaseLock', params, { auth: true, silent: true })
}
