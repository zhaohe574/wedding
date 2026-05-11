<template>
    <el-form label-width="86px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="mb-4 text-base font-medium text-[#101010]">模块标题</div>
            <el-form-item label="启用">
                <el-switch v-model="contentData.enabled" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <el-form-item label="眉标题">
                <el-input v-model="contentData.eyebrow" maxlength="40" show-word-limit />
            </el-form-item>
            <el-form-item label="标题">
                <el-input v-model="contentData.title" maxlength="30" show-word-limit />
            </el-form-item>
            <el-form-item label="副标题">
                <el-input v-model="contentData.subtitle" maxlength="70" show-word-limit />
            </el-form-item>
        </el-card>
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="mb-4 text-base font-medium text-[#101010]">统计项</div>
            <div class="space-y-4">
                <div v-for="(_, index) in contentData.data" :key="index" class="p-4 bg-fill-light">
                    <el-form-item label="数值">
                        <el-input v-model="contentData.data[index].value" maxlength="12" show-word-limit />
                    </el-form-item>
                    <el-form-item label="标签">
                        <el-input v-model="contentData.data[index].label" maxlength="16" show-word-limit />
                    </el-form-item>
                    <el-form-item label="说明" class="!mb-0">
                        <el-input v-model="contentData.data[index].description" maxlength="40" show-word-limit />
                    </el-form-item>
                </div>
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
    }
})

const defaultItems = [
    { value: '1000+', label: '活动服务经验', description: '覆盖仪式、发布与商务场景' },
    { value: '98%', label: '客户好评率', description: '来自长期合作与现场反馈' },
    { value: '30+', label: '覆盖城市', description: '支持跨区域活动执行' }
]

const contentData = computed({
    get: () => props.content,
    set: (newValue) => emits('update:content', newValue)
})

watchEffect(() => {
    if (!Array.isArray(props.content.data) || props.content.data.length !== 3) {
        emits('update:content', {
            ...props.content,
            data: defaultItems.map((item, index) => ({
                ...item,
                ...(props.content.data?.[index] || {})
            }))
        })
    }
})
</script>
