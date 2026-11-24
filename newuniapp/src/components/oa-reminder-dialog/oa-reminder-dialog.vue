<template>
    <!--
        服务号提醒选择弹窗（软引导）。
        由 utils/oa-reminder 的 remindBeforeOaAction 触发；页面只负责挂载与注册宿主，
        文案、选择结果、跳转都收敛在 utils 里。

        挂载点：components/page-shell —— 同 privacy-popup，不要挂 App.vue
        （小程序 App 根没有 wxss 输出路径，组件样式会变 0 字节）。
    -->
    <view
        v-if="oaReminderDialog.host === hostId"
        class="oa-reminder"
        @touchmove.stop.prevent
        @tap.self="chooseOaReminder('once')"
    >
        <view class="oa-reminder__card" @tap.stop>
            <view
                class="oa-reminder__close"
                hover-class="is-pressed"
                aria-role="button"
                aria-label="关闭提醒"
                @tap="chooseOaReminder('once')"
            >
                <tn-icon name="close" :size="28" />
            </view>

            <view class="oa-reminder__icon">
                <tn-icon name="notice" :size="40" />
            </view>

            <text class="oa-reminder__title">{{ oaReminderDialog.title }}</text>
            <text class="oa-reminder__content">{{ oaReminderDialog.content }}</text>

            <view
                class="oa-reminder__link"
                hover-class="is-pressed"
                aria-role="button"
                aria-label="查看绑定方法"
                @tap="chooseOaReminder('settings')"
            >
                <text class="oa-reminder__link-text">查看绑定方法</text>
                <tn-icon name="right" :size="22" />
            </view>

            <view class="oa-reminder__actions">
                <view
                    class="oa-reminder__button"
                    hover-class="is-pressed"
                    @tap="chooseOaReminder('once')"
                    >本次跳过</view
                >
                <view
                    class="oa-reminder__button oa-reminder__button--primary"
                    hover-class="is-pressed"
                    @tap="chooseOaReminder('today')"
                    >今天不再提醒</view
                >
            </view>

            <text class="oa-reminder__footer">两种选择都会继续当前操作</text>
        </view>
    </view>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { onBackPress, onHide, onShow, onUnload } from '@dcloudio/uni-app'
import {
    chooseOaReminder,
    oaReminderDialog,
    registerOaReminderHost,
    unregisterOaReminderHost
} from '@/utils/oa-reminder'

/** 每个挂载实例一个宿主 id；一个页面栈里只有归属页会渲染弹窗 */
const hostId = `oa_${Math.random().toString(36).slice(2)}`

const register = () => registerOaReminderHost(hostId)
const unregister = () => unregisterOaReminderHost(hostId)

onMounted(register)
onShow(register)
onHide(unregister)
onUnload(unregister)
onUnmounted(unregister)

// 返回键先关弹窗（视为「本次跳过」），不要把用户直接带离当前业务页
onBackPress(() => {
    if (oaReminderDialog.host !== hostId) return false
    chooseOaReminder('once')
    return true
})
</script>

<style lang="scss" scoped>
.oa-reminder {
    position: fixed;
    inset: 0;
    /* 高于 tabbar(100) 与首页弹窗广告(900)，低于 toast(1000) */
    z-index: 950;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 var(--sp-6);
    background: rgba(24, 22, 20, 0.56);

    &__card {
        position: relative;
        width: 100%;
        max-width: 600rpx;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: var(--sp-5) var(--sp-4) var(--sp-3);
        border: 1rpx solid var(--c-line);
        border-radius: var(--r-lg);
        background: var(--c-surface);
        box-shadow: var(--shadow-float);
        box-sizing: border-box;
    }

    &__close {
        position: absolute;
        top: var(--sp-1);
        right: var(--sp-1);
        width: 64rpx;
        height: 64rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-pill);
        color: var(--c-ink-3);
    }

    &__icon {
        width: 92rpx;
        height: 92rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: var(--sp-3);
        border-radius: var(--r-md);
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
    }

    &__title {
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-ink);
        text-align: center;
    }

    &__content {
        margin-top: var(--sp-2);
        font-size: var(--fs-md);
        line-height: var(--lh-loose);
        color: var(--c-ink-2);
    }

    &__link {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        padding: var(--sp-3) var(--sp-2);
        color: var(--c-brand-text);
    }

    &__link-text {
        font-size: var(--fs-md);
        color: var(--c-brand-text);
    }

    &__actions {
        width: 100%;
        display: flex;
        gap: var(--sp-2);
    }

    &__button {
        flex: 1;
        height: 88rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-pill);
        background: var(--c-surface-2);
        color: var(--c-ink-2);
        font-size: var(--fs-md);

        &--primary {
            background: var(--c-ink);
            color: var(--c-on-dark);
            font-weight: var(--fw-semibold);
        }
    }

    &__footer {
        margin-top: var(--sp-2);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        text-align: center;
    }
}
</style>
