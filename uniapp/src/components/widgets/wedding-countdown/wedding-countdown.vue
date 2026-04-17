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
                <text class="profile-countdown-value">{{ dayText }}</text>
                <text class="profile-countdown-note">{{ countdownNote }}</text>
            </view>

            <text v-if="descriptionText" class="profile-countdown-desc">
                {{ descriptionText }}
            </text>
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

const dayText = computed(() => `${Math.max(Number(countdown.value.days || 0), 0)} DAY`)

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

const descriptionText = computed(() => {
    const customDesc = String(props.content?.desc || '').trim()
    if (customDesc && customDesc !== '摄影、主持、场地档期已锁定，跟妆和花艺待确认。') {
        return customDesc
    }
    if (weddingDateText.value) {
        return `仪式日期 · ${weddingDateText.value}`
    }
    return ''
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
    display: flex;
    flex-direction: column;
    gap: var(--wm-user-countdown-gap, 16rpx);
    padding: var(--wm-user-countdown-padding-top, 36rpx)
        var(--wm-user-countdown-padding-right, 36rpx) var(--wm-user-countdown-padding-bottom, 40rpx)
        var(--wm-user-countdown-padding-left, 36rpx);
    border-radius: var(--wm-user-countdown-radius, 56rpx);
    border: 2rpx solid rgba(244, 199, 191, 0.72);
    background: rgba(255, 248, 245, 0.86);
    box-shadow: 0 16rpx 34rpx rgba(214, 185, 167, 0.1);
    backdrop-filter: blur(24rpx);
    -webkit-backdrop-filter: blur(24rpx);
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
    color: var(--wm-color-primary, #e85a4f);
}

.profile-countdown-date {
    flex-shrink: 0;
    font-size: 22rpx;
    line-height: 1.3;
    font-weight: 600;
    color: var(--wm-text-tertiary, #9d948f);
}

.profile-countdown-value {
    display: block;
    font-size: 66rpx;
    line-height: 1;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.profile-countdown-note {
    flex-shrink: 0;
    text-align: right;
    font-size: 22rpx;
    line-height: 1.45;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);
}

.profile-countdown-desc {
    display: block;
    font-size: 22rpx;
    line-height: 1.5;
    color: var(--wm-text-secondary, #7f7b78);
}
</style>
