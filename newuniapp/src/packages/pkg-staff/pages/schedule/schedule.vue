<template>
    <view class="page-wrap schedule">
        <!-- 月份切换 -->
        <view class="month">
            <view hover-class="is-pressed" class="month__nav" aria-label="上一个月" aria-role="button" @tap="prevMonth">
                <u-icon name="arrow-left" :size="32" color="var(--c-ink-2)" aria-hidden="true" />
            </view>
            <text class="month__text">{{ year }} 年 {{ month }} 月</text>
            <view hover-class="is-pressed" class="month__nav" aria-label="下一个月" aria-role="button" @tap="nextMonth">
                <u-icon name="arrow-right" :size="32" color="var(--c-ink-2)" aria-hidden="true" />
            </view>
        </view>

        <!-- 汇总 -->
        <view class="summary">
            <view class="summary__item">
                <text class="summary__num">{{ summary.available ?? 0 }}</text>
                <text class="summary__label">可预约</text>
            </view>
            <view class="summary__item">
                <text class="summary__num">{{ summary.booked ?? 0 }}</text>
                <text class="summary__label">已预约</text>
            </view>
            <view class="summary__item">
                <text class="summary__num">{{ summary.locked ?? 0 }}</text>
                <text class="summary__label">锁定</text>
            </view>
            <view class="summary__item">
                <text class="summary__num">{{ pendingOrders.length }}</text>
                <text class="summary__label">待服务</text>
            </view>
        </view>

        <view v-if="loading" class="page-pad">
            <base-skeleton :rows="2" />
        </view>

        <base-empty v-else-if="error" status="error"
            :error-kind="view?.errorKind || 'unknown'"
            :error-text="view?.title" :desc="view?.desc"
            :primary-action="view?.primaryAction"
            fixed @primary="onPrimary" @retry="load" />

        <template v-else>
            <view class="calendar" aria-label="档期日历，按日期显示可预约、已预约、锁定状态">
                <!-- 星期表头 -->
                <view class="week">
                    <text v-for="w in WEEK" :key="w" class="week__item">{{ w }}</text>
                </view>

                <!-- 日历 -->
                <view class="grid">
                    <view v-for="(cell, i) in cells" :key="i" class="cell" :class="{ 'is-empty': !cell.day }">
                        <template v-if="cell.day">
                            <view hover-class="is-pressed"
                                class="cell__inner"
                                :class="[`is-${cell.tone}`, { 'is-today': cell.isToday, 'is-selected': cell.date === selected }]"
                                :aria-label="`${month} 月 ${cell.day} 日，${toneLabel(cell.tone)}${cell.isToday ? '，今天' : ''}`"
                                @tap="selected = cell.date || ''"
                            >
                                <text class="cell__day">{{ cell.day }}</text>
                                <view v-if="cell.tone !== 'none'" class="cell__dot" />
                            </view>
                        </template>
                    </view>
                </view>

                <!-- 图例 -->
                <view class="legend">
                    <view v-for="l in LEGEND" :key="l.tone" class="legend__item">
                        <view class="legend__dot" :class="`is-${l.tone}`" />
                        <text class="legend__text">{{ l.label }}</text>
                    </view>
                </view>
            </view>

            <!-- 选中日详情 -->
            <view class="page-pad detail">
                <section-header :title="selected || '选择日期'" :subtitle="selectedInfo" />
                <view v-if="selectedOrders.length" class="orders">
                    <base-card v-for="o in selectedOrders" :key="o.id">
                        <view class="order">
                            <view class="order__body">
                                <text class="order__sn">{{ o.order_sn }}</text>
                                <text class="order__meta">{{ o.package_name || o.item_type_desc || '服务订单' }}</text>
                            </view>
                            <status-tag :label="orderStatusLabel(o)" :tone="orderStatusTone(o)" size="sm" />
                        </view>
                    </base-card>
                </view>
                <base-empty v-else status="empty" empty-text="该日暂无安排" />
            </view>
        </template>
    </view>
    <app-toast />
</template>

