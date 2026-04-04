<template>
    <div>
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="text-base text-[#101010] font-medium mb-4">资讯设置</div>
            <el-form-item label="标题">
                <el-input
                    v-model="contentData.title"
                    placeholder="请输入标题"
                    style="width: 300px"
                />
            </el-form-item>
        </el-card>
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
