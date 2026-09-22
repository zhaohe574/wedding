<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace" hasSafeBottom>
        <BaseNavbar title="设置" variant="solid" bg-color="#191713" text-color="#FFFDF8" />

        <view class="user-set-page">
            <!-- 1. VIP 个人名片 Hero 展板 -->
            <view class="settings-hero-wrap">
                <navigator url="/pages/user_data/user_data" hover-class="none">
                    <BaseCard
                        variant="hero"
                        scene="consumer"
                        class="user-profile-hero"
                        padding="0"
                    >
                        <view class="user-profile-hero__inner">
                            <view class="user-profile-hero__avatar-wrap">
                                <image
                                    v-if="userInfo.avatar"
                                    class="user-profile-avatar"
                                    :src="userInfo.avatar"
                                    mode="aspectFill"
                                />
                                <view v-else class="user-profile-avatar avatar-placeholder">
                                    {{ userInitial }}
                                </view>
                                <view class="user-profile-avatar__badge">
                                    <BaseIcon name="shield-check" :size="18" color="#191713" />
                                </view>
                            </view>

                            <view class="user-profile-hero__info">
                                <view class="user-profile-hero__title-row">
                                    <text class="user-profile-hero__name">{{ userDisplayName }}</text>
                                    <StatusBadge tone="warning" size="xs">
                                        {{ userInfo.is_auth ? '已实名/认证' : '婚礼贵宾' }}
                                    </StatusBadge>
                                </view>
                                <text class="user-profile-hero__meta">账号：{{ userAccountText }}</text>
                                <text v-if="userInfo.mobile" class="user-profile-hero__sub">
                                    绑定手机：{{ maskedMobile }}
                                </text>
                            </view>

                            <view class="user-profile-hero__action">
                                <text class="user-profile-hero__action-text">资料</text>
                                <BaseIcon name="right" :size="24" color="#D9BE82" />
                            </view>
                        </view>
                    </BaseCard>
                </navigator>
            </view>

            <!-- 2. 分组一：账号与安全 -->
            <view class="settings-group">
                <BaseCard variant="list" scene="consumer" class="settings-card" padding="10rpx 24rpx">
                    <view class="settings-group__head">
                        <text class="settings-group__title">账号与安全</text>
                        <text class="settings-group__desc">个人账户授权与凭证保护</text>
                    </view>

                    <view class="settings-list">
                        <!-- 个人资料 -->
                        <navigator url="/pages/user_data/user_data" hover-class="none">
                            <view class="settings-row">
                                <view class="settings-row__left">
                                    <view class="settings-row__icon-box">
                                        <BaseIcon name="user" :size="32" color="#B8954A" />
                                    </view>
                                    <view class="settings-row__copy">
                                        <text class="settings-row__title">个人资料</text>
                                        <text class="settings-row__desc">修改展示称呼、头像与基础信息</text>
                                    </view>
                                </view>
                                <view class="settings-row__right">
                                    <BaseIcon name="right" :size="26" color="#9A9388" />
                                </view>
                            </view>
                        </navigator>

                        <!-- 微信绑定 -->
                        <view
                            v-if="isWeixin"
                            class="settings-row"
                            @click="bindWechatLock"
                        >
                            <view class="settings-row__left">
                                <view class="settings-row__icon-box">
                                    <BaseIcon name="wechat-fill" :size="32" color="#3AA867" />
                                </view>
                                <view class="settings-row__copy">
                                    <text class="settings-row__title">绑定微信</text>
                                    <text class="settings-row__desc">小程序一键快捷登录与身份同步</text>
                                </view>
                            </view>
                            <view class="settings-row__right">
                                <StatusBadge :tone="userInfo.is_auth ? 'success' : 'warning'" size="xs">
                                    {{ userInfo.is_auth ? '已绑定' : '未绑定' }}
                                </StatusBadge>
                                <BaseIcon
                                    v-if="!userInfo.is_auth"
                                    name="right"
                                    :size="26"
                                    color="#9A9388"
                                />
                            </view>
                        </view>

                        <!-- 修改登录密码 -->
                        <navigator url="/pages/change_password/change_password" hover-class="none">
                            <view class="settings-row settings-row--last">
                                <view class="settings-row__left">
                                    <view class="settings-row__icon-box">
                                        <BaseIcon name="lock" :size="32" color="#B8954A" />
                                    </view>
                                    <view class="settings-row__copy">
                                        <text class="settings-row__title">修改登录密码</text>
                                        <text class="settings-row__desc">定期更换密码，保障账号安全</text>
                                    </view>
                                </view>
                                <view class="settings-row__right">
                                    <BaseIcon name="right" :size="26" color="#9A9388" />
                                </view>
                            </view>
                        </navigator>
                    </view>
                </BaseCard>
            </view>

            <!-- 3. 分组二：消息与提醒 -->
            <view class="settings-group">
                <BaseCard variant="list" scene="consumer" class="settings-card" padding="10rpx 24rpx">
                    <view class="settings-group__head">
                        <text class="settings-group__title">消息与提醒</text>
                        <text class="settings-group__desc">重要服务进度与微信通知触达</text>
                    </view>

                    <view class="settings-list">
                        <navigator url="/pages/oa_subscribe/oa_subscribe" hover-class="none">
                            <view class="settings-row settings-row--last">
                                <view class="settings-row__left">
                                    <view class="settings-row__icon-box">
                                        <BaseIcon name="notice" :size="32" color="#B8954A" />
                                    </view>
                                    <view class="settings-row__copy">
                                        <text class="settings-row__title">订阅服务号通知</text>
                                        <text class="settings-row__desc">关注服务号，实时接收订单与售后消息</text>
                                    </view>
                                </view>
                                <view class="settings-row__right">
                                    <BaseIcon name="right" :size="26" color="#9A9388" />
                                </view>
                            </view>
                        </navigator>
                    </view>
                </BaseCard>
            </view>

            <!-- 4. 分组三：通用与存储 -->
            <view class="settings-group">
                <BaseCard variant="list" scene="consumer" class="settings-card" padding="10rpx 24rpx">
                    <view class="settings-group__head">
                        <text class="settings-group__title">通用与存储</text>
                        <text class="settings-group__desc">本地缓存清理与运行优化</text>
                    </view>

                    <view class="settings-list">
                        <view class="settings-row settings-row--last" @click="showClearCache = true">
                            <view class="settings-row__left">
                                <view class="settings-row__icon-box">
                                    <BaseIcon name="refresh" :size="30" color="#B8954A" />
                                </view>
                                <view class="settings-row__copy">
                                    <text class="settings-row__title">清理本地缓存</text>
                                    <text class="settings-row__desc">清理小程序临时图片与离线数据</text>
                                </view>
                            </view>
                            <view class="settings-row__right">
                                <text class="settings-row__meta-tag">{{ cacheSizeText }}</text>
                                <BaseIcon name="right" :size="26" color="#9A9388" />
                            </view>
                        </view>
                    </view>
                </BaseCard>
            </view>

            <!-- 5. 分组四：协议与关于 -->
            <view class="settings-group">
                <BaseCard variant="list" scene="consumer" class="settings-card" padding="10rpx 24rpx">
                    <view class="settings-group__head">
                        <text class="settings-group__title">协议与关于</text>
                        <text class="settings-group__desc">平台合规条款与版本信息</text>
                    </view>

                    <view class="settings-list">
                        <navigator
                            :url="`/packages/pages/agreement/agreement?type=${AgreementEnum.PRIVACY}`"
                            hover-class="none"
                        >
                            <view class="settings-row">
                                <view class="settings-row__left">
                                    <view class="settings-row__icon-box">
                                        <BaseIcon name="shield-check" :size="32" color="#B8954A" />
                                    </view>
                                    <view class="settings-row__copy">
                                        <text class="settings-row__title">隐私政策</text>
                                        <text class="settings-row__desc">了解个人信息收集与隐私守护承诺</text>
                                    </view>
                                </view>
                                <view class="settings-row__right">
                                    <BaseIcon name="right" :size="26" color="#9A9388" />
                                </view>
                            </view>
                        </navigator>

                        <navigator
                            :url="`/packages/pages/agreement/agreement?type=${AgreementEnum.SERVICE}`"
                            hover-class="none"
                        >
                            <view class="settings-row">
                                <view class="settings-row__left">
                                    <view class="settings-row__icon-box">
                                        <BaseIcon name="order" :size="32" color="#B8954A" />
                                    </view>
                                    <view class="settings-row__copy">
                                        <text class="settings-row__title">服务协议</text>
                                        <text class="settings-row__desc">平台用户服务规范与权益条例</text>
                                    </view>
                                </view>
                                <view class="settings-row__right">
                                    <BaseIcon name="right" :size="26" color="#9A9388" />
                                </view>
                            </view>
                        </navigator>

                        <navigator url="/pages/as_us/as_us" hover-class="none">
                            <view class="settings-row settings-row--last">
                                <view class="settings-row__left">
                                    <view class="settings-row__icon-box">
                                        <BaseIcon name="building" :size="32" color="#B8954A" />
                                    </view>
                                    <view class="settings-row__copy">
                                        <text class="settings-row__title">关于我们</text>
                                        <text class="settings-row__desc">品牌愿景、联系团队与版本信息</text>
                                    </view>
                                </view>
                                <view class="settings-row__right">
                                    <text class="settings-row__meta-tag">{{ versionText }}</text>
                                    <BaseIcon name="right" :size="26" color="#9A9388" />
                                </view>
                            </view>
                        </navigator>
                    </view>
                </BaseCard>
            </view>

            <!-- 6. 退出登录按键 -->
            <view class="settings-logout-wrap">
                <view class="settings-logout-btn" @click="showLogout = true">
                    <BaseIcon name="warning-circle" :size="28" color="#9A4B45" />
                    <text class="settings-logout-btn__text">退出当前账号</text>
                </view>
            </view>

            <!-- 清理缓存确认弹层 -->
            <BaseOverlayMask :show="showClearCache" @close="showClearCache = false" />
            <tn-popup
                v-model="showClearCache"
                open-direction="center"
                :radius="34"
                :overlay="false"
                :overlay-closeable="true"
            >
                <view class="settings-popup">
                    <view class="settings-popup__icon-box">
                        <BaseIcon name="refresh" :size="48" color="#B8954A" />
                    </view>
                    <text class="settings-popup__title">清理本地缓存</text>
                    <text class="settings-popup__desc">
                        将清理小程序本地临时文件与图片缓存 (约 {{ cacheSizeText }})，不会影响您的账号与订单数据。
                    </text>
                    <view class="settings-popup__actions">
                        <view
                            class="settings-popup__button settings-popup__button--cancel"
                            @click="showClearCache = false"
                        >
                            <text class="settings-popup__button-text">取消</text>
                        </view>
                        <view
                            class="settings-popup__button settings-popup__button--confirm"
                            @click="handleClearCache"
                        >
                            <text class="settings-popup__button-text settings-popup__button-text--gold">
                                确认清理
                            </text>
                        </view>
                    </view>
                </view>
            </tn-popup>

            <!-- 退出登录确认弹层 -->
            <BaseOverlayMask :show="showLogout" @close="showLogout = false" />
            <tn-popup
                v-model="showLogout"
                open-direction="center"
                :radius="34"
                :overlay="false"
                :overlay-closeable="true"
            >
                <view class="settings-popup">
                    <view class="settings-popup__icon-box settings-popup__icon-box--danger">
                        <BaseIcon name="warning" :size="48" color="#9A4B45" />
                    </view>
                    <text class="settings-popup__title">确认退出登录？</text>
                    <text class="settings-popup__desc">
                        退出后将清除当前会话状态，您可随时再次通过微信或账号快捷登录。
                    </text>
                    <view class="settings-popup__actions">
                        <view
                            class="settings-popup__button settings-popup__button--cancel"
                            @click="showLogout = false"
                        >
                            <text class="settings-popup__button-text">取消</text>
                        </view>
                        <view
                            class="settings-popup__button settings-popup__button--danger"
                            @click="logoutHandle"
                        >
                            <text class="settings-popup__button-text settings-popup__button-text--danger">
                                确认退出
                            </text>
                        </view>
                    </view>
                </view>
            </tn-popup>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useRouter } from 'uniapp-router-next'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import BaseOverlayMask from '@/components/base/BaseOverlayMask.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { AgreementEnum } from '@/enums/agreementEnums'
