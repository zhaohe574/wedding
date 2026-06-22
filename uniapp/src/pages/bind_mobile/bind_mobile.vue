<template>
    <page-meta :page-style="$theme.pageStyle" />
    <AuthPageShell
        navbarTitle="绑定手机号"
        navbarTitleAlign="center"
        navbarVariant="solid"
        navbarBgColor="#191713"
        navbarTextColor="#FFFDF8"
    >
        <template #hero>
            <view class="bind-hero">
                <view class="bind-hero__mark">
                    <BaseIcon name="phone" size="44" color="#D9BE82" />
                </view>
                <view class="bind-hero__content">
                    <text class="bind-hero__title">绑定手机号</text>
                    <text class="bind-hero__tag">微信授权</text>
                </view>
            </view>
        </template>

        <view class="bind-mobile-panel">
            <view
                class="bind-mobile-status"
                :class="{ 'bind-mobile-status--invalid': !isBindReady }"
            >
                <view class="bind-mobile-status__icon">
                    <BaseIcon
                        :name="isBindReady ? 'check-circle' : 'warning'"
                        size="30"
                        :color="isBindReady ? '#71806F' : '#9F7A2E'"
                    />
                </view>
                <text class="bind-mobile-status__text">
                    {{ bindStatusText }}
                </text>
            </view>

            <view class="bind-mobile-panel__head">
                <text class="bind-mobile-panel__title">确认当前微信手机号</text>
                <text class="bind-mobile-panel__desc">用于登录校验和服务联系</text>
            </view>

            <!-- #ifdef MP-WEIXIN -->
            <button
                v-if="canRequestPhone"
                class="phone-auth-button"
                open-type="getPhoneNumber"
                plain
                hover-class="phone-auth-button--hover"
                :disabled="binding"
                @getphonenumber="handleGetPhoneNumber"
            >
                <BaseIcon name="wechat-fill" size="34" color="#D9BE82" />
                <text class="phone-auth-button__text">{{ bindButtonText }}</text>
            </button>
            <view v-else class="bind-mobile-fallback">
                <text class="bind-mobile-fallback__text">请返回登录页重新获取授权凭证</text>
                <BaseButton block variant="light" size="md" label="返回登录" @click="goBackToLogin" />
            </view>
            <!-- #endif -->

            <!-- #ifndef MP-WEIXIN -->
            <view class="bind-mobile-fallback">
                <text class="bind-mobile-fallback__text">当前绑定方式仅支持微信小程序</text>
                <BaseButton block variant="light" size="md" label="返回登录" @click="goBackToLogin" />
            </view>
            <!-- #endif -->
        </view>
    </AuthPageShell>
</template>

<script setup lang="ts">
import { userMnpMobile } from '@/api/user'
import AuthPageShell from '@/components/business/AuthPageShell.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import { BACK_URL } from '@/enums/constantEnums'
import { useUserStore } from '@/stores/user'
import { useThemeStore } from '@/stores/theme'
import cache from '@/utils/cache'
import { showError, showSuccess } from '@/utils/feedback'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { computed, ref } from 'vue'

const $theme = useThemeStore()
const userStore = useUserStore()

const DEFAULT_BIND_SUCCESS_URL = '/pages/user/user'
const TABBAR_PATHS = new Set(['/pages/index/index', '/pages/dynamic/dynamic', '/pages/user/user'])
const INVALID_BACK_PATHS = new Set(['/pages/login/login', '/pages/bind_mobile/bind_mobile'])

const binding = ref(false)
const isMpWeixin = ref(false)
// #ifdef MP-WEIXIN
isMpWeixin.value = true
// #endif

const restoreTempToken = (token?: unknown) => {
    const queryToken = String(token || '').trim()
    if (queryToken) {
        return userStore.setTemToken(queryToken)
    }

    return userStore.restoreTemToken()
}
const getTempToken = () => userStore.temToken || restoreTempToken()

restoreTempToken()

