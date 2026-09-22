<template>
    <page-meta :page-style="$theme.pageStyle" />
    <AuthPageShell
        navbarTitle=""
        navbarTitleAlign="center"
        navbarVariant="transparent"
        navbarBgColor="transparent"
        navbarTextColor="#191713"
    >
        <template #hero>
            <view class="auth-hero">
                <view class="auth-hero__emblem">
                    <image
                        v-if="appStore.getWebsiteConfig.shop_logo"
                        :src="appStore.getWebsiteConfig.shop_logo"
                        mode="aspectFit"
                        class="auth-hero__logo"
                    />
                    <view v-else class="auth-hero__logo-fallback">
                        <text>{{ (websiteConfig.shop_name || '婚礼').slice(0, 2) }}</text>
                    </view>
                </view>
                <text class="auth-hero__brand-name">{{ heroTitle }}</text>
                <view class="auth-hero__tagline">
                    <text class="auth-hero__tagline-symbol">✦</text>
                    <text class="auth-hero__tagline-text">婚礼纪事 · 尊享全流程管家服务</text>
                    <text class="auth-hero__tagline-symbol">✦</text>
                </view>
            </view>
        </template>

        <view class="auth-card-body">
            <!-- Quick WeChat Login -->
            <view class="auth-quick-login">
                <view class="auth-quick-login__header">
                    <text class="auth-quick-login__title">欢迎开启婚礼纪事</text>
                    <text class="auth-quick-login__subtitle">一键授权快速开启您的专属婚礼服务</text>
                </view>

                <view
                    class="auth-wechat-btn"
                    hover-class="auth-wechat-btn--active"
                    @click="wxLogin"
                >
                    <view class="auth-wechat-btn__icon-shell">
                        <BaseIcon name="wechat-fill" size="38" color="#D9BE82" />
                    </view>
                    <text class="auth-wechat-btn__label">微信一键快速登录</text>
                    <BaseIcon name="right" size="24" color="#D9BE82" />
                </view>
            </view>

            <!-- Agreement Panel -->
            <view v-if="isOpenAgreement" class="agreement-panel">
                <tn-checkbox v-model="isCheckAgreement" shape="round" active-color="#B8954A">
                    <view class="agreement-panel__text">
                        <text>我已阅读并同意</text>
                        <text class="agreement-panel__link" @click.stop="openAgreement('service')">
                            《服务协议》
                        </text>
                        <text class="agreement-panel__conjunction">与</text>
                        <text class="agreement-panel__link" @click.stop="openAgreement('privacy')">
                            《隐私协议》
                        </text>
                    </view>
                </tn-checkbox>
            </view>
        </view>

        <template #overlay>
            <BaseOverlayMask :show="showAgreementPopup" :closeable="false" />
            <tn-popup
                v-model="showAgreementPopup"
                open-direction="center"
                :radius="32"
                :overlay="false"
                :overlay-closeable="false"
            >
                <view class="agreement-popup">
                    <view class="agreement-popup__badge">
                        <BaseIcon name="shield-check" size="44" color="#D9BE82" />
                    </view>
                    <text class="agreement-popup__title">服务与隐私政策提示</text>
                    <text class="agreement-popup__desc">
                        为保障您的合法权益与数据隐私，在继续前请阅读并同意以下条款：
                    </text>
                    <view class="agreement-popup__list">
                        <view class="agreement-popup__item" @click="openAgreement('service')">
                            <view class="agreement-popup__item-left">
                                <BaseIcon name="file-text" size="28" color="#9A6B35" />
                                <text class="agreement-popup__item-name">《服务协议》</text>
                            </view>
                            <BaseIcon name="right" size="22" color="#B8954A" />
                        </view>
                        <view class="agreement-popup__item" @click="openAgreement('privacy')">
                            <view class="agreement-popup__item-left">
                                <BaseIcon name="lock" size="28" color="#9A6B35" />
                                <text class="agreement-popup__item-name">《隐私协议》</text>
                            </view>
                            <BaseIcon name="right" size="22" color="#B8954A" />
                        </view>
                    </view>
                    <view class="agreement-popup__actions">
                        <view
                            class="agreement-popup__action agreement-popup__action--cancel"
                            @click="closeAgreementPopup"
                        >
                            暂不同意
                        </view>
                        <view
                            class="agreement-popup__action agreement-popup__action--confirm"
                            @click="confirmAgreement"
                        >
                            同意并继续
                        </view>
                    </view>
                </view>
            </tn-popup>

            <mplogin-popup
                v-model:show="showLoginPopup"
                :logo="websiteConfig.shop_logo"
                :title="websiteConfig.shop_name"
                @update="handleUpdateUser"
            />
        </template>
    </AuthPageShell>
