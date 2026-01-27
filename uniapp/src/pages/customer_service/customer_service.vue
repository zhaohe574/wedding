<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
        <!-- #endif -->
    </page-meta>
    <view class="customer-service">
        <view v-for="(item, index) in state.pages" :key="index">
            <template v-if="item.name == 'customer-service'">
                <w-customer-service :content="item.content" :styles="item.styles" />
            </template>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getDecorate } from '@/api/shop'
import { reactive } from 'vue'
const state = reactive<{
    pages: any[]
}>({
    pages: []
})
const getData = async () => {
    const data = await getDecorate({ id: 3 })
    // 处理 data.data，可能是字符串或对象
    if (typeof data.data === 'string') {
        state.pages = JSON.parse(data.data)
    } else {
        state.pages = data.data
    }
}
getData()
</script>

<style></style>
