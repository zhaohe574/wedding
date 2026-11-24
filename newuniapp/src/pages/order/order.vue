<template>
    <!--
        22 我的订单（设计稿 4115–4225）
        设计意图：一屏看清「哪一单、到哪一步、下一步做什么」——状态用标签不用色块，
        倒计时只给会过期的两种，金额跟着状态走，一张卡只有一个按钮。

        数据现实：列表项的 staff / item_count / city_name / venue / amount_label /
        amount_value / countdown_seconds 都是可选字段（F-27 未落地）。
        缺了就按「头像→首项 staff_name」「金额→need_pay_amount 或已付金额」降级，
        倒计时只在后端真的给了秒数时才显示——不自己按时长推断，避免显示错误死线。
    -->
    <page-shell title="我的订单" bg="page" :show-back="true">
        <!-- 状态分组：与个人中心订单五格一致（全部 / 待确认 / 待支付 / 待服务 / 服务中 / 售后） -->
        <scroll-view
            class="od-tabs-scroll"
            scroll-x
            :show-scrollbar="false"
            :scroll-into-view="`tab-${activeKey}`"
            scroll-with-animation
        >
            <view class="od-tabs">
                <view
                    v-for="tab in TABS"
                    :id="`tab-${tab.key}`"
                    :key="tab.key"
                    class="od-pill"
                    :class="{ 'is-on': tab.key === activeKey }"
                    hover-class="is-pressed"
                    @tap="switchTab(tab.key)"
                >
                    <text class="od-pill__text">{{ tab.label }}</text>
                    <text v-if="countOf(tab.key) > 0" class="od-pill__num">{{
                        countOf(tab.key)
                    }}</text>
                </view>
            </view>
        </scroll-view>

        <view v-if="firstLoading" class="pad">
            <base-skeleton :rows="4" />
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
            empty-text="还没有相关订单"
            desc="选好日期与主持人后，订单会出现在这里。"
            action-text="去看看主持人"
            :show-action="true"
            fixed
            @action="goQuery"
        />

        <view v-else class="list">
            <view
                v-for="item in list"
                :key="item.id"
                class="card"
                hover-class="is-pressed"
                @tap="goDetail(item.id)"
            >
                <view class="card__top">
                    <image
                        v-if="avatarOf(item)"
                        class="card__av"
                        :src="avatarOf(item)"
                        mode="aspectFill"
                    />
                    <view v-else class="card__av card__av--txt">
                        <text class="card__av-text">{{
                            (staffNameOf(item) || '·').slice(0, 1)
                        }}</text>
                    </view>

                    <view class="card__who">
                        <view class="card__nm">
                            <text class="card__name">{{ staffNameOf(item) }}</text>
                        </view>
                        <text class="card__pkg">{{ summaryOf(item) }}</text>
                    </view>

                    <status-tag :label="statusLabel(item)" :tone="statusTone(item)" size="sm" />
                </view>

                <!-- 死线：只有后端给了剩余秒数才出现 -->
                <view v-if="deadlineOf(item)" class="card__warn">
                    <tn-icon name="clock" :size="22" />
                    <text class="card__warn-text">{{ deadlineOf(item)!.label }}</text>
                    <text class="card__warn-num">{{ deadlineOf(item)!.text }}</text>
                    <text class="card__warn-text">· {{ deadlineOf(item)!.hint }}</text>
                </view>

                <view v-if="dateLine(item)" class="card__line">
                    <tn-icon name="calendar" :size="22" />
                    <text class="card__line-text">{{ dateLine(item) }}</text>
                </view>

                <view class="card__foot">
                    <view class="card__amt">
                        <text class="card__amt-label">{{ amountOf(item).label }}</text>
                        <text class="card__amt-value">¥{{ amountOf(item).value }}</text>
                    </view>
                    <view
                        class="card__btn"
                        :class="{ 'is-solid': primaryAction(item).solid }"
                        hover-class="is-pressed"
                        @tap.stop="onAction(item)"
                    >
                        <text class="card__btn-text">{{ primaryAction(item).text }}</text>
                    </view>
                </view>
            </view>

            <load-more :status="loadMoreStatus" @retry="fetchPage(false)" />

            <view class="foot">
                <text class="foot__rule">✦ 愿每一场婚礼皆得圆满 ✦</text>
            </view>
        </view>
    </page-shell>
    <app-toast />
