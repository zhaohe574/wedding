<template>
    <el-form label-width="86px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="mb-4 text-base font-medium text-[#101010]">联系展示</div>
            <el-form-item label="启用">
                <el-switch v-model="contentData.enabled" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <el-form-item label="眉标题">
                <el-input v-model="contentData.eyebrow" maxlength="30" show-word-limit />
            </el-form-item>
            <el-form-item label="标题">
                <el-input v-model="contentData.title" maxlength="32" show-word-limit />
            </el-form-item>
            <el-form-item label="副标题">
                <el-input v-model="contentData.subtitle" maxlength="80" show-word-limit />
            </el-form-item>
            <el-form-item label="电话">
                <el-input v-model="contentData.phone" maxlength="24" show-word-limit />
            </el-form-item>
            <el-form-item label="时间">
                <el-input v-model="contentData.service_time" maxlength="40" show-word-limit />
            </el-form-item>
            <el-form-item label="地址">
                <el-input v-model="contentData.address" type="textarea" :rows="2" maxlength="80" show-word-limit />
            </el-form-item>
            <el-form-item label="二维码">
                <material-picker
                    v-model="contentData.qrcode"
                    width="120px"
                    height="120px"
                    upload-class="bg-body"
                    exclude-domain
                />
            </el-form-item>
            <el-form-item label="二维码说明">
                <el-input v-model="contentData.qrcode_alt" maxlength="30" show-word-limit />
            </el-form-item>
            <el-form-item label="备注">
                <el-input v-model="contentData.remark" type="textarea" :rows="2" maxlength="100" show-word-limit />
            </el-form-item>
            <el-form-item label="按钮文案">
                <el-input v-model="contentData.action_text" maxlength="12" show-word-limit />
            </el-form-item>
            <el-form-item label="页脚标语">
                <el-input v-model="contentData.footer_slogan" maxlength="40" show-word-limit />
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
    }
})

const contentData = computed({
    get: () => props.content,
    set: (newValue) => emits('update:content', newValue)
})

watchEffect(() => {
    const nextContent = {
        qrcode_alt: props.content.qrcode_alt || '格林社联系二维码',
        action_text: props.content.action_text || '拨打电话预约',
        footer_slogan: props.content.footer_slogan || '婚礼主持 · 仪式统筹 · 活动呈现'
    }
    const hasMissingField = Object.entries(nextContent).some(([key, value]) => props.content[key as keyof typeof props.content] !== value && !props.content[key as keyof typeof props.content])
    if (hasMissingField) {
        emits('update:content', {
            ...props.content,
            ...nextContent
        })
    }
})
</script>
