<template>
    <div class="financial-overview">
        <!-- 日期筛选 -->
        <el-card class="!border-none mb-4" shadow="never">
            <el-form :model="queryParams" inline>
                <el-form-item label="统计周期">
                    <el-date-picker
                        v-model="dateRange"
                        type="daterange"
                        range-separator="至"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                        value-format="YYYY-MM-DD"
                        :shortcuts="dateShortcuts"
                        @change="handleDateChange"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="fetchData">查询</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <!-- 核心指标卡片 -->
        <el-row :gutter="16" class="mb-4">
            <el-col :span="6">
                <el-card class="stat-card" shadow="never">
                    <div class="stat-title">总收入</div>
                    <div class="stat-value text-primary">¥{{ formatMoney(overview.total_income) }}</div>
                    <div class="stat-footer">
                        <span :class="overview.income_growth >= 0 ? 'text-success' : 'text-danger'">
                            <el-icon><component :is="overview.income_growth >= 0 ? 'Top' : 'Bottom'" /></el-icon>
                            {{ Math.abs(overview.income_growth) }}%
                        </span>
                        <span class="text-muted">环比</span>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card class="stat-card" shadow="never">
                    <div class="stat-title">总退款</div>
                    <div class="stat-value text-warning">¥{{ formatMoney(overview.total_refund) }}</div>
                    <div class="stat-footer">
                        <span :class="overview.refund_growth >= 0 ? 'text-danger' : 'text-success'">
                            <el-icon><component :is="overview.refund_growth >= 0 ? 'Top' : 'Bottom'" /></el-icon>
                            {{ Math.abs(overview.refund_growth) }}%
                        </span>
                        <span class="text-muted">环比</span>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card class="stat-card" shadow="never">
                    <div class="stat-title">净收入</div>
                    <div class="stat-value text-success">¥{{ formatMoney(overview.net_income) }}</div>
                    <div class="stat-footer">
                        <span class="text-muted">收入 - 退款</span>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card class="stat-card" shadow="never">
                    <div class="stat-title">毛利润</div>
                    <div class="stat-value" :class="overview.gross_profit >= 0 ? 'text-success' : 'text-danger'">
                        ¥{{ formatMoney(overview.gross_profit) }}
                    </div>
                    <div class="stat-footer">
                        <span class="text-muted">利润率 {{ overview.profit_rate }}%</span>
                    </div>
                </el-card>
            </el-col>
        </el-row>

        <!-- 次要指标 -->
        <el-row :gutter="16" class="mb-4">
            <el-col :span="6">
                <el-card class="stat-card-mini" shadow="never">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="stat-title-mini">总成本</div>
                            <div class="stat-value-mini">¥{{ formatMoney(overview.total_cost) }}</div>
                        </div>
                        <el-icon class="stat-icon" :size="40"><Money /></el-icon>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card class="stat-card-mini" shadow="never">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="stat-title-mini">订单数</div>
                            <div class="stat-value-mini">{{ overview.order_count }}</div>
                        </div>
                        <el-icon class="stat-icon" :size="40"><Document /></el-icon>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card class="stat-card-mini" shadow="never">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="stat-title-mini">客单价</div>
                            <div class="stat-value-mini">¥{{ formatMoney(overview.avg_order_amount) }}</div>
                        </div>
                        <el-icon class="stat-icon" :size="40"><ShoppingCart /></el-icon>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card class="stat-card-mini" shadow="never">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="stat-title-mini">统计天数</div>
                            <div class="stat-value-mini">{{ overview.period?.days || 0 }} 天</div>
                        </div>
                        <el-icon class="stat-icon" :size="40"><Calendar /></el-icon>
                    </div>
                </el-card>
            </el-col>
        </el-row>

        <!-- 图表区域 -->
        <el-row :gutter="16">
            <el-col :span="16">
                <el-card class="!border-none" shadow="never">
                    <template #header>
                        <div class="flex justify-between items-center">
                            <span>收入趋势</span>
                            <el-radio-group v-model="trendType" size="small" @change="fetchTrend">
                                <el-radio-button label="daily">日</el-radio-button>
                                <el-radio-button label="monthly">月</el-radio-button>
                            </el-radio-group>
                        </div>
                    </template>
                    <div ref="trendChartRef" style="height: 300px;"></div>
                </el-card>
            </el-col>
            <el-col :span="8">
                <el-card class="!border-none" shadow="never">
                    <template #header>支付方式分布</template>
                    <div ref="payWayChartRef" style="height: 300px;"></div>
                </el-card>
            </el-col>
        </el-row>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, nextTick } from 'vue'
import { getFinancialOverview, getIncomeTrend, getPayWayAnalysis } from '@/api/financial'
import * as echarts from 'echarts'

const dateRange = ref<string[]>([])
const queryParams = reactive({
    start_date: '',
    end_date: ''
})

