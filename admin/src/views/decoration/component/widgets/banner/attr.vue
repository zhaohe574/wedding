<template>
    <el-form label-width="96px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">首屏主图</div>
                <div class="ml-2 text-xs text-tx-secondary">首页最上方的大图，只有一张</div>
            </div>
            <div v-if="bannerItem" class="flex justify-center">
                <material-picker
                    size="122px"
                    v-model="bannerItem.image"
                    upload-class="bg-body"
                    exclude-domain
                >
                    <template #upload>
                        <div class="w-[122px] h-[122px] banner-upload-btn">上传首屏主图</div>
                    </template>
                </material-picker>
            </div>
            <div class="mt-2 text-xs text-tx-secondary">
                竖版，建议 750×1624；深色图更衬白色文字。不上传时真机使用内置主图。
            </div>
            <el-form-item label="主图高度" class="!mb-0 mt-4">
                <div class="w-full">
                    <div class="mb-2 flex flex-wrap items-center gap-2">
                        <el-button
                            v-for="preset in HERO_HEIGHT_PRESETS"
                            :key="preset.value"
                            size="small"
                            :type="heroHeight === preset.value ? 'primary' : ''"
                            @click="heroHeight = preset.value"
                        >
                            {{ preset.label }}
                        </el-button>
                    </div>
                    <div class="flex items-center gap-3 w-full">
                        <el-slider
                            v-model="heroHeight"
                            :min="HOME_HERO_HEIGHT_MIN"
                            :max="HOME_HERO_HEIGHT_MAX"
                            :step="10"
                            class="flex-1"
                        />
                        <el-input-number
                            v-model="heroHeight"
                            :min="HOME_HERO_HEIGHT_MIN"
                            :max="HOME_HERO_HEIGHT_MAX"
                            :step="10"
                            controls-position="right"
                            class="!w-32"
                        />
                        <span class="text-xs text-tx-secondary">rpx</span>
                    </div>
                    <div class="mt-1 text-xs text-tx-secondary">
                        单位 rpx（750 宽基准，默认 1054），范围 600–1400；调低高度时建议精简图片上文字，避免顶到状态栏。
                    </div>
                </div>
            </el-form-item>
        </el-card>

        <el-card v-if="bannerItem" shadow="never" class="!border-none flex-1 mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">
                    图片上的文字
                </div>
                <div class="ml-2 text-xs text-tx-secondary">
                    左侧开关只控制显不显示，关掉文案会保留
                </div>
            </div>
            <el-form-item label="宣传语">
                <div class="flex items-center gap-3 w-full">
                    <el-switch
                        v-model="bannerItem.slogan_show"
                        active-value="1"
                        inactive-value="0"
                        size="small"
                    />
                    <el-input
                        v-model="bannerItem.slogan"
                        maxlength="30"
                        show-word-limit
                        placeholder="主标题上方的小字；留空用「网站信息」里的前台 Slogan"
                    />
                    <el-color-picker v-model="bannerItem.slogan_color" />
                </div>
            </el-form-item>
            <el-form-item label="主标题">
                <div class="flex items-start gap-3 w-full">
                    <el-switch
                        v-model="bannerItem.title_show"
                        active-value="1"
                        inactive-value="0"
                        size="small"
                        class="mt-1"
                    />
                    <el-input
                        v-model="bannerItem.title"
                        type="textarea"
                        :rows="2"
                        maxlength="60"
                        show-word-limit
                        class="flex-1"
                        placeholder="可直接回车分多行，最多 4 行；留空用默认两行标题"
                    />
                </div>
            </el-form-item>
            <el-form-item label="服务范围">
                <div class="flex items-center gap-3 w-full">
                    <el-switch
                        v-model="bannerItem.subtitle_show"
                        active-value="1"
                        inactive-value="0"
                        size="small"
                    />
                    <el-input
                        v-model="bannerItem.subtitle"
                        maxlength="40"
                        show-word-limit
                        placeholder="留空用「西式 · 中式 · 商务主持 · 全程统筹」"
                    />
                </div>
            </el-form-item>
            <el-form-item label="主按钮">
                <div class="flex items-center gap-3 w-full">
                    <el-switch
                        v-model="bannerItem.primary_show"
                        active-value="1"
                        inactive-value="0"
                        size="small"
                    />
                    <el-input
                        v-model="bannerItem.primary_text"
                        maxlength="12"
                        show-word-limit
                        placeholder="留空用「查询档期」"
                    />
                </div>
            </el-form-item>
            <el-form-item v-if="type == 'mobile'" label="主按钮跳转">
                <link-picker
                    v-model="bannerItem.primary_link"
                    placeholder="留空默认跳转：查档期"
                />
            </el-form-item>
            <el-form-item label="次按钮">
                <div class="flex items-center gap-3 w-full">
                    <el-switch
                        v-model="bannerItem.secondary_show"
                        active-value="1"
                        inactive-value="0"
                        size="small"
                    />
                    <el-input
                        v-model="bannerItem.secondary_text"
                        maxlength="16"
                        show-word-limit
                        placeholder="留空显示「了解 + 站点名称」"
                    />
                </div>
            </el-form-item>
            <el-form-item v-if="type == 'mobile'" label="次按钮跳转">
                <link-picker
                    v-model="bannerItem.secondary_link"
                    placeholder="留空默认跳转：关于我们"
                />
            </el-form-item>
            <div class="mb-0 text-xs text-tx-secondary">
                按钮跳转留空时沿用默认路径：主按钮去「查档期」，次按钮去「关于我们」。
            </div>
        </el-card>

        <el-card v-if="bannerItem" shadow="never" class="!border-none flex-1 mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">
                    文字颜色
                </div>
                <div class="ml-2 text-xs text-tx-secondary">
                    首屏是深色图 + 渐变遮罩，建议保持浅色；留空用设计稿默认色
                </div>
            </div>
            <el-form-item label="标题颜色">
                <div class="flex items-center gap-3">
                    <el-color-picker v-model="bannerItem.title_color" />
                    <span class="text-xs text-tx-secondary">留空用设计稿默认（米白）</span>
                </div>
            </el-form-item>
            <el-form-item label="服务范围颜色" class="!mb-0">
                <div class="flex items-center gap-3">
                    <el-color-picker v-model="bannerItem.subtitle_color" />
                    <span class="text-xs text-tx-secondary">留空用设计稿默认（半透明米白）</span>
                </div>
            </el-form-item>
        </el-card>
    </el-form>
