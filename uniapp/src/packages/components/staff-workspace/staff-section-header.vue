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
    align-items: flex-start;
    justify-content: space-between;
    gap: 18rpx;
    padding: 0 4rpx;
}

.staff-section-header__copy {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 4rpx;
}

.staff-section-header__title {
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.35;
    color: var(--wm-text-primary, #111111);
}

.staff-section-header__desc,
.staff-section-header__meta {
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1.5;
    color: var(--wm-text-secondary, #4a4a4a);
}

.staff-section-header__side {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 14rpx;
    min-height: 42rpx;
}

.staff-section-header__action {
    font-size: 23rpx;
    font-weight: 700;
    line-height: 1.2;
    color: var(--wm-color-primary, #0b0b0b);
}
</style>
