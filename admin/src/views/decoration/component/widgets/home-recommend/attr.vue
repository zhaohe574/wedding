<template>
    <el-form label-width="96px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">本期推荐</div>
            </div>
            <el-form-item label="显示本区块" class="!mb-0">
                <el-switch v-model="contentData.enabled" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <div class="mt-3 text-xs text-tx-secondary">
                数据取自推荐服务人员，无数据时自动隐藏。
            </div>
        </el-card>
    </el-form>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'

import type options from './options'

type OptionsType = ReturnType<typeof options>

const emits = defineEmits<(event: 'update:content', data: OptionsType['content']) => void>()
const props = defineProps({
    content: {
        type: Object as PropType<OptionsType['content']>,
        default: () => ({})
    },
    styles: {
        type: Object as PropType<OptionsType['styles']>,
        default: () => ({})
    }
})

const contentData = computed({
    get: () => props.content,
    set: (newValue) => {
        emits('update:content', newValue)
    }
})

/** 老数据可能缺 enabled / 存了非法值：补成 0|1 后一次性回写 */
watchEffect(() => {
    if (props.content.enabled !== 0 && props.content.enabled !== 1) {
        emits('update:content', {
            ...props.content,
            enabled: Number(props.content.enabled) === 0 ? 0 : 1
        })
    }
})
</script>
