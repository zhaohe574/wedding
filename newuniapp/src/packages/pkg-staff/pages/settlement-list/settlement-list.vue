<template>
    <page-shell title="我的结算" bg="page" :show-back="true">
        <staff-guard
            v-if="!guardAllowed"
            :phase="guardPhase"
            :reason="guardReason"
            :error="guardError"
            @retry="onGuardRetry"
            @action="onGuardAction"
        />

        <view v-else class="page-wrap settle-page">
            <!-- 顶部深色汇总卡（统计口径明确为当前列表已加载记录） -->
            <view class="hero">
                <view class="hero__top">
                    <text class="hero__eyebrow">SETTLEMENT LEDGER</text>
                    <status-tag :label="activeTabLabel" tone="primary" size="sm" :dot="false" />
                </view>
                <view class="hero__main">
                    <text class="hero__label">已加载实结金额（{{ list.length }} 笔）</text>
                    <text class="hero__amount">{{ money(totalActualAmount) }}</text>
                </view>
                <view class="hero__stats">
                    <view class="hero-stat">
                        <text class="hero-stat__label">待确认收款</text>
                        <text class="hero-stat__value">{{ waitConfirmCount }} 笔</text>
                    </view>
                    <view class="hero-stat">
                        <text class="hero-stat__label">已结算金额</text>
                        <text class="hero-stat__value">{{ money(settledAmount) }}</text>
                    </view>
                    <view class="hero-stat">
                        <text class="hero-stat__label">待补平台</text>
                        <text class="hero-stat__value">{{ repayCount }} 笔</text>
                    </view>
                </view>
            </view>

            <!-- 状态筛选 -->
            <view class="tabs page-pad">
                <chip-group
                    :items="TABS.map((t) => ({ label: t.label, value: t.key }))"
                    :model-value="active"
                    scroll
                    @change="switchTab"
                />
            </view>

            <view v-if="loading" class="page-pad">
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
                @retry="reload"
            />

            <base-empty
                v-else-if="!list.length"
                status="empty"
                empty-icon="empty-order"
                :empty-text="emptyTitle"
                :desc="FB.EMPTY_HINT_CHANGE"
                fixed
            />

            <view v-else class="page-pad list">
                <base-card v-for="item in list" :key="item.id" compact class="settle-card">
                    <view class="settle-card__head">
                        <view class="settle-card__copy">
                            <text class="settle-card__title ellipsis">{{
                                item.orderTitle
                            }}</text>
                            <text class="settle-card__meta">
                                {{ item.metaText }}
                            </text>
                        </view>
                        <status-tag :label="item.statusLabel" :tone="item.statusTone" size="sm" />
                    </view>

                    <view class="settle-card__amount-panel">
                        <view class="settle-card__amount-copy">
                            <text class="settle-card__amount-label">实结金额</text>
                            <price size="md" :content="item.actualAmount" :trim-zeros="false" />
                        </view>
                        <view class="settle-card__way">
                            <text class="settle-card__way-text">{{ item.settleWayText }}</text>
                        </view>
                    </view>

                    <view class="settle-card__facts">
                        <view class="fact">
                            <text class="fact__label">结算单号</text>
                            <text class="fact__value ellipsis">{{ item.settlementSn }}</text>
                        </view>
                        <view class="fact">
                            <text class="fact__label">订单号</text>
                            <text class="fact__value ellipsis">{{ item.orderSnText }}</text>
                        </view>
                        <view class="fact">
                            <text class="fact__label">结算时间</text>
                            <text class="fact__value ellipsis">{{ item.settleTimeText }}</text>
                        </view>
                    </view>

                    <view v-if="item.showMoneyGrid" class="settle-card__facts settle-card__facts--money">
                        <view class="fact">
                            <text class="fact__label">平台抽成</text>
                            <text class="fact__value">{{ money(item.platformCommission) }}</text>
                        </view>
                        <view class="fact">
                            <text class="fact__label">平台实收</text>
                            <text class="fact__value">{{ money(item.platformPaidShare) }}</text>
                        </view>
                        <view class="fact">
                            <text class="fact__label">待补平台</text>
                            <text
                                class="fact__value"
                                :class="{ 'fact__value--danger': item.dueLeft > 0 }"
                                >{{ money(item.dueLeft) }}</text
                            >
                        </view>
                    </view>

                    <view v-if="item.duePlatform > 0" class="notice-bar notice-bar--danger">
                        <text class="notice-bar__label">平台补收</text>
                        <text class="notice-bar__text">
                            {{ item.dueCollectStatusText }} · 已补收 {{ money(item.dueCollected) }}
                            · 待补 {{ money(item.dueLeft) }}
                        </text>
                    </view>

                    <view
                        class="notice-bar"
                        :class="`notice-bar--${item.transferTone}`"
                    >
                        <text class="notice-bar__label">转账进度</text>
                        <text class="notice-bar__text">{{ item.transferText }}</text>
                    </view>

                    <view
                        v-if="item.canRepay || item.canReceive || item.canSync"
                        class="settle-card__actions"
                        :class="{ 'settle-card__actions--single': item.actionCount === 1 }"
                    >
                        <base-button
                            v-if="item.canRepay"
                            text="补交平台"
                            variant="danger"
                            size="sm"
                            :loading="busyId === item.id && busyAction === 'repay'"
                            :disabled="busyId > 0"
                            @click="onRepayPlatform(item)"
                        />
                        <base-button
                            v-if="item.canReceive"
                            text="确认收款"
                            variant="ink"
                            size="sm"
                            :loading="busyId === item.id && busyAction === 'receive'"
                            :disabled="busyId > 0"
                            @click="onConfirmTransfer(item)"
                        />
                        <base-button
                            v-if="item.canSync"
                            text="同步状态"
                            variant="ghost"
                            size="sm"
                            :loading="busyId === item.id && busyAction === 'sync'"
                            :disabled="busyId > 0"
                            @click="onSyncTransfer(item)"
                        />
                    </view>
                </base-card>

                <load-more :status="loadMoreStatus" @retry="fetchPage(false)" />
            </view>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 服务人员：我的结算。
 *
 * 支持查看每笔服务结算与抽成明细、确认微信商家转账收款（确认后立即同步状态）、
 * 手动刷新转账状态，以及对平台实收不足的结算单发起线上补交（prepay from=staff_settlement_repay + payStatus 轮询）。
 */
