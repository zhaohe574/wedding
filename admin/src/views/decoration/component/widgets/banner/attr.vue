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
                开启背景联动后，需为轮播图设置背景图，轮播图切换时，背景图也跟随切换，此时该页面自身的“页面背景“设置将失效。
            </div>
        </el-card>
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">轮播图高度</div>
                <div class="text-xs text-tx-secondary ml-2">
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
            <div v-if="showHeightWarning" class="text-warning text-xs mt-2">
                {{ heightWarningText }}
            </div>
        </el-card>
        <el-card shadow="never" class="!border-none flex-1 mt-2">
            <div class="flex items-end">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">轮播图片</div>
                <div class="text-xs text-tx-secondary ml-2">最多添加5张，建议图片尺寸：750px*340px</div>
            </div>
            <div class="flex-1">
                <draggable
                    class="draggable"
                    v-model="contentData.data"
                    animation="300"
                    handle=".drag-move"
                    item-key="index"
                >
                    <template v-slot:item="{ element: item, index }">
                        <del-wrap :key="index" @close="handleDelete(index)" class="w-full">
                            <div class="bg-fill-light w-full p-4 mt-4">
                                <div class="flex justify-center w-[467px]">
                                    <material-picker
                                        size="122px"
                                        v-model="item.image"
                                        upload-class="bg-body"
                                        exclude-domain
                                    >
                                        <template #upload>
                                            <div class="w-[122px] h-[122px] banner-upload-btn">
                                                轮播图
                                            </div>
                                        </template>
                                    </material-picker>
                                    <material-picker
                                        class="ml-[40px]"
                                        size="122px"
                                        v-model="item.bg"
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
                                    <el-form-item class="mt-[18px]" label="宣传语">
                                        <el-input
                                            v-model="item.slogan"
                                            type="textarea"
                                            :rows="3"
                                            maxlength="60"
                                            show-word-limit
                                            placeholder="请输入宣传语，支持换行显示"
                                        />
                                    </el-form-item>
                                    <el-form-item label="距顶部" class="mt-[18px]">
                                        <el-input-number
                                            v-model="item.slogan_top"
                                            :min="0"
                                            :max="2000"
                                            :step="10"
                                            controls-position="right"
                                            class="!w-full"
                                        />
                                        <div class="text-xs text-tx-secondary mt-2">
                                            留空时使用默认值：{{ defaultSloganTop }}rpx
                                        </div>
                                    </el-form-item>
                                    <el-form-item class="mt-[18px]" label="字体颜色">
                                        <div class="w-full">
                                            <color-picker v-model="item.slogan_color" />
                                            <div class="text-xs text-tx-secondary mt-2">
                                                未设置时默认白色
                                            </div>
                                        </div>
                                    </el-form-item>
                                    <el-form-item class="mt-[18px]" label="图片链接">
                                        <link-picker v-if="type == 'mobile'" v-model="item.link" />
                                        <el-input
                                            v-if="type == 'pc'"
                                            placeholder="请输入链接"
                                            v-model="item.link.path"
                                        />
                                    </el-form-item>
                                    <el-form-item label="是否显示" class="mt-[18px] !mb-0">
                                        <div class="flex-1 flex items-center">
                                            <el-switch
                                                v-model="item.is_show"
                                                active-value="1"
                                                inactive-value="0"
                                            />
                                            <div class="drag-move cursor-move ml-auto">
                                                <icon name="el-icon-Rank" size="18" />
                                            </div>
                                        </div>
                                    </el-form-item>
                                </div>
                            </div>
                        </del-wrap>
                    </template>
                </draggable>
            </div>
            <div class="mt-4" v-if="content.data?.length < limit">
                <el-button class="w-full" type="primary" @click="handleAdd">添加图片</el-button>
            </div>
        </el-card>
    </el-form>
</template>
<script lang="ts" setup>
import { cloneDeep } from 'lodash-es'
import type { PropType } from 'vue'
import Draggable from 'vuedraggable'

import feedback from '@/utils/feedback'

import type options from './options'

type OptionsType = ReturnType<typeof options>
const emits = defineEmits<(event: 'update:content', data: OptionsType['content']) => void>()
const limit = 5
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

const heightTip = computed(() => {
    return '建议高度：250-500rpx'
})

const showHeightWarning = computed(() => {
    const height = contentData.value.height || defaultHeight.value
    return height < 250 || height > 500
})

const heightWarningText = computed(() => {
    return '当前高度超出建议范围，可能影响显示效果'
})

const defaultSloganTop = computed(() => {
    return 120
})

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

const handleAdd = () => {
    if (props.content.data?.length < limit) {
        const content = cloneDeep(props.content)
        content.data.push({
            is_show: '1',
            image: '',
            bg: '',
            name: '',
            slogan: '',
            slogan_top: null,
            slogan_color: '',
            link: {}
        })
        emits('update:content', content)
    } else {
        feedback.msgError(`最多添加${limit}张图片`)
    }
}
const handleDelete = (index: number) => {
    if (props.content.data?.length <= 1) {
        return feedback.msgError('最少保留一张图片')
    }
    const content = cloneDeep(props.content)
    content.data.splice(index, 1)
    emits('update:content', content)
}
</script>

<style lang="scss" scoped>
.banner-upload-btn {
    @apply text-tx-secondary box-border rounded border-br border-dashed border flex flex-col justify-center items-center;
}
</style>
