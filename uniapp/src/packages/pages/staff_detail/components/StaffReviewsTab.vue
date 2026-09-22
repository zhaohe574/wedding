<template>
    <view class="content-section content-section--stack">
        <!-- 口碑总览评分卡 -->
        <view class="review-summary">
            <view class="review-summary-card">
                <view class="review-summary-val-wrap">
                    <text class="review-summary-value">
                        {{ reviewStats?.avg_score || '5.0' }}
                    </text>
                    <BaseIcon name="star-fill" size="28" color="#C8A45D" />
                </view>
                <text class="review-summary-label">综合评分</text>
            </view>

            <view class="review-summary-card">
                <text class="review-summary-value">
                    {{ reviewStats?.good_rate ?? 99 }}%
                </text>
                <text class="review-summary-label">新人好评率</text>
            </view>

            <view class="review-summary-card">
                <text class="review-summary-value">
                    {{ reviewStats?.total_count || 0 }}
                </text>
                <text class="review-summary-label">累计评价</text>
            </view>
        </view>

        <!-- 评价分类过滤胶囊 -->
        <view class="review-filter-row">
            <view class="review-filter-item">
                好评 {{ reviewStats?.good_count || 0 }}
            </view>
            <view class="review-filter-item">
                有图 {{ reviewStats?.image_count || 0 }}
            </view>
            <view class="review-filter-item">
                中差评 {{ (Number(reviewStats?.medium_count || 0) + Number(reviewStats?.bad_count || 0)) }}
            </view>
        </view>

        <!-- 评价列表 -->
        <view v-if="loading && !reviewsList.length" class="loading-state">
            <tn-loading mode="circle" />
        </view>

        <view v-else-if="reviewsList.length" class="reviews-list">
            <view
                v-for="review in reviewsList"
                :key="review.id"
                class="review-card"
                @click="emit('select-review', review)"
            >
                <view class="review-card-header">
                    <view class="review-user">
                        <image
                            class="review-user-avatar"
                            :src="review.user?.avatar || '/static/images/user/default_avatar.png'"
                            mode="aspectFill"
                        />

                        <view class="review-user-info">
                            <text class="review-user-name">
                                {{ review.user?.nickname || '匿名新人' }}
                            </text>
                            <text class="review-time">
                                {{ review.create_time_text || formatTime(review.create_time) }}
                            </text>
                        </view>
                    </view>

                    <view class="review-score">
                        <BaseIcon
                            v-for="star in 5"
                            :key="`${review.id}-${star}`"
                            :name="star <= Number(review.score || 0) ? 'star-fill' : 'star'"
                            size="22"
                            :color="star <= Number(review.score || 0) ? '#C8A45D' : '#D8D3C7'"
                        />
                    </view>
                </view>

                <text v-if="review.content" class="review-content">
                    {{ review.content }}
                </text>

                <view v-if="review.tags?.length" class="review-tag-list">
                    <view
                        v-for="tag in review.tags"
                        :key="tag.id || tag.name"
                        class="review-tag"
                    >
                        {{ tag.name }}
                    </view>
                </view>

                <view v-if="review.images?.length" class="review-image-list">
                    <image
                        v-for="(image, index) in review.images"
                        :key="`${review.id}-${index}`"
                        class="review-image"
                        :src="image"
                        mode="aspectFill"
                        @click.stop="previewImages(review.images, index)"
                    />
                </view>

                <view v-if="review.replies?.length" class="review-reply-list">
                    <view
                        v-for="reply in review.replies"
                        :key="reply.id"
                        class="review-reply-item"
                    >
                        <text class="review-reply-type">
                            {{ Number(reply.reply_type) === 1 ? '用户追评' : '商家致谢' }}
                        </text>
                        <text class="review-reply-content">
                            {{ reply.content }}
                        </text>
                    </view>
                </view>
            </view>

            <view v-if="hasMore" class="review-load-more">
                <text v-if="loading" class="review-load-more-text">加载中...</text>
                <text
                    v-else
                    class="review-load-more-text review-load-more-text--action"
                    @click="emit('load-more')"
                >
                    查看更多真实评价
                </text>
            </view>

            <view v-else class="review-load-more">
                <text class="review-load-more-text">已加载全部评价</text>
            </view>
        </view>

        <view v-else class="empty-card">
            <BaseIcon name="empty-data" size="64" color="#D8D3C7" />
            <text class="empty-card__text">暂无评价内容</text>
        </view>
    </view>