import { computed, ref } from 'vue'
import { onLoad, onPullDownRefresh, onReachBottom, onShow, onUnload } from '@dcloudio/uni-app'
import {
    STAFF_SETTLEMENT_STATUS_NAME_MAP,
    STAFF_SETTLEMENT_STATUS_TONE,
    StaffSettlementStatus,
    resolveStatusLabel,
    resolveTone
} from '@shared/contracts'
import { formatDateTime as formatDateTimeShared, formatMoney } from '@shared/utils/format'
import {
    getStaffSettlementList,
    staffSettlementReceive,
    staffSettlementSync
} from '@/api/staff'
import { getPayStatus, prepay } from '@/api/pay'
import { requestPayment, sleep } from '@/platform/pay'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { normalizePage } from '@/request'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { toast } from '@/composables/useToast'
import { FB } from '@/constants/feedback'

interface DisplaySettlement {
    id: number
    settlementSn: string
    orderTitle: string
    metaText: string
    orderSnText: string
    settleTimeText: string
    status: number
    statusLabel: string
    statusTone: 'neutral' | 'info' | 'primary' | 'success' | 'warning' | 'danger'
    settleWayText: string
    actualAmount: number
    platformCommission: number
    platformPaidShare: number
    duePlatform: number
    dueCollected: number
    dueLeft: number
    dueCollectStatusText: string
    showMoneyGrid: boolean
    transferTone: 'neutral' | 'success' | 'warning' | 'danger'
    transferText: string
    canReceive: boolean
    canRepay: boolean
    canSync: boolean
    actionCount: number
}

const REPAY_POLL_INTERVALS = [1, 2, 3, 5]

const TABS = [
    { key: 'all', label: '全部', status: undefined as number | undefined },
    { key: 'confirm', label: '待确认', status: StaffSettlementStatus.TRANSFER_PROCESSING },
    { key: 'pending', label: '待结算', status: StaffSettlementStatus.PENDING },
    { key: 'settled', label: '已结算', status: StaffSettlementStatus.SETTLED },
    { key: 'failed', label: '失败', status: StaffSettlementStatus.FAILED },
    { key: 'no_payout', label: '无需打款', status: StaffSettlementStatus.NO_PAYOUT }
]

const {
    guardPhase,
    guardReason,
    guardError,
    guardAllowed,
    verify,
    onGuardRetry,
    onGuardAction
} = useStaffGuard(() => init(true))

