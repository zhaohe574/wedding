<template>
    <!--
        18 订单售后（设计稿 3784–3858）
        资格全部来自真数据，前端不自己判定：
        · order.getOrderDetailV2 → can_user_refund / refundable_amount / paid_amount / order_status
        · order_change.checkCanChange → can_change / message（message 原样上墙，不改写）
        客服入口恒可用（兜底）。
    -->
    <page-shell bg="page" title="售后与投诉">
        <view v-if="loading" class="pad sk">
            <base-skeleton :rows="3" />
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
            @retry="load"
        />

        <view v-else class="as">
            <template v-if="isOrderMode">
                <!-- 先亮订单再给选项：售后最容易出错的是申请错单 -->
                <view class="ord">
                    <image
                        v-if="order.staffAvatar"
                        class="ord__av"
                        :src="order.staffAvatar"
                        mode="aspectFill"
                    />
                    <view v-else class="ord__av ord__av--text">
                        {{ (order.staffName || '订').slice(0, 1) }}
                    </view>
                    <view class="ord__m">
                        <text class="ord__title">{{ orderTitle }}</text>
                        <text class="ord__sub">{{ orderSub }}</text>
                    </view>
                    <view v-if="order.paidAmount !== ''" class="ord__r">
                        <text class="ord__r-label">已付</text>
                        <text class="ord__r-amount">¥{{ order.paidAmount }}</text>
                    </view>
                </view>

                <!-- 三条诉求：改期 / 退款 / 客服，按发生频率排序，客服最后兜底 -->
                <base-card class="blk" :flush="true">
                    <view
                        v-for="(e, i) in entries"
                        :key="e.key"
                        :hover-class="e.enabled ? 'is-pressed' : 'none'"
                        :hover-start-time="20"
                        :hover-stay-time="70"
                        :hover-stop-propagation="true"
                        class="entry"
                        :class="{ 'entry--last': i === entries.length - 1, 'entry--off': !e.enabled }"
                        :aria-role="e.enabled ? 'button' : 'text'"
                        :aria-label="`${e.title}${e.enabled ? '' : '，不可用'}`"
                        @tap="onEntry(e)"
                    >
                        <view class="entry__ic" :class="`entry__ic--${e.key}`">
                            <tn-icon :name="e.icon" :size="30" />
                        </view>
                        <view class="entry__t">
                            <view class="entry__head">
                                <text class="entry__title">{{ e.title }}</text>
                                <text v-if="e.badge" class="entry__badge">{{ e.badge }}</text>
                            </view>
                            <!-- 不可用原因原样用服务端 message，不改写 -->
                            <text class="entry__desc">{{ e.desc }}</text>
                        </view>
                        <tn-icon v-if="e.enabled" name="right" :size="22" />
                    </view>
                </base-card>

                <!-- 处理中：已提交的申请，避免用户重复提交 -->
                <view v-if="pending" class="rec">
                    <view class="rec__h">
                        <view class="rec__title-wrap">
                            <tn-icon name="tips" :size="26" class="rec__ic" />
                            <text class="rec__title">{{ pending.title }}</text>
                        </view>
                        <text class="rec__time">{{ pending.time }}</text>
                    </view>
                    <text class="rec__desc">{{ pending.desc }}</text>
                </view>
            </template>

            <!-- 全局「售后与投诉」中心模式（无 orderId 时展示） -->
            <template v-else>
                <view class="hub">
                    <view class="hub__top">
                        <text class="hub__eyebrow">AFTER-SALE SERVICE</text>
                        <text v-if="hubStatusPillText" class="hub__pill">{{
                            hubStatusPillText
                        }}</text>
                    </view>
                    <view class="hub__head">
                        <text class="hub__title">售后与投诉</text>
                        <text class="hub__desc">{{ hubSummaryText }}</text>
                    </view>
                    <view class="hub__line" />
                    <view class="hub__metrics">
                        <view
                            hover-class="is-pressed"
                            :hover-start-time="20"
                            :hover-stay-time="70"
                            :hover-stop-propagation="true"
                            class="hub__metric"
                            aria-role="button"
                            aria-label="查看待办工单"
                            @tap="
                                goPath('/packages/pkg-service/pages/aftersale/ticket-list', {
                                    status: 'unfinished'
                                })
                            "
                        >
                            <text class="hub__num">{{
                                hubStatsLoaded ? hubStats.ticketUnfinished : '—'
                            }}</text>
                            <text class="hub__label">待办工单</text>
                        </view>
                        <view class="hub__divider" />
                        <view
                            hover-class="is-pressed"
                            :hover-start-time="20"
                            :hover-stay-time="70"
                            :hover-stop-propagation="true"
                            class="hub__metric"
                            aria-role="button"
                            aria-label="查看处理中投诉"
                            @tap="
                                goPath('/packages/pkg-service/pages/aftersale/complaint-list', {
                                    status: 'unfinished'
                                })
                            "
                        >
                            <text class="hub__num">{{
                                hubStatsLoaded ? hubStats.complaintUnfinished : '—'
                            }}</text>
                            <text class="hub__label">处理中投诉</text>
                        </view>
                        <view class="hub__divider" />
                        <view
                            hover-class="is-pressed"
                            :hover-start-time="20"
                            :hover-stay-time="70"
                            :hover-stop-propagation="true"
                            class="hub__metric"
                            aria-role="button"
                            aria-label="查看待填回访"
                            @tap="goPath('/packages/pkg-service/pages/aftersale/callback-list')"
                        >
                            <text class="hub__num">{{
                                hubStatsLoaded ? hubStats.callbackPending : '—'
                            }}</text>
                            <text class="hub__label">待填回访</text>
                        </view>
                    </view>
                </view>

                <!-- O9：统计失败就地轻提示 + 重试入口，不整页错误、不伪装成 0 -->
                <view v-if="hubStatsError" class="stats-tip" aria-live="polite">
                    <text class="stats-tip__text">{{ hubStatsError }}</text>
                    <base-button
                        :text="FB.ACTION_RETRY"
                        size="sm"
                        variant="text"
                        :block="false"
                        @click="loadHubStats"
                    />
                </view>

                <!-- 双核心快捷通道 -->
                <view class="quick-grid">
                    <view
                        hover-class="is-pressed"
                        :hover-start-time="20"
                        :hover-stay-time="70"
                        :hover-stop-propagation="true"
                        class="quick-card"
                        aria-role="button"
                        aria-label="前往我的订单发起改期或退款"
                        @tap="goPath('/pages/order/order')"
                    >
                        <view class="quick-card__top">
                            <view class="quick-card__ic quick-card__ic--brand">
                                <tn-icon name="calendar" :size="30" />
                            </view>
                            <view class="quick-card__top-r">
                                <text class="quick-card__tag quick-card__tag--brand">订单售后</text>
                                <tn-icon name="right" :size="20" class="quick-card__go" />
                            </view>
                        </view>
                        <text class="quick-card__title">改期 / 退款</text>
                        <text class="quick-card__desc">选择订单后发起</text>
                    </view>

                    <view
                        hover-class="is-pressed"
                        :hover-start-time="20"
                        :hover-stay-time="70"
                        :hover-stop-propagation="true"
                        class="quick-card"
                        aria-role="button"
                        aria-label="发起投诉"
                        @tap="goPath('/packages/pkg-service/pages/aftersale/complaint-create')"
                    >
                        <view class="quick-card__top">
                            <view class="quick-card__ic quick-card__ic--danger">
                                <tn-icon name="edit" :size="30" />
                            </view>
                            <view class="quick-card__top-r">
                                <text class="quick-card__tag quick-card__tag--danger">平台督办</text>
                                <tn-icon name="right" :size="20" class="quick-card__go" />
                            </view>
                        </view>
                        <text class="quick-card__title">发起投诉</text>
                        <text class="quick-card__desc">履约与服务态度问题</text>
                    </view>
                </view>

                <!-- 服务记录分组 -->
                <view class="sec-group">
                    <section-header eyebrow="RECORDS" title="服务记录" flush />
                    <base-card class="blk" :flush="true">
                        <view
                            v-for="(e, i) in hubRecordEntries"
                            :key="e.key"
                            hover-class="is-pressed"
                            :hover-start-time="20"
                            :hover-stay-time="70"
                            :hover-stop-propagation="true"
                            class="entry"
                            :class="{ 'entry--last': i === hubRecordEntries.length - 1 }"
                            aria-role="button"
                            :aria-label="e.title"
                            @tap="onHubEntry(e)"
                        >
                            <view class="entry__ic" :class="`entry__ic--${e.tone}`">
                                <tn-icon :name="e.icon" :size="30" />
                            </view>
                            <view class="entry__t">
                                <view class="entry__head">
                                    <text class="entry__title">{{ e.title }}</text>
                                    <text
                                        v-if="e.badge"
                                        class="entry__badge"
                                        :class="`entry__badge--${e.tone}`"
                                        >{{ e.badge }}</text
                                    >
                                </view>
                                <text class="entry__desc">{{ e.desc }}</text>
                            </view>
                            <tn-icon name="right" :size="22" />
                        </view>
                    </base-card>
                </view>

                <!-- 专属人工客服卡 -->
                <view
                    hover-class="is-pressed"
                    :hover-start-time="20"
                    :hover-stay-time="70"
                    :hover-stop-propagation="true"
                    class="support-card"
                    aria-role="button"
                    aria-label="联系专属客服管家"
                    @tap="goCustomerService({ scene: 'aftersale' })"
                >
                    <view class="support-card__ic">
                        <tn-icon name="headset" :size="30" />
                    </view>
                    <view class="support-card__body">
                        <text class="support-card__title">联系专属客服管家</text>
                        <text class="support-card__desc"
                            >排期协调、款项说明与售后疑问一对一响应</text
                        >
                    </view>
                    <view class="support-card__btn">
                        <text class="support-card__btn-text">去咨询</text>
                        <tn-icon name="right" :size="18" />
                    </view>
                </view>
            </template>

            <!-- FAQ：只给问题，答案就地展开 -->
            <base-card class="blk">
                <section-header eyebrow="FAQ" title="常见问题" flush />
                <view class="faq">
                    <view v-for="(f, i) in FAQ" :key="f.q" class="faq__row">
                        <view
                            hover-class="is-pressed"
                            :hover-start-time="20"
                            :hover-stay-time="70"
                            :hover-stop-propagation="true"
                            class="faq__head"
                            :class="{ 'faq__head--last': i === FAQ.length - 1 && openFaq !== i }"
                            aria-role="button"
                            :aria-label="`${f.q}，${openFaq === i ? '收起' : '展开'}`"
                            @tap="toggleFaq(i)"
                        >
                            <text class="faq__q">{{ f.q }}</text>
                            <tn-icon :name="openFaq === i ? 'up' : 'down'" :size="22" />
                        </view>
                        <text v-if="openFaq === i" class="faq__a">{{ f.a }}</text>
                    </view>
                </view>
            </base-card>

            <text class="priv">改期与退款均由专属顾问人工审核，审核结果通过服务号通知。</text>

            <view
                hover-class="is-pressed"
                :hover-start-time="20"
                :hover-stay-time="70"
                :hover-stop-propagation="true"
                class="end"
                aria-role="button"
                aria-label="查看售后服务规则"
                @tap="goRule"
            >
                <text class="end__text"
                    >查看完整<text class="end__name">《售后服务规则》</text></text
                >
                <tn-icon name="right" :size="18" />
            </view>
        </view>
    </page-shell>
    <app-toast />
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { getOrderDetail, getOrderDetailV2 } from '@/api/order'
import { getAfterSaleStatistics } from '@/api/aftersale'
import { checkCanChange, getChangeList } from '@/api/change'
import { ApiError } from '@/request/error'
import { navigate } from '@/utils/link'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { FB } from '@/constants/feedback'
import { report } from '@/platform/report'

