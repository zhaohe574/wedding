<template>
    <div>
        <el-form label-width="80px">
            <!-- 展示样式 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">展示样式</div>
                </div>
                <el-radio-group v-model="contentData.style">
                    <el-radio :value="1">网格布局</el-radio>
                    <el-radio :value="2">横向滑动</el-radio>
                </el-radio-group>
                <el-form-item label="每行数量" class="mt-4">
                    <el-select v-model="contentData.per_line" style="width: 300px">
                        <el-option label="4个" :value="4" />
                        <el-option label="5个" :value="5" />
                    </el-select>
                </el-form-item>
            </el-card>

            <!-- 快捷入口设置 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">快捷入口设置</div>
                    <div class="text-xs text-tx-secondary ml-2">建议图标尺寸：80px*80px</div>
                </div>
                <div class="flex-1 mt-4">
                    <draggable
                        :model-value="Array.isArray(contentData.data) ? contentData.data : []"
                        @update:model-value="(val: any[]) => emits('update:content', { ...props.content, data: val })"
                        :item-key="(el: any, i: number) => (el && el.title) ? el.title + i : 'i' + i"
                        animation="300"
                        handle=".drag-handle"
                    >
                        <template #item="{ element, index }">
                            <div class="entry-item mb-4 p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-start">
                                    <!-- 拖拽手柄 -->
                                    <div class="drag-handle cursor-move mr-3 mt-2">
                                        <icon name="el-icon-Rank" :size="18" color="#999" />
                                    </div>

                                    <!-- 图标 -->
                                    <div class="mr-4 flex-shrink-0">
                                        <material-picker
                                            v-model="element.icon"
                                            :limit="1"
                                            type="image"
                                        >
                                            <div class="w-[60px] h-[60px] rounded overflow-hidden bg-white border border-gray-200 flex items-center justify-center cursor-pointer hover:border-primary">
                                                <el-image
                                                    v-if="element.icon"
                                                    :src="element.icon"
                                                    fit="cover"
                                                    class="w-full h-full"
                                                />
                                                <icon v-else name="el-icon-Plus" :size="24" color="#999" />
                                            </div>
                                        </material-picker>
                                    </div>

                                    <!-- 表单 -->
                                    <div class="flex-1 min-w-0">
                                        <el-form-item label="标题" label-width="60px" class="!mb-2">
                                            <el-input
                                                v-model="element.title"
                                                placeholder="请输入标题"
                                                style="width: 200px"
                                            />
                                        </el-form-item>
                                        <el-form-item label="跳转链接" label-width="60px" class="!mb-0">
                                            <link-picker v-model="element.link" />
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
                    <el-button type="primary" :icon="Plus" @click="handleAdd">
                        添加快捷入口
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

function ensureDataArray() {
    if (!Array.isArray(props.content?.data)) {
        emits('update:content', { ...props.content, data: [] })
    }
}

onMounted(() => {
    ensureDataArray()
})

// 添加快捷入口
function handleAdd() {
    const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
    const newItem: QuickEntryItem = {
        icon: '',
        title: '新入口',
        link: { path: '', type: 'shop' },
        is_show: '1',
        sort: list.length + 1
    }
    list.push(newItem)
    contentData.value = { ...contentData.value, data: list }
}

// 删除快捷入口
function handleDelete(index: number) {
    const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
    list.splice(index, 1)
    contentData.value = { ...contentData.value, data: list }
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
