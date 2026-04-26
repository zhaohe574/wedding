<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="设置" />

        <view class="user-set-page wm-page-content">
            <navigator :url="`/pages/user_data/user_data`" hover-class="none">
                <BaseCard variant="hero" class="user-profile-card">
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
                            <tn-icon name="right" :size="32" color="#9A9388" />
                        </view>
                    </view>
                </BaseCard>
            </navigator>

            <BaseCard variant="surface" class="settings-section">
                <view class="settings-section__head">
                    <text class="settings-section__title">账号安全</text>
                    <text class="settings-section__meta">账户与授权</text>
                </view>

                <view class="settings-list">
                    <view class="settings-item" @click="handlePwd">
                        <view class="settings-item__main">
                            <view
                                class="settings-item__icon"
                                :style="{ background: getIconBg('primary') }"
                            >
                                <tn-icon name="lock" :size="34" color="#FFFFFF" />
                            </view>
                            <view class="settings-item__copy">
                                <text class="settings-item__title">登录密码</text>
                                <text class="settings-item__desc">修改登录密码</text>
                            </view>
                        </view>
                        <view class="settings-item__tail">
                            <StatusBadge :tone="userInfo.has_password ? 'success' : 'warning'">
                                {{ userInfo.has_password ? '已设置' : '未设置' }}
                            </StatusBadge>
                            <tn-icon name="right" :size="28" color="#D8D3C7" />
                        </view>
                    </view>

                    <!--  #ifdef H5 || MP-WEIXIN -->
                    <view v-if="isWeixin" class="settings-item" @click="bindWechatLock">
                        <view class="settings-item__main">
                            <view
                                class="settings-item__icon"
                                :style="{ background: getIconBg('secondary') }"
                            >
                                <tn-icon name="wechat-fill" :size="34" color="#FFFFFF" />
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
                            <tn-icon
                                v-if="!userInfo.is_auth"
                                name="right"
                                :size="28"
                                color="#D8D3C7"
                            />
                        </view>
                    </view>
                    <!-- #endif -->
                </view>

                <view class="settings-section__logout">
                    <view class="settings-logout-button" @click="showLogout = true">
                        <text class="settings-logout-button__text">退出登录</text>
                    </view>
                </view>
            </BaseCard>

            <BaseCard variant="surface" class="settings-section">
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
                                    <tn-icon name="honor" :size="34" color="#FFFFFF" />
                                </view>
                                <view class="settings-item__copy">
                                    <text class="settings-item__title">隐私政策</text>
                                    <text class="settings-item__desc">查看协议</text>
                                </view>
                            </view>
                            <view class="settings-item__tail">
                                <tn-icon name="right" :size="28" color="#D8D3C7" />
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
                                    <tn-icon name="honor" :size="34" color="#FFFFFF" />
                                </view>
                                <view class="settings-item__copy">
                                    <text class="settings-item__title">服务协议</text>
                                    <text class="settings-item__desc">查看协议</text>
                                </view>
                            </view>
                            <view class="settings-item__tail">
                                <tn-icon name="right" :size="28" color="#D8D3C7" />
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
                                    <tn-icon name="building" :size="34" color="#FFFFFF" />
                                </view>
                                <view class="settings-item__copy">
                                    <text class="settings-item__title">关于我们</text>
                                    <text class="settings-item__desc">品牌与版本</text>
                                </view>
                            </view>
                            <view class="settings-item__tail settings-item__tail--meta">
                                <text class="settings-item__meta-text">{{ versionText }}</text>
                                <tn-icon name="right" :size="28" color="#D8D3C7" />
                            </view>
                        </view>
                    </navigator>
                </view>
            </BaseCard>

            <BaseOverlayMask :show="show" @close="show = false" />
            <tn-popup
                v-model="show"
                open-direction="bottom"
                :radius="32"
                :safe-area-inset-bottom="true"
                :overlay="false"
                :overlay-closeable="true"
            >
                <view class="settings-popup">
                    <view class="settings-popup__head">
                        <text class="settings-popup__title">密码管理</text>
                        <view class="settings-popup__close" @click="show = false">
                            <tn-icon name="close" :size="30" color="#5F5A50" />
                        </view>
                    </view>
                    <view class="settings-popup__list">
                        <view class="settings-popup__item" @click="handlePasswordAction(0)">
                            <view
                                class="settings-popup__item-icon"
                                :style="{ background: getIconBg('primary') }"
                            >
                                <tn-icon name="edit" :size="32" color="#FFFFFF" />
                            </view>
                            <view class="settings-popup__item-copy">
                                <text class="settings-popup__item-title">修改密码</text>
                                <text class="settings-popup__item-desc"
                                    >适用于当前账号仍记得原密码</text
                                >
                            </view>
                            <tn-icon name="right" :size="28" color="#D8D3C7" />
                        </view>
                        <view class="settings-popup__item" @click="handlePasswordAction(1)">
                            <view
                                class="settings-popup__item-icon"
                                :style="{ background: getIconBg('warning') }"
                            >
                                <tn-icon name="help" :size="32" color="#FFFFFF" />
                            </view>
                            <view class="settings-popup__item-copy">
                                <text class="settings-popup__item-title">忘记密码</text>
                                <text class="settings-popup__item-desc"
                                    >通过验证流程重新设置密码</text
                                >
                            </view>
                            <tn-icon name="right" :size="28" color="#D8D3C7" />
                        </view>
                    </view>
                </view>
            </tn-popup>

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
                        <tn-icon name="warning" :size="60" color="#FFFFFF" />
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

