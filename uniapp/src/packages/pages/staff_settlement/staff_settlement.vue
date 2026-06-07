<template>
    <page-meta :page-style="$theme.pageStyle" />

    <PageShell scene="staff" tone="workspace" hasSafeBottom>
        <BaseNavbar title="我的结算" />

        <view class="settlement-page wm-page-content">
            <view class="page-section page-section--top">
                <StaffWorkspaceHero
                    title="我的结算"
                >
                    <template #badges>
                        <StatusBadge v-if="pendingCount > 0" tone="warning" size="sm">
                            待确认 {{ pendingCount }}
                        </StatusBadge>
                        <StatusBadge v-if="failedCount > 0" tone="danger" size="sm">
                            失败 {{ failedCount }}
                        </StatusBadge>
                    </template>

                    <view class="settlement-metrics">
                        <view class="settlement-metric settlement-metric--accent">
                            <text class="settlement-metric__label">待确认金额</text>
                            <text class="settlement-metric__value">{{ pendingAmountText }}</text>
                        </view>
                        <view class="settlement-metric">
                            <text class="settlement-metric__label">结算笔数</text>
                            <text class="settlement-metric__value">{{ settlementCountText }}</text>
                        </view>
                        <view class="settlement-metric">
                            <text class="settlement-metric__label">已结算金额</text>
                            <text class="settlement-metric__value">{{ settledAmountText }}</text>
                        </view>
                    </view>

                    <StaffFilterBar
                        :items="statusTabs"
                        :model-value="currentStatus"
                        @select="handleStatusSelect"
                    />
                </StaffWorkspaceHero>
            </view>

            <view class="page-section page-section--list">
                <StaffSectionHeader
                    :title="listSectionTitle"
                    :description="listSectionDesc"
                    :meta="listSectionMeta"
                />

                <LoadingState v-if="loading && !hasLoaded" text="正在同步结算..." />

                <view v-else-if="settlementList.length" class="settlement-list">
                    <BaseCard
                        v-for="item in settlementList"
                        :key="item.id"
                        variant="glass"
                        scene="staff"
                        class="settlement-card"
                    >
                        <view class="settlement-card__head">
                            <view class="settlement-card__copy">
                                <text class="settlement-card__title">{{ item.order_title }}</text>
                                <text class="settlement-card__meta">{{ item.meta_text }}</text>
                            </view>
                            <StatusBadge :tone="item.badge_tone" size="sm">
                                {{ item.status_text }}
                            </StatusBadge>
                        </view>

                        <view class="settlement-card__amount-panel">
                            <view class="settlement-card__amount-copy">
                                <text class="settlement-card__amount-label">结算金额</text>
                                <text class="settlement-card__amount">{{
                                    formatAmount(item.actual_amount)
                                }}</text>
                            </view>
                            <view class="settlement-card__way-pill">
                                {{ item.settle_way_text || '微信转账' }}
                            </view>
                        </view>

                        <view class="settlement-card__detail-grid">
                            <view class="detail-item">
                                <text class="detail-item__label">服务日期</text>
                                <text class="detail-item__value">{{ item.service_date_text }}</text>
                            </view>
                            <view class="detail-item">
                                <text class="detail-item__label">订单号</text>
                                <text class="detail-item__value">{{ item.order_sn_text }}</text>
                            </view>
                            <view class="detail-item">
                                <text class="detail-item__label">结算时间</text>
                                <text class="detail-item__value">{{ item.settle_time_text }}</text>
                            </view>
                        </view>

                        <view :class="['transfer-line', `transfer-line--${item.transfer_tone}`]">
                            <text class="transfer-line__label">转账状态</text>
                            <text class="transfer-line__text">{{ item.transfer_text }}</text>
                        </view>

                        <view v-if="item.can_receive || item.can_sync" class="settlement-card__actions">
                            <view
                                v-if="item.can_receive"
                                class="action-button action-button--primary"
                                @click="confirmTransfer(item)"
                            >
                                <tn-icon name="wechat-fill" size="22" color="#111111" />
                                <text class="action-button__text action-button__text--primary">
                                    确认收款
                                </text>
                            </view>
                            <view
                                v-if="item.can_sync"
                                class="action-button action-button--ghost"
                                @click="syncTransfer(item)"
                            >
                                <tn-icon name="refresh" size="22" color="#5F5A50" />
                                <text class="action-button__text">同步状态</text>
                            </view>
                        </view>
                    </BaseCard>
                </view>

                <BaseCard v-else-if="hasLoaded" variant="quiet" scene="staff" class="empty-card">
                    <EmptyState
                        :title="emptyStateTitle"
                        description="服务完成并满足结算条件后会显示在这里"
                    />
                </BaseCard>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'

