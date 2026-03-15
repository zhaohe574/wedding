<template>
    <div class="workbench">
        <!-- 今日核心数据 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <el-card
                v-for="item in statCards"
                :key="item.key"
                class="!border-none stat-card cursor-pointer"
                shadow="never"
                @click="item.onClick?.()"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-tx-secondary text-sm mb-2">{{ item.label }}</div>
                        <div class="text-2xl font-semibold">{{ item.value }}</div>
                        <div class="text-xs mt-2 text-tx-secondary">
                            总计：{{ item.total }}
                        </div>
                    </div>
                    <div
                        class="w-12 h-12 rounded-lg flex items-center justify-center"
                        :style="{ backgroundColor: item.bgColor }"
                    >
                        <el-icon :size="24" :color="item.iconColor">
                            <component :is="item.icon" />
                        </el-icon>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-br flex items-center text-xs">
                    <span class="text-tx-secondary">较昨日</span>
                    <span
                        class="ml-2 flex items-center"
                        :class="item.compare >= 0 ? 'text-green-500' : 'text-red-500'"
                    >
                        <el-icon :size="12">
                            <component :is="item.compare >= 0 ? 'Top' : 'Bottom'" />
                        </el-icon>
                        {{ Math.abs(item.compare) }}%
                    </span>
                </div>
            </el-card>
        </div>

        <!-- 待办事项 -->
        <el-card class="!border-none mb-4" shadow="never">
            <template #header>
                <span>待办事项</span>
            </template>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div
                    v-for="item in todoItems"
                    :key="item.key"
                    class="text-center p-4 rounded-lg cursor-pointer transition-colors duration-200 hover:bg-fill-light"
                    @click="item.onClick?.()"
                >
                    <div
                        class="text-3xl font-semibold mb-1"
                        :class="item.count > 0 ? 'text-primary' : 'text-tx-secondary'"
                    >
                        {{ item.count }}
                    </div>
                    <div class="text-sm text-tx-secondary">{{ item.label }}</div>
                </div>
            </div>
        </el-card>

        <!-- 图表区域 -->
        <div class="lg:flex gap-4 mb-4">
            <!-- 营收趋势 -->
            <el-card class="!border-none mb-4 lg:mb-0 w-full lg:w-2/3" shadow="never">
                <template #header>
                    <div class="flex items-center justify-between">
                        <span>营收趋势</span>
                        <span class="text-tx-secondary text-xs">近15天</span>
                    </div>
                </template>
                <v-charts
                    style="height: 350px"
                    :option="revenueChartOption"
                    :autoresize="true"
                />
            </el-card>

            <!-- 订单状态分布 -->
            <el-card class="!border-none w-full lg:w-1/3" shadow="never">
                <template #header>
                    <span>订单状态分布</span>
                </template>
                <v-charts
                    style="height: 350px"
                    :option="orderStatusChartOption"
                    :autoresize="true"
                />
            </el-card>
        </div>

        <!-- 下半区：热门服务 + 近期订单 -->
        <div class="lg:flex gap-4">
            <!-- 热门服务 -->
            <el-card class="!border-none mb-4 lg:mb-0 w-full lg:w-1/3" shadow="never">
                <template #header>
                    <span>热门服务 TOP5</span>
                </template>
                <div v-if="workbenchData.hot_services.length === 0" class="text-center text-tx-secondary py-8">
                    暂无数据
                </div>
                <div v-else>
                    <div
                        v-for="(item, index) in workbenchData.hot_services"
                        :key="item.package_id"
                        class="flex items-center py-3"
                        :class="{ 'border-t border-br': index > 0 }"
                    >
                        <div
                            class="w-6 h-6 rounded flex items-center justify-center text-white text-xs font-semibold flex-none"
                            :style="{ backgroundColor: rankColors[index] || '#94a3b8' }"
                        >
                            {{ index + 1 }}
                        </div>
                        <div class="ml-3 flex-1 min-w-0">
                            <div class="truncate text-sm">{{ item.name }}</div>
                            <div class="text-xs text-tx-secondary mt-1">
                                {{ item.order_count }} 单 · ¥{{ item.total_amount }}
                            </div>
                        </div>
                    </div>
                </div>
            </el-card>

            <!-- 近期订单 -->
            <el-card class="!border-none w-full lg:w-2/3" shadow="never">
                <template #header>
                    <div class="flex items-center justify-between">
                        <span>近期订单</span>
                        <router-link to="/order/lists" class="text-primary text-sm cursor-pointer">
                            查看全部
                        </router-link>
                    </div>
                </template>
                <el-table :data="workbenchData.recent_orders" size="large">
                    <el-table-column label="订单号" prop="order_sn" min-width="180" />
                    <el-table-column label="客户" prop="contact_name" min-width="100" />
                    <el-table-column label="金额" min-width="100">
                        <template #default="{ row }">
                            ¥{{ row.pay_amount }}
                        </template>
                    </el-table-column>
                    <el-table-column label="服务日期" prop="service_date" min-width="110" />
                    <el-table-column label="状态" min-width="90">
                        <template #default="{ row }">
                            <el-tag
                                :type="statusTagType(row.order_status)"
                                size="small"
                            >
                                {{ row.order_status_desc }}
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="下单时间" prop="create_time" min-width="140" />
                </el-table>
            </el-card>
        </div>
    </div>
