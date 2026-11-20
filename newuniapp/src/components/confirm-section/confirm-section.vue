<template>
    <!--
        确认页区块外壳：序号 + 标题 + 右侧动作。
        只负责视觉与热区，不判断权限、不决定是否可点 —— action 传空即整块无动作。
    -->
    <view class="csec" :class="{ 'csec--flash': flash }">
        <view class="csec__head">
            <view class="csec__title-wrap">
                <text v-if="index" class="csec__index">{{ index }}</text>
                <text class="csec__title">{{ title }}</text>
            </view>
            <view
                v-if="action"
                class="csec__action"
                hover-class="is-pressed"
                aria-role="button"
                :aria-label="actionAria || action"
                @tap="emit('action')"
            >
                <text class="csec__action-text">{{ action }}</text>
                <tn-icon name="right" :size="24" class="csec__chev" aria-hidden="true" />
            </view>
        </view>
        <view class="csec__body">
            <slot />
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 订单确认页区块（08 屏）。
 *
 * 为什么抽出来：确认页有 6 个结构相同的块（套餐/附加项/联系人/地址/价格/支付），
 * 每块都是「卡片 + 序号 + 标题 + 可选修改入口」。写在页面里会重复 6 遍标题层级，
 * 字号与间距一旦漂移，整页的「清单感」就没了。
 *
 * 契约：
 *  - 只接受文案，不接受图标组件；chevron 固定 right（字体包已确认存在）。
 *  - 动作热区靠 padding + 负 margin 撑到约 88rpx，不改变标题行高度。
 *  - 卡片描边/圆角/底色全部走 token，页面不得覆盖。
 */
withDefaults(
    defineProps<{
        /** 两位序号，如 "01"；不传则不渲染 */
        index?: string
        title: string
        /** 右侧动作文案；为空则整块无动作 */
        action?: string
        /** 读屏文案，缺省用 action */
        actionAria?: string
        /** 校验失败定位时闪烁一次：由页面在 1.5s 后自行关闭 */
        flash?: boolean
    }>(),
    {
        index: '',
        action: '',
        actionAria: '',
        flash: false
    }
)

const emit = defineEmits<{ (e: 'action'): void }>()
</script>

<style lang="scss" scoped>
.csec {
    margin-top: var(--sp-3);
    padding: 30rpx;
    background: var(--c-surface);
    border: 1rpx solid var(--c-line-strong);
    border-radius: var(--r-md);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-bottom: 18rpx;
    }

    &__title-wrap {
        display: flex;
        align-items: baseline;
        gap: 14rpx;
        min-width: 0;
    }

    /* 24rpx 是信息字号下限；序号是装饰但仍需可读，不再用 20rpx */
    &__index {
        flex: none;
        font-family: var(--font-serif);
        font-size: var(--fs-xs);
        letter-spacing: 2rpx;
        color: var(--c-brand-text);
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-ink);
    }

    /* 视觉 24rpx + 24rpx 上下 padding（负 margin 抵消），热区约 78rpx */
    &__action {
        flex: none;
        display: inline-flex;
        align-items: center;
        gap: 2rpx;
        padding: 24rpx 0;
        margin: -24rpx 0;
    }

    &__action-text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__chev {
        color: var(--c-ink-3);
    }

    /*
        校验定位：描边与底色闪 3 次，不用位移，避免整块跳动。
        动画放在组件内部（而不是页面 scoped），保证子组件根节点的样式优先级稳定。
    */
    &--flash {
        animation: csec-flash 1.4s var(--ease-out);
    }
}

@keyframes csec-flash {
    0%,
    100% {
        border-color: var(--c-line-strong);
        background: var(--c-surface);
    }
    15%,
    45%,
    75% {
        border-color: var(--c-danger);
        background: var(--c-danger-bg);
    }
    30%,
    60% {
        border-color: var(--c-line-strong);
        background: var(--c-surface);
    }
}
</style>
