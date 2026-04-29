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
                    v-if="showCount && item.count !== undefined"
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
    margin: 0 calc(var(--wm-space-page-x, 37rpx) * -1);
    padding: 0 var(--wm-space-page-x, 37rpx);
}

.aftersale-filter-tabs__track {
    padding: 0;
    @include aftersale-filter-tabs;
}

.aftersale-filter-tabs__item {
    @include aftersale-filter-item;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10rpx;

    &.is-active {
        @include aftersale-filter-item-active;
    }
}

.aftersale-filter-tabs__label,
.aftersale-filter-tabs__count {
    font-size: 24rpx;
    line-height: 1.2;
}

.aftersale-filter-tabs__count {
    font-weight: 700;
}
</style>