/** ✘ G-17：FAQ 无接口，三条按现有处理口径写，不含未定义的费用比例 */
const FAQ: Array<{ q: string; a: string }> = [
    {
        q: '改期后已付的定金还能用吗',
        a: '改期成功后已付款项会转到新档期，不会重复收款。若新档期价格不同，差额由顾问确认后告知，不会直接从定金里扣。'
    },
    {
        q: '审核期间可以撤销申请吗',
        a: '可以。联系专属客服说明需要撤销即可；在顾问确认新档期之前，原档期一直为你保留，不会二次售出。'
    },
    {
        q: '退款多久能到账',
        a: '可退金额按《售后服务规则》计算后原路退回。具体到账时间以支付渠道与审核结果为准，审核结果会通过服务号通知。'
    }
]

interface EntryItem {
    key: 'change' | 'refund' | 'support'
    icon: string
    title: string
    desc: string
    enabled: boolean
    badge?: string
}

interface HubEntryItem {
    key: 'ticket' | 'complaint' | 'callback'
    icon: string
    title: string
    desc: string
    path: string
    tone: 'brand' | 'danger' | 'success'
    query?: Record<string, any>
    badge?: string
}

const loading = ref(true)
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
const error = computed(() => !!rawError.value)

const orderId = ref(0)
const isOrderMode = computed(() => orderId.value > 0)
const openFaq = ref(-1)
let handledRouteAction = false

