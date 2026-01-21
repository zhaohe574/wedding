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

            <!-- 套餐列表 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">套餐列表</div>
                    <div class="text-xs text-tx-secondary ml-2">建议图片尺寸：750px*400px</div>
                </div>
                <div class="package-list">
                    <draggable
                        v-model="contentData.data"
                        item-key="index"
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
                                    
                                    <!-- 封面图 -->
                                    <div class="mr-4">
                                        <material-picker
                                            v-model="element.image"
                                            :limit="1"
                                            upload-class="bg-body"
                                        >
                                            <div 
                                                class="w-[120px] h-[80px] rounded overflow-hidden bg-gray-200 flex items-center justify-center cursor-pointer hover:opacity-80 transition"
                                            >
                                                <el-image
                                                    v-if="element.image"
                                                    :src="element.image"
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
                                            <el-form-item label="套餐名称" label-width="70px" class="!mb-2">
                                                <el-input v-model="element.name" placeholder="请输入套餐名称" />
                                            </el-form-item>
                                            <el-form-item label="标签" label-width="70px" class="!mb-2">
                                                <el-select v-model="element.tag" placeholder="选择标签" clearable>
                                                    <el-option value="热门" label="热门" />
                                                    <el-option value="推荐" label="推荐" />
                                                    <el-option value="特惠" label="特惠" />
                                                    <el-option value="新品" label="新品" />
                                                    <el-option value="限时" label="限时" />
                                                </el-select>
                                            </el-form-item>
                                            <el-form-item label="套餐价格" label-width="70px" class="!mb-2">
                                                <el-input v-model="element.price" placeholder="请输入价格">
                                                    <template #prepend>¥</template>
                                                </el-input>
                                            </el-form-item>
                                            <el-form-item label="原价" label-width="70px" class="!mb-2">
                                                <el-input v-model="element.original_price" placeholder="可选">
                                                    <template #prepend>¥</template>
                                                </el-input>
                                            </el-form-item>
                                        </div>
                                        <el-form-item label="套餐描述" label-width="70px" class="!mb-2">
                                            <el-input 
                                                v-model="element.desc" 
                                                type="textarea" 
                                                :rows="2"
                                                placeholder="请输入套餐描述"
                                            />
                                        </el-form-item>
                                        <el-form-item label="包含服务" label-width="70px" class="!mb-2">
                                            <el-select
                                                v-model="element.services"
                                                multiple
                                                filterable
                                                allow-create
                                                default-first-option
                                                placeholder="输入或选择服务项"
                                                style="width: 100%"
                                            >
                                                <el-option value="摄影师" label="摄影师" />
                                                <el-option value="化妆师" label="化妆师" />
                                                <el-option value="婚纱礼服" label="婚纱礼服" />
                                                <el-option value="婚礼策划" label="婚礼策划" />
                                                <el-option value="婚车装饰" label="婚车装饰" />
                                                <el-option value="场地布置" label="场地布置" />
                                                <el-option value="司仪主持" label="司仪主持" />
                                                <el-option value="跟拍摄像" label="跟拍摄像" />
                                                <el-option value="精修照片" label="精修照片" />
                                                <el-option value="相册制作" label="相册制作" />
                                            </el-select>
                                        </el-form-item>
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
                        添加套餐
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

// 添加套餐
const handleAdd = () => {
    contentData.value.data.push({
        is_show: '1',
        image: '',
        name: '套餐名称',
        desc: '',
        price: '9999',
        original_price: '',
        tag: '',
        services: [],
        link: {}
    })
}

// 删除套餐
const handleDelete = (index: number) => {
    contentData.value.data.splice(index, 1)
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
