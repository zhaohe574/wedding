<template>
    <view class="order-card" @click="handleCardClick">
        <!-- 状态标签 -->
        <view class="order-card__status" :style="statusStyle">
            <text class="order-card__status-text">{{ statusText }}</text>
        </view>

        <!-- 订单抬头 -->
        <view class="order-card__header">
            <view class="order-card__order-info">
                <text class="order-card__eyebrow">服务订单</text>
                <text class="order-card__order-sn">订单号：{{ order.orderNo }}</text>
                <text class="order-card__order-time">{{ order.createTime }}</text>
            </view>
        </view>

        <!-- 人员/套餐信息 -->
        <view class="order-card__main">
            <!-- 人员信息 -->
            <view class="order-card__staff-section">
                <image
                    class="order-card__avatar"
                    :src="primaryStaff.avatar"
                    mode="aspectFill"
                    lazy-load
                />
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
                        <view class="order-card__schedule-list">
                            <view
                                v-for="(group, index) in groupedSchedules"
                                :key="index"
                                class="order-card__schedule-item"
                            >
                                <view class="order-card__schedule-date">
                                    <text class="order-card__schedule-date-text">{{
                                        group.date
                                    }}</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>

                <!-- 服务地点 -->
                <view class="order-card__info-row">
                    <view class="order-card__info-icon-wrapper">
                        <tn-icon name="location" size="28" color="#9A9388" />
                    </view>
                    <view class="order-card__info-content">
                        <view class="order-card__location-wrapper">
                            <text class="order-card__info-label">服务地点</text>
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
                    <text class="order-card__price-symbol" :style="{ color: $theme.ctaColor }"
                        >¥</text
                    >
                    <text class="order-card__price-total" :style="{ color: $theme.ctaColor }">{{
                        order.actualPrice
                    }}</text>
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
        | 'refunding'
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
    (event: 'click', orderId: number): void
    (
        event: 'action',
        action: { text: string; type: string; action: string },
        order: OrderData
    ): void
}>()

// 状态配置（使用设计规范中的状态色）
const statusConfig = {
    pending_confirm: { text: '待确认', color: '#9F7A2E', bgColor: 'rgba(159, 122, 46, 0.12)' },
    pending_pay: { text: '待支付', color: '#0B0B0B', bgColor: 'rgba(11, 11, 11, 0.12)' },
    paid: { text: '待服务', color: '#4F6F5A', bgColor: 'rgba(79, 111, 90, 0.12)' },
    in_service: { text: '服务中', color: '#596A7A', bgColor: 'rgba(89, 106, 122, 0.12)' },
    completed: { text: '已完成', color: '#4F6F5A', bgColor: 'rgba(79, 111, 90, 0.12)' },
    reviewed: { text: '已评价', color: '#4F6F5A', bgColor: 'rgba(79, 111, 90, 0.12)' },
    cancelled: { text: '已取消', color: '#9A9388', bgColor: 'rgba(154, 147, 136, 0.14)' },
    paused: { text: '已暂停', color: '#9F7A2E', bgColor: 'rgba(159, 122, 46, 0.12)' },
    refunding: { text: '退款中', color: '#596A7A', bgColor: 'rgba(89, 106, 122, 0.12)' },
    refunded: { text: '已退款', color: '#8A4B45', bgColor: 'rgba(138, 75, 69, 0.12)' }
}

