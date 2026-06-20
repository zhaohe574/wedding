<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace" hasSafeBottom>
        <BaseNavbar title="设置" variant="solid" bg-color="#191713" text-color="#FFFDF8" />

        <view class="user-set-page wm-page-content">
            <view class="settings-card-shell settings-card-shell--profile">
                <navigator :url="`/pages/user_data/user_data`" hover-class="none">
                    <BaseCard
                        variant="hero"
                        scene="consumer"
                        class="user-profile-card"
                        padding="32rpx"
                        border-radius="36rpx"
                    >
                        <view class="user-profile-card__content">
                            <tn-avatar
                                :url="userInfo.avatar || '/static/images/user/default_avatar.png'"
                                shape="square"
                                :size="120"
                                :border-radius="20"
                            />
                            <view class="user-profile-card__copy">
                                <text class="user-profile-card__title">{{ userDisplayName }}</text>
                                <text class="user-profile-card__meta">账号：{{ userAccountText }}</text>
                            </view>
                            <view class="user-profile-card__arrow">
                                <BaseIcon name="right" :size="32" color="#9A9388" />
                            </view>
                        </view>
                    </BaseCard>
                </navigator>
            </view>

            <!--  #ifdef H5 || MP-WEIXIN -->
            <view v-if="isWeixin" class="settings-card-shell">
                <BaseCard variant="surface" scene="consumer" class="settings-section">
                    <view class="settings-section__head">
                        <text class="settings-section__title">账号安全</text>
                        <text class="settings-section__meta">账户与授权</text>
                    </view>

                    <view class="settings-list">
                        <view
                            class="settings-item settings-item--last"
                            @click="bindWechatLock"
                        >
                            <view class="settings-item__main">
                                <view
                                    class="settings-item__icon"
                                    :style="{ background: getIconBg('secondary') }"
                                >
                                    <BaseIcon name="wechat-fill" :size="34" color="#FFFFFF" />
                                </view>
                                <view class="settings-item__copy">
                                    <text class="settings-item__title">绑定微信</text>
                                    <text class="settings-item__desc">微信快捷登录</text>
                                </view>
                            </view>
                            <view class="settings-item__tail">
                                <StatusBadge :tone="userInfo.is_auth ? 'success' : 'warning'">
                                    {{ userInfo.is_auth ? '已绑定' : '未绑定' }}
                                </StatusBadge>
                                <BaseIcon
                                    v-if="!userInfo.is_auth"
                                    name="right"
                                    class="settings-item__arrow"
                                    :size="28"
                                    color="#D8D3C7"
                                />
                            </view>
                        </view>
                    </view>
                </BaseCard>
            </view>
            <!-- #endif -->

            <view class="settings-card-shell">
                <BaseCard variant="surface" scene="consumer" class="settings-section">
                    <view class="settings-section__head">
                        <text class="settings-section__title">协议与关于</text>
                        <text class="settings-section__meta">协议与信息</text>
                    </view>

                    <view class="settings-list">
                        <navigator
                            :url="`/packages/pages/agreement/agreement?type=${AgreementEnum.PRIVACY}`"
                            hover-class="none"
                        >
                            <view class="settings-item">
                                <view class="settings-item__main">
                                    <view
                                        class="settings-item__icon"
                                        :style="{ background: getIconBg('accent') }"
                                    >
                                        <BaseIcon name="honor" :size="34" color="#FFFFFF" />
                                    </view>
                                    <view class="settings-item__copy">
                                        <text class="settings-item__title">隐私政策</text>
                                        <text class="settings-item__desc">查看协议</text>
                                    </view>
                                </view>
                                <view class="settings-item__tail">
                                    <BaseIcon
                                        name="right"
                                        class="settings-item__arrow"
                                        :size="28"
                                        color="#D8D3C7"
                                    />
                                </view>
                            </view>
                        </navigator>

                        <navigator
                            :url="`/packages/pages/agreement/agreement?type=${AgreementEnum.SERVICE}`"
                            hover-class="none"
                        >
                            <view class="settings-item">
                                <view class="settings-item__main">
                                    <view
                                        class="settings-item__icon"
                                        :style="{ background: getIconBg('cta') }"
                                    >
                                        <BaseIcon name="honor" :size="34" color="#FFFFFF" />
                                    </view>
                                    <view class="settings-item__copy">
                                        <text class="settings-item__title">服务协议</text>
                                        <text class="settings-item__desc">查看协议</text>
                                    </view>
                                </view>
                                <view class="settings-item__tail">
                                    <BaseIcon
                                        name="right"
                                        class="settings-item__arrow"
                                        :size="28"
                                        color="#D8D3C7"
                                    />
                                </view>
                            </view>
                        </navigator>

                        <navigator url="/pages/as_us/as_us" hover-class="none">
                            <view class="settings-item settings-item--last">
                                <view class="settings-item__main">
                                    <view
                                        class="settings-item__icon"
                                        :style="{ background: getIconBg('info') }"
                                    >
                                        <BaseIcon name="building" :size="34" color="#FFFFFF" />
                                    </view>
                                    <view class="settings-item__copy">
                                        <text class="settings-item__title">关于我们</text>
                                        <text class="settings-item__desc">品牌与版本</text>
                                    </view>
                                </view>
                                <view class="settings-item__tail settings-item__tail--meta">
                                    <text class="settings-item__meta-text">{{ versionText }}</text>
                                    <BaseIcon
                                        name="right"
                                        class="settings-item__arrow"
                                        :size="28"
                                        color="#D8D3C7"
                                    />
                                </view>
                            </view>
                        </navigator>
                    </view>
                </BaseCard>
            </view>

            <view class="settings-card-shell settings-card-shell--logout">
                <view class="settings-logout-panel">
                    <view class="settings-logout-button" @click="showLogout = true">
                        <text class="settings-logout-button__text">退出登录</text>
                    </view>
                </view>
            </view>

            <BaseOverlayMask :show="showLogout" @close="showLogout = false" />
            <tn-popup
                v-model="showLogout"
                open-direction="center"
                :radius="32"
                :overlay="false"
                :overlay-closeable="true"
            >
                <view class="logout-popup">
                    <view class="logout-popup__icon" :style="{ background: getIconBg('warning') }">
                        <BaseIcon name="warning" :size="60" color="#FFFFFF" />
                    </view>
                    <text class="logout-popup__title">确认退出登录？</text>
                    <text class="logout-popup__desc"> 退出后需重新登录。 </text>
                    <view class="logout-popup__actions">
                        <view
                            class="logout-popup__button logout-popup__button--secondary"
                            @click="showLogout = false"
                        >
                            <text class="logout-popup__button-text">取消</text>
                        </view>
                        <view
                            class="logout-popup__button logout-popup__button--danger"
                            @click="logoutHandle"
                        >
                            <text class="logout-popup__button-text">确认退出</text>
                        </view>
                    </view>
                </view>
            </tn-popup>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { onLoad, onShow } from '@dcloudio/uni-app'
