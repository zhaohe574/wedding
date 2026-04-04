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
                    <el-radio :value="1">地图+列表</el-radio>
                    <el-radio :value="2">纯地图</el-radio>
                    <el-radio :value="3">纯列表</el-radio>
                </el-radio-group>
                <el-form-item label="自动排序" class="mt-4">
                    <el-switch
                        v-model="contentData.auto_sort"
                        :active-value="1"
                        :inactive-value="0"
                        active-text="根据距离自动排序"
                    />
                </el-form-item>
            </el-card>

            <!-- 门店列表设置 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">门店列表设置</div>
                    <div class="text-xs text-tx-secondary ml-2">最多添加20个门店</div>
                </div>
                <div class="flex-1 mt-4">
                    <draggable
                        :model-value="Array.isArray(contentData.data) ? contentData.data : []"
                        @update:model-value="(val: any[]) => emits('update:content', { ...props.content, data: val })"
                        :item-key="(el: any, i: number) => (el && el.name) ? el.name + i : 'i' + i"
                        animation="300"
                        handle=".drag-handle"
                    >
                        <template #item="{ element, index }">
                            <div class="store-item mb-4 p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-start">
                                    <!-- 拖拽手柄 -->
                                    <div class="drag-handle cursor-move mr-3 mt-2">
                                        <icon name="el-icon-Rank" :size="18" color="#999" />
                                    </div>

                                    <!-- 表单 -->
                                    <div class="flex-1 min-w-0">
                                        <el-form-item label="门店名称" label-width="70px" class="!mb-2">
                                            <el-input
                                                v-model="element.name"
                                                placeholder="请输入门店名称"
                                                style="width: 250px"
                                            />
                                        </el-form-item>
                                        <el-form-item label="门店地址" label-width="70px" class="!mb-2">
                                            <el-input
                                                v-model="element.address"
                                                placeholder="请输入门店地址"
                                                style="width: 100%"
                                            />
                                        </el-form-item>
                                        <div class="flex gap-4">
                                            <el-form-item label="联系电话" label-width="70px" class="!mb-2 flex-1">
                                                <el-input
                                                    v-model="element.phone"
                                                    placeholder="请输入联系电话"
                                                />
                                            </el-form-item>
                                            <el-form-item label="营业时间" label-width="70px" class="!mb-2 flex-1">
                                                <el-input
                                                    v-model="element.business_hours"
                                                    placeholder="如：09:00-18:00"
                                                />
                                            </el-form-item>
                                        </div>
                                        <div class="flex gap-4">
                                            <el-form-item label="纬度" label-width="70px" class="!mb-0 flex-1">
                                                <el-input-number
                                                    v-model="element.latitude"
                                                    :precision="6"
                                                    :step="0.000001"
                                                    placeholder="纬度"
                                                    style="width: 100%"
                                                />
                                            </el-form-item>
                                            <el-form-item label="经度" label-width="70px" class="!mb-0 flex-1">
                                                <el-input-number
                                                    v-model="element.longitude"
                                                    :precision="6"
                                                    :step="0.000001"
                                                    placeholder="经度"
                                                    style="width: 100%"
                                                />
                                            </el-form-item>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            提示：可通过
                                            <a
                                                href="https://lbs.qq.com/getPoint/"
                                                target="_blank"
                                                class="text-primary hover:underline"
                                            >
                                                腾讯地图坐标拾取器
                                            </a>
                                            获取经纬度
                                        </div>
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
                        :disabled="(contentData.data?.length || 0) >= 20"
                    >
                        添加门店
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
import type { StoreMapItem } from './options'

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

// 添加门店
function handleAdd() {
    const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
    if (list.length >= 20) return
    
    const newItem: StoreMapItem = {
        name: '新门店',
        address: '',
        phone: '',
        business_hours: '09:00-18:00',
        latitude: 39.9042,
        longitude: 116.4074,
        is_show: '1',
        sort: list.length + 1
    }
    list.push(newItem)
    contentData.value = { ...contentData.value, data: list }
}

// 删除门店
function handleDelete(index: number) {
    const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
    list.splice(index, 1)
    contentData.value = { ...contentData.value, data: list }
}
</script>

<style lang="scss" scoped>
.store-item {
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