</template>

<script lang="ts" setup name="workbench">
import vCharts from 'vue-echarts'
import { getWorkbench } from '@/api/app'
import useSettingStore from '@/stores/modules/setting'
import { calcColor } from '@/utils/util'
import { useRouter } from 'vue-router'

const router = useRouter()
const settingStore = useSettingStore()

// 排名颜色
const rankColors = ['#f56c6c', '#e6a23c', '#409eff', '#94a3b8', '#94a3b8']

// 工作台数据
const workbenchData = reactive<Record<string, any>>({
    today: {
        time: '',
        revenue: 0,
        revenue_compare: 0,
        total_revenue: 0,
        order_count: 0,
        order_compare: 0,
        total_orders: 0,
        new_user: 0,
        user_compare: 0,
        total_users: 0,
    },
    todo: {
        pending_confirm: 0,
        pending_pay: 0,
        in_service: 0,
        pending_refund: 0,
        pending_staff: 0,
    },
    revenue_trend: { date: [], revenue: [], orders: [] },
    order_status: [],
    hot_services: [],
    recent_orders: [],
})

// 统计卡片配置
const statCards = computed(() => [
    {
        key: 'revenue',
        label: '今日营收',
        value: `¥${workbenchData.today.revenue}`,
        total: `¥${workbenchData.today.total_revenue}`,
        compare: workbenchData.today.revenue_compare,
        icon: 'Wallet',
        bgColor: calcColor(settingStore.theme, 0.1),
        iconColor: settingStore.theme,
        onClick: () => router.push('/financial/flow'),
    },
    {
        key: 'orders',
        label: '今日订单',
        value: workbenchData.today.order_count,
        total: workbenchData.today.total_orders,
        compare: workbenchData.today.order_compare,
        icon: 'Document',
        bgColor: calcColor('#67c23a', 0.1),
        iconColor: '#67c23a',
        onClick: () => router.push('/order/lists'),
    },
    {
        key: 'users',
        label: '今日新增用户',
        value: workbenchData.today.new_user,
        total: workbenchData.today.total_users,
        compare: workbenchData.today.user_compare,
        icon: 'User',
        bgColor: calcColor('#e6a23c', 0.1),
        iconColor: '#e6a23c',
        onClick: () => router.push('/consumer/lists'),
    },
    {
        key: 'todo',
        label: '待处理事项',
        value: todoTotal.value,
        total: '-',
        compare: 0,
        icon: 'Bell',
        bgColor: calcColor('#f56c6c', 0.1),
        iconColor: '#f56c6c',
    },
])

// 待办总数
const todoTotal = computed(() => {
    const t = workbenchData.todo
    return t.pending_confirm + t.pending_pay + t.in_service + t.pending_refund + t.pending_staff
})

