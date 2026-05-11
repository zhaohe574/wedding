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
                <el-input v-model="contentData.subtitle" type="textarea" :rows="2" maxlength="100" show-word-limit />
            </el-form-item>
        </el-card>
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="mb-4 text-base font-medium text-[#101010]">图集</div>
            <div class="space-y-4">
                <div v-for="(_, index) in contentData.data" :key="index" class="p-4 bg-fill-light">
                    <el-form-item label="图片">
                        <material-picker
                            v-model="contentData.data[index].image"
                            width="220px"
                            height="126px"
                            upload-class="bg-body"
                            exclude-domain
                        />
                    </el-form-item>
                    <el-form-item label="标题">
                        <el-input v-model="contentData.data[index].title" maxlength="18" show-word-limit />
                    </el-form-item>
                    <el-form-item label="描述" class="!mb-0">
                        <el-input v-model="contentData.data[index].description" type="textarea" :rows="2" maxlength="70" show-word-limit />
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
    { image: '/resource/image/adminapi/default/banner003.png', title: '企业发布现场', description: '稳定推进流程，强化品牌表达。' },
    { image: '/resource/image/adminapi/default/banner001.png', title: '庆典仪式现场', description: '兼顾秩序、情绪与仪式感。' },
    { image: '/resource/image/adminapi/default/banner002.png', title: '团队服务场景', description: '让细节在现场自然发生。' }
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
