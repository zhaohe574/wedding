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
                <el-input v-model="contentData.title" maxlength="40" show-word-limit />
            </el-form-item>
            <el-form-item label="副标题">
                <el-input v-model="contentData.subtitle" type="textarea" :rows="2" maxlength="120" show-word-limit />
            </el-form-item>
        </el-card>
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="mb-4 text-base font-medium text-[#101010]">优势项</div>
            <div class="space-y-4">
                <div v-for="(_, index) in contentData.data" :key="index" class="p-4 bg-fill-light">
                    <el-form-item label="标题">
                        <el-input v-model="contentData.data[index].title" maxlength="16" show-word-limit />
                    </el-form-item>
                    <el-form-item label="描述" class="!mb-0">
                        <el-input v-model="contentData.data[index].description" type="textarea" :rows="2" maxlength="80" show-word-limit />
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
    { title: '仪式文本定制', description: '围绕人物关系与活动目标，打磨有分寸感的主持文本。' },
    { title: '全流程节奏管理', description: '梳理环节、人员、物料与时间点，降低现场不确定性。' },
    { title: '现场审美协同', description: '让文案、音乐、影像与仪式氛围保持统一的品牌语气。' }
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
