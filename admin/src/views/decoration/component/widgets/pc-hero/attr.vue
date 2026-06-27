<template>
    <el-form label-width="86px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="mb-4 text-base font-medium text-[#101010]">首屏文案</div>
            <el-form-item label="启用">
                <el-switch v-model="contentData.enabled" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <el-form-item label="品牌名">
                <el-input v-model="contentData.brand_name" maxlength="20" show-word-limit />
            </el-form-item>
            <el-form-item label="品牌副标">
                <el-input v-model="contentData.brand_tagline" maxlength="30" show-word-limit />
            </el-form-item>
            <el-form-item label="眉标题">
                <el-input v-model="contentData.eyebrow" maxlength="60" show-word-limit />
            </el-form-item>
            <el-form-item label="主标题">
                <el-input v-model="contentData.title" maxlength="40" show-word-limit />
            </el-form-item>
            <el-form-item label="副标题">
                <el-input v-model="contentData.subtitle" maxlength="70" show-word-limit />
            </el-form-item>
            <el-form-item label="说明">
                <el-input v-model="contentData.description" type="textarea" :rows="3" maxlength="140" show-word-limit />
            </el-form-item>
            <el-form-item label="主按钮">
                <el-input v-model="contentData.primary_action" maxlength="12" show-word-limit />
            </el-form-item>
            <el-form-item label="次按钮">
                <el-input v-model="contentData.secondary_action" maxlength="12" show-word-limit />
            </el-form-item>
        </el-card>
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="mb-4 text-base font-medium text-[#101010]">首屏图片</div>
            <el-form-item label="主图">
                <material-picker
                    v-model="contentData.image"
                    width="260px"
                    height="150px"
                    upload-class="bg-body"
                    exclude-domain
                />
            </el-form-item>
            <el-form-item label="图片描述">
                <el-input v-model="contentData.image_alt" maxlength="40" show-word-limit />
            </el-form-item>
            <el-form-item label="图片说明">
                <el-input v-model="contentData.image_caption" maxlength="40" show-word-limit />
            </el-form-item>
            <el-form-item label="卡片眉题">
                <el-input v-model="contentData.panel_eyebrow" maxlength="30" show-word-limit />
            </el-form-item>
            <el-form-item label="卡片说明">
                <el-input v-model="contentData.panel_description" type="textarea" :rows="3" maxlength="100" show-word-limit />
            </el-form-item>
        </el-card>
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="mb-4 text-base font-medium text-[#101010]">标签</div>
            <div class="space-y-3">
                <el-input
                    v-for="(_, index) in contentData.badges"
                    :key="index"
                    v-model="contentData.badges[index]"
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
        brand_name: props.content.brand_name || '格林社婚礼服务',
        brand_tagline: props.content.brand_tagline || 'Ceremony House',
        image_alt: props.content.image_alt || '格林社婚礼仪式现场',
        panel_eyebrow: props.content.panel_eyebrow || 'Scene Direction',
        panel_description: props.content.panel_description || '从沟通、脚本、音乐节点到现场控场，保持审美和情绪在同一个节奏里。',
        primary_action: props.content.primary_action || '联系顾问',
        secondary_action: props.content.secondary_action || '查看案例'
    }

    if (!Array.isArray(props.content.badges) || props.content.badges.length !== 3) {
        emits('update:content', {
            ...props.content,
            ...nextContent,
            badges: ['婚礼主持', '仪式统筹', '高端庆典'].map((item, index) => props.content.badges?.[index] || item)
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
