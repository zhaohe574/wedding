<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace" hasSafeBottom>
        <BaseNavbar
            :title="pageTitle"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
            title-align="center"
        />

        <view class="change-pwd-page">
            <!-- 1. 顶部安全防护 Hero 展板 -->
            <BaseCard variant="hero" scene="consumer" class="security-hero" padding="0">
                <view class="security-hero__inner">
                    <view class="security-hero__icon-box">
                        <BaseIcon name="shield-check" :size="38" color="#D9BE82" />
                    </view>
                    <view class="security-hero__copy">
                        <text class="security-hero__title">保护您的婚礼订单与资产安全</text>
                        <text class="security-hero__desc">
                            定期更换登录密码，建议使用 6-20 位字母加数字组合，切勿向他人泄露账号信息。
                        </text>
                    </view>
                </view>
            </BaseCard>

            <!-- 2. 密码表单卡片 -->
            <BaseCard
                variant="list"
                scene="consumer"
                class="password-card"
                padding="24rpx 22rpx"
                border-radius="26rpx"
            >
                <view class="password-form">
                    <!-- 原密码 -->
                    <view v-if="type !== 'set'" class="password-form__group">
                        <text class="password-form__label">原登录密码</text>
                        <BaseInput
                            v-model="formData.old_password"
                            type="password"
                            placeholder="请输入当前使用的密码"
                            clearable
                        >
                            <template #prefix>
                                <BaseIcon name="lock" :size="30" color="#9A9388" />
                            </template>
                        </BaseInput>
                    </view>

                    <!-- 新密码 -->
                    <view class="password-form__group">
                        <text class="password-form__label">新登录密码</text>
                        <BaseInput
                            v-model="formData.password"
                            type="password"
                            placeholder="6-20位数字+字母组合"
                            :maxlength="20"
                            clearable
                        >
                            <template #prefix>
                                <BaseIcon name="key" :size="30" color="#9A9388" />
                            </template>
                        </BaseInput>
                    </view>

                    <!-- 确认密码 -->
                    <view class="password-form__group">
                        <text class="password-form__label">确认新密码</text>
                        <BaseInput
                            v-model="formData.password_confirm"
                            type="password"
                            placeholder="请再次输入新密码"
                            :maxlength="20"
                            clearable
                        >
                            <template #prefix>
                                <BaseIcon name="shield-check" :size="30" color="#9A9388" />
                            </template>
                        </BaseInput>
                    </view>
                </view>
            </BaseCard>

            <!-- 3. 密码安全强度与合规指引 -->
            <view class="password-rules">
                <view class="password-rules__head">
                    <text class="password-rules__title">密码安全标准</text>
                </view>
                <view class="password-rules__list">
                    <view
                        class="password-rules__item"
                        :class="{ 'is-passed': formData.password.length >= 6 && formData.password.length <= 20 }"
                    >
                        <view class="password-rules__icon">
                            <BaseIcon
                                :name="formData.password.length >= 6 ? 'check-circle' : 'warning-circle'"
                                :size="22"
                                :color="formData.password.length >= 6 ? '#3AA867' : '#9A9388'"
                            />
                        </view>
                        <text class="password-rules__text">密码长度在 6 到 20 位之间</text>
                    </view>

                    <view
                        class="password-rules__item"
                        :class="{ 'is-passed': hasMixedPassword }"
                    >
                        <view class="password-rules__icon">
                            <BaseIcon
                                :name="hasMixedPassword ? 'check-circle' : 'warning-circle'"
                                :size="22"
                                :color="hasMixedPassword ? '#3AA867' : '#9A9388'"
                            />
                        </view>
                        <text class="password-rules__text">建议同时包含英文字母与数字</text>
                    </view>

                    <view
                        v-if="formData.password_confirm"
                        class="password-rules__item"
                        :class="{ 'is-passed': isMatchPassword }"
                    >
                        <view class="password-rules__icon">
                            <BaseIcon
                                :name="isMatchPassword ? 'check-circle' : 'warning-circle'"
                                :size="22"
                                :color="isMatchPassword ? '#3AA867' : '#9A4B45'"
                            />
                        </view>
                        <text class="password-rules__text">
                            {{ isMatchPassword ? '两次输入密码一致' : '两次输入的密码不一致' }}
                        </text>
                    </view>
                </view>
            </view>

            <!-- 4. 底部操作按钮 -->
            <view class="change-pwd-actions">
                <BaseButton
                    block
                    variant="dark"
                    size="lg"
                    :loading="submitting"
                    loading-text="正在保存..."
                    @click="handleConfirm"
                >
                    确认{{ type === 'set' ? '设置' : '修改' }}
                </BaseButton>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { userChangePwd } from '@/api/user'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useThemeStore } from '@/stores/theme'