const hubStatsLoaded = ref(false)
const hubStatsError = ref('')
const hubStats = reactive({
    ticketTotal: 0,
    ticketUnfinished: 0,
    complaintTotal: 0,
    complaintUnfinished: 0,
    callbackTotal: 0,
    callbackPending: 0,
    unfinishedTotal: 0
})

const hubStatusPillText = computed(() => {
    if (!hubStatsLoaded.value) return ''
    return hubStats.unfinishedTotal > 0 ? `${hubStats.unfinishedTotal} 项推进中` : '全部已办结'
})

const hubSummaryText = computed(() => {
    if (!hubStatsLoaded.value) return '工单、投诉与回访进度'
    return hubStats.unfinishedTotal > 0
        ? `当前有 ${hubStats.unfinishedTotal} 项正在推进`
        : '所有事项均已办结'
})

const hubRecordEntries = computed<HubEntryItem[]>(() => [
    {
        key: 'ticket',
        icon: 'order',
        title: '服务工单',
        tone: 'brand',
        badge:
            hubStatsLoaded.value && hubStats.ticketUnfinished > 0
                ? `${hubStats.ticketUnfinished} 条待办`
                : undefined,
        desc:
            hubStatsLoaded.value && hubStats.ticketTotal > 0
                ? `累计 ${hubStats.ticketTotal} 条工单 · 查看进度`
                : '素材交付、流程协调、服务咨询',
        path: '/packages/pkg-service/pages/aftersale/ticket-list'
    },
    {
        key: 'complaint',
        icon: 'tips',
        title: '投诉记录',
        tone: 'danger',
        badge:
            hubStatsLoaded.value && hubStats.complaintUnfinished > 0
                ? `${hubStats.complaintUnfinished} 条跟进中`
                : undefined,
        desc:
            hubStatsLoaded.value && hubStats.complaintTotal > 0
                ? `累计 ${hubStats.complaintTotal} 条投诉 · 查看进展`
                : '服务态度、履约偏差、沟通问题',
        path: '/packages/pkg-service/pages/aftersale/complaint-list'
    },
    {
        key: 'callback',
        icon: 'chat',
        title: '服务回访',
        tone: 'success',
        badge:
            hubStatsLoaded.value && hubStats.callbackPending > 0
                ? `${hubStats.callbackPending} 份待填`
                : undefined,
        desc:
            hubStatsLoaded.value && hubStats.callbackPending > 0
                ? '有回访问卷待填写'
                : '完成后可帮助改进服务',
        path: '/packages/pkg-service/pages/aftersale/callback-list'
    }
])

