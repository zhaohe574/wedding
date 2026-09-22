<template>
    <view class="content-section">
        <view v-if="loading" class="loading-state">
            <tn-loading mode="circle" />
        </view>

        <view v-else-if="worksList.length" class="works-grid">
            <view
                v-for="work in worksList"
                :key="work.id"
                class="work-item"
                @click="emit('select-work', work)"
            >
                <view class="work-image-wrap">
                    <image
                        :src="work.cover || work.images?.[0] || '/static/images/default_cover.png'"
                        mode="aspectFill"
                        class="work-image"
                        lazy-load
                    />
                    <view class="work-type-badge">
                        <text>{{ work.type_desc || '精选大片' }}</text>
                    </view>
                </view>

                <view class="work-item__info">
                    <text class="work-title">{{ work.title || '婚礼作品' }}</text>
                    <view class="work-meta-row">
                        <text v-if="work.shoot_date" class="work-meta-text">{{ work.shoot_date }}</text>
                        <text class="work-meta-text">{{ work.view_count || 0 }} 浏览</text>
                    </view>
                </view>
            </view>
        </view>

        <view v-else class="empty-card">
            <BaseIcon name="empty-data" size="64" color="#D8D3C7" />
            <text class="empty-card__text">主创暂未上传作品</text>
        </view>
    </view>
</template>

<script setup lang="ts">
import BaseIcon from '@/components/base/BaseIcon.vue'

interface Props {
    worksList?: any[]
    loading?: boolean
}

withDefaults(defineProps<Props>(), {
    worksList: () => [],
    loading: false
})

const emit = defineEmits<{
    (e: 'select-work', work: any): void
}>()
</script>

<style lang="scss" scoped>
.content-section {
    display: flex;
    flex-direction: column;
}

.works-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20rpx;
}

.work-item {
    background: #FFFFFF;
    border-radius: 22rpx;
    overflow: hidden;
    box-shadow: 0 6rpx 24rpx rgba(24, 22, 20, 0.04);
    border: 1rpx solid rgba(217, 190, 130, 0.15);
    transition: transform 0.2s ease;

    &:active {
        transform: scale(0.98);
    }
}

.work-image-wrap {
    width: 100%;
    height: 240rpx;
    position: relative;
    background: #F2EFE9;
}

.work-image {
    width: 100%;
    height: 100%;
}

.work-type-badge {
    position: absolute;
    top: 14rpx;
    left: 14rpx;
    background: rgba(24, 22, 20, 0.7);
    backdrop-filter: blur(8px);
    border: 1rpx solid rgba(217, 190, 130, 0.35);
    border-radius: 10rpx;
    padding: 4rpx 14rpx;
    font-size: 20rpx;
    color: #FAF8F2;
    font-weight: 500;
}

.work-item__info {
    padding: 18rpx 20rpx;
}

.work-title {
    font-size: 27rpx;
    font-weight: 700;
    color: #181614;
    line-height: 1.4;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
}

.work-meta-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 12rpx;
}

.work-meta-text {
    font-size: 22rpx;
    color: #9E9890;
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
