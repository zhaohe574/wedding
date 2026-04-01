<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="投诉详情" />

        <view v-if="detail" class="complaint-detail">
            <view class="complaint-detail__status-card">
                <view class="complaint-detail__status-main">
                    <text class="complaint-detail__status-text">
                        {{ detail.status_desc || getStatusText(detail.status) }}
                    </text>
                    <text class="complaint-detail__status-time">
                        提交时间：{{ detail.create_time }}
                    </text>
                </view>
                <view class="complaint-detail__level" :class="getLevelClass(detail.level)">
                    {{ detail.level_desc || getLevelText(detail.level) }}
                </view>
            </view>

            <view class="complaint-detail__section">
                <text class="complaint-detail__section-title">投诉信息</text>
                <view class="complaint-detail__kv">
                    <text class="complaint-detail__kv-label">投诉编号</text>
                    <text class="complaint-detail__kv-value">{{ detail.complaint_sn }}</text>
                </view>
                <view class="complaint-detail__kv">
                    <text class="complaint-detail__kv-label">投诉类型</text>
                    <text class="complaint-detail__kv-value">
                        {{ detail.type_desc || getTypeText(detail.type) }}
                    </text>
                </view>
                <view v-if="detail.order?.order_sn" class="complaint-detail__kv">
                    <text class="complaint-detail__kv-label">关联订单</text>
                    <text class="complaint-detail__kv-value">{{ detail.order.order_sn }}</text>
                </view>
                <view v-if="detail.staff?.name" class="complaint-detail__kv">
                    <text class="complaint-detail__kv-label">涉及人员</text>
                    <text class="complaint-detail__kv-value">{{ detail.staff.name }}</text>
                </view>
            </view>

            <view class="complaint-detail__section">
                <text class="complaint-detail__section-title">投诉内容</text>
                <text class="complaint-detail__content-title">{{ detail.title }}</text>
                <text v-if="detail.content" class="complaint-detail__content-text">
                    {{ detail.content }}
                </text>
                <view
                    v-if="Array.isArray(detail.images) && detail.images.length"
                    class="complaint-detail__gallery"
                >
                    <image
                        v-for="(img, index) in detail.images"
                        :key="`${img}-${index}`"
                        :src="img"
                        mode="aspectFill"
                        class="complaint-detail__gallery-image"
                        @click="previewImage(detail.images, index)"
                    />
                </view>
            </view>

            <view v-if="detail.handle_result || detail.expect_result" class="complaint-detail__section">
                <text class="complaint-detail__section-title">处理说明</text>
                <text v-if="detail.handle_result" class="complaint-detail__content-text">
                    平台处理结果：{{ detail.handle_result }}
                </text>
                <text v-if="detail.expect_result" class="complaint-detail__content-text">
                    您的期望结果：{{ detail.expect_result }}
                </text>
            </view>
        </view>

        <view
            v-if="detail && detail.status === 2 && !detail.satisfaction"
            class="complaint-detail__action-bar"
        >
            <BaseButton block size="lg" @click="showRatePopup = true">评价处理结果</BaseButton>
        </view>

        <tn-popup
            v-model="showRatePopup"
            open-direction="bottom"
            :radius="28"
            safe-area-inset-bottom
        >
            <view class="rate-panel">
                <view class="rate-panel__head">
                    <text class="rate-panel__title">处理满意度</text>
                    <tn-icon name="close" size="28" color="#978B83" @click="showRatePopup = false" />
                </view>
                <view class="rate-panel__body">
                    <u-rate v-model="satisfaction" :min-count="1" />
                </view>
                <view class="rate-panel__footer">
                    <BaseButton block size="lg" @click="handleRate">提交评价</BaseButton>
                </view>
            </view>
        </tn-popup>
    </PageShell>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { getComplaintDetail, rateComplaint } from '@/api/aftersale'
import { useThemeStore } from '@/stores/theme'
import { onLoad } from '@dcloudio/uni-app'

const $theme = useThemeStore()
const complaintId = ref(0)
const detail = ref<any>(null)
const showRatePopup = ref(false)
const satisfaction = ref(5)

