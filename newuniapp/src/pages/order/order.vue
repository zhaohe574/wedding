<template>
    <view class="page-wrap order-page">
        <!-- 状态分组：11 个后端状态收敛为 5 组 -->
        <scroll-view class="tabs" scroll-x :show-scrollbar="false">
            <view class="tabs__row">
                <view hover-class="is-pressed"
                    v-for="tab in TABS"
                    :key="tab.key"
                    class="tabs__item"
                    :class="{ 'is-active': tab.key === activeKey }"
                    @tap="switchTab(tab.key)"
                >
                    <text class="tabs__text">{{ tab.label }}</text>
                    <view v-if="tab.key === activeKey" class="tabs__line" />
                </view>
            </view>
        </scroll-view>

        <!-- 骨架 -->
        <view v-if="firstLoading" class="page-pad">
            <base-skeleton :rows="4" />
        </view>

        <!-- 错误 -->
        <base-empty v-else-if="error" status="error"
            :error-kind="view?.errorKind || 'unknown'"
            :error-text="view?.title" :desc="view?.desc"
            :primary-action="view?.primaryAction"
            fixed @primary="onPrimary" @retry="reload" />

        <!-- 空 -->
        <base-empty
            v-else-if="!list.length"
            status="empty" empty-icon="empty-order"
            empty-text="还没有相关订单"
            action-text="去看看服务"
            :show-action="true"
            fixed
            @action="goHome"
        />

        <!-- 列表 -->
        <view v-else class="page-pad list">
            <base-card v-for="item in list" :key="item.id" compact clickable @tap="goDetail(item.id)">
                <view class="row">
                    <text class="row__sn ellipsis" aria-label="订单号，点击卡片可复制">{{ item.order_sn }}</text>
                    <status-tag :label="statusLabel(item)" :tone="statusTone(item)" size="sm" />
                </view>

                <view class="body">
                    <view class="body__left">
                        <text class="body__title ellipsis-2">{{ itemTitle(item) }}</text>
                        <text v-if="item.service_date" class="body__meta">服务日期 {{ item.service_date }}</text>
                        <text v-if="regionText(item)" class="body__meta">{{ regionText(item) }}</text>
                    </view>
                    <view class="body__right">
                        <price size="md" :content="item.total_amount ?? item.order_amount ?? 0" />
                        <text v-if="needPayText(item)" class="body__need">{{ needPayText(item) }}</text>
                    </view>
                </view>

                <view class="foot">
                    <text class="foot__time">{{ item.create_time }}</text>
                    <view class="foot__actions">
                        <base-button
                            v-if="canPay(item)"
                            text="去支付"
                            size="sm"
                            variant="ghost"
                            :block="false"
                            @click.stop="goPay(item.id)"
                        />
                        <base-button
                            v-else
                            text="查看详情"
                            size="sm"
                            variant="ghost"
                            :block="false"
                            @click.stop="goDetail(item.id)"
                        />
                    </view>
                </view>
            </base-card>

            <load-more :status="loadMoreStatus" @retry="fetchPage(false)" />
        </view>

        <app-tabbar current="/pages/order/order" />
        <app-toast />
    </view>
</template>

<script setup lang="ts">
/**
 * 订单列表。
 *
 * 后端有 11 个订单状态，移动端不需要 11 个 tab —— 收敛为 5 组，
 * 由后端 /order/lists 的 status 参数支持逗号分隔多值实现（已同步改动 OrderLogic::getUserOrders）。
 */
import { computed, ref } from 'vue'
import { onPullDownRefresh, onReachBottom, onShow } from '@dcloudio/uni-app'
import {
    ORDER_STATUS_NAME_MAP,
    ORDER_STATUS_TONE,
    resolveStatusLabel,
    resolveTone,
    type OrderStatus
} from '@shared/contracts'
import { getOrderList } from '@/api/order'
import { normalizePage } from '@/request'
import { navigate } from '@/utils/link'
import { formatMoney } from '@shared/utils/format'
import { resolveErrorView } from '@/utils/resolveErrorView'

const TABS = [
    { key: 'all', label: '全部', status: undefined as string | undefined },
    { key: 'unpaid', label: '待付款', status: '0,1' },
    { key: 'service', label: '待服务', status: '2' },
    { key: 'done', label: '已完成', status: '4,5' },
    { key: 'refund', label: '退款/售后', status: '8,10' }
]

const activeKey = ref('all')
const list = ref<any[]>([])
const page = ref(1)
const pageSize = 10
const total = ref(0)
const hasMore = ref(true)
const loadingMore = ref(false)
const firstLoading = ref(true)
const error = ref(false)
let requestId = 0

/** O5：真实错误驱动 errorKind / 文案 / 恢复动作 */
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') { reload(); return }
    if (kind === 'reselect' || kind === 'back') { uni.navigateBack(); return }
    if (kind === 'login') { uni.navigateTo({ url: '/pages/login/login' }); return }
    // contact / 其余：保持现有客服入口，不新增跳转
}

