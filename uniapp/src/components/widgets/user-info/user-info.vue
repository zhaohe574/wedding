<template>
    <view class="user-info-wrapper">
        <view class="user-header-shell">
            <view class="user-header-status" :style="{ height: `${navBarMetrics.statusBarHeight}px` }"></view>
            <view class="user-header-nav" :style="{ height: `${navBarMetrics.contentHeight}px` }">
                <text class="user-header-title">我的</text>
            </view>
        </view>

        <view class="user-card mx-[24rpx] rounded-[26rpx] overflow-hidden">
            <view class="user-content">
                <view class="settings-entry" @click.stop="handleSettingClick">
                    <tn-icon name="set" :size="28" color="#7f7b78" />
                </view>

                <view v-if="isLogin" class="profile-row" @click="handleProfileClick">
                    <view class="avatar-shell">
                        <tn-avatar
                            :url="user.avatar || '/static/images/user/default_avatar.png'"
                            :size="112"
                            shape="circle"
                        />
                    </view>
                    <view class="profile-main">
                        <text class="profile-name">{{ displayName }}</text>
                        <text class="profile-subtitle">{{ profileSubtitle }}</text>
                    </view>
                </view>

                <view v-else class="profile-row" @click="handleProfileClick">
                    <view class="avatar-shell">
                        <tn-avatar url="/static/images/user/default_avatar.png" :size="112" shape="circle" />
                    </view>
                    <view class="profile-main">
                        <text class="profile-name">未登录</text>
                        <text class="profile-subtitle">点击登录后完善资料</text>
                    </view>
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
    weddingInfo: {
        type: Object,
        default: () => ({})
    }
})

const navBarMetrics = useNavBarMetrics()

const displayName = computed(() => {
    const realName = String(props.user?.real_name || '').trim()
    if (realName) return realName
    const nickname = String(props.user?.nickname || '').trim()
    return nickname || '未填写称呼'
})

const profileSubtitle = computed(() => {
    const contentSubtitle = String(props.content?.profile_subtitle || '').trim()
    if (contentSubtitle) return contentSubtitle
    const weddingDate = String(props.weddingInfo?.wedding_date || '').trim()
    return weddingDate ? `婚礼主档期：${weddingDate}` : '点击完善资料，更新主档期'
})

const handleProfileClick = () => {
    if (!props.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    uni.navigateTo({ url: '/pages/user_data/user_data' })
}

const handleSettingClick = () => {
    if (!props.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    uni.navigateTo({ url: '/pages/user_set/user_set' })
}
</script>

<style lang="scss" scoped>
.user-info-wrapper {
    padding-bottom: 14rpx;
}

.user-header-shell {
    position: relative;
    padding-bottom: 74rpx;
}

.user-header-status {
    width: 100%;
}

.user-header-nav {
    display: flex;
    align-items: flex-end;
    padding: 0 40rpx 6rpx;
    box-sizing: border-box;
}

.user-header-title {
    max-width: 100%;
    font-size: 50rpx;
    font-weight: 700;
    line-height: 1.05;
    color: #1e2432;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-card {
    position: relative;
    z-index: 1;
    margin-top: -22rpx;
    background: rgba(255, 255, 255, 0.84);
    border: 1rpx solid #efe6e1;
    box-shadow: 0 20rpx 48rpx rgba(214, 185, 167, 0.16);

    &:active {
        transform: translateY(1rpx);
        opacity: 0.94;
    }
}

.user-content {
    position: relative;
    padding: 26rpx 24rpx;
}

.profile-row {
    display: flex;
    align-items: center;
    width: 100%;
    padding-right: 74rpx;
    box-sizing: border-box;
}

.settings-entry {
    position: absolute;
    top: 22rpx;
    right: 24rpx;
    width: 56rpx;
    height: 56rpx;
    border-radius: 999rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 248, 245, 0.96);
    border: 1rpx solid #efe6e1;
    box-shadow: 0 8rpx 22rpx rgba(214, 185, 167, 0.12);

    &:active {
        transform: scale(0.98);
        opacity: 0.92;
    }
}

.avatar-shell {
    width: 112rpx;
    height: 112rpx;
    border-radius: 999rpx;
    margin-right: 20rpx;
    flex-shrink: 0;
    background: #f2d8d0;
}

.profile-main {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.profile-name {
    font-size: 34rpx;
    line-height: 1.28;
    font-weight: 700;
    color: #1e2432;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.profile-subtitle {
    font-size: 25rpx;
    line-height: 1.55;
    color: #7f7b78;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
