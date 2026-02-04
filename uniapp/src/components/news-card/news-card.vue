<template>
    <navigator :url="`/pages/news_detail/news_detail?id=${newsId}`" class="news-card-wrapper">
        <view class="news-card">
            <image 
                v-if="item.image" 
                class="news-cover" 
                :src="item.image" 
                mode="aspectFill"
                lazy-load
            />
            <view class="news-content">
                <text class="news-title">{{ item.title }}</text>
                <text class="news-desc">{{ item.desc }}</text>
                <view class="news-meta">
                    <view class="meta-item">
                        <tn-icon name="clock" size="24" color="#999999" />
                        <text class="meta-text">{{ item.create_time }}</text>
                    </view>
                    <view class="meta-item">
                        <tn-icon name="eye" size="24" color="#999999" />
                        <text class="meta-text">{{ item.click }}</text>
                    </view>
                </view>
            </view>
        </view>
    </navigator>
</template>

<script lang="ts" setup>
import { ref } from 'vue'

const props = withDefaults(
    defineProps<{
        item: any
        newsId: number
    }>(),
    {
        item: {},
        newsId: ''
    }
)
</script>

<style lang="scss" scoped>
.news-card-wrapper {
    display: block;
    margin: 0 24rpx 24rpx;
}

.news-card {
    background: #ffffff;
    border-radius: 16rpx;
    padding: 24rpx;
    display: flex;
    gap: 24rpx;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);
    transition: all 0.2s ease;

    &:active {
        transform: translateY(-2rpx);
        box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.12);
    }
}

.news-cover {
    width: 240rpx;
    height: 180rpx;
    border-radius: 12rpx;
    flex-shrink: 0;
    background: #f5f5f5;
}

.news-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-width: 0;
}

.news-title {
    font-size: 30rpx;
    font-weight: 600;
    color: #333333;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-all;
}

.news-desc {
    margin-top: 12rpx;
    font-size: 26rpx;
    color: #999999;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-all;
}

.news-meta {
    margin-top: 16rpx;
    display: flex;
    align-items: center;
    gap: 32rpx;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8rpx;
}

.meta-text {
    font-size: 24rpx;
    color: #999999;
}
</style>
