<template>
    <page-meta :page-style="pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="评价详情" />

        <view class="review-detail-page wm-page-content" v-if="review">
            <view class="hero-card wm-panel-card">
                <view class="hero-header">
                    <view class="hero-user">
                        <image
                            class="user-avatar"
                            :src="review.user?.avatar || '/static/images/user/default_avatar.png'"
                            mode="aspectFill"
                        />
                        <view class="user-info">
                            <text class="user-name">{{ review.user?.nickname || '匿名用户' }}</text>
                            <text class="review-time">{{ review.create_time_text || '-' }}</text>
                        </view>
                    </view>
                    <view class="status-badge" :class="`status-${review.status}`">
                        {{ review.status_text }}
                    </view>
                </view>

                <view class="score-row">
                    <view class="score-stars">
                        <tn-icon
                            v-for="star in 5"
                            :key="star"
                            :name="star <= displayScoreStars ? 'star-fill' : 'star'"
                            size="28"
                            :color="star <= displayScoreStars ? '#C8A45D' : '#D8D3C7'"
                        />
                    </view>
                    <text class="score-text">{{ review.score_level }} · {{ review.score }}分</text>
                </view>

                <view class="meta-list">
                    <view class="meta-item">
                        <text class="meta-label">服务人员</text>
                        <text class="meta-value">{{ review.staff?.name || '-' }}</text>
                    </view>
                    <view class="meta-item">
                        <text class="meta-label">订单编号</text>
                        <text class="meta-value">{{ review.order?.order_sn || '-' }}</text>
                    </view>
                    <view class="meta-item">
                        <text class="meta-label">服务项目</text>
                        <text class="meta-value">
                            {{
                                review.order_item?.package_name ||
                                review.orderItem?.package_name ||
                                '-'
                            }}
                        </text>
                    </view>
                    <view class="meta-item">
                        <text class="meta-label">服务日期</text>
                        <text class="meta-value">
                            {{ review.service_date || review.order?.service_date || '-' }}
                        </text>
                    </view>
                </view>
            </view>

            <view class="section-card wm-form-block">
                <view class="section-title">审核说明</view>
                <text class="section-tip">{{ review.status_summary || '请留意审核结果。' }}</text>
            </view>

            <view class="section-card wm-form-block">
                <view class="section-title">评价内容</view>
                <text v-if="review.content" class="review-content">{{ review.content }}</text>
                <text v-else class="empty-text">未填写评价</text>

                <view v-if="review.tags?.length" class="tag-list">
                    <view v-for="tag in review.tags" :key="tag.id || tag.name" class="tag-item">
                        {{ tag.name }}
                    </view>
                </view>

                <view v-if="review.images?.length" class="image-grid">
                    <image
                        v-for="(image, index) in review.images"
                        :key="`${image}-${index}`"
                        class="review-image"
                        :src="image"
                        mode="aspectFill"
                        @click="previewImages(review.images, index)"
                    />
                </view>
            </view>

            <view v-if="review.replies?.length" class="section-card wm-form-block">
                <view class="section-title">回复记录</view>
                <view
                    v-for="reply in review.replies"
                    :key="reply.id"
                    class="reply-card wm-soft-card"
                >
                    <view class="reply-header">
                        <text class="reply-type">
                            {{ Number(reply.reply_type) === 1 ? '用户追评' : '商家回复' }}
                        </text>
                        <text class="reply-time">{{ formatDateTime(reply.create_time) }}</text>
                    </view>
                    <text class="reply-content">{{ reply.content }}</text>
                    <view v-if="reply.images?.length" class="reply-images">
                        <image
                            v-for="(image, index) in reply.images"
                            :key="`${image}-${index}`"
                            class="reply-image"
                            :src="image"
                            mode="aspectFill"
                            @click="previewImages(reply.images, index)"
                        />
                    </view>
                </view>
            </view>

            <view class="safe-bottom"></view>
        </view>

        <view v-else class="loading-wrap">
            <tn-loading mode="circle" />
            <text class="loading-text">评价详情加载中...</text>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad, onPullDownRefresh } from '@dcloudio/uni-app'
import { getReviewDetail } from '@/packages/common/api/review'
import { useThemeStore } from '@/stores/theme'
import { ensureMiniProgramReviewModeConfig } from '@/utils/miniProgramReviewMode'

const $theme = useThemeStore()

const reviewId = ref(0)
const review = ref<any>(null)
const pageStyle = computed(() => {
    const base = String($theme.pageStyle || '').trim()
    const separator = !base || base.endsWith(';') ? '' : ';'
    return `${base}${separator}overflow:visible;`
})
const displayScoreStars = computed(() => Math.max(1, Math.min(5, Math.round(Number(review.value?.score || 0)))))

