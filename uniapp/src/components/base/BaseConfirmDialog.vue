<template>
    <view v-if="show" class="base-confirm-dialog">
        <BaseOverlayMask :show="show" :z-index="zIndex" @close="emit('cancel')" />
        <view class="base-confirm-dialog__panel" :style="{ zIndex: zIndex + 1 }">
            <view class="base-confirm-dialog__header">
                <text class="base-confirm-dialog__title">{{ title }}</text>
                <BaseIconButton icon="close" size="sm" variant="ghost" @click="emit('cancel')" />
            </view>
            <text v-if="description" class="base-confirm-dialog__description">{{ description }}</text>
            <view class="base-confirm-dialog__actions">
                <BaseButton :label="cancelText" variant="light" size="sm" @click="emit('cancel')" />
                <BaseButton :label="confirmText" variant="dark" size="sm" @click="emit('confirm')" />
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import BaseButton from './BaseButton.vue'
import BaseIconButton from './BaseIconButton.vue'
import BaseOverlayMask from './BaseOverlayMask.vue'

interface Props {
    show: boolean
    title: string
    description?: string
    confirmText?: string
    cancelText?: string
    zIndex?: number
}

withDefaults(defineProps<Props>(), {
    description: '',
    confirmText: '确认',
    cancelText: '取消',
    zIndex: 20074
})

const emit = defineEmits<{
    (event: 'confirm'): void
    (event: 'cancel'): void
}>()
</script>

<script lang="ts">
export default {
    name: 'BaseConfirmDialog',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-confirm-dialog__panel {
    position: fixed;
    left: 48rpx;
    right: 48rpx;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    flex-direction: column;
    gap: 26rpx;
    padding: 40rpx;
    border-radius: var(--wm-radius-popup, 44rpx);
    background: var(--wm-color-bg-card, #FFFDF8);
    border: 1rpx solid var(--wm-color-border, #D8C9AD);
    box-shadow: var(--wm-shadow-floating, 0 24rpx 56rpx rgba(74, 43, 24, 0.16));
}

.base-confirm-dialog__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24rpx;
}

.base-confirm-dialog__title {
    flex: 1;
    min-width: 0;
    font-size: 36rpx;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
}

.base-confirm-dialog__description {
    font-size: 26rpx;
    line-height: 1.55;
    color: var(--wm-text-secondary, #665E52);
}

.base-confirm-dialog__actions {
    display: flex;
    justify-content: flex-end;
    gap: 20rpx;
}
</style>
