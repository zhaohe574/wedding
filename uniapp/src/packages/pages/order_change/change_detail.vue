<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="变更详情" />

        <view class="order-change-page">
            <view v-if="loading" class="order-change-page__center">
                <tn-loading size="72" mode="flower" :color="$theme.primaryColor" />
                <text class="order-change-page__center-text">加载变更详情中...</text>
            </view>

            <view v-else-if="detail" class="order-change-page__wrapper">
                <view
                    class="order-change-status-card"
                    :class="`order-change-status-card--${changeStatus.tone}`"
                >
                    <view class="order-change-status-card__eyebrow">
                        <text class="order-change-status-card__eyebrow-text">变更状态</text>
                        <StatusBadge :tone="changeType.tone" size="sm">
                            {{ changeType.label }}
                        </StatusBadge>
                    </view>
                    <text class="order-change-status-card__title">
                        {{ detail.change_status_desc || changeStatus.label }}
                    </text>
                    <text class="order-change-status-card__summary">
                        {{ changeStatus.summary }}
                    </text>
                    <view class="order-change-status-card__metrics">
                        <view
                            v-for="metric in statusMetrics"
                            :key="metric.label"
                            class="order-change-status-card__metric"
                        >
                            <text class="order-change-status-card__metric-label">
                                {{ metric.label }}
                            </text>
                            <text class="order-change-status-card__metric-value">
                                {{ metric.value }}
                            </text>
                        </view>
                    </view>
                </view>

                <BaseCard variant="surface" scene="consumer" class="order-change-card">
                    <text class="order-change-card__title">基础信息</text>
                    <view class="order-change-card__kv">
                        <text class="order-change-card__label">变更单号</text>
                        <text class="order-change-card__value">{{ detail.change_sn }}</text>
                    </view>
                    <view class="order-change-card__kv">
                        <text class="order-change-card__label">变更类型</text>
                        <view class="order-change-card__value">
                            <StatusBadge :tone="changeType.tone" size="sm">
                                {{ detail.change_type_desc || changeType.label }}
                            </StatusBadge>
                        </view>
                    </view>
                    <view class="order-change-card__kv">
                        <text class="order-change-card__label">申请时间</text>
                        <text class="order-change-card__value">
                            {{ getValueText(detail.create_time) }}
                        </text>
                    </view>
                    <view class="order-change-card__kv" v-if="detail.audit_time">
                        <text class="order-change-card__label">审核时间</text>
                        <text class="order-change-card__value">
                            {{ getValueText(detail.audit_time) }}
                        </text>
                    </view>
                    <view class="order-change-card__kv" v-if="detail.execute_time">
                        <text class="order-change-card__label">执行时间</text>
                        <text class="order-change-card__value">
                            {{ getValueText(detail.execute_time) }}
                        </text>
                    </view>
                </BaseCard>

                <BaseCard variant="surface" scene="consumer" class="order-change-card">
                    <text class="order-change-card__title">变更内容</text>

                    <template v-if="detail.change_type === 1">
                        <view class="order-change-comparison">
                            <view class="order-change-comparison__item">
                                <text class="order-change-comparison__label">原服务日期</text>
                                <text class="order-change-comparison__value">
                                    {{ getValueText(detail.old_service_date) }}
                                </text>
                            </view>
                            <view class="order-change-comparison__arrow">→</view>
                            <view
                                class="order-change-comparison__item order-change-comparison__item--highlight"
                            >
                                <text class="order-change-comparison__label">新服务日期</text>
                                <text class="order-change-comparison__value">
                                    {{ getValueText(detail.new_service_date) }}
                                </text>
                            </view>
                        </view>
                        <text class="order-change-card__paragraph">
                            改期申请会在审核通过后同步更新订单履约日期，请以最终执行结果为准。
                        </text>
                    </template>

                    <template v-else-if="detail.change_type === 2">
                        <view class="order-change-comparison">
                            <view class="order-change-comparison__item">
                                <text class="order-change-comparison__label">原服务人员</text>
                                <text class="order-change-comparison__value">
                                    {{ getValueText(detail.old_staff?.name || detail.old_staff_name) }}
                                </text>
                                <text class="order-change-comparison__meta">
                                    原价格：¥{{ formatCurrency(detail.old_price) }}
                                </text>
                            </view>
                            <view class="order-change-comparison__arrow">→</view>
                            <view
                                class="order-change-comparison__item order-change-comparison__item--highlight"
                            >
                                <text class="order-change-comparison__label">新服务人员</text>
                                <text class="order-change-comparison__value">
                                    {{ getValueText(detail.new_staff?.name || detail.new_staff_name) }}
                                </text>
                                <text class="order-change-comparison__meta">
                                    新价格：¥{{ formatCurrency(detail.new_price) }}
                                </text>
                            </view>
                        </view>

                        <view v-if="priceDiffText" class="order-change-summary-grid">
                            <view class="order-change-summary-grid__item">
                                <text class="order-change-summary-grid__label">差价</text>
                                <text
                                    class="order-change-summary-grid__value"
                                    :class="priceDiffClass"
                                >
                                    {{ priceDiffText }}
                                </text>
                            </view>
                            <view class="order-change-summary-grid__item">
                                <text class="order-change-summary-grid__label">处理提示</text>
                                <text class="order-change-summary-grid__value">
                                    {{ priceDiffHint }}
                                </text>
                            </view>
                        </view>
                    </template>

                    <template v-else-if="detail.change_type === 3">
                        <text class="order-change-card__headline">
                            {{ getValueText(detail.add_package_name, '新增服务待确认') }}
                        </text>
                        <text class="order-change-card__paragraph">
                            平台会按新增服务内容、服务人员和服务日期审核本次加项申请。
                        </text>
                        <view class="order-change-summary-grid">
                            <view class="order-change-summary-grid__item">
                                <text class="order-change-summary-grid__label">服务人员</text>
                                <text class="order-change-summary-grid__value">
                                    {{ getValueText(detail.add_staff?.name || detail.add_staff_name) }}
                                </text>
                            </view>
                            <view class="order-change-summary-grid__item">
                                <text class="order-change-summary-grid__label">服务日期</text>
                                <text class="order-change-summary-grid__value">
                                    {{ getValueText(detail.add_service_date) }}
                                </text>
                            </view>
                            <view class="order-change-summary-grid__item">
                                <text class="order-change-summary-grid__label">预计费用</text>
                                <text class="order-change-summary-grid__value">
                                    +¥{{ formatCurrency(detail.add_price) }}
                                </text>
                            </view>
                        </view>
                    </template>

                    <template v-else>
                        <view class="order-change-card__badge-row">
                            <StatusBadge tone="neutral" size="sm">历史下线能力</StatusBadge>
                        </view>
                        <text class="order-change-card__paragraph">
                            旧版附加服务变更不再在用户端提供详细编辑能力，当前页面仅保留状态和基础申请信息。
                        </text>
                    </template>
                </BaseCard>

                <BaseCard
                    v-if="detail.apply_reason || images.length"
                    variant="surface"
                    scene="consumer"
                    class="order-change-card"
                >
                    <text class="order-change-card__title">申请说明</text>
                    <text v-if="detail.apply_reason" class="order-change-card__paragraph">
                        {{ detail.apply_reason }}
                    </text>
                    <text v-else class="order-change-card__caption">申请人未填写补充说明。</text>
                    <view v-if="images.length" class="order-change-media-grid">
                        <image
                            v-for="(image, index) in images"
                            :key="`${image}-${index}`"
                            :src="image"
                            mode="aspectFill"
                            class="order-change-media-grid__image"
                            @click="openImagePreview(images, index)"
                        />
                    </view>
                </BaseCard>

                <BaseCard
                    v-if="detail.change_status >= 2 || priceDiffText || detail.reject_reason"
                    variant="surface"
                    scene="consumer"
                    class="order-change-card"
                >
                    <text class="order-change-card__title">审核与执行</text>
                    <view v-if="priceDiffText" class="order-change-card__kv">
                        <text class="order-change-card__label">差价说明</text>
                        <view class="order-change-card__value">
                            <text :class="priceDiffClass">{{ priceDiffText }}</text>
                            <text class="order-change-card__helper">{{ priceDiffHint }}</text>
                        </view>
                    </view>
                    <view v-if="detail.audit_remark" class="order-change-card__kv">
                        <text class="order-change-card__label">审核备注</text>
                        <text class="order-change-card__value">{{ detail.audit_remark }}</text>
                    </view>
                    <view v-if="detail.reject_reason" class="order-change-card__kv">
                        <text class="order-change-card__label">拒绝原因</text>
                        <text class="order-change-card__value order-change-card__value--danger">
                            {{ detail.reject_reason }}
                        </text>
                    </view>
                    <text
                        v-if="detail.change_status === 3 && detail.execute_time"
                        class="order-change-card__caption"
                    >
                        变更已在 {{ detail.execute_time }} 完成执行。
                    </text>
                </BaseCard>

                <BaseCard
                    v-if="detail.order"
                    variant="surface"
                    scene="consumer"
                    :interactive="true"
                    class="order-change-card"
                    @click="goOrder(detail.order.id)"
                >
                    <text class="order-change-card__title">关联订单</text>
                    <view class="order-change-link-card">
                        <view class="order-change-link-card__top">
                            <text class="order-change-link-card__main">
                                {{ getValueText(detail.order.order_sn, '订单待补充') }}
                            </text>
                            <text class="order-change-link-card__action">查看订单</text>
                        </view>
                        <view class="order-change-link-card__bottom">
                            <text class="order-change-link-card__meta">
                                服务日期：{{ getValueText(detail.order.service_date) }}
                            </text>
                            <text class="order-change-link-card__meta">
                                应付：¥{{ formatCurrency(detail.order.pay_amount) }}
                            </text>
                        </view>
                    </view>
                </BaseCard>
            </view>

            <view v-else class="order-change-page__center">
                <text class="order-change-page__center-text">未找到对应的变更记录。</text>
            </view>
        </view>

        <ActionArea v-if="detail && detail.change_status === 0" sticky safeBottom>
            <view class="order-change-page__actions">
                <BaseButton block size="lg" variant="danger" @click="handleCancel">
                    取消申请
                </BaseButton>
            </view>
        </ActionArea>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { cancelChange, getChangeDetail } from '@/api/orderChange'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { useThemeStore } from '@/stores/theme'
