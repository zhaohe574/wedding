<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
        <!-- #endif -->
    </page-meta>
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
                    <!-- #ifdef MP-WEIXIN || H5 -->
                    <view v-if="isOpenOtherAuth && isWeixin && inWxAuth" class="quick-login-section">
                        <view class="btn-primary" @click="wxLogin" hover-class="btn-hover">
                            <view class="btn-content">
                                <tn-icon name="wechat-fill" size="40" color="#ffffff" />
                                <text class="btn-text">微信一键登录</text>
                            </view>
                        </view>
                    </view>
                    <!-- #endif -->

                    <view class="divider-section" v-if="isOpenOtherAuth && isWeixin && inWxAuth">
                        <view class="divider-line"></view>
                        <text class="divider-text">或</text>
                        <view class="divider-line"></view>
                    </view>

                    <view class="phone-login-btn" @click="phoneLogin = !phoneLogin" hover-class="btn-hover">
                        <view class="btn-content">
                            <tn-icon name="phone" size="40" :color="primaryColor" />
                            <text class="btn-text">手机号登录</text>
                        </view>
                    </view>
                </block>

                <block v-if="phoneLogin">
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
                                <tn-icon name="phone" size="36" color="#9ca3af" class="input-icon" />
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
                                <tn-icon name="shield-check" size="36" color="#9ca3af" class="input-icon" />
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
                                    url="/pages/agreement/agreement?type=service"
                                    @click.stop
                                >
                                    《服务协议》
                                </navigator>
                                和
                                <navigator
                                    class="agreement-link"
                                    hover-class="none"
                                    url="/pages/agreement/agreement?type=privacy"
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
                        <navigator url="/pages/register/register" hover-class="none">
                            <view class="action-link">注册账号</view>
                        </navigator>
                    </view>
                </block>
            </view>
        </view>

        <!-- 协议弹框 -->
        <tn-modal ref="modalRef" />

        <!-- #ifdef MP-WEIXIN -->
        <mplogin-popup
            v-model:show="showLoginPopup"
            :logo="websiteConfig.shop_logo"
            :title="websiteConfig.shop_name"
            @update="handleUpdateUser"
        />
        <!--  #endif -->
    </view>
</template>

<script setup lang="ts">
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
import type { TnModalInstance } from '@tuniao/tnui-vue3-uniapp'
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
// #ifdef H5
isWeixin.value = isWeixinClient()
// #endif

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()
const appStore = useAppStore()
const themeStore = useThemeStore()
const modalRef = ref<TnModalInstance>()
const codeTips = ref('获取验证码')
const canGetCode = ref(true)
const showLoginPopup = ref(false)
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

const inWxAuth = computed(() => {
    return appStore.getLoginConfig.wechat_auth
})

const isOpenAgreement = computed(() => appStore.getLoginConfig.login_agreement == 1)

const isOpenOtherAuth = computed(() => appStore.getLoginConfig.third_auth == 1)
const isForceBindMobile = computed(() => appStore.getLoginConfig.coerce_mobile == 1)

// 显示协议弹窗
const showAgreementModal = () => {
    modalRef.value?.showModal({
        title: '温馨提示',
        content: '请先阅读并同意《服务协议》和《隐私协议》',
        showCancel: true,
        confirmText: '同意',
        cancelText: '取消',
        confirmStyle: {
            color: primaryColor.value
        },
        confirm: () => {
            isCheckAgreement.value = true
            // 同意后继续执行登录
            loginFun()
        }
    })
}

