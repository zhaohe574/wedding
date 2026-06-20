<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace" hasSafeBottom>
        <BaseNavbar
            title="工单详情"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <view v-if="detail" class="aftersale-detail-page">
            <view class="aftersale-detail-page__wrapper wm-page-content">
                <BaseCard variant="hero" scene="consumer" class="aftersale-status-card">
                    <view class="aftersale-status-card__top">
                        <view class="aftersale-status-card__copy">
                            <text class="aftersale-status-card__label">工单状态</text>
                            <text class="aftersale-status-card__title">
                                {{ ticketStatus.label }}
                            </text>
                        </view>

                        <view class="aftersale-status-card__badges">
                            <StatusBadge tone="primary" size="sm">
                                {{ ticketTypeText }}
                            </StatusBadge>
                            <StatusBadge v-if="ticketPriorityText" tone="warning" size="sm">
                                {{ ticketPriorityText }}
                            </StatusBadge>
                        </view>
                    </view>

                    <view class="aftersale-status-card__metrics">
                        <view
                            v-for="item in bannerMetrics"
                            :key="item.label"
                            class="aftersale-status-card__metric"
                        >
                            <text class="aftersale-status-card__metric-label">
                                {{ item.label }}
                            </text>
                            <text class="aftersale-status-card__metric-value">
                                {{ item.value }}
                            </text>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="surface" scene="consumer" class="aftersale-detail-card">
                    <view class="aftersale-detail-card__head">
                        <text class="aftersale-detail-card__title">基础信息</text>
                        <StatusBadge :tone="ticketStatus.tone" size="sm" dot>
                            {{ ticketStatus.label }}
                        </StatusBadge>
                    </view>

                    <view class="aftersale-info-grid">
                        <view
                            v-for="item in infoItems"
                            :key="item.label"
                            class="aftersale-info-item"
                        >
                            <text class="aftersale-info-item__label">{{ item.label }}</text>
                            <text class="aftersale-info-item__value">{{ item.value }}</text>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="surface" scene="consumer" class="aftersale-detail-card">
                    <view class="aftersale-detail-card__head">
                        <view class="aftersale-detail-card__title-group">
                            <text class="aftersale-detail-card__title">问题内容</text>
                            <text class="aftersale-detail-card__caption">{{ ticketTypeText }}</text>
                        </view>
                    </view>
                    <text class="aftersale-detail-card__headline">
                        {{ detail.title || '未命名工单' }}
                    </text>
                    <text v-if="detail.content" class="aftersale-detail-card__paragraph">
                        {{ detail.content }}
                    </text>
                    <view v-if="images.length" class="aftersale-detail-card__gallery">
                        <image
                            v-for="(img, index) in images"
                            :key="`${img}-${index}`"
                            :src="img"
                            mode="aspectFill"
                            class="aftersale-detail-card__gallery-image"
                            @click="openImagePreview(images, index)"
                        />
                    </view>
                </BaseCard>

                <BaseCard
                    v-if="Array.isArray(detail.logs) && detail.logs.length"
                    variant="surface"
                    scene="consumer"
                    class="aftersale-detail-card"
                >
                    <view class="aftersale-detail-card__head">
                        <text class="aftersale-detail-card__title">处理进度</text>
                        <StatusBadge tone="neutral" size="sm">
                            {{ detail.logs.length }} 条
                        </StatusBadge>
                    </view>
                    <view class="aftersale-timeline">
                        <view
                            v-for="(log, index) in detail.logs"
                            :key="`${log.create_time}-${index}`"
                            class="aftersale-timeline__item"
                        >
                            <view class="aftersale-timeline__line">
                                <view
                                    class="aftersale-timeline__dot"
                                    :class="{ 'is-active': index === 0 }"
                                />
                            </view>
                            <view class="aftersale-timeline__content">
                                <text class="aftersale-timeline__text">
                                    {{ getLogContentText(log) }}
                                </text>
                                <text class="aftersale-timeline__time">{{ log.create_time }}</text>
                            </view>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard
                    v-if="detail.handle_result"
                    variant="surface"
                    scene="consumer"
                    class="aftersale-detail-card"
                >
                    <view class="aftersale-detail-card__head">
                        <text class="aftersale-detail-card__title">处理结果</text>
                    </view>
                    <text class="aftersale-detail-card__paragraph">{{ detail.handle_result }}</text>
                </BaseCard>
            </view>
        </view>

        <ActionArea v-if="detail && (detail.status === 0 || detail.status === 2)" sticky safeBottom>
            <view class="aftersale-detail-page__actions">
                <BaseButton
                    v-if="detail.status === 0"
                    variant="ghost"
                    size="lg"
                    block
                    @click="handleCancel"
                >
                    取消工单
                </BaseButton>
                <BaseButton
                    v-if="detail.status === 2"
                    class="aftersale-detail-page__reject-button"
                    variant="light"
                    size="lg"
                    block
                    text-color="#7A3F1F"
                    icon-color="#9A6B35"
                    shadow="0 12rpx 28rpx rgba(154, 107, 53, 0.12)"
                    @click="showRejectPopup = true"
                >
                    拒绝处理结果
                </BaseButton>
                <BaseButton
                    v-if="detail.status === 2"
                    variant="primary"
                    size="lg"
                    block
                    @click="showConfirmPopup = true"
                >
                    确认完成
                </BaseButton>
            </view>
        </ActionArea>

        <AfterSaleBottomSheet
            v-model="showConfirmPopup"
            title="确认完成"
            subtitle="提交后将结束工单。"
            primary-text="确认提交"
            secondary-text="取消"
            @confirm="handleConfirm"
        >
            <view class="aftersale-sheet-form">
                <view class="aftersale-sheet-form__field">
                    <text class="aftersale-sheet-form__label">满意度</text>
                    <u-rate v-model="confirmForm.satisfaction" :min-count="1" />
                </view>
                <view class="aftersale-sheet-form__field">
                    <text class="aftersale-sheet-form__label">补充备注</text>
                    <textarea
                        v-model="confirmForm.remark"
                        class="aftersale-sheet-form__textarea"
                        placeholder="可补充处理体验"
                    />
                </view>
            </view>
        </AfterSaleBottomSheet>

        <AfterSaleBottomSheet
            v-model="showRejectPopup"
            title="拒绝处理结果"
            subtitle="退回后平台会继续处理。"
            primary-text="提交拒绝"
            secondary-text="取消"
            @confirm="handleReject"
        >
            <view class="aftersale-sheet-form">
                <view class="aftersale-sheet-form__field">
                    <text class="aftersale-sheet-form__label">拒绝原因</text>
                    <textarea
                        v-model="rejectForm.reason"
                        class="aftersale-sheet-form__textarea"
                        placeholder="请说明仍需处理的问题"
                    />
                </view>
            </view>
        </AfterSaleBottomSheet>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import {
    cancelTicket,
    confirmComplete,
    getTicketDetail,
    rejectComplete
} from '@/packages/common/api/aftersale'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { useThemeStore } from '@/stores/theme'
import { onLoad } from '@dcloudio/uni-app'
import { confirmModal, showError, showSuccess } from '@/utils/feedback'
import AfterSaleBottomSheet from './components/AfterSaleBottomSheet.vue'
import { getTicketStatusMeta, getValueText, normalizeMediaList, openImagePreview } from './shared'

