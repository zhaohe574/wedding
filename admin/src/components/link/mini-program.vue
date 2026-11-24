<template>
    <div class="mini-program h-[540px]">
        <div class="text-lg font-medium">跳转其他小程序</div>
        <div class="flex flex-wrap items-center mt-5">
            <div class="w-[96px] text-right">小程序 AppID</div>
            <div class="ml-4 flex-1 min-w-[200px]">
                <el-input
                    class="max-w-[360px]"
                    :model-value="modelValue?.query?.appId || ''"
                    placeholder="必填，例如 wx1234567890abcdef"
                    clearable
                    @input="(value: string) => handleInput('appId', value)"
                />
            </div>
        </div>
        <div class="flex flex-wrap items-center mt-4">
            <div class="w-[96px] text-right">目标页面路径</div>
            <div class="ml-4 flex-1 min-w-[200px]">
                <el-input
                    class="max-w-[360px]"
                    :model-value="modelValue?.query?.path || ''"
                    placeholder="选填，如 pages/index/index（留空跳对方首页）"
                    clearable
                    @input="(value: string) => handleInput('path', value)"
                />
            </div>
        </div>
        <div class="flex flex-wrap items-center mt-4">
            <div class="w-[96px] text-right">传递参数</div>
            <div class="ml-4 flex-1 min-w-[200px]">
                <el-input
                    class="max-w-[360px]"
                    :model-value="modelValue?.query?.query || ''"
                    placeholder="选填，如 id=2&from=wedding"
                    clearable
                    @input="(value: string) => handleInput('query', value)"
                />
            </div>
        </div>
        <div class="form-tips ml-[112px] max-w-[360px] mt-1">
            <div>禁止包含中文字符或空格，避免微信拦截报错。</div>
        </div>
        <div class="flex flex-wrap items-center mt-4">
            <div class="w-[96px] text-right">小程序版本</div>
            <div class="ml-4 flex-1 min-w-[200px]">
                <el-radio-group
                    :model-value="modelValue?.query?.env_version || 'release'"
                    @change="(value: unknown) => handleInput('env_version', String(value || 'release'))"
                >
                    <el-radio value="release">正式版</el-radio>
                    <el-radio value="trial">体验版</el-radio>
                    <el-radio value="develop">开发版</el-radio>
                </el-radio-group>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { type PropType, watch } from 'vue'

import { type Link, LinkTypeEnum } from '.'

const props = defineProps({
    modelValue: {
        type: Object as PropType<Link>,
        default: () => ({ path: '' })
    }
})
const emit = defineEmits<{
    (event: 'update:modelValue', value: Link): void
}>()

const handleInput = (key: 'appId' | 'path' | 'env_version' | 'query', value: string) => {
    const nextQuery: Record<string, string> = {
        ...(props.modelValue?.type === LinkTypeEnum.MINI_PROGRAM ? props.modelValue.query : {}),
        env_version: props.modelValue?.query?.env_version || 'release'
    }
    const cleaned = String(value ?? '').trim()
    if (cleaned) nextQuery[key] = cleaned
    else delete nextQuery[key]

    emit('update:modelValue', {
        path: 'mini_program',
        name: '跳转小程序',
        query: nextQuery,
        type: LinkTypeEnum.MINI_PROGRAM
    })
}

watch(
    () => props.modelValue,
    (value) => {
        if (value?.type === LinkTypeEnum.MINI_PROGRAM && !value.query?.env_version) {
            handleInput('env_version', 'release')
        }
    },
    { immediate: true }
)
</script>
