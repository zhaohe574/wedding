<template>
    <view v-if="visible" class="home-popup-ad" @touchmove.stop.prevent="stopPageTouchMove">
        <BaseOverlayMask :show="visible" :z-index="zIndex" :closeable="false" />
        <view
            class="home-popup-ad__panel"
            :class="panelClass"
            :style="panelStyle"
            @click.stop
            @touchmove.stop="stopPanelTouchMove"
        >
            <view v-if="showClose" class="home-popup-ad__close" @click="handleClose">
                <BaseIcon name="close" size="30" color="#FFFDF8" />
            </view>

            <view v-if="showImage" class="home-popup-ad__media" :style="mediaStyle">
                <image class="home-popup-ad__image" :src="imageUrl" mode="aspectFit" />
            </view>

            <view v-if="showText" class="home-popup-ad__body">
                <text v-if="normalizedConfig.title" class="home-popup-ad__title">
                    {{ normalizedConfig.title }}
                </text>
                <text v-if="normalizedConfig.content" class="home-popup-ad__content">
                    {{ normalizedConfig.content }}
                </text>
            </view>

            <view class="home-popup-ad__footer">
                <BaseButton
                    :label="buttonLabel"
                    variant="dark"
                    size="md"
                    block
                    height="84rpx"
                    @click="handleAction"
                />
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'

import BaseButton from '@/components/base/BaseButton.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseOverlayMask from '@/components/base/BaseOverlayMask.vue'
import { useAppStore } from '@/stores/app'
import cache from '@/utils/cache'
import { hasConfiguredLink, navigateTo } from '@/utils/util'

type HomePopupAdType = 'image' | 'text' | 'image_text'
type HomePopupAdFrequency =
    | 'daily'
    | 'session'
    | 'every_home_entry'
    | 'once'
    | 'interval_days'
    | 'custom_limit'
type HomePopupAdShowTiming = 'page_ready' | 'delay'
type AppLink = Record<string, any> | string | null | undefined

interface HomePopupAdConfig {
    enabled?: number | string | boolean
    type?: HomePopupAdType | string
    title?: string
    content?: string
    image?: string
    background_color?: string
    backgroundColor?: string
    button_text?: string
    buttonText?: string
    link?: AppLink
    frequency?: HomePopupAdFrequency | string
    show_timing?: HomePopupAdShowTiming | string
    showTiming?: HomePopupAdShowTiming | string
    delay_seconds?: number | string
    delaySeconds?: number | string
    interval_days?: number | string
    intervalDays?: number | string
    max_total_count?: number | string
    maxTotalCount?: number | string
    max_daily_count?: number | string
    maxDailyCount?: number | string
    start_time?: string
    startTime?: string
    end_time?: string
    endTime?: string
    close_counts_as_shown?: number | string | boolean
    closeCountsAsShown?: number | string | boolean
    show_close?: number | string | boolean
    showClose?: number | string | boolean
}

interface HomePopupAdStorageState {
    adKey?: string
    date?: string
    totalCount?: number
    dailyCount?: number
    lastShownDate?: string
    shownOnce?: boolean
}

const props = withDefaults(
    defineProps<{
        config?: HomePopupAdConfig | null
        zIndex?: number
        refreshKey?: number
        pageActive?: boolean
    }>(),
    {
        config: null,
        zIndex: 20060,
        refreshKey: 0,
        pageActive: true
    }
)

const appStore = useAppStore()
const visible = ref(false)
let sessionShown = false
let delayTimer: ReturnType<typeof setTimeout> | null = null

const STORAGE_KEY = 'home_popup_ad_shown'

const normalizeBoolean = (value: unknown, fallback = false) => {
    if (value === true || value === 1 || value === '1' || value === 'true') return true
    if (value === false || value === 0 || value === '0' || value === 'false') return false
    return fallback
}

