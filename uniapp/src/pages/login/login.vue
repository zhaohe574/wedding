<template>
    <page-meta :page-style="themeStore.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="登录" />
        <view class="login-container">
        <!-- 背景装饰 -->
        <view class="bg-decoration">
            <view class="circle circle-1"></view>
            <view class="circle circle-2"></view>
            <view class="circle circle-3"></view>
        </view>

        <!-- 主内容区 -->
        <view class="login-content">
            <!-- Logo 区域 -->
            <view class="logo-section">
                <view class="logo-wrapper">
                    <image
                        :src="appStore.getWebsiteConfig.shop_logo"
                        mode="aspectFit"
                        class="logo-image"
                    />
                </view>
                <view class="welcome-text">欢迎回来</view>
                <view class="subtitle-text">登录您的账号继续使用</view>
            </view>

            <!-- 登录表单卡片 -->
            <view class="form-card glass-card">
                <block v-if="!phoneLogin">
                    <!-- 快捷登录按钮 -->
                    <view v-if="showWechatLoginEntry" class="quick-login-section">
                        <view
                            class="login-entry login-entry--wechat"
                            :style="wechatEntryStyle"
                            @click="wxLogin"
                            hover-class="login-entry--hover"
                        >
                            <view class="login-entry__surface">
                                <view class="login-entry__icon-pill" :style="wechatEntryIconPillStyle">
                                    <tn-icon name="wechat-fill" size="34" :color="$theme.btnColor" />
                                </view>
                                <view class="login-entry__content">
                                    <text class="login-entry__label" :style="{ color: $theme.btnColor }">
                                        微信一键登录
                                    </text>
                                </view>
                                <view class="login-entry__arrow">
                                    <tn-icon name="right" size="28" :color="$theme.btnColor" />
                                </view>
                            </view>
                        </view>
                    </view>

                    <view class="divider-section" v-if="showLoginDivider">
                        <view class="divider-line"></view>
                        <text class="divider-text">或</text>
                        <view class="divider-line"></view>
                    </view>

                    <view
                        v-if="showLocalLoginEntry"
                        class="login-entry login-entry--secondary"
                        :style="localEntryStyle"
                        @click="phoneLogin = !phoneLogin"
                        hover-class="login-entry--hover"
                    >
                        <view class="login-entry__surface login-entry__surface--secondary">
                            <view class="login-entry__icon-pill" :style="localEntryIconPillStyle">
                                <tn-icon name="phone" size="32" :color="primaryColor" />
                            </view>
                            <view class="login-entry__content">
                                <text class="login-entry__label" :style="{ color: primaryColor }">
                                    {{ localLoginEntryText }}
                                </text>
                            </view>
                            <view class="login-entry__arrow">
                                <tn-icon name="right" size="26" :color="primaryColor" />
                            </view>
                        </view>
                    </view>
                </block>

                <block v-if="showLocalLoginForm">
                    <!-- 登录方式标题 -->
                    <view class="form-title">
                        {{ formData.scene == LoginWayEnum.ACCOUNT ? '账号密码登录' : '验证码登录' }}
                    </view>

                    <!-- 密码登录 -->
                    <template
                        v-if="
                            formData.scene == LoginWayEnum.ACCOUNT &&
                            includeLoginWay(LoginWayEnum.ACCOUNT)
                        "
                    >
                        <view class="input-group">
                            <view class="input-wrapper">
                                <tn-icon name="user" size="36" color="#9ca3af" class="input-icon" />
                                <tn-input
                                    class="custom-input"
                                    v-model="formData.account"
                                    :border="false"
                                    placeholder="请输入账号/手机号"
                                />
                            </view>
                        </view>

                        <view class="input-group">
                            <view class="input-wrapper">
                                <tn-icon name="lock" size="36" color="#9ca3af" class="input-icon" />
                                <tn-input
                                    class="custom-input"
                                    v-model="formData.password"
                                    type="password"
                                    placeholder="请输入密码"
                                    :border="false"
                                />
                                <navigator url="/pages/forget_pwd/forget_pwd" hover-class="none">
                                    <view class="forgot-link">忘记?</view>
                                </navigator>
                            </view>
                        </view>
                    </template>

                    <!-- 验证码登录 -->
                    <template
                        v-if="
                            formData.scene == LoginWayEnum.MOBILE &&
                            includeLoginWay(LoginWayEnum.MOBILE)
                        "
                    >
                        <view class="input-group">
                            <view class="input-wrapper">
                                <tn-icon
                                    name="phone"
                                    size="36"
                                    color="#9ca3af"
                                    class="input-icon"
                                />
                                <tn-input
                                    class="custom-input"
                                    v-model="formData.account"
                                    :border="false"
                                    placeholder="请输入手机号码"
                                />
                            </view>
                        </view>

                        <view class="input-group">
                            <view class="input-wrapper">
                                <tn-icon
                                    name="shield-check"
                                    size="36"
                                    color="#9ca3af"
                                    class="input-icon"
                                />
                                <tn-input
                                    class="custom-input"
                                    v-model="formData.code"
                                    placeholder="请输入验证码"
                                    :border="false"
                                />
                                <view
                                    class="code-btn"
                                    :class="{ 'code-btn-active': canGetCode && formData.account }"
                                    @click="sendSms"
                                    hover-class="code-btn-hover"
                                >
                                    {{ codeTips }}
                                </view>
                            </view>
                        </view>
                    </template>

                    <!-- 协议勾选 -->
                    <view class="agreement-section" v-if="isOpenAgreement">
                        <tn-checkbox v-model="isCheckAgreement" shape="round">
                            <view class="agreement-text">
                                已阅读并同意
                                <navigator
                                    class="agreement-link"
                                    hover-class="none"
                                    url="/packages/pages/agreement/agreement?type=service"
                                    @click.stop
                                >
                                    《服务协议》
                                </navigator>
                                和
                                <navigator
                                    class="agreement-link"
                                    hover-class="none"
                                    url="/packages/pages/agreement/agreement?type=privacy"
                                    @click.stop
                                >
                                    《隐私协议》
                                </navigator>
                            </view>
                        </tn-checkbox>
                    </view>

                    <!-- 登录按钮 -->
                    <view class="login-btn-wrapper">
                        <view
                            class="btn-primary btn-login"
                            :class="{ 'btn-disabled': !DisableStyle }"
                            @click="handleLogin(formData.scene)"
                            hover-class="btn-hover"
                        >
                            <view class="btn-text">立即登录</view>
                        </view>
                    </view>

                    <!-- 底部操作 -->
                    <view class="bottom-actions">
                        <view class="action-item">
                            <text
                                class="action-link"
                                @click="changeLoginWay(LoginWayEnum.ACCOUNT)"
                                v-if="
                                    formData.scene == LoginWayEnum.MOBILE &&
                                    includeLoginWay(LoginWayEnum.ACCOUNT)
                                "
                            >
                                密码登录
                            </text>
                            <text
                                class="action-link"
                                @click="changeLoginWay(LoginWayEnum.MOBILE)"
                                v-if="
                                    formData.scene == LoginWayEnum.ACCOUNT &&
                                    includeLoginWay(LoginWayEnum.MOBILE)
                                "
                            >
                                验证码登录
                            </text>
                        </view>
                        <navigator v-if="showRegisterEntry" url="/pages/register/register" hover-class="none">
                            <view class="action-link">注册账号</view>
                        </navigator>
                    </view>
                </block>
            </view>
        </view>

        <!-- 协议弹框 -->
        <tn-popup
            v-model="showAgreementPopup"
            open-direction="center"
            :radius="24"
            :overlay-closeable="false"
        >
            <view class="agreement-popup">
                <view class="agreement-popup__title">温馨提示</view>
                <view class="agreement-popup__content">
                    请先阅读并同意
                    <text class="agreement-popup__link" @click="openAgreement('service')">
                        《服务协议》
                    </text>
                    和
                    <text class="agreement-popup__link" @click="openAgreement('privacy')">
                        《隐私协议》
                    </text>
                </view>
                <view class="agreement-popup__actions">
                    <view
                        class="agreement-popup__action agreement-popup__action--cancel"
                        hover-class="agreement-popup__action--hover"
                        @click="closeAgreementPopup"
                    >
                        取消
                    </view>
                    <view
                        class="agreement-popup__action agreement-popup__action--confirm"
                        :style="{
                            background: `linear-gradient(135deg, ${primaryColor} 0%, ${primaryColor} 100%)`
                        }"
                        hover-class="agreement-popup__action--hover"
                        @click="confirmAgreement"
                    >
                        同意
                    </view>
                </view>
            </view>
        </tn-popup>

        <!-- #ifdef MP-WEIXIN -->
        <mplogin-popup
            v-model:show="showLoginPopup"
            :logo="websiteConfig.shop_logo"
            :title="websiteConfig.shop_name"
            @update="handleUpdateUser"
        />
        <!--  #endif -->
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import PageShell from '@/components/base/PageShell.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import { login, mnpLogin, updateUser, OALogin } from '@/api/account'
import { smsSend } from '@/api/app'
import { SMSEnum } from '@/enums/appEnums'
import { BACK_URL } from '@/enums/constantEnums'
import { useLockFn } from '@/hooks/useLockFn'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { useThemeStore } from '@/stores/theme'
import { useRouter, useRoute } from 'uniapp-router-next'
import cache from '@/utils/cache'
import { isWeixinClient } from '@/utils/client'
import { alphaColor, shadeColor } from '@/utils/color'
// #ifdef H5
import wechatOa, { UrlScene } from '@/utils/wechat'
// #endif
import { onLoad, onShow } from '@dcloudio/uni-app'
import { computed, reactive, ref, shallowRef, watch } from 'vue'

