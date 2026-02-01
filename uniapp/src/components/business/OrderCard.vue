<template>
  <view class="order-card" @click="handleCardClick">
    <!-- 状态标签 -->
    <view class="order-card__status" :style="statusStyle">
      <text class="order-card__status-text">{{ statusText }}</text>
    </view>
    
    <!-- 订单抬头 -->
    <view class="order-card__header">
      <view class="order-card__order-info">
        <text class="order-card__order-sn">订单号：{{ order.orderNo }}</text>
        <text class="order-card__order-time">{{ order.createTime }}</text>
      </view>
    </view>

    <!-- 人员/套餐信息 -->
    <view class="order-card__main">
      <!-- 人员信息 -->
      <view class="order-card__staff-section">
        <image class="order-card__avatar" :src="primaryStaff.avatar" mode="aspectFill" />
        <view class="order-card__staff-info">
          <text class="order-card__staff-name">{{ staffSummary }}</text>
          <text class="order-card__package-name">{{ packageSummary }}</text>
        </view>
      </view>
      
      <!-- 服务信息 -->
      <view class="order-card__service-info">
        <!-- 预约时间场次 -->
        <view class="order-card__info-row">
          <view class="order-card__info-icon-wrapper">
            <tn-icon name="calendar" size="28" :color="$theme.primaryColor" />
          </view>
          <view class="order-card__info-content">
            <text class="order-card__info-label">预约档期</text>
            <!-- 按日期分组显示场次 -->
            <view class="order-card__schedule-list">
              <view 
                v-for="(group, index) in groupedSchedules" 
                :key="index"
                class="order-card__schedule-item"
              >
                <view class="order-card__schedule-date">
                  <text class="order-card__schedule-date-text">{{ group.date }}</text>
                </view>
                <view class="order-card__schedule-slots">
                  <view 
                    v-for="(slot, slotIndex) in group.slots"
                    :key="slotIndex"
                    class="order-card__schedule-slot"
                    :style="{ 
                      backgroundColor: getSlotBgColor($theme.primaryColor),
                      color: $theme.primaryColor,
                      borderColor: $theme.primaryColor
                    }"
                  >
                    {{ slot.timeSlotDesc }}
                  </view>
                </view>
              </view>
            </view>
          </view>
        </view>
        
        <!-- 服务地址 -->
        <view class="order-card__info-row">
          <view class="order-card__info-icon-wrapper">
            <tn-icon name="location" size="28" color="#999999" />
          </view>
          <view class="order-card__info-content">
            <view class="order-card__location-wrapper">
              <text class="order-card__location-text">{{ order.location }}</text>
            </view>
          </view>
        </view>
      </view>
    </view>
    
    <!-- 价格信息 -->
    <view class="order-card__price">
      <view class="order-card__price-left">
        <view v-if="order.discount > 0" class="order-card__price-detail">
          <text class="order-card__price-label">原价：</text>
          <text class="order-card__price-original">¥{{ order.originalPrice }}</text>
        </view>
        <view v-if="order.discount > 0" class="order-card__price-detail">
          <text class="order-card__price-label">优惠：</text>
          <text class="order-card__price-discount">-¥{{ order.discount }}</text>
        </view>
      </view>
      <view class="order-card__price-right">
        <text class="order-card__price-label-total">实付</text>
        <view class="order-card__price-total-wrapper">
          <text class="order-card__price-symbol" :style="{ color: $theme.ctaColor }">¥</text>
          <text class="order-card__price-total" :style="{ color: $theme.ctaColor }">{{ order.actualPrice }}</text>
        </view>
      </view>
    </view>
    
    <!-- 操作按钮 -->
    <view v-if="order.actions && order.actions.length" class="order-card__actions">
      <view
        v-for="(action, index) in order.actions"
        :key="index"
        class="order-card__action-btn"
        :class="{ 'order-card__action-btn--primary': action.type === 'primary' }"
        :style="getActionButtonStyle(action.type)"
        @click.stop="handleAction(action)"
      >
        <text class="order-card__action-text" :style="getActionTextStyle(action.type)">
          {{ action.text }}
        </text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useThemeStore } from '@/stores/theme'

