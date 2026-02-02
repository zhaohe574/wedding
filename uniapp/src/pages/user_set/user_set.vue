<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar 
            title="设置"
            :front-color="$theme.navColor" 
            :background-color="$theme.navBgColor" 
        />
        <!-- #endif -->
    </page-meta>
    
    <view class="user-set-page">
        <!-- 用户信息卡片 -->
        <navigator :url="`/pages/user_data/user_data`" hover-class="none">
            <view class="user-info-card">
                <view class="card-content">
                    <tn-avatar
                        :url="userInfo.avatar || '/static/images/user/default_avatar.png'"
                        shape="square"
                        :size="120"
                        :border-radius="16"
                    ></tn-avatar>
                    <view class="user-details">
                        <view class="user-name">{{ userInfo.nickname }}</view>
                        <view class="user-account">账号：{{ userInfo.account }}</view>
                    </view>
                    <view class="arrow-icon">
                        <tn-icon name="right" :size="32" color="#999"></tn-icon>
                    </view>
                </view>
            </view>
        </navigator>

        <!-- 账号安全区域 -->
        <view class="section-card">
            <view class="section-title">账号安全</view>
            
            <view class="menu-item" hover-class="menu-item-hover" @click="handlePwd">
                <view class="menu-left">
                    <view class="menu-icon" :style="{ background: getIconBg('primary') }">
                        <tn-icon name="lock" :size="36" color="#FFFFFF"></tn-icon>
                    </view>
                    <text class="menu-text">登录密码</text>
                </view>
                <view class="menu-right">
                    <text class="menu-value">{{ userInfo.has_password ? '已设置' : '未设置' }}</text>
                    <tn-icon name="right" :size="32" color="#C8C9CC"></tn-icon>
                </view>
            </view>

            <!--  #ifdef H5 || MP-WEIXIN -->
            <view 
                v-if="isWeixin"
                class="menu-item" 
                hover-class="menu-item-hover" 
                @click="bindWechatLock"
            >
                <view class="menu-left">
                    <view class="menu-icon" :style="{ background: getIconBg('secondary') }">
                        <tn-icon name="wechat-fill" :size="36" color="#FFFFFF"></tn-icon>
                    </view>
                    <text class="menu-text">绑定微信</text>
                </view>
                <view class="menu-right">
                    <view 
                        class="status-badge"
                        :style="{ 
                            background: userInfo.is_auth ? getStatusBg('success') : getStatusBg('warning'),
                            color: userInfo.is_auth ? '#19BE6B' : '#FF9900'
                        }"
                    >
                        {{ userInfo.is_auth ? '已绑定' : '未绑定' }}
                    </view>
                    <tn-icon v-if="!userInfo.is_auth" name="right" :size="32" color="#C8C9CC"></tn-icon>
                </view>
            </view>
            <!-- #endif -->
        </view>

        <!-- 协议与关于 -->
        <view class="section-card">
            <view class="section-title">协议与关于</view>
            
            <navigator :url="`/pages/agreement/agreement?type=${AgreementEnum.PRIVACY}`" hover-class="none">
                <view class="menu-item" hover-class="menu-item-hover">
                    <view class="menu-left">
                        <view class="menu-icon" :style="{ background: getIconBg('accent') }">
                            <tn-icon name="honor" :size="36" color="#FFFFFF"></tn-icon>
                        </view>
                        <text class="menu-text">隐私政策</text>
                    </view>
                    <view class="menu-right">
                        <tn-icon name="right" :size="32" color="#C8C9CC"></tn-icon>
                    </view>
                </view>
            </navigator>

            <navigator :url="`/pages/agreement/agreement?type=${AgreementEnum.SERVICE}`" hover-class="none">
                <view class="menu-item" hover-class="menu-item-hover">
                    <view class="menu-left">
                        <view class="menu-icon" :style="{ background: getIconBg('cta') }">
                            <tn-icon name="honor" :size="36" color="#FFFFFF"></tn-icon>
                        </view>
                        <text class="menu-text">服务协议</text>
                    </view>
                    <view class="menu-right">
                        <tn-icon name="right" :size="32" color="#C8C9CC"></tn-icon>
                    </view>
                </view>
            </navigator>

            <navigator url="/pages/as_us/as_us" hover-class="none">
                <view class="menu-item menu-item-last" hover-class="menu-item-hover">
                    <view class="menu-left">
                        <view class="menu-icon" :style="{ background: getIconBg('info') }">
                            <tn-icon name="building" :size="36" color="#FFFFFF"></tn-icon>
                        </view>
                        <text class="menu-text">关于我们</text>
                    </view>
                    <view class="menu-right">
                        <text class="menu-value">{{ appStore.config.version }}</text>
                        <tn-icon name="right" :size="32" color="#C8C9CC"></tn-icon>
                    </view>
                </view>
            </navigator>
        </view>

        <!-- 退出登录按钮 -->
        <view class="logout-container">
            <view 
                class="logout-btn"
                :style="{ 
                    background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                    color: $theme.btnColor
                }"
                hover-class="logout-btn-hover"
                @click="showLogout = true"
            >
                <tn-icon name="logout" :size="36" :color="$theme.btnColor"></tn-icon>
                <text class="logout-text">退出登录</text>
            </view>
        </view>

        <!-- 密码操作选择弹窗 -->
        <tn-popup
            v-model="show"
            open-direction="bottom"
            :radius="32"
            :safe-area-inset-bottom="true"
        >
            <view class="password-action-popup">
                <view class="action-header">
                    <text class="action-title">密码管理</text>
                    <view class="close-btn" @click="show = false">
                        <tn-icon name="close" :size="32" color="#999"></tn-icon>
                    </view>
                </view>
                <view class="action-list">
                    <view 
                        class="action-item"
                        hover-class="action-item-hover"
                        @click="handlePasswordAction(0)"
                    >
                        <view class="action-icon" :style="{ background: getIconBg('primary') }">
                            <tn-icon name="edit" :size="36" color="#FFFFFF"></tn-icon>
                        </view>
                        <text class="action-text">修改密码</text>
                        <tn-icon name="right" :size="32" color="#C8C9CC"></tn-icon>
                    </view>
                    <view 
                        class="action-item"
                        hover-class="action-item-hover"
                        @click="handlePasswordAction(1)"
                    >
                        <view class="action-icon" :style="{ background: getIconBg('warning') }">
                            <tn-icon name="help" :size="36" color="#FFFFFF"></tn-icon>
                        </view>
                        <text class="action-text">忘记密码</text>
                        <tn-icon name="right" :size="32" color="#C8C9CC"></tn-icon>
                    </view>
                </view>
            </view>
        </tn-popup>

        <!-- 退出登录确认弹窗 -->
        <tn-popup
            v-model="showLogout"
            open-direction="center"
            :radius="32"
            :overlay-closeable="false"
        >
            <view class="logout-popup">
                <view class="popup-icon" :style="{ background: getIconBg('warning') }">
                    <tn-icon name="warning" :size="64" color="#FFFFFF"></tn-icon>
                </view>
                <view class="popup-title">温馨提示</view>
                <view class="popup-content">
                    是否清除当前登录信息，退出登录？
                </view>
                <view class="popup-actions">
                    <view 
                        class="action-btn action-btn-cancel"
                        hover-class="action-btn-hover"
                        @click="showLogout = false"
                    >
                        取消
                    </view>
                    <view 
                        class="action-btn action-btn-confirm"
                        :style="{ 
                            background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                            color: $theme.btnColor
                        }"
                        hover-class="action-btn-hover"
                        @click="logoutHandle"
                    >
                        确认退出
                    </view>
                </view>
            </view>
        </tn-popup>
    </view>
