<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace">
        <BaseNavbar title="关于我们" variant="solid" bg-color="#191713" text-color="#FFFDF8" />

        <view class="as-us-page wm-page-content">
            <BaseCard variant="hero" scene="consumer" class="brand-card" padding="36rpx">
                <view class="brand-card__inner">
                    <view class="brand-card__logo-shell">
                        <image
                            v-if="brandLogo"
                            :src="brandLogo"
                            mode="aspectFit"
                            class="brand-card__logo"
                        />
                        <BaseIcon v-else name="building" :size="54" color="#D9BE82" />
                    </view>

                    <view class="brand-card__copy">
                        <text class="brand-card__name">{{ brandName }}</text>
                        <text v-if="brandSlogan" class="brand-card__slogan">{{ brandSlogan }}</text>
                    </view>
                </view>
            </BaseCard>

            <BaseCard variant="panel" scene="consumer" class="info-panel" padding="0">
                <view
                    v-for="(item, index) in infoRows"
                    :key="item.label"
                    class="info-row"
                    :class="{
                        'info-row--last': index === infoRows.length - 1,
                        'info-row--multiline': item.multiline
                    }"
                >
                    <view class="info-row__icon">
                        <BaseIcon :name="item.icon" :size="32" color="#B8954A" />
                    </view>
                    <view class="info-row__main">
                        <text class="info-row__label">{{ item.label }}</text>
                        <text class="info-row__value">{{ item.value }}</text>
                    </view>
                </view>
            </BaseCard>

            <BaseCard variant="surface" scene="consumer" class="about-card" padding="32rpx">
                <view class="section-heading">
                    <text class="section-heading__title">关于我们</text>
                </view>
                <text class="about-card__text">{{ aboutText }}</text>
            </BaseCard>

            <view class="as-us-footer">
                <text class="as-us-footer__text">© {{ currentYear }} {{ brandName }}</text>
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

interface InfoRow {
    icon: string
    label: string
    value: string
    multiline?: boolean
}

const appStore = useAppStore()
const $theme = useThemeStore()
const currentYear = new Date().getFullYear()

const getText = (value: unknown) => (typeof value === 'string' ? value.trim() : '')

const formatVersion = (value: string) => {
    if (!value) return '当前版本'
    return value.toLowerCase().startsWith('v') ? value : `v${value}`
}

const websiteConfig = computed(() => appStore.getWebsiteConfig || {})
const brandName = computed(() => getText(websiteConfig.value.shop_name) || '服务中心')
const brandSlogan = computed(() => getText(websiteConfig.value.shop_slogan))
const brandLogo = computed(() => getText(websiteConfig.value.shop_logo))
const aboutText = computed(() => getText(websiteConfig.value.shop_intro) || '提供专业服务与团队支持。')
const versionText = computed(() =>
    formatVersion(getText(appStore.config?.app_update?.version) || getText(appStore.config?.version))
)

const infoRows = computed<InfoRow[]>(() => {
    const rows: InfoRow[] = [
        {
            icon: 'tip',
            label: '当前版本',
            value: versionText.value
        }
    ]
    const contactPhone = getText(websiteConfig.value.contact_phone)
    const contactEmail = getText(websiteConfig.value.contact_email)
    const companyAddress = getText(websiteConfig.value.company_address)

    if (contactPhone) {
        rows.push({
            icon: 'phone',
            label: '联系电话',
            value: contactPhone
        })
    }
    if (contactEmail) {
        rows.push({
            icon: 'mail',
            label: '联系邮箱',
            value: contactEmail
        })
    }
    if (companyAddress) {
        rows.push({
            icon: 'location',
            label: '公司地址',
            value: companyAddress,
            multiline: true
        })
    }

    return rows
})

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
    display: flex;
    flex-direction: column;
    gap: 24rpx;
    min-height: 100vh;
    padding-top: 24rpx;
    padding-bottom: 56rpx;
}

.brand-card__inner {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 28rpx;
    min-width: 0;
}

.brand-card__logo-shell {
    flex-shrink: 0;
    width: 132rpx;
    height: 132rpx;
    border-radius: 32rpx;
    border: 1rpx solid rgba(217, 190, 130, 0.72);
    background: rgba(255, 253, 248, 0.96);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    box-shadow: 0 18rpx 42rpx rgba(0, 0, 0, 0.18);
}

.brand-card__logo {
    width: 100%;
    height: 100%;
}

.brand-card__copy {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.brand-card__name {
    font-size: 40rpx;
    line-height: 1.25;
    font-weight: 900;
    color: var(--wm-text-inverse, #fffdf8);
    word-break: break-word;
}

.brand-card__slogan {
    margin-top: 12rpx;
    font-size: 24rpx;
    line-height: 1.55;
    color: rgba(255, 253, 248, 0.72);
    word-break: break-word;
}

.info-panel {
    display: block;
}

.info-row {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 22rpx;
    min-height: 112rpx;
    padding: 26rpx 30rpx;
    border-bottom: 1rpx solid var(--wm-color-border, #d8c9ad);
}

.info-row--last {
    border-bottom: none;
}

.info-row--multiline {
    align-items: flex-start;
}

.info-row__icon {
    flex-shrink: 0;
    width: 64rpx;
    height: 64rpx;
    border-radius: 20rpx;
    background: var(--wm-color-gold-soft, #f1e5c8);
    display: flex;
    align-items: center;
    justify-content: center;
}

.info-row__main {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24rpx;
}

.info-row--multiline .info-row__main {
    align-items: flex-start;
    flex-direction: column;
    gap: 8rpx;
}

.info-row__label {
    flex-shrink: 0;
    font-size: 24rpx;
    line-height: 1.4;
    font-weight: 800;
    color: var(--wm-text-secondary, #665e52);
}

.info-row__value {
    min-width: 0;
    max-width: 430rpx;
    font-size: 26rpx;
    line-height: 1.45;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
    text-align: right;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.info-row--multiline .info-row__value {
    max-width: 100%;
    text-align: left;
    white-space: normal;
    word-break: break-word;
    overflow: visible;
    text-overflow: clip;
}

.section-heading {
    position: relative;
    z-index: 1;
    margin-bottom: 18rpx;
}

.section-heading__title {
    font-size: 30rpx;
    line-height: 1.3;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
}

.about-card__text {
    position: relative;
    z-index: 1;
    display: block;
    font-size: 26rpx;
    line-height: 1.75;
    color: var(--wm-text-secondary, #665e52);
    text-align: justify;
    word-break: break-word;
}

.as-us-footer {
    display: flex;
    justify-content: center;
    padding: 8rpx 24rpx 0;
}

.as-us-footer__text {
    max-width: 100%;
    font-size: 22rpx;
    line-height: 1.5;
    color: var(--wm-text-tertiary, #8a806f);
    text-align: center;
    word-break: break-word;
}
</style>
