<template>
    <el-form label-width="96px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">
                    四宫格入口
                </div>
                <div class="ml-2 text-xs text-tx-secondary">
                    首屏数据条下方图文入口；支持大图/小图/通栏组合，建议 4 格（1 大 + 2 小 + 1 通栏），可拖动排序
                </div>
            </div>
            <el-form-item label="显示本区块">
                <el-switch v-model="contentData.enabled" :active-value="1" :inactive-value="0" />
            </el-form-item>
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
                                height="148px"
                                v-model="item.image"
                                upload-class="bg-body"
                                exclude-domain
                            >
                                <template #upload>
                                    <div class="w-[396px] h-[148px] cat-upload-btn">卡片图</div>
                                </template>
                            </material-picker>
                            <div class="mt-2 text-xs text-tx-secondary">
                                建议尺寸：大图 335×354px、小图 335×168px、通栏 690×168px；不上传时显示深色渐变底。
                            </div>
                            <el-form-item class="mt-[18px]" label="标题">
                                <el-input
                                    v-model="item.title"
                                    maxlength="16"
                                    show-word-limit
                                    placeholder="如 查档期 / 西式主持"
                                />
                            </el-form-item>
                            <el-form-item class="mt-[18px]" label="副标题">
                                <el-input
                                    v-model="item.subtitle"
                                    maxlength="24"
                                    show-word-limit
                                    placeholder="选填，如 SCHEDULE QUERY"
                                />
                            </el-form-item>
                            <el-form-item class="mt-[18px]" label="版式">
                                <el-radio-group v-model="item.size">
                                    <el-radio-button
                                        v-for="opt in HOME_SERVICE_CATEGORY_SIZES"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </el-radio-button>
                                </el-radio-group>
                            </el-form-item>
                            <el-form-item class="mt-[18px]" label="文字位置">
                                <el-radio-group v-model="item.text_position">
                                    <el-radio-button
                                        v-for="opt in HOME_SERVICE_CATEGORY_TEXT_POSITIONS"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </el-radio-button>
                                </el-radio-group>
                            </el-form-item>
                            <el-form-item class="mt-[18px]" label="文字对齐">
                                <el-radio-group v-model="item.text_align">
                                    <el-radio-button
                                        v-for="opt in HOME_SERVICE_CATEGORY_TEXT_ALIGNS"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </el-radio-button>
                                </el-radio-group>
                            </el-form-item>
                            <el-form-item class="mt-[18px]" label="点击跳转">
                                <link-picker v-if="type == 'mobile'" v-model="item.link" />
                                <el-input
                                    v-if="type == 'pc'"
                                    v-model="item.link.path"
                                    placeholder="请输入链接"
                                />
                            </el-form-item>
                            <el-form-item label="显示这个入口" class="mt-[18px] !mb-0">
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
            <div class="mt-4">
                <el-button class="w-full" type="primary" @click="handleAdd">添加入口</el-button>
            </div>
            <div class="mt-2 text-xs text-tx-secondary">
                标题或跳转链接缺一个，这个入口就不会出现在真机和预览里。
            </div>
        </el-card>
    </el-form>
</template>

<script lang="ts" setup>
import { cloneDeep } from 'lodash-es'
import type { PropType } from 'vue'
import Draggable from 'vuedraggable'

import {
    HOME_SERVICE_CATEGORY_SIZES,
    HOME_SERVICE_CATEGORY_TEXT_ALIGNS,
    HOME_SERVICE_CATEGORY_TEXT_POSITIONS
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

const handleAdd = () => {
    const content = cloneDeep(props.content)
    if (!Array.isArray(content.data)) {
        content.data = []
    }
    content.data.push({
        is_show: '1',
        title: '新入口',
        subtitle: '',
        image: '',
        size: 'small',
        text_position: 'bottom',
        text_align: 'left',
        link: {
            path: '',
            type: 'shop'
        }
    })
    emits('update:content', content)
}

const handleDelete = (index: number) => {
    const content = cloneDeep(props.content)
    if (!Array.isArray(content.data)) {
        content.data = []
    }
    content.data.splice(index, 1)
    emits('update:content', content)
}
</script>

<style lang="scss" scoped>
.cat-upload-btn {
    @apply text-tx-secondary box-border rounded border-br border-dashed border flex flex-col justify-center items-center;
}
</style>
