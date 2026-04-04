<template>
    <div>
        <el-form label-width="80px">
            <!-- 基础设置 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">基础设置</div>
                </div>
                <el-form-item label="组件标题">
                    <el-input
                        v-model="contentData.title"
                        placeholder="请输入标题"
                        style="width: 300px"
                    />
                </el-form-item>
                <el-form-item label="显示搜索">
                    <el-switch
                        v-model="contentData.show_search"
                        :active-value="1"
                        :inactive-value="0"
                    />
                </el-form-item>
            </el-card>

            <!-- 展示样式 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">展示样式</div>
                </div>
                <el-radio-group v-model="contentData.style">
                    <el-radio :value="1">折叠面板</el-radio>
                    <el-radio :value="2">列表式</el-radio>
                </el-radio-group>
            </el-card>

            <!-- 问题列表 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">问题列表</div>
                    <div class="text-xs text-tx-secondary ml-2">最多添加20个问题</div>
                </div>
                <div class="flex-1 mt-4">
                    <draggable
                        :model-value="Array.isArray(contentData.data) ? contentData.data : []"
                        @update:model-value="(val: any[]) => emits('update:content', { ...props.content, data: val })"
                        :item-key="(el: any, i: number) => (el && el.question) ? el.question + i : 'i' + i"
                        animation="300"
                        handle=".drag-handle"
                    >
                        <template #item="{ element, index }">
                            <div class="faq-item mb-4 p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-start">
                                    <!-- 拖拽手柄 -->
                                    <div class="drag-handle cursor-move mr-3 mt-2">
                                        <icon name="el-icon-Rank" :size="18" color="#999" />
                                    </div>

                                    <!-- 表单 -->
                                    <div class="flex-1 min-w-0">
                                        <el-form-item label="分类" label-width="60px" class="!mb-2">
                                            <el-input
                                                v-model="element.category"
                                                placeholder="如：预约相关"
                                                style="width: 200px"
                                            />
                                        </el-form-item>
                                        <el-form-item label="问题" label-width="60px" class="!mb-2">
                                            <el-input
                                                v-model="element.question"
                                                placeholder="请输入问题"
                                                style="width: 100%"
                                            />
                                        </el-form-item>
                                        <el-form-item label="答案" label-width="60px" class="!mb-0">
                                            <el-input
                                                v-model="element.answer"
                                                type="textarea"
                                                :rows="3"
                                                placeholder="请输入答案"
                                                style="width: 100%"
                                            />
                                        </el-form-item>
                                    </div>

                                    <!-- 操作按钮 -->
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

                    <!-- 添加按钮 -->
                    <el-button
                        type="primary"
                        :icon="Plus"
                        @click="handleAdd"
                        :disabled="(contentData.data?.length || 0) >= 20"
                    >
                        添加问题
                    </el-button>
                </div>
            </el-card>
        </el-form>
    </div>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'
import { Delete, Plus } from '@element-plus/icons-vue'
import draggable from 'vuedraggable'
import type options from './options'
import type { FaqItem } from './options'

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

function ensureDataArray() {
    if (!Array.isArray(props.content?.data)) {
        emits('update:content', { ...props.content, data: [] })
    }
}

onMounted(() => {
    ensureDataArray()
})

// 添加问题
function handleAdd() {
    const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
    if (list.length >= 20) return
    
    const newItem: FaqItem = {
        question: '新问题',
        answer: '请输入答案',
        category: '常见问题',
        is_show: '1',
        sort: list.length + 1
    }
    list.push(newItem)
    contentData.value = { ...contentData.value, data: list }
}

// 删除问题
function handleDelete(index: number) {
    const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
    list.splice(index, 1)
    contentData.value = { ...contentData.value, data: list }
}
</script>

<style lang="scss" scoped>
.faq-item {
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