const $theme = useThemeStore()
const ticketId = ref(0)
const detail = ref<any>(null)
const showConfirmPopup = ref(false)
const showRejectPopup = ref(false)
const confirmForm = reactive({
    satisfaction: 5,
    remark: ''
})
const rejectForm = reactive({
    reason: ''
})

const ticketStatus = computed(() => getTicketStatusMeta(Number(detail.value?.status || 0)))
const images = computed(() => normalizeMediaList(detail.value?.images))
const orderInfo = computed(() => detail.value?.order_info || {})
const ticketTypeText = computed(() =>
    getValueText(detail.value?.type_desc, getTypeText(detail.value?.type))
)
const ticketPriorityText = computed(() => getValueText(detail.value?.priority_desc, ''))
const bannerMetrics = computed(() => [
    {
        label: '更新时间',
        value: getValueText(detail.value?.update_time || detail.value?.create_time, '待更新')
    },
    {
        label: '进度记录',
        value: `${Array.isArray(detail.value?.logs) ? detail.value.logs.length : 0} 条`
    }
])
const infoItems = computed(() => [
    {
        label: '工单编号',
        value: getValueText(detail.value?.ticket_sn, '编号待补充')
    },
    {
        label: '关联订单',
        value: getValueText(orderInfo.value?.order_sn || detail.value?.order?.order_sn, '未关联')
    },
    {
        label: '服务人员',
        value: getValueText(orderInfo.value?.staff_name, '待补充')
    },
    {
        label: '服务日期',
        value: getValueText(orderInfo.value?.service_date, '待补充')
    },
    {
        label: '创建时间',
        value: getValueText(detail.value?.create_time, '待补充')
    },
    {
        label: '工单类型',
        value: ticketTypeText.value
    }
])