const staffList = computed(() => {
    const map = new Map<string, { name: string; avatar: string }>()
    props.order.items.forEach((item) => {
        const name = item.staffName || '服务人员'
        if (!map.has(name)) {
            map.set(name, {
                name,
                avatar: item.staffAvatar || '/static/images/user/default_avatar.png'
            })
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

const groupedSchedules = computed(() => {
    const groups = new Map<string, { date: string }>()

    props.order.items.forEach((item) => {
        const date = item.serviceDate || '未选择日期'
        if (!groups.has(date)) {
            groups.set(date, { date })
        }
    })

    return Array.from(groups.values())
})

const primaryStaff = computed(() => {
    return (
        staffList.value[0] || { name: '服务人员', avatar: '/static/images/user/default_avatar.png' }
    )
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

// 计算状态文本
const statusText = computed(() => {
    return statusConfig[props.order.status]?.text || '未知状态'
})

// 计算状态样式
const statusStyle = computed(() => {
    const config = statusConfig[props.order.status]
    return {
        background: config?.bgColor || 'rgba(154, 147, 136, 0.12)',
        color: config?.color || '#9A9388',
        border: `1rpx solid ${config?.color || '#9A9388'}1F`
    }
})

// 获取按钮样式
const getActionButtonStyle = (type: string) => {
    if (type === 'primary') {
        return {
            background: $theme.ctaColor,
            borderColor: $theme.ctaColor,
            boxShadow: `0 14rpx 28rpx rgba(208, 2, 27, 0.14)`
        }
    }
    return {
        background: 'rgba(255,255,255,0.84)',
        borderColor: 'var(--wm-color-border, #E2DED5)'
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
        color: 'var(--wm-text-primary, #111111)'
    }
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
    background: linear-gradient(
        180deg,
        rgba(255, 255, 255, 0.96) 0%,
        rgba(246, 245, 242, 0.96) 100%
    );
    border-radius: var(--wm-radius-card, 16rpx);
    padding: var(--wm-space-card-padding-lg, 24rpx);
    border: 1rpx solid var(--wm-color-border, #e2ded5);
    box-shadow: var(--wm-shadow-soft, 0 8rpx 20rpx rgba(17, 17, 17, 0.05));
    transition: transform var(--wm-motion-base, 220ms) ease,
        box-shadow var(--wm-motion-base, 220ms) ease;
    overflow: hidden;

    &:active {
        box-shadow: var(--wm-shadow-card, 0 12rpx 28rpx rgba(17, 17, 17, 0.07));
        transform: translateY(1rpx) scale(0.995);
    }

    &::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(120deg, rgba(255, 255, 255, 0.08), transparent 32%);
        pointer-events: none;
    }

    &__status {
        position: absolute;
        top: 20rpx;
        right: 20rpx;
        padding: 10rpx 18rpx;
        border-radius: var(--wm-radius-pill, 999rpx);
        font-size: 22rpx;
        font-weight: 600;
    }

    &__status-text {
        font-size: 22rpx;
        font-weight: 600;
    }

    &__header {
        margin-bottom: var(--wm-space-card-padding, 20rpx);
        padding-right: 136rpx;
    }

    &__order-info {
        display: flex;
        flex-direction: column;
        gap: 6rpx;
    }

    &__eyebrow {
        font-size: 20rpx;
        font-weight: 600;
        letter-spacing: 0;
        text-transform: uppercase;
        color: var(--wm-color-secondary, #c8a45d);
    }

    &__order-sn {
        font-size: 30rpx;
        font-weight: 700;
        color: var(--wm-text-primary, #111111);
    }

    &__order-time {
        font-size: 24rpx;
        color: var(--wm-text-secondary, #56524a);
    }

    &__main {
        display: flex;
        flex-direction: column;
        gap: var(--wm-space-section-gap-lg, 16rpx);
        margin-bottom: var(--wm-space-card-padding, 20rpx);
    }

    &__staff-section {
        display: flex;
        align-items: center;
        gap: var(--wm-space-section-gap-lg, 16rpx);
        padding: var(--wm-space-card-padding, 20rpx);
        background: linear-gradient(
            135deg,
            rgba(255, 255, 255, 0.94) 0%,
            rgba(246, 245, 242, 0.98) 100%
        );
        border: 1rpx solid var(--wm-color-border, #e2ded5);
        border-radius: var(--wm-radius-card-soft, 14rpx);
    }

    &__avatar {
        width: 80rpx;
        height: 80rpx;
        border-radius: var(--wm-radius-card-soft, 14rpx);
        flex-shrink: 0;
        border: 2rpx solid rgba(255, 255, 255, 0.9);
        box-shadow: 0 8rpx 18rpx rgba(17, 17, 17, 0.16);
    }

    &__staff-info {
        display: flex;
        flex-direction: column;
        gap: 6rpx;
        flex: 1;
        min-width: 0;
    }

    &__staff-name {
        font-size: 28rpx;
        font-weight: 600;
        color: var(--wm-text-primary, #111111);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__package-name {
        font-size: 24rpx;
        color: var(--wm-text-secondary, #56524a);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__service-info {
        display: flex;
        flex-direction: column;
        gap: 12rpx;
    }

    &__info-row {
        display: flex;
        align-items: flex-start;
        gap: var(--wm-space-section-gap-sm, 12rpx);
    }

    &__info-icon-wrapper {
        width: 26rpx;
        height: 26rpx;
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
        gap: 6rpx;
    }

    &__info-text {
        font-size: 26rpx;
        color: #56524A;
        line-height: 1.5;
    }

    &__info-label {
        font-size: 24rpx;
        color: var(--wm-text-secondary, #56524a);
        margin-bottom: 6rpx;
    }

    &__schedule-list {
        display: flex;
        flex-direction: column;
        gap: var(--wm-space-section-gap-sm, 12rpx);
    }

    &__schedule-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--wm-space-section-gap-lg, 16rpx) var(--wm-space-card-padding, 20rpx);
        background: rgba(255, 255, 255, 0.76);
        border-radius: var(--wm-radius-card-soft, 14rpx);
        border: 1rpx solid rgba(255, 255, 255, 0.72);
        transition: all 0.2s ease;
        gap: 12rpx;
    }

    &__schedule-date {
        display: flex;
        align-items: center;
        gap: var(--wm-space-section-gap-sm, 12rpx);
        flex-shrink: 0;
    }

    &__schedule-date-text {
        font-size: 26rpx;
        font-weight: 600;
        color: var(--wm-text-primary, #111111);
    }

    &__schedule-slots {
        display: flex;
        align-items: center;
        gap: var(--wm-space-section-gap-sm, 12rpx);
        flex-wrap: wrap;
    }

    &__schedule-slot {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6rpx 16rpx;
        border-radius: var(--wm-radius-chip, 20rpx);
        font-size: 24rpx;
        font-weight: 600;
        white-space: nowrap;
        border: 1rpx solid;
    }

    &__location-wrapper {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 8rpx;
        padding: var(--wm-space-section-gap-lg, 16rpx) var(--wm-space-card-padding, 20rpx);
        background: rgba(255, 255, 255, 0.76);
        border-radius: var(--wm-radius-card-soft, 14rpx);
        border: 1rpx solid rgba(255, 255, 255, 0.72);
    }

    &__location-text {
        flex: 1;
        font-size: 26rpx;
        color: var(--wm-text-secondary, #56524a);
        line-height: 1.6;
    }

    &__price {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        padding: var(--wm-space-card-padding, 20rpx) 0 0;
        border-top: 1rpx solid var(--wm-color-border, #e2ded5);
        margin-bottom: var(--wm-space-card-padding, 20rpx);
    }

    &__price-left {
        display: flex;
        flex-direction: column;
        gap: 6rpx;
    }

    &__price-detail {
        display: flex;
        align-items: center;
        gap: 6rpx;
    }

    &__price-label {
        font-size: 24rpx;
        color: var(--wm-text-secondary, #56524a);
    }

    &__price-original {
        font-size: 24rpx;
        color: var(--wm-text-secondary, #56524a);
        text-decoration: line-through;
    }

    &__price-discount {
        font-size: 24rpx;
        color: var(--wm-color-secondary, #c8a45d);
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
        color: var(--wm-text-secondary, #56524a);
    }

    &__price-total-wrapper {
        display: flex;
        align-items: baseline;
        gap: 4rpx;
    }

    &__price-symbol {
        font-size: 26rpx;
        font-weight: 600;
    }

    &__price-total {
        font-size: 44rpx;
        font-weight: 700;
        line-height: 1;
    }

    &__actions {
        display: flex;
        flex-wrap: wrap;
        gap: var(--wm-space-section-gap-sm, 12rpx);
        justify-content: flex-end;
    }

    &__action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 72rpx;
        min-width: 112rpx;
        padding: 0 var(--wm-space-card-padding-lg, 24rpx);
        box-sizing: border-box;
        border-radius: var(--wm-radius-pill, 999rpx);
        border: 1rpx solid var(--wm-color-border, #e2ded5);
        transition: all 0.2s ease;
        flex-shrink: 0;

        &--primary {
            box-shadow: var(--wm-shadow-soft, 0 8rpx 20rpx rgba(17, 17, 17, 0.05));
            border: none;
        }

        &:active {
            transform: translateY(1rpx);
            opacity: 0.9;
        }
    }

    &__action-text {
        display: block;
        font-size: 26rpx;
        font-weight: 600;
        line-height: 1;
        text-align: center;
    }
}
</style>