enum LoginWayEnum {
    ACCOUNT = 1,
    MOBILE = 2
}

const isWeixin = ref(true)
const isMpWeixinPlatform = ref(false)
const isH5Platform = ref(false)
// #ifdef MP-WEIXIN
isMpWeixinPlatform.value = true
// #endif
// #ifdef H5
isH5Platform.value = true
isWeixin.value = isWeixinClient()
// #endif

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()
const appStore = useAppStore()
const themeStore = useThemeStore()
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

// 主题色
const primaryColor = computed(() => themeStore.primaryColor)
const wechatEntryStyle = computed(() => ({
    background: `linear-gradient(135deg, ${primaryColor.value} 0%, ${shadeColor(primaryColor.value, 0.2)} 100%)`,
    boxShadow: `0 8rpx 20rpx ${alphaColor(primaryColor.value, 0.22)}`,
    border: `1rpx solid ${alphaColor(primaryColor.value, 0.16)}`
}))
const wechatEntryIconPillStyle = computed(() => ({
    background: alphaColor('#ffffff', 0.18)
}))
const localEntryStyle = computed(() => ({
    background: alphaColor(primaryColor.value, 0.07),
    border: `2rpx solid ${alphaColor(primaryColor.value, 0.18)}`,
    boxShadow: `0 6rpx 16rpx ${alphaColor(primaryColor.value, 0.08)}`
}))
const localEntryIconPillStyle = computed(() => ({
    background: alphaColor(primaryColor.value, 0.12)
}))

