<template>
    <AuthPageShell navbarTitle="注册">
        <template #hero>
            <view class="auth-hero">
                <text class="auth-hero__eyebrow">Create Account</text>
                <text class="auth-hero__title">注册新账号</text>
                <text class="auth-hero__desc">注册后即可预约与查看订单。</text>
            </view>
        </template>

        <view class="auth-form">
            <view class="auth-form__group">
                <text class="auth-form__label">账号</text>
                <BaseInput v-model="formData.account" placeholder="请输入账号">
                    <template #prefix>
                        <tn-icon name="user" size="30" color="#9A9388" />
                    </template>
                </BaseInput>
            </view>

            <view class="auth-form__group">
                <text class="auth-form__label">密码</text>
                <BaseInput
                    v-model="formData.password"
                    type="password"
                    placeholder="请输入密码，至少 6 位"
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
                    placeholder="请再次输入密码"
                >
                    <template #prefix>
                        <tn-icon name="shield-check" size="30" color="#9A9388" />
                    </template>
                </BaseInput>
            </view>

            <view v-if="formData.password" class="password-panel">
                <view
                    class="password-panel__item"
                    :class="{ 'password-panel__item--active': passwordStrength >= 1 }"
                >
                    <view class="password-panel__dot" />
                    <text>至少 6 位字符</text>
                </view>
                <view
                    class="password-panel__item"
                    :class="{ 'password-panel__item--active': passwordStrength >= 2 }"
                >
                    <view class="password-panel__dot" />
                    <text>同时包含字母和数字</text>
                </view>
            </view>

            <view v-if="isOpenAgreement" class="agreement-panel">
                <tn-checkbox v-model="isCheckAgreement" shape="round">
                    <view class="agreement-panel__text">
                        已阅读并同意
                        <router-navigate
                            class="agreement-panel__link"
                            to="/packages/pages/agreement/agreement?type=service"
                            @click.stop
                        >
                            《服务协议》
                        </router-navigate>
                        和
                        <router-navigate
                            class="agreement-panel__link"
                            to="/packages/pages/agreement/agreement?type=privacy"
                            @click.stop
                        >
                            《隐私协议》
                        </router-navigate>
                    </view>
                </tn-checkbox>
            </view>

            <BaseButton block size="lg" :disabled="!isFormValid" @click="accountRegister">
                立即注册
            </BaseButton>
        </view>

        <template #footer>
            <view class="auth-footer">
                <text class="auth-footer__text">已有账号？</text>
                <text class="auth-footer__link" @click="goToLogin">立即登录</text>
            </view>
            <tn-modal ref="modalRef" />
        </template>
    </AuthPageShell>
</template>

<script setup lang="ts">
import { register } from '@/api/account'
import AuthPageShell from '@/components/business/AuthPageShell.vue'
import { useAppStore } from '@/stores/app'
import { computed, reactive, ref } from 'vue'
import type { TnModalInstance } from '@tuniao/tnui-vue3-uniapp'

const isCheckAgreement = ref(false)
const appStore = useAppStore()
const isOpenAgreement = computed(() => appStore.getLoginConfig.login_agreement == 1)
const formData = reactive({
    account: '',
    password: '',
    password_confirm: ''
})
const modalRef = ref<TnModalInstance>()

const passwordStrength = computed(() => {
    let strength = 0
    if (formData.password.length >= 6) strength++
    if (/[a-zA-Z]/.test(formData.password) && /[0-9]/.test(formData.password)) strength++
    return strength
})

const isFormValid = computed(() => {
    return formData.account && formData.password && formData.password_confirm
})

const goToLogin = () => {
    uni.navigateBack()
}

const showAgreementModal = () => {
    modalRef.value?.showModal({
        title: '温馨提示',
        content: '请先阅读并同意《服务协议》和《隐私协议》',
        showCancel: true,
        mask: true,
        maskClosable: false,
        confirmText: '同意',
        cancelText: '取消',
        confirmStyle: {
            color: '#0B0B0B'
        },
        confirm: () => {
            isCheckAgreement.value = true
            accountRegister()
        }
    })
}

const accountRegister = async () => {
    if (!formData.account) return uni.$u.toast('请输入账号')
    if (!formData.password) return uni.$u.toast('请输入密码')
    if (formData.password.length < 6) return uni.$u.toast('密码至少 6 位字符')
    if (!formData.password_confirm) return uni.$u.toast('请输入确认密码')
    if (!isCheckAgreement.value && isOpenAgreement.value) {
        showAgreementModal()
        return
    }
    if (formData.password != formData.password_confirm) {
        return uni.$u.toast('两次输入的密码不一致')
    }

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

.password-panel {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    padding: 20rpx;
    border-radius: var(--wm-radius-card-soft, 20rpx);
    background: var(--wm-color-primary-soft, #f3f2ee);
}

.password-panel__item {
    display: flex;
    align-items: center;
    gap: 12rpx;
    font-size: 24rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

.password-panel__item--active {
    color: var(--wm-color-primary, #0b0b0b);
    font-weight: 600;
}

.password-panel__dot {
    width: 12rpx;
    height: 12rpx;
    border-radius: 999rpx;
    background: currentColor;
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
    color: var(--wm-text-secondary, #5f5a50);
}

.agreement-panel__link {
    color: var(--wm-color-primary, #0b0b0b);
    font-weight: 600;
}

.auth-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    font-size: 26rpx;
}

.auth-footer__text {
    color: var(--wm-text-secondary, #5f5a50);
}

.auth-footer__link {
    color: var(--wm-color-primary, #0b0b0b);
    font-weight: 600;
}
</style>
