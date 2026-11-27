<template>
    <page-shell
        title="服务日作战面板"
        bg="page"
        :show-back="true"
        :pull-refresh="true"
        @refresh="onRefresh"
    >
        <staff-guard
            v-if="!guardAllowed"
            :phase="guardPhase"
            :reason="guardReason"
            :error="guardError"
            @retry="onGuardRetry"
            @action="onGuardAction"
        />

        <view v-else class="page-wrap battle-page">
            <!-- 顶部日期切换栏 -->
            <view class="date-strip page-pad">
                <scroll-view class="date-strip__scroll" scroll-x :show-scrollbar="false">
                    <view class="date-strip__row">
                        <view
                            v-for="item in upcomingDates"
                            :key="item.date"
                            hover-class="is-pressed"
                            class="date-chip"
                            :class="{ 'is-active': item.date === activeDate }"
                            @tap="selectDate(item.date)"
                        >
                            <text class="date-chip__day">{{
                                item.is_today ? '今天' : formatShortDate(item.date)
                            }}</text>
                            <text class="date-chip__count">{{ item.total_count }}场</text>
                        </view>
                    </view>
                </scroll-view>

                <picker mode="date" :value="activeDate || today" @change="onPickDate">
                    <view hover-class="is-pressed" class="date-picker-btn" aria-label="选择日期">
                        <tn-icon name="calendar" :size="28" class="date-picker-btn__icon" />
                        <text class="date-picker-btn__text">选日期</text>
                    </view>
                </picker>
            </view>

            <view v-if="loading" class="page-pad">
                <base-skeleton :rows="4" variant="card" />
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
                @retry="reload"
            />

            <view v-else-if="panel" class="page-pad battle-body">
                <!-- 当日战场总览深墨主卡 -->
                <view class="hero-card">
                    <view class="hero-card__top">
                        <view class="hero-card__date-wrap">
                            <text class="hero-card__eyebrow">SERVICE DAY BRIEFING</text>
                            <view class="hero-card__title-row">
                                <text class="hero-card__date">{{ panel.date }}</text>
                                <text class="hero-card__week">{{ panel.weekday_label }}</text>
                            </view>
                        </view>
                        <view class="hero-card__total">
                            <rolling-number
                                class="hero-card__total-num"
                                :value="statNum(panel.summary.total_count)"
                            />
                            <text class="hero-card__total-unit">总场次</text>
                        </view>
                    </view>

                    <view class="hero-card__stats">
                        <view class="hero-stat">
                            <rolling-number
                                class="hero-stat__num"
                                :value="statNum(panel.summary.pending_confirm_count)"
                            />
                            <text class="hero-stat__label">待确认</text>
                        </view>
                        <view class="hero-stat">
                            <rolling-number
                                class="hero-stat__num"
                                :value="statNum(panel.summary.pending_service_count)"
                            />
                            <text class="hero-stat__label">待服务</text>
                        </view>
                        <view class="hero-stat">
                            <rolling-number
                                class="hero-stat__num"
                                :value="statNum(panel.summary.in_service_count)"
                            />
                            <text class="hero-stat__label">服务中</text>
                        </view>
                        <view class="hero-stat">
                            <rolling-number
                                class="hero-stat__num"
                                :value="statNum(panel.summary.completed_count)"
                            />
                            <text class="hero-stat__label">已完成</text>
                        </view>
                    </view>
                </view>

                <!-- 当日自检清单（本地按日期持久化） -->
                <base-card compact class="checklist-card">
                    <view class="checklist__head">
                        <text class="checklist__title">当日执行自检清单</text>
                        <text class="checklist__progress t-num"
                            >{{ checkedCount }}/{{ CHECKLIST_ITEMS.length }} 项已完成</text
                        >
                    </view>
                    <view class="checklist__list">
                        <view
                            v-for="(item, idx) in CHECKLIST_ITEMS"
                            :key="item.key"
                            hover-class="is-pressed"
                            class="check-row"
                            :class="{
                                'check-row--divided': idx > 0,
                                'is-checked': Boolean(checklistState[item.key])
                            }"
                            @tap="toggleChecklist(item.key)"
                        >
                            <view class="check-row__box">
                                <tn-icon
                                    v-if="checklistState[item.key]"
                                    name="success"
                                    :size="22"
                                    class="check-row__icon"
                                />
                            </view>
                            <text class="check-row__label">{{ item.label }}</text>
                        </view>
                    </view>
                </base-card>

                <!-- 空态兜底 -->
                <base-empty
                    v-if="!panel.orders.length && !panel.manual_schedules.length"
                    status="empty"
                    empty-icon="empty-order"
                    empty-text="当日暂无服务场次"
                    desc="选择上方其他日期查看近期档期，或前往档期日历添加线下记录"
                    show-action
                    action-text="前往档期日历"
                    @action="goSchedule"
                />

                <!-- 平台订单作战卡 -->
                <template v-if="panel.orders.length">
                    <section-header
                        title="平台订单作战卡"
                        :subtitle="`共 ${panel.orders.length} 笔平台预约`"
                        :bar="true"
                        flush
                    />
                    <view class="order-list" :class="{ stagger: staggerIn, 'is-in': staggerIn }">
                        <base-card
                            v-for="(item, index) in panel.orders"
                            :key="item.order_id"
                            compact
                            class="order-card"
                            :style="{ '--i': index }"
                        >
                            <view class="order-card__head">
                                <view class="order-card__title-wrap">
                                    <text class="order-card__pkg">{{
                                        item.items?.[0]?.package_name || '婚礼服务订单'
                                    }}</text>
                                    <text class="order-card__sn">单号 {{ item.order_sn }}</text>
                                </view>
                                <view class="order-card__tags">
                                    <status-tag
                                        v-if="item.has_pending_change"
                                        label="变更待确认"
                                        tone="warning"
                                        size="sm"
                                    />
                                    <status-tag
                                        :label="orderStatusLabel(item)"
                                        :tone="orderStatusTone(item)"
                                        size="sm"
                                    />
                                </view>
                            </view>

                            <!-- 套餐与附加项 -->
                            <view v-if="item.items?.length" class="pkg-box">
                                <view v-for="sub in item.items" :key="sub.id" class="pkg-box__row">
                                    <text class="pkg-box__name"
                                        >{{ sub.package_name }} × {{ sub.quantity }}</text
                                    >
                                    <text v-if="sub.addons?.length" class="pkg-box__addons">
                                        附加：{{
                                            sub.addons
                                                .map((a) => `${a.name}×${a.quantity}`)
                                                .join('、')
                                        }}
                                    </text>
                                </view>
                            </view>

                            <!-- 联系与场地信息 -->
                            <view class="info-list">
                                <view class="info-row">
                                    <text class="info-row__label">现场联系人</text>
                                    <view class="info-row__right">
                                        <text class="info-row__value">
                                            {{ item.contact_name || '客户' }}
                                            {{
                                                item.contact_mobile_masked
                                                    ? `（${item.contact_mobile_masked}）`
                                                    : ''
                                            }}
                                        </text>
                                        <view
                                            v-if="item.contact_mobile"
                                            hover-class="is-pressed"
                                            class="mini-action"
                                            @tap="dialPhone(item.contact_mobile)"
                                        >
                                            <text class="mini-action__text">拨打</text>
                                        </view>
                                    </view>
                                </view>

                                <view class="info-row">
                                    <text class="info-row__label">服务场地</text>
                                    <view class="info-row__right">
                                        <text class="info-row__value">{{
                                            item.service_address || '未填写详细地址'
                                        }}</text>
                                        <view
                                            v-if="item.service_address"
                                            hover-class="is-pressed"
                                            class="mini-action"
                                            @tap="copyText(item.service_address, '已复制服务地址')"
                                        >
                                            <text class="mini-action__text">复制</text>
                                        </view>
                                    </view>
                                </view>

                                <view v-if="item.current_pay_stage_desc || item.pay_status_desc" class="info-row">
                                    <text class="info-row__label">款项阶段</text>
                                    <text class="info-row__value">
                                        {{ item.current_pay_stage_desc || item.pay_status_desc }}
                                    </text>
                                </view>

                                <view v-if="item.start_service_time_text" class="info-row">
                                    <text class="info-row__label">开始履约</text>
                                    <text class="info-row__value">{{
                                        item.start_service_time_text
                                    }}</text>
                                </view>

                                <view v-if="item.remark" class="info-row">
                                    <text class="info-row__label">订单备注</text>
                                    <text class="info-row__value">{{ item.remark }}</text>
                                </view>
                            </view>

                            <!-- 同场协作人员 -->
                            <view v-if="item.teammates?.length" class="sub-block">
                                <text class="sub-block__title">同场协作团队</text>
                                <view class="teammates">
                                    <view
                                        v-for="tm in item.teammates"
                                        :key="`${tm.staff_id}-${tm.category_name}`"
                                        class="teammate-pill"
                                    >
                                        <text class="teammate-pill__text">
                                            {{ tm.category_name || '协作' }} · {{ tm.staff_name }}
                                        </text>
                                    </view>
                                </view>
                            </view>

                            <!-- 新人问卷要点与避讳提醒 -->
                            <view v-if="item.questionnaire" class="sub-block">
                                <view class="sub-block__head">
                                    <text class="sub-block__title">新人问卷要点</text>
                                    <text class="sub-block__meta">{{
                                        item.questionnaire.status_desc || '已记录'
                                    }}</text>
                                </view>
                                <view v-if="item.questionnaire.highlights?.length" class="q-list">
                                    <view
                                        v-for="(h, idx) in item.questionnaire.highlights"
                                        :key="`${item.order_id}-q-${idx}`"
                                        class="q-item"
                                        :class="{ 'is-warning': isTabooHighlight(h) }"
                                    >
                                        <text class="q-item__title">
                                            {{ h.category ? `[${h.category}] ` : '' }}{{ h.title }}
                                        </text>
                                        <text class="q-item__answer">{{ h.answer }}</text>
                                    </view>
                                </view>
                                <text v-else class="sub-block__empty">新人暂未填写补充细节</text>
                            </view>

                            <!-- 履约操作与工具快捷入口 -->
                            <view class="card-actions">
                                <base-button
                                    v-if="item.can_staff_confirm"
                                    text="确认接单"
                                    variant="ink"
                                    size="sm"
                                    :block="false"
                                    :loading="busyKey === `confirm:${item.order_id}`"
                                    :disabled="Boolean(busyKey)"
                                    @click="handleOrderFulfill('confirm', item)"
                                />
                                <base-button
                                    v-if="item.can_staff_start"
                                    text="开始履约"
                                    variant="gold"
                                    size="sm"
                                    :block="false"
                                    :loading="busyKey === `start:${item.order_id}`"
                                    :disabled="Boolean(busyKey)"
                                    @click="handleOrderFulfill('start', item)"
                                />
                                <base-button
                                    v-if="item.can_staff_complete"
                                    text="完成服务"
                                    variant="ink"
                                    size="sm"
                                    :block="false"
                                    :loading="busyKey === `complete:${item.order_id}`"
                                    :disabled="Boolean(busyKey)"
                                    @click="handleOrderFulfill('complete', item)"
                                />
                                <base-button
                                    text="档期海报"
                                    variant="ghost"
                                    size="sm"
                                    :block="false"
                                    :loading="busyKey === `poster-order:${item.order_id}`"
                                    :disabled="Boolean(busyKey)"
                                    @click="handleOrderPoster(item)"
                                />
                                <base-button
                                    text="发话术"
                                    variant="ghost"
                                    size="sm"
                                    :block="false"
                                    @click="goCopyTemplates(item.order_id, 0)"
                                />
                                <base-button
                                    text="新人档案"
                                    variant="ghost"
                                    size="sm"
                                    :block="false"
                                    @click="goCoupleDetail('order', item.order_id)"
                                />
                                <base-button
                                    text="订单详情"
                                    variant="text"
                                    size="sm"
                                    :block="false"
                                    @click="goOrderDetail(item.order_id)"
                                />
                            </view>
                        </base-card>
                    </view>
                </template>

                <!-- 线下自主档期作战卡 -->
                <template v-if="panel.manual_schedules.length">
                    <section-header
                        title="线下自主档期"
                        :subtitle="`共 ${panel.manual_schedules.length} 场自有记录`"
                        :bar="true"
                        flush
                    />
                    <view class="order-list" :class="{ stagger: staggerIn, 'is-in': staggerIn }">
                        <base-card
                            v-for="(m, index) in panel.manual_schedules"
                            :key="m.manual_schedule_id || m.id"
                            compact
                            class="order-card"
                            :style="{ '--i': index }"
                        >
                            <view class="order-card__head">
                                <view class="order-card__title-wrap">
                                    <text class="order-card__pkg">{{ m.service_name || '线下档期' }}</text>
                                    <text class="order-card__sn">{{
                                        m.region_name || '个人录入档期'
                                    }}</text>
                                </view>
                                <status-tag
                                    :label="m.status_desc || '已记录'"
                                    :tone="manualStatusTone(m.status)"
                                    size="sm"
                                />
                            </view>

                            <view class="info-list">
                                <view class="info-row">
                                    <text class="info-row__label">客户称呼</text>
                                    <view class="info-row__right">
                                        <text class="info-row__value">
                                            {{ m.customer_name || '未填写' }}
                                            {{
                                                m.customer_mobile_masked
                                                    ? `（${m.customer_mobile_masked}）`
                                                    : ''
                                            }}
                                        </text>
                                        <view
                                            v-if="m.customer_mobile"
                                            hover-class="is-pressed"
                                            class="mini-action"
                                            @tap="dialPhone(m.customer_mobile)"
                                        >
                                            <text class="mini-action__text">拨打</text>
                                        </view>
                                    </view>
                                </view>

                                <view v-if="m.service_address" class="info-row">
                                    <text class="info-row__label">服务场地</text>
                                    <view class="info-row__right">
                                        <text class="info-row__value">{{ m.service_address }}</text>
                                        <view
                                            hover-class="is-pressed"
                                            class="mini-action"
                                            @tap="copyText(m.service_address, '已复制服务地址')"
                                        >
                                            <text class="mini-action__text">复制</text>
                                        </view>
                                    </view>
                                </view>

                                <view v-if="m.remark" class="info-row">
                                    <text class="info-row__label">备注事项</text>
                                    <text class="info-row__value">{{ m.remark }}</text>
                                </view>
                            </view>

                            <view class="card-actions">
                                <base-button
                                    v-if="m.can_complete"
                                    text="标记已完成"
                                    variant="ink"
                                    size="sm"
                                    :block="false"
                                    :loading="busyKey === `manual-done:${m.manual_schedule_id || m.id}`"
                                    :disabled="Boolean(busyKey)"
                                    @click="handleManualComplete(m)"
                                />
                                <base-button
                                    v-if="Number(m.status) !== 2"
                                    text="档期海报"
                                    variant="ghost"
                                    size="sm"
                                    :block="false"
                                    :loading="
                                        busyKey === `poster-manual:${m.manual_schedule_id || m.id}`
                                    "
                                    :disabled="Boolean(busyKey)"
                                    @click="handleManualPoster(m)"
                                />
                                <base-button
                                    text="发话术"
                                    variant="ghost"
                                    size="sm"
                                    :block="false"
                                    @click="goCopyTemplates(0, m.manual_schedule_id || m.id)"
                                />
                                <base-button
                                    text="新人档案"
                                    variant="ghost"
                                    size="sm"
                                    :block="false"
                                    @click="goCoupleDetail('manual', m.manual_schedule_id || m.id)"
                                />
                            </view>
                        </base-card>
                    </view>
                </template>
            </view>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 服务日作战面板：聚合当日全部平台订单、线下档期、新人问卷避讳、同场团队与履约动作。
 */