import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import StaffFilterBar from '@/packages/components/staff-workspace/staff-filter-bar.vue'
import StaffSectionHeader from '@/packages/components/staff-workspace/staff-section-header.vue'
import StaffWorkspaceHero from '@/packages/components/staff-workspace/staff-workspace-hero.vue'
import {
    staffCenterSettlementLists,
    staffCenterSettlementReceive,
    staffCenterSettlementSync
} from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

type BadgeTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info'
type TransferTone = 'neutral' | 'success' | 'warning' | 'danger'
type StatusValue = number | ''

interface SettlementItem {
    id: number
    settlement_sn: string
    actual_amount: number | string
    service_date: string
    settle_time?: number | string
    create_time?: number | string
    fail_reason?: string
    status: number
    status_text: string
    settle_way_text: string
    order?: { order_sn?: string }
    order_item?: { package_name?: string; staff_name?: string }
    transfer_summary?: {
        count?: number
        success_count?: number
        wait_confirm_count?: number
        status_text?: string
        package_info?: string
        fail_reason?: string
    }
    can_receive?: boolean
}

interface DisplaySettlementItem extends SettlementItem {
    order_title: string
    meta_text: string
    badge_tone: BadgeTone
    transfer_tone: TransferTone
    transfer_text: string
    service_date_text: string
    order_sn_text: string
    settle_time_text: string
    can_sync: boolean
}

const $theme = useThemeStore()
const loading = ref(false)
const hasLoaded = ref(false)
const currentStatus = ref<StatusValue>('')
const settlementList = ref<DisplaySettlementItem[]>([])

const statusTabs = [
    { label: '全部', value: '' },
    { label: '待确认', value: 4 },
    { label: '已结算', value: 1 },
    { label: '失败', value: 3 }
]

const toNumber = (value: unknown) => {
    const result = Number(value ?? 0)

    return Number.isFinite(result) ? result : 0
}

const formatMoney = (value: unknown) => {
    const amount = toNumber(value)

    return amount.toFixed(2)
}

const formatAmount = (value: unknown) => `¥${formatMoney(value)}`

const sumAmountByStatus = (status: number) =>
    settlementList.value
        .filter((item) => item.status === status)
        .reduce((sum, item) => sum + toNumber(item.actual_amount), 0)

const pendingCount = computed(() => settlementList.value.filter((item) => item.status === 4).length)
const failedCount = computed(() => settlementList.value.filter((item) => item.status === 3).length)
const pendingAmountText = computed(() => formatAmount(sumAmountByStatus(4)))
const settledAmountText = computed(() => formatAmount(sumAmountByStatus(1)))
const settlementCountText = computed(() => `${settlementList.value.length}笔`)

const heroDescription = computed(() => {
    if (currentStatus.value === 4) return '待确认转账优先处理'
    if (currentStatus.value === 1) return '已完成结算集中对账'
    if (currentStatus.value === 3) return '失败记录等待同步或后台处理'

    return '关注服务款转账与到账进度'
})

const heroMetaText = computed(() => {
    if (!hasLoaded.value) return '正在加载结算数据'

    return `当前筛选 ${settlementList.value.length} 笔`
})

const resolveBadgeTone = (status: number): BadgeTone => {
    if (status === 1) return 'success'
    if (status === 3) return 'danger'
    if (status === 4) return 'warning'

    return 'neutral'
}

