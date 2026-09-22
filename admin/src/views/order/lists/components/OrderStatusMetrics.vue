<template>
    <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
        <el-card
            v-for="item in metricCards"
            :key="item.status"
            class="!border-none cursor-pointer transition-transform hover:-translate-y-0.5"
            :class="{ 'ring-2 ring-primary ring-offset-1': activeStatus === item.status }"
            shadow="never"
            @click="$emit('select-status', item.status)"
        >
            <div class="text-center">
                <div class="text-gray-500 text-sm">{{ item.label }}</div>
                <div class="text-2xl font-bold mt-2" :class="item.colorClass">
                    {{ getCount(item.status) }}
                </div>
            </div>
        </el-card>
    </div>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { OrderStatus } from '@/enums/orderEnums'

const props = defineProps<{
    statistics: Record<string, any>
    activeStatus?: string | number
}>()

defineEmits<{
    (e: 'select-status', status: number): void
}>()

const metricCards = [
    { status: OrderStatus.PENDING_CONFIRM, label: '待确认', colorClass: 'text-yellow-500' },
    { status: OrderStatus.PENDING_PAY, label: '待支付', colorClass: 'text-orange-500' },
    { status: OrderStatus.PENDING_SERVICE, label: '待服务', colorClass: 'text-blue-500' },
    { status: OrderStatus.IN_SERVICE, label: '服务中', colorClass: 'text-purple-500' },
    { status: OrderStatus.COMPLETED, label: '已完成', colorClass: 'text-green-500' },
    { status: OrderStatus.REVIEWED, label: '已评价', colorClass: 'text-emerald-500' },
    { status: OrderStatus.CANCELLED, label: '已取消', colorClass: 'text-gray-500' },
    { status: OrderStatus.PAUSED, label: '已暂停', colorClass: 'text-amber-500' },
    { status: OrderStatus.REFUNDING, label: '退款中', colorClass: 'text-cyan-500' },
    { status: OrderStatus.REFUNDED, label: '已退款', colorClass: 'text-red-500' },
    { status: OrderStatus.USER_DELETED, label: '用户已删除', colorClass: 'text-rose-500' }
]

const getCount = (status: number) => {
    if (!props.statistics?.status_counts) return 0
    const item = props.statistics.status_counts.find((s: any) => s.status === status)
    return item ? item.count : 0
}
</script>
