<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="支付结果" />
        <page-status :status="status">
            <template #error>
                <view class="wm-empty-shell">
                    <EmptyState
                        title="订单不存在"
                        description="当前支付记录无法找到对应订单，请返回首页重新查看。"
                        action-text="返回首页"
                        @action="goHome"
                    />
                </view>
            </template>
            <template #default>
                <view class="payment-result wm-page-content">
                    <view class="wm-page-stack">
                        <BaseCard variant="hero" scene="consumer" class="payment-result__hero">
                            <image
                                class="payment-result__image"
                                :src="paymentStatus.image"
                                mode="aspectFit"
                            />
                            <text class="payment-result__status">{{ paymentStatus.text }}</text>
                            <text class="payment-result__amount">
                                ¥ {{ normalizedOrder.order_amount || '0.00' }}
                            </text>
                            <text
                                v-if="normalizedOrder.current_pay_stage_desc"
                                class="payment-result__stage"
                            >
                                {{ normalizedOrder.current_pay_stage_desc }}
                            </text>
                            <text class="payment-result__desc">
                                {{
                                    normalizedPayStatus
                                        ? '款项已入账，请继续查看服务安排。'
                                        : '订单尚未支付，可稍后继续处理。'
                                }}
                            </text>
                        </BaseCard>

                        <BaseCard variant="surface" scene="consumer">
                            <view class="wm-kv-list">
                                <view class="wm-kv-item">
                                    <text class="wm-kv-item__label">订单编号</text>
                                    <text class="wm-kv-item__value">{{
                                        normalizedOrder.order_sn || '-'
                                    }}</text>
                                </view>
                                <view class="wm-kv-item">
                                    <text class="wm-kv-item__label">付款时间</text>
                                    <text class="wm-kv-item__value">{{
                                        normalizedOrder.pay_time || '-'
                                    }}</text>
                                </view>
                                <view class="wm-kv-item">
                                    <text class="wm-kv-item__label">支付方式</text>
                                    <text class="wm-kv-item__value">
                                        {{
                                            normalizedPayStatus
                                                ? normalizedOrder.pay_way || '-'
                                                : '未支付'
                                        }}
                                    </text>
                                </view>
                                <view class="wm-kv-item">
                                    <text class="wm-kv-item__label">订单总额</text>
                                    <text class="wm-kv-item__value"
                                        >¥{{
                                            normalizedOrder.pay_amount ||
                                            normalizedOrder.total_amount ||
                                            normalizedOrder.order_amount ||
                                            '0.00'
                                        }}</text
                                    >
                                </view>
                                <view class="wm-kv-item">
                                    <text class="wm-kv-item__label">已付金额</text>
                                    <text class="wm-kv-item__value"
                                        >¥{{ normalizedOrder.paid_amount || '0.00' }}</text
                                    >
                                </view>
                                <view class="wm-kv-item">
                                    <text class="wm-kv-item__label">待付金额</text>
                                    <text class="wm-kv-item__value"
                                        >¥{{ normalizedOrder.unpaid_amount || '0.00' }}</text
                                    >
                                </view>
                            </view>
                        </BaseCard>

                        <view class="wm-page-actions-bar payment-result__actions">
                            <BaseButton
                                v-if="
                                    pageOptions.from === 'recharge' || pageOptions.from === 'order'
                                "
                                variant="secondary"
                                size="lg"
                                block
                                @click="goOrder"
                            >
                                {{ pageOptions.from === 'recharge' ? '继续充值' : '查看订单' }}
                            </BaseButton>
                            <BaseButton variant="primary" size="lg" block @click="goHome">
                                返回首页
                            </BaseButton>
                        </view>
                    </view>
                </view>
            </template>
        </page-status>
    </PageShell>
</template>

<script lang="ts" setup>
import { getPayResult } from '@/api/pay'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import PageShell from '@/components/base/PageShell.vue'
import { PageStatusEnum } from '@/enums/appEnums'
import { onLoad } from '@dcloudio/uni-app'
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'uniapp-router-next'

const router = useRouter()

const mapStatus = {
    succeed: {
        text: '支付成功',
        image: '/static/images/payment/icon_succeed.png'
    },
    waiting: {
        text: '等待支付',
        image: '/static/images/payment/icon_waiting.png'
    }
}
const status = ref(PageStatusEnum['LOADING'])
const pageOptions = ref({
    id: '',
    from: '',
    payment_sn: ''
})
const orderInfo = reactive<any>({
    order: {}
})
const normalizedOrder = computed(() => orderInfo.order || {})
const normalizedPayStatus = computed(() =>
    Number(orderInfo.pay_status || normalizedOrder.value.pay_status || 0)
)
const paymentStatus = computed(() => {
    const isPaid = normalizedPayStatus.value > 0
    return mapStatus[isPaid ? 'succeed' : 'waiting']
})

const initPageData = () => {
    return new Promise((resolve, reject) => {
        getPayResult({
            order_id: pageOptions.value.id,
            from: pageOptions.value.from,
            payment_sn: pageOptions.value.payment_sn || ''
        })
            .then((data) => {
                Object.assign(orderInfo, data)
                resolve(data)
            })
            .catch((err) => {
                reject(err)
            })
    })
}

const goHome = () => {
    router.reLaunch('/pages/index/index')
}

const goOrder = () => {
    if (pageOptions.value.from === 'recharge') {
        router.navigateBack()
        return
    }
    if (pageOptions.value.from === 'order') {
        const paymentSn = pageOptions.value.payment_sn
            ? `&payment_sn=${pageOptions.value.payment_sn}`
            : ''
        router.replace(`/pages/order_detail/order_detail?id=${pageOptions.value.id}${paymentSn}`)
    }
}

onLoad(async (options: any) => {
    try {
        if (!options.id) throw new Error('订单不存在')
        pageOptions.value = options
        await initPageData()
        status.value = PageStatusEnum['NORMAL']
    } catch (err) {
        console.log(err)
        status.value = PageStatusEnum['ERROR']
    }
})
</script>

<style lang="scss" scoped>
.payment-result {
    padding-top: 20rpx;
}

.payment-result__hero {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16rpx;
    text-align: center;
}

.payment-result__image {
    width: 200rpx;
    height: 200rpx;
}

.payment-result__status {
    font-size: 40rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.payment-result__amount {
    font-size: 48rpx;
    font-weight: 700;
    color: var(--wm-color-primary, #e85a4f);
}

.payment-result__stage {
    font-size: 24rpx;
    color: var(--wm-color-primary, #e85a4f);
    font-weight: 600;
}

.payment-result__desc {
    font-size: 24rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #7f7b78);
}

.payment-result__actions {
    padding-bottom: 24rpx;
}
</style>
