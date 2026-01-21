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
                <el-form-item label="统计信息">
                    <el-switch v-model="contentData.show_stats" :active-value="1" :inactive-value="0" />
                    <span class="text-xs text-gray-500 ml-2">显示平均评分和评价数量</span>
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
                    <el-radio :value="2">横向滑动</el-radio>
                    <el-radio :value="3">简洁列表</el-radio>
                </el-radio-group>
            </el-card>

            <!-- 评价列表 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">评价列表</div>
                    <div class="text-xs text-tx-secondary ml-2">建议头像尺寸：200px*200px</div>
                </div>
                <div class="review-list">
                    <draggable
                        v-model="contentData.data"
                        item-key="index"
                        animation="300"
                        handle=".drag-handle"
                    >
                        <template #item="{ element, index }">
                            <div class="review-item mb-4 p-4 bg-gray-50 rounded-lg">
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
                                                class="w-[60px] h-[60px] rounded-full overflow-hidden bg-gray-200 flex items-center justify-center cursor-pointer hover:opacity-80 transition"
                                            >
                                                <el-image
                                                    v-if="element.avatar"
                                                    :src="element.avatar"
                                                    fit="cover"
                                                    class="w-full h-full"
                                                />
                                                <icon v-else name="el-icon-User" :size="24" color="#999" />
                                            </div>
                                        </material-picker>
                                    </div>

                                    <!-- 信息表单 -->
                                    <div class="flex-1">
                                        <div class="grid grid-cols-3 gap-4">
                                            <el-form-item label="客户姓名" label-width="70px" class="!mb-2">
                                                <el-input v-model="element.name" placeholder="请输入姓名" />
                                            </el-form-item>
                                            <el-form-item label="评分" label-width="70px" class="!mb-2">
                                                <el-rate v-model="element.rating" :max="5" />
                                            </el-form-item>
                                            <el-form-item label="日期" label-width="70px" class="!mb-2">
                                                <el-date-picker
                                                    v-model="element.date"
                                                    type="date"
                                                    format="YYYY-MM-DD"
                                                    value-format="YYYY-MM-DD"
                                                    placeholder="选择日期"
                                                />
                                            </el-form-item>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <el-form-item label="标签" label-width="70px" class="!mb-2">
                                                <el-select v-model="element.tag" placeholder="选择标签" clearable>
                                                    <el-option value="好评" label="好评" />
                                                    <el-option value="推荐" label="推荐" />
                                                    <el-option value="回头客" label="回头客" />
                                                    <el-option value="VIP" label="VIP" />
                                                </el-select>
                                            </el-form-item>
                                            <el-form-item label="服务项目" label-width="70px" class="!mb-2">
                                                <el-input v-model="element.service" placeholder="如：婚礼跟拍" />
                                            </el-form-item>
                                        </div>
                                        <el-form-item label="评价内容" label-width="70px" class="!mb-2">
                                            <el-input 
                                                v-model="element.content" 
                                                type="textarea" 
                                                :rows="2"
                                                placeholder="请输入评价内容"
                                            />
                                        </el-form-item>
                                        <el-form-item label="评价图片" label-width="70px" class="!mb-2">
                                            <material-picker
                                                v-model="element.images"
                                                :limit="9"
                                                upload-class="bg-body"
                                                multiple
                                            />
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
                        添加评价
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

// 添加评价
const handleAdd = () => {
    const today = new Date().toISOString().split('T')[0]
    contentData.value.data.push({
        is_show: '1',
        avatar: '',
        name: '客户姓名',
        rating: 5,
        content: '评价内容',
        date: today,
        tag: '好评',
        service: '',
        images: []
    })
}

// 删除评价
const handleDelete = (index: number) => {
    contentData.value.data.splice(index, 1)
}
</script>

<style lang="scss" scoped>
.review-item {
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
