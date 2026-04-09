<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="附加服务已下线" />

        <view class="order-change-page">
            <view class="order-change-page__wrapper">
                <view class="order-change-status-card order-change-status-card--neutral">
                    <view class="order-change-status-card__eyebrow">
                        <text class="order-change-status-card__eyebrow-text">状态说明</text>
                        <StatusBadge tone="neutral" size="sm">历史下线能力</StatusBadge>
                    </view>
                    <text class="order-change-status-card__title">
                        {{ orderChangeOfflineCopy.title }}
                    </text>
                    <text class="order-change-status-card__summary">
                        当前页面仅保留说明与回退入口，不再提供真实申请流程。
                    </text>
                    <view class="order-change-status-card__metrics">
                        <view class="order-change-status-card__metric">
                            <text class="order-change-status-card__metric-label">当前入口</text>
                            <text class="order-change-status-card__metric-value">只读说明页</text>
                        </view>
                        <view class="order-change-status-card__metric">
                            <text class="order-change-status-card__metric-label">推荐操作</text>
                            <text class="order-change-status-card__metric-value">返回我的申请</text>
                        </view>
                    </view>
                </view>

                <BaseCard variant="surface" scene="consumer" class="order-change-offline-card">
                    <tn-icon name="warning-circle" size="84" color="#76706B" />
                    <text class="order-change-offline-card__title">
                        {{ orderChangeOfflineCopy.title }}
                    </text>
                    <text class="order-change-offline-card__desc">
                        {{ orderChangeOfflineCopy.description }}
                    </text>
                </BaseCard>
            </view>
        </view>

        <ActionArea sticky safeBottom layout="split">
            <view class="order-change-page__actions">
                <BaseButton block size="lg" variant="secondary" @click="goBack">
                    返回上一页
                </BaseButton>
                <BaseButton block size="lg" @click="goList">我的申请</BaseButton>
            </view>
        </ActionArea>
    </PageShell>
</template>

<script setup lang="ts">
import ActionArea from '@/components/base/ActionArea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { useThemeStore } from '@/stores/theme'
import { getOrderChangeListUrl, orderChangeOfflineCopy } from './shared'

const $theme = useThemeStore()

const goList = () => {
    uni.redirectTo({ url: getOrderChangeListUrl('change') })
}

const goBack = () => {
    const pages = getCurrentPages()
    if (pages.length > 1) {
        uni.navigateBack()
        return
    }

    goList()
}
</script>

<style lang="scss" scoped>
@import './shared.scss';
</style>
