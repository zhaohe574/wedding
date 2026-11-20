/**
 * 01 首页。
 *
 * 现状冲突（必须由主理人裁决，见 01-接口契约.md §4.4-C3）：
 *   现网首页由后台装修（index.decorate / index.index 的 page.data）驱动，widget 白名单
 *   只有 banner / home-brand / home-feature-carousel / home-service-categories / home-popup-ad。
 *   设计稿 01 是固定版式（hero + 数据条 + 四宫格 + 推荐 + 吉日 + 服务流程），
 *   其中「数据条」可以走 home-brand.stats（现网已消费），
 *   「吉日」与「服务流程」不在白名单内，走装修就必须新增 widget 或新增接口。
 */

import type { ArticleCard } from '@shared/contracts'
import type { BitFlag, DateString, Money, ServerSeconds } from './common'
import type { StaffCardVO } from './catalog'

/** home-brand widget 的 stats[]（现网 index.vue 已在消费 value/label） */
export interface HomeBrandStat {
    value: string
    label: string
}

/** home-brand widget：只有开关 + 3 格统计（greeting / team_name / cta 已删除） */
export interface HomeBrandWidget {
    enabled?: BitFlag
    stats?: HomeBrandStat[]
}

/** banner widget 的一项（首屏只有一张图，旧的 bg / link / is_show 已删除） */
export interface HomeBannerVO {
    image?: string
    slogan?: string
    slogan_show?: '0' | '1'
    slogan_color?: string
    title?: string
    title_show?: '0' | '1'
    subtitle?: string
    subtitle_show?: '0' | '1'
    primary_text?: string
    primary_show?: '0' | '1'
    secondary_text?: string
    secondary_show?: '0' | '1'
    title_color?: string
    subtitle_color?: string
}

/** 01 屏四宫格快捷入口：纯前端常量，不接接口 */
export interface HomeQuickEntry {
    key: 'query' | 'staff' | 'case' | 'consult'
    label: string
    icon: string
    path: string
}

/** 01 屏「近期吉日 · 可约情况」一格。✘ G-02：剩余名额后端没有 */
export interface HomeLuckySlot {
    date: DateString
    /** 「11.14」 */
    date_text: string
    /** 「周六」 */
    weekday_text: string
    /** ✘ G-02 「剩 6 位」；现 luckyDays 无该字段 */
    remaining?: number
    is_full?: boolean
}

/** 01 屏服务流程一行（T-30 / T-01 / T-0）。✘ F-20：无接口承载，现为静态文案 */
export interface HomeFlowStep {
    tag: string
    title: string
    desc: string
}

/** 01 屏一次渲染所需的全部数据（页面只认这一个对象） */
export interface HomeScreenVO {
    brand: {
        name: string
        slogan?: string
        eyebrow?: string
        subtitle?: string
        hero_image?: string
        stats: HomeBrandStat[]
    }
    banners: HomeBannerVO[]
    /** ✔ 端点 staff.recommend 存在；✘ api 层无方法且从未被调用 */
    staff_recommend: StaffCardVO[]
    staff_total?: number
    /** ✘ G-02 吉日剩余名额 */
    lucky_slots: HomeLuckySlot[]
    lucky_range_text?: string
    /** ✘ F-20 服务流程 */
    flow_steps: HomeFlowStep[]
    articles: ArticleCard[]
    server_time?: ServerSeconds
    /** 设计稿价格为「¥3,800 起」，卡片需要 unit 文案 */
    price_unit?: Money
    /** 装修数据被跳过的 widget，用于上报 */
    skipped_widgets?: string[]
}
