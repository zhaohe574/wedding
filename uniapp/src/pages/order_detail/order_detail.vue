<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace" hasSafeBottom>
        <LoadingState text="正在打开订单详情..." />
    </PageShell>
</template>

<script setup lang="ts">
import { onLoad } from '@dcloudio/uni-app'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()

const buildTargetUrl = (options: Record<string, any> = {}) => {
    const query = Object.keys(options)
        .filter((key) => options[key] !== undefined && options[key] !== null && options[key] !== '')
        .map((key) => `${encodeURIComponent(key)}=${encodeURIComponent(String(options[key]))}`)
        .join('&')

    return `/packages/pages/order_detail/order_detail${query ? `?${query}` : ''}`
}

onLoad((options = {}) => {
    uni.redirectTo({
        url: buildTargetUrl(options)
    })
})
</script>
