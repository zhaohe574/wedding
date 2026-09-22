<template>
    <page-meta :page-style="pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="评价详情" variant="solid" bg-color="#181614" text-color="#FFFDF8" />

        <view v-if="review" class="review-detail-page">
            <view class="review-detail-page__body wm-page-content">
                <!-- 1. 沉浸式黑金评价 Hero 卡片 -->
                <view class="hero-card">
                    <view class="hero-card__user-row">
                        <view class="hero-card__avatar-ring">
                            <image
                                class="hero-card__avatar"
                                :src="review.user?.avatar || '/static/images/user/default_avatar.png'"
                                mode="aspectFill"
                            />
                        </view>
                        <view class="hero-card__user-info">
                            <text class="hero-card__user-name">{{ review.user?.nickname || '匿名用户' }}</text>
                            <text class="hero-card__time">发布于 {{ review.create_time_text || '-' }}</text>
                        </view>
                        <StatusBadge
                            :tone="getStatusTone(review.status)"
                            size="sm"
                            dot
                        >
                            {{ review.status_text || '已评价' }}
                        </StatusBadge>
                    </view>

                    <view class="hero-card__score-bar">
                        <view class="hero-card__stars">
                            <BaseIcon
                                v-for="star in 5"
                                :key="star"
                                :name="star <= displayScoreStars ? 'star-fill' : 'star'"
                                size="32"
                                :color="star <= displayScoreStars ? '#C6A15B' : '#6E675F'"
                            />
                        </view>
                        <view class="hero-card__score-tag">
                            <text class="hero-card__score-text">{{ review.score_level || '好评' }} · {{ Number(review.score || 0).toFixed(1) }}分</text>
                        </view>
                    </view>
                </view>

                <!-- 2. 关联服务信息卡片 -->
                <view class="detail-card">
                    <view class="detail-card__header">
                        <text class="detail-card__title">服务信息</text>
                    </view>
                    <view class="detail-info-list">
                        <BaseInfoRow label="服务人员" :value="review.staff?.name || '-'" />
                        <BaseInfoRow label="订单编号" :value="review.order?.order_sn || '-'" />
                        <BaseInfoRow
                            label="服务项目"
                            :value="review.order_item?.package_name || review.orderItem?.package_name || '-'"
                        />
                        <BaseInfoRow
                            label="服务日期"
                            :value="review.service_date || review.order?.service_date || '-'"
                        />
                    </view>
                </view>

                <!-- 3. 审核说明（若有） -->
                <view v-if="review.status_summary" class="advisory-strip">
                    <BaseIcon name="tip" size="24" color="#9A6B35" />
                    <text class="advisory-strip__text">{{ review.status_summary }}</text>
                </view>

                <!-- 4. 评价正文与现场画报相册 -->
                <view class="detail-card">
                    <view class="detail-card__header">
                        <text class="detail-card__title">评价详情</text>
                    </view>

                    <!-- 评价标签 -->
                    <view v-if="review.tags?.length" class="tags-row">
                        <view
                            v-for="tag in review.tags"
                            :key="tag.id || tag.name"
                            class="tag-pill"
                        >
                            <text class="tag-pill__text">{{ tag.name }}</text>
                        </view>
                    </view>

                    <!-- 正文 -->
                    <view class="content-box">
                        <text v-if="review.content" class="content-box__text">{{ review.content }}</text>
                        <text v-else class="content-box__empty">未填写文字评价内容</text>
                    </view>

                    <!-- 晒图画廊 -->
                    <view v-if="review.images?.length" class="gallery-grid">
                        <view
                            v-for="(image, index) in review.images"
                            :key="`${image}-${index}`"
                            class="gallery-cell"
                            @click="previewImages(review.images, index)"
                        >
                            <image class="gallery-cell__img" :src="image" mode="aspectFill" />
                            <view class="gallery-cell__zoom">
                                <BaseIcon name="search" size="22" color="#FFFDF8" />
                            </view>
                        </view>
                    </view>
                </view>

                <!-- 5. 商家回复与追评记录 -->
                <view v-if="review.replies?.length" class="detail-card">
                    <view class="detail-card__header">
                        <text class="detail-card__title">沟通记录</text>
                        <text class="detail-card__subtitle">共 {{ review.replies.length }} 条回复</text>
                    </view>

                    <view class="replies-list">
                        <view
                            v-for="reply in review.replies"
                            :key="reply.id"
                            class="reply-bubble"
                            :class="{ 'reply-bubble--merchant': Number(reply.reply_type) !== 1 }"
                        >
                            <view class="reply-bubble__head">
                                <view class="reply-bubble__role-tag">
                                    <text class="reply-bubble__role-text">
                                        {{ Number(reply.reply_type) === 1 ? '新人追评' : '团队官方回复' }}
                                    </text>
                                </view>
                                <text class="reply-bubble__time">{{ formatDateTime(reply.create_time) }}</text>
                            </view>
                            <text class="reply-bubble__content">{{ reply.content }}</text>
                            <view v-if="reply.images?.length" class="reply-bubble__images">
                                <image
                                    v-for="(image, index) in reply.images"
                                    :key="`${image}-${index}`"
                                    class="reply-bubble__thumb"
                                    :src="image"
                                    mode="aspectFill"
                                    @click="previewImages(reply.images, index)"
                                />
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <!-- 加载状态 -->
        <view v-else class="review-detail-page__loading">
            <tn-loading size="40" mode="flower" color="#C6A15B" />
            <text class="review-detail-page__loading-text">正在加载评价详情...</text>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad, onPullDownRefresh } from '@dcloudio/uni-app'
