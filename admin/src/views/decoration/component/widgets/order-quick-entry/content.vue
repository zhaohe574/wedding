<template>
    <view-container>
        <!-- 标题栏 -->
        <div class="flex items-center justify-between mb-3 px-1" v-if="content.title">
            <span class="text-base font-semibold text-gray-800">{{ content.title }}</span>
            <div class="flex items-center text-gray-500 cursor-pointer">
                <span class="text-sm">全部订单</span>
                <span class="ml-1">›</span>
            </div>
        </div>

        <!-- 订单状态入口卡片 -->
        <div 
            class="bg-white rounded-lg p-4"
            :style="{ 
                boxShadow: content.show_shadow !== false ? '0 1px 6px rgba(0, 0, 0, 0.08)' : 'none'
            }"
        >
            <div 
                class="grid gap-3"
                :style="{ 
                    gridTemplateColumns: `repeat(${columns}, 1fr)` 
                }"
            >
                <div 
                    v-for="(item, index) in showList"
                    :key="index"
                    class="flex flex-col items-center cursor-pointer hover:opacity-80 transition-opacity"
                >
                    <!-- 图标容器 -->
                    <div class="relative mb-2">
                        <!-- 自定义图标 -->
                        <div 
                            v-if="item.icon"
                            class="w-9 h-9 rounded-full flex items-center justify-center"
                            :style="{ backgroundColor: getStatusColor(item.status) + '15' }"
                        >
                            <img 
                                :src="item.icon" 
                                class="w-5 h-5 object-contain"
                                alt=""
                            />
                        </div>
                        <!-- 默认图标 -->
                        <div 
                            v-else
                            class="w-9 h-9 rounded-full flex items-center justify-center"
                            :style="{ backgroundColor: getStatusColor(item.status) + '15' }"
                        >
                            <span 
                                class="text-lg"
                                :style="{ color: getStatusColor(item.status) }"
                            >{{ getStatusIcon(item.status) }}</span>
                        </div>
                        
                        <!-- 数量角标 -->
                        <div 
                            v-if="item.count && item.count > 0"
                            class="absolute -top-1 -right-1 min-w-[18px] h-[18px] bg-orange-500 rounded-full 
                                   flex items-center justify-center px-1 shadow-md"
                        >
                            <span class="text-white text-[10px] font-semibold">
                                {{ item.count > 99 ? '99+' : item.count }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- 文字 -->
                    <span class="text-xs text-gray-700">{{ item.name }}</span>
                </div>
            </div>
        </div>

        <!-- 最近订单预览 -->
        <div 
            v-if="content.show_recent"
            class="mt-3 bg-white rounded-lg overflow-hidden"
            :style="{ 
                boxShadow: content.show_shadow !== false ? '0 1px 6px rgba(0, 0, 0, 0.08)' : 'none'
            }"
        >
            <!-- 标题 -->
            <div class="p-4 pb-3">
                <span class="text-sm font-semibold text-gray-800">最近订单</span>
            </div>
            
            <!-- 示例订单 -->
            <div class="px-4 py-3 border-t border-gray-100">
                <div class="flex items-start justify-between">
                    <div class="flex-1 mr-3">
                        <!-- 服务名称和状态 -->
                        <div class="flex items-center mb-1.5">
                            <span class="text-sm text-gray-800 font-medium flex-1">
                                示例服务订单
                            </span>
                            <div
                                class="ml-2 px-2 py-0.5 rounded text-xs font-medium"
                                :style="{
                                    backgroundColor: getStatusColor(3) + '15',
                                    color: getStatusColor(3)
                                }"
                            >
                                服务中
                            </div>
                        </div>
                        
                        <!-- 订单号 -->
                        <span class="text-xs text-gray-400">202401010001</span>
                    </div>
                    
                    <!-- 价格 -->
                    <div class="flex items-baseline text-orange-500">
                        <span class="text-xs font-semibold">{{ content.currency || '¥' }}</span>
                        <span class="text-lg font-bold ml-0.5">99.00</span>
                    </div>
                </div>
            </div>
            
            <div class="px-4 py-2 text-center text-xs text-gray-400 border-t border-gray-100">
                实际数据将从接口获取
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

// 每行显示的列数
const columns = computed(() => {
    return props.content.columns || 5 // 默认5列
})

// 获取状态颜色（支持数字状态码）
const getStatusColor = (status: string | number) => {
    const statusNum = typeof status === 'string' ? parseInt(status) : status
    
    const colors: Record<number, string> = {
        0: '#3B82F6',    // 待确认 - 蓝色
        1: '#F97316',    // 待付款 - 橙色
        2: '#3B82F6',    // 已支付 - 蓝色
        3: '#8B5CF6',    // 服务中 - 紫色
        4: '#22C55E',    // 已完成 - 绿色
        5: '#22C55E',    // 已评价 - 绿色
        6: '#94A3B8',    // 已取消 - 灰色
        7: '#EF4444',    // 已暂停 - 红色
        8: '#EF4444'     // 已退款 - 红色
    }
    return colors[statusNum] || '#3B82F6'
}

// 获取状态图标（支持数字状态码）
const getStatusIcon = (status: string | number) => {
    const statusNum = typeof status === 'string' ? parseInt(status) : status
    
    const icons: Record<number, string> = {
        0: '⏰',    // 待确认
        1: '💰',    // 待付款
        2: '✓',     // 已支付
        3: '⚙',     // 服务中
        4: '✓',     // 已完成
        5: '⭐',    // 已评价
        6: '✕',     // 已取消
        7: '⏸',     // 已暂停
        8: '↩'      // 已退款
    }
    return icons[statusNum] || '📋'
}
</script>
