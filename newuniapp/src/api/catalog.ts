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

/** 全站月历 @endpoint schedule.monthCalendar */
export function getMonthCalendar(params: { month?: string; staff_id?: number }) {
    return request.get<any>('/schedule/monthCalendar', params, { silent: true })
}

/** 吉日 @endpoint schedule.luckyDays */
export function getLuckyDays(params?: { month?: string }) {
    return request.get<any>('/schedule/luckyDays', params ?? {}, { silent: true })
}

/** 加入候补 @endpoint schedule.joinWaitlist */
export function joinWaitlist(params: { staff_id: number; date: string; [k: string]: any }) {
    return request.post<null>('/schedule/joinWaitlist', params, { auth: true })
}

/** @endpoint schedule.myWaitlist */
export function getMyWaitlist() {
    return request.get<any[]>('/schedule/myWaitlist', {}, { auth: true, silent: true })
}

/** @endpoint schedule.cancelWaitlist */
export function cancelWaitlist(params: { id: number }) {
    return request.post<null>('/schedule/cancelWaitlist', params, { auth: true })
}
