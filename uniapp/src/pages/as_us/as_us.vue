<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace" hasSafeBottom>
        <BaseNavbar title="关于我们" variant="solid" bg-color="#191713" text-color="#FFFDF8" />

        <view class="as-us-page">
            <!-- 1. 品牌 Hero 展板 -->
            <BaseCard variant="hero" scene="consumer" class="brand-hero" padding="0">
                <view class="brand-hero__inner">
                    <view class="brand-hero__logo-shell">
                        <image
                            v-if="brandLogo"
                            :src="brandLogo"
                            mode="aspectFit"
                            class="brand-hero__logo"
                        />
                        <view v-else class="brand-hero__logo-placeholder">
                            <BaseIcon name="building" :size="48" color="#D9BE82" />
                        </view>
                    </view>

                    <view class="brand-hero__copy">
                        <text class="brand-hero__name">{{ brandName }}</text>
                        <text class="brand-hero__slogan">{{ brandSlogan || '让每一场婚礼，都有迹可循 · 尽善尽美' }}</text>
                    </view>

                    <view class="brand-hero__version-tag" @click="checkAppUpdate">
                        <BaseIcon name="tip" :size="20" color="#D9BE82" />
                        <text class="brand-hero__version-text">{{ versionText }}</text>
                    </view>
                </view>
            </BaseCard>

            <!-- 2. 官方联络与服务信息矩阵 -->
            <BaseCard variant="list" scene="consumer" class="contact-card" padding="10rpx 24rpx">
                <view class="section-heading">
                    <text class="section-heading__title">官方联络与服务</text>
                    <text class="section-heading__desc">诚挚为您提供全方位备婚咨询支持</text>
                </view>

                <view class="contact-list">
                    <!-- 版本信息 -->
                    <view class="contact-row" @click="checkAppUpdate">
                        <view class="contact-row__left">
                            <view class="contact-row__icon-box">
                                <BaseIcon name="tip" :size="30" color="#B8954A" />
                            </view>
                            <view class="contact-row__copy">
                                <text class="contact-row__label">系统版本</text>
                                <text class="contact-row__value">{{ versionText }}</text>
                            </view>
                        </view>
                        <view class="contact-row__action" hover-class="action--hover">
                            <text class="contact-row__action-text">检查更新</text>
                        </view>
                    </view>

                    <!-- 联系电话 -->
                    <view v-if="contactPhone" class="contact-row">
                        <view class="contact-row__left">
                            <view class="contact-row__icon-box">
                                <BaseIcon name="phone" :size="30" color="#B8954A" />
                            </view>
                            <view class="contact-row__copy">
                                <text class="contact-row__label">服务热线</text>
                                <text class="contact-row__value">{{ contactPhone }}</text>
                            </view>
                        </view>
                        <view
                            class="contact-row__action contact-row__action--call"
                            hover-class="action--hover"
                            @click="makePhoneCall"
                        >
                            <BaseIcon name="phone" :size="22" color="#191713" />
                            <text class="contact-row__action-text contact-row__action-text--dark">拨打</text>
                        </view>
                    </view>

                    <!-- 联系邮箱 -->
                    <view v-if="contactEmail" class="contact-row">
                        <view class="contact-row__left">
                            <view class="contact-row__icon-box">
                                <BaseIcon name="mail" :size="30" color="#B8954A" />
                            </view>
                            <view class="contact-row__copy">
                                <text class="contact-row__label">商务与服务邮箱</text>
                                <text class="contact-row__value">{{ contactEmail }}</text>
                            </view>
                        </view>
                        <view
                            class="contact-row__action"
                            hover-class="action--hover"
                            @click="copyEmail"
                        >
                            <text class="contact-row__action-text">复制</text>
                        </view>
                    </view>

                    <!-- 公司地址 -->
                    <view v-if="companyAddress" class="contact-row contact-row--last">
                        <view class="contact-row__left">
                            <view class="contact-row__icon-box">
                                <BaseIcon name="location" :size="30" color="#B8954A" />
                            </view>
                            <view class="contact-row__copy">
                                <text class="contact-row__label">办公地址</text>
                                <text class="contact-row__value contact-row__value--multiline">
                                    {{ companyAddress }}
                                </text>
                            </view>
                        </view>
                    </view>
                </view>
            </BaseCard>

            <!-- 3. 平台使命与愿景卡片 -->
            <BaseCard variant="surface" scene="consumer" class="story-card" padding="24rpx 24rpx">
                <view class="story-card__head">
                    <view class="story-card__icon-wrap">
                        <BaseIcon name="heart" :size="28" color="#B8954A" />
                    </view>
                    <text class="story-card__title">平台愿景与服务初心</text>
                </view>
                <text class="story-card__text">{{ aboutText }}</text>

                <!-- 三大核心保障 -->
                <view class="story-pillars">
                    <view class="story-pillar">
                        <BaseIcon name="shield-check" :size="26" color="#B8954A" />
                        <text class="story-pillar__title">实名甄选</text>
                        <text class="story-pillar__desc">全量严格核验</text>
                    </view>
                    <view class="story-pillar">
                        <BaseIcon name="order" :size="26" color="#B8954A" />
                        <text class="story-pillar__title">履约存管</text>
                        <text class="story-pillar__desc">验收满意结算</text>
                    </view>
                    <view class="story-pillar">
                        <BaseIcon name="service" :size="26" color="#B8954A" />
                        <text class="story-pillar__title">管家专席</text>
                        <text class="story-pillar__desc">全程贴心协调</text>
                    </view>
                </view>
            </BaseCard>

            <!-- 4. 版权声明页脚 -->
            <view class="as-us-footer">
                <text class="as-us-footer__copy">© {{ currentYear }} {{ brandName }} 版权所有</text>
                <text class="as-us-footer__desc">专业数字化婚礼管家协同服务平台</text>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'

