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

        <BaseCard class="user-card" variant="listDark" interactive @click="handleProfileClick">
            <view class="profile-row">
                <view class="avatar-shell">
                    <image class="avatar-image" :src="avatarUrl" mode="aspectFill" />
                </view>
                <view class="profile-main">
                    <view class="profile-meta-row">
                        <text class="profile-eyebrow">婚礼档案</text>
                        <StatusBadge
                            :key="profileStatusKey"
                            :tone="profileBadgeTone"
                            :label="profileStatus"
                            size="xs"
                        />
                    </view>
                    <text class="profile-name">{{ profileName }}</text>
                    <text v-if="profileSubtitle" class="profile-subtitle">{{
                        profileSubtitle
                    }}</text>
                </view>
                <view class="profile-action">
                    <text class="profile-action-text">{{ actionText }}</text>
                    <BaseIcon name="right" size="22" color="#191713" />
                </view>
            </view>
        </BaseCard>
    </view>
</template>

<script lang="ts" setup>
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
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

const isAuthenticated = computed(() => Boolean(props.isLogin))

const avatarUrl = computed(() => {
    return String(props.user?.avatar || '').trim() || '/static/images/user/default_avatar.png'
})

const displayName = computed(() => {
    const nickname = String(props.user?.nickname || props.user?.nick_name || '').trim()
    const realName = String(props.user?.real_name || '').trim()
    const account = String(props.user?.account || props.user?.username || '').trim()
    return nickname || realName || account || '未填写称呼'
})

const profileName = computed(() => {
    return isAuthenticated.value ? displayName.value : '未登录'
})

const profileSubtitle = computed(() => {
    if (!isAuthenticated.value) return ''
    const contentSubtitle = String(props.content?.profile_subtitle || '').trim()
    if (contentSubtitle) return contentSubtitle
    return ''
})

const profileBadgeTone = computed(() => {
    return isAuthenticated.value ? 'paid' : 'pending'
})

const profileStatusKey = computed(() => {
    return isAuthenticated.value ? 'profile-status-login' : 'profile-status-guest'
})

const profileStatus = computed(() => {
    return isAuthenticated.value ? '已登录' : '待登录'
})

const actionText = computed(() => {
    return isAuthenticated.value ? '完善资料' : '去登录'
})

const handleProfileClick = () => {
    if (!isAuthenticated.value) {
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
    color: var(--wm-text-primary, #191713);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-card {
    display: block;
    --wm-space-list-panel-y: 0;
    --wm-space-list-panel-x: 0;
    border-radius: 32rpx;
    background: linear-gradient(135deg, #FFFDF8 0%, var(--wm-color-gold-soft, #F1E5C8) 70%, #E3D0A3 100%);
    border: 1.5rpx solid rgba(217, 190, 130, 0.5);
    box-shadow: 0 12rpx 32rpx rgba(74, 43, 24, 0.08);
    overflow: hidden;
}

.profile-row {
    position: relative;
    display: flex;
    align-items: center;
    min-height: 180rpx;
    padding: 30rpx 28rpx;
    box-sizing: border-box;

    &::after {
        content: '';
        position: absolute;
        top: -60rpx;
        right: -60rpx;
        width: 180rpx;
        height: 180rpx;
        border-radius: 999rpx;
        background: radial-gradient(circle, rgba(217, 190, 130, 0.15) 0%, rgba(217, 190, 130, 0) 70%);
        pointer-events: none;
    }
}

.avatar-shell {
    width: 116rpx;
    height: 116rpx;
    border-radius: 999rpx;
    margin-right: 22rpx;
    flex-shrink: 0;
    background: #FAF6EE;
    border: 3rpx solid #D9BE82;
    box-shadow: 0 6rpx 18rpx rgba(184, 149, 74, 0.22);
    overflow: hidden;
}

.avatar-image {
    width: 100%;
    height: 100%;
    display: block;
    border-radius: 999rpx;
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
    gap: 12rpx;
}

.profile-eyebrow {
    font-size: 20rpx;
    line-height: 1;
    font-weight: 700;
    letter-spacing: 1rpx;
    color: #9A6B35;
}

.profile-name {
    font-size: 38rpx;
    line-height: 1.35;
    font-weight: 800;
    color: #191713;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.profile-subtitle {
    font-size: 23rpx;
    line-height: 1.4;
    font-weight: 600;
    color: #665E52;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.profile-action {
    display: flex;
    align-items: center;
    gap: 6rpx;
    height: 58rpx;
    padding: 0 24rpx;
    border-radius: 999rpx;
    margin-left: 16rpx;
    background: linear-gradient(135deg, #F0DFB8 0%, #D9BE82 50%, #B8954A 100%);
    box-shadow: 0 6rpx 16rpx rgba(184, 149, 74, 0.28);
    flex-shrink: 0;

    &:active {
        transform: scale(0.96);
    }
}

.profile-action-text {
    font-size: 22rpx;
    font-weight: 800;
    line-height: 1;
    color: #191713;
}

</style>
