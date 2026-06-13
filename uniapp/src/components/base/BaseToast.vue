<template>
    <view v-if="show" class="base-toast" :class="`base-toast--${tone}`">
        <BaseIcon :name="iconName" size="30" color="var(--wm-color-champagne, #D9BE82)" />
        <text class="base-toast__text">{{ message }}</text>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseIcon from './BaseIcon.vue'

interface Props {
    show?: boolean
    message: string
    tone?: 'success' | 'warning' | 'danger' | 'info'
}

const props = withDefaults(defineProps<Props>(), {
    show: true,
    tone: 'success'
})

const iconName = computed(() => {
    if (props.tone === 'success') return 'success'
    if (props.tone === 'danger') return 'warning'
    if (props.tone === 'warning') return 'notice'
    return 'tips'
})
</script>

<script lang="ts">
export default {
    name: 'BaseToast',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-toast {
    display: inline-flex;
    align-items: center;
    gap: 14rpx;
    min-height: 96rpx;
    max-width: 540rpx;
    padding: 0 30rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: var(--wm-color-primary, #191713);
    border: 1rpx solid var(--wm-color-champagne, #D9BE82);
    box-shadow: var(--wm-shadow-action, 0 20rpx 44rpx rgba(74, 43, 24, 0.18));

    &__text {
        min-width: 0;
        font-size: 26rpx;
        font-weight: 900;
        color: var(--wm-text-inverse, #FFFDF8);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}
</style>