</template>
<script lang="ts" setup>
import type { PropType } from 'vue'

import {
    clampInt,
    HOME_HERO_HEIGHT_DEFAULT,
    HOME_HERO_HEIGHT_MAX,
    HOME_HERO_HEIGHT_MIN
} from '@shared/contracts/decorate'
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

/** 首屏只有一张图；数据形状由契约归一化保证 data[0] 一定存在 */
const bannerItem = computed(() => contentData.value.data?.[0])

/** 主图高度快捷档位：当前值命中时高亮 */
const HERO_HEIGHT_PRESETS = [
    { label: '紧凑 760', value: 760 },
    { label: '适中 900', value: 900 },
    { label: '标准 1054', value: 1054 },
    { label: '沉浸 1200', value: 1200 }
]

/**
 * 主图高度（rpx）：读写 content.hero_height。
 * getter/setter 都过 clampInt 归一化，清空输入框时回落默认值，不会写入 null / undefined。
 */
const heroHeight = computed<number>({
    get: () =>
        clampInt(
            contentData.value.hero_height,
            HOME_HERO_HEIGHT_MIN,
            HOME_HERO_HEIGHT_MAX,
            HOME_HERO_HEIGHT_DEFAULT
        ),
    set: (value) => {
        contentData.value.hero_height = clampInt(
            value,
            HOME_HERO_HEIGHT_MIN,
            HOME_HERO_HEIGHT_MAX,
            HOME_HERO_HEIGHT_DEFAULT
        )
    }
})
</script>

<style lang="scss" scoped>
.banner-upload-btn {
    @apply text-tx-secondary box-border rounded border-br border-dashed border flex flex-col justify-center items-center;
}
</style>