</template>

<script setup lang="ts">
import { remindBeforeOaAction, shouldRemindAfterLogin } from '@/utils/oa-reminder'
import AuthPageShell from '@/components/business/AuthPageShell.vue'
import { login, mnpLogin, updateUser } from '@/api/account'
import { smsSend } from '@/api/app'
import { SMSEnum } from '@/enums/appEnums'
import { BACK_URL } from '@/enums/constantEnums'
import { useLockFn } from '@/hooks/useLockFn'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { useThemeStore } from '@/stores/theme'
import { useRouter, useRoute } from 'uniapp-router-next'
import cache from '@/utils/cache'
import { getOaInvitation, OA_BINDING_PATH } from '@/utils/oa-invitation'
import { showError, showSuccess } from '@/utils/feedback'



import { onLoad } from '@dcloudio/uni-app'
import { computed, reactive, ref, watch } from 'vue'

enum LoginWayEnum {
    ACCOUNT = 1,
    MOBILE = 2
}

const isWeixin = ref(true)
const isMpWeixinPlatform = ref(false)

isMpWeixinPlatform.value = true






const route = useRoute()
const router = useRouter()
const userStore = useUserStore()
const appStore = useAppStore()
const themeStore = useThemeStore()
const $theme = themeStore
const codeTips = ref('获取验证码')
const canGetCode = ref(true)
const showLoginPopup = ref(false)
const showAgreementPopup = ref(false)
const isCheckAgreement = ref(false)

const formData = reactive({
    scene: 1,
    account: '',
    password: '',
    code: ''
})
const phoneLogin = ref(false)
const loginData = ref()

const primaryColor = computed(() => themeStore.primaryColor)

