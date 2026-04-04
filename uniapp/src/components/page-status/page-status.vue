<template>
    <view
        class="page-status"
        v-if="status !== PageStatusEnum['NORMAL']"
        :class="{ 'page-status--fixed': fixed }"
    >
        <!-- Loading -->
        <template v-if="status === PageStatusEnum['LOADING']">
            <slot name="loading">
                <tn-loading :size="60" mode="flower" />
            </slot>
        </template>
        <!-- Error -->
        <template v-if="status === PageStatusEnum['ERROR']">
            <slot name="error"></slot>
        </template>
        <!-- Empty -->
        <template v-if="status === PageStatusEnum['EMPTY']">
            <slot name="empty"></slot>
        </template>
    </view>
    <template v-else>
        <slot> </slot>
    </template>
</template>

<script lang="ts" setup>
import { PageStatusEnum } from '@/enums/appEnums'

const props = defineProps({
    status: {
        type: String,
        default: PageStatusEnum['LOADING']
    },
    fixed: {
        type: Boolean,
        default: true
    }
})
</script>

<style lang="scss" scoped>
.page-status {
    height: 100%;
    width: 100%;
    min-height: 100%;
    padding: 36rpx 24rpx;
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    background: radial-gradient(circle at top right, rgba(232, 90, 79, 0.08) 0, transparent 30%),
        linear-gradient(
            180deg,
            var(--wm-color-bg-page, #fcfbf9) 0%,
            var(--wm-color-bg-soft, #fff7f4) 100%
        );
    &--fixed {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        z-index: 900;
    }
}
</style>
