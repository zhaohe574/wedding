<template>
  <base-card class="order-card">
    <!-- 状态标签 -->
    <view class="order-card__status" :style="{ backgroundColor: statusColor }">
      <text class="order-card__status-text">{{ statusText }}</text>
    </view>
    
    <!-- 人员信息 -->
    <view class="order-card__staff" @click="handleStaffClick">
      <image class="order-card__avatar" :src="order.staff.avatar" mode="aspectFill" />
      <view class="order-card__staff-info">
        <text class="order-card__staff-name">{{ order.staff.name }}</text>
        <text class="order-card__staff-category">{{ order.staff.category }}</text>
      </view>
    </view>
    
    <!-- 服务信息 -->
    <view class="order-card__service">
      <view class="order-card__service-item">
        <tn-icon name="calendar" size="28" color="#999999" />
        <text class="order-card__service-text">{{ order.serviceDate }} {{ order.serviceTime }}</text>
      </view>
      <view class="order-card__service-item">
        <tn-icon name="map-pin" size="28" color="#999999" />
        <text class="order-card__service-text">{{ order.location }}</text>
      </view>
    </view>
    
    <!-- 价格信息 -->
    <view class="order-card__price">
      <view class="order-card__price-row">
        <text class="order-card__price-label">原价：</text>
        <text class="order-card__price-value">¥{{ order.originalPrice }}</text>
      </view>
      <view v-if="order.discount > 0" class="order-card__price-row">
        <text class="order-card__price-label">优惠：</text>
        <text class="order-card__price-discount">-¥{{ order.discount }}</text>
      </view>
      <view class="order-card__price-row order-card__price-row--total">
        <text class="order-card__price-label">实付：</text>
        <text class="order-card__price-total">¥{{ order.actualPrice }}</text>
      </view>
    </view>
    
    <!-- 操作按钮 -->
    <view v-if="order.actions && order.actions.length" class="order-card__actions">
      <base-button
        v-for="(action, index) in order.actions"
        :key="index"
        :type="action.type === 'primary' ? 'primary' : 'secondary'"
        size="sm"
        @click="handleAction(action)"
      >
        {{ action.text }}
      </base-button>
    </view>
  </base-card>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseButton from '@/components/base/BaseButton.vue'

interface OrderData {
  id: number
  orderNo: string
  status: 'pending' | 'unpaid' | 'paid' | 'completed' | 'cancelled'
  staff: {
    id: number
    name: string
    avatar: string
    category: string
  }
  serviceDate: string
  serviceTime: string
  location: string
  originalPrice: number
  discount: number
  actualPrice: number
  actions: Array<{
    text: string
    type: 'primary' | 'secondary' | 'danger'
    action: string
  }>
}

interface Props {
  order: OrderData
}

const props = defineProps<Props>()

const emit = defineEmits<{
  staffClick: [staffId: number]
  action: [action: { text: string; type: string; action: string }, order: OrderData]
}>()

// 状态配置
const statusConfig = {
  pending: { text: '待确认', color: '#FF9900' },
  unpaid: { text: '待支付', color: '#F97316' },
  paid: { text: '已支付', color: '#10B981' },
  completed: { text: '已完成', color: '#6B7280' },
  cancelled: { text: '已取消', color: '#EF4444' }
}

// 计算状态文本
const statusText = computed(() => {
  return statusConfig[props.order.status]?.text || '未知状态'
})

// 计算状态颜色
const statusColor = computed(() => {
  return statusConfig[props.order.status]?.color || '#999999'
})

// 处理人员点击
const handleStaffClick = () => {
  emit('staffClick', props.order.staff.id)
}

// 处理操作按钮点击
const handleAction = (action: { text: string; type: string; action: string }) => {
  emit('action', action, props.order)
}
</script>

<script lang="ts">
export default {
  name: 'OrderCard',
  options: {
    virtualHost: true
  }
}
</script>

<style lang="scss" scoped>
.order-card {
  position: relative;
  
  &__status {
    position: absolute;
    top: 0;
    right: 0;
    padding: 8rpx 16rpx;
    border-radius: 0 16rpx 0 16rpx;
  }
  
  &__status-text {
    font-size: 24rpx;
    color: #FFFFFF;
    font-weight: 500;
  }
  
  &__staff {
    display: flex;
    align-items: center;
    gap: 16rpx;
    padding-top: 8rpx;
    margin-bottom: 16rpx;
    cursor: pointer;
    
    &:active {
      opacity: 0.7;
    }
  }
  
  &__avatar {
    width: 80rpx;
    height: 80rpx;
    border-radius: 50%;
    flex-shrink: 0;
  }
  
  &__staff-info {
    display: flex;
    flex-direction: column;
    gap: 4rpx;
    flex: 1;
  }
  
  &__staff-name {
    font-size: 32rpx;
    font-weight: 600;
    color: var(--color-main, #333333);
  }
  
  &__staff-category {
    font-size: 26rpx;
    color: var(--color-content, #666666);
  }
  
  &__service {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    margin-bottom: 16rpx;
    padding: 16rpx;
    background: var(--color-bg-primary, #FAF5FF);
    border-radius: 12rpx;
  }
  
  &__service-item {
    display: flex;
    align-items: center;
    gap: 8rpx;
  }
  
  &__service-text {
    font-size: 26rpx;
    color: var(--color-content, #666666);
  }
  
  &__price {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    padding: 16rpx 0;
    border-top: 1rpx solid var(--color-light, #E5E5E5);
    border-bottom: 1rpx solid var(--color-light, #E5E5E5);
    margin-bottom: 16rpx;
  }
  
  &__price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    
    &--total {
      margin-top: 4rpx;
    }
  }
  
  &__price-label {
    font-size: 26rpx;
    color: var(--color-content, #666666);
  }
  
  &__price-value {
    font-size: 26rpx;
    color: var(--color-main, #333333);
  }
  
  &__price-discount {
    font-size: 26rpx;
    color: var(--color-cta, #F97316);
  }
  
  &__price-total {
    font-size: 32rpx;
    font-weight: 600;
    color: var(--color-cta, #F97316);
  }
  
  &__actions {
    display: flex;
    gap: 16rpx;
    justify-content: flex-end;
  }
}
</style>
