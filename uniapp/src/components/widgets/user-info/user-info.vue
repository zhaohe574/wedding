<template>
    <view class="user-info-wrapper">
        <!-- 用户信息卡片 -->
        <view class="user-card mx-[24rpx] mt-[24rpx] rounded-[24rpx] overflow-hidden">
            <!-- 用户信息区域 -->
            <view class="user-content p-[32rpx]">
                <view class="flex items-center justify-between">
                    <!-- 左侧：头像和信息 -->
                    <view
                        v-if="isLogin"
                        class="flex items-center flex-1"
                        @click="navigateTo('/pages/user_data/user_data')"
                    >
                        <!-- 头像 -->
                        <view class="avatar-wrapper">
                            <tn-avatar 
                                :url="user.avatar || '/static/images/user/default_avatar.png'" 
                                :size="140"
                                shape="circle"
                            />
                            <!-- VIP标识 -->
                            <view
                                v-if="user.is_vip"
                                class="vip-badge"
                                :style="{ backgroundColor: $theme.accentColor }"
                            >
                                <tn-icon name="vip" size="20" color="#FFFFFF" />
                                <text class="vip-text">VIP</text>
                            </view>
                        </view>
                        
                        <!-- 用户信息 -->
                        <view class="user-info-section ml-[24rpx] flex-1">
                            <view class="flex items-center mb-[12rpx]">
                                <text class="user-nickname">{{ user.nickname }}</text>
                                <!-- 认证标识 -->
                                <view
                                    v-if="user.is_verified"
                                    class="verified-badge"
                                    :style="{ backgroundColor: $theme.primaryColor + '15' }"
                                >
                                    <text class="verified-text" :style="{ color: $theme.primaryColor }">已认证</text>
                                </view>
                            </view>
                            <view class="flex items-center account-row" @click.stop="copy(user.account)">
                                <text class="account-text">账号：{{ user.account }}</text>
                                <tn-icon name="copy" size="28" color="#94A3B8" class="ml-[8rpx]" />
                            </view>
                        </view>
                    </view>
                    
                    <!-- 未登录状态 -->
                    <navigator v-else class="flex items-center flex-1" hover-class="none" url="/pages/login/login">
                        <view class="avatar-wrapper">
                            <tn-avatar 
                                url="/static/images/user/default_avatar.png" 
                                :size="140"
                                shape="circle"
                            />
                        </view>
                        <view class="ml-[24rpx]">
                            <text class="user-nickname">未登录</text>
                            <view class="mt-[8rpx]">
                                <text class="login-tip">点击登录享受更多服务</text>
                            </view>
                        </view>
                    </navigator>
                    
                    <!-- 右侧：设置按钮 -->
                    <navigator v-if="isLogin" hover-class="none" url="/pages/user_set/user_set" class="ml-[24rpx]">
                        <view class="setting-btn" :style="{ backgroundColor: $theme.primaryColor + '15' }">
                            <tn-icon name="set" :color="$theme.primaryColor" :size="44"></tn-icon>
                        </view>
                    </navigator>
                </view>
            </view>
        </view>
    </view>
</template>
<script lang="ts" setup>
import { useCopy } from '@/hooks/useCopy'
import { useThemeStore } from '@/stores/theme'

const props = defineProps({
    pageMeta: {
        type: Object,
        default: () => []
    },
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
    }
})

const { copy } = useCopy()
const $theme = useThemeStore()

const navigateTo = (url: string) => {
    uni.navigateTo({
        url
    })
}
</script>

<style lang="scss" scoped>
.user-info-wrapper {
    padding-bottom: 24rpx;
}

.user-card {
    background: #FFFFFF;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);
    transition: all 0.2s ease;
}

    .user-content {
        .avatar-wrapper {
            position: relative;
            flex-shrink: 0;
        
            .vip-badge {
            position: absolute;
            bottom: -8rpx;
            left: 50%;
            transform: translateX(-50%);
            padding: 4rpx 16rpx;
            border-radius: 12rpx;
            display: flex;
            align-items: center;
            box-shadow: 0 4rpx 12rpx rgba(255, 215, 0, 0.4);
            z-index: 10;
            
            .vip-text {
                color: #FFFFFF;
                font-size: 20rpx;
                font-weight: 500;
                margin-left: 4rpx;
            }
        }
    }
    
    .user-info-section {
        .user-nickname {
            font-size: 36rpx;
            font-weight: 600;
            color: #1E293B;
        }
        
        .verified-badge {
            margin-left: 12rpx;
            padding: 4rpx 12rpx;
            border-radius: 8rpx;
            
            .verified-text {
                font-size: 22rpx;
                font-weight: 500;
            }
        }
        
        .account-row {
            .account-text {
                font-size: 26rpx;
                color: #64748B;
            }
        }
    }
    
    .login-tip {
        font-size: 26rpx;
        color: #64748B;
    }
}

.setting-btn {
    width: 80rpx;
    height: 80rpx;
    padding: 18rpx;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    
    &:active {
        transform: scale(0.95);
        opacity: 0.8;
    }
}
</style>
