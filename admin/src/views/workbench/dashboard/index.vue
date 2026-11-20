<template>
    <div class="dashboard-page">
        <el-card v-if="denied" class="!border-none" shadow="never">
            <el-empty description="当前账号没有经营看板权限，请联系超级管理员在「设置 › 权限与账号 › 角色」里授权" />
        </el-card>

        <template v-else>
            <el-card class="!border-none" shadow="never" v-loading="loading">
                <template #header>
                    <div class="flex items-center justify-between">
                        <span class="font-medium">财务概览</span>
                        <span class="text-tx-secondary text-sm">{{ periodText }}</span>
                    </div>
                </template>

                <div class="grid grid-cols-4 gap-4">
                    <admin-stat-card
                        v-for="card in incomeCards"
                        :key="card.key"
                        :label="card.label"
                        :value="card.value"
                        :hint="card.hint"
                        :hint-class="card.hintClass"
                    />
                </div>
            </el-card>

            <el-card class="!border-none mt-4" shadow="never" v-loading="loading">
                <template #header>
                    <span class="font-medium">团队与档期</span>
                </template>
                <div class="grid grid-cols-4 gap-4">
                    <admin-stat-card
                        v-for="card in teamCards"
                        :key="card.key"
                        :label="card.label"
                        :value="card.value"
                    />
                </div>

                <div class="mt-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-tx-secondary">
                            {{ capacity.month_label || '本月' }} 档期占用率
                        </span>
                        <span class="text-sm font-medium">{{ capacity.booking_rate ?? 0 }}%</span>
                    </div>
                    <el-progress
                        :percentage="Number(capacity.booking_rate || 0)"
                        :stroke-width="10"
                        :show-text="false"
                    />
                    <div class="mt-2 text-tx-secondary text-xs">
                        已占 {{ capacity.month_occupied_slots ?? 0 }} / 共
                        {{ capacity.month_total_slots ?? 0 }} 个档期（已订
                        {{ capacity.month_booked_slots ?? 0 }}）
                    </div>
                </div>
            </el-card>

            <el-card class="!border-none mt-4" shadow="never" v-loading="loading">
                <template #header>
                    <span class="font-medium">待处理</span>
                </template>
                <div class="grid grid-cols-4 gap-4">
                    <admin-stat-card
                        v-for="card in todoCards"
                        :key="card.key"
                        :label="card.label"
                        :value="card.value"
                    />
                </div>
            </el-card>

            <el-card v-if="members.length" class="!border-none mt-4" shadow="never">
                <template #header>
                    <span class="font-medium">成员负载（近 30 天）</span>
                </template>
                <el-table :data="members" size="large">
                    <el-table-column label="人员" prop="name" min-width="140" />
                    <el-table-column label="分类" prop="category_name" min-width="120" />
                    <el-table-column label="近 30 天订单" prop="recent_order_count" width="140" />
                    <el-table-column label="未来 30 天已订档期" prop="upcoming_booked_slots" width="180" />
                    <el-table-column label="待跟进" prop="follow_up_count" width="110" />
                </el-table>
            </el-card>
        </template>
    </div>
</template>

<script lang="ts" setup name="dashboardIndex">
/**
 * 经营驾驶舱。
 *
 * 数据源是 adminapi/dashboard/*：此前只在小程序端用，后台没有页面，
 * 管理员在电脑上反而看不到经营数据。服务端用「绑定管理员 + RBAC 节点」鉴权，
 * 所以先问 access 再决定渲染，避免非授权账号一进来就吃 403。
 *
 * 趋势图（incomeTrend）与订单统计（orderStats）接口已就绪，
 * 本页先用指标卡 + 表格承载核心信息，图表在 B5 统一视觉时补。
 */
import { dashboardOverview, dashboardTeamOverview } from '@/api/dashboard'
import { formatMoney } from '@shared/utils/format'

const loading = ref(false)
const denied = ref(false)

const overview = reactive<Record<string, any>>({})
const team = reactive<Record<string, any>>({})
const capacity = reactive<Record<string, any>>({})
const todo = reactive<Record<string, any>>({})
const members = ref<any[]>([])

const periodText = computed(() => {
    const p = overview.period || {}
    return p.start_date && p.end_date ? `${p.start_date} 至 ${p.end_date}` : '本月至今'
})

const incomeCards = computed(() => [
    { key: 'income', label: '收款', value: money(overview.total_income) },
    { key: 'refund', label: '退款', value: money(overview.total_refund) },
    { key: 'net', label: '净收入', value: money(overview.net_income) },
    {
        key: 'growth',
        label: '收款环比',
        value: `${Number(overview.income_growth || 0) > 0 ? '+' : ''}${overview.income_growth ?? 0}%`,
        hint: `上期退款环比 ${overview.refund_growth ?? 0}%`,
        hintClass: Number(overview.income_growth || 0) >= 0 ? 'text-danger' : 'text-success'
    }
])

const teamCards = computed(() => [
    { key: 'total', label: '人员总数', value: team.total_staff ?? 0 },
    { key: 'active', label: '在职可接单', value: team.active_staff ?? 0 },
    { key: 'recommend', label: '推荐位', value: team.recommended_staff ?? 0 },
    { key: 'booked', label: '本月已订档期', value: capacity.month_booked_slots ?? 0 }
])

const todoCards = computed(() => [
    { key: 'confirm', label: '待确认订单', value: todo.pending_confirm ?? 0 },
    { key: 'pay', label: '待支付订单', value: todo.pending_pay ?? 0 },
    { key: 'service', label: '服务中订单', value: todo.in_service ?? 0 },
    { key: 'waitlist', label: '候补待跟进', value: todo.waitlist_total ?? 0 }
])

function money(v: any) {
    const n = Number(v || 0)
    if (n >= 10000) return `${formatMoney(n / 10000, { symbol: '¥' })} 万`
    return formatMoney(n, { symbol: '¥' })
}

function assign(target: Record<string, any>, src: any) {
    Object.keys(target).forEach((k) => delete target[k])
    if (src && typeof src === 'object') Object.assign(target, src)
}

async function load() {
    loading.value = true
    denied.value = false
    try {
        // 两个接口分别对应不同 RBAC 节点，任一失败只降级对应区块；
        // 都失败通常是没权限（入口已由菜单控制，这里兜住直接输 URL 的情况）
        let firstError: any = null
        const [ov, tm] = await Promise.all([
            dashboardOverview().catch((e) => {
                firstError = firstError ?? e
                return null
            }),
            dashboardTeamOverview().catch((e) => {
                firstError = firstError ?? e
                return null
            })
        ])
        if (!ov && !tm) {
            denied.value = true
            console.error('[dashboard] 加载失败：', firstError)
            return
        }
        assign(overview, ov)
        if (tm) {
            assign(team, tm.team)
            assign(capacity, tm.capacity)
            assign(todo, tm.todo)
            members.value = Array.isArray(tm.members) ? tm.members : []
        }
    } finally {
        loading.value = false
    }
}

onMounted(load)
</script>

