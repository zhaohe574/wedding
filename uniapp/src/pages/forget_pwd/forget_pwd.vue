<template>
    <AuthPageShell navbarTitle="忘记密码">
        <template #hero>
            <view class="auth-hero">
                <text class="auth-hero__eyebrow">Recover Access</text>
                <text class="auth-hero__title">重置登录密码</text>
                <text class="auth-hero__desc">验证手机号后重置密码。</text>
            </view>
        </template>

        <view class="auth-form">
            <view class="auth-form__group">
                <text class="auth-form__label">手机号</text>
                <BaseInput v-model="formData.mobile" type="tel" placeholder="请输入手机号码">
                    <template #prefix>
                        <tn-icon name="phone" size="30" color="#9A9388" />
                    </template>
                </BaseInput>
            </view>

            <view class="auth-form__group">
                <text class="auth-form__label">验证码</text>
                <BaseInput v-model="formData.code" placeholder="请输入验证码">
                    <template #prefix>
                        <tn-icon name="shield-check" size="30" color="#9A9388" />
                    </template>
                    <template #suffix>
                        <text
                            class="auth-code-btn"
                            :class="{ 'auth-code-btn--active': canGetCode && formData.mobile }"
                            @click="sendSms"
                        >
                            {{ codeTips }}
                        </text>
                    </template>
                </BaseInput>
            </view>

            <view class="auth-form__group">
                <text class="auth-form__label">新密码</text>
                <BaseInput
                    v-model="formData.password"
                    type="password"
                    placeholder="请输入新密码，至少 6 位"
                >
                    <template #prefix>
                        <tn-icon name="lock" size="30" color="#9A9388" />
                    </template>
                </BaseInput>
            </view>

            <view class="auth-form__group">
                <text class="auth-form__label">确认密码</text>
                <BaseInput
                    v-model="formData.password_confirm"
                    type="password"
                    placeholder="请再次输入新密码"
                >
                    <template #prefix>
                        <tn-icon name="shield-check" size="30" color="#9A9388" />
                    </template>
                </BaseInput>
            </view>

            <BaseButton block size="lg" :disabled="!isFormValid" @click="handleConfirm">
                确认重置
            </BaseButton>
        </view>
    </AuthPageShell>
</template>

<script setup lang="ts">
import { smsSend } from '@/api/app'
import { forgotPassword } from '@/api/user'
import { SMSEnum } from '@/enums/appEnums'
import AuthPageShell from '@/components/business/AuthPageShell.vue'
import { computed, reactive, ref } from 'vue'

const codeTips = ref('获取验证码')
const canGetCode = ref(true)
const formData = reactive({
    mobile: '',
    code: '',
    password: '',
    password_confirm: ''
})

const isFormValid = computed(() => {
    return formData.mobile && formData.code && formData.password && formData.password_confirm
})

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
    if (!canGetCode.value) return

    await smsSend({
        scene: SMSEnum.FIND_PASSWORD,
        mobile: formData.mobile
    })
    uni.$u.toast('发送成功')
    startCodeCountdown()
}

const handleConfirm = async () => {
    if (!formData.mobile) return uni.$u.toast('请输入手机号码')
    if (!formData.code) return uni.$u.toast('请输入验证码')
    if (!formData.password) return uni.$u.toast('请输入密码')
    if (formData.password.length < 6) return uni.$u.toast('密码至少 6 位字符')
    if (!formData.password_confirm) return uni.$u.toast('请输入确认密码')
    if (formData.password != formData.password_confirm) {
        return uni.$u.toast('两次输入的密码不一致')
    }

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
.auth-hero {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.auth-hero__eyebrow {
    font-size: 22rpx;
    font-weight: 600;
    letter-spacing: 0;
    text-transform: uppercase;
    color: var(--wm-color-primary, #0b0b0b);
}

.auth-hero__title {
    font-size: 52rpx;
    font-weight: 700;
    line-height: 1.18;
    color: var(--wm-text-primary, #111111);
}

.auth-hero__desc {
    font-size: 26rpx;
    line-height: 1.65;
    color: var(--wm-text-secondary, #5f5a50);
}

.auth-form {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.auth-form__group {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.auth-form__label {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #5f5a50);
}

.auth-code-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    min-width: 132rpx;
    height: 48rpx;
    padding-left: 16rpx;
    box-sizing: border-box;
    font-size: 24rpx;
    font-weight: 500;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    color: var(--wm-text-tertiary, #9a9388);
}

.auth-code-btn--active {
    color: var(--wm-color-primary, #0b0b0b);
    font-weight: 700;
}
</style>
