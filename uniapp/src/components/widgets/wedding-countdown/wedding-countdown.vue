<template>
    <view v-if="content.enabled !== 0 && hasWedding" class="wedding-countdown">
        <view class="profile-countdown-card">
            <view class="profile-countdown-head">
                <text class="profile-countdown-label">{{ labelText }}</text>
                <text v-if="weddingDateText" class="profile-countdown-date">{{
                    weddingDateText
                }}</text>
            </view>

            <view class="profile-countdown-main">
                <view class="profile-countdown-value-wrap">
                    <text class="profile-countdown-value">{{ dayNumber }}</text>
                    <text class="profile-countdown-unit">DAY</text>
                </view>
                <text class="profile-countdown-note">{{ countdownNote }}</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue'

const props = defineProps({
    content: {
        type: Object,
        default: () => ({})
    },
    styles: {
        type: Object,
        default: () => ({})
    },
    isLogin: {
        type: Boolean,
        default: true
    },
    weddingInfo: {
        type: Object,
        default: () => ({})
    }
})

const hasWedding = ref(false)
const weddingDate = ref<Date | null>(null)
const countdown = ref({
    days: 0,
    hours: 0,
    minutes: 0,
    seconds: 0
})
let timer: ReturnType<typeof setInterval> | null = null

const dayNumber = computed(() => `${Math.max(Number(countdown.value.days || 0), 0)}`)

const labelText = computed(() => String(props.content?.title || '').trim() || '婚礼倒计时')

const weddingDateText = computed(() => String(props.weddingInfo?.wedding_date || '').trim())

const countdownNote = computed(() => {
    if (!weddingDate.value) return '筹备中'
    const diff = weddingDate.value.getTime() - Date.now()
    if (diff <= 0) return '婚礼日已到'
    if (countdown.value.days > 0) {
        return `${countdown.value.hours} 小时 · ${countdown.value.minutes} 分`
    }
    return `${countdown.value.hours} 小时 · ${countdown.value.minutes} 分 · ${countdown.value.seconds} 秒`
})

const clearTimer = () => {
    if (!timer) return
    clearInterval(timer)
    timer = null
}

const resetCountdownState = () => {
    hasWedding.value = false
    weddingDate.value = null
    countdown.value = {
        days: 0,
        hours: 0,
        minutes: 0,
        seconds: 0
    }
}

const parseWeddingDate = (value: unknown) => {
    const rawValue = String(value || '').trim()
    if (!rawValue) return null
    const parsedDate = new Date(rawValue.replace(/-/g, '/'))
    return Number.isNaN(parsedDate.getTime()) ? null : parsedDate
}

const calculateCountdown = () => {
    if (!weddingDate.value) return

    const now = Date.now()
    const target = weddingDate.value.getTime()
    const diff = target - now

    if (diff <= 0) {
        countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0 }
        clearTimer()
        return
    }

    countdown.value.days = Math.floor(diff / (1000 * 60 * 60 * 24))
    countdown.value.hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
    countdown.value.minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
    countdown.value.seconds = Math.floor((diff % (1000 * 60)) / 1000)
}

const syncCountdownState = () => {
    if (!props.isLogin) {
        resetCountdownState()
        clearTimer()
        return
    }

    const hasOrder = Number(props.weddingInfo?.has_order || 0) === 1
    const nextWeddingDate = parseWeddingDate(props.weddingInfo?.wedding_date)
    if (!hasOrder || !nextWeddingDate) {
        resetCountdownState()
        clearTimer()
        return
    }

    hasWedding.value = true
    weddingDate.value = nextWeddingDate
    calculateCountdown()

    if (!timer) {
        timer = setInterval(calculateCountdown, 1000)
    }
}

watch(
    () => [props.isLogin, props.weddingInfo?.has_order, props.weddingInfo?.wedding_date],
    () => {
        syncCountdownState()
    },
    { immediate: true }
)

onUnmounted(() => {
    clearTimer()
})
</script>

<style scoped lang="scss">
.wedding-countdown {
    width: 100%;
}

.profile-countdown-card {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: var(--wm-user-countdown-gap, 16rpx);
    padding: 28rpx 32rpx;
    border-radius: 28rpx;
    border: 1.5rpx solid rgba(217, 190, 130, 0.42);
    background: linear-gradient(135deg, rgba(255, 253, 248, 0.98) 0%, #F5ECDA 100%);
    box-shadow: 0 12rpx 32rpx rgba(74, 43, 24, 0.06);
    overflow: hidden;

    &::after {
        content: '';
        position: absolute;
        top: -40rpx;
        right: -40rpx;
        width: 140rpx;
        height: 140rpx;
        border-radius: 999rpx;
        background: radial-gradient(circle, rgba(217, 190, 130, 0.25) 0%, rgba(217, 190, 130, 0) 70%);
        pointer-events: none;
    }
}

.profile-countdown-head,
.profile-countdown-main {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
}

.profile-countdown-label {
    min-width: 0;
    display: block;
    font-size: 24rpx;
    line-height: 1.35;
    font-weight: 700;
    color: #A07830;
    letter-spacing: 1rpx;
}

.profile-countdown-date {
    flex-shrink: 0;
    font-size: 22rpx;
    line-height: 1.3;
    font-weight: 600;
    color: #8A806F;
}

.profile-countdown-value-wrap {
    min-width: 0;
    display: inline-flex;
    align-items: flex-end;
    gap: 10rpx;
}

.profile-countdown-value {
    display: block;
    font-size: 68rpx;
    line-height: 1;
    font-weight: 900;
    color: #191713;
    letter-spacing: -2rpx;
}

.profile-countdown-unit {
    padding-bottom: 6rpx;
    font-size: 22rpx;
    line-height: 1;
    font-weight: 800;
    color: #B8954A;
    letter-spacing: 2rpx;
}

.profile-countdown-note {
    flex-shrink: 0;
    text-align: right;
    font-size: 23rpx;
    line-height: 1.45;
    font-weight: 600;
    color: #5E564B;
}
</style>
