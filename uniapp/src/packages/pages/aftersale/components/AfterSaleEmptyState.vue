<template>
    <view class="aftersale-empty-state">
        <BaseIcon :name="icon" size="104" color="#D8D3C7" />
        <text class="aftersale-empty-state__title">{{ title }}</text>
        <text v-if="description" class="aftersale-empty-state__desc">{{ description }}</text>
        <view v-if="buttonText" class="aftersale-empty-state__action">
            <BaseButton
                variant="primary"
                size="md"
                height="72rpx"
                font-size="26rpx"
                @click="emit('action')"
            >
                {{ buttonText }}
            </BaseButton>
        </view>
        <slot />
    </view>
</template>

<script setup lang="ts">
import BaseButton from '@/components/base/BaseButton.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'

interface Props {
    icon?: string
    title: string
    description?: string
    buttonText?: string
}

withDefaults(defineProps<Props>(), {
    icon: 'inbox',
    description: '',
    buttonText: ''
})

const emit = defineEmits<{
    (e: 'action'): void
}>()
</script>

<style lang="scss" scoped>
@import '../../../../styles/aftersale.scss';

.aftersale-empty-state {
    @include aftersale-empty-state;
}

.aftersale-empty-state__title {
    margin-top: 22rpx;
    font-size: 30rpx;
    font-weight: 800;
    color: var(--wm-text-primary, #191713);
}

.aftersale-empty-state__desc {
    margin-top: 10rpx;
    font-size: 24rpx;
    line-height: 1.65;
    color: var(--wm-text-secondary, #5f5a50);
}

.aftersale-empty-state__action {
    margin-top: 28rpx;
}
</style>

