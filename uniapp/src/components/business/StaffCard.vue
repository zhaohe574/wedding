<template>
  <base-card class="staff-card" @click="handleClick">
    <view class="staff-card__content">
      <!-- 头像和基本信息 -->
      <view class="staff-card__header">
        <image class="staff-card__avatar" :src="staff.avatar" mode="aspectFill" />
        <view class="staff-card__info">
          <view class="staff-card__name-row">
            <text class="staff-card__name">{{ staff.name }}</text>
            <view class="staff-card__rating">
              <tn-icon name="star-fill" size="24" color="#FFD700" />
              <text class="staff-card__rating-text">{{ staff.rating }}</text>
              <text class="staff-card__review-count">({{ staff.reviewCount }})</text>
            </view>
          </view>
          <text class="staff-card__category">{{ staff.category }}</text>
          <view class="staff-card__price-row">
            <text class="staff-card__price">¥{{ staff.price }}</text>
            <text class="staff-card__price-unit">起</text>
          </view>
        </view>
      </view>
      
      <!-- 标签 -->
      <view v-if="staff.tags && staff.tags.length" class="staff-card__tags">
        <view
          v-for="(tag, index) in staff.tags.slice(0, 3)"
          :key="index"
          class="staff-card__tag"
        >
          {{ tag }}
        </view>
      </view>
      
      <!-- 收藏按钮 -->
      <view v-if="showFavorite" class="staff-card__favorite" @click.stop="handleFavorite">
        <tn-icon
          :name="staff.isFavorite ? 'heart-fill' : 'heart'"
          size="40"
          :color="staff.isFavorite ? '#EC4899' : '#999999'"
        />
      </view>
    </view>
  </base-card>
</template>

<script setup lang="ts">
import BaseCard from '@/components/base/BaseCard.vue'

interface StaffData {
  id: number
  name: string
  avatar: string
  category: string
  rating: number
  reviewCount: number
  price: number
  tags?: string[]
  isFavorite?: boolean
}

interface Props {
  staff: StaffData
  showFavorite?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showFavorite: true
})

const emit = defineEmits<{
  click: [staff: StaffData]
  favorite: [staff: StaffData]
}>()

// 处理点击事件
const handleClick = () => {
  emit('click', props.staff)
}

// 处理收藏事件
const handleFavorite = () => {
  emit('favorite', props.staff)
}
</script>

<script lang="ts">
export default {
  name: 'StaffCard',
  options: {
    virtualHost: true
  }
}
</script>

<style lang="scss" scoped>
.staff-card {
  position: relative;
  
  &__content {
    position: relative;
  }
  
  &__header {
    display: flex;
    gap: 16rpx;
  }
  
  &__avatar {
    width: 80rpx;
    height: 80rpx;
    border-radius: 50%;
    flex-shrink: 0;
  }
  
  &__info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4rpx;
  }
  
  &__name-row {
    display: flex;
    align-items: center;
    gap: 8rpx;
  }
  
  &__name {
    font-size: 32rpx;
    font-weight: 600;
    color: var(--color-main, #333333);
  }
  
  &__rating {
    display: flex;
    align-items: center;
    gap: 4rpx;
  }
  
  &__rating-text {
    font-size: 26rpx;
    font-weight: 500;
    color: var(--color-main, #333333);
  }
  
  &__review-count {
    font-size: 24rpx;
    color: var(--color-muted, #999999);
  }
  
  &__category {
    font-size: 26rpx;
    color: var(--color-content, #666666);
  }
  
  &__price-row {
    display: flex;
    align-items: baseline;
    gap: 4rpx;
  }
  
  &__price {
    font-size: 32rpx;
    font-weight: 600;
    color: var(--color-cta, #F97316);
  }
  
  &__price-unit {
    font-size: 24rpx;
    color: var(--color-cta, #F97316);
  }
  
  &__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8rpx;
    margin-top: 16rpx;
  }
  
  &__tag {
    padding: 4rpx 12rpx;
    background: var(--color-primary-light-9, #FAF5FF);
    color: var(--color-primary, #7C3AED);
    font-size: 24rpx;
    border-radius: 8rpx;
  }
  
  &__favorite {
    position: absolute;
    top: 0;
    right: 0;
    padding: 8rpx;
    transition: transform 0.2s ease;
    
    &:active {
      transform: scale(1.2);
    }
  }
}
</style>