const order = reactive({
    orderSn: '',
    statusDesc: '',
    status: -1,
    serviceDate: '',
    staffName: '',
    staffAvatar: '',
    packageName: '',
    paidAmount: '',
    /** 服务端可退金额；'' 表示字段缺失（不显示，不补 0） */
    refundable: '' as string,
    canRefund: false
})

const change = reactive<{ can_change: boolean | null; message: string }>({
    can_change: null,
    message: ''
})

const pending = ref<{
    title: string
    time: string
    desc: string
} | null>(null)

const orderTitle = computed(() => {
    const name = order.staffName ? `${order.staffName} · ` : ''
    const pkg = order.packageName || ''
    if (name && pkg) return `${order.staffName} · ${pkg}`
    if (name) return order.staffName
    return order.orderSn || '订单'
})

const orderSub = computed(() => {
    const d = order.serviceDate
    const dateText =
        d.length >= 10
            ? `${d.replace(/-/g, '.')} 周${'日一二三四五六'[new Date(`${d}T00:00:00`).getDay()]}`
            : ''
    return [dateText, order.orderSn ? `订单 ${order.orderSn}` : ''].filter(Boolean).join(' · ')
})

/** 改期入口：can_change 明确为假 → 置灰并原样显示 message */
const changeEnabled = computed(() => {
    if (pending.value) return false // 已有在审申请，避免重复提交
    if (change.can_change === null) return true // 字段缺失时不禁用，交由提交页拦
    return Boolean(change.can_change)
})

/** 退款入口：can_user_refund 为真 且 可退金额 > 0 且金额字段存在 */
const refundEnabled = computed(() => {
    if (!order.canRefund) return false
    if (order.refundable === '') return false
    return Number(order.refundable) > 0
})