const getDetail = async () => {
    try {
        const res = await getComplaintDetail(complaintId.value)
        detail.value = res?.data || res
        satisfaction.value = Number(detail.value?.satisfaction || 5)
    } catch (error) {
        uni.showToast({ title: '获取详情失败', icon: 'none' })
    }
}

const getStatusText = (status: number) => {
    const map: Record<number, string> = {
        0: '待处理',
        1: '处理中',
        2: '已处理',
        3: '已申诉',
        4: '已关闭'
    }
    return map[status] || '未知'
}

const getLevelClass = (level: number) => {
    const map: Record<number, string> = {
        1: 'level-normal',
        2: 'level-serious',
        3: 'level-urgent'
    }
    return map[level] || ''
}

const getLevelText = (level: number) => {
    const map: Record<number, string> = {
        1: '一般',
        2: '严重',
        3: '紧急'
    }
    return map[level] || '一般'
}

const getTypeText = (type: number) => {
    const map: Record<number, string> = {
        1: '服务态度',
        2: '专业能力',
        3: '迟到早退',
        4: '违规行为',
        5: '其他'
    }
    return map[type] || '其他'
}

const previewImage = (images: string[], index: number) => {
    uni.previewImage({
        urls: images,
        current: index
    })
}

const handleRate = async () => {
    try {
        await rateComplaint({
            id: complaintId.value,
            satisfaction: satisfaction.value
        })
        uni.showToast({ title: '评价成功', icon: 'none' })
        showRatePopup.value = false
        getDetail()
    } catch (error: any) {
        uni.showToast({ title: error?.message || '评价失败', icon: 'none' })
    }
}

onLoad((options: any) => {
    complaintId.value = Number(options?.id || 0)
    if (complaintId.value) {
        getDetail()
    }
})
</script>

<style lang="scss" scoped>
@import '../../../styles/aftersale.scss';

.complaint-detail {
    @include aftersale-page-base;
    padding: 0 20rpx 160rpx;
}

.complaint-detail__status-card,
.complaint-detail__section {
    @include aftersale-section-card;
    margin-bottom: 16rpx;
}

.complaint-detail__status-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.complaint-detail__status-text {
    display: block;
    font-size: 32rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.complaint-detail__status-time {
    display: block;
    margin-top: 8rpx;
    font-size: 22rpx;
    color: var(--wm-text-tertiary, #b4aca8);
}

.complaint-detail__level {
    padding: 10rpx 18rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 700;
}

.complaint-detail__section-title {
    display: block;
    margin-bottom: 18rpx;
    font-size: 30rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.complaint-detail__kv {
    @include aftersale-kv-row;
}

.complaint-detail__kv + .complaint-detail__kv {
    margin-top: 14rpx;
}

.complaint-detail__kv-label {
    @include aftersale-kv-label;
}

.complaint-detail__kv-value {
    @include aftersale-kv-value;
}

.complaint-detail__content-title {
    display: block;
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.35;
    color: var(--wm-text-primary, #1e2432);
}

.complaint-detail__content-text {
    display: block;
    margin-top: 14rpx;
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--wm-text-secondary, #7f7b78);
}

.complaint-detail__gallery {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10rpx;
    margin-top: 18rpx;
}

.complaint-detail__gallery-image {
    width: 100%;
    height: 180rpx;
    border-radius: 16rpx;
}

.complaint-detail__action-bar {
    position: fixed;
    left: 20rpx;
    right: 20rpx;
    bottom: calc(20rpx + env(safe-area-inset-bottom));
}

.rate-panel {
    padding: 24rpx 20rpx 28rpx;
    background: var(--wm-color-bg-page, #fcfbf9);
}

.rate-panel__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.rate-panel__title {
    font-size: 32rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.rate-panel__body {
    padding: 26rpx 0 10rpx;
}

.rate-panel__footer {
    margin-top: 20rpx;
}

.level-normal {
    color: #607086;
    background: rgba(96, 112, 134, 0.12);
}

.level-serious {
    color: #c98524;
    background: rgba(201, 133, 36, 0.12);
}

.level-urgent {
    color: #b44a3a;
    background: rgba(180, 74, 58, 0.12);
}
</style>