import PageShell from '@/components/base/PageShell.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseInfoRow from '@/components/base/BaseInfoRow.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { getReviewDetail } from '@/packages/common/api/review'
import { useThemeStore } from '@/stores/theme'
import { showError } from '@/utils/feedback'
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

const getStatusTone = (status: number) => {
    const map: Record<number, 'neutral' | 'success' | 'warning' | 'danger' | 'pending'> = {
        0: 'pending',
        1: 'success',
        2: 'danger'
    }
    return map[Number(status)] || 'neutral'
}

const formatDateTime = (value: number | string) => {
    if (!value) return '-'
    if (typeof value === 'number') {
        return new Date(value * 1000).toLocaleString()
    }
    const normalized = value.includes('T') ? value : value.replace(' ', 'T')
    const time = new Date(normalized).getTime()
    if (Number.isNaN(time)) return value
    return new Date(time).toLocaleString()
}

const previewImages = (images: Array<string | number>, index: number | string = 0) => {
    const urls = (images || []).map((item) => String(item)).filter(Boolean)
    if (!urls.length) return
    const currentIndex = Number(index || 0)
    uni.previewImage({
        urls,
        current: urls[currentIndex] || urls[0]
    })
}

const fetchDetail = async () => {
    if (!reviewId.value) return
    try {
        review.value = await getReviewDetail({ id: reviewId.value })
    } catch (e: any) {
        showError(e, '加载失败')
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

<style lang="scss" scoped>
.review-detail-page {
    min-height: 100vh;
    background:
        radial-gradient(ellipse at 50% 0%, rgba(217, 190, 130, 0.1) 0%, rgba(248, 246, 240, 0) 65%),
        var(--wm-color-bg-page, #F8F6F0);
    box-sizing: border-box;
    padding-bottom: calc(44rpx + env(safe-area-inset-bottom));

    &__body {
        padding: 24rpx var(--wm-space-page-x, 28rpx) 40rpx;
        display: flex;
        flex-direction: column;
        gap: 24rpx;
        box-sizing: border-box;
    }

    &__loading {
        min-height: 60vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 16rpx;
    }

    &__loading-text {
        font-size: 24rpx;
        color: var(--wm-color-text-secondary, #5E564B);
    }
}

/* 沉浸式黑金 Hero 卡片 */
.hero-card {
    position: relative;
    padding: 34rpx 32rpx;
    border-radius: 36rpx;
    background: linear-gradient(145deg, #181614 0%, #29241C 100%);
    border: 1rpx solid rgba(217, 190, 130, 0.45);
    box-shadow: 0 18rpx 44rpx rgba(24, 22, 20, 0.22);
    display: flex;
    flex-direction: column;
    gap: 24rpx;
    box-sizing: border-box;

    &__user-row {
        display: flex;
        align-items: center;
        gap: 18rpx;
    }

    &__avatar-ring {
        width: 88rpx;
        height: 88rpx;
        border-radius: 50%;
        padding: 3rpx;
        box-sizing: border-box;
        background: linear-gradient(135deg, #D9BE82 0%, #FAF6EE 100%);
        box-shadow: 0 6rpx 14rpx rgba(24, 22, 20, 0.08);
        flex-shrink: 0;
    }

    &__avatar {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: #FFFFFF;
        display: block;
    }

    &__user-info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__user-name {
        font-size: 30rpx;
        font-weight: 800;
        color: #FFFDF8;
    }

    &__time {
        font-size: 21rpx;
        color: rgba(255, 253, 248, 0.6);
    }

    &__score-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 18rpx;
        border-top: 1rpx solid rgba(217, 190, 130, 0.2);
    }

    &__stars {
        display: flex;
        align-items: center;
        gap: 10rpx;
    }

    &__score-tag {
        padding: 6rpx 18rpx;
        border-radius: 999rpx;
        background: rgba(217, 190, 130, 0.2);
        border: 1rpx solid rgba(217, 190, 130, 0.4);
    }

    &__score-text {
        font-size: 23rpx;
        font-weight: 800;
        color: #D9BE82;
    }
}

/* 详情通用卡片 */
.detail-card {
    padding: 30rpx 28rpx;
    border-radius: 32rpx;
    background: linear-gradient(180deg, #FFFFFF 0%, #FAF8F5 100%);
    border: 1rpx solid rgba(217, 190, 130, 0.4);
    box-shadow: 0 12rpx 32rpx rgba(24, 22, 20, 0.05);
    display: flex;
    flex-direction: column;
    gap: 18rpx;
    box-sizing: border-box;

    &__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 4rpx;
    }

    &__title {
        font-size: 28rpx;
        font-weight: 800;
        color: var(--wm-color-primary, #181614);
    }

    &__subtitle {
        font-size: 21rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }
}

.detail-info-list {
    display: flex;
    flex-direction: column;
    gap: 0;

    :deep(.base-info-row + .base-info-row) {
        border-top: 1rpx solid rgba(231, 224, 211, 0.7);
    }
}

.advisory-strip {
    display: flex;
    align-items: center;
    gap: 10rpx;
    padding: 16rpx 22rpx;
    border-radius: 20rpx;
    background: rgba(217, 190, 130, 0.18);
    border: 1rpx solid rgba(217, 190, 130, 0.4);

    &__text {
        font-size: 23rpx;
        color: var(--wm-color-clay, #9A6B35);
        line-height: 1.4;
    }
}

/* 标签列表 */
.tags-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10rpx;
}

.tag-pill {
    padding: 6rpx 18rpx;
    border-radius: 999rpx;
    background: rgba(217, 190, 130, 0.18);
    border: 1rpx solid rgba(217, 190, 130, 0.35);

    &__text {
        font-size: 21rpx;
        font-weight: 600;
        color: var(--wm-color-clay, #9A6B35);
    }
}

/* 正文框 */
.content-box {
    padding: 20rpx 22rpx;
    border-radius: 22rpx;
    background: rgba(250, 246, 238, 0.85);
    border: 1rpx solid rgba(231, 224, 211, 0.85);

    &__text {
        font-size: 26rpx;
        line-height: 1.65;
        color: var(--wm-color-primary, #181614);
    }

    &__empty {
        font-size: 24rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }
}

/* 晒图相册网格 */
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14rpx;
}

.gallery-cell {
    position: relative;
    width: 100%;
    height: 190rpx;
    border-radius: 20rpx;
    overflow: hidden;
    border: 1rpx solid rgba(217, 190, 130, 0.35);

    &__img {
        width: 100%;
        height: 100%;
        display: block;
    }

    &__zoom {
        position: absolute;
        bottom: 8rpx;
        right: 8rpx;
        width: 40rpx;
        height: 40rpx;
        border-radius: 50%;
        background: rgba(24, 22, 20, 0.65);
        display: flex;
        align-items: center;
        justify-content: center;
    }
}

/* 回复记录 */
.replies-list {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.reply-bubble {
    padding: 20rpx 22rpx;
    border-radius: 24rpx;
    background: rgba(250, 246, 238, 0.9);
    border: 1rpx solid rgba(217, 190, 130, 0.35);
    display: flex;
    flex-direction: column;
    gap: 10rpx;

    &--merchant {
        background: rgba(248, 242, 228, 0.95);
        border-color: rgba(217, 190, 130, 0.55);
    }

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    &__role-tag {
        padding: 4rpx 14rpx;
        border-radius: 999rpx;
        background: rgba(198, 161, 91, 0.2);
    }

    &__role-text {
        font-size: 20rpx;
        font-weight: 700;
        color: var(--wm-color-clay, #9A6B35);
    }

    &__time {
        font-size: 20rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }

    &__content {
        font-size: 24rpx;
        line-height: 1.6;
        color: var(--wm-color-primary, #181614);
    }

    &__images {
        display: flex;
        gap: 10rpx;
        margin-top: 4rpx;
    }

    &__thumb {
        width: 120rpx;
        height: 120rpx;
        border-radius: 14rpx;
        border: 1rpx solid rgba(217, 190, 130, 0.3);
    }
}
</style>
