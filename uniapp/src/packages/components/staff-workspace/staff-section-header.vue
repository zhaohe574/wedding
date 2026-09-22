<template>
    <view class="staff-section-header">
        <view class="staff-section-header__copy">
            <text class="staff-section-header__title">{{ title }}</text>
            <text v-if="description" class="staff-section-header__desc">{{ description }}</text>
        </view>

        <view v-if="meta || actionText" class="staff-section-header__side">
            <text v-if="meta" class="staff-section-header__meta">{{ meta }}</text>
            <text
                v-if="actionText"
                class="staff-section-header__action"
                @click="emit('action')"
            >
                {{ actionText }}
            </text>
        </view>
    </view>
</template>

<script setup lang="ts">
interface Props {
    title: string
    description?: string
    meta?: string
    actionText?: string
}

withDefaults(defineProps<Props>(), {
    description: '',
    meta: '',
    actionText: ''
})

const emit = defineEmits<{
    (event: 'action'): void
}>()
</script>

<style lang="scss" scoped>
.staff-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
    padding: 6rpx 4rpx;
}

.staff-section-header__copy {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: baseline;
    gap: 12rpx;
    flex-wrap: wrap;
}

.staff-section-header__title {
    font-size: 32rpx;
    font-weight: 800;
    line-height: 1.35;
    color: var(--wm-text-primary, #181614);
    letter-spacing: 0.5rpx;
}

.staff-section-header__desc {
    font-size: 22rpx;
    font-weight: 500;
    line-height: 1.5;
    color: var(--wm-text-tertiary, #8C857B);
}

.staff-section-header__side {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 14rpx;
}

.staff-section-header__meta {
    font-size: 23rpx;
    font-weight: 600;
    line-height: 1.5;
    color: var(--wm-color-gold, #B8954A);
}

.staff-section-header__action {
    font-size: 23rpx;
    font-weight: 700;
    line-height: 1.2;
    color: var(--wm-color-gold, #B8954A);
    padding: 4rpx 12rpx;
    border-radius: 999rpx;
    background: rgba(200, 164, 93, 0.12);
    transition: opacity 0.2s ease;

    &:active {
        opacity: 0.75;
    }
}
</style>
