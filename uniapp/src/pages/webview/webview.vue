<template>
    <web-view :src="url" />
</template>

<script setup lang="ts">
import { onLoad } from '@dcloudio/uni-app'
import { ref } from 'vue'

const url = ref('')

onLoad((options) => {
    try {
        const target = decodeURIComponent(String(options?.url || ''))
        if (!/^https:\/\//i.test(target)) throw new Error('链接无效')
        url.value = target
    } catch {
        uni.showToast({ title: '链接无效，请返回重试', icon: 'none' })
    }
})
</script>