import { computed, onUnmounted, reactive, ref, watch } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import {
    ORDER_STATUS_NAME_MAP,
    ORDER_STATUS_TONE,
    resolveStatusLabel,
    resolveTone,
    type Tone
} from '@shared/contracts'
import {
    generateStaffConfirmLetter,
    getStaffServiceDayPanel,
    staffManualScheduleComplete,
    staffOrderComplete,
    staffOrderConfirm,
    staffOrderStartService,
    type StaffServiceDayConfirmLetterBrief,
    type StaffServiceDayManualSchedule,
    type StaffServiceDayOrderItem,
    type StaffServiceDayPanel,
    type StaffServiceDayQuestionnaireHighlight
} from '@/api/staff'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { toast } from '@/composables/useToast'
import { navigate } from '@/utils/link'
import { haptic } from '@/utils/haptics'
import { resolveErrorView } from '@/utils/resolveErrorView'

const CHECKLIST_ITEMS = [
    { key: 'contact', label: '核对新郎新娘与现场联系人电话' },
    { key: 'timeline', label: '确认彩排/到场时间与场地路线' },
    { key: 'questionnaire', label: '核对新人问卷偏好与避讳事项' },
    { key: 'gear', label: '检查设备、话术与服装物料清单' }
] as const

const {
    guardPhase,
    guardReason,
    guardError,
    guardAllowed,
    verify,
    onGuardRetry,
    onGuardAction
} = useStaffGuard(() => init(true))

