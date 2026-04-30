<template>
    <BaseOverlayMask :show="popupVisible" @close="emit('update:modelValue', false)" />
    <tn-popup
        v-model="popupVisible"
        open-direction="bottom"
        :radius="0"
        safe-area-inset-bottom
        :overlay="false"
        :overlay-closeable="true"
    >
        <view class="aftersale-sheet">
            <view class="aftersale-sheet__head">
                <view class="aftersale-sheet__title-wrap">
                    <text class="aftersale-sheet__title">{{ title }}</text>
                    <text v-if="subtitle" class="aftersale-sheet__subtitle">{{ subtitle }}</text>
                </view>
                <view class="aftersale-sheet__close" @click="emit('update:modelValue', false)">
                    <tn-icon name="close" size="28" color="#9A9388" />
                </view>
            </view>

            <view class="aftersale-sheet__body">
                <slot />
            </view>

            <view v-if="showFooter" class="aftersale-sheet__footer">
                <slot name="footer">
                    <view class="aftersale-sheet__actions">
                        <BaseButton
                            v-if="secondaryText"
                            variant="ghost"
                            size="lg"
                            block
                            @click="handleCancel"
                        >
                            {{ secondaryText }}
                        </BaseButton>
                        <BaseButton
                            variant="primary"
                            size="lg"
                            block
                            :loading="primaryLoading"
                            @click="emit('confirm')"
                        >
                            {{ primaryText }}
                        </BaseButton>
                    </view>
                </slot>
            </view>
        </view>
    </tn-popup>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseButton from '@/components/base/BaseButton.vue'

interface Props {
    modelValue: boolean
    title: string
    subtitle?: string
    primaryText?: string
    secondaryText?: string
    primaryLoading?: boolean
    showFooter?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    subtitle: '',
    primaryText: '确认',
    secondaryText: '',
    primaryLoading: false,
    showFooter: true
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void
    (event: 'confirm'): void
    (event: 'cancel'): void
}>()

const popupVisible = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})

const handleCancel = () => {
    emit('cancel')
    emit('update:modelValue', false)
}
</script>

<style lang="scss" scoped>
@import '../../../../styles/aftersale.scss';

.aftersale-sheet {
    @include aftersale-bottom-sheet;
}

.aftersale-sheet__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18rpx;
}

.aftersale-sheet__title-wrap {
    min-width: 0;
    flex: 1;
}

.aftersale-sheet__title {
    display: block;
    font-size: 34rpx;
    font-weight: 700;
    line-height: 1.3;
    color: var(--wm-text-primary, #111111);
}

.aftersale-sheet__subtitle {
    display: block;
    margin-top: 8rpx;
    font-size: 24rpx;
    line-height: 1.55;
    color: var(--wm-text-secondary, #5f5a50);
}

.aftersale-sheet__close {
    @include aftersale-sheet-close;
}

.aftersale-sheet__body {
    margin-top: 28rpx;
}

.aftersale-sheet__footer {
    margin-top: 30rpx;
}

.aftersale-sheet__actions {
    @include aftersale-action-row(18rpx);
}
</style>
