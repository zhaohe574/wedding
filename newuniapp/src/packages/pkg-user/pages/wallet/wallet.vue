<template>
    <!--
        我的钱包（纯对账）
        - 只做订单/活动支付与退款的只读流水对账，不提供任何资金变动能力
        - 深墨黑金汇总卡 + 单行筛选条 + 分月流水 + 四态管理
        - 点击流水打开对账详情抽屉；月份选择为半屏抽屉
    -->
    <page-shell
        title="我的钱包"
        bg="page"
        :show-back="true"
        :pull-refresh="true"
        @refresh="onRefresh"
        @reach-bottom="onReachBottomLoad"
    >
        <!-- 深墨黑金对账汇总卡 -->
        <view v-if="summaryReady" class="hero">
            <view class="hero__top">
                <view
                    class="hero__month hit-88"
                    hover-class="is-pressed"
                    aria-role="button"
                    aria-label="选择月份"
                    @tap="openMonthSheet"
                >
                    <text class="hero__month-text">{{ monthPillText }}</text>
                    <tn-icon name="down" :size="20" class="hero__month-ic" />
                </view>
                <text class="hero__count t-num">共 {{ summary.total_count }} 笔</text>
            </view>

            <view class="hero__main">
                <text class="hero__label">{{ selectedMonth ? '本月净支出' : '净支出' }}</text>
                <view class="hero__net">
                    <rolling-number
                        :value="netAmount"
                        :prefix="netNegative ? '-¥' : '¥'"
                        :decimals="2"
                        :thousands="true"
                        :min-chars="5"
                    />
                </view>
            </view>

            <view class="hero__stats">
                <view class="hero-stat">
                    <text class="hero-stat__label">累计已付</text>
                    <rolling-number
                        class="hero-stat__value"
                        :value="paidAmount"
                        prefix="¥"
                        :decimals="2"
                        :thousands="true"
                        :min-chars="4"
                    />
                    <text class="hero-stat__sub t-num">{{ summary.paid_count }} 笔</text>
                </view>
                <view class="hero-stat">
                    <text class="hero-stat__label">累计退款</text>
                    <rolling-number
                        class="hero-stat__value"
                        :value="refundedAmount"
                        prefix="¥"
                        :decimals="2"
                        :thousands="true"
                        :min-chars="4"
                    />
                    <text class="hero-stat__sub t-num">{{ summary.refunded_count }} 笔</text>
                </view>
            </view>

            <view
                v-if="summary.refunding_count > 0"
                class="hero__refunding"
                hover-class="is-pressed"
                aria-role="button"
                aria-label="查看退款中的流水"
                @tap="switchToRefund"
            >
                <text class="hero__refunding-text t-num">
                    退款中 ¥{{ money(summary.refunding_amount) }} ·
                    {{ summary.refunding_count }} 笔
                </text>
                <tn-icon name="right" :size="20" class="hero__refunding-ic" />
            </view>
        </view>

        <!-- 单行筛选条 -->
        <view class="filters">
            <chip-group
                :items="FILTERS.map((f) => ({ label: f.label, value: f.key }))"
                :model-value="activeFilter"
                scroll
                @change="switchFilter"
            />
        </view>

        <!-- 四态：加载 -->
        <view v-if="loading" class="pad">
            <base-skeleton :rows="4" variant="card" />
        </view>

        <!-- 四态：错误 -->
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

        <!-- 四态：空 -->
        <base-empty
            v-else-if="!list.length"
            status="empty"
            empty-icon="empty-order"
            :empty-text="FB.EMPTY_WALLET"
            :desc="emptyHint"
            fixed
        />

        <!-- 四态：就绪（分月流水） -->
        <view v-else class="mv">
            <view class="mv__list" :class="{ stagger: staggerOn, 'is-in': staggerIn }">
                <template v-for="group in groups" :key="group.key">
                    <view v-if="group.label" class="mv__month">
                        <text class="mv__month-text t-num">{{ group.label }}</text>
                    </view>
                    <view
                        v-for="item in group.items"
                        :key="item.flow_key"
                        class="flow"
                        hover-class="is-pressed"
                        aria-role="button"
                        :aria-label="item.title"
                        :style="{ '--i': flowIndex[item.flow_key] ?? 0 }"
                        @tap="openDetail(item)"
                    >
                        <view class="flow__ic" :class="`flow__ic--${iconTone(item)}`">
                            <tn-icon :name="flowIcon(item.biz_category)" :size="34" />
                        </view>

                        <view class="flow__main">
                            <text class="flow__title ellipsis">{{ item.title }}</text>
                            <text class="flow__meta ellipsis t-num">{{ flowMeta(item) }}</text>
                        </view>

                        <view class="flow__right">
                            <text
                                class="flow__amount t-num"
                                :class="`flow__amount--${amountTone(item)}`"
                            >
                                {{ flowAmount(item) }}
                            </text>
                            <status-tag
                                v-if="item.status !== 'completed'"
                                :label="item.status_label"
                                :tone="item.status_tone"
                                size="sm"
                            />
                            <text
                                v-else-if="item.direction === 'out' && item.refunded_amount > 0"
                                class="flow__refunded t-num"
                            >
                                已退 ¥{{ money(item.refunded_amount) }}
                            </text>
                        </view>
                    </view>
                </template>
            </view>

            <load-more :status="loadMoreStatus" @retry="loadMore" />
        </view>

        <template #bottom>
            <!-- 月份选择半屏抽屉：标题 / 关闭 / 拖拽由 snap-sheet 承担 -->
            <snap-sheet v-model="monthSheetOpen" title="选择月份">
                <view class="months">
                    <view
                        class="months__item"
                        :class="{ 'is-on': selectedMonth === '' }"
                        hover-class="is-pressed"
                        @tap="pickMonth('')"
                    >
                        <text class="months__label">全部时间</text>
                        <tn-icon
                            v-if="selectedMonth === ''"
                            name="check"
                            :size="28"
                            class="months__check"
                        />
                    </view>
                    <view
                        v-for="m in months"
                        :key="m"
                        class="months__item"
                        :class="{ 'is-on': selectedMonth === m }"
                        hover-class="is-pressed"
                        @tap="pickMonth(m)"
                    >
                        <text class="months__label t-num">{{ formatMonth(m) }}</text>
                        <tn-icon
                            v-if="selectedMonth === m"
                            name="check"
                            :size="28"
                            class="months__check"
                        />
                    </view>
                </view>
            </snap-sheet>

            <!-- 对账详情半屏抽屉 -->
            <wallet-detail-sheet v-model:show="detailOpen" :item="activeFlow" />

            <app-toast />
        </template>
    </page-shell>