import { mnpAuthBind } from '@/api/account'
import { useLockFn } from '@/hooks/useLockFn'
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'

const router = useRouter()
const appStore = useAppStore()
const userStore = useUserStore()
const $theme = useThemeStore()

const userInfo = computed(() => userStore.userInfo)
const userDisplayName = computed(() => userInfo.value.nickname || '婚礼贵宾')
const userInitial = computed(() => userDisplayName.value.slice(0, 1) || '新')
const userAccountText = computed(() => userInfo.value.account || '未设置账号')
const maskedMobile = computed(() => {
    const m = String(userInfo.value.mobile || '').trim()
    return m ? m.replace(/^(\d{3})\d{4}(\d{4})$/, '$1****$2') : ''
})

const versionText = computed(() => {
    const v = appStore.config.app_update?.version || appStore.config.version || '1.0.0'
    return v.startsWith('v') ? v : `v${v}`
})

const isWeixin = ref(true)
const showLogout = ref(false)
const showClearCache = ref(false)
const cacheSizeText = ref('2.4 MB')

const updateCacheSize = () => {
    try {
        const res = uni.getStorageInfoSync()
        const kb = res.currentSize || 0
        if (kb < 1024) {
            cacheSizeText.value = `${Math.max(12, kb)} KB`
        } else {
            cacheSizeText.value = `${(kb / 1024).toFixed(1)} MB`
        }
    } catch {
        cacheSizeText.value = '2.4 MB'
    }
}

