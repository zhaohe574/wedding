<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="工单详情" />

        <view v-if="detail" class="aftersale-detail-page">
            <view class="aftersale-detail-page__wrapper">
                <AfterSaleStatusBanner
                    label="工单状态"
                    :title="ticketStatus.label"
                    :summary="ticketStatus.summary"
                    :badges="[{ text: detail.type_desc || getTypeText(detail.type), tone: 'info' }]"
                    :metrics="bannerMetrics"
                />

                <BaseCard variant="surface" scene="consumer" class="aftersale-detail-card">
                    <text class="aftersale-detail-card__title">基础信息</text>
                    <view class="aftersale-detail-card__kv">
                        <text class="aftersale-detail-card__label">工单编号</text>
                        <text class="aftersale-detail-card__value">{{ detail.ticket_sn }}</text>
                    </view>
                    <view class="aftersale-detail-card__kv">
                        <text class="aftersale-detail-card__label">关联订单</text>
                        <text class="aftersale-detail-card__value">
                            {{ detail.order?.order_sn || '未关联' }}
                        </text>
                    </view>
                    <view class="aftersale-detail-card__kv">
                        <text class="aftersale-detail-card__label">创建时间</text>
                        <text class="aftersale-detail-card__value">{{ detail.create_time }}</text>
                    </view>
                </BaseCard>

                <BaseCard variant="surface" scene="consumer" class="aftersale-detail-card">
                    <text class="aftersale-detail-card__title">问题内容</text>
                    <text class="aftersale-detail-card__headline">{{ detail.title }}</text>
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
                    <text class="aftersale-detail-card__title">处理进度</text>
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
                                    {{ log.content || log.remark || '已更新进度' }}
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
                    <text class="aftersale-detail-card__title">处理结果</text>
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
            subtitle="提交满意度后将结束当前工单。"
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
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { cancelTicket, confirmComplete, getTicketDetail } from '@/api/aftersale'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useThemeStore } from '@/stores/theme'
import { onLoad } from '@dcloudio/uni-app'
import AfterSaleBottomSheet from './components/AfterSaleBottomSheet.vue'
import AfterSaleStatusBanner from './components/AfterSaleStatusBanner.vue'
import { getTicketStatusMeta, normalizeMediaList, openImagePreview } from './shared'

const $theme = useThemeStore()
const ticketId = ref(0)
const detail = ref<any>(null)
const showConfirmPopup = ref(false)
const confirmForm = reactive({
    satisfaction: 5,
    remark: ''
})

const ticketStatus = computed(() => getTicketStatusMeta(Number(detail.value?.status || 0)))
const images = computed(() => normalizeMediaList(detail.value?.images))
const bannerMetrics = computed(() => [
    {
        label: '更新时间',
        value: String(detail.value?.update_time || detail.value?.create_time || '-')
    },
    {
        label: '进度节点',
        value: String(Array.isArray(detail.value?.logs) ? detail.value.logs.length : 0)
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

const getDetail = async () => {
    try {
        const res = await getTicketDetail(ticketId.value)
        detail.value = res?.data || res
    } catch (error) {
        uni.showToast({ title: '获取详情失败', icon: 'none' })
    }
}

const handleCancel = async () => {
    const result = await uni.showModal({
        title: '取消工单',
        content: '确定取消当前工单吗？'
    })
    if (!result.confirm) {
        return
    }

    try {
        await cancelTicket(ticketId.value)
        uni.showToast({ title: '已取消', icon: 'none' })
        await getDetail()
    } catch (error: any) {
        uni.showToast({ title: error?.message || '取消失败', icon: 'none' })
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
        uni.showToast({ title: '确认成功', icon: 'none' })
        await getDetail()
    } catch (error: any) {
        uni.showToast({ title: error?.message || '操作失败', icon: 'none' })
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
}

.aftersale-detail-card {
    @include aftersale-detail-card;
}

.aftersale-detail-card__title {
    @include aftersale-detail-section-title;
    margin-bottom: 0;
}

.aftersale-detail-card__headline {
    @include aftersale-detail-card-headline;
}

.aftersale-detail-card__paragraph {
    @include aftersale-detail-card-paragraph;
}

.aftersale-detail-card__kv {
    @include aftersale-kv-row;
}

.aftersale-detail-card__label {
    @include aftersale-kv-label;
}

.aftersale-detail-card__value {
    @include aftersale-kv-value;
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
        background: var(--wm-color-primary, #e85a4f);
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
    color: var(--wm-text-secondary, #7f7b78);
}

.aftersale-sheet-form__textarea {
    min-height: 180rpx;
    padding: 22rpx 24rpx;
    @include aftersale-input-surface;
    box-sizing: border-box;
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--wm-text-primary, #1e2432);
}
</style>
