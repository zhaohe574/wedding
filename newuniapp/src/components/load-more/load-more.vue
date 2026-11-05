<template>
    <view
        v-if="status !== 'idle'"
        class="lm"
        :class="{ 'lm--error': status === 'error' }"
        :aria-live="status === 'nomore' || status === 'error' ? 'polite' : undefined"
        :aria-role="status === 'error' ? 'button' : undefined"
        :aria-label="status === 'error' ? '加载失败，点击重试' : undefined"
        :hover-class="status === 'error' ? 'is-pressed' : ''"
        @tap="onTap"
    >
        <view v-if="status === 'nomore'" class="lm__line" aria-hidden="true" />
        <u-loading v-if="status === 'loading'" :size="32" color="var(--c-ink-3)" aria-hidden="true" />
        <text class="lm__text">{{ text }}</text>
        <view v-if="status === 'nomore'" class="lm__line" aria-hidden="true" />
    </view>
</template>

<script setup lang="ts">
/**
 * 触底加载更多（四态展示，收编 10 个页面私有 .loadmore__text）。
 * 契约：完全受控（status 由页面 loadMore 传入）、不复用 z-paging/u-loadmore（O10）、
 * 不自行判断 hasMore、不发请求、不改 pageStatus；nomore 不转圈、error 态整行可点重试。
 */
import { computed } from 'vue'
import { FB } from '@/constants/feedback'

const props = defineProps<{ status: 'idle' | 'loading' | 'nomore' | 'error' }>()
const emit = defineEmits<{ (e: 'retry'): void }>()

const text = computed(() => ({
    idle: '',
    loading: FB.LOADING_MORE,
    nomore: FB.NO_MORE,
    error: FB.LOAD_MORE_ERROR
}[props.status]))

function onTap() {
    if (props.status === 'error') emit('retry')
}
</script>

<style lang="scss" scoped>
.lm {
    height: 88rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--sp-2);

    &__line {
        width: 48rpx;
        height: 1rpx;
        background: var(--c-line);
    }

    &__text {
        font-size: var(--fs-xs);
        font-weight: var(--fw-regular);
        color: var(--c-ink-3);
        line-height: var(--lh-none);
    }

    &--error .lm__text {
        color: var(--c-danger);
    }
}
</style>
