<template>
    <view class="bbar" :class="{ 'bbar--static': !fixed }">
        <!--
            附加行：协议勾选 / 说明文案等（可选）。
            ⚠ 这里不能写 v-if="$slots.note" —— mp-weixin 下 uni-app 把 initSlots 整段移除
            （@dcloudio/uni-mp-vue 的 setupComponent 里连 vnode.children 都不解构），
            $slots 恒为空对象，条件永远为假，#note 内容会**静默消失**。
            本行带 padding 和背景，空内容也不会自然为 0，所以也不能靠 CSS 撑开。
            → 有内容请显式传 :has-note="true"。
        -->
        <view v-if="hasNote" class="bbar__note">
            <slot name="note" />
        </view>
        <view class="bbar__row">
            <view class="bbar__left">
                <!-- 左侧：合计金额 / 已选摘要，默认插槽 -->
                <slot />
            </view>
            <view class="bbar__actions">
                <slot name="actions" />
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 吸底操作栏（24 屏里 14 屏用到：预约、支付、详情、售后、问卷…）。
 *
 * 契约：
 *  - 只负责「固定在底部 + 安全区 + 左右分栏」，不内含按钮文案/业务动作，按钮由页面用 base-button 放在 actions 槽；
 *  - 高度写入 tokens.css 的 --bottom-bar-h，页面容器的留白由 page-shell 的 bottomBar 属性负责，
 *    页面自己再写 padding-bottom 就是重复实现；
 *  - **带 note 行（协议勾选 / 说明文案）时必须显式传 `has-note`**，同时把预留高度调大：
 *    给 page-shell 传 :style="{ '--bottom-bar-h': '176rpx' }"。
 *    原因见模板注释：mp-weixin 下 `$slots` 恒为空，组件无法探测 #note 是否存在；
 *    这一行有 padding 和背景，空内容也不会塌成 0，所以只能由调用方显式声明。
 *  - 一个页面只允许一个吸底栏（小程序没有真正的层叠上下文管理，两个会互相遮挡）。
 */
withDefaults(defineProps<{ fixed?: boolean; hasNote?: boolean }>(), {
    fixed: true,
    hasNote: false
})
</script>

<style lang="scss" scoped>
.bbar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 90; /* < nav-bar 的 99，> 内容；永远低于 toast(1000) 与弹层 */
    background: var(--c-surface);
    border-top: 1rpx solid var(--c-line-strong);
    padding: var(--sp-2) var(--sp-4) 0;
    padding-bottom: calc(var(--sp-2) + var(--safe-bottom));
    box-sizing: border-box;

    &--static {
        position: static;
        padding-bottom: var(--sp-2);
    }

    &__note {
        padding-bottom: var(--sp-1);
    }

    &__row {
        min-height: 108rpx; /* 设计稿 56px；与 --bottom-bar-h(128rpx-20rpx) 对齐 */
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-3);
    }

    &__left {
        flex: 1;
        min-width: 0;
    }

    /* 动作区最多两个按钮：次要动作在左、主操作在右（设计稿统一「主操作贴右下角」） */
    &__actions {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
        flex-shrink: 0;
    }
}
</style>
