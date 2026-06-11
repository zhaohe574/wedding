<template>
    <page-meta :page-style="$theme.pageStyle" />
    <AuthPageShell navbarTitle="绑定手机号">
        <view class="auth-form">
            <view class="auth-form__group">
                <text class="auth-form__label">手机号</text>
                <BaseInput v-model="formData.mobile" type="tel" placeholder="请输入手机号码">
                    <template #prefix>
                        <BaseIcon name="phone" size="30" color="#9A9388" />
                    </template>
                </BaseInput>
            </view>

            <view class="auth-form__group">
                <text class="auth-form__label">验证码</text>
                <BaseInput v-model="formData.code" placeholder="请输入验证码">
                    <template #prefix>
                        <BaseIcon name="shield-check" size="30" color="#9A9388" />
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

const DEFAULT_BIND_SUCCESS_URL = '/pages/user/user'
const TABBAR_PATHS = new Set(['/pages/index/index', '/pages/dynamic/dynamic', '/pages/user/user'])

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
    if (TABBAR_PATHS.has(pagePath)) {
        uni.switchTab({ url: pagePath })
        return
    }

    uni.redirectTo({
        url: backUrl,
        fail: () => uni.reLaunch({ url: backUrl })
    })
}

const handleConfirm = async () => {
    if (!formData.mobile) return uni.$u.toast('请输入手机号码')
    if (!formData.code) return uni.$u.toast('请输入验证码')

    try {
        await userBindMobile(formData, { token: userStore.temToken })
        uni.$u.toast('绑定成功')
        userStore.login(userStore.temToken!)
        await userStore.getUser()
        userStore.temToken = null
        redirectAfterBindMobile()
    } catch (error: any) {
        uni.$u.toast(error || '绑定失败')
    }
}
</script>

<style lang="scss" scoped>

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
