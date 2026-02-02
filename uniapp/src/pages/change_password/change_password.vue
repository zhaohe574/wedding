<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar 
            :title="type == 'set' ? '设置登录密码' : '修改登录密码'"
            :front-color="$theme.navColor" 
            :background-color="$theme.navBgColor" 
        />
        <!-- #endif -->
    </page-meta>
    
    <view class="change-password-page">
        <!-- 顶部图标区域 -->
        <view class="header-section">
            <view class="icon-wrapper" :style="{ background: getIconBg() }">
                <tn-icon name="lock" :size="64" color="#FFFFFF"></tn-icon>
            </view>
            <view class="header-title">
                {{ type == 'set' ? '设置登录密码' : '修改登录密码' }}
            </view>
            <view class="header-subtitle">
                {{ type == 'set' ? '为了您的账号安全，请设置登录密码' : '请输入原密码和新密码' }}
            </view>
        </view>

        <!-- 表单卡片 -->
        <view class="form-card">
            <!-- 原密码 -->
            <view v-if="type != 'set'" class="form-item">
                <view class="form-label">
                    <tn-icon name="lock" :size="28" :color="$theme.primaryColor"></tn-icon>
                    <text class="label-text">原密码</text>
                </view>
                <view class="input-wrapper">
                    <tn-input
                        v-model="formData.old_password"
                        type="password"
                        placeholder="请输入原来的密码"
                        :border="false"
                        :custom-style="inputStyle"
                    />
                </view>
            </view>

            <!-- 新密码 -->
            <view class="form-item">
                <view class="form-label">
                    <tn-icon name="key" :size="28" :color="$theme.primaryColor"></tn-icon>
                    <text class="label-text">新密码</text>
                </view>
                <view class="input-wrapper">
                    <tn-input
                        v-model="formData.password"
                        type="password"
                        placeholder="6-20位数字+字母或符号组合"
                        :border="false"
                        :custom-style="inputStyle"
                    />
                </view>
            </view>

            <!-- 确认密码 -->
            <view class="form-item">
                <view class="form-label">
                    <tn-icon name="check-circle" :size="28" :color="$theme.primaryColor"></tn-icon>
                    <text class="label-text">确认密码</text>
                </view>
                <view class="input-wrapper">
                    <tn-input
                        v-model="formData.password_confirm"
                        type="password"
                        placeholder="再次输入新密码"
                        :border="false"
                        :custom-style="inputStyle"
                    />
                </view>
            </view>
        </view>

        <!-- 确认按钮 -->
        <view class="submit-section">
            <view 
                class="submit-btn"
                :style="{ 
                    background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                    color: $theme.btnColor
                }"
                hover-class="submit-btn-hover"
                @click="handleConfirm"
            >
                <tn-icon name="check" :size="32" :color="$theme.btnColor"></tn-icon>
                <text class="submit-text">确认{{ type == 'set' ? '设置' : '修改' }}</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { userChangePwd } from '@/api/user'
import { useThemeStore } from '@/stores/theme'
import { onLoad } from '@dcloudio/uni-app'
import { computed, reactive, ref } from 'vue'

const $theme = useThemeStore()
const type = ref('')
const formData = reactive<any>({
    password: '',
    password_confirm: ''
})

// 获取图标背景渐变
const getIconBg = () => {
    return `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`
}

// 输入框样式
const inputStyle = computed(() => ({
    fontSize: '30rpx',
    color: '#333333'
}))

// 表单验证（简化版，主要校验由后端完成）
const validateForm = () => {
    if (type.value != 'set' && !formData.old_password) {
        uni.$u.toast('请输入原密码')
        return false
    }
    
    if (!formData.password) {
        uni.$u.toast('请输入新密码')
        return false
    }
    
    // 只做基本长度校验
    if (formData.password.length < 6 || formData.password.length > 20) {
        uni.$u.toast('密码长度应为6-20位')
        return false
    }
    
    if (!formData.password_confirm) {
        uni.$u.toast('请输入确认密码')
        return false
    }
    
    if (formData.password != formData.password_confirm) {
        uni.$u.toast('两次输入的密码不一致')
        return false
    }
    
    return true
}

// 确认修改
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
.change-password-page {
    min-height: 100vh;
    background: linear-gradient(180deg, #F9FAFB 0%, #FFFFFF 100%);
    padding: 32rpx;
}

/* 顶部区域 */
.header-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 40rpx;
}

.icon-wrapper {
    width: 120rpx;
    height: 120rpx;
    border-radius: 60rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 24rpx;
    box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.3);
}

.header-title {
    font-size: 36rpx;
    font-weight: 600;
    color: #333333;
    margin-bottom: 12rpx;
    text-align: center;
}

.header-subtitle {
    font-size: 24rpx;
    color: #999999;
    text-align: center;
    line-height: 1.5;
}

/* 表单卡片 */
.form-card {
    background: #FFFFFF;
    border-radius: 24rpx;
    padding: 32rpx;
    margin-bottom: 24rpx;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
}

.form-item {
    margin-bottom: 32rpx;
    
    &:last-child {
        margin-bottom: 0;
    }
}

.form-label {
    display: flex;
    align-items: center;
    gap: 10rpx;
    margin-bottom: 16rpx;
}

.label-text {
    font-size: 28rpx;
    font-weight: 500;
    color: #333333;
}

.input-wrapper {
    background: #F9FAFB;
    border-radius: 16rpx;
    padding: 20rpx;
    border: 2rpx solid #E5E7EB;
    transition: all 0.2s ease;
    
    &:focus-within {
        background: #FFFFFF;
        border-color: var(--tn-color-primary, #7C3AED);
        box-shadow: 0 0 0 6rpx rgba(124, 58, 237, 0.1);
    }
}

/* 提交按钮 */
.submit-section {
    margin-top: 32rpx;
}

.submit-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    height: 88rpx;
    border-radius: 44rpx;
    font-size: 30rpx;
    font-weight: 600;
    box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.3);
    transition: all 0.3s ease;
}

.submit-btn-hover {
    transform: translateY(2rpx);
    box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.3);
    opacity: 0.9;
}

.submit-text {
    font-size: 30rpx;
    font-weight: 600;
}

/* 响应式适配 */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
