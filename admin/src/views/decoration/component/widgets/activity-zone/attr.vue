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
                        :max="10" 
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
                    <el-radio :value="1">大图样式</el-radio>
                    <el-radio :value="2">网格样式</el-radio>
                    <el-radio :value="3">横向滑动</el-radio>
                    <el-radio :value="4">列表样式</el-radio>
                </el-radio-group>
            </el-card>

            <!-- 数据来源 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">数据来源</div>
                </div>
                <el-radio-group v-model="contentData.data_source">
                    <el-radio value="auto">自动获取最新活动</el-radio>
                    <el-radio value="manual">手动选择活动</el-radio>
                </el-radio-group>
                
                <!-- 手动选择活动 -->
                <div v-if="contentData.data_source === 'manual'" class="mt-4">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-sm text-gray-600">已选择 {{ selectedActivities.length }} 个活动</span>
                        <el-button type="primary" size="small" @click="openActivityPicker">
                            选择活动
                        </el-button>
                    </div>
                    
                    <!-- 已选活动列表 -->
                    <draggable
                        v-if="selectedActivities.length"
                        v-model="selectedActivities"
                        item-key="id"
                        animation="300"
                        handle=".drag-handle"
                        @end="handleDragEnd"
                    >
                        <template #item="{ element, index }">
                            <div class="selected-activity-item mb-2 p-3 bg-gray-50 rounded-lg flex items-center">
                                <!-- 拖拽手柄 -->
                                <div class="drag-handle cursor-move mr-3">
                                    <icon name="el-icon-Rank" :size="18" color="#999" />
                                </div>
                                
                                <!-- 封面 -->
                                <div class="flex-shrink-0 mr-3">
                                    <el-image
                                        :src="element.cover_image"
                                        fit="cover"
                                        class="w-16 h-16 rounded"
                                    >
                                        <template #error>
                                            <div class="w-16 h-16 flex items-center justify-center bg-gray-200 rounded">
                                                <icon name="el-icon-Picture" :size="24" color="#ccc" />
                                            </div>
                                        </template>
                                    </el-image>
                                </div>
                                
                                <!-- 信息 -->
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-900 truncate">{{ element.title }}</div>
                                    <div class="text-xs text-gray-500 mt-1">{{ element.create_time_text }}</div>
                                </div>
                                
                                <!-- 删除按钮 -->
                                <el-button 
                                    type="danger" 
                                    text 
                                    :icon="Delete"
                                    @click="handleRemoveActivity(index)"
                                />
                            </div>
                        </template>
                    </draggable>
                    
                    <el-empty v-else description="暂未选择活动" :image-size="80" />
                </div>
            </el-card>
        </el-form>

        <!-- 活动选择器 -->
        <activity-picker ref="activityPickerRef" @confirm="handleActivityConfirm" />
    </div>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'
import { ref, computed, watch } from 'vue'
import { Delete } from '@element-plus/icons-vue'
import draggable from 'vuedraggable'
import ActivityPicker from './activity-picker.vue'
import { getDecorateActivityList } from '@/api/decoration'
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
    get: () => {
        // 确保data_source有默认值
        if (!props.content.data_source) {
            return {
                ...props.content,
                data_source: 'auto'
            }
        }
        return props.content
    },
    set: (newValue) => {
        emits('update:content', newValue)
    }
})

const activityPickerRef = ref()
const selectedActivities = ref<any[]>([])

// 初始化时加载已选活动的详细信息
watch(() => props.content.activity_ids, async (newIds) => {
    if (newIds && newIds.length > 0) {
        await loadSelectedActivities(newIds)
    } else {
        selectedActivities.value = []
    }
}, { immediate: true })

// 加载已选活动的详细信息
const loadSelectedActivities = async (ids: number[]) => {
    try {
        // 获取所有活动数据
        const res = await getDecorateActivityList({ limit: 100 })
        const allActivities = res.data || []
        
        // 按照ids的顺序筛选活动
        selectedActivities.value = ids
            .map(id => allActivities.find((item: any) => item.id === id))
            .filter(Boolean)
    } catch (error) {
        console.error('加载活动详情失败:', error)
    }
}

// 打开活动选择器
const openActivityPicker = () => {
    activityPickerRef.value?.open(contentData.value.activity_ids || [])
}

// 确认选择活动
const handleActivityConfirm = async (ids: number[]) => {
    contentData.value.activity_ids = ids
    await loadSelectedActivities(ids)
}

// 移除活动
const handleRemoveActivity = (index: number) => {
    selectedActivities.value.splice(index, 1)
    contentData.value.activity_ids = selectedActivities.value.map(item => item.id)
}

// 拖拽结束
const handleDragEnd = () => {
    contentData.value.activity_ids = selectedActivities.value.map(item => item.id)
}
</script>

<style lang="scss" scoped>
.selected-activity-item {
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