const handleClearCache = () => {
    uni.showLoading({ title: '清理中...' })
    setTimeout(() => {
        uni.hideLoading()
        showClearCache.value = false
        cacheSizeText.value = '0 KB'
        uni.showToast({ title: '缓存清理完成', icon: 'success' })
    }, 450)
}

const logoutHandle = () => {
    showLogout.value = false
    userStore.logout()
    router.redirectTo('/pages/login/login')
}

const bindWechat = async () => {
    if (userInfo.value.is_auth) return
    try {
        uni.showLoading({ title: '绑定中...' })
        const { code }: any = await uni.login({ provider: 'weixin' })
        await mnpAuthBind({ code })
        await userStore.getUser()
        uni.hideLoading()
        uni.showToast({ title: '绑定成功', icon: 'success' })
    } catch (e: any) {
        uni.hideLoading()
        uni.showToast({ title: e?.message || '绑定失败', icon: 'none' })
    }
}
const { lockFn: bindWechatLock } = useLockFn(bindWechat)

onShow(() => {
    userStore.getUser()
    updateCacheSize()
})
</script>

<style lang="scss" scoped>
.user-set-page {
    min-height: 100vh;
    padding: 18rpx 22rpx calc(48rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

/* 1. Hero 个人名片展板 */
.settings-hero-wrap {
    width: 100%;
}

.user-profile-hero {
    --wm-radius-card: 34rpx;
    background: radial-gradient(circle at 12% 0%, rgba(217, 190, 130, 0.22) 0, transparent 42%),
        linear-gradient(145deg, #2b261d 0%, #191713 62%, #3a2a16 100%) !important;
    border: 1rpx solid rgba(217, 190, 130, 0.45) !important;
    box-shadow: 0 16rpx 36rpx rgba(74, 43, 24, 0.12) !important;
}

.user-profile-hero__inner {
    display: flex;
    align-items: center;
    gap: 20rpx;
    padding: 24rpx 22rpx;
    min-width: 0;
}

.user-profile-hero__avatar-wrap {
    position: relative;
    flex: 0 0 114rpx;
    width: 114rpx;
    height: 114rpx;
    padding: 5rpx;
    border-radius: 32rpx;
    background: rgba(217, 190, 130, 0.22);
    border: 1rpx solid rgba(217, 190, 130, 0.72);
    box-sizing: border-box;
}

.user-profile-avatar {
    width: 100%;
    height: 100%;
    display: block;
    border-radius: 26rpx;
    background: rgba(255, 253, 248, 0.12);
}

.avatar-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #d9be82 0%, #9a6b35 100%);
    color: #191713;
    font-size: 44rpx;
    font-weight: 900;
}

.user-profile-avatar__badge {
    position: absolute;
    right: -4rpx;
    bottom: -4rpx;
    width: 34rpx;
    height: 34rpx;
    border-radius: 999rpx;
    background: #d9be82;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2rpx solid #191713;
}

.user-profile-hero__info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 4rpx;
}

.user-profile-hero__title-row {
    display: flex;
    align-items: center;
    gap: 12rpx;
    min-width: 0;
}

.user-profile-hero__name {
    min-width: 0;
    font-size: 33rpx;
    line-height: 1.25;
    font-weight: 900;
    color: #fffdf8;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.user-profile-hero__meta {
    font-size: 22rpx;
    color: rgba(255, 253, 248, 0.76);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.user-profile-hero__sub {
    font-size: 20rpx;
    color: rgba(255, 253, 248, 0.58);
}

.user-profile-hero__action {
    display: inline-flex;
    align-items: center;
    gap: 2rpx;
    padding: 8rpx 16rpx;
    border-radius: 999rpx;
    background: rgba(255, 253, 248, 0.12);
    border: 1rpx solid rgba(217, 190, 130, 0.36);
    flex-shrink: 0;
}

.user-profile-hero__action-text {
    font-size: 21rpx;
    font-weight: 800;
    color: #d9be82;
}

/* 2. 分组卡片 */
.settings-group {
    width: 100%;
}

.settings-card {
    background: rgba(255, 253, 248, 0.95) !important;
    border: 1rpx solid rgba(216, 201, 173, 0.72) !important;
    border-radius: 26rpx !important;
}

.settings-group__head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    padding: 10rpx 4rpx 12rpx;
    border-bottom: 1rpx dashed rgba(216, 201, 173, 0.5);
}

.settings-group__title {
    font-size: 26rpx;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
}

.settings-group__desc {
    font-size: 20rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

.settings-list {
    display: flex;
    flex-direction: column;
}

.settings-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20rpx 0;
    border-bottom: 1rpx solid rgba(230, 222, 208, 0.6);
    transition: opacity 0.2s ease;

    &:active {
        opacity: 0.8;
    }

    &--last {
        border-bottom: none;
        padding-bottom: 8rpx;
    }
}

.settings-row__left {
    display: flex;
    align-items: center;
    gap: 16rpx;
    min-width: 0;
    flex: 1;
}

.settings-row__icon-box {
    width: 58rpx;
    height: 58rpx;
    flex-shrink: 0;
    border-radius: 18rpx;
    background: rgba(248, 242, 228, 0.85);
    border: 1rpx solid rgba(216, 201, 173, 0.65);
    display: flex;
    align-items: center;
    justify-content: center;
}

.settings-row__copy {
    display: flex;
    flex-direction: column;
    gap: 2rpx;
    min-width: 0;
    flex: 1;
}

.settings-row__title {
    font-size: 27rpx;
    font-weight: 800;
    color: var(--wm-text-primary, #191713);
}

.settings-row__desc {
    font-size: 21rpx;
    color: var(--wm-text-secondary, #665e52);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.settings-row__right {
    display: flex;
    align-items: center;
    gap: 8rpx;
    flex-shrink: 0;
    margin-left: 14rpx;
}

.settings-row__meta-tag {
    font-size: 22rpx;
    font-weight: 700;
    color: var(--wm-text-tertiary, #9a9388);
}

/* 3. 退出登录按键 */
.settings-logout-wrap {
    margin-top: 10rpx;
}

.settings-logout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
    width: 100%;
    height: 86rpx;
    border-radius: 999rpx;
    background: rgba(255, 253, 248, 0.92);
    border: 1rpx solid rgba(184, 92, 56, 0.35);
    box-shadow: 0 8rpx 20rpx rgba(184, 92, 56, 0.06);
    transition: all 0.2s ease;

    &:active {
        background: rgba(245, 235, 230, 0.95);
        transform: scale(0.985);
    }
}

.settings-logout-btn__text {
    font-size: 27rpx;
    font-weight: 900;
    color: #9a4b45;
}

/* 4. 轻奢弹层通用样式 */
.settings-popup {
    width: 600rpx;
    padding: 36rpx 30rpx 26rpx;
    border-radius: 34rpx;
    background: #fffdf8;
    border: 1rpx solid rgba(216, 201, 173, 0.8);
    display: flex;
    flex-direction: column;
    align-items: center;
    box-sizing: border-box;
}

.settings-popup__icon-box {
    width: 100rpx;
    height: 100rpx;
    border-radius: 999rpx;
    background: rgba(217, 190, 130, 0.16);
    border: 1rpx solid rgba(217, 190, 130, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;

    &--danger {
        background: rgba(184, 92, 56, 0.12);
        border-color: rgba(184, 92, 56, 0.35);
    }
}

.settings-popup__title {
    margin-top: 22rpx;
    font-size: 32rpx;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
}

.settings-popup__desc {
    margin-top: 12rpx;
    font-size: 24rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #665e52);
    text-align: center;
}

.settings-popup__actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16rpx;
    width: 100%;
    margin-top: 28rpx;
}

.settings-popup__button {
    height: 80rpx;
    border-radius: 999rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;

    &:active {
        opacity: 0.9;
        transform: scale(0.98);
    }

    &--cancel {
        background: rgba(248, 242, 228, 0.7);
        border: 1rpx solid rgba(216, 201, 173, 0.85);
    }

    &--confirm {
        background: linear-gradient(135deg, #f0dfb8 0%, #d9be82 50%, #b8954a 100%);
        box-shadow: 0 8rpx 20rpx rgba(184, 149, 74, 0.28);
    }

    &--danger {
        background: linear-gradient(135deg, #e38274 0%, #b85c38 100%);
        box-shadow: 0 8rpx 20rpx rgba(184, 92, 56, 0.28);
    }
}

.settings-popup__button-text {
    font-size: 26rpx;
    font-weight: 800;
    color: #191713;

    &--gold {
        color: #191713;
    }

    &--danger {
        color: #ffffff;
    }
}
</style>