const normalizeType = (value: unknown): HomePopupAdType => {
    const nextType = String(value || '').trim()
    return ['image', 'text', 'image_text'].includes(nextType)
        ? (nextType as HomePopupAdType)
        : 'image_text'
}

const normalizeFrequency = (value: unknown): HomePopupAdFrequency => {
    const nextFrequency = String(value || '').trim()
    if (nextFrequency === 'every_time') return 'every_home_entry'
    return ['daily', 'session', 'every_home_entry', 'once', 'interval_days', 'custom_limit'].includes(nextFrequency)
        ? (nextFrequency as HomePopupAdFrequency)
        : 'daily'
}

const normalizeShowTiming = (value: unknown): HomePopupAdShowTiming => {
    const nextTiming = String(value || '').trim()
    return ['page_ready', 'delay'].includes(nextTiming) ? (nextTiming as HomePopupAdShowTiming) : 'page_ready'
}

const normalizeNumber = (value: unknown, min: number, max: number, fallback: number) => {
    if (value === undefined || value === null || value === '') return fallback
    const numberValue = Number(value)
    if (!Number.isFinite(numberValue)) return fallback
    return Math.max(min, Math.min(max, Math.floor(numberValue)))
}

const normalizeColor = (value: unknown) => {
    const color = String(value || '').trim()
    return /^#[0-9a-fA-F]{6}$/.test(color) || /^#[0-9a-fA-F]{3}$/.test(color)
        ? color.toUpperCase()
        : '#FFFDF8'
}

const todayKey = () => {
    const date = new Date()
    const year = date.getFullYear()
    const month = `${date.getMonth() + 1}`.padStart(2, '0')
    const day = `${date.getDate()}`.padStart(2, '0')
    return `${year}-${month}-${day}`
}

const parseDateTime = (value: string) => {
    const trimmedValue = value.trim()
    if (!trimmedValue) return null

    const normalizedValue = trimmedValue.includes(' ') ? trimmedValue.replace(' ', 'T') : trimmedValue
    const timestamp = new Date(normalizedValue).getTime()
    return Number.isNaN(timestamp) ? null : timestamp
}

const getDayTimestamp = (value: string) => {
    const timestamp = new Date(`${value}T00:00:00`).getTime()
    return Number.isNaN(timestamp) ? null : timestamp
}

const getDayDiff = (startDate: string, endDate: string) => {
    const start = getDayTimestamp(startDate)
    const end = getDayTimestamp(endDate)
    if (start === null || end === null) return 0
    return Math.floor((end - start) / 86400000)
}

const stringifyLink = (link: AppLink) => {
    if (typeof link === 'string') return link
    if (!link) return ''

    try {
        return JSON.stringify(link)
    } catch (error) {
        return ''
    }
}

const normalizedConfig = computed(() => {
    const config = props.config || {}
    const buttonText = String(config.button_text || config.buttonText || '').trim()
    return {
        enabled: normalizeBoolean(config.enabled, false),
        type: normalizeType(config.type),
        title: String(config.title || '').trim(),
        content: String(config.content || '').trim(),
        image: String(config.image || '').trim(),
        backgroundColor: normalizeColor(config.background_color ?? config.backgroundColor),
        buttonText: buttonText || '查看详情',
        link: config.link,
        frequency: normalizeFrequency(config.frequency),
        showTiming: normalizeShowTiming(config.show_timing ?? config.showTiming),
        delaySeconds: normalizeNumber(config.delay_seconds ?? config.delaySeconds, 0, 30, 0),
        intervalDays: normalizeNumber(config.interval_days ?? config.intervalDays, 1, 365, 7),
        maxTotalCount: normalizeNumber(config.max_total_count ?? config.maxTotalCount, 0, 999, 0),
        maxDailyCount: normalizeNumber(config.max_daily_count ?? config.maxDailyCount, 0, 99, 1),
        startTime: String(config.start_time || config.startTime || '').trim(),
        endTime: String(config.end_time || config.endTime || '').trim(),
        closeCountsAsShown: normalizeBoolean(config.close_counts_as_shown ?? config.closeCountsAsShown, true),
        showClose: normalizeBoolean(config.show_close ?? config.showClose, true)
    }
})

