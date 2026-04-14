<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="我的申请" />

        <view class="change-list-page wm-page-content">
            <scroll-view scroll-x class="change-list-page__filter-scroll" :show-scrollbar="false">
                <view class="change-list-page__filter-row">
                    <view
                        v-for="tab in typeTabs"
                        :key="tab.value"
                        class="change-list-page__filter-chip"
                        :class="{
                            'change-list-page__filter-chip--active': currentType === tab.value
                        }"
                        @click="changeType(tab.value)"
                    >
                        <text class="change-list-page__filter-chip-text">{{ tab.label }}</text>
                    </view>
                </view>
            </scroll-view>

            <view class="change-list-page__content">
                <view v-if="loading && list.length === 0" class="change-list-page__loading">
                    <tn-loading size="72" mode="flower" :color="$theme.primaryColor" />
                    <text class="change-list-page__loading-text">加载申请记录中...</text>
                </view>

                <EmptyState
                    v-else-if="list.length === 0"
                    :title="currentType === 'change' ? '暂无变更申请' : '暂无暂停申请'"
                    :description="
                        currentType === 'change'
                            ? '改期、加项等申请提交后会在这里统一查看进度。'
                            : '暂停申请提交后会在这里统一查看审核与恢复状态。'
                    "
                >
                    <template #icon>
                        <tn-icon
                            :name="currentType === 'change' ? 'file-text' : 'clock'"
                            size="120"
                            color="#D8CEC8"
                        />
                    </template>
                </EmptyState>

                <view v-else class="change-record-list">
                    <BaseCard
                        v-for="item in list"
                        :key="item.id"
                        variant="surface"
                        scene="consumer"
                        :interactive="true"
                        class="change-record-card"
                        @click="goDetail(item)"
                    >
                        <view class="change-record-card__head">
                            <view class="change-record-card__meta">
                                <text class="change-record-card__no">{{ getRecordNo(item) }}</text>
                                <view class="change-record-card__badges">
                                    <StatusBadge :tone="getTypeTone(item)" size="sm">
                                        {{ getTypeLabel(item) }}
                                    </StatusBadge>
                                    <StatusBadge :tone="getStatusTone(item)" size="sm">
                                        {{ getStatusLabel(item) }}
                                    </StatusBadge>
                                </view>
                            </view>
                            <text class="change-record-card__time">{{ item.create_time }}</text>
                        </view>

                        <text class="change-record-card__title">{{ getRecordTitle(item) }}</text>
                        <text class="change-record-card__summary">
                            {{ getPrimarySummary(item) }}
                        </text>
                        <text
                            v-if="getSecondarySummary(item)"
                            class="change-record-card__summary change-record-card__summary--muted"
                        >
                            {{ getSecondarySummary(item) }}
                        </text>

                        <view v-if="getReasonText(item)" class="change-record-card__reason">
                            <text class="change-record-card__reason-label">原因</text>
                            <text class="change-record-card__reason-text">
                                {{ getReasonText(item) }}
                            </text>
                        </view>

                        <view class="change-record-card__summary-box">
                            <text class="change-record-card__summary-title">当前进度</text>
                            <text class="change-record-card__summary-text">
                                {{ getStatusSummary(item) }}
                            </text>
                            <text
                                v-if="getResultText(item)"
                                class="change-record-card__summary-text change-record-card__summary-text--muted"
                            >
                                {{ getResultText(item) }}
                            </text>
                        </view>

                        <view class="change-record-card__foot">
                            <text class="change-record-card__foot-note">
                                {{ getFootNote(item) }}
                            </text>
                            <view
                                v-if="canCancel(item)"
                                class="change-record-card__actions"
                                @click.stop
                            >
                                <BaseButton size="sm" variant="danger" @click="handleCancel(item)">
                                    取消申请
                                </BaseButton>
                            </view>
                        </view>
                    </BaseCard>

                    <view v-if="hasMore" class="change-list-page__load-more">
                        <view v-if="loading" class="change-list-page__load-more-inner">
                            <tn-loading size="36" mode="flower" :color="$theme.primaryColor" />
                            <text class="change-list-page__load-more-text">加载中...</text>
                        </view>
                        <text
                            v-else
                            class="change-list-page__load-more-text change-list-page__load-more-text--action"
                            @click="loadMore"
                        >
                            加载更多
                        </text>
                    </view>

                    <view v-else class="change-list-page__load-more">
                        <text class="change-list-page__load-more-text">没有更多了</text>
                    </view>
                </view>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad, onReachBottom, onShow } from '@dcloudio/uni-app'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import {
    cancelChange,
    cancelPause,
    getChangeList,
    getPauseList
} from '@/packages/common/api/orderChange'
import { useThemeStore } from '@/stores/theme'
import {
    getChangeStatusMeta,
    getChangeTypeMeta,
    getPauseStatusMeta,
    getPauseTypeMeta
} from './shared'