import {
    formatCurrency,
    getChangeStatusMeta,
    getChangeTypeMeta,
    getValueText,
    normalizeImageList,
    openImagePreview
} from './shared'

const $theme = useThemeStore()

const loading = ref(true)
const detail = ref<any>(null)
const changeId = ref(0)

const changeStatus = computed(() => getChangeStatusMeta(Number(detail.value?.change_status || 0)))
const changeType = computed(() => getChangeTypeMeta(Number(detail.value?.change_type || 0)))
const images = computed(() => normalizeImageList(detail.value?.attach_images))
const priceDiffValue = computed(() => Number(detail.value?.price_diff || 0))
const priceDiffText = computed(() => {
    if (!priceDiffValue.value) {
        return ''
    }
    return `${priceDiffValue.value > 0 ? '+' : ''}${formatCurrency(priceDiffValue.value)} 元`
})
const priceDiffHint = computed(() => {
    if (priceDiffValue.value > 0) {
        return '需补差价，具体支付节点以审核结果为准。'
    }
    if (priceDiffValue.value < 0) {
        return '产生退款差额，平台将在确认后处理。'
    }
    return '本次变更不涉及费用差额。'
})
const priceDiffClass = computed(() =>
    priceDiffValue.value > 0
        ? 'change-detail__price-diff change-detail__price-diff--up'
        : 'change-detail__price-diff change-detail__price-diff--down'
)
const statusMetrics = computed(() => [
    { label: '申请时间', value: getValueText(detail.value?.create_time, '-') },
    {
        label: detail.value?.audit_time ? '审核时间' : '执行时间',
        value: getValueText(detail.value?.audit_time || detail.value?.execute_time, '-')
    },
    { label: '变更类型', value: detail.value?.change_type_desc || changeType.value.label }
])