const active = ref('all')
const rawList = ref<any[]>([])
const page = ref(1)
const pageSize = 15
const hasMore = ref(true)
const loadingMore = ref(false)
const loading = ref(true)
const error = ref(false)
const hasLoadedOnce = ref(false)
const loadMoreFailed = ref(false)
const busyId = ref(0)
const busyAction = ref<'receive' | 'sync' | 'repay' | ''>('')

let unmounted = false
let firstShow = true
let nativeSheetOpen = false

const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const loadMoreStatus = computed<'idle' | 'loading' | 'nomore' | 'error'>(() => {
    if (loadMoreFailed.value) return 'error'
    if (loadingMore.value && rawList.value.length) return 'loading'
    if (!hasMore.value) return 'nomore'
    return 'idle'
})

const activeTab = computed(() => TABS.find((t) => t.key === active.value) || TABS[0])
const activeTabLabel = computed(() => activeTab.value.label)

function toNum(v: unknown) {
    const n = Number(v ?? 0)
    return Number.isFinite(n) ? n : 0
}

function money(v: unknown) {
    return formatMoney(toNum(v), { symbol: '¥', thousand: true, trimZeros: false })
}

function formatDateTime(v: unknown) {
    if (!v || v === '0' || v === 0) return ''
    return formatDateTimeShared(v as any, 'YYYY-MM-DD HH:mm', String(v))
}

function mapTransferTone(status: number, hasFail: boolean): 'neutral' | 'success' | 'warning' | 'danger' {
    if (hasFail || status === StaffSettlementStatus.FAILED) return 'danger'
    if (status === StaffSettlementStatus.SETTLED) return 'success'
    if (status === StaffSettlementStatus.TRANSFER_PROCESSING) return 'warning'
    return 'neutral'
}

const list = computed<DisplaySettlement[]>(() =>
    rawList.value.map((raw) => {
        const status = toNum(raw.status)
        const mapped = resolveStatusLabel(STAFF_SETTLEMENT_STATUS_NAME_MAP as any, status)
        const orderItem = raw.order_item || raw.orderItem || {}
        const orderInfo = raw.order || {}
        const transfer = raw.transfer_summary || {}
        const serviceDate = String(raw.service_date || '').trim()
        const orderSn = String(orderInfo.order_sn || '').trim()
        const failReason = String(transfer.fail_reason || raw.fail_reason || '').trim()
        const transferText = transfer.status_text
            ? `${transfer.status_text}${
                  transfer.count ? ` ${toNum(transfer.success_count)}/${transfer.count}` : ''
              }`
            : status === StaffSettlementStatus.PENDING
              ? '待后台发起转账'
              : status === StaffSettlementStatus.SETTLED
                ? '转账已到账，结算完成'
                : status === StaffSettlementStatus.NO_PAYOUT
                  ? '无需向人员打款，仅核算抽成'
                  : '暂无转账记录'

        const platformCommission = toNum(raw.platform_commission_amount ?? raw.platform_amount)
        const platformPaidShare = toNum(raw.platform_paid_share_amount)
        const duePlatform = toNum(raw.staff_due_platform_amount)
        const dueCollected = toNum(raw.staff_due_collected_amount)
        const dueLeft = toNum(raw.staff_due_left_amount)
        const canReceive = Boolean(raw.can_receive)
        const canRepay =
            raw.can_repay_platform !== undefined
                ? Boolean(raw.can_repay_platform)
                : dueLeft > 0 && status !== StaffSettlementStatus.CANCELLED
        const canSync =
            status === StaffSettlementStatus.TRANSFER_PROCESSING ||
            status === StaffSettlementStatus.FAILED

        return {
            id: toNum(raw.id),
            settlementSn: String(raw.settlement_sn || '—'),
            orderTitle: String(orderItem.package_name || '服务结算'),
            metaText: [
                serviceDate ? `服务日期 ${serviceDate}` : '服务日期待补充',
                orderSn ? `单号 ${orderSn}` : ''
            ]
                .filter(Boolean)
                .join(' · '),
            orderSnText: orderSn || '—',
            settleTimeText:
                status === StaffSettlementStatus.SETTLED
                    ? formatDateTime(raw.settle_time) || '已结算'
                    : '未完成结算',
            status,
            statusLabel: String(raw.status_text || mapped.label),
            statusTone: resolveTone(STAFF_SETTLEMENT_STATUS_TONE as any, status),
            settleWayText: String(raw.settle_way_text || '结算方式待确认'),
            actualAmount: toNum(raw.actual_amount),
            platformCommission,
            platformPaidShare,
            duePlatform,
            dueCollected,
            dueLeft,
            dueCollectStatusText: String(raw.staff_due_collect_status_text || '无需补收'),
            showMoneyGrid: platformCommission > 0 || platformPaidShare > 0 || duePlatform > 0,
            transferTone: mapTransferTone(status, failReason !== ''),
            transferText: failReason || transferText,
            canReceive,
            canRepay,
            canSync,
            actionCount: Number(canRepay) + Number(canReceive) + Number(canSync)
        }
    })
)

