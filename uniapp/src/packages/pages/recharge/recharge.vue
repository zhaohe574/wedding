<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="充值" />
        <view class="recharge-page wm-page-content">
            <view class="wm-page-stack">
                <BaseCard variant="hero" scene="consumer">
                    <text class="recharge-page__eyebrow">Wallet Recharge</text>
                    <text class="recharge-page__title">为婚礼预算账户补充余额</text>
                    <text class="recharge-page__desc">
                        当前可用余额
                        <text class="recharge-page__balance"
                            >¥ {{ wallet.user_money || '0.00' }}</text
                        >
                    </text>
                </BaseCard>

                <BaseCard variant="surface" scene="consumer" class="recharge-page__form-card">
                    <text class="recharge-page__label">充值金额</text>
                    <view class="recharge-page__amount-box">
                        <text class="recharge-page__currency">¥</text>
                        <input
                            v-model="money"
                            class="recharge-page__amount-input"
                            placeholder="0.00"
                            type="digit"
                        />
                    </view>
                    <text class="recharge-page__hint">
                        {{
                            wallet.min_amount
                                ? `最低充值金额 ¥${wallet.min_amount}`
                                : '请输入需要充值的金额'
                        }}
                    </text>
                </BaseCard>

                <view class="wm-page-actions-bar">
                    <BaseButton
                        variant="primary"
                        size="lg"
                        block
                        :loading="isLock"
                        @click="rechargeLock"
                    >
                        立即充值
                    </BaseButton>
                </view>

                <navigator
                    class="recharge-page__record-link"
                    url="/packages/pages/recharge_record/recharge_record"
                    hover-class="none"
                >
                    查看充值记录
                </navigator>
            </view>

            <payment
                v-model:show="payState.showPay"
                v-model:show-check="payState.showCheck"
                :order-id="payState.orderId"
                :from="payState.from"
                :redirect="payState.redirect"
                @success="handlePaySuccess"
                @fail="handlePayFail"
            />
        </view>
    </PageShell>
</template>

<script lang="ts" setup>
import { recharge, rechargeConfig } from '@/api/recharge'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useLockFn } from '@/hooks/useLockFn'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { reactive, ref } from 'vue'

const money = ref('')

const payState = reactive({
    orderId: 0,
    from: '',
    showPay: false,
    showCheck: false,
    redirect: '/packages/pages/recharge/recharge'
})
const wallet = reactive({
    user_money: '',
    min_amount: 0
})

const { isLock, lockFn: rechargeLock } = useLockFn(async () => {
    const minNum = wallet.min_amount
    if (!money.value) return uni.$u.toast('请输入充值金额')
    if (minNum === 0 && Number(money.value) === minNum) {
        return uni.$u.toast('充值金额必须大于0')
    }
    if (Number(money.value) < minNum) return uni.$u.toast(`最低充值金额${minNum}`)
    const data = await recharge({
        money: money.value
    })
    payState.orderId = data.order_id
    payState.from = data.from
    payState.showPay = true
})

const handlePaySuccess = async () => {
    payState.showPay = false
    payState.showCheck = false
    uni.navigateTo({
        url: `/pages/payment_result/payment_result?id=${payState.orderId}&from=${payState.from}`
    })
}

const handlePayFail = async () => {
    uni.$u.toast('支付失败')
}

const getWallet = async () => {
    const data = await rechargeConfig()
    Object.assign(wallet, data)
}

onLoad((options: any) => {
    if (options?.checkPay) {
        payState.orderId = options.id
        payState.from = options.from
        payState.showCheck = true
    }
})

onShow(() => {
    getWallet()
})
</script>

<style lang="scss" scoped>
.recharge-page {
    padding-top: 20rpx;
}

.recharge-page__eyebrow {
    font-size: 22rpx;
    font-weight: 600;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: var(--wm-color-primary, #e85a4f);
}

.recharge-page__title {
    margin-top: 10rpx;
    display: block;
    font-size: 38rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.recharge-page__desc {
    margin-top: 14rpx;
    display: block;
    font-size: 24rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #7f7b78);
}

.recharge-page__balance {
    color: var(--wm-color-primary, #e85a4f);
    font-weight: 700;
}

.recharge-page__form-card {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.recharge-page__label {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);
}

.recharge-page__amount-box {
    display: flex;
    align-items: center;
    gap: 12rpx;
    padding: 8rpx 0 20rpx;
    border-bottom: 1rpx solid var(--wm-color-border, #efe6e1);
}

.recharge-page__currency {
    font-size: 48rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.recharge-page__amount-input {
    flex: 1;
    min-width: 0;
    font-size: 60rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.recharge-page__hint {
    font-size: 22rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.recharge-page__record-link {
    align-self: center;
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-color-primary, #e85a4f);
    padding: 12rpx 0 32rpx;
}
</style>
