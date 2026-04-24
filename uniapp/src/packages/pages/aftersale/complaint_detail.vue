<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="投诉详情" />

        <view v-if="detail" class="aftersale-detail-page">
            <view class="aftersale-detail-page__wrapper wm-page-content">
                <AfterSaleStatusBanner
                    label="投诉状态"
                    :title="complaintStatus.label"
                    :summary="complaintStatus.summary"
                    :badges="bannerBadges"
                    :metrics="bannerMetrics"
                />

                <BaseCard variant="surface" scene="consumer" class="aftersale-detail-card">
                    <text class="aftersale-detail-card__title">投诉信息</text>
                    <view class="aftersale-detail-card__kv">
                        <text class="aftersale-detail-card__label">投诉编号</text>
                        <text class="aftersale-detail-card__value">{{ detail.complaint_sn }}</text>
                    </view>
                    <view class="aftersale-detail-card__kv">
                        <text class="aftersale-detail-card__label">关联订单</text>
                        <text class="aftersale-detail-card__value">{{
                            detail.order?.order_sn || '未关联'
                        }}</text>
                    </view>
                    <view class="aftersale-detail-card__kv">
                        <text class="aftersale-detail-card__label">涉及人员</text>
                        <text class="aftersale-detail-card__value">{{
                            detail.staff?.name || '平台待核查'
                        }}</text>
                    </view>
                    <view class="aftersale-detail-card__kv">
                        <text class="aftersale-detail-card__label">联系人</text>
                        <text class="aftersale-detail-card__value">{{
                            detail.contact_name || '未填写'
                        }}</text>
                    </view>
                    <view class="aftersale-detail-card__kv">
                        <text class="aftersale-detail-card__label">联系电话</text>
                        <text class="aftersale-detail-card__value">{{
                            detail.contact_mobile || '未填写'
                        }}</text>
                    </view>
                </BaseCard>

                <BaseCard variant="surface" scene="consumer" class="aftersale-detail-card">
                    <text class="aftersale-detail-card__title">投诉内容</text>
                    <text class="aftersale-detail-card__headline">{{ detail.title }}</text>
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
                    <text class="aftersale-detail-card__title">处理说明</text>
                    <view class="aftersale-detail-card__kv">
                        <text class="aftersale-detail-card__label">期望结果</text>
                        <text class="aftersale-detail-card__value">{{
                            detail.expect_result || '未填写'
                        }}</text>
                    </view>
                    <view class="aftersale-detail-card__kv">
                        <text class="aftersale-detail-card__label">平台处理</text>
                        <text class="aftersale-detail-card__value">{{
                            detail.handle_result || '处理中'
                        }}</text>
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
import { useThemeStore } from '@/stores/theme'
import { onLoad } from '@dcloudio/uni-app'
import AfterSaleBottomSheet from './components/AfterSaleBottomSheet.vue'
import AfterSaleStatusBanner from './components/AfterSaleStatusBanner.vue'
import {
    getComplaintLevelMeta,
    getComplaintStatusMeta,
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
    { label: '提交时间', value: String(detail.value?.create_time || '-') },
    {
        label: '满意度',
        value: detail.value?.satisfaction ? `${detail.value.satisfaction} 分` : '待评价'
    }
])

const getDetail = async () => {
    try {
        const res = await getComplaintDetail(complaintId.value)
        detail.value = res?.data || res
        satisfaction.value = Number(detail.value?.satisfaction || 5)
    } catch (error) {
        uni.showToast({ title: '获取详情失败', icon: 'none' })
    }
}

const handleRate = async () => {
    try {
        await rateComplaint({
            id: complaintId.value,
            satisfaction: satisfaction.value
        })
        showRatePopup.value = false
        uni.showToast({ title: '评价成功', icon: 'none' })
        await getDetail()
    } catch (error: any) {
        uni.showToast({ title: error?.message || '评价失败', icon: 'none' })
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

.aftersale-detail-card__gallery,
.aftersale-detail-card__video-list {
    @include aftersale-media-grid;
}

.aftersale-detail-card__gallery-image {
    @include aftersale-gallery-image;
}

.aftersale-detail-card__video {
    width: 100%;
    height: 180rpx;
    border-radius: 28rpx;
    display: block;
    background: #0B0B0B;
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
</style>