const totalActualAmount = computed(() =>
    list.value.reduce((sum, item) => sum + item.actualAmount, 0)
)
const settledAmount = computed(() =>
    list.value
        .filter((item) => item.status === StaffSettlementStatus.SETTLED)
        .reduce((sum, item) => sum + item.actualAmount, 0)
)
const waitConfirmCount = computed(() =>
    list.value.filter((item) => item.status === StaffSettlementStatus.TRANSFER_PROCESSING).length
)
const repayCount = computed(() => list.value.filter((item) => item.canRepay).length)

const emptyTitle = computed(() => {
    if (active.value === 'confirm') return '暂无待确认转账'
    if (active.value === 'pending') return '暂无待结算记录'
    if (active.value === 'settled') return '暂无已结算记录'
    if (active.value === 'failed') return '暂无失败记录'
    if (active.value === 'no_payout') return '暂无无需打款记录'
    return FB.EMPTY_DEFAULT
})

async function fetchPage(reset = false) {
    if (reset) {
        page.value = 1
        hasMore.value = true
        error.value = false
    }
    if (!hasMore.value && !reset) return

    loadingMore.value = true
    loadMoreFailed.value = false
    try {
        const res = await getStaffSettlementList({
            page_no: page.value,
            page_size: pageSize,
            status: activeTab.value.status
        })
        const paged = normalizePage<any>(res, page.value, pageSize)
        rawList.value = reset ? paged.list : [...rawList.value, ...paged.list]
        hasMore.value = paged.hasMore
        if (hasMore.value) page.value += 1
    } catch (e) {
        rawError.value = e
        if (reset) error.value = true
        else loadMoreFailed.value = true
    } finally {
        loadingMore.value = false
        loading.value = false
        uni.stopPullDownRefresh()
    }
}

function reload() {
    if (!guardAllowed.value) return
    loading.value = true
    void fetchPage(true)
}

function switchTab(key: string) {
    if (!guardAllowed.value || key === active.value) return
    active.value = key
    rawList.value = []
    reload()
}

