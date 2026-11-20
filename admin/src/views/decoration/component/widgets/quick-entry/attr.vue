<template>
    <el-form label-width="96px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">我的服务</div>
                <div class="ml-2 text-xs text-tx-secondary">个人中心列表</div>
            </div>
            <el-form-item label="显示本区块">
                <div class="flex items-center gap-3">
                    <el-switch
                        v-model="contentData.enabled"
                        :active-value="1"
                        :inactive-value="0"
                    />
                    <span class="text-xs text-tx-secondary">
                        关闭后个人中心不显示「我的服务」面板
                    </span>
                </div>
            </el-form-item>
            <el-form-item label="面板标题">
                <el-input
                    v-model="contentData.title"
                    maxlength="12"
                    show-word-limit
                    placeholder="我的服务"
                />
            </el-form-item>
        </el-card>

        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">入口列表</div>
                <div class="text-xs text-tx-secondary ml-2">
                    按顺序展示；可控制显隐与跳转，行数不限
                </div>
            </div>
            <div class="flex-1">
                <draggable
                    :model-value="contentData.data || []"
                    @update:model-value="updateList"
                    :item-key="(el: any, i: number) => (el && el.title ? el.title + i : 'i' + i)"
                    animation="300"
                    handle=".drag-handle"
                >
                    <template #item="{ element, index }">
                        <div class="entry-item mb-4 p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-start">
                                <div class="drag-handle cursor-move mr-3 mt-2">
                                    <icon name="el-icon-Rank" :size="18" color="#999" />
                                </div>

                                <div class="flex-1 min-w-0">
                                    <el-form-item label="标题" label-width="60px" class="!mb-2">
                                        <el-input
                                            v-model="element.title"
                                            placeholder="我的收藏"
                                            style="width: 200px"
                                        />
                                    </el-form-item>
                                    <el-form-item label="标识" label-width="60px" class="!mb-2">
                                        <el-input
                                            v-model="element.key"
                                            placeholder="favorite / activity…"
                                            style="width: 200px"
                                            clearable
                                        />
                                        <div class="text-xs text-tx-secondary ml-2">
                                            匹配实时数量用，可留空
                                        </div>
                                    </el-form-item>
                                    <el-form-item label="副标题" label-width="60px" class="!mb-2">
                                        <el-input
                                            v-model="element.subtitle"
                                            placeholder="可选，显示在标题下方"
                                            style="width: 200px"
                                        />
                                    </el-form-item>
                                    <el-form-item label="跳转" label-width="60px" class="!mb-0">
                                        <link-picker v-model="element.link" />
                                    </el-form-item>
                                </div>

                                <div class="ml-4 flex flex-col gap-2 flex-shrink-0">
                                    <el-switch
                                        v-model="element.is_show"
                                        active-value="1"
                                        inactive-value="0"
                                        active-text="显示"
                                    />
                                    <el-button
                                        type="danger"
                                        text
                                        :icon="Delete"
                                        @click="handleDelete(index)"
                                    >
                                        删除
                                    </el-button>
                                </div>
                            </div>
                        </div>
                    </template>
                </draggable>

                <el-button type="primary" :icon="Plus" @click="handleAdd">
                    添加入口
                </el-button>
            </div>
        </el-card>
    </el-form>
</template>

<script lang="ts" setup>
import { Delete, Plus } from '@element-plus/icons-vue'
import { computed, type PropType } from 'vue'
import draggable from 'vuedraggable'

import type options from './options'
import type { QuickEntryItem } from './options'

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

function updateList(list: QuickEntryItem[]) {
    emits('update:content', { ...props.content, data: list })
}

function handleAdd() {
    const list = [...(contentData.value.data || [])]
    list.push({
        key: '',
        title: '新入口',
        subtitle: '',
        link: { path: '' },
        is_show: '1'
    })
    updateList(list)
}

function handleDelete(index: number) {
    const list = [...(contentData.value.data || [])]
    list.splice(index, 1)
    updateList(list)
}
</script>

<style lang="scss" scoped>
.entry-item {
    border: 1px solid #e5e7eb;
    transition: all 0.2s ease;

    &:hover {
        border-color: var(--el-color-primary-light-5);
    }
}

.drag-handle {
    &:hover {
        color: var(--el-color-primary);
    }
}
</style>