</template>

<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { formatDateTime, formatMoney } from '@shared/utils/format'
import { getWalletFlows } from '@/api/wallet'
import type {
    WalletBizGroup,
    WalletDirection,
    WalletFlowItemVO,
    WalletSummaryVO
} from '@/api/types/wallet'
import { FB } from '@/constants/feedback'
import { toast } from '@/composables/useToast'
import { navigate } from '@/utils/link'
import { resolveErrorView } from '@/utils/resolveErrorView'
import WalletDetailSheet from './components/wallet-detail-sheet.vue'

type FilterKey = 'all' | 'out' | 'in' | 'order' | 'activity'

interface WalletFilter {
    key: FilterKey
    label: string
    direction: WalletDirection
    biz_group: WalletBizGroup
}

interface MonthGroup {
    key: string
    label: string
    items: WalletFlowItemVO[]
}

const FILTERS: WalletFilter[] = [
    { key: 'all', label: '全部', direction: 'all', biz_group: 'all' },
    { key: 'out', label: '支出', direction: 'out', biz_group: 'all' },
    { key: 'in', label: '退款', direction: 'in', biz_group: 'all' },
    { key: 'order', label: '订单', direction: 'all', biz_group: 'order' },
    { key: 'activity', label: '活动', direction: 'all', biz_group: 'activity' }
]

const PAGE_SIZE = 20

const summary = ref<WalletSummaryVO>(emptySummary())
const months = ref<string[]>([])
const list = ref<WalletFlowItemVO[]>([])
const selectedMonth = ref('')
const activeFilter = ref<FilterKey>('all')

const page = ref(1)
const hasMore = ref(false)
const loading = ref(true)
const loadingMore = ref(false)
const loadMoreError = ref(false)
const rawError = ref<unknown>(null)
const summaryReady = ref(false)

const monthSheetOpen = ref(false)
const detailOpen = ref(false)
const activeFlow = ref<WalletFlowItemVO | null>(null)

/** 请求序号：切换筛选 / 月份 / 刷新 / 触底时作废旧请求，防止结果串味 */
let seq = 0

const error = computed(() => !!rawError.value)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
const activeQuery = computed(() => FILTERS.find((f) => f.key === activeFilter.value) ?? FILTERS[0])
const monthPillText = computed(() =>
    selectedMonth.value ? formatMonth(selectedMonth.value) : '全部时间'
)

