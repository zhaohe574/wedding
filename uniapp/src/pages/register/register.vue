<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
        <!-- #endif -->
    </page-meta>
    <view class="register-container">
        <!-- 背景装饰 -->
        <view class="bg-decoration">
            <view class="circle circle-1"></view>
            <view class="circle circle-2"></view>
            <view class="circle circle-3"></view>
        </view>

        <!-- 主内容区 -->
        <view class="register-content">
            <!-- 头部区域 -->
            <view class="header-section">
                <view class="back-btn" @click="goBack">
                    <tn-icon name="left" size="40" :color="primaryColor" />
                </view>
                <view class="title-text">注册新账号</view>
                <view class="subtitle-text">创建您的账号开始使用</view>
            </view>

            <!-- 注册表单卡片 -->
            <view class="form-card glass-card">
                <view class="form-title">账号信息</view>

                <!-- 账号输入 -->
                <view class="input-group">
                    <view class="input-wrapper">
                        <tn-icon name="user" size="36" color="#9ca3af" class="input-icon" />
                        <tn-input
                            class="custom-input"
                            v-model="formData.account"
                            :border="false"
                            placeholder="请输入账号"
                        />
                    </view>
                </view>

                <!-- 密码输入 -->
                <view class="input-group">
                    <view class="input-wrapper">
                        <tn-icon name="lock" size="36" color="#9ca3af" class="input-icon" />
                        <tn-input
                            class="custom-input"
                            v-model="formData.password"
                            type="password"
                            :border="false"
                            placeholder="请输入密码"
                        />
                    </view>
                </view>

                <!-- 确认密码 -->
                <view class="input-group">
                    <view class="input-wrapper">
                        <tn-icon name="shield-check" size="36" color="#9ca3af" class="input-icon" />
                        <tn-input
                            class="custom-input"
                            v-model="formData.password_confirm"
                            type="password"
                            :border="false"
                            placeholder="请再次输入密码"
                        />
                    </view>
                </view>

                <!-- 密码强度提示 -->
                <view class="password-tips" v-if="formData.password">
                    <view class="tip-item" :class="{ 'tip-active': passwordStrength >= 1 }">
                        <view class="tip-dot"></view>
                        <text class="tip-text">至少6位字符</text>
                    </view>
                    <view class="tip-item" :class="{ 'tip-active': passwordStrength >= 2 }">
                        <view class="tip-dot"></view>
                        <text class="tip-text">包含字母和数字</text>
                    </view>
                </view>

                <!-- 协议勾选 -->
                <view class="agreement-section" v-if="isOpenAgreement">
                    <tn-checkbox v-model="isCheckAgreement" shape="round">
                        <view class="agreement-text">
                            已阅读并同意
                            <router-navigate
                                class="agreement-link"
                                hover-class="none"
                                to="/pages/agreement/agreement?type=service"
                                @click.stop
                            >
                                《服务协议》
                            </router-navigate>
                            和
                            <router-navigate
                                class="agreement-link"
                                hover-class="none"
                                to="/pages/agreement/agreement?type=privacy"
                                @click.stop
                            >
                                《隐私协议》
                            </router-navigate>
                        </view>
                    </tn-checkbox>
                </view>

                <!-- 注册按钮 -->
                <view class="register-btn-wrapper">
                    <view
                        class="btn-primary btn-register"
                        :class="{ 'btn-disabled': !isFormValid }"
                        @click="accountRegister"
                        hover-class="btn-hover"
                    >
                        <text class="btn-text">立即注册</text>
                    </view>
                </view>

                <!-- 底部提示 -->
                <view class="bottom-tip">
                    <text class="tip-text">已有账号？</text>
                    <text class="tip-link" @click="goToLogin">立即登录</text>
                </view>
            </view>
        </view>

        <!-- 协议弹框 -->
        <tn-modal ref="modalRef" />
    </view>
</template>

<script setup lang="ts">
import { register } from '@/api/account'
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import { computed, reactive, ref } from 'vue'
import type { TnModalInstance } from '@tuniao/tnui-vue3-uniapp'

const isCheckAgreement = ref(false)
const appStore = useAppStore()
const themeStore = useThemeStore()
const isOpenAgreement = computed(() => appStore.getLoginConfig.login_agreement == 1)
const formData = reactive({
    account: '',
    password: '',
    password_confirm: ''
})
const modalRef = ref<TnModalInstance>()

// 主题色
const primaryColor = computed(() => themeStore.primaryColor)

// 计算密码强度
const passwordStrength = computed(() => {
    let strength = 0
    if (formData.password.length >= 6) strength++
    if (/[a-zA-Z]/.test(formData.password) && /[0-9]/.test(formData.password)) strength++
    return strength
})