const show = ref(false)
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
    if (!userInfo.value.has_password) {
        return router.navigateTo('/pages/change_password/change_password?type=set')
    }
    show.value = true
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
    display: flex;
    flex-direction: column;
    gap: 24rpx;
    padding-top: 24rpx;
    padding-bottom: 48rpx;
}

.user-profile-card__content,
.settings-item,
.settings-item__main,
.settings-item__tail,
.settings-popup__head,
.settings-popup__item,
.settings-popup__item-copy {
    display: flex;
    align-items: center;
}

.user-profile-card__content {
    gap: 24rpx;
}

.user-profile-card__copy,
.settings-section__head,
.settings-item__copy {
    display: flex;
    flex-direction: column;
}

.user-profile-card__copy,
.settings-item__copy,
.settings-popup__item-copy {
    flex: 1;
    min-width: 0;
}

.user-profile-card__title {
    font-size: 36rpx;
    line-height: 1.35;
    font-weight: 700;
    color: var(--wm-text-primary, #111111);
}

.user-profile-card__meta {
    margin-top: 10rpx;
    font-size: 24rpx;
    line-height: 1.5;
    color: var(--wm-text-secondary, #5f5a50);
}

.user-profile-card__arrow {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 72rpx;
    height: 72rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.68);
}

.settings-section__head {
    gap: 6rpx;
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
    gap: 16rpx;
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
    gap: 20rpx;
}

.settings-item__icon,
.settings-popup__item-icon {
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
.settings-popup__item-title {
    font-size: 28rpx;
    line-height: 1.35;
    font-weight: 600;
    color: var(--wm-text-primary, #111111);
}

.settings-item__desc,
.settings-popup__item-desc {
    margin-top: 6rpx;
    font-size: 22rpx;
    line-height: 1.5;
    color: var(--wm-text-secondary, #5f5a50);
}

.settings-item__tail {
    flex-shrink: 0;
    gap: 12rpx;
}

.settings-item__tail--meta {
    gap: 10rpx;
}

.settings-item__meta-text {
    font-size: 24rpx;
    line-height: 1.3;
    color: var(--wm-text-tertiary, #9a9388);
}

.settings-section__logout {
    margin-top: 24rpx;
    padding-top: 24rpx;
    border-top: 1rpx solid var(--wm-color-border, #e7e2d6);
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

.settings-popup {
    padding: 24rpx 24rpx 32rpx;
    background: rgba(255, 255, 255, 0.98);
    border-radius: 36rpx 36rpx 0 0;
}

.settings-popup__head {
    justify-content: space-between;
    gap: 16rpx;
}

.settings-popup__title {
    font-size: 32rpx;
    line-height: 1.3;
    font-weight: 700;
    color: var(--wm-text-primary, #111111);
}

.settings-popup__close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 64rpx;
    height: 64rpx;
    border-radius: 999rpx;
    background: var(--wm-color-bg-soft, #f3f2ee);
}

.settings-popup__list {
    margin-top: 18rpx;
}

.settings-popup__item {
    gap: 16rpx;
    padding: 24rpx 0;
}

.settings-popup__item + .settings-popup__item {
    border-top: 1rpx solid var(--wm-color-border, #e7e2d6);
}

.settings-popup__item-icon {
    width: 68rpx;
    height: 68rpx;
}

.logout-popup {
    width: 620rpx;
    padding: 40rpx 28rpx 28rpx;
    border-radius: 40rpx;
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