const $theme = useThemeStore()

const typeTabs = [
    { label: '变更申请', value: 'change' },
    { label: '暂停申请', value: 'pause' }
]

const currentType = ref<'change' | 'pause'>('change')
const list = ref<any[]>([])
const loading = ref(false)
const page = ref(1)
const hasMore = ref(true)

const getTypeTone = (item: any) => {
    return currentType.value === 'change'
        ? getChangeTypeMeta(Number(item?.change_type || 0)).tone
        : getPauseTypeMeta(Number(item?.pause_type || 0)).tone
}

const getStatusTone = (item: any) => {
    return currentType.value === 'change'
        ? getChangeStatusMeta(Number(item?.change_status || 0)).tone
        : getPauseStatusMeta(Number(item?.pause_status || 0)).tone
}

const getRecordNo = (item: any) =>
    currentType.value === 'change'
        ? item?.change_sn || '变更单待生成'
        : item?.pause_sn || '暂停单待生成'

const getTypeLabel = (item: any) =>
    currentType.value === 'change'
        ? item?.change_type_desc || getChangeTypeMeta(Number(item?.change_type || 0)).label
        : item?.pause_type_desc || getPauseTypeMeta(Number(item?.pause_type || 0)).label

const getStatusLabel = (item: any) =>
    currentType.value === 'change'
        ? item?.change_status_desc || getChangeStatusMeta(Number(item?.change_status || 0)).label
        : item?.pause_status_desc || getPauseStatusMeta(Number(item?.pause_status || 0)).label

const getRecordTitle = (item: any) => {
    if (currentType.value === 'pause') {
        return '暂停申请'
    }

    const map: Record<number, string> = {
        1: '改期申请',
        2: '换人申请',
        3: '加项申请'
    }
    return map[Number(item?.change_type || 0)] || '变更申请'
}

const getPrimarySummary = (item: any) => {
    if (currentType.value === 'pause') {
        return `暂停周期：${item?.pause_start_date || '待补充'} ~ ${
            item?.pause_end_date || '待补充'
        }`
    }

    const type = Number(item?.change_type || 0)
    if (type === 1) {
        return `服务日期：${item?.old_service_date || '待补充'} → ${
            item?.new_service_date || '待补充'
        }`
    }
    if (type === 2) {
        return `人员变更：${item?.old_staff_name || '待补充'} → ${item?.new_staff_name || '待补充'}`
    }
    if (type === 3) {
        return `新增服务：${item?.add_staff_name || '待补充'} / ${
            item?.add_package_name || '未选择套餐'
        }`
    }
    return '申请内容待补充'
}

const getSecondarySummary = (item: any) => {
    if (currentType.value === 'pause') {
        return `计划暂停 ${item?.pause_days || 0} 天`
    }

    const type = Number(item?.change_type || 0)
    if (type === 2 && Number(item?.price_diff || 0) !== 0) {
        return `差价：${Number(item.price_diff) > 0 ? '+' : ''}${item.price_diff} 元`
    }
    if (type === 3) {
        return `服务日期：${item?.add_service_date || '待补充'}`
    }
    return ''
}

const getReasonText = (item: any) =>
    currentType.value === 'change'
        ? String(item?.apply_reason || '').trim()
        : String(item?.pause_reason || '').trim()

