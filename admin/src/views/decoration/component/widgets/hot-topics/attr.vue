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
                <el-form-item label="话题来源">
                    <el-radio-group v-model="contentData.source">
                        <el-radio :value="1">手动选择</el-radio>
                        <el-radio :value="2">自动获取热门</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="显示数量">
                    <el-input-number
                        v-model="contentData.show_count"
                        :min="5"
                        :max="20"
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
                    <el-radio :value="1">标签云</el-radio>
                    <el-radio :value="2">横向滑动</el-radio>
                    <el-radio :value="3">列表式</el-radio>
                </el-radio-group>
            </el-card>

            <!-- 话题列表（仅手动选择时显示） -->
            <el-card v-if="contentData.source == 1" shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">话题列表</div>
                    <div class="text-xs text-tx-secondary ml-2">从已存在的话题中选择</div>
                </div>
                <div class="topic-list">
                    <draggable
                        :model-value="Array.isArray(contentData.data) ? contentData.data : []"
                        @update:model-value="(val: any[]) => emits('update:content', { ...props.content, data: val })"
                        :item-key="(el: any, i: number) => (el && el.topic_id) ? el.topic_id : 'i' + i"
                        animation="300"
                        handle=".drag-handle"
                    >
                        <template #item="{ element, index }">
                            <div class="topic-item mb-4 p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-start">
                                    <!-- 拖拽手柄 -->
                                    <div class="drag-handle cursor-move mr-3 mt-2">
                                        <icon name="el-icon-Rank" :size="18" color="#999" />
                                    </div>

                                    <!-- 话题信息（只读）与更换 -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap mb-2">
                                            <span class="font-medium text-gray-900">
                                                {{ getTopicName(element.topic_id) || '未选择' }}
                                            </span>
                                        </div>
                                        <el-form-item label="选择话题" class="!mb-0" label-width="80px">
                                            <el-select
                                                :model-value="element.topic_id || ''"
                                                placeholder="选择话题"
                                                filterable
                                                clearable
                                                style="width: 280px"
                                                @change="(id: number) => handleReplace(index, id)"
                                            >
                                                <el-option
                                                    v-for="topic in topicOptions"
                                                    :key="topic.id"
                                                    :label="topic.name"
                                                    :value="topic.id"
                                                />
                                            </el-select>
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
                    <div class="mt-2">
                        <span class="text-sm text-gray-500 mr-2">添加话题：</span>
                        <el-select
                            v-model="addTopicId"
                            placeholder="请选择要展示的话题"
                            filterable
                            clearable
                            style="width: 280px"
                            @change="handleAddByTopic"
                        >
                            <el-option
                                v-for="topic in topicOptions"
                                :key="topic.id"
                                :label="topic.name"
                                :value="topic.id"
                            />
                        </el-select>
                    </div>
                </div>
            </el-card>

            <!-- 自动获取提示 -->
            <el-card v-if="contentData.source == 2" shadow="never" class="!border-none flex mt-2">
                <el-alert
                    title="自动获取热门话题"
                    type="info"
                    :closable="false"
                    description="系统将自动获取最热门的话题，按参与人数排序。无需手动选择。"
                />
            </el-card>
        </el-form>
    </div>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'
import { Delete } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import draggable from 'vuedraggable'
import { getDecorateTopicList } from '@/api/decoration'
import type options from './options'
import type { HotTopicItem } from './options'

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

const topicOptions = ref<{ id: number; name: string }[]>([])
const addTopicId = ref<number | ''>('')

onMounted(() => {
    ensureDataArray()
    loadTopicOptions()
})

// 加载话题列表
async function loadTopicOptions() {
    try {
        const res = await getDecorateTopicList()
        topicOptions.value = Array.isArray(res) ? res : (res?.lists ?? res?.data ?? [])
    } catch (e) {
        console.error('加载话题列表失败', e)
        topicOptions.value = []
    }
}

// 获取话题名称
function getTopicName(topicId: number): string {
    const topic = topicOptions.value.find((t) => t.id === topicId)
    return topic?.name || ''
}

// 更换话题
function handleReplace(index: number, topicId: number | '') {
    if (!topicId) return
    const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
    const old = list[index] as HotTopicItem
    list[index] = {
        topic_id: topicId,
        is_show: old?.is_show ?? '1',
        sort: old?.sort ?? index
    }
    contentData.value = { ...contentData.value, data: list }
}

// 添加话题
function handleAddByTopic(topicId: number | '') {
    if (!topicId) return
    addTopicId.value = ''
    const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
    list.push({
        topic_id: topicId,
        is_show: '1',
        sort: list.length
    })
    contentData.value = { ...contentData.value, data: list }
    ElMessage.success('添加成功')
}

// 删除话题
function handleDelete(index: number) {
    const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
    list.splice(index, 1)
    contentData.value = { ...contentData.value, data: list }
}
</script>

<style lang="scss" scoped>
.topic-item {
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