// 验证码倒计时
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

const sendSms = async () => {
    if (!formData.account) return
    if (canGetCode.value) {
        await smsSend({
            scene: SMSEnum.LOGIN,
            mobile: formData.account
        })
        uni.$u.toast('发送成功')
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
const showWechatLoginEntry = computed(() => isOpenOtherAuth.value && isWeixin.value && inWxAuth.value)
const isMpWechatOnlyMode = computed(() => isMpWeixinPlatform.value && showWechatLoginEntry.value)
const showLocalLoginEntry = computed(() => !isMpWechatOnlyMode.value)
const showLocalLoginForm = computed(() => phoneLogin.value && showLocalLoginEntry.value)
const showLoginDivider = computed(() => showWechatLoginEntry.value && showLocalLoginEntry.value)
const showRegisterEntry = computed(() => !isMpWechatOnlyMode.value)
const localLoginEntryText = computed(() => {
    if (hasAccountLogin.value && hasMobileLogin.value) {
        return isH5Platform.value ? '账号/手机号登录' : '手机号登录'
    }

    if (hasAccountLogin.value) {
        return '账号密码登录'
    }

    if (hasMobileLogin.value) {
        return '手机号登录'
    }

    return '登录'
})

type AgreementConfirmHandler = () => void | Promise<void>
type AgreementType = 'service' | 'privacy'

const pendingAgreementHandler = ref<AgreementConfirmHandler | null>(null)

// 显示协议弹窗
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
        if (!formData.account) return uni.$u.toast('请输入账号/手机号码')
        if (!formData.password) return uni.$u.toast('请输入密码')
    }
    if (formData.scene == LoginWayEnum.MOBILE) {
        if (!formData.account) return uni.$u.toast('请输入手机号码')
        if (!formData.code) return uni.$u.toast('请输入验证码')
    }
    uni.showLoading({
        title: '请稍后...'
    })
    try {
        const data = await login(formData)
        loginHandle(data)
    } catch (error: any) {
        uni.hideLoading()
        uni.$u.toast(error)
    }
}

