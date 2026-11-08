<template>
    <web-view v-if="url" :src="url" />
    <app-toast />
</template>

<script setup lang="ts">
import { toast } from '@/composables/useToast'
/**
 * H5 容器。
 * 只允许 https —— 微信小程序 web-view 不支持 http，且域名需在后台配置为业务域名。
 */
import { ref } from 'vue'
// 失败态由 toast.error + 原 navigateBack 处理，不伪装成空态
import { onLoad } from '@dcloudio/uni-app'
import { isAllowedWebviewUrl } from '@/constants/domains'

const url = ref('')

onLoad((options: any) => {
    const raw = options?.url ? decodeURIComponent(String(options.url)) : ''
    // 合规 S5：先过域名白名单，不只是校验协议。
    // 只校验 https 的话，后端配了未加入「业务域名」的域名会白屏 → 审核判「页面无法打开」。
    if (!isAllowedWebviewUrl(raw)) {
        toast.error('该链接暂不支持在小程序内打开')
        setTimeout(() => uni.navigateBack(), 800)
        return
    }
    url.value = raw
})
</script>