</template>

<script setup lang="ts">
import { onLoad, onShow } from '@dcloudio/uni-app'
import { computed, ref } from 'vue'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { useThemeStore } from '@/stores/theme'
import { AgreementEnum } from '@/enums/agreementEnums'
import { isWeixinClient } from '@/utils/client'
import { mnpAuthBind, oaAuthBind } from '@/api/account'
import { useLockFn } from '@/hooks/useLockFn'
import { useRouter } from 'uniapp-router-next'
// #ifdef H5
import wechatOa from '@/utils/wechat'
// #endif

const router = useRouter()
const appStore = useAppStore()
const userStore = useUserStore()
const $theme = useThemeStore()
const userInfo = computed(() => userStore.userInfo)

const isWeixin = ref(true)
// #ifdef H5
isWeixin.value = isWeixinClient()
// #endif

const show = ref(false)
const showLogout = ref(false)

// 获取图标背景色
const getIconBg = (type: string) => {
    const colors: Record<string, string> = {
        primary: $theme.primaryColor,
        secondary: $theme.secondaryColor,
        cta: $theme.ctaColor,
        accent: $theme.accentColor,
        info: '#909399',
        warning: '#FF9900',
        success: '#19BE6B'
    }
    return `linear-gradient(135deg, ${colors[type]} 0%, ${colors[type]} 100%)`
}

