<template>
    <el-form label-width="110px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">
                    顶部背景图
                </div>
                <div class="ml-2 text-xs text-tx-secondary">
                    展示在登录页上方品牌视觉区，建议 750×746（约 1:1）
                </div>
            </div>

            <el-form-item label="背景图片">
                <material-picker
                    v-model="backgroundImage"
                    size="148px"
                    upload-class="bg-body"
                    exclude-domain
                >
                    <template #upload>
                        <div class="w-[148px] h-[148px] login-hero-upload-btn">上传背景图</div>
                    </template>
                </material-picker>
            </el-form-item>

            <div class="p-[12px] rounded-[8px] bg-[#f3f8ff] text-[#136bdf] text-xs leading-5">
                留空或清除图片后，小程序将自动使用内置默认婚礼场景图。图片下方叠加暗色渐变遮罩以保证品牌名称与 Slogan 清晰可读，建议主体内容略偏中上方。
            </div>
        </el-card>
    </el-form>
</template>

<script lang="ts" setup>
import { normalizeLoginHeroContent } from '@shared/contracts/decorate'
import { computed, type PropType } from 'vue'

import type options from './options'

type OptionsType = ReturnType<typeof options>

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

const emit = defineEmits<{
    (event: 'update:content', value: OptionsType['content']): void
}>()

const backgroundImage = computed({
    get: () => normalizeLoginHeroContent(props.content).background_image,
    set: (value: string) => {
        emit(
            'update:content',
            normalizeLoginHeroContent({
                ...props.content,
                background_image: value
            })
        )
    }
})
</script>

<style lang="scss" scoped>
.login-hero-upload-btn {
    border: 1px dashed var(--el-border-color);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: var(--el-text-color-secondary);
}
</style>