const canRequestPhone = computed(() => !!userStore.temToken)
const isBindReady = computed(() => isMpWeixin.value && canRequestPhone.value)
const bindStatusText = computed(() => {
    if (!isMpWeixin.value) return '仅支持微信小程序'
    return canRequestPhone.value ? '登录凭证已就绪' : '登录状态已失效'
})
const bindButtonText = computed(() => (binding.value ? '绑定中...' : '微信授权绑定'))

const normalizePagePath = (url: string) => {
    const path = String(url || '').split('?')[0]
    return path.startsWith('/') ? path : `/${path}`
}

const redirectAfterBindMobile = () => {
    const backUrl = cache.get(BACK_URL)
    if (!backUrl) {
        uni.switchTab({ url: DEFAULT_BIND_SUCCESS_URL })
        return
    }

    cache.remove(BACK_URL)

    const pagePath = normalizePagePath(backUrl)
    if (INVALID_BACK_PATHS.has(pagePath)) {
        uni.switchTab({ url: DEFAULT_BIND_SUCCESS_URL })
        return
    }

    if (TABBAR_PATHS.has(pagePath)) {
        uni.switchTab({ url: pagePath })
        return
    }

    uni.redirectTo({
        url: backUrl,
        fail: () => uni.reLaunch({ url: backUrl })
    })
}

const resolveErrorMessage = (error: unknown, fallback = '绑定失败') => {
    if (typeof error === 'string' && error.trim()) {
        return error
    }
    if (error && typeof error === 'object') {
        const value =
            (error as { msg?: unknown; message?: unknown }).msg ??
            (error as { message?: unknown }).message

        if (typeof value === 'string' && value.trim()) {
            return value
        }
    }

    return fallback
}

const finishLoginAfterBind = async (token: string) => {
    if (!token) {
        showError('登录状态已失效，请重新登录')
        goBackToLogin()
        return
    }

    userStore.login(token)
    await userStore.getUser()
    userStore.clearTemToken()
    showSuccess('绑定成功')
    redirectAfterBindMobile()
}

const handleGetPhoneNumber = async (event: any) => {
    if (binding.value) return
    const tempToken = getTempToken()
    if (!tempToken) {
        showError('登录状态已失效，请重新登录')
        goBackToLogin()
        return
    }

    const detail = event?.detail || {}
    const code = String(detail.code || '').trim()
    if (!code) {
        const errMsg = String(detail.errMsg || '')
        if (errMsg && !errMsg.includes(':ok')) {
            showError('未授权获取手机号')
        } else {
            showError('获取手机号失败，请重试')
        }
        return
    }

    try {
        binding.value = true
        await userMnpMobile({ code }, { token: tempToken })
        await finishLoginAfterBind(tempToken)
    } catch (error) {
        showError(resolveErrorMessage(error, '绑定失败'))
    } finally {
        binding.value = false
    }
}

const goBackToLogin = () => {
    userStore.clearTemToken()
    uni.redirectTo({
        url: '/pages/login/login',
        fail: () => uni.reLaunch({ url: '/pages/login/login' })
    })
}

onLoad((options: Record<string, any> = {}) => {
    restoreTempToken(options.temp_token)
})

onShow(() => {
    const tempToken = restoreTempToken()
    if (userStore.isLogin && !tempToken) {
        uni.switchTab({ url: DEFAULT_BIND_SUCCESS_URL })
    }
})
</script>

<style lang="scss" scoped>
.bind-hero {
    display: flex;
    align-items: center;
    gap: 20rpx;
}

