<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <view class="wm-empty-shell empty-page">
            <view class="wm-page-card wm-page-card--soft empty-page__card">
                <EmptyState
                    title="当前页面暂不可访问"
                    description="站点当前处于关闭或跳转状态，请稍后再试，或联系管理员确认开放时间。"
                    action-text="返回首页"
                    @action="goHome"
                />
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import EmptyState from '@/components/base/EmptyState.vue'
import PageShell from '@/components/base/PageShell.vue'

const goHome = () => {
    uni.switchTab({
        url: '/pages/index/index'
    })
}
</script>

<style scoped>
.empty-page {
    padding: 20rpx;
}

.empty-page__card {
    width: 100%;
    max-width: 640rpx;
}
</style>