const getStatusSummary = (item: any) =>
    currentType.value === 'change'
        ? getChangeStatusMeta(Number(item?.change_status || 0)).summary
        : getPauseStatusMeta(Number(item?.pause_status || 0)).summary

const getResultText = (item: any) => {
    const fields = [
        item?.audit_remark,
        item?.reject_reason,
        item?.handle_result,
        item?.admin_remark,
        item?.remark
    ]
        .map((value) => String(value || '').trim())
        .filter(Boolean)

    if (fields.length) {
        return fields[0]
    }

    if (currentType.value === 'change') {
        return Number(item?.change_status || 0) === 1
            ? '平台已审核通过，正在按变更结果安排执行。'
            : Number(item?.change_status || 0) === 3
            ? '本次变更已执行完成，可回到订单详情查看最新安排。'
            : ''
    }

    return Number(item?.pause_status || 0) === 1
        ? '订单已暂停，恢复后会继续履约。'
        : Number(item?.pause_status || 0) === 2
        ? '暂停流程已结束，订单已恢复正常履约状态。'
        : ''
}

const getFootNote = (item: any) => {
    if (currentType.value === 'pause') {
        const status = Number(item?.pause_status || 0)
        if (status === 0) return '当前等待平台审核，可在通过前取消申请'
        if (status === 1) return '当前等待平台恢复履约，可到订单详情查看同步状态'
        if (status === 2) return '恢复完成后可继续在订单中跟进后续安排'
        if (status === 3) return '申请未通过，请按处理说明调整后重新提交'
        return '点击查看暂停详情'
    }

    const status = Number(item?.change_status || 0)
    if (status === 0) return '当前等待平台审核，可在通过前取消申请'
    if (status === 1) return '申请已通过，等待平台按确认结果执行'
    if (status === 2) return '申请未通过，可按处理说明调整后重新发起'
    if (status === 3) return '变更已执行完成，请回到订单详情确认最新安排'
    return '点击查看变更详情'
}

const canCancel = (item: any) =>
    currentType.value === 'change'
        ? Number(item?.change_status || 0) === 0
        : Number(item?.pause_status || 0) === 0

const fetchList = async (refresh = false) => {
    if (loading.value) {
        return
    }
    loading.value = true

    try {
        if (refresh) {
            page.value = 1
            list.value = []
        }

        const params = { page: page.value, page_size: 10 }
        let res: any

        if (currentType.value === 'change') {
            res = await getChangeList(params)
            const rawList = Array.isArray(res?.lists) ? res.lists : []
            res = {
                ...res,
                lists: rawList.filter((item: any) => Number(item?.change_type) !== 4)
            }
        } else {
            res = await getPauseList(params)
        }

        const data = Array.isArray(res?.lists) ? res.lists : []

        if (refresh) {
            list.value = data
        } else {
            list.value.push(...data)
        }

        hasMore.value = data.length === 10
    } catch (error) {
        console.error('获取申请列表失败', error)
    } finally {
        loading.value = false
    }
}

const changeType = (type: 'change' | 'pause') => {
    if (currentType.value === type) {
        return
    }
    currentType.value = type
    void fetchList(true)
}

const loadMore = () => {
    if (hasMore.value && !loading.value) {
        page.value += 1
        void fetchList()
    }
}

const goDetail = (item: any) => {
    if (currentType.value === 'change') {
        uni.navigateTo({ url: `/packages/pages/order_change/change_detail?id=${item.id}` })
        return
    }

    uni.navigateTo({ url: `/packages/pages/order_change/pause_detail?id=${item.id}` })
}

const handleCancel = async (item: any) => {
    const result = await uni.showModal({
        title: '提示',
        content: `确定要取消该${currentType.value === 'change' ? '变更' : '暂停'}申请吗？`
    })

    if (!result.confirm) {
        return
    }

    try {
        if (currentType.value === 'change') {
            await cancelChange({ id: item.id })
        } else {
            await cancelPause({ id: item.id })
        }
        uni.showToast({ title: '已取消', icon: 'none' })
        await fetchList(true)
    } catch (error: any) {
        uni.showToast({ title: error?.message || '操作失败', icon: 'none' })
    }
}

onLoad((options: any) => {
    if (options?.type && ['change', 'pause'].includes(options.type)) {
        currentType.value = options.type
    }
})

