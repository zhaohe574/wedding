<template>
    <view class="user-info-wrapper">
        <view v-if="showHeader" class="user-header-shell">
            <view
                class="user-header-status"
                :style="{ height: `${navBarMetrics.statusBarHeight}px` }"
            ></view>
            <view class="user-header-nav" :style="{ height: `${navBarMetrics.contentHeight}px` }">
                <text class="user-header-title">我的</text>
            </view>
        </view>

        <view class="user-card" @click="handleProfileClick">
            <view class="profile-row">
                <view class="avatar-shell">
                    <tn-avatar :url="avatarUrl" :size="104" shape="round" />
                </view>
                <view class="profile-main">
                    <view class="profile-meta-row">
                        <text class="profile-eyebrow">婚礼档案</text>
                        <text class="profile-status">{{ profileStatus }}</text>
                    </view>
                    <text class="profile-name">{{ profileName }}</text>
                    <text v-if="profileSubtitle" class="profile-subtitle">{{
                        profileSubtitle
                    }}</text>
                </view>
                <view class="profile-action">
                    <text class="profile-action-text">{{ actionText }}</text>
                    <text class="profile-action-arrow">›</text>
                </view>
            </view>
        </view>
    </view>
</template>

<script lang="ts" setup>
import { useNavBarMetrics } from '@/hooks/useNavBarMetrics'
import { computed } from 'vue'

const props = defineProps({
    content: {
        type: Object,
        default: () => ({})
    },
    styles: {
        type: Object,
        default: () => ({})
    },
    user: {
        type: Object,
        default: () => ({})
    },
    isLogin: {
        type: Boolean
    },
    showHeader: {
        type: Boolean,
        default: true
    }
})

const navBarMetrics = useNavBarMetrics()

const avatarUrl = computed(() => {
    return String(props.user?.avatar || '').trim() || '/static/images/user/default_avatar.png'
})

const displayName = computed(() => {
    const realName = String(props.user?.real_name || '').trim()
    return realName || '未填写称呼'
})

const profileName = computed(() => {
    return props.isLogin ? displayName.value : '未登录'
})

const profileSubtitle = computed(() => {
    if (!props.isLogin) return ''
    const contentSubtitle = String(props.content?.profile_subtitle || '').trim()
    if (contentSubtitle) return contentSubtitle
    return ''
})

const profileStatus = computed(() => {
    return props.isLogin ? '已登录' : '待登录'
})

const actionText = computed(() => {
    return props.isLogin ? '完善资料' : '去登录'
})

const handleProfileClick = () => {
    if (!props.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    uni.navigateTo({ url: '/pages/user_data/user_data' })
}
</script>

<style lang="scss" scoped>
.user-info-wrapper {
    display: flex;
    flex-direction: column;
    gap: var(--wm-user-page-section-gap, 32rpx);
}

.user-header-shell {
    position: relative;
}

.user-header-status {
    width: 100%;
}

.user-header-nav {
    display: flex;
    align-items: flex-end;
    padding: 0 var(--wm-space-page-x, 37rpx);
    box-sizing: border-box;
}

.user-header-title {
    max-width: 100%;
    font-size: 52rpx;
    font-weight: 700;
    line-height: 1.05;
    color: var(--wm-text-primary, #111111);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-card {
    position: relative;
    z-index: 1;
    border-radius: var(--wm-user-profile-radius, 16rpx);
    overflow: hidden;
    background: var(--wm-color-primary, #0b0b0b);
    border: 1rpx solid var(--wm-color-primary, #0b0b0b);
    box-shadow: 0 14rpx 28rpx rgba(11, 11, 11, 0.16);
    backdrop-filter: none;
    -webkit-backdrop-filter: none;

    &:active {
        transform: translateY(1rpx);
        opacity: 0.94;
    }
}

.profile-row {
    display: flex;
    align-items: center;
    min-height: var(--wm-user-profile-min-height, 176rpx);
    padding: var(--wm-user-profile-padding, 32rpx);
    box-sizing: border-box;
}

.avatar-shell {
    width: var(--wm-user-profile-avatar-size, 112rpx);
    height: var(--wm-user-profile-avatar-size, 112rpx);
    border-radius: var(--wm-user-profile-avatar-radius, 56rpx);
    margin-right: var(--wm-user-profile-gap, 24rpx);
    flex-shrink: 0;
    background: var(--wm-color-primary-soft, #f3f2ee);
    border: 2rpx solid rgba(255, 255, 255, 0.16);
    overflow: hidden;
}

.profile-main {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.profile-meta-row {
    display: flex;
    align-items: center;
    gap: 14rpx;
}

.profile-eyebrow {
    font-size: 20rpx;
    line-height: 1;
    font-weight: 700;
    letter-spacing: 0;
    text-transform: uppercase;
    color: var(--wm-color-secondary, #c8a45d);
}

.profile-status {
    padding: 8rpx 14rpx;
    border-radius: 999rpx;
    font-size: 20rpx;
    line-height: 1;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.86);
    background: rgba(255, 255, 255, 0.12);
}

.profile-name {
    font-size: 36rpx;
    line-height: 1.42;
    font-weight: 700;
    color: #ffffff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.profile-subtitle {
    font-size: 24rpx;
    line-height: 1.55;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.72);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.profile-action {
    display: flex;
    align-items: center;
    gap: 8rpx;
    height: 56rpx;
    padding: 0 18rpx;
    border-radius: 999rpx;
    margin-left: 16rpx;
    background: #ffffff;
    color: var(--wm-color-primary, #0b0b0b);
    flex-shrink: 0;
}

.profile-action-text {
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1;
}

.profile-action-arrow {
    font-size: 28rpx;
    line-height: 1;
    color: var(--wm-color-primary, #0b0b0b);
}
</style>