const activeDate = ref('')
const today = ref('')
const panel = ref<StaffServiceDayPanel | null>(null)
const loading = ref(true)
const error = ref(false)
const hasLoadedOnce = ref(false)
const busyKey = ref('')
const checklistState = reactive<Record<string, boolean>>({})

let firstShow = true

const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const upcomingDates = computed(() => {
    const dates = panel.value?.upcoming_dates || []
    if (!activeDate.value) return dates
    if (dates.some((d) => d.date === activeDate.value)) return dates
    return [
        {
            date: activeDate.value,
            platform_count: panel.value?.summary?.platform_count || 0,
            manual_count: panel.value?.summary?.manual_count || 0,
            total_count: panel.value?.summary?.total_count || 0,
            is_today: activeDate.value === today.value
        },
        ...dates
    ]
})

const checkedCount = computed(
    () => CHECKLIST_ITEMS.filter((item) => Boolean(checklistState[item.key])).length
)

/** 汇总数字：rolling-number 只接受有限数，接口缺字段时落 0 */
function statNum(value: unknown): number {
    const n = Number(value ?? 0)
    return Number.isFinite(n) ? n : 0
}

/* ---- 场次作战卡错峰入场（§3.3）：先挂 .stagger，下一拍再放 .is-in ---- */
const staggerIn = ref(false)
let staggerTimer: ReturnType<typeof setTimeout> | null = null

