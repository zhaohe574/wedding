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
    if (!Array.isArray(props.content.points) || props.content.points.length !== 3) {
        emits('update:content', {
            ...props.content,
            points: ['流程策划', '主持执行', '现场统筹'].map((item, index) => props.content.points?.[index] || item)
        })
    }
})
</script>
