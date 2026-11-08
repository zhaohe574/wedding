<template>
    <view class="rp">
        <view class="rp__icon" :class="`rp__icon--${state}`" aria-hidden="true">
            <!-- v1 u-icon → v2 tn-icon：图标名见 iconName -->
            <tn-icon :name="iconName" :size="144" :color="iconColor" />
        </view>
        <text class="rp__title" aria-live="polite">{{ title }}</text>
        <text class="rp__desc">{{ desc }}</text>
        <view class="rp__amount"><slot name="amount" /></view>
        <view
            v-if="state === 'pending' && poll"
            class="rp__poll"
            :aria-live="pollTextVisible ? 'polite' : undefined"
        >
            <u-loading :size="32" :color="iconColor" aria-hidden="true" />
            <text class="rp__poll-text">{{ poll.text }}</text>
        </view>
        <view class="rp__actions">
            <!-- O2+O14 三态矩阵：success 主「查看订单」；pending 不让位主操作；unconfirmed 次「刷新结果」 -->
            <base-button
                v-if="state !== 'pending'"
                :text="FB.ACTION_VIEW_ORDER"
                variant="ink"
                size="lg"
                @click="emit('primary')"
            />
            <base-button
                v-if="state === 'pending'"
                :text="FB.ACTION_VIEW_ORDER"
                variant="ghost"
                size="md"
                @click="emit('secondary')"
            />
            <base-button
                v-if="state === 'unconfirmed'"
                :text="FB.ACTION_RECHECK_RESULT"
                variant="ghost"
                size="md"
                :loading="rechecking"
                @click="emit('secondary')"
            />
            <base-button
                :text="FB.ACTION_HOME"
                variant="text"
                size="sm"
                @click="emit('tertiary')"
            />
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 结果页容器（展示层，O2/O14）。本轮仅 payment-result 复用。
 * 契约：state 为展示语义（success/pending/unconfirmed），不发起支付、不轮询、
 * 不判断支付状态（POLL_INTERVALS/queryPayState/retryPay/goOrder 全归页面，保留不动）；
 * unconfirmed 不出现「失败」字样、不直接给「重新支付」、不用红叉/--c-danger。
 */
import { computed } from 'vue'
import { FB } from '@/constants/feedback'

const props = defineProps<{
    state: 'success' | 'pending' | 'unconfirmed'
    poll?: { current: number; max: number; text: string }
    rechecking?: boolean
}>()

const emit = defineEmits<{ (e: 'primary'): void; (e: 'secondary'): void; (e: 'tertiary'): void }>()

/** v1 u-icon → v2 tn-icon：checkmark-circle-fill → success-circle-fill；clock-fill 同名保留 */
const iconName = computed(() =>
    props.state === 'success' ? 'success-circle-fill' : 'clock-fill'
)
/** O2：success 用 --c-success；pending / unconfirmed 均用 --c-info（原 --c-warning 归位） */
const iconColor = computed(() => (props.state === 'success' ? 'var(--c-success)' : 'var(--c-info)'))
const title = computed(() =>
    props.state === 'success'
        ? FB.PAY_SUCCESS
        : props.state === 'pending'
        ? FB.PAY_PENDING_TITLE
        : FB.PAY_UNCONFIRMED_TITLE
)
const desc = computed(() =>
    props.state === 'success'
        ? FB.PAY_SUCCESS_DESC
        : props.state === 'pending'
        ? FB.PAY_PENDING_DESC
        : FB.PAY_UNCONFIRMED_DESC
)
// 节流：每 2~3 次轮询播报一次，不逐次刷屏
const pollTextVisible = computed(() => !!props.poll && props.poll.current % 3 === 0)
</script>

<style lang="scss" scoped>
.rp {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 120rpx var(--sp-4) calc(var(--sp-5) + var(--safe-bottom));

    &__icon {
        width: 160rpx;
        height: 160rpx;
        border-radius: var(--r-pill);
        display: flex;
        align-items: center;
        justify-content: center;
        /* 去掉 --shadow-float，改 1rpx 同色调描边 */
        &--success {
            background: var(--c-success-bg);
            border: 1rpx solid var(--c-success-border);
        }
        &--pending,
        &--unconfirmed {
            background: var(--c-info-bg);
            border: 1rpx solid var(--c-info-border);
        }
    }

    &__title {
        margin-top: var(--sp-6);
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        line-height: var(--lh-tight);
    }

    &__desc {
        margin-top: var(--sp-2);
        font-size: var(--fs-md);
        color: var(--c-ink-3);
        line-height: var(--lh-body);
        text-align: center;
    }

    &__amount {
        margin-top: var(--sp-5);
    }

    &__poll {
        margin-top: var(--sp-4);
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        padding: var(--sp-2) var(--sp-4);
        border-radius: var(--r-pill);
        background: var(--c-info-bg);
    }

    &__poll-text {
        font-size: var(--fs-md);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
    }

    &__actions {
        margin-top: auto;
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: var(--sp-2);
    }
}
</style>