function playStagger(): void {
    if (staggerIn.value || staggerTimer) return
    staggerTimer = setTimeout(() => {
        staggerTimer = null
        staggerIn.value = true
    }, 40)
}

watch(
    () => {
        const p = panel.value
        return p ? p.orders.length + p.manual_schedules.length : 0
    },
    (len) => {
        if (len) playStagger()
    }
)

onUnmounted(() => {
    if (staggerTimer) clearTimeout(staggerTimer)
})

function checklistStorageKey(dateStr: string): string {
    return `staff_service_day_checks:${dateStr}`
}

function loadChecklist(dateStr: string): void {
    Object.keys(checklistState).forEach((k) => {
        delete checklistState[k]
    })
    if (!dateStr) return
    try {
        const cached = uni.getStorageSync(checklistStorageKey(dateStr))
        if (cached && typeof cached === 'object') {
            CHECKLIST_ITEMS.forEach((item) => {
                checklistState[item.key] = Boolean(cached[item.key])
            })
        }
    } catch {
        // 忽略本地存储读取异常
    }
}

function toggleChecklist(key: string): void {
    haptic('light', 'service-day-check')
    const next = !checklistState[key]
    checklistState[key] = next
    const dateStr = panel.value?.date || activeDate.value
    if (!dateStr) return
    try {
        uni.setStorageSync(checklistStorageKey(dateStr), { ...checklistState })
    } catch {
        // 忽略本地存储写入异常
    }
}