import { computed, ref } from 'vue'
import PageShell from '@/components/base/PageShell.vue'
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
const userDisplayName = computed(() => userInfo.value.nickname || '未设置昵称')
const userAccountText = computed(() => userInfo.value.account || '未设置账号')
const versionText = computed(() => appStore.config.version || '当前版本')

const isWeixin = ref(true)
// #ifdef H5
isWeixin.value = isWeixinClient()
// #endif

const showLogout = ref(false)

const getIconBg = (type: string) => {
    const colors: Record<string, string> = {
        primary: $theme.primaryColor,
        secondary: $theme.secondaryColor,
        cta: $theme.ctaColor,
        accent: $theme.accentColor,
        info: '#9A9388',
        warning: '#9F7A2E',
        success: '#4D4A42'
    }
    return `linear-gradient(135deg, ${colors[type]} 0%, ${colors[type]} 100%)`
}

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
            code
        })
        // #endif
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
        } catch (_error) {
            /* 绑定失败静默处理，后续重定向清空 code */
        }
        router.redirectTo('/pages/user_set/user_set')
    }
    // #endif
})
</script>

<style lang="scss" scoped>
.user-set-page {
    padding-top: 24rpx;
    padding-bottom: 48rpx;
}

.settings-card-shell {
    display: block;
    margin-bottom: 28rpx;
}

.settings-card-shell--logout {
    margin-top: 4rpx;
    margin-bottom: 0;
}

.user-profile-card__content,
.settings-item,
.settings-item__main,
.settings-item__tail {
    display: flex;
    align-items: center;
}

.user-profile-card__content {
    position: relative;
    z-index: 1;
}

.user-profile-card__copy,
.settings-section__head,
.settings-item__copy {
    display: flex;
    flex-direction: column;
}

.user-profile-card__copy,
.settings-item__copy {
    flex: 1;
    min-width: 0;
    margin-left: 24rpx;
}