/** 汇总金额统一过一遍有限数：接口可能回字符串 / null，rolling-number 不能收到 NaN */
function amountNumber(value: number | string | null | undefined): number {
    const num = Number(value)
    return Number.isFinite(num) ? num : 0
}

const netValue = computed(() => amountNumber(summary.value.net_expense))
const netAmount = computed(() => Math.abs(netValue.value))
const netNegative = computed(() => netValue.value < 0)
const paidAmount = computed(() => amountNumber(summary.value.total_paid))
const refundedAmount = computed(() => amountNumber(summary.value.total_refunded))

const hasFilter = computed(() => activeFilter.value !== 'all' || selectedMonth.value !== '')
const emptyHint = computed(() =>
    hasFilter.value ? '换个筛选条件或时间试试' : FB.EMPTY_HINT_WALLET
)

const loadMoreStatus = computed<'idle' | 'loading' | 'nomore' | 'error'>(() => {
    if (loadingMore.value) return 'loading'
    if (loadMoreError.value) return 'error'
    if (!hasMore.value) return 'nomore'
    return 'idle'
})

/** 全部时间按月份分组；单月筛选不重复显示月标头 */
const groups = computed<MonthGroup[]>(() => {
    if (selectedMonth.value) {
        return [{ key: selectedMonth.value, label: '', items: list.value }]
    }

    const map = new Map<string, WalletFlowItemVO[]>()
    for (const item of list.value) {
        const key = item.month || ''
        const rows = map.get(key)
        if (rows) rows.push(item)
        else map.set(key, [item])
    }

    return Array.from(map.entries()).map(([month, items]) => ({
        key: month || 'unknown',
        label: month ? formatMonth(month) : '',
        items
    }))
})

/** 跨月份分组的扁平序号：错峰入场用 --i，第 7 项起由 CSS 封顶 */
const flowIndex = computed<Record<string, number>>(() => {
    const map: Record<string, number> = {}
    let index = 0
    for (const group of groups.value) {
        for (const item of group.items) map[item.flow_key] = ++index
    }
    return map
})

/* ---- 首屏错峰入场（§3.3）：先挂 .stagger，下一拍再放 .is-in，动画才有起点 ---- */
const staggerOn = ref(false)
const staggerIn = ref(false)
let staggerTimer: ReturnType<typeof setTimeout> | null = null

function playStagger() {
    if (!list.value.length || staggerIn.value) return
    staggerOn.value = true
    if (staggerTimer) return
    staggerTimer = setTimeout(() => {
        staggerTimer = null
        staggerIn.value = true
    }, 40)
}

watch(
    () => list.value.length,
    (len) => {
        if (len) playStagger()
    }
)

onUnmounted(() => {
    if (staggerTimer) clearTimeout(staggerTimer)
})

function emptySummary(): WalletSummaryVO {
    return {
        month: '',
        total_paid: 0,
        total_refunded: 0,
        net_expense: 0,
        refunding_amount: 0,
        refunding_count: 0,
        paid_count: 0,
        refunded_count: 0,
        total_count: 0
    }
}

function money(value: number | string | null | undefined): string {
    return formatMoney(value, { decimals: 2, thousand: true, trimZeros: false })
}

function formatMonth(month: string): string {
    const matched = /^(\d{4})-(\d{2})$/.exec(month)
    return matched ? `${matched[1]}年${matched[2]}月` : month
}

function shortTime(item: WalletFlowItemVO): string {
    if (item.event_time_text) return item.event_time_text
    return formatDateTime(item.event_time, 'MM-DD HH:mm', '')
}

function flowIcon(category: WalletFlowItemVO['biz_category']): string {
    if (category === 'order_pay') return 'pay'
    if (category === 'activity_pay') return 'ticket'
    return 'refund'
}

function iconTone(item: WalletFlowItemVO): 'out' | 'in-done' | 'in-pending' | 'exception' {
    if (item.status === 'exception') return 'exception'
    if (item.direction === 'in') return item.status === 'processing' ? 'in-pending' : 'in-done'
    return 'out'
}

function amountTone(item: WalletFlowItemVO): 'out' | 'in' | 'pending' {
    if (item.direction === 'out') return 'out'
    return item.status === 'completed' ? 'in' : 'pending'
}

function flowAmount(item: WalletFlowItemVO): string {
    return `${item.direction === 'out' ? '-' : '+'}¥${money(item.amount)}`
}

function flowMeta(item: WalletFlowItemVO): string {
    const parts = [shortTime(item), item.pay_way_label]
    if (Number(item.collection_owner) === 2) parts.push('人员代收')
    return parts.filter(Boolean).join(' · ')
}

