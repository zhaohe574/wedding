<template>
    <el-form label-width="86px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="mb-4 text-base font-medium text-[#101010]">品牌介绍</div>
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
                <el-input v-model="contentData.subtitle" type="textarea" :rows="2" maxlength="100" show-word-limit />
            </el-form-item>
            <el-form-item label="介绍">
                <el-input v-model="contentData.description" type="textarea" :rows="4" maxlength="220" show-word-limit />
            </el-form-item>
            <el-form-item label="图片">
                <material-picker
                    v-model="contentData.image"
                    width="240px"
                    height="150px"
                    upload-class="bg-body"
                    exclude-domain
                />
            </el-form-item>
            <el-form-item label="图片描述">
                <el-input v-model="contentData.image_alt" maxlength="40" show-word-limit />
            </el-form-item>
            <el-form-item label="卡片标题">
                <el-input v-model="contentData.caption_title" maxlength="24" show-word-limit />
            </el-form-item>
            <el-form-item label="卡片说明">
                <el-input v-model="contentData.caption_text" type="textarea" :rows="2" maxlength="80" show-word-limit />
            </el-form-item>
        </el-card>
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="mb-4 text-base font-medium text-[#101010]">关键词</div>
            <div class="space-y-3">
                <el-input
                    v-for="(_, index) in contentData.points"
                    :key="index"
                    v-model="contentData.points[index]"
                    maxlength="12"
                    show-word-limit
                />
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

const contentData = computed({
    get: () => props.content,
    set: (newValue) => emits('update:content', newValue)
})

watchEffect(() => {
    const nextContent = {
        image_alt: props.content.image_alt || '格林社品牌服务现场',
        caption_title: props.content.caption_title || '仪式不是流程清单',
        caption_text: props.content.caption_text || '而是人物关系、现场秩序与情绪峰值的共同呈现。'
    }

    if (!Array.isArray(props.content.points) || props.content.points.length !== 3) {
        emits('update:content', {
            ...props.content,
            ...nextContent,
            points: ['需求沟通', '仪式脚本', '现场控场'].map((item, index) => props.content.points?.[index] || item)
        })
        return
    }

    const hasMissingField = Object.entries(nextContent).some(([key, value]) => props.content[key as keyof typeof props.content] !== value && !props.content[key as keyof typeof props.content])
    if (hasMissingField) {
        emits('update:content', {
            ...props.content,
            ...nextContent
        })
    }
})
</script>
