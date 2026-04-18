<template>
    <AuthPageShell navbarTitle="绑定手机号">
        <template #hero>
            <view class="auth-hero">
                <view class="auth-hero__icon">
                    <tn-icon name="phone" size="54" color="#E85A4F" />
                </view>
                <text class="auth-hero__title">绑定手机号</text>
                <text class="auth-hero__desc">绑定后可接收订单通知。</text>
            </view>
        </template>

        <view class="auth-form wm-form-block">
            <view class="auth-form__group">
                <text class="auth-form__label">手机号</text>
                <BaseInput v-model="formData.mobile" type="tel" placeholder="请输入手机号码">
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
                            :class="{ 'auth-code-btn--active': canGetCode && formData.mobile }"
                            @click="sendSms"
                        >
                            {{ codeTips }}
                        </text>
                    </template>
                </BaseInput>
            </view>

            <BaseButton block size="lg" :disabled="!isFormValid" @click="handleConfirm">
                确认绑定
            </BaseButton>
        </view>
    </AuthPageShell>
</template>

<script setup lang="ts">
import { userBindMobile } from '@/api/user'
import { smsSend } from '@/api/app'
import { SMSEnum } from '@/enums/appEnums'
import { BACK_URL } from '@/enums/constantEnums'
import AuthPageShell from '@/components/business/AuthPageShell.vue'
import { useUserStore } from '@/stores/user'
import cache from '@/utils/cache'
import { computed, reactive, ref } from 'vue'

const userStore = useUserStore()

const codeTips = ref('获取验证码')
const canGetCode = ref(true)

const formData = reactive({
    type: 'bind',
    mobile: '',
    code: ''
})

const isFormValid = computed(() => {
    return formData.mobile && formData.code
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
        scene: SMSEnum.BIND_MOBILE,
        mobile: formData.mobile
    })
    uni.$u.toast('发送成功')
    startCodeCountdown()
}

const redirectAfterBindMobile = () => {
    const backUrl = cache.get(BACK_URL)
    if (!backUrl) {
        uni.navigateBack()
        return
    }

    cache.remove(BACK_URL)
    try {
        uni.reLaunch({ url: backUrl })
    } catch (error) {
        uni.redirectTo({ url: backUrl })
    }
}

const handleConfirm = async () => {
    if (!formData.mobile) return uni.$u.toast('请输入手机号码')
    if (!formData.code) return uni.$u.toast('请输入验证码')

    try {
        await userBindMobile(formData, { token: userStore.temToken })
        uni.$u.toast('绑定成功')
        userStore.login(userStore.temToken!)
        redirectAfterBindMobile()
    } catch (error: any) {
        uni.$u.toast(error || '绑定失败')
    }
}
</script>

<style lang="scss" scoped>
.auth-hero {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 14rpx;
}

.auth-hero__icon {
    width: 108rpx;
    height: 108rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.88);
    border: 1rpx solid rgba(244, 199, 191, 0.52);
    box-shadow: var(--wm-shadow-soft, 0 14rpx 32rpx rgba(214, 185, 167, 0.16));
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
    color: var(--wm-text-secondary, #7f7b78);
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
</style>