.user-profile-card__title {
    font-size: 36rpx;
    line-height: 1.35;
    font-weight: 900;
    color: var(--wm-text-inverse, #fffdf8);
}

.user-profile-card__meta {
    margin-top: 10rpx;
    font-size: 24rpx;
    line-height: 1.5;
    color: rgba(255, 253, 248, 0.72);
}

.user-profile-card__arrow {
    margin-left: 20rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 72rpx;
    height: 72rpx;
    border-radius: 999rpx;
    background: rgba(255, 253, 248, 0.12);
    border: 1rpx solid rgba(217, 190, 130, 0.34);
}

.settings-section__head {
    margin-bottom: 12rpx;
}

.settings-section__title {
    font-size: 30rpx;
    line-height: 1.3;
    font-weight: 700;
    color: var(--wm-text-primary, #111111);
}

.settings-section__meta {
    font-size: 22rpx;
    line-height: 1.4;
    color: var(--wm-text-secondary, #5f5a50);
}

.settings-list {
    margin-top: 8rpx;
}

.settings-item {
    justify-content: space-between;
    min-height: 112rpx;
    padding: 26rpx 0;
    border-bottom: 1rpx solid var(--wm-color-border, #e7e2d6);
}

.settings-item--last,
.settings-list > navigator:last-child .settings-item,
.settings-list > view:last-child.settings-item {
    border-bottom: none;
    padding-bottom: 4rpx;
}

.settings-item__main {
    flex: 1;
    min-width: 0;
}

.settings-item__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border-radius: 24rpx;
    box-shadow: 0 10rpx 22rpx rgba(11, 11, 11, 0.16);
}

.settings-item__icon {
    width: 72rpx;
    height: 72rpx;
}

.settings-item__title,
.logout-popup__button-text {
    font-size: 28rpx;
    line-height: 1.35;
    font-weight: 600;
    color: var(--wm-text-primary, #111111);
}

.settings-item__desc {
    margin-top: 6rpx;
    font-size: 22rpx;
    line-height: 1.5;
    color: var(--wm-text-secondary, #5f5a50);
}

.settings-item__tail {
    flex-shrink: 0;
    margin-left: 18rpx;
}

.settings-item__tail--meta {
    margin-left: 16rpx;
}

.settings-item__arrow {
    margin-left: 10rpx;
}

.settings-item__meta-text {
    font-size: 24rpx;
    line-height: 1.3;
    color: var(--wm-text-tertiary, #9a9388);
}

.settings-logout-button {
    width: 100%;
    min-height: 88rpx;
    border-radius: 999rpx;
    background: var(--wm-color-danger, #8a4b45);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10rpx 20rpx rgba(138, 75, 69, 0.16);

    &:active {
        opacity: 0.92;
        transform: translateY(2rpx) scale(0.99);
    }
}

.settings-logout-button__text {
    font-size: 28rpx;
    line-height: 1;
    font-weight: 700;
    color: #ffffff;
}

.logout-popup {
    width: 620rpx;
    padding: 40rpx 28rpx 28rpx;
    border-radius: var(--wm-radius-popup, 28rpx);
    background: rgba(255, 255, 255, 0.98);
    display: flex;
    flex-direction: column;
    align-items: center;
    box-sizing: border-box;
}

.logout-popup__icon {
    width: 120rpx;
    height: 120rpx;
    border-radius: 999rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 14rpx 28rpx rgba(159, 122, 46, 0.18);
}

.logout-popup__title {
    margin-top: 28rpx;
    font-size: 36rpx;
    line-height: 1.3;
    font-weight: 700;
    color: var(--wm-text-primary, #111111);
}

.logout-popup__desc {
    margin-top: 18rpx;
    font-size: 26rpx;
    line-height: 1.65;
    color: var(--wm-text-secondary, #5f5a50);
    text-align: center;
}

.logout-popup__actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16rpx;
    width: 100%;
    margin-top: 32rpx;
}

.logout-popup__button {
    min-width: 0;
    min-height: 76rpx;
    border-radius: 999rpx;
    display: flex;
    align-items: center;
    justify-content: center;

    &:active {
        opacity: 0.9;
        transform: translateY(1rpx);
    }
}

.logout-popup__button--secondary {
    background: #ffffff;
    border: 1rpx solid rgba(11, 11, 11, 0.12);
}

.logout-popup__button--secondary .logout-popup__button-text {
    color: var(--wm-text-primary, #111111);
}

.logout-popup__button--danger {
    background: var(--wm-color-danger, #8a4b45);
    box-shadow: 0 8rpx 16rpx rgba(138, 75, 69, 0.14);
}

.logout-popup__button--danger .logout-popup__button-text {
    color: #ffffff;
}

.logout-popup__button-text {
    max-width: 100%;
    font-size: 26rpx;
    line-height: 1;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
