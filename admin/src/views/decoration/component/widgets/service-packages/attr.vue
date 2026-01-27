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
                    <el-radio :value="1">横向滑动</el-radio>
                    <el-radio :value="2">纵向列表</el-radio>
                    <el-radio :value="3">大卡片</el-radio>
                </el-radio-group>
            </el-card>

            <!-- 套餐列表：从「服务套餐」中选择，选择后自动带出套餐信息 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">套餐列表</div>
                    <div class="text-xs text-tx-secondary ml-2">从已存在的公共套餐中选择，无需手动录入</div>
                </div>
                <div class="package-list">
                    <draggable
                        :model-value="Array.isArray(contentData.data) ? contentData.data : []"
                        @update:model-value="(val: any[]) => emits('update:content', { ...props.content, data: val })"
                        :item-key="(el: any, i: number) => (el && el.package_id) ? el.package_id : 'i'+i"
                        animation="300"
                        handle=".drag-handle"
                    >
                        <template #item="{ element, index }">
                            <div class="package-item mb-4 p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-start">
                                    <!-- 拖拽手柄 -->
                                    <div class="drag-handle cursor-move mr-3 mt-2">
                                        <icon name="el-icon-Rank" :size="18" color="#999" />
                                    </div>
                                    
                                    <!-- 封面图展示 -->
                                    <div class="mr-4 flex-shrink-0">
                                        <div 
                                            class="w-[120px] h-[80px] rounded overflow-hidden bg-gray-200 flex items-center justify-center"
                                        >
                                            <el-image
                                                v-if="element.image"
                                                :src="element.image"
                                                fit="cover"
                                                class="w-full h-full"
                                            />
                                            <icon v-else name="el-icon-Picture" :size="24" color="#999" />
                                        </div>
                                    </div>

                                    <!-- 套餐信息（只读）与更换 -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap mb-2">
                                            <span class="font-medium text-gray-900">{{ element.name || '未选择' }}</span>
                                            <span v-if="element.tag" class="px-2 py-0.5 bg-primary/10 rounded text-xs text-primary">
                                                {{ element.tag }}
                                            </span>
                                        </div>
                                        <div v-if="element.name" class="text-xs text-gray-500 mb-2">
                                            <span class="text-primary font-medium">¥{{ element.price }}</span>
                                            <span v-if="element.original_price" class="line-through ml-2">¥{{ element.original_price }}</span>
                                            <span v-if="element.services?.length" class="ml-2">
                                                · 包含{{ element.services.length }}项服务
                                            </span>
                                        </div>
                                        <div v-if="element.desc" class="text-xs text-gray-400 mb-2 line-clamp-2">
                                            {{ element.desc }}
                                        </div>
                                        <el-form-item label="更换套餐" class="!mb-0" label-width="80px">
                                            <el-select
                                                :model-value="element.package_id || ''"
                                                placeholder="选择公共套餐"
                                                filterable
                                                clearable
                                                style="width: 220px"
                                                @change="(id: number) => handleReplace(index, id)"
                                            >
                                                <el-option
                                                    v-for="pkg in packageOptions"
                                                    :key="pkg.id"
                                                    :label="pkg.name"
                                                    :value="pkg.id"
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

                    <!-- 添加：选择公共套餐后自动加入列表 -->
                    <div class="mt-2">
                        <span class="text-sm text-gray-500 mr-2">添加套餐：</span>
                        <el-select
                            v-model="addPackageId"
                            placeholder="请选择要展示的公共套餐"
                            filterable
                            clearable
                            style="width: 280px"
                            @change="handleAddByPackage"
                        >
                            <el-option
                                v-for="pkg in packageOptions"
                                :key="pkg.id"
                                :label="pkg.name"
                                :value="pkg.id"
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
import { packageAll, packageDetail } from '@/api/service'
import type options from './options'
import type { ServicePackageItem } from './options'

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

const packageOptions = ref<{ id: number; name: string }[]>([])
const addPackageId = ref<number | ''>('')
const loading = ref(false)

onMounted(() => {
    ensureDataArray()
    loadPackageOptions()
})

// 加载公共套餐列表
async function loadPackageOptions() {
    try {
        const res = await packageAll({})
        packageOptions.value = Array.isArray(res) ? res : (res?.lists ?? res?.data ?? [])
    } catch (e) {
        console.error('加载公共套餐列表失败', e)
        packageOptions.value = []
    }
}

// 从套餐详情构建列表项
function buildItemFromDetail(d: any, keepIsShow?: string): ServicePackageItem {
    // 只保存引用ID和控制信息，不保存业务数据快照
    return {
        package_id: d.id,
        is_show: keepIsShow ?? '1',
        sort: 0
    }
}

// 更换套餐
async function handleReplace(index: number, packageId: number | '') {
    if (!packageId) return
    loading.value = true
    try {
        const d = await packageDetail({ id: packageId })
        if (!d || !d.id) {
            ElMessage.error('获取套餐详情失败')
            return
        }
        const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
        const old = list[index] as ServicePackageItem
        list[index] = { ...buildItemFromDetail(d, old?.is_show ?? '1') }
        contentData.value = { ...contentData.value, data: list }
    } catch (e: any) {
        console.error('获取套餐详情失败', e)
        ElMessage.error(e?.message || '获取套餐详情失败，请检查后端接口')
    } finally {
        loading.value = false
    }
}

// 添加套餐
async function handleAddByPackage(packageId: number | '') {
    if (!packageId) return
    loading.value = true
    addPackageId.value = ''
    try {
        const d = await packageDetail({ id: packageId })
        if (!d || !d.id) {
            ElMessage.error('获取套餐详情失败')
            return
        }
        const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
        list.push(buildItemFromDetail(d))
        contentData.value = { ...contentData.value, data: list }
        ElMessage.success('添加成功')
    } catch (e: any) {
        console.error('获取套餐详情失败', e)
        ElMessage.error(e?.message || '获取套餐详情失败，请检查后端接口')
    } finally {
        loading.value = false
    }
}

// 删除套餐
function handleDelete(index: number) {
    const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
    list.splice(index, 1)
    contentData.value = { ...contentData.value, data: list }
}
</script>

<style lang="scss" scoped>
.package-item {
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