const getTypeText = (type: number) => {
    const map: Record<number, string> = {
        1: '投诉',
        2: '咨询',
        3: '售后',
        4: '建议',
        5: '其他'
    }
    return map[type] || '其他'
}

const getLogContentText = (log: any) => {
    const displayText = getValueText(log?.content_display, '')
    if (displayText) {
        return displayText
    }

    const contentText = getValueText(log?.content || log?.remark, '')
    const operatorName = getValueText(log?.operator_name, '')
    if (operatorName) {
        return contentText.replace(/管理员ID[:：]\s*\d+/g, operatorName) || '已更新进度'
    }

    return contentText || '已更新进度'
}

const getDetail = async () => {
    try {
        const res = await getTicketDetail(ticketId.value)
        detail.value = res?.data || res
    } catch (error) {
        showError('获取详情失败')
    }
}

const handleCancel = async () => {
    const confirmed = await confirmModal({
        title: '取消工单',
        content: '确定取消当前工单吗？'
    })
    if (!confirmed) {
        return
    }

    try {
        await cancelTicket(ticketId.value)
        showSuccess('已取消')
        await getDetail()
    } catch (error: any) {
        showError(error, '取消失败')
    }
}

const handleConfirm = async () => {
    try {
        await confirmComplete({
            id: ticketId.value,
            satisfaction: confirmForm.satisfaction,
            remark: confirmForm.remark.trim()
        })
        showConfirmPopup.value = false
        showSuccess('确认成功')
        await getDetail()
    } catch (error: any) {
        showError(error, '操作失败')
    }
}

const handleReject = async () => {
    const reason = rejectForm.reason.trim()
    if (!reason) {
        showError('请填写拒绝原因')
        return
    }

    try {
        await rejectComplete({
            id: ticketId.value,
            reason
        })
        showRejectPopup.value = false
        rejectForm.reason = ''
        showSuccess('已退回处理')
        await getDetail()
    } catch (error: any) {
        showError(error, '提交失败')
    }
}

onLoad((options: any) => {
    ticketId.value = Number(options?.id || 0)
    if (ticketId.value) {
        void getDetail()
    }
})
</script>

<style lang="scss" scoped>
@import '../../../styles/aftersale.scss';

.aftersale-detail-page {
    @include aftersale-page-base;
    min-height: 100vh;
}

.aftersale-detail-page__wrapper {
    @include aftersale-page-wrapper;
    gap: 18rpx;
    padding-top: 16rpx;
    padding-bottom: calc(var(--wm-space-section-gap-lg, 30rpx) + 132rpx);
}

.aftersale-status-card {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
}

.aftersale-status-card__top,
.aftersale-status-card__metrics {
    position: relative;
    z-index: 1;
}

.aftersale-status-card__top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20rpx;
}