// 表单验证
const isFormValid = computed(() => {
    return formData.account && formData.password && formData.password_confirm
})

// 返回上一页
const goBack = () => {
    uni.navigateBack()
}

// 跳转到登录页
const goToLogin = () => {
    uni.navigateBack()
}

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
            accountRegister()
        }
    })
}

// 注册
const accountRegister = async () => {
    if (!formData.account) return uni.$u.toast('请输入账号')
    if (!formData.password) return uni.$u.toast('请输入密码')
    if (formData.password.length < 6) return uni.$u.toast('密码至少6位字符')
    if (!formData.password_confirm) return uni.$u.toast('请输入确认密码')
    if (!isCheckAgreement.value && isOpenAgreement.value) {
        showAgreementModal()
        return
    }
    if (formData.password != formData.password_confirm) return uni.$u.toast('两次输入的密码不一致')

    try {
        await register(formData)
        uni.$u.toast('注册成功')
        setTimeout(() => {
            uni.navigateBack()
        }, 1500)
    } catch (error: any) {
        uni.$u.toast(error || '注册失败')
    }
}
</script>

<style lang="scss" scoped>
page {
    height: 100%;
}

.register-container {
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
.register-content {
    position: relative;
    z-index: 1;
    padding: 48rpx 32rpx;
    min-height: 100vh;
}

/* 头部区域 */
.header-section {
    margin-bottom: 40rpx;
    position: relative;

    .back-btn {
        position: absolute;
        left: 0;
        top: 0;
        width: 72rpx;
        height: 72rpx;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4rpx 16rpx rgba(124, 58, 237, 0.15);
        cursor: pointer;
        transition: all 0.2s ease;

        &:active {
            transform: scale(0.95);
            background: rgba(255, 255, 255, 1);
        }
    }

    .title-text {
        font-size: 44rpx;
        font-weight: 600;
        color: var(--color-primary, #7c3aed);
        margin-bottom: 12rpx;
        text-align: center;
        letter-spacing: 1rpx;
        margin-top: 20rpx;
    }

    .subtitle-text {
        font-size: 28rpx;
        color: var(--color-content, #666666);
        text-align: center;
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

/* 表单标题 */
.form-title {
    font-size: 32rpx;
    font-weight: 600;
    color: var(--color-primary, #7c3aed);
    margin-bottom: 24rpx;
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
    }
}

/* 密码强度提示 */
.password-tips {
    margin-top: -8rpx;
    margin-bottom: 20rpx;
    padding: 20rpx;
    background: var(--color-primary-light-9, #faf5ff);
    border-radius: 16rpx;
    border: 2rpx solid var(--color-primary-light-7, #ddd6fe);

    .tip-item {
        display: flex;
        align-items: center;
        margin-bottom: 12rpx;

        &:last-child {
            margin-bottom: 0;
        }

        .tip-dot {
            width: 12rpx;
            height: 12rpx;
            border-radius: 50%;
            background: #d1d5db;
            margin-right: 16rpx;
            transition: all 0.2s ease;
        }

        .tip-text {
            font-size: 24rpx;
            color: #9ca3af;
            transition: all 0.2s ease;
        }

        &.tip-active {
            .tip-dot {
                background: var(--color-primary, #7c3aed);
            }

            .tip-text {
                color: var(--color-primary, #7c3aed);
                font-weight: 500;
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

    .btn-text {
        font-size: 30rpx;
        color: #ffffff;
        font-weight: 600;
        letter-spacing: 1rpx;
        text-align: center;
        display: block;
    }

    &:active {
        transform: translateY(2rpx);
        box-shadow: 0 3rpx 10rpx rgba(124, 58, 237, 0.22);
    }
}

.btn-hover {
    opacity: 0.9;
}

.btn-register {
    margin-top: 16rpx;
}

.btn-disabled {
    opacity: 0.5;
    pointer-events: none;
    box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.15);
}

.register-btn-wrapper {
    margin-top: 24rpx;
}

/* 底部提示 */
.bottom-tip {
    text-align: center;
    margin-top: 24rpx;
    font-size: 26rpx;

    .tip-text {
        color: var(--color-content, #666666);
    }

    .tip-link {
        color: var(--color-primary, #7c3aed);
        font-weight: 500;
        margin-left: 8rpx;
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
    .register-content {
        padding: 40rpx 28rpx;
    }

    .form-card {
        padding: 36rpx 28rpx;
    }

    .header-section .title-text {
        font-size: 42rpx;
    }
}
</style>