function onPrimary() {
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

function getMerchantTransferFn(): ((opts: Record<string, any>) => void) | null {
    const wxGlobal = (globalThis as any)?.wx
    if (wxGlobal && typeof wxGlobal.requestMerchantTransfer === 'function') {
        return wxGlobal.requestMerchantTransfer.bind(wxGlobal)
    }
    const uniApi = uni as any
    if (uniApi && typeof uniApi.requestMerchantTransfer === 'function') {
        return uniApi.requestMerchantTransfer.bind(uniApi)
    }
    return null
}

async function onConfirmTransfer(item: DisplaySettlement) {
    if (!guardAllowed.value || busyId.value > 0) return
    busyId.value = item.id
    busyAction.value = 'receive'
    try {
        const payload = (await staffSettlementReceive({ id: item.id })) || {}
        const transferPackage = String(payload.package || payload.package_info || '')
        if (!transferPackage) {
            toast.error('当前转账缺少确认参数')
            return
        }

        const transferFn = getMerchantTransferFn()
        if (!transferFn) {
            toast.error('请在最新版微信小程序内确认收款')
            return
        }

        nativeSheetOpen = true
        const result = await new Promise<{ ok: boolean; cancelled?: boolean; message?: string }>(
            (resolve) => {
                transferFn({
                    mchId: String(payload.mch_id || ''),
                    appId: String(payload.app_id || ''),
                    package: transferPackage,
                    success: () => resolve({ ok: true }),
                    fail: (err: any) => {
                        const msg = String(err?.errMsg || '')
                        resolve({
                            ok: false,
                            cancelled: msg.toLowerCase().includes('cancel'),
                            message: msg
                        })
                    }
                })
            }
        )
        nativeSheetOpen = false

        if (!result.ok) {
            if (result.cancelled) toast.info('已取消确认收款')
            else toast.error(result.message || '确认收款未完成，可稍后点击同步状态')
            return
        }

        // SETTLE-005：微信商家转账确认成功后立即调用 sync 刷新结算单状态
        await staffSettlementSync({ id: item.id })
        toast.success('已确认收款，状态已同步')
        await fetchPage(true)
    } catch (e: any) {
        nativeSheetOpen = false
        toast.error(String(e?.message || '确认收款失败，请稍后重试'))
    } finally {
        nativeSheetOpen = false
        busyId.value = 0
        busyAction.value = ''
    }
}

async function onSyncTransfer(item: DisplaySettlement) {
    if (!guardAllowed.value || busyId.value > 0) return
    busyId.value = item.id
    busyAction.value = 'sync'
    try {
        await staffSettlementSync({ id: item.id })
        toast.success('状态已同步')
        await fetchPage(true)
    } catch (e: any) {
        toast.error(String(e?.message || '同步状态失败，请稍后重试'))
    } finally {
        busyId.value = 0
        busyAction.value = ''
    }
}

async function pollRepayStatus(settlementId: number, paymentSn?: string): Promise<boolean> {
    for (const sec of REPAY_POLL_INTERVALS) {
        if (unmounted) return false
        await sleep(sec * 1000)
        if (unmounted) return false
        try {
            const st: any = await getPayStatus({
                from: 'staff_settlement_repay',
                order_id: settlementId,
                payment_sn: paymentSn || undefined
            })
            if (Number(st?.pay_status ?? st?.payment?.pay_status ?? -1) === 1) {
                return true
            }
        } catch {
            // 单次轮询失败继续下一次查询
        }
    }
    return false
}

function onRepayPlatform(item: DisplaySettlement) {
    if (!guardAllowed.value || busyId.value > 0) return
    uni.showModal({
        title: '补交平台抽成',
        content: `确认通过微信支付补交 ${money(item.dueLeft)}？（结算单 ${item.settlementSn}）`,
        confirmText: '确认支付',
        cancelText: '取消',
        success: async (r) => {
            if (!r.confirm) return
            busyId.value = item.id
            busyAction.value = 'repay'
            try {
                // 资金安全：发起预支付前先幂等回查补交状态
                try {
                    const st: any = await getPayStatus({
                        from: 'staff_settlement_repay',
                        order_id: item.id
                    })
                    const ps = Number(st?.pay_status ?? st?.payment?.pay_status ?? -1)
                    if (ps === 1) {
                        toast.info(FB.PAY_ALREADY_PAID)
                        await fetchPage(true)
                        return
                    }
                    if (ps !== 0) {
                        toast.error(FB.PAY_RECHECK_UNKNOWN)
                        return
                    }
                } catch {
                    toast.error(FB.PAY_RECHECK_UNKNOWN)
                    return
                }

                const pre = await prepay({
                    from: 'staff_settlement_repay',
                    order_id: item.id,
                    pay_way: 2
                })
                if (!pre || !pre.config) {
                    throw new Error('预支付参数缺失')
                }

                nativeSheetOpen = true
                const payResult = await requestPayment(pre.config)
                nativeSheetOpen = false

                if (payResult === 'cancel') {
                    toast.info('已取消支付')
                    return
                }
                if (payResult === 'fail') {
                    toast.error('支付失败，请重试')
                    return
                }

                const confirmed = await pollRepayStatus(item.id, pre.payment_sn)
                if (confirmed) {
                    toast.success('补交成功')
                } else {
                    toast.info(FB.PAY_UNCONFIRMED_TITLE)
                }
                await fetchPage(true)
            } catch (e: any) {
                nativeSheetOpen = false
                toast.error(String(e?.message || '补交发起失败，请稍后重试'))
            } finally {
                nativeSheetOpen = false
                busyId.value = 0
                busyAction.value = ''
            }
        }
    })
}

async function init(forceGuard = false) {
    if (!(await verify(forceGuard))) return
    await fetchPage(true)
    hasLoadedOnce.value = true
}

onLoad(() => {
    firstShow = true
})

onShow(() => {
    if (nativeSheetOpen || busyId.value > 0) return
    if (firstShow) {
        firstShow = false
        void init()
        return
    }
    if (hasLoadedOnce.value) {
        void init(true)
    }
})

onPullDownRefresh(() => {
    if (!guardAllowed.value || busyId.value > 0) {
        uni.stopPullDownRefresh()
        return
    }
    void fetchPage(true)
})

onReachBottom(() => {
    if (!guardAllowed.value || loadingMore.value || !hasMore.value) return
    void fetchPage(false)
})

onUnload(() => {
    unmounted = true
})
</script>

<style lang="scss" scoped>
.settle-page {
    padding-bottom: calc(var(--sp-5) + var(--safe-bottom));
}

.hero {
    margin: var(--sp-2) var(--page-gutter) 0;
    padding: var(--sp-4);
    border: 1rpx solid var(--c-brand-text);
    border-radius: var(--r-lg);
    background: linear-gradient(145deg, var(--c-dark-1), var(--c-dark-2));

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__eyebrow {
        font-family: var(--font-serif);
        font-size: var(--fs-xs);
        letter-spacing: 0.16em;
        color: var(--c-brand-soft);
    }

    &__main {
        margin-top: var(--sp-3);
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__label {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-on-dark-2);
    }

    &__amount {
        font-family: var(--font-serif);
        font-size: var(--fs-2xl);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-on-dark);
        font-variant-numeric: tabular-nums;
    }

    &__stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: var(--sp-2);
        margin-top: var(--sp-3);
    }
}

