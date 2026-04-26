<template>
    <el-form label-width="70px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">背景联动</div>
            </div>
            <el-radio-group v-model="contentData.bg_style">
                <el-radio :value="1">开启</el-radio>
                <el-radio :value="0">关闭</el-radio>
            </el-radio-group>
            <div class="p-[15px] rounded-[8px] bg-[#f3f8ff] text-[#136bdf] mt-2">
                开启背景联动后，可为顶部图片设置背景色或背景图，首页背景同步使用该设置。
            </div>
        </el-card>
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">顶部图高度</div>
                <div class="ml-2 text-xs text-tx-secondary">
                    {{ heightTip }}
                </div>
            </div>
            <el-form-item label="高度">
                <el-input-number
                    v-model="contentData.height"
                    :min="100"
                    :max="2000"
                    :step="10"
                    controls-position="right"
                    :placeholder="defaultHeight.toString()"
                    class="!w-full"
                >
                    <template #append>rpx</template>
                </el-input-number>
            </el-form-item>
            <el-form-item label="覆盖区域">
                <el-input-number
                    v-model="contentData.overlap_height"
                    :min="0"
                    :max="520"
                    :step="10"
                    controls-position="right"
                    :placeholder="defaultOverlapHeight.toString()"
                    class="!w-full"
                >
                    <template #append>rpx</template>
                </el-input-number>
            </el-form-item>
            <div class="mt-2 text-xs text-tx-secondary">
                团队信息框向上覆盖轮播图的高度，默认 280rpx；轮播图高度与覆盖区域分开控制。
            </div>
            <div v-if="showHeightWarning" class="mt-2 text-xs text-warning">
                {{ heightWarningText }}
            </div>
        </el-card>
        <el-card shadow="never" class="!border-none flex-1 mt-2">
            <div class="flex items-end">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">顶部图片</div>
                <div class="ml-2 text-xs text-tx-secondary">仅配置1张，首页顶部不可左右滑动</div>
            </div>
            <div v-if="bannerItem" class="w-full p-4 mt-4 bg-fill-light">
                <div class="flex justify-center w-[467px]">
                    <material-picker
                        size="122px"
                        v-model="bannerItem.image"
                        upload-class="bg-body"
                        exclude-domain
                    >
                        <template #upload>
                            <div class="w-[122px] h-[122px] banner-upload-btn">
                                顶部图
                            </div>
                        </template>
                    </material-picker>
                    <material-picker
                        class="ml-[40px]"
                        size="122px"
                        v-model="bannerItem.bg"
                        upload-class="bg-body"
                        exclude-domain
                    >
                        <template #upload>
                            <div class="w-[122px] h-[122px] banner-upload-btn">
                                背景图
                            </div>
                        </template>
                    </material-picker>
                </div>
                <div class="flex-1">
                    <el-form-item class="mt-[18px]" label="背景色">
                        <div class="flex items-center gap-3">
                            <el-color-picker v-model="bannerItem.bg_color" />
                            <span class="text-xs text-tx-secondary">
                                背景联动开启时优先使用
                            </span>
                        </div>
                    </el-form-item>
                    <el-form-item class="mt-[18px]" label="图片链接">
                        <link-picker v-if="type == 'mobile'" v-model="bannerItem.link" />
                        <el-input
                            v-if="type == 'pc'"
                            placeholder="请输入链接"
                            v-model="bannerItem.link.path"
                        />
                    </el-form-item>
                    <el-form-item label="是否显示" class="mt-[18px] !mb-0">
                        <el-switch
                            v-model="bannerItem.is_show"
                            active-value="1"
                            inactive-value="0"
                        />
                    </el-form-item>
                </div>
            </div>
        </el-card>
    </el-form>
</template>
<script lang="ts" setup>
import { cloneDeep } from 'lodash-es'
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

const defaultHeight = computed(() => {
    return 321
})

const defaultOverlapHeight = computed(() => {
    return 280
})

const heightTip = computed(() => {
    return '建议高度：530-1080rpx'
})

const showHeightWarning = computed(() => {
    const height = contentData.value.height || defaultHeight.value
    return height < 530 || height > 1080
})

const heightWarningText = computed(() => {
    return '当前高度超出建议范围，可能影响显示效果'
})

const createDefaultBannerItem = (): OptionsType['content']['data'][number] => ({
    is_show: '1',
    image: '',
    bg: '',
    bg_color: '#000000',
    name: '',
    slogan: '',
    slogan_top: null,
    slogan_color: '',
    link: {}
})

const bannerItem = computed(() => contentData.value.data?.[0])

watch(
    () => props.content.style,
    (style) => {
        if (style === 1) {
            return
        }

        const content = cloneDeep(props.content)
        content.style = 1
        emits('update:content', content)
    },
    { immediate: true }
)

watch(
    () => props.content.data,
    (data) => {
        const rawFirstItem = Array.isArray(data) && data[0] ? data[0] : {}
        const firstItem =
            rawFirstItem && typeof rawFirstItem === 'object'
                ? (rawFirstItem as Record<string, any>)
                : {}
        const normalizedItem = {
            ...createDefaultBannerItem(),
            ...firstItem,
            link: firstItem.link && typeof firstItem.link === 'object' ? firstItem.link : {}
        }

        if (
            Array.isArray(data) &&
            data.length === 1 &&
            JSON.stringify(data[0]) === JSON.stringify(normalizedItem)
        ) {
            return
        }

        const content = cloneDeep(props.content)
        content.data = [normalizedItem]
        emits('update:content', content)
    },
    { immediate: true, deep: true }
)
</script>

<style lang="scss" scoped>
.banner-upload-btn {
    @apply text-tx-secondary box-border rounded border-br border-dashed border flex flex-col justify-center items-center;
}
</style>