</template>

<script setup lang="ts">
/**
 * 订单列表。
 *
 * 后端 11 个状态收敛为 5 组，/order/lists 的 status 支持逗号分隔多值。
 * 统计数字来自 /order/statistics（后端按状态分别 count），不是前端数当前页。
 */
import { computed, ref } from 'vue'
import { onLoad, onPullDownRefresh, onReachBottom, onShow } from '@dcloudio/uni-app'
import {
    ORDER_STATUS_NAME_MAP,
    ORDER_STATUS_TONE,
    resolveStatusLabel,
    resolveTone
} from '@shared/contracts'
import { getOrderList, getOrderStatistics } from '@/api/order'
import { normalizePage } from '@/request'
import { navigate } from '@/utils/link'
import { formatMoney } from '@shared/utils/format'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { useAppStore } from '@/stores/app'

const TABS = [
    { key: 'all', label: '全部', status: undefined as string | undefined, statKey: 'all' },
    { key: 'confirm', label: '待确认', status: '0', statKey: 'pending_confirm' },
    { key: 'unpaid', label: '待支付', status: '1', statKey: 'pending_pay' },
    { key: 'service', label: '待服务', status: '2', statKey: 'pending_service' },
    { key: 'serving', label: '服务中', status: '3', statKey: 'in_service' },
    { key: 'refund', label: '售后', status: '8,10', statKey: 'refund_total' }
]

const appStore = useAppStore()

const activeKey = ref('all')
const list = ref<any[]>([])
const page = ref(1)
const pageSize = 10
const total = ref(0)
const hasMore = ref(true)
const loadingMore = ref(false)
const firstLoading = ref(true)
const error = ref(false)
const stats = ref<Record<string, number>>({})
let requestId = 0

const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
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
        navigate({ path: '/pages/login/login' })
    }
}

const loadMoreFailed = ref(false)
const loadMoreStatus = computed<'idle' | 'loading' | 'nomore' | 'error'>(() => {
    if (loadMoreFailed.value) return 'error'
    if (loadingMore.value && list.value.length) return 'loading'
    if (!hasMore.value) return 'nomore'
    return 'idle'
})

const activeStatus = computed(() => TABS.find((t) => t.key === activeKey.value)?.status)

/** 统计：售后 = 退款中 + 已退款 */
function countOf(key: string) {
    const s = stats.value || {}
    if (key === 'refund') return Number(s.refunding || 0) + Number(s.refund || 0)
    const tab = TABS.find((t) => t.key === key)
    if (!tab) return 0
    return Number(s[tab.statKey] || 0)
}

function statusLabel(item: any) {
    const raw = Number(item.order_status)
    const mapped = resolveStatusLabel(ORDER_STATUS_NAME_MAP as any, raw)
    return mapped.known ? mapped.label : item.order_status_desc || mapped.label
}

function statusTone(item: any) {
    return resolveTone(ORDER_STATUS_TONE as any, item.order_status)
}

function staffNameOf(item: any) {
    return item.staff?.name || item.items?.[0]?.staff_name || '婚庆服务'
}