const entries = computed<EntryItem[]>(() => [
    {
        key: 'change',
        icon: 'calendar',
        title: '改期申请',
        badge: pending.value ? '处理中' : undefined,
        desc: changeEnabled.value
            ? '更换婚礼日期或主持时段，差价多退少补'
            : change.message ||
              (pending.value ? '你已有一笔改期申请在处理中' : '当前订单暂不支持改期，请联系客服'),
        enabled: changeEnabled.value
    },
    {
        key: 'refund',
        icon: 'receipt',
        title: '退款申请',
        desc: refundEnabled.value
            ? order.refundable !== ''
                ? `按《售后服务规则》计算可退金额 ¥${order.refundable}，原路退回`
                : '按《售后服务规则》计算可退金额，原路退回'
            : '当前订单状态不支持自助退款，请联系客服',
        enabled: refundEnabled.value
    },
    {
        key: 'support',
        icon: 'headset',
        title: '人工客服',
        desc: '找到专属客服管家，排期与款项都能问',
        enabled: true
    }
])

/** 防御式读取：退款两项不在 OrderDetailResponse 的公开类型里，按可选读，缺了当没有 */
function pickRefund(res: any) {
    const pay = res?.pay || {}
    const canRaw = pay.can_user_refund ?? res?.can_user_refund ?? res?.order?.can_user_refund
    const amountRaw =
        pay.refundable_amount ?? res?.refundable_amount ?? res?.order?.refundable_amount
    order.canRefund = canRaw !== undefined && canRaw !== null && Number(canRaw) === 1
    order.refundable =
        amountRaw === undefined || amountRaw === null || amountRaw === '' ? '' : String(amountRaw)
}

async function fetchOrderDetailWithFallback(id: number): Promise<any> {
    try {
        return await getOrderDetailV2(id)
    } catch {
        const legacy: any = await getOrderDetail({ id })
        if (!legacy || (!legacy.id && !legacy.order)) {
            throw new ApiError({
                code: 0,
                message: '订单不存在或已删除',
                errorCode: 'ORDER_NOT_FOUND',
                retryStrategy: 'none'
            })
        }
        return legacy
    }
}

async function load() {
    if (!isOrderMode.value) {
        loading.value = false
        rawError.value = null
        await loadHubStats()
        return
    }

    loading.value = true
    rawError.value = null
    try {
        const [detailRes, changeRes] = await Promise.all([
            fetchOrderDetailWithFallback(orderId.value),
            checkCanChange({ order_id: orderId.value }).catch(() => null)
        ])

        const res: any = detailRes || {}
        const o = res.order || res || {}
        if (!o || (!o.id && !o.order_sn)) {
            throw new ApiError({
                code: 0,
                message: '订单不存在或已删除',
                errorCode: 'ORDER_NOT_FOUND',
                retryStrategy: 'none'
            })
        }

        const item = (res.items || [])[0] || {}
        order.orderSn = o.order_sn || ''
        order.status = typeof o.order_status === 'number' ? o.order_status : -1
        order.statusDesc = o.order_status_desc || ''
        order.serviceDate = o.service_date || ''
        order.staffName = item?.staff?.name || item?.staff_name || o.staff_name || ''
        order.staffAvatar = item?.staff?.avatar || item?.staff_avatar || o.staff_avatar || ''
        order.packageName = item?.package?.name || item?.package_name || o.package_name || ''
        order.paidAmount =
            o.paid_amount === undefined || o.paid_amount === null || o.paid_amount === ''
                ? ''
                : String(o.paid_amount)
        pickRefund(res)

        // v2.order 不含 refundable_amount，若 actions.apply_refund 为真且未取到可退金额，则静默补拉 v1 detail
        if (
            res?.order &&
            res?.actions?.apply_refund === true &&
            order.refundable === ''
        ) {
            const legacy: any = await getOrderDetail({ id: orderId.value }).catch(() => null)
            if (legacy) {
                pickRefund(legacy)
            }
        }

        const c: any = changeRes || {}
        change.message = String(c.message || c.reason || '')
        const can = c.can_change ?? c.canChange ?? c.enabled
        change.can_change =
            can === undefined || can === null ? null : Number(can) === 1 || can === true

        // 处理中卡片：非关键路径，失败只隐藏，不影响两个入口
        loadPending()
    } catch (e) {
        rawError.value = e
    } finally {
        loading.value = false
    }
}

