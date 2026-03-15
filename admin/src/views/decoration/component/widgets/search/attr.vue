<template>
    <div>
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="text-base text-[#101010] font-medium mb-4">搜索框设置</div>
            <el-form-item label="显示搜索区">
                <el-switch
                    v-model="contentData.showSearchPanel"
                    :active-value="1"
                    :inactive-value="0"
                />
            </el-form-item>
            <el-form-item label="提示文字">
                <el-input
                    v-model="contentData.placeholder"
                    placeholder="请输入搜索提示文字"
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
