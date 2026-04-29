<template>
    <view class="splash-page" :style="pageStyle">
        <image
            v-if="displayConfig.image"
            class="splash-page__image"
            :src="displayImage"
            mode="aspectFill"
        />
        <view v-else class="splash-page__fallback" />
        <view class="splash-page__content">
            <view v-if="displayConfig.logoImage" class="splash-page__logo-wrap">
                <image
                    class="splash-page__logo"
                    :src="displayLogoImage"
                    mode="widthFix"
                />
            </view>
            <view class="splash-page__spacer" />
            <button
                class="splash-page__button"
                hover-class="splash-page__button--hover"
                @tap="enterHome"
            >
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
    fetchSplashConfigSafely,
    getDefaultSplashConfig,
    armSplashHomeBypass,
    markSplashShown,
    shouldShowSplash,
    SPLASH_HOME_PATH,
    type SplashAdConfig
} from '@/utils/splash'

const appStore = useAppStore()
const displayConfig = ref<SplashAdConfig>(getDefaultSplashConfig())
const countdown = ref(0)
let timer: ReturnType<typeof setInterval> | null = null
let entered = false

const displayImage = computed(() => appStore.getImageUrl(displayConfig.value.image))
const displayLogoImage = computed(() => appStore.getImageUrl(displayConfig.value.logoImage))
const pageStyle = computed(() => ({
    backgroundColor: '#000000'
}))
const buttonLabel = computed(() => {
    const suffix = countdown.value > 0 ? ` ${countdown.value}s` : ''
    return `${displayConfig.value.buttonText || '点击进入'}${suffix}`
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
    armSplashHomeBypass()
    uni.switchTab({
        url: SPLASH_HOME_PATH,
        fail: () => {
            uni.reLaunch({ url: SPLASH_HOME_PATH })
        }
    })
}

const startCountdown = () => {
    countdown.value = displayConfig.value.autoSeconds
    timer = setInterval(() => {
        countdown.value -= 1
        if (countdown.value <= 0) {
            enterHome()
        }
    }, 1000)
}

onLoad(async (query = {}) => {
    try {
        const config = await fetchSplashConfigSafely()
        displayConfig.value = config

        if (!shouldShowSplash(config, query as Record<string, any>)) {
            enterHome()
            return
        }

        markSplashShown(config.frequency)
        void appStore.getConfig().catch(() => ({}))
        if (config.autoEnterEnabled) {
            startCountdown()
        }
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

.splash-page__content {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100vh;
    padding: 210rpx 48rpx calc(88rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;
}

.splash-page__logo-wrap {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 260rpx;
    min-height: 168rpx;
}

.splash-page__logo {
    display: block;
    width: 260rpx;
    max-height: 180rpx;
    animation: splash-logo-breathe 2.35s ease-in-out infinite;
    transform-origin: center center;
    will-change: transform;
}

.splash-page__spacer {
    flex: 1;
    min-height: 48rpx;
}

.splash-page__button {
    width: 224rpx;
    height: 72rpx;
    margin: 0;
    padding: 0;
    border: 2rpx solid rgba(255, 255, 255, 0.72);
    border-radius: 999rpx;
    background: rgba(156, 158, 166, 0.78);
    color: #ffffff;
    font-size: 28rpx;
    font-weight: 600;
    line-height: 72rpx;
    letter-spacing: 0;
    box-shadow:
        inset 0 2rpx 0 rgba(255, 255, 255, 0.5),
        0 12rpx 28rpx rgba(0, 0, 0, 0.24);
    animation: splash-button-breathe 2.4s ease-in-out infinite;
    transform-origin: center center;
    -webkit-backdrop-filter: blur(10rpx);
    backdrop-filter: blur(10rpx);
}

.splash-page__button::after {
    border: 0;
}

.splash-page__button--hover {
    opacity: 0.88;
}

@keyframes splash-logo-breathe {
    0%,
    100% {
        opacity: 0.96;
        transform: scale(1);
    }

    50% {
        opacity: 1;
        transform: scale(1.08);
    }
}

@keyframes splash-button-breathe {
    0%,
    100% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.045);
    }
}
</style>
