<template>
    <view v-if="content.enabled !== 0 && hasWedding" class="wedding-countdown">
        <view class="profile-countdown-card">
            <text class="profile-countdown-label">{{ content.title || '婚礼倒计时' }}</text>
            <text class="profile-countdown-value">{{ dayText }}</text>
            <text class="profile-countdown-desc">
                {{ content.desc || '摄影、主持、场地档期已锁定，跟妆和花艺待确认。' }}
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

    .profile-countdown-card {
        display: flex;
        flex-direction: column;
        gap: var(--wm-user-countdown-gap, 16rpx);
        padding: var(--wm-user-countdown-padding-top, 36rpx)
            var(--wm-user-countdown-padding-right, 36rpx)
            var(--wm-user-countdown-padding-bottom, 40rpx)
            var(--wm-user-countdown-padding-left, 36rpx);
        border-radius: var(--wm-user-countdown-radius, 56rpx);
        border: 2rpx solid var(--wm-color-border-strong, #f4c7bf);
        background: linear-gradient(135deg, #fff5f1 0%, #fde7e1 100%);
        backdrop-filter: blur(32rpx);
        -webkit-backdrop-filter: blur(32rpx);
    }

    .profile-countdown-label {
        display: block;
        font-size: 24rpx;
        line-height: 1.35;
        font-weight: 700;
        color: #e85a4f;
    }

    .profile-countdown-value {
        display: block;
        font-size: 67rpx;
        line-height: 1;
        font-weight: 700;
        color: #1e2432;
    }

    .profile-countdown-desc {
        display: block;
        font-size: 26rpx;
        line-height: 1.6;
        color: #7f7b78;
    }
}
</style>
