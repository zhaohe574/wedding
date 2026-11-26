<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell title="我的档期" bg="page" :show-back="true">
        <staff-guard
            v-if="!guardAllowed"
            :phase="guardPhase"
            :reason="guardReason"
            :error="guardError"
            @retry="onGuardRetry"
            @action="onGuardAction"
        />

        <view v-else class="page-wrap schedule">
            <!-- 月份切换 -->
            <view class="month">
                <view
                    hover-class="is-pressed"
                    class="month__nav"
                    aria-label="上一个月"
                    aria-role="button"
                    @tap="prevMonth"
                >
                    <!-- v1 u-icon → v2 tn-icon：arrow-left → left -->
                    <tn-icon
                        name="left"
                        :size="32"
                        class="month__icon"
                        aria-hidden="true"
                    />
                </view>
                <text class="month__text">{{ year }} 年 {{ month }} 月</text>
                <view
                    hover-class="is-pressed"
                    class="month__nav"
                    aria-label="下一个月"
                    aria-role="button"
                    @tap="nextMonth"
                >
                    <!-- v1 u-icon → v2 tn-icon：arrow-right → right -->
                    <tn-icon
                        name="right"
                        :size="32"
                        class="month__icon"
                        aria-hidden="true"
                    />
                </view>
            </view>

            <!-- 汇总 -->
            <view class="summary">
                <view class="summary__item">
                    <text class="summary__num">{{ summary.available_days ?? 0 }}</text>
                    <text class="summary__label">可预约</text>
                </view>
                <view class="summary__item">
                    <text class="summary__num">{{ summary.occupied_days ?? 0 }}</text>
                    <text class="summary__label">已占用</text>
                </view>
                <view class="summary__item">
                    <text class="summary__num">{{ summary.unavailable_days ?? 0 }}</text>
                    <text class="summary__label">不可约</text>
                </view>
                <view class="summary__item">
                    <text class="summary__num">{{
                        summary.pending_service_count ?? pendingOrders.length
                    }}</text>
                    <text class="summary__label">待服务</text>
                </view>
            </view>

            <!-- 候补名单快捷入口 -->
            <view
                class="waitlist-entry"
                hover-class="is-pressed"
                aria-role="button"
                aria-label="查看候补名单"
                @tap="goWaitlist"
            >
                <view class="waitlist-entry__left">
                    <tn-icon name="clock" :size="28" class="waitlist-entry__icon" />
                    <text class="waitlist-entry__title">候补名单</text>
                    <text class="waitlist-entry__desc">
                        {{
                            pendingWaitlistCount > 0
                                ? `${pendingWaitlistCount} 条待通知候补，档期释放后可通知客户`
                                : '查看客户候补与释放通知状态'
                        }}
                    </text>
                </view>
                <tn-icon name="right" :size="22" class="waitlist-entry__arrow" />
            </view>

            <view v-if="loading" class="page-pad">
                <base-skeleton :rows="2" />
            </view>

            <base-empty
                v-else-if="error"
                status="error"
                :error-kind="view?.errorKind || 'unknown'"
                :error-text="view?.title"
                :desc="view?.desc"
                :primary-action="view?.primaryAction"
                fixed
                @primary="onPrimary"
                @retry="() => load()"
            />

            <template v-else>
                <view class="calendar" aria-label="档期日历，按日期显示可预约、已预约、锁定、不可约状态">
                    <!-- 星期表头 -->
                    <view class="week">
                        <text v-for="w in WEEK" :key="w" class="week__item">{{ w }}</text>
                    </view>

                    <!-- 日历 -->
                    <view class="grid">
                        <view
                            v-for="(cell, i) in cells"
                            :key="i"
                            class="cell"
                            :class="{ 'is-empty': !cell.day }"
                        >
                            <template v-if="cell.day">
                                <view
                                    hover-class="is-pressed"
                                    class="cell__inner"
                                    :class="[
                                        `is-${cell.tone}`,
                                        {
                                            'is-today': cell.isToday,
                                            'is-selected': cell.date === selected
                                        }
                                    ]"
                                    :aria-label="`${month} 月 ${cell.day} 日，${toneLabel(
                                        cell.tone
                                    )}${cell.isToday ? '，今天' : ''}`"
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

                <!-- 选中日操作与详情 -->
                <view class="page-pad detail">
                    <section-header :title="selected || '选择日期'" :subtitle="selectedInfo" />

                    <base-card v-if="selected" compact class="day-card">
                        <view class="day-card__top">
                            <text class="day-card__state">{{ selectedInfo || '无档期记录' }}</text>
                            <text v-if="selectedRecord?.remark" class="day-card__remark">{{
                                selectedRecord.remark
                            }}</text>
                        </view>
                        <view
                            v-if="
                                (isSelectedFutureOrToday &&
                                    (canToggleSchedule || selectedIsFree)) ||
                                selectedOrders.length ||
                                selectedManualSchedules.length
                            "
                            class="day-card__actions"
                        >
                            <base-button
                                v-if="isSelectedFutureOrToday && canToggleSchedule"
                                :text="isSelectedUnavailable ? '恢复可预约' : '设为不可约'"
                                variant="ghost"
                                size="sm"
                                :disabled="acting"
                                @click="onToggleScheduleStatus"
                            />
                            <base-button
                                v-if="isSelectedFutureOrToday && selectedIsFree"
                                text="添加线下档期"
                                variant="ink"
                                size="sm"
                                :disabled="acting"
                                @click="openAddManual"
                            />
                            <base-button
                                v-if="selectedOrders.length || selectedManualSchedules.length"
                                text="作战面板"
                                variant="gold"
                                size="sm"
                                :block="false"
                                @click="goServiceDay"
                            />
                        </view>
                        <text v-if="!isSelectedFutureOrToday" class="day-card__hint">
                            历史日期仅可查看，不可调整档期或新增线下安排
                        </text>
                    </base-card>

                    <view v-if="selectedManualSchedules.length" class="orders orders--spaced">
                        <base-card
                            v-for="m in selectedManualSchedules"
                            :key="`m-${m.manual_schedule_id || m.id}`"
                            compact
                        >
                            <view class="manual">
                                <view class="manual__head">
                                    <text class="manual__title">{{
                                        m.service_name || '线下档期'
                                    }}</text>
                                    <status-tag
                                        :label="m.status_desc || '线下档期'"
                                        :tone="manualStatusTone(m)"
                                        size="sm"
                                    />
                                </view>
                                <text v-if="m.customer_name || m.customer_mobile" class="manual__meta">
                                    客户：{{ m.customer_name || '未填写'
                                    }}{{
                                        m.customer_mobile
                                            ? ` · ${maskMobile(m.customer_mobile)}`
                                            : ''
                                    }}
                                </text>
                                <text v-if="m.region_name || m.service_address" class="manual__meta">
                                    地点：{{ [m.region_name, m.service_address].filter(Boolean).join(' ') }}
                                </text>
                                <text v-if="m.remark" class="manual__meta">
                                    备注：{{ m.remark }}
                                </text>
                                <view
                                    v-if="
                                        Number(m.can_edit) === 1 ||
                                        Number(m.can_complete) === 1 ||
                                        Number(m.can_cancel) === 1 ||
                                        Number(m.status) !== 2
                                    "
                                    class="manual__actions"
                                >
                                    <base-button
                                        v-if="Number(m.can_edit) === 1"
                                        text="编辑"
                                        variant="ghost"
                                        size="sm"
                                        :disabled="acting"
                                        @click="openEditManual(m)"
                                    />
                                    <base-button
                                        v-if="Number(m.can_complete) === 1"
                                        text="标记完成"
                                        variant="ink"
                                        size="sm"
                                        :disabled="acting"
                                        @click="onCompleteManual(m)"
                                    />
                                    <base-button
                                        v-if="Number(m.status) !== 2"
                                        text="档期海报"
                                        variant="ghost"
                                        size="sm"
                                        :block="false"
                                        :disabled="acting"
                                        @click="onGenerateManualPoster(m)"
                                    />
                                    <base-button
                                        v-if="Number(m.can_cancel) === 1"
                                        text="取消档期"
                                        variant="danger"
                                        size="sm"
                                        :disabled="acting"
                                        @click="onCancelManual(m)"
                                    />
                                </view>
                            </view>
                        </base-card>
                    </view>

                    <view v-if="selectedOrders.length" class="orders orders--spaced">
                        <base-card
                            v-for="o in selectedOrders"
                            :key="o.order_id || o.id"
                            compact
                        >
                            <view
                                hover-class="is-pressed"
                                class="order"
                                @tap="goOrderDetail(o)"
                            >
                                <view class="order__body">
                                    <text class="order__sn">{{ o.order_sn }}</text>
                                    <text class="order__meta">{{
                                        o.package_summary ||
                                        o.package_name ||
                                        o.item_type_desc ||
                                        '服务订单'
                                    }}</text>
                                </view>
                                <status-tag
                                    :label="orderStatusLabel(o)"
                                    :tone="orderStatusTone(o)"
                                    size="sm"
                                />
                                <tn-icon name="right" :size="24" class="order__arrow" />
                            </view>
                        </base-card>
                    </view>

                    <base-empty
                        v-else-if="!selectedManualSchedules.length"
                        status="empty"
                        empty-text="该日暂无服务安排"
                    />
                </view>

                <!-- 本月线下档期一览 -->
                <view v-if="manualSchedules.length" class="page-pad month-manual">
                    <section-header
                        title="本月线下档期"
                        :subtitle="`共 ${manualSchedules.length} 项`"
                    />
                    <view class="orders">
                        <base-card
                            v-for="m in manualSchedules"
                            :key="`all-m-${m.manual_schedule_id || m.id}`"
                            compact
                        >
                            <view
                                hover-class="is-pressed"
                                class="order"
                                @tap="selected = String(m.schedule_date || '')"
                            >
                                <view class="order__body">
                                    <text class="order__sn">{{
                                        `${m.schedule_date || ''} · ${m.service_name || '线下档期'}`
                                    }}</text>
                                    <text v-if="m.customer_name || m.service_address" class="order__meta">
                                        {{
                                            [m.customer_name, m.region_name, m.service_address]
                                                .filter(Boolean)
                                                .join(' · ')
                                        }}
                                    </text>
                                </view>
                                <status-tag
                                    :label="m.status_desc || '线下档期'"
                                    :tone="manualStatusTone(m)"
                                    size="sm"
                                />
                            </view>
                        </base-card>
                    </view>
                </view>
            </template>
        </view>
        <app-toast />
    </page-shell>
    <manual-schedule-sheet
        v-model="sheetVisible"
        :initial-date="sheetDate"
        :editing="editingManual"
        @saved="onManualSaved"
    />
</template>

<script setup lang="ts">
/**
 * 服务人员：我的档期（月历、档期状态开关与线下档期管理）。
 *
 * 数据来自 /staff_center/scheduleMonth，返回 { year, month, schedules{}, month_summary, pending_service_orders[], manual_schedules[] }。
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
import {
    generateStaffConfirmLetter,
    getStaffScheduleMonth,
    staffManualScheduleCancel,
    staffManualScheduleComplete,
    staffScheduleSetStatus
} from '@/api/staff'
import ManualScheduleSheet from '../../components/manual-schedule-sheet/manual-schedule-sheet.vue'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { navigate } from '@/utils/link'
import { toast } from '@/composables/useToast'

const {
    guardPhase,
    guardReason,
    guardError,
    guardAllowed,
    verify,
    onGuardRetry,
    onGuardAction
} = useStaffGuard(() => load(true))

const WEEK = ['日', '一', '二', '三', '四', '五', '六']
const LEGEND = [
    { tone: 'available', label: '可预约' },
    { tone: 'booked', label: '已预约' },
    { tone: 'locked', label: '锁定·预留' },
    { tone: 'unavailable', label: '不可约' }
]

const now = new Date()
const year = ref(now.getFullYear())
const month = ref(now.getMonth() + 1)
const loading = ref(true)
const error = ref(false)
const acting = ref(false)

/** O5：真实错误驱动 errorKind / 文案 / 恢复动作 */
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        void load()
        return
    }
    if (kind === 'reselect' || kind === 'back') {
        uni.navigateBack()
        return
    }
    if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
    }
}

/** 档期状态中文词（读屏 + 图例一致，不只靠颜色） */
function toneLabel(tone?: string) {
    if (tone === 'available') return '可预约'
    if (tone === 'booked') return '已预约'
    if (tone === 'locked') return '锁定·预留'
    if (tone === 'unavailable') return '不可约'
    return '无安排'
}

const scheduleMap = ref<Record<string, any>>({})
const summary = ref<Record<string, number>>({})
const pendingOrders = ref<any[]>([])
const manualSchedules = ref<any[]>([])
const pendingWaitlistCount = ref(0)
const selected = ref('')

const sheetVisible = ref(false)
const sheetDate = ref('')
const editingManual = ref<Record<string, any> | null>(null)

function pad(n: number) {
    return String(n).padStart(2, '0')
}

function getTodayStr(): string {
    const d = new Date()
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

const todayStr = computed(() => getTodayStr())

function maskMobile(value?: string): string {
    return String(value || '').replace(/^(\d{3})\d{4}(\d{4})$/, '$1****$2')
}

/** 兼容服务端返回 { "YYYY-MM-DD": { "0": row } } Map 或数组形态 */
function normalizeSchedules(raw: unknown): Record<string, any> {
    const map: Record<string, any> = {}
    if (!raw || typeof raw !== 'object') return map

    if (Array.isArray(raw)) {
        for (const item of raw) {
            const d = item?.schedule_date || item?.date
            if (d) map[String(d)] = item
        }
        return map
    }

    for (const [dateKey, slotVal] of Object.entries(raw as Record<string, any>)) {
        if (!slotVal || typeof slotVal !== 'object') continue
        const row =
            'status' in slotVal
                ? slotVal
                : slotVal['0'] ?? slotVal[0] ?? Object.values(slotVal)[0]
        if (row && typeof row === 'object') {
            map[dateKey] = row
        }
    }
    return map
}

function toneOf(status?: number): string {
    if (status === ScheduleStatus.AVAILABLE) return 'available'
    if (status === ScheduleStatus.BOOKED) return 'booked'
    if (status === ScheduleStatus.LOCKED) return 'locked'
    if (status === ScheduleStatus.RESERVED) return 'locked'
    if (status === ScheduleStatus.UNAVAILABLE) return 'unavailable'
    return 'none'
}

const cells = computed(() => {
    const first = new Date(year.value, month.value - 1, 1)
    const startWeekday = first.getDay()
    const daysInMonth = new Date(year.value, month.value, 0).getDate()
    const currentToday = getTodayStr()

    const result: Array<{ day?: number; date?: string; tone?: string; isToday?: boolean }> = []
    for (let i = 0; i < startWeekday; i += 1) result.push({})
    for (let d = 1; d <= daysInMonth; d += 1) {
        const date = `${year.value}-${pad(month.value)}-${pad(d)}`
        const row = scheduleMap.value[date]
        result.push({
            day: d,
            date,
            tone: toneOf(row ? Number(row.status) : undefined),
            isToday: date === currentToday
        })
    }
    return result
})

const selectedRecord = computed(() => (selected.value ? scheduleMap.value[selected.value] || null : null))

const selectedManualSchedules = computed(() =>
    manualSchedules.value.filter((m) => String(m.schedule_date || '') === selected.value)
)

const selectedOrders = computed(() =>
    pendingOrders.value.filter((o) => String(o.service_date || '') === selected.value)
)

const selectedInfo = computed(() => {
    if (!selected.value) return ''
    const rec = selectedRecord.value
    if (!rec) return '可预约（默认空闲）'
    return (SCHEDULE_STATUS_NAME_MAP as Record<number, string>)[Number(rec.status)] || ''
})

const isSelectedFutureOrToday = computed(
    () => !!selected.value && selected.value >= todayStr.value
)

const isSelectedUnavailable = computed(
    () =>
        !!selectedRecord.value &&
        Number(selectedRecord.value.status) === ScheduleStatus.UNAVAILABLE
)

const canToggleSchedule = computed(() => {
    if (!isSelectedFutureOrToday.value) return false
    const rec = selectedRecord.value
    if (!rec) return true
    const st = Number(rec.status)
    return (
        (st === ScheduleStatus.AVAILABLE || st === ScheduleStatus.UNAVAILABLE) &&
        Number(rec.order_id || 0) === 0 &&
        Number(rec.manual_schedule_id || 0) === 0
    )
})

const selectedIsFree = computed(() => {
    if (!isSelectedFutureOrToday.value) return false
    if (selectedOrders.value.length > 0) return false
    if (selectedManualSchedules.value.some((m) => Number(m.status) === 0)) return false
    const rec = selectedRecord.value
    if (!rec) return true
    return (
        Number(rec.status) === ScheduleStatus.AVAILABLE &&
        Number(rec.order_id || 0) === 0 &&
        Number(rec.manual_schedule_id || 0) === 0
    )
})

function manualStatusTone(m: any): 'info' | 'success' | 'neutral' {
    const st = Number(m?.status)
    if (st === 1) return 'success'
    if (st === 2) return 'neutral'
    return 'info'
}

function orderStatusLabel(o: any) {
    const mapped = resolveStatusLabel(ORDER_STATUS_NAME_MAP as any, Number(o.order_status))
    return mapped.known ? mapped.label : o.status_desc || mapped.label
}

function orderStatusTone(o: any) {
    return resolveTone(ORDER_STATUS_TONE as any, Number(o.order_status))
}

function goOrderDetail(o: any) {
    const id = Number(o?.order_id || o?.id || 0)
    if (!id) return
    navigate({ path: '/packages/pkg-staff/pages/order-detail/order-detail', query: { id } })
}

function goWaitlist() {
    if (!guardAllowed.value) return
    navigate({ path: '/packages/pkg-staff/pages/waitlist/waitlist' })
}

async function load(forceGuard = false, targetDate = '') {
    if (!(await verify(forceGuard))) return
    loading.value = true
    error.value = false
    try {
        const res = await getStaffScheduleMonth({ year: year.value, month: month.value })
        scheduleMap.value = normalizeSchedules((res as any)?.schedules)
        summary.value = (res as any)?.month_summary || {}
        pendingOrders.value = Array.isArray((res as any)?.pending_service_orders)
            ? (res as any).pending_service_orders
            : []
        manualSchedules.value = Array.isArray((res as any)?.manual_schedules)
            ? (res as any).manual_schedules
            : []
        pendingWaitlistCount.value = Number((res as any)?.pending_waitlist_count ?? 0)

        const monthPrefix = `${year.value}-${pad(month.value)}-`
        const curToday = getTodayStr()
        if (targetDate && targetDate.startsWith(monthPrefix)) {
            selected.value = targetDate
        } else if (selected.value && selected.value.startsWith(monthPrefix)) {
            // 保留当前选中日
        } else if (curToday.startsWith(monthPrefix)) {
            selected.value = curToday
        } else {
            selected.value = `${monthPrefix}01`
        }
    } catch (e) {
        rawError.value = e
        error.value = true
    } finally {
        loading.value = false
    }
}

function onToggleScheduleStatus() {
    if (!guardAllowed.value || acting.value || !canToggleSchedule.value || !selected.value) return
    const nextStatus: 0 | 1 = isSelectedUnavailable.value ? 1 : 0
    const label = nextStatus === 0 ? '设为不可约' : '恢复可预约'
    const version = Number(selectedRecord.value?.version ?? 0)
    const date = selected.value

    uni.showModal({
        title: label,
        content:
            nextStatus === 0
                ? `确认将 ${date} 设为不可约？设置后客户将无法预约该日期。`
                : `确认恢复 ${date} 为可预约状态？`,
        confirmText: label,
        cancelText: '取消',
        success: async (r) => {
            if (!r.confirm) return
            acting.value = true
            try {
                await staffScheduleSetStatus({ date, status: nextStatus, version })
                toast.success(nextStatus === 0 ? '已设为不可约' : '已恢复可预约')
                await load(false, date)
            } catch (e: any) {
                toast.error(String(e?.message || '操作失败，请稍后重试'))
            } finally {
                acting.value = false
            }
        }
    })
}

function openAddManual() {
    if (!guardAllowed.value || acting.value || !selected.value) return
    editingManual.value = null
    sheetDate.value = selected.value
    sheetVisible.value = true
}

function openEditManual(item: any) {
    if (!guardAllowed.value || acting.value) return
    editingManual.value = item
    sheetDate.value = String(item?.schedule_date || selected.value || '')
    sheetVisible.value = true
}

function onCancelManual(item: any) {
    const manualId = Number(item?.manual_schedule_id || item?.id || 0)
    if (!guardAllowed.value || acting.value || !manualId) return
    const date = String(item?.schedule_date || selected.value || '')
    uni.showModal({
        title: '取消线下档期',
        content: `确认取消 ${date} 的「${item?.service_name || '线下档期'}」并释放该日档期？`,
        confirmText: '确认取消',
        cancelText: '返回',
        success: async (r) => {
            if (!r.confirm) return
            acting.value = true
            try {
                await staffManualScheduleCancel({
                    manual_schedule_id: manualId,
                    version: Number(item?.version ?? 1)
                })
                toast.success('已取消并释放档期')
                await load(false, date)
            } catch (e: any) {
                toast.error(String(e?.message || '操作失败，请稍后重试'))
            } finally {
                acting.value = false
            }
        }
    })
}

function onCompleteManual(item: any) {
    const manualId = Number(item?.manual_schedule_id || item?.id || 0)
    if (!guardAllowed.value || acting.value || !manualId) return
    const date = String(item?.schedule_date || selected.value || '')
    uni.showModal({
        title: '标记已完成',
        content: `确认「${item?.service_name || '线下档期'}」已完成履约？`,
        confirmText: '确认完成',
        cancelText: '取消',
        success: async (r) => {
            if (!r.confirm) return
            acting.value = true
            try {
                await staffManualScheduleComplete({
                    manual_schedule_id: manualId,
                    version: Number(item?.version ?? 1)
                })
                toast.success('已标记完成')
                await load(false, date)
            } catch (e: any) {
                toast.error(String(e?.message || '操作失败，请稍后重试'))
            } finally {
                acting.value = false
            }
        }
    })
}

function goServiceDay() {
    if (!guardAllowed.value) return
    navigate({
        path: '/packages/pkg-staff/pages/service-day/service-day',
        query: selected.value ? { date: selected.value } : undefined
    })
}

async function onGenerateManualPoster(item: any) {
    const manualId = Number(item?.manual_schedule_id || item?.id || 0)
    if (!guardAllowed.value || acting.value || !manualId) return
    acting.value = true
    try {
        const res = await generateStaffConfirmLetter({ manual_schedule_id: manualId })
        const imgUrl = String(res?.full_image_url || res?.thumb_image_url || '').trim()
        toast.success('档期海报已生成')
        // 生成后立即刷新日历与当天档期，避免重入继续使用旧缓存
        if (selected.value) {
            await load(false, selected.value)
        } else {
            await load(false)
        }
        if (imgUrl) {
            uni.previewImage({ urls: [imgUrl], current: imgUrl })
        }
    } catch (e: any) {
        toast.error(String(e?.message || '生成档期海报失败'))
    } finally {
        acting.value = false
    }
}

async function onManualSaved(savedDate: string) {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(savedDate)
    if (match) {
        year.value = Number(match[1])
        month.value = Number(match[2])
    }
    await load(false, savedDate)
}

function prevMonth() {
    if (!guardAllowed.value) return
    if (month.value === 1) {
        month.value = 12
        year.value -= 1
    } else {
        month.value -= 1
    }
    void load()
}

function nextMonth() {
    if (!guardAllowed.value) return
    if (month.value === 12) {
        month.value = 1
        year.value += 1
    } else {
        month.value += 1
    }
    void load()
}

onLoad(() => void load())
</script>

<style lang="scss" scoped>
.schedule {
    background: var(--c-bg);
    /* 高度由 page-shell 承担：再写 100vh 会多出一个导航条的高度 */
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
        border-radius: var(--r-pill);
        flex-shrink: 0;

        &:active {
            background: var(--c-neutral-bg);
        }
    }

    &__icon {
        color: var(--c-ink-2);
    }

    &__text {
        min-width: 220rpx;
        text-align: center;
        font-size: var(--fs-lg);
        font-weight: 500;
        color: var(--c-ink);
    }
}

/* P2 §3-4：四格数字条改为 v2 stats 的「降级版式」。
   为什么降级：v2 stats 用 --fs-number（64rpx 衬线大字）是给「1,280 场 / 98%」这种三四位数用的，
   本处是**月内天数计数（1–2 位）**，64rpx 会显得空；01 §3.1 也明确「--fs-number 禁止用于非金额数字」。
   所以：数字 --fs-xl 衬线 + 标签 --fs-xs，靠竖线分层、**不加卡片底**（与 v2 stats 一致）。 */
.summary {
    display: flex;
    margin: 0 var(--sp-4);
    padding: var(--sp-3) 0;

    &__item {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--sp-1);

        /* 四格之间 1rpx 竖线，替代原来的卡片底分层 */
        & + & {
            border-left: 1rpx solid var(--c-line);
        }
    }

    &__num {
        font-family: var(--font-serif);
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

.waitlist-entry {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    margin: var(--sp-2) var(--sp-4) 0;
    padding: var(--sp-2) var(--sp-3);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-md);
    background: var(--c-surface);

    &__left {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__icon {
        flex-shrink: 0;
        color: var(--c-brand-text);
    }

    &__title {
        flex-shrink: 0;
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__desc {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__arrow {
        flex-shrink: 0;
        color: var(--c-ink-3);
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
        border-radius: var(--r-pill);
        display: flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;

        &.is-today {
            border-color: var(--c-control-border); /* 今天 1rpx 外框 ≥3:1 */
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
        border-radius: var(--r-pill);
    }

    /* 形状编码：可约=实心点 / 已约=空心圈 / 锁定=斜线（不只靠颜色） */
    &__inner.is-available &__dot {
        background: var(--c-success);
        border-radius: var(--r-pill);
    }
    &__inner.is-booked &__dot {
        background: transparent;
        border: 2rpx solid var(--c-danger);
        border-radius: var(--r-pill);
        width: 10rpx;
        height: 10rpx;
    }
    &__inner.is-locked &__dot {
        /* 批 1.5 F：锁定档从 --c-warning 改 --c-info（跨屏语义：query 的「紧张」才用琥珀） */
        background: var(--c-info);
        border-radius: 0;
        transform: rotate(45deg);
    }
    &__inner.is-unavailable &__dot {
        background: var(--c-ink-3);
        width: 14rpx;
        height: 4rpx;
        border-radius: var(--r-pill);
    }

    /* 批 1.5 D：选中态状态点（墨黑底上语义色全部掉到 3:1 以下，统一换香槟金）
       ⚠️ 这两条必须写在 is-available / is-booked / is-locked / is-unavailable 之后、.cell 块结束之前。
       四条选择器同为 (0,3,0)，靠源序生效；写进 .is-selected 块内会被上面覆盖，
       改完零变化且不报错。 */
    &__inner.is-selected &__dot {
        background: var(--c-brand-soft);
        border-color: var(--c-brand-soft); /* 空心圈边框原为 --c-danger，墨黑底仅 2.77:1 */
    }
    /* (0,4,0) 稳赢：空心态填回透明，保住「空心圈=已约」的形状编码（色盲用户唯一通道） */
    &__inner.is-selected.is-booked &__dot {
        background: transparent;
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
        border-radius: var(--r-pill);

        &.is-available {
            background: var(--c-success);
            border-radius: var(--r-pill);
        }
        &.is-booked {
            background: transparent;
            border: 2rpx solid var(--c-danger);
            border-radius: var(--r-pill);
        }
        &.is-locked {
            /* 批 1.5 F：图例同步改 --c-info，漏改会出现「日历是蓝的、图例是琥珀的」 */
            background: var(--c-info);
            border-radius: 0;
            transform: rotate(45deg);
        }
        &.is-unavailable {
            background: var(--c-ink-3);
            width: 14rpx;
            height: 4rpx;
            border-radius: var(--r-pill);
        }
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.detail {
    padding-top: var(--sp-2);
    padding-bottom: var(--sp-3);
}

.day-card {
    margin-bottom: var(--sp-2);

    &__top {
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__state {
        font-size: var(--fs-sm);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__remark,
    &__hint {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__hint {
        display: block;
        margin-top: var(--sp-2);
    }

    &__actions {
        display: flex;
        flex-wrap: wrap;
        gap: var(--sp-2);
        margin-top: var(--sp-3);
    }
}

.orders {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);

    &--spaced {
        margin-top: var(--sp-2);
    }
}

.manual {
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__title {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
        overflow-wrap: anywhere;
    }

    &__meta {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
        overflow-wrap: anywhere;
    }

    &__actions {
        display: flex;
        flex-wrap: wrap;
        gap: var(--sp-2);
        margin-top: var(--sp-2);
    }
}

.order {
    display: flex;
    align-items: center;
    gap: var(--sp-2);

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
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }

    &__arrow {
        color: var(--c-ink-3);
        flex-shrink: 0;
    }
}

.month-manual {
    padding-top: var(--sp-2);
    padding-bottom: calc(var(--sp-5) + var(--safe-bottom));
}
/* 翻月纯图标 88rpx 热区 */
.month__nav {
    min-width: 88rpx;
    min-height: 88rpx;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