const fetchDetail = async () => {
    loading.value = true
    try {
        const res = await getChangeDetail({ id: changeId.value })
        detail.value = res?.data || res
    } catch (error) {
        console.error('获取变更详情失败', error)
        detail.value = null
    } finally {
        loading.value = false
    }
}

const goOrder = (orderId: number) => {
    uni.navigateTo({ url: `/pages/order_detail/order_detail?id=${orderId}` })
}

const handleCancel = async () => {
    const result = await uni.showModal({
        title: '取消申请',
        content: '确定要取消当前变更申请吗？'
    })

    if (!result.confirm) {
        return
    }

    try {
        await cancelChange({ id: changeId.value })
        uni.showToast({ title: '已取消', icon: 'none' })
        await fetchDetail()
    } catch (error: any) {
        uni.showToast({ title: error?.message || '操作失败', icon: 'none' })
    }
}

onLoad((options: any) => {
    changeId.value = Number(options?.id || 0)
    if (changeId.value) {
        void fetchDetail()
    } else {
        loading.value = false
    }
})
</script>

<style lang="scss" scoped>
@import './shared.scss';

.order-change-card__value--danger {
    color: var(--wm-color-danger, #c94b49);
}

.change-detail__price-diff {
    font-weight: 700;

    &--up {
        color: var(--wm-color-danger, #c94b49);
    }

    &--down {
        color: var(--wm-color-success, #2f7d58);
    }
}
</style>
