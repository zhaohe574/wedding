<template>
    <AuthPageShell :navbarTitle="pageTitle">
        <template #hero>
            <view class="password-hero">
                <view class="password-hero__icon">
                    <tn-icon name="lock" size="52" color="#0B0B0B" />
                </view>
                <text class="password-hero__eyebrow">Account Security</text>
                <text class="password-hero__title">{{ pageTitle }}</text>
                <text class="password-hero__desc">{{ pageDesc }}</text>
            </view>
        </template>

        <view class="password-form wm-form-block">
            <view v-if="type !== 'set'" class="password-form__group">
                <text class="password-form__label">原密码</text>
                <BaseInput
                    v-model="formData.old_password"
                    type="password"
                    placeholder="请输入当前密码"
                >
                    <template #prefix>
                        <tn-icon name="lock" size="30" color="#9A9388" />
                    </template>
                </BaseInput>
            </view>

            <view class="password-form__group">
                <text class="password-form__label">新密码</text>
                <BaseInput
                    v-model="formData.password"
                    type="password"
                    placeholder="6-20位数字+字母或符号组合"
                >
                    <template #prefix>
                        <tn-icon name="key" size="30" color="#9A9388" />
                    </template>
                </BaseInput>
            </view>

            <view class="password-form__group">
                <text class="password-form__label">确认密码</text>
                <BaseInput
                    v-model="formData.password_confirm"
                    type="password"
                    placeholder="请再次输入新密码"
                >
                    <template #prefix>
                        <tn-icon name="check-circle" size="30" color="#9A9388" />
                    </template>
                </BaseInput>
            </view>

            <view v-if="formData.password" class="password-tips">
                <view
                    class="password-tips__item"
                    :class="{ 'is-active': formData.password.length >= 6 }"
                >
                    <view class="password-tips__dot" />
                    <text>长度保持在 6 到 20 位之间</text>
                </view>
                <view class="password-tips__item" :class="{ 'is-active': hasMixedPassword }">
                    <view class="password-tips__dot" />
                    <text>建议同时包含字母与数字</text>
                </view>
            </view>

            <BaseButton block size="lg" @click="handleConfirm">
                确认{{ type === 'set' ? '设置' : '修改' }}
            </BaseButton>
        </view>
    </AuthPageShell>
</template>

<script setup lang="ts">
import { userChangePwd } from '@/api/user'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseInput from '@/components/base/BaseInput.vue'
import AuthPageShell from '@/components/business/AuthPageShell.vue'
import { onLoad } from '@dcloudio/uni-app'
import { computed, reactive, ref } from 'vue'

const type = ref('')
const formData = reactive<any>({
    password: '',
    password_confirm: ''
})

const pageTitle = computed(() => (type.value === 'set' ? '设置登录密码' : '修改登录密码'))
const pageDesc = computed(() =>
    type.value === 'set' ? '请设置登录密码。' : '请输入当前密码并设置新密码。'
)
const hasMixedPassword = computed(
    () => /[a-zA-Z]/.test(formData.password) && /[0-9]/.test(formData.password)
)

const validateForm = () => {
    if (type.value !== 'set' && !formData.old_password) {
        uni.$u.toast('请输入原密码')
        return false
    }

    if (!formData.password) {
        uni.$u.toast('请输入新密码')
        return false
    }

    if (formData.password.length < 6 || formData.password.length > 20) {
        uni.$u.toast('密码长度应为6-20位')
        return false
    }

    if (!formData.password_confirm) {
        uni.$u.toast('请输入确认密码')
        return false
    }

    if (formData.password !== formData.password_confirm) {
        uni.$u.toast('两次输入的密码不一致')
        return false
    }

    return true
}

const handleConfirm = async () => {
    if (!validateForm()) return

    try {
        uni.showLoading({
            title: '处理中...',
            mask: true
        })

        await userChangePwd(formData)

        uni.hideLoading()
        uni.showToast({
            title: '操作成功',
            icon: 'success',
            duration: 1500
        })

        setTimeout(() => {
            uni.navigateBack()
        }, 1500)
    } catch (error) {
        uni.hideLoading()
        uni.$u.toast(error || '操作失败')
    }
}

onLoad((options) => {
    type.value = options.type || ''
})
</script>

<style lang="scss" scoped>
.password-hero {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 14rpx;
}

.password-hero__icon {
    width: 108rpx;
    height: 108rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.88);
    border: 1rpx solid rgba(216, 194, 138, 0.52);
    box-shadow: var(--wm-shadow-soft, 0 14rpx 32rpx rgba(17, 17, 17, 0.16));
}

.password-hero__eyebrow {
    font-size: 22rpx;
    font-weight: 600;
    letter-spacing: 0;
    text-transform: uppercase;
    color: var(--wm-color-primary, #0b0b0b);
}

.password-hero__title {
    font-size: 52rpx;
    font-weight: 700;
    line-height: 1.18;
    color: var(--wm-text-primary, #111111);
}

.password-hero__desc {
    font-size: 26rpx;
    line-height: 1.65;
    color: var(--wm-text-secondary, #5f5a50);
}

.password-form {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.password-form__group {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.password-form__label {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #5f5a50);
}

.password-tips {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
    padding: 20rpx 22rpx;
    border-radius: var(--wm-radius-card-soft, 20rpx);
    background: var(--wm-color-bg-soft, #ffffff);
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
}

.password-tips__item {
    display: flex;
    align-items: center;
    gap: 12rpx;
    font-size: 24rpx;
    color: var(--wm-text-secondary, #5f5a50);

    &.is-active {
        color: var(--wm-color-primary, #0b0b0b);
        font-weight: 600;
    }
}

.password-tips__dot {
    width: 14rpx;
    height: 14rpx;
    border-radius: 999rpx;
    background: currentColor;
}
</style>
