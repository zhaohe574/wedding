<template>
    <el-form label-width="86px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="mb-4 text-base font-medium text-[#101010]">首屏文案</div>
            <el-form-item label="启用">
                <el-switch v-model="contentData.enabled" :active-value="1" :inactive-value="0" />
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
            <el-form-item label="图片说明">
                <el-input v-model="contentData.image_caption" maxlength="40" show-word-limit />
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
    if (!Array.isArray(props.content.badges) || props.content.badges.length !== 3) {
        emits('update:content', {
            ...props.content,
            badges: ['婚礼主持', '仪式统筹', '高端庆典'].map((item, index) => props.content.badges?.[index] || item)
        })
    }
})
</script>
