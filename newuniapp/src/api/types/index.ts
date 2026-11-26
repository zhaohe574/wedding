/**
 * 页面契约层入口。
 *
 * 用法（页面里只需一行）：
 *   import type { StaffCardVO, HomeScreenVO, OrderDetailVO } from '@/api/types'
 *
 * 约定：
 *   1. 本目录只放「页面要消费的形状」，不放请求函数；请求函数仍在 src/api/*.ts。
 *   2. 字段与后端不一致时，先改这里的 optional 标记并登记到
 *      docs/redesign/v2/01-接口契约.md 的缺口表，再接字段；禁止先写死假数据再补。
 *   3. 状态机枚举一律从 @shared/contracts 引，本目录不重复定义数字字面量。
 *
 * 屏幕 → 类型速查
 *   01 首页          HomeScreenVO / HomeLuckySlot / StaffCardVO
 *   02 动态          FeedScreenVO / FeedPost / FeedEventBrief
 *   03 查档期        DayAvailability / AvailabilitySummaryVO / StaffCardVO / CityChip
 *   04 我的          （见 src/api/types/profile 由 v2/me 承担，暂用 @shared MeResponse）
 *   05 主持人详情    StaffDetailVO / WorkVO / ReviewVO / PackageVO
 *   06 选择套餐      PackageVO
 *   07 附加项        AddonVO
 *   08 订单确认      QuoteLineVO / PaymentScheduleStepVO / UpsellVO
 *   09 支付成功      PaySuccessVO / PayStatusVO
 *   10 订单详情      OrderDetailVO / OrderMilestoneVO / OrderTodoVO
 *   11 新人问卷      QuestionnaireTaskVO / QuestionVO / QuestionnaireProgress
 *   12 问卷已提交    QuestionnaireTaskVO / QuestionnaireResultRow
 *   13 动态详情      FeedDetailVO / FeedComment
 *   14 我的收藏      StaffCardVO（+ availability）
 *   15 我的活动      ActivityScreenVO / ActivityRegistrationVO
 *   16 通知与提醒    NotificationVO / NotificationGroupVO / NotifyTab
 *   17 我的咨询      ConversationVO / ChatMessageVO（G-13 未实现）
 *   18 订单售后      AfterSaleOrderBriefVO / ChangeRequestVO / AfterSaleEntryVO
 *   19 候补申请      WaitlistApplyScreenVO / WaitlistJoinPayload
 *   20 我的候补      WaitlistVO / WaitlistScreenVO
 *   21 登录          （登录入参保持 src/api/auth.ts 内联类型）
 *   22 我的订单      OrderListItemVO / OrderTabCountVO / OrderListQuery
 *   23 售后申请      DateChangePayload / ChangeReasonOptionVO / RefundRequestVO
 *   24 客服管家      ConciergeScreenVO / ConsultContactVO
 */

export * from './common'
export * from './catalog'
export * from './home'
export * from './feed'
export * from './activity'
export * from './order'
export * from './pay'
export * from './questionnaire'
export * from './notification'
export * from './consult'
export * from './aftersale'
export * from './waitlist'
export * from './wallet'
