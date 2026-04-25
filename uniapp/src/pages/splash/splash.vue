<template>
    <view class="splash-page" :style="pageStyle">
        <image
            v-if="displayConfig.image"
            class="splash-page__image"
            :src="displayImage"
            mode="aspectFill"
        />
        <view v-else class="splash-page__fallback" />
        <view class="splash-page__scrim" />
        <view class="splash-page__content">
            <view class="splash-page__copy">
                <text v-if="displayConfig.title" class="splash-page__title">{{ displayConfig.title }}</text>
                <text v-if="displayConfig.subtitle" class="splash-page__subtitle">{{ displayConfig.subtitle }}</text>
            </view>
            <button class="splash-page__button" hover-class="splash-page__button--hover" @tap="enterHome">
                {{ buttonLabel }}
            </button>
        </view>
    </view>
</template>

<script setup lang="ts">
import { onLoad, onUnload } from '@dcloudio/uni-app'
import { computed, ref } from 'vue'
import { useAppStore } from '@/stores/app'
import {
    fetchSplashConfig,
    markSplashShown,
    shouldShowSplash,
    SPLASH_HOME_PATH,
    type SplashAdConfig
} from '@/utils/splash'

const appStore = useAppStore()
const displayConfig = ref<SplashAdConfig>({
    enabled: false,
    image: '',
    title: '',
    subtitle: '',
    buttonText: '进入首页',
    duration: 3,
    frequency: 'once_day',
    backgroundColor: '#000000',
    textColor: '#ffffff'
})
const countdown = ref(0)
let timer: ReturnType<typeof setInterval> | null = null
let entered = false

const displayImage = computed(() => appStore.getImageUrl(displayConfig.value.image))
const pageStyle = computed(() => ({
    backgroundColor: displayConfig.value.backgroundColor,
    color: displayConfig.value.textColor
}))
const buttonLabel = computed(() => {
    const suffix = countdown.value > 0 ? ` ${countdown.value}s` : ''
    return `${displayConfig.value.buttonText || '进入首页'}${suffix}`
})

const clearCountdown = () => {
    if (!timer) return
    clearInterval(timer)
    timer = null
}

const enterHome = () => {
    if (entered) return
    entered = true
    clearCountdown()
    uni.reLaunch({ url: SPLASH_HOME_PATH })
}

const startCountdown = () => {
    countdown.value = displayConfig.value.duration
    timer = setInterval(() => {
        countdown.value -= 1
        if (countdown.value <= 0) {
            enterHome()
        }
    }, 1000)
}

onLoad(async (query = {}) => {
    try {
        await appStore.getConfig().catch(() => ({}))
        const config = await fetchSplashConfig()
        displayConfig.value = config

        if (!shouldShowSplash(config, query as Record<string, any>)) {
            enterHome()
            return
        }

        markSplashShown(config.frequency)
        startCountdown()
    } catch (error) {
        console.error('开屏广告加载失败', error)
        enterHome()
    }
})

onUnload(() => {
    clearCountdown()
})
</script>

<style lang="scss" scoped>
.splash-page {
    position: relative;
    width: 100vw;
    min-height: 100vh;
    overflow: hidden;
}

.splash-page__image,
.splash-page__fallback {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}

.splash-page__fallback {
    background: linear-gradient(180deg, #111111 0%, #34291d 100%);
}

.splash-page__scrim {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0, 0, 0, 0.05) 0%, rgba(0, 0, 0, 0.52) 100%);
}

.splash-page__content {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    min-height: 100vh;
    padding: 96rpx 48rpx calc(72rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;
}

.splash-page__copy {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    margin-bottom: 44rpx;
}

.splash-page__title {
    font-size: 48rpx;
    font-weight: 600;
    line-height: 1.25;
}

.splash-page__subtitle {
    font-size: 28rpx;
    line-height: 1.6;
    opacity: 0.86;
}

.splash-page__button {
    width: 100%;
    height: 88rpx;
    border: 0;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.94);
    color: #111111;
    font-size: 30rpx;
    font-weight: 600;
    line-height: 88rpx;
}

.splash-page__button::after {
    border: 0;
}

.splash-page__button--hover {
    opacity: 0.88;
}
</style>
