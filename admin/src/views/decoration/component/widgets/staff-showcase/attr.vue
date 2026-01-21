<template>
    <div>
        <el-form label-width="80px">
            <!-- 基础设置 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">基础设置</div>
                </div>
                <el-form-item label="组件标题">
                    <el-input v-model="contentData.title" placeholder="请输入标题" style="width: 300px" />
                </el-form-item>
                <el-form-item label="显示数量">
                    <el-input-number 
                        v-model="contentData.show_count" 
                        :min="1" 
                        :max="20" 
                        style="width: 300px"
                    />
                </el-form-item>
                <el-form-item label="查看更多">
                    <el-switch v-model="contentData.show_more" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item v-if="contentData.show_more" label="更多链接">
                    <link-picker v-model="contentData.more_link" />
                </el-form-item>
            </el-card>

            <!-- 展示样式 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">展示样式</div>
                </div>
                <el-radio-group v-model="contentData.style">
                    <el-radio :value="1">卡片样式</el-radio>
                    <el-radio :value="2">列表样式</el-radio>
                </el-radio-group>
                <el-form-item v-if="contentData.style == 1" label="每行数量" class="mt-4">
                    <el-select v-model="contentData.per_line" style="width: 300px">
                        <el-option :value="1" label="1个" />
                        <el-option :value="2" label="2个" />
                        <el-option :value="3" label="3个" />
                    </el-select>
                </el-form-item>
            </el-card>

            <!-- 人员列表 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">人员列表</div>
                    <div class="text-xs text-tx-secondary ml-2">建议头像尺寸：400px*400px</div>
                </div>
                <div class="staff-list">
                    <draggable
                        v-model="contentData.data"
                        item-key="index"
                        animation="300"
                        handle=".drag-handle"
                    >
                        <template #item="{ element, index }">
                            <div class="staff-item mb-4 p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-start">
                                    <!-- 拖拽手柄 -->
                                    <div class="drag-handle cursor-move mr-3 mt-2">
                                        <icon name="el-icon-Rank" :size="18" color="#999" />
                                    </div>
                                    
                                    <!-- 头像 -->
                                    <div class="mr-4">
                                        <material-picker
                                            v-model="element.avatar"
                                            :limit="1"
                                            upload-class="bg-body"
                                        >
                                            <div 
                                                class="w-[80px] h-[80px] rounded-full overflow-hidden bg-gray-200 flex items-center justify-center cursor-pointer hover:opacity-80 transition"
                                            >
                                                <el-image
                                                    v-if="element.avatar"
                                                    :src="element.avatar"
                                                    fit="cover"
                                                    class="w-full h-full"
                                                />
                                                <icon v-else name="el-icon-Plus" :size="24" color="#999" />
                                            </div>
                                        </material-picker>
                                    </div>

                                    <!-- 信息表单 -->
                                    <div class="flex-1">
                                        <div class="grid grid-cols-2 gap-4">
                                            <el-form-item label="姓名" label-width="60px" class="!mb-2">
                                                <el-input v-model="element.name" placeholder="请输入姓名" />
                                            </el-form-item>
                                            <el-form-item label="角色" label-width="60px" class="!mb-2">
                                                <el-select v-model="element.role" placeholder="选择角色">
                                                    <el-option value="摄影师" label="摄影师" />
                                                    <el-option value="化妆师" label="化妆师" />
                                                    <el-option value="策划师" label="策划师" />
                                                    <el-option value="主持人" label="主持人" />
                                                    <el-option value="摄像师" label="摄像师" />
                                                    <el-option value="花艺师" label="花艺师" />
                                                </el-select>
                                            </el-form-item>
                                            <el-form-item label="评分" label-width="60px" class="!mb-2">
                                                <el-input-number 
                                                    v-model="element.rating" 
                                                    :min="0" 
                                                    :max="5" 
                                                    :step="0.1"
                                                    :precision="1"
                                                />
                                            </el-form-item>
                                            <el-form-item label="订单数" label-width="60px" class="!mb-2">
                                                <el-input-number 
                                                    v-model="element.order_count" 
                                                    :min="0"
                                                />
                                            </el-form-item>
                                        </div>
                                        <el-form-item label="标签" label-width="60px" class="!mb-2">
                                            <el-select
                                                v-model="element.tags"
                                                multiple
                                                filterable
                                                allow-create
                                                default-first-option
                                                placeholder="输入或选择标签"
                                                style="width: 100%"
                                            >
                                                <el-option value="专业" label="专业" />
                                                <el-option value="耐心" label="耐心" />
                                                <el-option value="细心" label="细心" />
                                                <el-option value="创意" label="创意" />
                                                <el-option value="热情" label="热情" />
                                                <el-option value="经验丰富" label="经验丰富" />
                                                <el-option value="好评如潮" label="好评如潮" />
                                            </el-select>
                                        </el-form-item>
                                        <el-form-item label="链接" label-width="60px" class="!mb-2">
                                            <link-picker v-model="element.link" />
                                        </el-form-item>
                                    </div>

                                    <!-- 操作按钮 -->
                                    <div class="ml-4 flex flex-col gap-2">
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
                        添加人员
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
import MaterialPicker from '@/components/material/picker.vue'
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
    }
})

const contentData = computed({
    get: () => props.content,
    set: (newValue) => {
        emits('update:content', newValue)
    }
})

// 添加人员
const handleAdd = () => {
    contentData.value.data.push({
        is_show: '1',
        avatar: '',
        name: '人员名称',
        role: '摄影师',
        rating: 5.0,
        order_count: 0,
        tags: [],
        link: {}
    })
}

// 删除人员
const handleDelete = (index: number) => {
    contentData.value.data.splice(index, 1)
}
</script>

<style lang="scss" scoped>
.staff-item {
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
