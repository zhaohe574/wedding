<template>
    <el-form label-width="80px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] font-medium">区块设置</div>
                <div class="text-xs text-tx-secondary ml-2">
                    展示在首页底部，紧接「近期吉日」之后
                </div>
            </div>
            <el-form-item label="显示本区块">
                <div class="flex-1 flex items-center">
                    <el-switch
                        v-model="contentData.enabled"
                        :active-value="1"
                        :inactive-value="0"
                    />
                    <span class="ml-2 text-xs text-tx-secondary">
                        关闭后真机整块不显示（含标题）
                    </span>
                </div>
            </el-form-item>
            <el-form-item label="区块标题" class="!mb-0">
                <el-input
                    v-model="contentData.title"
                    maxlength="24"
                    show-word-limit
                    placeholder="留空使用「一次交付，全程在场」"
                />
            </el-form-item>
        </el-card>
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end">
                <div class="text-base text-[#101010] font-medium">服务承诺步骤</div>
                <div class="text-xs text-tx-secondary ml-2">
                    建议 3 条，最多 {{ MAX_ITEMS }} 条；全部删空时真机不显示该区块
                </div>
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
                            <el-form-item label="左侧标记">
                                <el-input
                                    v-model="item.tag"
                                    maxlength="8"
                                    show-word-limit
                                    placeholder="如 T-30 / T-01 / T-0"
                                />
                            </el-form-item>
                            <el-form-item class="mt-[18px]" label="步骤标题">
                                <el-input v-model="item.title" maxlength="32" show-word-limit />
                            </el-form-item>
                            <el-form-item class="mt-[18px]" label="补充说明">
                                <el-input
                                    v-model="item.desc"
                                    type="textarea"
                                    :rows="2"
                                    maxlength="80"
                                    show-word-limit
                                    placeholder="留空则真机只显示标题"
                                />
                            </el-form-item>
                            <el-form-item label="显示这一步" class="mt-[18px] !mb-0">
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
                <el-button
                    class="w-full"
                    type="primary"
                    :disabled="(contentData.data?.length || 0) >= MAX_ITEMS"
                    @click="handleAdd"
                >
                    添加步骤
                </el-button>
            </div>
        </el-card>
    </el-form>
</template>

<script lang="ts" setup>
import { HOME_FLOW_MAX_ITEMS } from '@shared/contracts/decorate'
import { cloneDeep } from 'lodash-es'
import type { PropType } from 'vue'
import Draggable from 'vuedraggable'

import type options from './options'

type OptionsType = ReturnType<typeof options>
type HomeFlowStep = OptionsType['content']['data'][number]

const MAX_ITEMS = HOME_FLOW_MAX_ITEMS

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

// 旧数据可能没有 data 字段，先补齐，否则 draggable 的 v-model 会写进 undefined
onMounted(() => {
    if (!Array.isArray(props.content.data)) {
        emits('update:content', { ...props.content, data: [] })
    }
})

const handleAdd = () => {
    const content = cloneDeep(props.content) as OptionsType['content']
    if (!Array.isArray(content.data)) {
        content.data = []
    }
    if (content.data.length >= MAX_ITEMS) {
        return
    }
    const step: HomeFlowStep = {
        tag: '',
        title: '新步骤',
        desc: '',
        is_show: '1'
    }
    content.data.push(step)
    emits('update:content', content)
}

const handleDelete = (index: number) => {
    const content = cloneDeep(props.content) as OptionsType['content']
    if (!Array.isArray(content.data)) {
        content.data = []
    }
    content.data.splice(index, 1)
    emits('update:content', content)
}
</script>
