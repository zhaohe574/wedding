<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace" hasSafeBottom>
        <BaseNavbar
            title="投诉详情"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <view v-if="detail" class="aftersale-detail-page">
            <view class="aftersale-detail-page__wrapper wm-page-content">
                <BaseCard variant="hero" scene="consumer" class="aftersale-status-card">
                    <view class="aftersale-status-card__top">
                        <view class="aftersale-status-card__copy">
                            <text class="aftersale-status-card__label">投诉状态</text>
                            <text class="aftersale-status-card__title">
                                {{ complaintStatus.label }}
                            </text>
                        </view>

                        <view class="aftersale-status-card__badges">
                            <StatusBadge
                                v-for="badge in bannerBadges"
                                :key="badge.text"
                                :tone="badge.tone"
                                size="sm"
                            >
                                {{ badge.text }}
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
                        <text class="aftersale-detail-card__title">投诉信息</text>
                        <StatusBadge :tone="complaintStatus.tone" size="sm" dot>
                            {{ complaintStatus.label }}
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
                            <text class="aftersale-detail-card__title">投诉内容</text>
                            <text class="aftersale-detail-card__caption">
                                {{ detail.type_desc || '服务投诉' }}
                            </text>
                        </view>
                    </view>
                    <text class="aftersale-detail-card__headline">
                        {{ detail.title || '未命名投诉' }}
                    </text>
                    <text v-if="detail.content" class="aftersale-detail-card__paragraph">{{
                        detail.content
                    }}</text>
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
                    <view v-if="videos.length" class="aftersale-detail-card__video-list">
                        <video
                            v-for="(video, index) in videos"
                            :key="`${video}-${index}`"
                            :src="video"
                            class="aftersale-detail-card__video"
                            controls
                            object-fit="cover"
                        />
                    </view>
                </BaseCard>

                <BaseCard variant="surface" scene="consumer" class="aftersale-detail-card">
                    <view class="aftersale-detail-card__head">
                        <text class="aftersale-detail-card__title">处理说明</text>
                    </view>
                    <view class="aftersale-result-list">
                        <view class="aftersale-result-item">
                            <text class="aftersale-result-item__label">期望结果</text>
                            <text class="aftersale-result-item__value">
                                {{ detail.expect_result || '未填写' }}
                            </text>
                        </view>
                        <view class="aftersale-result-item">
                            <text class="aftersale-result-item__label">平台处理</text>
                            <text class="aftersale-result-item__value">
                                {{ detail.handle_result || '处理中' }}
                            </text>
                        </view>
                    </view>
                </BaseCard>
            </view>
        </view>

        <ActionArea v-if="detail && detail.status === 2 && !detail.satisfaction" sticky safeBottom>
            <view class="aftersale-detail-page__actions">
                <BaseButton variant="primary" size="lg" block @click="showRatePopup = true">
                    评价处理结果
                </BaseButton>
            </view>
        </ActionArea>

        <AfterSaleBottomSheet
            v-model="showRatePopup"
            title="处理满意度"
            subtitle="提交后会同步记录。"
            primary-text="提交评价"
            secondary-text="取消"
            @confirm="handleRate"
        >
            <view class="aftersale-sheet-form">
                <view class="aftersale-sheet-form__field">
                    <text class="aftersale-sheet-form__label">满意度</text>
                    <u-rate v-model="satisfaction" :min-count="1" />
                </view>
            </view>
        </AfterSaleBottomSheet>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { getComplaintDetail, rateComplaint } from '@/packages/common/api/aftersale'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { useThemeStore } from '@/stores/theme'
import { onLoad } from '@dcloudio/uni-app'
import { showError, showSuccess } from '@/utils/feedback'
import AfterSaleBottomSheet from './components/AfterSaleBottomSheet.vue'
import {
    getComplaintLevelMeta,
    getComplaintStatusMeta,
    getValueText,
    normalizeMediaList,
    openImagePreview
} from './shared'

const $theme = useThemeStore()
const complaintId = ref(0)
const detail = ref<any>(null)
const showRatePopup = ref(false)
const satisfaction = ref(5)

const complaintStatus = computed(() => getComplaintStatusMeta(Number(detail.value?.status || 0)))
const complaintLevel = computed(() => getComplaintLevelMeta(Number(detail.value?.level || 1)))
const images = computed(() => normalizeMediaList(detail.value?.images))
const videos = computed(() => normalizeMediaList(detail.value?.videos))
const bannerBadges = computed(() => [
    { text: complaintLevel.value.label, tone: complaintLevel.value.tone },
    { text: detail.value?.type_desc || '投诉', tone: 'info' as const }
])
const bannerMetrics = computed(() => [
    { label: '提交时间', value: getValueText(detail.value?.create_time, '待补充') },
    {
        label: '满意度',
        value: detail.value?.satisfaction ? `${detail.value.satisfaction} 分` : '待评价'
    }
])
const infoItems = computed(() => [
    {
        label: '投诉编号',
        value: getValueText(detail.value?.complaint_sn, '编号待补充')
    },
    {
        label: '关联订单',
        value: getValueText(detail.value?.order?.order_sn, '未关联')
    },
    {
        label: '涉及人员',
        value: getValueText(detail.value?.staff?.name, '平台待核查')
    },
    {
        label: '联系人',
        value: getValueText(detail.value?.contact_name, '未填写')
    },
    {
        label: '联系电话',
        value: getValueText(detail.value?.contact_mobile, '未填写')
    },
    {
        label: '投诉等级',
        value: complaintLevel.value.label
    }
])

const getDetail = async () => {
    try {
        const res = await getComplaintDetail(complaintId.value)
        detail.value = res?.data || res
        satisfaction.value = Number(detail.value?.satisfaction || 5)
    } catch (error) {
        showError('获取详情失败')
    }
}

const handleRate = async () => {
    try {
        await rateComplaint({
            id: complaintId.value,
            satisfaction: satisfaction.value
        })
        showRatePopup.value = false
        showSuccess('评价成功')
        await getDetail()
    } catch (error: any) {
        showError(error, '评价失败')
    }
}

onLoad((options: any) => {
    complaintId.value = Number(options?.id || 0)
    if (complaintId.value) {
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

.aftersale-detail-card__gallery,
.aftersale-detail-card__video-list {
    @include aftersale-media-grid;
}

.aftersale-detail-card__gallery-image {
    @include aftersale-gallery-image;
}

.aftersale-detail-card__video {
    @include aftersale-gallery-image;
    background: #0b0b0b;
}

.aftersale-result-list {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: 14rpx;
}

.aftersale-result-item {
    padding: 20rpx 22rpx;
    border-radius: 24rpx;
    background: rgba(248, 242, 228, 0.52);
    border: 1rpx solid rgba(216, 201, 173, 0.58);
    box-sizing: border-box;
}

.aftersale-result-item__label {
    display: block;
    font-size: 22rpx;
    line-height: 1.35;
    color: var(--wm-text-tertiary, #9a9388);
}

.aftersale-result-item__value {
    display: block;
    margin-top: 10rpx;
    font-size: 26rpx;
    line-height: 1.65;
    color: var(--wm-text-primary, #191713);
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
    color: var(--wm-text-secondary, #5f5a50);
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