const loginHandle = async (data: any) => {
    const { token, mobile } = data
    if (!mobile && isForceBindMobile.value) {
        userStore.temToken = token
        router.navigateTo('/pages/bind_mobile/bind_mobile')
        uni.hideLoading()
        return
    }
    userStore.login(data.token)
    await userStore.getUser()
    uni.$u.toast('登录成功')
    uni.hideLoading()
    const pages = getCurrentPages()
    if (pages.length > 1) {
        const prevPage = pages[pages.length - 2]
        await router.navigateBack()
        // @ts-ignore
        const { onLoad, options } = prevPage
        // 刷新上一个页面
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

const oaLogin = async (options: any = { getUrl: true }) => {
    const { code, getUrl } = options
    if (getUrl) {
        await wechatOa.getUrl(UrlScene.LOGIN)
    } else {
        const data = await OALogin({
            code
        })
        return data
    }
    return Promise.reject()
}

const wxLogin = async () => {
    if (!isCheckAgreement.value && isOpenAgreement.value) {
        showAgreementModal(wxLogin)
        return
    }

    // #ifdef MP-WEIXIN

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
            uni.hideLoading()
            userStore.temToken = data.token
            showLoginPopup.value = true
            return
        }
        loginHandle(data)
    } catch (error: any) {
        uni.hideLoading()
        uni.$u.toast(error)
    }
    // #endif
    // #ifdef H5
    if (isWeixin.value) {
        oaLogin()
    }
    // #endif
}
const handleUpdateUser = async (value: any) => {
    await updateUser(value, { token: userStore.temToken })
    showLoginPopup.value = false
    loginHandle(loginData.value)
}

