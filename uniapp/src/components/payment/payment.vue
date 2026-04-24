<template>
    <BaseOverlayMask :show="showPay" :closeable="false" />
    <tn-popup
        v-model="showPay"
        open-direction="bottom"
        safe-area-inset-bottom
        :overlay="false"
        :overlay-closeable="false"
        :radius="14"
        :z-index="20075"
        close-btn
        @close="handleClose"
    >
        <view class="h-[900rpx]">
            <page-status :status="popupStatus" :fixed="false">
                <template #error>
                    <tn-empty text="订单信息错误，无法查询到订单信息" mode="order"></tn-empty>
                </template>
                <template #default>
                    <view class="payment h-full w-full flex flex-col">
                        <view class="header py-[50rpx] flex flex-col items-center">
                            <text v-if="payData.need_pay_label" class="payment-stage-label">
                                {{ payData.need_pay_label }}
                            </text>
                            <price
                                :content="payData.order_amount"
                                mainSize="44rpx"
                                minorSize="40rpx"
                                fontWeight="500"
                                color="#111111"
                            ></price>
                            <view
                                v-if="Number(payData.total_amount || 0) > 0"
                                class="payment-summary"
                            >
                                <text
                                    >总额 ¥{{ Number(payData.total_amount || 0).toFixed(2) }}</text
                                >
                                <text>已付 ¥{{ Number(payData.paid_amount || 0).toFixed(2) }}</text>
                                <text
                                    >待付 ¥{{ Number(payData.unpaid_amount || 0).toFixed(2) }}</text
                                >
                            </view>
                            <view v-if="payData.deposit_remark" class="payment-remark">
                                {{ payData.deposit_remark }}
                            </view>
                            <view v-if="showPayCountdown" class="countdown-tip">
                                <text class="countdown-tip__label">剩余支付时间</text>
                                <text class="countdown-tip__value">{{ payRemainText }}</text>
                            </view>
                        </view>
                        <view class="main flex-1 mx-[20rpx]">
                            <view>
                                <view class="payway-lists">
                                    <tn-radio-group v-model="payWay" class="w-full">
                                        <view
                                            class="p-[20rpx] flex items-center w-full payway-item"
                                            v-for="item in payData.lists"
                                            :key="item.pay_way"
                                            @click="selectPayWay(item.pay_way)"
                                        >
                                            <tn-icon
                                                class="flex-none"
                                                :size="48"
                                                :name="item.icon"
                                            ></tn-icon>
                                            <view class="mx-[16rpx] flex-1">
                                                <view class="payway-item--name flex-1">
                                                    {{ item.name }}
                                                </view>
                                                <view class="text-muted text-xs">{{
                                                    item.extra
                                                }}</view>
                                            </view>

                                            <tn-radio
                                                :active-color="$theme.primaryColor"
                                                class="mr-[-20rpx]"
                                                :label="item.pay_way"
                                            >
                                            </tn-radio>
                                        </view>
                                    </tn-radio-group>
                                </view>
                            </view>
                        </view>

                        <view class="submit-btn p-[20rpx]">
                            <tn-button
                                @click="handlePay"
                                width="100%"
                                height="88rpx"
                                size="lg"
                                shape="round"
                                type="primary"
                                font-size="30rpx"
                                bold
                                :bg-color="$theme.primaryColor"
                                :text-color="fallbackLightTextColor"
                                :loading="isLock"
                                :disabled="isPayDisabled"
                            >
                                {{
                                    isTimeoutLocked
                                        ? '支付已超时'
                                        : payData.need_pay_label || '立即支付'
                                }}
                            </tn-button>
                        </view>
                    </view>
                </template>
            </page-status>
        </view>
    </tn-popup>

    <BaseOverlayMask :show="showCheckPay" :z-index="20084" :closeable="false" />
    <tn-popup
        class="pay-popup"
        v-model="showCheckPay"
        open-direction="center"
        :radius="10"
        :overlay="false"
        :z-index="20085"
        :overlay-closeable="false"
    >
        <view class="content bg-white w-[560rpx] p-[40rpx]">
            <view class="text-2xl font-medium text-center"> 支付确认 </view>
            <view class="pt-[30rpx] pb-[40rpx]">
                <view> 请在微信内完成支付，如果您已支付成功，请点击`已完成支付`按钮 </view>
                <view v-if="showPayCountdown" class="check-popup__countdown">
                    剩余支付时间：{{ payRemainText }}
                </view>
                <view v-else-if="isTimeoutLocked" class="check-popup__timeout">
                    订单支付时间已到，请返回订单详情查看最新状态
                </view>
            </view>
            <view class="flex">
                <view class="flex-1 mr-[20rpx]">
                    <tn-button
                        shape="round"
                        type="primary"
                        plain
                        hover-class="none"
                        :border-color="$theme.primaryColor"
                        :text-color="$theme.primaryColor"
                        :custom-style="{ width: '100%' }"
                        :disabled="isTimeoutLocked"
                        @click="queryPayResult(false)"
                    >
                        重新支付
                    </tn-button>
                </view>
                <view class="flex-1">
                    <tn-button
                        shape="round"
                        type="primary"
                        bold
                        hover-class="none"
                        :bg-color="$theme.primaryColor"
                        :text-color="payPrimaryTextColor"
                        :custom-style="{ width: '100%' }"
                        :disabled="isTimeoutLocked"
                        @click="queryPayResult()"
                    >
                        已完成支付
                    </tn-button>
                </view>
            </view>
        </view>
    </tn-popup>