// 获取状态徽章背景色
const getStatusBg = (type: string) => {
    const colors: Record<string, string> = {
        success: 'rgba(25, 190, 107, 0.1)',
        warning: 'rgba(255, 153, 0, 0.1)',
        error: 'rgba(255, 44, 60, 0.1)'
    }
    return colors[type] || 'rgba(0, 0, 0, 0.05)'
}

// 修改/忘记密码
const handlePasswordAction = (index: number) => {
    show.value = false
    switch (index) {
        case 0:
            router.navigateTo('/pages/change_password/change_password')
            break
        case 1:
            router.navigateTo('/pages/forget_pwd/forget_pwd')
            break
    }
}

const handlePwd = () => {
    if (!userInfo.value.has_password)
        return router.navigateTo('/pages/change_password/change_password?type=set')
    show.value = true
}

// 退出登录
const logoutHandle = () => {
    userStore.logout()
    router.redirectTo('/pages/login/login')
}

const bindWechat = async () => {
    if (userInfo.value.is_auth) return
    try {
        uni.showLoading({
            title: '请稍后...'
        })
        // #ifdef MP-WEIXIN
        const { code }: any = await uni.login({
            provider: 'weixin'
        })
        await mnpAuthBind({
            code: code
        })
        //#endif
        // #ifdef H5
        if (isWeixin.value) {
            wechatOa.getUrl()
        }
        // #endif
        await userStore.getUser()
        uni.hideLoading()
    } catch (e) {
        uni.hideLoading()
        uni.$u.toast(e)
    }
}
const { lockFn: bindWechatLock } = useLockFn(bindWechat)

onShow(() => {
    userStore.getUser()
})

onLoad(async (options) => {
    // #ifdef H5
    const { code } = options
    if (!isWeixin.value) return
    if (code) {
        uni.showLoading({
            title: '请稍后...'
        })
        try {
            await oaAuthBind({ code })
            await userStore.getUser()
        } catch (error) {}
        //用于清空code
        router.redirectTo('/pages/user_set/user_set')
    }

    // #endif
})
</script>

<style lang="scss" scoped>
.user-set-page {
    min-height: 100vh;
    background: linear-gradient(180deg, #F9FAFB 0%, #FFFFFF 100%);
    padding: 24rpx;
    padding-bottom: 48rpx;
}

/* 用户信息卡片 */
.user-info-card {
    background: #FFFFFF;
    border-radius: 24rpx;
    padding: 32rpx;
    margin-bottom: 24rpx;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
    
    &:active {
        transform: translateY(-2rpx);
        box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.1);
    }
}

.card-content {
    display: flex;
    align-items: center;
    gap: 24rpx;
}

.user-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.user-name {
    font-size: 36rpx;
    font-weight: 600;
    color: #333333;
    line-height: 1.4;
}

.user-account {
    font-size: 26rpx;
    color: #999999;
    line-height: 1.5;
}

.arrow-icon {
    display: flex;
    align-items: center;
}

/* 区域卡片 */
.section-card {
    background: #FFFFFF;
    border-radius: 24rpx;
    padding: 24rpx 0;
    margin-bottom: 24rpx;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
    overflow: hidden;
}