const resolveLoginError = (error: unknown, fallback = '操作失败') => {
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

const resolveLoginToken = (data: any) => String(data?.token || '').trim()

const navigateToBindMobile = (token: string) => {
    const tempToken = encodeURIComponent(token)
    router.navigateTo(`/pages/bind_mobile/bind_mobile?temp_token=${tempToken}`)
}

const startCodeCountdown = () => {
    let seconds = 60
    canGetCode.value = false
    codeTips.value = `${seconds}秒`

    const timer = setInterval(() => {
        seconds--
        if (seconds > 0) {
            codeTips.value = `${seconds}秒`
        } else {
            clearInterval(timer)
            codeTips.value = '获取验证码'
            canGetCode.value = true
        }
    }, 1000)
}

const websiteConfig = computed(() => appStore.getWebsiteConfig)

const heroTitle = computed(() => String(websiteConfig.value.shop_name || '').trim() || '登录')

const currentLoginTitle = computed(() =>
    formData.scene == LoginWayEnum.ACCOUNT ? '账号密码登录' : '验证码登录'
)

const sendSms = async () => {
    if (!formData.account) return
    if (canGetCode.value) {
        await smsSend({
            scene: SMSEnum.LOGIN,
            mobile: formData.account
        })
        showSuccess('发送成功')
        startCodeCountdown()
    }
}

const changeLoginWay = (way: LoginWayEnum) => {
    formData.scene = way
}

const includeLoginWay = (way: LoginWayEnum) => {
    return appStore.getLoginConfig.login_way?.includes(String(way))
}

const hasAccountLogin = computed(() => includeLoginWay(LoginWayEnum.ACCOUNT))
const hasMobileLogin = computed(() => includeLoginWay(LoginWayEnum.MOBILE))

const inWxAuth = computed(() => {
    return appStore.getLoginConfig.wechat_auth
})

const isOpenAgreement = computed(() => appStore.getLoginConfig.login_agreement == 1)

const isOpenOtherAuth = computed(() => appStore.getLoginConfig.third_auth == 1)
const isForceBindMobile = computed(() => appStore.getLoginConfig.coerce_mobile == 1)
const shouldForceBindMobile = computed(() => isMpWeixinPlatform.value && isForceBindMobile.value)
const showWechatLoginEntry = computed(
    () => isOpenOtherAuth.value && isWeixin.value && inWxAuth.value
)
const isMpWechatOnlyMode = computed(() => true)
const showLocalLoginEntry = computed(() => false)
const showLocalLoginForm = computed(() => false)
const canShowLoginMethodList = computed(() => true)
const showRegisterEntry = computed(() => false)
const localLoginEntryText = computed(() => {
    if (hasAccountLogin.value && hasMobileLogin.value) {
        return '手机号 / 账号登录'
    }

    if (hasAccountLogin.value) {
        return '账号密码登录'
    }

    if (hasMobileLogin.value) {
        return '手机号快捷登录'
    }

    return '其他方式登录'
})

type AgreementConfirmHandler = () => void | Promise<void>
type AgreementType = 'service' | 'privacy'

const pendingAgreementHandler = ref<AgreementConfirmHandler | null>(null)

const showAgreementModal = (onConfirm?: AgreementConfirmHandler) => {
    pendingAgreementHandler.value = onConfirm ?? null
    showAgreementPopup.value = true
}

const closeAgreementPopup = () => {
    showAgreementPopup.value = false
    pendingAgreementHandler.value = null
}

const confirmAgreement = async () => {
    const handler = pendingAgreementHandler.value
    isCheckAgreement.value = true
    showAgreementPopup.value = false
    pendingAgreementHandler.value = null
    await handler?.()
}

const openAgreement = (type: AgreementType) => {
    router.navigateTo(`/packages/pages/agreement/agreement?type=${type}`)
}

const loginFun = async () => {
    if (!isCheckAgreement.value && isOpenAgreement.value) {
        showAgreementModal(loginFun)
        return
    }
    if (formData.scene == LoginWayEnum.ACCOUNT) {
        if (!formData.account) return showError('请输入账号/手机号码')
        if (!formData.password) return showError('请输入密码')
    }
    if (formData.scene == LoginWayEnum.MOBILE) {
        if (!formData.account) return showError('请输入手机号码')
        if (!formData.code) return showError('请输入验证码')
    }
    uni.showLoading({
        title: '请稍后...'
    })
    try {
        const data = await login(formData)
        loginHandle(data)
    } catch (error: any) {
        uni.hideLoading()
        showError(resolveLoginError(error, '登录失败'))
    }
}

const loginHandle = async (data: any) => {
    const token = resolveLoginToken(data)
    const mobile = data?.mobile
    if (!mobile && shouldForceBindMobile.value) {
        if (!token) {
            uni.hideLoading()
            showError('登录凭证缺失，请重新登录')
            return
        }
        userStore.setTemToken(token)
        navigateToBindMobile(token)
        uni.hideLoading()
        return
    }
    if (!token) {
        uni.hideLoading()
        showError('登录凭证缺失，请重新登录')
        return
    }
    userStore.login(token)
    await userStore.getUser()
    userStore.clearTemToken()
    showSuccess('登录成功')
    uni.hideLoading()
    if (getOaInvitation()) {
        cache.remove(BACK_URL)
        await router.redirectTo(OA_BINDING_PATH)
        return
    }
    if (shouldRemindAfterLogin() && !await remindBeforeOaAction()) return
    const pages = getCurrentPages()
    if (pages.length > 1) {
        const prevPage = pages[pages.length - 2]
        await router.navigateBack()
        // @ts-ignore
        const { onLoad, options } = prevPage
        onLoad && onLoad(options)
    } else if (cache.get(BACK_URL)) {
        try {
            router.redirectTo(cache.get(BACK_URL))
        } catch (error) {
            router.switchTab(cache.get(BACK_URL))
        }
    } else {
        router.reLaunch('/pages/index/index')
    }
    cache.remove(BACK_URL)
}

const { lockFn: handleLogin } = useLockFn(loginFun)


const wxLogin = async () => {
    if (!isCheckAgreement.value && isOpenAgreement.value) {
        showAgreementModal(wxLogin)
        return
    }


    uni.showLoading({
        title: '请稍后...'
    })
    try {
        const { code }: any = await uni.login({
            provider: 'weixin'
        })
        const data = await mnpLogin({
            code: code
        })
        loginData.value = data
        if (data.is_new_user) {
            const tempToken = resolveLoginToken(data)
            if (!tempToken) {
                uni.hideLoading()
                showError('登录凭证缺失，请重新登录')
                return
            }
            uni.hideLoading()
            userStore.setTemToken(tempToken)
            showLoginPopup.value = true
            return
        }
        loginHandle(data)
    } catch (error: any) {
        uni.hideLoading()
        showError(resolveLoginError(error, '登录失败'))
    }






}

const handleUpdateUser = async (value: any) => {
    const tempToken = userStore.temToken || userStore.restoreTemToken()
    if (!tempToken) {
        showError('登录状态已失效，请重新登录')
        showLoginPopup.value = false
        return
    }

    await updateUser(value, { token: tempToken })
    showLoginPopup.value = false
    loginHandle(loginData.value)
}

watch(
    () => appStore.getLoginConfig,
    (value) => {
        if (value.login_way?.length) {
            formData.scene = Number(value.login_way[0]) || 1
        }

        if (showWechatLoginEntry.value) {
            phoneLogin.value = false
        }
    },
    {
        immediate: true
    }
)

const DisableStyle = computed(() => {
    if (formData.scene == 1 && formData.account && formData.password) {
        return true
    } else if (formData.scene == 2 && formData.account && formData.code) {
        return true
    } else {
        return false
    }
})

const removeWxQuery = () => {
    const options = route.query
    if (options.code && options.state) {
        delete options.code
        delete options.state
        router.redirectTo({ path: route.path, query: options })
    }
}

onLoad(async () => {




















})
</script>

<style lang="scss" scoped>
/* Hero Section - Elegant Centered Emblem */
.auth-hero {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 10rpx 0 20rpx;
}

.auth-hero__emblem {
    width: 140rpx;
    height: 140rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 42rpx;
    background: #ffffff;
    border: 3rpx solid rgba(217, 190, 130, 0.85);
    box-shadow: 0 16rpx 36rpx rgba(184, 149, 74, 0.2);
    overflow: hidden;
}

.auth-hero__logo {
    width: 96rpx;
    height: 96rpx;
}

.auth-hero__logo-fallback {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40rpx;
    font-weight: 900;
    color: var(--wm-color-champagne-deep, #b8954a);
    letter-spacing: 2rpx;
}

.auth-hero__brand-name {
    margin-top: 24rpx;
    font-size: 44rpx;
    font-weight: 900;
    line-height: 1.25;
    color: var(--wm-text-primary, #191713);
    letter-spacing: 2rpx;
}

.auth-hero__tagline {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    margin-top: 14rpx;
    padding: 8rpx 24rpx;
    border-radius: 999rpx;
    background: rgba(217, 190, 130, 0.15);
    border: 1rpx solid rgba(217, 190, 130, 0.45);
}

.auth-hero__tagline-symbol {
    font-size: 18rpx;
    color: var(--wm-color-champagne-deep, #b8954a);
}

.auth-hero__tagline-text {
    font-size: 24rpx;
    font-weight: 700;
    color: #9a6b35;
    letter-spacing: 2rpx;
}

/* Card Body */
.auth-card-body {
    display: flex;
    flex-direction: column;
    gap: 28rpx;
}

/* Quick WeChat Login Mode */
.auth-quick-login {
    display: flex;
    flex-direction: column;
    gap: 26rpx;
}

.auth-quick-login__header {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 8rpx;
    padding-bottom: 6rpx;
}

.auth-quick-login__title {
    font-size: 34rpx;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
    letter-spacing: 1rpx;
}

.auth-quick-login__subtitle {
    font-size: 24rpx;
    color: var(--wm-text-secondary, #665e52);
}

/* WeChat Primary Hero CTA Button */
.auth-wechat-btn {
    height: 104rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 28rpx;
    border-radius: 52rpx;
    background: linear-gradient(135deg, #191713 0%, #2f281e 100%);
    border: 1.5rpx solid rgba(217, 190, 130, 0.6);
    box-shadow: 0 16rpx 36rpx rgba(25, 23, 19, 0.22);
    box-sizing: border-box;
    transition: transform 0.15s ease, opacity 0.15s ease;
}

.auth-wechat-btn--active {
    transform: scale(0.985);
    opacity: 0.94;
}

.auth-wechat-btn__icon-shell {
    width: 68rpx;
    height: 68rpx;
    border-radius: 34rpx;
    background: rgba(217, 190, 130, 0.18);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.auth-wechat-btn__label {
    flex: 1;
    text-align: center;
    font-size: 31rpx;
    font-weight: 800;
    color: #fffdf8;
    letter-spacing: 2rpx;
}

/* Divider */
.auth-divider {
    display: flex;
    align-items: center;
    gap: 20rpx;
    margin: 4rpx 0;
}

.auth-divider__line {
    flex: 1;
    height: 1rpx;
    background: rgba(217, 190, 130, 0.35);
}

.auth-divider__text {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-tertiary, #9a9388);
    white-space: nowrap;
}

/* Secondary Local Login Entry */
.auth-secondary-btn {
    height: 94rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 28rpx;
    border-radius: 47rpx;
    background: #faf6ee;
    border: 1.5rpx solid rgba(217, 190, 130, 0.7);
    box-shadow: 0 8rpx 20rpx rgba(74, 43, 24, 0.05);
    box-sizing: border-box;
    transition: transform 0.15s ease, background-color 0.15s ease;
}

.auth-secondary-btn--active {
    transform: scale(0.985);
    background: #f4ecdc;
}

.auth-secondary-btn__icon-shell {
    width: 58rpx;
    height: 58rpx;
    border-radius: 29rpx;
    background: rgba(217, 190, 130, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.auth-secondary-btn__label {
    flex: 1;
    text-align: center;
    font-size: 29rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #191713);
}

/* Local Login Form */
.auth-form {
    display: flex;
    flex-direction: column;
    gap: 26rpx;
}

.auth-form__top-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 52rpx;
    padding-bottom: 4rpx;
}

.auth-form__back {
    display: flex;
    align-items: center;
    gap: 6rpx;
    font-size: 26rpx;
    font-weight: 800;
    color: #9a6b35;
    padding: 6rpx 0;
}

.auth-form__title {
    font-size: 32rpx;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
    margin-left: auto;
}

/* Segmented Tabs */
.auth-segmented-tabs {
    display: flex;
    padding: 8rpx;
    border-radius: 24rpx;
    background: #faf6ee;
    border: 1.5rpx solid rgba(217, 190, 130, 0.5);
    gap: 8rpx;
}

.auth-segmented-tab {
    flex: 1;
    height: 72rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
    border-radius: 18rpx;
    font-size: 27rpx;
    font-weight: 700;
    color: #9a6b35;
    transition: all 0.2s ease;
}

.auth-segmented-tab--active {
    background: linear-gradient(135deg, #191713 0%, #2a251c 100%);
    color: #d9be82;
    box-shadow: 0 8rpx 18rpx rgba(25, 23, 19, 0.2);
}

.auth-form__group {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.auth-form__label-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.auth-form__label {
    font-size: 26rpx;
    font-weight: 800;
    color: #4a3c2c;
}

.auth-link-inline {
    font-size: 24rpx;
    font-weight: 800;
    color: #9a6b35;
}

/* Code button */
.auth-code-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    min-width: 144rpx;
    height: 56rpx;
    padding: 0 20rpx;
    box-sizing: border-box;
    border-radius: 999rpx;
    border: 1.5rpx solid rgba(217, 190, 130, 0.6);
    background: #faf6ee;
    font-size: 24rpx;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    color: var(--wm-text-tertiary, #9a9388);
    transition: all 0.2s ease;
}

.auth-code-btn--active {
    color: var(--wm-text-primary, #191713);
    border-color: var(--wm-color-champagne-deep, #b8954a);
    background: linear-gradient(135deg, #faf0d9 0%, #f5e5c0 100%);
    box-shadow: 0 4rpx 12rpx rgba(184, 149, 74, 0.2);
}

/* Agreement row */
.agreement-panel {
    display: flex;
    align-items: center;
    justify-content: center;
    padding-top: 6rpx;
}

.agreement-panel__text {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6rpx;
    font-size: 24rpx;
    line-height: 1.55;
    color: var(--wm-text-secondary, #665e52);
}

.agreement-panel__link {
    color: #9a6b35;
    font-weight: 800;
}

.agreement-panel__conjunction {
    color: var(--wm-text-secondary, #665e52);
}

/* Footer / Register */
.auth-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    font-size: 26rpx;
    padding: 8rpx 0;
}

.auth-footer__hint {
    color: var(--wm-text-secondary, #665e52);
}

.auth-footer__link {
    color: #9a6b35;
    font-weight: 900;
}

/* Agreement Popup */
.agreement-popup {
    width: 600rpx;
    padding: 44rpx 36rpx 36rpx;
    border-radius: 36rpx;
    background: var(--wm-color-bg-card, #fffdf8);
    border: 1.5rpx solid rgba(217, 190, 130, 0.6);
    box-shadow: 0 24rpx 60rpx rgba(74, 43, 24, 0.16);
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.agreement-popup__badge {
    width: 92rpx;
    height: 92rpx;
    border-radius: 46rpx;
    background: rgba(217, 190, 130, 0.16);
    border: 2rpx solid rgba(217, 190, 130, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20rpx;
}

.agreement-popup__title {
    font-size: 34rpx;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
    letter-spacing: 1rpx;
}

.agreement-popup__desc {
    margin-top: 14rpx;
    font-size: 25rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #665e52);
    text-align: center;
}

.agreement-popup__list {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    margin-top: 26rpx;
}

.agreement-popup__item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 22rpx 24rpx;
    border-radius: 20rpx;
    background: #faf6ee;
    border: 1rpx solid rgba(217, 190, 130, 0.45);
    transition: background-color 0.15s ease;
}

.agreement-popup__item:active {
    background: #f4ecdc;
}

.agreement-popup__item-left {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.agreement-popup__item-name {
    font-size: 27rpx;
    font-weight: 800;
    color: var(--wm-text-primary, #191713);
}

.agreement-popup__actions {
    width: 100%;
    display: flex;
    gap: 18rpx;
    margin-top: 34rpx;
}

.agreement-popup__action {
    flex: 1;
    height: 82rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    font-size: 28rpx;
    font-weight: 800;
    box-sizing: border-box;
    transition: transform 0.15s ease, opacity 0.15s ease;
}

.agreement-popup__action:active {
    transform: scale(0.985);
}

.agreement-popup__action--cancel {
    background: #faf6ee;
    color: var(--wm-text-secondary, #665e52);
    border: 1.5rpx solid rgba(217, 190, 130, 0.5);
}

.agreement-popup__action--confirm {
    background: linear-gradient(135deg, #191713 0%, #2a251c 100%);
    color: #fffdf8;
    border: 1.5rpx solid rgba(217, 190, 130, 0.6);
    box-shadow: 0 10rpx 24rpx rgba(25, 23, 19, 0.2);
}

@media (max-width: 360px) {
    .auth-hero {
        gap: 16rpx;
    }

    .auth-hero__brand {
        width: 92rpx;
        height: 92rpx;
        border-radius: 24rpx;
    }

    .auth-hero__title {
        font-size: 36rpx;
    }

    .auth-entry {
        min-height: 98rpx;
        padding: 18rpx 20rpx;
    }

    .auth-entry__icon {
        width: 60rpx;
        height: 60rpx;
        border-radius: 20rpx;
    }

    .auth-code-btn {
        min-width: 112rpx;
        padding: 0 12rpx;
        font-size: 22rpx;
    }
}
</style>
