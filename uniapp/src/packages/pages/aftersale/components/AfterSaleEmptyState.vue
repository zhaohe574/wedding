<template>
    <view class="aftersale-empty-state">
        <tn-icon :name="icon" size="104" color="#D9CDC7" />
        <text class="aftersale-empty-state__title">{{ title }}</text>
        <text v-if="description" class="aftersale-empty-state__desc">{{ description }}</text>
    </view>
</template>

<script setup lang="ts">
interface Props {
    icon?: string
    title: string
    description?: string
}

withDefaults(defineProps<Props>(), {
    icon: 'inbox',
    description: ''
})
</script>

<style lang="scss" scoped>
@import '../../../../styles/aftersale.scss';

.aftersale-empty-state {
    @include aftersale-empty-state;
}

.aftersale-empty-state__title {
    margin-top: 22rpx;
    font-size: 32rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.aftersale-empty-state__desc {
    margin-top: 10rpx;
    font-size: 24rpx;
    line-height: 1.65;
    color: var(--wm-text-secondary, #7f7b78);
}
</style>