const formatDateTime = (value: number | string) => {
    if (!value) {
        return '-'
    }

    if (typeof value === 'number') {
        return new Date(value * 1000).toLocaleString()
    }

    const normalized = value.includes('T') ? value : value.replace(' ', 'T')
    const time = new Date(normalized).getTime()
    if (Number.isNaN(time)) {
        return value
    }
    return new Date(time).toLocaleString()
}

const previewImages = (images: Array<string | number>, index: number | string = 0) => {
    const urls = (images || []).map((item) => String(item)).filter(Boolean)
    if (!urls.length) {
        return
    }
    const currentIndex = Number(index || 0)
    uni.previewImage({
        urls,
        current: urls[currentIndex] || urls[0]
    })
}

const fetchDetail = async () => {
    if (!reviewId.value) {
        return
    }
    try {
        review.value = await getReviewDetail({ id: reviewId.value })
    } catch (e: any) {
        uni.showToast({ title: e?.message || e || '加载失败', icon: 'none' })
    } finally {
        uni.stopPullDownRefresh()
    }
}

onLoad((options: any) => {
    reviewId.value = Number(options?.id || 0)
    ensureMiniProgramReviewModeConfig()
    fetchDetail()
})

onPullDownRefresh(() => {
    fetchDetail()
})
</script>

<style scoped lang="scss">
.review-detail-page {
    background: transparent;
    padding-bottom: calc(env(safe-area-inset-bottom) + 24rpx);
}

.hero-card,
.section-card {
    padding: 28rpx;
    margin-bottom: 24rpx;
}

.section-tip {
    display: block;
    font-size: 26rpx;
    line-height: 1.75;
    color: #6c665c;

    & + .section-tip {
        margin-top: 12rpx;
    }
}

.hero-header,
.section-header,
.reply-header,
.meta-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.hero-user,
.user-info,
.score-row,
.meta-list,
.tag-list,
.image-grid,
.reply-images {
    display: flex;
}

.hero-user {
    align-items: center;
    gap: 16rpx;
}

.user-avatar {
    width: 88rpx;
    height: 88rpx;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.82);
}

.user-info {
    flex: 1;
    min-width: 0;
    flex-direction: column;
    gap: 6rpx;
}

.user-name {
    font-size: 30rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #111111);
}

.review-time {
    font-size: 24rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

.status-badge {
    padding: 10rpx 18rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 600;
}

.status-0 {
    background: rgba(159, 122, 46, 0.12);
    color: #9f7a2e;
}

.status-1 {
    background: rgba(77, 74, 66, 0.12);
    color: #4D4A42;
}

.status-2 {
    background: rgba(90, 68, 51, 0.12);
    color: #5a4433;
}

.score-row {
    align-items: center;
    gap: 12rpx;
    margin-top: 28rpx;
}

.score-stars {
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
}

.score-text {
    font-size: 26rpx;
    color: #5F5A50;
}

.meta-list {
    flex-direction: column;
    gap: 18rpx;
    margin-top: 28rpx;
}

.meta-label {
    font-size: 24rpx;
    color: #9a9388;
}

.meta-value {
    flex: 1;
    text-align: right;
    font-size: 26rpx;
    color: #5F5A50;
}

.section-title {
    font-size: 30rpx;
    font-weight: 700;
    color: #111111;
}

.review-content,
.reply-content,
.section-tip {
    display: block;
    font-size: 26rpx;
    line-height: 1.7;
    color: #5F5A50;
}

.empty-text {
    font-size: 26rpx;
    color: #9a9388;
}

.tag-list {
    flex-wrap: wrap;
    gap: 12rpx;
    margin-top: 24rpx;
}

.tag-item {
    padding: 10rpx 18rpx;
    border-radius: 999rpx;
    background: rgba(11, 11, 11, 0.08);
    border: 1rpx solid rgba(11, 11, 11, 0.16);
    font-size: 24rpx;
    color: var(--wm-color-primary, #0b0b0b);
}

.image-grid,
.reply-images {
    flex-wrap: wrap;
    gap: 16rpx;
    margin-top: 24rpx;
}

.review-image,
.reply-image {
    width: calc((100% - 32rpx) / 3);
    height: 200rpx;
    border-radius: 18rpx;
    background: #f8f7f2;
}

.reply-card {
    padding: 22rpx 24rpx;
    border-radius: 20rpx;
    background: #ffffff;
    border: 1rpx solid #E7E2D6;
}

.reply-card + .reply-card {
    margin-top: 16rpx;
}

.reply-type {
    font-size: 26rpx;
    font-weight: 600;
    color: #5F5A50;
}

.reply-time {
    font-size: 22rpx;
    color: #9a9388;
}

.section-tip {
    margin-top: 12rpx;
}

.loading-wrap {
    min-height: 100vh;
    background: transparent;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 24rpx;
}

.loading-text {
    font-size: 26rpx;
    color: #9a9388;
}

.safe-bottom {
    height: 1rpx;
}
</style>
