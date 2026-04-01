<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar :title="pageTitle" />
        <view class="agreement-page">
            <u-parse :html="agreementContent"></u-parse>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getPolicy } from '@/api/app'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useThemeStore } from '@/stores/theme'

const agreementType = ref('') // 协议类型
const pageTitle = ref('协议') // 页面标题
const agreementContent = ref('') // 协议内容
const $theme = useThemeStore()

const getData = async (type: string) => {
    const res = await getPolicy({ type })
    agreementContent.value = res.content
    pageTitle.value = String(res.title || '协议')
}

onLoad((options: any) => {
    if (options.type) {
        agreementType.value = String(options.type)
        getData(agreementType.value)
    }
})
</script>

<style lang="scss" scoped>
.agreement-page {
    padding: 30rpx 24rpx 48rpx;
    background: transparent;
}
</style>
