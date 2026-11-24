<template>
    <div class="custom-link h-[540px]">
        <div class="text-lg font-medium">外部网页（WebView）</div>
        <div class="flex flex-wrap items-center mt-5">
            <div class="w-[86px] text-right">网页地址</div>
            <div class="ml-4 flex-1 min-w-[200px]">
                <el-input
                    class="max-w-[420px]"
                    :model-value="modelValue?.query?.url || ''"
                    placeholder="请输入 https:// 开头的完整网页地址"
                    clearable
                    @input="handleInput"
                />
            </div>
        </div>
        <div class="form-tips ml-[102px] max-w-[420px] mt-2 leading-6">
            <div>1. 微信小程序 web-view 仅支持 <strong>https://</strong> 协议地址；</div>
            <div>2. 域名需已在微信公众平台「开发设置 → 业务域名」及客户端白名单中登记。</div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'

import { type Link, LinkTypeEnum } from '.'

defineProps({
    modelValue: {
        type: Object as PropType<Link>,
        default: () => ({ path: '' })
    }
})
const emit = defineEmits<{
    (event: 'update:modelValue', value: Link): void
}>()

const handleInput = (value: string) => {
    emit('update:modelValue', {
        path: '/pages/webview/webview',
        name: '外部网页',
        query: {
            url: String(value ?? '').trim()
        },
        type: LinkTypeEnum.CUSTOM_LINK
    })
}
</script>