<script setup lang="ts">
/**
 * 服务人员：我的档期（只读月历）。
 *
 * 数据来自 /staff_center/scheduleMonth，返回 { year, month, schedules[], month_summary, pending_service_orders[] }。
 * 只读：改档期仍在后台或服务人员中心里做，小程序端不提供写入入口，避免误操作影响已成交订单。
 */
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import {
    ORDER_STATUS_NAME_MAP,
    ORDER_STATUS_TONE,
    SCHEDULE_STATUS_NAME_MAP,
    ScheduleStatus,
    resolveStatusLabel,
    resolveTone
} from '@shared/contracts'
import { getStaffScheduleMonth } from '@/api/staff'
import { resolveErrorView } from '@/utils/resolveErrorView'

const WEEK = ['日', '一', '二', '三', '四', '五', '六']
const LEGEND = [
    { tone: 'available', label: '可预约' },
    { tone: 'booked', label: '已预约' },
    { tone: 'locked', label: '锁定' }
]

const now = new Date()
const year = ref(now.getFullYear())
const month = ref(now.getMonth() + 1)
const loading = ref(true)
const error = ref(false)

/** O5：真实错误驱动 errorKind / 文案 / 恢复动作 */
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') { load(); return }
    if (kind === 'reselect' || kind === 'back') { uni.navigateBack(); return }
    if (kind === 'login') { uni.navigateTo({ url: '/pages/login/login' }); return }
    // contact / 其余：保持现有客服入口，不新增跳转
}

/** 档期状态中文词（读屏 + 图例一致，不只靠颜色） */
function toneLabel(tone?: string) {
    if (tone === 'available') return '可预约'
    if (tone === 'booked') return '已预约'
    if (tone === 'locked') return '锁定'
    return '无安排'
}
const schedules = ref<any[]>([])
const summary = ref<Record<string, number>>({})
const pendingOrders = ref<any[]>([])
const selected = ref('')

/** schedule_date → 状态 */
const byDate = computed(() => {
    const map: Record<string, number> = {}
    for (const s of schedules.value) {
        const d = s.schedule_date || s.date
        if (d) map[String(d)] = Number(s.status)
    }
    return map
})

function toneOf(status: number): string {
    if (status === ScheduleStatus.AVAILABLE) return 'available'
    if (status === ScheduleStatus.BOOKED) return 'booked'
    if (status === ScheduleStatus.LOCKED) return 'locked'
    if (status === ScheduleStatus.RESERVED) return 'locked'
    return 'none'
}

function pad(n: number) {
    return String(n).padStart(2, '0')
}

