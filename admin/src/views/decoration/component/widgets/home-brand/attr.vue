<template>
    <el-form label-width="90px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] font-medium">团队信息</div>
                <div class="text-xs text-tx-secondary ml-2">展示在首页白色内容面板顶部</div>
            </div>
            <el-form-item label="问候语">
                <el-input v-model="contentData.greeting" maxlength="20" show-word-limit />
            </el-form-item>
            <el-form-item label="团队名称">
                <el-input v-model="contentData.team_name" maxlength="40" show-word-limit />
            </el-form-item>
            <el-form-item label="副标题">
                <el-input v-model="contentData.subtitle" maxlength="40" show-word-limit />
            </el-form-item>
            <el-form-item label="按钮文字">
                <el-input v-model="contentData.cta_text" maxlength="12" show-word-limit />
            </el-form-item>
            <el-form-item label="按钮链接" class="!mb-0">
                <link-picker v-if="type == 'mobile'" v-model="contentData.cta_link" />
                <el-input
                    v-if="type == 'pc'"
                    v-model="contentData.cta_link.path"
                    placeholder="请输入链接"
                />
            </el-form-item>
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
    },
    type: {
        type: String as PropType<'mobile' | 'pc'>,
        default: 'mobile'
    }
})

const contentData = computed({
    get: () => props.content,
    set: (newValue) => {
        emits('update:content', newValue)
    }
})
</script>
