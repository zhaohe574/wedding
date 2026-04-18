<template>
    <AuthPageShell navbarTitle="登录">
        <template #hero>
            <view class="auth-hero">
                <view class="auth-hero__brand">
                    <image
                        v-if="appStore.getWebsiteConfig.shop_logo"
                        :src="appStore.getWebsiteConfig.shop_logo"
                        mode="aspectFit"
                        class="auth-hero__logo"
                    />
                    <view v-else class="auth-hero__logo auth-hero__logo--fallback">
                        <text>{{ (websiteConfig.shop_name || '婚礼').slice(0, 2) }}</text>
                    </view>
                </view>
                <text class="auth-hero__eyebrow">Welcome Back</text>
                <text class="auth-hero__title">登录您的账号</text>
                <text class="auth-hero__desc">
                    {{ websiteConfig.shop_name || '婚礼服务平台' }}
                </text>
            </view>
        </template>

        <view class="auth-panel wm-page-content">
            <view v-if="!phoneLogin" class="auth-entry-list">
                <view
                    v-if="showWechatLoginEntry"
                    class="auth-entry auth-entry--primary"
                    :style="wechatEntryStyle"
                    @click="wxLogin"
                >
                    <view class="auth-entry__icon" :style="wechatEntryIconPillStyle">
                        <tn-icon name="wechat-fill" size="34" :color="$theme.btnColor" />
                    </view>
                    <view class="auth-entry__content">
                        <text class="auth-entry__title">微信一键登录</text>
                        <text class="auth-entry__desc">快速登录</text>
                    </view>
                    <tn-icon name="right" size="24" :color="$theme.btnColor" />
                </view>

                <view
                    v-if="showLocalLoginEntry"
                    class="auth-entry auth-entry--secondary"
                    :style="localEntryStyle"
                    @click="phoneLogin = true"
                >
                    <view class="auth-entry__icon" :style="localEntryIconPillStyle">
                        <tn-icon name="phone" size="30" :color="primaryColor" />
                    </view>
                    <view class="auth-entry__content">
                        <text class="auth-entry__title">{{ localLoginEntryText }}</text>
                        <text class="auth-entry__desc"> 账号或验证码登录 </text>
                    </view>
                    <tn-icon name="right" size="24" :color="primaryColor" />
                </view>
            </view>

            <view v-if="showLocalLoginForm" class="auth-form wm-form-block">
                <view class="auth-form__head">
                    <text class="auth-form__title">
                        {{ formData.scene == LoginWayEnum.ACCOUNT ? '账号密码登录' : '验证码登录' }}
                    </text>
                    <text class="auth-form__desc">请输入账号信息</text>
                </view>

                <template
                    v-if="
                        formData.scene == LoginWayEnum.ACCOUNT &&
                        includeLoginWay(LoginWayEnum.ACCOUNT)
                    "
                >
                    <view class="auth-form__group">
                        <text class="auth-form__label">账号</text>
                        <BaseInput v-model="formData.account" placeholder="请输入账号或手机号">
                            <template #prefix>
                                <tn-icon name="user" size="30" color="#B4ACA8" />
                            </template>
                        </BaseInput>
                    </view>

                    <view class="auth-form__group">
                        <text class="auth-form__label">密码</text>
                        <BaseInput
                            v-model="formData.password"
                            type="password"
                            placeholder="请输入密码"
                        >
                            <template #prefix>
                                <tn-icon name="lock" size="30" color="#B4ACA8" />
                            </template>
                            <template #suffix>
                                <navigator
                                    url="/pages/forget_pwd/forget_pwd"
                                    hover-class="none"
                                    class="auth-link-inline"
                                >
                                    忘记密码
                                </navigator>
                            </template>
                        </BaseInput>
                    </view>
                </template>

                <template
                    v-if="
                        formData.scene == LoginWayEnum.MOBILE &&
                        includeLoginWay(LoginWayEnum.MOBILE)
                    "
                >
                    <view class="auth-form__group">
                        <text class="auth-form__label">手机号</text>
                        <BaseInput
                            v-model="formData.account"
                            type="tel"
                            placeholder="请输入手机号码"
                        >
                            <template #prefix>
                                <tn-icon name="phone" size="30" color="#B4ACA8" />
                            </template>
                        </BaseInput>
                    </view>

                    <view class="auth-form__group">
                        <text class="auth-form__label">验证码</text>
                        <BaseInput v-model="formData.code" placeholder="请输入验证码">
                            <template #prefix>
                                <tn-icon name="shield-check" size="30" color="#B4ACA8" />
                            </template>
                            <template #suffix>
                                <text
                                    class="auth-code-btn"
                                    :class="{
                                        'auth-code-btn--active': canGetCode && formData.account
                                    }"
                                    @click="sendSms"
                                >
                                    {{ codeTips }}
                                </text>
                            </template>
                        </BaseInput>
                    </view>
                </template>

                <view v-if="isOpenAgreement" class="agreement-panel">
                    <tn-checkbox v-model="isCheckAgreement" shape="round">
                        <view class="agreement-panel__text">
                            已阅读并同意
                            <navigator
                                class="agreement-panel__link"
                                hover-class="none"
                                url="/packages/pages/agreement/agreement?type=service"
                                @click.stop
                            >
                                《服务协议》
                            </navigator>
                            和
                            <navigator
                                class="agreement-panel__link"
                                hover-class="none"
                                url="/packages/pages/agreement/agreement?type=privacy"
                                @click.stop
                            >
                                《隐私协议》
                            </navigator>
                        </view>
                    </tn-checkbox>
                </view>

                <BaseButton
                    block
                    size="lg"
                    :disabled="!DisableStyle"
                    @click="handleLogin(formData.scene)"
                >
                    立即登录
                </BaseButton>

                <view class="auth-form__actions">
                    <text
                        v-if="
                            formData.scene == LoginWayEnum.MOBILE &&
                            includeLoginWay(LoginWayEnum.ACCOUNT)
                        "
                        class="auth-form__action"
                        @click="changeLoginWay(LoginWayEnum.ACCOUNT)"
                    >
                        使用密码登录
                    </text>
                    <text
                        v-if="
                            formData.scene == LoginWayEnum.ACCOUNT &&
                            includeLoginWay(LoginWayEnum.MOBILE)
                        "
                        class="auth-form__action"
                        @click="changeLoginWay(LoginWayEnum.MOBILE)"
                    >
                        使用验证码登录
                    </text>
                    <text class="auth-form__action" @click="phoneLogin = false">
                        返回登录方式
                    </text>
                </view>
            </view>
        </view>

        <template #footer>
            <view v-if="showRegisterEntry" class="auth-footer">
                <text class="auth-footer__text">还没有账号？</text>
                <navigator
                    url="/pages/register/register"
                    hover-class="none"
                    class="auth-footer__link"
                >
                    注册账号
                </navigator>
            </view>
        </template>

        <template #overlay>
            <BaseOverlayMask :show="showAgreementPopup" :closeable="false" />
            <tn-popup
                v-model="showAgreementPopup"
                open-direction="center"
                :radius="24"
                :overlay="false"
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
                            @click="closeAgreementPopup"
                        >
                            取消
                        </view>
                        <view
                            class="agreement-popup__action agreement-popup__action--confirm"
                            :style="{
                                background: `linear-gradient(135deg, ${primaryColor} 0%, ${primaryColor} 100%)`
                            }"
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
        </template>
    </AuthPageShell>