.section-title {
    font-size: 28rpx;
    font-weight: 600;
    color: #666666;
    padding: 0 32rpx 16rpx;
    letter-spacing: 0.5rpx;
}

/* 菜单项 */
.menu-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 28rpx 32rpx;
    border-bottom: 1rpx solid #F5F5F5;
    transition: all 0.2s ease;
    
    &:last-child,
    &.menu-item-last {
        border-bottom: none;
    }
}

.menu-item-hover {
    background: #F9FAFB;
}

.menu-left {
    display: flex;
    align-items: center;
    gap: 24rpx;
}

.menu-icon {
    width: 72rpx;
    height: 72rpx;
    border-radius: 16rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.1);
}

.menu-text {
    font-size: 30rpx;
    color: #333333;
    font-weight: 500;
}

.menu-right {
    display: flex;
    align-items: center;
    gap: 16rpx;
}

.menu-value {
    font-size: 26rpx;
    color: #999999;
}

/* 状态徽章 */
.status-badge {
    padding: 8rpx 20rpx;
    border-radius: 24rpx;
    font-size: 24rpx;
    font-weight: 500;
    letter-spacing: 0.5rpx;
}

/* 退出登录按钮 */
.logout-container {
    margin-top: 48rpx;
}

.logout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16rpx;
    height: 96rpx;
    border-radius: 48rpx;
    font-size: 32rpx;
    font-weight: 600;
    box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.3);
    transition: all 0.3s ease;
}

.logout-btn-hover {
    transform: translateY(2rpx);
    box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.3);
    opacity: 0.9;
}

.logout-text {
    font-size: 32rpx;
    font-weight: 600;
}

/* 退出登录弹窗 */
.logout-popup {
    background: #FFFFFF;
    width: 600rpx;
    border-radius: 32rpx;
    padding: 48rpx 40rpx 40rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.popup-icon {
    width: 120rpx;
    height: 120rpx;
    border-radius: 60rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 32rpx;
    box-shadow: 0 8rpx 24rpx rgba(255, 153, 0, 0.3);
}

.popup-title {
    font-size: 36rpx;
    font-weight: 600;
    color: #333333;
    margin-bottom: 24rpx;
    text-align: center;
}

.popup-content {
    font-size: 28rpx;
    color: #666666;
    line-height: 1.6;
    text-align: center;
    margin-bottom: 40rpx;
    padding: 0 20rpx;
}

.popup-actions {
    display: flex;
    gap: 24rpx;
    width: 100%;
}

.action-btn {
    flex: 1;
    height: 88rpx;
    border-radius: 44rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30rpx;
    font-weight: 600;
    transition: all 0.2s ease;
}

.action-btn-cancel {
    background: #F5F5F5;
    color: #666666;
}

.action-btn-confirm {
    box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.3);
}

.action-btn-hover {
    transform: scale(0.98);
    opacity: 0.9;
}

/* 密码操作弹窗 */
.password-action-popup {
    background: #FFFFFF;
    border-radius: 32rpx 32rpx 0 0;
    padding-bottom: constant(safe-area-inset-bottom);
    padding-bottom: env(safe-area-inset-bottom);
}

.action-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 32rpx 32rpx 24rpx;
    border-bottom: 1rpx solid #F5F5F5;
}

.action-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #333333;
}

.close-btn {
    width: 56rpx;
    height: 56rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 28rpx;
    background: #F5F5F5;
    transition: all 0.2s ease;
    
    &:active {
        background: #E5E5E5;
        transform: scale(0.95);
    }
}

.action-list {
    padding: 16rpx 0;
}

.action-item {
    display: flex;
    align-items: center;
    padding: 28rpx 32rpx;
    gap: 24rpx;
    transition: all 0.2s ease;
}

.action-item-hover {
    background: #F9FAFB;
}

.action-icon {
    width: 72rpx;
    height: 72rpx;
    border-radius: 16rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.1);
}

.action-text {
    flex: 1;
    font-size: 30rpx;
    color: #333333;
    font-weight: 500;
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