onShow(() => {
    void fetchList(true)
})

onReachBottom(() => {
    loadMore()
})
</script>

<style lang="scss" scoped>
.change-list-page {
    min-height: 100vh;
    background: var(--wm-color-bg-page, #fff7f4);
}

.change-list-page__filter-scroll {
    padding: 12rpx 0 0;
}

.change-list-page__filter-row {
    display: flex;
    gap: 12rpx;
    padding: 0 var(--wm-space-page-x, 37rpx) 12rpx;
}

.change-list-page__filter-chip {
    flex-shrink: 0;
    min-width: 138rpx;
    min-height: 66rpx;
    padding: 0 24rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    background: rgba(255, 255, 255, 0.86);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    transition: all var(--wm-motion-base, 220ms) ease;

    &--active {
        background: linear-gradient(135deg, var(--wm-color-primary, #e85a4f) 0%, #d9786d 100%);
        border-color: transparent;
        box-shadow: 0 12rpx 24rpx rgba(232, 90, 79, 0.18);

        .change-list-page__filter-chip-text {
            color: #ffffff;
        }
    }
}

.change-list-page__filter-chip-text {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);
}

.change-list-page__content {
    padding: 6rpx var(--wm-space-page-x, 37rpx) 40rpx;
}

.change-list-page__loading {
    min-height: 52vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 20rpx;
}

.change-list-page__loading-text,
.change-list-page__load-more-text {
    font-size: 24rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.change-record-list {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.change-record-card {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    padding: 28rpx 30rpx !important;
    border-radius: 42rpx !important;
}

.change-record-card__head,
.change-record-card__foot {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;
}

.change-record-card__meta {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.change-record-card__badges {
    display: flex;
    flex-wrap: wrap;
    gap: 10rpx;
}

.change-record-card__no,
.change-record-card__time,
.change-record-card__foot-note {
    font-size: 22rpx;
    line-height: 1.5;
    color: var(--wm-text-tertiary, #b4aca8);
}

.change-record-card__time {
    flex-shrink: 0;
}

.change-record-card__title {
    display: block;
    font-size: 30rpx;
    line-height: 1.38;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.change-record-card__summary {
    display: block;
    font-size: 25rpx;
    line-height: 1.62;
    color: var(--wm-text-primary, #1e2432);

    &--muted {
        color: var(--wm-text-secondary, #7f7b78);
    }
}

.change-record-card__reason {
    display: flex;
    align-items: flex-start;
    gap: 12rpx;
    padding: 16rpx 18rpx;
    border-radius: 24rpx;
    background: rgba(255, 247, 244, 0.82);
    border: 1rpx solid rgba(239, 230, 225, 0.92);
}

.change-record-card__reason-label {
    flex-shrink: 0;
    font-size: 22rpx;
    font-weight: 700;
    color: var(--wm-text-secondary, #7f7b78);
}

.change-record-card__reason-text {
    flex: 1;
    min-width: 0;
    font-size: 24rpx;
    line-height: 1.58;
    color: var(--wm-text-secondary, #7f7b78);
}

.change-record-card__summary-box {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    padding: 18rpx 20rpx;
    border-radius: 24rpx;
    background: rgba(249, 244, 240, 0.86);
    border: 1rpx solid rgba(239, 230, 225, 0.96);
}

.change-record-card__summary-title {
    font-size: 22rpx;
    font-weight: 700;
    color: var(--wm-text-secondary, #7f7b78);
}

.change-record-card__summary-text {
    font-size: 24rpx;
    line-height: 1.62;
    color: var(--wm-text-primary, #1e2432);

    &--muted {
        color: var(--wm-text-secondary, #7f7b78);
    }
}

.change-record-card__actions {
    flex-shrink: 0;
}

.change-list-page__load-more {
    display: flex;
    justify-content: center;
    padding: 12rpx 0 6rpx;
}

.change-list-page__load-more-inner {
    display: inline-flex;
    align-items: center;
    gap: 12rpx;
}

.change-list-page__load-more-text--action {
    color: var(--wm-color-primary, #e85a4f);
}
</style>