// 待办事项配置
const todoItems = computed(() => [
    {
        key: 'pending_confirm',
        label: '待确认订单',
        count: workbenchData.todo.pending_confirm,
        onClick: () => router.push('/order/lists?order_status=0'),
    },
    {
        key: 'pending_pay',
        label: '待支付订单',
        count: workbenchData.todo.pending_pay,
        onClick: () => router.push('/order/lists?order_status=1'),
    },
    {
        key: 'in_service',
        label: '服务中订单',
        count: workbenchData.todo.in_service,
        onClick: () => router.push('/order/lists?order_status=3'),
    },
    {
        key: 'pending_refund',
        label: '待审核退款',
        count: workbenchData.todo.pending_refund,
        onClick: () => router.push('/order/refund'),
    },
    {
        key: 'pending_staff',
        label: '待审核员工',
        count: workbenchData.todo.pending_staff,
        onClick: () => router.push('/service/staff'),
    },
])

// 营收趋势图配置
const revenueChartOption = computed(() => ({
    tooltip: {
        trigger: 'axis',
        axisPointer: { type: 'cross' },
    },
    legend: {
        data: ['营收', '订单数'],
    },
    xAxis: {
        type: 'category',
        data: workbenchData.revenue_trend.date,
    },
    yAxis: [
        {
            type: 'value',
            name: '营收（元）',
            position: 'left',
        },
        {
            type: 'value',
            name: '订单数',
            position: 'right',
        },
    ],
    series: [
        {
            name: '营收',
            type: 'line',
            smooth: true,
            data: workbenchData.revenue_trend.revenue,
            yAxisIndex: 0,
            itemStyle: { color: settingStore.theme },
            areaStyle: {
                color: {
                    type: 'linear',
                    x: 0, y: 0, x2: 0, y2: 1,
                    colorStops: [
                        { offset: 0, color: calcColor(settingStore.theme, 0.3) },
                        { offset: 1, color: calcColor(settingStore.theme, 0.02) },
                    ],
                },
            },
        },
        {
            name: '订单数',
            type: 'bar',
            data: workbenchData.revenue_trend.orders,
            yAxisIndex: 1,
            barWidth: '40%',
            itemStyle: {
                borderRadius: [4, 4, 0, 0],
                color: calcColor('#67c23a', 0.6),
            },
        },
    ],
}))

// 订单状态分布图配置
const orderStatusChartOption = computed(() => ({
    tooltip: {
        trigger: 'item',
        formatter: '{b}: {c} 单 ({d}%)',
    },
    legend: {
        orient: 'vertical',
        right: 10,
        top: 'center',
    },
    series: [
        {
            type: 'pie',
            radius: ['40%', '70%'],
            center: ['35%', '50%'],
            avoidLabelOverlap: false,
            label: { show: false },
            emphasis: {
                label: { show: true, fontSize: 14, fontWeight: 'bold' },
            },
            data: workbenchData.order_status,
            color: ['#e6a23c', '#f56c6c', '#409eff', '#67c23a', '#909399', '#c0c4cc', '#f89898'],
        },
    ],
}))

// 订单状态标签类型
const statusTagType = (status: number) => {
    const map = {
        0: 'warning',   // 待确认
        1: 'danger',    // 待支付
        2: 'info',      // 已支付
        3: 'primary',   // 服务中
        4: 'success',   // 已完成
        5: 'success',   // 已评价
        6: 'info',      // 已取消
        7: 'warning',   // 已暂停
        8: 'info',      // 已退款
    } as const
    return map[status as keyof typeof map] ?? 'info'
}

// 获取数据
const getData = async () => {
    try {
        const res: any = await getWorkbench()
        workbenchData.today = res.today ?? workbenchData.today
        workbenchData.todo = res.todo ?? workbenchData.todo
        workbenchData.revenue_trend = res.revenue_trend ?? workbenchData.revenue_trend
        workbenchData.order_status = res.order_status ?? []
        workbenchData.hot_services = res.hot_services ?? []
        workbenchData.recent_orders = res.recent_orders ?? []
    } catch (err) {
        console.error('工作台数据加载失败', err)
    }
}

onMounted(() => {
    getData()
})
</script>

<style lang="scss" scoped>
.stat-card {
    transition: box-shadow 0.2s;
    &:hover {
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    }
}
</style>