</template>

<script setup lang="ts">
import AuthPageShell from '@/components/business/AuthPageShell.vue'
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
import { onLoad } from '@dcloudio/uni-app'
import { computed, reactive, ref, watch } from 'vue'

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
const wechatEntryStyle = computed(() => ({
    background: `linear-gradient(135deg, ${primaryColor.value} 0%, ${shadeColor(
        primaryColor.value,
        0.1
    )} 100%)`,
    boxShadow: `0 14rpx 28rpx ${alphaColor(primaryColor.value, 0.22)}`,
    border: `1rpx solid ${alphaColor(primaryColor.value, 0.12)}`
}))
const wechatEntryIconPillStyle = computed(() => ({
    background: alphaColor('#ffffff', 0.18)
}))
const localEntryStyle = computed(() => ({
    background: alphaColor('#ffffff', 0.84),
    border: `1rpx solid ${alphaColor(primaryColor.value, 0.18)}`,
    boxShadow: `0 10rpx 24rpx ${alphaColor('#D6B9A7', 0.14)}`
}))
const localEntryIconPillStyle = computed(() => ({
    background: alphaColor(primaryColor.value, 0.1)
}))

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
const showWechatLoginEntry = computed(
    () => isOpenOtherAuth.value && isWeixin.value && inWxAuth.value
)
const isMpWechatOnlyMode = computed(() => isMpWeixinPlatform.value && showWechatLoginEntry.value)
const showLocalLoginEntry = computed(() => !isMpWechatOnlyMode.value)
const showLocalLoginForm = computed(() => phoneLogin.value && showLocalLoginEntry.value)
const showRegisterEntry = computed(() => !isMpWechatOnlyMode.value)
const localLoginEntryText = computed(() => {
    if (hasAccountLogin.value && hasMobileLogin.value) {
        return isH5Platform.value ? '账号 / 手机号登录' : '手机号登录'
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
        wechatOa.setAuthData()
    }
    //#endif
})
</script>