const resolveTransferTone = (status: number, hasFailReason: boolean): TransferTone => {
    if (hasFailReason || status === 3) return 'danger'
    if (status === 1) return 'success'
    if (status === 4) return 'warning'

    return 'neutral'
}

const formatDateTime = (value: number | string | undefined) => {
    if (!value) return ''

    let date: Date

    if (typeof value === 'number') {
        date = new Date(value < 1e12 ? value * 1000 : value)
    } else {
        date = new Date(String(value).replace(/-/g, '/'))
    }

    if (Number.isNaN(date.getTime())) return String(value)

    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    const hour = String(date.getHours()).padStart(2, '0')
    const minute = String(date.getMinutes()).padStart(2, '0')

    return `${year}-${month}-${day} ${hour}:${minute}`
}

const formatSettlement = (item: SettlementItem): DisplaySettlementItem => {
    const transfer = item.transfer_summary || {}
    const packageName = String(item.order_item?.package_name || '').trim()
    const orderSn = String(item.order?.order_sn || '').trim()
    const serviceDate = String(item.service_date || '').trim()
    const failReason = String(transfer.fail_reason || item.fail_reason || '').trim()
    const status = toNumber(item.status)
    const transferText = transfer.status_text
        ? `${transfer.status_text}${
              transfer.count ? ` ${transfer.success_count || 0}/${transfer.count}` : ''
          }`
        : status === 0
          ? '待后台发起转账'
          : status === 1
            ? '转账已到账，结算完成'
          : '暂无转账记录'

    return {
        ...item,
        order_title: packageName || '服务结算',
        meta_text: [serviceDate || '待补充服务日期', orderSn ? `订单号 ${orderSn}` : '订单号待同步']
            .filter(Boolean)
            .join(' · '),
        badge_tone: resolveBadgeTone(status),
        transfer_tone: resolveTransferTone(status, failReason !== ''),
        transfer_text: failReason || transferText,
        service_date_text: serviceDate || '待补充',
        order_sn_text: orderSn || '待同步',
        settle_time_text:
            status === 1 ? formatDateTime(item.settle_time) || '已完成' : '未完成结算',
        can_sync: status === 4 || status === 3
    }
}

const listSectionTitle = computed(() => {
    if (currentStatus.value === 4) return '待确认转账'
    if (currentStatus.value === 1) return '已结算记录'
    if (currentStatus.value === 3) return '结算失败'

    return '全部结算'
})

const listSectionDesc = computed(() => {
    if (currentStatus.value === 4) return '确认收款后会同步为已结算状态'
    if (currentStatus.value === 1) return '用于核对已完成的服务结算款'
    if (currentStatus.value === 3) return '查看失败原因并同步最新转账状态'

    return '按服务日期和转账状态查看结算记录'
})

const listSectionMeta = computed(() => `${settlementList.value.length} 笔`)

const emptyStateTitle = computed(() => {
    if (currentStatus.value === 4) return '暂无待确认转账'
    if (currentStatus.value === 1) return '暂无已结算记录'
    if (currentStatus.value === 3) return '暂无失败记录'

    return '暂无结算记录'
})

const resolveErrorMessage = (error: unknown) => {
    if (typeof error === 'string') return error
    if (error && typeof error === 'object') {
        const target = error as { msg?: string; message?: string }

        return target.msg || target.message || '操作失败'
    }
    return '操作失败'
}

const loadList = async () => {
    loading.value = true
    try {
        const res = await staffCenterSettlementLists({
            status: currentStatus.value,
            page_no: 1,
            page_size: 50
        })
        const list = Array.isArray(res?.lists) ? res.lists : []
        settlementList.value = list.map(formatSettlement)
        hasLoaded.value = true
    } catch (error) {
        uni.showToast({ title: resolveErrorMessage(error), icon: 'none' })
    } finally {
        loading.value = false
    }
}

const switchStatus = (status: number | '') => {
    currentStatus.value = status
    loadList()
}

const handleStatusSelect = (value: string | number) => {
    switchStatus(value === '' ? '' : Number(value))
}

