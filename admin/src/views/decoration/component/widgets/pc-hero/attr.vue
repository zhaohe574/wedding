<template>
    <el-form label-width="96px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="mb-4 text-base font-medium text-[#101010]">品牌与文案</div>
            <el-form-item label="显示本区块">
                <el-switch v-model="contentData.enabled" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <el-form-item label="品牌中文名">
                <el-input v-model="contentData.brand_name" maxlength="20" show-word-limit />
            </el-form-item>
            <el-form-item label="品牌英文主标">
                <el-input v-model="contentData.brand_en" maxlength="30" show-word-limit />
            </el-form-item>
            <el-form-item label="主标题">
                <el-input
                    v-model="contentData.title"
                    type="textarea"
                    :rows="2"
                    maxlength="40"
                    show-word-limit
                />
                <div class="form-tips">换行即在前台分行排印，建议两行短句</div>
            </el-form-item>
            <el-form-item label="英文副标" class="!mb-0">
                <el-input v-model="contentData.eyebrow" maxlength="60" show-word-limit />
            </el-form-item>
        </el-card>
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="mb-4 text-base font-medium text-[#101010]">首屏主视觉</div>
            <el-form-item label="主图">
                <material-picker
                    v-model="contentData.image"
                    width="260px"
                    height="150px"
                    upload-class="bg-body"
                    exclude-domain
                />
            </el-form-item>
            <el-form-item label="图片描述" class="!mb-0">
                <el-input v-model="contentData.image_alt" maxlength="40" show-word-limit />
            </el-form-item>
        </el-card>
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="mb-4 text-base font-medium text-[#101010]">右下扫码浮卡</div>
            <el-form-item label="浮卡文案" class="!mb-0">
                <el-input v-model="contentData.qr_caption" maxlength="30" show-word-limit />
                <div class="form-tips">
                    浮卡内二维码自动复用「小程序引导」或系统渠道设置中的小程序码，无需重复上传
                </div>
            </el-form-item>
        </el-card>
    </el-form>
</template>

<script lang="ts" setup>
import { normalizePcHeroContent } from '@shared/contracts/pc-decorate'
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

/** 缺省字段按契约补齐：只在字段确实缺失时 emit，避免与父级回写形成死循环 */
watchEffect(() => {
    const normalized = normalizePcHeroContent(props.content)
    const missingField = (Object.keys(normalized) as Array<keyof typeof normalized>).some(
        (key) => props.content[key] === undefined
    )
    if (missingField) {
        emits('update:content', { ...props.content, ...normalized })
    }
})
</script>