async function load(reset: boolean, silent = false) {
    const mySeq = ++seq
    const filter = activeQuery.value

    if (reset) {
        page.value = 1
        loadingMore.value = false
        loadMoreError.value = false
        rawError.value = null
        if (!silent) loading.value = true
    } else {
        loadingMore.value = true
        loadMoreError.value = false
    }

    try {
        const res = await getWalletFlows({
            month: selectedMonth.value || undefined,
            direction: filter.direction,
            biz_group: filter.biz_group,
            page: page.value,
            page_size: PAGE_SIZE
        })
        if (mySeq !== seq) return

        if (res?.summary) summary.value = res.summary
        if (Array.isArray(res?.months)) months.value = res.months

        const rows = Array.isArray(res?.list) ? res.list : []
        if (reset) {
            list.value = rows
        } else {
            const seen = new Set(list.value.map((row) => row.flow_key))
            list.value = list.value.concat(rows.filter((row) => !seen.has(row.flow_key)))
        }

        const total = Number(res?.total ?? list.value.length)
        hasMore.value =
            res?.has_more !== undefined ? Boolean(res.has_more) : list.value.length < total
        summaryReady.value = true
        rawError.value = null
    } catch (e) {
        if (mySeq !== seq) return
        if (reset) {
            // 下拉刷新失败只轻提示，不把已有列表整屏替换成错误态
            if (silent) toast.error('刷新失败，请稍后重试')
            else rawError.value = e
        } else {
            page.value = Math.max(1, page.value - 1)
            loadMoreError.value = true
        }
    } finally {
        if (mySeq === seq) {
            loading.value = false
            loadingMore.value = false
        }
    }
}

function reload() {
    void load(true)
}

function switchFilter(value: string | number) {
    const key = String(value) as FilterKey
    if (activeFilter.value === key) return
    activeFilter.value = key
    void load(true)
}

/** 点「退款中」提示条：直接切到退款筛选 */
function switchToRefund() {
    if (activeFilter.value === 'in') return
    activeFilter.value = 'in'
    void load(true)
}

function openMonthSheet() {
    monthSheetOpen.value = true
}

function pickMonth(month: string) {
    monthSheetOpen.value = false
    if (selectedMonth.value === month) return
    selectedMonth.value = month
    void load(true)
}

function openDetail(item: WalletFlowItemVO) {
    activeFlow.value = item
    detailOpen.value = true
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        reload()
        return
    }
    if (kind === 'login') {
        navigate({ path: '/pages/login/login' })
        return
    }
    if (kind === 'reselect' || kind === 'back') uni.navigateBack()
}

function onRefresh(done: () => void) {
    Promise.resolve(load(true, true)).finally(done)
}

function onReachBottomLoad() {
    if (!hasMore.value || loading.value || loadingMore.value) return
    page.value += 1
    void load(false)
}

/** 加载更多失败后的重试：page 已在失败时回退，直接重发同一页 */
function loadMore() {
    if (loading.value || loadingMore.value || !hasMore.value) return
    void load(false)
}

onLoad(() => {
    void load(true)
})
</script>

<style lang="scss" scoped>
.pad {
    padding: var(--sp-4) var(--page-gutter);
}

/* ---------------- 深墨黑金汇总卡（同心圆角：40 − 内边距 24 = 16） ---------------- */
.hero {
    margin: var(--sp-3) var(--page-gutter) 0;
    padding: var(--sp-3);
    border-radius: var(--r-lg);
    background: linear-gradient(145deg, var(--c-dark-1), var(--c-dark-2));
    border: 1rpx solid var(--c-card-border);
    box-shadow: var(--shadow-card);

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__month {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        height: 56rpx;
        padding: 0 var(--sp-3);
        border-radius: var(--r-pill);
        background: var(--c-dark-3);
        border: 1rpx solid var(--c-brand-text);
        /* 图标与文字同色 */
        color: var(--c-brand-soft);
        transition: transform var(--dur-press) var(--ease-out);

        &.is-pressed {
            transform: scale(0.96);
        }
    }

    &__month-text {
        font-size: var(--fs-22);
        font-weight: var(--fw-medium);
        color: var(--c-brand-soft);
    }

    &__month-ic {
        color: var(--c-brand-soft);
    }

    &__count {
        font-size: var(--fs-21);
        color: var(--c-on-dark-2);
    }

    &__main {
        margin-top: var(--sp-3);
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
    }

    &__net {
        display: block;
        max-width: 100%;
        overflow: hidden;
        white-space: nowrap;
        font-family: var(--font-serif);
        font-size: var(--fs-number);
        font-weight: var(--fw-medium);
        line-height: var(--lh-none);
        color: var(--c-on-dark);
    }

    &__stats {
        display: flex;
        gap: var(--sp-2);
        margin-top: var(--sp-3);
    }

    &__refunding {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-top: var(--sp-3);
        padding: var(--sp-2) var(--sp-3);
        border-radius: var(--r-sm);
        background: var(--c-dark-3);
        border: 1rpx solid var(--c-brand-text);
        color: var(--c-warning-on-dark);
    }

    &__refunding-text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-22);
        line-height: var(--lh-body);
        color: var(--c-warning-on-dark);
    }

    &__refunding-ic {
        flex: none;
        color: var(--c-warning-on-dark);
    }
}