</template>

<script lang="ts" setup>
import { pay, PayWayEnum } from '@/utils/pay'
import { getPayWay, prepay, getPayResult } from '@/api/pay'
import { computed, onUnmounted, ref, watch } from 'vue'
import { useLockFn } from '@/hooks/useLockFn'
import { series } from '@/utils/util'
import { ClientEnum, PageStatusEnum, PayStatusEnum } from '@/enums/appEnums'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import { client } from '@/utils/client'
import { resolveReadableTextColor } from '@/utils/color'
/*
页面参数 orderId：订单id，from：订单来源
*/

const props = defineProps({
    show: {
        type: Boolean,
        required: true
    },
    showCheck: {
        type: Boolean
    },
    // 订单id
    orderId: {
        type: Number,
        required: true
    },
    //订单来源
    from: {
        type: String,
        required: true
    },
    //h5微信支付回跳路径，一般为拉起支付的页面路径
    redirect: {
        type: String
    },
    // 当前支付流水号，主要用于 H5 回跳后的支付状态确认
    paymentSn: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:showCheck', 'update:show', 'close', 'success', 'fail'])

const $theme = useThemeStore()
const payWay = ref()
const popupStatus = ref(PageStatusEnum.LOADING)
const payData = ref<any>({
    order_amount: '',
    lists: [],
    pay_deadline_time: 0,
    pay_remain_seconds: 0,
    need_pay_label: '',
    total_amount: 0,
    paid_amount: 0,
    unpaid_amount: 0,
    deposit_remark: ''
})
const payCountdownSeconds = ref(0)
const currentPaymentSn = ref('')
let payCountdownTimer: ReturnType<typeof setInterval> | null = null

const showCheckPay = computed({
    get() {
        return props.showCheck
    },
    set(value) {
        emit('update:showCheck', value)
    }
})

const showPay = computed({
    get() {
        return props.show
    },
    set(value) {
        emit('update:show', value)
    }
})

const payPrimaryTextColor = computed(() =>
    resolveReadableTextColor($theme.primaryColor, $theme.btnColor)
)
const showPayCountdown = computed(() => payCountdownSeconds.value > 0)
const payRemainText = computed(() => formatPayRemain(payCountdownSeconds.value))
const isTimeoutLocked = computed(
    () => Number(payData.value?.pay_deadline_time || 0) > 0 && payCountdownSeconds.value <= 0
)
const isPayDisabled = computed(
    () => isLock.value || popupStatus.value !== PageStatusEnum.NORMAL || isTimeoutLocked.value
)

const handleClose = () => {
    showPay.value = false
    emit('close')
}

const clearPayCountdown = () => {
    if (payCountdownTimer) {
        clearInterval(payCountdownTimer)
        payCountdownTimer = null
    }
}

const syncPayCountdown = (seconds: number | string) => {
    clearPayCountdown()
    payCountdownSeconds.value = Math.max(Number(seconds || 0), 0)
    if (payCountdownSeconds.value <= 0) return

    payCountdownTimer = setInterval(() => {
        if (payCountdownSeconds.value > 0) {
            payCountdownSeconds.value -= 1
        }
        if (payCountdownSeconds.value <= 0) {
            clearPayCountdown()
        }
    }, 1000)
}

const formatPayRemain = (seconds: number) => {
    const total = Math.max(seconds, 0)
    const hours = Math.floor(total / 3600)
    const minutes = Math.floor((total % 3600) / 60)
    const remainSeconds = total % 60

    return [hours, minutes, remainSeconds].map((item) => String(item).padStart(2, '0')).join(':')
}

const emitTimeoutResult = (message = '订单支付超时，已自动取消') => {
    clearPayCountdown()
    showPay.value = false
    showCheckPay.value = false
    uni.$u.toast(message)
    emit('fail', { reason: 'timeout', message })
}

const getPayData = async () => {
    popupStatus.value = PageStatusEnum.LOADING
    try {
        payData.value = await getPayWay({
            order_id: props.orderId,
            from: props.from
        })
        syncPayCountdown(payData.value.pay_remain_seconds || 0)
        if (
            Number(payData.value.pay_deadline_time || 0) > 0 &&
            Number(payData.value.pay_remain_seconds || 0) <= 0
        ) {
            emitTimeoutResult()
            return
        }
        popupStatus.value = PageStatusEnum.NORMAL
        const checkPay =
            payData.value.lists.find((item: any) => item.is_default) || payData.value.lists[0]
        payWay.value = checkPay?.pay_way
    } catch (error: any) {
        clearPayCountdown()
        const message = error?.message || '支付信息加载失败'
        if (message.includes('超时')) {
            emitTimeoutResult(message)
            return
        }
        popupStatus.value = PageStatusEnum.ERROR
        uni.$u.toast(message)
    }
}

const userStore = useUserStore()
const selectPayWay = (pay: number) => {
    payWay.value = pay
}
const payment = (() => {
    // 查询是否绑定微信
    const checkIsBindWx = async () => {
        if (
            userStore.userInfo.is_auth == 0 &&
            [ClientEnum.OA_WEIXIN, ClientEnum.MP_WEIXIN].includes(client) &&
            payWay.value == PayWayEnum.WECHAT
        ) {
            const res: any = await uni.showModal({
                title: '温馨提示',
                content: '当前账号未绑定微信，无法完成支付',
                confirmText: '去绑定'
            })
            if (res.confirm) {
                uni.navigateTo({
                    url: '/pages/user_set/user_set'
                })
            }
            return Promise.reject()
        }
    }

    // 调用预支付
    const prepayTask = async () => {
        uni.showLoading({
            title: '正在支付中'
        })
        const data = await prepay({
            order_id: props.orderId,
            from: props.from,
            pay_way: payWay.value,
            redirect: props.redirect
        })
        currentPaymentSn.value = String(data?.payment_sn || data?.pay_sn || data?.sn || '')

        return data
    }

    //拉起支付
    const payTask = async (data: any) => {
        try {
            const res = await pay.payment(data.pay_way, data.config)
            return res
        } catch (error) {
            return Promise.reject(error)
        }
    }
    return series(checkIsBindWx, prepayTask, payTask)
})()
const { isLock, lockFn: handlePay } = useLockFn(async () => {
    try {
        if (isTimeoutLocked.value) {
            emitTimeoutResult()
            return
        }
        const res: PayStatusEnum = await payment()
        handlePayResult(res)
        uni.hideLoading()
    } catch (error) {
        uni.hideLoading()
        console.log(error)
    }
})

const handlePayResult = (status: PayStatusEnum) => {
    switch (status) {
        case PayStatusEnum.SUCCESS:
            emit('success', {
                paymentSn: currentPaymentSn.value || props.paymentSn || ''
            })
            break
        case PayStatusEnum.FAIL:
            emit('fail')
            break
    }
}

const queryPayResult = async (confirm = true) => {
    try {
        const res = await getPayResult({
            order_id: props.orderId,
            from: props.from,
            payment_sn: currentPaymentSn.value || props.paymentSn
        })

        payData.value.pay_deadline_time = Number(res?.order?.pay_deadline_time || 0)
        syncPayCountdown(Number(res?.order?.pay_remain_seconds || 0))

        if (res.pay_status === 0) {
            if (Number(res?.order?.order_status || 0) === 6) {
                emitTimeoutResult('订单已超时自动取消')
                return
            }
            if (confirm == true) {
                uni.$u.toast('您的订单还未支付，请重新支付')
            }
            if (!isTimeoutLocked.value) {
                showPay.value = true
            }
            handlePayResult(PayStatusEnum.FAIL)
        } else {
            if (confirm == false) {
                uni.$u.toast('您的订单已经支付，请勿重新支付')
            }
            handlePayResult(PayStatusEnum.SUCCESS)
        }
        showCheckPay.value = false
    } catch (error: any) {
        const message = error?.message || '支付状态查询失败'
        if (message.includes('超时')) {
            emitTimeoutResult(message)
            return
        }
        uni.$u.toast(message)
    }
}

watch(
    () => [props.show, props.showCheck],
    ([show, showCheck]) => {
        if (show || showCheck) {
            if (!props.orderId) {
                popupStatus.value = PageStatusEnum.ERROR
                return
            }
            getPayData()
        } else {
            clearPayCountdown()
        }
    },
    {
        immediate: true
    }
)

watch(
    () => props.paymentSn,
    (value) => {
        currentPaymentSn.value = String(value || '')
    },
    {
        immediate: true
    }
)

onUnmounted(() => {
    clearPayCountdown()
})
</script>

<style lang="scss" scoped>
.payway-lists {
    .payway-item {
        border-bottom: 1px solid;
        @apply border-page;
    }
}

.payment-stage-label {
    margin-bottom: 16rpx;
    padding: 8rpx 22rpx;
    border-radius: 999rpx;
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-color-primary, #0b0b0b);
    background: var(--wm-color-primary-soft, #f3f2ee);
}

.payment-summary {
    display: flex;
    gap: 16rpx;
    flex-wrap: wrap;
    justify-content: center;
    margin-top: 16rpx;
    font-size: 22rpx;
    color: #5f5a50;
}

.payment-remark {
    margin-top: 16rpx;
    padding: 16rpx 20rpx;
    border-radius: 20rpx;
    background: #F8F7F2;
    color: #5f5a50;
    font-size: 22rpx;
    line-height: 1.6;
}

.countdown-tip {
    margin-top: 20rpx;
    padding: 12rpx 24rpx;
    border-radius: 999rpx;
    background: var(--wm-color-primary-soft, #f3f2ee);
}

.countdown-tip__label {
    font-size: 22rpx;
    color: var(--wm-color-primary, #0b0b0b);
}

.countdown-tip__value {
    margin-left: 12rpx;
    font-size: 28rpx;
    font-weight: 600;
    color: var(--wm-color-primary, #0b0b0b);
}

.check-popup__countdown {
    margin-top: 20rpx;
    font-size: 24rpx;
    color: var(--wm-color-primary, #0b0b0b);
}

.check-popup__timeout {
    margin-top: 20rpx;
    font-size: 24rpx;
    color: var(--wm-color-danger, #5a4433);
}
</style>
