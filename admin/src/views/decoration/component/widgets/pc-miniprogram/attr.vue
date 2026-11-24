<template>
    <el-form label-width="96px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="mb-4 text-base font-medium text-[#101010]">模块文案</div>
            <el-form-item label="显示本区块">
                <el-switch v-model="contentData.enabled" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <el-form-item label="标题" class="!mb-0">
                <el-input
                    v-model="contentData.title"
                    type="textarea"
                    :rows="2"
                    maxlength="40"
                    show-word-limit
                />
                <div class="form-tips">换行即分行展示</div>
            </el-form-item>
        </el-card>
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="mb-4 text-base font-medium text-[#101010]">核心亮点</div>
            <el-form-item
                v-for="(_, index) in contentData.highlights"
                :key="index"
                :label="`亮点 ${index + 1}`"
                :class="{ '!mb-0': index === contentData.highlights.length - 1 }"
            >
                <div class="flex-1 space-y-3">
                    <el-input v-model="contentData.highlights[index].title" maxlength="16" placeholder="中文短标" show-word-limit />
                    <el-input v-model="contentData.highlights[index].en_label" maxlength="30" placeholder="英文副标" show-word-limit />
                </div>
            </el-form-item>
            <div class="mt-3 form-tips">固定 3 项，与真机小程序引导模块一一对应。</div>
        </el-card>
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="mb-4 text-base font-medium text-[#101010]">小程序展示与二维码</div>
            <el-form-item label="主屏预览图">
                <material-picker
                    v-model="contentData.preview_image"
                    width="120px"
                    height="214px"
                    upload-class="bg-body"
                    exclude-domain
                />
                <div class="form-tips">
                    前方主屏展示，建议上传小程序竖屏截图；留空时自动渲染内置预览
                </div>
            </el-form-item>
            <el-form-item label="副屏预览图">
                <material-picker
                    v-model="contentData.preview_image_back"
                    width="120px"
                    height="214px"
                    upload-class="bg-body"
                    exclude-domain
                />
                <div class="form-tips">
                    后方副屏展示，留空时自动复用主屏预览图；两张均留空则渲染内置预览
                </div>
            </el-form-item>
            <el-form-item label="小程序码">
                <material-picker
                    v-model="contentData.qrcode"
                    width="160px"
                    height="160px"
                    upload-class="bg-body"
                    exclude-domain
                />
                <div class="form-tips">
                    留空时自动复用「系统设置 → 渠道设置」中的小程序码，并与首屏扫码浮卡共用
                </div>
            </el-form-item>
            <el-form-item label="扫码引导语">
                <el-input v-model="contentData.qrcode_caption" maxlength="30" show-word-limit />
            </el-form-item>
            <el-form-item label="小程序名" class="!mb-0">
                <el-input v-model="contentData.miniapp_name" maxlength="30" show-word-limit />
            </el-form-item>
        </el-card>
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="mb-4 text-base font-medium text-[#101010]">页脚联络与品牌口号（可选，留空不展示）</div>
            <el-form-item label="联系电话">
                <el-input v-model="contentData.phone" maxlength="20" placeholder="留空则前台不展示" show-word-limit />
            </el-form-item>
            <el-form-item label="服务时间">
                <el-input v-model="contentData.service_time" maxlength="30" placeholder="留空则前台不展示" show-word-limit />
            </el-form-item>
            <el-form-item label="地址">
                <el-input v-model="contentData.address" maxlength="60" placeholder="留空则前台不展示" show-word-limit />
            </el-form-item>
            <el-form-item label="页脚口号" class="!mb-0">
                <el-input v-model="contentData.footer_slogan" maxlength="30" placeholder="留空则前台不展示" show-word-limit />
            </el-form-item>
        </el-card>
    </el-form>
</template>

<script lang="ts" setup>
import { normalizePcMiniProgramContent } from '@shared/contracts/pc-decorate'
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
    const normalized = normalizePcMiniProgramContent(props.content)
    const missingField = (Object.keys(normalized) as Array<keyof typeof normalized>).some(
        (key) => props.content[key] === undefined
    )
    const highlightsInvalid =
        !Array.isArray(props.content.highlights) || props.content.highlights.length !== normalized.highlights.length
    if (missingField || highlightsInvalid) {
        emits('update:content', { ...props.content, ...normalized })
    }
})
</script>