function formatShortDate(dateStr: string): string {
    return dateStr.length >= 10 ? dateStr.slice(5, 10) : dateStr
}

function orderStatusLabel(item: StaffServiceDayOrderItem): string {
    const mapped = resolveStatusLabel(ORDER_STATUS_NAME_MAP as any, Number(item.order_status))
    return mapped.known ? mapped.label : item.order_status_desc || mapped.label
}

function orderStatusTone(item: StaffServiceDayOrderItem): Tone {
    return resolveTone(ORDER_STATUS_TONE as any, Number(item.order_status))
}

function manualStatusTone(status: number): Tone {
    const n = Number(status)
    if (n === 1) return 'success'
    if (n === 2) return 'neutral'
    return 'info'
}

function isTabooHighlight(item: StaffServiceDayQuestionnaireHighlight): boolean {
    const combined = `${item.category || ''} ${item.title || ''} ${item.answer || ''}`
    return /避讳|忌讳|禁忌|敏感|雷区/.test(combined)
}

async function fetchPanel(): Promise<void> {
    if (!guardAllowed.value) return
    loading.value = true
    error.value = false
    rawError.value = null
    try {
        const res = await getStaffServiceDayPanel(
            activeDate.value ? { date: activeDate.value, range_days: 7 } : { range_days: 7 }
        )
        panel.value = res
        today.value = res.today
        if (!activeDate.value) {
            activeDate.value = res.date
        }
        loadChecklist(res.date)
    } catch (e) {
        rawError.value = e
        error.value = true
    } finally {
        loading.value = false
    }
}

function reload(): void {
    void fetchPanel()
}

function selectDate(dateStr: string): void {
    if (!dateStr || dateStr === activeDate.value || loading.value) return
    haptic('light', 'service-day-date')
    activeDate.value = dateStr
    void fetchPanel()
}

function onPickDate(e: any): void {
    const picked = String(e?.detail?.value || '').trim().slice(0, 10)
    if (!picked || picked === activeDate.value) return
    haptic('light', 'service-day-date-pick')
    activeDate.value = picked
    void fetchPanel()
}

