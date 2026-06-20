<template>
    <AfterSaleBottomSheet
        :model-value="modelValue"
        title="选择关联订单"
        :subtitle="subtitleText"
        :show-footer="false"
        @update:model-value="emit('update:modelValue', $event)"
    >
        <scroll-view
            class="aftersale-order-picker__scroll"
            scroll-y
            enhanced
            @touchmove.stop="stopScrollTouchMove"
        >
            <view v-if="orders.length" class="aftersale-order-picker__list">
                <view
                    v-for="order in orders"
                    :key="order.value"
                    class="aftersale-order-picker__item"
                    :class="{ 'is-selected': isSelected(order) }"
                    @click="selectOrder(order)"
                >
                    <view class="aftersale-order-picker__main">
                        <view class="aftersale-order-picker__head">
                            <text class="aftersale-order-picker__sn text-ellipsis">
                                {{ getDisplay(order).title }}
                            </text>
                            <StatusBadge
                                v-if="getDisplay(order).status"
                                :tone="getDisplay(order).statusTone"
                                size="xs"
                            >
                                {{ getDisplay(order).status }}
                            </StatusBadge>
                        </view>

                        <text
                            v-if="getDisplay(order).subtitle"
                            class="aftersale-order-picker__subtitle text-ellipsis"
                        >
                            {{ getDisplay(order).subtitle }}
                        </text>

                        <text
                            v-if="getDisplay(order).meta"
                            class="aftersale-order-picker__meta text-ellipsis"
                        >
                            {{ getDisplay(order).meta }}
                        </text>
                    </view>

                    <view class="aftersale-order-picker__check">
                        <BaseIcon
                            :name="isSelected(order) ? 'check' : 'right'"
                            size="24"
                            :color="isSelected(order) ? '#D9BE82' : '#9A9388'"
                        />
                    </view>
                </view>
            </view>

            <view v-else class="aftersale-order-picker__empty">
                <BaseIcon name="empty-data" size="56" color="var(--wm-text-tertiary, #9A9388)" />
                <text class="aftersale-order-picker__empty-title">暂无可关联订单</text>
            </view>
        </scroll-view>
    </AfterSaleBottomSheet>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import AfterSaleBottomSheet from './AfterSaleBottomSheet.vue'
import { getOrderDisplayInfo, type OrderOption } from '../shared'

interface Props {
    modelValue: boolean
    orders: OrderOption[]
    selectedValue?: number | string
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    orders: () => [],
    selectedValue: ''
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void
    (event: 'confirm', value: OrderOption): void
}>()

const subtitleText = computed(() => (props.orders.length ? `${props.orders.length} 个可选订单` : ''))

const isSelected = (order: OrderOption) => String(order.value) === String(props.selectedValue || '')
const getDisplay = (order: OrderOption) => getOrderDisplayInfo(order)
const selectOrder = (order: OrderOption) => {
    emit('confirm', order)
    emit('update:modelValue', false)
}

const stopScrollTouchMove = () => {
    return undefined
}
</script>

<style lang="scss" scoped>
.aftersale-order-picker__scroll {
    max-height: 58vh;
    min-height: 220rpx;
}

.aftersale-order-picker__list {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    padding-bottom: 4rpx;
}

.aftersale-order-picker__item {
    display: flex;
    align-items: center;
    gap: 18rpx;
    min-height: 132rpx;
    padding: 20rpx 22rpx;
    border-radius: 28rpx;
    border: 1rpx solid rgba(216, 201, 173, 0.82);
    background: rgba(255, 253, 248, 0.94);
    box-sizing: border-box;
    box-shadow: 0 10rpx 24rpx rgba(74, 43, 24, 0.05);
}

.aftersale-order-picker__item.is-selected {
    border-color: var(--wm-color-champagne, #d9be82);
    background: linear-gradient(180deg, #fffdf8 0%, var(--wm-color-secondary-soft, #f8f3e7) 100%);
}

.aftersale-order-picker__main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.aftersale-order-picker__head {
    display: flex;
    align-items: center;
    gap: 10rpx;
    min-width: 0;
}

.aftersale-order-picker__sn {
    flex: 1;
    min-width: 0;
    font-size: 27rpx;
    line-height: 1.35;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
}

.aftersale-order-picker__subtitle,
.aftersale-order-picker__meta {
    display: block;
    min-width: 0;
    font-size: 22rpx;
    line-height: 1.45;
}

.aftersale-order-picker__subtitle {
    color: var(--wm-text-secondary, #5f5a50);
}

.aftersale-order-picker__meta {
    color: var(--wm-text-tertiary, #9a9388);
}

.aftersale-order-picker__check {
    width: 46rpx;
    height: 46rpx;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    background: rgba(248, 247, 242, 0.92);
}

.aftersale-order-picker__empty {
    min-height: 260rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

.aftersale-order-picker__empty-title {
    font-size: 24rpx;
    font-weight: 800;
}
</style>