const overview = ref<any>({})
const trendType = ref('daily')
const trendChartRef = ref<HTMLElement | null>(null)
const payWayChartRef = ref<HTMLElement | null>(null)

let trendChart: echarts.ECharts | null = null
let payWayChart: echarts.ECharts | null = null

const dateShortcuts = [
    { text: '今天', value: () => [new Date(), new Date()] },
    { text: '本周', value: () => {
        const end = new Date()
        const start = new Date()
        start.setTime(start.getTime() - (start.getDay() - 1) * 24 * 60 * 60 * 1000)
        return [start, end]
    }},
    { text: '本月', value: () => {
        const end = new Date()
        const start = new Date(end.getFullYear(), end.getMonth(), 1)
        return [start, end]
    }},
    { text: '近30天', value: () => {
        const end = new Date()
        const start = new Date()
        start.setTime(start.getTime() - 29 * 24 * 60 * 60 * 1000)
        return [start, end]
    }}
]

const formatMoney = (val: number | string) => {
    const num = Number(val) || 0
    return num.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const handleDateChange = (val: string[] | null) => {
    if (val) {
        queryParams.start_date = val[0]
        queryParams.end_date = val[1]
    } else {
        queryParams.start_date = ''
        queryParams.end_date = ''
    }
}

const fetchData = async () => {
    try {
        const res = await getFinancialOverview(queryParams)
        overview.value = res || {}
        fetchTrend()
        fetchPayWay()
    } catch (e) {
        console.error(e)
    }
}

const fetchTrend = async () => {
    try {
        const params: any = { type: trendType.value }
        if (trendType.value === 'daily') {
            params.start_date = queryParams.start_date
            params.end_date = queryParams.end_date
        } else {
            params.year = new Date().getFullYear()
        }
        const res = await getIncomeTrend(params)
        renderTrendChart(res.data || {})
    } catch (e) {
        console.error(e)
    }
}

const fetchPayWay = async () => {
    try {
        const res = await getPayWayAnalysis(queryParams)
        renderPayWayChart(res.list || [])
    } catch (e) {
        console.error(e)
    }
}

const renderTrendChart = (data: Record<string, number>) => {
    if (!trendChartRef.value) return
    if (!trendChart) {
        trendChart = echarts.init(trendChartRef.value)
    }
    const xData = Object.keys(data)
    const yData = Object.values(data)
    
    trendChart.setOption({
        tooltip: { trigger: 'axis' },
        grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
        xAxis: { type: 'category', data: xData, boundaryGap: false },
        yAxis: { type: 'value', axisLabel: { formatter: '¥{value}' } },
        series: [{
            name: '收入',
            type: 'line',
            smooth: true,
            data: yData,
            areaStyle: { opacity: 0.3 },
            itemStyle: { color: '#409EFF' }
        }]
    })
}

const renderPayWayChart = (data: any[]) => {
    if (!payWayChartRef.value) return
    if (!payWayChart) {
        payWayChart = echarts.init(payWayChartRef.value)
    }
    
    const pieData = data.filter(item => item.amount > 0).map(item => ({
        name: item.label,
        value: item.amount
    }))
    
    payWayChart.setOption({
        tooltip: { trigger: 'item', formatter: '{b}: ¥{c} ({d}%)' },
        legend: { orient: 'vertical', left: 'left' },
        series: [{
            type: 'pie',
            radius: ['40%', '70%'],
            avoidLabelOverlap: false,
            itemStyle: { borderRadius: 10, borderColor: '#fff', borderWidth: 2 },
            label: { show: false },
            emphasis: { label: { show: true, fontSize: 14, fontWeight: 'bold' } },
            data: pieData
        }]
    })
}

onMounted(() => {
    // 默认选择本月
    const now = new Date()
    const start = new Date(now.getFullYear(), now.getMonth(), 1)
    dateRange.value = [
        start.toISOString().split('T')[0],
        now.toISOString().split('T')[0]
    ]
    handleDateChange(dateRange.value)
    fetchData()
})
</script>

<style scoped>
.stat-card {
    text-align: center;
    padding: 20px;
}
.stat-title {
    font-size: 14px;
    color: #909399;
    margin-bottom: 8px;
}
.stat-value {
    font-size: 28px;
    font-weight: bold;
    margin-bottom: 8px;
}
.stat-footer {
    font-size: 12px;
}
.stat-card-mini {
    padding: 16px;
}
.stat-title-mini {
    font-size: 12px;
    color: #909399;
}
.stat-value-mini {
    font-size: 20px;
    font-weight: bold;
    margin-top: 4px;
}
.stat-icon {
    color: #dcdfe6;
}
.text-primary { color: #409EFF; }
.text-success { color: #67C23A; }
.text-warning { color: #E6A23C; }
.text-danger { color: #F56C6C; }
.text-muted { color: #909399; }
</style>