function dialPhone(mobile: string): void {
    if (!mobile) return
    uni.makePhoneCall({ phoneNumber: mobile, fail: () => {} })
}

function copyText(text: string, msg: string): void {
    if (!text) return
    uni.setClipboardData({
        data: text,
        success: () => toast.success(msg)
    })
}

function previewImageUrl(url: string): void {
    if (!url) return
    uni.previewImage({ urls: [url], current: url })
}

function handleOrderFulfill(
    action: 'confirm' | 'start' | 'complete',
    item: StaffServiceDayOrderItem
): void {
    if (busyKey.value) return
    const copy =
        action === 'confirm'
            ? {
                  title: '确认接单',
                  content: `确认接下订单 ${item.order_sn} 的服务安排？`,
                  done: '已确认接单'
              }
            : action === 'start'
              ? {
                    title: '开始履约',
                    content: '确认已到达现场并开始当前服务？',
                    done: '已进入服务中'
                }
              : {
                    title: '完成服务',
                    content: '确认本次婚礼服务已全部结束？',
                    done: '已标记完成服务'
                }

    uni.showModal({
        title: copy.title,
        content: copy.content,
        confirmText: '确认',
        cancelText: '取消',
        success: async (r) => {
            if (!r.confirm) return
            busyKey.value = `${action}:${item.order_id}`
            try {
                if (action === 'confirm') {
                    await staffOrderConfirm({ id: item.order_id })
                } else if (action === 'start') {
                    await staffOrderStartService({ id: item.order_id })
                } else {
                    await staffOrderComplete({ id: item.order_id })
                }
                toast.success(copy.done)
                await fetchPanel()
            } catch (e: any) {
                toast.error(String(e?.message || '操作失败，请稍后重试'))
            } finally {
                busyKey.value = ''
            }
        }
    })
}

/**
 * 平台订单 / 线下档期共用的海报动作：
 * 无图直接生成；已过期先确认重新生成；未过期给「预览当前海报 / 重新生成」选择。
 */
async function handlePosterAction(
    busyLabel: string,
    payload: { order_id?: number; manual_schedule_id?: number },
    letter: StaffServiceDayConfirmLetterBrief | null
): Promise<void> {
    if (busyKey.value) return

    const generate = async (): Promise<void> => {
        busyKey.value = busyLabel
        try {
            const res = await generateStaffConfirmLetter(payload)
            toast.success('档期海报已生成')
            const imgUrl = String(res?.full_image_url || res?.thumb_image_url || '').trim()
            if (imgUrl) previewImageUrl(imgUrl)
            await fetchPanel()
        } catch (e: any) {
            toast.error(String(e?.message || '生成档期海报失败'))
        } finally {
            busyKey.value = ''
        }
    }

    const existingUrl = String(letter?.full_image_url || '').trim()
    if (!existingUrl) {
        await generate()
        return
    }

    if (Number(letter?.is_outdated || 0) === 1) {
        uni.showModal({
            title: '档期海报已过期',
            content: '订单或档期信息已变更，建议重新生成最新海报。',
            confirmText: '重新生成',
            cancelText: '预览旧图',
            success: (r) => {
                if (r.confirm) {
                    void generate()
                } else {
                    previewImageUrl(existingUrl)
                }
            }
        })
        return
    }

    uni.showActionSheet({
        itemList: ['预览当前海报', '重新生成最新海报'],
        success: (r) => {
            if (r.tapIndex === 0) {
                previewImageUrl(existingUrl)
            } else if (r.tapIndex === 1) {
                void generate()
            }
        }
    })
}

function handleOrderPoster(item: StaffServiceDayOrderItem): void {
    void handlePosterAction(
        `poster-order:${item.order_id}`,
        { order_id: item.order_id },
        item.confirm_letter
    )
}

function handleManualComplete(item: StaffServiceDayManualSchedule): void {
    const manualId = Number(item.manual_schedule_id || item.id || 0)
    if (!manualId || busyKey.value) return
    uni.showModal({
        title: '完成线下档期',
        content: `确认将「${item.service_name}」标记为已完成？`,
        confirmText: '确认',
        cancelText: '取消',
        success: async (r) => {
            if (!r.confirm) return
            busyKey.value = `manual-done:${manualId}`
            try {
                await staffManualScheduleComplete({
                    manual_schedule_id: manualId,
                    version: Number(item.version ?? 0)
                })
                toast.success('已完成该线下档期')
                await fetchPanel()
            } catch (e: any) {
                toast.error(String(e?.message || '操作失败，请稍后重试'))
            } finally {
                busyKey.value = ''
            }
        }
    })
}