import { showError, showSuccess } from '@/utils/feedback'

const $theme = useThemeStore()

const type = ref('')
const submitting = ref(false)
const formData = reactive({
    old_password: '',
    password: '',
    password_confirm: ''
})

const pageTitle = computed(() => (type.value === 'set' ? '设置登录密码' : '修改登录密码'))

const hasMixedPassword = computed(
    () => /[a-zA-Z]/.test(formData.password) && /[0-9]/.test(formData.password)
)

const isMatchPassword = computed(
    () => Boolean(formData.password && formData.password === formData.password_confirm)
)

const validateForm = () => {
    if (type.value !== 'set' && !formData.old_password) {
        showError('请输入原密码')
        return false
    }

    if (!formData.password) {
        showError('请输入新密码')
        return false
    }

    if (formData.password.length < 6 || formData.password.length > 20) {
        showError('密码长度应为6-20位')
        return false
    }

    if (!formData.password_confirm) {
        showError('请输入确认密码')
        return false
    }

    if (formData.password !== formData.password_confirm) {
        showError('两次输入的密码不一致')
        return false
    }

    return true
}

const handleConfirm = async () => {
    if (!validateForm() || submitting.value) return

    try {
        submitting.value = true
        await userChangePwd(formData)
        showSuccess('密码修改成功', { duration: 1500 })

        setTimeout(() => {
            uni.navigateBack()
        }, 1500)
    } catch (error: any) {
        showError(error?.message || '操作失败，请稍后重试')
    } finally {
        submitting.value = false
    }
}

onLoad((options?: Record<string, string>) => {
    type.value = options?.type || ''
})
</script>

<style lang="scss" scoped>
.change-pwd-page {
    min-height: 100vh;
    padding: 18rpx 22rpx calc(48rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

/* 1. 安全 Hero 展板 */
.security-hero {
    --wm-radius-card: 32rpx;
    background: radial-gradient(circle at 12% 0%, rgba(217, 190, 130, 0.22) 0, transparent 42%),
        linear-gradient(145deg, #2b261d 0%, #191713 62%, #3a2a16 100%) !important;
    border: 1rpx solid rgba(217, 190, 130, 0.45) !important;
}

.security-hero__inner {
    display: flex;
    align-items: center;
    gap: 20rpx;
    padding: 24rpx 22rpx;
}

.security-hero__icon-box {
    width: 80rpx;
    height: 80rpx;
    flex-shrink: 0;
    border-radius: 24rpx;
    background: rgba(217, 190, 130, 0.18);
    border: 1rpx solid rgba(217, 190, 130, 0.65);
    display: flex;
    align-items: center;
    justify-content: center;
}

.security-hero__copy {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.security-hero__title {
    font-size: 29rpx;
    font-weight: 900;
    color: #fffdf8;
    line-height: 1.3;
}

.security-hero__desc {
    font-size: 21rpx;
    line-height: 1.45;
    color: rgba(255, 253, 248, 0.72);
}

/* 2. 表单卡片 */
.password-card {
    background: rgba(255, 253, 248, 0.95) !important;
    border: 1rpx solid rgba(216, 201, 173, 0.72) !important;
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
    font-weight: 800;
    color: var(--wm-text-primary, #191713);
}

/* 3. 密码规则提示 */
.password-rules {
    padding: 20rpx 22rpx;
    border-radius: 24rpx;
    background: rgba(255, 253, 248, 0.88);
    border: 1rpx solid rgba(216, 201, 173, 0.65);
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.password-rules__head {
    padding-bottom: 8rpx;
    border-bottom: 1rpx dashed rgba(216, 201, 173, 0.5);
}

.password-rules__title {
    font-size: 23rpx;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
}

.password-rules__list {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.password-rules__item {
    display: flex;
    align-items: center;
    gap: 12rpx;
    font-size: 22rpx;
    color: var(--wm-text-secondary, #665e52);
    transition: color 0.2s ease;

    &.is-passed {
        color: var(--wm-text-primary, #191713);
        font-weight: 700;
    }
}

.password-rules__icon {
    display: flex;
    align-items: center;
    justify-content: center;
}

/* 4. 底部操作 */
.change-pwd-actions {
    margin-top: 20rpx;
}
</style>