async function loadHubStats() {
    try {
        const res: any = await getAfterSaleStatistics()
        const data = res?.ticket ? res : res?.data || res || {}
        hubStats.ticketTotal = Number(data?.ticket?.total ?? 0)
        hubStats.ticketUnfinished = Number(data?.ticket?.unfinished ?? data?.ticket?.pending ?? 0)
        hubStats.complaintTotal = Number(data?.complaint?.total ?? 0)
        hubStats.complaintUnfinished = Number(
            data?.complaint?.unfinished ?? data?.complaint?.pending ?? 0
        )
        hubStats.callbackTotal = Number(data?.callback?.total ?? 0)
        hubStats.callbackPending = Number(data?.callback?.pending ?? data?.callback?.unfinished ?? 0)
        hubStats.unfinishedTotal = Number(
            data?.unfinished_total ??
                hubStats.ticketUnfinished + hubStats.complaintUnfinished + hubStats.callbackPending
        )
        hubStatsLoaded.value = true
        hubStatsError.value = ''
    } catch {
        // 保留上一次有效值（若有），未加载成功不伪装成 0，仅给就地提示 + 重试入口
        hubStatsError.value = FB.ERR_SERVER
    }
}

async function loadPending() {
    try {
        const res: any = await getChangeList({ order_id: orderId.value, page: 1, page_size: 3 })
        const arr: any[] = Array.isArray(res) ? res : res?.lists || res?.data || []
        // 只展示待审核单据（契约 OrderChangeStatus.PENDING = 0；后端字段 change_status，历史误读 status/1 双重错误）
        const one = (arr || []).find((i) => Number(i.change_status) === 0)
        if (!one) {
            pending.value = null
            return
        }
        pending.value = {
            title: `${one.change_type === 1 || !one.change_type ? '改期申请' : '变更申请'} · ${
                one.change_status_desc || '待审核'
            }`,
            time: one.create_time
                ? `${String(one.create_time).slice(5, 10).replace('-', '.')} 提交`
                : '',
            desc: one.reason || '专属顾问会在 24 小时内与你确认档期与差价。'
        }
    } catch {
        pending.value = null
        report('api_error', { endpoint: 'order_change.lists', message: '售后页处理中单据加载失败' })
    }
}

function toggleFaq(i: number) {
    openFaq.value = openFaq.value === i ? -1 : i
}

function goPath(path: string, query?: Record<string, any>) {
    navigate({ path, query })
}

function goCustomerService(query?: Record<string, any>) {
    const pages = getCurrentPages()
    const prevRoute = pages.length >= 2 ? String((pages[pages.length - 2] as any)?.route || '') : ''
    if (prevRoute === 'pages/customer-service/customer-service' && !query?.order_id) {
        uni.navigateBack()
        return
    }
    navigate({ path: '/pages/customer-service/customer-service', query })
}

function onHubEntry(e: HubEntryItem) {
    goPath(e.path, e.query)
}

function onEntry(e: EntryItem) {
    if (!e.enabled) return
    if (e.key === 'support') {
        goCustomerService({ scene: 'aftersale', order_id: orderId.value })
        return
    }
    navigate({
        path: '/packages/pkg-service/pages/aftersale/ticket-create',
        query: { orderId: orderId.value, type: e.key === 'refund' ? 'refund' : 'change' }
    })
}

function goRule() {
    navigate({ path: '/pages/agreement/agreement', query: { type: 'aftersale' } })
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'login') {
        navigate({ path: '/pages/login/login' })
        return
    }
    if (kind === 'back') {
        uni.navigateBack()
        return
    }
    if (kind === 'contact') {
        goCustomerService(
            orderId.value ? { scene: 'aftersale', order_id: orderId.value } : { scene: 'aftersale' }
        )
        return
    }
    load()
}

onLoad((options: any) => {
    orderId.value = Number(options?.orderId || options?.order_id || options?.id || 0) || 0
    if (!orderId.value && options?.action === 'submit_complaint' && !handledRouteAction) {
        handledRouteAction = true
        navigate({ path: '/packages/pkg-service/pages/aftersale/complaint-create' })
    }
})

/** 从 23 返回时可能刚提交过申请，重新拉一次（含处理中卡片或中心统计） */
onShow(() => {
    if (!isOrderMode.value) {
        loading.value = false
        rawError.value = null
        loadHubStats()
        return
    }
    load()
})
</script>

<style lang="scss" scoped>
.pad {
    padding: 0 var(--page-gutter);
}
.sk {
    padding-top: var(--sp-3);
}
.as {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding: var(--sp-3) var(--page-gutter) calc(var(--sp-5) + var(--safe-bottom));
}