.aftersale-status-card__copy {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.aftersale-status-card__label {
    font-size: 22rpx;
    line-height: 1.2;
    font-weight: 800;
    color: var(--wm-color-champagne, #d9be82);
}

.aftersale-status-card__title {
    display: block;
    font-size: 42rpx;
    line-height: 1.18;
    font-weight: 900;
    color: var(--wm-text-inverse, #fffdf8);
}

.aftersale-status-card__badges {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 10rpx;
}

.aftersale-status-card__metrics {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14rpx;
}

.aftersale-status-card__metric {
    min-width: 0;
    padding: 18rpx 20rpx;
    border-radius: 24rpx;
    border: 1rpx solid rgba(217, 190, 130, 0.24);
    background: rgba(255, 253, 248, 0.08);
    box-sizing: border-box;
}

.aftersale-status-card__metric-label {
    display: block;
    font-size: 21rpx;
    line-height: 1.35;
    color: rgba(255, 253, 248, 0.62);
}

.aftersale-status-card__metric-value {
    display: block;
    min-width: 0;
    margin-top: 8rpx;
    font-size: 26rpx;
    line-height: 1.35;
    font-weight: 900;
    color: var(--wm-text-inverse, #fffdf8);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.aftersale-detail-card {
    @include aftersale-detail-card;
    gap: 20rpx;
}

.aftersale-detail-card__head {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18rpx;
}

.aftersale-detail-card__title-group {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.aftersale-detail-card__title {
    @include aftersale-detail-section-title;
    margin-bottom: 0;
}

.aftersale-detail-card__caption {
    display: block;
    font-size: 22rpx;
    line-height: 1.35;
    color: var(--wm-text-tertiary, #9a9388);
}

.aftersale-detail-card__headline {
    @include aftersale-detail-card-headline;
}

.aftersale-detail-card__paragraph {
    @include aftersale-detail-card-paragraph;
}

.aftersale-info-grid {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12rpx;
}

.aftersale-info-item {
    min-width: 0;
    padding: 18rpx 20rpx;
    border-radius: 24rpx;
    background: rgba(248, 242, 228, 0.58);
    border: 1rpx solid rgba(216, 201, 173, 0.62);
    box-sizing: border-box;
}

.aftersale-info-item__label {
    display: block;
    font-size: 21rpx;
    line-height: 1.35;
    color: var(--wm-text-tertiary, #9a9388);
}

.aftersale-info-item__value {
    display: block;
    min-width: 0;
    margin-top: 8rpx;
    font-size: 25rpx;
    line-height: 1.42;
    font-weight: 800;
    color: var(--wm-text-primary, #191713);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.aftersale-detail-card__gallery {
    @include aftersale-media-grid;
}

.aftersale-detail-card__gallery-image {
    @include aftersale-gallery-image;
}

.aftersale-timeline {
    @include aftersale-timeline;
}

.aftersale-timeline__item {
    @include aftersale-timeline-item;
}

.aftersale-timeline__line {
    @include aftersale-timeline-line;
}

.aftersale-timeline__dot {
    @include aftersale-timeline-dot;

    &.is-active {
        background: var(--wm-color-primary, #0b0b0b);
    }
}

.aftersale-timeline__content {
    @include aftersale-timeline-content;
}

.aftersale-timeline__text {
    @include aftersale-timeline-text;
}

.aftersale-timeline__time {
    @include aftersale-timeline-time;
}

.aftersale-detail-page__actions {
    @include aftersale-action-row;
}

.aftersale-detail-page__reject-button {
    :deep(.base-button) {
        border-color: rgba(154, 107, 53, 0.36);
        background: linear-gradient(180deg, rgba(255, 253, 248, 0.98) 0%, #f2ddd5 100%);
    }
}

.aftersale-sheet-form {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.aftersale-sheet-form__field {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.aftersale-sheet-form__label {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #5f5a50);
}

.aftersale-sheet-form__textarea {
    display: block;
    width: 100%;
    max-width: 100%;
    min-height: 180rpx;
    padding: 22rpx 24rpx;
    @include aftersale-input-surface;
    box-sizing: border-box;
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--wm-text-primary, #111111);
}

@media screen and (max-width: 360px) {
    .aftersale-status-card__top,
    .aftersale-detail-card__head {
        align-items: stretch;
        flex-direction: column;
    }

    .aftersale-status-card__badges {
        align-items: flex-start;
    }

    .aftersale-status-card__metrics,
    .aftersale-info-grid {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
