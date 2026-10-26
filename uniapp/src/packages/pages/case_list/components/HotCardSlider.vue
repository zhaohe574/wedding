<template>
    <view class="hot-card-container">
        <swiper
            class="hot-swiper"
            :current="currentIndex"
            previous-margin="32rpx"
            next-margin="80rpx"
            circular
            @change="handleSwiperChange"
        >
            <swiper-item
                v-for="(item, index) in items"
                :key="item.caseId || index"
                class="hot-swiper-item"
                @tap="handleCardTap(item)"
            >
                <view
                    class="hot-card-wrapper"
                    :class="{ 'hot-card-active': currentIndex === index }"
                >
                    <!-- 封面大图与动态光晕 -->
                    <view class="cover-box">
                        <image
                            class="card-cover"
                            :src="item.refCover"
                            mode="aspectFill"
                            lazy-load
                        />
                        <!-- 底部虚化投影 -->
                        <view
                            v-if="currentIndex === index"
                            class="cover-blur-shadow"
                            :style="{ backgroundImage: `url(${item.refCover})` }"
                        ></view>
                        <!-- 爆款角标 -->
                        <view v-if="item.isHot" class="hot-badge">
                            <image
                                class="hot-badge-icon"
                                src="/packages/static/images/case/case-hot-icon.png"
                                mode="aspectFit"
                            />
                            <text class="hot-badge-text">{{ item.explosionMemo || '热门爆款' }}</text>
                        </view>
                    </view>

                    <!-- 下方详情区（激活时清晰展现） -->
                    <view class="card-info" :class="{ 'card-info--visible': currentIndex === index }">
                        <view class="title-and-price">
                            <text class="case-title">{{ item.theme }}</text>
                            <view class="price-box">
                                <text class="price-symbol">¥</text>
                                <text class="price-num">{{ item.refPrice }}</text>
                            </view>
                        </view>
                        <view class="reason-and-hotel">
                            <view class="hotel-pill">
                                <image
                                    class="hotel-icon"
                                    src="/packages/static/images/case/case-more.png"
                                    mode="aspectFit"
                                />
                                <text class="hotel-name">{{ item.hotel }}</text>
                            </view>
                            <text class="style-text">{{ item.refStyle }}</text>
                        </view>
                    </view>
                </view>
            </swiper-item>

            <!-- 最后一页：查看更多卡片 -->
            <swiper-item class="hot-swiper-item more-item" @tap="handleSeeMore">
                <view class="see-more-card">
                    <text class="see-more-text">查看更多爆款</text>
                    <view class="see-more-arrow">></view>
                </view>
            </swiper-item>
        </swiper>
    </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { CaseItem } from '@/api/case'

interface Props {
    items: CaseItem[]
}

const props = withDefaults(defineProps<Props>(), {
    items: () => []
})

const emit = defineEmits<{
    (e: 'select', item: CaseItem): void
    (e: 'more'): void
}>()

const currentIndex = ref(0)

const handleSwiperChange = (e: any) => {
    currentIndex.value = e.detail.current
}

const handleCardTap = (item: CaseItem) => {
    emit('select', item)
}

const handleSeeMore = () => {
    emit('more')
}
</script>

<style scoped lang="scss">
.hot-card-container {
    width: 100%;
    height: 540rpx;
    box-sizing: border-box;
}

.hot-swiper {
    width: 100%;
    height: 100%;
}

.hot-swiper-item {
    display: flex;
    align-items: center;
    box-sizing: border-box;
}

.hot-card-wrapper {
    width: 580rpx;
    height: 510rpx;
    position: relative;
    box-sizing: border-box;
    transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
    transform: scale(0.94);
    opacity: 0.85;

    &.hot-card-active {
        transform: scale(1);
        opacity: 1;
    }
}

.cover-box {
    position: relative;
    width: 100%;
    height: 370rpx;
    border-radius: 20rpx;
}

.card-cover {
    width: 100%;
    height: 100%;
    border-radius: 20rpx;
    background-color: #f7f8fa;
    display: block;
}

.cover-blur-shadow {
    position: absolute;
    top: 30rpx;
    left: 20rpx;
    right: 20rpx;
    bottom: -16rpx;
    background-size: cover;
    background-position: center;
    filter: blur(24rpx);
    opacity: 0.45;
    z-index: -1;
    border-radius: 24rpx;
}

.hot-badge {
    position: absolute;
    top: 20rpx;
    left: 20rpx;
    display: flex;
    align-items: center;
    background: rgba(0, 0, 0, 0.55);
    backdrop-filter: blur(8rpx);
    border-radius: 100rpx;
    padding: 6rpx 18rpx 6rpx 12rpx;
}

.hot-badge-icon {
    width: 26rpx;
    height: 28rpx;
    margin-right: 8rpx;
}

.hot-badge-text {
    font-size: 22rpx;
    font-weight: 500;
    color: #ffd992;
    letter-spacing: 1rpx;
}

.card-info {
    margin-top: 22rpx;
    opacity: 0.7;
    transition: opacity 0.3s ease;

    &.card-info--visible {
        opacity: 1;
    }
}

.title-and-price {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.case-title {
    font-size: 34rpx;
    font-weight: 600;
    color: #2b2b2b;
    max-width: 360rpx;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.price-box {
    display: flex;
    align-items: baseline;
    color: #c29b6b;
}

.price-symbol {
    font-size: 24rpx;
    font-weight: 600;
    margin-right: 4rpx;
}

.price-num {
    font-size: 36rpx;
    font-weight: 700;
    font-family: 'DIN Alternate', PingFangSC-Medium, sans-serif;
}

.reason-and-hotel {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 10rpx;
}

.hotel-pill {
    display: flex;
    align-items: center;
    max-width: 360rpx;
}

.hotel-icon {
    width: 22rpx;
    height: 22rpx;
    margin-right: 6rpx;
    opacity: 0.6;
}

.hotel-name {
    font-size: 24rpx;
    color: #8c8d90;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.style-text {
    font-size: 24rpx;
    color: #c29b6b;
    font-weight: 500;
}

/* 查看更多卡片 */
.see-more-card {
    width: 140rpx;
    height: 370rpx;
    background: #f7f8fa;
    border-radius: 20rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin-left: 20rpx;
    box-sizing: border-box;
    padding: 30rpx 20rpx;
}

.see-more-text {
    writing-mode: vertical-rl;
    font-size: 26rpx;
    color: #646566;
    letter-spacing: 8rpx;
    line-height: 40rpx;
}

.see-more-arrow {
    margin-top: 16rpx;
    font-size: 24rpx;
    color: #999;
}
</style>