function avatarOf(item: any) {
    const url = item.staff?.avatar || item.items?.[0]?.staff_avatar || ''
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

/** 「全案统筹 · 共 3 项服务」：两个字段各自可缺，缺一个就少写一段 */
function summaryOf(item: any) {
    const pkg = item.package_name || item.items?.[0]?.package_name || ''
    const n = Number(item.item_count ?? (item.items?.length || 0))
    const parts: string[] = []
    if (pkg) parts.push(pkg)
    if (n > 0) parts.push(`共 ${n} 项服务`)
    return parts.join(' · ') || '婚庆服务'
}

/** 「2027.05.20 · 杭州 西溪湿地」 */
function dateLine(item: any) {
    const parts: string[] = []
    if (item.service_date) parts.push(String(item.service_date).replace(/-/g, '.'))
    const city = item.city_name || item.service_city || ''
    const venue = item.venue || item.service_address || ''
    const place = [city, venue].filter(Boolean).join(' ')
    if (place) parts.push(place)
    else if (item.service_region_text) parts.push(item.service_region_text)
    return parts.join(' · ')
}

/** 死线：按状态读后端 pay_remain_seconds / confirm_remain_seconds（兜底 countdown_seconds） */
function deadlineOf(item: any) {
    const status = Number(item.order_status)
    const rawSec =
        status === 1
            ? item.pay_remain_seconds ?? item.countdown_seconds
            : status === 0
              ? item.confirm_remain_seconds ?? item.countdown_seconds
              : 0
    const sec = Number(rawSec ?? 0)
    if (!sec || sec <= 0) return null
    const label = status === 1 ? '剩余支付时间' : status === 0 ? '剩余确认时间' : ''
    if (!label) return null
    const mm = String(Math.floor(sec / 60)).padStart(2, '0')
    const ss = String(sec % 60).padStart(2, '0')
    return {
        label,
        text: `${mm}:${ss}`,
        hint: status === 1 ? '超时释放档期' : '超时自动关闭'
    }
}

/**
 * 金额语义：优先用后端给的 amount_label / amount_value；
 * 没给就按「还欠多少」降级——有 need_pay_amount 就写待付，取消/已退款且实付为 0 时写订单金额。
 */
function amountOf(item: any) {
    if (item.amount_label && item.amount_value !== undefined) {
        return {
            label: item.amount_label,
            value: formatMoney(Number(item.amount_value), { thousand: true })
        }
    }
    const status = Number(item.order_status)
    const need = Number(item.need_pay_amount ?? 0)
    if (need > 0 && ![6, 8, 9].includes(status)) {
        return { label: '待付', value: formatMoney(need, { thousand: true }) }
    }
    const paid = Number(item.paid_amount ?? 0)
    if (paid <= 0 && [6, 8, 9].includes(status)) {
        const orderAmt = Number(item.pay_amount ?? item.total_amount ?? 0)
        return { label: '订单金额', value: formatMoney(orderAmt, { thousand: true }) }
    }
    const displayPaid = Number(item.paid_amount ?? item.pay_amount ?? item.total_amount ?? 0)
    return { label: '已付', value: formatMoney(displayPaid, { thousand: true }) }
}

/** 一张卡只有一个按钮；只有需要用户动手的才用实心 */
function primaryAction(item: any) {
    const status = Number(item.order_status)
    if (status === 1) return { text: '去支付', solid: true }
    return { text: '查看详情', solid: false }
}

async function fetchStats() {
    try {
        const res: any = await getOrderStatistics()
        stats.value = (res && typeof res === 'object' ? res : {}) as Record<string, number>
    } catch {
        // 统计失败只是不显示角标，不影响列表
    }
}

async function fetchPage(reset = false, size = pageSize) {
    if (reset) {
        error.value = false
    } else {
        if (loadingMore.value || !hasMore.value) return
    }

    const currentRequest = ++requestId
    const requestPage = reset ? 1 : page.value
    loadingMore.value = true
    loadMoreFailed.value = false
    try {
        const res = await getOrderList({
            page: requestPage,
            page_size: size,
            status: activeStatus.value
        })
        if (currentRequest !== requestId) return
        const paged = normalizePage<any>(res, requestPage, size)
        list.value = reset ? paged.list : [...list.value, ...paged.list]
        total.value = paged.total
        hasMore.value = paged.hasMore
        page.value = Math.floor(list.value.length / pageSize) + 1
    } catch (e) {
        rawError.value = e
        if (currentRequest === requestId && reset && !list.value.length) error.value = true
        else if (currentRequest === requestId && !reset) loadMoreFailed.value = true
    } finally {
        if (currentRequest === requestId) {
            loadingMore.value = false
            firstLoading.value = false
            uni.stopPullDownRefresh()
        }
    }
}

function reload() {
    firstLoading.value = true
    fetchStats()
    fetchPage(true)
}

/** 返回列表时按「已加载条数」刷新，避免内容塌陷导致滚动位置丢失 */
function refreshKeepingCount() {
    const keep = list.value.length
    if (keep <= pageSize) {
        fetchPage(true)
        return
    }
    fetchPage(true, keep)
}

function switchTab(key: string) {
    if (key === activeKey.value) return
    activeKey.value = key
    list.value = []
    reload()
}

function goDetail(id: number) {
    navigate({ path: '/packages/pkg-order/pages/detail/detail', query: { id } })
}

function onAction(item: any) {
    const status = Number(item.order_status)
    if (status === 1) {
        navigate({
            path: '/packages/pkg-order/pages/detail/detail',
            query: { id: item.id, pay: '1' }
        })
        return
    }
    goDetail(item.id)
}

function goQuery() {
    uni.switchTab({ url: '/pages/query/query' })
}

function findTabByStatus(raw: string) {
    const cleaned = raw.trim()
    if (!cleaned) return undefined
    if (cleaned === 'all') return TABS[0]
    const exact = TABS.find((item) => item.status === cleaned)
    if (exact) return exact
    const rawTokens = cleaned
        .split(',')
        .map((s) => s.trim())
        .filter(Boolean)
    if (!rawTokens.length) return undefined
    return TABS.find((item) => {
        if (!item.status) return false
        const tabTokens = item.status.split(',').map((s) => s.trim())
        return rawTokens.every((tok) => tabTokens.includes(tok))
    })
}

onLoad((options: any) => {
    const rawTab = String(options?.tab || '').trim()
    const rawStatus = String(options?.status || '').trim()
    const matched = TABS.find((item) => item.key === rawTab) || findTabByStatus(rawStatus)
    if (matched && matched.key !== activeKey.value) {
        activeKey.value = matched.key
        list.value = []
        firstLoading.value = true
    }
})

onShow(() => {
    // 兼容「我的」写入的 tab 状态（switchTab 不能带 query）
    const status = uni.getStorageSync('order_tab_status')
    if (status !== '' && status !== undefined && status !== null) {
        uni.removeStorageSync('order_tab_status')
        const tab = findTabByStatus(String(status))
        if (tab && tab.key !== activeKey.value) {
            activeKey.value = tab.key
            list.value = []
            firstLoading.value = true
        }
    }
    fetchStats()
    refreshKeepingCount()
})
onPullDownRefresh(() => {
    fetchStats()
    fetchPage(true)
})
onReachBottom(() => fetchPage(false))
</script>

<style lang="scss" scoped>
.pad {
    padding: var(--sp-4);
}

.od-tabs-scroll {
    width: 100%;
    white-space: nowrap;
}

.od-tabs {
    display: inline-flex;
    align-items: center;
    gap: 12rpx;
    padding: 24rpx 42rpx 8rpx;
}

.od-pill {
    flex: none;
    height: 56rpx;
    padding: 0 24rpx;
    border-radius: var(--r-pill);
    /* 可点控件边界（模板 @tap="switchTab" + hover-class + is-on）：--c-line-strong 1.60:1 → --c-control-border 3.77:1 */
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);
    display: flex;
    align-items: center;
    gap: 8rpx;

    &__text {
        font-size: var(--fs-23);
        color: var(--c-ink-2);
    }

    &__num {
        font-size: var(--fs-19);
        color: var(--c-ink-3);
    }

    &.is-on {
        background: var(--c-ink);
        border-color: var(--c-ink);

        .od-pill__text {
            color: var(--c-on-dark);
        }

        .od-pill__num {
            color: var(--c-brand-soft);
        }
    }
}