watch(
    () => appStore.getLoginConfig,
    (value) => {
        if (value.login_way?.length) {
            formData.scene = value.login_way[0]
        }

        if (isMpWechatOnlyMode.value) {
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
    //#ifdef H5
    const options = wechatOa.getAuthData()
    try {
        if (options.code && options.scene === UrlScene.LOGIN) {
            uni.showLoading({
                title: '请稍后...'
            })
            const data = await oaLogin(options)
            if (data) {
                loginData.value = data

                loginHandle(loginData.value)
            }
        }
    } catch (error) {
        removeWxQuery()
    } finally {
        uni.hideLoading()
        //清除保存的授权数据
        wechatOa.setAuthData()
    }
    //#endif
})
</script>

<style lang="scss" scoped>
page {
    height: 100%;
}

.login-container {
    min-height: 100vh;
    background: linear-gradient(180deg, var(--color-primary-light-9, #faf5ff) 0%, #ffffff 100%);
    position: relative;
    overflow: hidden;
}

/* 背景装饰圆圈 */
.bg-decoration {
    position: absolute;
    width: 100%;
    height: 100%;
    overflow: hidden;
    z-index: 0;

    .circle {
        position: absolute;
        border-radius: 50%;
        opacity: 0.15;
        animation: float 20s infinite ease-in-out;
    }

    .circle-1 {
        width: 400rpx;
        height: 400rpx;
        background: linear-gradient(
            135deg,
            var(--color-primary, #7c3aed),
            var(--color-primary-light-3, #a78bfa)
        );
        top: -100rpx;
        right: -100rpx;
        animation-delay: 0s;
    }

    .circle-2 {
        width: 300rpx;
        height: 300rpx;
        background: linear-gradient(
            135deg,
            var(--color-secondary, #ec4899),
            var(--color-secondary-light-3, #f9a8d4)
        );
        bottom: 100rpx;
        left: -80rpx;
        animation-delay: 5s;
    }

    .circle-3 {
        width: 200rpx;
        height: 200rpx;
        background: linear-gradient(
            135deg,
            var(--color-cta, #f97316),
            var(--color-cta-light-3, #fdba74)
        );
        top: 50%;
        right: 50rpx;
        animation-delay: 10s;
    }
}

@keyframes float {
    0%,
    100% {
        transform: translateY(0) scale(1);
    }
    50% {
        transform: translateY(-40rpx) scale(1.1);
    }
}

/* 主内容区 */
.login-content {
    position: relative;
    z-index: 1;
    padding: 60rpx 32rpx 40rpx;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* Logo 区域 */
.logo-section {
    text-align: center;
    margin-bottom: 56rpx;

    .logo-wrapper {
        display: inline-block;
        padding: 16rpx;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 50%;
        box-shadow: 0 8rpx 32rpx rgba(124, 58, 237, 0.2);
        margin-bottom: 28rpx;
    }

    .logo-image {
        width: 140rpx;
        height: 140rpx;
        border-radius: 50%;
        display: block;
    }

    .welcome-text {
        font-size: 44rpx;
        font-weight: 600;
        color: var(--color-primary, #7c3aed);
        margin-bottom: 16rpx;
        letter-spacing: 1rpx;
    }

    .subtitle-text {
        font-size: 28rpx;
        color: var(--color-content, #666666);
        font-weight: 400;
    }
}

/* 表单卡片 - 玻璃态效果 */
.form-card {
    background: rgba(255, 255, 255, 0.85);
    border-radius: 24rpx;
    padding: 40rpx 32rpx;
    box-shadow: 0 20rpx 60rpx rgba(124, 58, 237, 0.12), 0 8rpx 16rpx rgba(0, 0, 0, 0.04);
    backdrop-filter: blur(20rpx);
    border: 2rpx solid rgba(255, 255, 255, 0.3);
}

/* 快捷登录区域 */
.quick-login-section {
    margin-bottom: 16rpx;
}

/* 分割线 */
.divider-section {
    display: flex;
    align-items: center;
    margin: 24rpx 0;

    .divider-line {
        flex: 1;
        height: 2rpx;
        background: linear-gradient(to right, transparent, #e5e7eb, transparent);
    }

    .divider-text {
        padding: 0 20rpx;
        font-size: 24rpx;
        color: #9ca3af;
        font-weight: 500;
    }
}

.login-entry {
    min-height: 88rpx;
    padding: 4rpx 0;
    border-radius: 40rpx;
    box-sizing: border-box;
    transition: all 0.2s ease;

    &__surface {
        min-height: 80rpx;
        padding: 0 24rpx;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
        border-radius: 40rpx;
        box-sizing: border-box;

        &--secondary {
            background: transparent;
        }
    }

    &__icon-pill {
        width: 64rpx;
        height: 64rpx;
        border-radius: 999rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    &__content {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__label {
        font-size: 30rpx;
        line-height: 1.4;
        font-weight: 600;
        letter-spacing: 1rpx;
        text-align: center;
    }

    &__arrow {
        width: 36rpx;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex-shrink: 0;
    }

    &--hover {
        opacity: 0.96;
        transform: translateY(2rpx);
    }
}

/* 表单标题 */
.form-title {
    font-size: 34rpx;
    font-weight: 600;
    color: var(--color-primary, #7c3aed);
    margin-bottom: 24rpx;
    text-align: center;
}

/* 输入框组 */
.input-group {
    margin-bottom: 20rpx;

    .input-wrapper {
        display: flex;
        align-items: center;
        background: #f9fafb;
        border: 2rpx solid #e5e7eb;
        border-radius: 16rpx;
        padding: 0 24rpx;
        height: 88rpx;
        transition: all 0.2s ease;

        &:focus-within {
            border-color: var(--color-primary, #7c3aed);
            background: #ffffff;
            box-shadow: 0 0 0 6rpx rgba(124, 58, 237, 0.1);
        }

        .input-icon {
            margin-right: 16rpx;
        }

        .custom-input {
            flex: 1;
            font-size: 28rpx;
        }

        .forgot-link {
            font-size: 24rpx;
            color: var(--color-primary, #7c3aed);
            padding-left: 24rpx;
            border-left: 2rpx solid #e5e7eb;
            white-space: nowrap;
            font-weight: 500;
        }

        .code-btn {
            font-size: 24rpx;
            color: #9ca3af;
            padding-left: 24rpx;
            border-left: 2rpx solid #e5e7eb;
            white-space: nowrap;
            min-width: 160rpx;
            text-align: center;
            transition: all 0.2s ease;

            &.code-btn-active {
                color: var(--color-primary, #7c3aed);
                font-weight: 600;
                cursor: pointer;

                &:active {
                    color: var(--color-primary-dark-2, #6d28d9);
                }
            }
        }
    }
}

/* 协议区域 */
.agreement-section {
    margin: 20rpx 0;

    .agreement-text {
        font-size: 24rpx;
        color: var(--color-content, #666666);
        display: flex;
        flex-wrap: wrap;
        align-items: center;

        .agreement-link {
            color: var(--color-primary, #7c3aed);
            font-weight: 500;
            margin: 0 4rpx;
        }
    }
}

.agreement-popup {
    width: 580rpx;
    background: #ffffff;
    padding: 40rpx;

    &__title {
        font-size: 34rpx;
        font-weight: 600;
        color: #1f2937;
        text-align: center;
    }

    &__content {
        margin-top: 24rpx;
        font-size: 28rpx;
        line-height: 1.7;
        color: #4b5563;
        text-align: center;
    }

    &__link {
        color: var(--color-primary, #7c3aed);
        font-weight: 500;
        margin: 0 4rpx;
    }

    &__actions {
        display: flex;
        gap: 20rpx;
        margin-top: 36rpx;
    }

    &__action {
        flex: 1;
        height: 76rpx;
        border-radius: 999rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28rpx;
        font-weight: 500;

        &--cancel {
            color: #6b7280;
            background: #f3f4f6;
        }

        &--confirm {
            color: #ffffff;
        }

        &--hover {
            opacity: 0.92;
        }
    }
}

/* 按钮样式 */
.btn-primary {
    background: linear-gradient(
        135deg,
        var(--color-primary, #7c3aed) 0%,
        var(--color-primary-dark-2, #6d28d9) 100%
    );
    border-radius: 32rpx;
    height: 72rpx;
    padding: 0 32rpx;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 6rpx 16rpx rgba(124, 58, 237, 0.22);

    .btn-content {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12rpx;
    }

    .btn-text {
        font-size: 30rpx;
        color: #ffffff;
        font-weight: 600;
        letter-spacing: 1rpx;
        text-align: center;
        width: 100%;
    }

    &:active {
        transform: translateY(2rpx);
        box-shadow: 0 3rpx 10rpx rgba(124, 58, 237, 0.22);
    }
}

.btn-hover {
    opacity: 0.9;
}

.btn-login {
    margin-top: 16rpx;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-disabled {
    opacity: 0.5;
    pointer-events: none;
    box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.15);
}

.login-btn-wrapper {
    margin-top: 24rpx;
}

/* 底部操作 */
.bottom-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 24rpx;
    font-size: 26rpx;

    .action-item {
        color: var(--color-content, #666666);
    }

    .action-link {
        color: var(--color-primary, #7c3aed);
        font-weight: 500;
        cursor: pointer;
        transition: color 0.2s ease;

        &:active {
            color: var(--color-primary-dark-2, #6d28d9);
        }
    }
}

/* 弹窗样式 */
.modal-content {
    padding: 60rpx 40rpx;
    text-align: center;

    .modal-title {
        font-size: 34rpx;
        font-weight: 600;
        color: var(--color-primary, #7c3aed);
        margin-bottom: 32rpx;
    }

    .modal-text {
        font-size: 28rpx;
        color: var(--color-content, #666666);
        line-height: 1.6;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        align-items: center;

        .modal-link {
            color: var(--color-primary, #7c3aed);
            font-weight: 500;
            margin: 0 4rpx;
        }
    }
}

/* 响应式优化 */
@media (max-width: 375px) {
    .login-content {
        padding: 48rpx 28rpx 32rpx;
    }

    .form-card {
        padding: 36rpx 28rpx;
    }

    .logo-section .welcome-text {
        font-size: 42rpx;
    }
}
</style>
