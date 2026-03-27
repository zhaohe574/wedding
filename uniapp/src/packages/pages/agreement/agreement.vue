<template>
    <BaseNavbar :title="pageTitle" />
    <view class="p-[30rpx]">
        <u-parse :html="agreementContent"></u-parse>
    </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getPolicy } from '@/api/app'

const agreementType = ref('') // 协议类型
const pageTitle = ref('协议') // 页面标题
const agreementContent = ref('') // 协议内容

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

<style lang="scss" scoped></style>
