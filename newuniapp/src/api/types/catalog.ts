/**
 * 目录域：服务人员 / 套餐 / 附加项 / 作品 / 评价 / 档期。
 *
 * 覆盖屏幕：01 首页推荐、03 查档期、05 主持人详情、06 选择套餐、07 附加项、
 * 08/10/22 订单里的主持人摘要、14 我的收藏、19 候补申请、20 我的候补。
 *
 * 字段来源说明（严格区分）：
 *   ✔ 已确认   shared/contracts/dto.ts 或现有页面已在消费（staff-list / staff-detail / booking）
 *   ◐ 待确认   端点存在但返回结构无契约（openapi-core.yaml 未覆盖 /staff/*）
 *   ✘ 缺口     后端当前没有，标 optional 并注缺口编号，见 01-接口契约.md
 */

import type { AvailabilityInfo, MoneyValue } from '@shared/contracts'
import type { BitFlag, DateString, Money } from './common'

/** 职级徽章：设计稿「首席 / 资深 / 新锐」。✘ G-25（后端 staff 表无 rank 字段） */
export type StaffRankKey = 'chief' | 'senior' | 'rookie'

/** 卡片右上角档期状态：03/14 屏「当日可约 / 仅剩 1 档 / 05.20 已满」 */
export type AvailabilityState = 'ok' | 'tight' | 'full' | 'unknown'

export interface StaffBadge {
    key: StaffRankKey | string
    label: string
}

/** 卡片级人员信息（01 推荐位、03 列表、14 收藏、22 订单内嵌） */
export interface StaffCardVO {
    id: number
    name: string
    avatar?: string
    cover?: string
    /** ✘ G-25 职级徽章 */
    badges?: StaffBadge[]
    /** ✔ staff/lists、staff/detail 已返回 */
    rating?: number | string
    /** ✘ F-08 03 屏「128 场评价」需要评价条数，现有字段是 order_count（单量） */
    review_count?: number
    order_count?: number
    experience_years?: number
    /** ◐ 03 屏「西式 · 大气控场 · 双语可选」，staff/detail 已返回 tags: string[] */
    tags?: string[]
    profile?: string
    price?: Money
    has_price?: number
    is_favorite?: number
    category_name?: string
    /** ✔ staff/lists 已返回布尔；日期通过 date 参数传入 */
    schedule_available?: boolean
    schedule_message?: string
    /** ✘ G-25 03 屏「仅剩 1 档」需要剩余档位；F-09 14 屏需要下次可约日 */
    availability?: {
        state: AvailabilityState
        label?: string
        remaining?: number
        next_available_date?: DateString
    }
}

/** 05 屏 spec-grid 一行（擅长风格 / 语言 / 服务城市 / 从业年限） */
export interface StaffSpecRow {
    label: string
    value: string
}

/** 05 屏作品网格瓦片：需要年份、场景、人数 */
export interface WorkVO {
    id: number
    title?: string
    cover?: string
    /** ✘ F-13 作品年份 */
    year?: number | string
    /** ✘ F-13 场景（户外草坪 / 中式敬茶） */
    scene?: string
    /** ✘ F-13 人数 */
    guest_count?: number
}

/** 06 屏套餐卡 / 05 屏套餐列表 */
export interface PackageVO {
    /** 后端 staff/packages 返回 package_id + 嵌套 package，两套 id 都要兼容 */
    package_id?: number
    id: number
    name: string
    description?: string
    price: Money
    origin_price?: Money
    /** ✔ booking 页已在消费，如「8 小时现场服务」 */
    duration_desc?: string
    /** ✘ F-11 「/ 场」计价单位 */
    unit?: string
    /** ✘ F-11 06 屏半屏大图，套餐无图片字段 */
    cover?: string
    images?: string[]
    /** ✘ F-12 05 屏「服务包含」三条；后端只在套餐描述里混写 */
    includes?: string[]
    /** ✔ addon_ids 在 shared PackageItem 中已声明 */
    addon_ids?: number[]
}

