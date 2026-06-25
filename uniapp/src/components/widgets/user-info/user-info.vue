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
                    <tn-avatar :url="avatarUrl" :size="104" shape="round" />
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
                    <BaseIcon name="right" size="24" color="var(--wm-text-inverse, #FFFDF8)" />
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
}

.profile-row {
    display: flex;
    align-items: center;
    min-height: var(--wm-user-profile-min-height, 176rpx);
    padding: var(--wm-user-profile-padding, 30rpx 16rpx);
    box-sizing: border-box;
}

.avatar-shell {
    width: var(--wm-user-profile-avatar-size, 112rpx);
    height: var(--wm-user-profile-avatar-size, 112rpx);
    border-radius: var(--wm-user-profile-avatar-radius, 56rpx);
    margin-right: var(--wm-user-profile-gap, 24rpx);
    flex-shrink: 0;
    background: var(--wm-color-primary, #191713);
    border: 2rpx solid rgba(217, 190, 130, 0.32);
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
    color: var(--wm-color-gold, #B8954A);
}

.profile-name {
    font-size: 36rpx;
    line-height: 1.42;
    font-weight: 700;
    color: var(--wm-text-primary, #191713);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.profile-subtitle {
    font-size: 24rpx;
    line-height: 1.55;
    font-weight: 600;
    color: var(--wm-text-secondary, #665E52);
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
    background: var(--wm-color-primary, #191713);
    color: var(--wm-text-inverse, #FFFDF8);
    border: 1rpx solid var(--wm-color-champagne, #D9BE82);
    flex-shrink: 0;
}

.profile-action-text {
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1;
}

</style>
