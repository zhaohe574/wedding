<template>
    <view-container>
        <!-- 标题栏 -->
        <div class="flex items-center justify-between mb-2" v-if="content.title">
            <span class="font-medium text-gray-800">{{ content.title }}</span>
            <span class="text-sm text-gray-500">全部订单 ></span>
        </div>

        <!-- 订单状态入口 -->
        <div 
            class="bg-white rounded-lg p-4"
            :style="{ 
                boxShadow: content.show_shadow !== false ? '0 1px 3px rgba(0, 0, 0, 0.1)' : 'none'
            }"
        >
            <div class="flex items-center justify-around">
                <div 
                    v-for="(item, index) in showList"
                    :key="index"
                    class="flex flex-col items-center"
                >
                    <!-- 图标 -->
                    <div class="relative mb-2">
                        <div 
                            class="w-8 h-8 rounded-full flex items-center justify-center"
                            :style="{ backgroundColor: getStatusColor(item.status) + '20' }"
                        >
                            <span 
                                class="text-xs font-medium"
                                :style="{ color: getStatusColor(item.status) }"
                            >{{ getStatusIcon(item.status) }}</span>
                        </div>
                        <!-- 角标预览 -->
                        <div 
                            v-if="item.count && item.count > 0"
                            class="absolute -top-1 -right-1 min-w-4 h-4 bg-orange-500 rounded-full 
                                   flex items-center justify-center px-1"
                        >
                            <span class="text-white text-[10px]">{{ item.count }}</span>
                        </div>
                    </div>
                    <span class="text-xs text-gray-600">{{ item.name }}</span>
                </div>
            </div>
        </div>

        <!-- 最近订单预览 -->
        <div 
            v-if="content.show_recent"
            class="mt-3 bg-white rounded-lg overflow-hidden"
            :style="{ 
                boxShadow: content.show_shadow !== false ? '0 1px 3px rgba(0, 0, 0, 0.1)' : 'none'
            }"
        >
            <div class="p-3 border-b border-gray-100">
                <span class="text-sm font-medium text-gray-800">最近订单</span>
            </div>
            <div class="p-3 text-center text-xs text-gray-400">
                订单数据将从接口获取
            </div>
        </div>
    </view-container>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    content: any
    styles?: any
}>()

// 过滤显示的入口列表
const showList = computed(() => {
    return props.content.data?.filter((item: any) => item.is_show !== '0') || []
})

// 获取状态颜色
const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        'pending_pay': '#F97316',
        'pending_confirm': '#3B82F6',
        'processing': '#8B5CF6',
        'completed': '#22C55E',
        'cancelled': '#94A3B8',
        'refund': '#EF4444'
    }
    return colors[status] || '#7C3AED'
}

// 获取状态图标
const getStatusIcon = (status: string) => {
    const icons: Record<string, string> = {
        'pending_pay': '付',
        'pending_confirm': '待',
        'processing': '进',
        'completed': '完',
        'cancelled': '消',
        'refund': '退'
    }
    return icons[status] || '单'
}
</script>