const requestMerchantTransfer = (payload: {
    mch_id?: string
    app_id?: string
    package?: string
    package_info?: string
}) => {
    const transferPackage = String(payload.package || payload.package_info || '')
    if (!transferPackage) {
        uni.showToast({ title: '当前转账缺少确认参数', icon: 'none' })
        return
    }

    // #ifdef MP-WEIXIN
    const wxApi = uni as unknown as {
        requestMerchantTransfer?: (options: {
            mchId: string
            appId: string
            package: string
            success?: () => void
            fail?: (error: unknown) => void
        }) => void
    }
    if (typeof wxApi.requestMerchantTransfer !== 'function') {
        uni.showToast({ title: '当前微信版本不支持确认商家转账', icon: 'none' })
        return
    }

    wxApi.requestMerchantTransfer({
        mchId: String(payload.mch_id || ''),
        appId: String(payload.app_id || ''),
        package: transferPackage,
        success: () => {
            uni.showToast({ title: '确认后正在同步', icon: 'none' })
            loadList()
        },
        fail: (error) => {
            uni.showToast({ title: resolveErrorMessage(error), icon: 'none' })
        }
    })
    // #endif

    // #ifndef MP-WEIXIN
    uni.showToast({ title: '请在微信小程序内确认收款', icon: 'none' })
    // #endif
}

const confirmTransfer = async (item: DisplaySettlementItem) => {
    try {
        const res = await staffCenterSettlementReceive({ id: item.id })
        requestMerchantTransfer(res || {})
    } catch (error) {
        uni.showToast({ title: resolveErrorMessage(error), icon: 'none' })
    }
}

const syncTransfer = async (item: DisplaySettlementItem) => {
    try {
        await staffCenterSettlementSync({ id: item.id })
        uni.showToast({ title: '已同步', icon: 'none' })
        loadList()
    } catch (error) {
        uni.showToast({ title: resolveErrorMessage(error), icon: 'none' })
    }
}

onShow(async () => {
    $theme.setScene('staff')

    if (!(await ensureStaffCenterAccess())) return

    await loadList()
})
</script>