function handleManualPoster(item: StaffServiceDayManualSchedule): void {
    const manualId = Number(item.manual_schedule_id || item.id || 0)
    if (!manualId) return
    void handlePosterAction(
        `poster-manual:${manualId}`,
        { manual_schedule_id: manualId },
        item.confirm_letter
    )
}

function goOrderDetail(orderId: number): void {
    navigate({
        path: '/packages/pkg-staff/pages/order-detail/order-detail',
        query: { id: orderId }
    })
}

function goCopyTemplates(orderId: number, manualScheduleId: number): void {
    const query: Record<string, string | number> = {}
    if (orderId > 0) query.order_id = orderId
    if (manualScheduleId > 0) query.manual_schedule_id = manualScheduleId
    navigate({
        path: '/packages/pkg-staff/pages/copy-templates/copy-templates',
        query
    })
}

function goCoupleDetail(sourceType: 'order' | 'manual', targetId: number): void {
    if (!targetId) return
    navigate({
        path: '/packages/pkg-staff/pages/couple-detail/couple-detail',
        query: { source: sourceType, id: targetId }
    })
}

function goSchedule(): void {
    navigate('/packages/pkg-staff/pages/schedule/schedule')
}

function onPrimary(): void {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        reload()
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

async function init(forceGuard = false): Promise<void> {
    if (!(await verify(forceGuard))) return
    await fetchPanel()
    hasLoadedOnce.value = true
}

onLoad((options?: Record<string, any>) => {
    firstShow = true
    const paramDate = String(options?.date || '').trim().slice(0, 10)
    if (/^\d{4}-\d{2}-\d{2}$/.test(paramDate)) {
        activeDate.value = paramDate
    }
})

onShow(() => {
    if (firstShow) {
        firstShow = false
        void init()
        return
    }
    if (hasLoadedOnce.value) {
        void init(true)
    }
})

function onRefresh(done: () => void) {
    if (!guardAllowed.value) {
        done()
        return
    }
    Promise.resolve(fetchPanel()).finally(done)
}
</script>

<style lang="scss" scoped>
.battle-page {
    padding-bottom: calc(var(--sp-5) + var(--safe-bottom));
}

.date-strip {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    padding-top: var(--sp-3);
    padding-bottom: var(--sp-3);

    &__scroll {
        flex: 1;
        min-width: 0;
        white-space: nowrap;
    }

    &__row {
        display: inline-flex;
        align-items: center;
        gap: var(--sp-2);
    }
}

/* 日期胶囊与选日期按钮：视觉仍是小胶囊，热区直接给足 88rpx */
.date-chip {
    display: inline-flex;
    align-items: center;
    gap: var(--sp-1);
    min-height: 88rpx;
    padding: 0 var(--sp-3);
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);
    box-sizing: border-box;
    transition:
        background-color var(--dur-fast) var(--ease-out),
        border-color var(--dur-fast) var(--ease-out),
        transform var(--dur-press) var(--ease-out);

    &.is-pressed {
        transform: scale(0.97);
    }

    &.is-active {
        border-color: var(--c-active-border);
        background: var(--c-active-bg);

        .date-chip__day,
        .date-chip__count {
            color: var(--c-active-fg);
        }
    }

    &__day {
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        font-variant-numeric: tabular-nums;
    }

    &__count {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
        font-variant-numeric: tabular-nums;
    }
}

.date-picker-btn {
    display: inline-flex;
    align-items: center;
    gap: var(--sp-1);
    min-height: 88rpx;
    padding: 0 var(--sp-3);
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);
    color: var(--c-ink);
    flex-shrink: 0;
    box-sizing: border-box;
    transition: transform var(--dur-press) var(--ease-out);

    &.is-pressed {
        transform: scale(0.97);
    }

    &__icon {
        color: inherit;
    }

    &__text {
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        color: inherit;
    }
}

.battle-body {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
}

