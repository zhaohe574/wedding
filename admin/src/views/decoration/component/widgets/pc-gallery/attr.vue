<template>
    <el-form label-width="96px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="mb-4 text-base font-medium text-[#101010]">模块文案</div>
            <el-form-item label="显示本区块">
                <el-switch v-model="contentData.enabled" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <el-form-item label="眉标题">
                <el-input v-model="contentData.eyebrow" maxlength="40" show-word-limit />
            </el-form-item>
            <el-form-item label="标题" class="!mb-0">
                <el-input v-model="contentData.title" maxlength="40" show-word-limit />
            </el-form-item>
        </el-card>
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="mb-4 text-base font-medium text-[#101010]">非对称画册</div>
            <el-form-item
                v-for="(_, index) in contentData.data"
                :key="index"
                :label="slotLabels[index]"
                :class="{ '!mb-0': index === contentData.data.length - 1 }"
            >
                <div class="flex-1 space-y-3">
                    <material-picker
                        v-model="contentData.data[index].image"
                        width="220px"
                        height="126px"
                        upload-class="bg-body"
                        exclude-domain
                    />
                    <el-input v-model="contentData.data[index].alt" maxlength="30" placeholder="图片描述" show-word-limit />
                    <el-input v-model="contentData.data[index].scene" maxlength="16" placeholder="场景标签，如 SCENE 01" show-word-limit />
                    <el-input v-model="contentData.data[index].title" maxlength="18" placeholder="标题" show-word-limit />
                    <el-input
                        v-model="contentData.data[index].description"
                        type="textarea"
                        :rows="2"
                        maxlength="70"
                        placeholder="简述"
                        show-word-limit
                    />
                </div>
            </el-form-item>
            <div class="mt-3 form-tips">
                固定 3 项，与真机非对称画册一一对应：第 1 项为左栏大竖幅主图，第 2、3 项为右栏上下横幅。
            </div>
        </el-card>
    </el-form>
</template>

<script lang="ts" setup>
import { normalizePcGalleryContent } from '@shared/contracts/pc-decorate'
import type { PropType } from 'vue'

import type options from './options'

/** 非对称画册布局位次标签：与真机 1+2 布局一一对应 */
const slotLabels = ['左栏大竖幅主图', '右栏上方横幅', '右栏下方横幅']

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

/** 缺省字段按契约补齐：只在字段确实缺失时 emit，避免与父级回写形成死循环 */
watchEffect(() => {
    const normalized = normalizePcGalleryContent(props.content)
    const missingField = (Object.keys(normalized) as Array<keyof typeof normalized>).some(
        (key) => props.content[key] === undefined
    )
    const dataInvalid = !Array.isArray(props.content.data) || props.content.data.length !== normalized.data.length
    if (missingField || dataInvalid) {
        emits('update:content', { ...props.content, ...normalized })
    }
})
</script>
