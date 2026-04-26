<template>
    <el-form label-width="80px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] font-medium">轮播设置</div>
                <div class="text-xs text-tx-secondary ml-2">显示在立即预定下方第一个图片区</div>
            </div>
            <el-form-item label="高度">
                <el-input-number
                    v-model="contentData.height"
                    :min="180"
                    :max="520"
                    :step="10"
                    controls-position="right"
                    class="!w-full"
                />
            </el-form-item>
            <el-form-item label="自动播放">
                <el-switch v-model="contentData.autoplay" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <el-form-item label="间隔(秒)">
                <el-input-number
                    v-model="contentData.interval"
                    :min="2"
                    :max="10"
                    :step="1"
                    controls-position="right"
                    class="!w-full"
                />
            </el-form-item>
        </el-card>

        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end">
                <div class="text-base text-[#101010] font-medium">轮播图片</div>
                <div class="text-xs text-tx-secondary ml-2">最多添加5张，建议图片尺寸：690px*300px</div>
            </div>
            <draggable
                v-model="contentData.data"
                animation="300"
                handle=".drag-move"
                item-key="index"
            >
                <template #item="{ element: item, index }">
                    <del-wrap :key="index" @close="handleDelete(index)" class="w-full">
                        <div class="bg-fill-light w-full p-4 mt-4">
                            <material-picker
                                width="396px"
                                height="172px"
                                v-model="item.image"
                                upload-class="bg-body"
                                exclude-domain
                            />
                            <el-form-item class="mt-[18px]" label="图片名称">
                                <el-input v-model="item.name" maxlength="20" show-word-limit />
                            </el-form-item>
                            <el-form-item class="mt-[18px]" label="图片链接">
                                <link-picker v-if="type == 'mobile'" v-model="item.link" />
                                <el-input
                                    v-if="type == 'pc'"
                                    v-model="item.link.path"
                                    placeholder="请输入链接"
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
                    </del-wrap>
                </template>
            </draggable>
            <div class="mt-4" v-if="contentData.data?.length < limit">
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

const limit = 5
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

const handleAdd = () => {
    if (props.content.data?.length < limit) {
        const content = cloneDeep(props.content)
        content.data.push({
            is_show: '1',
            image: '',
            name: '',
            link: {
                path: '/pages/schedule_query/schedule_query',
                type: 'shop'
            }
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
