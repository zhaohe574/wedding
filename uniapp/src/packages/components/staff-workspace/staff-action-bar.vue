<template>
    <ActionArea sticky safeBottom :layout="secondaryText ? 'split' : 'single'">
        <view class="staff-action-bar">
            <view
                v-if="secondaryText"
                class="staff-action-bar__btn staff-action-bar__btn--secondary"
                @click="emit('secondary')"
            >
                <text class="staff-action-bar__btn-text staff-action-bar__btn-text--secondary">
                    {{ secondaryText }}
                </text>
            </view>

            <view
                class="staff-action-bar__btn staff-action-bar__btn--primary"
                :class="{ 'is-disabled': disabled || loading }"
                :style="{ opacity: disabled || loading ? 0.66 : 1 }"
                @click="handlePrimary"
            >
                <tn-icon
                    v-if="loading"
                    name="loading"
                    size="26"
                    color="#ffffff"
                    class="staff-action-bar__loading"
                />
                <text class="staff-action-bar__btn-text">{{ primaryText }}</text>
            </view>
        </view>
    </ActionArea>
</template>

<script setup lang="ts">
import ActionArea from '@/components/base/ActionArea.vue'

interface Props {
    primaryText: string
    secondaryText?: string
    loading?: boolean
    disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    secondaryText: '',
    loading: false,
    disabled: false
})

const emit = defineEmits<{
    (event: 'primary'): void
    (event: 'secondary'): void
}>()

const handlePrimary = () => {
    if (props.loading || props.disabled) return
    emit('primary')
}
</script>

<style lang="scss" scoped>
.staff-action-bar {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 18rpx;
}

.staff-action-bar__btn {
    flex: 1;
    min-height: 82rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    transition: opacity var(--wm-motion-base, 220ms) ease,
        transform var(--wm-motion-base, 220ms) ease;

    &:active {
        transform: translateY(2rpx);
        opacity: 0.9;
    }

    &.is-disabled:active {
        transform: none;
    }
}

.staff-action-bar__btn--primary {
    background: var(--wm-color-primary, #0b0b0b);
}

.staff-action-bar__btn--secondary {
    background: #ffffff;
    border: 1rpx solid rgba(11, 11, 11, 0.16);
}

.staff-action-bar__btn-text {
    font-size: 27rpx;
    font-weight: 700;
    line-height: 1;
    color: var(--wm-text-inverse, #ffffff);
}

.staff-action-bar__btn-text--secondary {
    color: var(--wm-color-primary, #0b0b0b);
}

.staff-action-bar__loading {
    animation: staff-action-rotate 1s linear infinite;
}

@keyframes staff-action-rotate {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
