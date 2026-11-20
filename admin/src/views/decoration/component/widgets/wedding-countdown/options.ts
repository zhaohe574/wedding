import {
    WEDDING_COUNTDOWN_DEFAULTS,
    type WeddingCountdownContent
} from '@shared/contracts/decorate'

export type { WeddingCountdownContent }

export type WeddingCountdownStyles = Record<string, never>

export interface WeddingCountdownOptions {
    title: string
    name: string
    pageScope: string[]
    content: WeddingCountdownContent
    styles: WeddingCountdownStyles
}

/**
 * 个人中心婚期卡片的默认值。
 *
 * ⚠ 直接复用 shared 契约的 `WEDDING_COUNTDOWN_DEFAULTS`，不在这里写第二份。
 * 剩余天数 / 待办数 / 场地来自 `/v2/me`，不属于装修字段。
 */
export default (): WeddingCountdownOptions => ({
    title: '婚期卡片',
    name: 'wedding-countdown',
    pageScope: ['user'],
    content: { ...WEDDING_COUNTDOWN_DEFAULTS },
    styles: {}
})
