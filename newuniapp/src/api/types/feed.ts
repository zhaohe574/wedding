/**
 * 02 动态 / 13 动态详情。
 *
 * 现状约束（来自 api/content.ts 注释与 endpoints.json）：
 *   · dynamic/publish 后端硬关闭，用户侧无发布入口 —— 设计稿只读，正确。
 *   · 评论端点在后端存在（commentLists / addComment / likeComment），但 api 层没有方法，
 *     且送审模式（feature_switch.mini_program_review_mode）下评论被禁用。
 *     13 屏的评论区是本契约里最大的功能缺口之一（G-11）。
 *   · 动态字段没有任何 TS 契约，api 层全部 any —— 下面的字段全部标 ◐ 待确认。
 */

import type { BitFlag, Money, TimeText } from './common'

/** 02 屏分类 tab（全部 / 现场 / 团队 / 课程）。◐ 后端 dynamic/lists 只确认支持 tag 参数 */
export interface FeedCategory {
    key: string
    label: string
    count?: number
}

/** 作者身份：姓名旁的「官方 / 首席主持 / 备婚中」徽章 */
export interface FeedAuthor {
    id: number
    name: string
    avatar?: string
    /** 徽章文案数组，前端按顺序渲染，最多两个 */
    badge_texts?: string[]
    is_official?: BitFlag
    /** 可点击进详情、显示「预约 TA」时需要 */
    staff_id?: number
    role_label?: string
    rating?: number | string
    order_count?: number
}

/** 媒体块：图片或视频静帧 */
export interface FeedMedia {
    type: 'image' | 'video'
    cover: string
    url?: string
    /** 视频时长秒数，展示为 03:12 */
    duration?: number | string
    aspect?: 'land' | 'portrait'
    /** 13 屏「西溪湿地 · 户外草坪」角标 */
    place?: string
}

/** 正文内嵌活动卡（02 屏第 3 条、13 屏正文下） */
export interface FeedEventBrief {
    activity_id: number
    title: string
    /** 「12.06」 */
    date_text?: string
    /** 「周日 14:00」 */
    time_text?: string
    address?: string
    joined_count?: number
    /** ✘ F-21 「还剩 6 席」 */
    remaining_seats?: number
    /** ◐ 报名态：0 未报名 1 已报名 2 已满 */
    join_state?: number
}

export interface FeedPost {
    id: number
    author: FeedAuthor
    /** 「2 小时前 · 杭州·西溪」 */
    time_text?: TimeText
    content: string
    title?: string
    medias?: FeedMedia[]
    event?: FeedEventBrief
    like_count?: number
    comment_count?: number
    share_count?: number
    /** ◐ 当前用户是否已赞 */
    is_liked?: BitFlag
    /** ◐ 13 屏「3,284 次浏览」 */
    view_count?: number
    /** ◐ 话题标签（# 户外草坪） */
    tags?: string[]
    create_time?: string
}

/** 13 屏评论。✘ G-11：api 层无方法 + 送审模式禁用 */
export interface FeedComment {
    id: number
    post_id: number
    author: FeedAuthor
    content: string
    time_text?: TimeText
    like_count?: number
    is_liked?: BitFlag
    /** 作者回复（「陈默 回复：…」） */
    reply?: { author_name: string; content: string }
    /** ◐ 排序：hot / latest */
    sort?: string
}

/** 02 屏列表筛选状态（页面本地状态，不需要接口） */
export interface FeedFilter {
    category?: string
    tag?: string
    sort?: 'latest' | 'hot'
}

/** 02 屏一屏的数据 */
export interface FeedScreenVO {
    categories: FeedCategory[]
    hot_tags: string[]
    list: FeedPost[]
    has_more: boolean
    /** 空态文案依据 */
    total?: number
    /** 送审模式：评论与点赞入口需置灰（FEATURE_OFF） */
    interaction_enabled?: boolean
}

/** 13 屏一屏的数据 */
export interface FeedDetailVO {
    post: FeedPost
    /** 「预约 TA」所需人员摘要 */
    author_staff?: { id: number; name: string; avatar?: string; price?: Money }
    comments: FeedComment[]
    comment_total: number
    comment_enabled: boolean
    event?: FeedEventBrief
}