<style lang="scss" scoped>
.auth-hero {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.auth-hero__brand {
    width: 116rpx;
    height: 116rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid rgba(244, 199, 191, 0.52);
    box-shadow: var(--wm-shadow-soft, 0 14rpx 32rpx rgba(214, 185, 167, 0.16));
}

.auth-hero__logo {
    width: 74rpx;
    height: 74rpx;
}

.auth-hero__logo--fallback {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28rpx;
    font-weight: 700;
    color: var(--wm-color-primary, #e85a4f);
}

.auth-hero__eyebrow {
    font-size: 22rpx;
    font-weight: 600;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: var(--wm-color-primary, #e85a4f);
}

.auth-hero__title {
    font-size: 52rpx;
    font-weight: 700;
    line-height: 1.18;
    color: var(--wm-text-primary, #1e2432);
}

.auth-hero__desc {
    font-size: 26rpx;
    line-height: 1.65;
    color: var(--wm-text-secondary, #7f7b78);
}

.auth-panel,
.auth-entry-list,
.auth-form {
    display: flex;
    flex-direction: column;
}

.auth-panel,
.auth-entry-list,
.auth-form {
    gap: 20rpx;
}

.auth-entry {
    min-height: 112rpx;
    display: flex;
    align-items: center;
    gap: 18rpx;
    padding: 22rpx 24rpx;
    border-radius: 24rpx;
}

.auth-entry__icon {
    width: 68rpx;
    height: 68rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    flex-shrink: 0;
}

.auth-entry__content {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.auth-entry__title {
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.3;
    color: inherit;
}

.auth-entry__desc {
    font-size: 22rpx;
    line-height: 1.6;
    color: inherit;
    opacity: 0.82;
}

.auth-entry--primary {
    color: #ffffff;
}

.auth-entry--secondary {
    color: var(--wm-text-primary, #1e2432);
}

.auth-form__head {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.auth-form__title {
    font-size: 34rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.auth-form__desc {
    font-size: 24rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.auth-form__group {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.auth-form__label {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);
}

.auth-form__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 20rpx;
}

.auth-form__action,
.auth-link-inline,
.agreement-panel__link,
.auth-footer__link,
.agreement-popup__link {
    color: var(--wm-color-primary, #e85a4f);
    font-weight: 600;
}

.auth-link-inline {
    padding-left: 16rpx;
    font-size: 24rpx;
}

.auth-code-btn {
    padding-left: 20rpx;
    font-size: 24rpx;
    font-weight: 500;
    color: var(--wm-text-tertiary, #b4aca8);
}

.auth-code-btn--active {
    color: var(--wm-color-primary, #e85a4f);
    font-weight: 700;
}

.agreement-panel {
    padding: 4rpx 0 8rpx;
}

.agreement-panel__text {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4rpx;
    font-size: 24rpx;
    line-height: 1.7;
    color: var(--wm-text-secondary, #7f7b78);
}

.auth-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    font-size: 26rpx;
}

.auth-footer__text {
    color: var(--wm-text-secondary, #7f7b78);
}

.agreement-popup {
    width: 580rpx;
    padding: 40rpx;
    border-radius: 28rpx;
    background: #ffffff;
}

.agreement-popup__title {
    font-size: 34rpx;
    font-weight: 700;
    text-align: center;
    color: var(--wm-text-primary, #1e2432);
}

.agreement-popup__content {
    margin-top: 24rpx;
    font-size: 28rpx;
    line-height: 1.7;
    text-align: center;
    color: var(--wm-text-secondary, #7f7b78);
}

.agreement-popup__actions {
    display: flex;
    gap: 20rpx;
    margin-top: 36rpx;
}

.agreement-popup__action {
    flex: 1;
    height: 76rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    font-size: 28rpx;
    font-weight: 600;
}

.agreement-popup__action--cancel {
    background: var(--wm-color-bg-soft, #fff7f4);
    color: var(--wm-text-secondary, #7f7b78);
}

.agreement-popup__action--confirm {
    color: #ffffff;
}
</style>