const imageUrl = computed(() => appStore.getImageUrl(normalizedConfig.value.image))
const panelStyle = computed(() => ({
    zIndex: props.zIndex + 1,
    backgroundColor: normalizedConfig.value.backgroundColor
}))
const mediaStyle = computed(() => ({
    backgroundColor: normalizedConfig.value.backgroundColor
}))
const showImage = computed(() => normalizedConfig.value.type !== 'text' && !!normalizedConfig.value.image)
const showText = computed(
    () =>
        normalizedConfig.value.type !== 'image' &&
        (!!normalizedConfig.value.title || !!normalizedConfig.value.content)
)
const hasValidContent = computed(() => {
    if (normalizedConfig.value.type === 'image') return showImage.value
    if (normalizedConfig.value.type === 'text') return showText.value
    return showImage.value || showText.value
})
const buttonLabel = computed(() => normalizedConfig.value.buttonText)
const showClose = computed(() => normalizedConfig.value.showClose)
const panelClass = computed(() => [
    `home-popup-ad__panel--${normalizedConfig.value.type}`,
    {
        'home-popup-ad__panel--image-only': showImage.value && !showText.value,
        'home-popup-ad__panel--text-only': showText.value && !showImage.value
    }
])

const clearDelayTimer = () => {
    if (delayTimer) {
        clearTimeout(delayTimer)
        delayTimer = null
    }
}

const readState = (): HomePopupAdStorageState => {
    const savedState = cache.get(STORAGE_KEY) as HomePopupAdStorageState | null
    return savedState && typeof savedState === 'object' ? savedState : {}
}

const getAdKey = () => {
    const { type, title, content, image, buttonText, link } = normalizedConfig.value
    return [type, title, content, image, buttonText, stringifyLink(link)].join('|')
}

const normalizeCountValue = (value: unknown) => {
    const numberValue = Number(value || 0)
    return Number.isFinite(numberValue) ? Math.max(0, Math.floor(numberValue)) : 0
}

const getCurrentState = () => {
    const savedState = readState()
    const adKey = getAdKey()
    const state = !savedState.adKey || savedState.adKey === adKey ? savedState : {}
    const today = todayKey()
    const dailyCount = state.date === today ? normalizeCountValue(state.dailyCount) : 0
    return {
        adKey,
        date: state.date || '',
        totalCount: normalizeCountValue(state.totalCount),
        dailyCount,
        lastShownDate: state.lastShownDate || state.date || '',
        shownOnce: !!state.shownOnce
    }
}

const writeState = (state: HomePopupAdStorageState) => {
    cache.set(STORAGE_KEY, state)
}

const isWithinActiveTime = () => {
    const { startTime, endTime } = normalizedConfig.value
    const now = Date.now()
    const startTimestamp = parseDateTime(startTime)
    const endTimestamp = parseDateTime(endTime)

    if (startTimestamp !== null && now < startTimestamp) return false
    if (endTimestamp !== null && now > endTimestamp) return false

    return true
}

const isCountAllowed = (state: ReturnType<typeof getCurrentState>) => {
    const { maxTotalCount, maxDailyCount } = normalizedConfig.value
    if (maxTotalCount > 0 && state.totalCount >= maxTotalCount) return false
    if (maxDailyCount > 0 && state.dailyCount >= maxDailyCount) return false

    return true
}

const isFrequencyAllowed = (state: ReturnType<typeof getCurrentState>) => {
    const { frequency, intervalDays } = normalizedConfig.value
    const today = todayKey()

    if (frequency === 'every_home_entry') return true
    if (frequency === 'session') return !sessionShown
    if (frequency === 'daily') return state.lastShownDate !== today
    if (frequency === 'once') return !state.shownOnce
    if (frequency === 'interval_days') {
        if (!state.lastShownDate) return true
        return getDayDiff(state.lastShownDate, today) >= intervalDays
    }

    return true
}

