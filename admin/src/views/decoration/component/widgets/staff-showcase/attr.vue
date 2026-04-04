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
                    <el-radio :value="1">横向滑动卡片</el-radio>
                    <el-radio :value="2">列表样式</el-radio>
                </el-radio-group>
                <div v-if="contentData.style == 1" class="mt-2 text-xs text-gray-500">
                    卡片将以横向滑动方式展示，用户可左右滑动查看更多人员
                </div>
            </el-card>

            <!-- 人员列表：从「服务人员」中选择，选择后自动带出姓名、角色、评分等 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">人员列表</div>
                    <div class="text-xs text-tx-secondary ml-2">从已存在的服务人员中选择，无需手动录入</div>
                </div>
                <div class="staff-list">
                    <draggable
                        :model-value="Array.isArray(contentData.data) ? contentData.data : []"
                        @update:model-value="(val: any[]) => emits('update:content', { ...props.content, data: val })"
                        :item-key="(el: any, i: number) => (el && el.staff_id) ? el.staff_id : 'i'+i"
                        animation="300"
                        handle=".drag-handle"
                    >
                        <template #item="{ element, index }">
                            <div class="staff-item mb-4 p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-start">
                                    <div class="drag-handle cursor-move mr-3 mt-2">
                                        <icon name="el-icon-Rank" :size="18" color="#999" />
                                    </div>
                                    <!-- 头像展示 -->
                                    <div class="mr-4 flex-shrink-0">
                                        <div class="w-[80px] h-[80px] rounded-full overflow-hidden bg-gray-200 flex items-center justify-center">
                                            <el-image
                                                v-if="element.avatar"
                                                :src="element.avatar"
                                                fit="cover"
                                                class="w-full h-full"
                                            />
                                            <icon v-else name="el-icon-User" :size="28" color="#999" />
                                        </div>
                                    </div>
                                    <!-- 人员信息（只读）与更换 -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap mb-2">
                                            <span class="font-medium text-gray-900">{{ element.name || '未选择' }}</span>
                                            <span v-if="element.role" class="px-2 py-0.5 bg-primary/10 rounded text-xs text-primary">{{ element.role }}</span>
                                        </div>
                                        <div v-if="element.name" class="text-xs text-gray-500 mb-2">
                                            ★ {{ element.rating || '5.0' }} · 已服务{{ element.order_count ?? 0 }}单
                                            <span v-if="element.tags?.length"> · {{ element.tags.slice(0, 3).join('、') }}</span>
                                        </div>
                                        <el-form-item label="更换人员" class="!mb-0" label-width="80px">
                                            <el-select
                                                :model-value="element.staff_id || ''"
                                                placeholder="选择服务人员"
                                                filterable
                                                clearable
                                                style="width: 220px"
                                                @change="(id: number) => handleReplace(index, id)"
                                            >
                                                <el-option
                                                    v-for="s in staffOptions"
                                                    :key="s.id"
                                                    :label="`${s.name}${s.sn ? `（${s.sn}）` : ''}`"
                                                    :value="s.id"
                                                />
                                            </el-select>
                                        </el-form-item>
                                    </div>
                                    <div class="ml-4 flex flex-col gap-2 flex-shrink-0">
                                        <el-switch
                                            v-model="element.is_show"
                                            active-value="1"
                                            inactive-value="0"
                                            active-text="显示"
                                        />
                                        <el-button type="danger" text :icon="Delete" @click="handleDelete(index)">
                                            删除
                                        </el-button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </draggable>

                    <!-- 添加：选择服务人员后自动加入列表 -->
                    <div class="mt-2">
                        <span class="text-sm text-gray-500 mr-2">添加人员：</span>
                        <el-select
                            v-model="addStaffId"
                            placeholder="请选择要推荐的服务人员"
                            filterable
                            clearable
                            style="width: 280px"
                            @change="handleAddByStaff"
                        >
                            <el-option
                                v-for="s in staffOptions"
                                :key="s.id"
                                :label="`${s.name}${s.sn ? `（${s.sn}）` : ''}`"
                                :value="s.id"
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
import draggable from 'vuedraggable'
import { staffAll, staffDetail } from '@/api/staff'
import type options from './options'
import type { StaffShowcaseItem } from './options'

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
    set: (newValue) => { emits('update:content', newValue) }
})

function ensureDataArray() {
    if (!Array.isArray(props.content?.data)) {
        emits('update:content', { ...props.content, data: [] })
    }
}

const staffOptions = ref<{ id: number; name: string; sn?: string }[]>([])
const addStaffId = ref<number | ''>('')
const loading = ref(false)

onMounted(() => {
    ensureDataArray()
    loadStaffOptions()
})

async function loadStaffOptions() {
    try {
        const res = await staffAll({})
        staffOptions.value = Array.isArray(res) ? res : (res?.lists ?? res?.data ?? [])
    } catch (e) {
        console.error('加载服务人员列表失败', e)
        staffOptions.value = []
    }
}

function buildItemFromDetail(d: any, keepIsShow?: string): StaffShowcaseItem {
    // 只保存引用ID和控制信息，不保存业务数据快照
    return {
        staff_id: d.id,
        is_show: keepIsShow ?? '1',
        sort: 0
    }
}

async function handleReplace(index: number, staffId: number | '') {
    if (!staffId) return
    loading.value = true
    try {
        const d = await staffDetail({ id: staffId })
        if (!d || !d.id) return
        const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
        const old = list[index] as StaffShowcaseItem
        list[index] = { ...buildItemFromDetail(d, old?.is_show ?? '1') }
        contentData.value = { ...contentData.value, data: list }
    } catch (e) {
        console.error('获取人员详情失败', e)
    } finally {
        loading.value = false
    }
}

async function handleAddByStaff(staffId: number | '') {
    if (!staffId) return
    loading.value = true
    addStaffId.value = ''
    try {
        const d = await staffDetail({ id: staffId })
        if (!d || !d.id) return
        const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
        list.push(buildItemFromDetail(d))
        contentData.value = { ...contentData.value, data: list }
    } catch (e) {
        console.error('获取人员详情失败', e)
    } finally {
        loading.value = false
    }
}

function handleDelete(index: number) {
    const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
    list.splice(index, 1)
    contentData.value = { ...contentData.value, data: list }
}
</script>

<style lang="scss" scoped>
.staff-item {
    border: 1px solid #e5e7eb;
    transition: all 0.2s ease;
    &:hover { border-color: var(--el-color-primary-light-5); }
}
.drag-handle:hover { color: var(--el-color-primary); }
</style>