</template>

<script setup lang="ts">
import BaseIcon from '@/components/base/BaseIcon.vue'

interface Props {
    reviewsList?: any[]
    reviewStats?: any
    loading?: boolean
    hasMore?: boolean
}

withDefaults(defineProps<Props>(), {
    reviewsList: () => [],
    reviewStats: () => ({}),
    loading: false,
    hasMore: false
})

const emit = defineEmits<{
    (e: 'load-more'): void
    (e: 'select-review', review: any): void
}>()

const formatTime = (time: any) => {
    if (!time) return ''
    if (typeof time === 'string') return time.split(' ')[0]
    return ''
}

const previewImages = (images: string[], current: number) => {
    if (!images?.length) return
    uni.previewImage({
        urls: images,
        current: images[current] || images[0]
    })
}
</script>

<style lang="scss" scoped>
.content-section {
    display: flex;
    flex-direction: column;

    &--stack {
        gap: 24rpx;
    }
}

.review-summary {
    display: flex;
    gap: 16rpx;
}

.review-summary-card {
    flex: 1;
    background: #FFFFFF;
    border-radius: 20rpx;
    padding: 22rpx 12rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4rpx 18rpx rgba(24, 22, 20, 0.03);
    border: 1rpx solid rgba(217, 190, 130, 0.2);
}

.review-summary-val-wrap {
    display: flex;
    align-items: center;
    gap: 6rpx;
}

.review-summary-value {
    font-size: 38rpx;
    font-weight: 800;
    color: #C6A15B;
}

.review-summary-label {
    font-size: 22rpx;
    color: #8E8880;
    margin-top: 6rpx;
}

.review-filter-row {
    display: flex;
    gap: 14rpx;
}

.review-filter-item {
    background: #FAF6EE;
    border: 1rpx solid rgba(217, 190, 130, 0.3);
    border-radius: 30rpx;
    padding: 10rpx 24rpx;
    font-size: 24rpx;
    color: #75561E;
    font-weight: 500;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.review-card {
    background: #FFFFFF;
    border-radius: 24rpx;
    padding: 28rpx;
    box-shadow: 0 4rpx 20rpx rgba(24, 22, 20, 0.03);
    border: 1rpx solid rgba(217, 190, 130, 0.15);
}

.review-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.review-user {
    display: flex;
    align-items: center;
    gap: 16rpx;
}

.review-user-avatar {
    width: 68rpx;
    height: 68rpx;
    border-radius: 50%;
    background: #EEE;
    border: 1rpx solid rgba(217, 190, 130, 0.3);
}

.review-user-info {
    display: flex;
    flex-direction: column;
}

.review-user-name {
    font-size: 26rpx;
    font-weight: 600;
    color: #181614;
}

.review-time {
    font-size: 20rpx;
    color: #9E9890;
    margin-top: 4rpx;
}

.review-score {
    display: flex;
    gap: 4rpx;
}

.review-content {
    font-size: 26rpx;
    color: #333333;
    line-height: 1.6;
    margin-top: 18rpx;
}

.review-tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10rpx;
    margin-top: 14rpx;
}

.review-tag {
    background: #F8F6F2;
    border-radius: 8rpx;
    padding: 6rpx 14rpx;
    font-size: 20rpx;
    color: #8A7D6C;
}

.review-image-list {
    display: flex;
    flex-wrap: wrap;
    gap: 14rpx;
    margin-top: 16rpx;
}

.review-image {
    width: 156rpx;
    height: 156rpx;
    border-radius: 14rpx;
    background: #F2EFE9;
}

.review-reply-list {
    margin-top: 18rpx;
    background: #FAF8F4;
    border-radius: 16rpx;
    padding: 18rpx;
}

.review-reply-item {
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.review-reply-type {
    font-size: 22rpx;
    font-weight: 700;
    color: #C6A15B;
}

.review-reply-content {
    font-size: 24rpx;
    color: #555555;
    line-height: 1.5;
}

.review-load-more {
    text-align: center;
    padding: 24rpx 0;
}

.review-load-more-text {
    font-size: 24rpx;
    color: #9E9890;

    &--action {
        color: #C6A15B;
        font-weight: 600;
    }
}

.loading-state {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60rpx 0;
}

.empty-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60rpx 0;

    &__text {
        font-size: 26rpx;
        color: #9E9890;
        margin-top: 16rpx;
    }
}
</style>
