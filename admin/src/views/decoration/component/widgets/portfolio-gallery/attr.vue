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
                <el-form-item label="分类标签">
                    <el-switch v-model="contentData.show_tabs" :active-value="1" :inactive-value="0" />
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
                    <el-radio :value="1">网格布局</el-radio>
                    <el-radio :value="2">瀑布流</el-radio>
                    <el-radio :value="3">横向滑动</el-radio>
                </el-radio-group>
                <el-form-item v-if="contentData.style == 1" label="每行数量" class="mt-4">
                    <el-select v-model="contentData.per_line" style="width: 300px">
                        <el-option :value="2" label="2个" />
                        <el-option :value="3" label="3个" />
                    </el-select>
                </el-form-item>
            </el-card>

            <!-- 案例列表 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">案例列表</div>
                    <div class="text-xs text-tx-secondary ml-2">建议图片尺寸：750px*500px</div>
                </div>
                <div class="portfolio-list">
                    <draggable
                        v-model="contentData.data"
                        item-key="index"
                        animation="300"
                        handle=".drag-handle"
                    >
                        <template #item="{ element, index }">
                            <div class="portfolio-item mb-4 p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-start">
                                    <!-- 拖拽手柄 -->
                                    <div class="drag-handle cursor-move mr-3 mt-2">
                                        <icon name="el-icon-Rank" :size="18" color="#999" />
                                    </div>
                                    
                                    <!-- 封面图 -->
                                    <div class="mr-4">
                                        <material-picker
                                            v-model="element.cover"
                                            :limit="1"
                                            upload-class="bg-body"
                                        >
                                            <div 
                                                class="w-[120px] h-[80px] rounded overflow-hidden bg-gray-200 flex items-center justify-center cursor-pointer hover:opacity-80 transition"
                                            >
                                                <el-image
                                                    v-if="element.cover"
                                                    :src="element.cover"
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
                                            <el-form-item label="案例标题" label-width="70px" class="!mb-2">
                                                <el-input v-model="element.title" placeholder="请输入标题" />
                                            </el-form-item>
                                            <el-form-item label="分类" label-width="70px" class="!mb-2">
                                                <el-select 
                                                    v-model="element.category" 
                                                    placeholder="选择分类"
                                                    filterable
                                                    allow-create
                                                >
                                                    <el-option value="婚礼跟拍" label="婚礼跟拍" />
                                                    <el-option value="婚纱摄影" label="婚纱摄影" />
                                                    <el-option value="婚礼策划" label="婚礼策划" />
                                                    <el-option value="场地布置" label="场地布置" />
                                                    <el-option value="新娘妆容" label="新娘妆容" />
                                                </el-select>
                                            </el-form-item>
                                            <el-form-item label="类型" label-width="70px" class="!mb-2">
                                                <el-select v-model="element.type">
                                                    <el-option value="image" label="图片" />
                                                    <el-option value="video" label="视频" />
                                                </el-select>
                                            </el-form-item>
                                            <el-form-item label="浏览量" label-width="70px" class="!mb-2">
                                                <el-input-number v-model="element.views" :min="0" />
                                            </el-form-item>
                                        </div>
                                        <el-form-item label="跳转链接" label-width="70px" class="!mb-2">
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
                        添加案例
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

// 添加案例
const handleAdd = () => {
    contentData.value.data.push({
        is_show: '1',
        cover: '',
        title: '案例名称',
        category: '婚礼跟拍',
        type: 'image',
        views: 0,
        height: '360rpx',
        link: {}
    })
}

// 删除案例
const handleDelete = (index: number) => {
    contentData.value.data.splice(index, 1)
}
</script>

<style lang="scss" scoped>
.portfolio-item {
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
