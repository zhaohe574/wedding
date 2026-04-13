<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="暂停详情" />

        <view class="order-change-page">
            <view v-if="loading" class="order-change-page__center">
                <tn-loading size="72" mode="flower" :color="$theme.primaryColor" />
                <text class="order-change-page__center-text">加载暂停详情中...</text>
            </view>

            <view v-else-if="detail" class="order-change-page__wrapper">
                <view
                    class="order-change-status-card"
                    :class="`order-change-status-card--${pauseStatus.tone}`"
                >
                    <view class="order-change-status-card__eyebrow">
                        <text class="order-change-status-card__eyebrow-text">暂停状态</text>
                        <StatusBadge :tone="pauseType.tone" size="sm">
                            {{ detail.pause_type_desc || pauseType.label }}
                        </StatusBadge>
                    </view>
                    <text class="order-change-status-card__title">
                        {{ detail.pause_status_desc || pauseStatus.label }}
                    </text>
                    <text class="order-change-status-card__summary">
                        {{ pauseStatus.summary }}
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
                        <text class="order-change-card__label">暂停单号</text>
                        <text class="order-change-card__value">{{ detail.pause_sn }}</text>
                    </view>
                    <view class="order-change-card__kv">
                        <text class="order-change-card__label">暂停类型</text>
                        <view class="order-change-card__value">
                            <StatusBadge :tone="pauseType.tone" size="sm">
                                {{ detail.pause_type_desc || pauseType.label }}
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
                    <view class="order-change-card__kv" v-if="detail.resume_time">
                        <text class="order-change-card__label">恢复时间</text>
                        <text class="order-change-card__value">
                            {{ getValueText(detail.resume_time) }}
                        </text>
                    </view>
                </BaseCard>

                <BaseCard variant="surface" scene="consumer" class="order-change-card">
                    <text class="order-change-card__title">暂停时间</text>
                    <view class="order-change-comparison">
                        <view class="order-change-comparison__item">
                            <text class="order-change-comparison__label">开始日期</text>
                            <text class="order-change-comparison__value">
                                {{ getValueText(detail.pause_start_date) }}
                            </text>
                        </view>
                        <view class="order-change-comparison__arrow">~</view>
                        <view
                            class="order-change-comparison__item order-change-comparison__item--highlight"
                        >
                            <text class="order-change-comparison__label">结束日期</text>
                            <text class="order-change-comparison__value">
                                {{ getValueText(detail.pause_end_date) }}
                            </text>
                        </view>
                    </view>
                    <view class="order-change-summary-grid">
                        <view class="order-change-summary-grid__item">
                            <text class="order-change-summary-grid__label">计划天数</text>
                            <text class="order-change-summary-grid__value">
                                {{
                                    getValueText(
                                        detail.pause_days ? `${detail.pause_days} 天` : '',
                                        '待补充'
                                    )
                                }}
                            </text>
                        </view>
                        <view class="order-change-summary-grid__item">
                            <text class="order-change-summary-grid__label">实际天数</text>
                            <text class="order-change-summary-grid__value">
                                {{
                                    getValueText(
                                        detail.actual_pause_days
                                            ? `${detail.actual_pause_days} 天`
                                            : '',
                                        '尚未结束'
                                    )
                                }}
                            </text>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="surface" scene="consumer" class="order-change-card">
                    <text class="order-change-card__title">暂停说明</text>
                    <text class="order-change-card__paragraph">
                        {{ getValueText(detail.pause_reason, '申请人未填写暂停原因。') }}
                    </text>
                    <view v-if="proofImages.length" class="order-change-media-grid">
                        <image
                            v-for="(image, index) in proofImages"
                            :key="`${image}-${index}`"
                            :src="image"
                            mode="aspectFill"
                            class="order-change-media-grid__image"
                            @click="openImagePreview(proofImages, index)"
                        />
                    </view>
                </BaseCard>

                <BaseCard
                    v-if="
                        detail.reject_reason ||
                        detail.original_service_date ||
                        detail.new_service_date ||
                        detail.resume_time
                    "
                    variant="surface"
                    scene="consumer"
                    class="order-change-card"
                >
                    <text class="order-change-card__title">审核与恢复</text>
                    <view v-if="detail.reject_reason" class="order-change-card__kv">
                        <text class="order-change-card__label">拒绝原因</text>
                        <text class="order-change-card__value order-change-card__value--danger">
                            {{ detail.reject_reason }}
                        </text>
                    </view>
                    <view v-if="detail.resume_time" class="order-change-card__kv">
                        <text class="order-change-card__label">恢复时间</text>
                        <text class="order-change-card__value">{{ detail.resume_time }}</text>
                    </view>
                    <view v-if="detail.original_service_date" class="order-change-card__kv">
                        <text class="order-change-card__label">原服务日期</text>
                        <text class="order-change-card__value">
                            {{ detail.original_service_date }}
                        </text>
                    </view>
                    <view v-if="detail.new_service_date" class="order-change-card__kv">
                        <text class="order-change-card__label">新服务日期</text>
                        <text class="order-change-card__value">{{ detail.new_service_date }}</text>
                    </view>
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
                <text class="order-change-page__center-text">未找到对应的暂停记录。</text>
            </view>
        </view>

        <ActionArea v-if="detail && detail.pause_status === 0" sticky safeBottom>
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
import { cancelPause, getPauseDetail } from '@/packages/common/api/orderChange'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { useThemeStore } from '@/stores/theme'
import {
    formatCurrency,
    getPauseStatusMeta,
    getPauseTypeMeta,
    getRemainDays,
    getValueText,
    normalizeImageList,
    openImagePreview
} from './shared'

const $theme = useThemeStore()

const loading = ref(true)
const detail = ref<any>(null)
const pauseId = ref(0)

const pauseStatus = computed(() => getPauseStatusMeta(Number(detail.value?.pause_status || 0)))
const pauseType = computed(() => getPauseTypeMeta(Number(detail.value?.pause_type || 0)))
const proofImages = computed(() => normalizeImageList(detail.value?.proof_images))
const remainDays = computed(() => getRemainDays(detail.value?.pause_end_date))
const remainDaysText = computed(() => {
    if (Number(detail.value?.pause_status || 0) !== 1 || remainDays.value < 0) {
        return '-'
    }
    return remainDays.value > 0 ? `剩余 ${remainDays.value} 天` : '今日到期'
})
const statusMetrics = computed(() => [
    { label: '申请时间', value: getValueText(detail.value?.create_time, '-') },
    {
        label: '暂停周期',
        value: getValueText(detail.value?.pause_days ? `${detail.value.pause_days} 天` : '', '-')
    },
    { label: '当前状态', value: remainDaysText.value }
])

const fetchDetail = async () => {
    loading.value = true
    try {
        const res = await getPauseDetail({ id: pauseId.value })
        detail.value = res?.data || res
    } catch (error) {
        console.error('获取暂停详情失败', error)
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
        content: '确定要取消当前暂停申请吗？'
    })

    if (!result.confirm) {
        return
    }

    try {
        await cancelPause({ id: pauseId.value })
        uni.showToast({ title: '已取消', icon: 'none' })
        await fetchDetail()
    } catch (error: any) {
        uni.showToast({ title: error?.message || '操作失败', icon: 'none' })
    }
}

onLoad((options: any) => {
    pauseId.value = Number(options?.id || 0)
    if (pauseId.value) {
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
</style>
