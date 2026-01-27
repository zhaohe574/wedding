<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
        <!-- #endif -->
    </page-meta>
    <view class="forget-pwd-container">
        <!-- 背景装饰 -->
        <view class="bg-decoration">
            <view class="circle circle-1"></view>
            <view class="circle circle-2"></view>
        </view>

        <!-- 主内容区 -->
        <view class="content">
            <!-- 头部区域 -->
            <view class="header-section">
                <view class="back-btn" @click="goBack">
                    <tn-icon name="left" size="40" :color="primaryColor" />
                </view>
                <view class="title-text">忘记密码</view>
                <view class="subtitle-text">通过手机号重置您的密码</view>
            </view>

            <!-- 表单卡片 -->
            <view class="form-card">
                <!-- 手机号 -->
                <view class="input-group">
                    <view class="input-label">手机号</view>
                    <view class="input-wrapper">
                        <tn-icon name="phone" size="36" color="#9ca3af" class="input-icon" />
                        <tn-input
                            v-model="formData.mobile"
                            :border="false"
                            placeholder="请输入手机号码"
                            class="custom-input"
                        />
                    </view>
                </view>

                <!-- 验证码 -->
                <view class="input-group">
                    <view class="input-label">验证码</view>
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
                            :class="{ 'code-btn-active': canGetCode && formData.mobile }"
                            @click="sendSms"
                        >
                            {{ codeTips }}
                        </view>
                    </view>
                </view>

                <!-- 新密码 -->
                <view class="input-group">
                    <view class="input-label">新密码</view>
                    <view class="input-wrapper">
                        <tn-icon name="lock" size="36" color="#9ca3af" class="input-icon" />
                        <tn-input
                            type="password"
                            v-model="formData.password"
                            placeholder="6-20位数字+字母或符号组合"
                            :border="false"
                            class="custom-input"
                        />
                    </view>
                </view>

                <!-- 确认密码 -->
                <view class="input-group">
                    <view class="input-label">确认密码</view>
                    <view class="input-wrapper">
                        <tn-icon name="shield-check" size="36" color="#9ca3af" class="input-icon" />
                        <tn-input
                            type="password"
                            v-model="formData.password_confirm"
                            placeholder="再次输入新密码"
                            :border="false"
                            class="custom-input"
                        />
                    </view>
                </view>

                <!-- 确定按钮 -->
                <view class="btn-wrapper">
                    <view
                        class="btn-primary"
                        :class="{ 'btn-disabled': !isFormValid }"
                        @click="handleConfirm"
                        hover-class="btn-hover"
                    >
                        <text class="btn-text">确定</text>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { smsSend } from '@/api/app'
import { forgotPassword } from '@/api/user'
import { SMSEnum } from '@/enums/appEnums'
import { useThemeStore } from '@/stores/theme'
import { computed, reactive, ref } from 'vue'

const themeStore = useThemeStore()

// 主题色
const primaryColor = computed(() => themeStore.primaryColor)

const codeTips = ref('获取验证码')
const canGetCode = ref(true)
const formData = reactive({
    mobile: '',
    code: '',
    password: '',
    password_confirm: ''
})

// 表单验证
const isFormValid = computed(() => {
    return formData.mobile && formData.code && formData.password && formData.password_confirm
})

// 返回上一页
const goBack = () => {
    uni.navigateBack()
}

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

const sendSms = async () => {
    if (!formData.mobile) return uni.$u.toast('请输入手机号码')
    if (canGetCode.value) {
        await smsSend({
            scene: SMSEnum.FIND_PASSWORD,
            mobile: formData.mobile
        })
        uni.$u.toast('发送成功')
        startCodeCountdown()
    }
}

const handleConfirm = async () => {
    if (!formData.mobile) return uni.$u.toast('请输入手机号码')
    if (!formData.code) return uni.$u.toast('请输入验证码')
    if (!formData.password) return uni.$u.toast('请输入密码')
    if (formData.password.length < 6) return uni.$u.toast('密码至少6位字符')
    if (!formData.password_confirm) return uni.$u.toast('请输入确认密码')
    if (formData.password != formData.password_confirm) return uni.$u.toast('两次输入的密码不一致')
    
    try {
        await forgotPassword(formData)
        uni.$u.toast('密码重置成功')
        setTimeout(() => {
            uni.navigateBack()
        }, 1500)
    } catch (error: any) {
        uni.$u.toast(error || '重置失败')
    }
}
</script>

<style lang="scss" scoped>
page {
    height: 100%;
}

.forget-pwd-container {
    min-height: 100vh;
    background: linear-gradient(180deg, var(--color-primary-light-9, #FAF5FF) 0%, #FFFFFF 100%);
    position: relative;
    overflow: hidden;
}

/* 背景装饰 */
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
        width: 350rpx;
        height: 350rpx;
        background: linear-gradient(135deg, var(--color-primary, #7C3AED), var(--color-primary-light-3, #A78BFA));
        top: -80rpx;
        right: -80rpx;
    }

    .circle-2 {
        width: 250rpx;
        height: 250rpx;
        background: linear-gradient(135deg, var(--color-secondary, #EC4899), var(--color-secondary-light-3, #F9A8D4));
        bottom: 80rpx;
        left: -60rpx;
        animation-delay: 5s;
    }
}

@keyframes float {
    0%, 100% {
        transform: translateY(0) scale(1);
    }
    50% {
        transform: translateY(-30rpx) scale(1.05);
    }
}

/* 主内容区 */
.content {
    position: relative;
    z-index: 1;
    padding: 60rpx 40rpx;
    min-height: 100vh;
}

/* 头部区域 */
.header-section {
    margin-bottom: 60rpx;
    position: relative;

    .back-btn {
        position: absolute;
        left: 0;
        top: 0;
        width: 80rpx;
        height: 80rpx;
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
        color: var(--color-primary, #7C3AED);
        margin-bottom: 16rpx;
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

/* 表单卡片 */
.form-card {
    background: rgba(255, 255, 255, 0.85);
    border-radius: 32rpx;
    padding: 60rpx 40rpx;
    box-shadow: 0 20rpx 60rpx rgba(124, 58, 237, 0.12),
                0 8rpx 16rpx rgba(0, 0, 0, 0.04);
    backdrop-filter: blur(20rpx);
    border: 2rpx solid rgba(255, 255, 255, 0.3);
}

/* 输入框组 */
.input-group {
    margin-bottom: 32rpx;

    .input-label {
        font-size: 26rpx;
        color: #6B7280;
        margin-bottom: 16rpx;
        font-weight: 500;
    }

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

/* 按钮 */
.btn-wrapper {
    margin-top: 48rpx;
}

.btn-primary {
    background: linear-gradient(135deg, var(--color-primary, #7C3AED) 0%, var(--color-primary-dark-2, #6D28D9) 100%);
    border-radius: 48rpx;
    padding: 28rpx;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.3);

    .btn-text {
        font-size: 32rpx;
        color: #FFFFFF;
        font-weight: 600;
        letter-spacing: 1rpx;
        text-align: center;
        display: block;
    }

    &:active {
        transform: translateY(2rpx);
        box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.3);
    }
}

.btn-hover {
    opacity: 0.9;
}

.btn-disabled {
    opacity: 0.5;
    pointer-events: none;
    box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.15);
}

/* 响应式优化 */
@media (max-width: 375px) {
    .content {
        padding: 48rpx 32rpx;
    }

    .form-card {
        padding: 48rpx 32rpx;
    }
}
</style>
