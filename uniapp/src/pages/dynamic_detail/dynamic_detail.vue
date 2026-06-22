<template>
    <view class="redirect-page"></view>
</template>

<script setup lang="ts">
const buildTargetUrl = () => {
    const pages = getCurrentPages()
    const currentPage = pages[pages.length - 1] as any
    const options = currentPage?.options || {}
    const query = Object.keys(options)
        .filter((key) => options[key] !== undefined && options[key] !== null && options[key] !== '')
        .map((key) => `${encodeURIComponent(key)}=${encodeURIComponent(String(options[key]))}`)
        .join('&')

    return `/packages/pages/dynamic_detail/dynamic_detail${query ? `?${query}` : ''}`
}

onLoad(() => {
    uni.redirectTo({
        url: buildTargetUrl()
    })
})
</script>

<style scoped>
.redirect-page {
    min-height: 100vh;
    background: #f7f3ea;
}
</style>
