<template>
    <div>
        <el-form label-width="80px">
            <!-- 基础设置 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">基础设置</div>
                </div>
                <el-form-item label="显示数量">
                    <el-input-number
                        v-model="contentData.show_count"
                        :min="1"
                        :max="5"
                        style="width: 300px"
                    />
                </el-form-item>
                <el-form-item label="滚动速度">
                    <el-slider
                        v-model="contentData.scroll_speed"
                        :min="10"
                        :max="100"
                        :step="10"
                        show-input
                        style="width: 300px"
                    />
                    <div class="text-xs text-gray-500 mt-1">单位：像素/秒</div>
                </el-form-item>
            </el-card>

            <!-- 展示样式 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">展示样式</div>
                </div>
                <el-radio-group v-model="contentData.style">
                    <el-radio :value="1">横向滚动</el-radio>
                    <el-radio :value="2">纵向滚动</el-radio>
                    <el-radio :value="3">静态展示</el-radio>
                </el-radio-group>
                <el-form-item label="背景颜色" class="mt-4">
                    <el-color-picker v-model="contentData.bg_color" show-alpha />
                </el-form-item>
                <el-form-item label="文字颜色">
                    <el-color-picker v-model="contentData.text_color" />
                </el-form-item>
            </el-card>

            <!-- 公告列表 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">公告列表</div>
                    <div class="text-xs text-tx-secondary ml-2">从已存在的公告中选择</div>
                </div>
                <div class="notice-list">
                    <draggable
                        :model-value="Array.isArray(contentData.data) ? contentData.data : []"
                        @update:model-value="(val: any[]) => emits('update:content', { ...props.content, data: val })"
                        :item-key="(el: any, i: number) => (el && el.notice_id) ? el.notice_id : 'i' + i"
                        animation="300"
                        handle=".drag-handle"
                    >
                        <template #item="{ element, index }">
                            <div class="notice-item mb-4 p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-start">
                                    <!-- 拖拽手柄 -->
                                    <div class="drag-handle cursor-move mr-3 mt-2">
                                        <icon name="el-icon-Rank" :size="18" color="#999" />
                                    </div>

                                    <!-- 公告信息（只读）与更换 -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap mb-2">
                                            <span class="font-medium text-gray-900">
                                                {{ getNoticeName(element.notice_id) || '未选择' }}
                                            </span>
                                        </div>
                                        <el-form-item label="选择公告" class="!mb-0" label-width="80px">
                                            <el-select
                                                :model-value="element.notice_id || ''"
                                                placeholder="选择公告"
                                                filterable
                                                clearable
                                                style="width: 280px"
                                                @change="(id: number) => handleReplace(index, id)"
                                            >
                                                <el-option
                                                    v-for="notice in noticeOptions"
                                                    :key="notice.id"
                                                    :label="notice.title"
                                                    :value="notice.id"
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
                        <span class="text-sm text-gray-500 mr-2">添加公告：</span>
                        <el-select
                            v-model="addNoticeId"
                            placeholder="请选择要展示的公告"
                            filterable
                            clearable
                            style="width: 280px"
                            @change="handleAddByNotice"
                        >
                            <el-option
                                v-for="notice in noticeOptions"
                                :key="notice.id"
                                :label="notice.title"
                                :value="notice.id"
                            />
                        </el-select>
                    </div>
                </div>
            </el-card>
        </el-form>
    </div>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'
import { Delete } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import draggable from 'vuedraggable'
import { getDecorateNoticeList } from '@/api/decoration'
import type options from './options'
import type { NoticeBarItem } from './options'

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

const noticeOptions = ref<{ id: number; title: string }[]>([])
const addNoticeId = ref<number | ''>('')

onMounted(() => {
    ensureDataArray()
    loadNoticeOptions()
})

// 加载公告列表
async function loadNoticeOptions() {
    try {
        const res = await getDecorateNoticeList()
        noticeOptions.value = Array.isArray(res) ? res : (res?.lists ?? res?.data ?? [])
    } catch (e) {
        console.error('加载公告列表失败', e)
        noticeOptions.value = []
    }
}

// 获取公告名称
function getNoticeName(noticeId: number): string {
    const notice = noticeOptions.value.find((n) => n.id === noticeId)
    return notice?.title || ''
}

// 更换公告
function handleReplace(index: number, noticeId: number | '') {
    if (!noticeId) return
    const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
    const old = list[index] as NoticeBarItem
    list[index] = {
        notice_id: noticeId,
        is_show: old?.is_show ?? '1',
        sort: old?.sort ?? index
    }
    contentData.value = { ...contentData.value, data: list }
}

// 添加公告
function handleAddByNotice(noticeId: number | '') {
    if (!noticeId) return
    addNoticeId.value = ''
    const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
    list.push({
        notice_id: noticeId,
        is_show: '1',
        sort: list.length
    })
    contentData.value = { ...contentData.value, data: list }
    ElMessage.success('添加成功')
}

// 删除公告
function handleDelete(index: number) {
    const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
    list.splice(index, 1)
    contentData.value = { ...contentData.value, data: list }
}
</script>

<style lang="scss" scoped>
.notice-item {
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