interface OrderData {
  id: number
  orderNo: string
  status:
    | 'pending_confirm'
    | 'pending_pay'
    | 'paid'
    | 'in_service'
    | 'completed'
    | 'reviewed'
    | 'cancelled'
    | 'paused'
    | 'refunded'
  location: string
  originalPrice: number
  discount: number
  actualPrice: number
  createTime: string
  items: Array<{
    id: number
    staffId: number
    staffName: string
    staffAvatar: string
    packageName: string
    serviceDate: string
    timeSlot: number
    timeSlotDesc?: string
  }>
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
const $theme = useThemeStore()

const emit = defineEmits<{
  click: [orderId: number]
  action: [action: { text: string; type: string; action: string }, order: OrderData]
}>()

const getTimeSlotLabel = (timeSlot: any) => {
  const map: Record<number, string> = {
    0: '全天',
    1: '早礼',
    2: '午宴',
    3: '晚宴'
  }
  const slot = Number(timeSlot ?? -1)
  return Number.isFinite(slot) && slot >= 0 ? (map[slot] || '未知场次') : '未选择场次'
}

// 状态配置（使用设计规范中的状态色）
const statusConfig = {
  pending_confirm: { text: '待确认', color: '#F59E0B', bgColor: 'rgba(245, 158, 11, 0.12)' },
  pending_pay: { text: '待支付', color: '#F97316', bgColor: 'rgba(249, 115, 22, 0.12)' },
  paid: { text: '已支付', color: '#19BE6B', bgColor: 'rgba(25, 190, 107, 0.12)' },
  in_service: { text: '服务中', color: '#3B82F6', bgColor: 'rgba(59, 130, 246, 0.12)' },
  completed: { text: '已完成', color: '#16A34A', bgColor: 'rgba(22, 163, 74, 0.12)' },
  reviewed: { text: '已评价', color: '#10B981', bgColor: 'rgba(16, 185, 129, 0.12)' },
  cancelled: { text: '已取消', color: '#9CA3AF', bgColor: 'rgba(156, 163, 175, 0.12)' },
  paused: { text: '已暂停', color: '#F59E0B', bgColor: 'rgba(245, 158, 11, 0.12)' },
  refunded: { text: '已退款', color: '#EF4444', bgColor: 'rgba(239, 68, 68, 0.12)' }
}

const staffList = computed(() => {
  const map = new Map<string, { name: string; avatar: string }>()
  props.order.items.forEach((item) => {
    const name = item.staffName || '服务人员'
    if (!map.has(name)) {
      map.set(name, { name, avatar: item.staffAvatar || '/static/images/default-avatar.png' })
    }
  })
  return Array.from(map.values())
})

const packageList = computed(() => {
  const set = new Set<string>()
  props.order.items.forEach((item) => {
    set.add(item.packageName || '服务套餐')
  })
  return Array.from(set.values())
})

const dateList = computed(() => {
  const set = new Set<string>()
  props.order.items.forEach((item) => {
    const date = item.serviceDate || '未选择日期'
    const slotText = item.timeSlotDesc || getTimeSlotLabel(item.timeSlot)
    set.add(`${date} ${slotText}`)
  })
  return Array.from(set.values())
})

// 按日期分组的场次列表
const groupedSchedules = computed(() => {
  const groups = new Map<string, Array<{ timeSlot: number; timeSlotDesc: string }>>()
  
  props.order.items.forEach((item) => {
    const date = item.serviceDate || '未选择日期'
    const slotText = item.timeSlotDesc || getTimeSlotLabel(item.timeSlot)
    
    if (!groups.has(date)) {
      groups.set(date, [])
    }
    
    // 检查是否已存在相同场次
    const slots = groups.get(date)!
    const exists = slots.some(s => s.timeSlot === item.timeSlot)
    
    if (!exists) {
      slots.push({
        timeSlot: item.timeSlot,
        timeSlotDesc: slotText
      })
    }
  })
  
  // 转换为数组格式，并按场次排序
  return Array.from(groups.entries()).map(([date, slots]) => ({
    date,
    slots: slots.sort((a, b) => a.timeSlot - b.timeSlot)
  }))
})

const primaryStaff = computed(() => {
  return staffList.value[0] || { name: '服务人员', avatar: '/static/images/default-avatar.png' }
})

const staffSummary = computed(() => {
  if (staffList.value.length <= 1) {
    return primaryStaff.value.name
  }
  return `${primaryStaff.value.name} 等${staffList.value.length}人`
})

const packageSummary = computed(() => {
  if (packageList.value.length === 0) return '服务套餐'
  if (packageList.value.length <= 2) return packageList.value.join(' / ')
  return `${packageList.value[0]} 等${packageList.value.length}个套餐`
})

const dateSummary = computed(() => {
  if (dateList.value.length === 0) return '未选择档期'
  if (dateList.value.length <= 2) return dateList.value.join(' / ')
  return `${dateList.value[0]} 等${dateList.value.length}个场次`
})

// 计算状态文本
const statusText = computed(() => {
  return statusConfig[props.order.status]?.text || '未知状态'
})

// 计算状态样式
const statusStyle = computed(() => {
  const config = statusConfig[props.order.status]
  return {
    backgroundColor: config?.bgColor || '#F5F5F5',
    color: config?.color || '#999999'
  }
})

// 获取按钮样式
const getActionButtonStyle = (type: string) => {
  if (type === 'primary') {
    return {
      background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
      borderColor: $theme.primaryColor
    }
  }
  return {
    background: 'transparent',
    borderColor: '#E5E5E5'
  }
}

// 获取按钮文字样式
const getActionTextStyle = (type: string) => {
  if (type === 'primary') {
    return {
      color: $theme.btnColor
    }
  }
  return {
    color: '#666666'
  }
}

// 获取场次标签浅色背景
const getSlotBgColor = (primaryColor: string) => {
  // 将主题色转换为浅色背景（透明度10%）
  return `${primaryColor}1A`
}

// 处理卡片点击
const handleCardClick = () => {
  emit('click', props.order.id)
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
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 24rpx;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);
  transition: all 0.2s ease;
  overflow: hidden;
  