.hero-stat {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);
    padding: var(--sp-2) var(--sp-3);
    border-radius: var(--r-16);
    background: var(--c-dark-3);

    &__label {
        font-size: var(--fs-20);
        color: var(--c-on-dark-2);
    }

    &__value {
        font-family: var(--font-serif);
        font-size: var(--fs-30);
        font-weight: var(--fw-medium);
        line-height: var(--lh-tight);
        color: var(--c-on-dark);
    }

    &__sub {
        font-size: var(--fs-20);
        color: var(--c-on-dark-2);
    }
}

@media (prefers-reduced-motion: reduce) {
    .hero__month {
        transition: none;

        &.is-pressed {
            transform: none;
        }
    }
}

/* ---------------- 筛选条 ---------------- */
.filters {
    padding: var(--sp-3) var(--page-gutter) 0;
}

/* ---------------- 分月流水 ---------------- */
.mv {
    padding: var(--sp-2) 0 calc(var(--sp-6) + var(--safe-bottom));

    &__month {
        padding: var(--sp-3) var(--page-gutter) var(--sp-1);
        /* 月份标头不参与错峰：它是分组标签，不是列表项 */
        opacity: 1;
        transform: none;
        animation: none;
    }

    &__month-text {
        font-size: var(--fs-21);
        font-weight: var(--fw-medium);
        color: var(--c-ink-3);
    }
}

.flow {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    margin: 0 var(--page-gutter) var(--sp-2);
    padding: var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-card-border);
    box-shadow: var(--shadow-card);

    &__ic {
        flex: none;
        width: 72rpx;
        height: 72rpx;
        border-radius: var(--r-pill);
        display: flex;
        align-items: center;
        justify-content: center;

        &--out {
            background: var(--c-brand-bg);
            color: var(--c-brand-text);
        }

        &--in-done {
            background: var(--c-success-bg);
            color: var(--c-success);
        }

        &--in-pending {
            background: var(--c-warning-bg);
            color: var(--c-warning);
        }

        &--exception {
            background: var(--c-danger-bg);
            color: var(--c-danger);
        }
    }

    &__main {
        flex: 1;
        min-width: 0;
    }

    &__title {
        display: block;
        font-size: var(--fs-26);
        font-weight: var(--fw-medium);
        line-height: var(--lh-tight);
        color: var(--c-ink);
    }

    &__meta {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-20);
        line-height: var(--lh-tight);
        color: var(--c-ink-3);
    }

    &__right {
        flex: none;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: var(--sp-1);
    }

    &__amount {
        font-family: var(--font-serif);
        font-size: var(--fs-29);
        font-weight: var(--fw-medium);
        line-height: var(--lh-tight);
        font-variant-numeric: tabular-nums;

        &--out {
            color: var(--c-ink);
        }

        &--in {
            color: var(--c-success);
        }

        &--pending {
            color: var(--c-warning);
        }
    }

    &__refunded {
        font-size: var(--fs-19);
        color: var(--c-brand-text);
    }
}

/* ---------------- 月份选择抽屉（脚部由 snap-sheet 承担） ---------------- */
.months {
    display: flex;
    flex-direction: column;
    padding-top: var(--sp-1);

    &__item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        min-height: 96rpx;
        padding: 0 var(--sp-2);
        border-bottom: 1rpx solid var(--c-line);
        transition: background-color var(--dur-fast) var(--ease-out);

        &:last-child {
            border-bottom: 0;
        }

        &.is-pressed {
            background: var(--c-surface-2);
        }
    }

    &__label {
        font-size: var(--fs-md);
        color: var(--c-ink-2);
    }

    &__check {
        color: var(--c-brand-text);
    }

    &__item.is-on &__label {
        color: var(--c-brand-text);
        font-weight: var(--fw-semibold);
    }
}

@media (prefers-reduced-motion: reduce) {
    .months__item {
        transition: none;
    }
}
</style>