const shouldShow = () => {
    if (!props.pageActive || !normalizedConfig.value.enabled || !hasValidContent.value || !isWithinActiveTime()) {
        return false
    }

    const state = getCurrentState()
    return isCountAllowed(state) && isFrequencyAllowed(state)
}

const openIfNeeded = () => {
    clearDelayTimer()
    if (!shouldShow()) {
        visible.value = false
        return
    }

    if (normalizedConfig.value.showTiming === 'delay' && normalizedConfig.value.delaySeconds > 0) {
        const delayMilliseconds = normalizedConfig.value.delaySeconds * 1000
        delayTimer = setTimeout(() => {
            delayTimer = null
            visible.value = shouldShow()
        }, delayMilliseconds)
        return
    }

    visible.value = true
}

const markShown = (countAsShown = true) => {
    if (!countAsShown) return

    const state = getCurrentState()
    const today = todayKey()
    writeState({
        adKey: state.adKey,
        date: today,
        totalCount: state.totalCount + 1,
        dailyCount: state.dailyCount + 1,
        lastShownDate: today,
        shownOnce: true
    })

    if (normalizedConfig.value.frequency === 'session') {
        sessionShown = true
    }
}

const handleClose = () => {
    clearDelayTimer()
    visible.value = false
    markShown(normalizedConfig.value.closeCountsAsShown)
}

const handleAction = () => {
    const link = normalizedConfig.value.link
    clearDelayTimer()
    visible.value = false
    markShown(true)
    if (hasConfiguredLink(link)) {
        navigateTo(link)
    }
}

const stopPageTouchMove = () => {}
const stopPanelTouchMove = () => {}

watch(
    [normalizedConfig, () => props.refreshKey, () => props.pageActive],
    () => {
        openIfNeeded()
    },
    { immediate: true }
)

onBeforeUnmount(() => {
    clearDelayTimer()
})
</script>

<style lang="scss" scoped>
.home-popup-ad {
    position: fixed;
    inset: 0;
    z-index: 20060;
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    padding: 48rpx;
}

.home-popup-ad__panel {
    position: relative;
    width: 100%;
    max-width: 640rpx;
    overflow: hidden;
    border-radius: var(--wm-radius-popup, 44rpx);
    background: var(--wm-color-bg-card, #fffdf8);
    box-shadow: 0 34rpx 90rpx rgba(11, 11, 11, 0.32);
}

.home-popup-ad__close {
    position: absolute;
    top: 20rpx;
    right: 20rpx;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 58rpx;
    height: 58rpx;
    border-radius: 999rpx;
    background: rgba(11, 11, 11, 0.56);
    backdrop-filter: blur(10rpx);
}

.home-popup-ad__media {
    width: 100%;
    height: 520rpx;
    background: #111111;
}

.home-popup-ad__panel--image-only .home-popup-ad__media {
    height: 720rpx;
}

.home-popup-ad__image {
    width: 100%;
    height: 100%;
}

.home-popup-ad__body {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
    padding: 42rpx 44rpx 0;
    text-align: center;
}

.home-popup-ad__panel--text-only .home-popup-ad__body {
    padding-top: 58rpx;
}

.home-popup-ad__title {
    color: var(--wm-text-primary, #191713);
    font-size: 34rpx;
    font-weight: 700;
    line-height: 1.38;
}

.home-popup-ad__content {
    color: var(--wm-text-secondary, #6c6254);
    font-size: 26rpx;
    line-height: 1.72;
    white-space: pre-line;
}

.home-popup-ad__footer {
    padding: 36rpx 44rpx 44rpx;
}

.home-popup-ad__panel--image-only .home-popup-ad__footer {
    padding-top: 28rpx;
}
</style>
