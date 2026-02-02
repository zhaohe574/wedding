<template>
    <div>
        <!-- 基础设置 -->
        <el-form label-width="80px">
            <el-form-item label="组件标题">
                <el-input v-model="contentData.title" placeholder="请输入标题" />
            </el-form-item>

            <el-form-item label="显示阴影">
                <el-switch v-model="contentData.show_shadow" />
            </el-form-item>

            <el-form-item label="每行个数">
                <el-select v-model="contentData.columns" placeholder="请选择">
                    <el-option label="3个" :value="3" />
                    <el-option label="4个" :value="4" />
                    <el-option label="5个" :value="5" />
                </el-select>
                <div class="text-xs text-gray-400 mt-1">每行最多显示的入口个数</div>
            </el-form-item>

            <el-form-item label="最近订单">
                <el-switch v-model="contentData.show_recent" />
                <div class="text-xs text-gray-400 mt-1">开启后显示最近订单列表</div>
            </el-form-item>

            <el-form-item label="货币符号">
                <el-input v-model="contentData.currency" placeholder="¥" style="width: 80px" />
            </el-form-item>
        </el-form>

        <!-- 订单入口配置 -->
        <div class="mt-4">
            <div class="flex items-center justify-between mb-3">
                <span class="font-medium">订单状态入口</span>
                <el-button type="primary" link @click="handleAdd">
                    <el-icon><Plus /></el-icon>
                    添加入口
                </el-button>
            </div>

            <draggable 
                v-model="contentData.data" 
                item-key="status"
                handle=".drag-handle"
                class="space-y-2"
            >
                <template #item="{ element, index }">
                    <div class="border border-gray-200 rounded-lg p-3 bg-white">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                                <el-icon class="drag-handle cursor-move mr-2 text-gray-400">
                                    <Rank />
                                </el-icon>
                                <el-switch 
                                    v-model="element.is_show"
                                    active-value="1"
                                    inactive-value="0"
                                    size="small"
                                />
                                <span class="ml-2 text-sm">{{ element.name }}</span>
                            </div>
                            <el-button 
                                type="danger" 
                                link 
                                size="small"
                                @click="handleDelete(index)"
                            >
                                删除
                            </el-button>
                        </div>

                        <el-form label-width="70px" size="small">
                            <el-form-item label="名称">
                                <el-input v-model="element.name" placeholder="请输入名称" />
                            </el-form-item>

                            <el-form-item label="状态标识">
                                <el-select v-model="element.status" placeholder="请选择状态">
                                    <el-option label="待确认" :value="0" />
                                    <el-option label="待付款" :value="1" />
                                    <el-option label="已支付" :value="2" />
                                    <el-option label="服务中" :value="3" />
                                    <el-option label="已完成" :value="4" />
                                    <el-option label="已评价" :value="5" />
                                    <el-option label="已取消" :value="6" />
                                    <el-option label="已暂停" :value="7" />
                                    <el-option label="已退款" :value="8" />
                                </el-select>
                            </el-form-item>

                            <el-form-item label="自定义图标">
                                <material-picker 
                                    v-model="element.icon"
                                    :limit="1"
                                    upload-class="bg-body"
                                />
                            </el-form-item>

                            <el-form-item label="跳转链接">
                                <link-picker v-model="element.link" />
                            </el-form-item>
                        </el-form>
                    </div>
                </template>
            </draggable>

            <div 
                v-if="!contentData.data?.length"
                class="text-center py-8 text-gray-400 border border-dashed border-gray-200 rounded-lg"
            >
                暂无入口，点击上方按钮添加
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Plus, Rank } from '@element-plus/icons-vue'
import draggable from 'vuedraggable'

const props = defineProps<{
    content: any
}>()

const emit = defineEmits(['update:content'])

const contentData = computed({
    get: () => props.content,
    set: (val) => emit('update:content', val)
})

// 添加入口
const handleAdd = () => {
    if (!contentData.value.data) {
        contentData.value.data = []
    }
    contentData.value.data.push({
        is_show: '1',
        icon: '',
        name: '新入口',
        status: 1, // 默认：待付款
        count: 0,
        link: {
            path: '/pages/order/order',
            name: '我的订单',
            type: 'shop',
            query: { status: 1 }
        }
    })
}

// 删除入口
const handleDelete = (index: number) => {
    contentData.value.data.splice(index, 1)
}
</script>