const appStore = useAppStore()
const $theme = useThemeStore()
const currentYear = new Date().getFullYear()

const getText = (value: unknown) => (typeof value === 'string' ? value.trim() : '')

const websiteConfig = computed(() => appStore.getWebsiteConfig || {})
const brandName = computed(() => getText(websiteConfig.value.shop_name) || '高端婚礼管理平台')
const brandSlogan = computed(() => getText(websiteConfig.value.shop_slogan))
const brandLogo = computed(() => getText(websiteConfig.value.shop_logo))
const aboutText = computed(
    () =>
        getText(websiteConfig.value.shop_intro) ||
        '我们致力于为每对新人打造全周期、高透明度、有温度的数字化备婚与礼成体验。汇聚甄选手艺人与服务团队，提供从档期协调、签约资金托管到全程满意度督办的一站式护航。'
)

const versionText = computed(() => {
    const raw = getText(appStore.config?.app_update?.version) || getText(appStore.config?.version) || '1.0.0'
    return raw.toLowerCase().startsWith('v') ? raw : `v${raw}`
})

const contactPhone = computed(() => getText(websiteConfig.value.contact_phone))
const contactEmail = computed(() => getText(websiteConfig.value.contact_email))
const companyAddress = computed(() => getText(websiteConfig.value.company_address))

const makePhoneCall = () => {
    if (!contactPhone.value) return
    uni.makePhoneCall({
        phoneNumber: contactPhone.value
    })
}

const copyEmail = () => {
    if (!contactEmail.value) return
    uni.setClipboardData({
        data: contactEmail.value,
        success: () => uni.showToast({ title: '邮箱已复制', icon: 'success' })
    })
}

const checkAppUpdate = () => {
    // #ifdef MP-WEIXIN
    const updateManager = uni.getUpdateManager()
    updateManager.onCheckForUpdate((res) => {
        if (!res.hasUpdate) {
            uni.showToast({ title: '当前已是最新版本', icon: 'none' })
        }
    })
    updateManager.onUpdateReady(() => {
        uni.showModal({
            title: '更新提示',
            content: '新版本已准备好，是否立即重启应用？',
            success: (modalRes) => {
                if (modalRes.confirm) updateManager.applyUpdate()
            }
        })
    })
    // #endif
    // #ifndef MP-WEIXIN
    uni.showToast({ title: `当前版本 ${versionText.value}`, icon: 'none' })
    // #endif
}

onShow(async () => {
    try {
        await appStore.getConfig()
    } catch (error) {
        console.error(error)
    }
})
</script>

