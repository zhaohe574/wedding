<template>
    <view class="user-info-wrapper">
        <view
            class="user-header-shell"
            :style="{ '--user-header-nav-bg': $theme.navBgColor || '#0F141D' }"
        >
            <tn-navbar
                fixed
                :bg-color="$theme.navBgColor || '#FFFFFF'"
                :text-color="$theme.navColor || '#111827'"
                :back-icon="''"
                :home-icon="''"
                :safe-area-inset-right="false"
                :bottom-shadow="false"
            >
                <text class="user-header-title">个人中心</text>
            </tn-navbar>
        </view>

        <view class="user-card mx-[24rpx] rounded-[24rpx] overflow-hidden">
            <view class="user-content p-[32rpx]">
                <view class="flex items-center justify-between">
                    <view
                        v-if="isLogin"
                        class="flex items-center flex-1"
                        @click="navigateTo('/pages/user_data/user_data')"
                    >
                        <view class="avatar-wrapper">
                            <tn-avatar
                                :url="user.avatar || '/static/images/user/default_avatar.png'"
                                :size="140"
                                shape="circle"
                            />
                            <view
                                v-if="user.is_vip"
                                class="vip-badge"
                                :style="{ backgroundColor: $theme.accentColor }"
                            >
                                <tn-icon name="vip" size="20" color="#FFFFFF" />
                                <text class="vip-text">VIP</text>
                            </view>
                        </view>

                        <view class="user-info-section ml-[24rpx] flex-1">
                            <view class="flex items-center mb-[12rpx]">
                                <text class="user-nickname">{{ user.nickname }}</text>
                                <view
                                    v-if="user.is_verified"
                                    class="verified-badge"
                                    :style="{ backgroundColor: $theme.primaryColor + '15' }"
                                >
                                    <text class="verified-text" :style="{ color: $theme.primaryColor }">
                                        已认证
                                    </text>
                                </view>
                            </view>
                            <view class="flex items-center account-row" @click.stop="copy(user.account)">
                                <text class="account-text">账号：{{ user.account }}</text>
                                <tn-icon name="copy" size="28" color="#94A3B8" class="ml-[8rpx]" />
                            </view>
                        </view>
                    </view>

                    <navigator
                        v-else
                        class="flex items-center flex-1"
                        hover-class="none"
                        url="/pages/login/login"
                    >
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

                    <navigator
                        v-if="isLogin"
                        hover-class="none"
                        url="/pages/user_set/user_set"
                        class="ml-[24rpx]"
                    >
                        <view
                            class="setting-btn"
                            :style="{ backgroundColor: $theme.primaryColor + '15' }"
                        >
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

defineProps({
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

.user-header-shell {
    position: relative;
    padding-bottom: 84rpx;
    border-bottom-left-radius: 42rpx;
    border-bottom-right-radius: 42rpx;
    overflow: hidden;
    background:
        radial-gradient(circle at top left, rgba(255, 255, 255, 0.18) 0, transparent 36%),
        linear-gradient(
            145deg,
            var(--user-header-nav-bg, #0f141d) 0%,
            rgba(25, 32, 45, 0.96) 52%,
            rgba(76, 58, 29, 0.94) 100%
        );
}

.user-header-title {
    max-width: 100%;
    font-size: 32rpx;
    font-weight: 600;
    line-height: 1.2;
    color: var(--cinema-text-inverse, #fff8ea);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    letter-spacing: 1rpx;
}

.user-card {
    position: relative;
    z-index: 1;
    margin-top: -34rpx;
    background: var(--cinema-surface-elevated, #fffdf8);
    border: 1rpx solid var(--cinema-border, rgba(198, 168, 106, 0.24));
    box-shadow: var(--cinema-shadow-medium, 0 20rpx 52rpx rgba(8, 10, 16, 0.12));
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
                color: #ffffff;
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
            color: var(--cinema-text-primary, #151a23);
        }

        .verified-badge {
            margin-left: 12rpx;
            padding: 6rpx 14rpx;
            border-radius: 999rpx;

            .verified-text {
                font-size: 22rpx;
                font-weight: 500;
            }
        }

        .account-row {
            .account-text {
                font-size: 26rpx;
                color: var(--cinema-text-secondary, #5d6472);
            }
        }
    }

    .login-tip {
        font-size: 26rpx;
        color: var(--cinema-text-secondary, #5d6472);
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
    border: 1rpx solid var(--cinema-border, rgba(198, 168, 106, 0.24));
    box-shadow: var(--cinema-shadow-soft, 0 18rpx 44rpx rgba(8, 10, 16, 0.08));
    transition: all 0.2s ease;

    &:active {
        transform: scale(0.95);
        opacity: 0.8;
    }
}
</style>
