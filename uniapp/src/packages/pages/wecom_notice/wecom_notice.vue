<template>
    <page-meta :page-style="$theme.pageStyle" />

    <PageShell :scene="pageScene" tone="workspace" hasSafeBottom>
        <BaseNavbar title="企微通知" title-align="left" />

        <view class="wecom-notice-page wm-page-content">
            <view class="notice-panel wm-panel-card">
                <view class="notice-panel__head">
                    <view class="notice-panel__icon">
                        <tn-icon name="notice" size="34" color="#111111" />
                    </view>
                    <view class="notice-panel__copy">
                        <text class="notice-panel__eyebrow">企业微信消息</text>
                        <text class="notice-panel__title">{{ noticeTitle }}</text>
                    </view>
                </view>

                <text class="notice-panel__desc">{{ noticeDesc }}</text>

                <view class="notice-meta">
                    <view class="notice-meta__row">
                        <text class="notice-meta__label">消息类型</text>
                        <text class="notice-meta__value">{{ sceneLabel }}</text>
                    </view>
                    <view v-if="targetId > 0" class="notice-meta__row">
                        <text class="notice-meta__label">关联编号</text>
                        <text class="notice-meta__value">{{ targetId }}</text>
                    </view>
                </view>

                <view class="notice-actions">
                    <view class="notice-action notice-action--primary" @click="handlePrimaryAction">
                        <text class="notice-action__text">{{ primaryActionText }}</text>
                    </view>
                    <view class="notice-action notice-action--ghost" @click="goNotificationCenter">
                        <text class="notice-action__text">查看消息中心</text>
                    </view>
                </view>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'

import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'

const $theme = useThemeStore()
const userStore = useUserStore()

const scene = ref('notice')
const noticeType = ref('')
const targetId = ref(0)

const sceneConfig: Record<
    string,
    {
        label: string
        title: string
        desc: string
        scene: 'consumer' | 'staff' | 'admin'
        actionText: string
        actionUrl: string
    }
> = {
    wecom_test: {
        label: '测试消息',
        title: '企微小程序卡片已连通',
        desc: '当前企业微信应用消息已成功跳转到小程序，可继续核对接收成员、卡片内容和后续业务跳转。',
        scene: 'admin',
        actionText: '进入经营驾驶舱',
        actionUrl: '/packages/pages/admin_dashboard/admin_dashboard'
    },
    advisor_consult: {
        label: '客户咨询',
        title: '有新的客户咨询需要跟进',
        desc: '客户从小程序发起咨询，当前小程序端暂未提供顾问 CRM 详情页，请进入后台工作台处理客户分配与跟进记录。',
        scene: 'admin',
        actionText: '进入经营驾驶舱',
        actionUrl: '/packages/pages/admin_dashboard/admin_dashboard'
    },
    loss_warning: {
        label: '流失预警',
        title: '有客户流失预警需要处理',
        desc: '该预警来自后台 CRM 规则，当前小程序端暂未提供顾问客户详情页，请进入后台查看客户跟进情况。',
        scene: 'admin',
        actionText: '进入经营驾驶舱',
        actionUrl: '/packages/pages/admin_dashboard/admin_dashboard'
    },
    notice: {
        label: '企微通知',
        title: '企微通知已打开',
        desc: '当前消息没有绑定可直达的小程序页面，可从消息中心或对应后台入口继续处理。',
        scene: 'consumer',
        actionText: '查看消息中心',
        actionUrl: '/packages/pages/notification/index'
    }
}

const activeConfig = computed(() => sceneConfig[scene.value] || sceneConfig.notice)
const sceneLabel = computed(() => activeConfig.value.label)
const noticeTitle = computed(() => activeConfig.value.title)
const noticeDesc = computed(() => activeConfig.value.desc)
const pageScene = computed(() => activeConfig.value.scene)
const primaryActionText = computed(() => activeConfig.value.actionText)

const ensureLogin = () => {
    if (userStore.isLogin) {
        return true
    }

    uni.navigateTo({ url: '/pages/login/login' })
    return false
}

const handlePrimaryAction = () => {
    if (!ensureLogin()) {
        return
    }

    uni.navigateTo({ url: activeConfig.value.actionUrl })
}

const goNotificationCenter = () => {
    if (!ensureLogin()) {
        return
    }

    uni.navigateTo({ url: '/packages/pages/notification/index' })
}

onLoad((options: any) => {
    const nextScene = String(options?.scene || 'notice')
    scene.value = sceneConfig[nextScene] ? nextScene : 'notice'
    noticeType.value = String(options?.notice_type || '')
    targetId.value = Number(options?.target_id || 0)
})
</script>

<style lang="scss" scoped>
.wecom-notice-page {
    min-height: 100vh;
    box-sizing: border-box;
}

.notice-panel {
    display: flex;
    flex-direction: column;
    gap: 28rpx;
    padding: 34rpx;
    border-radius: var(--wm-radius-card, 16rpx);
    background: rgba(255, 255, 255, 0.94);
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
}

.notice-panel__head {
    display: flex;
    align-items: center;
    gap: 20rpx;
}

.notice-panel__icon {
    width: 76rpx;
    height: 76rpx;
    border-radius: 18rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--wm-color-gold-soft, #f7f0df);
    border: 1rpx solid var(--wm-color-border-strong, #d8c28a);
    flex: none;
}

.notice-panel__copy {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    min-width: 0;
}

.notice-panel__eyebrow {
    font-size: 22rpx;
    line-height: 1.4;
    color: var(--wm-text-secondary, #56524a);
}

.notice-panel__title {
    font-size: 34rpx;
    font-weight: 700;
    line-height: 1.35;
    color: var(--wm-text-primary, #111111);
}

.notice-panel__desc {
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--wm-text-secondary, #56524a);
}

.notice-meta {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    padding: 22rpx;
    border-radius: var(--wm-radius-card-soft, 14rpx);
    background: rgba(247, 240, 223, 0.55);
}

.notice-meta__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20rpx;
}

.notice-meta__label {
    font-size: 24rpx;
    color: var(--wm-text-secondary, #56524a);
}

.notice-meta__value {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-primary, #111111);
}

.notice-actions {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.notice-action {
    height: 88rpx;
    border-radius: 12rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
}

.notice-action--primary {
    background: #111111;
    color: #ffffff;
}

.notice-action--ghost {
    background: #ffffff;
    border: 1rpx solid var(--wm-color-border-strong, #d8c28a);
    color: var(--wm-text-primary, #111111);
}

.notice-action__text {
    font-size: 28rpx;
    font-weight: 700;
}
</style>
