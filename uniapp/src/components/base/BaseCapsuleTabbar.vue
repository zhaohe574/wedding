<template>
    <view class="base-capsule-tabbar">
        <view
            v-for="item in safeItems"
            :key="item.key"
            class="base-capsule-tabbar__item"
            :class="{ 'base-capsule-tabbar__item--active': item.key === modelValue }"
            @click="selectItem(item.key)"
        >
            <BaseIcon
                v-if="resolveItemIcon(item)"
                :name="resolveItemIcon(item)"
                size="34"
                :color="item.key === modelValue ? 'var(--wm-color-champagne, #D9BE82)' : 'var(--wm-text-tertiary, #8A806F)'"
            />
            <text class="base-capsule-tabbar__text">{{ item.label }}</text>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseIcon from './BaseIcon.vue'

interface TabbarItem {
    key: string
    label: string
    icon?: string
}

interface Props {
    modelValue: string
    items: TabbarItem[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
    (event: 'update:modelValue', value: string): void
    (event: 'change', value: string): void
}>()

const selectItem = (value: string) => {
    emit('update:modelValue', value)
    emit('change', value)
}

const safeItems = computed(() => props.items || [])
const resolveItemIcon = (item: TabbarItem) => (typeof item.icon === 'string' ? item.icon.trim() : '')
</script>

<script lang="ts">
export default {
    name: 'BaseCapsuleTabbar',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-capsule-tabbar {
    display: flex;
    align-items: center;
    gap: 8rpx;
    min-height: 144rpx;
    padding: 16rpx;
    border-radius: var(--wm-radius-tabbar-shell, 72rpx);
    background: var(--wm-color-primary, #191713);
    border: 1rpx solid var(--wm-color-champagne, #D9BE82);
    box-shadow: var(--wm-shadow-action, 0 20rpx 44rpx rgba(74, 43, 24, 0.18));

    &__item {
        flex: 1;
        min-width: 0;
        min-height: 112rpx;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6rpx;
        border-radius: var(--wm-radius-tabbar-item, 56rpx);
    }

    &__item--active {
        background: var(--wm-color-gold-soft, #F1E5C8);
    }

    &__text {
        font-size: 22rpx;
        font-weight: 900;
        color: var(--wm-text-tertiary, #8A806F);
    }

    &__item--active &__text {
        color: var(--wm-text-primary, #191713);
    }
}
</style>
