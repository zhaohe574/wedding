<template>
    <page-meta :page-style="$theme.pageStyle" />

    <PageShell scene="consumer">
        <BaseNavbar title="404" />

        <view class="wm-empty-shell not-found-page">
            <view class="wm-page-card wm-page-card--soft not-found-page__card">
                <EmptyState title="页面不存在" description="页面不存在或链接有误。" />

                <BaseButton block size="lg" @click="goHome">返回首页</BaseButton>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import BaseButton from '@/components/base/BaseButton.vue'

import EmptyState from '@/components/base/EmptyState.vue'

import PageShell from '@/components/base/PageShell.vue'

const goHome = () => {
    uni.reLaunch({
        url: '/pages/index/index'
    })
}
</script>

<style scoped>
.not-found-page {
    padding: 20rpx;
}

.not-found-page__card {
    width: 100%;

    max-width: 640rpx;

    display: flex;

    flex-direction: column;

    gap: 20rpx;
}
</style>
