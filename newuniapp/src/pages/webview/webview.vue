<template>
    <web-view v-if="url" :src="url" />
</template>

<script setup lang="ts">
/**
 * H5 容器。
 * 只允许 https —— 微信小程序 web-view 不支持 http，且域名需在后台配置为业务域名。
 */
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'

const url = ref('')

onLoad((options: any) => {
    const raw = options?.url ? decodeURIComponent(String(options.url)) : ''
    if (!/^https:\/\//i.test(raw)) {
        uni.showToast({ title: '链接不合法', icon: 'none' })
        setTimeout(() => uni.navigateBack(), 800)
        return
    }
    url.value = raw
})
</script>
