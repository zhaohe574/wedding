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
            </el-card>

            <!-- 展示样式 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">展示样式</div>
                </div>
                <el-radio-group v-model="contentData.style">
                    <el-radio :value="1">时间轴</el-radio>
                    <el-radio :value="2">步骤卡片</el-radio>
                    <el-radio :value="3">横向流程图</el-radio>
                </el-radio-group>
                <el-form-item label="流程线颜色" class="mt-4">
                    <el-color-picker v-model="contentData.line_color" />
                </el-form-item>
                <el-form-item label="图标样式">
                    <el-radio-group v-model="contentData.icon_style">
                        <el-radio :value="1">圆形</el-radio>
                        <el-radio :value="2">方形</el-radio>
                    </el-radio-group>
                </el-form-item>
            </el-card>

            <!-- 流程步骤设置 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">流程步骤设置</div>
                    <div class="text-xs text-tx-secondary ml-2">建议图标尺寸：80px*80px，最多添加8个步骤</div>
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
                            <div class="step-item mb-4 p-4 bg-gray-50 rounded-lg">
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
                                        <el-form-item label="步骤标题" label-width="70px" class="!mb-2">
                                            <el-input
                                                v-model="element.title"
                                                placeholder="请输入步骤标题"
                                                style="width: 250px"
                                            />
                                        </el-form-item>
                                        <el-form-item label="步骤描述" label-width="70px" class="!mb-0">
                                            <el-input
                                                v-model="element.description"
                                                type="textarea"
                                                :rows="2"
                                                placeholder="请输入步骤描述"
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
                        :disabled="(contentData.data?.length || 0) >= 8"
                    >
                        添加步骤
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
import type { ProcessStep } from './options'

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

// 添加步骤
function handleAdd() {
    const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
    if (list.length >= 8) return
    
    const newItem: ProcessStep = {
        icon: '',
        title: '新步骤',
        description: '请输入步骤描述',
        is_show: '1',
        sort: list.length + 1
    }
    list.push(newItem)
    contentData.value = { ...contentData.value, data: list }
}

// 删除步骤
function handleDelete(index: number) {
    const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
    list.splice(index, 1)
    contentData.value = { ...contentData.value, data: list }
}
</script>

<style lang="scss" scoped>
.step-item {
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
