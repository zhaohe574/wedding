<template>
    <scroll-view scroll-x class="aftersale-filter-tabs" :show-scrollbar="false">
        <view class="aftersale-filter-tabs__track">
            <view
                v-for="item in tabs"
                :key="String(item.value)"
                class="aftersale-filter-tabs__item"
                :class="{ 'is-active': modelValue === item.value }"
                @click="handleChange(item.value)"
            >
                <text class="aftersale-filter-tabs__label">{{ item.label }}</text>
                <text
                    v-if="showCount && item.count !== undefined && item.count > 0"
                    class="aftersale-filter-tabs__count"
                >
                    {{ item.count }}
                </text>
            </view>
        </view>
    </scroll-view>
</template>

<script setup lang="ts">
interface TabItem {
    label: string
    value: string | number
    count?: number
}

interface Props {
    modelValue: string | number
    tabs: TabItem[]
    showCount?: boolean
}

withDefaults(defineProps<Props>(), {
    showCount: false
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: string | number): void
    (event: 'change', value: string | number): void
}>()

const handleChange = (value: string | number) => {
    emit('update:modelValue', value)
    emit('change', value)
}
</script>

<style lang="scss" scoped>
@import '../../../../styles/aftersale.scss';

.aftersale-filter-tabs {
    margin: 0 calc(var(--wm-space-page-x, 28rpx) * -1);
    padding: 0 var(--wm-space-page-x, 28rpx);
    width: auto;
}

.aftersale-filter-tabs__track {
    padding: 4rpx 2rpx 8rpx;
    @include aftersale-filter-tabs;
}

.aftersale-filter-tabs__item {
    @include aftersale-filter-item;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;

    &.is-active {
        @include aftersale-filter-item-active;
    }
}

.aftersale-filter-tabs__label {
    font-size: 24rpx;
    font-weight: 800;
    line-height: 1;
}

.aftersale-filter-tabs__count {
    min-width: 32rpx;
    height: 32rpx;
    padding: 0 8rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(25, 23, 19, 0.08);
    font-size: 20rpx;
    font-weight: 800;
    line-height: 32rpx;
    text-align: center;
    color: var(--wm-text-primary, #191713);
}

.is-active .aftersale-filter-tabs__count {
    background: var(--wm-color-champagne, #d9be82);
    color: #191713;
}
</style>