/** 07 屏附加项卡 */
export interface AddonVO {
    id: number
    name: string
    price: Money
    description?: string
    /** ✘ F-11 附加项无图片，07 屏大图随点选切换无法实现 */
    cover?: string
    /** ✘ F-14 07 屏默认预选前两项需要后端给推荐位 */
    is_default?: BitFlag
    group?: string
}

/** 05 屏评价区顶部统计 */
export interface ReviewSummaryVO {
    score?: number | string
    total: number
    /** ✘ F-16 「98% 推荐」推荐率 */
    good_rate?: number
    /** ✘ F-16 高频标签（控场稳 / 台本细 / 守时）；review/tags 端点存在但无 api 方法 */
    tags?: string[]
}

export interface ReviewVO {
    id: number
    score: number
    content: string
    images?: string[]
    create_time?: string
    user?: { nickname?: string; avatar?: string }
    /** ✘ F-17 13 屏/05 屏「陈默 回复：」，评价回复字段 */
    reply?: { content: string; operator?: string }
}

/** 05 屏详情聚合（对应 v2/staff；v2 未上线时由 detail+works+reviews 三接口拼） */
export interface StaffDetailVO {
    staff: StaffCardVO & {
        intro?: string
        /** ✘ F-10 05 屏简介两段，后端是单段 intro */
        intro_paragraphs?: string[]
        /** ✘ F-10 语言（普通话 · 英语） */
        language?: string[]
        /** ✘ F-10 服务城市（杭州 · 上海 · 苏州） */
        service_cities?: string[]
        /** ◐ 05 屏「持证司仪」 */
        certificates?: Array<{ id: number; name: string; image?: string }>
        works_count?: number
        /** ✘ F-12 服务包含清单 */
        service_includes?: string[]
        specs?: StaffSpecRow[]
    }
    packages: PackageVO[]
    addons: AddonVO[]
    works: { list: WorkVO[]; total: number; has_more: boolean }
    review: { stats: ReviewSummaryVO; list: ReviewVO[] }
    availability: AvailabilityInfo & {
        state?: AvailabilityState
        /** ✘ F-09 剩余档位 */
        remaining?: number
    }
    /** ◐ booking 页用 related_role_configs（detail 返回），v2 用 role_configs，字段名两套 */
    role_configs?: Array<{ role_key: string; label?: string; role_label?: string; required: boolean }>
    banners?: Array<{ image?: string; url?: string }>
}

/** 01/03 屏日期条的一天。✘ G-02：后端无「每日可约人数」批量接口 */
export interface DayAvailability {
    date: DateString
    /** 0=周日 … 6=周六，前端可自算；后端给了就直接用 */
    day_of_week?: number
    /** ✘ G-02 吉日标记（香槟色圆点） */
    is_lucky?: boolean
    /** ✘ G-02 当日可约人数 */
    available_count?: number
    total_count?: number
    state?: AvailabilityState
    /** ✘ G-02 剩余名额（首页吉日卡「剩 6 位」） */
    remaining?: number
}

/** ✘ G-02 批量档期摘要（建议 GET /schedule/availabilitySummary?from=&to=&city_code=&category_id=） */
export interface AvailabilitySummaryVO {
    days: DayAvailability[]
    month?: string
    server_time?: number
}

/** 03 屏地区 chips（杭州 / 上海 / 苏州 / 全国） */
export interface RegionNodeVO {
    id: number
    name: string
    code?: string
    /** ✘ F-04 「热门城市」短列表需后端标记 */
    is_hot?: BitFlag
    children?: RegionNodeVO[]
}

/** 03 屏服务类型 chips */
export interface ServiceCategoryVO {
    id: number
    name: string
    image?: string
    children?: ServiceCategoryVO[]
}

/** 03 屏地区 chip 选中值 */
export interface CityChip {
    code: string
    name: string
    province_code?: string
}

/** 金额透传（避免页面再 import 一次 MoneyValue 时口径不一致） */
export type { MoneyValue }
