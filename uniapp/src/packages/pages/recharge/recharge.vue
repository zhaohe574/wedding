<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="充值" />
        <view class="recharge-page wm-page-content">
            <view class="wm-page-stack">
                <BaseCard variant="surface" scene="consumer" class="recharge-page__balance-card">
                    <text class="recharge-page__balance-label">当前可用余额</text>
                    <text class="recharge-page__balance">¥ {{ wallet.user_money || '0.00' }}</text>
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
                        variant="cta"
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
import { recharge, rechargeConfig } from '@/packages/common/api/recharge'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useLockFn } from '@/hooks/useLockFn'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { reactive, ref } from 'vue'
import { showError } from '@/utils/feedback'

interface RechargePageQuery {
    checkPay?: string | number | boolean
    id?: string | number
    from?: string
}

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
    if (!money.value) return showError('请输入充值金额')
    if (minNum === 0 && Number(money.value) === minNum) {
        return showError('充值金额必须大于0')
    }
    if (Number(money.value) < minNum) return showError(`最低充值金额${minNum}`)
    const data = await recharge({
        money: money.value
    })
    payState.orderId = data.order_id
    payState.from = data.from
    payState.showPay = true
})

const handlePaySuccess = async (payload?: { paymentSn?: string }) => {
    payState.showPay = false
    payState.showCheck = false
    uni.navigateTo({
        url: `/packages/pages/payment_result/payment_result?id=${payState.orderId}&from=${payState.from}${
            payload?.paymentSn ? `&payment_sn=${payload.paymentSn}` : ''
        }`
    })
}

const handlePayFail = async () => {
    showError('支付失败')
}

const getWallet = async () => {
    const data = await rechargeConfig()
    Object.assign(wallet, data)
}

onLoad((options?: RechargePageQuery) => {
    if (options?.checkPay) {
        payState.orderId = Number(options.id || 0)
        payState.from = String(options.from || '')
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

.recharge-page__balance-card {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.recharge-page__balance-label {
    font-size: 24rpx;
    color: var(--wm-text-secondary, #5f5a50);
}

.recharge-page__balance {
    font-size: 34rpx;
    line-height: 1.25;
    font-weight: 700;
    color: var(--wm-text-primary, #111111);
}

.recharge-page__form-card {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.recharge-page__label {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #5f5a50);
}

.recharge-page__amount-box {
    display: flex;
    align-items: center;
    gap: 12rpx;
    padding: 8rpx 0 20rpx;
    border-bottom: 1rpx solid var(--wm-color-border, #e7e2d6);
}

.recharge-page__currency {
    font-size: 48rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #111111);
}

.recharge-page__amount-input {
    flex: 1;
    min-width: 0;
    font-size: 60rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #111111);
}

.recharge-page__hint {
    font-size: 22rpx;
    color: var(--wm-text-secondary, #5f5a50);
}

.recharge-page__record-link {
    align-self: center;
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-color-primary, #0b0b0b);
    padding: 12rpx 0 32rpx;
}
</style>
