<template>
    <div>
        <el-form label-width="90px" size="large" label-position="top">
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="text-base text-[#101010] font-medium mb-4">文案设置</div>
                <el-form-item label="主标题">
                    <el-input
                        class="w-[400px]"
                        show-word-limit
                        maxlength="20"
                        v-model="contentData.title"
                    />
                </el-form-item>
                <el-form-item label="副标题">
                    <el-input
                        class="w-[400px]"
                        show-word-limit
                        maxlength="40"
                        v-model="contentData.subtitle"
                    />
                </el-form-item>
                <el-form-item label="主按钮文案">
                    <el-input
                        class="w-[400px]"
                        show-word-limit
                        maxlength="16"
                        v-model="contentData.buttonText"
                    />
                </el-form-item>
                <el-form-item label="服务时间">
                    <el-input
                        class="w-[400px]"
                        show-word-limit
                        maxlength="30"
                        v-model="contentData.time"
                    />
                </el-form-item>
                <el-form-item label="底部提示">
                    <el-input
                        class="w-[400px]"
                        show-word-limit
                        maxlength="40"
                        v-model="contentData.tips"
                    />
                </el-form-item>
            </el-card>
        </el-form>
    </div>
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
</script>

<style lang="scss" scoped></style>
