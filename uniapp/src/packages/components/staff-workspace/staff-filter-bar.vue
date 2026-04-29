<template>
    <scroll-view scroll-x class="staff-filter-bar" show-scrollbar="false">
        <view class="staff-filter-bar__row">
            <FilterChip
                v-for="item in items"
                :key="String(item.value)"
                scene="staff"
                :selected="isSelected(item.value)"
                @click="selectItem(item.value)"
            >
                <text class="staff-filter-bar__chip-content">
                    <text class="staff-filter-bar__label">{{ item.label }}</text>
                    <text v-if="hasCount(item)" class="staff-filter-bar__count">{{
                        formatCount(item.count)
                    }}</text>
                </text>
            </FilterChip>
        </view>
    </scroll-view>
</template>

<script setup lang="ts">
import FilterChip from '@/components/base/FilterChip.vue'

interface StaffFilterItem {
    label: string
    value: string | number
    count?: number
}

interface Props {
    items: StaffFilterItem[]
    modelValue: string | number
}

const props = defineProps<Props>()

const emit = defineEmits<{
    (event: 'update:modelValue', value: string | number): void
    (event: 'select', value: string | number): void
}>()

const isSelected = (value: string | number) => String(value) === String(props.modelValue)

const hasCount = (item: StaffFilterItem) => typeof item.count === 'number'

const formatCount = (count?: number) => {
    const value = Number(count || 0)

    return value > 99 ? '99+' : String(value)
}

const selectItem = (value: string | number) => {
    emit('update:modelValue', value)
    emit('select', value)
}
</script>

<style lang="scss" scoped>
.staff-filter-bar {
    width: 100%;
    white-space: nowrap;
}

.staff-filter-bar__row {
    display: inline-flex;
    gap: 12rpx;
    padding: 2rpx 0;
}

.staff-filter-bar__chip-content {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    min-width: 0;
}

.staff-filter-bar__label {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.staff-filter-bar__count {
    flex-shrink: 0;
    min-width: 30rpx;
    height: 30rpx;
    padding: 0 8rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(11, 11, 11, 0.08);
    font-size: 19rpx;
    font-weight: 700;
    line-height: 1;
}
</style>
