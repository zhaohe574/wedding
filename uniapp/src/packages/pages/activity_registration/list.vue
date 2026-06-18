<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="我的活动" variant="solid" bg-color="#191713" text-color="#FFFDF8" />
        <view class="activity-registration-list-page wm-page-content">
            <ActivityRegistrationListView ref="listRef" />
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onReachBottom, onShow } from '@dcloudio/uni-app'
import PageShell from '@/components/base/PageShell.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import { useThemeStore } from '@/stores/theme'
import ActivityRegistrationListView from './ActivityRegistrationListView.vue'

const $theme = useThemeStore()
const listRef = ref<InstanceType<typeof ActivityRegistrationListView> | null>(null)

onShow(() => {
    listRef.value?.refresh()
})

onReachBottom(() => {
    listRef.value?.loadMore()
})
</script>

<style lang="scss" scoped>
.activity-registration-list-page {
    padding-top: 24rpx;
    padding-bottom: 40rpx;
}
</style>