/* ---------- 全局中心概览卡 ---------- */
.hub {
    padding: var(--sp-4);
    border-radius: var(--r-md);
    background: linear-gradient(135deg, var(--c-dark-1), var(--c-dark-2));
    color: var(--c-on-dark);

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-bottom: var(--sp-2);
    }

    &__eyebrow {
        font-family: var(--font-serif);
        font-size: var(--fs-20);
        letter-spacing: 0.08em;
        color: var(--c-brand-soft);
    }

    &__pill {
        flex: none;
        padding: 4rpx var(--sp-2);
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-brand-soft);
        font-size: var(--fs-xs);
        line-height: var(--lh-tight);
        color: var(--c-brand-soft);
    }

    &__head {
        margin-bottom: var(--sp-3);
    }

    &__title {
        display: block;
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-on-dark);
    }

    &__desc {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-on-dark-2);
        overflow-wrap: anywhere;
    }

    &__line {
        height: 1rpx;
        background: var(--c-on-dark-2);
        opacity: 0.2;
        margin-bottom: var(--sp-3);
    }

    &__metrics {
        display: flex;
        align-items: center;
    }

    &__metric {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--sp-1);
        padding: var(--sp-1) 0;
    }

    &__divider {
        width: 1rpx;
        height: 56rpx;
        background: var(--c-on-dark-2);
        opacity: 0.25;
    }

    &__num {
        font-family: var(--font-serif);
        font-size: var(--fs-2xl);
        font-weight: var(--fw-medium);
        line-height: var(--lh-tight);
        color: var(--c-brand-soft);
    }

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
    }
}

.stats-tip {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    padding: var(--sp-2) var(--sp-3);
    border-radius: var(--r-sm);
    background: var(--c-warning-bg);
    border: 1rpx solid var(--c-warning-border);

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-warning);
    }
}

/* ---------- 双核心快捷通道 ---------- */
.quick-grid {
    display: flex;
    gap: var(--sp-3);
}

.quick-card {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    padding: var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);
    box-sizing: border-box;

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-1);
        margin-bottom: var(--sp-3);
    }

    &__ic {
        width: 60rpx;
        height: 60rpx;
        border-radius: var(--r-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1rpx solid var(--c-line);

        &--brand {
            background: var(--c-brand-bg);
            border-color: var(--c-primary-border);
            color: var(--c-brand-text);
        }

        &--danger {
            background: var(--c-danger-bg);
            border-color: var(--c-danger-border);
            color: var(--c-danger);
        }
    }

    &__tag {
        padding: 4rpx var(--sp-1);
        border-radius: var(--r-xs);
        font-size: var(--fs-20);
        line-height: var(--lh-none);

        &--brand {
            color: var(--c-brand-text);
            background: var(--c-brand-bg);
            border: 1rpx solid var(--c-primary-border);
        }

        &--danger {
            color: var(--c-danger);
            background: var(--c-danger-bg);
            border: 1rpx solid var(--c-danger-border);
        }
    }

    &__title {
        display: block;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__desc {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        flex: 1;
    }

    &__top-r {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        flex: none;
    }

    &__go {
        flex: none;
        color: var(--c-ink-3);
    }
}

.sec-group {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
    padding-top: var(--sp-1);
}

/* ---------- 专属人工客服卡 ---------- */
.support-card {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    padding: var(--sp-3) var(--sp-4);
    border-radius: var(--r-md);
    background: var(--c-brand-bg);
    border: 1rpx solid var(--c-primary-border);

    &__ic {
        width: 64rpx;
        height: 64rpx;
        border-radius: var(--r-sm);
        flex: none;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--c-dark-1);
        color: var(--c-brand-soft);
    }

    &__body {
        flex: 1;
        min-width: 0;
    }

    &__title {
        display: block;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__desc {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
    }

    &__btn {
        flex: none;
        display: flex;
        align-items: center;
        gap: 4rpx;
        padding: var(--sp-1) var(--sp-3);
        border-radius: var(--r-pill);
        background: var(--c-action-bg);
        color: var(--c-action-fg);
    }

    &__btn-text {
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        color: var(--c-action-fg);
    }
}

/* ---------- 订单摘要条 ---------- */
.ord {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    padding: var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);

    &__av {
        width: 72rpx;
        height: 72rpx;
        border-radius: var(--r-sm);
        flex: none;
        background: var(--c-neutral-bg);

        &--text {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--c-brand-bg);
            font-family: var(--font-serif);
            font-size: var(--fs-md);
            color: var(--c-brand-text);
        }
    }

    &__m {
        flex: 1;
        min-width: 0;
    }

    &__title {
        display: block;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__sub {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }

    &__r {
        flex: none;
        text-align: right;
    }

    &__r-label {
        display: block;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__r-amount {
        display: block;
        margin-top: 2rpx;
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        color: var(--c-ink);
    }
}

/* ---------- 三条入口 ---------- */
.blk {
    display: block;
    width: 100%;
}

.entry {
    /* 行尾箭头；&-title / &-desc / &-badge 各自声明了颜色 */
    color: var(--c-ink-3);
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    min-height: 128rpx;
    padding: var(--sp-3) var(--sp-4);
    border-bottom: 1rpx solid var(--c-line);

    &--last {
        border-bottom: 0;
    }

    &--off {
        opacity: 0.62;
    }

    &__ic {
        /* 入口图标，此容器只放图标 */
        color: var(--c-ink-2);
        width: 64rpx;
        height: 64rpx;
        border-radius: var(--r-sm);
        flex: none;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--c-surface-2);
        border: 1rpx solid var(--c-line);

        &--brand,
        &--change {
            background: var(--c-brand-bg);
            border-color: var(--c-primary-border);
            color: var(--c-brand-text);
        }

        &--danger {
            background: var(--c-danger-bg);
            border-color: var(--c-danger-border);
            color: var(--c-danger);
        }

        &--success {
            background: var(--c-success-bg);
            border-color: var(--c-success-border);
            color: var(--c-success);
        }

        &--refund {
            background: var(--c-warning-bg);
            border-color: var(--c-warning-border);
            color: var(--c-warning);
        }

        &--support {
            background: var(--c-surface-2);
            border-color: var(--c-line);
            color: var(--c-ink-2);
        }
    }

    &__t {
        flex: 1;
        min-width: 0;
    }

    &__head {
        /* FAQ 展开箭头；&-q 自带 --c-ink-2 */
        color: var(--c-ink-3);
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        letter-spacing: 0.02em;
        color: var(--c-ink);
    }

    &__badge {
        flex: none;
        height: 32rpx;
        padding: 0 var(--sp-1);
        border-radius: var(--r-xs);
        font-size: var(--fs-xs);
        line-height: 32rpx;
        color: var(--c-neutral);
        background: var(--c-neutral-bg);
        border: 1rpx solid var(--c-neutral-border);

        &--brand {
            color: var(--c-brand-text);
            background: var(--c-brand-bg);
            border-color: var(--c-primary-border);
        }

        &--danger {
            color: var(--c-danger);
            background: var(--c-danger-bg);
            border-color: var(--c-danger-border);
        }

        &--success {
            color: var(--c-success);
            background: var(--c-success-bg);
            border-color: var(--c-success-border);
        }
    }

    &__desc {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }
}

