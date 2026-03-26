<template>
    <view v-if="content.enabled !== 0 && hasWedding" class="wedding-countdown mx-[24rpx] mt-[16rpx]">
        <view v-if="isProfileStyle" class="profile-countdown-card">
            <text class="profile-countdown-label">{{ content.title || '婚礼倒计时' }}</text>
            <text class="profile-countdown-value">{{ dayText }}</text>
            <text class="profile-countdown-desc">
                {{ content.desc || '摄影、主持、场地档期已锁定，跟妆和花艺待确认。' }}
            </text>
        </view>

        <view v-else class="default-countdown-card">
            <text class="default-countdown-title">{{ content.title || '距离婚礼还有' }}</text>
            <view class="default-countdown-grid">
                <view class="default-countdown-item">
                    <text class="default-countdown-number">{{ countdown.days }}</text>
                    <text class="default-countdown-unit">天</text>
                </view>
                <view class="default-countdown-item">
                    <text class="default-countdown-number">{{ countdown.hours }}</text>
                    <text class="default-countdown-unit">时</text>
                </view>
                <view class="default-countdown-item">
                    <text class="default-countdown-number">{{ countdown.minutes }}</text>
                    <text class="default-countdown-unit">分</text>
                </view>
                <view class="default-countdown-item">
                    <text class="default-countdown-number">{{ countdown.seconds }}</text>
                    <text class="default-countdown-unit">秒</text>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getUserWeddingDate } from '@/api/user'
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

const isProfileStyle = computed(() => Number(props.content?.style || 0) === 4)
const dayText = computed(() => `${Math.max(Number(countdown.value.days || 0), 0)} DAY`)

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

const calculateCountdown = () => {
    if (!weddingDate.value) return

    const now = Date.now()
    const target = weddingDate.value.getTime()
    const diff = target - now

    if (diff <= 0) {
        countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0 }
        if (timer) {
            clearInterval(timer)
            timer = null
        }
        return
    }

    countdown.value.days = Math.floor(diff / (1000 * 60 * 60 * 24))
    countdown.value.hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
    countdown.value.minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
    countdown.value.seconds = Math.floor((diff % (1000 * 60)) / 1000)
}

const loadWeddingDate = async () => {
    if (!props.isLogin) {
        resetCountdownState()
        if (timer) {
            clearInterval(timer)
            timer = null
        }
        return
    }

    try {
        const result = await getUserWeddingDate()
        if (!result?.has_order || !result?.wedding_date) {
            resetCountdownState()
            return
        }

        hasWedding.value = true
        weddingDate.value = new Date(result.wedding_date)

        if (Number.isFinite(Number(result.days_remaining))) {
            countdown.value.days = Math.max(Number(result.days_remaining || 0), 0)
        }
        calculateCountdown()

        if (!timer) {
            timer = setInterval(calculateCountdown, 1000)
        }
    } catch (error) {
        console.error('获取婚礼倒计时失败', error)
        resetCountdownState()
    }
}

watch(
    () => props.isLogin,
    () => {
        loadWeddingDate()
    },
    { immediate: true }
)

onUnmounted(() => {
    if (timer) {
        clearInterval(timer)
        timer = null
    }
})
</script>

<style scoped lang="scss">
.wedding-countdown {
    .profile-countdown-card {
        padding: 26rpx 24rpx 28rpx;
        border-radius: 28rpx;
        border: 1rpx solid #f4c7bf;
        background: linear-gradient(135deg, #fff5f1 0%, #fde7e1 100%);
        box-shadow: 0 18rpx 36rpx rgba(214, 185, 167, 0.14);
    }

    .profile-countdown-label {
        display: block;
        font-size: 22rpx;
        line-height: 1.35;
        font-weight: 700;
        color: #e85a4f;
    }

    .profile-countdown-value {
        display: block;
        margin-top: 10rpx;
        font-size: 64rpx;
        line-height: 1;
        font-weight: 700;
        color: #1e2432;
    }

    .profile-countdown-desc {
        display: block;
        margin-top: 12rpx;
        font-size: 24rpx;
        line-height: 1.6;
        color: #7f7b78;
    }

    .default-countdown-card {
        padding: 24rpx;
        border-radius: 24rpx;
        background: #ffffff;
        border: 1rpx solid #efe6e1;
    }

    .default-countdown-title {
        display: block;
        font-size: 28rpx;
        font-weight: 600;
        color: #1e2432;
    }

    .default-countdown-grid {
        margin-top: 16rpx;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12rpx;
    }

    .default-countdown-item {
        border-radius: 16rpx;
        padding: 14rpx 8rpx;
        text-align: center;
        background: #fff8f5;
    }

    .default-countdown-number {
        display: block;
        font-size: 32rpx;
        line-height: 1.25;
        font-weight: 700;
        color: #1e2432;
    }

    .default-countdown-unit {
        display: block;
        margin-top: 4rpx;
        font-size: 20rpx;
        color: #7f7b78;
    }
}
</style>
