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
                <div class="flex items-center justify-between">
                    <span class="font-bold text-base">待办处理中心</span>
                    <span class="text-tx-secondary text-xs">共 {{ todoTotal }} 项需处理</span>
                </div>
            </template>
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
                <div
                    v-for="item in todoItems"
                    :key="item.key"
                    class="text-center p-3 rounded-lg cursor-pointer transition-all duration-200 border border-transparent hover:border-br hover:shadow-sm hover:bg-fill-light"
                    @click="item.onClick?.()"
                >
                    <div
                        class="text-2xl font-bold mb-1"
                        :class="item.count > 0 ? item.colorClass || 'text-primary' : 'text-tx-secondary'"
                    >
                        {{ item.count }}
                    </div>
                    <div class="text-xs text-tx-secondary whitespace-nowrap">{{ item.label }}</div>
                </div>
            </div>
        </el-card>

        <!-- 婚期风险急件雷达 (未来7天临期订单) -->
        <el-card
            v-if="workbenchData.risk_orders && workbenchData.risk_orders.length > 0"
            class="!border-none mb-4 border-l-4 !border-l-amber-500"
            shadow="never"
        >
            <template #header>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block animate-pulse"></span>
                        <span class="font-bold text-base text-tx-primary">婚期急件雷达（未来 7 天内服务）</span>
                        <el-tag size="small" type="danger" effect="light">
                            {{ workbenchData.risk_orders.length }} 单急需协同
                        </el-tag>
                    </div>
                    <span class="text-tx-secondary text-xs hidden md:inline">
                        系统自动监测：婚期临近但尾款未结清或有协同风险的订单，请主理人与客服优先跟进
                    </span>
                </div>
            </template>
            <el-table :data="workbenchData.risk_orders" size="default" stripe>
                <el-table-column label="紧急程度" width="130">
                    <template #default="{ row }">
                        <el-tag :type="row.days_left <= 2 ? 'danger' : 'warning'" effect="light">
                            {{ row.days_left === 0 ? '今日服务' : `距婚期 ${row.days_left} 天` }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="订单号" prop="order_sn" min-width="160" />
                <el-table-column label="新人 / 客户" min-width="140">
                    <template #default="{ row }">
                        <div class="font-medium">{{ row.contact_name }}</div>
                        <div class="text-xs text-tx-secondary">{{ row.contact_mobile }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="服务婚期" prop="service_date" width="120" align="center" />
                <el-table-column label="风险项与金额" min-width="200">
                    <template #default="{ row }">
                        <el-tag size="small" type="danger">{{ row.risk_label }}</el-tag>
                        <span class="text-xs text-tx-secondary ml-2">订单总额 ¥{{ row.pay_amount }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="订单状态" width="100" align="center">
                    <template #default="{ row }">
                        <el-tag size="small" :type="statusTagType(row.order_status)">
                            {{ row.order_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="100" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            type="primary"
                            link
                            @click="pushToPath(routePaths.order.value, { order_sn: row.order_sn })"
                        >
                            立即处理
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
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
                        <router-link :to="routePaths.order.value" class="text-primary text-sm cursor-pointer">
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
import { getRoutePath } from '@/router'
import useSettingStore from '@/stores/modules/setting'
import { calcColor } from '@/utils/util'
import { useRouter } from 'vue-router'

const router = useRouter()
const settingStore = useSettingStore()
const routePaths = {
    flow: computed(() => getRoutePath('finance.flow/lists') || '/financial/flow'),
    order: computed(() => getRoutePath('ops.order/lists') || '/order/lists'),
    refund: computed(() => getRoutePath('ops.refund/lists') || '/order/refund'),
    settlement: computed(() => getRoutePath('finance.settlement/lists') || '/financial/settlement'),
    user: computed(() => getRoutePath('user.user/lists') || '/consumer/lists'),
    staff: computed(() => getRoutePath('ops.staff/lists') || '/staff'),
}

const pushToPath = (path: string, query?: Record<string, string | number>) => {
    if (!path) {
        return
    }

    router.push({
        path,
        query,
    })
}

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
        pending_voucher: 0,
        pending_refund: 0,
        pending_settlement: 0,
        near_service_risk_count: 0,
        in_service: 0,
        pending_staff: 0,
    },
    risk_orders: [],
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
        onClick: () => pushToPath(routePaths.flow.value),
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
        onClick: () => pushToPath(routePaths.order.value),
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
        onClick: () => pushToPath(routePaths.user.value),
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
        onClick: () => pushToPath(routePaths.order.value, { order_status: 0 }),
    },
])

// 待办总数
const todoTotal = computed(() => {
    const t = workbenchData.todo
    return (
        Number(t.pending_confirm || 0) +
        Number(t.pending_pay || 0) +
        Number(t.pending_voucher || 0) +
        Number(t.pending_refund || 0) +
        Number(t.pending_settlement || 0) +
        Number(t.near_service_risk_count || 0) +
        Number(t.in_service || 0) +
        Number(t.pending_staff || 0)
    )
})

// 待办事项配置
const todoItems = computed(() => [
    {
        key: 'pending_confirm',
        label: '待确认订单',
        count: workbenchData.todo.pending_confirm || 0,
        colorClass: 'text-amber-500',
        onClick: () => pushToPath(routePaths.order.value, { order_status: 0 }),
    },
    {
        key: 'pending_pay',
        label: '待支付订单',
        count: workbenchData.todo.pending_pay || 0,
        colorClass: 'text-blue-500',
        onClick: () => pushToPath(routePaths.order.value, { order_status: 1 }),
    },
    {
        key: 'pending_voucher',
        label: '待审收款凭证',
        count: workbenchData.todo.pending_voucher || 0,
        colorClass: 'text-rose-500',
        onClick: () => pushToPath(routePaths.settlement.value),
    },
    {
        key: 'pending_refund',
        label: '待审核退款',
        count: workbenchData.todo.pending_refund || 0,
        colorClass: 'text-red-500',
        onClick: () => pushToPath(routePaths.refund.value),
    },
    {
        key: 'pending_settlement',
        label: '待处理结算',
        count: workbenchData.todo.pending_settlement || 0,
        colorClass: 'text-emerald-500',
        onClick: () => pushToPath(routePaths.settlement.value),
    },
    {
        key: 'near_service_risk_count',
        label: '婚期临期急件',
        count: workbenchData.todo.near_service_risk_count || 0,
        colorClass: 'text-purple-500',
        onClick: () => pushToPath(routePaths.order.value, { payment_mode: 'deposit', deposit_paid: 1, balance_paid: 0 }),
    },
    {
        key: 'in_service',
        label: '服务中订单',
        count: workbenchData.todo.in_service || 0,
        colorClass: 'text-indigo-500',
        onClick: () => pushToPath(routePaths.order.value, { order_status: 3 }),
    },
    {
        key: 'pending_staff',
        label: '待审核员工',
        count: workbenchData.todo.pending_staff || 0,
        colorClass: 'text-cyan-500',
        onClick: () => pushToPath(routePaths.staff.value),
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
            color: ['#e6a23c', '#f56c6c', '#409eff', '#67c23a', '#909399', '#c0c4cc', '#f89898', '#c084fc'],
        },
    ],
}))

// 订单状态标签类型
const statusTagType = (status: number) => {
    const map = {
        0: 'warning',   // 待确认
        1: 'danger',    // 待支付
        2: 'info',      // 待服务
        3: 'primary',   // 服务中
        4: 'success',   // 已完成
        5: 'success',   // 已评价
        6: 'info',      // 已取消
        7: 'warning',   // 已暂停
        8: 'info',      // 已退款
        9: 'danger',    // 用户已删除
    } as const
    return map[status as keyof typeof map] ?? 'info'
}

// 获取数据
const getData = async () => {
    try {
        const res: any = await getWorkbench()
        workbenchData.today = res.today ?? workbenchData.today
        workbenchData.todo = res.todo ?? workbenchData.todo
        workbenchData.risk_orders = res.risk_orders ?? []
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