.list {
    padding: 16rpx 0 calc(var(--sp-6) + var(--safe-bottom));
}

.card {
    margin: 0 42rpx 20rpx;
    padding: 26rpx 26rpx 22rpx;
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);
    box-shadow: var(--shadow-card);

    &__top {
        display: flex;
        align-items: center;
        gap: 18rpx;
    }

    &__av {
        width: 84rpx;
        height: 84rpx;
        border-radius: var(--r-24);
        background: var(--c-neutral-bg);
        flex: none;

        &--txt {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--c-brand-bg);
        }
    }

    &__av-text {
        font-family: var(--font-serif);
        font-size: var(--fs-30);
        color: var(--c-brand-text);
    }

    &__who {
        flex: 1;
        min-width: 0;
    }

    &__nm {
        display: flex;
        align-items: center;
        gap: 10rpx;
    }

    &__name {
        font-size: var(--fs-29);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        letter-spacing: 0.02em;
    }

    &__pkg {
        display: block;
        margin-top: 8rpx;
        font-size: var(--fs-21);
        color: var(--c-ink-3);
    }

    &__warn {
        display: flex;
        align-items: center;
        gap: 8rpx;
        /* 图标颜色由父级继承（tn-icon 不认 var()，只认 tn-* 类或 hex）。
           两个文字子元素各自声明了 --c-warning，不会被这条覆盖。 */
        color: var(--c-warning);
        margin-top: 18rpx;
        padding: 12rpx 16rpx;
        border-radius: var(--r-14);
        background: var(--c-warning-bg);
    }

    &__warn-text {
        font-size: var(--fs-20);
        color: var(--c-warning);
    }

    &__warn-num {
        font-family: var(--font-serif);
        font-size: var(--fs-22);
        color: var(--c-warning);
    }

    &__line {
        display: flex;
        align-items: center;
        gap: 10rpx;
        /* 同上：图标继承父级；.card__line-text 自己声明了 --c-ink-2，不受影响 */
        color: var(--c-ink-3);
        margin-top: 18rpx;
        padding-top: 18rpx;
        border-top: 1rpx solid var(--c-line);
    }

    &__line-text {
        font-size: var(--fs-22);
        color: var(--c-ink-2);
    }

    &__foot {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        margin-top: 18rpx;
    }

    &__amt {
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__amt-label {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }

    &__amt-value {
        font-family: var(--font-serif);
        font-size: var(--fs-34);
        color: var(--c-ink);
    }

    &__btn {
        height: 64rpx;
        padding: 0 30rpx;
        border-radius: var(--r-pill);
        /* 可点控件边界：--c-line-strong 对卡面 1.60:1（不可见），按项目既有约定改
           --c-control-border（3.77:1）。判定依据是模板里有 @tap.stop + hover-class，
           不是类名 —— `__btn` 这个名字看起来"当然是按钮"，但同类里 `.chip-tag`/`.seg__item`
           都不含可点字样却也是控件，反之也有叫 chip 却不可点的（`.ohero__chip`）。 */
        border: 1rpx solid var(--c-control-border);
        display: flex;
        align-items: center;
        justify-content: center;

        &-text {
            font-size: var(--fs-xs);
            color: var(--c-ink);
        }

        &.is-solid {
            background: var(--c-ink);
            border-color: var(--c-ink);

            .card__btn-text {
                color: var(--c-on-dark);
            }
        }
    }
}

.foot {
    padding: 26rpx 0 8rpx;
    text-align: center;

    &__rule {
        font-family: var(--font-serif);
        font-size: var(--fs-20);
        letter-spacing: 0.16em;
        color: var(--c-ink-3);
    }
}
</style>