const loginFun = async () => {
    if (!isCheckAgreement.value && isOpenAgreement.value) {
        showAgreementModal()
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
        showModel.value = true
        console.log(showModel.value)
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
        if (value.login_way) {
            formData.scene = value.login_way[0]
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
    background: linear-gradient(180deg, var(--color-primary-light-9, #FAF5FF) 0%, #FFFFFF 100%);
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
        background: linear-gradient(135deg, var(--color-primary, #7C3AED), var(--color-primary-light-3, #A78BFA));
        top: -100rpx;
        right: -100rpx;
        animation-delay: 0s;
    }

    .circle-2 {
        width: 300rpx;
        height: 300rpx;
        background: linear-gradient(135deg, var(--color-secondary, #EC4899), var(--color-secondary-light-3, #F9A8D4));
        bottom: 100rpx;
        left: -80rpx;
        animation-delay: 5s;
    }

    .circle-3 {
        width: 200rpx;
        height: 200rpx;
        background: linear-gradient(135deg, var(--color-cta, #F97316), var(--color-cta-light-3, #FDBA74));
        top: 50%;
        right: 50rpx;
        animation-delay: 10s;
    }
}

@keyframes float {
    0%, 100% {
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
    padding: 80rpx 40rpx 60rpx;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* Logo 区域 */
.logo-section {
    text-align: center;
    margin-bottom: 80rpx;

    .logo-wrapper {
        display: inline-block;
        padding: 20rpx;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 50%;
        box-shadow: 0 8rpx 32rpx rgba(124, 58, 237, 0.2);
        margin-bottom: 40rpx;
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
        color: var(--color-primary, #7C3AED);
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
    border-radius: 32rpx;
    padding: 60rpx 40rpx;
    box-shadow: 0 20rpx 60rpx rgba(124, 58, 237, 0.12),
                0 8rpx 16rpx rgba(0, 0, 0, 0.04);
    backdrop-filter: blur(20rpx);
    border: 2rpx solid rgba(255, 255, 255, 0.3);
}

/* 快捷登录区域 */
.quick-login-section {
    margin-bottom: 40rpx;
}

/* 分割线 */
.divider-section {
    display: flex;
    align-items: center;
    margin: 40rpx 0;

    .divider-line {
        flex: 1;
        height: 2rpx;
        background: linear-gradient(to right, transparent, #E5E7EB, transparent);
    }

    .divider-text {
        padding: 0 24rpx;
        font-size: 24rpx;
        color: #9CA3AF;
        font-weight: 500;
    }
}

/* 手机号登录按钮 */
.phone-login-btn {
    background: rgba(255, 255, 255, 0.8);
    border-radius: 48rpx;
    padding: 28rpx;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2rpx solid var(--color-primary-light-7, #DDD6FE);

    &:active {
        transform: scale(0.98);
        background: rgba(255, 255, 255, 1);
        border-color: var(--color-primary, #7C3AED);
    }
    
    .btn-content {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12rpx;
    }
    
    .btn-text {
        font-size: 32rpx;
        color: var(--color-primary, #7C3AED);
        font-weight: 600;
        letter-spacing: 1rpx;
    }
}

/* 表单标题 */
.form-title {
    font-size: 34rpx;
    font-weight: 600;
    color: var(--color-primary, #7C3AED);
    margin-bottom: 40rpx;
    text-align: center;
}

/* 输入框组 */
.input-group {
    margin-bottom: 24rpx;

    .input-wrapper {
        display: flex;
        align-items: center;
        background: #F9FAFB;
        border: 2rpx solid #E5E7EB;
        border-radius: 16rpx;
        padding: 0 24rpx;
        height: 88rpx;
        transition: all 0.2s ease;

        &:focus-within {
            border-color: var(--color-primary, #7C3AED);
            background: #FFFFFF;
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
            color: var(--color-primary, #7C3AED);
            padding-left: 24rpx;
            border-left: 2rpx solid #E5E7EB;
            white-space: nowrap;
            font-weight: 500;
        }

        .code-btn {
            font-size: 24rpx;
            color: #9CA3AF;
            padding-left: 24rpx;
            border-left: 2rpx solid #E5E7EB;
            white-space: nowrap;
            min-width: 160rpx;
            text-align: center;
            transition: all 0.2s ease;

            &.code-btn-active {
                color: var(--color-primary, #7C3AED);
                font-weight: 600;
                cursor: pointer;

                &:active {
                    color: var(--color-primary-dark-2, #6D28D9);
                }
            }
        }
    }
}

/* 协议区域 */
.agreement-section {
    margin: 24rpx 0;

    .agreement-text {
        font-size: 24rpx;
        color: var(--color-content, #666666);
        display: flex;
        flex-wrap: wrap;
        align-items: center;

        .agreement-link {
            color: var(--color-primary, #7C3AED);
            font-weight: 500;
            margin: 0 4rpx;
        }
    }
}

/* 按钮样式 */
.btn-primary {
    background: linear-gradient(135deg, var(--color-primary, #7C3AED) 0%, var(--color-primary-dark-2, #6D28D9) 100%);
    border-radius: 48rpx;
    padding: 28rpx;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.3);

    .btn-content {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12rpx;
    }

    .btn-text {
        font-size: 32rpx;
        color: #FFFFFF;
        font-weight: 600;
        letter-spacing: 1rpx;
        text-align: center;
        width: 100%;
    }

    &:active {
        transform: translateY(2rpx);
        box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.3);
    }
}

.btn-hover {
    opacity: 0.9;
}

.btn-login {
    margin-top: 16rpx;
}

.btn-disabled {
    opacity: 0.5;
    pointer-events: none;
    box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.15);
}

.login-btn-wrapper {
    margin-top: 40rpx;
}

/* 底部操作 */
.bottom-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 32rpx;
    font-size: 26rpx;

    .action-item {
        color: var(--color-content, #666666);
    }

    .action-link {
        color: var(--color-primary, #7C3AED);
        font-weight: 500;
        cursor: pointer;
        transition: color 0.2s ease;

        &:active {
            color: var(--color-primary-dark-2, #6D28D9);
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
        color: var(--color-primary, #7C3AED);
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
            color: var(--color-primary, #7C3AED);
            font-weight: 500;
            margin: 0 4rpx;
        }
    }
}

/* 响应式优化 */
@media (max-width: 375px) {
    .login-content {
        padding: 60rpx 32rpx 40rpx;
    }

    .form-card {
        padding: 48rpx 32rpx;
    }

    .logo-section .welcome-text {
        font-size: 42rpx;
    }
}
</style>
