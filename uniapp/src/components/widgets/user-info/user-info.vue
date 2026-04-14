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
                    <tn-avatar :url="avatarUrl" :size="112" shape="round" />
                </view>
                <view class="profile-main">
                    <text class="profile-name">{{ profileName }}</text>
                    <text class="profile-subtitle">{{ profileSubtitle }}</text>
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
    if (realName) return realName
    const nickname = String(props.user?.nickname || '').trim()
    return nickname || '未填写称呼'
})

const profileName = computed(() => {
    return props.isLogin ? displayName.value : '未登录'
})

const profileSubtitle = computed(() => {
    if (!props.isLogin) {
        return '点击登录后完善资料'
    }
    const contentSubtitle = String(props.content?.profile_subtitle || '').trim()
    if (contentSubtitle) return contentSubtitle
    return '点击完善资料，更新主档期'
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
    color: var(--wm-text-primary, #1e2432);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-card {
    position: relative;
    z-index: 1;
    border-radius: var(--wm-user-profile-radius, 52rpx);
    overflow: hidden;
    background: var(--wm-color-bg-card, rgba(255, 255, 255, 0.84));
    border: 2rpx solid var(--wm-color-border, #efe6e1);
    box-shadow: var(--wm-shadow-soft, 0 24rpx 48rpx rgba(214, 185, 167, 0.1));
    backdrop-filter: blur(28rpx);
    -webkit-backdrop-filter: blur(28rpx);

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
    background: var(--wm-color-primary-soft, #fff1ee);
    overflow: hidden;
}

.profile-main {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 11rpx;
}

.profile-name {
    font-size: 34rpx;
    line-height: 1.42;
    font-weight: 600;
    color: var(--wm-text-primary, #1e2432);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.profile-subtitle {
    font-size: 24rpx;
    line-height: 1.55;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