const cells = computed(() => {
    const first = new Date(year.value, month.value - 1, 1)
    const startWeekday = first.getDay()
    const daysInMonth = new Date(year.value, month.value, 0).getDate()
    const todayStr = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`

    const result: Array<{ day?: number; date?: string; tone?: string; isToday?: boolean }> = []
    for (let i = 0; i < startWeekday; i += 1) result.push({})
    for (let d = 1; d <= daysInMonth; d += 1) {
        const date = `${year.value}-${pad(month.value)}-${pad(d)}`
        result.push({
            day: d,
            date,
            tone: toneOf(byDate.value[date]),
            isToday: date === todayStr
        })
    }
    return result
})

const selectedOrders = computed(() =>
    pendingOrders.value.filter((o) => String(o.service_date || '') === selected.value)
)

const selectedInfo = computed(() => {
    if (!selected.value) return ''
    const s = byDate.value[selected.value]
    if (s === undefined) return '无档期记录'
    return (SCHEDULE_STATUS_NAME_MAP as Record<number, string>)[s] || ''
})

function orderStatusLabel(o: any) {
    const mapped = resolveStatusLabel(ORDER_STATUS_NAME_MAP as any, Number(o.order_status))
    return mapped.known ? mapped.label : o.status_desc || mapped.label
}

function orderStatusTone(o: any) {
    return resolveTone(ORDER_STATUS_TONE as any, Number(o.order_status))
}

async function load() {
    loading.value = true
    error.value = false
    try {
        const res = await getStaffScheduleMonth({ year: year.value, month: month.value })
        schedules.value = Array.isArray((res as any)?.schedules) ? (res as any).schedules : []
        summary.value = (res as any)?.month_summary || {}
        pendingOrders.value = Array.isArray((res as any)?.pending_service_orders)
            ? (res as any).pending_service_orders
            : []
        selected.value = ''
    } catch (e) {
        rawError.value = e
        error.value = true
    } finally {
        loading.value = false
    }
}

function prevMonth() {
    if (month.value === 1) {
        month.value = 12
        year.value -= 1
    } else {
        month.value -= 1
    }
    load()
}

function nextMonth() {
    if (month.value === 12) {
        month.value = 1
        year.value += 1
    } else {
        month.value += 1
    }
    load()
}

onLoad(() => load())
</script>

<style lang="scss" scoped>
.schedule {
    background: var(--c-bg);
    min-height: 100vh;
}

.month {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--sp-4);
    padding: var(--sp-3) var(--sp-4) var(--sp-2);

    &__nav {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 88rpx;
        height: 88rpx;
        margin: calc(-1 * var(--sp-2));
        border-radius: 50%;
        flex-shrink: 0;

        &:active {
            background: var(--c-neutral-bg);
        }
    }

    &__text {
        min-width: 220rpx;
        text-align: center;
        font-size: var(--fs-lg);
        font-weight: 500;
        color: var(--c-ink);
    }
}

.summary {
    display: flex;
    justify-content: space-around;
    margin: 0 var(--sp-4);
    padding: var(--sp-3) var(--sp-2);
    border-radius: var(--r-lg);
    background: var(--c-hero-bg);

    &__item {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--sp-1);
    }

    &__num {
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-brand-text);
        font-variant-numeric: tabular-nums;
    }

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        line-height: var(--lh-body);
        white-space: nowrap;
    }
}

.calendar {
    margin: var(--sp-3) var(--sp-4) 0;
    padding: var(--sp-2) var(--sp-2) 0;
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-lg);
    background: var(--c-surface);
    box-shadow: var(--shadow-card);
    overflow: hidden;
}

.week {
    display: flex;
    padding: var(--sp-2) 0;

    &__item {
        flex: 1;
        text-align: center;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.grid {
    display: flex;
    flex-wrap: wrap;
}

.cell {
    width: 14.2857%;
    height: 96rpx;
    display: flex;
    align-items: center;
    justify-content: center;

    &__inner {
        position: relative;
        width: 100%;
        max-width: 88rpx;
        height: 88rpx;
        border: 1rpx solid transparent;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;

        &.is-today {
            border-color: var(--c-control-border);   /* 今天 1rpx 外框 ≥3:1 */
        }

        &.is-selected {
            background: var(--c-ink);
        }

        &.is-selected .cell__day {
            color: var(--c-on-dark);
            font-weight: var(--fw-semibold);
        }
    }

    &__day {
        font-size: var(--fs-xs);
        color: var(--c-ink);
    }

    &__dot {
        position: absolute;
        bottom: 10rpx;
        width: 8rpx;
        height: 8rpx;
        border-radius: 50%;
    }

    /* 形状编码：可约=实心点 / 已约=空心圈 / 锁定=斜线（不只靠颜色） */
    &__inner.is-available &__dot {
        background: var(--c-success);
        border-radius: 50%;
    }
    &__inner.is-booked &__dot {
        background: transparent;
        border: 2rpx solid var(--c-danger);
        border-radius: 50%;
        width: 10rpx;
        height: 10rpx;
    }
    &__inner.is-locked &__dot {
        background: var(--c-warning);
        border-radius: 2rpx;
        transform: rotate(45deg);
    }
}

.legend {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: var(--sp-2) var(--sp-4);
    padding: var(--sp-3) 0;

    &__item {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        min-height: 44rpx;
    }

    &__dot {
        width: 12rpx;
        height: 12rpx;
        border-radius: 50%;

        &.is-available {
            background: var(--c-success);
            border-radius: 50%;
        }
        &.is-booked {
            background: transparent;
            border: 2rpx solid var(--c-danger);
            border-radius: 50%;
        }
        &.is-locked {
            background: var(--c-warning);
            border-radius: 2rpx;
            transform: rotate(45deg);
        }
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.detail {
    padding-top: var(--sp-2);
    padding-bottom: calc(var(--sp-4) + var(--safe-bottom));
}

.orders {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.order {
    display: flex;
    align-items: center;
    gap: var(--sp-3);

    &__body {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
        min-width: 0;
    }

    &__sn {
        font-size: var(--fs-xs);
        color: var(--c-ink);
        overflow-wrap: anywhere;
    }

    &__meta {
        font-size: var(--fs-xs);
        line-height: 1.5;
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }
}
/* 翻月纯图标 88rpx 热区 */
.month__nav { min-width: 88rpx; min-height: 88rpx; display: flex; align-items: center; justify-content: center; }
</style>