  &:active {
    box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.12);
    transform: translateY(-2rpx);
  }
  
  &__status {
    position: absolute;
    top: 0;
    right: 0;
    padding: 10rpx 20rpx;
    border-radius: 0 16rpx 0 20rpx;
    font-size: 24rpx;
    font-weight: 600;
  }
  
  &__status-text {
    font-size: 24rpx;
    font-weight: 600;
  }
  
  &__header {
    margin-bottom: 24rpx;
    padding-right: 120rpx;
  }

  &__order-info {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
  }

  &__order-sn {
    font-size: 28rpx;
    font-weight: 600;
    color: #333333;
  }

  &__order-time {
    font-size: 24rpx;
    color: #999999;
  }

  &__main {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
    margin-bottom: 24rpx;
  }

  &__staff-section {
    display: flex;
    align-items: center;
    gap: 20rpx;
    padding: 20rpx;
    background: linear-gradient(135deg, #F9FAFB 0%, #F3F4F6 100%);
    border-radius: 12rpx;
  }

  &__avatar {
    width: 96rpx;
    height: 96rpx;
    border-radius: 50%;
    flex-shrink: 0;
    border: 3rpx solid #FFFFFF;
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
  }
  
  &__staff-info {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    flex: 1;
    min-width: 0;
  }

  &__staff-name {
    font-size: 30rpx;
    font-weight: 600;
    color: #333333;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  &__package-name {
    font-size: 26rpx;
    color: #666666;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  &__service-info {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
  }

  &__info-row {
    display: flex;
    align-items: flex-start;
    gap: 12rpx;
  }

  &__info-icon-wrapper {
    width: 28rpx;
    height: 28rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 2rpx;
  }

  &__info-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
  }

  &__info-text {
    font-size: 26rpx;
    color: #666666;
    line-height: 1.5;
  }

  &__info-label {
    font-size: 24rpx;
    color: #999999;
    margin-bottom: 8rpx;
  }

  &__schedule-list {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
  }

  &__schedule-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16rpx 20rpx;
    background: #FFFFFF;
    border-radius: 12rpx;
    border: 2rpx solid #F0F0F0;
    transition: all 0.2s ease;
    gap: 16rpx;
  }

  &__schedule-date {
    display: flex;
    align-items: center;
    gap: 12rpx;
    flex-shrink: 0;
  }

  &__schedule-date-text {
    font-size: 28rpx;
    font-weight: 600;
    color: #333333;
  }

  &__schedule-slots {
    display: flex;
    align-items: center;
    gap: 12rpx;
    flex-wrap: wrap;
  }

  &__schedule-slot {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8rpx 20rpx;
    border-radius: 24rpx;
    font-size: 24rpx;
    font-weight: 600;
    white-space: nowrap;
    border: 1rpx solid;
  }

  &__location-wrapper {
    display: flex;
    align-items: flex-start;
    gap: 12rpx;
    padding: 16rpx 20rpx;
    background: #FAFAFA;
    border-radius: 12rpx;
    border: 1rpx solid #F0F0F0;
  }

  &__location-text {
    flex: 1;
    font-size: 26rpx;
    color: #666666;
    line-height: 1.6;
  }
  
  &__price {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    padding: 20rpx 0;
    border-top: 1rpx solid #E5E5E5;
    margin-bottom: 20rpx;
  }
  
  &__price-left {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
  }
  
  &__price-detail {
    display: flex;
    align-items: center;
    gap: 8rpx;
  }
  
  &__price-label {
    font-size: 24rpx;
    color: #999999;
  }
  
  &__price-original {
    font-size: 26rpx;
    color: #999999;
    text-decoration: line-through;
  }
  
  &__price-discount {
    font-size: 26rpx;
    color: #FF9900;
    font-weight: 600;
  }
  
  &__price-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4rpx;
  }
  
  &__price-label-total {
    font-size: 24rpx;
    color: #999999;
  }
  
  &__price-total-wrapper {
    display: flex;
    align-items: baseline;
    gap: 4rpx;
  }
  
  &__price-symbol {
    font-size: 28rpx;
    font-weight: 600;
  }
  
  &__price-total {
    font-size: 48rpx;
    font-weight: 700;
    line-height: 1;
  }
  
  &__actions {
    display: flex;
    gap: 16rpx;
    justify-content: flex-end;
  }
  
  &__action-btn {
    padding: 18rpx 36rpx;
    border-radius: 56rpx;
    border: 2rpx solid #E5E5E5;
    transition: all 0.2s ease;
    
    &--primary {
      box-shadow: 0 8rpx 20rpx rgba(124, 58, 237, 0.3);
      border: none;
    }
    
    &:active {
      transform: translateY(1rpx);
      opacity: 0.9;
    }
  }
  
  &__action-text {
    font-size: 28rpx;
    font-weight: 600;
  }
}
</style>