<style lang="scss" scoped>
.as-us-page {
    min-height: 100vh;
    padding: 18rpx 22rpx calc(48rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

/* 1. 品牌 Hero 展板 */
.brand-hero {
    --wm-radius-card: 32rpx;
    background: radial-gradient(circle at 12% 0%, rgba(217, 190, 130, 0.22) 0, transparent 42%),
        linear-gradient(145deg, #2b261d 0%, #191713 62%, #3a2a16 100%) !important;
    border: 1rpx solid rgba(217, 190, 130, 0.45) !important;
    box-shadow: 0 16rpx 36rpx rgba(74, 43, 24, 0.12) !important;
}

.brand-hero__inner {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 36rpx 28rpx 32rpx;
    gap: 14rpx;
    position: relative;
}

.brand-hero__logo-shell {
    width: 128rpx;
    height: 128rpx;
    border-radius: 36rpx;
    padding: 6rpx;
    background: rgba(217, 190, 130, 0.22);
    border: 1rpx solid rgba(217, 190, 130, 0.72);
    box-sizing: border-box;
    display: flex;
    align-items: center;
    justify-content: center;
}

.brand-hero__logo {
    width: 100%;
    height: 100%;
    border-radius: 28rpx;
}

.brand-hero__logo-placeholder {
    width: 100%;
    height: 100%;
    border-radius: 28rpx;
    background: #191713;
    display: flex;
    align-items: center;
    justify-content: center;
}

.brand-hero__copy {
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.brand-hero__name {
    font-size: 34rpx;
    font-weight: 900;
    color: #fffdf8;
    line-height: 1.25;
}

.brand-hero__slogan {
    font-size: 21rpx;
    color: rgba(255, 253, 248, 0.75);
    line-height: 1.4;
}

.brand-hero__version-tag {
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
    padding: 6rpx 16rpx;
    margin-top: 4rpx;
    border-radius: 999rpx;
    background: rgba(255, 253, 248, 0.12);
    border: 1rpx solid rgba(217, 190, 130, 0.36);
}

.brand-hero__version-text {
    font-size: 20rpx;
    font-weight: 800;
    color: #d9be82;
}

/* 2. 官方联络矩阵 */
.contact-card {
    background: rgba(255, 253, 248, 0.95) !important;
    border: 1rpx solid rgba(216, 201, 173, 0.72) !important;
    border-radius: 26rpx !important;
}

.section-heading {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    padding: 10rpx 4rpx 12rpx;
    border-bottom: 1rpx dashed rgba(216, 201, 173, 0.5);
}

.section-heading__title {
    font-size: 26rpx;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
}

.section-heading__desc {
    font-size: 20rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

.contact-list {
    display: flex;
    flex-direction: column;
}

.contact-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20rpx 0;
    border-bottom: 1rpx solid rgba(230, 222, 208, 0.6);

    &--last {
        border-bottom: none;
        padding-bottom: 8rpx;
    }
}

.contact-row__left {
    display: flex;
    align-items: center;
    gap: 16rpx;
    min-width: 0;
    flex: 1;
}

.contact-row__icon-box {
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

.contact-row__copy {
    display: flex;
    flex-direction: column;
    gap: 2rpx;
    min-width: 0;
    flex: 1;
}

.contact-row__label {
    font-size: 21rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

.contact-row__value {
    font-size: 26rpx;
    font-weight: 800;
    color: var(--wm-text-primary, #191713);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;

    &--multiline {
        white-space: normal;
        line-height: 1.4;
    }
}

.contact-row__action {
    display: inline-flex;
    align-items: center;
    gap: 4rpx;
    padding: 8rpx 18rpx;
    border-radius: 999rpx;
    background: rgba(248, 242, 228, 0.7);
    border: 1rpx solid rgba(216, 201, 173, 0.85);
    flex-shrink: 0;
    margin-left: 14rpx;

    &--call {
        background: linear-gradient(135deg, #f0dfb8 0%, #d9be82 50%, #b8954a 100%);
        border: none;
        box-shadow: 0 6rpx 16rpx rgba(184, 149, 74, 0.25);
    }
}

.contact-row__action-text {
    font-size: 22rpx;
    font-weight: 800;
    color: var(--wm-text-secondary, #665e52);

    &--dark {
        color: #191713;
    }
}

.action--hover {
    opacity: 0.85;
    transform: scale(0.97);
}

/* 3. 愿景初心卡片 */
.story-card {
    background: rgba(255, 253, 248, 0.95) !important;
    border: 1rpx solid rgba(216, 201, 173, 0.72) !important;
    border-radius: 26rpx !important;
}

.story-card__head {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin-bottom: 14rpx;
}

.story-card__icon-wrap {
    width: 44rpx;
    height: 44rpx;
    border-radius: 14rpx;
    background: rgba(217, 190, 130, 0.18);
    display: flex;
    align-items: center;
    justify-content: center;
}

.story-card__title {
    font-size: 26rpx;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
}

.story-card__text {
    font-size: 23rpx;
    line-height: 1.68;
    color: var(--wm-text-secondary, #665e52);
    display: block;
}

.story-pillars {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12rpx;
    margin-top: 18rpx;
    padding-top: 18rpx;
    border-top: 1rpx dashed rgba(216, 201, 173, 0.5);
}

.story-pillar {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 6rpx;
    padding: 14rpx 8rpx;
    border-radius: 18rpx;
    background: rgba(248, 242, 228, 0.55);
    border: 1rpx solid rgba(216, 201, 173, 0.45);
}

.story-pillar__title {
    font-size: 22rpx;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
}

.story-pillar__desc {
    font-size: 19rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

/* 4. 页脚版权 */
.as-us-footer {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6rpx;
    margin-top: 14rpx;
    padding: 12rpx 0;
}

.as-us-footer__copy {
    font-size: 21rpx;
    font-weight: 700;
    color: var(--wm-text-tertiary, #9a9388);
}

.as-us-footer__desc {
    font-size: 19rpx;
    color: rgba(154, 147, 136, 0.7);
}
</style>