const loadMoreFailed = ref(false)
const loadMoreStatus = computed<'idle' | 'loading' | 'nomore' | 'error'>(() => {
    if (loadMoreFailed.value) return 'error'
    if (loadingMore.value && list.value.length) return 'loading'
    if (!hasMore.value) return 'nomore'
    return 'idle'
})

const activeStatus = computed(() => TABS.find((t) => t.key === activeKey.value)?.status)

function statusLabel(item: any) {
    const raw = Number(item.order_status)
    const mapped = resolveStatusLabel(ORDER_STATUS_NAME_MAP as any, raw)
    return mapped.known ? mapped.label : item.order_status_desc || mapped.label
}

function statusTone(item: any) {
    return resolveTone(ORDER_STATUS_TONE as any, item.order_status)
}

function itemTitle(item: any) {
    const first = item.items && item.items[0]
    if (!first) return '婚庆服务'
    return first.package_name || first.staff_name || '婚庆服务'
}

function regionText(item: any) {
    return item.service_region_text || ''
}

function needPayText(item: any) {
    if (item.need_pay_label) return item.need_pay_label
    const amount = Number(item.need_pay_amount ?? 0)
    return amount > 0 ? `待付 ¥${formatMoney(amount, { thousand: true })}` : ''
}

function canPay(item: any) {
    return Number(item.order_status) === 1
}

async function fetchPage(reset = false, size = pageSize) {
    if (reset) {
        // 静默刷新失败时保留旧列表的分页游标，不能在响应前回退到第 1 页。
        error.value = false
    } else {
        // 分页请求串行；reset 不受此限制，可 supersede 旧请求。
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
        // 快捷入口、切换 tab 与 onShow 可能连续触发，旧响应不能覆盖新状态。
        if (currentRequest !== requestId) return
        const paged = normalizePage<any>(res, requestPage, size)
        list.value = reset ? paged.list : [...list.value, ...paged.list]
        total.value = paged.total
        hasMore.value = paged.hasMore
        // 游标按「已加载条数」推算：一次取回 N 条时，下一页仍是 N+1
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
    fetchPage(true)
}

/**
 * 返回列表时按「已加载条数」刷新：条数不变 → 列表高度不变 → 滚动位置自然保留。
 * 只取第 1 页会把 30 条替换成 10 条，内容塌陷后滚动位置必然丢失。
 */
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

/** 列表「去支付」直达：进详情后由详情页在 actions.pay 为真时自动拉起支付 */
function goPay(id: number) {
    navigate({ path: '/packages/pkg-order/pages/detail/detail', query: { id, pay: '1' } })
}

function goHome() {
    uni.switchTab({ url: '/pages/index/index' })
}

onShow(() => {
    // switchTab 不能携带 query，消费“我的”写入的状态后立即清理，避免污染下次进入。
    const status = uni.getStorageSync('order_tab_status')
    if (status) {
        uni.removeStorageSync('order_tab_status')
        const tab = TABS.find((item) => item.status === String(status))
        if (tab && tab.key !== activeKey.value) {
            activeKey.value = tab.key
            list.value = []
            firstLoading.value = true
        }
    }
    // 首载与返回统一走这里；按已加载条数静默刷新，避免返回后滚回顶部。
    refreshKeepingCount()
})
onPullDownRefresh(() => fetchPage(true))
onReachBottom(() => fetchPage(false))
</script>

<style lang="scss" scoped>
.order-page {
    padding-bottom: calc(100rpx + var(--safe-bottom) + var(--sp-4));
}

.tabs {
    background: var(--c-surface);
    position: sticky;
    top: 0;
    z-index: 10;
    white-space: nowrap;

    &__row {
        display: inline-flex;
        padding: 0 var(--sp-3);
    }

    &__item {
        position: relative;
        padding: var(--sp-3) var(--sp-3) var(--sp-3);
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    &__text {
        font-size: var(--fs-md);
        color: var(--c-ink-2);
    }

    &__item.is-active &__text {
        color: var(--c-ink);
        font-weight: 500;
    }

    &__line {
        position: absolute;
        bottom: 8rpx;
        width: 40rpx;
        height: 4rpx;
        border-radius: var(--r-pill);
        background: var(--c-brand);
    }
}

.list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding-top: var(--sp-3);
}

.row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: var(--sp-3);
    border-bottom: 1rpx solid var(--c-line);

    &__sn {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.body {
    display: flex;
    gap: var(--sp-3);
    padding: var(--sp-3) 0;

    &__left {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 6rpx;
        min-width: 0;
    }

    &__title {
        font-size: var(--fs-md);
        color: var(--c-ink);
        font-weight: 500;
    }

    &__meta {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__right {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 4rpx;
        flex-shrink: 0;
    }

    &__need {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
    }
}

.foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: var(--sp-3);
    border-top: 1rpx solid var(--c-line);

    &__time {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__actions {
        display: flex;
        gap: var(--sp-2);
    }
}

.loadmore {
    padding: var(--sp-4) 0 var(--sp-6);
    text-align: center;

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}
</style>