/* ---------- 处理中卡片 ---------- */
.rec {
    padding: var(--sp-3) var(--sp-4);
    border-radius: var(--r-md);
    background: var(--c-warning-bg);
    border: 1rpx solid var(--c-warning-border);

    &__h {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__title-wrap {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        min-width: 0;
        flex: 1;
    }

    &__ic {
        color: var(--c-warning);
        flex: none;
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-warning);
    }

    &__time {
        font-family: var(--font-serif);
        font-size: var(--fs-xs);
        color: var(--c-warning);
    }

    &__desc {
        display: block;
        margin-top: var(--sp-2);
        font-size: var(--fs-xs);
        line-height: var(--lh-loose);
        color: var(--c-warning);
    }
}

/* ---------- FAQ ---------- */
.faq {
    margin-top: var(--sp-2);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-3);
        min-height: 88rpx;
        border-bottom: 1rpx solid var(--c-line);

        &--last {
            border-bottom: 0;
        }
    }

    &__q {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
        overflow-wrap: anywhere;
    }

    &__a {
        display: block;
        padding: var(--sp-2) 0 var(--sp-3);
        font-size: var(--fs-xs);
        line-height: var(--lh-loose);
        color: var(--c-ink-3);
        border-bottom: 1rpx solid var(--c-line);
        overflow-wrap: anywhere;
    }
}

/* ---------- 页尾 ---------- */
.priv {
    display: block;
    padding-top: var(--sp-1);
    font-size: var(--fs-xs);
    line-height: var(--lh-loose);
    color: var(--c-ink-3);
}

.end {
    /* 箭头；&-text / &-name 自带颜色 */
    color: var(--c-ink-3);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--sp-1);
    padding-top: var(--sp-3);
    border-top: 1rpx solid var(--c-line);

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__name {
        color: var(--c-ink-2);
    }
}
</style>
