/** 人员详情轮播配置：默认值、归一化与高度换算（页面与轮播组件共用同一套口径） */

import { getDeviceInfo } from '@/platform/device'

export type BannerIndicatorStyle = 0 | 1 | 2 | 3 | 4

export interface BannerSlide {
    key: string
    type: 'image' | 'video'
    src: string
    poster: string
    autoplay: boolean
}

export interface BannerConfig {
    /** 1 = 小图可展开；2 = 大图固定 */
    mode: 1 | 2
    smallHeight: number
    largeHeight: number
    indicatorStyle: BannerIndicatorStyle
    autoplay: boolean
    interval: number
}

export const DEFAULT_BANNER_CONFIG: BannerConfig = {
    mode: 1,
    smallHeight: 400,
    largeHeight: 600,
    indicatorStyle: 1,
    autoplay: true,
    interval: 3000
}

function positive(value: unknown, fallback: number) {
    const n = Number(value)
    return Number.isFinite(n) && n > 0 ? Math.round(n) : fallback
}

function normalizeIndicator(value: unknown): BannerIndicatorStyle {
    const n = Number(value)
    return n === 0 || n === 1 || n === 2 || n === 3 || n === 4 ? (n as BannerIndicatorStyle) : 1
}

/**
 * 后台字段可能来自 v1（banner_mode / banner_small_height…）或 v2（mode / small_height…），
 * 统一在这里归一化；非法值一律回落默认，不让页面自己猜。
 */
export function normalizeBannerConfig(input: any): BannerConfig {
    const mode = Number(input?.mode) === 2 ? 2 : 1
    const smallHeight = positive(input?.smallHeight, DEFAULT_BANNER_CONFIG.smallHeight)
    const largeHeight = positive(input?.largeHeight, DEFAULT_BANNER_CONFIG.largeHeight)
    const interval = Math.min(10000, Math.max(1000, positive(input?.interval, DEFAULT_BANNER_CONFIG.interval)))

    return {
        mode,
        smallHeight,
        largeHeight,
        indicatorStyle: normalizeIndicator(input?.indicatorStyle),
        autoplay: Number(input?.autoplay) !== 0,
        interval
    }
}

/** 小图模式且展开高度确实更大时才出现展开入口 */
export function canExpandBanner(config: BannerConfig) {
    return config.mode === 1 && config.largeHeight > config.smallHeight
}

/**
 * 后台可配置的高度上限（rpx）。
 * 必须与后端 StaffLogic::updateBannerConfig 的 1600 上限一致：
 * 后台能存多高，前台就能画多高，不再用 58vh 把配置吃掉。
 */
export const BANNER_MAX_HEIGHT_RPX = 1600

/**
 * rpx 配置 → 当前设备的 px 高度。
 * 下限 280rpx：200rpx 时指示器/展开按钮会挤进导航区；
 * 上限 1600rpx：与后台配置上限对齐（后端可设 >1000，最高 1600）。
 */
export function bannerStageHeight(config: BannerConfig, expanded = false) {
    const raw = config.mode === 2 || expanded ? config.largeHeight : config.smallHeight
    const rpx = Math.min(
        BANNER_MAX_HEIGHT_RPX,
        Math.max(280, Math.round(raw) || DEFAULT_BANNER_CONFIG.smallHeight)
    )
    const device = getDeviceInfo()
    return Math.round((rpx * device.windowWidth) / 750)
}