.bind-hero__mark {
    flex-shrink: 0;
    width: 104rpx;
    height: 104rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 28rpx;
    background: var(--wm-color-primary, #191713);
    border: 1rpx solid var(--wm-color-champagne, #d9be82);
    box-shadow: 0 18rpx 38rpx rgba(25, 23, 19, 0.18);
}

.bind-hero__content {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 16rpx;
}

.bind-hero__title {
    flex: 1;
    min-width: 0;
    display: block;
    font-size: 42rpx;
    font-weight: 900;
    line-height: 1.16;
    color: var(--wm-text-primary, #191713);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.bind-hero__tag {
    flex-shrink: 0;
    padding: 8rpx 18rpx;
    border-radius: 999rpx;
    border: 1rpx solid var(--wm-color-champagne, #d9be82);
    background: var(--wm-color-gold-soft, #f1e5c8);
    font-size: 22rpx;
    font-weight: 900;
    line-height: 1;
    color: var(--wm-text-primary, #191713);
}

.bind-mobile-panel {
    display: flex;
    flex-direction: column;
    gap: 22rpx;
}

.bind-mobile-status {
    min-height: 76rpx;
    display: flex;
    align-items: center;
    gap: 14rpx;
    padding: 16rpx 18rpx;
    border-radius: 26rpx;
    background: var(--wm-color-sage-soft, #e8efe6);
    border: 1rpx solid rgba(113, 128, 111, 0.28);
    box-sizing: border-box;
}

.bind-mobile-status--invalid {
    background: var(--wm-color-warning-soft, #f1e5c8);
    border-color: rgba(217, 190, 130, 0.72);
}

.bind-mobile-status__icon {
    flex-shrink: 0;
    width: 48rpx;
    height: 48rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 18rpx;
    background: rgba(255, 253, 248, 0.78);
}

.bind-mobile-status__text {
    flex: 1;
    min-width: 0;
    font-size: 25rpx;
    font-weight: 900;
    line-height: 1.35;
    color: var(--wm-text-primary, #191713);
}

.bind-mobile-panel__head {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.bind-mobile-panel__title {
    font-size: 34rpx;
    font-weight: 900;
    line-height: 1.25;
    color: var(--wm-text-primary, #191713);
}

.bind-mobile-panel__desc {
    font-size: 24rpx;
    font-weight: 700;
    line-height: 1.45;
    color: var(--wm-text-secondary, #665e52);
}

.phone-auth-button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 104rpx;
    margin: 6rpx 0 0;
    padding: 0 40rpx;
    border-radius: 999rpx;
    border: 1rpx solid var(--wm-color-champagne, #d9be82);
    background: var(--wm-color-primary, #191713);
    box-shadow: var(--wm-shadow-action, 0 20rpx 44rpx rgba(74, 43, 24, 0.18));
    box-sizing: border-box;
    line-height: 104rpx;
    letter-spacing: 0;
    color: var(--wm-text-inverse, #fffdf8);
    gap: 14rpx;
}

.phone-auth-button::after {
    border: none;
}

.phone-auth-button__text {
    display: block;
    font-size: 28rpx;
    font-weight: 900;
    line-height: 104rpx;
    letter-spacing: 0;
    color: var(--wm-text-inverse, #fffdf8);
}

.phone-auth-button[disabled] {
    opacity: 0.52;
    box-shadow: none;
}

.phone-auth-button--hover {
    transform: translateY(2rpx) scale(0.99);
    box-shadow: 0 10rpx 24rpx rgba(74, 43, 24, 0.14);
}

.bind-mobile-fallback {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 18rpx;
}

.bind-mobile-fallback__text {
    display: block;
    padding: 20rpx 22rpx;
    border-radius: 24rpx;
    background: var(--wm-color-bg-soft, #faf6ee);
    border: 1rpx solid var(--wm-color-border, #d8c9ad);
    font-size: 24rpx;
    font-weight: 700;
    line-height: 1.45;
    text-align: center;
    color: var(--wm-text-secondary, #665e52);
}

@media (max-width: 360px) {
    .bind-hero {
        gap: 16rpx;
    }

    .bind-hero__mark {
        width: 92rpx;
        height: 92rpx;
        border-radius: 24rpx;
    }

    .bind-hero__title {
        font-size: 36rpx;
    }

    .phone-auth-button {
        height: 98rpx;
        line-height: 98rpx;
    }

    .phone-auth-button__text {
        font-size: 27rpx;
        line-height: 98rpx;
    }
}
</style>