.hero-stat {
    min-width: 0;
    padding: var(--sp-2);
    border: 1rpx solid var(--c-dark-3);
    border-radius: var(--r-md);
    background: var(--c-dark-3);
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);

    &__label {
        font-size: var(--fs-xs);
        line-height: var(--lh-tight);
        color: var(--c-on-dark-2);
        white-space: nowrap;
    }

    &__value {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-brand-soft);
        font-variant-numeric: tabular-nums;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}

.tabs {
    padding-top: var(--sp-3);
    padding-bottom: var(--sp-2);
}

.list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.settle-card {
    &__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-ink);
    }

    &__meta {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }

    &__amount-panel {
        margin-top: var(--sp-3);
        padding: var(--sp-3);
        border-radius: var(--r-sm);
        border: 1rpx solid var(--c-line);
        background: var(--c-surface-2);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__amount-copy {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__amount-label {
        font-size: var(--fs-xs);
        line-height: var(--lh-none);
        color: var(--c-ink-3);
    }

    &__way {
        flex-shrink: 0;
        padding: var(--sp-1) var(--sp-2);
        border-radius: var(--r-pill);
        background: var(--c-brand-bg);
        border: 1rpx solid var(--c-primary-border);
    }

    &__way-text {
        font-size: var(--fs-xs);
        line-height: var(--lh-none);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
    }

    &__facts {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: var(--sp-2);
        margin-top: var(--sp-2);
    }

    &__actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: var(--sp-2);
        margin-top: var(--sp-3);

        &--single {
            grid-template-columns: 1fr;
        }
    }
}

.fact {
    min-width: 0;
    padding: var(--sp-2);
    border-radius: var(--r-sm);
    background: var(--c-neutral-bg);
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);

    &__label {
        font-size: var(--fs-xs);
        line-height: var(--lh-tight);
        color: var(--c-ink-3);
    }

    &__value {
        font-size: var(--fs-xs);
        line-height: var(--lh-tight);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        font-variant-numeric: tabular-nums;

        &--danger {
            color: var(--c-danger);
        }
    }
}

.notice-bar {
    margin-top: var(--sp-2);
    padding: var(--sp-2) var(--sp-3);
    border-radius: var(--r-sm);
    border: 1rpx solid var(--c-neutral-border);
    background: var(--c-neutral-bg);
    display: flex;
    align-items: flex-start;
    gap: var(--sp-2);

    &--success {
        border-color: var(--c-success-border);
        background: var(--c-success-bg);

        .notice-bar__text {
            color: var(--c-success);
        }
    }

    &--warning {
        border-color: var(--c-warning-border);
        background: var(--c-warning-bg);

        .notice-bar__text {
            color: var(--c-warning);
        }
    }

    &--danger {
        border-color: var(--c-danger-border);
        background: var(--c-danger-bg);

        .notice-bar__text,
        .notice-bar__label {
            color: var(--c-danger);
        }
    }

    &__label {
        flex-shrink: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        font-weight: var(--fw-semibold);
        color: var(--c-ink-3);
    }

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
        overflow-wrap: anywhere;
    }
}
</style>
