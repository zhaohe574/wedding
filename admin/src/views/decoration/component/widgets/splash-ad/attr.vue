<template>
    <el-form label-width="100px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">开屏广告设置</div>
            </div>
            <el-form-item label="启用状态">
                <el-switch v-model="contentData.enabled" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <el-form-item label="广告图片">
                <material-picker v-model="contentData.image" size="122px" upload-class="bg-body" exclude-domain>
                    <template #upload>
                        <div class="w-[122px] h-[122px] splash-upload-btn">开屏图</div>
                    </template>
                </material-picker>
            </el-form-item>
            <div class="p-[12px] rounded-[8px] bg-[#f3f8ff] text-[#136bdf] mb-4 text-xs leading-5">
                开屏图片仅用于展示，不提供图片链接或点击跳转配置。关闭或未上传图片时，移动端将直接进入首页。
            </div>
            <el-form-item label="自动进入">
                <el-switch v-model="contentData.auto_enter" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <el-form-item label="进入秒数">
                <el-input-number
                    v-model="contentData.auto_seconds"
                    :min="1"
                    :max="10"
                    :step="1"
                    controls-position="right"
                    class="!w-full"
                />
            </el-form-item>
            <el-form-item label="展示频率">
                <el-select v-model="contentData.frequency" class="!w-full">
                    <el-option label="每会话一次" value="session" />
                    <el-option label="每天一次" value="daily" />
                    <el-option label="每次进入" value="every_time" />
                    <el-option label="首次访问" value="first_visit" />
                </el-select>
            </el-form-item>
        </el-card>

        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">底部按钮</div>
            </div>
            <el-form-item label="按钮文案">
                <el-input v-model="contentData.button_text" maxlength="12" show-word-limit placeholder="点击进入" />
            </el-form-item>
            <el-form-item label="按钮底色">
                <el-color-picker v-model="stylesData.button_bg_color" show-alpha />
            </el-form-item>
            <el-form-item label="文字颜色">
                <el-color-picker v-model="stylesData.button_text_color" />
            </el-form-item>
            <el-form-item label="按钮圆角">
                <el-input-number
                    v-model="stylesData.button_radius"
                    :min="0"
                    :max="999"
                    :step="2"
                    controls-position="right"
                    class="!w-full"
                />
            </el-form-item>
            <el-form-item label="遮罩颜色">
                <el-color-picker v-model="stylesData.mask_color" show-alpha />
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
    }
})

const contentData = computed({
    get: () => props.content,
    set: (newValue) => {
        emits('update:content', newValue)
    }
})

const stylesData = computed(() => props.styles)
</script>

<style lang="scss" scoped>
.splash-upload-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 122px;
    height: 122px;
    color: #8c8c8c;
    border: 1px dashed #dcdfe6;
    border-radius: 8px;
}
</style>
