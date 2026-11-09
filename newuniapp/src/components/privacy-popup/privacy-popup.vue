<template>
    <!--
        隐私授权弹窗（合规 B1）。
        挂载点：components/page-shell —— **不要挂 App.vue**：
        小程序端 App 根没有 wxss 输出路径，组件样式会变成 0 字节
        （app-toast 当初就是这么丢样式的，47 页共用一份空样式）。

        合规红线（三条都不能破）：
        1. 「同意」必须是**原生 button + open-type 的隐私授权类型** ——
           用户同意状态靠这个 button 被点击来记录，普通 view / 自绘按钮记录不了；
        2. 不能默认勾选、不能把同意做成不可见的默认行为；
        3. 弹窗内必须有可查看的隐私政策入口（走官方 openPrivacyContract）。
    -->
    <tn-popup v-model="visible" open-direction="center" :radius="28">
        <view class="pp">
            <text class="pp__title">隐私保护提示</text>

            <view class="pp__body">
                <!-- 用一句概括表述覆盖全部采集面（图片 / 昵称头像 / 联系人 / 手机号 / 设备信息），
                     不逐项枚举名称、不写具体用途、不编造用途说明 —— 避免枚举口径与后台
                     《隐私保护指引》措辞不一致反而制造新矛盾。后台声明项最终对齐由运维做。 -->
                <text class="pp__text"
                    >为向你提供预约与报名服务，我们需要处理你选择的图片、昵称与头像、联系人信息、手机号及设备信息。请阅读并同意</text
                >
                <text class="pp__link" @tap="openPrivacyPolicy">《格林社隐私政策》</text>
                <text class="pp__text">。</text>
            </view>

            <view class="pp__actions">
                <view class="pp__btn">
                    <!-- 必须 block：base-button 默认 inline-flex，宽度随内容；
                         右侧「同意并继续」是原生 button width:100%，不传 block 两个按钮不等宽 -->
                    <base-button
                        text="暂不同意"
                        variant="ghost"
                        size="lg"
                        block
                        @click="onDisagree"
                    />
                </view>
                <!-- 关键：原生 button + open-type，同意记录由它落地；id 与 resolve 的 buttonId 同源 -->
                <button
                    :id="AGREE_BTN_ID"
                    class="pp__agree pp__btn"
                    open-type="agreePrivacyAuthorization"
                    @agreeprivacyauthorization="onAgree"
                >
                    同意并继续
                </button>
            </view>
        </view>
    </tn-popup>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
    AGREE_BTN_ID,
    privacyVisible,
    agreePrivacy,
    disagreePrivacy,
    openPrivacyPolicy
} from '@/composables/usePrivacyGuard'

/** 与 composable 的可见性双向绑定：tn-popup 走 v-model */
const visible = computed({
    get: () => privacyVisible.value,
    set: (v: boolean) => {
        // 点遮罩 / 关闭 = 拒绝。划走弹窗不能被当成同意，这是审核红线。
        if (!v) disagreePrivacy()
    }
})

/** 原生 button 的 agreeprivacyauthorization 事件：微信此时已记录同意，这里只负责应答队列 */
function onAgree() {
    agreePrivacy()
}

function onDisagree() {
    disagreePrivacy()
}
</script>

<style lang="scss" scoped>
.pp {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding: var(--sp-4);

    &__title {
        font-size: var(--fs-lg);
        font-weight: 500;
        color: var(--c-ink);
    }

    &__body {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
    }

    &__text {
        font-size: var(--fs-md);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
    }

    &__link {
        font-size: var(--fs-md);
        line-height: var(--lh-body);
        color: var(--c-brand-text);
        text-decoration: underline;
    }

    &__actions {
        display: flex;
        gap: var(--sp-2);
        margin-top: var(--sp-2);
    }

    &__btn {
        flex: 1;
        min-width: 0;
    }

    /* 原生 button 重置 + 逐项对齐 base-button size="lg"：
       base-button 的 lg 是 height 104rpx / font-size --fs-lg / padding 0 --sp-5 / 圆角 --r-pill，
       这里必须一项不差，否则弹窗里两个按钮一高一矮。
       小程序 button 的默认描边画在 ::after 上，不重置会多一圈。 */
    &__agree {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 104rpx;
        margin: 0;
        padding: 0 var(--sp-5);
        border: none;
        border-radius: var(--r-pill);
        background: var(--c-ink);
        color: var(--c-on-dark);
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-none);
        box-sizing: border-box;

        &::after {
            border: none;
        }
    }
}
</style>