<style lang="scss" scoped>
.settlement-page {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
    min-height: 100vh;
    padding: 20rpx 0 calc(32rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;
    background:
        radial-gradient(circle at top left, rgba(11, 11, 11, 0.08) 0, rgba(248, 247, 242, 0) 34%),
        linear-gradient(180deg, rgba(248, 247, 242, 0.94) 0%, #ffffff 48%);
}

.page-section {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.settlement-metrics {
    display: grid;
    grid-template-columns: 1.2fr 0.9fr 1.1fr;
    gap: 12rpx;
}

.settlement-metric {
    min-width: 0;
    min-height: 124rpx;
    padding: 18rpx 16rpx;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    border-radius: var(--wm-radius-card-soft, 14rpx);
    background: rgba(247, 246, 241, 0.86);
    border: 1rpx solid rgba(216, 194, 138, 0.55);
    box-sizing: border-box;

    &--accent {
        background: linear-gradient(180deg, #111111 0%, #2f2924 100%);
        border-color: rgba(200, 164, 93, 0.76);
    }

    &__label,
    &__value {
        display: block;
        line-height: 1.2;
    }

    &__label {
        font-size: 22rpx;
        font-weight: 600;
        color: var(--wm-text-secondary, #5f5a50);
    }

    &__value {
        margin-top: 12rpx;
        font-size: 30rpx;
        font-weight: 700;
        color: var(--wm-text-primary, #111111);
    }
}

.settlement-metric--accent {
    .settlement-metric__label {
        color: rgba(255, 255, 255, 0.72);
    }

    .settlement-metric__value {
        color: #f8f2e4;
    }
}

.settlement-list {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.settlement-card {
    background: #ffffff;

    &__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 6rpx;
    }

    &__title {
        font-size: 28rpx;
        font-weight: 700;
        line-height: 1.35;
        color: #111111;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__meta {
        font-size: 21rpx;
        font-weight: 600;
        line-height: 1.45;
        color: #5f5a50;
    }

    &__amount-panel {
        margin-top: 22rpx;
        padding: 22rpx;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18rpx;
        border-radius: var(--wm-radius-card-soft, 14rpx);
        background: linear-gradient(180deg, rgba(248, 247, 242, 0.95) 0%, #ffffff 100%);
        border: 1rpx solid rgba(226, 222, 213, 0.88);
    }

    &__amount-copy {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 8rpx;
    }

    &__amount-label {
        font-size: 21rpx;
        font-weight: 600;
        line-height: 1.2;
        color: #5f5a50;
    }

    &__amount {
        font-size: 42rpx;
        font-weight: 700;
        line-height: 1;
        color: #111111;
    }

    &__way-pill {
        flex-shrink: 0;
        min-height: 44rpx;
        padding: 0 16rpx;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--wm-radius-pill, 999rpx);
        background: rgba(200, 164, 93, 0.12);
        border: 1rpx solid rgba(200, 164, 93, 0.28);
        font-size: 21rpx;
        font-weight: 700;
        color: #7a6128;
        box-sizing: border-box;
    }

    &__detail-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10rpx;
        margin-top: 16rpx;
    }

    &__actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12rpx;
        margin-top: 18rpx;
    }
}

.detail-item {
    min-width: 0;
    padding: 16rpx 14rpx;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    border-radius: var(--wm-radius-card-soft, 14rpx);
    background: rgba(247, 246, 241, 0.76);
    box-sizing: border-box;

    &__label,
    &__value {
        display: block;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__label {
        font-size: 20rpx;
        font-weight: 600;
        color: #9a9388;
    }

    &__value {
        font-size: 22rpx;
        font-weight: 700;
        color: #111111;
    }
}

.transfer-line {
    margin-top: 16rpx;
    padding: 16rpx 18rpx;
    display: flex;
    align-items: flex-start;
    gap: 12rpx;
    border-radius: var(--wm-radius-card-soft, 14rpx);
    background: rgba(89, 106, 122, 0.06);
    border: 1rpx solid rgba(89, 106, 122, 0.12);
    box-sizing: border-box;

    &--success {
        background: rgba(79, 111, 90, 0.08);
        border-color: rgba(79, 111, 90, 0.18);
    }

    &--warning {
        background: rgba(159, 122, 46, 0.08);
        border-color: rgba(159, 122, 46, 0.18);
    }

    &--danger {
        background: rgba(138, 75, 69, 0.08);
        border-color: rgba(138, 75, 69, 0.18);
    }

    &__label {
        flex-shrink: 0;
        font-size: 21rpx;
        font-weight: 700;
        line-height: 1.45;
        color: #9a9388;
    }

    &__text {
        flex: 1;
        min-width: 0;
        font-size: 22rpx;
        font-weight: 700;
        line-height: 1.45;
        color: #5f5a50;
    }
}

.transfer-line--success .transfer-line__text {
    color: #4f6f5a;
}

.transfer-line--warning .transfer-line__text {
    color: #8a6b26;
}

.transfer-line--danger .transfer-line__text {
    color: #8a4b45;
}

.action-button {
    min-height: 72rpx;
    padding: 0 22rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    border-radius: var(--wm-radius-card-soft, 14rpx);
    box-sizing: border-box;

    &__text {
        font-size: 23rpx;
        font-weight: 700;
        line-height: 1;
        color: #5f5a50;

        &--primary {
            color: #111111;
        }
    }
}

.action-button--primary {
    background: linear-gradient(135deg, #f8f2e4 0%, #d8c28a 100%);
    border: 1rpx solid rgba(200, 164, 93, 0.42);
}

.action-button--ghost {
    background: #ffffff;
    border: 1rpx solid var(--wm-color-border, #e2ded5);
}

.empty-card {
    background: #ffffff;

    :deep(.empty-state-block) {
        padding: 58rpx 28rpx 64rpx;
    }
}

@media screen and (max-width: 360px) {
    .settlement-metrics,
    .settlement-card__detail-grid,
    .settlement-card__actions {
        grid-template-columns: 1fr;
    }

    .settlement-metric {
        min-height: 106rpx;
    }

    .settlement-card__amount-panel {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