.hero-card {
    padding: var(--sp-4);
    border: 1rpx solid var(--c-card-border);
    border-radius: var(--r-lg);
    background: linear-gradient(135deg, var(--c-dark-1), var(--c-dark-2));
    box-shadow: var(--shadow-card);
    color: var(--c-on-dark);
    display: flex;
    flex-direction: column;
    gap: var(--sp-4);

    &__top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: var(--sp-3);
    }

    &__date-wrap {
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__eyebrow {
        font-family: var(--font-serif);
        font-size: var(--fs-18);
        letter-spacing: 2rpx;
        color: var(--c-brand-soft);
    }

    &__title-row {
        display: flex;
        align-items: baseline;
        gap: var(--sp-2);
    }

    &__date {
        font-family: var(--font-serif);
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        color: var(--c-on-dark);
        font-variant-numeric: tabular-nums;
    }

    &__week {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
    }

    &__total {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    &__total-num {
        font-family: var(--font-serif);
        font-size: var(--fs-2xl);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-none);
        color: var(--c-brand-soft);
        font-variant-numeric: tabular-nums;
    }

    &__total-unit {
        margin-top: var(--sp-1);
        font-size: var(--fs-20);
        color: var(--c-on-dark-2);
    }

    &__stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        padding-top: var(--sp-3);
        border-top: 1rpx solid var(--c-line-strong);
    }
}

.hero-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--sp-1);

    &__num {
        font-family: var(--font-serif);
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-on-dark);
        font-variant-numeric: tabular-nums;
    }

    &__label {
        font-size: var(--fs-20);
        color: var(--c-on-dark-2);
    }
}

.checklist-card {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.checklist {
    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__progress {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
        font-variant-numeric: tabular-nums;
    }
}

.check-row {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    padding: var(--sp-2) 0;

    &--divided {
        border-top: 1rpx solid var(--c-line);
    }

    &__box {
        width: 34rpx;
        height: 34rpx;
        border-radius: var(--r-xs);
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: var(--c-ink-inverse);
    }

    &__icon {
        color: inherit;
    }

    &__label {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
    }

    &.is-checked {
        .check-row__box {
            border-color: var(--c-success);
            background: var(--c-success);
        }

        .check-row__label {
            color: var(--c-ink-3);
            text-decoration: line-through;
        }
    }
}

.order-list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
}

.order-card {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);

    &__head {        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__title-wrap {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__pkg {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__sn {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        font-variant-numeric: tabular-nums;
    }

    &__tags {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: var(--sp-1);
    }
}

.pkg-box {
    padding: var(--sp-2) var(--sp-3);
    border-radius: var(--r-sm);
    background: var(--c-surface-2);
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);

    &__row {
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__name {
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__addons {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }
}

.info-list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.info-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--sp-2);

    &__label {
        width: 144rpx;
        flex-shrink: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__right {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: var(--sp-2);
    }

    &__value {
        flex: 1;
        min-width: 0;
        text-align: right;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
        overflow-wrap: anywhere;
    }
}

.mini-action {
    padding: 4rpx var(--sp-2);
    border-radius: var(--r-pill);
    background: var(--c-brand-bg);
    border: 1rpx solid var(--c-primary-border);
    flex-shrink: 0;

    &__text {
        font-size: var(--fs-20);
        color: var(--c-brand-text);
        font-weight: var(--fw-medium);
    }
}

.sub-block {
    padding-top: var(--sp-2);
    border-top: 1rpx solid var(--c-line);
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__title {
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__meta {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }

    &__empty {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.teammates {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-1);
}

.teammate-pill {
    padding: var(--sp-1) var(--sp-2);
    border-radius: var(--r-xs);
    background: var(--c-neutral-bg);
    border: 1rpx solid var(--c-neutral-border);

    &__text {
        font-size: var(--fs-20);
        color: var(--c-ink-2);
    }
}

.q-list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);
}

.q-item {
    padding: var(--sp-2);
    border-radius: var(--r-sm);
    background: var(--c-surface-2);
    display: flex;
    flex-direction: column;
    gap: 4rpx;

    &.is-warning {
        background: var(--c-warning-bg);
        border: 1rpx solid var(--c-warning-border);

        .q-item__title,
        .q-item__answer {
            color: var(--c-warning);
        }
    }

    &__title {
        font-size: var(--fs-20);
        font-weight: var(--fw-medium);
        color: var(--c-ink-3);
    }

    &__answer {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink);
        overflow-wrap: anywhere;
    }
}

.card-actions {
    padding-top: var(--sp-2);
    border-top: 1rpx solid var(--c-line);
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--sp-2);
}

@media (prefers-reduced-motion: reduce) {
    .date-chip,
    .date-picker-btn {
        transition: none;

        &.is-pressed {
            transform: none;
        }
    }
}
</style>
